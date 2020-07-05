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
}

abstract class SchemaField {
	public function set(string $key, $value, object $schema) {
		$this->{$key} = method_exists($this, $key) ? $this->{$key}($value, $schema) : $value;

		return $this;
	}

	public function get(string $key) {
		if ($key) return $this->{$key};

		return $this->{$key};
	}

	private function caller($callable, $i, $schema) {
		$value = isset($this->{$callable}) ? $this->{$callable} : null;

		$this->$callable($value, $schema);
	}
}



namespace framework\creator;

class CreatorSchema {
	public function fromArray(object $schema, array $template, string $name) {
		$schema->name = $name;
		$schema->field = '\urls\CollectionSchemaField';
		$schema->schema = new $this->field($this, $name, $template);

		$schema->field = '\urls\CollectionFieldSchemaField';

		array_walk($template['fields'], [$this, 'recurse']);

		$this->schema->fields = $template['fields'];

		// var_dump($this);
	}
}

class CreatorSchemaField {
	public function fromArray(object $schema_field, string $name, $field) {
		$callables = array_diff(
			get_class_methods($this),
			get_class_methods(__CLASS__)
		);

		$schema->field_name = $name;

		array_walk_recursive($field, [$this, 'recurse'], $schema);

		if (! empty($callables))
			array_walk($callables, [$this, 'caller'], $schema);

		unset($schema->field_name);
	}

	public function recurse(&$value, string $key, object $schema) {
		$value = $this->{$key} = method_exists($this, $key) ? $this->{$key}($value, $schema) : $value;
	}

	public function setShorthand(object $schema, string $field, string $fixed_key) {
		$this->field = $field;
		$this->schema = $schema;
		$this->key = $fixed_key;
	}

	public function set(...$args) {
		if (count($args) > 1) return (new $this->field)->set($args[0], $args[1], $this->schema);
		else return (new $this->field)->set($this->key, $args[0], $this->schema);
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
	public string $name = 'Config';

	public function __construct() {
		$this->schema = new Config_SchemaField_Schema;

		$field_shorthand = new \framework\creator\CreatorSchemaField();
		$field_shorthand->setShorthand($this->schema, '\framework\Config_SchemaField_Schema', 'type');

		$this->schema->Host = [
			'ssr' => $field_shorthand->set(\framework\VALUE_BOOL),
			'error_404' => $field_shorthand->set(\framework\VALUE_STR),
			'error_50x' => $field_shorthand->set(\framework\VALUE_STR),
			'backend_path' => $field_shorthand->set(\framework\VALUE_STR)
		];

		$this->schema->Database = [
			'dsn' => $field_shorthand->set(\framework\VALUE_STR),
			'username' => $field_shorthand->set(\framework\VALUE_STR),
			'password' => $field_shorthand->set(\framework\VALUE_STR),
			'options' => $field_shorthand->set(\framework\VALUE_ARR),
			'shadow' => $field_shorthand->set(\framework\VALUE_BOOL)
		];

		$this->schema->Network = [
			'setup' => $field_shorthand->set(\framework\VALUE_BOOL),
			'user_acl' => $field_shorthand->set(\framework\VALUE_STR),
			'user_action_lifetime' => $field_shorthand->set(\framework\VALUE_INT)
		];

		// var_dump($this);
	}
}
