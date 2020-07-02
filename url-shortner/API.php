<?php
/**
 * framework/API.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;
use \ErrorException;
use \Error;
use \ReflectionMethod;
use \stdClass;

use \urls\Urls_Config;
use \urls\Urls_Database;
use \urls\Urls_Controller;
use \urls\Urls_Shortner;
use \urls\Urls_Authentication;


/**
 * Interface for api class
 *
 * @interface
 */
interface ApiInterface {

}

class APIException extends Exception {}

/**
 * @class
 */
class API implements ApiInterface {
	private $config, $db, $controller, $routes, $shortner;

	public function __construct() {
		if (! defined('\urls\ROUTES'))
			throw new Error('Undef constant ROUTES');

		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);

		//-TEMP
		$GLOBALS['debug_data'] = false;
		$GLOBALS['debug_session'] = false;
		//-TEMP

		$this->config = new \urls\Urls_Config();
		$this->config = $this->config->get();

		$this->db = new \urls\Urls_Database(
			$this->config['Database'],
			\urls\COLLECTION_TEMPLATE,
			\urls\COLLECTION_SCHEMA
		);
		$this->controller = new \urls\Urls_Controller($this->config, $this->db);
		$this->routes = \urls\ROUTES;

		$this->shortner = new \urls\Urls_Shortner;

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

	public function authenticate($user_email, $user_name, $user_password) {
		$auth = new \urls\Urls_Authentication($this->config, $this->controller, $this->db);

		try {
			return $auth->authorize($user_email, $user_name, $user_password);
		} catch (Exception $error) {
			// var_dump($error);
			error_log($error);

			throw new APIException('authenticate'/*, $error*/);
		}
	}

	public function isAuthenticated() {
		$auth = new \urls\Authentication($this->config);

		try {
			if ($auth->isAuthorized()) return true;
			else return false;
		} catch (Exception $error) {
			// var_dump($error);
			error_log($error);

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

		if ($call && method_exists($this->controller, $call))
			return $this->request($this->controller, $call, $body);

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
		} catch (ControllerException $error) {
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
		 	if ($call && method_exists($this->controller, $call)) {
		 		$this->allow();

		 		return $this->request($this->controller, $call, $_GET);
		// 		return $this->request($this->controller, $call, $_POST);
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
