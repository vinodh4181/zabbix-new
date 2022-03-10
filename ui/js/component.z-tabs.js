/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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
const KEYCODE = {
	DOWN: 40,
	LEFT: 37,
	RIGHT: 39,
	UP: 38,
	HOME: 36,
	END: 35,
};

class ZTabs extends HTMLElement {
	constructor() {
		super();
		this.attachShadow({ mode: 'open' });
		this.shadowRoot.appendChild(this._getTemplate().content.cloneNode(true));
		this._idPrefix = 't' + Math.random().toString(36).substring(2, 6);
		this._onKeyDown = this._onKeyDown.bind(this);
	}

	get activeTab() {
		return this.getAttribute('active-tab');
	}

	set activeTab(newValue) {
		this.setAttribute('active-tab', newValue);
	}

	static get observedAttributes() {
		return ['active-tab'];
	}

	attributeChangedCallback(name, oldValue, newValue) {
		if (oldValue === newValue) {
			return
		}
		switch (name) {
			case 'active-tab':
				if (this._getTabButton(newValue)) {
					this._selectTab(newValue);
				}
				break;

			default:
				return;
		}
	}

	connectedCallback() {
		setTimeout(() => {
			Promise.all([
				customElements.whenDefined('z-tab')
			]).then(() => {
				this._renderTabsNav();
				this._initStartingPosition();
			});
		});
	}

	_getTemplate() {
		const template = document.createElement('template');

		template.innerHTML = `
			<style>
				:host {
					contain: content;
					box-sizing: border-box;
				}

				.component-container {
					margin: 0 0 10px;
					background-color: var(--component-bg-color);
					border: 1px solid var(--component-border-color);
					padding: 10px;
				}

				ul {
					margin: -10px -10px 10px;
					padding: 0;
					height: 30px;
					list-style: none;
					border-bottom: 1px solid var(--component-table-border-color);
					background-color: var(--component-bg-color);
				}

				li {
					display: inline-block;
				}

				button {
					position: relative;
					outline: none;
					font: inherit;
					height: 30px;
					line-height: 0;
					border: none;
					color: var(--component-link-color, blue);
					background-color: var(--component-bg-color);
					padding: 8px 10px 6px;
					cursor: pointer;
					transition: background-color .2s ease-out;
				}

				button:focus {
					outline: 1px dotted var(--component-font-color);
					z-index: 1;
				}

				button[aria-selected=true] {
					border-bottom: 3px solid var(--component-tab-bg-selected-color);
					background-color: transparent;
					cursor: default;
					color: var(--component-font-color);
				}

				button[aria-selected=false]:not([disabled]):hover {
					background-color: var(--component-hover-color);
				}

				button:disabled {
					cursor: default;
					background-color: transparent;
					color: var(--component-disabled-font-color);
					border: none;
				}
			</style>
			<div class="component-container">
				<ul class="tabs" role=tablist>

				</ul>

				<slot></slot>
			</div>
		`;

		return template;
	}

	_getTabs() {
		return Array.from(this.children).filter((element) => element.tagName.toLowerCase() === 'z-tab');
	}

	_getTabButton(index) {
		return this.shadowRoot.querySelector(`button[data-tab-index="${index}"]`);
	}

	_getTabButtons() {
		return Array.from(this.shadowRoot.querySelectorAll('button[data-tab-index]'));
	}

	_selectTab(index) {
		this._reset();
		this._getTabs()[index].hidden = false;
		const button = this._getTabButton(index);

		button.setAttribute('aria-selected', true);
		button.removeAttribute('tabindex');
	}

	_initStartingPosition() {
		let init_position = this._findNextTab(this.activeTab || 0, true);

		if (this.activeTab == init_position) {
			this._selectTab(init_position);
		}

		this.activeTab = init_position;
	}

	_reset() {
		this._getTabs().forEach((tab) => tab.hidden = true);
		this._getTabButtons().forEach((button) => {
			button.setAttribute('aria-selected', false);
			button.setAttribute('tabindex', '-1');
		});
	}

	_renderTabsNav() {
		const nav_container = this.shadowRoot.querySelector('.tabs');
		this._getTabs().forEach((tab, index) => {
			if (tab.tagName.toLowerCase() !== 'z-tab') {
				return;
			}
			const tab_button = this._createButtonElement(tab, index);

			tab_button.firstElementChild.addEventListener('click', (event) => {
				this.activeTab = event.target.dataset.tabIndex;
			});
			nav_container.appendChild(tab_button);
			nav_container.addEventListener('keydown', this._onKeyDown);
		});
	}

	_createButtonElement(panel, index) {
		const tab_panel_id = this._idPrefix + '_tab_panel_' + index;
		const tab_button_id = this._idPrefix + '_tab_' + index;
		const tab_button = document.createElement('button');
		const button_item = document.createElement('li');

		panel.setAttribute('aria-labelledby', tab_button_id);
		panel.id = tab_panel_id;

		button_item.setAttribute('role', 'presentation');

		tab_button.innerText = panel.title;
		tab_button.setAttribute('id', tab_button_id);
		tab_button.setAttribute('data-tab-index', index);
		tab_button.setAttribute('role', 'tab');
		tab_button.setAttribute('aria-controls', tab_panel_id);
		tab_button.setAttribute('aria-selected', (index == this.activeTab));

		if (panel.disabled) {
			tab_button.setAttribute('disabled', '');
			tab_button.setAttribute('aria-disabled', 'true');
		}

		button_item.appendChild(tab_button);

		return button_item;
	}

	_findNextTab(index, forward) {
		let valid_index = this._adjustIndex(index);

		if (!this._getTabButtons().find((button) => !button.disabled)) {
			return 0;
		}

		while (this._getTabButton(valid_index).disabled) {
			valid_index = (forward ? valid_index + 1 : valid_index - 1);
			valid_index = this._adjustIndex(valid_index);
		}

		return valid_index;
	}

	_adjustIndex(index) {
		let length = this._getTabs().length;

		return (index + length) % length;
	}

	_onKeyDown(event) {
		if (event.target.getAttribute('role') !== 'tab' || event.altKey) {
			return;
		}

		let tab_count = this._getTabs().length;
		let focus_tab = this.shadowRoot.activeElement.dataset.tabIndex;
		let forward = true;

		switch (event.keyCode) {
			case KEYCODE.RIGHT:
			case KEYCODE.DOWN:
				focus_tab++;
				break;

			case KEYCODE.LEFT:
			case KEYCODE.UP:
				forward = false;
				focus_tab--;
				break;

			case KEYCODE.HOME:
				focus_tab = 0;
				break;

			case KEYCODE.END:
				forward = false;
				focus_tab = tab_count - 1;
				break;
			default:
				return;
		}

		event.preventDefault();
		clearTimeout(this.activating);

		let next_tab = this._findNextTab(focus_tab, forward);
		this._getTabButton(next_tab).focus();

		if (!event.ctrlKey && !event.metaKey) {
			this.activating = setTimeout(() => {
				this.activeTab = next_tab;
			}, 300);
		}
	}
}

customElements.define('z-tabs', ZTabs);
