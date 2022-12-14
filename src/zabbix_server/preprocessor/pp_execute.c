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
#include "pp_error.h"
#include "log.h"
#include "pp_log.h"
#include "item_preproc.h"

static int	pp_execute_multiply(unsigned char value_type, zbx_variant_t *value, const char *params)
{
	char	buffer[MAX_STRING_LEN], *error = NULL, *errmsg = NULL;

	zbx_strlcpy(buffer, params, sizeof(buffer));
	zbx_trim_float(buffer);

	if (FAIL == zbx_is_double(buffer, NULL))
	{
		zbx_variant_set_str(value, zbx_dsprintf(NULL, "a numerical value is expected or the value is"
				" out of range"));
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
		zbx_variant_t *history_value, zbx_timespec_t history_ts)
{
	char	*errmsg = NULL;

	if (SUCCEED == item_preproc_delta(value_type, value, &ts, type, history_value, &history_ts, &errmsg))
		return SUCCEED;

	zbx_variant_clear(value);
	zbx_variant_set_error(value, zbx_dsprintf(NULL,  "cannot calculate delta (%s) for value of type"
				" \"%s\": %s", pp_delta_desc(type), zbx_variant_type_desc(value), errmsg));
	zbx_free(errmsg);

	return FAIL;
}

static int	pp_execute_step(zbx_pp_cache_t *cache, unsigned char value_type, zbx_variant_t *value,
		zbx_timespec_t ts, zbx_pp_step_t *step, zbx_variant_t *history_value, zbx_timespec_t history_ts)
{
	switch (step->type)
	{
		case ZBX_PREPROC_MULTIPLIER:
			return pp_execute_multiply(value_type, value, step->params);
		case ZBX_PREPROC_RTRIM:
		case ZBX_PREPROC_LTRIM:
		case ZBX_PREPROC_TRIM:
			return pp_execute_trim(step->type, value, step->params);
		case ZBX_PREPROC_VALIDATE_NOT_SUPPORTED:
			return pp_check_not_error(value);
		case ZBX_PREPROC_DELTA_VALUE:
		case ZBX_PREPROC_DELTA_SPEED:
			return pp_execute_delta(step->type, value_type, value, ts, history_value, history_ts);
		default:
			zbx_variant_clear(value);
			zbx_variant_set_error(value, zbx_dsprintf(NULL, "unknown preprocessing step"));
			return FAIL;
		}
}

void	pp_execute(zbx_pp_item_preproc_t *preproc, zbx_pp_cache_t *cache, zbx_variant_t *value_in, zbx_timespec_t ts,
		zbx_variant_t *value_out)
{
	zbx_pp_result_t		*results;
	zbx_pp_history_t	*history;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(): value:%s type:%s", __func__, zbx_variant_value_desc(value_in),
			zbx_variant_type_desc(value_in));

	if (NULL == cache)
		zbx_variant_copy(value_out, value_in);
	else
		value_in = &cache->value;

	if (0 == preproc->steps_num)
	{
		if (NULL != cache)
			zbx_variant_copy(value_out, &cache->value);

		return;
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

		if (SUCCEED != pp_execute_step(cache, preproc->value_type, value_out, ts, preproc->steps + i,
				&history_value, history_ts))
		{
			pp_error_on_fail(value_out, preproc->steps + i);
			action = preproc->steps[i].error_handler;
		}
		else
			action = ZBX_PREPROC_FAIL_DEFAULT;

		pp_result_set(results + results_num++, value_out, action);

		if (NULL != history && ZBX_VARIANT_NONE != history_value.type && ZBX_VARIANT_ERR != value_out->type)
		{
			if (SUCCEED == pp_preproc_uses_history(preproc->steps[i].type))
				pp_history_add(history, i, &history_value, ts);
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

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s(): value:'%s' type:%s", __func__, zbx_variant_value_desc(value_out),
			zbx_variant_type_desc(value_out));

}
