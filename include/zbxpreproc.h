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

#ifndef ZABBIX_PP_PREPROC_H
#define ZABBIX_PP_PREPROC_H

#include "zbxalgo.h"
#include "zbxvariant.h"
#include "zbxtime.h"
#include "zbxtimekeeper.h"
#include "zbxipcservice.h"
#include "zbxthreads.h"
#include "zbxjson.h"
#include "zbxstats.h"

#define ZBX_PREPROCESSING_BATCH_SIZE	256

typedef void (*zbx_pp_notify_cb_t)(void *data);

/* one preprocessing step history */
typedef struct
{
	int		index;
	zbx_variant_t	value;
	zbx_timespec_t	ts;
}
zbx_pp_step_history_t;

ZBX_VECTOR_DECL(pp_step_history, zbx_pp_step_history_t)

/* item preprocessing history for preprocessing steps using previous values */
typedef struct
{
	zbx_vector_pp_step_history_t	step_history;
}
zbx_pp_history_t;

void	zbx_pp_history_init(zbx_pp_history_t *history);
void	zbx_pp_history_clear(zbx_pp_history_t *history);

typedef enum
{
	ZBX_PP_PROCESS_PARALLEL,
	ZBX_PP_PROCESS_SERIAL
}
zbx_pp_process_mode_t;

typedef struct
{
	int	type;
	int	error_handler;
	char	*params;
	char	*error_handler_params;
}
zbx_pp_step_t;

void	zbx_pp_step_free(zbx_pp_step_t *step);

ZBX_PTR_VECTOR_DECL(pp_step_ptr, zbx_pp_step_t *)

typedef struct
{
	zbx_uint32_t		refcount;

	int			steps_num;
	zbx_pp_step_t		*steps;

	int			dep_itemids_num;
	zbx_uint64_t		*dep_itemids;

	unsigned char		type;
	unsigned char		value_type;
	unsigned char		flags;
	zbx_pp_process_mode_t	mode;

	zbx_pp_history_t	*history;	/* the preprocessing history */
	int			history_num;	/* the number of preprocessing steps requiring history */
}
zbx_pp_item_preproc_t;

#define ZBX_PP_VALUE_OPT_NONE		0x0000
#define ZBX_PP_VALUE_OPT_META		0x0001
#define ZBX_PP_VALUE_OPT_LOG		0x0002

typedef struct
{
	zbx_uint32_t	flags;
	int		mtime;
	int		timestamp;
	int		severity;
	int		logeventid;
	zbx_uint64_t	lastlogsize;
	char		*source;
}
zbx_pp_value_opt_t;

typedef struct
{
	zbx_uint64_t		itemid;
	zbx_uint64_t		hostid;
	zbx_uint64_t		revision;

	zbx_pp_item_preproc_t	*preproc;
}
zbx_pp_item_t;

/* preprocessing step execution result */
typedef struct
{
	zbx_variant_t	value;
	zbx_variant_t	value_raw;
	int		action;
}
zbx_pp_result_t;

ZBX_PTR_VECTOR_DECL(pp_result_ptr, zbx_pp_result_t *)

void	zbx_pp_result_free(zbx_pp_result_t *result);

typedef struct
{
	int	workers_num;
}
zbx_thread_pp_manager_args;

typedef struct zbx_pp_manager	zbx_pp_manager_t;

typedef void(*zbx_flush_value_func_t)(zbx_pp_manager_t *manager, zbx_uint64_t itemid, unsigned char value_type,
	unsigned char flags, zbx_variant_t *value, zbx_timespec_t ts, zbx_pp_value_opt_t *value_opt);

void	zbx_init_library_preproc(zbx_flush_value_func_t flush_value_cb);

zbx_pp_item_preproc_t	*zbx_pp_item_preproc_create(unsigned char type, unsigned char value_type, unsigned char flags);
void	zbx_pp_item_preproc_release(zbx_pp_item_preproc_t *preproc);
int	zbx_pp_preproc_has_history(int type);

zbx_hashset_t	*zbx_pp_manager_items(zbx_pp_manager_t *manager);

typedef struct
{
	zbx_uint64_t	itemid;
	int		tasks_num;
}
zbx_pp_sequence_stats_t;

ZBX_PTR_VECTOR_DECL(pp_sequence_stats_ptr, zbx_pp_sequence_stats_t *)

void zbx_preproc_stats_ext_get(struct zbx_json *json, const void *arg);
zbx_uint64_t	zbx_preprocessor_get_queue_size(void);
void	zbx_preprocessor_get_worker_info(zbx_process_info_t *info);
void	zbx_preprocess_item_value(zbx_uint64_t itemid, zbx_uint64_t hostid, unsigned char item_value_type,
		unsigned char item_flags, AGENT_RESULT *result, zbx_timespec_t *ts, unsigned char state, char *error);
void	zbx_preprocessor_flush(void);
int	zbx_preprocessor_get_diag_stats(zbx_uint64_t *preproc_num, zbx_uint64_t *pending_num,
		zbx_uint64_t *finished_num, zbx_uint64_t *sequences_num, char **error);
int	zbx_preprocessor_get_top_sequences(int limit, zbx_vector_pp_sequence_stats_ptr_t *sequences, char **error);
int	zbx_preprocessor_test(unsigned char value_type, const char *value, const zbx_timespec_t *ts,
		unsigned char state, const zbx_vector_pp_step_ptr_t *steps, zbx_vector_pp_result_ptr_t *results,
		zbx_pp_history_t *history, char **error);
int	zbx_preprocessor_get_usage_stats(zbx_vector_dbl_t *usage, int *count, char **error);

ZBX_THREAD_ENTRY(zbx_pp_manager_thread, args);

#endif
