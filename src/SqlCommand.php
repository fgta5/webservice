<?php namespace Fgta5\Webservice;

/* Query
 *
 * usage:
 * 
  	$data = [
		'id' => '1234',
		'nama' => 'agung',
		'alamat' => 'jakarta',
		'nik' => '12345'
	];
	$query = Query::create('mst_session', $data, ['id']);
	$query->setQuote('`', '`');
	$query->generateSQL_Select();
	$sql = $query->getSQL();
	$params = $query->getParameter();
	$keys = $query->getKeys();
	$keysvalue = $query->getKeysValue();
*
*/

class SqlCommand {

	private static string $_default_fbq = '`'; // starting quote;
	private static string $_default_feq = '`'; // ending quote
	

	private string $_fbq;
	private string $_feq;

	private string $_tablename;
	private string $_sql;

	private array $_data;
	private array $_paramsvalue;
	private array $_keys;
	private array $_keysvalue;

	function __construct(string $tablename, array $data, ?array $keys=null) {
		try {
			$this->_tablename = $tablename;
			$this->_data = $data;
			if ($keys!=null) {
				$this->_keys = $keys;
				$keysvalue = [];
				foreach ($keys as $keyname) {
					if (!array_key_exists($keyname, $data)) {
						throw new \Exception("SqlCommand: key '$keyname' tidak ada di data");
					}
					$keysvalue[$keyname] = $data[$keyname];
				}
				$this->_keysvalue = $keysvalue;
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
		
	}
	
	public static function create(string $tablename, array $data, ?array $keys=null) : self {
		$query = new self($tablename, $data, $keys);
		$query->setQuote(self::$_default_fbq, self::$_default_feq);
		return $query;
	}

	public static function setDefaultQuote(string $fbq, string $feq) : void {
		self::$_default_fbq = $fbq; 
		self::$_default_feq = $feq;
	}

	public function setQuote(string $fbq, string $feq) : void {
		$this->_fbq = $fbq;
		$this->_feq = $feq;
	}

	private function quote(string $fieldname) : string {
		return $this->_fbq . $fieldname . $this->_feq; 
	}

	private function getparamname(string $fieldname) : string {
		return ":" . $fieldname;
	}

	public function getSQL() : string {
		return $this->_sql;
	}

	public function getParameter() : array {
		return $this->_paramsvalue;
	}

	public function generateSQL_Insert() : array {
		try {
			$fields = [];
			$paramsname = [];
			$paramsvalue = [];
			$keysvalue = [];
			foreach ($this->_data as $name=>$value) {
				$fieldname = $this->quote($name); 
				$paramname = $this->getparamname($name);

				$fields[] = $fieldname;
				$paramsname[] = $paramname;
				$paramsvalue[$paramname] = $value; 
			}

			$tablename = $this->quote($this->_tablename);
			
			$sql  = "INSERT INTO $tablename\r\n";
			$sql .= "(" .  implode(', ', $fields) . ")\r\n";
			$sql .= "VALUES\r\n";
			$sql .= "(" . implode(', ', $paramsname) . ")";

			$this->_sql = $sql;
			$this->_paramsvalue = $paramsvalue;
			$this->_keysvalue = $keysvalue; 

			return [
				'sql' => $sql,
				'parameter' => $paramsvalue,
				'keysvalue' => $keysvalue
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function generateSQL_Update() : array {
		try {
			if (!isset($this->_keys)) {
				throw new \Exception('keys belum didefinisikan pada saat create Query');
			}

			if ($this->_keys==null) {
				throw new \Exception('keys belum didefinisikan pada saat create Query');
			}

			$paramsvalue = [];
			$updatefields = [];
			$keyfields = [];
			$keysvalue = [];
			foreach ($this->_data as $name=>$value) {
				$fieldname = $this->quote($name); 
				$paramname = $this->getparamname($name);

				if (!in_array($name, $this->_keys)) {
					$updatefields[] = "$fieldname = $paramname";
				} else {
					$keyfields[] = "$fieldname=$paramname";
					$keysvalue[$name] = $value;
				}
				$paramsvalue[$paramname] = $value; 
			}

			$tablename = $this->quote($this->_tablename);
			$sql  = "UPDATE $tablename\r\n";
			$sql .= "SET\r\n";
			$sql .= implode(",\r\n", $updatefields) . "\r\n";
			$sql .= "WHERE\r\n";
			$sql .= implode(' AND ', $keyfields);

			$this->_sql = $sql;
			$this->_paramsvalue = $paramsvalue;
			$this->_keysvalue = $keysvalue;

			return [
				'sql' => $sql,
				'parameter' => $paramsvalue,
				'keysvalue' => $keysvalue
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function generateSQL_Delete() : array {
		try {
			if (!isset($this->_keys)) {
				throw new \Exception('keys belum didefinisikan pada saat create Query');
			}

			if ($this->_keys==null) {
				throw new \Exception('keys belum didefinisikan pada saat create Query');
			}

			$paramsvalue = [];
			$keyfields = [];
			$keysvalue = [];
			foreach ($this->_data as $name=>$value) {
				$operator="=";
				if (is_array($value)) {
					$operator = $value[0];
					$value = $value[1];
				}

				$fieldname = $this->quote($name); 
				$paramname = $this->getparamname($name);
				if (in_array($name, $this->_keys)) {
					$keyfields[] = "$fieldname".$operator."$paramname";
					$keysvalue[$name] = $value;
				}
				$paramsvalue[$paramname] = $value; 
			}

			$tablename = $this->quote($this->_tablename);
			$sql  = "DELETE FROM $tablename\r\n";
			$sql .= "WHERE\r\n";
			$sql .= implode(' AND ', $keyfields);


			$this->_sql = $sql;
			$this->_paramsvalue = $paramsvalue;
			$this->_keysvalue = $keysvalue;

			return [
				'sql' => $sql,
				'parameter' => $paramsvalue,
				'keysvalue' => $keysvalue
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function generateSQL_Select() : array {
		try {
			$fields = [];
			$paramsname = [];
			$paramsvalue = [];
			$keyfields = [];
			$keysvalue = [];
			foreach ($this->_data as $name=>$value) {
				$fieldname = $this->quote($name); 
				$paramname = $this->getparamname($name);

				$fields[] = $fieldname;
				if (!isset($this->_keys)) {
					continue;
				}

				if (is_array($this->_keys)) {
					if (in_array($name, $this->_keys)) {
						$keyfields[] = "$fieldname=$paramname";
						$keysvalue[$name] = $value;
						$paramsname[] = $paramname;
						$paramsvalue[$paramname] = $value; 
					}
				}
			}

			$tablename = $this->quote($this->_tablename);
			$sql  = "SELECT " . implode(', ', $fields) . "\r\n";
			$sql .= "FROM $tablename\r\n";

			if (isset($this->_keys)) {
				if (is_array($this->_keys)) {
					$sql .= "WHERE\r\n";
					$sql .= implode(' AND ', $keyfields);
				} 
			}

			$this->_sql = $sql;
			$this->_paramsvalue = $paramsvalue;
			$this->_keysvalue = $keysvalue;


			return [
				'sql' => $sql,
				'parameter' => $paramsvalue,
				'keysvalue' => $keysvalue
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function getData() : array {
		return $this->_data;
	}

	public function getKeys() : array {
		return $this->_keys;
	}

	public function getKeysValue() : array {
		return $this->_keysvalue;
	}





}