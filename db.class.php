<?php

class DB{
	var $db;
	var $prefix;
	
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
	
	function charset($charset){
		$this->db->exec('SET CHARACTER SET ' . $charset);
	}
	
	function prefix($prefix){
		$this->prefix = $prefix;
	}
	
	function conditionParse($condition){
		$where = '';
		if($condition){
			if(is_array($condition)){
				foreach($condition as $key => $value){
					$where .= ($where ? ' AND ' : '') . $key . '=' . $this->db->quote($value);
				}
			}else{
				$part = explode('\'', $condition);
				$num = 1;
				foreach($part as $value){
					$where .= ($num%2 ? $value : $this->db->quote($value));
					$num++;
				}
			}
		}
		return $where;
	}
	
	function dataGet($table, $column, $condition, $limit = 0, $order = '', $addkey = false){
		$where = $this->conditionParse($condition);
		
		$query = "SELECT " . $column . " FROM " . $this->prefix . $table;
		if(isset($where)) $query .= " WHERE " . $where;
		if($order) $query .= " ORDER BY " . $order;
		if($limit) $query .= " LIMIT " . $limit;
		
		$response = [];
		$response['query'] = $query;
		
		$result = $this->db->query($query);
		$error = $this->db->errorInfo();
		
		if($error[2]){
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['rows'] = 0;
		}else{
			$response['rows'] = $result->rowCount();
			$response['result'] = [];
			if($result->rowCount()==1 && !$addkey){
				$response['result'] = $result->fetch(PDO::FETCH_ASSOC);
			}else if($result->rowCount()==1 && $addkey){
				$response['result'][0] = $result->fetch(PDO::FETCH_ASSOC);
			}else if($result->rowCount()>1){
				$id = 0;
				while($response['result'][$id] = $result->fetch(PDO::FETCH_ASSOC)){
					$id++;
				}
				unset($response['result'][$id]);
			}
		}
		
		return $response;
	}

	function dataInsert($table, $data, $stackvalues = false){
		$keys = '';
		foreach($data as $key => $value){
			if(is_array($value)){
				if($stackvalues){
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
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['rows'] = 0;
			$response['success'] = false;
		}else{
			$response['rows'] = $result->rowCount();
			$response['id'] = $this->db->lastInsertId();
			$response['success'] = count($values) == $response['rows'];
		}
		
		return $response;
	}
	
	function dataUpdate($table, $data, $condition, $limit = 0){
		$set = '';
		foreach($data as $key => $value){
			$set .= ($set ? ', ' : '') . $key . '=' . $thie->db->quote($value);
		}
		
		$where = $this->conditionParse($condition);
		
		$query = "UPDATE " . $thie->prefix . $table . " SET " . $set;
		if(isset($where)) $query .= " WHERE " . $where;
		if($limit) $query .= " LIMIT " . $limit;
		
		$response = [];
		
		$response['query'] = $query;
		
		$result = $thie->db->query($query);
		$error = $thie->db->errorInfo();
		
		if($error[2]){
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['rows'] = 0;
			$response['success'] = false;
		}else{
			$response['rows'] = $result->rowCount();
			$response['success'] = true;
		}
		
		return $response;
	}

	function dataDelete($table, $condition, $limit = 0){
		$where = $this->conditionParse($condition);
		
		$query = "DELETE FROM " . $thie->prefix . $table;
		if($where) $query .= " WHERE " . $where;
		if($limit) $query .= " LIMIT " . $limit;
		
		$response = [];
		
		$response['query'] = $query;
		
		$result = $thie->db->query($query);
		$error = $thie->db->errorInfo();
		
		if($error[2]){
			$response['error']['code'] = $error[0];
			$response['error']['message'] = $error[2];
			$response['rows'] = 0;
			$response['success'] = false;
		}else{
			$response['rows'] = $result->rowCount();
			$response['success'] = true;
		}
		
		return $response;
	}
}

?>