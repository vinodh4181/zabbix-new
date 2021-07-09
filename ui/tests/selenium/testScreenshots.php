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
					'url' => 'zabbix.php?action=dashboard.view', //0
					'name' => 'Admin Monitoring Dashboard'
//					'regions' => [
//						'class:clock'
//					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=problem.view', //1
					'name' => 'Admin Monitoring Problems'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=host.view', //2
					'name' => 'Admin Monitoring Hosts'
				]
			],
			[
				[
					'url' => 'overview.php?type=0', //3
					'name' => 'Admin Monitoring Overview Trigger'
				]
			],
			[
				[
					'url' => 'overview.php?type=1', //4
					'name' => 'Admin Monitoring Overview Data'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=latest.view', //5
					'name' => 'Admin Monitoring Latest Data'
				]
			],
			[
				[
					'url' => 'sysmaps.php', //6
					'name' => 'Admin Monitoring Maps'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=discovery.view', //7
					'name' => 'Admin Monitoring Discovery'
				]
			],
			[
				[
					'url' => 'srv_status.php', //8
					'name' => 'Admin Monitoring Services'
				]
			],
			[
				[
					'url' => 'hostinventoriesoverview.php', //9
					'name' => 'Admin Inventory Overview'
				]
			],
			[
				[
					'url' => 'hostinventories.php', //10
					'name' => 'Admin Inventory Hosts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=report.status', //11
					'name' => 'Admin Reports System information'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=scheduledreport.list', //12
					'name' => 'Admin Reports Scheduled reports'
				]
			],
			[
				[
					'url' => 'report2.php', //13
					'name' => 'Admin Reports Availability report'
				]
			],
			[
				[
					'url' => 'toptriggers.php', //14
					'name' => 'Admin Reports Triggers top 100'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=auditlog.list', //15
					'name' => 'Admin Reports Audit'
				]
			],
			[
				[
					'url' => 'auditacts.php', //16
					'name' => 'Admin Reports Action log'
				]
			],
			[
				[
					'url' => 'report4.php', //17
					'name' => 'Admin Reports Notifications',
					'regions' => [
						'xpath://tbody'
					]
				]
			],
			[
				[
					'url' => 'hostgroups.php', //18
					'name' => 'Admin Configuration Host groups'
				]
			],
			[
				[
					'url' => 'templates.php', //19
					'name' => 'Admin Configuration Templates'
				]
			],
			[
				[
					'url' => 'hosts.php', //20
					'name' => 'Admin Configuration Hosts'
				]
			],
			[
				[
					'url' => 'maintenance.php', //21
					'name' => 'Admin Configuration Maintenance'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=0', //22
					'name' => 'Admin Configuration Actions Trigger actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=1', //23
					'name' => 'Admin Configuration Actions Discovery actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=2', //24
					'name' => 'Admin Configuration Actions Autoregistration actions'
				]
			],
			[
				[
					'url' => 'actionconf.php?eventsource=3', //25
					'name' => 'Admin Configuration Actions Internal actions'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=correlation.list', //26
					'name' => 'Admin Configuration Event correlation'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=discovery.list', //27
					'name' => 'Admin Configuration Discovery'
				]
			],
			[
				[
					'url' => 'services.php', //28
					'name' => 'Admin Configuration Services'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=gui.edit', //29
					'name' => 'Admin Administration General GUI'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=autoreg.edit', //30
					'name' => 'Admin Administration General Autoregistration'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=housekeeping.edit', //31
					'name' => 'Admin Administration General Housekeeping'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=image.list', //32
					'name' => 'Admin Administration General Images'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=iconmap.list', //33
					'name' => 'Admin Administration General Icon mapping'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=regex.list', //34
					'name' => 'Admin Administration General Regular expressions'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=macros.edit', //35
					'name' => 'Admin Administration General Macros'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=trigdisplay.edit', //36
					'name' => 'Admin Administration General Trigger displaying options'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=module.list', //37
					'name' => 'Admin Administration General Modules'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=token.list', //38
					'name' => 'Admin Administration General API tokens'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=miscconfig.edit', //39
					'name' => 'Admin Administration General Other'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=proxy.list', //40
					'name' => 'Admin Administration Proxies'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=authentication.edit', //41
					'name' => 'Admin Administration Authentication'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=usergroup.list', //42
					'name' => 'Admin Administration User groups'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=userrole.list', //43
					'name' => 'Admin Administration User roles'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=user.list', //44
					'name' => 'Admin Administration Users'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=mediatype.list', //45
					'name' => 'Admin Administration Media types'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=script.list', //46
					'name' => 'Admin Administration Scripts'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.overview', //47
					'name' => 'Admin Administration Queue Queue overview'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.overview.proxy', //48
					'name' => 'Admin Administration Queue Queue overview by proxy'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=queue.details', //49
					'name' => 'Admin Administration Queue Queue details'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=userprofile.edit', //50
					'name' => 'User Settings Profile'
				]
			],
			[
				[
					'url' => 'zabbix.php?action=user.token.list', //51
					'name' => 'User Settings API tokens'
				]
			],
		];
	}

	/**
	 * Test for covering with screenshots.
	 *
	 * @dataProvider getPageData
	 */
	public function testScreenshots_ComparePageWithScreenshot($data) {
		$this->page->login()->open($data['url'])->waitUntilReady();
		$this->page->removeFocus();
		sleep(1);

		$regions = [];
		if (CTestArrayHelper::get($data, 'regions')) {
			foreach ($data['regions'] as $selector) {
				if (is_array($selector)) {
					$regions[] = $selector;
				}
				else {
					$regions[] = $this->query($selector)->one();
				}
			}
		}

		try {
			$this->assertScreenshotExcept(null, $regions, $data['name']);
		} catch (Exception $ex) {
			$this->fail($ex->getMessage());
		}
	}

	public function getNotAuthPageData() {
		return [
			[
				[
					'url' => 'index.php',
					'name' => 'Auth page for Guest',
					'regions' => [
//						'css:a[href$="com/"]',
						[
							'x' => 650,
							'y' => 850,
							'width' => 75,
							'height' => 25
						]
					]
				]
			],
			[
				[
					'url' => 'zabbix.php?action=dashboard.view',
					'name' => 'Dashboard view',
//					'regions' => [
//						'class:clock'
//					]
				]
			]
		];
	}

	/**
	 * Test for covering with screenshots.
	 *
	 * @dataProvider getNotAuthPageData
	 * @onBeforeOnce removeGuestFromDisabledGroup
	 * @onAfterOnce addGuestToDisabledGroup
	 */
	public function testScreenshots_CompareNotAuthPageWithScreenshot($data) {
		$this->page->userLogin('guest', '');
		$this->page->open($data['url'])->waitUntilReady();
		$this->page->removeFocus();
		sleep(1);

		$regions = [];
		if (CTestArrayHelper::get($data, 'regions')) {
			foreach ($data['regions'] as $selector) {
				if (is_array($selector)) {
					$regions[] = $selector;
				}
				else {
					$regions[] = $this->query($selector)->one();
				}
			}

		}

		try {
			$this->assertScreenshotExcept(null, $regions, $data['name']);
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
