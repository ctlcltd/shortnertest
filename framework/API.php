<?php
/**
 * framework/API.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \stdClass;
use \ReflectionMethod;
use \Exception;
use \ErrorException;
use \Error;

use \framework\App;
use \framework\Authentication;


interface ApiInterface {
	public function error(int $errno, string $errstr, string $errfile, int $errline);
	public function exception(object $exception);
	public function getPathInfo($path_info);
	public function authenticate(array $request);
	public function isAuthenticated();
	public function router(string $method, string $endpoint);
	public function response(bool $status, $data);
	public function request(object $func, string $call, array $request);
	public function install(string $method, string $endpoint);
	public function allow();
	public function deny();
	public function busy();
	public function unreachable();
	public function notFound();
	public function noContent();
	public function raiseParametersMissing(array $method_params);
	public function raiseParametersWrong(array $params_diff);
}

class APIException extends Exception {}

class API implements ApiInterface {
	public array $config;
	public object $dbo;
	public object $app;
	public array $routes;


	public function __construct() {
		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		$this->_temp_debug();


		$config = new Config(new ConfigSchema);
		$config->fromArray(\framework\CONFIG);
		$this->config = $config->get();

		$this->Authentication = '\framework\Authentication';
		$this->App = '\framework\App';

		$this->dbo = new stdClass;

		$this->app = new $this->App($this->config, $this->dbo);
		$this->ath = new $this->Authentication($this->config);

		$this->routes = \framework\ROUTES;

		//same origin

		$path_info = empty($_SERVER['PATH_INFO']) ? NULL : $_SERVER['PATH_INFO'];
		$method = $_SERVER['REQUEST_METHOD'];
		$endpoint = $this->getPathInfo($path_info);

		$this->router($method, $endpoint);
	}

	public function __destruct() {
		restore_error_handler();
		restore_exception_handler();
	}

	//-TEMP
	public function _temp_debug() {
		$GLOBALS['debug_data'] = false;
		$GLOBALS['debug_session'] = false;
	}
	//-TEMP

	public function error(int $errno, string $errstr, string $errfile, int $errline) {
		$exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);

		error_log($exception);

		throw new Exception('API Internal Error');

		return false;
	}

	public function exception(object $exception) {
		$msg = $exception->getMessage();

		header('Status: 500', true, 500);

		$this->response(false, $msg);

		error_log($exception);
		// error_log($msg);
	}

	public function getPathInfo($path_info) {
		if (! $path_info) return '/';

		if ($sq = strpos('?', $path_info))
			$path_info = substr($path_info, 0, $sq);
		
		$path_info = rtrim($path_info, '/');

		return $path_info;
	}

	public function authenticate(array $body) {
		$this->ath->transaction();

		try {
			$auth = new stdClass;
			$auth->flag = empty($body) ? false : true;

			$status = $response = $this->ath->authorize($auth);
		} catch (Exception $error) {
			error_log($error);

			$status = $response = false;

			throw $error;
		}

		$this->ath->commit();

		return $this->response($status, $response);
	}

	public function isAuthenticated() {
		$this->ath->transaction();

		try {
			if ($this->ath->isAuthorized()) return true;
			else return false;
		} catch (Exception $error) {
			error_log($error);

			throw $error;
		}

		$this->ath->commit();
	}

	public function router(string $method, string $endpoint) {
		if (! isset($this->routes[$endpoint]) || ! isset($this->routes[$endpoint][$method]))
			return $this->unreachable();

		$need_auth = true;

		if (isset($this->routes[$endpoint][$method]['auth']))
			$need_auth = $this->routes[$endpoint][$method]['auth'];

		if (isset($this->routes[$endpoint][$method]['access'])) {
			return $this->authenticate($_POST);
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

		if ($call && method_exists($this->app, $call))
			return $this->request($this->app, $call, $body);

		return $this->notFound();
	}

	public function response(bool $status, $response) {
		$output = ['status' => $status, 'data' => 0];

		if ($status && empty($response)) $this->noContent();
		else if (is_bool($response)) $output['data'] = (int) $response;
		else $output['data'] = $response;

		echo json_encode($output);
	}

	public function request(object $func, string $call, array $request) {
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
			// if (isset($_SERVER['PATH_INFO']) && ! empty($method_params) && empty($request_params))
			// 	throw $this->raiseParametersMissing($method_params);
			//-TEMP

			//-TEMP
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
		} catch (AppException $error) {
			$msg = sprintf('Error. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (Error $error) {
			trigger_error($error);
		}

		$this->response(true, $data);

		exit;
	}

	public function install(string $method, string $endpoint) {
		$call = $this->routes[$endpoint][$method]['call'];

		// try {
		 	if ($call && method_exists($this->app, $call)) {
		 		$this->allow();

		 		return $this->request($this->app, $call, $_GET);
		// 		return $this->request($this->app, $call, $_POST);
			}
		// } catch (Exception $error) {
		// 	trigger_error($error);
		// }

		return $this->unreachable();
	}

	public function allow() {
		$this->dbo->connect();

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

	public function raiseParametersMissing(array $method_params) {
		$msg = sprintf('Missing parameters, expected: %s', implode(' or ', $method_params));

		return new APIException($msg);
	}

	public function raiseParametersWrong(array $params_diff) {
		$props = [];

		foreach ($params_diff as $param) {
			if (strlen($param) > 16) return new APIException('Unexpected behaviour');

			return new APIException(sprintf('Unknown parameter: %s', $param));
		}
	}
}
