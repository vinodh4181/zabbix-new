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

#ifndef ZABBIX_MONITOR_H
#define ZABBIX_MONITOR_H

#include "zbxalgo.h"

#define ZBX_MONITOR_DELAY	1

#define ZBX_MONITOR_AGGR_FUNC_ONE	0
#define ZBX_MONITOR_AGGR_FUNC_AVG	1
#define ZBX_MONITOR_AGGR_FUNC_MAX	2
#define ZBX_MONITOR_AGGR_FUNC_MIN	3

#define ZBX_PROCESS_STATE_IDLE		0
#define ZBX_PROCESS_STATE_BUSY		1
#define ZBX_PROCESS_STATE_COUNT		2	/* number of process states */

typedef struct zbx_monitor zbx_monitor_t;

typedef struct
{
	double	busy_max;
	double	busy_min;
	double	busy_avg;
	double	idle_max;
	double	idle_min;
	double	idle_avg;
}
zbx_monitor_stats_t;

typedef struct
{
	unsigned short	counters[ZBX_PROCESS_STATE_COUNT];
}
zbx_monitor_state_t;

typedef void (*zbx_monitor_sync_func_t)(void *data);

typedef struct
{
	zbx_monitor_sync_func_t	lock;
	zbx_monitor_sync_func_t	unlock;
	void			*data;
}
zbx_monitor_sync_t;

void	zbx_monitor_sync_init(zbx_monitor_sync_t *sync, zbx_monitor_sync_func_t lock, zbx_monitor_sync_func_t unlock,
		void *data);

size_t	zbx_monitor_get_size(int units_num);
zbx_monitor_t	*zbx_monitor_create_ext(int units_num, zbx_monitor_sync_t *sync,
		zbx_mem_malloc_func_t mem_malloc_func, zbx_mem_realloc_func_t mem_realloc_func,
		zbx_mem_free_func_t mem_free_func);
zbx_monitor_t	*zbx_monitor_create(int units_num,  const zbx_monitor_sync_t *sync);
void	zbx_monitor_destroy(zbx_monitor_t *monitor);

void	zbx_monitor_update(zbx_monitor_t *monitor, int index, unsigned char state);
void	zbx_monitor_collect(zbx_monitor_t *monitor);
int	zbx_monitor_get_stat(zbx_monitor_t *monitor, int unit_index, int count, unsigned char aggr_func,
		unsigned char state, double *value, char **error);
zbx_monitor_state_t	*zbx_monitor_get_counters(zbx_monitor_t *monitor);

#endif
