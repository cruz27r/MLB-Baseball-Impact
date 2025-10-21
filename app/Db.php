<?php
/**
 * CS437 MLB Global Era - Database Connection Class
 * 
 * PDO MySQL connector using environment variables with proper error handling.
 */

class Db {
    private static $instance = null;
    private $pdo = null;

    private function __construct() {
        $this->loadEnv();
        $this->connect();
    }

    /**
     * Get singleton database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public static function getDb() {
        return self::getInstance()->pdo;
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        
        // Set defaults
        $_ENV['MLB_DB_HOST'] = $_ENV['MLB_DB_HOST'] ?? 'localhost';
        $_ENV['MLB_DB_PORT'] = $_ENV['MLB_DB_PORT'] ?? '3306';
        $_ENV['MLB_DB_NAME'] = $_ENV['MLB_DB_NAME'] ?? 'mlb';
        $_ENV['MLB_DB_USER'] = $_ENV['MLB_DB_USER'] ?? 'mlbuser';
        $_ENV['MLB_DB_PASS'] = $_ENV['MLB_DB_PASS'] ?? 'mlbpass';

        if (!file_exists($envFile)) {
            return; // Use defaults if no .env file
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Establish PDO connection to MySQL
     */
    private function connect() {
        $host = $_ENV['MLB_DB_HOST'];
        $port = $_ENV['MLB_DB_PORT'];
        $dbname = $_ENV['MLB_DB_NAME'];
        $user = $_ENV['MLB_DB_USER'];
        $pass = $_ENV['MLB_DB_PASS'];

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }

    /**
     * Handle connection errors with helpful messages
     */
    private function handleConnectionError($e) {
        $errorMsg = $e->getMessage();
        $helpText = '';

        if (strpos($errorMsg, 'Access denied') !== false) {
            $helpText = 'Check your database credentials in .env file. ';
            $helpText .= 'Copy .env.example to .env and update MLB_DB_USER and MLB_DB_PASS.';
        } elseif (strpos($errorMsg, 'Unknown database') !== false) {
            $helpText = 'Database does not exist. Create it with: ';
            $helpText .= 'mysql -e "CREATE DATABASE mlb;"';
        } elseif (strpos($errorMsg, "Can't connect") !== false) {
            $helpText = 'Cannot connect to MySQL server. ';
            $helpText .= 'Check that MySQL is running and MLB_DB_HOST/MLB_DB_PORT are correct.';
        }

        $this->pdo = null;
        
        // Store error for display
        $_ENV['DB_ERROR'] = $errorMsg;
        $_ENV['DB_HELP'] = $helpText;
    }

    /**
     * Check if database is connected
     */
    public static function isConnected() {
        $instance = self::getInstance();
        return $instance->pdo !== null;
    }

    /**
     * Get connection error message
     */
    public static function getError() {
        return $_ENV['DB_ERROR'] ?? null;
    }

    /**
     * Get help text for connection error
     */
    public static function getHelp() {
        return $_ENV['DB_HELP'] ?? null;
    }
}
