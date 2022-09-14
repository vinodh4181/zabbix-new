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

#include "zbxmocktest.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"

#include "../../../src/libs/zbxsysinfo/simple/simple.h"
#include "../../../include/zbxsysinfo.h"

int	__wrap_tcp_expect(const char *host, unsigned short port, int timeout, const char *request,
		int (*validate_func)(const char *), const char *sendtoclose, int *value_int)
{
	ZBX_UNUSED(host);
	ZBX_UNUSED(port);
	ZBX_UNUSED(timeout);
	ZBX_UNUSED(request);
	ZBX_UNUSED(validate_func);
	ZBX_UNUSED(sendtoclose);
	ZBX_UNUSED(value_int);

	return SYSINFO_RET_OK;
}

void	zbx_mock_test_entry(void **state)
{
	AGENT_REQUEST	request;
	AGENT_RESULT	result;
	const char	*default_addr = NULL, *ip = NULL;
	int		returned_code, expected_code;
	char		key[1024];

	ZBX_UNUSED(state);

	expected_code = zbx_mock_str_to_return_code(zbx_mock_get_parameter_string("out.return"));
	default_addr = zbx_mock_get_parameter_string("in.interface");
	ip = zbx_mock_get_parameter_string("in.ip");
	zbx_sysinfo_set_config_timeout(3);

	init_result(&result);
	init_request(&request);
	*key = '\0';
	strcat(key, "net.tcp.service[smtp");

	if (NULL != ip && '\0' != *ip)
	{
		strcat(key, ",");
		strcat(key, ip);
	}

	strcat(key, "]");
	parse_item_key(key, &request);

	returned_code = check_service(&request, default_addr, &result, 0);
	if (SUCCEED != returned_code && NULL != result.msg && '\0' != *(result.msg))
		printf("check_service_test error: %s\n", result.msg);

	zbx_mock_assert_result_eq("Return value", expected_code, returned_code);

	free_result(&result);
	free_request(&request);
}
