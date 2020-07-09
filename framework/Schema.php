<?php
/**
 * framework/Schema.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

declare(strict_types=1);

namespace framework;

use \Exception;


abstract class Schema {
	public string $name;
	protected string $schema;
	protected string $field;
	public $items;

	public function __construct() {
		$this->items = new $this->schema;
	}
}

abstract class SchemaField {
}


interface SchemaMaskInterface {
	public function __call(string $key, array $arguments);
	public function __set(string $key, $value);
	public function __get(string $key);
	public function set($key, $value);
	public function get($key);
}

abstract class SchemaMask implements SchemaMaskInterface {
	public bool $interrupt = false;
	public object $schema;
	public object $field;
	public array $deep;

	public function __construct(object $schema, object $field, ... $deep_mask) {
		$this->schema = $schema;
		$this->field = $field;
		$this->deep = $deep_mask;
		$this->interrupt = true;
	}

	public function __call(string $key, array $arguments) {
		// if (property_exists($this, $key))
		 	$this->schema->{$key} = $this->field->{$key} = $arguments[0];
		// else
		// 	throw new Exception(sprintf('Unknown property %s', $key));

		return $this;
	}

	public function __set(string $key, $value) {
		if ($this->interrupt) return;

		//if (property_exists($this, $key))
			$this->schema->{$key} = $this->field->{$name} = $value;
		//else
		//	throw new Exception(sprintf('Unknown property %s', $key));
	}

	public function __get(string $key) {
		$value = isset($this->field->{$key}) ? $this->field->{$key} : NULL;

		if (method_exists($this, $key)) {
			$this->schema->{$key} = $this->field->{$key} = $this->{$key}($value);
		}

		return $value;
	}

	public function set($key, $value) {
		$this->schema->{$key} = $this->field->{$key} = $value;

		return $this;
	}

	public function get($key) {
		return $this->field->{$key};
	}
}



/*namespace framework\creator;

class CreatorSchemaField {
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
}*/
