<?php
/**
 * urls/Collections.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;

use \framework\Schema;
use \framework\SchemaField;
use \framework\SchemaMask;

use \urls\Virtual;


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
	public function label($value, $field, $name) {
		$_replace_transfunc = function($matches) {
			return strtoupper($matches[0]);
		};

		$label = str_replace('_', ' ', $name);

		// if (stripos($label, 'id') === false)
		// 	$label = substr($label, (stripos($label, $name) + strlen($name) + 1));

		$label = preg_replace_callback('/\b[\w]{2,3}\b|\b\w/', $_replace_transfunc, $label);

		return $label;
	}
}

class CollectionSchema {
	public string $schema = '\urls\Collection_SchemaField_Schema';
	public string $field = '\urls\Collection_SchemaField_Field';

	public function __construct() {
		$this->items = new $this->schema;
	}
}

class CollectionField {
}

class Collection {
	public string $label;
	public string $source;
	public string $acl;
	protected object $_schema;
	protected object $_field;
	protected object $_mask;
	private object $data;
	private bool $blind = false;

	public function __construct(object $database) {
		$this->_schema = new Collection_SchemaField_Schema;
		$this->_field = new Collection_SchemaField_Field;
		$this->_mask = new Collection_SchemaMask_Schema($this->label, $this->_schema, $this);
		$this->data = $database;

		$this->fields = [];

		if (method_exists($this, '__fields'))
			$this->__fields();

		$this->label;

		$this->blind();
	}

	public function __set($key, $value) {
		if ($this->blind)
			throw new Exception('Cannot override initial set properties.');

		if ($value)
			$this->_mask->field->{$key} = $this->{$key} = $value;
	}

	public function field($name) {
		if (isset($this->fields[$name]))
			$field = $this->fields[$name];
		else
			$field = new \urls\CollectionField;

		$mask = new Collection_SchemaMask_Field($name, $this->_field, $field);

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

