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

require_once dirname(__FILE__).'/../include/CIntegrationTest.php';

/**
 * Test suite for High availability
 *
 * @backup ha_node
 */
class testHighAvailability extends CIntegrationTest {

	const STANDALONE_NAME = '<standalone server>';
	const NODE1_NAME = 'node1';
	const NODE2_NAME = 'node2';

	/**
	 * @required-components server, server_ha1
	 * @inheritdoc
	 */
	public function prepareData() {
		$socketDir = $this->getConfigurationValue(self::COMPONENT_SERVER_HANODE1, 'SocketDir');

		if (file_exists($socketDir) === false) {
			mkdir($socketDir);
		}

		return true;
	}

	/**
	 * Component configuration provider for 2 nodes (Active + standby)
	 *
	 * @return array
	 */
	public function serverConfigurationProvider_ha() {
		return [
			self::COMPONENT_SERVER => [
				'HANodeName' => self::NODE1_NAME
			],
			self::COMPONENT_SERVER_HANODE1 => [
				'HANodeName' => self::NODE2_NAME,
				'NodeAddress' => 'localhost:'.self::getConfigurationValue(self::COMPONENT_SERVER_HANODE1, 'ListenPort')
			]
		];
	}

	/**
	 * Launching Zabbix server in stand-alone mode
	 *
	 * @required-components server
	 */
	public function testHighAvailability_checkStandaloneModeStartup() {
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, "standalone node started", true, 3, 3);

		return true;
	}

	/**
	 * Launching High availability cluster with 2 nodes (Active + standby)
	 *
	 * @required-components server, server_ha1
	 * @configurationDataProvider serverConfigurationProvider_ha
	 */
	public function testHighAvailability_checkHaStartup() {
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, '"'.self::NODE1_NAME.'" node started in "active" mode', true, 3, 3);
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER_HANODE1, '"'.self::NODE2_NAME.'" node started in "standby" mode', true, 3, 3);

		return true;
	}

	/**
	 * Stopping the active node (Standby node should take over)
	 * Starting stopped node (when another node is active and when there are no active nodes)
	 * Stopping a stand-by node
	 *
	 * @required-components server, server_ha1
	 * @configurationDataProvider serverConfigurationProvider_ha
	 */
	public function testHighAvailability_checkModeSwitching() {
		var_dump("stopping server");
		$this->stopComponent(self::COMPONENT_SERVER);
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER_HANODE1, '"'.self::NODE2_NAME.'" node switched to "active" mode', true, 5, 15);

		var_dump("starting server");
		$this->startComponent(self::COMPONENT_SERVER, "HA manager started");
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, '"'.self::NODE1_NAME.'" node started in "standby" mode', true, 5, 15);

		$this->stopComponent(self::COMPONENT_SERVER_HANODE1);
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, '"'.self::NODE1_NAME.'" node switched to "active" mode', true, 5, 15);

		return true;
	}

	private function verifyNodesStatus($expected_nodes) {
		$this->executeRuntimeControlCommand(self::COMPONENT_SERVER, 'ha_status');

		foreach ($expected_nodes as $node) {
			$re = $node["nodename"].".*".$node["expected_status"];
			$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, $re, true, 20, 3, true);
		}
	}

	/**
	 * Retrieving ha cluster info via ha_status runtime command
	 *
	 * @required-components server, server_ha1
	 * @configurationDataProvider serverConfigurationProvider_ha
	 */
	public function testHighAvailability_haStatus() {
		$expected_nodes = [
			[
				"nodename" => self::NODE1_NAME,
				"expected_status" => "active"
			],
			[
				"nodename" => self::NODE2_NAME,
				"expected_status" => "standby"
			]
		];

		$this->verifyNodesStatus($expected_nodes);
	}

	/**
	 * Remove node
	 *
	 * @required-components server, server_ha1
	 * @configurationDataProvider serverConfigurationProvider_ha
	 */
	public function testHighAvailability_removeNode() {
		$this->executeRuntimeControlCommand(self::COMPONENT_SERVER, 'ha_remove_node=node1');
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, "removed node", true, 3, 5);

		$response = $this->call('hanode.get', [
			'output' => 'extend',
			'filter' => [
				'name' => self::STANDALONE_NAME
			]
		]);
		$this->assertEmpty($response['result']);
	}

	/**
	 * Updating the failover delay via ha_set_failover_delay runtime command
	 *
	 * @required-components server, server_ha1
	 * @configurationDataProvider serverConfigurationProvider_ha
	 */
	public function testHighAvailability_failover() {
		$this->executeRuntimeControlCommand(self::COMPONENT_SERVER, 'ha_set_failover_delay=10s');

		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, 'HA failover delay set to 10s', true, 20, 3);

		$ha_mgr_pid = shell_exec("ps -eo pid,cmd | grep -E 'server_ha1: ha manager' | grep -v grep | grep -Eo '\b[0-9]+\b'");
		posix_kill(intval($ha_mgr_pid), 9);

		$ha_srv_pid = file_get_contents(self::getPidPath(self::COMPONENT_SERVER_HANODE1));
		posix_kill(intval($ha_srv_pid), 9);

		sleep(10);

		$response = $this->call('hanode.get', [
			'output' => 'extend',
			'filter' => [
				'name' => self::NODE2_NAME,
				'status' => ZBX_NODE_STATUS_UNAVAILABLE
			]
		]);
		$this->assertCount(1, $response['result']);
	}

}
