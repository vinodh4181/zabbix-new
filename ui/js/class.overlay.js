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


/**
 * Overlay object DOM node to be mounted before document body closing tag.
 *
 * @param {string} type
 * @param {string} (optional) dialogueid
 *
 * @prop {jQuery} $dialog
 * @prop {jQuery} $backdrop
 * @prop {string} type
 * @prop {string} headerid
 */
function Overlay(type, dialogueid) {
	this.type = type;
	this.dialogueid = dialogueid || overlays_stack.getNextId();
	this.headerid = 'dashboard-widget-head-title-' + this.dialogueid;
	this.$backdrop = jQuery('<div>', {
		'class': 'overlay-bg',
		'data-dialogueid': this.dialogueid
	});

	this.$dialog = jQuery('<div>', {
		'class': 'overlay-dialogue modal',
		'data-dialogueid': this.dialogueid,
		'role': 'dialog',
		'aria-modal': 'true',
		'aria-labeledby': this.headerid
	});

	var $close_btn = jQuery('<button>', {
			class: 'overlay-close-btn',
			title: t('S_CLOSE')
		})
		.click(function(e) {
			overlayDialogueDestroy(this.dialogueid);
			e.preventDefault();
		}.bind(this));

	this.$dialog.append($close_btn);

	this.$dialog.$header = jQuery('<h4>', {id: this.headerid});
	this.$dialog.$controls = jQuery('<div>', {class: 'overlay-dialog-controls'});
	this.$dialog.$body = jQuery('<div>', {class: 'overlay-dialog-body'});
	this.$dialog.$debug = jQuery('<pre>', {class: 'debug-output'});
	this.$dialog.$footer = jQuery('<div>', {class: 'overlay-dialog-footer'});
	this.$dialog.$script = jQuery('<script>');

	this.$dialog.append(jQuery('<div>', {class: 'dashboard-widget-head'}).append(this.$dialog.$header));
	this.$dialog.append(this.$dialog.$body);
	this.$dialog.append(this.$dialog.$footer);

	this.center_dialog_function = this.centerDialog.bind(this);

	var body_mutation_observer = window.MutationObserver || window.WebKitMutationObserver;
	this.body_mutation_observer = new body_mutation_observer(this.center_dialog_function);

	jQuery(window).resize(function() {
		this.$dialog.is(':visible') && this.centerDialog();
	}.bind(this));

	this.setProperties({
		content: jQuery('<div>', {'height': '68px', class: 'is-loading'})
	});
}

/**
 * Centers the $dialog.
 */
Overlay.prototype.centerDialog = function() {
	var body_scroll_height = this.$dialog.$body[0].scrollHeight,
		body_height = this.$dialog.$body.innerHeight();

	if (body_height != Math.floor(body_height)) {
		// The body height is often about a half pixel less than the height.
		body_height = Math.floor(body_height) + 1;
	}

	// A fix for IE and Edge to stop popup width flickering when having vertical scrollbar.
	this.$dialog.$body.css('overflow-y', body_scroll_height > body_height ? 'scroll' : 'hidden');

	// Allow full width to determine actual width taken by the contents.
	this.$dialog.css({
		'left': 0,
		'top': 0
	});

	this.$dialog.css({
		'left': Math.max(0, parseInt((jQuery(window).width() - this.$dialog.outerWidth(true)) / 2)) + 'px',
		'top': this.$dialog.hasClass('sticked-to-top')
			? ''
			: Math.max(0, parseInt((jQuery(window).height() - this.$dialog.outerHeight(true)) / 2)) + 'px'
	});

	var size = {
			width: this.$dialog.$body[0].scrollWidth,
			height: this.$dialog.$body[0].scrollHeight
		},
		size_saved = this.$dialog.data('size') || size;

	if (JSON.stringify(size) !== JSON.stringify(size_saved)) {
		this.$dialog.trigger('overlay-dialog-resize', [size, size_saved]);
	}

	this.$dialog.data('size', size);
};

/**
 * Determines element to place focus on and focuses it if found.
 */
Overlay.prototype.recoverFocus = function() {
	if (this.$btn_focus) {
		this.$btn_focus.focus();
		return;
	}

	if (jQuery('[autofocus=autofocus]', this.$dialog).length) {
		jQuery('[autofocus=autofocus]', this.$dialog).first().focus();
	}
	else if (jQuery('.overlay-dialog-body form :focusable', this.$dialog).length) {
		jQuery('.overlay-dialog-body form :focusable', this.$dialog).first().focus();
	}
	else {
		jQuery(':focusable:first', this.$dialog).focus();
	}
};

/**
 * Binds keyboard events to contain focus within dialog window.
 */
Overlay.prototype.containFocus = function() {
	var focusable = jQuery(':focusable', this.$dialog);

	if (focusable.length > 1) {
		var first_focusable = focusable.filter(':first'),
			last_focusable = focusable.filter(':last');

		first_focusable
			.off('keydown')
			.on('keydown', function(e) {
				// TAB and SHIFT
				if (e.which == 9 && e.shiftKey) {
					last_focusable.focus();
					return false;
				}
			});

		last_focusable
			.off('keydown')
			.on('keydown', function(e) {
				// TAB and not SHIFT
				if (e.which == 9 && !e.shiftKey) {
					first_focusable.focus();
					return false;
				}
			});
	}
	else {
		focusable
			.off('keydown')
			.on('keydown', function(e) {
				if (e.which == 9) {
					return false;
				}
			});
	}
};

/**
 * Sets dialog in loading sate.
 */
Overlay.prototype.setLoading = function() {
	this.$dialog.$body.addClass('is-loading');
	this.$dialog.$controls.find('z-select, button').prop('disabled', true);
	this.$btn_submit && this.$btn_submit.prop('disabled', true);
};

/**
 * Sets dialog in idle sate.
 */
Overlay.prototype.unsetLoading = function() {
	this.$dialog.$body.removeClass('is-loading');
	this.$btn_submit && this.$btn_submit.removeClass('is-loading').prop('disabled', false);
};

/**
 * @param {string} action
 * @param {array|object} options (optional)
 *
 * @return {jQuery.XHR}
 */
Overlay.prototype.load = function(action, options) {
	var url = new Curl('zabbix.php');
	url.setArgument('action', action);

	// Properties 'action' and 'options' are stored to enable popup reload. This may be done outside the class.
	this.action = action;
	this.options = options;

	if (this.xhr) {
		this.xhr.abort();
	}

	this.setLoading();
	this.xhr = jQuery.ajax({
		url: url.getUrl(),
		type: 'post',
		dataType: 'json',
		data: options
	});

	this.xhr.always(function() {
		this.unsetLoading();
	}.bind(this));

	return this.xhr;
};

/**
 * Removes associated nodes from DOM.
 */
Overlay.prototype.unmount = function() {
	this.cancel_action && this.cancel_action();

	jQuery.unsubscribe('debug.click', this.center_dialog_function);

	this.$backdrop.remove();
	this.$dialog.remove();

	this.body_mutation_observer.disconnect();

	var $wrapper = jQuery('.wrapper');

	if (!jQuery('[data-dialogueid]').length) {
		$wrapper.css('overflow', $wrapper.data('overflow'));
		$wrapper.removeData('overflow');
	}
};

/**
 * Appends associated nodes to document body.
 */
Overlay.prototype.mount = function() {
	var $wrapper = jQuery('.wrapper');

	if (!jQuery('[data-dialogueid]').length) {
		$wrapper.data('overflow', $wrapper.css('overflow'));
		$wrapper.css('overflow', 'hidden');
	}

	this.$backdrop.appendTo($wrapper);
	this.$dialog.appendTo($wrapper);

	this.body_mutation_observer.observe(this.$dialog[0], {childList: true, subtree: true});
	this.centerDialog();

	jQuery.subscribe('debug.click', this.center_dialog_function);
};

/**
 * @param {object} obj
 *
 * @return {jQuery}
 */
Overlay.prototype.makeButton = function(obj) {
	var $button = jQuery('<button>', {
			type: 'button',
			text: obj.title
		});

	$button.on('click', function(e) {
		if (('confirmation' in obj) && !confirm(obj.confirmation)) {
			e.preventDefault();
			return;
		}

		if (obj.isSubmit && this.$btn_submit) {
			this.$btn_submit.blur().addClass('is-loading');
		}

		if (obj.action && obj.action(this) !== false) {
			this.cancel_action = null;

			if (!obj.keepOpen) {
				overlayDialogueDestroy(this.dialogueid);
			}
		}

		e.preventDefault();
	}.bind(this));

	if (obj.class) {
		$button.addClass(obj.class);
	}

	if (obj.enabled === false) {
		$button.prop('disabled', true);
	}

	return $button;
};

/**
 * @param {array} arr
 *
 * @return {array}
 */
Overlay.prototype.makeButtons = function(arr) {
	var buttons = [];

	this.$btn_submit = null;
	this.$btn_focus = null;

	arr.forEach(function(obj) {
		if (typeof obj.action === 'string') {
			obj.action = new Function('overlay', obj.action);
		}

		var $button = this.makeButton(obj);

		if (obj.cancel) {
			this.cancel_action = obj.action;
		}

		if (obj.isSubmit) {
			this.$btn_submit = $button;
		}

		if (obj.focused) {
			this.$btn_focus = $button;
		}

		buttons.push($button);
	}.bind(this));

	return buttons;
};

/**
 * @param {string} key
 */
Overlay.prototype.unsetProperty = function(key) {
	switch (key) {
		case 'title':
			this.$dialog.$header.text('');
			break;

		case 'buttons':
			this.$dialog.$footer.find('button').remove();
			break;

		case 'content':
			this.$dialog.$body.html('');
			if (this.$dialog.$debug.html().length) {
				this.$dialog.$body.append(this.$dialog.$debug);
			}
			break;

		case 'controls':
			this.$dialog.$controls.remove();
			break;

		case 'debug':
			this.$dialog.$debug.remove();
			break;

		case 'script_inline':
			this.$dialog.$script.remove();
			break;
	}
};

/**
 * Evaluates and applies properties.
 *
 * @param {object} obj
 */
Overlay.prototype.setProperties = function(obj) {
	for (var key in obj) {
		if (!obj[key]) {
			this.unsetProperty(key);
			continue;
		}

		switch (key) {
			case 'class':
				this.$dialog.addClass(obj[key]);
				break;

			case 'title':
				this.$dialog.$header.text(obj[key]);
				break;

			case 'buttons':
				this.unsetProperty(key);
				this.$dialog.$footer.append(this.makeButtons(obj[key]));
				break;

			case 'footer':
				this.unsetProperty(key);
				this.$dialog.$footer.append(obj[key]);
				break;

			case 'content':
				this.$dialog.$body.html(obj[key]);
				if (this.$dialog.$debug.html().length) {
					this.$dialog.$body.append(this.$dialog.$debug);
				}
				break;

			case 'controls':
				this.$dialog.$controls.html(obj[key]);
				this.$dialog.$body.before(this.$dialog.$controls);
				break;

			case 'debug':
				this.$dialog.$debug.html(jQuery(obj[key]).html());
				this.$dialog.$body.append(this.$dialog.$debug);
				break;

			case 'script_inline':
				this.unsetProperty(key);
				// See: jQuery.html() rnoInnerhtml = /<script|<style|<link/i
				// If content matches this regex it will be parsed in jQuery.buildFragment as HTML, but here we have JS.
				this.$dialog.$script.get(0).innerHTML = obj[key];
				this.$dialog.$footer.prepend(this.$dialog.$script);
				break;

			case 'element':
				this.element = obj[key];
				break;

			case 'data':
				this.data = obj[key];
				break;
		}
	}

	// Hijack form.
	this.$btn_submit && this.$dialog.$body.find('form').on('submit', function(e) {
		e.preventDefault();
		this.$btn_submit.trigger('click');
	}.bind(this));
};
