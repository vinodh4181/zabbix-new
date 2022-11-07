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

	/**
	 * Attach MessageBehavior to the test.
	 *
	 * @return array
	 */
	public function getBehaviors() {
		return [
			'class' => CMessageBehavior::class
		];
	}

	use TableTrait;

	/**
	 * SQL query to get widget and widget_field tables to compare hash values, but without widget_fieldid
	 * because it can change.
	 */
	private $sql = 'SELECT wf.widgetid, wf.type, wf.name, wf.value_int, wf.value_str, wf.value_groupid, wf.value_hostid,'.
	' wf.value_itemid, wf.value_graphid, wf.value_sysmapid, w.widgetid, w.dashboard_pageid, w.type, w.name, w.x, w.y,'.
	' w.width, w.height'.
	' FROM widget_field wf'.
	' INNER JOIN widget w'.
	' ON w.widgetid=wf.widgetid ORDER BY wf.widgetid, wf.name, wf.value_int, wf.value_str, wf.value_groupid, wf.value_hostid,'.
	' wf.value_itemid, wf.value_graphid';

	/**
	 * Check clock widgets layout.
	 */
	public function testDashboardClockWidget_CheckLayout() {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->getWidget('Server')->edit();

		// Check edit forms header.
		$this->assertEquals('Edit widget',
			$form->query('xpath://h4[contains(@id, "dashboard-widget-head-title-widget_properties")]')->one()->getText());

		// Check if widget type is selected as "Clock".
		$this->assertEquals('Clock', $form->query('xpath:.//button[contains(@class, "focusable")]')->one()->getText());

		// Check "Name" field max length.
		$this->assertEquals('255', $form->query('id:name')->one()->getAttribute('maxlength'));

		// Check fields "Refresh interval" values.
		$refreshinterval_values = ['Default (15 minutes)', 'No refresh', '10 seconds', '30 seconds', '1 minute',
			'2 minutes', '10 minutes', '15 minutes'];

		$ri_dropdown = $form->query('name', 'rf_rate')->asDropdown()->one();
		$this->assertEquals($refreshinterval_values, $ri_dropdown->getOptions()->asText());

		// Check fields "Time type" values.
		$timetype_values = ['Local time', 'Server time', 'Host time'];
		$tt_dropdown = $form->query('name', 'time_type')->asDropdown()->one();
		$this->assertEquals($timetype_values, $tt_dropdown->getOptions()->asText());

		// Check that it's possible to select host items, when time type is "Host Time".
		$form->fill(['Time type' => 'Host time']);
		$this->assertTrue($this->query('button:Select')->waitUntilVisible()->one()->isClickable());
		$form->fill(['Time type' => 'Server time']);

		// Check that it's possible to change the status of "Show header" checkbox.
		$this->assertTrue($form->query('xpath://input[contains(@id, "show_header")]')->one()->isSelected());

		// Check if "Apply" and "Cancel" buttons are clickable.
		foreach($form->query('button', ['Add', 'Cancel'])->all() as $button) {
			$this->assertTrue($button->isClickable());
		}

		// Select Digital and check if "Analog" and "Digital" buttons are selectable.
		$form->fill(['Clock type' => 'Digital']);
		foreach($form->query('button', ['Analog', 'Digital'])->all() as $button) {
			$this->assertTrue($button->isSelected());
		}

		// Check that there are three options what should Digital Clock widget show and select them as "Yes".
		$form->fill(['id:show_1' => true, 'id:show_2' => true, 'id:show_3' => true]);
		foreach($form->query('button', ['Date', 'Time', 'Time zone'])->all() as $button) {
			$this->assertTrue($button->isSelected());
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
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendServerClock',
						'Refresh interval' => 'No refresh',
						'Time type' => 'Server time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendLocalClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Local time',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendHostClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Host time',
						'Item' => 'DEV-2236 item',
						'Clock type' => 'Analog'
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
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
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
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
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
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
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => false,
						'id:show_2' => true,
						'id:show_3' => true
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => false,
						'id:show_2' => true,
						'id:show_3' => false
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => true
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => false,
						'id:show_2' => false,
						'id:show_3' => true
					]
				]
			],
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'FrontendDigitalClock',
						'Refresh interval' => 'Default (15 minutes)',
						'Time type' => 'Local time',
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'xpath://button[@id="lbl_bg_color"]/..' => 'C2185B',
						'id:date_size' => '20',
						'id:date_bold' => true,
						'xpath://button[@id="lbl_date_color"]/..' => '7F3700',
						'id:time_size' => '30',
						'id:time_bold' => true,
						'xpath://button[@id="lbl_time_color"]/..' => 'FFEB3B',
						'id:time_sec' => true,
						'id:time_format_0' => false,
						'id:time_format_1' => true,
						'id:tzone_size' => '20',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => '37474F',
						'id:tzone_format_0' => true
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
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->edit()->addWidget()->asForm();
		$form->fill($data['Fields']);
		$form->query('xpath://button[contains(@class, "dialogue-widget-save")]')->waitUntilClickable()->one()->click();
		$dashboard->save();
	}

	/**
	 * Check clock widgets successful simple update.
	 */
	public function testDashboardClockWidget_SimpleUpdate() {
		$old_hash = CDBHelper::getHash($this->sql);
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->getWidget('FrontendLocalClock')->edit();
		$form->submit();
		$this->page->waitUntilReady();
		$dashboard->getWidget('FrontendLocalClock');
		$dashboard->save();
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public static function getUpdateData() {
		return [
			[
				[
					'UpdateFields' => [
						'Type' => 'Clock',
						'Name' => 'UpdatedClockWidget'
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
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->getWidget('FrontendLocalClock')->edit();
		$form->fill($data['UpdateFields'])->waitUntilReady();
		$form->submit();
		$this->page->waitUntilReady();

		// Check that a widget with the corresponding header exists.
		$header = ($data['UpdateFields']['Name']);
		$dashboard->getWidget($header);
		$dashboard->save();

		// Check that Dashboard has been saved and that widget has been added.
		$message = CMessageElement::find()->waitUntilVisible()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Dashboard updated', $message->getTitle());
		$this->assertEquals($dashboard->getWidget($header)->getText(), 'UpdatedClockWidget');
	}

	/**
	 * Check clock widgets successful copy.
	 */
	public function testDashboardClockWidget_Copy() {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();

		// Get size of widget which will be copied;
		$sql = "Select width, height from widget where name =".zbx_dbstr('Server')." ORDER BY widgetid DESC";
		$original_size = CDBHelper::getRow($sql);

		$dashboard->copyWidget('Server');
		$dashboard->edit();
		$dashboard->pasteWidget();
		sleep(5);
		$this->query('xpath://div[contains(@class, "is-loading")]')->waitUntilNotPresent();
		$dashboard->save();
		$this->page->waitUntilReady();

		// Get size of widget which has been copied;
		$copied_size = CDBHelper::getRow($sql);
		$this->assertEquals($original_size, $copied_size);
	}

	public static function getDeleteData() {
		return [
			[
				[
					'Fields' => [
						'Type' => 'Clock',
						'Name' => 'Local'
					]
				]
			]
		];
	}

	/**
	 * Check clock widgets deletion.
	 *
	 * @dataProvider getDeleteData
	 */
	public function testDashboardClockWidget_Delete($data) {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$widget = $dashboard->edit()->getWidget($data['Fields']['Name']);
		$this->assertTrue($widget->isEditable());
		$dashboard->deleteWidget($data['Fields']['Name']);
		$dashboard->save();
		$this->page->waitUntilReady();
		$message = CMessageElement::find()->waitUntilPresent()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Dashboard updated', $message->getTitle());

		// Check that widget is not present on dashboard and in DB.
		$this->assertFalse($dashboard->getWidget($data['Fields']['Name'], false)->isValid());
		$sql = 'SELECT * FROM widget_field wf LEFT JOIN widget w ON w.widgetid=wf.widgetid'.
			' WHERE w.name='.zbx_dbstr($data['Fields']['Name']);
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
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.DEV-2236 dashboard');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();

		// Start creating a widget.
		$overlay = $dashboard->edit()->addWidget();
		$form = $overlay->asForm();
		$form->getField('Type')->fill('Clock');
		$form->getField('Name')->fill('Widget to be cancelled');
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
