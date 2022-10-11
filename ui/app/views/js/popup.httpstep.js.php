<?php declare(strict_types = 0);
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
'use strict';

window.http_step_popup = new class {

	init({data}) {
		this.data = data;
		this.form = document.getElementById('http_step');
		this.row_id = 1;

		this.pairs = {
			query_fields: null,
			post_fields: null,
			variables: null,
			headers: null
		};

		this.overlay = overlays_stack.getById('http_step_edit');

		const tables = this.form.querySelectorAll('.httpconf-dynamic-table');

		for (const elem of tables) {
			const type = elem.dataset.type;

			if (!(type in this.data.pairs)) {
				this.data.pairs[type] = [{index: this.row_id, type: type, name: '', value: ''}];
			}

			jQuery(elem)
				.dynamicRows({
					template: '#scenario-pair-row-tmpl',
					rows: this.data.pairs[type],
					counter: 0,
					dataCallback: (data) => {
						return {...data, ...{type: type, index: this.row_id++}};
					}
				})
				.on('afteradd.dynamicRows', (e) => {
					if (type === 'variables') {
						e.target.querySelector('.' + ZBX_STYLE_DRAG_ICON).remove();
					}

					if (type === 'variables' || type === 'headers' || type === 'post_fields') {
						e.target.querySelector('[data-type="value"]').setAttribute('maxlength', 2000);
					}
				});

			if (type === 'variables') {
				[...elem.querySelectorAll('.' + ZBX_STYLE_DRAG_ICON)].map((elem) => elem.remove());
			}

			jQuery(elem)
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
					}
				});

			this.pairs[type] = elem;
		};

		this.radio_retrieve_mode = document.getElementById('retrieve_mode');
		this.textarea_raw_post = document.getElementById('posts');
		this.radio_post_type = document.getElementById('post_type');

		[...this.radio_post_type.querySelectorAll('input')].map((elem) => {
			elem.addEventListener('change', () => this._update());
		});

		[...this.radio_retrieve_mode.querySelectorAll('input')].map((elem) => {
			elem.addEventListener('change', () => this._update());
		});

		this.input_url = document.getElementById('url');

		this._update();
	}

	_update() {
		const is_disabled = this.radio_retrieve_mode.querySelector('input:checked').value == HTTPTEST_STEP_RETRIEVE_MODE_HEADERS;

		this.textarea_raw_post.disabled = is_disabled;
		[...this.radio_post_type.querySelectorAll('input')].map((input) => {
			input.disabled = is_disabled;
		});

		const is_raw = this.radio_post_type.querySelector('input:checked').value == ZBX_POSTTYPE_RAW;

		[...this.form.querySelectorAll('.js-raw-post')].map((elem) => elem.style.display = is_raw ? 'block' : 'none');
		[...this.form.querySelectorAll('.js-post-fields')].map((elem) => elem.style.display = is_raw ? 'none' : 'block');

		if (is_raw) {
			this.textarea_raw_post.value = ScenarioHelper.parsePostPairsToRaw(this.pairs.post_fields);
		}
		else {
			if (this.textarea_raw_post.value != '') {
				this.data.pairs.post_fields = ScenarioHelper.parsePostRawToPairs(this.textarea_raw_post.value);

				// clear table
				[...document.querySelectorAll('.httpconf-dynamic-table[data-type=post_fields] .form_row')].map(
					elem => elem.remove()
				);

				// clear event
				jQuery(document.querySelector('.httpconf-dynamic-table[data-type=post_fields]')).off();

				jQuery(document.querySelector('.httpconf-dynamic-table[data-type=post_fields]'))
					.dynamicRows({
						template: '#scenario-pair-row-tmpl',
						rows: this.data.pairs.post_fields,
						counter: 0,
						dataCallback: (data) => {
							return {...data, ...{type: 'post_fields', index: this.row_id++}};
						}
					})
			}
		}

		// jQuery(this.pairs.post_fields).sortable('option', 'disabled', is_disabled);
		this.pairs.post_fields.classList.toggle('disabled', is_disabled);

		[...this.pairs.post_fields.querySelectorAll('input, button')].map((elem) => elem.disabled = is_disabled);

		if (!is_disabled) {
			jQuery(this.pairs.post_fields).trigger('tableupdate.dynamicRows', this);
		}
	}

	submit() {
		const fields = getFormFields(this.form);

		fields.name = fields.name.trim();
		if ('posts' in fields) {
			fields.posts = fields.posts.trim();
		}
		fields.timeout = fields.timeout.trim();
		fields.url = fields.url.trim();
		fields.required = fields.required.trim();

		const pairs = [];

		for (const value of Object.values(fields.pairs)) {
			value.name = value.name.trim();
			value.value = value.value.trim();

			pairs.push(value);
		}

		for (const value of Object.values(fields.query_fields)) {
			value.name = value.name.trim();
			value.value = value.value.trim();
			value.type = 'query_fields';

			pairs.push(value);
		}

		fields.pairs = pairs;

		for (const elem of this.form.parentNode.children) {
			if (elem.matches('.msg-good, .msg-bad, .msg-warning')) {
				elem.parentNode.removeChild(elem);
			}
		}

		this.overlay.setLoading();

		const curl = new Curl('zabbix.php', false);
		curl.setArgument('action', 'popup.httpstep');

		this._post(curl.getUrl(), fields, (response) => {
			overlayDialogueDestroy(this.overlay.dialogueid);
			this.overlay.$dialogue[0].dispatchEvent(new CustomEvent('dialogue.submit', {detail: response.params}));
		});
	}

	_post(url, data, success_callback) {
		fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		})
			.then((response) => response.json())
			.then((response) => {
				if ('error' in response) {
					throw {error: response.error};
				}

				return response;
			})
			.then(success_callback)
			.catch((exception) => {
				for (const element of this.form.parentNode.children) {
					if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
						element.parentNode.removeChild(element);
					}
				}

				let title, messages;

				if (typeof exception === 'object' && 'error' in exception) {
					title = exception.error.title;
					messages = exception.error.messages;
				}
				else {
					messages = [<?= json_encode(_('Unexpected server error.')) ?>];
				}

				const message_box = makeMessageBox('bad', messages, title)[0];

				this.form.parentNode.insertBefore(message_box, this.form);
			})
			.finally(() => {
				this.overlay.unsetLoading();
			});
	}

	parseUrl() {
		const table = jQuery('.js-tbl-editable').data('editableTable');
		const url = parseUrlString(this.input_url.value);

		if (typeof url === 'object') {
			if (url.pairs.length) {
				table.addRows(url.pairs);
				table.getTableRows()
					.map(function() {
						const empty = $(this).find('input[type="text"]').map(function() {
							return ($(this).val() === '') ? this : null;
						});

						return (empty.length == 2) ? this : null;
					})
					.map(function() {
						table.removeRow(this);
					});
			}

			this.input_url.value = url.url;
		}
		else {
			overlayDialogue({
				'title': <?= json_encode(_('Error')); ?>,
				'class': 'modal-popup position-middle',
				'content': $('<span>').html(<?=
					json_encode(_('Failed to parse URL.').'<br><br>'._('URL is not properly encoded.'));
				?>),
				'buttons': [
					{
						title: <?= json_encode(_('Ok')); ?>,
						class: 'btn-alt',
						focused: true,
						action: function() {}
					}
				]
			}, e.target);
		}
	}
};
