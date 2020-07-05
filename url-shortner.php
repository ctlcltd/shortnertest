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


require_once __DIR__ . '/framework/constants.php';
require_once __DIR__ . '/framework/Config.php';
require_once __DIR__ . '/framework/Database.php';
require_once __DIR__ . '/framework/Authentication.php';
require_once __DIR__ . '/framework/Schema.php';
require_once __DIR__ . '/framework/App.php';
require_once __DIR__ . '/framework/Logger.php';
require_once __DIR__ . '/framework/API.php';

require_once __DIR__ . '/urls/Collection.php';
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


const COLLECTIONS_TEMPLATE = [
	'store' => [
		'table' => 'urls_store',
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
		'table' => 'urls_domains',
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
		'table' => 'urls_users',
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
	],
	'shadows' => [
		'table' => 'urls_shadows',
		'public' => false,
		'fields' => [
			'event' => [
				'type' => \framework\VALUE_ARR,
				'acl' => '*',
				'public' => false
			],
			'shadow_time' => [
				'type' => \framework\VALUE_STR,
				'acl' => '*',
				'public' => false
			],
			'shadow_blob' => [
				'type' => \framework\VALUE_ARR,
				'acl' => '*',
				'public' => false
			]
		]
	]
];


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

		$this->schema->Network['user_acl'] = (new Config_SchemaField_Schema)
			->set('type', \framework\VALUE_STR, $this->schema);

		$this->schema->Network['user_action_lifetime'] = (new Config_SchemaField_Schema)
			->set('type', \framework\VALUE_INT, $this->schema);
	}
}











// new \framework\ConfigSchema(\urls\CONFIG_TEMPLATE, 'Config');
// new \urls\CollectionSchema(\urls\COLLECTIONS_TEMPLATE['store'], 'Store');







