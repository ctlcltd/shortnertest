<?php
/**
 * framework/Logger.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \stdClass;


/**
 * Interface for logger class
 *
 * @interface
 */
interface LoggerInterface {
	public function getToken();
	public function getTime();
}

class Logger implements LoggerInterface {
	private $callee, $event, $time;
	public $epoch, $hash;

	public function __construct($callec, $callea, $write = true) {
		$time = $_SERVER['REQUEST_TIME'];

		$this->callee = "{$callec}_{$callea}";
		$this->epoch = $time;
		$this->time = date('c', $time);
		$this->event = new stdClass;
		$this->event->time = $time;
		$this->event->callea = $callea;

		if ($write) {
			$hash = random_bytes(4);
			$hash = bin2hex($hash);

			$this->hash = $hash;
			$this->event->hash = $hash;
		}
	}

	public function getToken() {
		return (array) $this->event;
	}

	public function getTime() {
		return $this->time;
	}
}