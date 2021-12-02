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

require_once dirname(__FILE__) . '/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../behaviors/CMessageBehavior.php';

/**
 * @onBefore prepareDashboardCopyWidgetsData
 *
 * @backup widget, profiles
 */
class testDashboardCopyWidgets extends CWebTest {

	const NEW_PAGE_NAME = 'Test_page';
	const UPDATE_TEMPLATEID = 50000;
	const TEMPLATED_PAGE_NAME = 'Page for pasting widgets';

	private static $replaced_widget_name = "Test widget for replace";
	private static $replaced_widget_size = [ 'width' => '13', 'height' => '8'];

	protected static $dashboard_id;
	protected static $paste_dashboard_id;

	private static $dashboardid_with_widgets;
	private static $empty_dashboardid;
	private static $templated_page_id;

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

	/**
	 * Create new dashboards for copy widgets scenarios.
	 */
	public function prepareDashboardCopyWidgetsData() {
		$response = CDataHelper::call('dashboard.create', [
			[
				'name' => 'Dashboard for Copying widgets',
				'display_period' => 30,
				'auto_start' => 1,
				'pages' => [
					[
						'name' => 'Page 1',
						'widgets' => [
							[
								'name' => 'Test copy Action log',
								'type' => 'actionlog',
								'x' => 0,
								'y' => 0,
								'width' => 7,
								'height' => 6,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '10'
									],
									[
										'type' => 0,
										'name' => 'show_lines',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'sort_triggers',
										'value' => 7
									]
								]
							],
							[
								'name' => 'Test copy Clock',
								'type' => 'clock',
								'x' => 7,
								'y' => 0,
								'width' => 2,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '60'
									],
									[
										'type' => 0,
										'name' => 'time_type',
										'value' => 2
									],
									[
										'type' => 4,
										'name' => 'itemid',
										'value' => 29172
									]
								]
							],
							[
								'name' => 'Test copy Data overview',
								'type' => 'dataover',
								'x' => 9,
								'y' => 0,
								'width' => 4,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '0'
									],
									[
										'type' => 0,
										'name' => 'show_suppressed',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'style',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'application',
										'value' => '3'
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50011
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 50012
									]
								]
							],
							[
								'name' => 'Test copy classic Graph',
								'type' => 'graph',
								'x' => 13,
								'y' => 0,
								'width' => 11,
								'height' => 6,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '30'
									],
									[
										'type' => 0,
										'name' => 'dynamic',
										'value' => 10
									],
									[
										'type' => 0,
										'name' => 'show_legend',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'source_type',
										'value' => 1
									],
									[
										'type' => 4,
										'name' => 'itemid',
										'value' => 99088
									]
								]
							],
							[
								'name' => 'Test copy Favourite graphs',
								'type' => 'favgraphs',
								'x' => 7,
								'y' => 2,
								'width' => 2,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '30'
									]
								]
							],
							[
								'name' => 'Test copy Favourite maps',
								'type' => 'favmaps',
								'x' => 9,
								'y' => 2,
								'width' => 4,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '600'
									]
								]
							],
							[
								'name' => 'Test copy Discovery status',
								'type' => 'discovery',
								'x' => 9,
								'y' => 4,
								'width' => 4,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '900'
									]
								]
							],
							[
								'name' => 'Test copy Graph prototype',
								'type' => 'graphprototype',
								'x' => 0,
								'y' => 6,
								'width' => 13,
								'height' => 4,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => '0',
										'name' => 'columns',
										'value' => 3
									],
									[
										'type' => '0',
										'name' => 'rows',
										'value' => 2
									],
									[
										'type' => '0',
										'name' => 'dynamic',
										'value' => 1
									],
									[
										'type' => '0',
										'name' => 'rf_rate',
										'value' => '30'
									],
									[
										'type' => '0',
										'name' => 'show_legend',
										'value' => 0
									],
									[
										'type' => '7',
										'name' => 'graphid',
										'value' => 600000
									]
								]
							],
							[
								'name' => 'Test copy Host availability',
								'type' => 'hostavail',
								'x' => 13,
								'y' => 6,
								'width' => 5,
								'height' => 4,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'interface_type',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'interface_type',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'interface_type',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'layout',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'maintenance',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '60'
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50013
									]
								]
							],
							[
								'name' => 'Test copy Map',
								'type' => 'map',
								'x' => 18,
								'y' => 6,
								'width' => 6,
								'height' => 4,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 1,
										'name' => 'reference',
										'value' => 'OYKZW'
									],
									[
										'type' => 8,
										'name' => 'sysmapid',
										'value' => 3
									]
								]
							],
							[
								'name' => 'Test copy plain text',
								'type' => 'plaintext',
								'x' => 13,
								'y' => 10,
								'width' => 5,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'dynamic',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '0'
									],
									[
										'type' => 0,
										'name' => 'show_as_html',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'show_lines',
										'value' => 12
									],
									[
										'type' => 0,
										'name' => 'style',
										'value' => 1
									],
									[
										'type' => 4,
										'name' => 'itemids',
										'value' => 29171
									]
								]
							],
							[
								'name' => 'Test copy Problem hosts',
								'type' => 'problemhosts',
								'x' => 18,
								'y' => 10,
								'width' => 6,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'evaltype',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'ext_ack',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'hide_empty_groups',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '30'
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 5
									],
									[
										'type' => 0,
										'name' => 'show_suppressed',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'tags.operator.0',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'problem',
										'value' => 'Test'
									],
									[
										'type' => 1,
										'name' => 'tags.tag.0',
										'value' => 'Tag1'
									],
									[
										'type' => 1,
										'name' => 'tags.value.0',
										'value' => '1'
									],
									[
										'type' => 2,
										'name' => 'exclude_groupids',
										'value' => 50014
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50011
									],
									[
										'type' => 4,
										'name' => 'itemids',
										'value' => 29171
									]
								]
							],
							[
								'name' => 'Test copy Problems',
								'type' => 'problems',
								'x' => 0,
								'y' => 12,
								'width' => 8,
								'height' => 6,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 4
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'evaltype',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '900'
									],
									[
										'type' => 0,
										'name' => 'show',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'show_lines',
										'value' => 12
									],
									[
										'type' => 0,
										'name' => 'show_opdata',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'show_suppressed',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'show_tags',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'sort_triggers',
										'value' => 15
									],
									[
										'type' => 0,
										'name' => 'show_timeline',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'tag_name_format',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'tags.operator.0',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'tags.operator.1',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'unacknowledged',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'problem',
										'value' => 'test2'
									],
									[
										'type' => 1,
										'name' => 'tags.value.0',
										'value' => '2'
									],
									[
										'type' => 1,
										'name' => 'tags.value.1',
										'value' => '33'
									],
									[
										'type' => 1,
										'name' => 'tag_priority',
										'value' => '1,2'
									],
									[
										'type' => 1,
										'name' => 'tags.tag.0',
										'value' => 'tag2'
									],
									[
										'type' => 1,
										'name' => 'tags.tag.1',
										'value' => 'tagg33'
									],
									[
										'type' => 2,
										'name' => 'exclude_groupids',
										'value' => 50014
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50005
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 99026
									]
								]
							],
							[
								'name' => 'Test copy Problems 2',
								'type' => 'problems',
								'x' => 8,
								'y' => 12,
								'width' => 16,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '60'
									],
									[
										'type' => 0,
										'name' => 'show',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'show_lines',
										'value' => 5
									],
									[
										'type' => 0,
										'name' => 'show_opdata',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'show_suppressed',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'show_tags',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'show_timeline',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'sort_triggers',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'tag_name_format',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'tags.operator.0',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'tags.operator.1',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'unacknowledged',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'problem',
										'value' => 'test4'
									],
									[
										'type' => 1,
										'name' => 'tags.value.0',
										'value' => '3'
									],
									[
										'type' => 1,
										'name' => 'tags.value.1',
										'value' => '44'
									],
									[
										'type' => 1,
										'name' => 'tag_priority',
										'value' => 'test5, test6',
									],
									[
										'type' => 1,
										'name' => 'tags.tag.0',
										'value' => 'tag3'
									],
									[
										'type' => 1,
										'name' => 'tags.tag.1',
										'value' => 'tag44'
									],
									[
										'type' => 2,
										'name' => 'exclude_groupids',
										'value' => 50014
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50006
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 99015
									]
								]
							],
							[
								'name' => 'Test copy Problems by severity',
								'type' => 'problemsbysv',
								'x' => 8,
								'y' => 14,
								'width' => 16,
								'height' => 4,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'evaltype',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'ext_ack',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'layout',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '30'
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'show_opdata',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'show_timeline',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'show_type',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'tags.operator.0',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'problem',
										'value' => 'test problem'
									],
									[
										'type' => 1,
										'name' => 'tags.tag.0',
										'value' => 'tag5'
									],
									[
										'type' => 1,
										'name' => 'tags.value.0',
										'value' => '5'
									],
									[
										'type' => 2,
										'name' => 'exclude_groupids',
										'value' => 50008
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50011
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 99012
									]
								]
							],
							[
								'name' => 'Test copy System information',
								'type' => 'systeminfo',
								'x' => 0,
								'y' => 18,
								'width' => 7,
								'height' => 3,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '30'
									]
								]
							],
							[
								'name' => 'Test copy Trigger overview',
								'type' => 'trigover',
								'x' => 7,
								'y' => 18,
								'width' => 17,
								'height' => 3,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '120'
									],
									[
										'type' => 0,
										'name' => 'show',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'show_suppressed',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'style',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'application',
										'value' => 'Inventory'
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50011
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 99012
									]
								]
							],
							[
								'name' => 'Test copy URL',
								'type' => 'url',
								'x' => 0,
								'y' => 21,
								'width' => 7,
								'height' => 3,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'dynamic',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '120'
									],
									[
										'type' => 1,
										'name' => 'url',
										'value' => 'https://www.zabbix.com/integrations'
									]
								]
							],
							[
								'name' => 'Test copy Web monitoring',
								'type' => 'web',
								'x' => 7,
								'y' => 21,
								'width' => 3,
								'height' => 3,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'maintenance',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '120'
									],
									[
										'type' => 2,
										'name' => 'exclude_groupids',
										'value' => 50008
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50016
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 99133
									]
								]
							],
							[
								'name' => 'Test copy Problems 3',
								'type' => 'problems',
								'x' => 10,
								'y' => 21,
								'width' => 14,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'evaltype',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '60'
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 4
									],
									[
										'type' => 0,
										'name' => 'severities',
										'value' => 5
									],
									[
										'type' => 0,
										'name' => 'show',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'show_lines',
										'value' => 5
									],
									[
										'type' => 0,
										'name' => 'show_opdata',
										'value' => 2,
									],
									[
										'type' => 0,
										'name' => 'show_suppressed',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'show_tags',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'sort_triggers',
										'value' => 3
									],
									[
										'type' => 0,
										'name' => 'tags.operator.0',
										'value' => 1
									],
									[
										'type' => 0,
										'name' => 'tag_name_format',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'unacknowledged',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'problem',
										'value' => 'test5'
									],
									[
										'type' => 1,
										'name' => 'tag_priority',
										'value' => 'test7, test8'
									],
									[
										'type' => 1,
										'name' => 'tags.tag.0',
										'value' => 'tag9'
									],
									[
										'type' => 1,
										'name' => 'tags.value.0',
										'value' => '9'
									],
									[
										'type' => 2,
										'name' => 'exclude_groupids',
										'value' => 50014
									],
									[
										'type' => 2,
										'name' => 'groupids',
										'value' => 50006
									],
									[
										'type' => 3,
										'name' => 'hostids',
										'value' => 99015
									]
								]
							],
							[
								'name' => 'Test copy Graph prototype 2',
								'type' => 'graphprototype',
								'x' => 10,
								'y' => 23,
								'width' => 14,
								'height' => 5,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'show_legend',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'columns',
										'value' => 20
									],
									[
										'type' => 0,
										'name' => 'rows',
										'value' => 5
									],
									[
										'type' => 0,
										'name' => 'dynamic',
										'value' => 0
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '600'
									],
									[
										'type' => 7,
										'name' => 'graphid',
										'value' => 600000
									]
								]
							],
							[
								'name' => 'Test copy Map from tree',
								'type' => 'map',
								'x' => 6,
								'y' => 10,
								'width' => 7,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '120'
									],
									[
										'type' => 0,
										'name' => 'source_type',
										'value' => 2
									],
									[
										'type' => 1,
										'name' => 'filter_widget_reference',
										'value' => 'STZDI'
									],
									[
										'type' => 1,
										'name' => 'reference',
										'value' => 'PVEYR'
									]
								]
							],
							[
								'name' => 'Test copy Map navigation tree',
								'type' => 'navtree',
								'x' => 0,
								'y' => 10,
								'width' => 6,
								'height' => 2,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 0,
										'name' => 'navtree.order.2',
										'value' => 2
									],
									[
										'type' => 0,
										'name' => 'rf_rate',
										'value' => '60'
									],
									[
										'type' => 0,
										'name' => 'show_unavailable',
										'value' => 1
									],
									[
										'type' => 1,
										'name' => 'navtree.name.1',
										'value' => 'Map with icon mapping'
									],
									[
										'type' => 1,
										'name' => 'navtree.name.2',
										'value' => 'Public map with image'
									],
									[
										'type' => 1,
										'name' => 'reference',
										'value' => 'STZDI'
									],
									[
										'type' => 8,
										'name' => 'navtree.sysmapid.1',
										'value' => 6
									],
									[
										'type' => 8,
										'name' => 'navtree.sysmapid.2',
										'value' => 10
									]
								]
							]
						]
					],
					[
						'name' => 'Test_page'
					]
				]
			],
			[
				'name' => 'Dashboard for Paste widgets',
				'display_period' => 30,
				'auto_start' => 1,
				'pages' => [
					[
						'name' => '',
						'widgets' => [
							[
								'name' => 'Test widget for replace',
								'type' => 'clock',
								'x' => 6,
								'y' => 0,
								'width' => 13,
								'height' => 8,
								'view_mode' => 0
							],
							[
								'name' => 'Test copy Map navigation tree',
								'type' => 'navtree',
								'x' => 0,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'view_mode' => 0,
								'fields' => [
									[
										'type' => 1,
										'name' => 'reference',
										'value' => 'FYKXG'
									]
								]
							]
						]
					]
				]
			]
		]);

		$this->assertArrayHasKey('dashboardids', $response);
		$dashboard_ids = CDataHelper::getIds('name');

		self::$dashboard_id = $dashboard_ids['Dashboard for Copying widgets'];
		self::$paste_dashboard_id = $dashboard_ids['Dashboard for Paste widgets'];
	}

	/**
	 * Function creates template dashboards and defines the corresponding dashboard IDs.
	 */
	public static function prepareTemplateDashboardsData() {
		CDataHelper::setSessionId(null);

		$response = CDataHelper::call('templatedashboard.create', [
			[
				'templateid' => self::UPDATE_TEMPLATEID,
				'name' => 'Dashboard with all widgets',
				'pages' => [
					[
						'name' => 'Page with widgets',
						'widgets' => [
							[
								'type' => 'clock',
								'name' => 'Clock widget',
								'width' => 4,
								'height' => 4
							],
							[
								'type' => 'graph',
								'name' => 'Graph (classic) widget',
								'x' => 4,
								'y' => 0,
								'width' => 8,
								'height' => 4,
								'fields' => [
									[
										'type' => 0,
										'name' => 'source_type',
										'value' => 1
									],
									[
										'type' => 4,
										'name' => 'itemid',
										'value' => 40041
									]
								]
							],
							[
								'type' => 'plaintext',
								'name' => 'Plain text widget',
								'x' => 12,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 4,
										'name' => 'itemids',
										'value' => 40041
									]
								]
							],
							[
								'type' => 'url',
								'name' => 'URL widget',
								'x' => 18,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'url',
										'value' => 'http://zabbix.com'
									]
								]
							],
							[
								'type' => 'graphprototype',
								'name' => 'Graph prototype widget',
								'x' => 0,
								'y' => 4,
								'width' => 12,
								'height' => 6,
								'fields' => [
									[
										'type' => 7,
										'name' => 'graphid',
										'value' => 700016
									]
								]
							]
						]
					],
					[
						'name' => 'Page for pasting widgets',
						'widgets' => []
					]
				]
			],
			[
				'templateid' => self::UPDATE_TEMPLATEID,
				'name' => 'Dashboard without widgets',
				'pages' => [[]]
			]
		]);

		self::$dashboardid_with_widgets = $response['dashboardids'][0];
		self::$empty_dashboardid = $response['dashboardids'][1];
		self::$templated_page_id = CDBHelper::getValue('SELECT dashboard_pageid FROM dashboard_page WHERE name='
				.zbx_dbstr(self::TEMPLATED_PAGE_NAME)
		);
	}

	/**
	 * Data provider for copying widgets.
	 */
	public static function getCopyWidgetsData() {
		return CDBHelper::getDataProvider('SELECT * FROM widget w'.
			' WHERE EXISTS ('.
				'SELECT NULL'.
				' FROM dashboard_page dp'.
				' WHERE w.dashboard_pageid=dp.dashboard_pageid'.
					' AND dp.dashboardid='.self::$dashboard_id.
			') ORDER BY w.widgetid DESC'
		);
	}

	/**
	 * @dataProvider getCopyWidgetsData
	 */
	public function testDashboardCopyWidgets_SameDashboard($data) {
		$this->copyWidgets($data);
	}

	/**
	 * @dataProvider getCopyWidgetsData
	 */
	public function testDashboardCopyWidgets_OtherDashboard($data) {
		$this->copyWidgets($data, true);
	}

	/**
	 * @dataProvider getCopyWidgetsData
	 */
	public function testDashboardCopyWidgets_ReplaceWidget($data) {
		$this->copyWidgets($data, true, true);
	}

	/**
	 * @dataProvider getCopyWidgetsData
	 */
	public function testDashboardCopyWidgets_NewPage($data) {
		$this->copyWidgets($data, false, false, true);
	}

	private function copyWidgets($data, $new_dashboard = false, $replace = false, $new_page = false, $templated = false) {
		$name = $data['name'];

		// Exclude Map navigation tree widget from replacing tests.
		if ($replace && $name === 'Test copy Map navigation tree') {
			return;
		}

		$replaces = self::$replaced_widget_name;

		// Write name for replacing widget next case.
		if ($replace) {
			self::$replaced_widget_name = $name;
		}

		// Use the appropriate dashboard and page in case of templated dashboard widgets.
		if ($templated) {
			$dashboard_id = self::$dashboardid_with_widgets;
			$new_dashboard_id = self::$empty_dashboardid;
			$new_page_name = self::TEMPLATED_PAGE_NAME;
			$new_page_id = self::$templated_page_id;
			$url = 'zabbix.php?action=template.dashboard.edit&dashboardid=';
		}
		else {
			$dashboard_id = self::$dashboard_id;
			$new_dashboard_id = self::$paste_dashboard_id;
			$new_page_name = self::NEW_PAGE_NAME;
			$new_page_id =  CDBHelper::getValue('SELECT dashboard_pageid FROM dashboard_page WHERE name='.
					zbx_dbstr(self::NEW_PAGE_NAME));
			$url = 'zabbix.php?action=dashboard.view&dashboardid=';
		}

		// Mapping for tags in problem widgets.
		$mapping = [
			'tag',
			[
				'name' => 'match',
				'class' => CSegmentedRadioElement::class
			],
			'value'
		];
		$this->page->login()->open($url.$dashboard_id);
		$dashboard = CDashboardElement::find()->one();

		// Get fields from widget form to compare them with new widget after copying.
		$fields = $dashboard->getWidget($name)->edit()->getFields();

		// Add tag fields mapping to form for problem widgets.
		if (stristr($name, 'Problem')) {
			$fields->set('', $fields->get('')->asMultifieldTable(['mapping' => $mapping]));
		}

		$original_form = $fields->asValues();
		$original_widget_size = $replace
			? self::$replaced_widget_size
			: CDBHelper::getRow('SELECT w.width, w.height'.
					' FROM widget w WHERE EXISTS ('.
						'SELECT NULL FROM dashboard_page dp'.
						' WHERE w.dashboard_pageid=dp.dashboard_pageid'.
							' AND dp.dashboardid='.$dashboard_id.
					')'.
					' AND w.name='.zbx_dbstr($name).' ORDER BY w.widgetid DESC'
			);

		// Close widget configuration overlay.
		COverlayDialogElement::find()->one()->close();
		$dashboard->copyWidget($name);

		// Open other dashboard for paste widgets.
		if ($new_dashboard) {
			$this->page->open($url.$new_dashboard_id);
			$dashboard = CDashboardElement::find()->one();
		}

		if ($new_page) {
			$this->query('xpath://div[@class="dashboard-navigation-tabs"]//span[text()="'.$new_page_name.'"]')
					->waitUntilClickable()->one()->click();
			$this->query('xpath://div[@class="selected-tab"]//span[text()="'.$new_page_name.'"]')
					->waitUntilVisible()->one();
		}

		$dashboard->edit();

		if ($replace) {
			$dashboard->replaceWidget($replaces);
		}
		else {
			$dashboard->pasteWidget();
		}

		// Wait until widget is pasted and loading spinner disappeared.
		sleep(1);
		$this->query('xpath://div[contains(@class, "is-loading")]')->waitUntilNotPresent();
		$copied_widget = $dashboard->getWidgets()->last();

		// For Other dashboard and Map from Navigation tree case - add map source, because it is not being copied by design.
		if (($new_dashboard || $new_page) && stristr($name, 'Map from tree')) {
			$copied_widget_form = $copied_widget->edit();
			$copied_widget_form->fill(['Filter' => 'Test copy Map navigation tree']);
			$copied_widget_form->submit();
		}

		$this->assertEquals($name, $copied_widget->getHeaderText());
		$copied_fields = $copied_widget->edit()->getFields();

		// Add tag fields mapping to form for newly copied problem widgets.
		if (stristr($name, 'Problem')) {
			$copied_fields->set('', $copied_fields->get('')->asMultifieldTable(['mapping' => $mapping]));
		}

		$copied_form = $copied_fields->asValues();
		$this->assertEquals($original_form, $copied_form);

		// Close overlay and save dashboard to get new widget size from DB.
		$copied_overlay = COverlayDialogElement::find()->one();
		$copied_overlay->close();

		if ($templated) {
			$this->query('button:Save changes')->one()->click();
		}
		else {
			$dashboard->save();
		}
		$this->page->waitUntilReady();

		// For templated dashboards the below SQL is executed faster than the corresponding record is added to DB.
		if ($templated) {
			$this->assertMessage(TEST_GOOD, 'Dashboard updated');
		}
		$copied_widget_size = CDBHelper::getRow('SELECT w.width, w.height'.
				' FROM widget w WHERE EXISTS ('.
					'SELECT NULL'.
					' FROM dashboard_page dp'.
					' WHERE w.dashboard_pageid='.($new_page ? $new_page_id : 'dp.dashboard_pageid').
						' AND dp.dashboardid='.($new_dashboard ? $new_dashboard_id : $dashboard_id).
				')'.
				' AND w.name='.zbx_dbstr($name).' ORDER BY w.widgetid DESC'
		);
		$this->assertEquals($original_widget_size, $copied_widget_size);
	}

	public static function getTemplateDashboardWidgetData() {
		return [
			[
				[
					'name' => 'Clock widget',
					'copy to' => 'same page'
				]
			],
			[
				[
					'name' => 'Graph (classic) widget',
					'copy to' => 'same page'
				]
			],
			[
				[
					'name' => 'URL widget',
					'copy to' => 'same page'
				]
			],
			[
				[
					'name' => 'Plain text widget',
					'copy to' => 'same page'
				]
			],
			[
				[
					'name' => 'URL widget',
					'copy to' => 'same page'
				]
			],
			[
				[
					'name' => 'Clock widget',
					'copy to' => 'another page'
				]
			],
			[
				[
					'name' => 'Graph (classic) widget',
					'copy to' => 'another page'
				]
			],
			[
				[
					'name' => 'URL widget',
					'copy to' => 'another page'
				]
			],
			[
				[
					'name' => 'Plain text widget',
					'copy to' => 'another page'
				]
			],
			[
				[
					'name' => 'URL widget',
					'copy to' => 'another page'
				]
			],
			[
				[
					'name' => 'Clock widget',
					'copy to' => 'another dashboard'
				]
			],
			[
				[
					'name' => 'Graph (classic) widget',
					'copy to' => 'another dashboard'
				]
			],
			[
				[
					'name' => 'URL widget',
					'copy to' => 'another dashboard'
				]
			],
			[
				[
					'name' => 'Plain text widget',
					'copy to' => 'another dashboard'
				]
			],
			[
				[
					'name' => 'URL widget',
					'copy to' => 'another dashboard'
				]
			],
			[
				[
					'name' => 'Clock widget',
					'copy to' => 'another template'
				]
			]
		];
	}

	/**
	 * Function that checks copy operation for template dashboard widgets to different locations.
	 *
	 * @dataProvider getTemplateDashboardWidgetData
	 *
	 * @backupOnce dashboard
	 *
	 * @onBeforeOnce prepareTemplateDashboardsData
	 */
	public function testDashboardCopyWidgets_CopyTemplateWidgets($data) {
		switch ($data['copy to']) {
			case 'same page':
				$this->copyWidgets($data, false, false, false, true);
				break;

			case 'another page':
				$this->copyWidgets($data, false, false, true, true);
				break;

			case 'another dashboard':
				$this->copyWidgets($data, true, false, false, true);
				break;

			case 'another template':
				$this->page->login()->open('zabbix.php?action=template.dashboard.edit&dashboardid='.self::$dashboardid_with_widgets);
				$dashboard = CDashboardElement::find()->one()->waitUntilVisible();
				$dashboard->copyWidget($data['name']);

				$this->page->open('zabbix.php?action=template.dashboard.edit&templateid=50002');
				$this->page->waitUntilReady();
				COverlayDialogElement::find()->one()->close();
				$this->query('id:dashboard-add')->one()->click();
				$this->assertFalse(CPopupMenuElement::find()->one()->getItem('Paste widget')->isEnabled());

				$this->closeDialogues();
				break;
		}
	}

	public static function getTemplateDashboardPageData() {
		return [
			[
				[
					'copy to' => 'same dashboard'
				]
			],
			[
				[
					'copy to' => 'another dashboard'
				]
			],
			[
				[
					'copy to' => 'another template'
				]
			]
		];
	}

	/**
	 * Function that checks copy operation for template dashboard pages to different locations.
	 *
	 * @dataProvider getTemplateDashboardPageData
	 *
	 * @onBeforeOnce prepareTemplateDashboardsData
	 */
	public function testDashboardCopyWidgets_CopyTemplateDashboardPage($data) {
		$this->page->login()->open('zabbix.php?action=template.dashboard.edit&dashboardid='.self::$dashboardid_with_widgets);
		$dashboard = CDashboardElement::find()->one()->waitUntilVisible();

		$dashboard->query('xpath://span[text()= "Page with widgets"]/../button')->one()->click();
		CPopupMenuElement::find()->one()->waitUntilVisible()->select('Copy');

		switch ($data['copy to']) {
			case 'same dashboard':
				$this->query('id:dashboard-add')->one()->click();
				CPopupMenuElement::find()->one()->waitUntilVisible()->select('Paste page');
				$dashboard->query('xpath:(//span[@title="Page with widgets"])[2]')->waitUntilVisible()->one();
				$this->assertEquals(2, $dashboard->query('xpath://span[@title="Page with widgets"]')->all()->count());
				break;

			case 'another dashboard':
				$this->page->open('zabbix.php?action=template.dashboard.edit&dashboardid='.self::$empty_dashboardid);
				$this->page->waitUntilReady();

				$this->query('id:dashboard-add')->one()->click();
				CPopupMenuElement::find()->one()->waitUntilVisible()->select('Paste page');
				$this->assertEquals(1, $dashboard->query('xpath://span[@title="Page with widgets"]')
						->waitUntilVisible()->all()->count()
				);
				break;

			case 'another template':
				$this->page->open('zabbix.php?action=template.dashboard.edit&templateid=50002');
				$this->page->waitUntilReady();
				COverlayDialogElement::find()->one()->close();
				$this->query('id:dashboard-add')->one()->click();
				$this->assertFalse(CPopupMenuElement::find()->one()->getItem('Paste page')->isEnabled());
				break;
		}

		$this->closeDialogues();
	}

	/**
	 * Function that closes all dialogs and alerts on a template dashboard before proceeding to the next test.
	 */
	private function closeDialogues() {
		$overlay = COverlayDialogElement::find()->one(false);
		if ($overlay->isValid()) {
			$overlay->close();
		}
		$this->query('link:Cancel')->one()->forceClick();

		if ($this->page->isAlertPresent()) {
			$this->page->acceptAlert();
		}
	}
}
