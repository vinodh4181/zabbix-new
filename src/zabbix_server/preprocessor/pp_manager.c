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
#include "pp_log.h"
#include "pp_queue.h"
#include "pp_item.h"
#include "pp_xml.h"
#include "pp_task.h"
#include "zbxcommon.h"
#include "zbxalgo.h"

#define PP_STARTUP_TIMEOUT	10

typedef struct
{
	zbx_pp_worker_t		*workers;
	int			workers_num;

	zbx_hashset_t		items;

	zbx_pp_queue_t	queue;
}
zbx_pp_manager_t;

int	pp_manager_init(zbx_pp_manager_t *manager, int workers_num, char **error)
{
	int		i, ret = FAIL, started_num = 0;
	time_t		time_start;
	struct timespec	poll_delay = {0, 1e8};

	/* TODO: for debug logging, remove */
	pp_log_init("manager", 3);
	pp_infof("starting ...");

	pp_xml_init();

	memset(manager, 0, sizeof(zbx_pp_manager_t));

	if (SUCCEED != pp_task_queue_init(&manager->queue, error))
		goto out;

	manager->workers_num = workers_num;
	manager->workers = (zbx_pp_worker_t *)zbx_calloc(NULL, workers_num, sizeof(zbx_pp_worker_t));

	for (i = 0; i < workers_num; i++)
	{
		/* TODO: for debug logging, remove */
		manager->workers[i].id = i + 1;

		if (SUCCEED != pp_worker_init(&manager->workers[i], &manager->queue, error))
			goto out;
	}

	zbx_hashset_create_ext(&manager->items, 100, ZBX_DEFAULT_UINT64_HASH_FUNC, ZBX_DEFAULT_UINT64_COMPARE_FUNC,
			(zbx_clean_func_t)pp_item_clear, ZBX_DEFAULT_MEM_MALLOC_FUNC, ZBX_DEFAULT_MEM_REALLOC_FUNC,
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

	pp_infof("workers started");

	ret = SUCCEED;
out:
	if (FAIL == ret)
	{
		for (i = 0; i < manager->workers_num; i++)
			pp_worker_destroy(&manager->workers[i]);

		pp_task_queue_destroy(&manager->queue);
	}

	pp_xml_destroy();

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

	task = pp_task_test_create(item->itemid, item->preproc, value, ts);
	pp_task_queue_push_new(&manager->queue, item, task);
	pp_task_queue_notify(&manager->queue);
}

void	pp_manager_queue_preproc(zbx_pp_manager_t *manager, zbx_uint64_t itemid, zbx_variant_t *value,
		zbx_timespec_t ts)
{
	zbx_pp_item_t	*item;
	zbx_pp_task_t	*task;

	if (NULL == (item = (zbx_pp_item_t *)zbx_hashset_search(&manager->items, &itemid)))
		return;

	if (ZBX_PP_PROCESS_PARALLEL == item->preproc->mode)
		task = pp_task_value_create(item->itemid, item->preproc, value, ts, NULL);
	else
		task = pp_task_value_seq_create(item->itemid, item->preproc, value, ts, NULL);

	pp_task_queue_push_new(&manager->queue, item, task);
	pp_task_queue_notify(&manager->queue);
}

void	pp_manager_requeue_value_task(zbx_pp_manager_t *manager, zbx_pp_task_t *task)
{
	zbx_pp_task_value_t	*d = (zbx_pp_task_value_t *)PP_TASK_DATA(task);

	if (0 != d->preproc->dep_itemids_num)
	{
		zbx_pp_task_t		*dep_task;
		zbx_pp_item_t		*item;
		zbx_pp_item_preproc_t	*preproc;

		dep_task = pp_task_dependent_create(d->preproc->dep_itemids[0], d->preproc);

		if (NULL != (item = (zbx_pp_item_t *)zbx_hashset_search(&manager->items, &d->preproc->dep_itemids[0])))
			preproc = item->preproc;
		else
			preproc = NULL;

		zbx_pp_task_dependent_t	*d_dep = (zbx_pp_task_dependent_t *)PP_TASK_DATA(dep_task);

		d_dep->first_task = pp_task_value_create(d->preproc->dep_itemids[0], preproc, &d->result, d->ts,
				NULL);

		pp_task_queue_push_immediate(&manager->queue, dep_task);
		pp_task_queue_notify(&manager->queue);
	}
}

zbx_pp_task_t	*pp_manager_requeue_dependent_task(zbx_pp_manager_t *manager, zbx_pp_task_t *task)
{
	int	i;

	zbx_pp_task_dependent_t	*d = (zbx_pp_task_dependent_t *)PP_TASK_DATA(task);
	zbx_pp_task_t		*task_value = d->first_task;
	zbx_pp_task_value_t	*d_first = (zbx_pp_task_value_t *)PP_TASK_DATA(task_value);

	pp_manager_requeue_value_task(manager, d->first_task);

	for (i = 1; i < d->preproc->dep_itemids_num; i++)
	{
		zbx_pp_item_t	*item;
		zbx_pp_task_t	*new_task;

		if (NULL == (item = (zbx_pp_item_t *)zbx_hashset_search(&manager->items, &d->preproc->dep_itemids[i])))
			continue;

		if (ZBX_PP_PROCESS_PARALLEL == item->preproc->mode)
			new_task = pp_task_value_create(item->itemid, item->preproc, NULL, d_first->ts, d->cache);
		else
			new_task = pp_task_value_seq_create(item->itemid, item->preproc, NULL, d_first->ts, d->cache);

		pp_task_queue_push_immediate(&manager->queue, new_task);
	}

	pp_task_queue_notify_all(&manager->queue);

	d->first_task = NULL;
	pp_task_free(task);

	return task_value;
}

zbx_pp_task_t	*pp_manager_requeue_sequence_task(zbx_pp_manager_t *manager, zbx_pp_task_t *task_seq)
{
	zbx_pp_task_sequence_t	*d_seq = (zbx_pp_task_sequence_t *)PP_TASK_DATA(task_seq);
	zbx_pp_task_t		*task = NULL, *tmp_task;

	if (SUCCEED == zbx_list_pop(&d_seq->tasks, (void **)&task))
	{
		switch (task->type)
		{
			case ZBX_PP_TASK_VALUE:
			case ZBX_PP_TASK_VALUE_SEQ:
				pp_manager_requeue_value_task(manager, task);
				break;
			case ZBX_PP_TASK_DEPENDENT:
				task = pp_manager_requeue_dependent_task(manager, task);
				break;
			default:
				THIS_SHOULD_NEVER_HAPPEN;
				break;
		}
	}

	if (SUCCEED == zbx_list_peek(&d_seq->tasks, (void **)&tmp_task))
	{
		pp_task_queue_push_immediate(&manager->queue, task_seq);
		pp_task_queue_notify(&manager->queue);
	}
	else
		pp_task_queue_remove_sequence(&manager->queue, task_seq->itemid);

	return task;
}

#define PP_FINISSHED_TASK_BATCH_SIZE	100

/* WDN */
int	pp_processed_total;

ZBX_PTR_VECTOR_DECL(pp_task, zbx_pp_task_t *)
ZBX_PTR_VECTOR_IMPL(pp_task, zbx_pp_task_t *)

void	pp_manager_process_finished(zbx_pp_manager_t *manager)
{
	zbx_vector_pp_task_t	tasks;
	zbx_pp_task_t		*task;

	zbx_vector_pp_task_create(&tasks);
	zbx_vector_pp_task_reserve(&tasks, PP_FINISSHED_TASK_BATCH_SIZE);

	pp_task_queue_lock(&manager->queue);

	while (PP_FINISSHED_TASK_BATCH_SIZE > tasks.values_num)
	{
		if (NULL != (task = pp_task_queue_pop_done(&manager->queue)))
		{
			switch (task->type)
			{
				case ZBX_PP_TASK_VALUE:
					pp_manager_requeue_value_task(manager, task);
					break;
				case ZBX_PP_TASK_DEPENDENT:
					task = pp_manager_requeue_dependent_task(manager, task);
					break;
				case ZBX_PP_TASK_SEQUENCE:
					task = pp_manager_requeue_sequence_task(manager, task);
					break;
				default:
					break;
			}
		}

		if (NULL == task)
			break;

		zbx_vector_pp_task_append(&tasks, task);

		/* WDN */
		pp_processed_total++;

	}

	pp_task_queue_unlock(&manager->queue);

	for (int i = 0; i < tasks.values_num; i++)
	{
		pp_warnf("flush task %p type:%u itemid:%llu", tasks.values[i], tasks.values[i]->type,
				tasks.values[i]->itemid);
	}

	zbx_vector_pp_task_clear_ext(&tasks, pp_task_free);
	zbx_vector_pp_task_destroy(&tasks);
}

/* WDN: debug */

#include "zbxvariant.h"

static zbx_pp_item_t	*pp_manager_add_item(zbx_pp_manager_t *manager, zbx_uint64_t itemid, unsigned char type,
		unsigned char value_type, zbx_pp_process_mode_t preproc_mode)
{
	zbx_pp_item_t	item_local = {.itemid = itemid};
	zbx_pp_item_t	*item;

	item = zbx_hashset_insert(&manager->items, &item_local, sizeof(item_local));
	pp_item_init(item, type, value_type, 0, preproc_mode);

	return item;
}

static void	pp_add_item_preproc(zbx_pp_item_t *item, unsigned char type, const char *params,
		unsigned char error_handler, const char *error_handler_params)
{
	int	last = item->preproc->steps_num++;

	item->preproc->steps = (zbx_pp_step_t *)zbx_realloc(item->preproc->steps,
			item->preproc->steps_num * sizeof(zbx_pp_step_t));

	item->preproc->steps[last].type = type;
	item->preproc->steps[last].params = zbx_strdup(NULL, params);
	item->preproc->steps[last].error_handler = error_handler;

	if (NULL != error_handler_params)
		item->preproc->steps[last].error_handler_params = zbx_strdup(NULL, error_handler_params);
	else
		item->preproc->steps[last].error_handler_params = NULL;

	if (SUCCEED == pp_preproc_has_history(type))
		item->preproc->history_num++;
}

static void	pp_add_item_dep(zbx_pp_item_t *item, zbx_uint64_t dep_itemid)
{
	int	last = item->preproc->dep_itemids_num++;

	item->preproc->dep_itemids = (zbx_uint64_t *)zbx_realloc(item->preproc->dep_itemids,
			item->preproc->dep_itemids_num * sizeof(zbx_uint64_t));

	item->preproc->dep_itemids[last] = dep_itemid;
}

static void	pp_manager_update_item(zbx_pp_manager_t *manager, zbx_uint64_t itemid, unsigned char type,
		unsigned char value_type, zbx_pp_process_mode_t preproc_mode)
{
	zbx_pp_item_t	*item;

	if (NULL != (item = zbx_hashset_search(&manager->items, &itemid)))
	{
		pp_item_preproc_release(item->preproc);
		item->preproc = pp_item_preproc_create(type, value_type, 0);
		item->preproc->mode = preproc_mode;
	}
}

static void	test_tasks(zbx_pp_manager_t * manager)
{
	zbx_variant_t	value;
	zbx_timespec_t	ts;
	zbx_pp_item_t	*item, *item2;

	item = pp_manager_add_item(manager, 1001, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_SERIAL);
	item2 = pp_manager_add_item(manager, 1002, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);
	pp_manager_add_item(manager, 1003, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);
	pp_manager_add_item(manager, 1004, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);

	pp_add_item_dep(item, 1002);
	pp_add_item_dep(item, 1003);
	pp_add_item_dep(item, 1004);

	pp_manager_add_item(manager, 1005, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);
	pp_manager_add_item(manager, 1006, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);
	pp_manager_add_item(manager, 1007, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64, ZBX_PP_PROCESS_PARALLEL);

	pp_add_item_dep(item2, 1005);
	pp_add_item_dep(item2, 1006);
	pp_add_item_dep(item2, 1007);

	zbx_variant_set_ui64(&value, 1);
	zbx_timespec(&ts);

	pp_task_queue_lock(&manager->queue);

	pp_manager_queue_preproc(manager, 1001, &value, ts);

	pp_task_queue_unlock(&manager->queue);
	pp_task_queue_notify_all(&manager->queue);

	for (int i = 0; i < 10; i++)
	{
		printf("==== iteration: %d\n", i);
		pp_manager_process_finished(manager);
		sleep(1);
	}
}

static void     snapshot_start(struct timeval *s1)
{
	gettimeofday(s1, NULL);
}

static int      snapshot_end(struct timeval *s1)
{
	struct timeval  s2;
	int             diff;

	gettimeofday(&s2, NULL);


	diff = s2.tv_sec - s1->tv_sec;

	if (s2.tv_usec < s1->tv_usec)
	{
		s2.tv_usec += 1000000;
		diff--;
	}

	diff = diff * 1000 + (s2.tv_usec - s1->tv_usec) / 1000;

	return diff;
}

#define PP_PERF_ITEMS		100
#define PP_PERF_ITERATIONS	20000

static void	test_perf(zbx_pp_manager_t *manager)
{
	zbx_variant_t	value;
	zbx_timespec_t	ts;
	struct timeval	s1;
	int		i, j;
	double		secs;

	for (i = 0; i < PP_PERF_ITEMS; i++)
	{
		pp_manager_add_item(manager, 1001 + i, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_UINT64,
				ZBX_PP_PROCESS_PARALLEL);
	}

	zbx_variant_set_ui64(&value, 1);
	zbx_timespec(&ts);

	snapshot_start(&s1);

	for (i = 0; i < PP_PERF_ITERATIONS; i++)
	{
		pp_task_queue_lock(&manager->queue);

		for (j = 0; j < PP_PERF_ITEMS; j++)
			pp_manager_queue_preproc(manager, 1001 + j, &value, ts);

		pp_task_queue_unlock(&manager->queue);
		pp_manager_process_finished(manager);
	}

	printf("wait while finished: %d\n", pp_processed_total);

	while (PP_PERF_ITEMS * PP_PERF_ITERATIONS != pp_processed_total)
		pp_manager_process_finished(manager);

	snapshot_end(&s1);

	secs = (double)snapshot_end(&s1) / 1000;

	printf("RESULT %d values in %.3f seconds (%.3f values/sec)\n",
			pp_processed_total, secs, (double)pp_processed_total / secs);
}

static void	test_preproc(zbx_pp_manager_t * manager)
{
	zbx_variant_t	value;
	zbx_timespec_t	ts;
	zbx_pp_item_t	*item1, *item2, *item3;

	item1 = pp_manager_add_item(manager, 1001, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_STR, ZBX_PP_PROCESS_SERIAL);
	item2 = pp_manager_add_item(manager, 1002, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_STR, ZBX_PP_PROCESS_PARALLEL);
	item3 = pp_manager_add_item(manager, 1003, ITEM_TYPE_TRAPPER, ITEM_VALUE_TYPE_STR, ZBX_PP_PROCESS_PARALLEL);

	/*
	pp_add_item_dep(item1, 1002);
	pp_add_item_dep(item1, 1003);
	*/

	pp_add_item_preproc(item1, ZBX_PREPROC_STR_REPLACE, "xyz\n123", 0, NULL);


	/*zbx_variant_set_str(&value, "regex validation error"); */
	/* zbx_variant_set_str(&value, "[{\"id\":1,\"name\":\"one\"},{\"id\":2,\"name\":\"two\"}]"); */
	/* zbx_variant_set_str(&value, "{\"error\":\"error from the json\""); */
	/* zbx_variant_set_str(&value, "<root><el><id>1</id><name>one</name></el><el><id>2</id><name>two</name></el></root>"); */



	zbx_timespec(&ts);

	pp_task_queue_lock(&manager->queue);

	zbx_variant_set_str(&value, "string xyz to replace");

	pp_manager_queue_preproc(manager, 1001, &value, ts);

	pp_task_queue_unlock(&manager->queue);
	pp_task_queue_notify_all(&manager->queue);

	for (int i = 0; i < 4; i++)
	{
		printf("==== iteration: %d\n", i);
		pp_manager_process_finished(manager);
		sleep(1);
	}
}

int	test_pp(void)
{
	zbx_pp_manager_t	manager;
	char			*error = NULL;

	if (SUCCEED != pp_manager_init(&manager, 1, &error))
	{
		printf("Failed to initialize preprocessing subsystem: %s\n", error);
		zbx_free(error);
		exit(EXIT_FAILURE);
	}

	/* test_perf(&manager) */
	/* test_tasks(&manager); */
	test_preproc(&manager);

	printf("==== shutting down...\n");
	pp_manager_destroy(&manager);

	exit(0);
}

