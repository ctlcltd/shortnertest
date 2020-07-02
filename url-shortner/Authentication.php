<?php
/**
 * framework/Authentication.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;
use \stdClass;


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