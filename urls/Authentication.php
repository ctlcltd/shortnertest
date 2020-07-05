<?php
/**
 * urls/Authentication.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \stdClass;
use \Exception;


interface AuthenticationInterface extends \framework\AuthenticationInterface {
	public function authorize(object $auth);
	public function setUserData(array $app);
	public function getUserData();
}

class Authentication extends \framework\Authentication implements \urls\AuthenticationInterface {
	protected object $dbo;
	protected object $app;

	public function __construct(array $config, object $database, object $app) {
		$this->config = $config;
		$this->dbo = $database;
		$this->app = $app;

		$this->prefix = __NAMESPACE__;
	}

	public function authorize(object $auth) {
		$this->dbo && $this->dbo->connect();

		$row = $this->app->user_match($auth->email, $auth->name, $auth->password);

		$this->dbo && $this->dbo->disconnect();

		if ($row && $row['user_match'] === true) {
			$this->setAuthorization(true);
			$this->setUserData($row);

			if ($GLOBALS['debug_session']) var_dump($_SESSION);

			return true;
		} else {
			$this->setAuthorization(false);
		}

		return false;
	}

	public function setUserData(array $row) {
		$this->set('-user-id', $row['user_id']);
		$this->set('-user-acl', $row['user_acl']);
		$this->set('-user-name', $row['user_name']);
	}

	public function getUserData() {
		$user = new stdClass;

		$user->id = $this->get('-user-id');
		$user->acl = $this->get('-user-acl');
		$user->name = $this->get('-user-name');

		return $user;
	}
}
