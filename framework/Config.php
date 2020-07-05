<?php
/**
 * framework/Config.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \stdClass;
use \Exception;


interface ConfigInterface {
	public function fromIniFile(string $file);
	public function fromIniString(string $ini_contents);
	public function fromJsonFile(string $file);
	public function fromJsonString(string $json_contents);
	public function fromArray(array $config);
	public function validate(array $config);
	public function get($skey, $key);
	public function set($skey, $kvalue, $value);
}

class Config implements ConfigInterface {
	private object $schema;
	private bool $has_sections;
	private string $from;
	protected array $config;

	public function __construct(object $schema, bool $has_sections = true) {
		$this->schema = $schema;
		$this->has_sections = $has_sections;
	}

	public function fromIniFile(string $file) {
		$this->from = 'INI';

		try {
			$contents = $this->fileRead($file);
		} catch (Exception $error) {
			throw $error;
		}

		$this->fromIniString($contents);
	}

	public function fromIniString(string $ini_contents) {
		$this->from = 'INI';

		$ini_contents = preg_replace('/(.+)\[\]\s\=$/m', '$1 = "array(empty)"', $ini_contents);

		if ($config = parse_ini_string($ini_contents, $this->has_sections, INI_SCANNER_TYPED)) {
			$this->fromArray($config);
		} else {
			throw new Exception(sprintf('Error parsing Configuration %s', $this->from));
		}
	}

	public function fromJsonFile(string $file) {
		$this->from = 'JSON';

		try {
			$contents = $this->fileRead($file);
		} catch (Exception $error) {
			throw $error;
		}

		$this->fromJsonString($contents);
	}

	public function fromJsonString(string $json_contents) {
		$this->from = 'JSON';

		if ($config = json_decode($json_contents, true))
			$this->fromArray($config);
		else
			throw new Exception(sprintf('Error parsing Configuration %s', $this->from));
	}

	public function fromArray(array $config) {
		try {
			if ($this->has_sections)
				$config = $this->validateFixDeep($config);
			else
				$config = $this->validateFixPlain($config);
		} catch (Exception $error) {
			throw $error;
		}

		$this->config = $config;
	}

	public function validate(array $config) {
		try {
			if ($this->has_sections)
				$this->validateFixDeep($config);
			else
				$this->validateFixPlain($config);
		} catch (Exception $error) {
			throw $error;

			return false;
		}

		return true;
	}

	public function get($skey = NULL, $key = NULL) {
		if ($this->has_sections && $key && isset($this->config[$section][$key]))
			return $this->config[$section][$key];
		else if ($skey && isset($this->config[$key]))
			return $this->config[$key];

		return $this->config;
	}

	public function set($skey = NULL, $kvalue = NULL, $value) {
		if ($this->has_sections && $skey && $kvalue && $value)
			$this->config[$section][$kvalue] = $value;
		else if ($skey && $kvalue)
			$this->config[$skey] = $kvalue;
		else
			throw new Exception('Empty keys or value');
	}

	private function type(int $type_int) {
		switch ($type_int) {
			case \framework\VALUE_NULL: return 'NULL';
			case \framework\VALUE_INT: return 'integer';
			case \framework\VALUE_STR: return 'string';
			case \framework\VALUE_BOOL: return 'boolean';
			case \framework\VALUE_ARR: return 'array';
			default: throw new Exception('Unknown type');
		}
	}

	private function fileRead(string $file) {
		if (! file_exists($file))
			throw new Exception(sprintf('Configuration %s file not exists', $this->from));

		if ($contents = file_get_contents($file)) {
			return $contents;
		} else {
			throw new Exception(sprintf('Error reading Configuration %s file', $this->from));
		}
	}

	private function validateFixDeep(array $config) {
		foreach ($this->schema->schema as $section => $values) {
			if (! \array_key_exists($section, $config))
				throw new Exception(sprintf('Undefined schema section: %s', $section));

			if (! is_array($values))
				throw new Exception('Value is not of type array');

			foreach ($values as $key => $value) {
				if (! \array_key_exists($key, $this->schema->schema->{$section}))
					throw new Exception(sprintf('Undefined schema key: %s', $key));

				if (gettype($config[$section][$key]) !== $this->type($value->type)) {
					if ($this->from === 'INI' && $config[$section][$key] === 'array(empty)')
						$config[$section][$key] = array();
					else
						throw new Exception(sprintf('Wrong type of value: %s', $key));
				}
			}
		}

		return $config;
	}

	private function validateFixPlain(array $config) {
		foreach ($this->schema->schema as $key => $type) {
			if (! \array_key_exists($key, $this->schema))
				throw new Exception(sprintf('Undefined schema key: %s', $key));

			if (gettype($config[$key]) !== $this->type($value->type)) {
				if ($this->from === 'INI' && $config[$key] === 'array(empty)')
						$config[$key] = array();
				else
					throw new Exception(sprintf('Wrong type of value: %s', $key));
			}
		}

		return $config;
	}
}
