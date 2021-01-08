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

static void	replace_values(zbx_eval_context_t *ctx, const char *path)
{
	zbx_mock_handle_t	htokens, htoken, hdata;
	zbx_mock_error_t	err;

	if (ZBX_MOCK_SUCCESS != zbx_mock_parameter(path, &htokens))
		return;

	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(htokens, &htoken))))
	{
		const char	*data, *value = NULL, *error = NULL;
		int		i;
		size_t		data_len;

		if (ZBX_MOCK_SUCCESS != err)
			fail_msg("cannot read token contents");

		data = zbx_mock_get_object_member_string(htoken, "token");
		if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(htoken, "value", &hdata))
		{
			if (ZBX_MOCK_SUCCESS != zbx_mock_string(hdata, &value))
				fail_msg("invalid token value");
		}
		else if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(htoken, "error", &hdata))
		{
			if (ZBX_MOCK_SUCCESS != zbx_mock_string(hdata, &error))
				fail_msg("invalid token error");
		}
		else
			fail_msg("invalid token contents");

		data_len = strlen(data);

		for (i = 0; i < ctx->stack.values_num; i++)
		{
			zbx_eval_token_t	*token = &ctx->stack.values[i];

			if (data_len == token->loc.r - token->loc.l + 1 &&
					0 == memcmp(data, ctx->expression + token->loc.l, data_len))
			{
				if (NULL != value)
					zbx_variant_set_str(&token->value, zbx_strdup(NULL, value));
				else
					zbx_variant_set_error(&token->value, zbx_strdup(NULL, error));
				break;
			}
		}
	}
}

void	zbx_mock_test_entry(void **state)
{
	zbx_eval_context_t	ctx;
	char			*error = NULL;
	zbx_uint64_t		rules;
	int			expected_ret, returned_ret;
	zbx_variant_t		value;

	ZBX_UNUSED(state);

	rules = mock_expression_eval_rules("in.rules");
	expected_ret = zbx_mock_str_to_return_code(zbx_mock_get_parameter_string("out.result"));

	if (SUCCEED != zbx_eval_parse_expression(&ctx, zbx_mock_get_parameter_string("in.expression"), rules, &error))
	{
		if (SUCCEED != expected_ret)
			return;
		fail_msg("failed to parse expression: %s", error);
	}

	replace_values(&ctx, "in.replace");

	returned_ret = zbx_eval_execute(&ctx, &value, &error);

	if (SUCCEED != returned_ret)
		printf("ERROR: %s\n", error);

	zbx_mock_assert_result_eq("return value", expected_ret, returned_ret);

	if (SUCCEED == expected_ret)
	{
		zbx_mock_assert_str_eq("output value", zbx_mock_get_parameter_string("out.value"),
				zbx_variant_value_desc(&value));
		zbx_variant_clear(&value);
	}

	zbx_eval_clean(&ctx);
}
