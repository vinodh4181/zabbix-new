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
#include "pp_log.h"

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

	return task;
}


/* TODO pass socket */
zbx_pp_task_t	*pp_task_test_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc, zbx_variant_t *value,
		zbx_timespec_t ts)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_test_t));
	zbx_pp_task_test_t	*d = (zbx_pp_task_test_t *)PP_TASK_DATA(task);

	pp_debugf("pp_task_test_create() -> %p", task);

	task->itemid = itemid;
	task->type = ZBX_PP_TASK_TEST;
	zbx_variant_copy(&d->value, value);
	d->ts = ts;

	d->preproc = pp_item_preproc_copy(preproc);

	return task;
}

static void	pp_task_test_clear(zbx_pp_task_test_t *task)
{
	pp_debugf("pp_task_test_clear(%p)", task);

	zbx_variant_clear(&task->value);
	pp_item_preproc_release(task->preproc);
}

zbx_pp_task_t	*pp_task_value_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc, zbx_variant_t *value,
		zbx_timespec_t ts, zbx_pp_cache_t *cache)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_value_t));
	zbx_pp_task_value_t	*d = (zbx_pp_task_value_t *)PP_TASK_DATA(task);

	pp_debugf("pp_task_value_create(%lu) -> %p", itemid, task);

	task->itemid = itemid;
	task->type = ZBX_PP_TASK_VALUE;

	if (NULL != value)
		zbx_variant_copy(&d->value, value);
	else
		zbx_variant_set_none(&d->value);

	zbx_variant_set_none(&d->result);
	d->cache = pp_cache_copy(cache);
	d->ts = ts;

	d->preproc = pp_item_preproc_copy(preproc);

	return task;
}

static void	pp_task_value_clear(zbx_pp_task_value_t *task)
{
	pp_debugf("pp_task_value_in_clear(), value: %s", zbx_variant_value_desc(&task->value));

	zbx_variant_clear(&task->value);
	zbx_variant_clear(&task->result);
	pp_item_preproc_release(task->preproc);
	pp_cache_release(task->cache);
}

zbx_pp_task_t	*pp_task_value_seq_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc, zbx_variant_t *value,
		zbx_timespec_t ts)
{
	zbx_pp_task_t	*task = pp_task_value_create(itemid, preproc, value, ts, NULL);

	pp_debugf("pp_task_value_seq_in_create(%lu) -> %p", itemid, task);

	task->type = ZBX_PP_TASK_VALUE_SEQ;

	return task;
}

zbx_pp_task_t	*pp_task_dependent_create(zbx_uint64_t itemid, zbx_pp_item_preproc_t *preproc)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_dependent_t));
	zbx_pp_task_dependent_t	*d = (zbx_pp_task_dependent_t *)PP_TASK_DATA(task);

	pp_debugf("pp_task_dependent_create(%lu) -> %p", itemid, task);

	task->itemid = itemid;
	task->type = ZBX_PP_TASK_DEPENDENT;

	d->first_task = NULL;
	d->cache = NULL;

	d->preproc = pp_item_preproc_copy(preproc);

	return task;
}

static void	pp_task_dependent_clear(zbx_pp_task_dependent_t *task)
{
	pp_debugf("pp_task_dependent_in_clear()");

	pp_item_preproc_release(task->preproc);
	pp_cache_release(task->cache);

	if (NULL != task->first_task)
		pp_task_free(task->first_task);

}

zbx_pp_task_t	*pp_task_sequence_create(zbx_uint64_t itemid)
{
	zbx_pp_task_t		*task = pp_task_create(sizeof(zbx_pp_task_sequence_t));
	zbx_pp_task_sequence_t	*d = (zbx_pp_task_sequence_t *)PP_TASK_DATA(task);

	pp_debugf("pp_task_sequence_create(%lu) -> %p", itemid, task);

	task->itemid = itemid;
	task->type = ZBX_PP_TASK_SEQUENCE;
	zbx_list_create(&d->tasks);

	return task;
}

static void	pp_task_sequence_clear(zbx_pp_task_sequence_t *seq)
{
	zbx_pp_task_t	*task;

	pp_debugf("pp_task_dependent_in_clear(%p)", seq);

	while (SUCCEED == zbx_list_pop(&seq->tasks, (void **)&task))
		pp_task_free(task);

	zbx_list_destroy(&seq->tasks);
}

void	pp_task_free(zbx_pp_task_t *task)
{
	pp_debugf("pp_task_free(%p)", task);

	if (NULL == task)
		return;

	switch (task->type)
	{
		case ZBX_PP_TASK_TEST:
			pp_task_test_clear((zbx_pp_task_test_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_VALUE:
		case ZBX_PP_TASK_VALUE_SEQ:
			pp_task_value_clear((zbx_pp_task_value_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_DEPENDENT:
			pp_task_dependent_clear((zbx_pp_task_dependent_t *)PP_TASK_DATA(task));
			break;
		case ZBX_PP_TASK_SEQUENCE:
			pp_task_sequence_clear((zbx_pp_task_sequence_t *)PP_TASK_DATA(task));
			break;
	}

	zbx_free(task);
}
