<?php
/**
 * framework/Config.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

declare(strict_types=1);

namespace framework;

use \stdClass;
use \Exception;

use \framework\Schema;
use \framework\SchemaField;


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
		foreach ($this->schema->items as $section => $values) {
			if (! \array_key_exists($section, $config))
				throw new Exception(sprintf('Undefined schema section: %s', $section));

			// if (! is_array($values))
			// 	throw new Exception('Value is not of type array');

			foreach ((array) $values as $key => $value) {
				// if (! \array_key_exists($key, $this->schema->items->{$section}))
				// 	throw new Exception(sprintf('Undefined schema key: %s', $key));

				if (! property_exists($this->schema->items[$section], $key))
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
		foreach ($this->schema->items as $key => $value) {
			// if (! \array_key_exists($key, $this->schema->items))
			// 	throw new Exception(sprintf('Undefined schema key: %s', $key));

			if (! property_exists($this->schema->items, $key))
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


class Settings_SchemaMask_Schema extends SchemaMask {
	public function field($key, $type) {
		return $this->deep[1]->field($this, $this->deep[0], $key, $type);
	}
}

class Settings_SchemaMask_Field extends SchemaMask {
	public function field($key, $type) {
		return $this->deep[1]->field($this->deep[2], $this->deep[0], $key, $type);
	}
}


class Settings_SchemaField_Schema extends SchemaField {
}

class Settings_SchemaField_Field extends SchemaField {
	public int $type;
}


class SettingsSection {
}

class SettingsField {
}


class SettingsSchema extends Schema {
	public string $name = 'SettingsSchema';
	public string $schema = '\framework\Settings_SchemaField_Schema';
	public string $field = '\framework\Settings_SchemaField_Field';

	protected object $_schema;
	protected object $_field;
	private bool $blind = false;
	public $items;

	public function __construct() {
		$this->_schema = new Settings_SchemaField_Schema;
		$this->_field = new Settings_SchemaField_Field;

		$this->__items();

		// var_dump($this->items);

		$this->blind = true;
	}

	public function __set(string $key, $value) {
		static $mask;

		if ($this->blind)
			throw new Exception('Cannot override initial set properties.');

		if (! isset($mask))
			$mask = new Settings_SchemaMask_Schema(clone $this->_schema, $this);

		$mask->field->{$key} = $this->{$key} = $value;
	}

	public function __items() {
		$this->section('Host')
			->field('ssr', \framework\VALUE_BOOL)
			->field('error_404', \framework\VALUE_STR)
			->field('error_50x', \framework\VALUE_STR);

		$this->section('Network')
			->field('setup', \framework\VALUE_BOOL)
			->field('api_test', \framework\VALUE_BOOL);
	}

	public function section(string $skey) {
		if (isset($this->items[$skey]))
			$field = $this->items[$skey];
		else
			$field = new SettingsSection;

		$mask = new Settings_SchemaMask_Schema(clone $this->_schema, $field, $skey, $this);

		$this->items[$skey] = $mask->field;

		return $mask;
	}

	public function field(object $mask_field, string $skey, string $key, int $type) {
		if (isset($this->items[$skey]->{$key}))
			$field = $this->items[$skey]->{$key};
		else
			$field = new SettingsField;

		$mask = new Settings_SchemaMask_Field(clone $this->_field, $field, $skey, $this, $mask_field);

		$mask->set('type', $type);

		$this->items[$skey]->{$key} = $mask->field;

		return $mask;
	}
}
