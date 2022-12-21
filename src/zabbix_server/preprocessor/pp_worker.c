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
#include "pp_log.h"
#include "pp_cache.h"
#include "pp_queue.h"
#include "pp_execute.h"

#include "zbxcommon.h"
#include "log.h"

#define PP_WORKER_INIT_NONE	0x00
#define PP_WORKER_INIT_THREAD	0x01

/* WDN */
/* #define sleep(x) */

static void	pp_task_process_test(zbx_pp_context_t *ctx, zbx_pp_task_t *task)
{
	zbx_pp_task_test_t	*d = (zbx_pp_task_test_t *)PP_TASK_DATA(task);
	zbx_variant_t		result;

	pp_execute(ctx, d->preproc, NULL, &d->value, d->ts, &result);

	/* TODO: send back result to the ipc client */
}

static void	pp_task_process_value(zbx_pp_context_t *ctx, zbx_pp_task_t *task)
{
	zbx_pp_task_value_t	*d = (zbx_pp_task_value_t *)PP_TASK_DATA(task);

	pp_execute(ctx, d->preproc, d->cache, &d->value, d->ts, &d->result);
}

static void	pp_task_process_dependent(zbx_pp_context_t *ctx, zbx_pp_task_t *task)
{
	zbx_pp_task_dependent_t	*d = (zbx_pp_task_dependent_t *)PP_TASK_DATA(task);
	zbx_pp_task_value_t	*d_first = (zbx_pp_task_value_t *)PP_TASK_DATA(d->first_task);

	d->cache = pp_cache_create(d_first->preproc, &d_first->value);
	pp_execute(ctx, d_first->preproc, d->cache, &d_first->value, d_first->ts, &d_first->result);
}

static	void	pp_task_process_sequence(zbx_pp_context_t *ctx, zbx_pp_task_t *task_seq)
{
	zbx_pp_task_sequence_t	*d_seq = (zbx_pp_task_sequence_t *)PP_TASK_DATA(task_seq);
	zbx_pp_task_t		*task;

	if (SUCCEED == zbx_list_peek(&d_seq->tasks, (void **)&task))
	{
		switch (task->type)
		{
			case ZBX_PP_TASK_VALUE:
			case ZBX_PP_TASK_VALUE_SEQ:
				pp_task_process_value(ctx, task);
				break;
			case ZBX_PP_TASK_DEPENDENT:
				pp_task_process_dependent(ctx, task);
				break;
			default:
				THIS_SHOULD_NEVER_HAPPEN;
				break;
		}
	}
}

static void	*pp_worker_start(void *arg)
{
	zbx_pp_worker_t	*worker = (zbx_pp_worker_t *)arg;
	zbx_pp_queue_t	*queue = worker->queue;
	zbx_pp_task_t	*in;

	/* TODO: for debug logging, remove */
	char	name[64];

	zbx_snprintf(name, sizeof(name), "worker%02d", worker->id);
	pp_log_init(name, 1);

	pp_infof("starting ...");

	worker->stop = 0;

	pp_task_queue_register_worker(queue);
	pp_task_queue_lock(queue);

	while (0 == worker->stop)
	{
		if (NULL != (in = pp_task_queue_pop_new(queue)))
		{
			pp_task_queue_unlock(queue);

			/* TODO: process task */

			pp_warnf("process task %p type:%u itemid:%llu", in, in->type, in->itemid);

			switch (in->type)
			{
				case ZBX_PP_TASK_TEST:
					pp_task_process_test(&worker->execute_ctx, in);
					break;
				case ZBX_PP_TASK_VALUE:
				case ZBX_PP_TASK_VALUE_SEQ:
					pp_task_process_value(&worker->execute_ctx, in);
					break;
				case ZBX_PP_TASK_DEPENDENT:
					pp_task_process_dependent(&worker->execute_ctx, in);
					break;
				case ZBX_PP_TASK_SEQUENCE:
					pp_task_process_sequence(&worker->execute_ctx, in);
					break;
			}

			pp_task_queue_lock(queue);
			pp_task_queue_push_done(queue, in);

			continue;
		}

		if (SUCCEED != pp_task_queue_wait(queue))
			worker->stop = 1;
	}

	pp_task_queue_deregister_worker(queue);
	pp_task_queue_unlock(queue);

	pp_infof("stop worker");

	return (void *)0;
}

int	pp_worker_init(zbx_pp_worker_t *worker, zbx_pp_queue_t *queue, char **error)
{
	int	err, ret = FAIL;

	worker->queue = queue;

	if (0 != (err = pthread_create(&worker->thread, NULL, pp_worker_start, (void *)worker)))
	{
		*error = zbx_dsprintf(NULL, "cannot craete thread: %s", zbx_strerror(err));
		goto out;
	}
	worker->init_flags |= PP_WORKER_INIT_THREAD;

	pp_context_init(&worker->execute_ctx);

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

	pp_context_destroy(&worker->execute_ctx);

	worker->init_flags = PP_WORKER_INIT_NONE;
}
