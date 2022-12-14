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

#ifndef ZABBIX_PP_LOG
#define ZABBIX_PP_LOG

void	pp_log_init(const char *source, int level);
void	pp_debugf(const char *format, ...);
void	pp_warnf(const char *format, ...);
void	pp_infof(const char *format, ...);

#undef zabbix_log

#	define zabbix_log(level, format, ...)								\
	do												\
	{	switch (level) {									\
			case LOG_LEVEL_DEBUG:								\
				pp_warnf(format, __VA_ARGS__);						\
				break;									\
			case LOG_LEVEL_WARNING:								\
				pp_warnf(format, __VA_ARGS__);						\
				break;									\
			case LOG_LEVEL_INFORMATION:							\
				pp_infof(format, __VA_ARGS__);						\
				break;									\
			default:									\
				break;									\
		}											\
	} while (0)

#endif
