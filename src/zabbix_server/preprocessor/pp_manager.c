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

#include "zbxcommon.h"
#include "zbxalgo.h"

#define PP_STARTUP_TIMEOUT	10

typedef struct
{
	zbx_pp_worker_t		*workers;
	int			workers_num;

	zbx_list_t		tasks;

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

	/* wait for threads to start */
	time_start = time(NULL);

	while (started_num != workers_num)
	{
		if (time_start + PP_STARTUP_TIMEOUT < time(NULL))
		{
			*error = zbx_strdup(NULL, "timeout occured while waiting for workers to start");
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
}


int	test_pp(void)
{
	zbx_pp_manager_t	manager;
	char			*error = NULL;
	zbx_uint64_t		taskid = 1;

	if (SUCCEED != pp_manager_init(&manager, 2, &error))
	{
		printf("Failed to initialize preprocessing subsystem: %s\n", error);
		zbx_free(error);
		exit(EXIT_FAILURE);
	}

	for (int i = 0; i < 10; i++)
	{
		printf("batch %d ...\n", i);

		pthread_mutex_lock(&manager.queue.lock);

		for (int j = 0; j < 10; j++)
		{
			zbx_pp_task_t	*task = (zbx_pp_task_t *)zbx_malloc(NULL, sizeof(zbx_pp_task_t));

			task->data = (void *)taskid++;
			pp_task_queue_push_pending(&manager.queue, task);
		}
		pthread_mutex_unlock(&manager.queue.lock);
		pthread_cond_broadcast(&manager.queue.event);

		//sleep(1);
	}

	sleep(1);

	printf("shutting down...\n");
	pp_manager_destroy(&manager);

	exit(0);
}



