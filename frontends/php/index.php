<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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


require_once dirname(__FILE__).'/include/classes/user/CWebUser.php';
CWebUser::disableSessionCookie();

require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

/*
function generateRandomPoint($centre, $radius) {
    $radius_earth = 3959; //miles

    //Pick random distance within $distance;
    $distance = lcg_value()*$radius;

    //Convert degrees to radians.
    $centre_rads = array_map( 'deg2rad', $centre );

    //First suppose our point is the north pole.
    //Find a random point $distance miles away
    $lat_rads = (pi()/2) -  $distance/$radius_earth;
    $lng_rads = lcg_value()*2*pi();


    //($lat_rads,$lng_rads) is a point on the circle which is
    //$distance miles from the north pole. Convert to Cartesian
    $x1 = cos( $lat_rads ) * sin( $lng_rads );
    $y1 = cos( $lat_rads ) * cos( $lng_rads );
    $z1 = sin( $lat_rads );


    //Rotate that sphere so that the north pole is now at $centre.

    //Rotate in x axis by $rot = (pi()/2) - $centre_rads[0];
    $rot = (pi()/2) - $centre_rads[0];
    $x2 = $x1;
    $y2 = $y1 * cos( $rot ) + $z1 * sin( $rot );
    $z2 = -$y1 * sin( $rot ) + $z1 * cos( $rot );

    //Rotate in z axis by $rot = $centre_rads[1]
    $rot = $centre_rads[1];
    $x3 = $x2 * cos( $rot ) + $y2 * sin( $rot );
    $y3 = -$x2 * sin( $rot ) + $y2 * cos( $rot );
    $z3 = $z2;


    //Finally convert this point to polar co-ords
    $lng_rads = atan2( $x3, $y3 );
    $lat_rads = asin( $z3 );

    return array_map( 'rad2deg', array( $lat_rads, $lng_rads ) );
}

$center = [56.953159, 24.216532];

for ($i = 1; 10000 > $i; $i++) {
	$point = generateRandomPoint($center, 25);
	$center = [$point[0], $point[1]];

	API::Host()->create([
		'host' => 'h'.$i,
		'interfaces' => [
			'type' => 1,
			'main' => 1,
			'useip' => 1,
			'ip' => '127.0.0.1',
			'dns' => '',
			'port' => '10050'
		],
		'groups' => [
			[
				'groupid' => 21
			]
        ],
		'templates' => [
			[
				'templates' => 10283
			]
        ],
        'inventory_mode' => 0,
        'inventory' => [
			'location_lat' => $point[0],
            'location_lon' => $point[1]
		]
	]);
}
exit;
*/

$page['title'] = _('ZABBIX');
$page['file'] = 'index.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'name' =>		[T_ZBX_STR, O_NO,	null,	null,	'isset({enter}) && {enter} != "'.ZBX_GUEST_USER.'"', _('Username')],
	'password' =>	[T_ZBX_STR, O_OPT, null,	null,	'isset({enter}) && {enter} != "'.ZBX_GUEST_USER.'"'],
	'sessionid' =>	[T_ZBX_STR, O_OPT, null,	null,	null],
	'reconnect' =>	[T_ZBX_INT, O_OPT, P_SYS,	null,	null],
	'enter' =>		[T_ZBX_STR, O_OPT, P_SYS,	null,	null],
	'autologin' =>	[T_ZBX_INT, O_OPT, null,	null,	null],
	'request' =>	[T_ZBX_STR, O_OPT, null,	null,	null],
	'form' =>		[T_ZBX_STR, O_OPT, null,	null,	null]
];
check_fields($fields);

if (hasRequest('reconnect') && CWebUser::isLoggedIn()) {
	CWebUser::logout();
}

$config = select_config();
$autologin = hasRequest('enter') ? getRequest('autologin', 0) : getRequest('autologin', 1);
$request = getRequest('request', '');

if ($request) {
	$test_request = [];
	preg_match('/^\/?(?<filename>[a-z0-9\_\.]+\.php)(?<request>\?.*)?$/i', $request, $test_request);

	$request = (array_key_exists('filename', $test_request) && file_exists('./'.$test_request['filename']))
		? $test_request['filename'].(array_key_exists('request', $test_request) ? $test_request['request'] : '')
		: '';
}

if (!hasRequest('form') && $config['http_auth_enabled'] == ZBX_AUTH_HTTP_ENABLED
		&& $config['http_login_form'] == ZBX_AUTH_FORM_HTTP && !hasRequest('enter')) {
	redirect('index_http.php');

	exit;
}

// login via form
if (hasRequest('enter') && CWebUser::login(getRequest('name', ZBX_GUEST_USER), getRequest('password', ''))) {
	if (CWebUser::$data['autologin'] != $autologin) {
		API::User()->update([
			'userid' => CWebUser::$data['userid'],
			'autologin' => $autologin
		]);
	}

	$redirect = array_filter([CWebUser::isGuest() ? '' : $request, CWebUser::$data['url'], ZBX_DEFAULT_URL]);
	redirect(reset($redirect));

	exit;
}

if (CWebUser::isLoggedIn() && !CWebUser::isGuest()) {
	redirect(CWebUser::$data['url'] ? CWebUser::$data['url'] : ZBX_DEFAULT_URL);
}

$messages = clear_messages();

(new CView('general.login', [
	'http_login_url' => $config['http_auth_enabled'] == ZBX_AUTH_HTTP_ENABLED
		? (new CUrl('index_http.php'))->setArgument('request', getRequest('request'))
		: '',
	'guest_login_url' => CWebUser::isGuestAllowed() ? (new CUrl())->setArgument('enter', ZBX_GUEST_USER) : '',
	'autologin' => $autologin == 1,
	'error' => hasRequest('enter') && $messages ? array_pop($messages) : null
]))
	->disableJsLoader()
	->render();
