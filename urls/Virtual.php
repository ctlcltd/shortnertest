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
	public function fetch(string $collection, $keys = NULL, bool $single = false, bool $distinct = false) {
		$collection = \urls\COLLECTIONS_TEMPLATE[$collection]['source'];

		parent::fetch($collection, $keys, $single, $distinct);
	}

	public function add(string $collection) {
		$collection = \urls\COLLECTIONS_TEMPLATE[$collection]['source'];

		parent::add($collection);
	}

	public function update(string $collection) {
		$collection = \urls\COLLECTIONS_TEMPLATE[$collection]['source'];

		parent::update($collection);
		
	}

	public function remove(string $collection) {
		$collection = \urls\COLLECTIONS_TEMPLATE[$collection]['source'];

		parent::remove($collection);
	}
}
