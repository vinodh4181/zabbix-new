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


require_once dirname(__FILE__) . '/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../../include/helpers/CDataHelper.php';
require_once dirname(__FILE__).'/../traits/TableTrait.php';
require_once dirname(__FILE__).'/../behaviors/CMessageBehavior.php';

/**
 * @backup widget, profiles
 *
 * @dataSource ClockWidgets
 */

class testDashboardClockWidget extends CWebTest {

	use TableTrait;

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
			' ON w.widgetid=wf.widgetid '.
			' ORDER BY wf.widgetid, wf.name, wf.value_int, wf.value_str, wf.value_groupid, wf.value_itemid, wf.value_graphid';

	/**
	 * Check clock widgets layout.
	 */
	public function testDashboardClockWidget_Layout() {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$form = CDashboardElement::find()->one()->edit()->addWidget()->asForm();
		$dialog = COverlayDialogElement::find()->waitUntilReady()->one();
		$this->assertEquals('Add widget', $dialog->getTitle());
		$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Clock')]);

		$form->checkValue([
			'id:show_header' => true,
			'Name' => '',
			'Refresh interval' => 'Default (15 minutes)',
			'Time type' => 'Local time',
			'Clock type' => 'Analog',
			'id:show_header' => true
		]);

		// Check fields "Refresh interval" and "Time type" values.
		$dropdowns =[
			'Refresh interval' => ['Default (15 minutes)',  'No refresh', '10 seconds', '30 seconds', '1 minute', '2 minutes',
					'10 minutes',  '15 minutes'
			],
			'Time type' => ['Local time', 'Server time', 'Host time']
		];

		foreach ($dropdowns as $field => $options) {
			$this->assertEquals($options, $form->getField($field)->asDropdown()->getOptions()->asText());
		}

		// Check that it's possible to select host items, when time type is "Host Time".
		$fields = ['Type', 'Name', 'Refresh interval', 'Time type', 'Clock type'];

		foreach (['Local time', 'Server time', 'Host time'] as $type) {
			$form->fill(['Time type' => CFormElement::RELOADABLE_FILL($type)]);

			if ($type === 'Host time') {
				array_splice($fields, 4, 0, ['Item']);
				$form->checkValue(['Item' => '']);
				$form->isRequired('Item');
			}

			$this->assertEquals($fields, $form->getLabels()->filter(new CElementFilter(CElementFilter::VISIBLE))->asText());
		}

		// Check that it's possible to change the status of "Show header" checkbox.
		$form->checkValue(['id:show_header' => true]);

		// Check if Apply and Cancel button are clickable and there are two of them.
		$dialog->invalidate();
		$this->assertEquals(2, $dialog->getFooter()->query('button', ['Add', 'Cancel'])->all()
				->filter(new CElementFilter(CElementFilter::CLICKABLE))->count()
		);

		// Check fileds' visibility depending on Analog or Digital clock type.
		foreach (['Analog' => false, 'Digital' => true] as $type => $status) {
			$form->fill(['Clock type' => $type]);

			// Check Show and Advanced configuration checkboxes visibility and values. (Only Time is checked by default).
			foreach (['show_1' => false, 'show_2' => true, 'show_3' => false, 'adv_conf' => false] as $id => $checked) {
				$checkbox = $form->query('id', $id)->asCheckbox()->one();
				$this->assertTrue($checkbox->isVisible($status));
				$this->assertTrue($checkbox->asCheckbox()->isChecked($checked));
			}

			if ($status) {
				$form->isRequired('Show');

				// Set Advanced configuration=true to check its fields.
				$form->fill(['Advanced configuration' => true]);

				// Check that only Background color and Time fields are visible (because only Time checkbox is checked).
				foreach ( ['Background color' => true, 'Date' => false, 'Time' => true, 'Time zone' => false] as $name => $visible) {
					$this->assertTrue($form->getField($name)->isVisible($visible));
				}

				// Fill other Show checkboxes and get other Advanced config fields.
				$form->fill(['id:show_1' => true, 'id:show_3' => true]);

				$advanced_configuration = [
					'Date' => ['id:date_size' => 20, 'id:date_bold' => false, 'id:date_color' => null],
					'Time' => ['id:time_size' => 30, 'id:time_bold' => false, 'id:time_color' => null, 'id:time_sec' => true,
							'id:time_format' => '24-hour'
					] ,
					// This is Time zone field found by xpath, because we have one more field with Time zone label.
					'xpath:.//div[@class="fields-group fields-group-tzone"]' => ['id:tzone_size' => 20, 'id:tzone_bold' => false,
							'id:tzone_color' => null, 'id:tzone_timezone' => 'Local default: (UTC+02:00) Europe/Riga' ,
							'id:tzone_format' => 'Short'
					]
				];

				// Check Advanced config fields depending on Time type.
				foreach (['Local time', 'Server time', 'Host time'] as $type) {
					$form->fill(['Time type' => CFormElement::RELOADABLE_FILL($type)]);

					// Check that with Host time 'Time zone' and 'Format' fields disappear.
					if ($type === 'Host time') {
						$advanced_configuration['xpath:.//div[@class="fields-group fields-group-tzone"]'] = ['id:tzone_size' => 20,
							'id:tzone_bold' => false, 'id:tzone_color' => null
						];

						foreach (['id:tzone_timezone', 'id:tzone_format'] as $id) {
							$this->assertFalse( $form->getField($id)->isVisible());
						}
					}

					// Check Advanced fields' visibility and values.
					foreach ($advanced_configuration as $field => $config) {
						$advanced_field = $form->getField($field);
						$this->assertTrue($advanced_field->isVisible());
						$this->assertTrue($advanced_field->isEnabled());

						foreach ($config as $id => $value) {
							$advanced_subfield = $form->getField($id);
							$this->assertEquals($value, $advanced_subfield->getValue());
							$this->assertTrue($advanced_subfield->isEnabled());
						}
					}
				}

				// Check form fields' maximal lenghts.
				foreach (['Name' =>  255, 'id:date_size' => 3, 'id:time_size' => 3, 'id:tzone_size' => 3] as $field => $length) {
					$this->assertEquals($length, $form->getField($field)->getAttribute('maxlength'));
				}

				// Now remove the Time checkbox from Show field and check that only its Advanced config disappeared.
				$form->fill(['id:show_2' => false]);

				foreach ( ['Date' => true, 'Time' => false, 'xpath:.//div[@class="fields-group fields-group-tzone"]' => true]
						as $name => $visible) {
					$this->assertTrue($form->getField($name)->isVisible($visible));
				}
			}
		}
	}

	/**
	 * Function checks specific scenario when Clock widget has "Time type" as "Host time"
	 * and name for widget itself isn't provided, after creating widget, host name should be displayed on widget as
	 * the widget name.
	 */
	public function testDashboardClockWidget_CheckClockWidgetsName() {
		$dashboardid = CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one();
		$form = $dashboard->getWidget('LayoutClock')->edit();
		$form->fill(['Name' => '']);
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$this->assertMessage(TEST_GOOD, 'Dashboard updated');
		$this->assertEquals('Host for clock widget', $dashboard->getWidget('Host for clock widget')->getHeaderText());
		$dashboard->getWidget('Host for clock widget')->edit()->fill(['Name' => 'LayoutClock']);
		$this->query('button', 'Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$dashboard->save();
		$this->assertMessage(TEST_GOOD, 'Dashboard updated');
		$this->assertEquals('LayoutClock', $dashboard->getWidget('LayoutClock')->getHeaderText());
	}

	public static function getClockWidgetCommonData() {
		return [
			// #0 Name and show header change.
			[
				[
					'check_dialog_properties' => true,
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'Name and show header name'
					]
				]
			],
			// #1 Refresh interval change.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Refresh interval' => '10 seconds',
						'Name' => 'Refresh interval change name'
					]
				]
			],
			// #2 Time type changed to Server time.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Time type changed to Server time',
						'Time type' => CFormElement::RELOADABLE_FILL('Server time')
					]
				]
			],
			// #3 Time type changed to Local time.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Time type changed to Local time',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time')
					]
				]
			],
			// #4 Time type and refresh interval changed.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Type' => 'Clock',
						'Time type' => CFormElement::RELOADABLE_FILL('Server time'),
						'Refresh interval' => '10 seconds',
						'Name' => 'Time type and refresh interval changed'
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
						'Time type' => CFormElement::RELOADABLE_FILL('Server time')
					]
				]
			],
			// #9 Host time without item.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Show header' => false,
						'Name' => 'ClockWithoutItem',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Host time'),
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
						'Name' => 'Time type with item',
						'Time type' => CFormElement::RELOADABLE_FILL('Host time'),
						'Item' => 'Item for clock widget'
					]
				]
			],
			// #11 Update item.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Update item',
						'Time type' => CFormElement::RELOADABLE_FILL('Host time'),
						'Item' => 'Item for clock widget 2'
					]
				]
			],
			// #12.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'HostTimeClock',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Host time'),
						'Item' => 'Item for clock widget',
						'Clock type' => 'Analog'
					]
				]
			],
			// #13.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Show header' => true,
						'Name' => 'LocalTimeClock123',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Analog'
					]
				]
			],
			// #14.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => '1233212',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Analog'
					]
				]
			],
			// #15.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false
					]
				]
			],
			// #16.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock2',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => false
					]
				]
			],
			// #15.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock3',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true
					]
				]
			],
			// #16.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock4',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false,
						'Advanced configuration' => true
					]
				]
			],
			// #17.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock5',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
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
			// #18.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock6',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
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
						'id:time_format' => '24-hour'
					]
				]
			],
			// #19.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock7',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
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
						'id:time_format' => '12-hour'
					]
				]
			],
			// #20.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock8',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
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
						'id:time_format' => '12-hour',
						'id:tzone_size' => '20',
						'id:tzone_bold' => false,
						'xpath://button[@id="lbl_tzone_color"]/..' => '06081F',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Atlantic/Stanley'),
						'id:time_format' => '24-hour'
					]
				]
			],
			// #21.
			[
				[
					'expected' => TEST_GOOD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock9',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
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
						'id:time_format' => '12-hour',
						'id:tzone_size' => '35',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:time_format' => '12-hour'
					]
				]
			],
			// #22 Empty Size fields.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'id:date_size' => '',
						'id:time_size' => '',
						'id:tzone_size' => ''
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #23 Characters in Size fields.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'id:date_size' => 'tes',
						'id:time_size' => 'tfi',
						'id:tzone_size' => 'eld'
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #24 Zeros in Size fields.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'id:date_size' => 0,
						'id:time_size' => 0,
						'id:tzone_size' => 0
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #25 Negatives in Size fields.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'id:date_size' => -1,
						'id:time_size' => -12,
						'id:tzone_size' => -99
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #26 Floats in Size fields.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => true,
						'id:show_3' => true,
						'Advanced configuration' => true,
						'id:date_size' => 0.5,
						'id:time_size' => 1.3,
						'id:tzone_size' => 9.9
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #27.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock12',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
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
						'id:time_format' => '12-hour',
						'id:tzone_size' => '353',
						'id:tzone_bold' => true,
						'xpath://button[@id="lbl_tzone_color"]/..' => 'CDDC39',
						'xpath://button[@id="label-tzone_timezone"]/..' => CDateTimeHelper::getTimeZoneFormat('Africa/Bangui'),
						'id:time_format' => '12-hour'
					],
					'Error message' => [
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.',
						'Invalid parameter "Size": value must be one of 1-100.'
					]
				]
			],
			// #28.
			[
				[
					'expected' => TEST_BAD,
					'second_page' => true,
					'fields' => [
						'Show header' => true,
						'Name' => 'DigitalClock13',
						'Refresh interval' => '30 seconds',
						'Time type' => CFormElement::RELOADABLE_FILL('Host time'),
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
						'id:time_format' => '12-hour',
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
	 * Function for checking Clock widget form.
	 *
	 * @param array      $data      data provider
	 * @param boolean    $update    true if update scenario, false if create
	 *
	 * @dataProvider getClockWidgetCommonData
	 */
	public function checkFormClockWidget($data, $update = false) {
		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$old_hash = CDBHelper::getHash($this->sql);
		}

		$dashboardid = $update
			? CDataHelper::get('ClockWidgets.dashboardids.Dashboard for updating clock widgets')
			: CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets');

		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.$dashboardid);
		$dashboard = CDashboardElement::find()->one()->waitUntilVisible();

		if (array_key_exists('second_page', $data) && $update === false) {
			$dashboard->selectPage('Second page');
			$dashboard->invalidate();
		}

		$form = $update
			? $dashboard->getWidgets()->last()->edit()
			: $dashboard->edit()->addWidget()->asForm();
		$dialog = COverlayDialogElement::find()->one();

		if (CTestArrayHelper::get($data, 'check_dialog_properties', false) && $update === true) {
			$this->assertEquals('Edit widget', $dialog->getTitle());
			$form->checkValue(['Type' => 'Clock']);
		}

		if (!$update) {
			$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Clock')]);
		}

		$form->fill($data['fields']);
		$form->submit();

		if ($data['expected'] === TEST_GOOD) {
			$dashboard->save();
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');

			/**
			 * After saving dashboard, it returns you to first page, if widget created in 2nd page,
			 * then it needs to be opened.
			 */
			if (array_key_exists('second_page', $data) && $update === false) {
				$dashboard->selectPage('Second page');
				$dashboard->invalidate();
			}

			if (array_key_exists('Item', $data['fields'])) {
				$data['fields'] = array_replace($data['fields'], ['Item' => 'Host for clock widget: '.
						$data['fields']['Item']]);
			}

			// Check that widget updated.
			$dashboard->edit();
			$dashboard->getWidgets()->last()->edit()->checkValue($data['fields']);

			// Check that widget is saved in DB.
			$this->assertEquals(1, CDBHelper::getCount('SELECT *'.
				' FROM widget w'.
				' WHERE EXISTS ('.
					' SELECT NULL'.
					' FROM dashboard_page dp'.
					' WHERE w.dashboard_pageid=dp.dashboard_pageid'.
						' AND dp.dashboardid='.$dashboardid.
						' AND w.name ='.zbx_dbstr(CTestArrayHelper::get($data['fields'], 'Name', '')).
				')'
			));
		}
		else {
			$this->assertMessage(TEST_BAD, null, $data['Error message']);

			// Check that DB hash is not changed.
			$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
		}
	}

	/**
	 * Function for checking Clock Widgets creation.
	 *
	 * @param array $data    data provider
	 *
	 * @dataProvider getClockWidgetCommonData
	 */
	public function testDashboardClockWidget_Create($data) {
		$this->checkFormClockWidget($data);
	}

	/**
	 * Function for checking Clock Widgets successful update.
	 *
	 * @param array $data    data provider
	 *
	 * @dataProvider getClockWidgetCommonData
	 */
	public function testDashboardClockWidget_Update($data) {
		$this->checkFormClockWidget($data, true);
	}

	public function testDashboardClockWidget_SimpleUpdate() {
		$this->checkNoChanges();
	}

	public static function getCancelData() {
		return [
			// Cancel creating widget with saving the dashboard.
			[
				[
					'cancel_form' => true,
					'create_widget' => true,
					'save_dashboard' => true
				]
			],
			// Cancel updating widget with saving the dashboard.
			[
				[
					'cancel_form' => true,
					'create_widget' => false,
					'save_dashboard' => true
				]
			],
			// Create widget without saving the dashboard.
			[
				[
					'cancel_form' => false,
					'create_widget' => false,
					'save_dashboard' => false
				]
			],
			// Update widget without saving the dashboard.
			[
				[
					'cancel_form' => false,
					'create_widget' => false,
					'save_dashboard' => false
				]
			]
		];
	}

	/**
	 * @dataProvider getCancelData
	 */
	public function testDashboardClockWidget_Cancel($data) {
		$this->checkNoChanges($data['cancel_form'], $data['create_widget'], $data['save_dashboard']);
	}

	/**
	 * Function for checking canceling form or submitting without any changes.
	 *
	 * @param boolean $cancel            true if cancel scenario, false if form is submitted
	 * @param boolean $create            true if create scenario, false if update
	 * @param boolean $save_dashboard    true if dashboard will be saved, false if not
	 */
	private function checkNoChanges($cancel = false, $create = false, $save_dashboard = true) {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.
				CDataHelper::get('ClockWidgets.dashboardids.Dashboard for creating clock widgets')
		);
		$dashboard = CDashboardElement::find()->one();
		$old_widget_count = $dashboard->getWidgets()->count();

		$form = $create
			? $dashboard->edit()->addWidget()->asForm()
			: $dashboard->getWidget('CancelClock')->edit();

		$dialog = COverlayDialogElement::find()->one()->waitUntilReady();

		if (!$create) {
			$values = $form->getFields()->asValues();
		}
		else {
			$form->fill(['Type' => 'Clock']);
		}

		if ($cancel || !$save_dashboard) {
			$form->fill([
						'Name' => 'Widget to be cancelled',
						'Refresh interval' => '10 minutes',
						'Time type' => CFormElement::RELOADABLE_FILL('Local time'),
						'Clock type' => 'Digital',
						'id:show_1' => true,
						'id:show_2' => false,
						'id:show_3' => false,
						'Advanced configuration' => true
			]);
		}

		if ($cancel) {
			$dialog->query('button:Cancel')->one()->click();
		}
		else {
			$form->submit();
		}

		COverlayDialogElement::ensureNotPresent();

		if (!$cancel) {
			$dashboard->getWidget(!$save_dashboard ? 'Widget to be cancelled' : 'CancelClock')->waitUntilReady();
		}

		if ($save_dashboard) {
			$dashboard->save();
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');
		}
		else {
			$dashboard->cancelEditing();
		}

		$this->assertEquals($old_widget_count, $dashboard->getWidgets()->count());

		// Check that updating widget form values did not change in frontend.
		if (!$create && !$save_dashboard) {
			$this->assertEquals($values, $dashboard->getWidget('CancelClock')->edit()->getFields()->asValues());
		}

		// Check that DB hash is not changed.
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
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
		$this->assertMessage(TEST_GOOD, 'Dashboard updated');

		// Check that widget is not present on dashboard and in DB.
		$this->assertFalse($dashboard->getWidget('DeleteClock', false)->isValid());
		$this->assertEquals(0, CDBHelper::getCount('SELECT *'.
			' FROM widget_field wf'.
			' LEFT JOIN widget w'.
			' ON w.widgetid=wf.widgetid'.
			' WHERE w.name='.zbx_dbstr('DeleteClock')
		));
	}
}
