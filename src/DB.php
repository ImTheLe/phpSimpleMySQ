<?php
// php-db-pdo-mysql by Leon, MIT License

namespace Le;

Exception::register(20, [
	11 => "Invalid database host, expecting string",
	12 => "Invalid database port, expecting number larger than 0",
	13 => "Invalid database name, expecting string",
	14 => "Invalid database user, expecting string",
	15 => "Invalid database password, expecting string",
	16 => "Invalid database prefix, expecting string",
	17 => "Invalid database charset, expecting string",
	18 => "Database connection error",
	19 => "Invalid condition column name with index ?, expecting non-empty string",
	20 => "Invalid condition value for column '?', expecting string, number, bool or null",
	21 => "Invalid additional option 'order', expecting string",
	22 => "Invalid additional option 'limit', expecting number larger than 0",
	23 => "Invalid additional option 'offset', expecting positive number",
	24 => "Invalid data column name with index ?, expecting non-empty string",
	25 => "Data value count doesn't match column count on entry with index ?",
	26 => "Invalid data value for column '?', expecting string, number, bool or null",
	27 => "Invalid table name, expecting string",
	28 => "Invalid columns list, expecting string",
	29 => "Invalid conditions list, expecting array or string",
	30 => "Invalid additional parameter, expecting array",
	31 => "Invalid data list, expecting non-empty array",
	32 => "Database query error: ?"
]);

class DB{
	private $db, $prefix, $return_query, $transaction = false;

	function __construct($argoptions = []){
		$options = ['host' => 'localhost', 'port' => 3306, 'database' => '', 'user' => '', 'password' => '', 'return_query' => false];
		$options = array_replace($options, $argoptions);

		if(!is_string($options['host']) || empty($options['host'])) throw new Exception(20, 11);
		if(!is_numeric($options['port']) || $options['port'] < 1) throw new Exception(20, 12);
		if(!is_string($options['database']) || empty($options['database'])) throw new Exception(20, 13);
		if(!is_string($options['user'])) throw new Exception(20, 14);
		if(!is_string($options['password'])) throw new Exception(20, 15);

		if(isset($options['prefix']) && (!is_string($options['prefix']) || empty($options['prefix']))) throw new Exception(20, 16);
		if(isset($options['charset']) && (!is_string($options['charset']) || empty($options['charset']))) throw new Exception(20, 17);

		$this->return_query = $options['return_query'];
		$this->prefix = (isset($options['prefix'])) ? $options['prefix'] : '';

		try{
			$this->db = new \PDO('mysql:host=' . $options['host'] . ';port=' . $options['port'] . ';dbname=' . $options['database'], $options['user'], $options['password']);
		}catch(\PDOException $error){
			throw new Exception(20, 18, [], $error);
		}

		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		if(isset($options['charset']) && !empty($options['charset'])) $this->db->exec('SET CHARACTER SET ' . $options['charset']);
	}

	function __destruct(){
		$this->db = null;
	}

	private function conditionsParse($conditions){
		if(empty($conditions)) return '';
		if(is_string($conditions)) return 'WHERE ' . $conditions;

		$query = '';
		$index = 0;
		foreach($conditions as $column => $value){
			if(!is_string($column) && !is_numeric($column) || empty($column)) throw new Exception(20, 19, [$index]);
			if(!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) throw new Exception(20, 20, [$column]);

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

	private function additionalParse($additional){
		$query = '';

		if(isset($additional['order'])){
			if(!is_string($additional['order'])) throw new Exception(20, 21);
			if($additional['order']) $query .= ' ORDER BY ' . $additional['order'];
		}
		
		if(isset($additional['single']) && $additional['single']) $additional['limit'] = 1;

		if(isset($additional['limit'])){
			if(!is_numeric($additional['limit']) || $additional['limit']<1) throw new Exception(20, 22);
			$query .= ' LIMIT ' . $additional['limit'];

			if(isset($additional['offset'])){
				if(!is_numeric($additional['offset']) || $additional['offset']<0) throw new Exception(20, 23);
				$query .= ' OFFSET ' . $additional['offset'];
			}
		}

		return $query;
	}

	private function dataParse($data, $single_row = false){
		$query = '';
		$values = '';

		// $single_row is true for update method to make sure the SET syntax is used
		if(isset($data[0]) && is_array($data[0]) && !$single_row){ // format: 0 => [column names], 1 => [row values], 2 => ...
			$count = count($data[0]);
			foreach($data[0] as $index => $column){
				if(!is_string($column) && !is_numeric($column) || empty($column)) throw new Exception(20, 24, [$index]);
				$data[0][$index] = $this->escapeName($column);
			}
			$query = '(' . implode(', ', $data[0]) . ') VALUES ';

			foreach($data as $id => $row){
				if($id === 0) continue; // skipping column names

				if(count($row) != $count) throw new Exception(20, 25, [$id]);

				foreach($row as $column_id => $value){
					if(!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) throw new Exception(20, 26, [$data[0][$column_id]]);

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
				if(!is_string($column) && !is_numeric($column) || empty($column)) throw new Exception(20, 24, [$index]);
				if(!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) throw new Exception(20, 26, [$column]);

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

	public function schema($table){
		$query = 'DESCRIBE ' . $this->prefix . $table;

		try{
			$result = $this->db->query($query);
		}catch(\PDOException $error){
			throw new Exception(20, 32, [$query], $error);
		}

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
		if(!is_string($table)) throw new Exception(20, 27);
		if(!is_string($columns)) throw new Exception(20, 28);
		if(!is_array($conditions) && !is_string($conditions)) throw new Exception(20, 29);
		if(!is_array($additional)) throw new Exception(20, 30);

		$query = 'SELECT ' . $columns . ' FROM ' . $this->escapeName($this->prefix . $table) . $this->conditionsParse($conditions) . $this->additionalParse($additional);

		try{
			$result = $this->db->query($query);
		}catch(\PDOException $error){
			throw new Exception(20, 32, [$query], $error);
		}

		$data = [];
		if($result->rowCount() > 0){
			$id = 0;
			while($data[$id] = $result->fetch(\PDO::FETCH_ASSOC)) $id++;
			unset($data[$id]);

			if($result->rowCount() === 1 && isset($additional['single']) && $additional['single']) $data = $data[0];
		}

		$output = [
			'count' => $result->rowCount(),
			'data' => $data
		];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function insert($table, $data){
		if(!is_string($table)) throw new Exception(20, 27);
		if(!is_array($data) || empty($data)) throw new Exception(20, 31);

		$query = 'INSERT INTO ' . $this->escapeName($this->prefix . $table) . $this->dataParse($data);

		try{
			$result = $this->db->query($query);
		}catch(\PDOException $error){
			throw new Exception(20, 32, [$query], $error);
		}

		$output = [
			'count' => $result->rowCount(),
			'id' => $this->db->lastInsertId()
		];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function update($table, $data, $conditions = [], $additional = []){
		if(!is_string($table)) throw new Exception(20, 27);
		if(!is_array($data) || empty($data)) throw new Exception(20, 31);
		if(!is_array($conditions) && !is_string($conditions)) throw new Exception(20, 29);
		if(!is_array($additional)) throw new Exception(20, 30);

		$query = 'UPDATE ' . $this->escapeName($this->prefix . $table) . $this->dataParse($data, true) . $this->conditionsParse($conditions) . $this->additionalParse($additional);

		try{
			$result = $this->db->query($query);
		}catch(\PDOException $error){
			throw new Exception(20, 32, [$query], $error);
		}

		$output = ['count' => $result->rowCount()];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function delete($table, $conditions = [], $additional = []){
		if(!is_string($table)) throw new Exception(20, 27);
		if(!is_array($conditions) && !is_string($conditions)) throw new Exception(20, 29);
		if(!is_array($additional)) throw new Exception(20, 30);

		$query = 'DELETE FROM ' . $this->escapeName($this->prefix . $table) . $this->conditionsParse($conditions) . $this->additionalParse($additional);

		try{
			$result = $this->db->query($query);
		}catch(\PDOException $error){
			throw new Exception(20, 32, [$query], $error);
		}

		$output = ['count' => $result->rowCount()];
		if($this->return_query) $output['query'] = $query;
		return $output;
	}

	public function transactionBegin(){
		if($this->transaction) return true;

		$this->db->beginTransaction();
		$this->transaction = true;

		return true;
	}

	public function commit(){
		if(!$this->transaction) return false;

		$this->db->commit();
		$this->transaction = false;

		return true;
	}

	public function rollback(){
		if(!$this->transaction) return false;

		$this->db->rollBack();
		$this->transaction = false;

		return true;
	}

	public function escape($string){
		return $this->db->quote($string);
	}
	
	public function escapeName($string){
		return '`' . str_replace('`', '\\`', $string) . '`';
	}
}
