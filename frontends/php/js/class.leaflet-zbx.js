var icon = L.icon({
	iconUrl: 'imgstore.php?iconid=151',
	iconSize: [72, 96],
	iconAnchor: [36, 96],
	popupAnchor: [-10, -99]
});

function LeafletZBX(data) {
	this.map = L.map('map').setView([56.955105, 24.216532], 12);
	this.data = data;
	this.elements = L.featureGroup().addTo(this.map).on('click', this.iconOnClick);

	// Add tile layer.
	L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 18
	}).addTo(this.map);

	// Add map elements.
	var obj = this;
	L.geoJSON(this.data.elements, {
		pointToLayer: function (feature, latlng) {
			return L.marker(latlng, {icon: obj.getElementIcon(feature)});
		}
	}).addTo(this.elements);

	setInterval(this.update.bind(this), 5000);
}

LeafletZBX.prototype.update = function() {
	var url = new Curl('zabbix.php', true),
		obj = this,
		hosts = [];

	url.setArgument('action', 'widget.dev1076a.update');
	this.elements.getLayers().first().getLayers().forEach(function(item) {
		hosts.push(item.feature.properties.hostid);
	});

	jQuery.ajax({
		url: url.getUrl(),
		data: {
			hostids: hosts
		},
		success: function(response) {
			obj.elements.getLayers().first().getLayers().forEach(function(item) {
				var hostid = item.feature.properties.histid,
					properties = response.hosts[hostid];
				console.log(response);
				for (var prop in properties) {
					console.log(prop, properties[prop]);
					item.feature.properties[prop] = properties[prop];
				}

				console.log(item.feature.properties);
				item.setIcon(obj.getElementIcon(item.feature)).update();
			});
		},
		method: 'POST',
		dataType: 'json'
	});
};

LeafletZBX.prototype.iconOnClick = function(feature) {
	console.log(feature.layer.feature.properties);
};

LeafletZBX.prototype.getElementIcon = function(feature) {
	var divIcon = {
		html: '<img src="imgstore.php?iconid=151"/>'+
		  '<span class="label">' + feature.properties.hostname + '</span>'
	};

	return new L.DivIcon(jQuery.extend(divIcon, icon));
};
