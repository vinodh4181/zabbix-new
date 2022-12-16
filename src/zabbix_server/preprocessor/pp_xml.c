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

#include "pp_xml.h"
#include "zbxxml.h"

#ifdef HAVE_LIBXML2

#ifndef LIBXML_THREAD_ENABLED
static pthread_mutex_t	xml_lock;
#endif

void	pp_xml_init(void)
{
#ifndef LIBXML_THREAD_ENABLED
	pthread_mutex_init(&xml_lock, NULL);
#endif
	xmlInitParser();
}

void	pp_xml_destroy(void)
{
	xmlCleanupParser();

#ifndef LIBXML_THREAD_ENABLED
	pthread_mutex_destroy(&xml_lock);
#endif
}

int	pp_xml_query_xpath(zbx_variant_t *value, const char *params, char **errmsg)
{
#ifndef LIBXML_THREAD_ENABLED
	pthread_mutex_lock(&xml_lock);
#endif

	return zbx_query_xpath(value, params, errmsg);

#ifndef LIBXML_THREAD_ENABLED
	pthread_mutex_unlock(&xml_lock);
#endif
}


#else

void	pp_xml_init(void)
{
}

int	pp_xml_query_xpath(zbx_variant_t *value, const char *params, char **errmsg)
{
	return zbx_query_xpath(value, params, errmsg);
}

#endif
