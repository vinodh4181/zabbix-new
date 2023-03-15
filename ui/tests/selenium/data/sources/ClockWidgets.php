<?php
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


class ClockWidgets {

	/**
	 * Create data for autotests which use ClockWidget.
	 *
	 * @return array
	 */
	public static function load() {
		CDataHelper::call('hostgroup.create', [
			[
				'name' => 'Host group for clock widget'
			]
		]);

		$hostgrpid = CDataHelper::getIds('name');

		CDataHelper::createHosts([
			[
				'host' => 'Host for clock widget',
				'groups' => [
					'groupid' => $hostgrpid['Host group for clock widget']
				],
				'interfaces' => [
					[
						'type'=> 1,
						'main' => 1,
						'useip' => 1,
						'ip' => '192.168.3.217',
						'dns' => '',
						'port' => '10050'
					]
				],
				'items' => [
					[
						'name' => 'Item for clock widget',
						'key_' => 'system.localtime[local]',
						'type' => 0,
						'value_type' => 1,
						'delay' => '5s'
					],
					[
						'name' => 'Item for clock widget 2',
						'key_' => 'system.localtime[local2]',
						'type' => 0,
						'value_type' => 1,
						'delay' => '5s'
					]
				]
			]
		]);
		$itemid = CDataHelper::getIds('name');

		CDataHelper::call('dashboard.create', [
			[
				'name' => 'Dashboard for creating clock widgets',
				'display_period' => 60,
				'auto_start' => 0,
				'pages' => [
					[
						'name' => 'First page',
						'widgets' => [
							[
								'type' => 'clock',
								'name' => 'DeleteClock',
								'x' => 5,
								'y' => 0,
								'width' => 5,
								'height' => 5
							],
							[
								'type' => 'clock',
								'name' => 'CancelClock',
								'x' => 0,
								'y' => 0,
								'width' => 5,
								'height' => 5
							],
							[
								'type' => 'clock',
								'name' => 'LayoutClock',
								'x' => 10,
								'y' => 0,
								'width' => 5,
								'height' => 5,
								'fields' => [
									[
										'type' => 4,
										'name' => 'itemid',
										'value' => $itemid['Item for clock widget']
									],
									[
										'type' => 0,
										'name' => 'time_type',
										'value' => 2
									]
								]
							]
						]
					],
					[
						'name' => 'Second page'
					]
				]
			],
			[
				'name' => 'Dashboard for updating clock widgets',
				'pages' => [
					[
						'widgets' => [
							[
								'type' => 'clock',
								'name' => 'UpdateClock',
								'x' => 0,
								'y' => 0,
								'width' => 5,
								'height' => 5
							]
						]
					]
				]
			]
		]);

		return [
			'dashboardids' => CDataHelper::getIds('name')
		];
	}
}
