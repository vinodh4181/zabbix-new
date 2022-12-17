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

#include "zbxmocktest.h"
#include "zbxmockdata.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"

#include "zbxcommon.h"
#include "zbxjson.h"
#include "zbxcacheconfig.h"
#include "zbxembed.h"
#include "log.h"

#include "../../../src/zabbix_server/preprocessor/item_preproc.h"
#include "../../../src/zabbix_server/preprocessor/pp_execute.h"
#include "../../../src/zabbix_server/preprocessor/pp_item.h"

zbx_es_t	es_engine;

static unsigned char	str_to_preproc_type(const char *str)
{
	if (0 == strcmp(str, "ZBX_PREPROC_MULTIPLIER"))
		return ZBX_PREPROC_MULTIPLIER;
	if (0 == strcmp(str, "ZBX_PREPROC_RTRIM"))
		return ZBX_PREPROC_RTRIM;
	if (0 == strcmp(str, "ZBX_PREPROC_LTRIM"))
		return ZBX_PREPROC_LTRIM;
	if (0 == strcmp(str, "ZBX_PREPROC_TRIM"))
		return ZBX_PREPROC_TRIM;
	if (0 == strcmp(str, "ZBX_PREPROC_REGSUB"))
		return ZBX_PREPROC_REGSUB;
	if (0 == strcmp(str, "ZBX_PREPROC_BOOL2DEC"))
		return ZBX_PREPROC_BOOL2DEC;
	if (0 == strcmp(str, "ZBX_PREPROC_OCT2DEC"))
		return ZBX_PREPROC_OCT2DEC;
	if (0 == strcmp(str, "ZBX_PREPROC_HEX2DEC"))
		return ZBX_PREPROC_HEX2DEC;
	if (0 == strcmp(str, "ZBX_PREPROC_DELTA_VALUE"))
		return ZBX_PREPROC_DELTA_VALUE;
	if (0 == strcmp(str, "ZBX_PREPROC_DELTA_SPEED"))
		return ZBX_PREPROC_DELTA_SPEED;
	if (0 == strcmp(str, "ZBX_PREPROC_XPATH"))
		return ZBX_PREPROC_XPATH;
	if (0 == strcmp(str, "ZBX_PREPROC_JSONPATH"))
		return ZBX_PREPROC_JSONPATH;
	if (0 == strcmp(str, "ZBX_PREPROC_VALIDATE_RANGE"))
		return ZBX_PREPROC_VALIDATE_RANGE;
	if (0 == strcmp(str, "ZBX_PREPROC_VALIDATE_REGEX"))
		return ZBX_PREPROC_VALIDATE_REGEX;
	if (0 == strcmp(str, "ZBX_PREPROC_VALIDATE_NOT_REGEX"))
		return ZBX_PREPROC_VALIDATE_NOT_REGEX;
	if (0 == strcmp(str, "ZBX_PREPROC_ERROR_FIELD_JSON"))
		return ZBX_PREPROC_ERROR_FIELD_JSON;
	if (0 == strcmp(str, "ZBX_PREPROC_ERROR_FIELD_XML"))
		return ZBX_PREPROC_ERROR_FIELD_XML;
	if (0 == strcmp(str, "ZBX_PREPROC_ERROR_FIELD_REGEX"))
		return ZBX_PREPROC_ERROR_FIELD_REGEX;
	if (0 == strcmp(str, "ZBX_PREPROC_THROTTLE_VALUE"))
		return ZBX_PREPROC_THROTTLE_VALUE;
	if (0 == strcmp(str, "ZBX_PREPROC_THROTTLE_TIMED_VALUE"))
		return ZBX_PREPROC_THROTTLE_TIMED_VALUE;
	if (0 == strcmp(str, "ZBX_PREPROC_PROMETHEUS_PATTERN"))
		return ZBX_PREPROC_PROMETHEUS_PATTERN;
	if (0 == strcmp(str, "ZBX_PREPROC_PROMETHEUS_TO_JSON"))
		return ZBX_PREPROC_PROMETHEUS_TO_JSON;
	if (0 == strcmp(str, "ZBX_PREPROC_CSV_TO_JSON"))
		return ZBX_PREPROC_CSV_TO_JSON;
	if (0 == strcmp(str, "ZBX_PREPROC_STR_REPLACE"))
		return ZBX_PREPROC_STR_REPLACE;

	fail_msg("unknow preprocessing step type: %s", str);
	return FAIL;
}

static int	str_to_preproc_error_handler(const char *str)
{
	if (0 == strcmp(str, "ZBX_PREPROC_FAIL_DEFAULT"))
		return ZBX_PREPROC_FAIL_DEFAULT;
	if (0 == strcmp(str, "ZBX_PREPROC_FAIL_DISCARD_VALUE"))
		return ZBX_PREPROC_FAIL_DISCARD_VALUE;
	if (0 == strcmp(str, "ZBX_PREPROC_FAIL_SET_VALUE"))
		return ZBX_PREPROC_FAIL_SET_VALUE;
	if (0 == strcmp(str, "ZBX_PREPROC_FAIL_SET_ERROR"))
		return ZBX_PREPROC_FAIL_SET_ERROR;

	fail_msg("unknow preprocessing error handler: %s", str);
	return FAIL;
}

static void	read_value(const char *path, unsigned char *value_type, zbx_variant_t *value, zbx_timespec_t *ts)
{
	zbx_mock_handle_t	handle;

	handle = zbx_mock_get_parameter_handle(path);
	if (NULL != value_type)
		*value_type = zbx_mock_str_to_value_type(zbx_mock_get_object_member_string(handle, "value_type"));
	zbx_strtime_to_timespec(zbx_mock_get_object_member_string(handle, "time"), ts);
	zbx_variant_set_str(value, zbx_strdup(NULL, zbx_mock_get_object_member_string(handle, "data")));
}

static void	read_history_value(const char *path, zbx_pp_item_preproc_t *preproc)
{
	zbx_mock_handle_t	handle;
	zbx_timespec_t		ts;
	zbx_variant_t		value;

	handle = zbx_mock_get_parameter_handle(path);
	zbx_strtime_to_timespec(zbx_mock_get_object_member_string(handle, "time"), &ts);
	zbx_variant_set_str(&value, zbx_strdup(NULL, zbx_mock_get_object_member_string(handle, "data")));
	zbx_variant_convert(&value, zbx_mock_str_to_variant(zbx_mock_get_object_member_string(handle, "variant")));

	pp_history_add(preproc->history, 0, &value, ts);
}

static zbx_pp_item_preproc_t	*read_preproc(const char *path, unsigned char value_type)
{
	zbx_mock_handle_t	hop, hop_params, herror, herror_params;
	zbx_pp_item_preproc_t	*preproc;

	hop = zbx_mock_get_parameter_handle(path);

	preproc = pp_item_preproc_create(ITEM_TYPE_TRAPPER, value_type, 0);

	preproc->steps_num = 1;
	preproc->steps = (zbx_pp_step_t *)zbx_malloc(NULL, sizeof(zbx_pp_step_t));

	preproc->steps->type = str_to_preproc_type(zbx_mock_get_object_member_string(hop, "type"));

	if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(hop, "params", &hop_params))
		preproc->steps->params = zbx_strdup(NULL, (char *)zbx_mock_get_object_member_string(hop, "params"));
	else
		preproc->steps->params = NULL;

	if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(hop, "error_handler", &herror))
		preproc->steps->error_handler = str_to_preproc_error_handler(zbx_mock_get_object_member_string(hop, "error_handler"));
	else
		preproc->steps->error_handler = ZBX_PREPROC_FAIL_DEFAULT;

	if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(hop, "error_handler_params", &herror_params))
		preproc->steps->error_handler_params = zbx_strdup(NULL,
				(char *)zbx_mock_get_object_member_string(hop, "error_handler_params"));
	else
		preproc->steps->error_handler_params = NULL;

	if (SUCCEED == pp_preproc_has_history(preproc->steps->type))
	{
		preproc->history_num = 1;
		preproc->history = pp_history_create(preproc->history_num);

		if (ZBX_MOCK_SUCCESS == zbx_mock_parameter_exists("in.history"))
			read_history_value("in.history", preproc);
	}

	return preproc;
}

/******************************************************************************
 *                                                                            *
 * Purpose: checks if the preprocessing step is supported based on build      *
 *          configuration or other settings                                   *
 *                                                                            *
 * Parameters: type [IN] the preprocessing step type                          *
 *                                                                            *
 * Return value: SUCCEED - the preprocessing step is supported                *
 *               FAIL    - the preprocessing step is not supported and will   *
 *                         always fail                                        *
 *                                                                            *
 ******************************************************************************/
static int	is_step_supported(int type)
{
	switch (type)
	{
		case ZBX_PREPROC_XPATH:
		case ZBX_PREPROC_ERROR_FIELD_XML:
#ifdef HAVE_LIBXML2
			return SUCCEED;
#else
			return FAIL;
#endif
		default:
			return SUCCEED;
	}
}

void	zbx_mock_test_entry(void **state)
{
	zbx_variant_t			value, history_value, value_out;
	unsigned char			value_type;
	zbx_timespec_t			ts, history_ts, expected_history_ts;
	int				expected_ret;
	char				*error = NULL;
	zbx_pp_context_t		ctx;
	zbx_pp_item_preproc_t		*preproc;

	ZBX_UNUSED(state);

	read_value("in.value", &value_type, &value, &ts);
	preproc = read_preproc("in.step", value_type);

	pp_context_init(&ctx);

	pp_execute(&ctx, preproc, NULL, &value, ts, &value_out);

	zabbix_log(LOG_LEVEL_DEBUG, "Preprocessing result of type %s: %s", zbx_variant_type_desc(&value_out),
			zbx_variant_value_desc(&value_out));

	if (SUCCEED == is_step_supported(preproc->steps->type))
		expected_ret = zbx_mock_str_to_return_code(zbx_mock_get_parameter_string("out.return"));
	else
		expected_ret = FAIL;

	if (ZBX_VARIANT_ERR == value_out.type)
	{
		if (SUCCEED == expected_ret)
			fail_msg("expected success result while got failure");
	}
	else
	{
		if (SUCCEED != expected_ret)
			fail_msg("expected failure result while got success");

		if (SUCCEED == is_step_supported(preproc->steps->type) &&
				ZBX_MOCK_SUCCESS == zbx_mock_parameter_exists("out.error"))
		{
			zbx_mock_assert_str_eq("error message", zbx_mock_get_parameter_string("out.error"), error);
		}
		else
		{
			if (ZBX_MOCK_SUCCESS == zbx_mock_parameter_exists("out.value"))
			{
				if (ZBX_VARIANT_NONE == value_out.type)
					fail_msg("preprocessing result was empty value");

				if (ZBX_VARIANT_DBL == value_out.type)
				{
					zbx_mock_assert_double_eq("processed value",
							atof(zbx_mock_get_parameter_string("out.value")),
							value_out.data.dbl);
				}
				else
				{
					zbx_variant_convert(&value_out, ZBX_VARIANT_STR);
					zbx_mock_assert_str_eq("processed value", zbx_mock_get_parameter_string("out.value"),
							value_out.data.str);
				}
			}
			else
			{
				if (ZBX_VARIANT_NONE != value_out.type)
					fail_msg("expected empty value, but got %s", zbx_variant_value_desc(&value_out));
			}

			pp_history_pop(preproc->history, 0, &history_value, &history_ts);

			if (ZBX_MOCK_SUCCESS == zbx_mock_parameter_exists("out.history"))
			{
				if (ZBX_VARIANT_NONE == history_value.type)
					fail_msg("preprocessing history was empty value");

				zbx_variant_convert(&history_value, ZBX_VARIANT_STR);
				zbx_mock_assert_str_eq("preprocessing step history value",
						zbx_mock_get_parameter_string("out.history.data"), history_value.data.str);

				zbx_strtime_to_timespec(zbx_mock_get_parameter_string("out.history.time"), &expected_history_ts);
				zbx_mock_assert_timespec_eq("preprocessing step history time", &expected_history_ts, &history_ts);
			}
			else
			{
				if (ZBX_VARIANT_NONE != history_value.type)
					fail_msg("expected empty history, but got %s", zbx_variant_value_desc(&history_value));
			}
		}
	}

	zbx_variant_clear(&value);
	zbx_variant_clear(&value_out);
	zbx_variant_clear(&history_value);
	zbx_free(error);

	pp_item_preproc_release(preproc);
	pp_context_destroy(&ctx);

}
