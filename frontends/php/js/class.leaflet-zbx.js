function LeafletZBX(data) {
	this.map = L.map('map', {center: [56.955105, 24.216532], zoom: 5});//.on('click', this.mapOnClick);
	//this.map = L.map('map', {center: [56.950176, 24.104731], zoom: 17});//.on('click', this.mapOnClick);
	this.elements = L.featureGroup().addTo(this.map);//.on('click', this.elementsOnClick);
	this.icons = {
		151: L.icon({
			iconUrl: 'imgstore.php?iconid=151',
			iconSize: [72, 96],
			iconAnchor: [36, 96]
		}),
		148: L.icon({
			iconUrl: 'imgstore.php?iconid=148',
			iconSize: [18, 24],
			iconAnchor: [9, 12]
		})
	};

	// Add tile layer.
	L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 18}).addTo(this.map);
	//L.tileLayer(data.tile, {maxZoom: 18}).addTo(this.map);

	// Add elements.
	this.createElementsLayer(data.elements).addTo(this.elements);

	// Init min-severity acontrol.
	this.addMinSeverityControl();

	// Draw some useless shapes.
	this.addShapes();

	// Init updater.
	setInterval(this.update.bind(this), 5000);
}

LeafletZBX.prototype.addShapes = function() {
	L.circle([57.005581, 24.180795], {
		color: 'red',
		fillColor: '#f03',
		fillOpacity: 0.5,
		radius: 500
	}).addTo(this.map);
};

LeafletZBX.prototype.addMinSeverityControl = function() {
	var control = L.control(),
		severityLevels = {
			0: {name: 'Not classified', style: 'na-bg'},
			1: {name: 'Information', style: 'info-bg'},
			2: {name: 'Warning', style: 'warning-bg'},
			3: {name: 'Average', style: 'average-bg'},
			4: {name: 'High', style: 'high-bg'},
			5: {name: 'Disaster', style: 'disaster-bg'}
		};

	control.onAdd = function() {
		var _div = L.DomUtil.create('div'),
			_btn = L.DomUtil.create('button', null, _div).setStyle({'background': '#0275b8'}),
			_list = L.DomUtil.create('div', 'overlay-dialogue', _div);

		_btn.addEventListener('click', function(e) {
			_list.show();
		});
		_div.addEventListener('mouseleave', function(e) {
			_list.hide();
		});

		_list.setStyle({'right': '0px'}).hide();

		var list = L.DomUtil.create('ul', 'notif-body', _list),
			li = L.DomUtil.create('li', null, list);

		li.innerHTML = 'Minimal severity:';
		li.setStyle({'padding-left': '0px'});
		for (var i in severityLevels) {
			var li = L.DomUtil.create('li', null, list),
				h4 = L.DomUtil.create('h4', null, li);
			L.DomUtil.create('div', 'notif-indic '+severityLevels[i].style, li).setStyle({'margin-top': '-16px'});
			h4.innerHTML = severityLevels[i].name;
			li.dataSeverity = i;

			li.addEventListener('click', function(e) {
				console.log(this.dataSeverity);
			});
		}

		return _div;
	};

	control.addTo(this.map);
};

LeafletZBX.prototype.createElementsLayer = function(elements) {
	var obj = this;

	return L.geoJSON(elements, {
		layerName: 'map-elements',
		pointToLayer: function (feature, latlng) {
			return L.marker(latlng, {
				icon: obj.getElementIcon(feature)
			});
		}
	});
};

LeafletZBX.prototype.updateMapElements = function(elmnts) {
	var url = new Curl('zabbix.php', true),
		obj = this;

	url.setArgument('action', 'widget.dev1076a.update');

	jQuery.ajax({
		url: url.getUrl(),
		data: elmnts,
		method: 'POST',
		dataType: 'json',
		success: function(response) {
			obj.elements.getLayers().forEach(function(layer){
				if (layer.options.layerName === 'map-elements') {
					layer.getLayers().forEach(function(elmnt) {
						var hostid = elmnt.feature.properties.hostid,
							host = response.hosts[hostid] || {};

						elmnt.feature.properties = jQuery.extend(elmnt.feature.properties, host);
						elmnt.setIcon(obj.getElementIcon(elmnt.feature));//.update();
					});
				}
			});
		}
	});
};

LeafletZBX.prototype.update = function() {
	var hostids = [],
		obj = this;

	this.elements.getLayers().forEach(function(layer){
		if (layer.options.layerName === 'map-elements') {
			layer.getLayers().forEach(function(elmnt) {
				hostids.push(elmnt.feature.properties.hostid);
			});

			if (hostids.length) {
				obj.updateMapElements({
					hostids: hostids
				});
			}
		}
	});
};

LeafletZBX.prototype.getElementIcon = function(feature) {
	var iconid = feature.properties.iconid,
		divIcon = {
			html: '<img src="' + this.icons[iconid].options.iconUrl + '"/>'+
				'<span class="label">' + feature.properties.host + '</span>'
		};

	return new L.DivIcon(jQuery.extend(divIcon, this.icons[iconid]));
};


/**
 * Contextmenu plugin for leaflet maps.
 * It bridges leaflet markers with Zabbix internal context menu system.
 */
(function(factory, window) {
	if (typeof define === 'function' && define.amd) {
		define(['leaflet'], factory);
	}
	else if (typeof exports === 'object') {
		module.exports = factory(require('leaflet'));
	}

	if (typeof window !== 'undefined' && window.L) {
		window.L.ContextMenu = factory(L);
	}
}(function(L) {
	const contextMenuEvent = 'contextmenu';

	L.Map.ContextMenu = L.Handler.extend({
		initialize: function(map) {
			L.Handler.prototype.initialize.call(this, map);

			this._items = [];
			this._visible = false;
			this._container = L.DomUtil.create('ul', 'action-menu action-menu-top', map._container);
			this._container.setStyle({'zIndex': 10000, 'position': 'absolute'});

			this._container.id = Math.random().toString(36).substring(7);
			jQuery(this._map._container).data('menu-popup-id', this._container.id);

			L.DomEvent
				.on(this._container, 'click', L.DomEvent.stop)
				.on(this._container, 'mousedown', L.DomEvent.stop)
				.on(this._container, 'dblclick', L.DomEvent.stop)
				.on(this._container, 'contextmenu', L.DomEvent.stop);
		},
		_makeContextMenu: function(data, event) {
			var marker_event = {
					target: $(event.target._icon),
					originalEvent: {
						detail: 0
					}
				},
				menu = [];

			switch (data.type) {
				case 'map_element_image':
					menu = getMenuPopupMapElementImage(data);
					break;

				case 'host':
					menu = getMenuPopupHost(data, marker_event.target);
					break;

				case 'map_element_trigger':
					menu = getMenuPopupMapElementTrigger(data);
					break;

				case 'map_element_submap':
					menu = getMenuPopupMapElementSubmap(data);
					break;

				case 'map_element_group':
					menu = getMenuPopupMapElementGroup(data);
					break;
			}

			if (menu.length) {
				jQuery(this._map._container).menuPopup([], marker_event);
				jQuery(this._map._container).menuPopup('clearSections');
				jQuery(this._map._container).menuPopup('addSections', menu);
			}
		},
		_hideContextMenu: function() {
			console.log('_hideContextMenu');
		},
		showAt: function(point, data) {
			if (point instanceof L.LatLng) {
				point = this._map.latLngToContainerPoint(point);
			}
			this._showAtPoint(point, data);
		},
		_showAtPoint: function(pt, data) {
			var event = L.extend(data || {}, {contextmenu: this});

			this._showLocation = {
				containerPoint: pt
			};

			if (data && data.relatedTarget){
				this._showLocation.relatedTarget = data.relatedTarget;
			}

			this._setPosition(pt);

			if (!this._visible) {
				this._container.style.display = 'block';
				this._visible = true;
			}

			this._map.fire('contextmenu.show', event);
		},
		_hide: function() {
			if (this._visible) {
				this._visible = false;
				this._container.style.display = 'none';
				this._map.fire('contextmenu.hide', {contextmenu: this});
			}
		},
		_setPosition: function(pt) {
			var mapSize = this._map.getSize(),
				container = this._container,
				containerSize = this._getElementSize(container),
				anchor;

			if (this._map.options.contextmenuAnchor) {
				anchor = L.point(this._map.options.contextmenuAnchor);
				pt = pt.add(anchor);
			}

			container._leaflet_pos = pt;

			if (pt.x + containerSize.x > mapSize.x) {
				container.style.left = 'auto';
				container.style.right = Math.min(Math.max(mapSize.x - pt.x, 0), mapSize.x - containerSize.x - 1) + 'px';
			} else {
				container.style.left = Math.max(pt.x, 0) + 'px';
				container.style.right = 'auto';
			}

			if (pt.y + containerSize.y > mapSize.y) {
				container.style.top = 'auto';
				container.style.bottom = Math.min(Math.max(mapSize.y - pt.y, 0), mapSize.y - containerSize.y - 1) + 'px';
			} else {
				container.style.top = Math.max(pt.y, 0) + 'px';
				container.style.bottom = 'auto';
			}
		},
		_getElementSize: function(el) {
			var size = this._size,
				initialDisplay = el.style.display;

			if (!size || this._sizeChanged) {
				size = {};

				el.style.left = '-999999px';
				el.style.right = 'auto';
				el.style.display = 'block';

				size.x = el.offsetWidth;
				size.y = el.offsetHeight;

				el.style.left = 'auto';
				el.style.display = initialDisplay;

				this._sizeChanged = false;
			}

			return size;
		}
	});

	L.Map.addInitHook('addHandler', contextMenuEvent, L.Map.ContextMenu);

	L.Mixin.ContextMenu = {
		_init: function() {
			this.on(contextMenuEvent, this._show, this);
			//this._map.once('contextmenu.hide', this._hide, this);
		},
		_getItems: function() {
			var url = new Curl('zabbix.php', true),
				menu = null;
			url.setArgument('action', 'menu.popup');
			url.setArgument('type', 'map_element');

			jQuery.ajax({
				url: url.getUrl(),
				data: {
					data: {
						sysmapid: 3,
						selementid: 3
					}
				},
				async: false,
				method: 'POST',
				dataType: 'json',
				success: function(r) {
					menu = r;
				}
			});

			return menu;
		},
		_show: function(e) {
			var data = this._getItems(),
				pt = this._map.mouseEventToContainerPoint(e.originalEvent);

			this._map.contextmenu._makeContextMenu(data.data, e);
			this._map.contextmenu.showAt(pt, L.extend({relatedTarget: this}, e));
		},
		_hide: function() {
			this._map.contextmenu._hideContextMenu();
		}
	};

	// Bind contextmenu hook to markers.
	L.Marker.addInitHook(function() {
		this._init();
	});

//	L.Path.addInitHook(function() {
//		this._init();
//	});

	L.Marker.include(L.Mixin.ContextMenu);
//	L.Path.include(L.Mixin.ContextMenu);

	return L.Map.ContextMenu;
}, window));
