<?php
namespace urls;

use \Exception;
use \ErrorException;
use \Error;
use \ReflectionMethod;
use \PDO;
use \PDOException;


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
	public function statement($command, $collection, $clauses);
	public function run();
	public function select($keys, $distinct);
	public function set($param, $value, $type);
	public function where($param, $value, $type, $condition);
	public function limit($param, $start, $end);
	public function prepare();
	public function fetch($table);
	public function add($table);
	public function update($table);
	public function remove($table);
}

/**
 * Interface for data class
 *
 * @interface
 */
interface DataInterface {
	public function store_add($domain_id, $url);
	public function store_delete($id);
	public function store_list($domain_id);
	public function domain_get($domain_id);
	public function domain_list($user_id);
	public function domain_add($user_id, $master, $service);
	public function domain_update($user_id, $master, $service);
	public function domain_delete($domain_id, $purge);
	public function user_get_by_id($user_id);
	public function user_get_by_name($user);
	public function user_add($name, $password);
	public function user_delete($user_id, $purge);
	public function user_authentication($user, $password);
}

/**
 * Interface for authenticator class
 *
 * @interface
 */
interface AuthenticatorInterface {
	// public function route_resolver($locale, $search);
	// public function name_resolver($locale, $search, $type, $subtype);
	// public function action_call();
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
			var_dump($error->getMessage());
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
				if (gettype($config[$section][$key]) !== $this->type($type)) {
					throw new Exception(sprintf('Wrong value: %s', $key));
				}
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

		$slug = hash('adler32', $index);

		return ['index' => $index, 'slug' => $slug];
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
	private $config, $template, $dbh;

	private const SQL_TEMPLATES = [
		'fetch' => 'SELECT %s FROM %s',
		'add' => 'INSERT INTO %s (%s) VALUES(%s)',
		'update' => 'UPDATE %s SET(%s) VALUES(%s)',
		'remove' => 'DELETE FROM %s'
	];

	protected $statement = [];

	function __construct($config_db, $collection_template) {
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

	public function statement($command, $collection, $clauses) {
		if (! isset($this->template[$collection]))
			throw new DatabaseException('Collection');			

		if (! in_array($command, ['fetch', 'add', 'update', 'remove']))
			throw new DatabaseException('Command DatabaseException');

		$this->command = $command;
		$this->table = $this->template[$collection];
		$this->statement = array_fill_keys($clauses, []);
	}

	public function run() {
		$this->prepare();

		$results = $this->sth->execute();

		if ($GLOBALS["debug_data"]) var_dump($this->dbh->errorInfo());

		if ($results && $this->command === 'fetch')
			$results = $this->sth->fetchAll(PDO::FETCH_ASSOC);

		$this->end();

		$this->statement = NULL;
		$this->sth = NULL;

		return $results;
	}

	public function select($keys, $distinct) {
		$this->statement['select'] = [
			'distinct' => $distinct ? true : false,
			'keys' => is_array($keys) ? $keys : NULL
		];
	}

	public function set($param, $value, $type) {
		$this->statement['set'][$param] = [
			'value' => $value,
			'type' => $type
		];
	}

	public function where($param, $value, $type, $condition = '') {
		$this->statement['where'][$param] = [
			'condition' => strtoupper($condition),
			'value' => $value,
			'type' => $type
		];
	}

	public function limit($param, $start, $end) {
		$this->statement['limit'][$param]['start'] = (int) $start;
		$this->statement['limit'][$param]['end'] = (int) $end;
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
		$_where_flat_transfunc = function(&$clause, $key, &$i) {
			if ($i++ && empty($clause['condition']))
				throw new DatabaseException('WHERE clause');

			$clause = "{$clause['condition']} {$key} = :{$key}";
		};

		$sql = self::SQL_TEMPLATES[$this->command];
		$values = [];

		if (isset($this->statement['set'])) {
			$params = array_keys($this->statement['set']);

			$vars = array_map($_key_prefix_transfunc, $params);

			$sql = sprintf($sql, $this->table, implode(', ', $params), implode(', ', $vars));

			$values += $this->statement['set'];
		}

		if (isset($this->statement['select'])) {
			$select = '';

			if ($this->statement['select']['distinct'])
				$select = 'DISTINCT ';

			if ($this->statement['select']['keys']) {
				$keys = array_values($this->statement['select']['keys']);

				$select .= implode(', ', $keys);
			} else {
				$select .= '*';
			}

			$sql = sprintf($sql, $select, $this->table);
		} else {
			$sql = sprintf($sql, $this->table);
		}

		if (isset($this->statement['where'])) {
			$where = $this->statement['where'];

			array_walk($where, $_where_flat_transfunc, 0);

			$sql .= ' WHERE' . implode(' ', $where);

			$values += $this->statement['where'];
		}

		$this->sth = $this->dbh->prepare($sql);

		foreach ($values as $param => $set) {
			if ($set['type'] === Shortner::VALUE_ARR) {
				$set['value'] = json_encode($set['value']);
				$set['type'] = Shortner::VALUE_STR;
			}

			$this->sth->bindValue(":{$param}", $set['value'], $set['type']);
		}

		if ($GLOBALS['debug_data']) {
			var_dump($sql);
			var_dump($this->sth->debugDumpParams());
		}
	}

	public function fetch($collection, $keys = NULL, $distinct = false) {
		$this->statement('fetch', $collection, ['where', 'limit']);
		$this->select($keys, $distinct);
	}

	public function add($collection) {
		$this->statement('add', $collection, ['set']);
	}

	public function update($collection) {
		$this->statement('update', $collection, ['set', 'where']);
	}

	public function remove($collection) {
		$this->statement('remove', $collection, ['set', 'where']);
	}
}


class DataException extends \Exception {}

/**
 * @class
 */
class Data implements DataInterface {
	private $config, $data;

	public const COLLECTION_TEMPLATE = [
		'store' => 'urls_store',
		'domains' => 'urls_domains',
		'users' => 'urls_users'
	];

	function __construct($config, $data) {
		$this->config = $config;
		$this->data = $data;
	}

	public function store_list($domain_id = "domain5ef778d51cc032.56503922") {
		$user_id = "admin5ef76aa12338e5.58992943";
		//auth
		$this->data->fetch('store', ['store_index', 'store_slug', 'store_url']);

		$this->data->where('user_id', $user_id, Shortner::VALUE_STR);
		$this->data->where('domain_id', $domain_id, Shortner::VALUE_STR, 'and');

		return $this->data->run();
	}

	public function store_add($domain_id = "domain5ef778d51cc032.56503922", $url) {
		//auth
		$this->data->add('store');

		$user_id = "admin5ef76aa12338e5.58992943";
		$store_id = uniqid('store', true);
		//validate
		$store_url = $url;
		$shortner = Shortner::shortner($store_url);

		$this->data->set('store_id', $store_id, Shortner::VALUE_STR);
		$this->data->set('user_id', $user_id, Shortner::VALUE_STR);
		$this->data->set('domain_id', $domain_id, Shortner::VALUE_STR);
		$this->data->set('store_index', $shortner['index'], Shortner::VALUE_STR);
		$this->data->set('store_slug', $shortner['slug'], Shortner::VALUE_STR);
		$this->data->set('store_url', $url, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function store_update($store_id, $url) {
		//auth
		$this->data->update('store');

		//validate
		$store_url = $url;
		$shortner = Shortner::shortner($store_url);

		$this->data->set('store_index', $shortner['index'], Shortner::VALUE_STR);
		$this->data->set('store_slug', $shortner['slug'], Shortner::VALUE_STR);
		$this->data->set('store_url', $store_url, Shortner::VALUE_STR);
		$this->data->where('store_id', $store_id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function store_delete($store_id) {
		//auth
		$this->data->remove('store');

		$this->data->where('store_id', $id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function domain_get($domain_id) {
		//auth
		$this->data->fetch('domains', ['domain_master', 'domain_service']);

		$this->data->where('domain_id', $domain_id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function domain_list($user_id) {
		//auth
		$this->data->fetch('domains', ['domain_master', 'domain_service']);

		$this->data->where('user_id', $user_id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function domain_add($user_id, $master, $service) {
		$this->data->add('domains');

		$user_id = "admin5ef76aa12338e5.58992943";
		$domain_id = uniqid("domain", true);

		$this->data->set('domain_id', $domain_id, Shortner::VALUE_STR);
		$this->data->set('user_id', $user_id, Shortner::VALUE_STR);
		$this->data->set('domain_master', $master, Shortner::VALUE_STR);
		$this->data->set('domain_service', $service, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function domain_update($domain_id, $master, $service) {
		$this->data->update('domains');

		$this->data->set('domain_id', $domain_id, Shortner::VALUE_STR);
		$this->data->set('domain_master', $master, Shortner::VALUE_STR);
		$this->data->set('domain_service', $service, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function domain_delete($domain_id, $purge) {
		//superuser or sameid
		$this->data->remove('domains');

		$this->data->set('domain_id', $domain_id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function user_get_by_id($user_id) {
		$this->data->fetch('users', ['user_name']);

		$this->data->where('user_id', $user_id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function user_get_by_name($user_name) {
		$this->data->fetch('users', ['user_name']);

		$this->data->where('user_name', $user_name, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function user_add($user_name, $user_password) {
		//superuser or sameid
		//acl?
		$user_name = trim($user_name);

		if (! empty($this->user_get_by_name($user_name)))
			throw new DataException('User name exists');

		//single data statement
		$this->data->add('users');

		$user_id = uniqid($user_name, true);
		$user_pass = password_hash($user_password, PASSWORD_DEFAULT);

		$this->data->set('user_id', $user_id, Shortner::VALUE_STR);
		$this->data->set('user_name', $user_name, Shortner::VALUE_STR);
		$this->data->set('user_pass', $user_pass, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function user_delete($user_id, $purge) {
		//superuser or sameid
		$this->data->remove('users');

		$this->data->set('user_id', $id, Shortner::VALUE_STR);

		return $this->data->run();
	}

	public function user_authentication($user_name, $user_password) {
		$this->data->fetch('user', ['user_pass']);

		$this->data->set('user_name', $name, Shortner::VALUE_STR);

		$row = $this->data->run();

		return ($row && password_verify($password, $row['user_pass']));
	}
}


class Authenticator implements AuthenticatorInterface {
	function __construct() {
		session_start();
	}

}


class APIException extends \Exception {}

/**
 * @class
 */
class API implements ApiInterface {
	function __construct() {
		if (! class_exists('\urls\UrlsData'))
			throw new Error('Undef class UrlsData');

		if (! defined('\urls\ROUTES'))
			throw new Error('Undef constant ROUTES');

		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		//-TEMP
		$GLOBALS['debug_data'] = false;
		//-TEMP

		$this->shortner = new \urls\UrlsShortner;
		$this->config = $this->shortner->config;
		$this->db = new \urls\UrlsDatabase($this->config['Database'], \urls\UrlsData::COLLECTION_TEMPLATE); //
		$this->data = new \urls\UrlsData($this->config, $this->db);
		$this->routes = \urls\ROUTES;

		if (isset($_SERVER['PATH_INFO'])) {
			$method = $_SERVER['REQUEST_METHOD'];
			$endpoint = $this->path($_SERVER['PATH_INFO']);

			//path info length
			$this->router($method, $endpoint, $_SERVER['PATH_INFO']);
		} else {
			throw new Error('ERROR');
		}
	}

	function __destruct() {
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

		error_log($exception);
	}

	public function path($path_info) {
		if ($sq = strpos('?', $path_info))
			$path_info = substr($path_info, 0, $sq);

		$path_info = rtrim($path_info, '/');

		return $path_info;
	}

	public function auth() {
		
	}

	public function router($method, $endpoint, $uri) {
		//var_dump($endpoint, $method, $this->routes[$endpoint], $this->routes[$endpoint][$method]);

		if (! isset($this->routes[$endpoint]) && ! isset($this->routes[$endpoint][$method]))
			return $this->unreachable();

		$call = $this->routes[$endpoint][$method]['call'];
		$auth = $this->routes[$endpoint][$method]['auth'];

		if ($auth && ! $this->auth()) {
			$this->deny();

			exit;
		} else {
			$this->allow();
		}

		if ($call && method_exists($this->data, $call)) {
			//array length limit
			$this->request($call, $_REQUEST);

			exit;
		}

		$this->unreachable();

		exit;
	}

	public function response($status, $data) {
		$output = [ 'status' => $status, 'data' => 0 ];

		if ($status && empty($data)) $this->no_content();
		else if (is_bool($data)) $output['data'] = (int) $data;
		else $output['data'] = $data;

		echo json_encode($output);
	}

	public function request($call, $request) {
		$_method_params_transfunc = function(&$param, $i) {
			$param = $param->name;
		};

		try {
			$request_params = array_keys($request);

			$method = new ReflectionMethod($this->data, $call);
			$method_params = $method->getParameters();

			array_walk($method_params, $_method_params_transfunc);

			if (count($request_params) < count($method_params))
				throw $this->raise_parameters_missing($method_params);

			$params_diff = array_diff($request_params, $method_params);

			if (! empty($params_diff))
				throw $this->raise_parameters_wrong($params_diff);

			$request_params = array_filter($request);

			//array length limit
			if ($request_params !== $request)
				throw $this->raise_parameters_missing($method_params);

			$data = call_user_func_array([$this->data, $call], $request);
		} catch (APIException $error) {
			$msg = sprintf('Uncaught call. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (DataException $error) {
			$msg = sprintf('Error. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (Exception $error) {
			trigger_error($error);
		}

		$this->response(true, $data);

		exit;
	}

	public function allow() {
		$this->db->connect();

		header('Status: 200', true, 200);
	}

	public function deny() {
		header('Status: 403', true, 403);
	}

	public function unreachable() {
		header('Status: 404', true, 404);
	}

	public function no_content() {
		//header('Status: 204', true, 204);
	}

	public function raise_parameters_missing($method_params) {
		$msg = sprintf('Missing parameters, expected: %s', implode(' or ', $method_params));

		return new APIException($msg);
	}

	public function raise_parameters_wrong($params_diff) {
		$props = [];

		foreach ($params_diff as $param) {
			if (strlen($param) > 16) return new APIException('Unexpected behaviour');

			return new APIException(sprintf('Unknown parameter: %s', $param));
		}
	}
}
