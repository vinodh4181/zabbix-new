<?php

/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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
 * @backup dashboard
 *
 * @on-before mapCreate
 * @on-before prepareDashboardData
 */
class testDashboardWidgetUpdateInterval extends CWebTest {

	protected static $dashboardid;
	protected static $mapid;

	/**
	 * Function creates  map with defined host.
	 */
	public static function mapCreate($host_id = '10084') {
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
							['hostid' => $host_id]
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
									'value' => 1
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
									'value' => 1
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
									'value' => 1
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
									'value' => 1
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
									'value' => 1
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
									'value' => 1
								]
							]
					],
					[
						'type' => 'favscreens', // 6
						'name' => 'Favorite screens widget',
						'x' => 0,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'svggraph', // 7
						'name' => 'SVG graph widget',
						'x' => 2,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'graph', // 8
						'name' => 'Graph widget',
						'x' => 4,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'map', // 9
						'name' => 'Map widget',
						'x' => 6,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								],
								[
									'type' => 8,
									'name' => 'sysmapid',
									'value' => self::$mapid
								]
							]
					],
					[
						'type' => 'navtree', // 10
						'name' => 'Map Navigation Tree widget',
						'x' => 8,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'plaintext', // 11
						'name' => 'Plain text widget',
						'x' => 10,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'problemhosts', // 12
						'name' => 'Problem hosts widget',
						'x' => 0,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'problems', // 13
						'name' => 'Problems widget',
						'x' => 2,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'problemsbysv', // 14
						'name' => 'Problems by severity widget',
						'x' => 4,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'systeminfo', // 15
						'name' => 'System information widget',
						'x' => 6,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'trigover', // 16
						'name' => 'Trigger overview widget',
						'x' => 8,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'url', // 17
						'name' => 'URL widget',
						'x' => 10,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
								]
							]
					],
					[
						'type' => 'web', // 18
						'name' => 'Web monitoring widget',
						'x' => 0,
						'y' => 6,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 1
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
					'name' => 'SVG graph widget', // 7
					'check_type' => 'svg',
					'tag' => 'xpath:.//*[local-name()="svg"]'
				]
			],
			[
				[
					'name' => 'Graph widget' // 8
				]
			],
			[
				[
					'name' => 'Map widget', // 9
					'check_type' => 'map'
				]
			],
			[
				[
					'name' => 'Map Navigation Tree widget', // 10
					'check_type' => 'id',
					'tag' => 'div'
				]
			],
			[
				[
					'name' => 'Plain text widget' // 11
				]
			],
			[
				[
					'name' => 'Problem hosts widget' // 12
				]
			],
			[
				[
					'name' => 'Problems widget' // 13
				]
			],
			[
				[
					'name' => 'Problems by severity widget' // 14
				]
			],
			[
				[
					'name' => 'System information widget' // 15
				]
			],
			[
				[
					'name' => 'Trigger overview widget' // 16
				]
			],
			[
				[
					'name' => 'URL widget' // 17
				]
			],
			[
				[
					'name' => 'Web monitoring widget', // 18
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
		$id_xpath = 'xpath:.//div[@class="dashbrd-grid-widget-content"]/';
		$type = (array_key_exists('check_type', $data)) ? $data['check_type'] : 'id_table';

		switch ($type) {
				case 'id_table':
					$attribute = $widget->query($id_xpath.'table')->one()->getAttribute('id');
					sleep(2);
					$this->assertNotEquals($attribute, $widget->query($id_xpath.'table')->one()->getAttribute('id'));
					break;

				case 'id':
					$attribute = $widget->query($id_xpath.$data['tag'])->one()->getAttribute('id');
					sleep(2);
					$this->assertNotEquals($attribute, $widget->query($id_xpath.$data['tag'])->one()->getAttribute('id'));
					break;

				case 'svg':
					$attribute = $widget->query($data['tag'])->one()->getAttribute('id');
					sleep(2);
					$this->assertNotEquals($attribute, $widget->query($data['tag'])->one()->getAttribute('id'));
					break;

				case 'map':
					$host_id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host="Test host"');
					$host_ip = CDBHelper::getValue('SELECT ip FROM interface WHERE hostid='.$host_id);

					DBexecute('UPDATE interface SET ip = "200.2.2.3" WHERE hostid='.$host_id);
					sleep(2);
					$host_new_ip = $widget->query('xpath:.//*[contains(text(),"200.2.2.3")]')->one()->getText();
					$this->assertNotEquals($host_ip, $host_new_ip);
					DBexecute('UPDATE interface SET ip = "127.0.0.1" WHERE hostid='.$host_id);
					break;

				default:
					$attribute = $widget->query($id_xpath.'table')->one()->getAttribute('id');
					sleep(2);
					$this->assertNotEquals($attribute, $widget->query($id_xpath.'table')->one()->getAttribute('id'));
					break;
			}



//		$widget = CDashboardElement::find()->one()->getWidgets()->first();
//		$widget->query('xpath:(//button[@class="btn-widget-action"])')->one()->click();
//		CPopupMenuElement::find()->waitUntilVisible()->one()->select('10 seconds');
//		$attribute = $widget->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')->one()->getAttribute('id');
//		$attribute = $widget->getAttribute('id');
//		var_dump($widget->getRefreshInterval());
//		echo spl_object_hash($widget);
//		sleep(12);
//		$widgetNew = CDashboardElement::find()->one()->getWidgets()->first();
//		echo spl_object_hash($widget)."\r\n";
//		echo spl_object_hash($widgetNew);
//		var_dump($widget === $widgetNew);
//		$this->assertNotEquals($attribute, $widget->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')->one()->getAttribute('id'));
	}
}

