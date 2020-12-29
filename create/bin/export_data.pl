#!/usr/bin/env perl
#
# Zabbix
# Copyright (C) 2001-2020 Zabbix SIA
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License version 2 as
# published by the Free Software Foundation
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

use strict;
use warnings;

use FindBin;
use lib "$FindBin::RealBin";

use DBI;
use Getopt::Long qw(GetOptionsFromArray);
use Getopt::Long qw(:config no_ignore_case);
use Pod::Usage;
use Text::Table;
use Cwd;

use utf8;
binmode STDOUT, ':encoding(utf8)';

my %OPTS;

sub print_license()
{
	printf "--
-- Zabbix
-- Copyright (C) 2001-2020 Zabbix SIA
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
--

";
}

# increase this if there are tables with foreign keys dependencies that are heavily nested and
# more iterations might be required to meet them
my $MAXIMUM_FOREIGN_KEYS_SCAN_RECHECKS = 5;

sub get_primary_key_and_foreign_keys($$$)
{
	my ($dbh, $dbname, $table) = @_;

	my $rows_ref = db_select($dbh, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA =".
			" '${dbname}' AND TABLE_NAME = '$table' AND COLUMN_KEY = 'PRI';");
	my $key = $rows_ref->[0]->[0];

	$rows_ref = db_select($dbh,"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE".
			" REFERENCED_TABLE_SCHEMA = '${dbname}' AND REFERENCED_TABLE_NAME =".
			" '$table' and TABLE_NAME='$table';");

	return ($key, $rows_ref);
}

sub format_row_line_into_array_of_fields($)
{
	my $fields = shift;

	my @fields_arr = split /,/, $fields;
	@fields_arr = map { "|" . $_ } @fields_arr;
	unshift(@fields_arr, "FIELDS");
	push(@fields_arr, "|");
	return \@fields_arr;
}

sub go_through_row_fields_and_check_if_they_meet_foreign_key_dependencies($$$$$)
{
	my $unprocessed_rows_ref_ref = shift;
	my $processed_rows_ref = shift;
	my $processed_primary_keys = shift;
	my $primary_key_index = shift;
	my $foreign_keys_indexes = shift;

	my @not_ready_rows = ();

	foreach my $row_ref (@$$unprocessed_rows_ref_ref)
	{
		my $row_field_index = -1;
		my $all_foreign_key_dependencies_are_met = 1;
		my $cur_primary_index = 0;

		foreach my $row_field (@$row_ref)
		{
			$row_field_index++;

			if (not defined $row_field)
			{
				next;
			}

			foreach my $foreign_key_index (@$foreign_keys_indexes)
			{
				if ($row_field_index == $foreign_key_index)
				{
					if (not exists($processed_primary_keys->{"$row_field"}))
					{
						$all_foreign_key_dependencies_are_met = 0;
					}

					last;
				}
			}

			if (not $all_foreign_key_dependencies_are_met)
			{
				last;
			}

			if ($row_field_index == $primary_key_index)
			{
				$cur_primary_index = $row_field;
			}
		}

		if ($all_foreign_key_dependencies_are_met)
		{
			$processed_primary_keys->{"$cur_primary_index"} = 1;
			push(@$processed_rows_ref, $row_ref);
		}
		else
		{
			push(@not_ready_rows, $row_ref);
		}
	}

	$$unprocessed_rows_ref_ref = \@not_ready_rows;
}

sub go_through_rows_and_print_them_in_order_to_meet_foreign_keys_deps($$$$$$$)
{
	my $dbh = shift;
	my $table = shift;
	my $fields = shift;
	my $escaped_fields = shift;
	my $sortorder = shift;
	my $primary_key_index = shift;
	my $foreign_keys_indexes = shift;

	my @processed_rows = ();
	my %processed_primary_keys;

	my $fields_arr = format_row_line_into_array_of_fields($fields);
	my $raw_export_rows_ref = db_select($dbh, "select $escaped_fields from $table $sortorder");
	my $unprocessed_rows = $raw_export_rows_ref;

	foreach my $i (0 .. $MAXIMUM_FOREIGN_KEYS_SCAN_RECHECKS)
	{
		if (scalar @$unprocessed_rows == 0)
		{
			last;
		}

		go_through_row_fields_and_check_if_they_meet_foreign_key_dependencies(\$unprocessed_rows,
				\@processed_rows, \%processed_primary_keys, $primary_key_index, $foreign_keys_indexes);
	}

	if (scalar @$unprocessed_rows != 0)
	{
		my $unprocessed_rows_err = "";

		foreach my $row_ref (@$unprocessed_rows)
		{
			format_row_array_into_line(\$row_ref);
			$unprocessed_rows_err = $unprocessed_rows_err . "@$row_ref\n";
		}

		$dbh->disconnect();
		die "Did not manage to meet foreign key dependencies for the following rows:\n" . $unprocessed_rows_err;
	}

	foreach my $row_ref (@processed_rows)
	{
		format_row_array_into_line(\$row_ref);
	}

	print_table($table, $fields_arr, \@processed_rows);
}

sub format_row_array_into_line($)
{
	my $row_ref = shift;

	@$$row_ref = map { if (defined $_) {"|" . $_} else {"|NULL"} } @$$row_ref;
	unshift(@$$row_ref, "ROW");
	push(@$$row_ref, "|");
}

sub print_table($)
{
	my ($table, $fields_arr, $processed_rows) = @_;

	printf "TABLE |$table\n";
	my $tb = Text::Table->new(@$fields_arr);
	$tb->load(@$processed_rows);
	print $tb;
	print "\n";
}

sub update_sortorder($$$)
{
	my ($table, $line, $sortorder) = @_;

	if (($line !~ /(\|[^|]*){7}/))
	{
		return $sortorder;
	}

	my $reftable = $1;

	$reftable =~ s/\||\s+$//g;

	if ($reftable ne $table)
	{
		return $sortorder;
	}

	my $pri_field = "";

	if (($line =~ /(\|[^|]*){1}/))
	{
		$pri_field = $1;
		$pri_field =~ s/\||\s+$//g;
	}

	my $ref_field = "";

	if (($line =~ /(\|[^|]*){8}/))
	{
		$ref_field = $1;
		$ref_field =~ s/\||\s+$//g;
	}

	if ($sortorder eq "")
	{
		$sortorder="order by $table.$pri_field<$table.$ref_field,".
				"$table.$ref_field";
	}
	else
	{
		$sortorder="$sortorder,$table.$pri_field<$table.$ref_field,".
				"$table.$ref_field";
	}

	return $sortorder;
}

sub process_table($$$$)
{
	my ($fh, $dbh, $table, $dbname) = @_;

	my $rows = db_select($dbh, "select count(*) from $table");
	my $key = $rows->[0]->[0];

	if ($key == 0)
	{
		return;
	}

	my $fields = "";
	my $escaped_fields = "";
	my $first_field = "";
	my $sortorder = "";
	my $firstIter = 1;

	my ($primary_key, $foreign_keys) = get_primary_key_and_foreign_keys($dbh, $dbname, $table);
	my $primary_key_index = -1;
	my @foreign_keys_indexes = ();
	my $index = -1;

	while (<$fh>)
	{
		last if ($_ eq "\n");
		next if ($_ =~ /\bZBX_NODATA\b/);
		next unless ($_ =~ /\bFIELD\b/);

		if ($_ =~ m/^FIELD\s*\|(\w+)\s*/)
		{
			my $field = $1;
			my $escaped_field="replace(replace(replace($field,'|','&pipe;'),'\\r\\n','&eol;'),".
					"'\\n','&bsn;') as $field";

			$index++;

			if ($field eq $primary_key)
			{
				$primary_key_index = $index;
			}
			else
			{
				foreach my $x (@$foreign_keys)
				{
					if ($field eq @$x[0])
					{
						push (@foreign_keys_indexes, $index);
						last;
					}
				}
			}

			if ($firstIter)
			{
				$first_field = $field;
				$fields = $fields . "$field";
				$escaped_fields = $escaped_fields . "$escaped_field";
				$firstIter = 0;
			}
			else
			{
				$fields = $fields . ',' . "$field";
				$escaped_fields = $escaped_fields . ',' . "$escaped_field";
			}
		}
		else
		{
			$dbh->disconnect();
			die("unexpected line, cannot extract FIELD from: $_\n");
		}

		$sortorder = update_sortorder($table, $_, $sortorder);
	}

	if ($sortorder eq "")
	{
		$sortorder = "order by $table.$first_field";
	}

	go_through_rows_and_print_them_in_order_to_meet_foreign_keys_deps($dbh, $table, $fields, $escaped_fields,
			$sortorder, $primary_key_index, \@foreign_keys_indexes);

}

sub main()
{
	my $basedir = Cwd::cwd();
	my $schema=$basedir . "/../src/schema.tmpl";

	parse_opts();

	my $host	= opt('h')	? getopt('h')		: die ("--host not specified");
	my $port	= opt('P')	? getopt('P')		: die ("--port not specified");
	my $username	= opt('u')	? getopt('u')		: die ("--user not specified");
	my $password	= opt('p')	? getopt('p')		: die ("--password not specified");
	my $dbname	= opt('dbname')	? getopt('dbname')	: die ("--dbname not specified");
	my $dbflag	= opt('dbflag')	? getopt('dbflag')	: die ("--dbflag not specified");

	open my $fh, '<', $schema or die "Cannot open file: $!\n";

	my $dbh = DBI->connect("DBI:mysql:database=$dbname;host=$host;port=$port", $username, $password,
			{mysql_enable_utf8 => 1}) or die "Cannot connect to MySQL server\n";

	print_license();

	while(<$fh>)
	{
		next unless ($_ =~ /\bTABLE\b/);
		next unless ($_ =~ /\b$dbflag\b/);

		if ($_ =~ m/^TABLE\|(\w+)\|/)
		{
			my $table = $1;

			process_table($fh, $dbh, $table, $dbname);
		}
		else
		{
			$dbh->disconnect();
			die("unexpected line, cannot extract TABLE from: $_\n");
		}
	}

	$dbh->disconnect();
}

sub db_select($;$)
{
	my ($db_handle, $query, $bind_values) = @_;

	my $sth = $db_handle->prepare($query);

	if (defined($bind_values))
	{
		$sth->execute(@{$bind_values});
	}
	else
	{
		$sth->execute();
	}

	my $rows = $sth->fetchall_arrayref();

	return $rows;
}

sub parse_opts()
{
	my $rv = GetOptionsFromArray([@ARGV], \%OPTS, "h=s", "P=n", "u=s", "p=s",
			"dbname=s", "dbflag=s");

	if (!$rv)
	{
		usage(undef, 0);
	}
}

sub opt($)
{
	my $key = shift;

	return exists($OPTS{$key});
}

sub getopt($)
{
	my $key = shift;

	return exists($OPTS{$key}) ? $OPTS{$key} : undef;
}

sub usage($$)
{
	my ($message, $exitval) = @_;

	pod2usage(
		-message => $message,
		-exitval => $exitval,
		-verbose => 2,
		-noperldoc,
		-output  => $exitval == 0 ? \*STDOUT : \*STDERR,
	);
}

################################################################################
# end of script
################################################################################

main();

__END__

=head1 NAME

export_data.pl - generate data file out of existing MySQL database.

=head1 SYNOPSIS

export_data.pl --h <hostname> --P <port> --u <username> --p <password> --dbname <dbname> --dbflag <dbflag>

=head1 OPTIONS

=over 8

=item B<--h> string

Specify hostname

=item B<--P> int

Specify port

=item B<--u> int

Specify username

=item B<--p> int

Specify password

=item B<--dbname> int

Specify dbname

=item B<--dbflag> int

Specify dbflag

=back

=head1 DESCRIPTION

B<This program> will generate data file out of existing MySQL database. Note, this script assumes the primary
keys always come before the foreign keys in the table schema.

=head1 EXAMPLES

perl export_data.pl --h 127.0.0.1 --P 3306 --u zabbix --p useruser --dbname zabbix --dbflag ZBX_DATA > ../src/data.tmpl

perl export_data.pl --h 127.0.0.1 --P 3306 --u zabbix --p useruser --dbname zabbix --dbflag ZBX_TEMPLATE >
   /src/templates.tmpl

perl export_data.pl --h 127.0.0.1 --P 3306 --u zabbix --p useruser --dbname zabbix --dbflag ZBX_DASHBOARD >
   /src/dashboards.tmpl

=cut
