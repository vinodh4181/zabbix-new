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
#include "log.h"


#define PP_WORKER_INIT_NONE	0x00
#define PP_WORKER_INIT_THREAD	0x01

static void	*pp_worker_start(void *arg)
{
	zbx_pp_worker_t		*worker = (zbx_pp_worker_t *)arg;
	zbx_pp_task_queue_t	*queue = worker->queue;
	int			err;
	zbx_pp_task_t		*task;

	printf("[%lu] start worker\n", pthread_self());

	worker->stop = 0;

	pp_task_queue_register_worker(queue);
	pp_task_queue_lock(queue);

	while (0 == worker->stop)
	{
		if (NULL != (task = pp_task_queue_pop_pending(queue)))
		{
			pp_task_queue_unlock(queue);

			// TODO: process task

			printf("[%lu] process task %llu\n", pthread_self(), (zbx_uint64_t)task->data);

			pp_task_free(task);

			// TODO: push either new pending tasks or finished results to queue

			pp_task_queue_lock(queue);

			continue;
		}

		if (SUCCEED != pp_task_queue_wait(queue))
			worker->stop = 1;
	}

	pp_task_queue_deregister_worker(queue);
	pp_task_queue_unlock(queue);

	printf("[%lu] stop worker\n", pthread_self());

	return (void *)0;
}

int	pp_worker_init(zbx_pp_worker_t *worker, zbx_pp_task_queue_t *queue, char **error)
{
	int	err, ret = FAIL;

	worker->queue = queue;

	if (0 != (err = pthread_create(&worker->thread, NULL, pp_worker_start, (void *)worker)))
	{
		*error = zbx_dsprintf(NULL, "cannot craete thread: %s", zbx_strerror(err));
		goto out;
	}
	worker->init_flags |= PP_WORKER_INIT_THREAD;

	ret = SUCCEED;
out:
	if (FAIL == ret)
		pp_worker_destroy(worker);

	return err;
}

void	pp_worker_destroy(zbx_pp_worker_t *worker)
{
	if (0 != (worker->init_flags & PP_WORKER_INIT_THREAD))
	{
		void	*retval;

		worker->stop = 1;
		pthread_cond_broadcast(&worker->queue->event);
		pthread_join(worker->thread, &retval);
	}

	worker->init_flags = PP_WORKER_INIT_NONE;
}
