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

/**
 *
 *
 * @onBefore prepareTriggersData
 */
class testTriggerDependencies extends CWebTest {

	protected static $templateids;
	protected static $template_itemids;
	protected static $template_triggerids;
	protected static $druleids;
	protected static $item_protids;
	protected static $trigger_protids;
	protected static $hostids;
	protected static $host_itemids;
	protected static $host_triggerids;
	protected static $host_druleids;
	protected static $host_item_protids;
	protected static $host_trigger_protids;

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
			]
		]);
		$this->assertArrayHasKey('templateids', $templates);
		self::$templateids = CDataHelper::getIds('host');

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
			]
		]);
		$this->assertArrayHasKey('itemids', $template_items);
		self::$template_itemids = CDataHelper::getIds('name');

		$template_triggers = CDataHelper::call('trigger.create', [
			[
				'description' => 'trigger update',
				'expression' => 'last(/Template with everything/everything)=0'
			],
			[
				'description' => 'trigger simple',
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
			]
		]);
		$this->assertArrayHasKey('itemids', $item_prot);
		self::$item_protids = CDataHelper::getIds('name');

		$trigger_prot = CDataHelper::call('triggerprototype.create', [
			[
				'description' => 'trigger prototype update{#KEY}',
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
		self::$host_itemids = CDataHelper::getIds('name');

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
		self::$host_item_protids = CDataHelper::getIds('name');

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

	public static function getHostCreateData() {
		return [

		];
	}




	/**
	 * Create trigger with dependencies on host.
	 *
	 * @dataProvider getHostCreateData
	 */
	public function testTriggerDependencies_Create() {
		$this->page->login()->open('triggers.php?hostid=99262&form=create&context=host')->waitUntilReady();
		$form = $this->query('name:triggersForm')->asForm()->one();
		$form->fill(['Name' => 'test', 'Expression' => 'last(/Host with everything/host_item_1)=0']);
		$form->selectTab('Dependencies')->waitUntilReady();
		$form->query('id:add_dep_trigger')->one()->click();
		$dialog = COverlayDialogElement::find()->one()->waitUntilReady();
		$dialog->query('id:generic-popup-form')->asMultiselect()->one()->fill(['Host' => 'Host with everything']);
		$dialog->waitUntilReady();

		$dialog->query('xpath:.//input[@value="100215"]')->asCheckbox()->one()->check();
		$dialog->query('xpath:(.//button[text()="Select"])[2]')->one()->click();
		$dialog->waitUntilNotVisible();

		$form->submit();
		$this->assertMessage(TEST_GOOD, 'Trigger added');
	}
}
