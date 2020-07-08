<?php
/**
 * url-shortner.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \framework\Config_SchemaField_Schema;
use \framework\creator\CreatorSchemaField;

use \framework\Collection_SchemaField_Schema;
use \framework\Collection_SchemaField_Field;


require_once __DIR__ . '/framework/constants.php';
require_once __DIR__ . '/framework/Schema.php';
require_once __DIR__ . '/framework/Config.php';
require_once __DIR__ . '/framework/Router.php';
require_once __DIR__ . '/framework/Authentication.php';
require_once __DIR__ . '/framework/Database.php';
require_once __DIR__ . '/framework/Collection.php';
require_once __DIR__ . '/framework/App.php';
require_once __DIR__ . '/framework/Logger.php';
require_once __DIR__ . '/framework/API.php';

require_once __DIR__ . '/urls/App.php';
require_once __DIR__ . '/urls/Authentication.php';
require_once __DIR__ . '/urls/API.php';
require_once __DIR__ . '/urls/Dummy.php';
require_once __DIR__ . '/urls/Virtual.php';
require_once __DIR__ . '/urls/Shortner.php';


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


final class StoreCollection extends \framework\Collection {
	public string $label = 'Store';
	public string $source = 'urls_store';
	public string $acl = 'store';

	public function __fields() {
		$this->field('store_id')
			->set('type', \framework\VALUE_STR)
			->set('readonly', true);

		$this->field('user_id')
			->set('type', \framework\VALUE_STR)
			->set('acl', '*')
			->set('readonly', true);

		$this->field('domain_id')
			->set('type', \framework\VALUE_STR)
			->set('readonly', true);

		$this->field('event')
			->set('type', \framework\VALUE_ARR)
			->set('acl', '*')
			->set('public', false);

		$this->field('store_time_created')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'datetime')
			->set('acl', '*')
			->set('readonly', true);

		$this->field('store_time_modified')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'datetime')
			->set('acl', '*')
			->set('readonly', true);

		$this->field('store_index')
			->set('type', \framework\VALUE_STR)
			->set('acl', '*')
			->set('readonly', true);

		$this->field('store_slug')
			->set('type', \framework\VALUE_STR)
			->set('readonly', true);

		$this->field('store_url')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'url');
	}
}


final class DomainsCollection extends \framework\Collection {
	public string $label = 'Domains';
	public string $source = 'urls_domains';
	public string $acl = 'domains';

	public function __fields() {

		// $this->field('domain_id')
		// 	->type(\framework\VALUE_STR)
		// 	->readonly(true);

		$this->field('domain_id')
			->set('type', \framework\VALUE_STR)
			->set('readonly', true);

		$this->field('user_id')
			->set('type', \framework\VALUE_STR)
			->set('acl', '*')
			->set('readonly', true);

		$this->field('event')
			->set('type', \framework\VALUE_ARR)
			->set('acl', '*')
			->set('public', false);

		$this->field('domain_time_created')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'datetime')
			->set('acl', '*')
			->set('readonly', true);

		$this->field('domain_time_modified')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'datetime')
			->set('acl', '*')
			->set('readonly', true);

		$this->field('domain_master')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'url')
			->set('php:validate', [FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME])
			->set('js:transform', 'hostname');

		$this->field('domain_service')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'url')
			->set('transform', 'hostname');

		$this->field('domain_enable')
			->set('type', \framework\VALUE_STR)
			->set('acl', '*')
			->set('muta', 'check');
	}
}


final class UsersCollection extends \framework\Collection {
	public string $label = 'Users';
	public string $source = 'urls_users';
	public string $acl = 'users';

	public function __fields() {
		$this->field('user_id')
			->set('type', \framework\VALUE_STR)
			->set('readonly', true);

		$this->field('event')
			->set('type', \framework\VALUE_ARR)
			->set('acl', '*')
			->set('public', false);

		$this->field('user_time_created')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'datetime')
			->set('acl', '*')
			->set('readonly', true);

		$this->field('user_time_modified')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'datetime')
			->set('acl', '*')
			->set('readonly', true);

		$this->field('user_pending')
			->set('type', \framework\VALUE_ARR)
			->set('acl', '*')
			->set('readonly', true);

		$this->field('user_email')
			->set('type', \framework\VALUE_STR)
			->set('muta', 'email')
			->set('acl', '*');

		$this->field('user_name')
			->set('type', \framework\VALUE_STR)
			->set('transform', 'user_name')
			->set('acl', '*');

		$this->field('user_pass')
			->set('type', \framework\VALUE_STR)
			->set('acl', '*')
			->set('public', false);

		$this->field('user_notify')
			->set('type', \framework\VALUE_INT)
			->set('muta', 'radio')
			->set('transform', 'user_notify')
			->set('acl', '*');
	}
}


/*const COLLECTIONS_TEMPLATE = [
	'store' => [
		'source' => 'urls_store',
		'acl' => 'store',
		'fields' => [
			'store_id' => [
				'type' => \framework\VALUE_STR,
				'readonly' => true
			],
			'user_id' => [
				'type' => \framework\VALUE_STR,
				'acl' => '*',
				'readonly' => true
			],
			'domain_id' => [
				'type' => \framework\VALUE_STR,
				'readonly' => true
			],
			'event' => [
				'type' => \framework\VALUE_ARR,
				'acl' => '*',
				'public' => false
			],
			'store_time_created' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'datetime',
				'acl' => '*',
				'readonly' => true
			],
			'store_time_modified' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'datetime',
				'acl' => '*',
				'readonly' => true
			],
			'store_index' => [
				'type' => \framework\VALUE_STR,
				'acl' => '*',
				'readonly' => true
			],
			'store_slug' => [
				'type' => \framework\VALUE_STR,
				'readonly' => true
			],
			'store_url' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'url'
			]
		]
	],
	'domains' => [
		'source' => 'urls_domains',
		'acl' => 'domains',
		'fields' => [
			'domain_id' => [
				'type' => \framework\VALUE_STR,
				'readonly' => true
			],
			'user_id' => [
				'type' => \framework\VALUE_STR,
				'acl' => '*',
				'readonly' => true
			],
			'event' => [
				'type' => \framework\VALUE_ARR,
				'acl' => '*',
				'public' => false
			],
			'domain_time_created' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'datetime',
				'acl' => '*',
				'readonly' => true
			],
			'domain_time_modified' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'datetime',
				'acl' => '*',
				'readonly' => true
			],
			'domain_master' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'url',
				'transform' => 'hostname'
			],
			'domain_service' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'url',
				'transform' => 'hostname'
			],
			'domain_enable' => [
				'type' => \framework\VALUE_BOOL,
				'muta' => 'check',
				'acl' => '*'
			]
		]
	],
	'users' => [
		'source' => 'urls_users',
		'acl' => 'users',
		'fields' => [
			'user_id' => [
				'type' => \framework\VALUE_STR,
				'acl' => '*',
				'readonly' => true
			],
			'user_acl' => [
				'type' => \framework\VALUE_ARR,
				'muta' => 'radio',
				'transform' => 'user_acl'
			],
			'event' => [
				'type' => \framework\VALUE_ARR,
				'acl' => '*',
				'public' => false
			],
			'user_time_created' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'datetime',
				'acl' => '*',
				'readonly' => true
			],
			'user_time_modified' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'datetime',
				'acl' => '*',
				'readonly' => true
			],
			'user_pending' => [
				'type' => \framework\VALUE_ARR, // \framework\VALUE_NULL || \framework\VALUE_ARR
				'acl' => '*',
				'readonly' => true
			],
			'user_email' => [
				'type' => \framework\VALUE_STR,
				'muta' => 'email',
				'acl' => '*'
			],
			'user_name' => [
				'type' => \framework\VALUE_STR,
				'transform' => 'user_name',
				'acl' => '*'
			],
			'user_pass' => [
				'type' => \framework\VALUE_STR,
				'acl' => '*',
				'public' => false
			],
			'user_notify' => [
				'type' => \framework\VALUE_INT,
				'muta' => 'radio',
				'transform' => 'user_notify',
				'acl' => '*'
			]
		]
	]
];*/


/*const CONFIG_TEMPLATE = [
	'Host' => [
		'ssr' => \framework\VALUE_BOOL,
		'error_404' => \framework\VALUE_STR,
		'error_50x' => \framework\VALUE_STR,
		'backend_path' => \framework\VALUE_STR
	],
	'Database' => [
		'dsn' => \framework\VALUE_STR,
		'username' => \framework\VALUE_STR,
		'password' => \framework\VALUE_STR,
		'options' => \framework\VALUE_ARR,
		'shadow' => \framework\VALUE_BOOL
	],
	'Network' => [
		'setup' => \framework\VALUE_BOOL,
		'user_acl' => \framework\VALUE_STR,
		'user_action_lifetime' => \framework\VALUE_INT
	]
];*/


class ConfigSchema extends \framework\ConfigSchema {
	public function __construct() {
		parent::__construct();

		$field_shorthand = new \framework\creator\CreatorSchemaField;
		$field_shorthand->setShorthand($this, $this->field, 'type');

		$this->items->Database = [
			'dsn' => $field_shorthand->set(\framework\VALUE_STR),
			'username' => $field_shorthand->set(\framework\VALUE_STR),
			'password' => $field_shorthand->set(\framework\VALUE_STR),
			'options' => $field_shorthand->set(\framework\VALUE_ARR),
			'shadow' => $field_shorthand->set(\framework\VALUE_BOOL)
		];

		$this->items->Network['user_acl'] = $field_shorthand->set(\framework\VALUE_STR);
		$this->items->Network['user_action_lifetime'] = $field_shorthand->set(\framework\VALUE_INT);
	}
}








// $config = new \framework\Config(new \framework\ConfigSchema);
// $config->fromIniFile(__DIR__ . '/config.ini.php');
// $config = $config->get();

// $database = new VirtualNew($config['Database']);

// $store_collection = new StoreCollection($database);
// $domains_collection = new DomainsCollection($database);
// $users_collection = new UsersCollection($database);

// var_dump(serialize($store_collection));
// var_dump(json_decode(json_encode($store_collection, JSON_PARTIAL_OUTPUT_ON_ERROR), true));

// var_dump(serialize($domains_collection));
// var_dump(json_decode(json_encode($domains_collection, JSON_PARTIAL_OUTPUT_ON_ERROR), true));

// var_dump(serialize($users_collection));
// var_dump(json_decode(json_encode($users_collection, JSON_PARTIAL_OUTPUT_ON_ERROR), true));







