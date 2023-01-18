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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
**/


/**
 * @var CView $this
 * @var array $data
 */

$this->includeJsFile('configuration.httpconf.edit.js.php');

$widget = (new CWidget())->setTitle(_('Web monitoring'));

// Append host summary to widget header.
if ($data['hostid'] != 0) {
	$widget->setNavigation(getHostNavigation('web', $data['hostid']));
}

$url = (new CUrl('httpconf.php'))
	->setArgument('context', $data['context'])
	->getUrl();

$form = (new CForm('post', $url))
	->setId('http-form')
	->setName('httpForm')
	->setAttribute('aria-labelledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $data['form'])
	->addVar('hostid', $data['hostid'])
	->addVar('templated', $data['templated']);

if ($data['httptestid'] != 0) {
	$form->addVar('httptestid', $data['httptestid']);
}

// Scenario tab.
$scenario_tab = new CFormGrid();

if ($data['templates']) {
	$scenario_tab->addItem([
		new CLabel(_('Parent web scenarios')),
		new CFormField($data['templates'])
	]);
}

$name_text_box = (new CTextBox('name', $data['name'], $data['templated'], DB::getFieldLength('httptest', 'name')))
	->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	->setAriaRequired();
if (!$data['templated']) {
	$name_text_box->setAttribute('autofocus', 'autofocus');
}

$agent_select = (new CSelect('agent'))
	->setId('agent')
	->setFocusableElementId('label-agent')
	->setValue($data['agent']);

$user_agents_all = userAgents();
$user_agents_all[_('Others')][ZBX_AGENT_OTHER] = _('other').' ...';

foreach ($user_agents_all as $user_agent_group => $user_agents) {
	$agent_select->addOptionGroup((new CSelectOptionGroup($user_agent_group))
		->addOptions(CSelect::createOptionsFromArray($user_agents))
	);
}

$scenario_tab
	->addItem([
		(new CLabel(_('Name'), 'name'))->setAsteriskMark(),
		new CFormField($name_text_box)
	])
	->addItem([
		(new CLabel(_('Update interval'), 'delay'))->setAsteriskMark(),
		new CFormField(
			(new CTextBox('delay', $data['delay']))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAriaRequired()
		)
	])
	->addItem([
		(new CLabel(_('Attempts'), 'retries'))->setAsteriskMark(),
		new CFormField(
			(new CNumericBox('retries', $data['retries'], 2))
				->setAriaRequired()
				->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
		)
	])
	->addItem([
		new CLabel(_('Agent'), $agent_select->getFocusableElementId()),
		new CFormField($agent_select)
	])
	->addItem([
		(new CLabel(_('User agent string'), 'agent_other'))->addClass('js-agent-other'),
		(new CFormField(
			(new CTextBox('agent_other', $data['agent_other']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->addClass('js-agent-other')
	])
	->addItem([
		new CLabel(_('HTTP proxy'), 'http_proxy'),
		new CFormField(
			(new CTextBox('http_proxy', $data['http_proxy']))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAttribute('placeholder', '[protocol://][user[:password]@]proxy.example.com[:port]')
				->disableAutocomplete()
		)
	])
	->addItem([
		new CLabel(_('Variables')),
		new CFormField(
			(new CDiv(
				(new CTable())
					->addClass('httpconf-variables-dynamic-table')
					->addClass('httpconf-dynamic-table')
					->setAttribute('data-type', 'variables')
					->setAttribute('style', 'width: 100%;')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->addRow(
						(new CRow([
							(new CCol(
								(new CButton(null, _('Add')))
									->addClass(ZBX_STYLE_BTN_LINK)
									->addClass('element-table-add')
							))->setColSpan(5)
						]))
					)
			))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		)
	])
	->addItem([
		new CLabel(_('Headers')),
		new CFormField(
			(new CDiv(
				(new CTable())
					->addClass('httpconf-headers-dynamic-table')
					->addClass('httpconf-dynamic-table')
					->setAttribute('data-type', 'headers')
					->setAttribute('style', 'width: 100%;')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->addRow(
						(new CRow([
							(new CCol(
								(new CButton(null, _('Add')))
									->addClass(ZBX_STYLE_BTN_LINK)
									->addClass('element-table-add')
							))->setColSpan(5)
						]))
					)
			))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		)
	])
	->addItem([
		new CLabel(_('Enabled'), 'status'),
		new CFormField(
			(new CCheckBox('status'))->setChecked(!$data['status'])
		)
	]);

// Step tab.
$steps_tab = new CFormGrid();
$steps_table = (new CTable())
	->addClass('httpconf-dynamic-table')
	->addClass('httpconf-steps-dynamic-table')
	->addClass('list-numbered')
	->setHeader([
		(new CColHeader())->setWidth('15'),
		(new CColHeader())->setWidth('15'),
		(new CColHeader(_('Name')))->setWidth('150'),
		(new CColHeader(_('Timeout')))->setWidth('50'),
		(new CColHeader(_('URL')))->setWidth('200'),
		(new CColHeader(_('Required')))->setWidth('75'),
		(new CColHeader(_('Status codes')))
			->addClass(ZBX_STYLE_NOWRAP)
			->setWidth('90'),
		(new CColHeader(_('Action')))->setWidth('50')
	]);

if (!$data['templated']) {
	$steps_table->addRow(
		(new CCol(
			(new CButton(null, _('Add')))
				->addClass('element-table-add')
				->addClass(ZBX_STYLE_BTN_LINK)
		))->setColSpan(8)
	);
}
else {
	$steps_table->addRow(
		(new CCol(null))
			->setColSpan(8)
			->addClass('element-table-add')
	);
}

$steps_tab->addItem([
	(new CLabel(_('Steps'), $steps_table->getId()))->setAsteriskMark(),
	new CFormField(
		(new CDiv($steps_table))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAriaRequired()
	)
]);

// Authentication tab.
$authentication_tab = (new CFormGrid())
	->addItem([
		new CLabel(_('HTTP authentication'), 'label-authentication'),
		new CFormField(
			(new CSelect('authentication'))
				->setId('authentication')
				->setFocusableElementId('label-authentication')
				->setValue($data['authentication'])
				->addOptions(CSelect::createOptionsFromArray(httptest_authentications()))
		)
	])
	->addItem([
		(new CLabel(_('User'), 'http_user'))->addClass('js-http-auth'),
		(new CFormField(
			(new CTextBox('http_user', $data['http_user'], false, DB::getFieldLength('httptest', 'http_user')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->addClass('js-http-auth')
	])
	->addItem([
		(new CLabel(_('Password'), 'http_password'))->addClass('js-http-auth'),
		(new CFormField(
			(new CTextBox('http_password', $data['http_password'], false,
				DB::getFieldLength('httptest', 'http_password')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->addClass('js-http-auth')
	])
	->addItem([
		new CLabel(_('SSL verify peer'), 'verify_peer'),
		new CFormField(
			(new CCheckBox('verify_peer'))->setChecked($data['verify_peer'] == 1)
		)
	])
	->addItem([
		new CLabel(_('SSL verify host'), 'verify_host'),
		new CFormField(
			(new CCheckBox('verify_host'))->setChecked($data['verify_host'] == 1)
		)
	])
	->addItem([
		new CLabel(_('SSL certificate file'), 'ssl_cert_file'),
		new CFormField(
			(new CTextBox('ssl_cert_file', $data['ssl_cert_file'], false,
				DB::getFieldLength('httptest', 'ssl_cert_file')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		)
	])
	->addItem([
		new CLabel(_('SSL key file'), 'ssl_key_file'),
		new CFormField(
			(new CTextBox('ssl_key_file', $data['ssl_key_file'], false, DB::getFieldLength('httptest', 'ssl_key_file')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		)
	])
	->addItem([
		new CLabel(_('SSL key password'), 'ssl_key_password'),
		new CFormField(
			(new CTextBox('ssl_key_password', $data['ssl_key_password'], false,
				DB::getFieldLength('httptest', 'ssl_key_password')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		)
	]);

$http_tabs = (new CTabView())
	->addTab('scenario-tab', _('Scenario'), $scenario_tab)
	->addTab('steps-tab', _('Steps'), $steps_tab, TAB_INDICATOR_STEPS)
	->addTab('tags-tab', _('Tags'),
		new CPartial('configuration.tags.tab', [
			'source' => 'httptest',
			'tags' => $data['tags'],
			'show_inherited_tags' => $data['show_inherited_tags'],
			'readonly' => false,
			'tabs_id' => 'tabs',
			'tags_tab_id' => 'tags-tab'
		]),
		TAB_INDICATOR_TAGS
	)
	->addTab('authentication-tab', _('Authentication'), $authentication_tab, TAB_INDICATOR_HTTP_AUTH);

if ($data['form_refresh'] == 0) {
	$http_tabs->setSelected(0);
}

if ($data['httptestid'] != 0) {
	$buttons = [new CSubmit('clone', _('Clone'))];

	if ($data['host']['status'] == HOST_STATUS_MONITORED || $data['host']['status'] == HOST_STATUS_NOT_MONITORED) {
		$buttons[] = new CButtonQMessage(
			'del_history',
			_('Clear history and trends'),
			_('History clearing can take a long time. Continue?')
		);
	}

	$buttons[] = (new CButtonDelete(_('Delete web scenario?'), url_params(['form', 'httptestid', 'hostid', 'context']),
		'context'
	))->setEnabled(!$data['templated']);
	$buttons[] = new CButtonCancel(url_param('context'));

	$http_tabs->setFooter(makeFormFooter(new CSubmit('update', _('Update')), $buttons));
}
else {
	$http_tabs->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('context'))]
	));
}

$form->addItem($http_tabs);
$widget->addItem($form);

$widget->show();

(new CScriptTag('
	view.init('.json_encode([
		'form_name' => $form->getName(),
		'templated' => $data['templated'],
		'pairs' => $data['pairs'],
		'steps' => $data['steps']
	]).');
'))
	->setOnDocumentReady()
	->show();
