<?php
// phpSimpleMySQL v1.2 by ImTheLe, MIT License

class DB{
	private $db, $private;
	
	function __construct($host, $dbname, $user, $pass, $port = 3306){
		if(!$user) $user = '';
		if(!$pass) $pass = '';
		
		try{
			$this->db = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname, $user, $pass);
		}catch(PDOException $error){
			throw new Exception($error);
		}
		
		$this->prefix = '';
	}
	
	function __destruct(){
		$this->db = null;
	}
	
	public function charset($charset){
		$this->db->exec('SET CHARACTER SET ' . $charset);
	}
	
	public function prefix($prefix){
		$this->prefix = $prefix;
	}
	
	private function conditionsParse($conditions){
		$where = '';
		if($conditions){
			if(is_array($conditions)){
				foreach($conditions as $key => $value){
					$where .= ($where ? ' AND ' : '') . $key . '=' . $this->db->quote($value);
				}
			}else{
				$part = explode('\'', $conditions);
				$num = 1;
				foreach($part as $value){
					$where .= ($num%2 ? $value : $this->db->quote($value));
					$num++;
				}
			}
		}
		return $where;
	}
	
	public function dataGet($table, $columns, $conditions, $additional = []){
		$where = $this->conditionsParse($conditions);
		
		$query = "SELECT " . $columns . " FROM " . $this->prefix . $table;
		if($where) $query .= " WHERE " . $where;
		if(isset($additional['order']) && $additional['order']) $query .= " ORDER BY " . $additional['order'];
		if(isset($additional['limit']) && $additional['limit']){
			$query .= " LIMIT " . $additional['limit'];
			if(isset($additional['offset']) && $additional['offset']) $query .= " OFFSET " . $additional['offset'];
		}
		
		$response = [];
		$response['query'] = $query;
		
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		
		if($error[2]){
			$response['success'] = false;
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['count'] = 0;
		}else{
			$response['success'] = true;
			$response['count'] = $result->rowCount();
			$response['rows'] = [];
			if($result->rowCount()==1 && (!isset($additional['single_key']) || !$additional['single_key'])){
				$response['rows'] = $result->fetch(PDO::FETCH_ASSOC);
			}else if($result->rowCount()==1 && isset($additional['single_key']) && $additional['single_key']){
				$response['rows'][0] = $result->fetch(PDO::FETCH_ASSOC);
			}else if($result->rowCount()>1){
				$id = 0;
				while($response['rows'][$id] = $result->fetch(PDO::FETCH_ASSOC)){
					$id++;
				}
				unset($response['rows'][$id]);
			}
		}
		
		return $response;
	}

	public function dataInsert($table, $data, $additional = []){
		$keys = '';
		foreach($data as $key => $value){
			if(is_array($value)){
				if(isset($additional['stacked_values']) && $additional['stacked_values']){
					foreach($value as $subkey => $subvalue){
						$values[$subkey][$key] = $this->db->quote($subvalue);
					}
					$keys = implode(", ", array_keys($data));
				}else{
					foreach($value as $subkey => $subvalue){
						$values[$key][$subkey] = $this->db->quote($subvalue);
					}
					$keys = implode(", ", array_keys($value));
				}
			}else{
				$values[0][$key] = $this->db->quote($value);
				$keys = implode(", ", array_keys($data));
			}
		}
		
		$query = "INSERT INTO " . $this->prefix . $table . " ($keys) VALUES ";
		
		$num = 0;
		foreach($values as $key => $value){
			$qv = implode(", ", $values[$key]);
			$query .= ($num++ ? ', ' : '') . "($qv)";
		}
		
		$response = [];
		$response['query'] = $query;
		
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		
		if($error[2]){
			$response['success'] = false;
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['count'] = 0;
		}else{
			$response['success'] = count($values) == $response['count'];
			$response['count'] = $result->rowCount();
			$response['id'] = $this->db->lastInsertId();
		}
		
		return $response;
	}
	
	public function dataUpdate($table, $data, $conditions, $additional = []){
		$set = '';
		foreach($data as $key => $value){
			$set .= ($set ? ', ' : '') . $key . '=' . $this->db->quote($value);
		}
		
		$where = $this->conditionsParse($conditions);
		
		$query = "UPDATE " . $this->prefix . $table . " SET " . $set;
		if($where) $query .= " WHERE " . $where;
		if(isset($additional['limit']) && $additional['limit']) $query .= " LIMIT " . $additional['limit'];
		
		$response = [];
		
		$response['query'] = $query;
		
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		
		if($error[2]){
			$response['success'] = false;
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['count'] = 0;
		}else{
			$response['success'] = true;
			$response['count'] = $result->rowCount();
		}
		
		return $response;
	}

	public function dataDelete($table, $conditions, $additional = []){
		$where = $this->conditionsParse($conditions);
		
		$query = "DELETE FROM " . $this->prefix . $table;
		if($where) $query .= " WHERE " . $where;
		if(isset($additional['limit']) && $additional['limit']) $query .= " LIMIT " . $additional['limit'];
		
		$response = [];
		
		$response['query'] = $query;
		
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		
		if($error[2]){
			$response['success'] = false;
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['count'] = 0;
		}else{
			$response['success'] = true;
			$response['count'] = $result->rowCount();
		}
		
		return $response;
	}
}

?>