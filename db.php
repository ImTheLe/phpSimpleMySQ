<?php
// phpSimpleMySQL v2.4.0 by ImTheLe, MIT License

namespace Le;

class DB{
	private $db, $prefix;

	protected function error($error, $query){
		return ['success' => false, 'query' => $query, 'count' => 0, 'error' => ['code' => $error[0], 'desc' => $error[2], 'trace' => debug_backtrace()]];
	}

	protected function output($status, $query, $count, $data = [], $id = 0){
		return ['success' => $status, 'query' => $query, 'count' => $count, 'data' => $data, 'id' => $id];
	}

	function __construct($argoptions = []){
		$options = ['host' => 'localhost', 'port' => 3306, 'database' => '', 'user' => '', 'password' => ''];
		$options = array_replace_recursive($options, $argoptions);

		try{
			$this->db = new \PDO('mysql:host=' . $options['host'] . ';port=' . $options['port'] . ';dbname=' . $options['database'], $options['user'], $options['password']);
		}catch(\PDOException $error){
			throw new Exception(json_encode(['error' => ['code' => $error->getCode(), 'desc' => $error->getMessage(), 'trace' => debug_backtrace()]]));
		}

		$this->prefix = isset($options['prefix']) ? $options['prefix'] : '';
		if(isset($options['charset'])) $this->db->exec('SET CHARACTER SET ' . $options['charset']);
	}

	function __destruct(){
		$this->db = null;
	}

	private function conditionsParse($conds){
		if(is_string($conds) || !is_array($conds)) return $conds;

		$where = '';
		foreach($conds as $key => $value){
			$where .= ($where ? ' AND ' : '') . $key . '=' . ($value===null?'NULL':$this->db->quote($value));
		}
		return $where;
	}

	private function buildQuery($action, $table, $options = [], $additional = []){
		$query = '';
		if($action === 'get') $query = 'SELECT ' . $options['columns'] . ' FROM ' . $this->prefix . $table;
		else if($action === 'update') $query = 'UPDATE ' . $this->prefix . $table . ' SET ' . $options['data'];
		else if($action === 'delete') $query = 'DELETE FROM ' . $this->prefix . $table;

		if(isset($options['conditions']) && !empty($options['conditions'])) $query .= ' WHERE ' . $options['conditions'];
		if(isset($additional['order']) && $additional['order']) $query .= ' ORDER BY ' . $additional['order'];
		if(isset($additional['limit']) && $additional['limit']){
			$query .= ' LIMIT ' . $additional['limit'];
			if(isset($additional['offset']) && $additional['offset']) $query .= ' OFFSET ' . $additional['offset'];
		}

		return $query;
	}

	public function escape($string){
		return $this->db->quote($string);
	}

	public function dataGet($table, $columns = '*', $conditions = [], $additional = []){
		$query = $this->buildQuery('get', $table, ['columns' => $columns, 'conditions' => $this->conditionsParse($conditions)], $additional);
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		if($error[1]) return $this->error($error, $query);

		$data = [];
		if($result->rowCount()==1){
			if(isset($additional['limit']) && $additional['limit'] == 1 && isset($additional['single_no_key']) && $additional['single_no_key'])
				$data = $result->fetch(\PDO::FETCH_ASSOC);
			else
				$data[0] = $result->fetch(\PDO::FETCH_ASSOC);
		}else if($result->rowCount()>1){
			$id = 0;
			while($data[$id] = $result->fetch(\PDO::FETCH_ASSOC)) $id++;
			unset($data[$id]);
		}

		return $this->output(true, $query, $result->rowCount(), $data);
	}

	public function dataInsert($table, $data, $additional = []){
		$keys = '';
		foreach($data as $key => $value){
			if(is_array($value)){
				if(isset($additional['stacked_values']) && $additional['stacked_values']){
					foreach($value as $subkey => $subvalue){
						$values[$subkey][$key] = ($subvalue===null?'NULL':$this->db->quote($subvalue));
					}
					$keys = implode(', ', array_keys($data));
				}else{
					foreach($value as $subkey => $subvalue){
						$values[$key][$subkey] = ($subvalue===null?'NULL':$this->db->quote($subvalue));
					}
					$keys = implode(', ', array_keys($value));
				}
			}else{
				$values[0][$key] = ($value===null?'NULL':$this->db->quote($value));
				$keys = implode(', ', array_keys($data));
			}
		}

		$query = 'INSERT INTO ' . $this->prefix . $table . " ($keys) VALUES ";

		$num = 0;
		foreach($values as $key => $value){
			$qv = implode(', ', $values[$key]);
			$query .= ($num++ ? ', ' : '') . "($qv)";
		}

		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		if($error[1]) return $this->error($error, $query);

		return $this->output(count($values) === $result->rowCount(), $query, $result->rowCount(), [], $this->db->lastInsertId());
	}

	public function dataUpdate($table, $data, $conditions = [], $additional = []){
		$set = '';
		foreach($data as $key => $value){
			$set .= ($set ? ', ' : '') . $key . '=' . ($value===null?'NULL':$this->db->quote($value));
		}

		$query = $this->buildQuery('update', $table, ['data' => $set, 'conditions' => $this->conditionsParse($conditions)], $additional);
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		if($error[1]) return $this->error($error, $query);

		return $this->output(true, $query, $result->rowCount());
	}

	public function dataDelete($table, $conditions = [], $additional = []){
		$query = $this->buildQuery('delete', $table, ['conditions' => $this->conditionsParse($conditions)], $additional);
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		if($error[1]) return $this->error($error, $query);

		return $this->output(true, $query, $result->rowCount());
	}
}

?>
