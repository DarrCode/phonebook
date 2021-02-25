<?php

class Player {
    protected $server = "localhost";
    protected $username = "root";
    protected $database = "bookphone";
    protected $pass = "";

    private static $prefix;
	private static $number;
	private static $name;
	private static $data=[];
    private static $phone_codes = [];

   // table name
    protected $tableName = 'all_phone_book';


    public function __construct(){
        if(!isset($this->db)){
            // Connect to the database
            $conn = new mysqli($this->server, $this->username, $this->pass, $this->database);

            if($conn->connect_error){
                die("Failed to connect with MySQL: " . $conn->connect_error);
            }else{
                $this->db = $conn;
            }
        }
    }

    /**
     * function is used to add record
     * @param array $data
     * @return int $lastInsertedId
     */
    public function add($data) {

        if (!empty($data)) {
            self::$name = $data["name"];
            self::$prefix = $data["prefix"];
            self::$number = $data["number"];
        }

        $sql = "INSERT INTO {$this->tableName} 
            (name, prefix, number)
            VALUES  ('".self::$name."', '".self::$prefix."', '".self::$number."')
            ON DUPLICATE KEY UPDATE name='".self::$name."'";
        $result = mysqli_query($this->db, $sql);
        return $result;
    }

    /**
     * function is used edit    
     * @param array $data
     * @return int $lastInsertedId
     */
    public function update($data, $id) {
        if (!empty($data)) {                   
            self::$name = $data["name"];
            self::$prefix = $data["prefix"];
            self::$number = $data["number"];
        }   

        $sql = "UPDATE {$this->tableName} SET name='".self::$name."', prefix='".self::$prefix."', number='".self::$number."' WHERE id=$id";
        try {
            mysqli_query($this->db, $sql);
            return 'ok';
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            $this->db->rollback();
        }
    }

    /**
     * function is used to get records
     * @param int $stmt
     * @param int @limit
     * @return array $results
     */

    public function getRows($start = 0, $limit = 4) {
         if ($sql = $this->db->query( "SELECT * FROM {$this->tableName} WHERE deleted=0 ORDER BY id ASC LIMIT {$start},{$limit}")) {

            while($row = $sql->fetch_array(MYSQLI_ASSOC)) {
                self::$data[] = $row;
            }
           return self::$data;
        }
    }

    // delete row using id
    public function deleteRow($id) {

        if (!$id)
		    return false;
            
        $sql = "DELETE FROM {$this->tableName} WHERE id='".floor($id)."'";

        $result = mysqli_query($this->db, $sql);
        
		if ($result) 
			return $result;	
    }

    public function getCount() {

        $query = $this->db->prepare("SELECT COUNT(*) AS ucount FROM {$this->tableName}");
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();

        return $row['ucount'];
    }

    /**
     * function is used to get single record based on the column value
     * @param string $fileds
     * @param any $value
     * @return array $results
     */
    public function getRow($field, $value) {
        if ($sql = $this->db->query("SELECT * FROM {$this->tableName}  WHERE {$field}={$value}")) {
            $result = $sql->fetch_array(MYSQLI_ASSOC);
            return $result;
        }
    }

    public function searchPlayer($searchText, $start = 0, $limit = 4) {
        self::$number = $searchText;

        if ($sql = $this->db->query("SELECT * FROM {$this->tableName} WHERE number LIKE '%".self::$number."%' AND deleted=0 ORDER BY id DESC LIMIT {$start},{$limit}")){
            while($row = $sql->fetch_array(MYSQLI_ASSOC)) {
                self::$data[] = $row;
            }
            return self::$data;
        }
    }

    public function processPhone($phone_str) {
		$phone_str=str_replace('00','+',$phone_str);
		$phone_str=str_replace(array(' ','	','-'),array('','',''),trim($phone_str));
		
		$check_number = preg_match('/\+|(00)/', $phone_str, $match);
		$prefix;
		if($check_number == 1) { /********* AND THE ELSE LOOP?********/
			$phone_str = preg_replace('/[^0-9]|(00)/', '', $phone_str);
			$nums = [];
		
			foreach (self::phoneCodes() as $val) {
				$test = preg_match_all('/(^'.$val['code'].')/', $phone_str, $matches);
				if($matches[0]) {
					$nums = $matches[0];
				}
			}
			
			if(count($nums) > 1) {
				$long_code = substr($phone_str, 0, strlen(max($nums)));
				if($check_number_length < 5) {
					foreach ($nums as $val) {
						$prefix = $val;
					}
				} else {
					$prefix = $long_code;
				}
			} else {
				$prefix = $nums[0];
			}
		} else {
			
			$local_code = substr($phone_str, 0, 2);
			if($local_code == '02' || $local_code == '03' || $local_code == '07') {
				$prefix = 40;
			} else {
				$prefix = 49;
			}
			$phone_str = $local_code.$phone_str;
		}
		
		self::$number = substr($phone_str, strlen($prefix), strlen($phone_str));
		self::$prefix = '+'.$prefix;
	}

    public static function getPrefix() {
		return self::$prefix;
	}
	
	public static function getNumber() {
		return self::$number;
	}
	
	public static function getName() {
		return self::$name;
	}
	
	public static function getData() {
		return self::$data;
	}
	
	public static function getPhoneCodes() {
		return self::phoneCodes();
	}
}
