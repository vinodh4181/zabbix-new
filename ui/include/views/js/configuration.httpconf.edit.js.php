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
 */
?>

<script type="text/x-jquery-tmpl" id="scenario-step-row-templated-tmpl">
	<?= (new CRow([
			'',
			(new CSpan('#{no}:'))->setAttribute('data-row-num', ''),
			(new CLink('#{name}', 'javascript:;'))
				->setAttribute('data-row-name', '#{name}')
				->setAttribute('onclick', 'view.open(this)'),
			'#{timeout}',
			(new CSpan('#{url_short}'))->setHint('#{url}', '', true, 'word-break: break-all;')
				->setAttribute('data-hintbox', '#{enabled_hint}'),
			'#{required}',
			'#{status_codes}',
			''
		]))
			->addClass('form_row')
			->toString()
	?>
</script>

<script type="text/x-jquery-tmpl" id="scenario-step-row-tmpl">
	<?= (new CRow([
			(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))->addClass(ZBX_STYLE_TD_DRAG_ICON),
			(new CSpan('#{no}:'))->setAttribute('data-row-num', '#{no}'),
			(new CLink('#{name}', 'javascript:;'))
				->setAttribute('data-row-name', '#{name}')
				->setAttribute('onclick', 'view.open(this)'),
			'#{timeout}',
			(new CSpan('#{url_short}'))->setHint('#{url}', '', true, 'word-break: break-all;')
				->setAttribute('data-hintbox', '#{enabled_hint}'),
			'#{required}',
			'#{status_codes}',
			(new CCol((new CButton(null, _('Remove')))
				->setAttribute('onclick', 'view.removeStepRow(this)')
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		]))
			->addClass('sortable')
			->addClass('form_row')
			->toString()
	?>
</script>

<script type="text/x-jquery-tmpl" id="scenario-pair-row-tmpl">
	<?= (new CRow([
			(new CCol([
				(new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)
			]))->addClass(ZBX_STYLE_TD_DRAG_ICON),
			(new CTextBox('pairs[#{index}][name]', '#{name}'))
				->setAttribute('placeholder', _('name'))
				->setAttribute('data-type', 'name')
				->setId('')
				->setWidth(ZBX_TEXTAREA_HTTP_PAIR_NAME_WIDTH),
			'&rArr;',
			(new CTextBox('pairs[#{index}][value]', '#{value}'))
				->setAttribute('placeholder', _('value'))
				->setAttribute('data-type', 'value')
				->setId('')
				->setWidth(ZBX_TEXTAREA_HTTP_PAIR_VALUE_WIDTH),
			(new CCol([
				(new CButton(null, _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove'),
				new CVar('pairs[#{index}][type]', '#{type}', '')
			]))->addClass(ZBX_STYLE_NOWRAP)
		]))
			->addClass('sortable')
			->addClass('form_row')
			->toString()
	?>
</script>

<script>
	'use strict';

	const ZBX_AGENT_OTHER = <?= ZBX_AGENT_OTHER ?>;
	const HTTPTEST_AUTH_NONE = <?= HTTPTEST_AUTH_NONE ?>;

	const ZBX_STYLE_DRAG_ICON = <?= json_encode(ZBX_STYLE_DRAG_ICON) ?>;
	const ZBX_STYLE_DISPLAY_NONE = <?= json_encode(ZBX_STYLE_DISPLAY_NONE) ?>;

	// Constants for popup.
	const ZBX_POSTTYPE_RAW = <?= ZBX_POSTTYPE_RAW ?>;
	const HTTPTEST_STEP_RETRIEVE_MODE_HEADERS = <?= HTTPTEST_STEP_RETRIEVE_MODE_HEADERS ?>;
	const ZBX_POSTTYPE_FORM = <?= ZBX_POSTTYPE_FORM ?>;

	const URL_MAX_LENGTH = 65;

	const view = new class {
		init({form_name, templated, pairs, steps}) {
			this.form_name = form_name;
			this.form = document.querySelector("form[name='" + this.form_name + "']");
			this.steps = null; // Initialized in initStepsTab function.
			this.templated = templated;
			this.row_id = 1;

			document
				.getElementById('agent')
				.addEventListener('change', () => this._update());
			document
				.getElementById('authentication')
				.addEventListener('change', () => this._update());

			this.initScenarioTab(ScenarioHelper.sanitizePairs(pairs));
			this.initStepsTab(steps);

			this.form.addEventListener('submit', (e) => {
				var hidden_form = document.querySelector('#hidden-form');

				hidden_form && hidden_form.remove();
				hidden_form = document.createElement('div');
				hidden_form.id = 'hidden-form';

				hidden_form.appendChild(this.stepsFragment());

				this.form.appendChild(hidden_form);
			});

			this._update();
		}

		_update() {
			const is_agent_disabled = document.getElementById('agent').value != ZBX_AGENT_OTHER;

			[...document.querySelectorAll('.js-agent-other, #agent_other')].map(
				(elem) => {
					if (elem instanceof HTMLInputElement) {
						elem.disabled = is_agent_disabled;
					}

					return elem
						.classList
						.toggle(ZBX_STYLE_DISPLAY_NONE, is_agent_disabled)
				}
			);

			const is_auth_disabled = document.getElementById('authentication').value == HTTPTEST_AUTH_NONE;

			document.getElementById('http_user').disabled = is_auth_disabled;
			document.getElementById('http_password').disabled = is_auth_disabled;

			[...document.querySelectorAll('.js-http-auth')].map(
				(elem) => elem
					.classList
					.toggle(ZBX_STYLE_DISPLAY_NONE, is_auth_disabled)
			);
		}

		initScenarioTab(pairs) {
			[...document.querySelectorAll('.httpconf-headers-dynamic-table, .httpconf-variables-dynamic-table')].map(
				(elem) => {
					const $elem = jQuery(elem);
					const type = elem.dataset.type;

					$elem
						.dynamicRows({
							template: '#scenario-pair-row-tmpl',
							rows: pairs[type],
							counter: 0,
							dataCallback: (data) => {
								return {...data, ...{type: type, index: this.row_id++}};
							}
						})
						.on('afteradd.dynamicRows', (e, dynamic_rows) => {
							if (type === 'variables') {
								e.target.querySelector('.' + ZBX_STYLE_DRAG_ICON).remove();
							}

							if (type === 'variables' || type === 'headers') {
								e.target.querySelector('[data-type="value"]').setAttribute('maxlength', 2000);
							}
						});

					if (type === 'variables') {
						[...elem.querySelectorAll('.' + ZBX_STYLE_DRAG_ICON)].map((elem) => elem.remove());
					}

					$elem.sortable({
						items: 'tbody tr.sortable',
						axis: 'y',
						containment: 'parent',
						cursor: 'grabbing',
						handle: 'div.' + ZBX_STYLE_DRAG_ICON,
						tolerance: 'pointer',
						opacity: 0.6,
						start: (e, ui) => {
							ui.placeholder.height(ui.item.height());
						}
					});
				}
			);
		}

		initStepsTab(steps) {
			const container = document.querySelector('.httpconf-steps-dynamic-row');

			this.steps = Object.values(steps).sort((a, b) => a.no > b.no);

			this.tableHandler(container);

			if (!this.templated) {
				jQuery(container)
					.sortable({
						items: 'tbody tr.sortable',
						axis: 'y',
						containment: 'parent',
						cursor: 'grabbing',
						handle: 'div.' + ZBX_STYLE_DRAG_ICON,
						tolerance: 'pointer',
						opacity: 0.6,
						start: (e, ui) => {
							ui.placeholder.height(ui.item.height());
						},
						update: this.stepSortOrderUpdate.bind(this)
					});
			}
		}

		tableHandler(container) {
			const templated = this.templated;
			const tmpl = templated ? '#scenario-step-row-templated-tmpl' : '#scenario-step-row-tmpl';
			const conainer_row = container.querySelector('tbody tr');

			for (const index in this.steps) {
				if (!this.steps.hasOwnProperty(index)) {
					continue;
				}

				this.addStepRow(this.steps[index], conainer_row, tmpl);
			}

			container
				.querySelector('.element-table-add')
				.addEventListener('click', () => {
					this.open();
				});
		}

		addStepRow(data, container, tmpl = '#scenario-step-row-tmpl') {
			data.url = data.url ?? '';
			data.enabled_hint = data.url.length > URL_MAX_LENGTH ? 1 : 0;
			data.url_short = ScenarioHelper.urlShortener(data.url);

			container.insertAdjacentHTML('beforeBegin',
				new Template(document.querySelector(tmpl).innerHTML).evaluate(data)
			);
		}

		removeStepRow(elem) {
			const row = elem.closest('tr');
			const index = row.querySelector('[data-row-num]').dataset.rowNum;

			row.remove();

			this.steps.splice(index - 1, 1);
			this.stepSortOrderUpdate();
		}

		updateStepRow(data, no) {
			this.steps = this.steps.map((value) => {
				if (value.no == no) {
					return data;
				}

				return value;
			});

			const elem = this.form.querySelector(`[data-row-num='${no}']`).closest('tr');

			data.url = data.url ?? '';
			data.enabled_hint = data.url.length > URL_MAX_LENGTH ? 1 : 0;
			data.url_short = ScenarioHelper.urlShortener(data.url);

			const el = document.createElement('tr');
			el.innerHTML = new Template(document.querySelector('#scenario-step-row-tmpl').innerHTML).evaluate(data);
			el.classList.add('sortable', 'form_row');
			elem.replaceWith(el);
		}

		open(elem) {
			let index = -1;

			if (elem) {
				index = elem.closest('tr').querySelector('[data-row-num]').dataset.rowNum;
			}

			const overlay = this.openPopup(index,
				document
					.querySelector('.httpconf-steps-dynamic-row')
					.querySelector('[data-index="' + index + '"] a')
			);

			overlay.$dialogue[0].addEventListener('dialogue.submit', this.savePopup.bind(this));
		}

		openPopup(index, trigger_element) {
			const data = this.steps.hasOwnProperty(index - 1)
				? {...this.steps[index - 1], ...{
						no: index, templated: this.templated ? 1 : 0, steps_names: this.getStepNames()
					}}
				: {steps_names: this.getStepNames()};

			if (this.steps.hasOwnProperty(index - 1)) {
				data.old_name = data.name;
			}

			return PopUp('popup.httpstep', data,
				{
					dialogueid: 'http_step_edit',
					dialogue_class: 'modal-popup-generic',
					trigger_element
				}
			);
		}

		savePopup(e) {
			const tmpl = this.templated ? '#scenario-step-row-templated-tmpl' : '#scenario-step-row-tmpl';
			const container = document.querySelector('.httpconf-steps-dynamic-row tbody tr:last-child');

			e.detail.httpstepid = 0;
			e.detail.old_name = e.detail.name;

			const pairs = {query_fields: [], post_fields: [], variables: [], headers: []};

			for (const value of Object.values(e.detail.pairs)) {
				pairs[value.type].push(value);
			}

			e.detail.pairs = pairs;

			if (e.detail.no > 0) {
				this.updateStepRow(e.detail, e.detail.no);
				return;
			}

			e.detail.no = this.steps.length + 1;

			this.steps.push(e.detail);
			this.addStepRow(e.detail, container, tmpl);
		}

		getStepNames() {
			const names = [];

			this.steps.map((value) => names.push(value.name));

			return names;
		}

		stepSortOrderUpdate() {
			let sort_index = 1;

			[...document.querySelectorAll('.httpconf-steps-dynamic-row .form_row')].map((elem) => {
				const numb = sort_index++;
				const name = elem.querySelector('[data-row-name]').dataset.rowName;
				const old_numb = elem.querySelector('[data-row-num]').dataset.rowNum;

				elem.querySelector('[data-row-num]').dataset.rowNum = numb;
				elem.querySelector('[data-row-num]').innerText = numb + ':';

				if (numb != old_numb) {
					this.steps = this.steps.map((value) => {
						if (value.name == name && value.no == old_numb) {
							value.no = numb;
						}

						return value;
					});
				}
			});
		}

		stepsFragment() {
			const frag = document.createDocumentFragment();
			let iter_step = 0;

			for (const value of this.steps) {
				let iter_pair = 0;
				let prefix_step = 'steps[' + (iter_step ++) + ']';
				let prefix_pair;

				frag.appendChild(ScenarioHelper.hiddenInput('follow_redirects', value.follow_redirects, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('httpstepid', value.httpstepid, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('name', value.name, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('post_type', value.post_type, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('required', value.required, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('retrieve_mode', value.retrieve_mode, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('status_codes', value.status_codes, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('timeout', value.timeout, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('url', value.url, prefix_step));
				frag.appendChild(ScenarioHelper.hiddenInput('no', value.no, prefix_step));

				if (value.retrieve_mode != HTTPTEST_STEP_RETRIEVE_MODE_HEADERS) {
					if (value.post_type != ZBX_POSTTYPE_FORM) {
						frag.appendChild(ScenarioHelper.hiddenInput('posts', value.posts, prefix_step));
					}
					else {
						if ('post_fields' in value.pairs) {
							for (const pair of value.pairs.post_fields) {
								prefix_pair = prefix_step + '[pairs][' + (iter_pair ++) + ']';
								frag.appendChild(ScenarioHelper.hiddenInput('type', 'post_fields', prefix_pair));
								frag.appendChild(ScenarioHelper.hiddenInput('name', pair.name, prefix_pair));
								frag.appendChild(ScenarioHelper.hiddenInput('value', pair.value, prefix_pair));
							}
						}
					}
				}

				for (const type of ['query_fields', 'variables', 'headers']) {
					if (type in value.pairs) {
						for (const pair of value.pairs[type]) {
							prefix_pair = prefix_step + '[pairs][' + (iter_pair ++) + ']';

							frag.appendChild(ScenarioHelper.hiddenInput('type', type, prefix_pair));
							frag.appendChild(ScenarioHelper.hiddenInput('name', pair.name, prefix_pair));
							frag.appendChild(ScenarioHelper.hiddenInput('value', pair.value, prefix_pair));
						}
					}
				}
			}

			return frag;
		}
	};

	class ScenarioHelper {

		static hiddenInput(name, value, prefix) {
			const input = window.document.createElement('input');

			input.type = 'hidden';
			input.value = value;
			input.name = prefix ? prefix + '[' + name + ']' : name;

			return input;
		};

		static parsePostRawToPairs(raw_txt) {
			if (!raw_txt) {
				return [];
			}

			const pairs = [];

			raw_txt.split('&').forEach((pair) => {
				const fields = pair.split('=');

				if (fields[0] === '' || fields[0].length > 255) {
					return;
				}

				if (fields.length == 1) {
					fields.push('');
				}

				const malformed = (fields.length > 2);
				const non_printable_chars = (fields[0].match(/%[01]/) || fields[1].match(/%[01]/));

				if (malformed || non_printable_chars) {
					return;
				}

				pairs.push({
					name: decodeURIComponent(fields[0].replace(/\+/g, ' ')),
					value: decodeURIComponent(fields[1].replace(/\+/g, ' '))
				});
			});

			return pairs;
		};

		static parsePostPairsToRaw(table) {
			const fields = [];

			for (const row of [...table.querySelectorAll('.form_row')]) {
				const parts = [];
				const name = row.querySelector('[data-type=name]').value;
				const value = row.querySelector('[data-type=value]').value;

				if (name === '') {
					continue;
				}

				parts.push(encodeURIComponent(name.replace(/'/g,'%27').replace(/"/g,'%22')));

				if (value !== '') {
					parts.push(encodeURIComponent(value.replace(/'/g,'%27').replace(/"/g,'%22')));
				}

				fields.push(parts.join('='));
			}

			return fields.join('&');
		};

		static sanitizePairs(pairs) {
			const obj = {variables: [], headers: []};

			for (const value of pairs) {
				obj[value.type].push(value);
			}

			return obj;
		};

		static urlShortener(str, max = URL_MAX_LENGTH) {
			return str.length < max
				? str
				: [
					str.slice(0, Math.floor((max - 3) / 2)),
					'...',
					str.slice(- Math.ceil((max - 3) / 2))
				].join('');
		};
	};
</script>
