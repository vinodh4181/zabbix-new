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
#include "mock_eval.h"

zbx_uint64_t	mock_compose_flags(const char *path)
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

		if (0 == strcmp(flag, "ZBX_EVAL_COMPOSE_TRIGGER_EXPRESSION"))
			flags |= ZBX_EVAL_COMPOSE_TRIGGER_EXPRESSION;
		else if (0 == strcmp(flag, "ZBX_EVAL_COMPOSE_LLD_EXPRESSION"))
			flags |= ZBX_EVAL_COMPOSE_LLD_EXPRESSION;
		else
			fail_msg("Unsupported flag: %s", flag);

		flags_num++;
	}

	return flags;
}

static void	replace_values(zbx_eval_context_t *ctx, const char *path)
{
	zbx_mock_handle_t	htokens, htoken;
	zbx_mock_error_t	err;

	htokens = zbx_mock_get_parameter_handle(path);

	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(htokens, &htoken))))
	{
		const char	*data, *value;
		int		i;
		size_t		data_len;

		if (ZBX_MOCK_SUCCESS != err)
			fail_msg("cannot read token value");

		data = zbx_mock_get_object_member_string(htoken, "token");
		value = zbx_mock_get_object_member_string(htoken, "value");
		data_len = strlen(data);

		for (i = 0; i < ctx->stack.values_num; i++)
		{
			zbx_eval_token_t	*token = &ctx->stack.values[i];

			if (data_len == token->loc.r - token->loc.l + 1 &&
					0 == memcmp(data, ctx->expression + token->loc.l, data_len))
			{
				token->value = zbx_strdup(NULL, value);
				break;
			}
		}
	}
}

void	zbx_mock_test_entry(void **state)
{
	zbx_eval_context_t	ctx;
	char			*error = NULL, *ret_expression = NULL;
	zbx_uint32_t		flags;
	const char		*exp_expression;

	ZBX_UNUSED(state);

	flags = mock_expression_eval_flags("in.flags");

	if (SUCCEED != zbx_eval_parse_expression(&ctx, zbx_mock_get_parameter_string("in.expression"), flags, &error))
			fail_msg("failed to parse expression: %s", error);

	replace_values(&ctx, "in.replace");

	exp_expression = zbx_mock_get_parameter_string("out.expression");

	flags = mock_compose_flags("in.compose");
	zbx_eval_compose_expression(&ctx, flags, &ret_expression);

	zbx_mock_assert_str_eq("invalid composed expression", exp_expression, ret_expression);

	zbx_free(ret_expression);
	zbx_eval_clean(&ctx);
}
