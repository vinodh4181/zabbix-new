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

require_once dirname(__FILE__) . '/../include/CIntegrationTest.php';

/**
 * Test suite for alerting for services.
 *
 * @required-components server
 * @configurationDataProvider serverConfigurationProvider
 * @backup actions, config, functions, globalmacro
 * @backup group_prototype, host_discovery, host_inventory, hostmacro, host_rtdata, hosts, hosts_groups, hosts_templates
 * @backup host_tag, hstgrp, interface, item_condition, item_discovery, item_parameter, item_preproc, item_rtdata, items
 * @backup item_tag, lld_macro_path, lld_override, lld_override_condition, lld_override_opdiscover, lld_override_operation
 * @backup lld_override_opstatus, operations, opgroup, opmessage, opmessage_grp, optemplate
 */
class testInitialConfSync extends CIntegrationTest
{
	private $expected_initial = [
		[
			'config' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'autoreg' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'autoreghost' =>
			[
				'insert' => '0',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'hosts' =>
			[
				'insert' => '16',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'host_invent' =>
			[
				'insert' => '2',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'templates' =>
			[
				'insert' => '2',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'globmacros' =>
			[
				'insert' => '4',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'hostmacros' =>
			[
				'insert' => '4',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'interfaces' =>
			[
				'insert' => '10',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'items' =>
			[
				'insert' => '42',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'template_items' =>
			[
				'insert' => '0',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'prototype_items' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'item_discovery' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'triggers' =>
			[
				'insert' => '6',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'trigdeps' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'trigtags' =>
			[
				'insert' => '2',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'hosttags' =>
			[
				'insert' => '3',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'itemtags' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'functions' =>
			[
				'insert' => '6',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'expressions' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'actions' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'operations' =>
			[
				'insert' => '0',
				'update' => '1',
				'delete' => '0',
			],
		],
		[
			'conditions' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'corr' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'corr_cond' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'corr_op' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'hgroups' =>
			[
				'insert' => '17',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'itempproc' =>
			[
				'insert' => '10',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'itemscriptparam' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			'maintenance' =>
			[
				'insert' => '1',
				'update' => '0',
				'delete' => '0',
			],
		],
	];

	private $expected_update =
	[
		[
			"config" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"autoreg" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			'autoreghost' =>
			[
				'insert' => '0',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			"hosts" =>
			[
				"insert" =>
				"0",
				"update" =>
				"14",
				"delete" =>
				"0",
			]
		],
		[
			"host_invent" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"templates" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"globmacros" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"hostmacros" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"interfaces" =>
			[
				"insert" =>
				"0",
				"update" =>
				"7",
				"delete" =>
				"0",
			]
		],
		[
			"items" =>
			[
				"insert" =>
				"0",
				"update" =>
				"24",
				"delete" =>
				"0",
			]
		],
		[
			"template_items" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"prototype_items" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"item_discovery" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"triggers" =>
			[
				"insert" =>
				"0",
				"update" =>
				"4",
				"delete" =>
				"0",
			]
		],
		[
			"trigdeps" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"trigtags" =>
			[
				"insert" =>
				"2",
				"update" =>
				"0",
				"delete" =>
				"2",
			]
		],
		[
			"hosttags" =>
			[
				"insert" =>
				"2",
				"update" =>
				"0",
				"delete" =>
				"2",
			]
		],
		[
			"itemtags" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"functions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"expressions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"actions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"operations" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"conditions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"corr" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"corr_cond" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"corr_op" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"hgroups" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
		[
			"itempproc" =>
			[
				"insert" =>
				"0",
				"update" =>
				"2",
				"delete" =>
				"0",
			]
		],
		[
			"itemscriptparam" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"maintenance" =>
			[
				"insert" =>
				"0",
				"update" =>
				"1",
				"delete" =>
				"0",
			]
		],
	];

	private $expected_delete = [
		[
			"config" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"autoreg" =>
			[
				"insert" =>
				"1",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			'autoreghost' =>
			[
				'insert' => '0',
				'update' => '0',
				'delete' => '0',
			],
		],
		[
			"hosts" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"16",
			]
		],
		[
			"host_invent" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"templates" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"globmacros" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"hostmacros" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"interfaces" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"7",
			]
		],
		[
			"items" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"25",
			]
		],
		[
			"template_items" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"prototype_items" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"25",
			]
		],
		[
			"item_discovery" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"triggers" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"4",
			]
		],
		[
			"trigdeps" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"trigtags" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"2",
			]
		],
		[
			"hosttags" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"2",
			]
		],
		[
			"itemtags" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"functions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"4",
			]
		],
		[
			"expressions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"actions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"operations" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"0",
			]
		],
		[
			"conditions" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"corr" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"corr_cond" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"corr_op" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"hgroups" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"itempproc" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"2",
			]
		],
		[
			"itemscriptparam" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
		[
			"maintenance" =>
			[
				"insert" =>
				"0",
				"update" =>
				"0",
				"delete" =>
				"1",
			]
		],
	];

	private static $proxyid_active;
	private static $proxyid_passive;
	private static $actionid;
	private static $triggerid;
	private static $correlationid;
	private static $maintenanceid;
	private static $regexpid;
	private static $vaultmacroid;
	private static $secretmacroid;

	/**
	 * @inheritdoc
	 */
	public function prepareData()
	{
		return true;
	}

	/**
	 * Component configuration provider for server related tests.
	 *
	 * @return array
	 */
	public function serverConfigurationProvider()
	{
		return [
			self::COMPONENT_SERVER => [
				'LogFileSize' => 0,
				'DebugLevel' => 4,
				'Vault' => 'CyberArk',
				'VaultURL' => 'https://127.0.0.1:1858',
			]
		];
	}

	private function parseSyncResults()
	{
		$log = file_get_contents(self::getLogPath(self::COMPONENT_SERVER));
		var_dump($log);
		$data = explode("\n", $log);

		$sync_lines = preg_grep('/DCsync_configuration.*\([0-9]+\/[0-9]+\/[0-9]+\)\.$/', $data);

		$sync_lines1 = preg_replace(
			[
				"/^\s*[0-9]+:[0-9]+:[0-9]+\.[0-9]+ DCsync_configuration\(\) /",
				"/\s+/",
				"/:sql:[0-9]+\.[0-9]+sync:[0-9]+\.[0-9]+sec/",
				"/:sql:[0-9]+\.[0-9]+sec/",
			],
			"",
			$sync_lines
		);

		$sync_lines2 = preg_replace(
			[
				"/(\(\))|(\()/",
				"/\)\.|\./",
			],
			[
				":",
				""
			],
			$sync_lines1
		);

		$results = [];

		foreach ($sync_lines2 as $v) {
			$o = explode(":", $v);

			$subject = $o[0];
			$operations = explode("/", $o[1]);

			if (count($operations) < 3) {
				continue;
			}

			$pair = [
				$subject => [
					'insert' => $operations[0],
					'update' => $operations[1],
					'delete' => $operations[2],
				]
			];

			array_push($results, $pair);
		}

		return $results;
	}

	private function getStringPoolCount() {
		$log = file_get_contents(self::getLogPath(self::COMPONENT_SERVER));
		$data = explode("\n", $log);

		$stringpool_lines = preg_grep('/DCsync_configuration\(\)\s+strings/', $data);
		$stringpool_lines1 = preg_replace(
			[
				"/^[0-9]+:[0-9]+:[0-9]+\.[0-9]+\s*DCsync_configuration\(\) /",
				"/\s+/",
				'/\([0-9]+slots\)/'
			],
			"",
			$stringpool_lines
		);

		$stringpool_lines1 = explode(":", array_key_first($stringpool_lines1));

		if (is_null($stringpool_lines1) || count($stringpool_lines1) < 1) {
			throw new Exception('Failed to retrieve stringpool data from the log file');
		}

		return $stringpool_lines1[0];
	}

	private function purgeHostGroups()
	{
		$response = $this->call('hostgroup.get', [
			'output' => 'extend',
			'preservekeys' => true
		]);
		$this->assertArrayHasKey('result', $response);

		$filtered_groups = array_filter($response['result'], function ($obj) {
			return $obj['name'] != 'Discovered hosts';
		});

		$ids = array_keys($filtered_groups);
		if (empty($ids)) {
			return;
		}

		$response = $this->call('hostgroup.delete', $ids);
	}

	private function purgeExisting($method, $field_name)
	{
		$params = [
			'output' => $field_name,
			'preservekeys' => true
		];

		$response = $this->call($method . '.get', $params);
		$this->assertArrayHasKey('result', $response);

		$ids = array_keys($response['result']);

		if (empty($ids)) {
			return;
		}

		$response = $this->call($method . '.delete', $ids);
	}

	private function createActions()
	{
		$response = $this->call('trigger.get', [
			'output' => 'triggerids',
			'preservekeys' => true
		]);
		$this->assertArrayHasKey('result', $response);
		self::$triggerid = array_key_first($response['result']);

		$response = $this->call('action.create', [
			'esc_period' => '1h',
			'eventsource' => EVENT_SOURCE_TRIGGERS,
			'status' => 0,
			'filter' => [
				'conditions' => [
					[
						'conditiontype' => CONDITION_TYPE_TRIGGER,
						'operator' => CONDITION_OPERATOR_EQUAL,
						'value' => self::$triggerid
					]
				],
				'evaltype' => CONDITION_EVAL_TYPE_AND_OR
			],
			'name' => 'Trapper received 1 (problem) clone',
			'operations' => [
				[
					'esc_period' => 0,
					'esc_step_from' => 1,
					'esc_step_to' => 1,
					'operationtype' => OPERATION_TYPE_MESSAGE,
					'opmessage' => [
						'default_msg' => 1,
						'mediatypeid' => 0
					],
					'opmessage_grp' => [
						['usrgrpid' => 7]
					]
				]
			],
			'pause_suppressed' => 0,
			'recovery_operations' => [
				[
					'operationtype' => OPERATION_TYPE_MESSAGE,
					'opmessage' => [
						'default_msg' => 1,
						'mediatypeid' => 0
					],
					'opmessage_grp' => [
						['usrgrpid' => 7]
					]
				]
			]
		]);
		$this->assertArrayHasKey('actionids', $response['result']);
		$this->assertEquals(1, count($response['result']['actionids']));
		self::$actionid = $response['result']['actionids'][0];
	}

	private function updateAction()
	{
		$response = $this->call('action.update', [
			'esc_period' => '5m',
			'actionid' => self::$actionid,
			'filter' => [
				'conditions' => [
					[
						'conditiontype' => CONDITION_TYPE_TRIGGER,
						'operator' => CONDITION_OPERATOR_NOT_EQUAL,
						'value' => self::$triggerid
					]
				],
				'evaltype' => CONDITION_EVAL_TYPE_OR
			],
			'operations' => [
				[
					'esc_period' => 0,
					'esc_step_from' => 1,
					'esc_step_to' => 1,
					'operationtype' => OPERATION_TYPE_MESSAGE,
					'opmessage' => [
						'default_msg' => 0,
						'mediatypeid' => 0,
						'message' => '{SERVICE.NAME}|{SERVICE.TAGS}|{SERVICE.TAGSJSON}|{SERVICE.ROOTCAUSE}',
						'subject' => 'Problem'
					],
					'opmessage_grp' => [['usrgrpid' => 7]]
				]
			]
		]);

		$this->assertArrayHasKey('actionids', $response['result']);
		$this->assertArrayHasKey(0, $response['result']['actionids']);
	}

	private function createMaintenance()
	{
		$response = $this->call('host.get', [
			'output' => 'hostids',
			'preservekeys' => true
		]);
		$this->assertArrayHasKey('result', $response);
		$hostid = array_key_first($response['result']);

		$maint_start_tm = time();
		$maint_end_tm = $maint_start_tm + 60 * 2;

		$response = $this->call('maintenance.create', [
			'name' => 'Test maintenance',
			'hosts' => ['hostid' => $hostid],
			'active_since' => $maint_start_tm,
			'active_till' => $maint_end_tm,
			'tags_evaltype' => MAINTENANCE_TAG_EVAL_TYPE_AND_OR,
			'timeperiods' => [
				'timeperiod_type' => TIMEPERIOD_TYPE_ONETIME,
				'period' => 300,
				'start_date' => $maint_start_tm
			]
		]);
		$this->assertArrayHasKey('maintenanceids', $response['result']);
		$this->assertEquals(1, count($response['result']['maintenanceids']));
		self::$maintenanceid = $response['result']['maintenanceids'][0];
	}

	private function updateMaintenance()
	{
		$response = $this->call('maintenance.update', [
			'maintenanceid' => self::$maintenanceid,
			'active_since' => time(),
			'active_till' => time() + 86400,
		]);
		$this->assertArrayHasKey('maintenanceids', $response['result']);
		$this->assertEquals(1, count($response['result']['maintenanceids']));
	}

	private function createCorrelation()
	{
		$response = $this->call('correlation.create', [
			'name' => 'new corr',
			'filter' => [
				'evaltype' => 0,
				'conditions' => [[
					'type' => 1,
					'tag' => 'ok'
				]],
			],
			'operations' => [
				['type' => 0]
			]
		]);
		$this->assertArrayHasKey("correlationids", $response['result']);
		self::$correlationid = $response['result']['correlationids'][0];
	}

	private function updateCorrelation()
	{
		$response = $this->call('correlation.update', [
			'correlationid' => self::$correlationid,
			'name' => 'cr',
			'filter' => [
				'evaltype' => 0,
				'conditions' => [[
					'type' => 3,
					'oldtag' => 'x',
					'newtag' => 'y'
				]],
			],
			'operations' => [
				['type' => 1]
			]
		]);
		$this->assertArrayHasKey("correlationids", $response['result']);
	}

	private function createRegexp()
	{
		$response = $this->call('regexp.create', [
			'name' => 'global regexp test',
			'test_string' => '/boot',
			'expressions' => [
				[
					'expression' => '.*',
					'expression_type' => EXPRESSION_TYPE_FALSE,
					'case_sensitive' => 1
				]
			]
		]);
		$this->assertArrayHasKey("regexpids", $response['result']);
		self::$regexpid = $response['result']['regexpids'][0];
	}

	private function updateRegexp()
	{
		$response = $this->call('regexp.update', [
			'regexpid' => self::$regexpid,
			'test_string' => '/tmp',
			'expressions' => [
				[
					'expression' => '.*a',
					'expression_type' => EXPRESSION_TYPE_TRUE,
					'case_sensitive' => 1
				]
			]
		]);
		$this->assertArrayHasKey("regexpids", $response['result']);
	}

	private function createProxies()
	{
		$response = $this->call('proxy.create', [
			'host' => 'ProxyA',
			'status' => HOST_STATUS_PROXY_ACTIVE,
			'hosts' => []
		]);
		$this->assertArrayHasKey("proxyids", $response['result']);
		self::$proxyid_active = $response['result']['proxyids'][0];

		$response = $this->call('proxy.create', [
			'host' => 'ProxyP',
			'status' => HOST_STATUS_PROXY_PASSIVE,
			'hosts' => [],
			'interface' => [
				"ip" => "127.0.0.1",
				"dns" => "",
				"useip" => "1",
				"port" => "10099"
			]
		]);
		$this->assertArrayHasKey("proxyids", $response['result']);
		self::$proxyid_passive = $response['result']['proxyids'][0];
	}

	private function updateProxies()
	{
		$response = $this->call('proxy.update', [
			'proxyid' => self::$proxyid_active,
			'proxy_address' => "127.9.9.9"
		]);
		$this->assertArrayHasKey("proxyids", $response['result']);

		$response = $this->call('proxy.update', [
			'proxyid' => self::$proxyid_passive,
			'host' => "ProxyP1",
			'interface' => [
				"ip" => "127.1.30.2",
				"dns" => "",
				"useip" => "1",
				"port" => "10299"
			]
		]);
		$this->assertArrayHasKey("proxyids", $response['result']);
	}

	private function createGlobalMacros()
	{
		$response = $this->call('usermacro.createglobal', [
			'macro' => '{$GLOBDELAY}',
			'value' => '1'
		]);
		$this->assertArrayHasKey('result', $response);
		$this->assertArrayHasKey('globalmacroids', $response['result']);

		$response = $this->call('usermacro.createglobal', [
			'macro' => '{$SECRETMACRO}',
			'value' => '1234567890',
			'type' => 1
		]);
		$this->assertArrayHasKey('result', $response);
		$this->assertArrayHasKey('globalmacroids', $response['result']);
		self::$secretmacroid = $response['result']['globalmacroids'][0];

		$response = $this->call('usermacro.createglobal', [
			'macro' => '{$VAULTMACRO}',
			'value' => 'secret/zabbix:password',
			'type' => 2
		]);
		$this->assertArrayHasKey('result', $response);
		$this->assertArrayHasKey('globalmacroids', $response['result']);
		self::$vaultmacroid = $response['result']['globalmacroids'][0];
	}

	private function updateGlobalMacro()
	{
		$response = $this->call('usermacro.get', [
			'output' => 'extend',
			'globalmacro' => 'true'
		]);
		$this->assertArrayHasKey(0, $response['result']);
		$this->assertArrayHasKey('globalmacroid', $response['result'][0]);

		$globalmacroid = $response['result'][0]['globalmacroid'];

		$response = $this->call('usermacro.updateglobal', [
			'globalmacroid' => $globalmacroid,
			'macro' => '{$UU}',
			'value' => 'updated'
		]);
		$this->assertArrayHasKey('globalmacroids', $response['result']);

		$response = $this->call('usermacro.updateglobal', [
			'globalmacroid' => self::$secretmacroid,
			'value' => 'qwerasdfzxcv'
		]);
		$this->assertArrayHasKey('globalmacroids', $response['result']);

		$response = $this->call('usermacro.updateglobal', [
			'globalmacroid' => self::$vaultmacroid,
			'value' => 'secret/zabbix:ZABBIX123',
		]);
		$this->assertArrayHasKey('globalmacroids', $response['result']);
	}

	private function importTemplate($filename, $update, $params)
	{
		$xml = file_get_contents('integration/data/' . $filename);

		$response = $this->call('templategroup.get', [
			'output' => 'extend'
		]);
		var_dump($response);

		$response = $this->call('configuration.import', [
			'format' => 'xml',
			'source' => $xml,
			'rules' => [
				'templates' => $params,
				'template_groups' => [
					'createMissing' => true,
					'updateExisting' => false,
				]
			]
		]);
	}

	/**
	 */
	public function testInitialConfSync_Insert()
	{
		$this->createGlobalMacros();
		$this->purgeExisting('host', 'hostids');
		$this->purgeExisting('template', 'templateids');
		$this->purgeExisting('regexp', 'extend');
		$this->purgeHostGroups();

		$this->createProxies();
		$this->createCorrelation();
		$this->createRegexp();

		$this->importTemplate('confsync_tmpl.xml', false, [
			'createMissing' => true,
			'updateExisting' => false
		]);

		self::stopComponent(self::COMPONENT_SERVER);
		self::clearLog(self::COMPONENT_SERVER);

		$xml = file_get_contents('integration/data/confsync_hosts.xml');

		$response = $this->call('proxy.get', [
			'output' => 'extend'
		]);
		var_dump($response);

		$response = $this->call('configuration.import', [
			'format' => 'xml',
			'source' => $xml,
			'rules' => [
				'hosts' => [
					'createMissing' => true,
					'updateExisting' => false
				],
				'items' => [
					'createMissing' => true,
					'updateExisting' => false
				],
				'host_groups' => [
					'createMissing' => true,
					'updateExisting' => false
				],
				'discoveryRules' => [
					'createMissing' => true,
					'updateExisting' => false,
					'deleteMissing' => true
				],
				'httptests' => [
					'createMissing' => true,
					'updateExisting' => false,
					'deleteMissing' => true
				],
				'triggers' => [
					'createMissing' => true,
					'updateExisting' => false,
					'deleteMissing' => true
				],
				'templateLinkage' => [
					'createMissing' => true
				],
			]
		]);

		$this->createActions();
		$this->createMaintenance();


		self::startComponent(self::COMPONENT_SERVER);

		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, "End of DCsync_configuration()", true, 30, 1);

		$got = $this->parseSyncResults();
		var_dump($got);
		var_dump(self::getLogPath(self::COMPONENT_SERVER));
		$this->assertEquals($this->expected_initial, $got);

		$stringpool_old = $this->getStringPoolCount();

		self::stopComponent(self::COMPONENT_SERVER);
		self::clearLog(self::COMPONENT_SERVER);
		self::startComponent(self::COMPONENT_SERVER);

		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, "End of DCsync_configuration()", true, 30, 1);

		$stringpool_new = $this->getStringPoolCount();
		$this->assertEquals($stringpool_old, $stringpool_new);

		return true;
	}

	public function testInitialConfSync_Update()
	{
		$this->updateProxies();
		$this->updateCorrelation();
		$this->updateMaintenance();
		$this->updateRegexp();

		$this->importTemplate('confsync_tmpl_updated.xml', true, [
			'createMissing' => false,
			'updateExisting' => true
		]);

		$response = $this->call('proxy.get', [
			'output' => 'extend'
		]);
		var_dump($response);


		$xml = file_get_contents('integration/data/confsync_hosts_updated.xml');

		$response = $this->call('configuration.import', [
			'format' => 'xml',
			'source' => $xml,
			'rules' => [
				'hosts' => [
					'createMissing' => false,
					'updateExisting' => true
				],
				'items' => [
					'createMissing' => false,
					'updateExisting' => true
				],
				'host_groups' => [
					'createMissing' => false,
					'updateExisting' => true
				],
				'discoveryRules' => [
					'createMissing' => false,
					'updateExisting' => true,
					'deleteMissing' => false
				],
				'httptests' => [
					'createMissing' => false,
					'updateExisting' => true,
					'deleteMissing' => false
				],
				'triggers' => [
					'createMissing' => false,
					'updateExisting' => true,
					'deleteMissing' => false
				],
				'templateLinkage' => [
					'createMissing' => false
				],
			]
		]);

		$this->updateGlobalMacro();

		$this->clearLog(self::COMPONENT_SERVER);
		$this->updateAction();
		$this->reloadConfigurationCache(self::COMPONENT_SERVER);
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, "End of DCsync_configuration()", true, 30, 1);

		$got = $this->parseSyncResults();
		$this->assertEquals($this->expected_update, $got);

		return true;
	}

	public function testInitialConfSync_Delete()
	{
		$this->purgeExisting('maintenance', 'maintenanceids');
		$this->purgeExisting('host', 'hostids');
		$this->purgeExisting('proxy', 'proxyids');
		$this->purgeExisting('template', 'templateids');
		$this->purgeExisting('correlation', 'correlationids');
		$this->purgeExisting('regexp', 'extend');
		$this->purgeHostGroups();

		$this->clearLog(self::COMPONENT_SERVER);
		$this->reloadConfigurationCache(self::COMPONENT_SERVER);
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, "End of DCsync_configuration()", true, 30, 1);

		$got = $this->parseSyncResults();
		$this->assertEquals($this->expected_delete, $got);

		self::stopComponent(self::COMPONENT_SERVER);
		self::clearLog(self::COMPONENT_SERVER);
		self::startComponent(self::COMPONENT_SERVER);

		return true;
	}
}
