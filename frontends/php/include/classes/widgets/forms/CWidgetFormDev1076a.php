<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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


/**
 * DEV-1076 Experiment #1 widget form.
 */
class CWidgetFormDev1076a extends CWidgetForm {

	public function __construct($data) {
		parent::__construct($data, WIDGET_DEV_1076_A);

		// Host groups.
		$field_groups = new CWidgetFieldGroup('groupids', _('Host groups'));

		if (array_key_exists('groupids', $this->data)) {
			$field_groups->setValue($this->data['groupids']);
		}

		$this->fields[$field_groups->getName()] = $field_groups;

		// Hosts field.
		$field_hosts = new CWidgetFieldHost('hostids', _('Hosts'));

		if (array_key_exists('hostids', $this->data)) {
			$field_hosts->setValue($this->data['hostids']);
		}

		$this->fields[$field_hosts->getName()] = $field_hosts;
	}
}
