<?php declare(strict_types = 0);
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
**/


/**
 * Trigger overview widget form view.
 *
 * @var CView $this
 * @var array $data
 */

$fields = $data['dialogue']['fields'];

$form = CWidgetHelper::createForm();

$scripts = [];

$form_grid = CWidgetHelper::createFormGrid($data['dialogue']['name'], $data['dialogue']['type'],
	$data['dialogue']['view_mode'], $data['known_widget_types'],
	$data['templateid'] === null ? $fields['rf_rate'] : null
);

// Show.
$form_grid->addItem([
	CWidgetHelper::getLabel($fields['show']),
	new CFormField(CWidgetHelper::getRadioButtonList($fields['show']))
]);

// Host groups.
$field_groupids = CWidgetHelper::getGroup($fields['groupids'], $data['captions']['ms']['groups']['groupids'],
	$form->getName()
);
$form_grid->addItem([
	CWidgetHelper::getMultiselectLabel($fields['groupids']),
	new CFormField($field_groupids)
]);
$scripts[] = $field_groupids->getPostJS();

// Hosts.
$field_hostids = CWidgetHelper::getHost($fields['hostids'], $data['captions']['ms']['hosts']['hostids'],
	$form->getName()
);
$form_grid->addItem([
	CWidgetHelper::getMultiselectLabel($fields['hostids']),
	new CFormField($field_hostids)
]);
$scripts[] = $field_hostids->getPostJS();

// Tags.
$form_grid
	->addItem([
		CWidgetHelper::getLabel($fields['evaltype']),
		new CFormField(CWidgetHelper::getRadioButtonList($fields['evaltype']))
	])
	->addItem(
		new CFormField(CWidgetHelper::getTags($fields['tags']))
	);
$scripts[] = $fields['tags']->getJavascript();
$jq_templates['tag-row-tmpl'] = CWidgetHelper::getTagsTemplate($fields['tags']);

// Show suppressed problems.
$form_grid->addItem([
	CWidgetHelper::getLabel($fields['show_suppressed']),
	new CFormField(CWidgetHelper::getCheckBox($fields['show_suppressed']))
]);

// Hosts location.
$form_grid->addItem([
	CWidgetHelper::getLabel($fields['style']),
	new CFormField(CWidgetHelper::getRadioButtonList($fields['style']))
]);

$form->addItem($form_grid);

return [
	'form' => $form,
	'scripts' => $scripts,
	'jq_templates' => $jq_templates
];
