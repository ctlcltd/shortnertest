<?php
/**
 * framework/Controller.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;

use \urls\Authentication;
use \urls\Logger;


class ControllerException extends Exception {}

abstract class Controller {
	// private $config, $data, $enable_shadow;

	// public function __construct($config, $data) {
	// 	$this->config = $config;
	// 	$this->data = $data;
	// 	$this->enable_shadow = $this->config['Database']['dbshadow'];
	// 	$this->need_preauth = true;
	// }

	public function install() {
		$this->call('', 'install', false, true);

		if (! $this->config['Network']['nwsetup'])
			throw new ControllerException('Bad request');
	}

	public function exploiting() {
		if (! $this->config['Network']['nwapitest'])
			throw new ControllerException('Bad request');

		return \urls\ROUTES;
	}

	protected function shadow($event, $collection, $id_key) {
		if (! $this->enable_shadow) return NULL;

		$key = key($id_key);

		return $this->data->shadow($collection, 'shadows', $event,
			[[$key, $id_key[$key]]],
			[
				['event', 'token'],
				['shadow_time', 'time'],
				['shadow_blob', 'blob']
			]
		);
	}

	protected function pending($action) {
		$digest = random_bytes(8);
		$digest = bin2hex($digest);

		return ['action' => $action, 'digest' => $digest];
	}

	protected function uniqid($callec, $event) {
		$blob = "{$callec}|{$event->epoch}|{$event->hash}";

		return md5($blob);
	}

	//
	// ACL
	//
	//
	// user
	//
	//       NULL === ["store", "domains"] === $config['Network']['nwuseracl']
	//       ["store", "domains", "users"]
	//       {"store": ["list", "query", "get", "add"], "domains"}
	//
	//
	// super user
	//
	//       {"*", "store": "*", "domains": "*"}
	//       ["*"] === {"store": "*", "domains": "*", "users": "*"}
	//       {"*", "store": ["*", "list", "query", "get", "add"], "domains": "*", "users": "*"}
	//       {"*", "store": ["list", "query", "get", "add"], "domains": "*"}
	//

	protected function authorize() {
		$auth = new Authentication($this->config);

		try {
			if ($auth->isAuthorized()) $this->user = $auth->getUserData();
			else throw new Exception('Unauth');
		} catch (Exception $error) {
			throw new ControllerException('Unauth request'/*, $error*/);
		}
	}

	protected function call($callec, $callea, $write = true, $need_preauth = true) {
		if ($need_preauth && $this->need_preauth) {
			$this->authorize();

			$this->need_preauth = true;
		} else {
			$this->need_preauth = false;
		}

		$event = new Logger($callec, $callea, $write);

		return $event;
	}
}