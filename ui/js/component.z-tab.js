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

class ZTab extends HTMLElement {
	constructor() {
		super();

		this.attachShadow({ mode: "open" });

		this.shadowRoot.appendChild(this._getTemplate().content.cloneNode(true));
	}

	get disabled() {
		return this.hasAttribute('disabled');
	}

	set disabled(disabled) {
		if (disabled) {
			this.setAttribute('disabled', '');
		}
		else {
			this.removeAttribute('disabled');
		}
	}

	_getTemplate() {
		const template = document.createElement("template");

		template.innerHTML = `
			<style>
				:host {
					contain: content;
				}
			</style>
			<slot></slot>
		`;

		return template;
	}

	connectedCallback() {
		if (!this.hasAttribute('title') || this.title === '') {
			throw Error('Tab title attribute is required');
		}
		this._manageHidden();
		this.setAttribute("role", "tabpanel");
	}

	static get observedAttributes() {
		return ["hidden"];
	}

	attributeChangedCallback(name, oldValue, newValue) {
		if (name === "hidden") {
			this._manageHidden();
		}
	}

	_manageHidden() {
		this.setAttribute("aria-disabled", this.hidden);
	}
}

customElements.define("z-tab", ZTab);
