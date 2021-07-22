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
					'url' => 'zabbix.php?action=dashboard.view', // 0
					'name' => 'Monitoring Dashboard'
//					'regions' => [
//						'class:clock'
//					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view', // 1
					'user' => 'guest',
					'name' => 'Monitoring Dashboard'
//					'regions' => [
//						'class:clock'
//					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=problem.view', // 2
					'name' => 'Monitoring Problems'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=problem.view', // 3
					'user' => 'guest',
					'name' => 'Monitoring Problems'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=host.view', // 4
					'name' => 'Monitoring Hosts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=host.view', // 5
					'user' => 'guest',
					'name' => 'Monitoring Hosts'
				]
			],
			[
				[
					'url' => 'overview.php?type=0', // 6
					'name' => 'Monitoring Overview Trigger'
				]
			],
			[
				[
					'url' => 'overview.php?type=0', // 7
					'user' => 'guest',
					'name' => 'Monitoring Overview Trigger'
				]
			],
			[
				[
					'url' => 'overview.php?type=1', // 8
					'name' => 'Monitoring Overview Data'
				]
			],
			[
				[
					'url' => 'overview.php?type=1', // 9
					'user' => 'guest',
					'name' => 'Monitoring Overview Data'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=latest.view', // 10
					'name' => 'Monitoring Latest Data'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=latest.view', // 11
					'user' => 'guest',
					'name' => 'Monitoring Latest Data'
				]
			],
			[
				[
					'url' => 'sysmaps.php', // 12
					'name' => 'Monitoring Maps'
				]
			],
			[
				[
					'url' => 'sysmaps.php', // 13
					'user' => 'guest',
					'name' => 'Monitoring Maps'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=discovery.view', // 14
					'name' => 'Monitoring Discovery'
				]
			],
			[
				[
					'url' => 'srv_status.php', // 15
					'name' => 'Monitoring Services'
				]
			],
			[
				[
					'url' => 'srv_status.php', // 16
					'user' => 'guest',
					'name' => 'Monitoring Services'
				]
			],
			[
				[
					'url' => 'hostinventoriesoverview.php', // 17
					'name' => 'Inventory Overview'
				]
			],
			[
				[
					'url' => 'hostinventoriesoverview.php', // 18
					'user' => 'guest',
					'name' => 'Inventory Overview'
				]
			],
			[
				[
					'url' => 'hostinventories.php', // 19
					'name' => 'Inventory Hosts'
				]
			],
			[
				[
					'url' => 'hostinventories.php', // 20
					'user' => 'guest',
					'name' => 'Inventory Hosts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=report.status', // 21
					'name' => 'Reports System information'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=scheduledreport.list', // 22
					'name' => 'Reports Scheduled reports'
				]
			],
			[
				[
					'url' => 'report2.php', // 23
					'name' => 'Reports Availability report'
				]
			],
			[
				[
					'url' => 'report2.php', // 24
					'user' => 'guest',
					'name' => 'Reports Availability report'
				]
			],
			[
				[
					'url' => 'toptriggers.php', // 25
					'name' => 'Reports Triggers top 100'
				]
			],
			[
				[
					'url' => 'toptriggers.php', // 26
					'user' => 'guest',
					'name' => 'Reports Triggers top 100'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=auditlog.list', // 27
					'name' => 'Reports Audit'
				]
			],
			[
				[
					'url' => 'auditacts.php', // 28
					'name' => 'Reports Action log'
				]
			],
			[
				[
					'url' => 'report4.php', // 29
					'name' => 'Reports Notifications',
					'regions' => [
						'xpath://tbody'
					]
				]
			],
			[
				[
					'url' => 'hostgroups.php', // 30
					'name' => 'Configuration Host groups'
				]
			],
			[
				[
					'url' => 'templates.php', // 31
					'name' => 'Configuration Templates'
				]
			],
			[
				[
					'url' => 'hosts.php', // 32
					'name' => 'Configuration Hosts'
				]
			],
			[
				[
					'url' => 'maintenance.php', // 33
					'name' => 'Configuration Maintenance'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=0', // 34
					'name' => 'Configuration Actions Trigger actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=1', // 35
					'name' => 'Configuration Actions Discovery actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=2', // 36
					'name' => 'Configuration Actions Autoregistration actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=3', // 37
					'name' => 'Configuration Actions Internal actions'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=correlation.list', // 38
					'name' => 'Configuration Event correlation'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=discovery.list', // 39
					'name' => 'Configuration Discovery'
				]
			],
			[
				[
					'url' => 'services.php', // 40
					'name' => 'Configuration Services'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=gui.edit', // 41
					'name' => 'Administration General GUI'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=autoreg.edit', // 42
					'name' => 'Administration General Autoregistration'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=housekeeping.edit', // 43
					'name' => 'Administration General Housekeeping'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=image.list', // 44
					'name' => 'Administration General Images'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=iconmap.list', // 45
					'name' => 'Administration General Icon mapping'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=regex.list', // 46
					'name' => 'Administration General Regular expressions'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=macros.edit', // 47
					'name' => 'Administration General Macros'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=trigdisplay.edit', // 48
					'name' => 'Administration General Trigger displaying options'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=module.list', // 49
					'name' => 'Administration General Modules'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=token.list', // 50
					'name' => 'Administration General API tokens'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=miscconfig.edit', // 51
					'name' => 'Administration General Other'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=proxy.list', // 52
					'name' => 'Administration Proxies'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=authentication.edit', // 53
					'name' => 'Administration Authentication'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=usergroup.list', // 54
					'name' => 'Administration User groups'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=userrole.list', // 55
					'name' => 'Administration User roles'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=user.list', // 56
					'name' => 'Administration Users'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=mediatype.list', // 57
					'name' => 'Administration Media types'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=script.list', // 58
					'name' => 'Administration Scripts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.overview', // 59
					'name' => 'Administration Queue Queue overview'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.overview.proxy', // 60
					'name' => 'Administration Queue Queue overview by proxy'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.details', // 61
					'name' => 'Administration Queue Queue details'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=userprofile.edit', // 62
					'name' => 'User Settings Profile'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=user.token.list', // 63
					'name' => 'User Settings API tokens'
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
		$this->page->userLogin(CTestArrayHelper::get($data, 'user', 'Admin'),
				((CTestArrayHelper::get($data, 'user', 'Admin') === 'guest') ? '' : 'zabbix')
		);
		$this->page->open($data['url'])->waitUntilReady();
		$this->page->removeFocus();
		sleep(1);

		$regions = [];
		if (CTestArrayHelper::get($data, 'regions')) {
			foreach ($data['regions'] as $selector) {
				$regions[] = (is_array($selector)) ? $selector : $this->query($selector)->one();
			}
		}

		try {
			$this->assertScreenshotExcept(null, $regions, $data['name'].' '.(CTestArrayHelper::get($data, 'user', 'Admin')));
		} catch (Exception $ex) {
			$this->fail($ex->getMessage());
		}
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
