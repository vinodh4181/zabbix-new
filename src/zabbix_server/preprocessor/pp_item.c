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

void	pp_value_free(zbx_pp_value_t *value)
{
	zbx_variant_clear(&value->var);
	zbx_free(value);
}

void	pp_item_init(zbx_pp_item_t *item, unsigned char type, unsigned char value_type)
{
	item->refcount = 1;
	item->type = type;
	item->value_type = value_type;
	item->mode = ZBX_PP_ITEM_PROCESS_PARALLEL;

	zbx_list_create(&item->values);
}

void	pp_item_release(zbx_pp_item_t *item)
{
	zbx_pp_value_t	*value;

	if (0 != --item->refcount)
		return;

	while (NULL != (value = (zbx_pp_value_t *)zbx_list_pop(&item->values)))
		pp_value_free(value);
	zbx_list_destroy(&item->values);

	zbx_free(item);
}

void	pp_item_clear(zbx_pp_item_t *item)
{
	while (NULL != (value = (zbx_pp_value_t *)zbx_list_pop(&item->values)))
		pp_value_free(value);
	zbx_list_destroy(&item->values);
}

#endif
