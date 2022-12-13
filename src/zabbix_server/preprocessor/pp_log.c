/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

/* debug logging, remove for release */

#include "pp_log.h"
#include "zbxcommon.h"

static __thread char	name[64];
static __thread int	log_level;

void	pp_log_init(const char *source, int level)
{
	zbx_strlcpy(name, source, sizeof(name));
	log_level = level;
}

static void	pp_log(const char *format, va_list args)
{
	char	buf[MAX_BUFFER_LEN];

	vsnprintf(buf, sizeof(buf), format, args);
	printf("[%s] %s\n", name, buf);
}

void	pp_debugf(const char *format, ...)
{
	if (4 <= log_level)
	{
		va_list	args;

		va_start(args, format);
		pp_log(format, args);
		va_end(args);
	}
}

void	pp_warnf(const char *format, ...)
{
	if (3 <= log_level)
	{
		va_list	args;

		va_start(args, format);
		pp_log(format, args);
		va_end(args);
	}
}

void	pp_infof(const char *format, ...)
{
	if (0 <= log_level)
	{
		va_list	args;

		va_start(args, format);
		pp_log(format, args);
		va_end(args);
	}
}


