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

#include "pp_worker.h"

#include "pp_task.h"
#include "zbxcommon.h"
#include "zbxalgo.h"

#define PP_STARTUP_TIMEOUT	10

typedef struct
{
	zbx_pp_worker_t		*workers;
	int			workers_num;

	zbx_hashset_t		items;

	zbx_pp_task_queue_t	queue;
}
zbx_pp_manager_t;

int	pp_manager_init(zbx_pp_manager_t *manager, int workers_num, char **error)
{
	int		i, ret = FAIL, started_num = 0;
	time_t		time_start;
	struct timespec	poll_delay = {0, 1e8};

	memset(manager, 0, sizeof(zbx_pp_manager_t));

	if (SUCCEED != pp_task_queue_init(&manager->queue, error))
		goto out;

	manager->workers_num = workers_num;
	manager->workers = (zbx_pp_worker_t *)zbx_calloc(NULL, workers_num, sizeof(zbx_pp_worker_t));

	for (i = 0; i < workers_num; i++)
	{
		if (SUCCEED != pp_worker_init(&manager->workers[i], &manager->queue, error))
			goto out;
	}

	zbx_hashset_create_ext(&manager->items, 100, ZBX_DEFAULT_UINT64_HASH_FUNC, ZBX_DEFAULT_UINT64_COMPARE_FUNC,
			pp_item_clear, ZBX_DEFAULT_MEM_MALLOC_FUNC, ZBX_DEFAULT_MEM_REALLOC_FUNC,
			ZBX_DEFAULT_MEM_FREE_FUNC);

	/* wait for threads to start */
	time_start = time(NULL);

	while (started_num != workers_num)
	{
		if (time_start + PP_STARTUP_TIMEOUT < time(NULL))
		{
			*error = zbx_strdup(NULL, "timeout occurred while waiting for workers to start");
			goto out;
		}

		pthread_mutex_lock(&manager->queue.lock);
		started_num = manager->queue.workers_num;
		pthread_mutex_unlock(&manager->queue.lock);

		nanosleep(&poll_delay, NULL);
	}

	ret = SUCCEED;
out:
	if (FAIL == ret)
	{
		for (i = 0; i < manager->workers_num; i++)
			pp_worker_destroy(&manager->workers[i]);

		pp_task_queue_destroy(&manager->queue);
	}

	return ret;
}

void	pp_manager_destroy(zbx_pp_manager_t *manager)
{
	int	i;

	for (i = 0; i < manager->workers_num; i++)
		pp_worker_destroy(&manager->workers[i]);

	pp_task_queue_destroy(&manager->queue);
	zbx_hashset_destroy(&manager->items);
}

/* TODO: add output socket/client to parameters */
void	pp_manager_queue_test(zbx_pp_manager_t *manager, zbx_uint64_t itemid, zbx_variant_t *value)
{
	zbx_pp_item_t		*item;
	zbx_pp_task_t		*task;
	zbx_timespec_t		ts;

	if (NULL == (item = (zbx_pp_item_t *)zbx_hashset_search(&manager->items, &itemid)))
		return;

	zbx_timespec(&ts);

	task = pp_task_test_in_create(item, value, ts);
	pp_task_queue_push_new(&manager->queue, item, task);

}

void	pp_manager_queue_preproc(zbx_pp_manager_t *manager, zbx_uint64_t itemid, zbx_variant_t *value,
		zbx_timespec_t ts)
{
	zbx_pp_item_t	*item;
	zbx_pp_task_t	*task;

	if (NULL == (item = (zbx_pp_item_t *)zbx_hashset_search(&manager->items, &itemid)))
		return;

	if (ZBX_PP_PROCESS_PARALLEL == item->mode)
		task = pp_task_value_in_create(item, value, ts);
	else
		task = pp_task_value_seq_in_create(item, value, ts);

	pp_task_queue_push_new(&manager->queue, item, task);
}

void	pp_manager_process_dependent_task(zbx_pp_manager_t *manager, zbx_pp_task_t *task)
{
	/* TODO: queue 1: dependent item preprocessing tasks */
}

void	pp_manager_process_sequence_task(zbx_pp_manager_t *manager, zbx_pp_task_t *out)
{
	zbx_pp_task_sequence_out_t	*out_d = (zbx_pp_task_sequence_out_t *)PP_TASK_DATA(out);
	zbx_pp_task_sequence_in_t	*in_d = (zbx_pp_task_sequence_in_t *)PP_TASK_DATA(out_d->in);
	zbx_pp_task_t			*task;

	if (SUCCEED == zbx_list_pop(&in_d->tasks, (void **)&task))
		pp_task_free(task);

	if (SUCCEED == zbx_list_peek(&in_d->tasks, (void **)&task))
	{
		pp_task_queue_push_immediate(&manager->queue, out_d->in);
		out_d->in = NULL;
	}
}

void	pp_manager_process_finished(zbx_pp_manager_t *manager)
{
	zbx_pp_task_t	*task;

	while (1)
	{
		pp_task_queue_lock(&manager->queue);

		if (NULL != (task = pp_task_queue_pop_done(&manager->queue)))
		{
			switch (task->type)
			{
				case ZBX_PP_TASK_DEPENDENT_OUT:
					pp_manager_process_dependent_task(manager, task);
					break;
				case ZBX_PP_TASK_SEQUENCE_OUT:
					pp_manager_process_sequence_task(manager, task);
					break;
			}
		}

		pp_task_queue_unlock(&manager->queue);

		if (NULL == task)
			break;

		/* TODO: process popped task depending on its type */
		pp_task_free(task);

	}
}

/* WDN: debug */

#include "zbxvariant.h"

static void	pp_manager_add_item(zbx_pp_manager_t *manager, zbx_uint64_t itemid, unsigned char type,
		unsigned char value_type, zbx_pp_process_mode_t preproc_mode)
{
	zbx_pp_item_t	item_local = {.itemid = itemid, .type = type, .value_type = value_type, .mode = preproc_mode};
	zbx_pp_item_t	*item;

	item = zbx_hashset_insert(&manager->items, &item_local, sizeof(item_local));
	item->preproc = pp_item_preproc_create();
}

static void	pp_manager_update_item(zbx_pp_manager_t *manager, zbx_uint64_t itemid)
{
	zbx_pp_item_t	*item;

	if (NULL != (item = zbx_hashset_search(&manager->items, &itemid)))
	{
		pp_item_preproc_release(item->preproc);
		item->preproc = pp_item_preproc_create();
	}
}


int	test_pp(void)
{
	zbx_pp_manager_t	manager;
	char			*error = NULL;
	zbx_variant_t		value;
	zbx_timespec_t		ts;

	if (SUCCEED != pp_manager_init(&manager, 2, &error))
	{
		printf("Failed to initialize preprocessing subsystem: %s\n", error);
		zbx_free(error);
		exit(EXIT_FAILURE);
	}

	pp_manager_add_item(&manager, 1001, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);
	pp_manager_add_item(&manager, 1002, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_SERIAL);

	zbx_variant_set_ui64(&value, 1);
	zbx_timespec(&ts);

	pp_task_queue_lock(&manager.queue);

	pp_manager_queue_preproc(&manager, 1001, &value, ts);

	zbx_variant_set_ui64(&value, 2);
	pp_manager_queue_preproc(&manager, 1001, &value, ts);

	pp_task_queue_unlock(&manager.queue);
	pp_task_queue_notify_all(&manager.queue);

	for (int i = 0; i < 4; i++)
	{
		printf("iteration: %d\n", i);
		pp_manager_process_finished(&manager);
		sleep(1);
	}

	printf("shutting down...\n");
	pp_manager_destroy(&manager);

	exit(0);
}



