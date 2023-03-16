/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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

#include "preproc_server.h"

#include "zbxpreproc.h"
#include "zbxtime.h"
#include "zbxcachehistory.h"
#include "zbxlld.h"
#include "zbxvariant.h"

static void	add_history_variant_server(zbx_uint64_t itemid, unsigned char value_type, unsigned char item_flags,
		zbx_variant_t *value, zbx_timespec_t ts, const zbx_pp_value_opt_t *value_opt)
{
	unsigned char	value_flags = 0;
	zbx_uint64_t	lastlogsize;
	int		mtime;

	if (0 != (value_opt->flags & ZBX_PP_VALUE_OPT_META))
	{
		value_flags = ZBX_DC_FLAG_META;
		lastlogsize = value_opt->lastlogsize;
		mtime = value_opt->mtime;

		value_flags |= ZBX_DC_FLAG_META;
	}
	else
	{
		value_flags = 0;
		lastlogsize = 0;
		mtime = 0;
	}

	if (ZBX_VARIANT_ERR == value->type)
	{
		dc_local_add_history_notsupported(itemid, &ts, value->data.err, lastlogsize, mtime, value_flags);

		return;
	}

	if (ZBX_VARIANT_NONE == value->type)
		value_flags |= ZBX_DC_FLAG_NOVALUE;

	/* Add data to the local history cache if:                                           */
	/*   1) the NOVALUE flag is set (data contains either meta information or timestamp) */
	/*   2) the NOVALUE flag is not set and value conversion succeeded                   */

	if (0 != (value_flags & ZBX_DC_FLAG_NOVALUE))
	{
		if (0 != (value_flags & ZBX_DC_FLAG_META))
			dc_local_add_history_log(itemid, value_type, &ts, NULL, lastlogsize, mtime, value_flags);
		else
			dc_local_add_history_empty(itemid, value_type, &ts, value_flags);

		return;
	}

	if (0 != (ZBX_FLAG_DISCOVERY_RULE & item_flags))
		return;

	if (0 != (value_opt->flags & ZBX_PP_VALUE_OPT_LOG))
	{
		zbx_log_t	log;

		zbx_variant_convert(value, ZBX_VARIANT_STR);

		log.logeventid = value_opt->logeventid;
		log.severity = value_opt->severity;
		log.timestamp = value_opt->timestamp;
		log.source = value_opt->source;
		log.value = value->data.str;

		dc_local_add_history_log(itemid, value_type, &ts, &log, lastlogsize, mtime, value_flags);

		return;
	}

	switch (value->type)
	{
		case ZBX_VARIANT_UI64:
			dc_local_add_history_uint(itemid, value_type, &ts, value->data.ui64, lastlogsize, mtime,
					value_flags);
			break;
		case ZBX_VARIANT_DBL:
			dc_local_add_history_dbl(itemid, value_type, &ts, value->data.dbl, lastlogsize, mtime,
					value_flags);
			break;
		case ZBX_VARIANT_STR:
			dc_local_add_history_text(itemid, value_type, &ts, value->data.str, lastlogsize, mtime,
					value_flags);
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
	}
}

void	preproc_flush_value_server(zbx_pp_manager_t *manager, zbx_uint64_t itemid, unsigned char value_type,
	unsigned char flags, zbx_variant_t *value, zbx_timespec_t ts, zbx_pp_value_opt_t *value_opt)
{
	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (0 == (flags & ZBX_FLAG_DISCOVERY_RULE))
	{
		add_history_variant_server(itemid, value_type, flags, value, ts, value_opt);
	}
	else
	{
		zbx_pp_item_t	*item;

		if (NULL != (item = (zbx_pp_item_t *)zbx_hashset_search(zbx_pp_manager_items(manager), &itemid)))
		{
			const char	*value_lld = NULL, *error_lld = NULL;
			unsigned char	meta = 0;
			zbx_uint64_t	lastlogsize = 0;
			int		mtime = 0;

			if (ZBX_VARIANT_ERR == value->type)
			{
				error_lld = value->data.err;
			}
			else
			{
				if (SUCCEED == zbx_variant_convert(value, ZBX_VARIANT_STR))
					value_lld = value->data.str;
			}

			if (0 != (value_opt->flags & ZBX_PP_VALUE_OPT_META))
			{
				meta = 1;
				lastlogsize = value_opt->lastlogsize;
				mtime = value_opt->mtime;
			}

			if (NULL != value_lld || NULL != error_lld || 0 != meta)
			{
				zbx_lld_process_value(itemid, item->hostid, value_lld, &ts, meta, lastlogsize, mtime,
						error_lld);
			}
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}
