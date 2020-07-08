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

use \urls\ConfigSchema;
use \urls\Shortner;
use \urls\Authentication;
use \urls\App;
use \urls\Virtual;


class API extends \framework\API implements \framework\APIInterface {
	private string $Router = '\framework\Router';
	private string $Authentication = '\urls\Authentication';
	private string $App = '\urls\App';
	private $shortner;

	public function __construct() {
		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		$this->_temp_debug();

		$config = new Config(new ConfigSchema);
		$config->fromIniFile(__DIR__ . '/../config.ini.php');
		$this->config = $config->get();

		$this->dbo = new Virtual($this->config);

		$this->app = new $this->App($this->config, $this->dbo);
		$this->ath = new $this->Authentication($this->config, $this->dbo, $this->app);

		$this->routes = \urls\ROUTES;
		$this->shortner = new Shortner;

		new $this->Router($this->config, $this);
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
