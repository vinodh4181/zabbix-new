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

#ifndef ZABBIX_PP_TASK_H
#define ZABBIX_PP_TASK_H

#include "zbxpreproc.h"
#include "pp_cache.h"

#include "zbxalgo.h"
#include "zbxvariant.h"
#include "zbxtime.h"
#include "zbxipcservice.h"

#define PP_TASK_DATA(x)		(&x->data)

typedef enum
{
	ZBX_PP_TASK_TEST = 1,
	ZBX_PP_TASK_VALUE,
	ZBX_PP_TASK_VALUE_SEQ,
	ZBX_PP_TASK_DEPENDENT,
	ZBX_PP_TASK_SEQUENCE
}
zbx_pp_task_type_t;

typedef struct
{
	zbx_pp_task_type_t	type;
	zbx_uint64_t		itemid;
	zbx_uint64_t		hostid;
	void			*data;
}
zbx_pp_task_t;

ZBX_PTR_VECTOR_DECL(pp_task_ptr, zbx_pp_task_t *)

typedef struct
{
	zbx_variant_t		value;
	zbx_timespec_t		ts;

	zbx_variant_t		result;
	zbx_pp_result_t		*results;
	int			results_num;

	zbx_pp_item_preproc_t	*preproc; /* created from the data provided in request */

	zbx_ipc_client_t	*client;
}
zbx_pp_task_test_t;

typedef struct
{
	zbx_variant_t		value;
	zbx_variant_t		result;
	zbx_timespec_t		ts;
	zbx_pp_value_opt_t	opt;

	zbx_pp_item_preproc_t	*preproc;
	zbx_pp_cache_t		*cache;
}
zbx_pp_task_value_t;

typedef struct
{
	zbx_pp_item_preproc_t	*preproc;
	zbx_pp_task_t		*primary;
	zbx_pp_cache_t		*cache;
}
zbx_pp_task_dependent_t;

typedef struct
{
	zbx_list_t	tasks;
}
zbx_pp_task_sequence_t;

void	pp_task_free(zbx_pp_task_t *task);

zbx_pp_task_t	*pp_task_test_create(zbx_pp_item_preproc_t *preproc, zbx_variant_t *value, zbx_timespec_t ts,
		zbx_ipc_client_t *client);
zbx_pp_task_t	*pp_task_value_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc, zbx_variant_t *value,
		zbx_timespec_t ts, const zbx_pp_value_opt_t *value_opt, zbx_pp_cache_t *cache);
zbx_pp_task_t	*pp_task_dependent_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc);
zbx_pp_task_t	*pp_task_value_seq_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc, zbx_variant_t *value,
		zbx_timespec_t ts, const zbx_pp_value_opt_t *value_opt, zbx_pp_cache_t *cache);
zbx_pp_task_t	*pp_task_sequence_create(zbx_uint64_t itemid);

void	pp_tasks_clear(zbx_vector_pp_task_ptr_t *tasks);

void	pp_value_task_get_data(zbx_pp_task_t *task, unsigned char *value_type, unsigned char *flags,
		zbx_variant_t **value, zbx_timespec_t *ts, zbx_pp_value_opt_t **value_opt);
void	pp_test_task_get_data(zbx_pp_task_t *task, zbx_ipc_client_t **client, zbx_variant_t **value,
		zbx_pp_result_t **results, int *results_num, zbx_pp_history_t **history);

#endif
