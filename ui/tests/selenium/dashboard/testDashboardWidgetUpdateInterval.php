<?php

/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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

require_once dirname(__FILE__).'/../../include/CWebTest.php';

/**
 * @backup sysmaps
 * @backup dashboard
 *
 * @onBefore mapCreate
 * @onBefore prepareDashboardData
 */
class testDashboardWidgetUpdateInterval extends CWebTest {

	protected const HOSTID = '10084';

	protected static $dashboardid;
	protected static $mapid;


	/**
	 * Function creates  map with defined host.
	 */
	public static function mapCreate() {
		$response = CDataHelper::call('map.create', [
			[
				'name' => 'Test map',
				'width' => 70,
				'height' => 60,
				'label_type' => 1,
				'selements' => [
					[
						'selementid' => "1",
						'elements' => [
							['hostid' => self::HOSTID]
						],
						'elementtype' => 0,
						'iconid_off' => 4
					]
				]
			]
		]);
		self::$mapid = $response['sysmapids'][0];
	}

	/**
	 * Function creates dashboard with Widgets.
	 */
	public static function prepareDashboardData() {
		$response = CDataHelper::call('dashboard.create', [
			[
				'name' => 'Test Dashboard',
				'widgets' => [
					[
						'type' => 'actionlog', // 0
						'name' => 'Action log widget',
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'clock', // 1
						'name' => 'Clock widget',
						'x' => 2,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'dataover', // 2
						'name' => 'Data overview widget',
						'x' => 4,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'discovery', // 3
						'name' => 'Discovery status widget',
						'x' => 6,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'favgraphs', // 4
						'name' => 'Favorite graphs widget',
						'x' => 8,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'favmaps', // 5
						'name' => 'Favorite maps widget',
						'x' => 10,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'favscreens', // 6
						'name' => 'Favorite screens widget',
						'x' => 12,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'graph', // 7
						'name' => 'Graph widget',
						'x' => 14,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'graphprototype', // 8
						'name' => 'Graph prototype widget',
						'x' => 16,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'hostavail', // 9
						'name' => 'Host availability widget',
						'x' => 18,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'map', // 10
						'name' => 'Map widget',
						'x' => 20,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								],
								[
									'type' => 8,
									'name' => 'sysmapid',
									'value' => self::$mapid
								]
							]
					],
					[
						'type' => 'navtree', // 11
						'name' => 'Map Navigation Tree widget',
						'x' => 22,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'plaintext', // 12
						'name' => 'Plain text widget',
						'x' => 0,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'problemhosts', // 13
						'name' => 'Problem hosts widget',
						'x' => 2,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'problems', // 14
						'name' => 'Problems widget',
						'x' => 4,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'problemsbysv', // 15
						'name' => 'Problems by severity widget',
						'x' => 6,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'svggraph', // 16
						'name' => 'SVG graph widget',
						'x' => 8,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'systeminfo', // 17
						'name' => 'System information widget',
						'x' => 10,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'trigover', // 18
						'name' => 'Trigger overview widget',
						'x' => 12,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'url', // 19
						'name' => 'URL widget',
						'x' => 14,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					],
					[
						'type' => 'web', // 20
						'name' => 'Web monitoring widget',
						'x' => 16,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 10
								]
							]
					]
				]
			]
		]);
	self::$dashboardid = $response['dashboardids'][0];
	}

	/**
	 * Data provider for CheckUpdateInterval.
	 */
	public function getWidgetData() {
		return [
			[
				[
					'name' => 'Action log widget' // 0
				]
			],
			[
				[
					'name' => 'Clock widget', // 1
					'check_type' => 'id',
					'tag' => 'div'
				]
			],
			[
				[
					'name' => 'Data overview widget' // 2
				]
			],
			[
				[
					'name' => 'Discovery status widget' // 3
				]
			],
			[
				[
					'name' => 'Favorite graphs widget' // 4
				]
			],
			[
				[
					'name' => 'Favorite maps widget' // 5
				]
			],
			[
				[
					'name' => 'Favorite screens widget' // 6
				]
			],
			[
				[
					'name' => 'Graph widget' // 7
				]
			],
			[
				[
					'name' => 'Graph prototype widget', // 8
					'check_type' => 'tag',
					'tag' => 'xpath:.//div[@class="dashbrd-grid-iterator-content"]/div/table'
				]
			],
			[
				[
					'name' => 'Host availability widget', // 9
				]
			],
			[
				[
					'name' => 'Map widget', // 10
					'check_type' => 'map'
				]
			],
			[
				[
					'name' => 'Map Navigation Tree widget', // 11
					'check_type' => 'id',
					'tag' => 'div'
				]
			],
			[
				[
					'name' => 'Plain text widget' // 12
				]
			],
			[
				[
					'name' => 'Problem hosts widget' // 13
				]
			],
			[
				[
					'name' => 'Problems widget' // 14
				]
			],
			[
				[
					'name' => 'Problems by severity widget' // 15
				]
			],
			[
				[
					'name' => 'SVG graph widget', // 16
					'check_type' => 'tag',
					'tag' => 'xpath:.//*[local-name()="svg"]'
				]
			],
			[
				[
					'name' => 'System information widget' // 17
				]
			],
			[
				[
					'name' => 'Trigger overview widget' // 18
				]
			],
			[
				[
					'name' => 'URL widget' // 19
				]
			],
			[
				[
					'name' => 'Web monitoring widget', // 20
				]
			]
		];

	}

	/**
	 * Test to check Dashboard Widgets update interval.
	 *
	 * @dataProvider getWidgetData
	 */
	public function testDashboardWidgetUpdateInterval_CheckUpdateInterval($data) {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();
		$dashboard = CDashboardElement::find()->one();
		$widget = $dashboard->getWidget($data['name']);
		$id_xpath = ($data['name'] === 'URL widget')
			? 'xpath:.//div[@class="dashbrd-grid-widget-content no-padding"]/'
			: 'xpath:.//div[@class="dashbrd-grid-widget-content"]/';
		$type = (array_key_exists('check_type', $data)) ? $data['check_type'] : 'id_table';

		switch ($type) {
				case 'id_table':
					$attribute = $widget->query($id_xpath.'table')->one()->getAttribute('id');
					sleep(12);
					$this->assertNotEquals($attribute, $widget->query($id_xpath.'table')->one()->getAttribute('id'));
					break;

				case 'id':
					$attribute = $widget->query($id_xpath.$data['tag'])->one()->getAttribute('id');
					sleep(12);
					$this->assertNotEquals($attribute, $widget->query($id_xpath.$data['tag'])->one()->getAttribute('id'));
					break;

				case 'tag':
					$attribute = $widget->query($data['tag'])->one()->getAttribute('id');
					sleep(12);
					$this->assertNotEquals($attribute, $widget->query($data['tag'])->one()->getAttribute('id'));
					break;

				case 'map':
					$hostid = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host="Test host"');
					$host_ip = CDBHelper::getValue('SELECT ip FROM interface WHERE hostid='.$hostid);

					DBexecute('UPDATE interface SET ip = "200.2.2.3" WHERE hostid='.$hostid);
					sleep(12);
					$host_new_ip = $widget->query('xpath:.//*[contains(text(),"200.2.2.3")]')->one()->getText();
					$this->assertNotEquals($host_ip, $host_new_ip);
					DBexecute('UPDATE interface SET ip = "127.0.0.1" WHERE hostid='.$hostid);
					break;

				default:
					$attribute = $widget->query($id_xpath.'table')->one()->getAttribute('id');
					sleep(12);
					$this->assertNotEquals($attribute, $widget->query($id_xpath.'table')->one()->getAttribute('id'));
					break;
			}
	}
}



