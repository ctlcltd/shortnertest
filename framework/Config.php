<?php
/**
 * framework/Config.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;
use \Error;
use \stdClass;


interface ConfigInterface {
	public function parse_ini_file(string $file);
	public function get($key);
	public function set(array $config);
}

class Config implements ConfigInterface {
	private $schema;
	protected $config;

	public function __construct(array $schema) {
		$this->schema = $schema;
	}

	public function parse_ini_file(string $file) {
		try {
			if (! file_exists($file))
				throw new Exception('File not exists');

			if ($config = parse_ini_file($file, true, INI_SCANNER_TYPED)) {
				$this->set($config);
			} else {
				throw new Exception('Parse INI');
			}
		} catch (Error $error) {
			throw $error;
		}
	}

	private function type(int $type_int) {
		switch ($type_int) {
			case 0: return 'NULL';
			case 1: return 'integer';
			case 2: return 'string';
			case 5: return 'boolean';
			case 6: return 'array';
			default: throw new Exception('Type Exception');
		}
	}

	public function get($key = NULL) {
		if ($key && isset($this->config[$key]))
			return $this->config[$key];

		return $this->config;
	}

	public function set(array $config) {
		foreach ($this->schema as $section => $values) {
			if (! \array_key_exists($section, $config))
				throw new Exception(sprintf('Undef section: %s', $section));

			if (! is_array($values))
				throw new Exception("Value not arr");

			foreach ($values as $key => $type) {
				if (! \array_key_exists($key, $this->schema[$section]))
					throw new Exception(sprintf('Undef key: %s', $key));

				if (gettype($config[$section][$key]) !== $this->type($type))
					throw new Exception(sprintf('Wrong value: %s', $key));
			}
		}

		if ($config['Database']['options'] === [''])
			$config['Database']['options'] = NULL;

		$this->config = $config;
	}
}
