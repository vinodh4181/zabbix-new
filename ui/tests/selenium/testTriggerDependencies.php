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
				'description' => 'trigger linked 1',
				'expression' => 'last(/Template that linked to host/everything_2)=0'
			],
			[
				'description' => 'trigger linked 2',
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
				'description' => 'trigger prototype linked 1{#KEY}',
				'expression' => 'last(/Template that linked to host/linking_prot_[{#KEY}])=0'
			],
			[
				'description' => 'trigger prototype linked 2{#KEY}',
				'expression' => 'last(/Template that linked to host/linking_prot_[{#KEY}])=0'
			]
		]);
		$this->assertArrayHasKey('triggerids', $trigger_prot);
		self::$trigger_protids = CDataHelper::getIds('description');

		$hosts = CDataHelper::call('host.create', [
			[
				'host' => 'With linked template',
				'templates' => [
					'templateid' => self::$templateids['Template that linked to host']
				],
				'groups' => [
					['groupid' => 4]
				]
			],
			[
				'host' => 'With everything',
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
				'hostid' => self::$hostids['With everything'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			],
			[
				'name' => 'Host item 2',
				'key_' => 'host_item_2',
				'hostid' => self::$hostids['With everything'],
				'type' => 2,
				'value_type' => 3,
				'delay' => 0
			]
		]);
		$this->assertArrayHasKey('itemids', $host_items);
		self::$host_itemids = CDataHelper::getIds('name');

		$host_triggers
	}

	public function testTriggerDependencies_Create() {
//		var_dump(self::$templateids['Template 1']);
	}
}
