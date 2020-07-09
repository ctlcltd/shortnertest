<?php
/**
 * framework/App.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;


class AppException extends Exception {}

interface AppInterface {
	public function install();
	public function exploiting();
}

class App implements AppInterface {
	public array $config;
	public array $routes;

	public function __construct(array $config, Router $router) {
		$this->config = $config;
		$this->router = $router;
	}

	public function install() {
		$this->call('', 'install', false, true);

		if (! $this->config['Network']['setup'])
			throw new AppException('Bad request');
	}

	public function exploiting() {
		if (! $this->config['Network']['api_test'])
			throw new AppException('Bad request');

		return $this->router->routes;
	}

	public function sample() {
		
	}
}
