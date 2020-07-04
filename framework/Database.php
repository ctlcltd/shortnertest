<?php
/**
 * framework/Database.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;
use \PDO;
use \PDOException;


interface DatabaseInterface {
	public function connect();
	public function disconnect();
	public function transaction();
	public function commit();
	public function rollback();
	public function end();
	public function shadow(string $collection, string $shadow, object $event, array $id_keys, array $keys_shadow);
	public function prepare();
	public function process(string $command, string $collection, array $clauses);
	public function run();
	public function select($keys, bool $single, bool $distinct);
	public function set(string $param, $value, int $type);
	public function where(string $param, $value, int $type, string $condition);
	public function sort(string $param, string $sort_by);
	public function group(string $param, $group_by);
	public function limit(int $limit_offset, int $limit);
	public function fetch(string $collection);
	public function add(string $collection);
	public function update(string $collection);
	public function remove(string $collection);
}

class DatabaseException extends \Exception {}

class Database implements DatabaseInterface {
	private $config, $dbh, $sth;

	private const SQL_TEMPLATES = [
		'fetch' => 'SELECT %s FROM %s',
		'add' => 'INSERT INTO %s (%s) VALUES(%s)',
		'update' => 'UPDATE %s SET %s',
		'remove' => 'DELETE FROM %s'
	];
	private const SQL_CLAUSES = [
		'select',
		'count',
		'set',
		'values',
		'where',
		'group',
		'sort',
		'limit'
	];

	protected $statement, $command, $collection, $clauses;

	public function __construct(array $config_db) {
		$this->config = $config_db;
	}

	public function connect() {
		try {
			$this->dbh = new PDO(
				$this->config['dsn'],
				$this->config['username'],
				$this->config['password'],
				$this->config['options']
			);

			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		} catch (PDODatabaseException $error) {
			die(sprintf('PDO DatabaseException: %s', $error->getMessage()));
		}
	}

	public function disconnect() {

	}

	public function transaction() {
		if (! empty($this->statement))
			throw new DatabaseException('Opened statement');

		return $this->dbh->beginTransaction();
	}

	public function commit() {
		if (empty($this->statement))
			return NULL;

		return $this->dbh->commit();
	}

	public function rollback() {
		return $this->dbh->rollBack();
	}

	public function end() {
		$this->sth->closeCursor();
	}

	public function shadow(string $collection, string $shadow, object $event, array $id_keys, array $keys_shadow) {
		$token = $event->getToken();
		$time = $event->getTime();

		$this->fetch($collection, NULL, true);

		foreach ($id_keys as $id_key)
			$this->where($id_key[0], $id_key[1], $id_key[2]);

		$blob = $this->run();

		$this->add($shadow);

		$shadow = compact('token', 'time', 'blob');

		foreach ($keys_shadow as $key_id)
			$this->set($key_id[0], $shadow[$key_id[1]], $key_id[2]);

		return $this->run();
	}

	public function process(string $command, string $collection, array $clauses) {
		if (! in_array($command, ['fetch', 'add', 'update', 'remove']))
			throw new DatabaseException('Unknown SQL command');

		if (! empty(array_diff($clauses, self::SQL_CLAUSES)))
			throw new DatabaseException('Unknown SQL clauses');

		$sql_clauses = array_fill_keys(self::SQL_CLAUSES, false);
		$clauses = array_fill_keys($clauses, true);

		$this->command = $command;
		//-TEMP
		$this->collection = \urls\COLLECTIONS_TEMPLATE[$collection]['table'];
		// $this->collection = $collection;
		//-TEMP
		$this->clauses = array_merge($sql_clauses, $clauses);
		$this->statement = [];
	}

	public function prepare() {
		if ($GLOBALS['debug_data']) var_dump($this);

		if (
			($this->command === 'add' || $this->command === 'update') &&
			! isset($this->statement['set'])
		) throw new DatabaseException(sprintf('Undef set for %s', $this->command));

		if (
			($this->command === 'update' || $this->command === 'remove') &&
			! isset($this->statement['where'])
		) throw new DatabaseException(sprintf('Undef where for %s', $this->command));

		$_key_prefix_transfunc = function($key) {
			return ":{$key}";
		};
		$_set_flat_transfunc = function(&$value, $key) {
			$value = "{$key}=:{$key}";
		};
		$_group_flat_transfunc = function(&$value, $key) {
			$value = "{$key} {$clause}";
		};
		$_where_flat_transfunc = function(&$value, $key, &$i) {
			if ($i++ && empty($value['condition']))
				throw new DatabaseException('WHERE clause');

			$value = "{$value['condition']} {$key}=:{$key}";
		};

		$sql = self::SQL_TEMPLATES[$this->command];
		$values = [];

		if ($this->clauses['set'] && isset($this->statement['set'])) {
			if ($this->clauses['values']) {
				$params = array_keys($this->statement['set']);

				$vars = array_map($_key_prefix_transfunc, $params);

				$sql = sprintf($sql, $this->collection, implode(',', $params), implode(',', $vars));
			} else {
				$params = $this->statement['set'];

				array_walk($params, $_set_flat_transfunc);

				$sql = sprintf($sql, $this->collection, implode(',', $params));
			}

			$values += $this->statement['set'];
		}

		if ($this->clauses['select'] && isset($this->statement['select'])) {
			$select = '';

			if ($this->statement['select']['keys']) {
				$keys = array_values($this->statement['select']['keys']);

				$select .= implode(',', $keys);
			} else {
				$select .= '*';
			}

			if ($this->statement['select']['count'])
				$select = "COUNT({$select})";

			if ($this->statement['select']['distinct'])
				$select = "DISTINCT {$select}";

			$sql = sprintf($sql, $select, $this->collection);

			if (isset($this->statement['sort'])) {
				$sort = $this->statement['sort'];

				array_walk($sort, $_group_flat_transfunc);

				$sql .= ' ORDER BY ' . implode(',', $sort);
			}

			if (isset($this->statement['group'])) {
				$group = $this->statement['group'];

				array_walk($group, $_group_flat_transfunc);

				$sql .= ' GROUP BY ' . implode(',', $group);
			}

			if (isset($this->statement['limit']))
				$sql .= ' LIMIT ' . implode(',', $this->statement['limit']);
		} else {
			$sql = sprintf($sql, $this->collection);
		}

		if ($this->clauses['where'] && isset($this->statement['where'])) {
			$where = $this->statement['where'];

			array_walk($where, $_where_flat_transfunc, 0);

			$sql .= ' WHERE' . implode(' ', $where);

			$values += $this->statement['where'];
		}

		if ($GLOBALS['debug_data']) var_dump($sql);

		$this->sth = $this->dbh->prepare($sql);

		foreach ($values as $param => $set) {
			if ($set['type'] === 6) {
				$set['value'] = json_encode($set['value']);
				$set['type'] = 2;
			}

			$this->sth->bindValue(":{$param}", $set['value'], $set['type']);
		}

		if ($GLOBALS['debug_data']) var_dump($this->sth->debugDumpParams());
	}

	public function run() {
		$this->prepare();

		$results = $this->sth->execute();

		if ($GLOBALS["debug_data"]) var_dump($this->dbh->errorInfo());

		if ($results && $this->command === 'fetch') {
			if ($this->statement['select']['single'])
				$results = $this->sth->fetch(PDO::FETCH_ASSOC);
			else
				$results = $this->sth->fetchAll(PDO::FETCH_ASSOC);
		}

		if ($GLOBALS["debug_data"]) var_dump($results);

		$this->end();

		$this->statement = NULL;
		$this->sth = NULL;

		return $results;
	}

	public function select($keys, bool $single = false, bool $distinct = false) {
		if (! $this->clauses['select'])
			throw new Exception('Select clause not allowed');

		$this->statement['select'] = [
			'single' => $single,
			'distinct' => $distinct,
			'count' => false,
			'keys' => is_array($keys) ? $keys : NULL
		];

		return $this;
	}

	public function count() {
		if (! $this->clauses['count'])
			throw new Exception('Count clause not allowed');

		$this->statement['select']['count'] = true;

		return $this;
	}

	public function set(string $param, $value, int $type = 2) {
		if (! $this->clauses['set'])
			throw new Exception('Set clause not allowed');

		$this->statement['set'][$param] = [
			'value' => $value,
			'type' => $type
		];

		return $this;
	}

	public function where(string $param, $value, int $type = 2, string $condition = '') {
		if (! $this->clauses['where'])
			throw new Exception('Where clause not allowed');

		if (! empty($this->statement['where']) && ! $condition)
			$condition = 'and';

		$this->statement['where'][$param] = [
			'condition' => strtoupper($condition),
			'value' => $value,
			'type' => $type
		];

		return $this;
	}

	public function sort(string $param, string $sort_by = 'desc') {
		if (! $this->clauses['sort'])
			throw new Exception('Sort clause not allowed');

		$this->statement['sort'][$param] = strtoupper($sort_by);

		return $this;
	}

	public function group(string $param, $group_by) {
		if (! $this->clauses['group'])
			throw new Exception('Group clause not allowed');

		$this->statement['group'][$param] = strtoupper($group_by);

		return $this;
	}

	public function limit(int $limit_offset, int $limit = 0) {
		if (! $this->clauses['limit'])
			throw new Exception('Limit clause not allowed');

		if (! $limit) {
			$this->statement['limit']['to'] = (int) $limit_offset;
		} else {
			$this->statement['limit']['from'] = (int) $limit_offset;
			$this->statement['limit']['to'] = (int) $limit;
		}			

		return $this;
	}

	public function fetch(string $collection, $keys = NULL, bool $single = false, bool $distinct = false) {
		$this->process('fetch', $collection, [
			'select',
			'count',
			'where',
			'group',
			'sort',
			'limit'
		]);

		$this->select($keys, $single, $distinct);
	}

	public function add(string $collection) {
		$this->process('add', $collection, ['set', 'values']);
	}

	public function update(string $collection) {
		$this->process('update', $collection, ['set', 'where']);
	}

	public function remove(string $collection) {
		$this->process('remove', $collection, ['set', 'where']);
	}
}
