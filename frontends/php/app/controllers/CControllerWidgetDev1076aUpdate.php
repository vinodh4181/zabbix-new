<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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


class CControllerWidgetDev1076aUpdate extends CController {

	protected function checkInput() {
		return true;
	}

	protected function checkPermissions() {
		return true;
	}

	protected function doAction() {
		$hostids = $_REQUEST['hostids'];

		$hosts = API::Host()->get([
			'output' => ['host', 'hostid'],
			'hostids' => $hostids,
			'preservekeys' => true
		]);

		//$hosts['10280']['host'] = $hosts['10280']['host'];
		//$hosts['10280']['iconid'] = 141;

		$output = [
			'hosts' => $hosts
		];

		$this->setResponse((new CControllerResponseData(['main_block' => CJs::encodeJson($output)]))->disableView());
	}
}
