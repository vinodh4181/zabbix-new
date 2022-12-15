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

/* debug logging, remove for release */

#include "pp_cache.h"
#include "zbxjson.h"

/******************************************************************************
 *                                                                            *
 * Purpose: create preprocessing cache                                        *
 *                                                                            *
 * Parameters: preproc  - [IN] the preprocessing data                         *
 *             value    - [IN/OUT] the input value - it will copied to cache  *
 *                                 and cleared                                *
 *                                                                            *
 * Return value: The created preprocessing cache                              *
 *                                                                            *
 ******************************************************************************/
zbx_pp_cache_t	*pp_cache_create(zbx_pp_item_preproc_t *preproc, zbx_variant_t *value)
{
	zbx_pp_cache_t	*cache = (zbx_pp_cache_t *)zbx_malloc(NULL, sizeof(zbx_pp_cache_t));

	cache->type = (0 != preproc->steps_num ? preproc->steps[0].type : ZBX_PREPROC_NONE);
	cache->value = *value;
	zbx_variant_set_none(value);
	cache->data = NULL;
	cache->refcount = 1;

	return cache;
}

static void	pp_cache_jsonpath_free(void *data)
{
	zbx_jsonobj_t	*obj = (zbx_jsonobj_t *)data;

	zbx_jsonobj_clear(obj);
}

static void	pp_cache_free(zbx_pp_cache_t *cache)
{
	zbx_variant_clear(&cache->value);

	if (NULL != cache->data)
	{
		switch (cache->type)
		{
			case ZBX_PREPROC_JSONPATH:
				pp_cache_jsonpath_free(cache->data);
				break;
		}

		zbx_free(cache->data);
	}

	zbx_free(cache);
}

void	pp_cache_release(zbx_pp_cache_t *cache)
{
	if (NULL == cache || 0 != --cache->refcount)
		return;

	pp_cache_free(cache);
}

zbx_pp_cache_t	*pp_cache_copy(zbx_pp_cache_t *cache)
{
	if (NULL == cache)
		return NULL;

	cache->refcount++;

	return cache;
}

void	pp_cache_get_value(zbx_pp_cache_t *cache, zbx_variant_t *value)
{
	if (NULL != cache)
	{
		zbx_variant_clear(value);
		zbx_variant_copy(value, &cache->value);
	}
}
