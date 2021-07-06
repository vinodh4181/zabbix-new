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
					'url' => 'zabbix.php?action=dashboard.view',
					'name' => 'Admin Dashboard view',
					'regions' => [
						'class:clock'
					]
				]
			],
			[
				[
					'url' => 'hosts.php',
					'name' => 'List of hosts',
				]
			],

			[
				[
					'url' => 'zabbix.php?action=userprofile.edit',
					'name' => 'User profile edit',
					'regions' => ['button:Change password']
				]
			]
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
					'name' => 'Start page',
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
					'regions' => [
						'class:clock'
					]
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
		$this->page->open('index.php')->waitUntilReady();
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
	public static function removeGuestFromDisabledGroup() {
		DBexecute('DELETE FROM users_groups WHERE userid=2 AND usrgrpid=9');
	}

	public function addGuestToDisabledGroup() {
		DBexecute('INSERT INTO users_groups (id, usrgrpid, userid) VALUES (150, 9, 2)');
	}
}
