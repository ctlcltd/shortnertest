<?php
/**
 * urls/API.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \stdClass;

use \framework\APIInterface;
use \framework\APIException;
use \framework\Config;
use \framework\Database;

use \urls\Shortner;
use \urls\Authentication;
use \urls\Controller;


class API extends \framework\API implements APIInterface {
	private $shortner;

	public function __construct() {
		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		$this->_temp_debug();


		$config = new Config(\urls\CONFIG_TEMPLATE);
		$config->fromIniFile(__DIR__ . '/../config.ini.php');
		$this->config = $config->get();

		$this->Authentication = '\urls\Authentication';
		$this->Controller = '\urls\Controller';

		$this->dbo = new Database($this->config['Database']);

		$this->cth = new $this->Controller($this->config, $this->dbo);
		$this->ath = new $this->Authentication($this->config, $this->dbo, $this->cth);

		$this->routes = \urls\ROUTES;
		$this->shortner = new Shortner;

		//same origin

		$path_info = empty($_SERVER['PATH_INFO']) ? NULL : $_SERVER['PATH_INFO'];
		$method = $_SERVER['REQUEST_METHOD'];
		$endpoint = $this->getPathInfo($path_info);

		$this->router($method, $endpoint);
	}

	public function __destruct() {
		restore_error_handler();
		restore_exception_handler();

		$this->dbo->disconnect();
	}

	public function authenticate(array $request) {
		$this->ath->transaction();

		try {
			$auth = new stdClass;
			$auth->email = empty($request['user_email']) ? '' : $request['user_email'];
			$auth->name = empty($request['user_name']) ? '' : $request['user_name'];
			$auth->password = empty($request['user_password']) ? '' : $request['user_password'];

			$status = $response = $this->ath->authorize($auth);
		} catch (Exception $error) {
			error_log($error);

			$status = $response = false;

			throw $error;
		}

		$this->ath->commit();

		return $this->response($status, $response);
	}
}
