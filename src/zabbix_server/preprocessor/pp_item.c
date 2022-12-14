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

#include "pp_item.h"
#include "pp_log.h"
#include "pp_history.h"
#include "zbxvariant.h"

zbx_pp_item_preproc_t	*pp_item_preproc_create(unsigned char type, unsigned char value_type, unsigned char flags)
{
	zbx_pp_item_preproc_t	*preproc = zbx_malloc(NULL, sizeof(zbx_pp_item_preproc_t));

	pp_debugf("pp_item_preproc_create() -> 0x%p", preproc);
	preproc->refcount = 1;
	preproc->steps_num = 0;
	preproc->steps = NULL;
	preproc->dep_itemids_num = 0;
	preproc->dep_itemids = NULL;

	preproc->type = type;
	preproc->value_type = value_type;
	preproc->flags = flags;

	preproc->history = NULL;
	preproc->history_num = 0;

	return preproc;
}

static void	pp_item_preproc_free(zbx_pp_item_preproc_t *preproc)
{
	int	i;

	for (i = 0; i < preproc->steps_num; i++)
	{
		zbx_free(preproc->steps[i].params);
		zbx_free(preproc->steps[i].error_handler_params);
	}

	zbx_free(preproc->steps);
	zbx_free(preproc->dep_itemids);

	if (NULL != preproc->history)
		pp_history_free(preproc->history);

	pp_debugf("pp_item_preproc_free(0x%p)", preproc);

	zbx_free(preproc);
}

zbx_pp_item_preproc_t	*pp_item_preproc_copy(zbx_pp_item_preproc_t *preproc)
{
	if (NULL == preproc)
		return NULL;

	preproc->refcount++;

	return preproc;
}

void	pp_item_preproc_release(zbx_pp_item_preproc_t *preproc)
{
	if (NULL == preproc || 0 != --preproc->refcount)
		return;

	pp_item_preproc_free(preproc);
}

int	pp_preproc_uses_history(unsigned char type)
{
	switch (type)
	{
		case ZBX_PREPROC_DELTA_VALUE:
		case ZBX_PREPROC_DELTA_SPEED:
		case ZBX_PREPROC_THROTTLE_VALUE:
		case ZBX_PREPROC_THROTTLE_TIMED_VALUE:
		case ZBX_PREPROC_SCRIPT:
			return SUCCEED;
		default:
			return FAIL;

	}
}

/* TODO: preprocessing mode must be calculated automatically after setting steps */
void	pp_item_init(zbx_pp_item_t *item, unsigned char type, unsigned char value_type, unsigned char flags,
		zbx_pp_process_mode_t mode)
{
	item->preproc = pp_item_preproc_create(type, value_type, flags);
	item->preproc->mode = mode;
}

void	pp_item_clear(zbx_pp_item_t *item)
{
	pp_debugf("pp_item_clear(0x%p)", item);
	pp_item_preproc_release(item->preproc);
}
