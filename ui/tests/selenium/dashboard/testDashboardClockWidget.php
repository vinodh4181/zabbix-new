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


require_once dirname(__FILE__) . '/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../../include/helpers/CDataHelper.php';
require_once dirname(__FILE__).'/../traits/TableTrait.php';
require_once dirname(__FILE__).'/../behaviors/CMessageBehavior.php';

/**
 * @backup widget, profiles
 * @dataSource ClockWidgets
 */

class testDashboardClockWidget extends CWebTest {

	/**
	 * Attach MessageBehavior to the test.
	 *
	 * @return array
	 */
	public function getBehaviors() {
		return ['class' => CMessageBehavior::class];
	}

	/**
	 * SQL query to get widget and widget_field tables to compare hash values, but without widget_fieldid
	 * because it can change.
	 */
	private $sql = 'SELECT wf.widgetid, wf.type, wf.name, wf.value_int, wf.value_str, wf.value_groupid, wf.value_hostid,'.
	' wf.value_itemid, wf.value_graphid, wf.value_sysmapid, w.widgetid, w.dashboard_pageid, w.type, w.name, w.x, w.y,'.
	' w.width, w.height'.
	' FROM widget_field wf'.
	' INNER JOIN widget w'.
	' ON w.widgetid=wf.widgetid ORDER BY wf.widgetid, wf.name, wf.value_int, wf.value_str, wf.value_groupid,'.
	' wf.value_itemid, wf.value_graphid';

	/**
	 * Check clock widgets layout.
	 */
	public function testDashboardClockWidget_CheckLayout() {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->getWidget('LayoutClock')->edit();

		// Check edit forms header.
		$this->assertEquals('Edit widget',
			$form->query('xpath://h4[@id="dashboard-widget-head-title-widget_properties"]')->one()->getText());

		// Check if widget type is selected as "Clock".
		$form->checkValue(['Type' => 'Clock']);

		// Check "Name" field max length.
		$this->assertEquals('255', $form->query('id:name')->one()->getAttribute('maxlength'));

		// Check fields "Refresh interval" values.
		$this->assertEquals(['Default (15 minutes)', 'No refresh', '10 seconds', '30 seconds', '1 minute', '2 minutes', '10 minutes', '15 minutes'],
			$form->query('name', 'rf_rate')->asDropdown()->one()->getOptions()->asText()
		);

		$this->assertEquals(['Local time', 'Server time', 'Host time'],
			$form->query('name', 'time_type')->asDropdown()->one()->getOptions()->asText()
		);

		// Check fields "Time type" values.
		$this->assertEquals(['Local time', 'Server time', 'Host time'],
				$form->query('name', 'time_type')->asDropdown()->one()->getOptions()->asText()
		);

		// Check that it's possible to select host items, when time type is "Host Time".
		$fields = ['Type', 'Name', 'Refresh interval', 'Time type', 'Clock type'];

		foreach (['Local time', 'Server time', 'Host time'] as $type) {
			$form->fill(['Time type' => CFormElement::RELOADABLE_FILL($type)]);

			if ($type === 'Host time') {
				array_splice($fields, 4, 0, ['Item']);
			}

			$this->assertEquals($fields, $form->getLabels()->filter(new CElementFilter(CElementFilter::VISIBLE))->asText());

		}

		// Check that it's possible to change the status of "Show header" checkbox.
		$this->assertTrue($form->query('xpath://input[contains(@id, "show_header")]')->one()->isSelected());

		// Check that clock widget with "Time Type" - "Host time", displays host name, when clock widget name is empty.
		$form = $dashboard->getWidget('LayoutClock')->edit();
		$form->fill(['Name' => '']);
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$this->assertEquals('Host for clock widget', $dashboard->getWidget('Host for clock widget')->getHeaderText());

		// Check if Apply and Cancel button are clickable.
		$form = $dashboard->getWidget('Host for clock widget')->edit();

		foreach (['Apply', 'Cancel'] as $button) {
			$this->assertTrue($this->query('button', $button)->one()->isClickable());
		}

		// Check that "Clock type" buttons are present.
		$this->assertEquals(['Analog', 'Digital'], $form->getField('Clock type')->asSegmentedRadio()->getLabels()->asText());

		// Check that there are three options what should Digital Clock widget show and select them as "Yes".
		$form->fill(['Clock type' => 'Digital', 'id:show_1' => true, 'id:show_2' => true, 'id:show_3' => true]);

		// Select "Advanced configuration" checkbox.
		$form->fill(['Advanced configuration' => true]);
		$this->assertTrue($form->query('id:adv_conf')->one()->asCheckbox()->isChecked(True));

		// Check default values with "Advanced configuration" = true.
		$default = [
			'Background color' => null,
			'id:date_size' => '20',
			'id:date_bold' => false,
			'id:date_color' => null,
			'id:time_size' => '30',
			'id:time_bold' => false,
			'id:time_color' => null,
			'id:time_sec' => true,
			'id:time_format_0' => true,
			'id:time_format_1' => false,
			'id:tzone_size' => '20',
			'id:tzone_bold' => false,
			'id:tzone_color' => null,
			'id:label-tzone_timezone' => null,
			'id:tzone_format_0' => true
		];

		foreach ($default as $field => $value) {
			$this->assertEquals($value, $form->getField($field)->getValue());
		}
	}

	public static function getCreateData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'ServerTimeClock',
						'Refresh interval' => 'No refresh',
						'Time type' => 'Server time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => false,
						'Name' => 'LocalTimeClock',
						'Refresh interval' => '10 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'HostTimeClock',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Item' => 'Item for clock widget',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => false,
						'Name' => 'ClockWithoutItem',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Clock type' => 'Analog'
					],
					'Error message' => [
						'Invalid parameter "Item": cannot be empty.'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'LocalTimeClock123',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => false,
						'Name' => 'Symb0l$InN@m3Cl0ck',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => '1233212',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => false,
						'Name' => '~@#$%^&*()_+|',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockSimpleShowDate',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockSimpleShowDateandTime',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockSimpleShowAll',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedDefault',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false,
						'Advanced configuration' => true
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedOne',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false,
						'Advanced configuration' => true,
						'Background color' => 'FFEB3B',
						'id:date_size' => '50',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => 'F57F17'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedTwo',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false,
						'Advanced configuration' => true,
						'Background color' => '7B1FA2',
						'id:date_size' => '15',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '002B4D',
						'id:time_size' => '30',
						'id:time_bold' => false,
						'xpath://button[@id="lbl_time_color"]/..' => '00897B',
						'id:time_sec' => true,
						'id:time_format_0' => true,
						'id:time_format_1' => false
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedThree',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false,
						'Advanced configuration' => true,
						'Background color' => '43A047',
						'id:date_size' => '55',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '64B5F6',
						'id:time_size' => '25',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '180D49',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedFour',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => 'C62828',
						'id:date_size' => '40',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => 'FDD835',
						'id:time_size' => '15',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1B5E20',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '20',
						'id:tzone_bold' => false,
						'xpath://button[@id="lbl_tzone_color"]/..' => '06081F',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Atlantic/Stanley'),
						'id:tzone_format_0' => true,
						'id:tzone_format_1' => false
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedFive',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '33',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '12',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedSix',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '333',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '12',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedSeven',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '333',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '123',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedEight',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '333',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '123',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '353',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'DigitalClockShowDateAdvancedModifiedTwelve',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '33',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '23',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '33',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39'
					],
					'Error message' => [
						'Invalid parameter "Item": cannot be empty.'
					]
				]
			]
		];
	}

	/**
	 * Check clock widget successful creation.
	 *
	 * @dataProvider getCreateData
	 */
	public function testDashboardClockWidget_Create($data) {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();

		// If first page is already full with widgets, select second page.
		if (array_key_exists('second_page', $data)) {
			$dashboard->selectPage('Second page');
		}

		$form = $dashboard->edit()->addWidget()->asForm();
		$form->fill($data['fields'])->submit();

		if ($data['expected'] === TEST_GOOD) {
			$this->page->waitUntilReady();
			$dashboard->save();
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');

			// After saving dashboard, it returns you to first page, if widget created in 2nd page,
			// then it needs to be opened.
			if (array_key_exists('second_page', $data)) {
				$dashboard->selectPage('Second page');
				$dashboard->waitUntilReady();
			}

			if ($data['fields']['Time type'] === 'Host time') {
				$data['fields'] = array_replace($data['fields'], ['Item' => 'Host for clock widget: Item for clock widget']);
			}

			$dashboard->getWidgets()->last()->edit()->checkValue($data['fields']);
		} else {
			$this->assertMessage(TEST_BAD, null, $data['Error message']);
		}
	}

	/**
	 * Check clock widgets successful simple update.
	 */
	public function testDashboardClockWidget_SimpleUpdate() {
		$old_hash = CDBHelper::getHash($this->sql);
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for updating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$dashboard->getWidget('UpdateClock')->edit();
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public static function getUpdateData() {
		return [
			// #0 name and show header change.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => false,
						'Name' => 'Changed name'
					]
				]
			],
			// #1 Refresh interval change.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Refresh interval' => '10 seconds'
					]
				]
			],
			// #2 Time type changed to Server time.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Time type' => 'Server time'
					]
				]
			],
			// #3 Time type changed to Local time.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Time type' => 'Local time'
					]
				]
			],
			// #4 Time type and refresh interval changed.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Time type' => 'Server time',
						'Refresh interval' => '10 seconds'
					]
				]
			],
			// #5 Empty name added.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => ''
					]
				]
			],
			// #6 Symbols/numbers name added.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => '!@#$%^&*()1234567890-='
					]
				]
			],
			// #7 Cyrillic added in name.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Имя кирилицей'
					]
				]
			],
			// #8 all fields changed.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'Updated_name',
						'Refresh interval' => '10 minutes',
						'Time type' => 'Server time'
					]
				]
			],
			// #9 Host time without item.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Type' => 'Clock',
						'Show header' => false,
						'Name' => 'ClockWithoutItem',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Clock type' => 'Analog'
					],
					'Error message' => [
						'Invalid parameter "Item": cannot be empty.'
					]
				]
			],
			// #10 Time type with item.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Time type' => 'Host time',
						'Item' => 'Item for clock widget'
					]
				]
			],
			// #11 Update item.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Time type' => 'Host time',
						'Item' => 'Item for clock widget 2'
					]
				]
			],
			// #12
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false
					]
				]
			],
			// #13
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock2',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false
					]
				]
			],
			// #14
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock3',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true
					]
				]
			],
			// #15
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock4',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false,
						'Advanced configuration' => true
					]
				]
			],
			// #16
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock5',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false,
						'Advanced configuration' => true,
						'Background color' => 'FFEB3B',
						'id:date_size' => '50',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => 'F57F17'
					]
				]
			],
			// #17
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock6',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false,
						'Advanced configuration' => true,
						'Background color' => '7B1FA2',
						'id:date_size' => '15',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '002B4D',
						'id:time_size' => '30',
						'id:time_bold' => false,
						'xpath://button[@id="lbl_time_color"]/..' => '00897B',
						'id:time_sec' => true,
						'id:time_format_0' => true,
						'id:time_format_1' => false
					]
				]
			],
			// #18
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock7',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false,
						'Advanced configuration' => true,
						'Background color' => '43A047',
						'id:date_size' => '55',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '64B5F6',
						'id:time_size' => '25',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '180D49',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true
					]
				]
			],
			// #19
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock8',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => 'C62828',
						'id:date_size' => '40',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => 'FDD835',
						'id:time_size' => '15',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1B5E20',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '20',
						'id:tzone_bold' => false,
						'xpath://button[@id="lbl_tzone_color"]/..' => '06081F',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Atlantic/Stanley'),
						'id:tzone_format_0' => true,
						'id:tzone_format_1' => false
					]
				]
			],
			// #20
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock9',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '33',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '12',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					]
				]
			],
			// #21
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock10',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '333',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '12',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #22
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock11',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '333',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '123',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #23
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock12',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '333',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '123',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '353',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:tzone_format_0' => false,
						'id:tzone_format_1' => true
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #24
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalUpdateClock13',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'Background color' => '001819',
						'id:date_size' => '33',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '607D8B',
						'id:time_size' => '23',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => '1565C0',
						'id:time_sec' => false,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '33',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39'
					],
					'Error message' => [
						'Invalid parameter "Item": cannot be empty.'
					]
				]
			]
		];
	}

	/**
	 * Check clock widgets successful update.
	 *
	 * @dataProvider getUpdateData
	 */
	public function testDashboardClockWidget_Update($data) {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for updating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->getWidgets()->last()->edit();
		$form->fill($data['fields']);
		$form->query('xpath://button[normalize-space()="Apply"]')->waitUntilReady()->one()->click();

		if ($data['expected'] === TEST_GOOD) {
			$this->page->waitUntilReady();
			$dashboard->save();
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');

			if (array_key_exists('Item', $data['fields'])) {
				$item_name = ($data['fields']['Item'] === 'Item for clock widget')
					? 'Host for clock widget: Item for clock widget'
					: 'Host for clock widget: Item for clock widget 2';
				$data['fields'] = array_replace($data['fields'], ['Item' => $item_name]);
			}

			// Check that widget updated.
			$dashboard->getWidgets()->last()->edit()->checkValue($data['fields']);
		} else {
			$this->assertMessage(TEST_BAD, null, $data['Error message']);
		}
	}

	/**
	 * Check clock widgets deletion.
	 */
	public function testDashboardClockWidget_Delete() {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$widget = $dashboard->edit()->getWidget('DeleteClock');
		$this->assertTrue($widget->isEditable());
		$dashboard->deleteWidget('DeleteClock');
		$dashboard->save();
		$this->page->waitUntilReady();
		$message = CMessageElement::find()->waitUntilPresent()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Dashboard updated', $message->getTitle());

		// Check that widget is not present on dashboard and in DB.
		$this->assertFalse($dashboard->getWidget('DeleteClock', false)->isValid());
		$sql = 'SELECT * FROM widget_field wf LEFT JOIN widget w ON w.widgetid=wf.widgetid'.
			' WHERE w.name='.zbx_dbstr('DeleteClock');
		$this->assertEquals(0, CDBHelper::getCount($sql));
	}

	public static function getCancelData() {
		return [
			// Cancel update widget.
			[
				[
					'existing_widget' => 'CancelClock',
					'save_widget' => true,
					'save_dashboard' => false
				]
			],
			[
				[
					'existing_widget' => 'CancelClock',
					'save_widget' => false,
					'save_dashboard' => true
				]
			],
			// Cancel create widget.
			[
				[
					'save_widget' => true,
					'save_dashboard' => false
				]
			],
			[
				[
					'save_widget' => false,
					'save_dashboard' => true
				]
			]
		];
	}

	/**
	 * Check if it's possible to cancel creation of clock widget.
	 *
	 * @dataProvider getCancelData
	 */
	public function testDashboardClockWidget_Cancel($data)
	{
		$old_hash = CDBHelper::getHash($this->sql);
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid)->waitUntilReady();
		$dashboard = CDashboardElement::find()->one();

		// Start updating or creating a widget.
		if (CTestArrayHelper::get($data, 'existing_widget', false)) {
			$widget = $dashboard->getWidget($data['existing_widget']);
			$form = $widget->edit();
		} else {
			$overlay = $dashboard->edit()->addWidget();
			$form = $overlay->asForm();
			$form->fill(['Type' => 'Clock', 'Clock type' => 'Analog']);
			$widget = $dashboard->getWidgets()->last();
		}

		$form->fill(['Name' => 'Widget to be cancelled']);

		// Save or cancel widget.
		if (CTestArrayHelper::get($data, 'save_widget', false)) {
			$form->submit();
			$this->page->waitUntilReady();

			// Check that changes took place on the unsaved dashboard.
			$this->assertTrue($dashboard->getWidget('Widget to be cancelled')->isValid());
		} else {
			$dialog = COverlayDialogElement::find()->one();
			$dialog->query('button:Cancel')->one()->click();
			$dialog->ensureNotPresent();

			// Check that widget changes didn't take place after pressing "Cancel".
			if (CTestArrayHelper::get($data, 'existing_widget', false)) {
				$this->assertNotEquals('Widget to be cancelled', $widget->waitUntilReady()->getHeaderText());
			} else {
				// If test fails and widget isn't canceled, need to wait until widget appears on the dashboard.
				sleep(5);

				if ($widget->getID() !== $dashboard->getWidgets()->last()->getID()) {
					$this->fail('New widget was added after pressing "Cancel"');
				}
			}
		}

		// Save or cancel dashboard update.
		if (CTestArrayHelper::get($data, 'save_dashboard', false)) {
			$dashboard->save();
		} else {
			$dashboard->cancelEditing();
		}

		// Confirm that no changes were made to the widget.
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}
}
