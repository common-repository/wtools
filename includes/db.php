<?php

define('WTOOLS_DB_ALL_ROWS', -1);

interface WTools_Table {
	public static function load($id);
	public static function create($object);
}

/**
 * Base class for all classes representing db tables.
 */
abstract class WTools_Table_Base implements WTools_Table {
	public static $table_name;
	public static $auto_id;
	public static $key;
	/**
	 * 
	 * @param stdClass|array $object
	 *  Values for the database table row.
	 */
	public function __construct($object = null) {
		if ($object) {
			foreach ((array) $object as $key => $value) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Get complete table name.
	 *
	 * @global wpdb $wpdb
	 * @return string
	 */
	public static function getTableName() {
		global $wpdb;
		return $wpdb->prefix . static::$table_name;
	}
	
	/**
	 * 
	 * @global wpdb $wpdb
	 * @param object|array|stdClass $object
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public static function create( $object) {
		global $wpdb;
		$table_name = static::getTableName();
		$return = $wpdb->insert($table_name, (array) $object);
		if (isset(static::$auto_id)) {
			// We assume it will be last inserted one as everyone assumed.
			// Read more at http://wordpress.stackexchange.com/a/4698/112815
			$object->{static::$auto_id} = $wpdb->insert_id;
		}
		return $return;
	}
	
	/**
	 * 
	 * @global wpdb $wpdb
	 * @param mixed $id
	 * @return WTools_Table_Base
	 * @throws Exception
	 */
	public static function load($id) {
		if (!isset(static::$key)) {
			throw new Exception('Cannot load object without declaring key in class definition.');
		}
		global $wpdb;
		$table_name = static::getTableName();
		$key_field = static::$key;
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE $key_field = %s", array($id)));
		if ($row) {
			return new static($row);
		}
	}

	/**
	 * Get multiple rows from db table.
	 *
	 * @global wpdb $wpdb
	 * @param array $query_args
	 * @return array
	 */
	public static function listItems($query_args = array()) {
		$query_args += array(
			'offset' => 0,
			'limit' => 10,
			'order_by' => '',
			'where' => array(),
			'fields' => '*',
		);
		global $wpdb;
		$table_name = static::getTableName();

		$args = array();

		if (count($query_args['where'])) {
			$conditions = array();
			// Normalize
			foreach ($query_args['where'] as $field => $info) {
				if (is_array($info)) {
					$value = $info['value'];
					$format = isset($info['format']) ? $info['format'] : '%s';
				}
				else {
					$value = $info;
					$format = '%s';
				}
				if (is_null($value)) {
					$conditions[] = "`$field` IS NULL";
					continue;
				}
				$conditions[] = "`$field` = $format";
				$args[] = $value;
			}

			$where_string = 'WHERE ' . implode(' AND ', $conditions);
		}
		else {
			$where_string = '';
		}
		
		$query = "SELECT {$query_args['fields']} FROM $table_name $where_string {$query_args['order_by']}";

		if ($query_args['limit'] != WTOOLS_DB_ALL_ROWS) {
			$query .= " LIMIT %d OFFSET %d";
			$args[] = (int) $query_args['limit'];
			$args[] = (int) $query_args['offset'];
		}

		if (!empty($args)) {
			$query = $wpdb->prepare($query, $args);
		}
		$list = array();

		$result = $wpdb->get_results($query);
		foreach ($result as $row) {
			$list[] = new static($row);
		}
		return $list;
	}

	/**
	 * Save a database row.
	 *
	 * @global wpdb $wpdb
	 * @return int|false The number of rows updated, or false on error.
	 * @throws Exception
	 */
	public function save() {
		if (!isset(static::$key)) {
			throw new Exception('Cannot save object without key defined in class definition.');
		}
		global $wpdb;
		if (!isset($this->{static::$key})) {
			return static::create($this);
		}
		else {
			return $wpdb->update( static::getTableName(), (array) $this, array( static::$key => $this->{static::$key} ) );
		}
	}

	/**
	 * Delete a row.
	 *
	 * @global wpdb $wpdb
	 * @param WTools_Table_Base|stdClass $object
	 * @throws Exception
	 */
	public static function delete($object) {
		if (!isset(static::$key)) {
			throw new Exception('Cannot delete object without key defined in class definition.');
		}
		global $wpdb;
		$return =  $wpdb->delete( static::getTableName(), array( static::$key => $object->{static::$key}) );
		if ($return) {
			unset($object);
		}
	}
	
}
