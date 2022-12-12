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

#include "zbxcommon.h"
#include "log.h"

#define PP_WORKER_INIT_NONE	0x00
#define PP_WORKER_INIT_THREAD	0x01

/* WDN */
/* #define sleep(x) */

static zbx_pp_task_t	*pp_task_process_test(zbx_pp_task_t *in)
{
	zbx_pp_task_t	*task = pp_task_test_out_create(in);
	sleep(1);

	return task;
}

static zbx_pp_task_t	*pp_task_process_value(zbx_pp_task_t *in)
{
	zbx_pp_task_t	*task = pp_task_value_out_create(in);

	sleep(1);

	return task;
}

static zbx_pp_task_t	*pp_task_process_dependent(zbx_pp_task_t *in)
{
	zbx_pp_task_t			*task = pp_task_dependent_out_create(in);
	zbx_pp_task_dependent_in_t	*d_in = (zbx_pp_task_dependent_in_t *)PP_TASK_DATA(in);
	zbx_pp_task_dependent_out_t	*d_out = (zbx_pp_task_dependent_out_t *)PP_TASK_DATA(task);

	d_out->cache = pp_cache_create(0);

	/* TODO: set either created cache or the input value */
	zbx_variant_copy(&d_out->cache->value, &d_in->value);

	sleep(1);

	return task;
}

static zbx_pp_task_t	*pp_task_process_sequence(zbx_pp_task_t *in)
{
	zbx_pp_task_t	*task = pp_task_sequence_out_create(in);

	sleep(1);

	return task;
}

static void	*pp_worker_start(void *arg)
{
	zbx_pp_worker_t	*worker = (zbx_pp_worker_t *)arg;
	zbx_pp_queue_t	*queue = worker->queue;
	int		err;
	zbx_pp_task_t	*in, *out;

	/* TODO: for debug logging, remove */
	char	name[64];

	zbx_snprintf(name, sizeof(name), "worker%02d", worker->id);
	pp_log_init(name);

	pp_log("starting ...");

	worker->stop = 0;

	pp_task_queue_register_worker(queue);
	pp_task_queue_lock(queue);

	while (0 == worker->stop)
	{
		if (NULL != (in = pp_task_queue_pop_new(queue)))
		{
			pp_task_queue_unlock(queue);

			/* TODO: process task */

			pp_log("process task %p type:%u itemid:%llu", in, in->type, in->itemid);

			switch (in->type)
			{
				case ZBX_PP_TASK_TEST_IN:
					out = pp_task_process_test(in);
					break;
				case ZBX_PP_TASK_VALUE_IN:
				case ZBX_PP_TASK_VALUE_SEQ_IN:
					out = pp_task_process_value(in);
					break;
				case ZBX_PP_TASK_DEPENDENT_IN:
					out = pp_task_process_dependent(in);
					break;
				case ZBX_PP_TASK_SEQUENCE_IN:
					out = pp_task_process_sequence(in);
					break;
			}

			pp_log("done");

			pp_task_queue_lock(queue);
			pp_task_queue_push_done(queue, out);

			continue;
		}

		if (SUCCEED != pp_task_queue_wait(queue))
			worker->stop = 1;
	}

	pp_task_queue_deregister_worker(queue);
	pp_task_queue_unlock(queue);

	pp_log("stop worker");

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
