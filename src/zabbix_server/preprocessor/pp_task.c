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

#include "log.h"

#define PP_TASK_QUEUE_INIT_NONE		0x00
#define PP_TASK_QUEUE_INIT_LOCK		0x01
#define PP_TASK_QUEUE_INIT_EVENT	0x02


int	pp_task_queue_init(zbx_pp_task_queue_t *queue, char **error)
{
	int	err, ret = FAIL;

	queue->workers_num = 0;
	zbx_list_create(&queue->pending);
	zbx_list_create(&queue->finished);
	zbx_list_create(&queue->direct);

	zbx_hashset_create(&queue->items, 100, ZBX_DEFAULT_UINT64_HASH_FUNC, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

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

	zbx_list_destroy(&queue->pending);
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

void	pp_task_queue_push_pending(zbx_pp_task_queue_t *queue, zbx_pp_task_t *task)
{
	zbx_list_append(&queue->pending, task, NULL);
}

zbx_pp_task_t	*pp_task_queue_pop_pending(zbx_pp_task_queue_t *queue)
{
	void	*task;

	if (SUCCEED == zbx_list_pop(&queue->pending, &task))
		return (zbx_pp_task_t *)task;

	return NULL;
}

void	pp_task_queue_push_finished(zbx_pp_task_queue_t *queue, zbx_pp_task_t *task)
{
	zbx_list_append(&queue->finished, task, NULL);
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

void	pp_task_free(zbx_pp_task_t *task)
{
	zbx_free(task);
}

