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

#ifndef ZABBIX_PP_HISTORY_H
#define ZABBIX_PP_HISTORY_H

#include "zbxalgo.h"
#include "zbxvariant.h"
#include "zbxtime.h"

typedef struct
{
	int		index;
	zbx_variant_t	value;
	zbx_timespec_t	ts;
}
zbx_pp_step_history_t;

ZBX_VECTOR_DECL(pp_step_history, zbx_pp_step_history_t);

typedef struct
{
	zbx_vector_pp_step_history_t	step_history;
}
zbx_pp_history_t;

zbx_pp_history_t	*pp_history_create(int history_num);
void	pp_history_free(zbx_pp_history_t *history);

void	pp_history_add(zbx_pp_history_t *history, int index, zbx_variant_t *value, zbx_timespec_t ts);
void	pp_history_pop(zbx_pp_history_t *history, int index, zbx_variant_t *value, zbx_timespec_t *ts);

#endif
