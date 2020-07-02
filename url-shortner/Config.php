<?php
/**
 * framework/Config.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;
use \stdClass;


/**
 * @class
 */
class Config {
	public $config;

	public function __construct() {
		try {
			if (! defined('\urls\CONFIG_SCHEMA'))
				throw new Exception('Undef config schema');

			if ($config = parse_ini_file(__DIR__ . '/../config.ini.php', true, INI_SCANNER_TYPED)) {
				$this->set(\urls\CONFIG_SCHEMA, $config);
			} else {
				throw new Exception('Parse INI');
			}
		} catch (Exception $error) {
			die('Missing or wrong configuration file.');
		}
	}

	private function type($type_int) {
		switch ($type_int) {
			case 0: return 'NULL';
			case 1: return 'integer';
			case 2: return 'string';
			case 5: return 'boolean';
			case 6: return 'array';
			default: throw new Exception('Type Exception');
		}
	}

	protected function set($schema, $config) {
		foreach ($schema as $section => $values) {
			if (! \array_key_exists($section, $config))
				throw new Exception(sprintf('Undef section: %s', $section));

			if (! is_array($values))
				throw new Exception("Value not arr");

			foreach ($values as $key => $type) {
				if (! \array_key_exists($key, $schema[$section]))
					throw new Exception(sprintf('Undef key: %s', $key));

				if (gettype($config[$section][$key]) !== $this->type($type))
					throw new Exception(sprintf('Wrong value: %s', $key));
			}
		}

		if ($config['Database']['dbopts'] === [''])
			$config['Database']['dbopts'] = NULL;

		$this->config = $config;
	}

	public function get($key = NULL) {
		if ($key && isset($this->config[$key]))
			return $this->config[$key];

		return $this->config;
	}
}