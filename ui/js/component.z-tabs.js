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
						background-color: #ffffff;
						border: 1px solid #dfe4e7;
						padding: 10px;
					}

					ul {
						margin: -10px -10px 10px;
						padding: 0;
						height: 30px;
						list-style: none;
						border-bottom: 1px solid #ebeef0;
						background-color: white;
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
						color: #0275b8;
						background-color: white;
						padding: 8px 10px 6px;
						cursor: pointer;
						transition: background-color .2s ease-out;
					}

					button:focus {
						outline: 1px dotted black;
						z-index: 1;
					}

					button[aria-selected=true] {
						border-bottom: 3px solid #0275b8;
						background-color: transparent;
						cursor: default;
						color: #1f2c33;
					}

					button[aria-selected=false]:not([disabled]):hover {
						background-color: #e8f5ff;
					}

					button:disabled {
						cursor: default;
						background-color: transparent;
						color: gray;
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
		return Array.from(this.children);
	}

	_getTabButton(index) {
		return this.shadowRoot.querySelector(`button[data-tab-index="${index}"]`);
	}

	_getTabButtons() {
		return this.shadowRoot.querySelectorAll('button[data-tab-index]');
	}

	_selectTab(index) {
		this._reset();
		this._getTabs()[index].hidden = false;
		const button = this._getTabButton(index);

		button.setAttribute('aria-selected', true);
		button.removeAttribute('tabindex');
	}

	// save tab state in cookie
	_initStartingPosition() {
		if (this.activeTab === null) {
			this.activeTab = 0;
		}
		else {
			this._selectTab(this.activeTab);
		}
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

	// apply delay, skip disabled tabs.
	_onKeyDown(event) {
		if (event.target.getAttribute('role') !== 'tab') {
			return;
		}

		if (event.altKey) {
			return;
		}

		let tab_count = this._getTabs().length;
		let next_tab;

		switch (event.keyCode) {
			case KEYCODE.RIGHT:
			case KEYCODE.DOWN:
				next_tab = (this.activeTab + 1) % tab_count;
				break;

			case KEYCODE.LEFT:
			case KEYCODE.UP:
				next_tab = (tab_count + this.activeTab - 1) % tab_count;
				break;

			case KEYCODE.HOME:
				next_tab = 0;
				break;

			case KEYCODE.END:
				next_tab = tab_count - 1;
				break;
			default:
				return;
		}

		this._getTabButton(next_tab).focus();
		this.activeTab = next_tab;
	}
}

customElements.define('z-tabs', ZTabs);
