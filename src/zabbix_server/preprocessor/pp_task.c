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

#include "pp_task.h"

#include "zbxcommon.h"
#include "log.h"
#include "zbxalgo.h"
#include "zbxsysinc.h"

#define PP_TASK_QUEUE_INIT_NONE		0x00
#define PP_TASK_QUEUE_INIT_LOCK		0x01
#define PP_TASK_QUEUE_INIT_EVENT	0x02

void	pp_task_free(zbx_pp_task_t *task);

static zbx_pp_task_t	*pp_task_create(size_t size)
{
	zbx_pp_task_t	*task;

	task = (zbx_pp_task_t *)zbx_malloc(NULL, offsetof(zbx_pp_task_t, data) + size);

	printf("[%lu] pp_task_create(%llu) -> %p\n", pthread_self(), size, task);

	return task;
}


/* TODO pass socket */
zbx_pp_task_t	*pp_task_test_in_create(zbx_pp_item_t *item, zbx_variant_t *value, zbx_timespec_t ts)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_test_in_t));
	zbx_pp_task_test_in_t	*d = (zbx_pp_task_test_in_t *)PP_TASK_DATA(task);

	task->itemid = item->itemid;
	task->type = ZBX_PP_TASK_TEST_IN;
	d->value = *value;
	d->ts = ts;

	d->preproc = pp_item_copy_preproc(item);

	return task;
}

static void	pp_task_test_in_clear(zbx_pp_task_test_in_t *task)
{
	printf("[%lu] pp_task_test_in_clear(), value: %s\n", pthread_self(), zbx_variant_value_desc(&task->value));

	zbx_variant_clear(&task->value);
	pp_item_preproc_release(task->preproc);
}

zbx_pp_task_t	*pp_task_value_in_create(zbx_pp_item_t *item, zbx_variant_t *value, zbx_timespec_t ts)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_value_in_t));
	zbx_pp_task_value_in_t	*d = (zbx_pp_task_value_in_t *)PP_TASK_DATA(task);

	task->itemid = item->itemid;
	task->type = ZBX_PP_TASK_VALUE_IN;
	d->value = *value;
	d->ts = ts;

	d->preproc = pp_item_copy_preproc(item);

	return task;
}

static void	pp_task_value_in_clear(zbx_pp_task_value_in_t *task)
{
	printf("[%lu] pp_task_value_in_clear(), value: %s\n", pthread_self(), zbx_variant_value_desc(&task->value));


	zbx_variant_clear(&task->value);
	pp_item_preproc_release(task->preproc);
}

zbx_pp_task_t	*pp_task_value_seq_in_create(zbx_pp_item_t *item, zbx_variant_t *value, zbx_timespec_t ts)
{
	zbx_pp_task_t	*task = pp_task_value_in_create(item, value, ts);

	task->type = ZBX_PP_TASK_VALUE_SEQ_IN;

	return task;
}

static void	pp_task_dependent_in_clear(zbx_pp_task_dependent_in_t *task)
{
	ZBX_UNUSED(task);
}

zbx_pp_task_t	*pp_task_sequence_in_create(zbx_uint64_t itemid)
{
	zbx_pp_task_t			*task = pp_task_create(sizeof(zbx_pp_task_sequence_in_t));
	zbx_pp_task_sequence_in_t	*d = (zbx_pp_task_sequence_in_t *)PP_TASK_DATA(task);

	task->itemid = itemid;
	task->type = ZBX_PP_TASK_SEQUENCE_IN;
	zbx_list_create(&d->tasks);

	return task;
}

static void	pp_task_sequence_in_clear(zbx_pp_task_sequence_in_t *seq)
{
	zbx_pp_task_t	*task;
	printf("[%lu] pp_task_sequence_in_clear()\n", pthread_self());

	while (SUCCEED == zbx_list_pop(&seq->tasks, (void **)&task))
		pp_task_free(task);

	zbx_list_destroy(&seq->tasks);
}

zbx_pp_task_t	*pp_task_test_out_create(zbx_pp_task_t *in)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_test_out_t));
	zbx_pp_task_test_out_t	*d = (zbx_pp_task_test_out_t *)PP_TASK_DATA(task);

	task->itemid = in->itemid;
	task->type = ZBX_PP_TASK_TEST_OUT;
	d->in = in;

	return task;
}

static void	pp_task_test_out_clear(zbx_pp_task_test_out_t *task)
{
	pp_task_free(task->in);
}

zbx_pp_task_t	*pp_task_value_out_create(zbx_pp_task_t *in)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_value_out_t));
	zbx_pp_task_value_out_t	*d = (zbx_pp_task_value_out_t *)PP_TASK_DATA(task);

	task->itemid = in->itemid;
	task->type = ZBX_PP_TASK_VALUE_OUT;
	d->in = in;

	printf("[%lu] pp_task_value_out_create(%p), data:%p data->in:%p\n", pthread_self(), in, d, d->in);

	return task;
}

static void	pp_task_value_out_clear(zbx_pp_task_value_out_t *task)
{
	printf("[%lu] pp_task_value_out_clear(), data:%p data->in:%p\n", pthread_self(), task, task->in);
	pp_task_free(task->in);
}

zbx_pp_task_t	*pp_task_dependent_out_create(zbx_pp_task_t *in)
{
	zbx_pp_task_t			*task = pp_task_create(sizeof(zbx_pp_task_dependent_out_t));
	zbx_pp_task_dependent_out_t	*d = (zbx_pp_task_dependent_out_t *)PP_TASK_DATA(task);

	task->itemid = in->itemid;
	task->type = ZBX_PP_TASK_DEPENDENT_OUT;
	d->in = in;

	return task;
}

static void	pp_task_dependent_out_clear(zbx_pp_task_dependent_out_t *task)
{
	pp_task_free(task->in);
}

zbx_pp_task_t	*pp_task_sequence_out_create(zbx_pp_task_t *in)
{
	zbx_pp_task_t			*task = pp_task_create(sizeof(zbx_pp_task_sequence_out_t));
	zbx_pp_task_sequence_out_t	*d = (zbx_pp_task_sequence_out_t *)PP_TASK_DATA(task);

	task->itemid = in->itemid;
	task->type = ZBX_PP_TASK_SEQUENCE_OUT;
	d->in = in;

	return task;
}

static void	pp_task_sequence_out_clear(zbx_pp_task_sequence_out_t *task)
{
	pp_task_free(task->in);
}

void	pp_task_free(zbx_pp_task_t *task)
{
	printf("[%lu] pp_task_free(%p)\n", pthread_self(), task);

	if (NULL == task)
		return;

	switch (task->type)
	{
		case ZBX_PP_TASK_TEST_IN:
			pp_task_test_in_clear((zbx_pp_task_test_in_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_VALUE_IN:
		case ZBX_PP_TASK_VALUE_SEQ_IN:
			pp_task_value_in_clear((zbx_pp_task_value_in_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_DEPENDENT_IN:
			pp_task_dependent_in_clear((zbx_pp_task_dependent_in_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_SEQUENCE_IN:
			pp_task_sequence_in_clear((zbx_pp_task_sequence_in_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_TEST_OUT:
			pp_task_test_out_clear((zbx_pp_task_test_out_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_VALUE_OUT:
		case ZBX_PP_TASK_VALUE_SEQ_OUT:
			pp_task_value_out_clear((zbx_pp_task_value_out_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_DEPENDENT_OUT:
			pp_task_dependent_out_clear((zbx_pp_task_dependent_out_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_SEQUENCE_OUT:
			pp_task_sequence_out_clear((zbx_pp_task_sequence_out_t *)PP_TASK_DATA(task));
			break;
	}

	zbx_free(task);
}

static void	pp_task_sequence_clear(void *d)
{
	zbx_pp_task_sequence_t	*seq = (zbx_pp_task_sequence_t *)d;

	pp_task_free(seq->task);
}

int	pp_task_queue_init(zbx_pp_task_queue_t *queue, char **error)
{
	int	err, ret = FAIL;

	queue->workers_num = 0;
	zbx_list_create(&queue->pending);
	zbx_list_create(&queue->immediate);
	zbx_list_create(&queue->finished);

	zbx_hashset_create_ext(&queue->sequences, 100, ZBX_DEFAULT_UINT64_HASH_FUNC, ZBX_DEFAULT_UINT64_COMPARE_FUNC,
			pp_task_sequence_clear, ZBX_DEFAULT_MEM_MALLOC_FUNC, ZBX_DEFAULT_MEM_REALLOC_FUNC,
			ZBX_DEFAULT_MEM_FREE_FUNC);

	if (0 != (err = pthread_mutex_init(&queue->lock, NULL)))
	{
		*error = zbx_dsprintf(NULL, "cannot initialize mutex: %s", zbx_strerror(err));
		goto out;
	}
	queue->init_flags |= PP_TASK_QUEUE_INIT_LOCK;

	if (0 != (err = pthread_cond_init(&queue->event, NULL)))
	{
		*error = zbx_dsprintf(NULL, "cannot initialize conditional variable: %s", zbx_strerror(err));
		goto out;
	}
	queue->init_flags |= PP_TASK_QUEUE_INIT_EVENT;

	ret = SUCCEED;
out:
	if (FAIL == ret)
		pp_task_queue_destroy(queue);

	return ret;
}

void	pp_task_queue_destroy(zbx_pp_task_queue_t *queue)
{
	if (0 != (queue->init_flags & PP_TASK_QUEUE_INIT_LOCK))
		pthread_mutex_destroy(&queue->lock);

	if (0 != (queue->init_flags & PP_TASK_QUEUE_INIT_EVENT))
		pthread_cond_destroy(&queue->event);

	zbx_hashset_destroy(&queue->sequences);

	zbx_list_destroy(&queue->pending);
	zbx_list_destroy(&queue->immediate);
	zbx_list_destroy(&queue->finished);

	queue->init_flags = PP_TASK_QUEUE_INIT_NONE;
}

void	pp_task_queue_lock(zbx_pp_task_queue_t *queue)
{
	pthread_mutex_lock(&queue->lock);
}

void	pp_task_queue_unlock(zbx_pp_task_queue_t *queue)
{
	pthread_mutex_unlock(&queue->lock);
}

void	pp_task_queue_register_worker(zbx_pp_task_queue_t *queue)
{
	queue->workers_num++;
}

void	pp_task_queue_deregister_worker(zbx_pp_task_queue_t *queue)
{
	queue->workers_num--;
}

void	pp_task_queue_push_immediate(zbx_pp_task_queue_t *queue, zbx_pp_task_t *task)
{
	zbx_list_append(&queue->immediate, task, NULL);
}

static zbx_pp_task_t	*pp_task_queue_add_sequence(zbx_pp_task_queue_t *queue, zbx_pp_task_t *task)
{
	zbx_pp_task_sequence_t	*sequence;
	zbx_pp_task_t		*new_task;

	if (NULL == (sequence = (zbx_pp_task_sequence_t *)zbx_hashset_search(&queue->sequences, &task->itemid)))
	{
		zbx_pp_task_sequence_t	sequence_local = {.itemid = task->itemid};

		sequence = (zbx_pp_task_sequence_t *)zbx_hashset_insert(&queue->sequences, &sequence_local,
				sizeof(sequence_local));

		sequence->task = pp_task_sequence_in_create(task->itemid);
		pp_task_queue_push_immediate(queue, task);

		new_task = sequence->task;
	}
	else
		new_task = NULL;

	zbx_pp_task_sequence_in_t	*seq_task = (zbx_pp_task_sequence_in_t *)PP_TASK_DATA(sequence->task);

	zbx_list_append(&seq_task->tasks, task, NULL);

	return new_task;
}

void	pp_task_queue_push_new(zbx_pp_task_queue_t *queue, zbx_pp_item_t *item, zbx_pp_task_t *task)
{
	if (ZBX_PP_TASK_TEST_IN == task->type)
	{
		zbx_list_append(&queue->immediate, task, NULL);
		return;
	}

	/* value processing task */

	if (ITEM_TYPE_INTERNAL != item->type)
	{
		zbx_list_append(&queue->pending, task, NULL);
		return;
	}

	if (ZBX_PP_TASK_VALUE_IN == task->type)
	{
		zbx_list_append(&queue->immediate, task, NULL);
		return;
	}

	(void)pp_task_queue_add_sequence(queue, task);
}

zbx_pp_task_t	*pp_task_queue_pop_new(zbx_pp_task_queue_t *queue)
{
	zbx_pp_task_t	*task = NULL;

	if (SUCCEED == zbx_list_pop(&queue->immediate, (void **)&task))
		return (zbx_pp_task_t *)task;

	while (SUCCEED == zbx_list_pop(&queue->pending, (void **)&task))
	{
		if (ZBX_PP_TASK_VALUE_SEQ_IN == task->type)
			task = pp_task_queue_add_sequence(queue, task);

		if (NULL != task)
			return task;
	}

	return NULL;
}

void	pp_task_queue_push_done(zbx_pp_task_queue_t *queue, zbx_pp_task_t *task)
{
	zbx_list_append(&queue->finished, task, NULL);
}

zbx_pp_task_t	*pp_task_queue_pop_done(zbx_pp_task_queue_t *queue)
{
	zbx_pp_task_t	*task;

	if (SUCCEED == zbx_list_pop(&queue->finished, (void **)&task))
		return task;

	return NULL;
}

int	pp_task_queue_wait(zbx_pp_task_queue_t *queue)
{
	int	err;

	if (0 != (err = pthread_cond_wait(&queue->event, &queue->lock)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot wait for conditional variable: %s", zbx_strerror(err));
		return FAIL;
	}

	return SUCCEED;
}

void	pp_task_queue_notify_all(zbx_pp_task_queue_t *queue)
{
	int	err;

	if (0 != (err = pthread_cond_broadcast(&queue->event)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot broadcast conditional variable: %s", zbx_strerror(err));
	}
}
