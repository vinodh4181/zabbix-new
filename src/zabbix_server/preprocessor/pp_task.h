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

#ifndef ZABBIX_PP_TASK_H
#define ZABBIX_PP_TASK_H

#include "pp_item.h"
#include "pp_cache.h"

#include "zbxcommon.h"
#include "zbxalgo.h"
#include "zbxvariant.h"
#include "zbxtime.h"

typedef enum
{
	ZBX_PP_TASK_TEST_IN = 1,
	ZBX_PP_TASK_VALUE_IN,
	ZBX_PP_TASK_VALUE_SEQ_IN,
	ZBX_PP_TASK_DEPENDENT_IN,
	ZBX_PP_TASK_SEQUENCE_IN,
	ZBX_PP_TASK_TEST_OUT,
	ZBX_PP_TASK_VALUE_OUT,
	ZBX_PP_TASK_VALUE_SEQ_OUT,
	ZBX_PP_TASK_DEPENDENT_OUT,
	ZBX_PP_TASK_SEQUENCE_OUT
}
zbx_pp_task_type_t;

typedef struct
{
	zbx_pp_task_type_t	type;
	zbx_uint64_t		itemid;
	void			*data;
}
zbx_pp_task_t;

typedef struct
{
	zbx_variant_t		value;
	zbx_timespec_t		ts;
	zbx_pp_item_preproc_t	*preproc;

	/* TODO: output socket */
}
zbx_pp_task_test_in_t;

typedef struct
{
	zbx_variant_t		value;
	zbx_timespec_t		ts;
	zbx_uint64_t		lastlogsize;
	int			mtime;

	zbx_pp_item_preproc_t	*preproc;

	zbx_pp_cache_t		*cache;
}
zbx_pp_task_value_in_t;

typedef struct
{
	zbx_variant_t		value;
	zbx_timespec_t		ts;
	zbx_pp_item_preproc_t	*preproc;
}
zbx_pp_task_dependent_in_t;

typedef struct
{
	zbx_list_t	tasks;
}
zbx_pp_task_sequence_in_t;

typedef struct
{
	zbx_pp_task_t	*in;
}
zbx_pp_task_test_out_t;

typedef struct
{
	zbx_pp_task_t	*in;
	zbx_variant_t	value;
}
zbx_pp_task_value_out_t;

typedef struct
{
	zbx_pp_task_t		*in;

	zbx_pp_cache_t	*cache;
}
zbx_pp_task_dependent_out_t;

typedef struct
{
	zbx_pp_task_t	*in;
	zbx_variant_t	value;
}
zbx_pp_task_sequence_out_t;

#define PP_TASK_DATA(x)		(&x->data)

zbx_pp_task_t	*pp_task_test_out_create(zbx_pp_task_t *in);
zbx_pp_task_t	*pp_task_value_out_create(zbx_pp_task_t *in);
zbx_pp_task_t	*pp_task_dependent_out_create(zbx_pp_task_t *in);
zbx_pp_task_t	*pp_task_sequence_out_create(zbx_pp_task_t *in);

void	pp_task_free(zbx_pp_task_t *task);

zbx_pp_task_t	*pp_task_test_in_create(zbx_pp_item_t *item, zbx_variant_t *value, zbx_timespec_t ts);
zbx_pp_task_t	*pp_task_value_in_create(zbx_pp_item_t *item, zbx_variant_t *value, zbx_timespec_t ts,
		zbx_pp_cache_t *cache);
zbx_pp_task_t	*pp_task_dependent_in_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc,
		const zbx_variant_t *value, zbx_timespec_t ts);
zbx_pp_task_t	*pp_task_value_seq_in_create(zbx_pp_item_t *item, zbx_variant_t *value, zbx_timespec_t ts);
zbx_pp_task_t	*pp_task_sequence_in_create(zbx_uint64_t itemid);


#endif
