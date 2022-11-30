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
 * @backup widget
 * @backup profiles
 * @dataSource ClockWidgets
 */

class testDashboardClockWidget extends CWebTest {

	private static $name = 'UpdateClock';

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
		$timetype_values = ['Local time', 'Server time', 'Host time'];
		$tt_dropdown = $form->query('name', 'time_type')->asDropdown()->one();
		$this->assertEquals($timetype_values, $tt_dropdown->getOptions()->asText());

		// Check that it's possible to select host items, when time type is "Host Time".
		$form->fill(['Time type' => 'Host time']);
		$fields = ['Type', 'Name', 'Refresh interval', 'Time type', 'Clock type'];
		foreach (['Local time', 'Server time', 'Host time', ] as $type) {
			$form->fill(['Time type' => CFormElement::RELOADABLE_FILL($type)]);

			// If "Time type" is selected as "Host time", then label "Item" is inserted
			// in required position of $fields array.
			if (($type === 'Host time') ? array_splice($fields, 4, 0, ['Item']) : $fields) {

				// Filter only those labels which are visible in form and check if they are equal with previously defined
				// array $fields.
				$this->assertEquals($fields, $form->getLabels()->filter(new CElementFilter(CElementFilter::VISIBLE))->asText());
			}
			else {
				$this->assertEquals($fields, $form->getLabels()->filter(new CElementFilter(CElementFilter::VISIBLE))->asText());
			}
		}

		// Check that it's possible to change the status of "Show header" checkbox.
		$this->assertTrue($form->query('xpath://input[contains(@id, "show_header")]')->one()->isSelected());

		// Check that clock widget with "Time Type" - "Host time", displays host name, when clock widget name is empty.
		$form = $dashboard->getWidget('LayoutClock')->edit();
		$form->fill(['Name' => '']);
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$hostname = $dashboard->getWidget('Host for clock widget')->getText();
		$this->assertEquals("Host for clock widget", $hostname);

		// Update widget back to it's original name.
		$form = $dashboard->getWidget('Host for clock widget')->edit();
		$form->fill(['Name' => 'LayoutClock']);
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$form = $dashboard->getWidget('LayoutClock')->edit();

		// Check that "Clock type" buttons are present.
		$dashboard->getWidget('LayoutClock')->edit();
		foreach ($form->query('button', ['Analog', 'Digital']) as $button) {
			$this->assertTrue($form->query('radio', $button)->one()->isPresent());
		}

		// Check the default status of "Clock type" buttons.

		foreach (['id:clock_type_0', 'id:clock_type_1'] as $selector) {
			$this->assertTrue($this->query($selector)->exists());
		}

		// Check that there are three options what should Digital Clock widget show and select them as "Yes".
		$form->fill(['Clock type' => 'Digital']);
		$form->fill(['id:show_1' => true, 'id:show_2' => true, 'id:show_3' => true]);
		$checkboxes = ['id:show_1', 'id:show_2', 'id:show_3'];
		foreach ($form->query($checkboxes) as $checkbox) {
			$this->query($checkbox)->asCheckbox()->one()->check();
		}

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

		// Check if "Apply" and "Cancel" button are clickable.
		foreach (['Apply', 'Cancel'] as $button) {
			$this->assertTrue($this->query('button', $button)->one()->isClickable());
		}
	}

	public static function getCreateData() {
		return [
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_BAD,
					'Page Name' => 'First page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => false,
						'Name' => 'ClockWithoutItem',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'First page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
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
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
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
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
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
						'Invalid parameter "Size": value must be one of 1-100.',
					]
				]
			],
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
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
						'Invalid parameter "Size": value must be one of 1-100.',
					]
				]
			],
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
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
		if ($data['Page Name'] === 'Second page') {
			$this->query('xpath://li[2]//div[1]')->one()->click();
		}
		$form = $dashboard->edit()->addWidget()->asForm();
		$form->fill($data['Fields']);

		if ($data['Expected'] === TEST_GOOD) {
			$form->query('xpath://button[@class="dialogue-widget-save"]')->waitUntilReady()->one()->click();
			$this->page->waitUntilReady();
			$dashboard->save();
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');

			// After saving dashboard, it returns you to first page, if widget created in 2nd page,
			// then it needs to be opened.
			if ($data['Page Name'] === 'Second page') {
				$this->query('xpath://li[2]//div[1]')->one()->click();
			}

			// Get fields from saved widgets.
			$fields = $dashboard->getWidget($data['Fields']['Name'])->edit()->getFields();
			$original_widget = $fields->asValues();

			// Check if added widgets are truly added and fields are filled as expected.
			$created_widget = $fields->asValues();
			$this->assertEquals($original_widget, $created_widget);
		}
		else {
			if (($data['Fields']['Clock type'] === "Digital")) {
				$form->query('xpath://button[@class="dialogue-widget-save"]')->waitUntilReady()->one()->click();
				$this->assertMessage(TEST_BAD, null, $data['Error message']);
				$form->getOverlayMessage()->close();
				$this->query('button', 'Cancel')->waitUntilClickable()->one()->click();
			}
			else {
				$form->query('xpath://button[@class="dialogue-widget-save"]')->waitUntilReady()->one()->click();
				$this->assertMessage(TEST_BAD, null, 'Invalid parameter "Item": cannot be empty.');
				$form->getOverlayMessage()->close();
				$this->query('button', 'Cancel')->waitUntilClickable()->one()->click();
			}
		}
	}

	/**
	 * Check clock widgets successful simple update.
	 */
	public function testDashboardClockWidget_SimpleUpdate() {
		$old_hash = CDBHelper::getHash($this->sql);
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$dashboard->getWidget('CopyClock')->edit();
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public static function getUpdateData() {
		return [
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'ServerTimeClockForUpdate',
						'Refresh interval' => 'No refresh',
						'Time type' => 'Server time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'LocalTimeClockForUpdate',
						'Refresh interval' => '10 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'HostTimeClockForUpdate',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Host time',
						'Item' => 'Item for clock widget',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'LocalTimeClock123ForUpdate',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'Symb0l$InN@m3Cl0ckForUpdate',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => '1233212ForUpdate',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => '~@#$%^&*()_+|ForUpdate',
						'Refresh interval' => '30 seconds',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
						'Show header' => true,
						'Name' => 'ClockWithoutItemForUpdate',
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
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_GOOD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
						'Invalid parameter "Size": value must be one of 1-100.',
					]
				]
			],
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
						'Invalid parameter "Size": value must be one of 1-100.',
					]
				]
			],
			[
				[
					'Expected' => TEST_BAD,
					'Page Name' => 'Second page',
					'Fields' => [
						'Type' => 'Clock',
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
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();

		// Open second page, due to fact, that loading speed is huge for previously created clock widgets.
		if ($data['Page Name'] === 'Second page') {
			$this->query('xpath://li[2]//div[1]')->one()->click();
		}

		// Get widget fields before they are updated.
		$fields = $dashboard->getWidget(self::$name)->edit()->getFields();
		$original_widget = $fields->asValues();

		$form = $dashboard->getWidget(self::$name)->edit();
		$form->fill($data['Fields']);

		if ($data['Expected'] === TEST_GOOD) {
			$this->query('button', 'Apply')->waitUntilReady()->one()->click();
			$dashboard->save();
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');

			// Use updated widget as next widget which will be updated.
			if (array_key_exists('Name', $data['Fields'])) {
				self::$name = $data['Fields']['Name'];
			}

			// After saving dashboard, it opens by default first page.
			if ($data['Page Name'] === 'Second page') {
				$this->query('xpath://li[2]//div[1]')->one()->click();
			}

			// Check if widget is added to the dashboard by header.
			$this->assertEquals($dashboard->getWidget(self::$name)->getHeaderText(), self::$name);

			// Get fields from updated widgets.
			$fields = $dashboard->getWidget(self::$name)->edit()->getFields();
			$updated_widget = $fields->asValues();

			// Compare if widget fields are not equal with original widget fields.
			$this->assertNotEquals($original_widget, $updated_widget);
		}
		else {
			if (($data['Fields']['Clock type'] === "Digital")) {
				$this->query('button', 'Apply')->waitUntilReady()->one()->click();
				$this->assertMessage(TEST_BAD, null, $data['Error message']);
				$form->getOverlayMessage()->close();
				$this->query('button', 'Cancel')->waitUntilClickable()->one()->click();

			}
			else {
				$this->query('button', 'Apply')->waitUntilReady()->one()->click();
				$this->assertMessage(TEST_BAD, null, 'Invalid parameter "Item": cannot be empty.');
				$form->getOverlayMessage()->close();
			}
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
	public function testDashboardClockWidget_Cancel($data) {
		$old_hash = CDBHelper::getHash($this->sql);
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();

		// Start creating a widget.
		$overlay = $dashboard->edit()->addWidget();
		$form = $overlay->asForm();
		$form->fill(['Type' => 'Clock', 'Name' => 'Widget to be cancelled']);
		$widget = $dashboard->getWidgets()->last();

		// Save or cancel widget.
		if (CTestArrayHelper::get($data, 'save_widget', false)) {
			$form->submit();
			$this->page->waitUntilReady();

			// Check that changes took place on the unsaved dashboard.
			$this->assertTrue($dashboard->getWidget('Widget to be cancelled')->isVisible());
		}
		else {
			$this->query('button:Cancel')->one()->click();

			// Check that widget changes didn't take place after pressing "Cancel".
			if (CTestArrayHelper::get($data, 'existing_widget', false)) {
				$this->assertNotEquals('Widget to be cancelled', $widget->waitUntilReady()->getHeaderText());
			}
			else {
				// If test fails and widget isn't canceled, need to wait until widget appears on the dashboard.
				sleep(5);

				if ($widget->getID() !== $dashboard->getWidgets()->last()->getID()) {
					$this->fail('New widget was added after pressing "Cancel"');
				}
			}
		}

		// Cancel update process of already existing widget.
		$dashboard->edit()->getWidget('CancelClock')->edit();
		$this->query('button', 'Cancel')->waitUntilClickable()->one()->click();

		// Save or cancel dashboard update.
		if (CTestArrayHelper::get($data, 'save_dashboard', false)) {
			$dashboard->save();
		}
		else {
			$dashboard->cancelEditing();
		}

		// Confirm that no changes were made to the widget.
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}
}
