<?php
/*!
 * url-shortner.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \urls\Config;
use \urls\Shortner;
use \urls\Database;
use \urls\Authentication;
use \urls\API;


/**
 * Interface for shortner class
 *
 * @interface
 */
interface ShortnerInterface {
	public static function shortner($src_url);
	public static function resolver($uri);
}

/**
 * @class
 */
abstract class Shortner implements ShortnerInterface {
	public const VALUE_NULL = 0;
	public const VALUE_INT = 1;
	public const VALUE_STR = 2;
	public const VALUE_BOOL = 5;
	public const VALUE_ARR = 6;

	public static function shortner($src_url) {
		$src_url = parse_url($src_url);
		$src_url = substr($src_url['path'], 1) .
			(isset($src_url['query']) ? '?' . $src_url['query'] : '') .
			(isset($src_url['fragment']) ? '#' . $src_url['fragment'] : '');

		$index = zlib_encode($src_url, ZLIB_ENCODING_DEFLATE, 9);
		$index = base64_encode($index);

		$data = new stdClass;
		$data->index = $index;
		$data->slug = hash('adler32', $index);

		return $data;
	}

	public static function resolver($uri) {
		$uri = substr($uri, 1);

		$index = zlib_decode($uri, ZLIB_ENCODING_DEFLATE, 9);
		$index = base64_encode($uri);

		return $index;
	}
}

require_once __DIR__ . '/url-shortner/Config.php';
require_once __DIR__ . '/url-shortner/Database.php';
require_once __DIR__ . '/url-shortner/Authentication.php';
require_once __DIR__ . '/url-shortner/Controller.php';
require_once __DIR__ . '/url-shortner/Urls_Controller.php';
require_once __DIR__ . '/url-shortner/Model.php';
require_once __DIR__ . '/url-shortner/Logger.php';
require_once __DIR__ . '/url-shortner/API.php';

const ROUTES = [
	'/' => [
		'GET' => [ 'call' => 'exploiting', 'auth' => false ],
		'POST' => [ 'access' => true ]
	],
	'/store' => [
		'GET' => [ 'call' => 'store_list' ],
		'POST' => [ 'call' => 'store_add' ],
		'PATCH' => [ 'call' => 'store_update' ],
		'DELETE' => [ 'call' => 'store_delete' ],
		//-TEMP
		'PUT' => [ 'call' => 'store_get_by_id' ]
		//-TEMP
	],
	'/domains' => [
		'GET' => [ 'call' => 'domain_list' ],
		'POST' => [ 'call' => 'domain_add' ],
		'PATCH' => [ 'call' => 'domain_update' ],
		'DELETE' => [ 'call' => 'domain_delete' ],
		//-TEMP
		'PUT' => [ 'call' => 'domain_get' ]
		//-TEMP
	],
	'/users' => [
		'GET' => [ 'call' => 'user_list' ],
		'POST' => [ 'call' => 'user_add' ],
		'PATCH' => [ 'call' => 'user_update' ],
		'DELETE' => [ 'call' => 'user_delete' ],
		//-TEMP
		'PUT' => [ 'call' => 'user_get_by_id' ]
		//-TEMP
	],
	'/setup' => [
		'GET' => [ 'call' => 'install', 'setup' => true ]
	],
	'/activate' => [
		'GET' => [ 'call' => 'user_activation', 'auth' => false ]
	]
];

const COLLECTION_TEMPLATE = [
	'store' => 'urls_store',
	'domains' => 'urls_domains',
	'users' => 'urls_users',
	'shadows' => 'urls_shadows'
];

const COLLECTION_SCHEMA = [
	'store' => [
		'store_id' => [
			'type' => Shortner::VALUE_STR,
			'readonly' => true
		],
		'user_id' => [
			'type' => Shortner::VALUE_STR,
			'acl' => '*',
			'readonly' => true
		],
		'domain_id' => [
			'type' => Shortner::VALUE_STR,
			'readonly' => true
		],
		'event' => [
			'type' => Shortner::VALUE_ARR,
			'acl' => '*',
			'public' => false
		],
		'store_time_created' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'datetime',
			'acl' => '*',
			'readonly' => true
		],
		'store_time_modified' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'datetime',
			'acl' => '*',
			'readonly' => true
		],
		'store_index' => [
			'type' => Shortner::VALUE_STR,
			'acl' => '*',
			'readonly' => true
		],
		'store_slug' => [
			'type' => Shortner::VALUE_STR,
			'readonly' => true
		],
		'store_url' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'url'
		]
	],
	'domains' => [
		'domain_id' => [
			'type' => Shortner::VALUE_STR,
			'readonly' => true
		],
		'user_id' => [
			'type' => Shortner::VALUE_STR,
			'acl' => '*',
			'readonly' => true
		],
		'event' => [
			'type' => Shortner::VALUE_ARR,
			'acl' => '*',
			'public' => false
		],
		'domain_time_created' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'datetime',
			'acl' => '*',
			'readonly' => true
		],
		'domain_time_modified' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'datetime',
			'acl' => '*',
			'readonly' => true
		],
		'domain_master' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'url',
			'transform' => 'hostname'
		],
		'domain_service' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'url',
			'transform' => 'hostname'
		],
		'domain_enable' => [
			'type' => Shortner::VALUE_BOOL,
			'muta' => 'check',
			'acl' => '*'
		]
	],
	'users' => [
		'user_id' => [
			'type' => Shortner::VALUE_STR,
			'acl' => '*',
			'readonly' => true
		],
		'user_acl' => [
			'type' => Shortner::VALUE_ARR,
			'muta' => 'radio',
			'transform' => 'user_acl'
		],
		'event' => [
			'type' => Shortner::VALUE_ARR,
			'acl' => '*',
			'public' => false
		],
		'user_time_created' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'datetime',
			'acl' => '*',
			'readonly' => true
		],
		'user_time_modified' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'datetime',
			'acl' => '*',
			'readonly' => true
		],
		'user_pending' => [
			'type' => Shortner::VALUE_ARR,
			'acl' => '*',
			'readonly' => true
		],
		'user_email' => [
			'type' => Shortner::VALUE_STR,
			'muta' => 'email',
			'acl' => '*'
		],
		'user_name' => [
			'type' => Shortner::VALUE_STR,
			'transform' => 'user_name',
			'acl' => '*'
		],
		'user_pass' => [
			'type' => Shortner::VALUE_STR,
			'acl' => '*',
			'public' => false
		],
		'user_notify' => [
			'type' => Shortner::VALUE_INT,
			'muta' => 'radio',
			'transform' => 'user_notify',
			'acl' => '*'
		]
	],
	'shadows' => [
		'event' => [
			'type' => Shortner::VALUE_ARR,
			'acl' => '*',
			'public' => false
		],
		'shadow_time' => [
			'type' => Shortner::VALUE_STR,
			'acl' => '*',
			'public' => false
		],
		'shadow_blob' => [
			'type' => Shortner::VALUE_ARR,
			'acl' => '*',
			'public' => false
		]
	]
];

const CONFIG_SCHEMA = [
	'Host' => [
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
	],
	'Network' => [
		'nwsetup' => Shortner::VALUE_BOOL,
		'nwuseracl' => Shortner::VALUE_STR,
		'nwuseractionlifetime' => Shortner::VALUE_INT
	]
];


class Urls_Config extends \urls\Config {}
class Urls_Shortner extends \urls\Shortner {}
class Urls_Database extends \urls\Database {}
class Urls_Authentication extends \urls\Authentication {}
class Urls_API extends \urls\API {}
