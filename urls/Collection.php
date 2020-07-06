<?php
/**
 * urls/Collections.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \framework\Schema;
use \framework\SchemaField;


class Collection_SchemaField_Schema extends SchemaField {
	public string $label;
	public string $source;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public array $fields;

	public function label($value, $items) {
		return $this->label = $items->name;
	}
}

class Collection_SchemaField_Field extends SchemaField {
	public string $label;
	public int $type;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public string $muta;
	public string $transform;

	public function label($value, $schema) {
		if (! isset($this->label) && ! isset($this->public)) {
			$_replace_transfunc = function($matches) {
				return strtoupper($matches[0]);
			};

			$this->label = str_replace('_', ' ', $schema->field_name);

			// if (stripos($this->label, 'id') === false)
			// 	$this->label = substr($this->label, (stripos($this->label, $schema->name) + strlen($schema->name) + 1));

			$this->label = preg_replace_callback('/\b[\w]{2,3}\b|\b\w/', $_replace_transfunc, $this->label);
		}
	}
}

class CollectionSchema extends Schema {
	public string $name = 'CollectionSchema';
	public string $schema = '\urls\Collection_SchemaField_Schema';
	public string $field = '\urls\Collection_SchemaField_Field';

	public function __construct() {
		$this->items = new $this->schema;
		$this->items->fields = [];
		$this->items->fields[] = new $this->field;
	}
}

abstract class Collection {
	public string $label;
	public string $source;
	public string $acl;
	protected object $schema;

	public function __construct() {
		$this->schema = new CollectionSchema;

		foreach ($this->schema->items as $key => $item) {
			if ($item && isset($this->{$key})) $this->schema->items->{$key} = $this->{$key};
		}
	}
}

