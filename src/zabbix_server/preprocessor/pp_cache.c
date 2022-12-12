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
#include "zbxcommon.h"

zbx_pp_cache_t	*pp_cache_create(unsigned char type)
{
	zbx_pp_cache_t	*cache = (zbx_pp_cache_t *)zbx_malloc(NULL, sizeof(zbx_pp_cache_t));

	cache->type = type;
	zbx_variant_set_none(&cache->value);
	cache->data = NULL;
	cache->refcount = 1;

	return cache;
}

void	pp_cache_release(zbx_pp_cache_t *cache)
{
	if (0 != --cache->refcount)
		return;

	zbx_variant_clear(&cache->value);

	/* TODO: free data */

	zbx_free(cache);
}

zbx_pp_cache_t	*pp_cache_copy(zbx_pp_cache_t *cache)
{
	if (NULL == cache)
		return NULL;

	cache->refcount++;

	return cache;
}
