<?php
/**
 * framework/Schema.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;


abstract class Schema {
	protected string $schema;
	protected string $field;
	public string $name;
	public object $items;

	public function __construct() {
		$this->items = new $this->schema;
	}
}

abstract class SchemaField {
	public function set(string $key, $value, $schema) {
		$this->{$key} = method_exists($this, $key) ? $this->{$key}($value, $schema) : $value;

		return $this;
	}

	public function get(string $key) {
		if ($key) return $this->{$key};

		return $this->{$key};
	}

	public function caller($callable, $i, $schema) {
		$value = isset($this->{$callable}) ? $this->{$callable} : null;

		$this->$callable($value, $schema);
	}
}



namespace framework\creator;

class CreatorSchema {
	public function fromArray(object $schema, array $template, string $name) {
	}
}

class CreatorSchemaField {
	public function fromArray(object $schema_field, string $name, $field) {
	}

	public function recurse(&$value, string $key, object $schema) {
		$value = $this->{$key} = method_exists($this, $key) ? $this->{$key}($value, $schema) : $value;
	}

	public function setShorthand(object $schema, string $field, $fixed_key = NULL) {
		$this->field = $field;
		$this->schema = $schema;
		$this->key = $fixed_key;
	}

	public function set(...$args) {
		// if (count($args) > 1) return (new $this->field)->set((string) $args[0], $args[1], $this->schema);
		// else 
		return (new $this->field)->set($this->key, $args[0], $this->schema);
	}
}



namespace framework;

class RouteFieldSchemaField extends SchemaField {
	public string $call;
	public bool $access;
	public bool $install;
	public bool $setup;
	public bool $auth;
}

class Config_SchemaField_Schema extends SchemaField {
}

class Config_SchemaField_Field extends SchemaField {
	public string $type;
}


class RoutesSchema extends Schema {
}

class ConfigSchema extends Schema {
	public string $name = 'ConfigSchema';
	public string $schema = '\framework\Config_SchemaField_Schema';
	public string $field = '\framework\Config_SchemaField_Field';

	public function __construct() {
		parent::__construct();

		$this->items->Host = [
			'ssr' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_BOOL, $this),
			'error_404' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_STR, $this),
			'error_50x' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_STR, $this),
			'backend_path' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_STR, $this)
		];

		$this->items->Network = [
			'setup' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_BOOL, $this),
			'api_test' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_BOOL, $this)
		];

		// var_dump($this);
	}
}
