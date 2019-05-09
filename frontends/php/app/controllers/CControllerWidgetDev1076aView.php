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


class CControllerWidgetDev1076aView extends CControllerWidget {

	public function __construct() {
		parent::__construct();

		$this->setType(WIDGET_DEV_1076_A);
		$this->setValidationRules([
			'name' => 'string',
			'fields' => 'json'
		]);
	}

	protected function doAction() {
		$fields = $this->getForm()->getFieldsData();
		$elements = [];
		$error = null;

		$hosts = API::Host()->get([
			'output' => ['host', 'hostid'],
			'selectInventory' => ['location_lat', 'location_lon'],
			'hostids' => $fields['hostids'],
			'preservekeys' => true
		]);

		foreach ($hosts as $host) {
			$elements[] = [
				'type' => 'Feature',
				'properties' => [
					'hostname' => $host['host'],
					'hostid' => $host['hostid'],
				],
				'geometry' => [
					'type' => 'Point',
					'coordinates' => [
						$host['inventory']['location_lon'],
						$host['inventory']['location_lat']
					]
				]
			];
		}

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->getDefaultHeader()),
			'elements' => [
				'type' => 'FeatureCollection',
				'features' => $elements
			],
			//'elements' => $elements,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
