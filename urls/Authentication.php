<?php
/**
 * urls/Authentication.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;
use \stdClass;


interface AuthenticationInterface extends \framework\AuthenticationInterface {
	public function authorize(object $auth);
	public function setUserData(array $cth);
	public function getUserData();
}

class Authentication extends \framework\Authentication implements \urls\AuthenticationInterface {
	protected object $dbo;
	protected object $cth;

	public function __construct(array $config, object $database, object $controller) {
		$this->config = $config;
		$this->dbo = $database;
		$this->cth = $controller;

		$this->prefix = __NAMESPACE__;
	}

	public function authorize(object $auth) {
		if (! $this->cth || ! $this->dbo)
			throw new Exception('Data');

		$this->dbo && $this->dbo->connect();

		$auth = $this->cth->user_match($auth->email, $auth->name, $auth->password);

		$this->dbo && $this->dbo->disconnect();

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

	public function setUserData(array $cth) {
		$this->set('-cth-id', $cth['user_id']);
		$this->set('-cth-acl', $cth['user_acl']);
		$this->set('-cth-name', $cth['user_name']);
	}

	public function getUserData() {
		$cth = new stdClass;

		$cth->id = $this->get('-cth-id');
		$cth->acl = $this->get('-cth-acl');
		$cth->name = $this->get('-cth-name');

		return $cth;
	}
}
