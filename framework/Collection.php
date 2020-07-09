<?php
/**
 * framework/Collections.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

declare(strict_types=1);

namespace framework;

use \Exception;

use \framework\Schema;
use \framework\SchemaField;
use \framework\SchemaMask;


class Collection_SchemaField_Schema extends SchemaField {
	public string $label;
	public string $source;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public array $fields;
}

class Collection_SchemaField_Field extends SchemaField {
	public string $label;
	public int $type;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public string $muta;
	public string $transform;
}


class Collection_SchemaMask_Schema extends SchemaMask {
}

class Collection_SchemaMask_Field extends SchemaMask {
	public function label($value) {
		if (! empty($value)) return $value;

		$_replace_transfunc = function($matches) {
			return strtoupper($matches[0]);
		};

		$label = str_replace('_', ' ', $this->deep[0]);

		// if (stripos($label, 'id') === false)
		// 	$label = substr($label, (stripos($label, $name) + strlen($name) + 1));

		$label = preg_replace_callback('/\b[\w]{2,3}\b|\b\w/', $_replace_transfunc, $label);

		return $label;
	}
}


class CollectionSchema {
	public string $schema = '\framework\Collection_SchemaField_Schema';
	public string $field = '\framework\Collection_SchemaField_Field';
}

class CollectionField {
}


interface CollectionInterface {
	public function __set(string $key, $value);
	public function __fields();
	public function field(string $name);
	public function fetch($keys, bool $single, bool $distinct);
	public function add();
	public function update();
	public function remove();
}

class Collection implements CollectionInterface {
	public string $label;
	public string $source;
	public string $acl;
	public array $fields = [];
	protected object $_schema;
	protected object $_field;
	protected object $_mask;
	private object $data;
	private bool $blind = false;

	public function __construct(object $database) {
		$this->_schema = new Collection_SchemaField_Schema;
		$this->_field = new Collection_SchemaField_Field;
		$this->data = $database;

		$this->__fields();

		$this->label;

		$this->blind();
	}

	public function __set(string $key, $value) {
		static $mask;

		if ($this->blind)
			throw new Exception('Cannot override initial set properties.');

		if (! isset($mask))
			$mask = new Collection_SchemaMask_Schema(clone $this->_schema, $this);

		$mask->field->{$key} = $this->{$key} = $value;
	}

	public function __fields() {
	}

	public function field(string $name) {
		if (isset($this->fields[$name]))
			$field = $this->fields[$name];
		else
			$field = new CollectionField;

		$mask = new Collection_SchemaMask_Field(clone $this->_field, $field, $name);

		//autofill
		$mask->label;

		$this->fields[$name] = $mask->field;

		return $mask;
	}

	public function fetch($keys = NULL, bool $single = false, bool $distinct = false) {
		$this->data->fetch($this->source, $keys, $single, $distinct);
	}

	public function add() {
		$this->data->add($this->source);
	}

	public function update() {
		$this->data->update($this->source);
	}

	public function remove() {
		$this->data->remove($this->source);
	}

	private function blind() {
		$this->blind = true;
	}
}

