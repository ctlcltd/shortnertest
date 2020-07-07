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
	public string $name;
	protected string $schema;
	protected string $field;
	public object $items;

	public function __construct() {
		$this->items = new $this->schema;
	}
}

abstract class SchemaField {
}

abstract class SchemaMask {
	public bool $interrupt = false;
	public string $name;
	public object $schema;
	public object $field;

	public function __construct(string $name, object $schema, object $field) {
		$this->name = $name;
		$this->schema = $schema;
		$this->field = $field;
		$this->interrupt = true;
	}

	public function __call(string $key, array $arguments) {
		$this->schema->{$key} = $this->field->{$key} = $arguments[0];

		return $this;
	}

	public function __set(string $key, $value) {
		if ($this->interrupt)
			$this->schema->{$key} = $this->field->{$name} = $value;
	}

	public function __get(string $key) {
		if (method_exists($this, $key)) {
			$this->schema->{$key} = $this->field->{$key} = $this->{$key}('', $this->field, $this->name);
		}

		return isset($this->field->{$key}) ? $this->field->{$key} : NULL;
	}

	public function set($key, $value) {
		$this->schema->{$key} = $this->field->{$key} = $value;

		return $this;
	}

	public function get($key) {
		return $this->field->{$key};
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

	//-TEMP
	public function set($key, $value) {
		$this->{$key} = $value;

		return $this;
	}

	public function get($key) {
		return $this->{$key};
	}
	//-TEMP
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
				->set('type', \framework\VALUE_BOOL),
			'error_404' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_STR),
			'error_50x' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_STR),
			'backend_path' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_STR)
		];

		$this->items->Network = [
			'setup' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_BOOL),
			'api_test' => (new Config_SchemaField_Field)
				->set('type', \framework\VALUE_BOOL)
		];

		// var_dump($this);
	}
}
