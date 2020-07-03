<?php
/**
 * framework/Schema.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;


interface RouteSchema {
	public string $call;
	public bool $access;
	public bool $install;
	public bool $setup;
	public bool $auth;
}

interface CollectionSchema {
	public string $table;
	public string $acl;
	public bool $public;
	public bool $readonly;
}

interface CollectionFieldSchema {
	public int $type;
	public string $acl;
	public bool $public;
	public bool $readonly;
	public string $muta;
	public string $transform;
}

abstract class Schema {
}
