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

use \framework\ConfigSchema;
use \framework\Config;
use \framework\Router;
use \framework\Authentication;
use \framework\App;


interface ApiInterface {
	public function run();
	public function error(int $errno, string $errstr, string $errfile, int $errline);
	public function exception(object $exception);
	public function overrideEncoder(string $func_name, array $func_args);
	public function needAuthentication(bool $only_check);
	public function setHeader(int $reponse_code, bool $stop_exec);
	public function allowRequest(string $call, array $body);
	public function setup(string $method, string $endpoint);
	public function authenticate(array $request);
	public function isAuthenticated();
	public function request(object $func, string $call, array $request);
	public function response(bool $status, $data);
	public function raiseParametersMissing(array $method_params);
	public function raiseParametersWrong(array $params_diff);
}

class APIException extends Exception {}

class API implements ApiInterface {
	private string $Router = '\framework\Router';
	private string $Authentication = '\framework\Authentication';
	private string $App = '\framework\App';
	private $encoder_func = 'json_encode';
	private array $encoder_args;
	public array $config;
	public object $dbo;
	public object $rrh;
	public object $app;
	public object $ath;
	protected bool $autorun;
	protected int $header;
	protected array $response;

	public function __construct(array $config, bool $autorun = true) {
		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		$this->_temp_debug();


		$this->config = $config;

		$this->dbo = new stdClass;

		$this->rrh = new $this->Router($this->config, $this);
		$this->app = new $this->App($this->config, $this->rrh);
		$this->ath = new $this->Authentication($this->config);

		$this->autorun = $autorun;

		if ($autorun) $this->run();
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

	public function run() {
		$this->rrh->begin();
	}

	public function error(int $errno, string $errstr, string $errfile, int $errline) {
		$exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);

		error_log($exception);

		throw new Exception('API Internal Error');

		return false;
	}

	public function exception(object $exception) {
		$msg = $exception->getMessage();

		$this->setHeader(Router::HTTP_HEADER_INTERNAL_ERROR, false);

		$this->response(false, $msg);

		error_log($exception);
		// error_log($msg);
	}

	public function overrideEncoder(string $func_name, array $func_args = NULL) {
		if (is_callable($func_name)) {
			$this->encoder_func = $func_name;
			$this->encoder_args = $func_args;
		} else {
			throw new Exception('API Internal Error');
		}
	}

	public function needAuthentication(bool $only_check) {
		if ($only_check) return $this->isAuthenticated();

		return $this->authenticate($_POST);
	}

	public function setHeader(int $response_code, bool $stop_exec = NULL) {
		$this->header = $response_code;

		if ($this->autorun) {
			header("Status: {$response_code}", true, $response_code);

			if ((isset($stop_exec) && $stop_exec) && $response_code > 204)
				exit;
		}
	}

	public function allowRequest(string $call, array $body) {
		if (method_exists($this->app, $call)) {
			$this->dbo->connect();

			$this->request($this->app, $call, $body);
		}

		$this->setHeader(Router::HTTP_HEADER_UNREACHABLE);
	}

	public function setup(string $method, string $endpoint) {
		if (method_exists($this->app, 'install')) {
			$this->requestHeader(Router::HTTP_HEADER_ALLOW);

			$this->dbo->connect();

			return $this->request($this->app, 'install', $body);
		}

		$this->setHeader(Router::HTTP_HEADER_UNREACHABLE);
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

		$this->response($status, $response);

		if ($this->autorun)
			$this->printResponse();
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

			$this->call = [$func, $call];
			$this->route = $request;

			if ($this->autorun) $this->call();

			return $this->route;
		} catch (APIException $error) {
			$msg = sprintf('Uncaught call. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (Error $error) {
			trigger_error($error);
		}
	}

	public function call() {
		try {
			$data = call_user_func_array($this->call, $this->route);

			$this->response(true, $data);
		} catch (APIException $error) {
			$msg = sprintf('Uncaught call. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (AppException $error) {
			$msg = sprintf('Error. %s', $error->getMessage());

			throw new Exception($msg);
		} catch (Error $error) {
			trigger_error($error);
		}
	}

	public function response(bool $status, $response) {
		$output = ['status' => $status, 'data' => 0];

		if ($status && empty($response)) $this->noContent();
		else if (is_bool($response)) $output['data'] = (int) $response;
		else $output['data'] = $response;

		$this->response = $output;

		if ($this->autorun)
			$this->printResponse();

		return $this->response;
	}

	public function getRoute() {
		return $this->route;
	}

	public function getResponseHeader() {
		return $this->header;
	}

	public function getResponseRaw() {
		return $this->response;
	}

	public function printResponse() {
		try {
			echo call_user_func_array($this->encoder_func, [$this->response]);
		} catch (Error $error) {
			trigger_error($error);
		}
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
