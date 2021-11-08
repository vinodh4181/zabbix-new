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
 * @on-before prepareDashboardData
 */
class testDashboardWidgetUpdateInterval extends CWebTest {

	protected static $dashboardid;

	/**
	 * Function creates template dashboard with Widgets.
	 */
	public static function prepareDashboardData() {
		$response = CDataHelper::call('dashboard.create', [
			[
				'name' => 'Test Dashboard',
				'widgets' => [
					[
						'type' => 'actionlog',
						'name' => 'Action log widget',
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 30
								]
							]
					],
					[
						'type' => 'clock',
						'name' => 'Clock widget',
						'x' => 2,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'dataover',
						'name' => 'Data overview widget',
						'x' => 4,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'discovery',
						'name' => 'Discovery status widget',
						'x' => 6,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'favgraphs',
						'name' => 'Favorite graphs widget',
						'x' => 8,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'favmaps',
						'name' => 'Favorite maps widget',
						'x' => 10,
						'y' => 0,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'favscreens',
						'name' => 'Favorite screens widget',
						'x' => 0,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'svggraph',
						'name' => 'SVG graph widget',
						'x' => 2,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'graph', // img src?
						'name' => 'Graph widget',
						'x' => 4,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'map', // svg g?
						'name' => 'Map widget',
						'x' => 6,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'navtree',
						'name' => 'Map Navigation Tree widget',
						'x' => 8,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'plaintext', // pre br ?
						'name' => 'Plain text widget',
						'x' => 10,
						'y' => 2,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'problemhosts',
						'name' => 'Problem hosts widget',
						'x' => 0,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'problems',
						'name' => 'Problems widget',
						'x' => 2,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'problemsbysv',
						'name' => 'Problems by severity widget',
						'x' => 4,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'systeminfo',
						'name' => 'System information widget',
						'x' => 6,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'trigover',
						'name' => 'Trigger overview widget',
						'x' => 8,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'url', // pre br ?
						'name' => 'URL widget',
						'x' => 10,
						'y' => 4,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
								]
							]
					],
					[
						'type' => 'web',
						'name' => 'Web monitoring widget',
						'x' => 0,
						'y' => 6,
						'width' => 2,
						'height' => 2,
							'fields' => [
								[
									'type' => 0,
									'name' => 'rf_rate',
									'value' => 3
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
//			[
//				[
//					'name' => 'Action log widget' // 0
//				]
//			],
			[
				[
					'name' => 'Clock widget' // 1
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
					'name' => 'SVG graph widget' // 7
				]
			],
			[
				[
					'name' => 'Graph widget', // 8
//					'check_type' => 'url'
				]
			],
			[
				[
					'name' => 'Map widget', // 9
//					'check_type' => 'url'
				]
			],
			[
				[
					'name' => 'Map Navigation Tree widget', // 10
				]
			],
			[
				[
					'name' => 'Plain text widget', // 11
				]
			],
			[
				[
					'name' => 'Problem hosts widget', // 12
				]
			],
			[
				[
					'name' => 'Problems widget', // 13
				]
			],
			[
				[
					'name' => 'Problems by severity widget', // 14
				]
			],
			[
				[
					'name' => 'System information widget', // 15
				]
			],
			[
				[
					'name' => 'Trigger overview widget', // 16
				]
			],
			[
				[
					'name' => 'URL widget', // 17
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
//		var_dump($dashboard->getWidgets()->count());
//		var_dump($data['name']);
		$type = (array_key_exists('check_type', $data)) ? $data['check_type'] : 'id';

		switch ($type) {
				case 'id':
					$attribute = $widget->getContent()->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')->one()->getAttribute('id');
//					var_dump($attribute);
					sleep(5);
//					var_dump($widget->getContent()->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')->one()->getAttribute('id'));
//					var_dump($widget->getHeaderText());
					$this->assertNotEquals($attribute, $widget->getContent()->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')
							->one()->getAttribute('id'));
					break;

				case 'url':
					$attribute = $widget->query('xpath:(//div[@class="flickerfreescreen"]//img[@src])')->one()->getAttribute('src');
					sleep(5);
					$this->assertNotEquals($attribute, $widget->query('xpath:(\/\/div[@class="flickerfreescreen"]\/\/img[@src])')
							->one()->getAttribute('src'));
					break;

				default:
					$attribute = $widget->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')->one()->getAttribute('id');
					sleep(5);
					$this->assertNotEquals($attribute, $widget->query('xpath:(//div[@class="dashbrd-grid-widget-content"]/*[1])')
							->one()->getAttribute('id'));
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

