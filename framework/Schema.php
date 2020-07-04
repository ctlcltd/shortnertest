<?php
/**
 * framework/Schema.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \stdClass;
use \Exception;


abstract class Schema {
	protected string $field;
	public string $name;
	public object $schema;

	public function set(string $name, $field) {
		$this->schema[$name] = new $this->field($this, $name, $field);
	}

	public function get(string $name, $field) {
		if ($name) return $this->schema[$name];

		return $this->schema;
	}

	public function recurse(&$field, $name) {
		$field = new $this->field($this, $name, $field);
	}
}

abstract class SchemaField {
	public function __construct(object $schema, string $name, $field) {
		$callables = array_diff(
			get_class_methods($this),
			get_class_methods(__CLASS__)
		);

		array_walk_recursive($field, [$this, 'recurse'], $schema);

		if (! empty($callables))
			array_walk($callables, [$this, 'caller'], $schema);
	}

	public function recurse(&$value, string $key, object $schema) {
		$value = $this->{$key} = method_exists($this, $key) ? $this->{$key}($value, $schema) : $value;
	}

	public function set(string $key, $value, object $schema) {
		$this->{$key} = method_exists($this, $key) ? $this->{$key}($value) : $value;
	}

	public function get(string $key, $value) {
		if ($key) return $this->{$key};

		return $this->{$key};
	}

	private function caller($callable, $key, $schema) {
		$this->$callable('', $schema);
	}
}


class RouteFieldSchemaField extends SchemaField {
	public string $call;
	public bool $access;
	public bool $install;
	public bool $setup;
	public bool $auth;
}

class ConfigSchemaField extends SchemaField {}

class ConfigFieldSchemaField extends SchemaField {
	public string $type;
}


class RoutesSchema extends Schema {
}

class ConfigSchema extends Schema {
	public function __construct(array $template, string $name) {
		$this->name = $name;

		$this->field = '\framework\ConfigFieldSchemaField';

		array_walk_recursive($template, [$this, 'recurse']);

		$this->schema = (object) $template;

		var_dump($this);
	}

	public function recurse(&$field, $name) {
		$field = new $this->field($this, $name, ['type' => $field]);
	}
}
