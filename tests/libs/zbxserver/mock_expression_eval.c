/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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

#include "zbxmocktest.h"
#include "zbxmockdata.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"

#include "common.h"
#include "zbxserver.h"

zbx_uint64_t	mock_expression_eval_flags(const char *path)
{
	zbx_uint64_t		flags = 0;
	zbx_mock_handle_t	hflags, hflag;
	zbx_mock_error_t	err;
	int			flags_num;

	hflags = zbx_mock_get_parameter_handle(path);
	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(hflags, &hflag))))
	{
		const char	*flag;

		if (ZBX_MOCK_SUCCESS != err || ZBX_MOCK_SUCCESS != (err = zbx_mock_string(hflag, &flag)))
			fail_msg("Cannot read flag #%d: %s", flags_num, zbx_mock_error_string(err));

		if (0 == strcmp(flag, "ZBX_EVAL_PARSE_FUNCTIONID"))
			flags |= ZBX_EVAL_PARSE_FUNCTIONID;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_ITEM_QUERY"))
			flags |= ZBX_EVAL_PARSE_ITEM_QUERY;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_LLD"))
			flags |= ZBX_EVAL_PARSE_LLD;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_CONST_INDEX"))
			flags |= ZBX_EVAL_PARSE_CONST_INDEX;
		else
			fail_msg("Unsupported flag: %s", flag);

		flags_num++;
	}

	return flags;
}
