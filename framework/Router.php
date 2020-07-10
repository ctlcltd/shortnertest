<?php
/**
 * framework/Router.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;

use \framework\Schema;
use \framework\SchemaField;
use \framework\SchemaMask;


class Router_SchemaField_RouteField extends SchemaField {
	public string $call;
	public bool $access;
	public bool $setup;
	public bool $auth;
}

class Router_SchemaField_Route extends SchemaField {
	// public string $method;
}

class Router_SchemaField_Schema extends SchemaField {
	public array $routes;
}


class RouterSchema extends Schema {
	public function __construct() {
		$this->_route = new Router_SchemaField_Route;
		$this->_field = new Router_SchemaField_RouteField;
	}
}


class Router_SchemaMask extends SchemaMask {
}

class Router_SchemaMask_Route extends SchemaMask {
	public function method($method) {
		return $this->deep[1]->field($this, $this->deep[0], $method);
	}
}

class Router_SchemaMask_Field extends SchemaMask {
	public function method($method) {
		return $this->deep[1]->field($this->deep[2], $this->deep[0], $method);
	}
}


class RouterRoute {
}

class RouterField {
}


interface RouterInterface {
	public function __set(string $key, $value);
	public function __routes();
	public function route(string $path);
	public function field(object $mask_field, string $path, string $method);
	public function begin();
	public function routing(string $method, string $endpoint);
	public function getPathInfo($path_info);
	public function needAuthentication(bool $only_check);
	public function forwardResponseHeader(int $reponse_code);
	public function forwardRequest(string $call, array $body);
	public function forwardSetup(string $method, string $endpoint);
}

class Router implements RouterInterface {
	const HTTP_HEADER_ALLOW = 200;
	const HTTP_HEADER_NO_CONTENT = 204;
	const HTTP_HEADER_DENY = 401;
	const HTTP_HEADER_UNREACHABLE = 403;
	const HTTP_HEADER_NOT_FOUND = 404;
	const HTTP_HEADER_INTERNAL_ERROR = 500;
	const HTTP_HEADER_BUSY = 503;

	protected object $_schema;
	protected object $_route;
	protected object $_field;
	private bool $blind = false;
	public array $routes;

	public function __construct(array $config, object $lawyer) {
		$this->_schema = new Router_SchemaField_Schema;
		$this->_route = new Router_SchemaField_Route;
		$this->_field = new Router_SchemaField_RouteField;
		
		$this->config = $config;
		$this->lawyer = $lawyer;

		//same origin

		$this->__routes();
		// var_dump($this);

		$this->blind = true;
	}

	public function __set(string $key, $value) {
		static $mask;

		if ($this->blind)
			throw new Exception('Cannot override initial set properties.');

		if (! isset($mask))
			$mask = new Router_SchemaMask(clone $this->_schema, $this);

		$mask->field->{$key} = $this->{$key} = $value;
	}

	public function __routes() {
		//context
		//map

		$this->route('/')
			->method('GET')->call('exploiting')->auth(false)
			->method('POST')->access(true);

		$this->route('/setup')
			->method('GET')->setup(true);

		$this->route('/sample')
			->method('GET')->call('sample');
	}

	public function route(string $path) {
		if (isset($this->routes[$path]))
			$route = $this->routes[$path];
		else
			$route = new RouterRoute;

		$mask = new Router_SchemaMask_Route(clone $this->_route, $route, $path, $this);

		$this->routes[$path] = $mask->field;

		return $mask;
	}

	public function field(object $mask_field, string $path, string $method) {
		if (isset($this->routes[$path]->{$method}))
			$field = $this->routes[$path]->{$method};
		else
			$field = new RouterField;

		$mask = new Router_SchemaMask_Field(clone $this->_field, $field, $path, $this, $mask_field);

		$this->routes[$path]->{$method} = $mask->field;

		return $mask;
	}

	public function begin() {
		$path_info = empty($_SERVER['PATH_INFO']) ? NULL : $_SERVER['PATH_INFO'];
		$method = $_SERVER['REQUEST_METHOD'];
		$endpoint = $this->getPathInfo($path_info);

		$this->routing($method, $endpoint);
	}

	public function routing(string $method, string $endpoint) {
		if (! isset($this->routes[$endpoint]) || ! isset($this->routes[$endpoint]->{$method}))
			return $this->forwardResponseHeader(self::HTTP_HEADER_UNREACHABLE);

		$need_auth = true;

		if (isset($this->routes[$endpoint]->{$method}->{'auth'}))
			$need_auth = $this->routes[$endpoint]->{$method}->{'auth'};

		if (isset($this->routes[$endpoint]->{$method}->{'access'})) {
			return $this->needAuthentication(false);
		} else if (isset($this->routes[$endpoint]->{$method}->{'setup'})) {
			if ($this->config['Network']['setup']) return $this->requestSetup($method, $endpoint);
			else return $this->forwardResponseHeader(self::HTTP_HEADER_NOT_FOUND);
		} else if (isset($this->routes[$endpoint]->{$method}->{'call'})) {
			if (! $need_auth) $this->forwardResponseHeader(self::HTTP_HEADER_ALLOW);
			else if ($this->needAuthentication(true)) $this->forwardResponseHeader(self::HTTP_HEADER_ALLOW);
			else return $this->forwardResponseHeader(self::HTTP_HEADER_DENY);
		} else {
			return $this->forwardResponseHeader(self::HTTP_HEADER_UNREACHABLE);
		}

		if ($method === 'GET') {
			$body = $_GET;
		} else if ($method !== 'POST') {
			$body = []; 
			$bodyraw = file_get_contents('php://input');

			parse_str($bodyraw, $body);
		} else {
			$body = $_POST;
		}

		if ($call = $this->routes[$endpoint]->{$method}->{'call'})
			return $this->forwardRequest($call, $body);

		return $this->forwardResponseHeader(self::HTTP_HEADER_NOT_FOUND);
	}

	public function getPathInfo($path_info) {
		if (! $path_info) return '/';

		if ($sq = strpos('?', $path_info))
			$path_info = substr($path_info, 0, $sq);
		
		$path_info = rtrim($path_info, '/');

		return $path_info;
	}

	public function needAuthentication(bool $only_check = false) {
		return $this->lawyer->needAuthentication($only_check);
	}

	public function forwardResponseHeader($response_code) {
		return $this->lawyer->setHeader($response_code);
	}

	public function forwardRequest(string $call, array $body) {
		return $this->lawyer->allowRequest($call, $body);
	}

	public function forwardSetup(string $method, string $endpoint) {
		return $this->lawyer->setup($method, $endpoint);
	}
}
