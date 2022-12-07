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
#include "zbxvariant.h"

void	pp_value_free(zbx_pp_value_t *value)
{
	zbx_variant_clear(&value->var);
	zbx_free(value);
}

zbx_pp_item_preproc_t	*pp_item_preproc_create()
{
	zbx_pp_item_preproc_t	*preproc = zbx_malloc(NULL, sizeof(zbx_pp_item_preproc_t));

	printf("pp_item_preproc_create() -> 0x%p\n", preproc);
	preproc->refcount = 1;
	return preproc;
}

void	pp_item_preproc_free(zbx_pp_item_preproc_t *preproc)
{
	/* TODO: free preprocessing data */

	printf("pp_item_preproc_free(0x%p)\n", preproc);

	zbx_free(preproc);
}

void	pp_item_preproc_release(zbx_pp_item_preproc_t *preproc)
{
	if (0 == --preproc->refcount)
		pp_item_preproc_free(preproc);
}

void	pp_item_init(zbx_pp_item_t *item, unsigned char type, unsigned char value_type, zbx_pp_process_mode_t mode)
{
	item->type = type;
	item->value_type = value_type;
	item->mode = mode;
}

void	pp_item_clear(zbx_pp_item_t *item)
{
	printf("pp_item_clear(0x%p)\n", item);
	pp_item_preproc_release(item->preproc);
}

zbx_pp_item_preproc_t	*pp_item_copy_preproc(zbx_pp_item_t *item)
{
	item->preproc->refcount++;

	return item->preproc;
}
