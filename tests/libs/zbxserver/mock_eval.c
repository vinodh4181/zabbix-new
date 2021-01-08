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

zbx_uint64_t	mock_expression_eval_rules(const char *path)
{
	zbx_uint64_t		rules = 0;
	zbx_mock_handle_t	hrules, hflag;
	zbx_mock_error_t	err;
	int			rules_num;

	hrules = zbx_mock_get_parameter_handle(path);
	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(hrules, &hflag))))
	{
		const char	*flag;

		if (ZBX_MOCK_SUCCESS != err || ZBX_MOCK_SUCCESS != (err = zbx_mock_string(hflag, &flag)))
			fail_msg("Cannot read flag #%d: %s", rules_num, zbx_mock_error_string(err));

		if (0 == strcmp(flag, "ZBX_EVAL_PARSE_FUNCTIONID"))
			rules |= ZBX_EVAL_PARSE_FUNCTIONID;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_FUNCTION"))
			rules |= ZBX_EVAL_PARSE_FUNCTION;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_ITEM_QUERY"))
			rules |= ZBX_EVAL_PARSE_ITEM_QUERY;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_MACRO"))
			rules |= ZBX_EVAL_PARSE_MACRO;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_USERMACRO"))
			rules |= ZBX_EVAL_PARSE_USERMACRO;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_LLDMACRO"))
			rules |= ZBX_EVAL_PARSE_LLDMACRO;
		else if (0 == strcmp(flag, "ZBX_EVAL_PARSE_CONST_INDEX"))
			rules |= ZBX_EVAL_PARSE_CONST_INDEX;
		else if (0 == strcmp(flag, "ZBX_EVAL_COMPOSE_TRIGGER_EXPRESSION"))
			rules |= ZBX_EVAL_COMPOSE_TRIGGER_EXPRESSION;
		else if (0 == strcmp(flag, "ZBX_EVAL_COMPOSE_LLD_EXPRESSION"))
			rules |= ZBX_EVAL_COMPOSE_LLD_EXPRESSION;
		else if (0 == strcmp(flag, "ZBX_EVAL_PROCESS_ERROR"))
			rules |= ZBX_EVAL_PROCESS_ERROR;
		else if (0 == strcmp(flag, "ZBX_EVAL_PROCESS_HISTORY"))
			rules |= ZBX_EVAL_PROCESS_HISTORY;
		else if (0 == strcmp(flag, "ZBX_EVAL_PROCESS_FUNCTIONID"))
			rules |= ZBX_EVAL_PROCESS_FUNCTIONID;
		else
			fail_msg("Unsupported flag: %s", flag);

		rules_num++;
	}

	return rules;
}
