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


require_once dirname(__FILE__).'/../include/CWebTest.php';
require_once dirname(__FILE__).'/behaviors/CMessageBehavior.php';
require_once dirname(__FILE__).'/traits/TableTrait.php';

/**
 * @backup hosts, profiles
 *
 * @onBefore prepareTriggersData
 */
class testTriggerDependencies extends CWebTest {

	use TableTrait;

	private static $templateids;
	private static $template_triggerids;
	private static $druleids;
	private static $trigger_protids;
	private static $hostids;
	private static $host_triggerids;
	private static $host_druleids;
	private static $host_trigger_protids;

	private static $trigger_update_name = 'Host trigger update';
	private static $trigger_prot_update = 'Trigger prototype update{#KEY}';
	private static $triger_template = 'Trigger update';
	private static $trigger_template_prot = 'Trigger prototype update{#KEY}';

	/**
	 * Attach Behaviors to the test.
	 *
	 * @return array
	 */
	public function getBehaviors() {
		return ['class' => CMessageBehavior::class];
	}

	public function prepareTriggersData() {
		$templates = CDataHelper::call('template.create', [
			[
				'host' => 'Template with everything',
				'groups' => [
					'groupid' => 1
				]
			],
			[
				'host' => 'Template that linked to host',
				'groups' => [
					'groupid' => 1
				]
			],
			[
				'host' => 'Template with linked template',
				'groups' => [
					'groupid' => 1
				]
			],
			[
				'host' => 'Template that linked to template',
				'groups' => [
					'groupid' => 1
				]
			]
		]);
		$this->assertArrayHasKey('templateids', $templates);
		self::$templateids = CDataHelper::getIds('host');

		$response = CDataHelper::call('template.update', [
			[
				'templateid' => self::$templateids['Template with linked template'],
				'templates' => [
					[
						'templateid' => self::$templateids['Template that linked to template']
					]
				]
			]
		]);
		$this->assertArrayHasKey('templateids', $response);

		$template_items = CDataHelper::call('item.create', [
			[
				'name' => 'template item for everything',
				'key_' => 'everything',
				'hostid' => self::$templateids['Template with everything'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'template item for linking',
				'key_' => 'everything_2',
				'hostid' => self::$templateids['Template that linked to host'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'template item for template',
				'key_' => 'linked_temp',
				'hostid' => self::$templateids['Template that linked to template'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $template_items);

		$template_triggers = CDataHelper::call('trigger.create', [
			[
				'description' => 'Trigger update',
				'expression' => 'last(/Template with everything/everything)=0'
			],
			[
				'description' => 'trigger simple',
				'expression' => 'last(/Template with everything/everything)=0'
			],
			[
				'description' => 'trigger simple_2',
				'expression' => 'last(/Template with everything/everything)=0'
			],
			[
				'description' => 'trigger linked',
				'expression' => 'last(/Template that linked to host/everything_2)=0'
			],
			[
				'description' => 'trigger linked update',
				'expression' => 'last(/Template that linked to host/everything_2)=0'
			]
			,
			[
				'description' => 'trigger template linked',
				'expression' => 'last(/Template that linked to template/linked_temp)=0'
			],
			[
				'description' => 'trigger template linked update',
				'expression' => 'last(/Template that linked to template/linked_temp)=0'
			]
		]);
		$this->assertArrayHasKey('triggerids', $template_triggers);
		self::$template_triggerids = CDataHelper::getIds('description');

		$drule = CDataHelper::call('discoveryrule.create', [
			[
				'name' => 'Drule for everything',
				'key_' => 'everything_drule',
				'hostid' => self::$templateids['Template with everything'],
				'type' => 2,
				'delay' => 0
			],
			[
				'name' => 'Drule for linking',
				'key_' => 'linked_drule',
				'hostid' => self::$templateids['Template that linked to host'],
				'type' => 2,
				'delay' => 0
			],
			[
				'name' => 'Drule for template',
				'key_' => 'template_drule',
				'hostid' => self::$templateids['Template that linked to template'],
				'type' => 2,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $drule);
		self::$druleids = CDataHelper::getIds('name');

		$item_prot = CDataHelper::call('itemprototype.create', [
			[
				'name' => 'Item prot with everything',
				'key_' => 'everything_prot_[{#KEY}]',
				'hostid' => self::$templateids['Template with everything'],
				'ruleid' => self::$druleids['Drule for everything'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'Item prot for linking',
				'key_' => 'linking_prot_[{#KEY}]',
				'hostid' => self::$templateids['Template that linked to host'],
				'ruleid' => self::$druleids['Drule for linking'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'Item prot for template',
				'key_' => 'template_prot_[{#KEY}]',
				'hostid' => self::$templateids['Template that linked to template'],
				'ruleid' => self::$druleids['Drule for template'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $item_prot);

		$trigger_prot = CDataHelper::call('triggerprototype.create', [
			[
				'description' => 'Trigger prototype update{#KEY}',
				'expression' => 'last(/Template with everything/everything_prot_[{#KEY}])=0'
			],
			[
				'description' => 'trigger prototype simple{#KEY}',
				'expression' => 'last(/Template with everything/everything_prot_[{#KEY}])=0'
			],
			[
				'description' => 'trigger prototype linked{#KEY}',
				'expression' => 'last(/Template that linked to host/linking_prot_[{#KEY}])=0'
			],
			[
				'description' => 'trigger prototype linked update{#KEY}',
				'expression' => 'last(/Template that linked to host/linking_prot_[{#KEY}])=0'
			],
			[
				'description' => 'trigger prototype template{#KEY}',
				'expression' => 'last(/Template that linked to template/template_prot_[{#KEY}])=0'
			],
			[
				'description' => 'trigger prototype template update{#KEY}',
				'expression' => 'last(/Template that linked to template/template_prot_[{#KEY}])=0'
			]
		]);
		$this->assertArrayHasKey('triggerids', $trigger_prot);
		self::$trigger_protids = CDataHelper::getIds('description');

		$hosts = CDataHelper::call('host.create', [
			[
				'host' => 'Host with linked template',
				'templates' => [
					'templateid' => self::$templateids['Template that linked to host']
				],
				'groups' => [
					['groupid' => 4]
				]
			],
			[
				'host' => 'Host with everything',
				'groups' => [
					['groupid' => 4]
				]
			]
		]);
		$this->assertArrayHasKey('hostids', $hosts);
		self::$hostids = CDataHelper::getIds('host');

		$host_items = CDataHelper::call('item.create', [
			[
				'name' => 'Host item 1',
				'key_' => 'host_item_1',
				'hostid' => self::$hostids['Host with everything'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'Host item 2',
				'key_' => 'host_item_2',
				'hostid' => self::$hostids['Host with linked template'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $host_items);

		$host_triggers = CDataHelper::call('trigger.create', [
			[
				'description' => 'Host trigger update',
				'expression' => 'last(/Host with everything/host_item_1)=0'
			],
			[
				'description' => 'Host trigger everything',
				'expression' => 'last(/Host with everything/host_item_1)=0'
			],
			[
				'description' => 'Host trigger everything 2',
				'expression' => 'last(/Host with everything/host_item_1)=0'
			],
			[
				'description' => 'Host trigger 2',
				'expression' => 'last(/Host with linked template/host_item_2)=0'
			]
		]);
		$this->assertArrayHasKey('triggerids', $host_triggers);
		self::$host_triggerids = CDataHelper::getIds('description');

		$host_drule = CDataHelper::call('discoveryrule.create', [
			[
				'name' => 'Drule for host everything',
				'key_' => 'host_everything_drule',
				'hostid' => self::$hostids['Host with everything'],
				'type' => 2,
				'delay' => 0
			],
			[
				'name' => 'Drule for host with linking',
				'key_' => 'host_linked_drule',
				'hostid' => self::$hostids['Host with linked template'],
				'type' => 2,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $host_drule);
		self::$host_druleids = CDataHelper::getIds('name');

		$host_item_prot = CDataHelper::call('itemprototype.create', [
			[
				'name' => 'Host Item prot with everything',
				'key_' => 'host_everything_prot_[{#KEY}]',
				'hostid' => self::$hostids['Host with everything'],
				'ruleid' => self::$host_druleids['Drule for host everything'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'Host Item prot for linking',
				'key_' => 'host_linking_prot_[{#KEY}]',
				'hostid' => self::$hostids['Host with linked template'],
				'ruleid' => self::$host_druleids['Drule for host with linking'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $host_item_prot);

		$host_trigger_prot = CDataHelper::call('triggerprototype.create', [
			[
				'description' => 'Host trigger prot update{#KEY}',
				'expression' => 'last(/Host with everything/host_everything_prot_[{#KEY}])=0'
			],
			[
				'description' => 'Host trigger prot simple{#KEY}',
				'expression' => 'last(/Host with everything/host_everything_prot_[{#KEY}])=0'
			],
			[
				'description' => 'Host trigger prot simple_2{#KEY}',
				'expression' => 'last(/Host with everything/host_everything_prot_[{#KEY}])=0'
			],
			[
				'description' => 'Host trigger prot for linked{#KEY}',
				'expression' => 'last(/Host with linked template/host_linking_prot_[{#KEY}])=0'
			],
			[
				'description' => 'Host trigger prot for linked update{#KEY}',
				'expression' => 'last(/Host with linked template/host_linking_prot_[{#KEY}])=0'
			]
		]);
		$this->assertArrayHasKey('triggerids', $host_trigger_prot);
		self::$host_trigger_protids = CDataHelper::getIds('description');
	}

	public static function getTriggerCreateData() {
		return [
			// #0 simple dependence on another trigger on same host.
			[
				[
					'name' => 'Simple trigger',
					'dependencie' => ['Host with everything' =>
						[
							'Host trigger everything'
						]
					]
				]
			],
			// #1 dependence on 2 triggers from same host.
			[
				[
					'name' => 'Two trigger dependence',
					'dependencie' => [
						'Host with everything' => [
							'Host trigger everything',
							'Host trigger everything 2'
						]
					]
				]
			],
			// #2 dependence on trigger from another host.
			[
				[
					'name' => 'Triggers from another hosts',
					'dependencie' => [
						'Host with linked template' => [
							'Host trigger 2'
						]
					]
				]
			],
			// #3 dependence on trigger from another and same host.
			[
				[
					'name' => 'Two triggers from different',
					'dependencie' => [
						'Host with linked template' => [
							'Host trigger 2'
						],
						'Host with everything' => [
							'Host trigger everything'
						]
					]
				]
			],
			// #4 dependence on linked trigger.
			[
				[
					'name' => 'Depends on linked trigger',
					'dependencie' => [
						'Host with linked template' => [
							'trigger linked'
						]
					]
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTriggerCreateData
	 */
	public function testTriggerDependencies_TriggerCreate($data) {
		$this->page->login()->open('triggers.php?filter_set=1&filter_hostids%5B0%5D='.self::$hostids['Host with everything'].
			'&context=host')->waitUntilReady();
		$this->query('button:Create trigger')->one()->click();
		$this->page->waitUntilReady();
		$this->triggerCreateUpdate($data, 'last(/Host with everything/host_item_1)=0');
		$this->assertMessage(TEST_GOOD, 'Trigger added');
		$this->checkTrigger($data);
	}

	public static function getTriggerUpdateData() {
		return [
			// #0 simple dependence on another trigger on same host.
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Host trigger update',
					'dependencie' => [
						'Host with everything' => [
							'Host trigger update'
						]
					],
					'error_message' => 'Trigger "Host trigger update" cannot depend on the trigger '.
							'"Host trigger update", because a circular linkage '.
							'("Host trigger update" -> "Host trigger update") would occur.'
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTriggerUpdateData
	 * @dataProvider getTriggerCreateData
	 */
	public function testTriggerDependencies_TriggerUpdate($data) {
		$this->page->login()->open('triggers.php?form=update&triggerid='.self::$host_triggerids['Host trigger update'].
				'&context=host')->waitUntilReady();
		$this->triggerCreateUpdate($data, null, true);

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$this->assertMessage(TEST_BAD, 'Cannot update trigger', $data['error_message']);
		}
		else {
			$this->assertMessage(TEST_GOOD, 'Trigger updated');
			$this->checkTrigger($data, null, true);
			self::$trigger_update_name = $data['name'].'_update';
		}
	}

	public static function getTriggerPrototypeCreateData() {
		return [
			// #0 dependence on one trigger prototype.
			[
				[
					'name' => 'Depends on one trigger_prot',
					'prototype_dependencie' => [
						'Host trigger prot simple{#KEY}'
					]
				]
			],
			// #1 dependence on two trigger prototype.
			[
				[
					'name' => 'Depends on two trigger_prot',
					'prototype_dependencie' => [
						'Host trigger prot simple{#KEY}',
						'Host trigger prot simple_2{#KEY}'
					]
				]
			],
			// #2 dependence on trigger and trigger prototype.
			[
				[
					'name' => 'Depends on trigger and trigger_prot',
					'dependencie' => [
						'Host with everything' => [
							'Host trigger everything'
						]
					],
					'prototype_dependencie' => [
						'Host trigger prot simple{#KEY}'
					]
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTriggerCreateData
	 * @dataProvider getTriggerPrototypeCreateData
	 */
	public function testTriggerDependencies_TriggerPrototypeCreate($data) {
		$this->page->login()->open('trigger_prototypes.php?parent_discoveryid='.
				self::$host_druleids['Drule for host everything'].'&context=host')->waitUntilReady();
		$this->query('button:Create trigger prototype')->one()->click();
		$this->page->waitUntilReady();
		$this->triggerCreateUpdate($data, 'last(/Host with everything/host_everything_prot_[{#KEY}])=0');
		$this->assertMessage(TEST_GOOD, 'Trigger prototype added');
		$this->checkTrigger($data, 'Host with everything');
	}

	public static function getTriggerPrototypeUpdateData() {
		return [
			// #0 dependence on one trigger prototype.
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Host trigger prot update{#KEY}',
					'prototype_dependencie' => [
						'Host trigger prot update{#KEY}'
					],
					'error_message' => 'Trigger prototype "Host trigger prot update{#KEY}" cannot depend on the '.
							'trigger prototype "Host trigger prot update{#KEY}", because a circular linkage ("Host '.
							'trigger prot update{#KEY}" -> "Host trigger prot update{#KEY}") would occur.'
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTriggerPrototypeUpdateData
	 * @dataProvider getTriggerCreateData
	 * @dataProvider getTriggerPrototypeCreateData
	 */
	public function testTriggerDependencies_TriggerPrototypeUpdate($data) {
		$this->page->login()->open('trigger_prototypes.php?form=update&parent_discoveryid='.
				self::$host_druleids['Drule for host everything'].'&triggerid='.
				self::$host_trigger_protids['Host trigger prot update{#KEY}'].'&context=host')->waitUntilReady();
		$this->triggerCreateUpdate($data);

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$this->assertMessage(TEST_BAD, 'Cannot update trigger prototype', $data['error_message']);
		}
		else {
			$this->assertMessage(TEST_GOOD, 'Trigger prototype updated');
			$this->checkTrigger($data, 'Host with everything', true);
			self::$trigger_prot_update = $data['name'].'_update';
		}
	}

	public static function getTemplateTriggerCreateData()
	{
		return [
			// #0 simple dependence on another trigger on same template.
			[
				[
					'name' => 'Simple template trigger',
					'dependencie' => [
						'Template with everything' => [
							'trigger simple'
						]
					]
				]
			],
			// #1 dependence on 2 triggers from same template.
			[
				[
					'name' => 'Two trigger dependence',
					'dependencie' => [
						'Template with everything' => [
							'trigger simple',
							'trigger simple_2'
						]
					]
				]
			],
			// #2 dependence on trigger from another template.
			[
				[
					'name' => 'Triggers from another template',
					'dependencie' => [
						'Template that linked to host' => [
							'trigger linked'
						]
					]
				]
			],
			// #3 dependence on trigger from another and same template.
			[
				[
					'name' => 'Two triggers from different',
					'dependencie' => [
						'Template that linked to host' => [
							'trigger linked'
						],
						'Template with everything' => [
							'trigger simple'
						]
					]
				]
			],
			// #4 dependence on template trigger that linked to another template.
			[
				[
					'name' => 'Depends on linked trigger',
					'dependencie' => [
						'Template that linked to template' => [
							'trigger template linked'
						]
					]
				]
			],
			// #5 dependence on hosts trigger.
			[
				[
					'name' => 'Depends on hosts trigger',
					'host_dependencie' => [
						'Host with everything' => [
							'Host trigger everything'
						]
					]
				]
			],
			// #6 dependence on two hosts trigger.
			[
				[
					'name' => 'Depends on two hosts trigger',
					'host_dependencie' => [
						'Host with everything' => [
							'Host trigger everything',
							'Host trigger everything 2'
						]
					]
				]
			],
			// #7 dependence on trigger that linked from another template.
			[
				[
					'name' => 'Depends on trigger that linked from another template',
					'dependencie' => [
						'Template with linked template' => [
							'trigger template linked'
						]
					]
				]
			],
			//#8 dependence on trigger from template and trigger from host.
			[
				[
					'name' => 'Depends on trigger from template and host',
					'host_dependencie' => [
						'Host with everything' => [
							'Host trigger everything'
						]
					],
					'dependencie' => [
						'Template with everything' => [
							'trigger simple'
						]
					]
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTemplateTriggerCreateData
	 */
	public function testTriggerDependencies_TemplateTriggerCreate($data) {
		$this->page->login()->open('triggers.php?filter_set=1&filter_hostids%5B0%5D='.
				self::$templateids['Template with everything'].'&context=template')->waitUntilReady();
		$this->query('button:Create trigger')->one()->click();
		$this->page->waitUntilReady();
		$this->triggerCreateUpdate($data, 'last(/Template with everything/everything)=0');
		$this->assertMessage(TEST_GOOD, 'Trigger added');
		$this->checkTrigger($data);
	}

	public static function getTemplateTriggerUpdateData() {
		return [
			// #0 simple dependence on another trigger on same template.
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Trigger update',
					'dependencie' => [
						'Template with everything' => [
							'Trigger update'
						]
					],
					'error_message' => 'Trigger "Trigger update" cannot depend on the trigger "Trigger update", '.
							'because a circular linkage ("Trigger update" -> "Trigger update") would occur.'
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTemplateTriggerUpdateData
	 * @dataProvider getTemplateTriggerCreateData
	 */
	public function testTriggerDependencies_TemplateTriggerUpdate($data) {
		$this->page->login()->open('triggers.php?form=update&triggerid='.self::$template_triggerids['Trigger update'].
					'&context=template')->waitUntilReady();
		$this->triggerCreateUpdate($data);

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$this->assertMessage(TEST_BAD, 'Cannot update trigger', $data['error_message']);
		}
		else {
			$this->assertMessage(TEST_GOOD, 'Trigger updated');
			$this->checkTrigger($data, null, true);
			self::$triger_template = $data['name'].'_update';
		}
	}

	public static function getTemplateTriggerPrototypeCreateData() {
		return [
			// #0 dependence on trigger from template, host and trigger prototype.
			[
				[
					'name' => 'Depends on trigger, hosts trigger and prototype_{#KEY}',
					'host_dependencie' => [
						'Host with everything' => [
							'Host trigger everything'
						]
					],
					'dependencie' => [
						'Template with everything' => [
							'trigger simple'
						]
					],
					'prototype_dependencie' => [
						'trigger prototype simple{#KEY}'
					]
				]
			],
			// #1 dependence on prototype only.
			[
				[
					'name' => 'Depends on prototype_{#KEY}',
					'prototype_dependencie' => [
						'trigger prototype simple{#KEY}'
					]
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTemplateTriggerCreateData
	 * @dataProvider getTemplateTriggerPrototypeCreateData
	 */
	public function testTriggerDependencies_TemplateTriggerPrototypeCreate($data) {
		$this->page->login()->open('trigger_prototypes.php?parent_discoveryid='.
			self::$druleids['Drule for everything'].'&context=template')->waitUntilReady();
		$this->query('button:Create trigger prototype')->one()->click();
		$this->page->waitUntilReady();
		$this->triggerCreateUpdate($data, 'last(/Template with everything/everything_prot_[{#KEY}])=0');
		$this->assertMessage(TEST_GOOD, 'Trigger prototype added');
		$this->checkTrigger($data, 'Template with everything');
	}

	public static function getTemplateTriggerPrototypeUpdateData() {
		return [
			// #0 dependence on trigger from template, host and trigger prototype.
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Depends on trigger, hosts trigger and prototype_{#KEY}',
					'prototype_dependencie' => [
						'Trigger prototype update{#KEY}'
					],
					'error_message' => 'Trigger prototype "Trigger prototype update{#KEY}" cannot depend on the trigger '.
							'prototype "Trigger prototype update{#KEY}", because a circular linkage ("Trigger prototype '.
							'update{#KEY}" -> "Trigger prototype update{#KEY}") would occur.'
				]
			]
		];
	}

	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getTemplateTriggerPrototypeUpdateData
	 * @dataProvider getTemplateTriggerCreateData
	 * @dataProvider getTemplateTriggerPrototypeCreateData
	 */
	public function testTriggerDependencies_TemplateTriggerPrototypeUpdate($data) {
		$this->page->login()->open('trigger_prototypes.php?form=update&parent_discoveryid='.
					self::$druleids['Drule for everything'].'&triggerid='.
					self::$trigger_protids['Trigger prototype update{#KEY}'].'&context=template')->waitUntilReady();
		$this->triggerCreateUpdate($data);

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$this->assertMessage(TEST_BAD, 'Cannot update trigger prototype', $data['error_message']);
		}
		else {
			$this->assertMessage(TEST_GOOD, 'Trigger prototype updated');
			$this->checkTrigger($data, 'Template with everything', true);
			self::$trigger_template_prot = $data['name'].'_update';
		}
	}

	/**
	 * Create or update trigger with dependencies.
	 *
	 * @param array $data				data provider.
	 * @param string $expression		trigger expression used in create scenarios.
	 */
	private function triggerCreateUpdate($data, $expression = null) {
		$form = $this->query('name:triggersForm')->asForm()->one();

		if ($expression === null) {
			$form->fill(['Name' => $data['name'].'_update']);
			$form->selectTab('Dependencies')->waitUntilReady();
			$rows = $this->query('id:dependency-table')->asTable()->one()->getRows();

			foreach ($rows as $row) {
				$row->query('button:Remove')->one()->click();
			}
		}
		else {
			$form->fill(['Name' => $data['name'], 'Expression' => $expression]);
			$form->selectTab('Dependencies')->waitUntilReady();
		}

		if (array_key_exists('dependencie', $data)) {
			$this->addDependence($data['dependencie'], 'id:add_dep_trigger');
		}

		if (array_key_exists('host_dependencie', $data)) {
			$this->addDependence($data['host_dependencie'], 'id:add_dep_host_trigger');
		}

		if (array_key_exists('prototype_dependencie', $data)) {
			$form->query('id:add_dep_trigger_prototype')->one()->click();
			$dialog = COverlayDialogElement::find()->one()->waitUntilReady();

			foreach ($data['prototype_dependencie'] as $trigger) {
				$dialog->query('xpath:.//a[text()="'.$trigger.'"]/../preceding-sibling::td/input')->asCheckbox()->one()->check();
			}

			$dialog->query('xpath:.//button[text()="Select"]')->one()->click();
			$dialog->waitUntilNotVisible();
		}

		$form->submit();
	}

	/**
	 * Check that dependencies added/updated on trigger.
	 *
	 * @param array $data			data provider.
	 * @param string $prot_host		host name that has trigger prototype used for dependencies check.
	 * @param boolean $update		if this function use in update scenario or not.
	 */
	private function checkTrigger($data, $prot_host = null, $update = false) {
		$trigger_name = ($update) ? $data['name'].'_update' : $data['name'];
		$this->query('class:list-table')->one()->asTable()->query('link', $trigger_name)->one()->click();
		$this->page->waitUntilReady();
		$this->query('name:triggersForm')->asForm()->one()->selectTab('Dependencies')->waitUntilReady();
		$column_values = $this->getTableColumnData('Name', 'id:dependency-table');

		$host_check = (array_key_exists('host_dependencie', $data)) ? $data['host_dependencie'] : null;
		$trigger_check = (array_key_exists('dependencie', $data)) ? $data['dependencie'] : null;
		$prototype_check = (array_key_exists('prototype_dependencie', $data))
			? [$prot_host => $data['prototype_dependencie']]
			: null;

		foreach ([$trigger_check, $prototype_check, $host_check] as $dependence_type) {
			if ($dependence_type !== null) {
				foreach ($dependence_type as $host => $triggers) {
					foreach ($triggers as $trigger) {
						$this->assertTrue(in_array($host.': '.$trigger, $column_values));
					}
				}
			}
		}
	}

	/**
	 * Add trigger dependency - host trigger, simple trigger
	 *
	 * @param array $values			host/template name and trigger name.
	 * @param string $selector		Add/Add host trigger - button selector.
	 */
	private function addDependence($values, $selector){
		foreach ($values as $host_name => $triggers) {
			$this->query($selector)->one()->click();
			$dialog = COverlayDialogElement::find()->one()->waitUntilReady();
			$dialog->query('id:generic-popup-form')->asMultiselect()->one()->fill(['Host' => $host_name]);
			$dialog->waitUntilReady();

			foreach ($triggers as $trigger) {
				$dialog->query('xpath:.//a[text()="'.$trigger.'"]/../preceding-sibling::td/input')->asCheckbox()->one()->check();
			}

			$dialog->query('xpath:(.//button[text()="Select"])[2]')->one()->click();
			$dialog->waitUntilNotVisible();
		}
	}
}
