<?php
/**
 * framework/Controller.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;


class ControllerException extends Exception {}

interface ControllerInterface {
	public function install();
	public function exploiting();
}

class Controller implements ControllerInterface {
	public array $config;
	public array $routes;

	public function __construct(array $config) {
		$this->config = $config;
		$this->routes = \framework\ROUTES;
	}

	public function install() {
		$this->call('', 'install', false, true);

		if (! $this->config['Network']['setup'])
			throw new ControllerException('Bad request');
	}

	public function exploiting() {
		if (! $this->config['Network']['api_test'])
			throw new ControllerException('Bad request');

		return $this->routes;
	}

	public function sample() {
		
	}
}
