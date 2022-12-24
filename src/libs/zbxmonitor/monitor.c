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

#include "zbxmonitor.h"
#include "zbxcommon.h"
#include "zbxalgo.h"
#include "zbxmonitor.h"

#define MAX_HISTORY	60

#define ZBX_MONITOR_FLUSH_DELAY		(ZBX_MONITOR_DELAY * 0.5)

#define ZBX_PROCESS_STATE_IDLE		0
#define ZBX_PROCESS_STATE_BUSY		1
#define ZBX_PROCESS_STATE_COUNT		2	/* number of execution unit states */

/* unit state cache, updated only by the execution units themselves */
typedef struct
{
	/* the current usage statistics */
	zbx_uint64_t	counter[ZBX_PROCESS_STATE_COUNT];

	/* ticks of the last self monitoring update */
	clock_t		ticks;

	/* ticks of the last self monitoring cache flush */
	clock_t		ticks_flush;

	/* the current process state (see ZBX_PROCESS_STATE_* defines) */
	unsigned char	state;
}
zbx_monitor_unit_cache_t;

/* execution unit state statistics */
typedef struct
{
	/* historical unit state data */
	unsigned short		h_counter[ZBX_PROCESS_STATE_COUNT][MAX_HISTORY];

	/* unit state data for the current data gathering cycle */
	unsigned short		counter[ZBX_PROCESS_STATE_COUNT];

	/* the unit state that was already applied to the historical state data */
	zbx_uint64_t		counter_used[ZBX_PROCESS_STATE_COUNT];

	/* the unit state cache */
	zbx_monitor_unit_cache_t	cache;
}
zbx_monitor_unit_t;

struct zbx_monitor
{
	zbx_monitor_unit_t	*units;
	int			units_num;
	int			first;
	int			count;

	/* number of ticks per second */
	int			ticks_per_sec;

	/* ticks of the last self monitoring sync (data gathering) */
	clock_t			ticks_sync;

	zbx_monitor_sync_t	sync;

	zbx_mem_malloc_func_t	mem_malloc_func;
	zbx_mem_realloc_func_t	mem_realloc_func;
	zbx_mem_free_func_t	mem_free_func;
};

void	zbx_monitor_sync_init(zbx_monitor_sync_t *sync, zbx_monitor_sync_func_t lock, zbx_monitor_sync_func_t unlock,
		void *data)
{
	sync->lock = lock;
	sync->unlock = unlock;
	sync->data = data;
}

zbx_monitor_t	*zbx_monitor_create_ext(int units_num, const zbx_monitor_sync_t *sync,
		zbx_mem_malloc_func_t mem_malloc_func, zbx_mem_realloc_func_t mem_realloc_func,
		zbx_mem_free_func_t mem_free_func)
{
	zbx_monitor_t	*monitor;

	monitor = (zbx_monitor_t *)mem_malloc_func(NULL, sizeof(zbx_monitor_t));
	monitor->units = (zbx_monitor_unit_t *)mem_malloc_func(NULL, sizeof(zbx_monitor_unit_t) * units_num);
	memset(monitor->units, 0, sizeof(zbx_monitor_unit_t) * units_num);
	monitor->units_num = units_num;
	monitor->first = 0;
	monitor->count = 0;
	monitor->ticks_per_sec = sysconf(_SC_CLK_TCK);
	monitor->ticks_sync = 0;
	monitor->sync = *sync;

	monitor->mem_malloc_func = mem_malloc_func;
	monitor->mem_realloc_func = mem_realloc_func;
	monitor->mem_free_func = mem_free_func;

	return monitor;
}

zbx_monitor_t	*zbx_monitor_create(int units_num,  const zbx_monitor_sync_t *sync)
{
	return zbx_monitor_create_ext(units_num, sync, ZBX_DEFAULT_MEM_MALLOC_FUNC,
			ZBX_DEFAULT_MEM_REALLOC_FUNC, ZBX_DEFAULT_MEM_FREE_FUNC);
}

void	zbx_monitor_destroy(zbx_monitor_t *monitor)
{
	monitor->mem_free_func(monitor->units);
	monitor->mem_free_func(monitor);
}

/******************************************************************************
 *                                                                            *
 * Parameters: info  - [IN] caller process info                               *
 *             state - [IN] new process state; ZBX_PROCESS_STATE_*            *
 *                                                                            *
 ******************************************************************************/
void	zbx_monitor_update(zbx_monitor_t *monitor, int index, unsigned char state)
{
	zbx_monitor_unit_t	*unit;
	clock_t			ticks;
	struct tms		buf;
	int			i;

	if (0 > index || index >= monitor->units_num)
		return;

	unit = monitor->units + index;

	if (-1 == (ticks = times(&buf)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot get process times: %s", zbx_strerror(errno));
		unit->cache.state = state;
		return;
	}

	if (0 == unit->cache.ticks_flush)
	{
		unit->cache.ticks_flush = ticks;
		unit->cache.state = state;
		unit->cache.ticks = ticks;
		return;
	}

	/* update process statistics in local cache */
	unit->cache.counter[unit->cache.state] += ticks - unit->cache.ticks;

	if (ZBX_MONITOR_FLUSH_DELAY < (double)(ticks - unit->cache.ticks_flush) / monitor->ticks_per_sec)
	{
		monitor->sync.lock(monitor->sync.data);

		for (i = 0; i < ZBX_PROCESS_STATE_COUNT; i++)
		{
			/* If process did not update selfmon counter during one self monitoring data   */
			/* collection interval, then self monitor will collect statistics based on the */
			/* current process state and the ticks passed since last self monitoring data  */
			/* collection. This value is stored in counter_used and the local statistics   */
			/* must be adjusted by this (already collected) value.                         */
			if (unit->cache.counter[i] > unit->counter_used[i])
			{
				unit->cache.counter[i] -= unit->counter_used[i];
				unit->counter[i] += unit->cache.counter[i];
			}

			/* reset current cache statistics */
			unit->counter_used[i] = 0;
			unit->cache.counter[i] = 0;
		}

		unit->cache.ticks_flush = ticks;

		monitor->sync.unlock(monitor->sync.data);
	}

	/* update local self monitoring cache */
	unit->cache.state = state;
	unit->cache.ticks = ticks;
}

void	zbx_monitor_collect(zbx_monitor_t *monitor)
{
	zbx_monitor_unit_t	*unit;
	clock_t			ticks, ticks_done;
	struct tms		buf;
	unsigned char		i;
	int			index, last;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (-1 == (ticks = times(&buf)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot get process times: %s", zbx_strerror(errno));
		goto out;
	}

	if (0 == monitor->ticks_sync)
	{
		monitor->ticks_sync = ticks;
		goto out;
	}

	if (MAX_HISTORY <= (index = monitor->first + monitor->count))
		index -= MAX_HISTORY;

	if (monitor->count < MAX_HISTORY)
		monitor->count++;
	else if (++monitor->first == MAX_HISTORY)
		monitor->first = 0;

	if (0 > (last = index - 1))
		last += MAX_HISTORY;

	monitor->sync.lock(monitor->sync.data);

	ticks_done = ticks - monitor->ticks_sync;

	for (i = 0; i < monitor->units_num; i++)
	{
		unit = monitor->units + i;

		if (unit->cache.ticks_flush < monitor->ticks_sync)
		{
			/* If the process local cache was not flushed during the last self monitoring  */
			/* data collection interval update the process statistics based on the current */
			/* process state and ticks passed during the collection interval. Store this   */
			/* value so the process local self monitoring cache can be adjusted before     */
			/* flushing.                                                                   */
			unit->counter[unit->cache.state] += ticks_done;
			unit->counter_used[unit->cache.state] += ticks_done;
		}

		for (int j = 0; j < ZBX_PROCESS_STATE_COUNT; j++)
		{
			/* The data is gathered as ticks spent in corresponding states during the */
			/* self monitoring data collection interval. But in history the data are  */
			/* stored as relative values. To achieve it we add the collected data to  */
			/* the last values.                                                       */
			unit->h_counter[j][index] = unit->h_counter[j][last] + unit->counter[j];
			unit->counter[j] = 0;
		}
	}

	monitor->ticks_sync = ticks;
	monitor->sync.unlock(monitor->sync.data);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

size_t	zbx_monitor_get_size(int units_num)
{
#define MONITOR_ALIGN8(x) (((x) + 7) & (~7))

	/* monitor size, units array size + overhead for 2 allocations: monitor and units array */
	return MONITOR_ALIGN8(sizeof(zbx_monitor_t)) + MONITOR_ALIGN8(sizeof(zbx_monitor_unit_t)) * units_num +
			4 * sizeof(zbx_uint64_t);
#undef MONITOR_ALIGN8
}

int	zbx_monitor_get_stat(zbx_monitor_t *monitor, int unit_index, int count, unsigned char aggr_func,
		unsigned char state, double *value, char **error)
{
	unsigned int	total = 0, counter = 0;
	int		i, current, ret = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (0 > unit_index || unit_index >= monitor->units_num)
	{
		*error = zbx_strdup(NULL, "index out of bounds");
		goto out;
	}

	if (0 == count)
		count = monitor->units_num - unit_index;

	switch (aggr_func)
	{
		case ZBX_MONITOR_AGGR_FUNC_ONE:
			count = 1;
			break;
		case ZBX_MONITOR_AGGR_FUNC_AVG:
		case ZBX_MONITOR_AGGR_FUNC_MAX:
		case ZBX_MONITOR_AGGR_FUNC_MIN:
			break;
		default:
			*error = zbx_strdup(NULL, "unknown aggregation function");
			goto out;
	}

	monitor->sync.lock(monitor->sync.data);

	if (1 >= monitor->count)
		goto unlock;

	if (MAX_HISTORY <= (current = (monitor->first + monitor->count - 1)))
		current -= MAX_HISTORY;

	for (i = unit_index; i < unit_index + count; i++)
	{
		unsigned int	one_total = 0, one_counter;
		unsigned char	s;

		for (s = 0; s < ZBX_PROCESS_STATE_COUNT; s++)
		{
			one_total += (unsigned short)(monitor->units[i].h_counter[s][current] -
					monitor->units[i].h_counter[s][monitor->first]);
		}

		one_counter = (unsigned short)(monitor->units[i].h_counter[state][current] -
				monitor->units[i].h_counter[state][monitor->first]);

		switch (aggr_func)
		{
			case ZBX_MONITOR_AGGR_FUNC_ONE:
			case ZBX_MONITOR_AGGR_FUNC_AVG:
				total += one_total;
				counter += one_counter;
				break;
			case ZBX_MONITOR_AGGR_FUNC_MAX:
				if (0 == i || one_counter > counter)
				{
					counter = one_counter;
					total = one_total;
				}
				break;
			case ZBX_MONITOR_AGGR_FUNC_MIN:
				if (0 == i || one_counter < counter)
				{
					counter = one_counter;
					total = one_total;
				}
				break;
		}
	}

unlock:
	monitor->sync.unlock(monitor->sync.data);

	*value = (0 == total ? 0 : 100. * (double)counter / (double)total);

	ret = SUCCEED;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);

	return ret;
}

void	zbx_monitor_get_all_stats(zbx_monitor_t *monitor, zbx_monitor_stats_t *stats, int stats_num)
{
	int	i;

	for (i = 0; i < monitor->units_num; i++)
	{

	}
}
