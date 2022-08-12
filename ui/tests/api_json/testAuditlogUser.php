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


require_once dirname(__FILE__).'/../include/CAPITest.php';

/**
 * @backup users, ids
 */
class testAuditlogUser extends CAPITest {

	protected static $resourceid;

	public function testAuditlogUser_Create() {
		$created = "{\"user.username\":[\"add\",\"Audit\"],\"user.passwd\":[\"add\",\"******\"],\"user.name\":[".
				"\"add\",\"Audit_name\"],\"user.surname\":[\"add\",\"Audit_surname\"],\"user.roleid\":[\"add".
				"\",\"3\"],\"user.usrgrps[90021]\":[\"add\"],\"user.usrgrps[90021].usrgrpid\":[\"add\",\"7\"],".
				"\"user.usrgrps[90021].id\":[\"add\",\"90021\"],\"user.medias[1]\":[\"add\"],\"user.medias[1].mediatypeid".
				"\":[\"add\",\"1\"],\"user.medias[1].sendto\":[\"add\",\"audit@audit.com\"],\"user.medias[1].mediaid".
				"\":[\"add\",\"1\"],\"user.userid\":[\"add\",\"90001\"]}";

		$create = $this->call('user.create', [
			[
				'username' => 'Audit',
				'passwd' => 'zabbixzabbix',
				'name' => 'Audit_name',
				'surname' => 'Audit_surname',
				'roleid' => '3',
				'usrgrps' => [
					[
						'usrgrpid' => '7'
					]
				],
				'medias' => [
					[
						'mediatypeid' => '1',
						'sendto' => [
							'audit@audit.com'
						],
						'active' => 0,
						'severity' => 63,
						'period' => '1-7,00:00-24:00'
					]
				]
			]
		]);

		self::$resourceid = $create['result']['userids'][0];
		$this->sendGetRequest('details', 0, $created);
	}

	public function testAuditUser_Login() {
		$login = "Audit";

		$this->authorize('Audit', 'zabbixzabbix');
		$this->sendGetRequest('username', 8, $login);
	}

	public function testAuditUser_Logout() {
		$logout = "Audit";

		$this->authorize('Audit', 'zabbixzabbix');
		$this->call('user.logout', []);
		$this->authorize('Admin', 'zabbix');
		$this->sendGetRequest('username', 4, $logout);
	}

	public function testAuditUser_FailedLogin() {
		$failed = "Audit";

		$this->authorize('Audit', 'incorrect_pas');
		$this->authorize('Admin', 'zabbix');
		$this->sendGetRequest('username', 9, $failed);
	}

	public function testAuditlogUser_Update() {
		$updated = "{\"user.usrgrps[90021]\":[\"delete\"],\"user.medias[1]\":[\"delete\"],\"user.usrgrps[90022]".
				"\":[\"add\"],\"user.medias[2]\":[\"add\"],\"user.username\":[\"update\",\"updated_Audit\",\"Audit\"],".
				"\"user.passwd\":[\"update\",\"******\",\"******\"],\"user.name\":[\"update\",\"Updated_Audit_name\",".
				"\"Audit_name\"],\"user.surname\":[\"update\",\"Updated_Audit_surname\",\"Audit_surname\"],".
				"\"user.usrgrps[90022].usrgrpid\":[\"add\",\"11\"],\"user.usrgrps[90022].id\":[\"add\",\"90022\"],".
				"\"user.medias[2].mediatypeid\":[\"add\",\"1\"],\"user.medias[2].sendto\":[\"add\",\"update_audit@audit.com".
				"\"],\"user.medias[2].mediaid\":[\"add\",\"2\"]}";

		$this->authorize('Admin', 'zabbix');
		$this->call('user.update', [
			[
				'userid' => self::$resourceid,
				'username' => 'updated_Audit',
				'passwd' => 'updatezabbix',
				'name' => 'Updated_Audit_name',
				'surname' => 'Updated_Audit_surname',
				'usrgrps' => [
					[
						'usrgrpid' => '11'
					]
				],
				'medias' => [
					[
						'mediatypeid' => '1',
						'sendto' => [
							'update_audit@audit.com'
						],
						'active' => 0,
						'severity' => 63,
						'period' => '1-7,00:00-24:00'
					]
				]
			]
		]);

		$this->sendGetRequest('details', 1, $updated);
	}

	public function testAuditlogUser_Delete() {
		$this->call('user.delete', [self::$resourceid]);
		$this->sendGetRequest('resourcename', 2, 'updated_Audit');
	}

	private function sendGetRequest($output, $action, $result) {
		$get = $this->call('auditlog.get', [
			'output' => [$output],
			'sortfield' => 'clock',
			'sortorder' => 'DESC',
			'filter' => [
				'resourceid' => self::$resourceid,
				'action' => $action
			]
		]);

		$this->assertEquals($result, $get['result'][0][$output]);
	}
}
