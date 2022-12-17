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

#include "pp_execute.h"
#include "pp_cache.h"
#include "pp_error.h"
#include "pp_xml.h"
#include "../db_lengths.h"
#include "log.h"
#include "pp_log.h"
#include "item_preproc.h"
#include "zbxprometheus.h"
#include "zbxxml.h"

static int	pp_execute_multiply(unsigned char value_type, zbx_variant_t *value, const char *params)
{
	char	buffer[MAX_STRING_LEN], *error = NULL, *errmsg = NULL;

	zbx_strlcpy(buffer, params, sizeof(buffer));
	zbx_trim_float(buffer);

	if (FAIL == zbx_is_double(buffer, NULL))
	{
		error = zbx_dsprintf(NULL, "a numerical value is expected or the value is out of range");
	}
	else if (SUCCEED == item_preproc_multiplier_variant(value_type, value, buffer, &errmsg))
	{
		return SUCCEED;
	}
	else
	{
		error = zbx_dsprintf(NULL, "cannot apply multiplier \"%s\" to value of type \"%s\": %s",
			params, zbx_variant_type_desc(value), errmsg);
		zbx_free(errmsg);
	}

	zbx_variant_clear(value);
	zbx_variant_set_error(value, error);

	return FAIL;
}

static const char	*pp_trim_desc(unsigned char type)
{
	switch (type)
	{
		case ZBX_PREPROC_RTRIM:
			return "right ";
		case ZBX_PREPROC_LTRIM:
			return "left ";
		default:
			return "";
	}
}

static int	pp_execute_trim(unsigned char type, zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL, *characters;

	if (SUCCEED == item_preproc_trim(value, type, params, &errmsg))
		return SUCCEED;

	characters = zbx_str_printable_dyn(params);
	zbx_variant_clear(value);
	zbx_variant_set_error(value, zbx_dsprintf(NULL, "cannot perform %strim of \"%s\" for value of type"
			" \"%s\": %s", 	pp_trim_desc(type), characters, zbx_variant_type_desc(value), errmsg));

	zbx_free(characters);
	zbx_free(errmsg);

	return FAIL;
}

static int	pp_check_not_error(const zbx_variant_t *value)
{
	if (ZBX_VARIANT_ERR == value->type)
		return FAIL;

	return SUCCEED;
}

static const char	*pp_delta_desc(unsigned char type)
{
	switch (type)
	{
		case ZBX_PREPROC_DELTA_VALUE:
			return "simple change";
		case ZBX_PREPROC_DELTA_SPEED:
			return "speed per second";
		default:
			return "";
	}
}

static int	pp_execute_delta(unsigned char type, unsigned char value_type, zbx_variant_t *value, zbx_timespec_t ts,
		zbx_variant_t *history_value, zbx_timespec_t *history_ts)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_delta(value_type, value, &ts, type, history_value, history_ts, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, zbx_dsprintf(NULL,  "cannot calculate delta (%s) for value of type"
				" \"%s\": %s", pp_delta_desc(type), zbx_variant_type_desc(value), errmsg));
	zbx_free(errmsg);

	return FAIL;
}

static int	pp_execute_regsub(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL, *ptr;
	int	len;

	if (SUCCEED == item_preproc_regsub_op(value, params, &errmsg))
		return SUCCEED;

	if (NULL == (ptr = strchr(params, '\n')))
		len = strlen(params);
	else
		len = ptr - params;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, zbx_dsprintf(NULL, "cannot perform regular expression \"%.*s\""
			" match for value of type \"%s\": %s",
			len, params, zbx_variant_type_desc(value), errmsg));

	zbx_free(errmsg);

	return FAIL;
}

/******************************************************************************
 *                                                                            *
 * Purpose: execute jsonpath query                                            *
 *                                                                            *
 * Parameters: cache  - [IN] the preprocessing cache                          *
 *             value  - [IN/OUT] the value to process                         *
 *             params - [IN] the operation parameters                         *
 *             errmsg - [OUT] error message                                   *
 *                                                                            *
 * Return value: SUCCEED - the value was processed successfully               *
 *               FAIL - otherwise                                             *
 *                                                                            *
 ******************************************************************************/
static int	pp_excute_jsonpath_step(zbx_pp_cache_t *cache, zbx_variant_t *value, const char *params, char **errmsg)
{
	char	*data = NULL;

	if (NULL == cache || ZBX_PREPROC_JSONPATH != cache->type)
	{
		zbx_jsonobj_t	obj;

		if (FAIL == item_preproc_convert_value(value, ZBX_VARIANT_STR, errmsg))
			return FAIL;

		if (FAIL == zbx_jsonobj_open(value->data.str, &obj))
		{
			*errmsg = zbx_strdup(*errmsg, zbx_json_strerror());
			return FAIL;
		}

		if (FAIL == zbx_jsonobj_query(&obj, params, &data))
		{
			zbx_jsonobj_clear(&obj);
			*errmsg = zbx_strdup(*errmsg, zbx_json_strerror());
			return FAIL;
		}

		zbx_jsonobj_clear(&obj);
	}
	else
	{
		zbx_jsonobj_t	*obj;

		if (NULL == (obj = (zbx_jsonobj_t *)cache->data))
		{
			if (FAIL == item_preproc_convert_value(value, ZBX_VARIANT_STR, errmsg))
				return FAIL;

			obj = (zbx_jsonobj_t *)zbx_malloc(NULL, sizeof(zbx_jsonobj_t));

			if (SUCCEED != zbx_jsonobj_open(value->data.str, obj))
			{
				*errmsg = zbx_strdup(*errmsg, zbx_json_strerror());
				zbx_free(obj);
				return FAIL;
			}

			cache->data = (void *)obj;
		}

		if (FAIL == zbx_jsonobj_query(obj, params, &data))
		{
			*errmsg = zbx_strdup(*errmsg, zbx_json_strerror());
			return FAIL;
		}
	}

	if (NULL == data)
	{
		*errmsg = zbx_strdup(*errmsg, "no data matches the specified path");
		return FAIL;
	}

	zbx_variant_clear(value);
	zbx_variant_set_str(value, data);

	return SUCCEED;
}

static int	pp_execute_jsonpath(zbx_pp_cache_t *cache, zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == pp_excute_jsonpath_step(cache, value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, zbx_dsprintf(NULL, "cannot extract value from json by path \"%s\": %s", params,
			errmsg));

	zbx_free(errmsg);

	return FAIL;
}

static const char	*pp_2dec_desc(unsigned char type)
{
	switch (type)
	{
		case ZBX_PREPROC_BOOL2DEC:
			return "boolean";
		case ZBX_PREPROC_OCT2DEC:
			return "octal";
		case ZBX_PREPROC_HEX2DEC:
			return "hexadecimal";
		default:
			return "";
	}
}

static int	pp_execute_2dec(unsigned char type, zbx_variant_t *value)
{
	char	*errmsg = NULL, *value_desc;

	if (SUCCEED == item_preproc_2dec(value, type, &errmsg))
		return SUCCEED;

	value_desc = zbx_strdup(NULL, zbx_variant_value_desc(value));
	zbx_variant_clear(value);
	zbx_variant_set_error(value, zbx_dsprintf(NULL, "cannot convert value  \"%s\" from %s to decimal format: %s",
			value_desc, pp_2dec_desc(type), errmsg));

	zbx_free(value_desc);
	zbx_free(errmsg);

	return FAIL;
}

static int	pp_execute_xpath_step(zbx_variant_t *value, const char *params, char **error)
{
	char	*errmsg = NULL;

	if (FAIL == item_preproc_convert_value(value, ZBX_VARIANT_STR, error))
		return FAIL;

	if (SUCCEED == zbx_query_xpath(value, params, &errmsg))
		return SUCCEED;

	*error = zbx_dsprintf(NULL, "cannot extract XML value with xpath \"%s\": %s", params, errmsg);
	zbx_free(errmsg);

	return FAIL;
}

static int	pp_execute_xpath(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == pp_execute_xpath_step(value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_validate_range(unsigned char value_type, zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_validate_range(value_type, value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_validate_regex(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_validate_regex(value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_validate_not_regex(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_validate_not_regex(value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_error_from_json(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;
	int	ret;

	ret = item_preproc_get_error_from_json(value, params, &errmsg);

	if (NULL != errmsg)
	{
		zbx_variant_clear(value);
		zbx_variant_set_error(value, errmsg);
	}

	return ret;
}

static int	pp_error_from_xml(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;
	int	ret;

	ret = item_preproc_get_error_from_xml(value, params, &errmsg);

	if (NULL != errmsg)
	{
		zbx_variant_clear(value);
		zbx_variant_set_error(value, errmsg);
	}

	return ret;
}

static int	pp_error_from_regex(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;
	int	ret;

	ret = item_preproc_get_error_from_regex(value, params, &errmsg);

	if (NULL != errmsg)
	{
		zbx_variant_clear(value);
		zbx_variant_set_error(value, errmsg);
	}

	return ret;
}

static int	pp_throttle_timed_value(zbx_variant_t *value, zbx_timespec_t ts, const char *params,
		zbx_variant_t *history_value, zbx_timespec_t *history_ts)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_throttle_timed_value(value, &ts, params, history_value, history_ts, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_execute_script(zbx_pp_context_t *ctx, zbx_variant_t *value, const char *params,
		zbx_variant_t *history_value)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_script(pp_context_es_engine(ctx), value, params, history_value, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

/******************************************************************************
 *                                                                            *
 * Purpose: parse Prometheus format metrics                                   *
 *                                                                            *
 * Parameters: cache  - [IN] the preprocessing cache                          *
 *             value  - [IN/OUT] the value to process                         *
 *             params - [IN] the operation parameters                         *
 *             errmsg - [OUT] error message                                   *
 *                                                                            *
 * Return value: SUCCEED - the value was processed successfully               *
 *               FAIL - otherwise                                             *
 *                                                                            *
 ******************************************************************************/
static int	pp_execute_prometheus_pattern_step(zbx_pp_cache_t *cache, zbx_variant_t *value, const char *params,
		char **errmsg)
{
	char	pattern[ITEM_PREPROC_PARAMS_LEN * ZBX_MAX_BYTES_IN_UTF8_CHAR + 1], *request, *output, *value_out = NULL,
		*err = NULL;
	int	ret = FAIL;

	zbx_strlcpy(pattern, params, sizeof(pattern));

	if (NULL == (request = strchr(pattern, '\n')))
	{
		*errmsg = zbx_strdup(*errmsg, "cannot find second parameter");
		return FAIL;
	}
	*request++ = '\0';

	if (NULL == (output = strchr(request, '\n')))
	{
		*errmsg = zbx_strdup(*errmsg, "cannot find third parameter");
		return FAIL;
	}
	*output++ = '\0';

	if (NULL == cache || ZBX_PREPROC_PROMETHEUS_PATTERN != cache->type)
	{
		if (FAIL == item_preproc_convert_value(value, ZBX_VARIANT_STR, errmsg))
			return FAIL;

		pp_warnf("RAW");
		ret = zbx_prometheus_pattern(value->data.str, pattern, request, output, &value_out, &err);
	}
	else
	{
		zbx_prometheus_t	*prom_cache;

		if (NULL == (prom_cache = (zbx_prometheus_t *)cache->data))
		{
			prom_cache = (zbx_prometheus_t *)zbx_malloc(NULL, sizeof(zbx_prometheus_t));

			if (FAIL == item_preproc_convert_value(value, ZBX_VARIANT_STR, errmsg))
				return FAIL;

			if (SUCCEED != zbx_prometheus_init(prom_cache, value->data.str, &err))
			{
				zbx_free(prom_cache);
				goto out;
			}

			pp_warnf("CACHE...");
			cache->data = (void *)prom_cache;
		}

		pp_warnf("CACHED");
		ret = zbx_prometheus_pattern_ex(prom_cache, pattern, request, output, &value_out, &err);
	}
out:
	if (FAIL == ret)
	{
		*errmsg = zbx_dsprintf(*errmsg, "cannot apply Prometheus pattern: %s", err);
		zbx_free(err);
		return FAIL;
	}

	zbx_variant_clear(value);
	zbx_variant_set_str(value, value_out);

	return SUCCEED;
}

static int	pp_execute_prometheus_pattern(zbx_pp_cache_t *cache, zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == pp_execute_prometheus_pattern_step(cache, value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_execute_prometheus_to_json(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_prometheus_to_json(value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_execute_csv_to_json(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_csv_to_json(value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_execute_xml_to_json(zbx_variant_t *value)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_xml_to_json(value, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_execute_str_replace(zbx_variant_t *value, const char *params)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_str_replace(value, params, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, errmsg);

	return FAIL;
}

static int	pp_execute_step(zbx_pp_context_t *ctx, zbx_pp_cache_t *cache, unsigned char value_type,
		zbx_variant_t *value, zbx_timespec_t ts, zbx_pp_step_t *step, zbx_variant_t *history_value,
		zbx_timespec_t *history_ts)
{
	pp_cache_copy_value(cache, step->type, value);

	switch (step->type)
	{
		case ZBX_PREPROC_MULTIPLIER:
			return pp_execute_multiply(value_type, value, step->params);
		case ZBX_PREPROC_RTRIM:
		case ZBX_PREPROC_LTRIM:
		case ZBX_PREPROC_TRIM:
			return pp_execute_trim(step->type, value, step->params);
		case ZBX_PREPROC_REGSUB:
			return pp_execute_regsub(value, step->params);
		case ZBX_PREPROC_BOOL2DEC:
		case ZBX_PREPROC_OCT2DEC:
		case ZBX_PREPROC_HEX2DEC:
			return pp_execute_2dec(step->type, value);
		case ZBX_PREPROC_DELTA_VALUE:
		case ZBX_PREPROC_DELTA_SPEED:
			return pp_execute_delta(step->type, value_type, value, ts, history_value, history_ts);
		case ZBX_PREPROC_XPATH:
			return pp_execute_xpath(value, step->params);
		case ZBX_PREPROC_JSONPATH:
			return pp_execute_jsonpath(cache, value, step->params);
		case ZBX_PREPROC_VALIDATE_RANGE:
			return pp_validate_range(value_type, value, step->params);
		case ZBX_PREPROC_VALIDATE_REGEX:
			return pp_validate_regex(value, step->params);
		case ZBX_PREPROC_VALIDATE_NOT_REGEX:
			return pp_validate_not_regex(value, step->params);
		case ZBX_PREPROC_VALIDATE_NOT_SUPPORTED:
			return pp_check_not_error(value);
		case ZBX_PREPROC_ERROR_FIELD_JSON:
			return pp_error_from_json(value, step->params);
		case ZBX_PREPROC_ERROR_FIELD_XML:
			return pp_error_from_xml(value, step->params);
		case ZBX_PREPROC_ERROR_FIELD_REGEX:
			return pp_error_from_regex(value, step->params);
		case ZBX_PREPROC_THROTTLE_VALUE:
			return item_preproc_throttle_value(value, &ts, history_value, history_ts);
		case ZBX_PREPROC_THROTTLE_TIMED_VALUE:
			return pp_throttle_timed_value(value, ts, step->params, history_value, history_ts);
		case ZBX_PREPROC_SCRIPT:
			return pp_execute_script(ctx, value, step->params, history_value);
		case ZBX_PREPROC_PROMETHEUS_PATTERN:
			return pp_execute_prometheus_pattern(cache, value, step->params);
		case ZBX_PREPROC_PROMETHEUS_TO_JSON:
			return pp_execute_prometheus_to_json(value, step->params);
		case ZBX_PREPROC_CSV_TO_JSON:
			return pp_execute_csv_to_json(value, step->params);
		case ZBX_PREPROC_XML_TO_JSON:
			return pp_execute_xml_to_json(value);
		case ZBX_PREPROC_STR_REPLACE:
			return pp_execute_str_replace(value, step->params);
		default:
			zbx_variant_clear(value);
			zbx_variant_set_error(value, zbx_dsprintf(NULL, "unknown preprocessing step"));
			return FAIL;
		}
}

void	pp_execute(zbx_pp_context_t *ctx, zbx_pp_item_preproc_t *preproc, zbx_pp_cache_t *cache,
		zbx_variant_t *value_in, zbx_timespec_t ts, zbx_variant_t *value_out)
{
	zbx_pp_result_t		*results;
	zbx_pp_history_t	*history;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(): value:%s type:%s", __func__,
			zbx_variant_value_desc(NULL == cache ? value_in : &cache->value),
			zbx_variant_type_desc(NULL == cache ? value_in : &cache->value));

	if (NULL == cache)
		zbx_variant_copy(value_out, value_in);
	else
		value_in = &cache->value;

	if (0 == preproc->steps_num)
	{
		if (NULL != cache)
			zbx_variant_copy(value_out, &cache->value);

		goto out;
	}

	results = (zbx_pp_result_t *)zbx_malloc(NULL, sizeof(zbx_pp_result_t) * preproc->steps_num);
	history = (0 != preproc->history_num ? pp_history_create(preproc->history_num) : NULL);

	unsigned char	action = ZBX_PREPROC_FAIL_DEFAULT;
	int		results_num = 0;

	for (int i = 0; i < preproc->steps_num; i++)
	{
		zbx_variant_t	history_value;
		zbx_timespec_t	history_ts;

		if (ZBX_VARIANT_ERR == value_out->type && ZBX_PREPROC_VALIDATE_NOT_SUPPORTED != preproc->steps[i].type)
			break;

		pp_history_pop(preproc->history, i, &history_value, &history_ts);

		if (SUCCEED != pp_execute_step(ctx, cache, preproc->value_type, value_out, ts, preproc->steps + i,
				&history_value, &history_ts))
		{
			action = pp_error_on_fail(value_out, preproc->steps + i);
		}
		else
		{	if (ZBX_VARIANT_ERR == value_out->type)
				action = ZBX_PREPROC_FAIL_FORCE_ERROR;
			else
				action = ZBX_PREPROC_FAIL_DEFAULT;
		}

		pp_result_set(results + results_num++, value_out, action);

		if (NULL != history && ZBX_VARIANT_NONE != history_value.type && ZBX_VARIANT_ERR != value_out->type)
		{
			if (SUCCEED == pp_preproc_has_history(preproc->steps[i].type))
				pp_history_add(history, i, &history_value, history_ts);
		}

		zbx_variant_clear(&history_value);

		cache = NULL;
	}

	if (ZBX_VARIANT_ERR == value_out->type)
	{
		/* reset preprocessing history in the case of error */
		if (NULL != history)
		{
			pp_history_free(history);
			history = NULL;
		}

		if (ZBX_PREPROC_FAIL_SET_ERROR != action && ZBX_PREPROC_FAIL_FORCE_ERROR != action)
		{
			char	*error = NULL;

			pp_format_error(value_in, results, results_num, &error);
			zbx_variant_clear(value_out);
			zbx_variant_set_error(value_out, error);
		}
		/* TODO: format error message from results */
	}

	/* replace preprocessing history */

	if (NULL != preproc->history)
		pp_history_free(preproc->history);

	preproc->history = history;


	if (0 != results_num)
		pp_free_results(results, results_num);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s(): value:'%s' type:%s", __func__, zbx_variant_value_desc(value_out),
			zbx_variant_type_desc(value_out));

}

void	pp_context_init(zbx_pp_context_t *ctx)
{
	memset(ctx, 0, sizeof(zbx_pp_context_t));
}

void	pp_context_destroy(zbx_pp_context_t *ctx)
{
	if (0 != ctx->es_initialized)
		zbx_es_destroy(&ctx->es_engine);
}

zbx_es_t	*pp_context_es_engine(zbx_pp_context_t *ctx)
{
	if (0 == ctx->es_initialized)
	{
		zbx_es_init(&ctx->es_engine);
		ctx->es_initialized = 1;
	}

	return &ctx->es_engine;
}
