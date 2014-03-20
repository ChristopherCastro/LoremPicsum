<?php
class MySQL {
	private $__dbConn = null;
	private $__dbLastQuery = null;
	private $__dbLog = array();
	private $__debugLevel = 0;
	private $__cachePath = null;
	
	public function __construct($db, $usr, $pass, $hst = 'localhost') {
		$this->__cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR;

		if (!is_writable($this->__cachePath)) {
			die("Make sure the following path is writable: {$this->__cachePath}");
		}

		$this->__dbConn = mysql_connect($hst, $usr, $pass);
		mysql_select_db($db);
	}

/**
 * Deletes from $table all the rows matching $where condition.
 *
 * # Usage:
 *
 *     // remove everyone younger than 10 years old
 *     $db->Delete('persons', "age < 10");
 *
 * @param string $table The table name
 * @param string $where Conditions that rows should match
 * @return object MySQL resource
 */
	public function Delete($table, $where = null) {
		$where = !$where ? '1 = 1' : $where;
		$args = array($this->FormatTableName($table), $where);
		$query = vsprintf("DELETE FROM %s WHERE %s", $args);

		return $this->Execute($query);
	}

/**
 * Similar to dbQuery, except it returns only the values for a single column
 * of every row.
 *
 * # Usage:
 *
 *     // people's weight older than 40
 *     $db->QueryOneColumn('weight', 'SELECT * FROM %s WHERE age > %d', 'persons', 40);
 *
 *     array(
 *         0 => 50.6,
 *         1 => 70.2,
 *         2 => 42.3,
 *         ...
 *     );
 *
 * @param string $column Name of the column to get
 * @param string $query SQL query statement
 * @param mixed $args Multiple arguments in function for $query
 * @return array Associative array of row's columns => values.
 * @see MySQL::Query()
 */
	public function QueryOneColumn($column, $query, $args = null) {
		if (!is_null($args)) {
			$args = array_slice(func_get_args(), 2);
			$query = vsprintf($query, $args);
		}

		$results = array();

		if ($this->Execute($query)) {
			while ($row = $this->FetchRow()) {
				$results[] = $row[$column];
			}
		}

		return $results;	
	}

/**
 * Similar to MySQL::Query, except it returns only the first row
 * instead of all of them.
 *
 * # Usage:
 *
 *     $db->QueryFirstRow('SELECT * FROM %s WHERE id = %d', $table_name, $id);
 *
 *     array(
 *         'column_1' => 'value1',
 *         'column_2' => 'value2',
 *         ...
 *     );
 *
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return array Associative array of row's columns => values.
 * @see MySQL::Query
 */
	public function QueryFirstRow($query, $args = null) {
		if (!is_null($args)) {
			$args = !is_array($args) ? array_slice(func_get_args(), 1) : $args;
			$query = vsprintf($query, $args);
		}

		if ($this->Execute($query)) {
			return $this->FetchRow();
		}
	}

/**
 * Returns all the rows matching the query.
 *
 * # Usage:
 *
 *     $db->Query('SELECT * FROM %s WHERE id = %d', $table_name, $id);
 *
 *     array(
 *         0 => array(
 *             'column_1' => 'row1_value1',
 *             'column_2' => 'row1_value2',
 *             ...
 *         ),
 *         1 => array(
 *             'column_1' => 'row2_value1',
 *             'column_2' => 'row2_value2',
 *             ...
 *         ),
 *     );
 *
 * @param string $query Query string pattern
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return array Resulting rows
 */
	public function Query($query, $args = null) {
		if (!is_null($args)) {
			if (!is_array($args)) {
				$args = array_slice(func_get_args(), 1);
			}

			$query = vsprintf($query, $args);
		}

		$results = array();

		if ($this->Execute($query)) {
			while ($row = $this->FetchRow()) {
				$results[] = $row;
			}
		}

		return $results;
	}

/**
 * Updates all the rows of $table.
 *
 * # Usage:
 *
 *     // update John's age to 10 years old
 *     $db->Update('persons', array('age' => 10), 'name = "John"');
 *
 * @para string $table The table
 * @para array $params Associative array of new values
 * @para array $where SQL conditions. e.g.: `age > 10`
 * @return object MySQL resource id
 */
	function Update($table, $params, $where = null) {
		$where = !$where ? '1 = 1': $where;
		$keyval = array();
		$schema = $this->Schema($table);

		if (isset($schema['modified'])) {
			if (!array_key_exists('format', $schema['modified'])) {
				$time = strtotime('now');
			} else {
				$time = call_user_func($schema['modified']['formatter'], $schema['modified']['format']);
			}

			$params['modified'] = $time;
		}

		foreach ($params as $key => $value) {
			$keyval[] = "`" . $key . "`=" . $this->Sanitize($value);
		}

		$query = "UPDATE " . $this->FormatTableName($table) . " SET " . implode(', ', $keyval) . " WHERE " . $where;
		return $this->Execute($query);
	}

/**
 * Inserts into $table the given rows
 *
 * # Usage:
 *
 *     // inserting mutiple rows in one call
 *      $db->Insert('persons',
 *          array(
 *              array('name' => 'Peter', 'age' => 18, ...),
 *              array('name' => 'John', 'age' => 53, ...),
 *              ...
 *          )
 *      );
 *
 *     // inserting ONE row at time
 *     $db->Insert('persons', array('name' => 'Peter', 'age' => 18, ...)); 
 *     $db->Insert('persons', array('name' => 'John', 'age' => 53, ...)); 
 *
 * @param string $table The name of the table where to insert
 * @param array $data Data to insert, both one or multiple rows
 * @return integer The id of the last inserted row
 */
	public function Insert($table, $dataSet) {
		$schema = $this->Schema($table);
		$dataSet = unserialize(serialize($dataSet)); // break references within array
		$keys = null;

		if (isset($dataSet[0]) && is_array($dataSet[0])) {
			$many = true;
		} else {
			$dataSet = array($dataSet);
			$many = false;
		}		

		foreach ($dataSet as $data) {
			if (!$keys) {
				$keys = array_keys($data);

				if (isset($schema['created'])) {
					$keys[] = 'created';
				}

				if ($many) {
					sort($keys);
				}
			}

			$insert_values = array();

			foreach ($keys as $key) {
				if ($many && !array_key_exists($key, $data)) {
					die('insert/replace many: each assoc array must have the same keys!');
				}

				if ($key != 'created') {
					$datum = $data[$key];
				} else {
					if (!array_key_exists('format', $schema['created'])) {
						$time = strtotime('now');
					} else {
						$time = call_user_func($schema['created']['formatter'], $schema['created']['format']);
					}

					$datum = $time;
				}

				$datum = $this->Sanitize($datum);
				$insert_values[] = $datum;
			}

			$values[] = '(' . implode(', ', $insert_values) . ')';
		}

		$table = $this->FormatTableName($table);
		$keys_str = implode(', ', $this->WrapStr($keys, '`'));
		$values_str = implode(',', $values);
		$this->Execute("INSERT INTO {$table} ({$keys_str}) VALUES {$values_str};");

		return mysql_insert_id();
	}

/**
 * Counts the number of rows in $table that matches $where conditions
 *
 * @param string $table The name of the table to count
 * @param string $where Optional conditions
 * @return integer
 */
	public function Count($table, $where = null) {
		$table = $this->FormatTableName($table);
		$where = is_null($where) ? '1 = 1': $where;
		$query = "SELECT COUNT(*) AS __count__ FROM {$table} WHERE {$where}";

		if ($this->Execute($query)) {
			$r = $this->FetchRow();
			return $r['__count__'];
		}

		return 0;
	}

/**
 * Return a HTML debug table.
 *
 * @return string HTML
 */
	public function SqlLog() {
		$out = '<table class="sql-log table table-striped">';
		$out .= '<thead>';
			$out .= '<tr>';
				$out .= '<th>Nr.</th>';
				$out .= '<th align="left">Query</th>';
				$out .= '<th>Error</th>';
				$out .= '<th>Affected</th>';
				$out .= '<th>Num. Rows</th>';
				$out .= '<th>Took (ms)</th>';
			$out .= '</tr>';
		$out .= '</thead>';
		$out .= '<tbody>';

		foreach ($this->__dbLog as $nr => $log) {
			$nr++;
			$status = !$log['error'] ? 'success' : 'error';
			$error_message = $log['error'] ? "<p class=\"text-error\">{$log['error_message']}</p>" : '';
			$out .= "<tr class=\"{$status}\">";
				$out .= "<td>{$nr}</td>";
				$out .= "<td align=\"left\">{$log['query']}{$error_message}</td>";
				$out .= "<td>" . ($log['error'] ? 'yes' : 'no') . "</td>";
				$out .= "<td>{$log['affected_rows']}</td>";
				$out .= "<td>{$log['num_rows']}</td>";
				$out .= "<td>{$log['ms']}</td>";
			$out .= '</tr>';
		}

		$out .= '</tbody>';
		$out .= '</table>';

		return $out;
	}

/**
 * Gets the last MySQL error
 *
 * @return string The MySQL error message
 */
	public function LastError() {
		return mysql_error($this->__dbConn);
	}

/**
 * Fetch the next rows of the last executed query.
 *
 * @return array The row as an associative array
 */
	public function FetchRow() {
		return mysql_fetch_assoc($this->__dbLastQuery);
	}

/**
 * Executes a SQL statement
 *
 * @param $query SQL statement
 * @return object MySQL resource
 */
	public function Execute($query) {
		$ms = microtime(true);
		$this->__dbLastQuery = mysql_query($query, $this->__dbConn);

		if ($this->Debug() > 0) {
			$log = array(
				'query' => $query,
				'error' => false,
				'error_message' => false,
				'affected_rows' => 0,
				'num_rows' => 0,
				'ms' => 0
			);

			if (!$this->__dbLastQuery) {
				$log['error'] = true;
				$log['error_message'] = $this->LastError();
			} else {
				$log['affected_rows'] = mysql_affected_rows();
				$log['num_rows'] = @mysql_num_rows($this->__dbLastQuery);
			}

			$log['ms'] = (microtime(true) - $ms) * 1000;
			$this->__dbLog[] = $log;
		}

		return $this->__dbLastQuery;
	}

/**
 * Makes the given table name SQL-safe.
 *
 * @param string $table Table name. e.g. my_table 
 * @return string Safe table name. e.g. `my_table`
 */
	public function FormatTableName($table) {
		$table = str_replace('`', '', $table);

		if (strpos($table, '.')) {
			list($table_db, $table_table) = explode('.', $table, 2);
			$table = "`{$table_db}`.`{$table_table}`";
		} else {
			$table = "`{$table}`";
		}

		return $table;
	}

/**
 * Wraps the given string using the given char.
 *
 * # Usage:
 *
 *     $db->WrapStr('Wrap this', '@');
 *     // returns: @Wrap this@
 *
 * @param array|string $strOrArray The string to wrap, or multiple strings in array
 * @param string $wrapChar Wrapping char. e.g. "`"
 * @param boolean $escape Should the string be escaped ?
 */
	public function WrapStr($strOrArray, $wrapChar, $escape = false) {
		if (!is_array($strOrArray)) {
			if ($escape) return $wrapChar . $this->Escape($strOrArray) . $wrapChar;
			else return $wrapChar . $strOrArray . $wrapChar;
		} else {
			$R = array();
			foreach ($strOrArray as $element) {
				$R[] = $this->WrapStr($element, $wrapChar, $escape);
			}

			return $R;
		}
	}

/**
 * Makes a PHP variable SQL-safe.
 *
 * @param mixed $value Value to sanitize
 * @return string SQL safe string
 */
	public function Sanitize($value) {
		if (is_array($value) || is_object($value)) {
			$value = serialize($value);
		}

		if (is_string($value)) {
			$value = "'" . $this->Escape($value) . "'";
		} else if (is_null($value)) {
			$value = 'NULL';
		} else if (is_bool($value)) {
			$value = ($value ? 1 : 0);
		}

		return $value;	
	}

/**
 * Returns an array of table metadata (column names and types) from the database.
 * $field => keys(type, null, default, key, length, extra)
 *
 * @param string $table Set to true to reload schema, or a string to return a specific field
 * @param string $field Optional, return a specific field of the table
 * @return array Array of table metadata
 */
	public function Schema($table, $field = false) {
		$fields = array();
		$formatters = array(
			'integer' => array('formatter' => 'intval'),
			'float' => array('formatter' => 'floatval'),
			'datetime' => array('format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
			'timestamp' => array('format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
			'time' => array('format' => 'H:i:s', 'formatter' => 'date'),
			'date' => array('format' => 'Y-m-d', 'formatter' => 'date')
		);	
		$cacheExpires = $this->Debug() > 0 ? '+20 seconds' : '+1 day';
		$cache = $this->Cache("schema_{$table}", null, $cacheExpires);

		if ($cache) {
			return $cache;
		}

		foreach ($this->Query('SHOW FULL COLUMNS FROM %s', $this->FormatTableName($table)) as $column) {
			$cn = $column['Field'];
			$type = $this->ColumnType($column['Type']);

			$fields[$cn] = array(
				'type' => $type,
				'null' => ($column['Null'] === 'YES' ? true : false),
				'default' => $column['Default'],
				'length' => $this->ColumnLength($column['Type']),
			);

			if (isset($formatters[$type])) {
				$fields[$cn] = array_merge($fields[$cn], $formatters[$type]);
			}

			if (!empty($column['Key'])) {
				$fields[$cn]['key'] = $column['Key'];
			}
		}

		if (!empty($fields)) {
			$this->Cache("schema_{$table}", $fields, $cacheExpires);
		}

		if ($field && isset($fields[$field])) {
			return $fields[$field];
		}

		return $fields;
	}

/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	public function ColumnType($real) {
		if (is_array($real)) {
			$col = $real['name'];

			if (isset($real['limit'])) {
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}

		$col = str_replace(')', '', $real);
		$limit = $this->ColumnLength($real);

		if (strpos($col, '(') !== false) {
			list($col, $vals) = explode('(', $col);
		}

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}

		if (($col === 'tinyint' && $limit == 1) || $col === 'boolean') {
			return 'boolean';
		}

		if (strpos($col, 'int') !== false) {
			return 'integer';
		}

		if (strpos($col, 'char') !== false || $col === 'tinytext') {
			return 'string';
		}

		if (strpos($col, 'text') !== false) {
			return 'text';
		}

		if (strpos($col, 'blob') !== false || $col === 'binary') {
			return 'binary';
		}

		if (strpos($col, 'float') !== false || strpos($col, 'double') !== false || strpos($col, 'decimal') !== false) {
			return 'float';
		}

		if (strpos($col, 'enum') !== false) {
			return "enum($vals)";
		}

		return 'text';
	}

/**
 * Gets the length of a database-native column description, or null if no length
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return mixed An integer or string representing the length of the column, or null for unknown length.
 */
	public function ColumnLength($real) {
		if (!preg_match_all('/([\w\s]+)(?:\((\d+)(?:,(\d+))?\))?(\sunsigned)?(\szerofill)?/', $real, $result)) {
			$col = str_replace(array(')', 'unsigned'), '', $real);
			$limit = null;

			if (strpos($col, '(') !== false) {
				list($col, $limit) = explode('(', $col);
			}
			if ($limit !== null) {
				return intval($limit);
			}
			return null;
		}

		$types = array(
			'int' => 1,
			'tinyint' => 1,
			'smallint' => 1,
			'mediumint' => 1,
			'integer' => 1,
			'bigint' => 1
		);

		list($real, $type, $length, $offset, $sign, $zerofill) = $result;
		$typeArr = $type;
		$type = $type[0];
		$length = $length[0];
		$offset = $offset[0];
		$isFloat = in_array($type, array('dec', 'decimal', 'float', 'numeric', 'double'));

		if ($isFloat && $offset) {
			return $length . ',' . $offset;
		}

		if (($real[0] == $type) && (count($real) === 1)) {
			return null;
		}

		if (isset($types[$type])) {
			$length += $types[$type];

			if (!empty($sign)) {
				$length--;
			}
		} elseif (in_array($type, array('enum', 'set'))) {
			$length = 0;

			foreach ($typeArr as $key => $enumValue) {
				if ($key === 0) {
					continue;
				}
	
				$tmpLength = strlen($enumValue);

				if ($tmpLength > $length) {
					$length = $tmpLength;
				}
			}
		}
		return intval($length);
	}

/**
 * Wrapper for mysql_real_escape_string()
 *
 * @param mixed $value The value to escape
 * @param string Escaped string
 * @see http://php.net/manual/en/function.mysql-escape-string.php
 */
	public function Escape($value) {
		return mysql_real_escape_string($value);	
	}

/**
 * Sets debug level. Or gets current debug level.
 *
 * @param mixed $lvl And integer value as new Debug level.
 * Or false (default) to get current value
 * @return mixed Current debug level if $lvl = false. Or void if new value was set.
 */
	public function Debug($lvl = false) {
		if ($lvl === false) {
			return $this->__debugLevel;
		} else {
			$this->__debugLevel = intval($lvl);
		}
	}

/**
 * Set a new path where to store cache files. Or get current path.
 *
 * @param mixed String for set a new path. or FALSE (default) to get current path.
 * @return mixed String if $path is FALSE. Or void if a new path was set.
 */
	public function cachePath($path = false) {
		if ($path === false) {
			$this->__cachePath = preg_replace('/(\/+)/', '', $path . DIRECTORY_SEPARATOR);

			if (!is_writable($this->__cachePath)) {
				die("Make sure the following path is writable: {$this->__cachePath}");
			}
		} else {
			return $this->__cachePath;
		}
	}

/**
 * Reads/writes temporary data to cache files.
 *
 * @param string $name	File name within /tmp to save the data.
 * @param mixed $data	The data to save to the temporary file.
 * @param mixed $expires A valid strtotime string when the data expires.
 * @return mixed The contents of the temporary file.
 */
	private function Cache($name, $data = null, $expires = '+1 day') {
		$now = time();

		if (!is_numeric($expires)) {
			$expires = strtotime($expires, $now);
		}

		$timediff = $expires - $now;
		$filetime = false;
		$filename = $this->__cachePath . $name;

		if (file_exists($filename)) {
			$filetime = @filemtime($filename);
		}

		if ($data === null) {
			if (file_exists($filename) && $filetime !== false) {
				if ($filetime + $timediff < $now) {
					@unlink($filename);
				} else {
					$data = @unserialize(file_get_contents($filename));
				}
			}
		} elseif (is_writable(dirname($filename))) {
			@file_put_contents($filename, serialize($data), LOCK_EX);
		}

		return $data;
	}
}