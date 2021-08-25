<?php
define('GROUPID', 15); // host group ID
define('GRID_Y', 50); // hosts to generate vertically
define('GRID_X', 50); // hosts to generate horizontally
define('LAT_START', 56.9520105); // start pos latitude
define('LNG_START', 24.2227327); // start pos longitude
define('DISTANCE', 1000); // distance between hosts in meters

require_once dirname(__FILE__).'/include/config.inc.php';

$host_nr = 0;
for ($y = 0; GRID_Y > $y; $y++) {
	for ($x = 0; GRID_X > $x; $x++) {
		list($lat, $lng) = calcNextPoint($x, $y);
		save('h'.($host_nr+1), $lat, $lng);
		$host_nr++;
	}
}

echo 'Created ' . $host_nr . ' hosts.';
exit;

function calcNextPoint($steps_x, $steps_y) {
	$distance_in_degree = 1 / 111.32 / 1000 * DISTANCE;

	return [
		LAT_START + $distance_in_degree * $steps_x,
		LNG_START + $distance_in_degree * $steps_y / cos(LAT_START * pi() / 45)
	];
}

function save($hostname, $lat, $lng) {

	API::Host()->create($options = [
		'host' => $hostname,
		'status' => HOST_STATUS_NOT_MONITORED,
		'groups' => [[
			'groupid' => GROUPID
		]],
		'inventory_mode' => HOST_INVENTORY_MANUAL,
		'inventory' => [
			'location_lat' => $lat,
			'location_lon' => $lng
		],
		'interfaces' => [[
			'type' => INTERFACE_TYPE_AGENT,
			'main' => INTERFACE_PRIMARY,
			'useip' => INTERFACE_USE_IP,
			'ip' => '127.0.0.1',
			'dns' => '',
			'port' => '10050'
		]]
	]);

	if (($messages = getMessages()) !== null) {
		sdii([
			'messages' => $messages,
			'options' => $options
		]);
		exit;
	}
}
