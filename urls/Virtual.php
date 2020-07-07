<?php
/**
 * urls/App.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \framework\DatabaseSQL;


class Virtual extends DatabaseSQL {
	const TEMP_COLLECTIONS = [
		'store' => 'urls_store',
		'domains' => 'urls_domains',
		'users' => 'urls_users',
		//
		'shadows' => 'urls_shadows'
	];

	public function fetch(string $collection, $keys = NULL, bool $single = false, bool $distinct = false) {
		$collection = self::TEMP_COLLECTIONS[$collection];

		parent::fetch($collection, $keys, $single, $distinct);
	}

	public function add(string $collection) {
		$collection = self::TEMP_COLLECTIONS[$collection];

		parent::add($collection);
	}

	public function update(string $collection) {
		$collection = self::TEMP_COLLECTIONS[$collection];

		parent::update($collection);
		
	}

	public function remove(string $collection) {
		$collection = self::TEMP_COLLECTIONS[$collection];

		parent::remove($collection);
	}
}

class VirtualNew extends DatabaseSQL {
	public function fetch(string $collection, $keys = NULL, bool $single = false, bool $distinct = false) {
		parent::fetch($collection, $keys, $single, $distinct);
	}

	public function add(string $collection) {
		parent::add($collection);
	}

	public function update(string $collection) {
		parent::update($collection);
		
	}

	public function remove(string $collection) {
		parent::remove($collection);
	}
}
