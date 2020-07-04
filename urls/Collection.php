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


class CollectionFieldSchemaField extends SchemaField {
	public string $label;
	public int $type;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public string $muta;
	public string $transform;

	public function label($value, $schema) {
		if (! isset($this->public) && ! isset($this->label)) {
			$_replace_transfunc = function($matches) {
				return strtoupper($matches[0]);
			};

			$this->label = str_replace('_', ' ', $schema->name);
			$this->label = preg_replace_callback('/\b[\w]{2,3}\b|\b\w/', $_replace_transfunc, $this->label);
		}
	}
}

class CollectionSchemaField extends SchemaField {
	public string $label;
	public string $table;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public array $fields;

	public function label($value, $schema) {
		$this->label = $schema->name;
	}
}

class CollectionSchema extends Schema {
	public function __construct(array $template, string $name) {
		$this->name = $name;
		$this->field = '\urls\CollectionSchemaField';
		$this->schema = new $this->field($this, $name, $template);

		$this->field = '\urls\CollectionFieldSchemaField';

		array_walk($template['fields'], [$this, 'recurse']);

		$this->schema->fields = $template['fields'];

		var_dump($this);
	}
}
