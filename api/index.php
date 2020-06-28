<?php
namespace urls;

require_once __DIR__ . '/../url-shortner.php';

use \urls\Shortner;
use \urls\Data;
use \urls\API;


const ROUTES = [
	'/' => [
		'POST' => [ 'auth' => true ]
	],
	'/store' => [
		'GET' => [ 'call' => 'store_list' ],
		'POST' => [ 'call' => 'store_add' ],
		'PATCH' => [ 'call' => 'store_update' ],
		'DELETE' => [ 'call' => 'store_delete' ]
	],
	'/domains' => [
		'GET' => [ 'call' => 'domain_list' ],
		'POST' => [ 'call' => 'domain_add' ],
		'PATCH' => [ 'call' => 'domain_update' ],
		'DELETE' => [ 'call' => 'domain_delete' ]
	],
	'/users' => [
		'GET' => [ 'call' => 'user_get_by_id' ],
		'POST' => [ 'call' => 'user_add' ],
		'PATCH' => [ 'call' => 'user_update' ],
		'DELETE' => [ 'call' => 'user_delete' ]
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
		'dbopts' => Shortner::VALUE_ARR,
		'dbshadow' => Shortner::VALUE_BOOL
	]
];

class UrlsShortner extends \urls\Shortner {}
class UrlsDatabase extends \urls\Database {}
class UrlsData extends \urls\Data {}
class UrlsAPI extends \urls\API {}

new UrlsAPI;
