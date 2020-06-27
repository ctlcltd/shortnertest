<?php
namespace urls;

require_once __DIR__ . '/../url-shortner.php';

use \urls\Shortner;
use \urls\Data;
use \urls\API;


const ROUTES = [
	'/' => [
		'GET' => [ 'call' => 'resolver', 'auth' => false ]
	],
	'/store' => [
		'GET' => [ 'call' => 'store_list', 'auth' => false ],
		'POST' => [ 'call' => 'store_add', 'auth' => false ],
		'PATCH' => [ 'call' => 'store_update', 'auth' => false ],
		'DELETE' => [ 'call' => 'store_delete', 'auth' => false ]
	],
	'/domains' => [
		'GET' => [ 'call' => 'domain_list', 'auth' => false ],
		'POST' => [ 'call' => 'domain_add', 'auth' => false ],
		'PATCH' => [ 'call' => 'domain_update', 'auth' => false ],
		'DELETE' => [ 'call' => 'domain_delete', 'auth' => false ]
	],
	'/users' => [
		'GET' => [ 'call' => 'user_get_by_id', 'auth' => false ],
		'POST' => [ 'call' => 'user_add', 'auth' => false ],
		'PATCH' => [ 'call' => 'user_update', 'auth' => false ],
		'DELETE' => [ 'call' => 'user_delete', 'auth' => false ]
	]
];

const CONFIG_SCHEMA = [
	'Host' => [
		'htmasterdomain' => Shortner::VALUE_STR,
		'htservicedomain' => Shortner::VALUE_STR,
		'htbackenddomain' => Shortner::VALUE_STR,
		'htssr' => Shortner::VALUE_BOOL,
		'ht404' => Shortner::VALUE_STR,
		'ht50x' => Shortner::VALUE_STR
	],
	'Backend' => [
		'bepath' => Shortner::VALUE_STR
	],
	'Database' => [
		'dbdsn' => Shortner::VALUE_STR,
		'dbuser' => Shortner::VALUE_STR,
		'dbpass' => Shortner::VALUE_STR,
		'dbopts' => Shortner::VALUE_ARR
	]
];

class UrlsShortner extends \urls\Shortner {}
class UrlsDatabase extends \urls\Database {}
class UrlsData extends \urls\Data {}
class UrlsAPI extends \urls\API {}

new UrlsAPI;
