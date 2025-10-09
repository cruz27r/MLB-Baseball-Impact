<?php
/**
 * CS437 MLB Global Era - Database Connection
 * 
 * Provides database connection functionality using PDO.
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host;
    private $database;
    private $username;
    private $password;
    private $port;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Load configuration from environment variables or use defaults
        // Support both MLB_DB_* and DB_* prefixes for backward compatibility
        $this->host = getenv('MLB_DB_HOST') ?: getenv('DB_HOST') ?: 'localhost';
        $this->database = getenv('MLB_DB_NAME') ?: getenv('DB_NAME') ?: 'mlb_global_era';
        $this->username = getenv('MLB_DB_USER') ?: getenv('DB_USER') ?: 'postgres';
        $this->password = getenv('MLB_DB_PASSWORD') ?: getenv('DB_PASSWORD') ?: '';
        $this->port = getenv('MLB_DB_PORT') ?: getenv('DB_PORT') ?: '5432';
        
        $this->connect();
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            // Log error and throw exception
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw new Exception("Query execution failed.");
        }
    }
    
    /**
     * Fetch all rows from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single row from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
