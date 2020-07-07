<?php
/**
 * urls/Dummy.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;

use \framework\Logger;

use \urls\Authentication;


interface DummyInterface {
	public function shadow(object $event, string $collection, array $id_key);
	public function pending(string $action);
	public function uniqid(string $callec, object $event);
	public function call(string $callec, string $callea, bool $write, bool $need_preauth);
	public function password_hash(string $password);
	public function password_verify(string $password, string $hash);
}

class Dummy implements DummyInterface {
	const SHADOW_COLLECTION = 'urls_shadows';

	private array $config;
	private object $cth;
	private object $ath;
	private bool $enable_shadow;
	protected bool $need_preauth;

	public function __construct(array $config, object $database, object $app) {
		$this->config = $config;
		$this->cth = $app;
		$this->ath = new Authentication($config, $database, $app);
		$this->enable_shadow = $this->config['Database']['shadow'];
		$this->need_preauth = true;

		$this->user = NULL;
	}

	public function shadow(object $event, string $collection, array $id_key) {
		if (! $this->enable_shadow) return NULL;

		$key = key($id_key);

		return $this->cth->shadow($collection, self::SHADOW_COLLECTION, $event,
			[[$key, $id_key[$key]]],
			[
				['event', 'token'],
				['shadow_time', 'time'],
				['shadow_blob', 'blob']
			]
		);
	}

	public function pending(string $action) {
		$digest = random_bytes(8);
		$digest = bin2hex($digest);

		return ['action' => $action, 'digest' => $digest];
	}

	public function uniqid(string $callec, object $event) {
		$blob = "{$callec}|{$event->epoch}|{$event->hash}";

		return md5($blob);
	}

	public function call(string $callec, string $callea, bool $write = true, bool $need_preauth = true) {
		if ($need_preauth && $this->need_preauth) {
			$this->authorize();

			$this->need_preauth = true;
		} else {
			$this->need_preauth = false;
		}

		$event = new Logger($callec, $callea, $write);

		return $event;
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

	public function password_hash(string $password) {
		return \password_hash($password, PASSWORD_DEFAULT);
	}

	public function password_verify(string $password, string $hash) {
		return \password_verify($password, $hash);
	}

	protected function authorize() {
		try {
			if ($this->ath->isAuthorized()) $this->user = $this->ath->getUserData();
			else throw new Exception('Unauth request');
		} catch (Exception $error) {
			throw $error;
		}
	}
}
