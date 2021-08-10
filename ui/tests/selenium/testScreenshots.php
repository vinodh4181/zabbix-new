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

require_once dirname(__FILE__).'/../include/CWebTest.php';

class testScreenshots extends CWebTest {

	public function getPageData() {
		return [
			[
				[
					'url' => 'index.php', // 0
					'user' => 'guest',
					'name' => 'Index'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 1
					'name' => 'Monitoring Dashboard',
					'regions' => [
						'class:clock'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 2
					'user' => 'guest',
					'name' => 'Monitoring Dashboard',
					'regions' => [
						'class:clock'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=problem.view', // 3
					'name' => 'Monitoring Problems'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=problem.view', // 4
					'user' => 'guest',
					'name' => 'Monitoring Problems'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=host.view', // 5
					'name' => 'Monitoring Hosts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=host.view', // 6
					'user' => 'guest',
					'name' => 'Monitoring Hosts'
				]
			],
			[
				[
					'url' => 'overview.php?type=0', // 7
					'name' => 'Monitoring Overview Trigger'
				]
			],
			[
				[
					'url' => 'overview.php?type=0', // 8
					'user' => 'guest',
					'name' => 'Monitoring Overview Trigger'
				]
			],
			[
				[
					'url' => 'overview.php?type=1', // 9
					'name' => 'Monitoring Overview Data'
				]
			],
			[
				[
					'url' => 'overview.php?type=1', // 10
					'user' => 'guest',
					'name' => 'Monitoring Overview Data'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=latest.view', // 11
					'name' => 'Monitoring Latest Data'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=latest.view', // 12
					'user' => 'guest',
					'name' => 'Monitoring Latest Data'
				]
			],
			[
				[
					'url' => 'sysmaps.php', // 13
					'name' => 'Monitoring Maps'
				]
			],
			[
				[
					'url' => 'sysmaps.php', // 14
					'user' => 'guest',
					'name' => 'Monitoring Maps'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=discovery.view', // 15
					'name' => 'Monitoring Discovery'
				]
			],
			[
				[
					'url' => 'srv_status.php', // 16
					'name' => 'Monitoring Services'
				]
			],
			[
				[
					'url' => 'srv_status.php', // 17
					'user' => 'guest',
					'name' => 'Monitoring Services'
				]
			],
			[
				[
					'url' => 'hostinventoriesoverview.php', // 18
					'name' => 'Inventory Overview'
				]
			],
			[
				[
					'url' => 'hostinventoriesoverview.php', // 19
					'user' => 'guest',
					'name' => 'Inventory Overview'
				]
			],
			[
				[
					'url' => 'hostinventories.php', // 20
					'name' => 'Inventory Hosts'
				]
			],
			[
				[
					'url' => 'hostinventories.php', // 21
					'user' => 'guest',
					'name' => 'Inventory Hosts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=report.status', // 22
					'name' => 'Reports System information'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=scheduledreport.list', // 23
					'name' => 'Reports Scheduled reports'
				]
			],
			[
				[
					'url' => 'report2.php', // 24
					'name' => 'Reports Availability report'
				]
			],
			[
				[
					'url' => 'report2.php', // 25
					'user' => 'guest',
					'name' => 'Reports Availability report'
				]
			],
			[
				[
					'url' => 'toptriggers.php', // 26
					'name' => 'Reports Triggers top 100'
				]
			],
			[
				[
					'url' => 'toptriggers.php', // 27
					'user' => 'guest',
					'name' => 'Reports Triggers top 100'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=auditlog.list', // 28
					'name' => 'Reports Audit'
				]
			],
			[
				[
					'url' => 'auditacts.php', // 29
					'name' => 'Reports Action log'
				]
			],
			[
				[
					'url' => 'report4.php', // 30
					'name' => 'Reports Notifications',
				]
			],
			[
				[
					'url' => 'hostgroups.php', // 31
					'name' => 'Configuration Host groups'
				]
			],
			[
				[
					'url' => 'templates.php', // 32
					'name' => 'Configuration Templates'
				]
			],
			[
				[
					'url' => 'hosts.php', // 33
					'name' => 'Configuration Hosts'
				]
			],
			[
				[
					'url' => 'maintenance.php', // 34
					'name' => 'Configuration Maintenance'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=0', // 35
					'name' => 'Configuration Actions Trigger actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=1', // 36
					'name' => 'Configuration Actions Discovery actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=2', // 37
					'name' => 'Configuration Actions Autoregistration actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=3', // 38
					'name' => 'Configuration Actions Internal actions'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=correlation.list', // 39
					'name' => 'Configuration Event correlation'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=discovery.list', // 40
					'name' => 'Configuration Discovery'
				]
			],
			[
				[
					'url' => 'services.php', // 41
					'name' => 'Configuration Services'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=gui.edit', // 42
					'name' => 'Administration General GUI'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=autoreg.edit', // 43
					'name' => 'Administration General Autoregistration'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=housekeeping.edit', // 44
					'name' => 'Administration General Housekeeping'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=image.list', // 45
					'name' => 'Administration General Images'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=iconmap.list', // 46
					'name' => 'Administration General Icon mapping'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=regex.list', // 47
					'name' => 'Administration General Regular expressions'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=macros.edit', // 48
					'name' => 'Administration General Macros'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=trigdisplay.edit', // 49
					'name' => 'Administration General Trigger displaying options'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=module.list', // 50
					'name' => 'Administration General Modules'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=token.list', // 51
					'name' => 'Administration General API tokens'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=miscconfig.edit', // 52
					'name' => 'Administration General Other'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=proxy.list', // 53
					'name' => 'Administration Proxies'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=authentication.edit', // 54
					'name' => 'Administration Authentication'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=usergroup.list', // 55
					'name' => 'Administration User groups'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=userrole.list', // 56
					'name' => 'Administration User roles'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=user.list', // 57
					'name' => 'Administration Users'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=mediatype.list', // 58
					'name' => 'Administration Media types'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=script.list', // 59
					'name' => 'Administration Scripts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.overview', // 60
					'name' => 'Administration Queue Queue overview'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.overview.proxy', // 61
					'name' => 'Administration Queue Queue overview by proxy'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.details', // 62
					'name' => 'Administration Queue Queue details'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=userprofile.edit', // 63
					'name' => 'User Settings Profile'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=user.token.list', // 64
					'name' => 'User Settings API tokens'
				]
			],
			[
				[ // Go deeper...
					'url' => 'zabbix.php?action=dashboard.view', // 65
					'name' => 'Monitoring Dashboard - Edit widget - System information',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[1]',
						'xpath:(//button[@class="btn-widget-edit"])[1]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 66
					'name' => 'Monitoring Dashboard - System information - Actions',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[1]',
						'xpath:(//button[@class="btn-widget-action"])[1]'
					]
				]
			],
			[ 
				[
					'url' => 'zabbix.php?action=dashboard.view', // 67
					'name' => 'Monitoring Dashboard - Edit widget - Host availability',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[2]',
						'xpath:(//button[@class="btn-widget-edit"])[2]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 68
					'name' => 'Monitoring Dashboard - Host availability - Actions',
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[2]',
						'xpath:(//button[@class="btn-widget-action"])[2]'
					]
				]
			],
			[ 
				[
					'url' => 'zabbix.php?action=dashboard.view', // 69
					'name' => 'Monitoring Dashboard - Edit widget - Clock',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[3]',
						'xpath:(//button[@class="btn-widget-edit"])[3]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 70
					'name' => 'Monitoring Dashboard - Clock - Actions',
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[3]',
						'xpath:(//button[@class="btn-widget-action"])[3]'
					]
				]
			],
			[ 
				[
					'url' => 'zabbix.php?action=dashboard.view', // 71
					'name' => 'Monitoring Dashboard - Edit widget - Problems by severity',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[4]',
						'xpath:(//button[@class="btn-widget-edit"])[4]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 72
					'name' => 'Monitoring Dashboard - Problems by severity - Actions',
					'regions' => [
						[
							'x' => 1230,
							'y' => 100,
							'width' => 170,
							'height' => 115
						],
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[4]',
						'xpath:(//button[@class="btn-widget-action"])[4]'
					]
				]
			],
			[ 
				[
					'url' => 'zabbix.php?action=dashboard.view', // 73
					'name' => 'Monitoring Dashboard - Edit widget - Problems',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[5]',
						'xpath:(//button[@class="btn-widget-edit"])[5]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 74
					'name' => 'Monitoring Dashboard - Problems - Actions',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[5]',
						'xpath:(//button[@class="btn-widget-action"])[5]'
					]
				]
			],
			[ 
				[
					'url' => 'zabbix.php?action=dashboard.view', // 75
					'name' => 'Monitoring Dashboard - Edit widget - Favourite maps',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[6]',
						'xpath:(//button[@class="btn-widget-edit"])[6]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 76
					'name' => 'Monitoring Dashboard - Favourite maps - Actions',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[6]',
						'xpath:(//button[@class="btn-widget-action"])[6]'
					]
				]
			],
			[ 
				[
					'url' => 'zabbix.php?action=dashboard.view', // 77
					'name' => 'Monitoring Dashboard - Edit widget - Favourite graphs',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[7]',
						'xpath:(//button[@class="btn-widget-edit"])[7]'
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 78
					'name' => 'Monitoring Dashboard - Favourite graphs - Actions',
					'regions' => [
						'class:clock'
					],
					'click_query' => [
						'xpath:(//div[@class="dashboard-grid-widget-head"])[7]',
						'xpath:(//button[@class="btn-widget-action"])[7]'
					]
				]
			],
			
			[
				[
					'url' => 'hosts.php', // ??
					'name' => 'Configuration Hosts - Host Groups select',
					'click_query' => [
						'xpath:(//* [@class="multiselect-control"]// button[not(@disabled)])[1]'
					]
				]
			],
			[
				[
					'url' => 'hosts.php', // ??
					'name' => 'Configuration Hosts - Templates select',
					'click_query' => [
						'xpath:(//* [@class="multiselect-control"]// button[not(@disabled)])[2]'
					]
				]
			]
		];
	}

	/**
	 * Test for covering with screenshots.
	 *
	 * @dataProvider getPageData
	 * @onBeforeOnce removeGuestFromDisabledGroup
	 * @onAfterOnce addGuestToDisabledGroup
	 */
	public function testScreenshots_ComparePageWithScreenshot($data) {
		$user = CTestArrayHelper::get($data, 'user', 'Admin');
		$this->page->userLogin($user, (($user === 'guest') ? '' : 'zabbix'));
		$this->page->open($data['url'])->waitUntilReady();
		$this->page->removeFocus();

		$regions = [];
		if (array_key_exists('regions', $data)) {
			foreach ($data['regions'] as $selector) {
				$regions[] = (is_array($selector)) ? $selector : $this->query($selector)->waitUntilPresent()->one();
			}
		}
		
		if (array_key_exists('click_query', $data)) {
			foreach ($data['click_query'] as $query) {
				$this->query($query)->one()->waitUntilClickable()->click();
			}
		}
		
			$this->assertScreenshotExcept(null, $regions, $data['name'].' '.$user);
	}
	
	/**
	 * Guest user needs to be out of "Disabled" group to have access to frontend.
	 */
	public function removeGuestFromDisabledGroup() {
		DBexecute('DELETE FROM users_groups WHERE userid=2 AND usrgrpid=9');
	}

	public static function addGuestToDisabledGroup() {
		DBexecute('INSERT INTO users_groups (id, usrgrpid, userid) VALUES (150, 9, 2)');
	}
}
