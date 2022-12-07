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

#ifndef ZABBIX_PP_ITEM_H
#define ZABBIX_PP_ITEM_H

#include "zbxcommon.h"
#include "zbxalgo.h"
#include "zbxvariant.h"
#include "zbxtime.h"

typedef enum
{
	ZBX_PP_PROCESS_PARALLEL,
	ZBX_PP_PROCESS_SERIAL
}
zbx_pp_process_mode_t;

typedef struct
{
	zbx_variant_t	var;
	zbx_timespec_t	ts;
}
zbx_pp_value_t;

typedef struct
{
	zbx_uint32_t	refcount;

	/* TODO: preprocessing data */
}
zbx_pp_item_preproc_t;

typedef struct
{
	zbx_uint64_t		itemid;
	unsigned char		type;
	unsigned char		value_type;
	zbx_pp_process_mode_t	mode;

	zbx_pp_item_preproc_t	*preproc;

	/* TODO: history vault */

}
zbx_pp_item_t;

void	pp_value_free(zbx_pp_value_t *value);

void	pp_item_clear(zbx_pp_item_t *item);
void	pp_item_init(zbx_pp_item_t *item, unsigned char type, unsigned char value_type, zbx_pp_process_mode_t mode);

zbx_pp_item_preproc_t	*pp_item_preproc_create();
void	pp_item_preproc_release(zbx_pp_item_preproc_t *preproc);
zbx_pp_item_preproc_t	*pp_item_copy_preproc(zbx_pp_item_t *item);

#endif
