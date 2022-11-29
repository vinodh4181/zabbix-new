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

#include "zbxcommon.h"
#include "zbxalgo.h"

typedef enum
{
	ZBX_PP_TASK_PREPROC_ITEM = 1,
	ZBX_PP_TASK_PREPROC_DEPS,
	ZBX_PP_TASK_TEST_ITEM
}
zbx_pp_task_type_t;

typedef struct
{
	int	type;
	void	*data;
}
zbx_pp_task_t;

typedef struct
{
	zbx_uint32_t	init_flags;
	int		workers_num;

	zbx_hashset_t	items;

	zbx_list_t	pending;
	zbx_list_t	finished;
	zbx_list_t	direct;

	pthread_mutex_t	lock;
	pthread_cond_t	event;
}
zbx_pp_task_queue_t;

int	pp_task_queue_init(zbx_pp_task_queue_t *queue, char **error);
void	pp_task_queue_destroy(zbx_pp_task_queue_t *queue);

void	pp_task_queue_lock(zbx_pp_task_queue_t *queue);
void	pp_task_queue_unlock(zbx_pp_task_queue_t *queue);
void	pp_task_queue_register_worker(zbx_pp_task_queue_t *queue);
void	pp_task_queue_deregister_worker(zbx_pp_task_queue_t *queue);
zbx_pp_task_t	*pp_task_queue_pop_pending(zbx_pp_task_queue_t *queue);
void	pp_task_queue_push_finished(zbx_pp_task_queue_t *queue, zbx_pp_task_t *task);

void	pp_task_free(zbx_pp_task_t *task);

#endif
