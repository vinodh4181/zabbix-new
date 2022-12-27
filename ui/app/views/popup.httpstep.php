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


/**
 * @var CView $this
 * @var array $data
 */

$options = $data['options'];

$form = (new CForm())
	->cleanItems()
	->setId('http_step')
	->addVar('no', $options['no'])
	->addVar('httpstepid', $options['httpstepid'])
	->addItem((new CVar('templated', $options['templated']))->removeId())
	->addVar('old_name', $options['old_name'])
	->addVar('steps_names', $options['steps_names'])
	->addVar('action', 'popup.httpstep')
	->addVar('validate', '1')
	->addItem((new CInput('submit', 'submit'))->addStyle('display: none;'));

$query_fields = (new CTag('script', true))->setAttribute('type', 'text/json');
$query_fields->items = array_key_exists('query_fields', $options['pairs'])
	? [json_encode($options['pairs']['query_fields'])]
	: [json_encode([['name' => '', 'value' => '', 'index' => 1]])];

$popup_grid = (new CFormGrid())
	->addItem([
		(new CLabel(_('Name'), 'step_name'))->setAsteriskMark(),
		new CFormField(
			(new CTextBox('name', $options['name'], (bool) $options['templated'],
				DB::getFieldLength('httpstep', 'name')
			))
				->setAriaRequired()
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setId('step_name')
		)
	])
	->addItem([
		(new CLabel(_('URL'), 'url'))->setAsteriskMark(),
		new CFormField([
			(new CTextBox('url', $options['url'], false, DB::getFieldLength('httpstep', 'url')))
				->setAriaRequired()
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CButton('parse', _('Parse')))
				->onClick('http_step_popup.parseUrl();')
				->addClass(ZBX_STYLE_BTN_GREY)
		])
	])
	->addItem([
		new CLabel(_('Query fields')),
		new CFormField(
			(new CDiv([
				(new CTable())
					->setAttribute('style', 'width: 100%;')
					->setAttribute('data-type', 'query_fields')
					->setAttribute('data-templated', $options['templated'])
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->addRow((new CRow())->setAttribute('data-insert-point', 'append'))
					->setFooter(new CRow(
						(new CCol(
							(new CButton(null, _('Add')))
								->addClass(ZBX_STYLE_BTN_LINK)
								->setAttribute('data-row-action', 'add_row')
						))->setColSpan(5)
					)),
				(new CTag('script', true))
					->setAttribute('type', 'text/x-jquery-tmpl')
					->addItem(new CRow([
						(new CCol(
							(new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)
						))->addClass(ZBX_STYLE_TD_DRAG_ICON),
						(new CTextBox('query_fields[#{index}][name]', '#{name}'))
							->setAttribute('placeholder', _('name'))
							->setWidth(ZBX_TEXTAREA_HTTP_PAIR_NAME_WIDTH),
						'&rArr;',
						(new CTextBox('query_fields[#{index}][value]', '#{value}'))
							->setAttribute('placeholder', _('value'))
							->setWidth(ZBX_TEXTAREA_HTTP_PAIR_VALUE_WIDTH),
						(new CButton(null, _('Remove')))
							->addClass(ZBX_STYLE_BTN_LINK)
							->addClass('js-editable-row-remove')
							->setAttribute('data-row-action', 'remove_row')
					])),
				$query_fields
			]))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->addClass('js-tbl-editable')
				->setAttribute('data-sortable-pairs-table', '1')
				->addStyle('min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		)
	])
	->addItem([
		new CLabel(_('Post type')),
		new CFormField(
			(new CRadioButtonList('post_type', (int) $options['post_type']))
				->addValue(_('Form data'), ZBX_POSTTYPE_FORM)
				->addValue(_('Raw data'), ZBX_POSTTYPE_RAW)
				->setModern(true)
		)
	])
	->addItem([
		(new CLabel(_('Post fields')))->addClass('js-post-fields'),
		(new CFormField(
			(new CDiv(
				(new CTable())
					->addClass('httpconf-dynamic-table')
					->addStyle('width: 100%;')
					->setAttribute('data-type', 'post_fields')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->addRow((new CRow([
						(new CCol(
							(new CButton(null, _('Add')))
								->addClass('element-table-add')
								->addClass(ZBX_STYLE_BTN_LINK)
						))->setColSpan(5)
					])))
			))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->addStyle('min-width: '.ZBX_TEXTAREA_BIG_WIDTH . 'px;')
		))->addClass('js-post-fields')
	])
	->addItem([
		(new CLabel(_('Raw post'), 'posts'))->addClass('js-raw-post'),
		(new CFormField(
			(new CTextArea('posts', $options['posts']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH))
		)->addClass('js-raw-post')
	])
	->addItem([
		new CLabel(_('Variables')),
		new CFormField(
			(new CDiv(
				(new CTable())
					->addClass('httpconf-dynamic-table')
					->setAttribute('data-type', 'variables')
					->addStyle('width: 100%;')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->addRow((new CRow([
						(new CCol(
							(new CButton(null, _('Add')))
								->addClass('element-table-add')
								->addClass(ZBX_STYLE_BTN_LINK)
						))->setColSpan(5)
					])))
			))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->addStyle('min-width: '.ZBX_TEXTAREA_BIG_WIDTH . 'px;')
		)
	])
	->addItem([
		new CLabel(_('Headers')),
		new CFormField(
			(new CDiv(
				(new CTable())
					->addClass('httpconf-dynamic-table')
					->setAttribute('data-type', 'headers')
					->addStyle('width: 100%;')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->addRow((new CRow([
						(new CCol(
							(new CButton(null, _('Add')))
								->addClass('element-table-add')
								->addClass(ZBX_STYLE_BTN_LINK)
						))->setColSpan(5)
					])))
			))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->addStyle('min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		)
	])
	->addItem([
		new CLabel(_('Follow redirects'), 'follow_redirects'),
		new CFormField(
			(new CCheckBox('follow_redirects'))
				->setChecked($options['follow_redirects'] == HTTPTEST_STEP_FOLLOW_REDIRECTS_ON)
		)
	])
	->addItem([
		new CLabel(_('Retrieve mode'), 'retrieve_mode'),
		new CFormField(
			(new CRadioButtonList('retrieve_mode', (int) $options['retrieve_mode']))
				->addValue(_('Body'), HTTPTEST_STEP_RETRIEVE_MODE_CONTENT)
				->addValue(_('Headers'), HTTPTEST_STEP_RETRIEVE_MODE_HEADERS)
				->addValue(_('Body and headers'), HTTPTEST_STEP_RETRIEVE_MODE_BOTH)
				->setModern(true)
		)
	])
	->addItem([
		(new CLabel(_('Timeout'), 'timeout'))->setAsteriskMark(),
		new CFormField(
			(new CTextBox('timeout', $options['timeout']))
				->setAriaRequired()
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
		)
	])
	->addItem([
		new CLabel(_('Required string'), 'required'),
		new CFormField(
			(new CTextBox('required', $options['required']))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAttribute('placeholder', _('pattern'))
		)
	])
	->addItem([
		new CLabel(_('Required status codes'), 'status_codes'),
		new CFormField(
			(new CTextBox('status_codes', $options['status_codes']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		)
	]);

$form
	->addItem($popup_grid)
	->addItem(
		(new CScriptTag('
			http_step_popup.init('.json_encode([
				'data' => $options
			]).');
		'))->setOnDocumentReady()
	);

$form->addItem(new CJsScript($this->readJsFile('../../../include/views/js/editabletable.js.php')));

$output = [
	'header' => $data['title'],
	'buttons' => [
		[
			'title' => $options['old_name'] ? _('Update') : _('Add'),
			'class' => '',
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'return http_step_popup.submit(overlay);'
		]
	],
	'body' => (new CDiv($form))->toString(),
	'script_inline' => getPagePostJs().$this->readJsFile('popup.httpstep.js.php')
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
