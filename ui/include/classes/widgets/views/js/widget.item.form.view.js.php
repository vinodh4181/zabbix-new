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
?>


window.widget_item_form = new class {

	init({thresholds_colors}) {
		this.form = document.getElementById('widget-dialogue-form');

		this.show_description = document.getElementById(`show_${<?= WIDGET_ITEM_SHOW_DESCRIPTION ?>}`);
		this.show_value = document.getElementById(`show_${<?= WIDGET_ITEM_SHOW_VALUE ?>}`);
		this.show_time = document.getElementById(`show_${<?= WIDGET_ITEM_SHOW_TIME ?>}`);
		this.show_change_indicator = document.getElementById(`show_${<?= WIDGET_ITEM_SHOW_CHANGE_INDICATOR ?>}`);

		this.advance_configuration = document.getElementById('adv_conf');
		this.units_show = document.getElementById('units_show');

		jQuery('#itemid').on('change', () => this.updateWarningIcon());

		for (const colorpicker of this.form.querySelectorAll('.<?= ZBX_STYLE_COLOR_PICKER ?> input')) {
			$(colorpicker).colorpicker({
				appendTo: ".overlay-dialogue-body",
				use_default: !colorpicker.name.includes('thresholds'),
				onUpdate: ['up_color', 'down_color', 'updown_color'].includes(colorpicker.name)
					? (color) => this.setIndicatorColor(colorpicker.name, color)
					: null
			});
		}

		const show = [this.show_description, this.show_value, this.show_time, this.show_change_indicator];

		for (const checkbox of show) {
			checkbox.addEventListener('change', (e) => {
				if (show.filter((checkbox) => checkbox.checked).length > 0) {
					this.updateForm();
				}
				else {
					e.target.checked = true;
				}
			});
		}

		for (const checkbox of [this.advance_configuration, this.units_show]) {
			checkbox.addEventListener('change', () => this.updateForm());
		}

		colorPalette.setThemeColors(thresholds_colors);

		this.updateForm();
		this.updateWarningIcon();
	}

	updateForm() {
		const show_description_row = this.advance_configuration.checked && this.show_description.checked;
		const show_value_row = this.advance_configuration.checked && this.show_value.checked;
		const show_time_row = this.advance_configuration.checked && this.show_time.checked;
		const show_change_indicator_row = this.advance_configuration.checked && this.show_change_indicator.checked;
		const show_bg_color_row = this.advance_configuration.checked;
		const show_thresholds_row = this.advance_configuration.checked;

		for (const element of this.form.querySelectorAll('.js-row-description')) {
			element.style.display = show_description_row ? '' : 'none';
		}
		for (const element of this.form.querySelectorAll('.js-row-description input, .js-row-description textarea')) {
			element.disabled = !show_description_row;
		}

		for (const element of this.form.querySelectorAll('.js-row-value')) {
			element.style.display = show_value_row ? '' : 'none';
		}
		for (const element of this.form.querySelectorAll('.js-row-value input')) {
			element.disabled = !show_value_row;
		}
		for(const element of document.querySelectorAll('#units, #units_pos, #units_size, #units_bold, #units_color')) {
			element.disabled = !show_value_row || !this.units_show.checked;
		}

		for (const element of this.form.querySelectorAll('.js-row-time')) {
			element.style.display = show_time_row ? '' : 'none';
		}
		for (const element of this.form.querySelectorAll('.js-row-time input')) {
			element.disabled = !show_time_row;
		}

		for (const element of this.form.querySelectorAll('.js-row-change-indicator')) {
			element.style.display = show_change_indicator_row ? '' : 'none';
		}
		for (const element of this.form.querySelectorAll('.js-row-change-indicator input')) {
			element.disabled = !show_change_indicator_row;
		}

		for (const element of this.form.querySelectorAll('.js-row-bg-color')) {
			element.style.display = show_bg_color_row ? '' : 'none';
		}
		for (const element of this.form.querySelectorAll('.js-row-bg-color input')) {
			element.disabled = !show_bg_color_row;
		}

		for (const element of this.form.querySelectorAll('.js-row-thresholds')) {
			element.style.display = show_thresholds_row ? '' : 'none';
		}
		for (const element of this.form.querySelectorAll('.js-row-thresholds input')) {
			element.disabled = !show_thresholds_row;
		}
	}

	setIndicatorColor(name, color) {
		const indicator_ids = {
			up_color: 'change-indicator-up',
			down_color: 'change-indicator-down',
			updown_color: 'change-indicator-updown'
		};

		document.getElementById(indicator_ids[name])
			.querySelector("polygon").style.fill = (color !== '') ? `#${color}` : '';
	}

	updateWarningIcon() {
		document.getElementById('item-value-thresholds-warning').style.display = 'none';

		const ms_item_data = $('#itemid').multiSelect('getData');

		if (ms_item_data.length > 0) {
			const curl = new Curl('jsrpc.php', false);
			curl.setArgument('method', 'item_value_type.get');
			curl.setArgument('type', <?= PAGE_TYPE_TEXT_RETURN_JSON ?>);
			curl.setArgument('itemid', ms_item_data[0].id);

			fetch(curl.getUrl())
				.then((response) => response.json())
				.then((response) => {
					switch (response.result) {
						case '<?= ITEM_VALUE_TYPE_FLOAT ?>':
						case '<?= ITEM_VALUE_TYPE_UINT64 ?>':
							document.getElementById('item-value-thresholds-warning').style.display = 'none';
							break;
						default:
							document.getElementById('item-value-thresholds-warning').style.display = '';
					}
				})
				.catch((exception) => {
					console.log('Could not get value data type of the item:', exception);
				});
		}
	}
};
