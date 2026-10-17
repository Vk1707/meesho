<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', '1');
ini_set('error_log', 'error.log');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

class Database {

    // Local server
    private $host = 'localhost';
    private $db_name = 'meesho_orders';
    private $username = 'root';
    private $password = '';

    private $conn;

    // Constructor - Establishes database connection
    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Function to execute a query
    public function executeQuery($query, $params = []) {
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    // Function to insert data into the database
   public function insert($table, $data) {
    try {
        $columns = implode(", ", array_keys($data));
        $values = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        $stmt = $this->conn->prepare($sql);

        $stmt->execute($data);
        return true; // Return true if insertion was successful
    } catch(PDOException $e) {
            // You can handle the error in different ways, such as logging it or throwing an exception
            error_log("Error inserting data: " . $e->getMessage());
        echo $e->getMessage();
            return false; // Return false if insertion failed
        }
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

     public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    // Function to select data from the database
    public function select($query, $params = []) {
        try 
        {
            $stmt = $this->executeQuery($query, $params);
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
             error_log('Database select Error: ' . $e->getMessage());
             throw new Exception('An error occurred while fetching data.');
            // echo $query;
             return [];
        }
    }

    // Function to select data from the database
    public function select_single($query, $params = []) {
        try {
            $stmt = $this->executeQuery($query, $params);
            return $stmt->fetch();
       }
        catch (PDOException $e) {
            error_log('Database select_single Error: ' . $e->getMessage());
            throw new Exception('An error occurred while fetching data.' . $e->getMessage());
            return [];
        } 
    }

    // Function to update data in the database
    public function update($table, $data, $where, $whereParams = []) 
    {
        try {  
            $set = "";
            foreach ($data as $key => $value) {
                $set .= "$key = :$key, ";
            }
            $set = rtrim($set, ', ');

            $query = "UPDATE $table SET $set WHERE $where";
            $params = array_merge($data, $whereParams); // Merge data and whereParams arrays

            $this->executeQuery($query, $params);
            return true;
        } 
        catch(PDOException $e) 
        {
            error_log("Error updating data: " . $e->getMessage());
            return false; // Return false if insertion failed
        }
    }


    // Function to delete data from the database
    public function delete($table, $where, $params = []) 
    {
        try{
            $query = "DELETE FROM $table WHERE $where";
            $this->executeQuery($query, $params);
            return true;
         }
         catch(PDOException $e) 
         {
             error_log("Error updating data: " . $e->getMessage());
             return false; // Return false 
             
         }
    }

    // Start a transaction
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }

    // Commit a transaction
    public function commit() {
        $this->conn->commit();
    }

    // Rollback a transaction
    public function rollBack() {
        $this->conn->rollBack();
    }

    // Destructor - Closes database connection automatically
    public function __destruct() {
        $this->conn = null;
    }

}

function gotopage($url) {
    echo "<script language=\"javascript\">";
    echo "window.location = '".$url."'; \n";
    echo "</script>";
}
function dispMessage($msg,$url) {
    echo "<script language=\"javascript\">";
    echo "alert('".$msg."'); \n";
    echo "window.location = '".$url."'; \n";
    echo "</script>";
}

$arrUserType=array(1 =>"Master Admin","Admin","User",);