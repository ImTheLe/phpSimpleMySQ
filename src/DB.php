<?php namespace Le;
// php-db-pdo-mysql by Leon, MIT License

final class DB{
	public static $debug = false;

	private static function parseColumns($columns){
		if(empty($columns)) return '';
		if(is_string($columns)) return $columns;

		foreach($columns as $index => $column){
			if(self::$debug && (!is_string($column) && !is_numeric($column) || empty($column))) throw new Error("DEBUG: invalid column name with index " . $index);
			$columns[$index] = $this->escapeName($column);
		}

		$query = implode(', ', $columns);
		return $query;
	}

	private static function parseConditions($conditions){
		if(empty($conditions)) return '';
		if(is_string($conditions)) return ' WHERE ' . $conditions;

		$query = '';
		$index = 0;
		foreach($conditions as $column => $value){
			if(self::$debug){
				if(!is_string($column) && !is_numeric($column) || empty($column)) throw new Error("DEBUG: invalid column name with index " . $index);
				if(!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) throw new Error("DEBUG: invalid value for column " . $column);
			}

			if(is_bool($value)) $value = $value ? 1 : 0;
			$column = $this->escapeName($column);

			if($query) $query .= ' AND ';

			if(is_null($value)){
				$query .= $column . ' IS NULL';
			}else{
				$value = $this->db->quote($value);
				$query .= $column . '=' . $value;
			}

			$index++;
		}

		return ' WHERE ' . $query;
	}

	private static function parseAdditional($additional){
		$query = '';

		if(isset($additional['order'])){
			if(self::$debug && !is_string($additional['order'])) throw new Error("DEBUG: invalid additional order");
			if($additional['order']) $query .= ' ORDER BY ' . $additional['order'];
		}

		if(isset($additional['single']) && $additional['single']) $additional['limit'] = 1;

		if(isset($additional['limit'])){
			if(self::$debug && (!is_numeric($additional['limit']) || $additional['limit']<1)) throw new Error("DEBUG: invalid additional limit");
			$query .= ' LIMIT ' . $additional['limit'];

			if(isset($additional['offset'])){
				if(self::$debug && (!is_numeric($additional['offset']) || $additional['offset']<0)) throw new Error("DEBUG: invalid additional offset");
				$query .= ' OFFSET ' . $additional['offset'];
			}
		}

		return $query;
	}

	private static function parseData($data, $single_row = false){
		$query = '';
		$values = '';

		// $single_row is true for update method to make sure the SET syntax is used
		if(isset($data[0]) && is_array($data[0]) && !$single_row){ // format: 0 => [column names], 1 => [row values], 2 => ...
			$count = count($data[0]);
			foreach($data[0] as $index => $column){
				if(self::$debug && (!is_string($column) && !is_numeric($column) || empty($column))) throw new Error("DEBUG: invalid column name with index " . $index);
				$data[0][$index] = $this->escapeName($column);
			}
			$query = '(' . implode(', ', $data[0]) . ') VALUES ';

			foreach($data as $id => $row){
				if($id === 0) continue; // skipping column names

				if(count($row) != $count) throw new Error("DEBUG: value count doesn't match column count on id " . $id);

				foreach($row as $column_id => $value){
					if(self::$debug && !is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) throw new Error("DEBUG: invalid value for column " . $data[0][$column_id]);

					if(is_bool($value)) $value = $value ? 1 : 0;

					if(is_null($value)) $row[$column_id] = 'NULL';
					else $row[$column_id] = $this->db->quote($value);
				}

				if($values) $values .= ', ';
				$values .= '(' . implode(', ', $row) . ')';
			}
		}else{ // format: 'column1' => 'value1', 'column2' => 'value2', ...
			$query = ' SET ';
			$index = 0;
			foreach($data as $column => $value){
				if(self::$debug){
					if(!is_string($column) && !is_numeric($column) || empty($column)) throw new Error("DEBUG: invalid column with index " . $index);
					if(!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) throw new Error("DEBUG: invalid value for column " . $column);
				}

				if(is_bool($value)) $value = $value ? 1 : 0;

				if(is_null($value)) $value = 'NULL';
				else $value = $this->db->quote($value);

				if($values) $values .= ', ';
				$values .= $this->escapeName($column) . '=' . $value;

				$index++;
			}
		}

		$query .= $values;
		return $query;
	}

	// OBJECT

	private $db, $prefix, $return_query, $transaction = 0;

	function __construct($argoptions = []){
		$options = ['host' => 'localhost', 'port' => 3306, 'database' => '', 'user' => '', 'password' => '', 'return_query' => self::$debug];
		$options = array_replace($options, $argoptions);

		if(self::$debug){
			if(!is_string($options['host']) || empty($options['host'])) throw new Error("DEBUG: invalid host");
			if(!is_numeric($options['port']) || $options['port'] < 1) throw new Error("DEBUG: invalid port");
			if(!is_string($options['database']) || empty($options['database'])) throw new Error("DEBUG: invalid db name");
			if(!is_string($options['user'])) throw new Error("DEBUG: invalid username");
			if(!is_string($options['password'])) throw new Error("DEBUG: invalid password");

			if(isset($options['prefix']) && (!is_string($options['prefix']) || empty($options['prefix']))) throw new Error("DEBUG: invalid table prefix");
			if(isset($options['charset']) && (!is_string($options['charset']) || empty($options['charset']))) throw new Error("DEBUG: invalid charset");
		}

		$this->return_query = $options['return_query'];
		$this->prefix = (isset($options['prefix'])) ? $options['prefix'] : '';

		$this->db = new \PDO('mysql:host=' . $options['host'] . ';port=' . $options['port'] . ';dbname=' . $options['database'], $options['user'], $options['password']);
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		if(isset($options['charset']) && !empty($options['charset'])) $this->db->exec('SET CHARACTER SET ' . $options['charset']);
	}

	function __destruct(){
		$this->db = null;
	}

	public function escape($string){
		return $this->db->quote($string);
	}

	public function escapeName($string){
		return '`' . str_replace('`', '\\`', $string) . '`';
	}

	public function schema($table){
		$query = 'DESCRIBE ' . $this->escapeName($this->prefix . $table);
		$result = $this->db->query($query);

		$data = []; $id = 0;
		while($data[$id] = $result->fetch(\PDO::FETCH_ASSOC)) $id++;
		unset($data[$id]);

		$output = [
			'count' => $result->rowCount(),
			'data' => $data
		];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function get($table, $columns = '*', $conditions = [], $additional = []){
		if(self::$debug){
			if(!is_string($table)) throw new Error("DEBUG: invalid table");
			if(!is_array($conditions) && !is_string($conditions)) throw new Error("DEBUG: invalid columns");
			if(!is_array($conditions) && !is_string($conditions)) throw new Error("DEBUG: invalid conditions");
			if(!is_array($additional)) throw new Error("DEBUG: invalid additional");
		}

		$query = 'SELECT ' . self::parseColumns($columns) . ' FROM ' . $this->escapeName($this->prefix . $table) . self::parseConditions($conditions) . self::parseAdditional($additional);
		$result = $this->db->query($query);

		$data = [];
		if($result->rowCount() > 0){
			$id = 0;
			while($data[$id] = $result->fetch(\PDO::FETCH_ASSOC)) $id++;
			unset($data[$id]);

			if(isset($additional['single']) && $additional['single']) $data = $data[0];
		}

		$output = [
			'count' => $result->rowCount(),
			'data' => $data
		];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function insert($table, $data){
		if(self::$debug){
			if(!is_string($table)) throw new Error("DEBUG: invalid table");
			if(!is_array($data) || empty($data)) throw new Error("DEBUG: invalid data");
		}

		$query = 'INSERT INTO ' . $this->escapeName($this->prefix . $table) . self::parseData($data);
		$result = $this->db->query($query);

		$output = [
			'count' => $result->rowCount(),
			'id' => $this->db->lastInsertId()
		];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function update($table, $data, $conditions = [], $additional = []){
		if(self::$debug){
			if(!is_string($table)) throw new Error("DEBUG: invalid table");
			if(!is_array($data) || empty($data)) throw new Error("DEBUG: invalid data");
			if(!is_array($conditions) && !is_string($conditions)) throw new Error("DEBUG: invalid conditions");
			if(!is_array($additional)) throw new Error("DEBUG: invalid additional");
		}

		$query = 'UPDATE ' . $this->escapeName($this->prefix . $table) . self::parseData($data, true) . self::parseConditions($conditions) . self::parseAdditional($additional);
		$result = $this->db->query($query);

		$output = ['count' => $result->rowCount()];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function delete($table, $conditions = [], $additional = []){
		if(self::$debug){
			if(!is_string($table)) throw new Error("DEBUG: invalid table");
			if(!is_array($conditions) && !is_string($conditions)) throw new Error("DEBUG: invalid conditions");
			if(!is_array($additional)) throw new Error("DEBUG: additional");
		}

		$query = 'DELETE FROM ' . $this->escapeName($this->prefix . $table) . self::parseConditions($conditions) . self::parseAdditional($additional);
		$result = $this->db->query($query);

		$output = ['count' => $result->rowCount()];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function transactionBegin(){
		if($this->transaction){
			$this->transaction++;
			return true;
		}

		$this->db->beginTransaction();
		$this->transaction = 1;
		return true;
	}

	public function commit(){
		if(!$this->transaction) return false;

		if($this->transaction === 1) $this->db->commit();
		$this->transaction--;

		return true;
	}

	public function rollback(){
		if(!$this->transaction) return false;

		$this->db->rollBack();
		$this->transaction = 0;

		return true;
	}
}
