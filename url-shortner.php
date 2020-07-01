<?php
namespace urls;

use \Exception;
use \ErrorException;
use \Error;
use \ReflectionMethod;
use \PDO;
use \PDOException;
use \stdClass;


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
 * Interface for database class
 *
 * @interface
 */
interface DatabaseInterface {
	public function connect();
	public function disconnect();
	public function transaction();
	public function commit();
	public function rollback();
	public function end();
	public function shadow($collection, $shadow, $event, $id_keys, $keys_shadow);
	public function prepare();
	public function process($command, $collection, $allowed);
	public function run();
	public function select($keys, $single, $distinct);
	public function set($param, $value, $type);
	public function where($param, $value, $type, $condition);
	public function limit($limit_offset, $limit);
	public function fetch($collection);
	public function add($collection);
	public function update($collection);
	public function remove($collection);
}

/**
 * Interface for data class
 *
 * @interface
 */
interface DataInterface {
	public function store_add($domain_id, $url);
	public function store_delete($store_id);
	public function store_list($domain_id);
	public function domain_get($domain_id);
	public function domain_list($user_id);
	public function domain_add($master, $service);
	public function domain_update($domain_id, $master, $service);
	public function domain_delete($domain_id, $purge);
	public function user_get_by_id($user_id);
	public function user_get_by_name($user);
	public function user_add($user_acl, $user_email, $user_name, $user_password, $user_notify);
	public function user_delete($user_id, $purge);
	public function user_match($user_email, $user_name, $user_password);
}

/**
 * Interface for logger class
 *
 * @interface
 */
interface LoggerInterface {
	public function getToken();
	public function getTime();
}

/**
 * Interface for authentication class
 *
 * @interface
 */
interface AuthenticationInterface {
	public function transaction();
	public function commit();
	public function flush();
	public function get($key);
	public function set($key, $value);
	public function authorize($user_email, $user_name, $user_password);
	public function unauthorize();
	public function isAuthorized();
	public function setAuthorization($authorized);
	public function setUserData($data);
	public function getUserData();
}

/**
 * Interface for api class
 *
 * @interface
 */
interface ApiInterface {

}

/**
 * @class
 */
class Shortner implements ShortnerInterface {
	public const VALUE_NULL = 0;
	public const VALUE_INT = 1;
	public const VALUE_STR = 2;
	public const VALUE_BOOL = 5;
	public const VALUE_ARR = 6;

	public $config;

	function __construct() {
		try {
			if (! defined('\urls\CONFIG_SCHEMA'))
				throw 'Undef config schema';
				
			if ($config = parse_ini_file(__DIR__ . '/config.ini.php', true, INI_SCANNER_TYPED))
				$this->setup(\urls\CONFIG_SCHEMA, $config);
			else
				throw 'Parse INI';
		} catch (Exception $error) {
			die('Missing or wrong configuration file.');
		}
	}

	protected function setup($schema, $config) {
		foreach ($schema as $section => $values) {
			if (! \array_key_exists($section, $config))
				throw new Exception(sprintf('Undef section: %s', $section));

			if (! is_array($values))
				throw new Exception("Value not arr");

			foreach ($values as $key => $type) {
				if (! \array_key_exists($key, $schema[$section]))
					throw new Exception(sprintf('Undef key: %s', $key));

				if (gettype($config[$section][$key]) !== $this->type($type))
					throw new Exception(sprintf('Wrong value: %s', $key));
			}
		}

		if ($config['Database']['dbopts'] === [''])
			$config['Database']['dbopts'] = NULL;

		$this->config = $config;
	}

	private function type($type_int) {
		switch ($type_int) {
			case 0: return 'NULL';
			case 1: return 'integer';
			case 2: return 'string';
			case 5: return 'boolean';
			case 6: return 'array';
			default: throw new Exception('Type Exception');
		}
	}

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


class DatabaseException extends \Exception {}

/**
 * Class for data handling
 * 
 * @class
 */
class Database implements DatabaseInterface {
	private $config, $template, $dbh, $sth;

	private const SQL_TEMPLATES = [
		'fetch' => 'SELECT %s FROM %s',
		'add' => 'INSERT INTO %s (%s) VALUES(%s)',
		'update' => 'UPDATE %s SET %s',
		'remove' => 'DELETE FROM %s'
	];
	private const SQL_CLAUSES = [
		'select',
		'count',
		'set',
		'values',
		'where',
		'group',
		'sort',
		'limit'
	];

	protected $statement, $command, $table, $allowed;

	public function __construct($config_db, $collection_template) {
		$this->config = $config_db;
		$this->template = $collection_template;
	}

	public function connect() {
		try {
			$this->dbh = new PDO(
				$this->config['dbdsn'],
				$this->config['dbuser'],
				$this->config['dbpass'],
				$this->config['dbopts']
			);

			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		} catch (PDODatabaseException $error) {
			die(sprintf('PDO DatabaseException: %s', $error->getMessage()));
		}
	}

	public function disconnect() {

	}

	public function transaction() {
		if (! empty($this->statement))
			throw new DatabaseException('Opened statement');

		return $this->dbh->beginTransaction();
	}

	public function commit() {
		if (empty($this->statement))
			return NULL;

		return $this->dbh->commit();
	}

	public function rollback() {
		return $this->dbh->rollBack();
	}

	public function end() {
		$this->sth->closeCursor();
	}

	public function shadow($collection, $shadow, $event, $id_keys, $keys_shadow) {
		$token = $event->getToken();
		$time = $event->getTime();

		$this->fetch($collection, NULL, true);

		foreach ($id_keys as $id_key)
			$this->where($id_key[0], $id_key[1], $id_key[2]);

		$blob = $this->run();

		$this->add($shadow);

		$shadow = compact('token', 'time', 'blob');

		foreach ($keys_shadow as $key_id)
			$this->set($key_id[0], $shadow[$key_id[1]], $key_id[2]);

		return $this->run();
	}

	public function process($command, $collection, $clauses) {
		if (! isset($this->template[$collection]))
			throw new DatabaseException('Unknown collection');			

		if (! in_array($command, ['fetch', 'add', 'update', 'remove']))
			throw new DatabaseException('Unknown SQL command');

		if (! empty(array_diff($clauses, self::SQL_CLAUSES)))
			throw new DatabaseException('Unknown SQL clauses');

		$sql_clauses = array_fill_keys(self::SQL_CLAUSES, false);
		$clauses = array_fill_keys($clauses, true);

		$this->command = $command;
		$this->table = $this->template[$collection];
		$this->clauses = array_merge($sql_clauses, $clauses);
		$this->statement = [];
	}

	public function prepare() {
		if ($GLOBALS['debug_data']) var_dump($this);

		if (
			($this->command === 'add' || $this->command === 'update') &&
			! isset($this->statement['set'])
		) throw new DatabaseException(sprintf('Undef set for %s', $this->command));

		if (
			($this->command === 'update' || $this->command === 'remove') &&
			! isset($this->statement['where'])
		) throw new DatabaseException(sprintf('Undef where for %s', $this->command));

		$_key_prefix_transfunc = function($key) {
			return ":{$key}";
		};
		$_set_flat_transfunc = function(&$value, $key) {
			$value = "{$key}=:{$key}";
		};
		$_group_flat_transfunc = function(&$value, $key) {
			$value = "{$key} {$clause}";
		};
		$_where_flat_transfunc = function(&$value, $key, &$i) {
			if ($i++ && empty($value['condition']))
				throw new DatabaseException('WHERE clause');

			$value = "{$value['condition']} {$key}=:{$key}";
		};

		$sql = self::SQL_TEMPLATES[$this->command];
		$values = [];

		if ($this->clauses['set'] && isset($this->statement['set'])) {
			if ($this->clauses['values']) {
				$params = array_keys($this->statement['set']);

				$vars = array_map($_key_prefix_transfunc, $params);

				$sql = sprintf($sql, $this->table, implode(',', $params), implode(',', $vars));
			} else {
				$params = $this->statement['set'];

				array_walk($params, $_set_flat_transfunc);

				$sql = sprintf($sql, $this->table, implode(',', $params));
			}

			$values += $this->statement['set'];
		}

		if ($this->clauses['select'] && isset($this->statement['select'])) {
			$select = '';

			if ($this->statement['select']['keys']) {
				$keys = array_values($this->statement['select']['keys']);

				$select .= implode(',', $keys);
			} else {
				$select .= '*';
			}

			if ($this->statement['select']['count'])
				$select = "COUNT({$select})";

			if ($this->statement['select']['distinct'])
				$select = "DISTINCT {$select}";

			$sql = sprintf($sql, $select, $this->table);

			if (isset($this->statement['sort'])) {
				$sort = $this->statement['sort'];

				array_walk($sort, $_group_flat_transfunc);

				$sql .= ' ORDER BY ' . implode(',', $sort);
			}

			if (isset($this->statement['group'])) {
				$group = $this->statement['group'];

				array_walk($group, $_group_flat_transfunc);

				$sql .= ' GROUP BY ' . implode(',', $group);
			}

			if (isset($this->statement['limit']))
				$sql .= ' LIMIT ' . implode(',', $this->statement['limit']);
		} else {
			$sql = sprintf($sql, $this->table);
		}

		if ($this->clauses['where'] && isset($this->statement['where'])) {
			$where = $this->statement['where'];

			array_walk($where, $_where_flat_transfunc, 0);

			$sql .= ' WHERE' . implode(' ', $where);

			$values += $this->statement['where'];
		}

		if ($GLOBALS['debug_data']) var_dump($sql);

		$this->sth = $this->dbh->prepare($sql);

		foreach ($values as $param => $set) {
			if ($set['type'] === 6) {
				$set['value'] = json_encode($set['value']);
				$set['type'] = 2;
			}

			$this->sth->bindValue(":{$param}", $set['value'], $set['type']);
		}

		if ($GLOBALS['debug_data']) var_dump($this->sth->debugDumpParams());
	}

	public function run() {
		$this->prepare();

		$results = $this->sth->execute();

		if ($GLOBALS["debug_data"]) var_dump($this->dbh->errorInfo());

		if ($results && $this->command === 'fetch') {
			if ($this->statement['select']['single'])
				$results = $this->sth->fetch(PDO::FETCH_ASSOC);
			else
				$results = $this->sth->fetchAll(PDO::FETCH_ASSOC);
		}

		if ($GLOBALS["debug_data"]) var_dump($results);

		$this->end();

		$this->statement = NULL;
		$this->sth = NULL;

		return $results;
	}

	public function select($keys, $single, $distinct) {
		if (! $this->clauses['select'])
			throw new Exception('Select clause not allowed');

		$this->statement['select'] = [
			'single' => $single,
			'distinct' => $distinct ? true : false,
			'count' => false,
			'keys' => is_array($keys) ? $keys : NULL
		];

		return $this;
	}

	public function count() {
		if (! $this->clauses['count'])
			throw new Exception('Count clause not allowed');

		$this->statement['select']['count'] = true;

		return $this;
	}

	public function set($param, $value, $type = 2) {
		if (! $this->clauses['set'])
			throw new Exception('Set clause not allowed');

		$this->statement['set'][$param] = [
			'value' => $value,
			'type' => $type
		];

		return $this;
	}

	public function where($param, $value, $type = 2, $condition = '') {
		if (! $this->clauses['where'])
			throw new Exception('Where clause not allowed');

		if (! empty($this->statement['where']) && ! $condition)
			$condition = 'and';

		$this->statement['where'][$param] = [
			'condition' => strtoupper($condition),
			'value' => $value,
			'type' => $type
		];

		return $this;
	}

	public function sort($param, $sort_by) {
		if (! $this->clauses['sort'])
			throw new Exception('Sort clause not allowed');

		$this->statement['sort'][$param] = strtoupper($sort_by);

		return $this;
	}

	public function group($param, $group_by) {
		if (! $this->clauses['group'])
			throw new Exception('Group clause not allowed');

		$this->statement['group'][$param] = strtoupper($group_by);

		return $this;
	}

	public function limit($limit_offset, $limit = 0) {
		if (! $this->clauses['limit'])
			throw new Exception('Limit clause not allowed');

		if (! $limit) {
			$this->statement['limit']['to'] = (int) $limit_offset;
		} else {
			$this->statement['limit']['from'] = (int) $limit_offset;
			$this->statement['limit']['to'] = (int) $limit;
		}			

		return $this;
	}

	public function fetch($collection, $keys = NULL, $single = false, $distinct = false) {
		$this->process('fetch', $collection, [
			'select',
			'count',
			'where',
			'group',
			'sort',
			'limit'
		]);

		$this->select($keys, $single, $distinct);
	}

	public function add($collection) {
		$this->process('add', $collection, ['set', 'values']);
	}

	public function update($collection) {
		$this->process('update', $collection, ['set', 'where']);
	}

	public function remove($collection) {
		$this->process('remove', $collection, ['set', 'where']);
	}
}


/**
 * @class
 */
class Data implements DataInterface {
	private $config, $data, $enable_shadow;
	protected $user;

	public const COLLECTION_TEMPLATE = [
		'store' => 'urls_store',
		'domains' => 'urls_domains',
		'users' => 'urls_users',
		'shadows' => 'urls_shadows'
	];

	public function __construct($config, $data) {
		$this->config = $config;
		$this->data = $data;
		$this->enable_shadow = $this->config['Database']['dbshadow'];
		$this->need_preauth = true;

		$this->user = NULL;
	}

	public function store_get_by_id($store_id) {
		$event = $this->call('store', 'get_by_id', false);

		$user_id = $this->user->id;

		$this->data->fetch('store', ['store_index', 'store_slug', 'store_url']);

		$this->data->where('store_id', $user_id);

		return $this->data->run();
	}

	public function store_get_by_slug($store_slug) {
		$event = $this->call('store', 'get_by_slug', false);

		$user_id = $this->user->id;

		$this->data->fetch('store', ['store_index', 'store_slug', 'store_url']);

		$this->data->where('store_slug', $store_slug);

		return $this->data->run();
	}

	public function store_list($domain_id) {
		$event = $this->call('store', 'list', false);

		$user_id = $this->user->id;

		$this->data->fetch('store', ['store_index', 'store_slug', 'store_url']);

		$this->data
			->where('user_id', $user_id)
			->where('domain_id', $domain_id);

		return $this->data->run();
	}

	public function store_add($domain_id, $url) {
		$event = $this->call('store', 'add');

		if (! filter_var($url, FILTER_VALIDATE_URL))
			throw new DataException('Not a valid URL');

		//same domain ?

		$this->data->add('store');

		$user_id = $this->user->id;
		$store_id = $this->uniqid('store', $event);

		//sanitize with fragment

		$store_url = $url;
		$shortner = Shortner::shortner($store_url);

		$this->data
			->set('store_id', $store_id)
			->set('user_id', $user_id)
			->set('domain_id', $domain_id)
			->set('store_index', $shortner->index)
			->set('store_slug', $shortner->slug)
			->set('store_url', $store_url)
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('store_time_created', $event->getTime());

		return $this->data->run();
	}

	public function store_update($store_id, $url) {
		$event = $this->call('store', 'update');

		if (! filter_var($url, FILTER_VALIDATE_URL))
			throw new DataException('Not a valid URL');

		//same domain ?

		$this->data->fetch('store', ['store_url']);

		$row = $this->data->run();

		//sanitize with fragment

		$store_url = $url;

		if ($row['store_url'] === $store_url)
			return false;

		$this->shadow($event, 'store', ['store_id' => $store_id]);

		$this->data->update('store');

		$shortner = Shortner::shortner($store_url);

		$this->data
			->where('store_id', $store_id)
			->set('store_index', $shortner->index)
			->set('store_slug', $shortner->slug)
			->set('store_url', $store_url)
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('store_time_modified', $event->getTime());

		return $this->data->run();
	}

	public function store_delete($store_id) {
		$event = $this->call('store', 'delete');

		$this->shadow($event, 'store', ['store_id' => $store_id]);

		$this->data->remove('store');

		$this->data->where('store_id', $store_id);

		return $this->data->run();
	}

	public function domain_get($domain_id) {
		$event = $this->call('domain', 'get', false);

		$this->data->fetch('domains', ['domain_master', 'domain_service']);

		$this->data->where('domain_id', $domain_id);

		return $this->data->run();
	}

	public function domain_list($user_id) {
		$event = $this->call('domain', 'list', false);

		$this->data->fetch('domains', ['domain_id', 'domain_master', 'domain_service']);

		$this->data->where('user_id', $user_id);

		return $this->data->run();
	}

	public function domain_add($master, $service) {
		$event = $this->call('domain', 'add');

		if (! filter_var($master, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new DataException('Not a valid master domain');

		if (! filter_var($service, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new DataException('Not a valid service domain');

		$this->data->add('domains');

		$user_id = $this->user->id;
		$domain_id = $this->uniqid('domain', $event);

		$this->data
			->set('domain_id', $domain_id)
			->set('user_id', $user_id)
			->set('domain_master', $master)
			->set('domain_service', $service)
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('domain_time_created', $event->getTime());

		return $this->data->run();
	}

	public function domain_update($domain_id, $master, $service) {
		$event = $this->call('domain', 'update');

		if ($master && ! filter_var($master, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new DataException('Not a valid master domain');

		if ($service && ! filter_var($service, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new DataException('Not a valid service domain');

		$this->shadow($event, 'domains', ['domain_id' => $domain_id]);

		$this->data->update('domains');

		$this->data->where('domain_id', $domain_id);

		$master || $this->data->set('domain_master', $master);
		$service || $this->data->set('domain_service', $service);

		$this->data
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('domain_time_modified', $event->getTime());

		return $this->data->run();
	}

	public function domain_delete($domain_id, $purge) {
		$event = $this->call('domain', 'delete');

		$this->shadow($event, 'domains', ['domain_id' => $domain_id]);

		$this->data->remove('domains');

		$this->data->set('domain_id', $domain_id);

		return $this->data->run();
	}

	public function user_get_by_id($user_id) {
		$event = $this->call('user', 'get_by_id', false);

		$this->data->fetch('users', [
			'user_id',
			'user_acl',
			'user_pending',
			'user_email',
			'user_name',
			'user_notify'
		]);

		$this->data->where('user_id', $user_id);

		return $this->data->run();
	}

	public function user_get_by_name($user_name) {
		$event = $this->call('user', 'get_by_name', false);

		$this->data->fetch('users', ['user_name']);

		$this->data->where('user_name', $user_name);

		return $this->data->run();
	}

	public function user_list() {
		$event = $this->call('user', 'list', false);

		$this->data->fetch('users', [
			'user_id',
			'user_acl',
			'user_pending',
			'user_email',
			'user_name',
			'user_notify'
		]);

		return $this->data->run();
	}

	public function user_add($user_acl, $user_email, $user_name, $user_password, $user_notify) {
		$event = $this->call('user', 'add');

		if (! filter_var($user_email, FILTER_VALIDATE_EMAIL))
			throw new DataException('Not a valid e-mail address');

		$user_email = filter_var($user_email, FILTER_SANITIZE_EMAIL);

		//check & sanitize
		$user_name = trim($user_name);

		if ($this->user_get_by_name($user_name))
			throw new DataException('User name already exists');

		$user_acl = $user_acl ? $user_acl : $this->config["Network"]["nwuseracl"];

		$this->data->add('users');

		$user_id = $this->uniqid('user', $event);
		$user_pass = password_hash($user_password, PASSWORD_DEFAULT);
		$user_pending = $this->pending('activation');
		$user_notify = $user_notify ? (int) $user_notify : 0;

		$this->data
			->set('user_id', $user_id)
			->set('user_acl', $user_acl)
			->set('user_pending', $user_pending, Shortner::VALUE_ARR)
			->set('user_email', $user_email)
			->set('user_name', $user_name)
			->set('user_pass', $user_pass)
			->set('user_notify', $user_notify, Shortner::VALUE_INT)
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('user_time_created', $event->getTime());

		//auth
		//mail

		return $this->data->run();
	}

	public function user_update($user_id, $user_acl, $user_email, $user_name, $user_password, $user_notify) {
		$event = $this->call('user', 'update');

		if (! $user_id)
			$user_id = $this->user->id;

		if ($user_email) {
			if (! filter_var($user_email, FILTER_VALIDATE_EMAIL))
				throw new DataException('Not a valid e-mail address');

			$user_email = filter_var($user_email, FILTER_SANITIZE_EMAIL);
		}

		if ($user_name) {
			//check & sanitize
			$user_name = trim($user_name);

			if ($this->user_get_by_name($user_name))
				throw new DataException('User name already exists');
		}

		$this->shadow($event, 'users', ['user_id' => $user_id]);

		$this->data->update('users');

		$this->data->where('user_id', $user_id);

		if ($user_pass)
			$user_pass = password_hash($user_password, PASSWORD_DEFAULT);

		$user_acl || $this->data->set('user_acl', $user_acl, Shortner::VALUE_ARR);
		$user_email || $this->data->set('user_email', $user_email);
		$user_name || $this->data->set('user_name', $user_name);
		$user_pass || $this->data->set('user_pass', $user_pass);

		$this->data
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('user_time_modified', $event->getTime());

		//auth
		//mail

		return $this->data->run();
	}

	public function user_delete($user_id, $purge) {
		$event = $this->call('user', 'delete');

		$this->shadow($event, 'users', ['user_id' => $user_id]);

		$this->data->remove('users');

		$this->data->set('user_id', $user_id);

		return $this->data->run();
	}

	public function user_activation($email, $token) {
		$event = $this->call('user', 'activation', true, false);

		if (! filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new DataException('Not a valid e-mail address');

		//email privacy another public ID
		//validate & sanitize

		$user_email = filter_var($email, FILTER_SANITIZE_EMAIL);
		$activation_token = $token;

		$this->data->fetch('users', ['user_id', 'user_time_created', 'user_pending'], true);

		$this->data->where('user_email', $user_email);

		$row = $this->data->run();

		if (empty($row))
			throw new DataException('Unknown user');

		$user_id = $row['user_id'];
		$user_created_epoch = strtotime($row['user_time_created']);
		$user_pending = empty($row['user_pending']) ? false : json_decode($row['user_pending']);

		$action_lifetime = (int) $this->config['Network']['nwuseractionlifetime'];
		$user_action_lifetime = ($user_created_epoch + $action_lifetime);

		if (! $user_pending)
			throw new DataException('User already activated');
		else if ($event->epoch > $user_action_lifetime)
			throw new DataException('User activation expired');
		else if ($user_pending->digest !== $token)
			throw new DataException('Bad request');

		$this->shadow($event, 'users', ['user_id' => $user_id]);

		$this->data->update('users');

		$this->data->where('user_id', $user_id);

		$this->data->set('user_pending', NULL, Shortner::VALUE_NULL);

		$this->data
			->set('event', $event->getToken(), Shortner::VALUE_ARR)
			->set('user_time_modified', $event->getTime());

		return $this->data->run();
	}

	public function user_match($user_email, $user_name, $user_password) {
		$this->data->fetch('users', ['user_id', 'user_acl', 'user_name', 'user_pass'], true);

		if ($user_email) $this->data->where('user_email', $user_email);
		else $this->data->where('user_name', $user_name);

		$row = $this->data->run();

		if (isset($row['user_id']))
			$row['user_match'] = password_verify($user_password, $row['user_pass']);

		return $row;
	}

	public function install($user_email, $user_name, $user_password) {
		$this->call('', 'install', false, true);

		if (! $this->config['Network']['nwsetup'])
			throw new DataException('Bad request');

		$this->data->fetch('users', ['user_id']);

		$this->data->count()->limit(1);

		$count = $this->data->run();

		if (! empty($count))
			throw new DataException('Already installed');

		$user_acl = '*';
		$user_notify = true;

		$row = $this->user_add($user_acl, $user_email, $user_name, $user_password, $user_notify);

		return $row;
	}

	public function exploiting() {
		if (! $this->config['Network']['nwapitest'])
			throw new DataException('Bad request');

		return \urls\ROUTES;
	}

	protected function shadow($event, $collection, $id_key) {
		if (! $this->enable_shadow) return NULL;

		$key = key($id_key);

		return $this->data->shadow($collection, 'shadows', $event,
			[[$key, $id_key[$key], Shortner::VALUE_STR]],
			[
				['event', 'token', Shortner::VALUE_ARR],
				['shadow_time', 'time', Shortner::VALUE_STR],
				['shadow_blob', 'blob', Shortner::VALUE_ARR]
			]
		);
	}

	protected function pending($action) {
		$digest = random_bytes(8);
		$digest = bin2hex($digest);

		return ['action' => $action, 'digest' => $digest];
	}

	protected function uniqid($callec, $event) {
		$blob = "{$callec}|{$event->epoch}|{$event->hash}";

		return md5($blob);
	}

	//
	// ACL
	//
	//
	// user
	//
	//       NULL === ["store", "domains"] === $config['Network']['nwuseracl']
	//       ["store", "domains", "users"]
	//       {"store": ["list", "query", "get", "add"], "domains"}
	//
	//
	// super user
	//
	//       {"*", "store": "*", "domains": "*"}
	//       ["*"] === {"store": "*", "domains": "*", "users": "*"}
	//       {"*", "store": ["*", "list", "query", "get", "add"], "domains": "*", "users": "*"}
	//       {"*", "store": ["list", "query", "get", "add"], "domains": "*"}
	//

	protected function authorize() {
		$auth = new Authentication($this->config);

		try {
			if ($auth->isAuthorized()) $this->user = $auth->getUserData();
			else throw new Exception('Unauth');
		} catch (Exception $error) {
			throw new DataException('Unauth request'/*, $error*/);
		}
	}

	protected function call($callec, $callea, $write = true, $need_preauth = true) {
		if ($need_preauth && $this->need_preauth) {
			$this->authorize();

			$this->need_preauth = true;
		} else {
			$this->need_preauth = false;
		}

		$event = new Logger($callec, $callea, $write);

		return $event;
	}
}


class Logger implements LoggerInterface {
	private $callee, $event, $time;
	public $epoch, $hash;

	public function __construct($callec, $callea, $write = true) {
		$time = $_SERVER['REQUEST_TIME'];

		$this->callee = "{$callec}_{$callea}";
		$this->epoch = $time;
		$this->time = date('c', $time);
		$this->event = new stdClass;
		$this->event->time = $time;
		$this->event->callea = $callea;

		if ($write) {
			$hash = random_bytes(4);
			$hash = bin2hex($hash);

			$this->hash = $hash;
			$this->event->hash = $hash;
		}
	}

	public function getToken() {
		return (array) $this->event;
	}

	public function getTime() {
		return $this->time;
	}
}

class DataException extends Exception {}

class Authentication implements AuthenticationInterface {
	private $config, $data, $connection, $prefix;

	public function __construct($config, $data = NULL, $connection = NULL) {
		$this->config = $config;
		$this->data = $data;
		$this->connection = $connection;

		$this->prefix = __NAMESPACE__;

		$this->transaction();
	}

	public function __destruct() {
		$this->commit();
	}

	public function transaction() {
		ini_set('session.use_strict_mode', 1);

		session_cache_limiter('nocache');
		session_cache_expire(0);

		//same origin

		session_start();

		if ($GLOBALS['debug_session']) var_dump($_SESSION);

		if (isset($_SESSION["{$this->prefix}"])) {
			session_regenerate_id();
		} else {
			session_create_id("{$this->prefix}-");

			$_SESSION["{$this->prefix}"] = microtime();
		}
	}

	public function commit() {
		session_commit();
	}

	public function flush() {
		session_destroy();
		session_start();
	}

	public function get($key) {
		if (isset($_SESSION["{$this->prefix}-{$key}"]))
			return $_SESSION["{$this->prefix}-{$key}"];

		return NULL;
	}

	public function set($key, $value) {
		$_SESSION["{$this->prefix}-{$key}"] = $value;
	}

	public function authorize($user_email, $user_name, $user_password) {
		if (! $this->data || ! $this->connection)
			throw new Exception('Data');

		$this->connection && $this->connection->connect();

		$auth = $this->data->user_match($user_email, $user_name, $user_password);

		$this->connection && $this->connection->disconnect();

		if ($auth && $auth['user_match'] === true) {
			$this->setAuthorization(true);
			$this->setUserData($auth);

			if ($GLOBALS['debug_session']) var_dump($_SESSION);

			return true;
		} else {
			$this->setAuthorization(false);
		}

		return false;
	}

	public function unauthorize() {
		$this->flush();
	}

	public function isAuthorized() {
		return $this->get('authorized') ? true : false;
	}

	public function setAuthorization($authorized) {
		if ($authorized) $this->set('authorized', true);
		else $this->flush();
	}

	public function setUserData($data) {
		$this->set('-data-id', $data['user_id']);
		$this->set('-data-acl', $data['user_acl']);
		$this->set('-data-name', $data['user_name']);
	}

	public function getUserData() {
		$data = new stdClass;

		$data->id = $this->get('-data-id');
		$data->acl = $this->get('-data-acl');
		$data->name = $this->get('-data-name');

		return $data;
	}
}


class APIException extends Exception {}

/**
 * @class
 */
class API implements ApiInterface {
	public function __construct() {
		if (! class_exists('\urls\UrlsData'))
			throw new Error('Undef class UrlsData');

		if (! defined('\urls\ROUTES'))
			throw new Error('Undef constant ROUTES');

		ini_set('expose_php', 0);
		ini_set('enable_postdata_reading', 1);
		ini_set('post_max_size', '128B');
			ini_set('file_uploads', 0);
			ini_set('upload_max_filesize', 0);
			ini_set('max_file_uploads', 0);
		ini_set('variables_order', 'GPS');
		ini_set('http.request.datashare.cookie', 0);
		ini_set('cgi.fix_pathinfo', 1);

		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		//-TEMP
		$GLOBALS['debug_data'] = false;
		$GLOBALS['debug_session'] = false;
		//-TEMP

		$this->shortner = new \urls\UrlsShortner;
		$this->config = $this->shortner->config;
		$this->db = new \urls\UrlsDatabase($this->config['Database'], \urls\UrlsData::COLLECTION_TEMPLATE); //
		$this->data = new \urls\UrlsData($this->config, $this->db);
		$this->routes = \urls\ROUTES;

		//same origin

		$path_info = empty($_SERVER['PATH_INFO']) ? NULL : $_SERVER['PATH_INFO'];
		$method = $_SERVER['REQUEST_METHOD'];
		$endpoint = $this->getPathInfo($path_info);

		$this->router($method, $endpoint);
	}

	public function __destruct() {
		$this->db->disconnect();

		restore_error_handler();
		restore_exception_handler();
	}

	public function error($errno, $errstr, $errfile, $errline) {
		$exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);

		error_log($exception);

		throw new Exception('API Internal Error');

		return false;
	}

	public function exception($exception) {
		$msg = $exception->getMessage();

		header('Status: 500', true, 500);

		$this->response(false, $msg);

		error_log($msg);
	}

	public function getPathInfo($path_info) {
		if (! $path_info) return '/';

		if ($sq = strpos('?', $path_info))
			$path_info = substr($path_info, 0, $sq);
		
		$path_info = rtrim($path_info, '/');

		return $path_info;
	}

	public function authenticate($user_email, $user_name, $user_password) {
		$auth = new Authentication($this->config, $this->data, $this->db);

		try {
			return $auth->authorize($user_email, $user_name, $user_password);
		} catch (Exception $error) {
			//var_dump($error);

			throw new APIException('authenticate'/*, $error*/);
		}
	}

	public function isAuthenticated() {
		$auth = new Authentication($this->config);

		try {
			if ($auth->isAuthorized()) return true;
			else return false;
		} catch (Exception $error) {
			//var_dump($error);

			throw new APIException('isAuthenticated'/*, $error*/);
		}
	}

	public function router($method, $endpoint) {
		if (! isset($this->routes[$endpoint]) || ! isset($this->routes[$endpoint][$method]))
			return $this->unreachable();

		$need_auth = true;

		if (isset($this->routes[$endpoint][$method]['auth']))
			$need_auth = $this->routes[$endpoint][$method]['auth'];

		if (isset($this->routes[$endpoint][$method]['access'])) {
			return $this->request($this, 'authenticate', $_REQUEST);
			//return $this->request($this, 'authenticate', $_POST);
		} else if (isset($this->routes[$endpoint][$method]['setup'])) {
			if ($this->config['Network']['nwsetup']) return $this->install($method, $endpoint);
			else return $this->notFound();
		} else if (isset($this->routes[$endpoint][$method]['call'])) {
			if (! $need_auth) $this->allow();
			else if ($this->isAuthenticated()) $this->allow();
			else return $this->deny();
		} else {
			return $this->unreachable();
		}

		$call = $this->routes[$endpoint][$method]['call'];

		if ($method === 'GET') {
			$body = $_GET;
		} else if ($method !== 'POST') {
			$body = []; 
			$bodyraw = file_get_contents('php://input');

			parse_str($bodyraw, $body);
		} else {
			$body = $_POST;
		}

		if ($call && method_exists($this->data, $call))
			return $this->request($this->data, $call, $body);

		return $this->notFound();
	}

	public function response($status, $data) {
		$output = ['status' => $status, 'data' => 0];

		if ($status && empty($data)) $this->noContent();
		else if (is_bool($data)) $output['data'] = (int) $data;
		else $output['data'] = $data;

		echo json_encode($output);
	}

	public function request($func, $call, $request) {
		$_method_params_transfunc = function(&$param, $i) {
			$param = $param->name;
		};

		try {
			$method = new ReflectionMethod($func, $call);

			if (! $method->isPublic())
				throw new APIException('Bad request');

			$request_params = array_keys($request);
			$method_params = $method->getParameters();

			array_walk($method_params, $_method_params_transfunc);

			//-TEMP
			if (isset($_SERVER['PATH_INFO']) && ! empty($method_params) && empty($request_params))
			 	throw $this->raiseParametersMissing($method_params);
			// if (count($request_params) < count($method_params))
			// 	throw $this->raiseParametersMissing($method_params);
			//-TEMP

			$params_diff = array_diff($request_params, $method_params);

			if (! empty($params_diff))
				throw $this->raiseParametersWrong($params_diff);

			$request_params = array_filter($request);

			if ($request_params !== $request)
				throw $this->raiseParametersMissing($method_params);

			$method_params = array_fill_keys($method_params, '');
			$request = array_replace($method_params, $request);

			//url decode

			$data = call_user_func_array([$func, $call], $request);
		} catch (APIException $error) {
			$msg = sprintf('Uncaught call. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (DataException $error) {
			$msg = sprintf('Error. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (Error $error) {
			trigger_error($error);
		}

		$this->response(true, $data);

		exit;
	}

	public function install($method, $endpoint) {
		$call = $this->routes[$endpoint][$method]['call'];

		// try {
		 	if ($call && method_exists($this->data, $call)) {
		 		$this->allow();

		 		return $this->request($this->data, $call, $_GET);
		// 		return $this->request($this->data, $call, $_POST);
			}
		// } catch (Exception $error) {
		// 	trigger_error($error);
		// }

		return $this->unreachable();
	}

	public function allow() {
		$this->db->connect();

		header('Status: 200', true, 200);
	}

	public function deny() {
		header('Status: 401', true, 401);
		exit;
	}

	public function busy() {
		header('Status: 503', true, 503);
		exit;
	}

	public function unreachable() {
		header('Status: 403', true, 403);
		exit;
	}

	public function notFound() {
		header('Status: 404', true, 404);
		exit;
	}

	public function noContent() {
		//header('Status: 204', true, 204);
	}

	public function raiseParametersMissing($method_params) {
		$msg = sprintf('Missing parameters, expected: %s', implode(' or ', $method_params));

		return new APIException($msg);
	}

	public function raiseParametersWrong($params_diff) {
		$props = [];

		foreach ($params_diff as $param) {
			if (strlen($param) > 16) return new APIException('Unexpected behaviour');

			return new APIException(sprintf('Unknown parameter: %s', $param));
		}
	}
}
