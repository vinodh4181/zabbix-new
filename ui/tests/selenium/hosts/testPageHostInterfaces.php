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

require_once dirname(__FILE__).'/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../../include/helpers/CDataHelper.php';

/**
 * @backup hosts
 * @onBefore prepareInterfacesData
 */
class testPageHostInterfaces extends CWebTest {

	const RED = 'rgba(214, 78, 78, 1)';
	const GREEN = 'rgba(52, 175, 103, 1)';
	const ORANGE = 'rgba(241, 165, 11, 1)';
	const GREY = 'rgba(235, 235, 235, 1)';


	public static function prepareInterfacesData() {
		$interfaces = [
			[
				'type' => 1,
				'main' => 1,
				'useip' => 0,
				'ip' => '127.1.1.1',
				'dns' => '1available.zabbix.com',
				'port' => '10050',
				'available' => 1
			],
			[
				'type' => 1,
				'main' => 0,
				'useip' => 0,
				'ip' => '127.1.1.2',
				'dns' => '2available.zabbix.com',
				'port' => '10051',
				'available' => 1
			],
			[
				'type' => 2,
				'main' => 1,
				'useip' => 1,
				'ip' => '127.0.0.98',
				'dns' => 'snmpv3zabbix.com',
				'port' => '163',
				'details' => [
					'version' => '3',
					'bulk' => '1',
					'securityname' => 'zabbix',
					'max_repetitions' => 10
				],
				'available' => 1
			],
			[
				'type' => 2,
				'main' => 0,
				'useip' => 0,
				'ip' => '',
				'dns' => 'snmpv3auth.com',
				'port' => '163',
				'details' => [
					'version' => '3',
					'bulk' => '1',
					'securitylevel' => 2,
					'authprotocol' => 2,
					'privprotocol' => 4,
					'max_repetitions' => 10
				],
				'available' => 1
			],
			[
				'type' => 2,
				'main' => 0,
				'useip' => 1,
				'ip' => '127.0.0.99',
				'dns' => 'snmpv2zabbix.com',
				'port' => '162',
				'details' => [
					'version' => '2',
					'bulk' => '1',
					'community' => '{$SNMP_COMMUNITY}',
					'max_repetitions' => 10
				],
				'available' => 2
			],
			[
				'type' => 3,
				'main' => 1,
				'useip' => 0,
				'ip' => '127.0.0.1',
				'dns' => '1unavail.IPMI.zabbix.com',
				'port' => '623',
				'available' => 2,
				'error' => '1 Error IPMI'
			],
			[
				'type' => 3,
				'main' => 0,
				'useip' => 0,
				'ip' => '127.0.0.1',
				'dns' => '2unavail.IPMI.zabbix.com',
				'port' => '624',
				'available' => 2,
				'error' => '2 Error IPMI'
			]
		];

		$groups = [
			[
				'groupid' => 4
			]
		];

		CDataHelper::createHosts([
			[
				'host' => 'Host with Orange interface',
				'name' => 'Host with Orange interface',
				'description' => 'API Created Host with Orange interface for Host availability test',
				'interfaces' => $interfaces,
				'groups' => $groups,
				'status' => HOST_STATUS_MONITORED
			]
		]);
	}

	public function getCheckInterfacesData() {
		return [
			[
				[
					'host' => 'Not available host',
					'interfaces' => [
						'ZBX' => [
							'color' => self::RED,
							'rows' => [
								[
									'Interface' => 'zabbixzabbixzabbix.com:10050',
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => 'ERROR Agent'
								]
							]
						],
						'SNMP' => [
							'color' => self::RED,
							'rows' => [
								[
									'Interface' => "zabbixzabbixzabbix.com:10050\nSNMPv2, Community: {\$SNMP_COMMUNITY}",
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => 'ERROR SNMP'
								]
							]
						],
						'IPMI' => [
							'color' => self::RED,
							'rows' => [
								[
									'Interface' => 'zabbixzabbixzabbix.com:10050',
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => 'ERROR IPMI'
								]
							]
						],
						'JMX' => [
							'color' => self::RED,
							'rows' => [
								[
									'Interface' => 'zabbixzabbixzabbix.com:10050',
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => 'ERROR JMX'
								]
							]
						]
					]
				]
			],
			[
				[
					'host' => 'Host with Orange interface',
					'interfaces' => [
						'ZBX' => [
							'color' => self::GREEN,
							'rows' => [
								[
									'Interface' => '1available.zabbix.com:10050',
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								],
								[
									'Interface' => '2available.zabbix.com:10051',
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								]
							]
						],
						'SNMP' => [
							'color' => self::ORANGE,
							'rows' => [
								[
									'Interface' => "127.0.0.98:163\nSNMPv3, Context name:",
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								],
								[
									'Interface' => "127.0.0.99:162\nSNMPv2, Community: {\$SNMP_COMMUNITY}",
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => ''
								],
								[
									'Interface' => "snmpv3auth.com:163\nSNMPv3, Context name: , (priv: AES192C, auth: SHA224)",
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								]
							]
						],
						'IPMI' => [
							'color' => self::RED,
							'rows' => [
								[
									'Interface' => '1unavail.IPMI.zabbix.com:623',
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => '1 Error IPMI'
								],
								[
									'Interface' => '2unavail.IPMI.zabbix.com:624',
									'Status' => [
										'text' => 'Not available',
										'color' => self::RED
									],
									'Error' => '2 Error IPMI'
								]
							]
						]
					]
				]
			],
			[
				[
					'host' => 'Template inheritance test host',
					'interfaces' => [
						'ZBX' => [
							'color' => self::GREY,
							'rows' => [
								[
									'Interface' => '127.0.0.1:10051',
									'Status' => [
										'text' => 'Unknown',
										'color' => self::GREY
									],
									'Error' => ''
								],
								[
									'Interface' => '127.0.0.2:10052',
									'Status' => [
										'text' => 'Unknown',
										'color' => self::GREY
									],
									'Error' => ''
								]
							]
						],
						'SNMP' => [
							'color' => self::GREY,
							'rows' => [
								[
									'Interface' => "127.0.0.3:10053\nSNMPv2, Community: {\$SNMP_COMMUNITY}",
									'Status' => [
										'text' => 'Unknown',
										'color' => self::GREY
									],
									'Error' => ''
								]
							]
						],
						'IPMI' => [
							'color' => self::GREY,
							'rows' => [
								[
									'Interface' => '127.0.0.4:10054',
									'Status' => [
										'text' => 'Unknown',
										'color' => self::GREY
									],
									'Error' => ''
								]
							]
						],
						'JMX' => [
							'color' => self::GREY,
							'rows' => [
								[
									'Interface' => '127.0.0.5:10055',
									'Status' => [
										'text' => 'Unknown',
										'color' => self::GREY
									],
									'Error' => ''
								]
							]
						]
					]
				]
			],
			[
				[
					'host' => 'Available host',
					'interfaces' => [
						'ZBX' => [
							'color' => self::GREEN,
							'rows' => [
								[
									'Interface' => '127.0.0.1:10050',
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								]
							]
						],
						'SNMP' => [
							'color' => self::GREEN,
							'rows' => [
								[
									'Interface' => "zabbixzabbixzabbix.com:10050\nSNMPv2, Community: {\$SNMP_COMMUNITY}",
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								]
							]
						],
						'IPMI' => [
							'color' => self::GREEN,
							'rows' => [
								[
									'Interface' => 'zabbixzabbixzabbix.com:10050',
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								]
							]
						],
						'JMX' => [
							'color' => self::GREEN,
							'rows' => [
								[
									'Interface' => 'zabbixzabbixzabbix.com:10050',
									'Status' => [
										'text' => 'Available',
										'color' => self::GREEN
									],
									'Error' => ''
								]
							]
						]
					]
				]
			]
		];
	}

	/**
	 * Test displaying host interfaces on Monitoring->Hosts page.
	 *
	 * @dataProvider getCheckInterfacesData
	 */
	public function testPageHostInterfaces_MonitoringHosts($data) {
		$this->checkInterfaces($data, 'zabbix.php?action=host.view', 'host_view');
	}

	/**
	 * Test displaying host interfaces on Configuration->Hosts page.
	 *
	 * @dataProvider getCheckInterfacesData
	 */
	public function testPageHostInterfaces_ConfigurationHosts($data) {
		$this->checkInterfaces($data, self::HOST_LIST_PAGE, 'hosts');
	}

	/**
	 * Test displaying host interfaces on Discovery rules page.
	 *
	 * @dataProvider getCheckInterfacesData
	 */
	public function testPageHostInterfaces_DiscoveryPage($data) {
		$id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host ='.zbx_dbstr($data['host']));
		$link = 'host_discovery.php?filter_set=1&filter_hostids%5B0%5D='.$id.'&context=host';
		$this->checkInterfaces($data, $link, $selector = null, true);
	}

	/**
	 * Function for checking interfaces.
	 *
	 * @param array     $data          data from data provider
	 * @param string    $link          checked page link
	 * @param string    $selector      table selector on page
	 * @param boolean   $navigation    is it upper navigation block or not
	 */
	private function checkInterfaces($data, $link, $selector = null, $navigation = false) {
		if ($navigation) {
			$this->page->login()->open($link)->waitUntilReady();
			$availability = $this->query('xpath://div[@class="status-container"]')->waitUntilPresent()->one();
		}
		else {
			$this->page->login()->open($link)->waitUntilReady();
			$table = $this->query('xpath://form[@name='.zbx_dbstr($selector).']/table[@class="list-table"]')
					->waitUntilReady()->asTable()->one();
			$availability = $table->findRow('Name', $data['host'])->getColumn('Availability');
		}

		$host_interfaces = [];
		foreach ($availability->query('xpath:.//span[@data-hintbox="1"]')->all() as $interface) {
			$interface_name = $interface->getText();
			// Write interfaces names into array.
			$host_interfaces[] = $interface_name;
			// Check interface color in availability column.
			$this->assertEquals($data['interfaces'][$interface_name]['color'], $interface->getCSSValue('background-color'));
			// Open interface popup.
			$interface->click();
			$overlay = $this->query('xpath://div[@class="overlay-dialogue"]')->asOverlayDialog()->waitUntilPresent()->one();
			$interface_table = $overlay->query('xpath:.//table[@class="list-table"]')->asTable()->one();
			// Check table headers in popup.
			$this->assertSame(['Interface', 'Status', 'Error'], $interface_table->getHeadersText());

			// Check every interface row.
			foreach ($interface_table->getRows() as $i => $row) {
				$interface_details  = $data['interfaces'][$interface_name]['rows'][$i];
				$this->assertEquals($interface_details['Interface'], $row->getColumn('Interface')->getText());
				$this->assertEquals($interface_details['Status']['text'], $row->getColumn('Status')->getText());
				$this->assertEquals($interface_details['Status']['color'], $row->getColumn('Status')
						->query('xpath:.//span[contains(@class, "status")]')->one()->getCSSValue('background-color'));
				$this->assertEquals($interface_details['Error'], $row->getColumn('Error')->getText());
			}

			$overlay->close();
			$overlay->waitUntilNotPresent();
		}
		// Assert interface names in Availability column.
		$this->assertEquals(array_keys($data['interfaces']), $host_interfaces);
	}
}
