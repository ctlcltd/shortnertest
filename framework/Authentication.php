<?php
/**
 * framework/Authentication.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;


interface AuthenticationInterface {
	public function transaction();
	public function commit();
	public function flush();
	public function get(string $key);
	public function set(string $key, $value);
	public function authorize(object $auth);
	public function unauthorize();
	public function isAuthorized();
	public function setAuthorization(object $auth);
}

class Authentication implements AuthenticationInterface {
	public array $config;
	public string $prefix;

	public function __construct(array $config) {
		$this->config = $config;

		$this->prefix = __NAMESPACE__;
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

	public function get(string $key) {
		if (isset($_SESSION["{$this->prefix}-{$key}"]))
			return $_SESSION["{$this->prefix}-{$key}"];

		return NULL;
	}

	public function set(string $key, $value) {
		$_SESSION["{$this->prefix}-{$key}"] = $value;
	}

	public function authorize(object $auth) {
		if ($auth->flag) {
			$this->setAuthorization(true);

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
}
