<?php
/**
 * CS437 MLB Global Era - Helper Functions
 * 
 * Formatting helpers and safe query wrapper for MySQL operations.
 */

require_once __DIR__ . '/Db.php';

/**
 * Safe query execution that handles missing tables gracefully
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters for prepared statement
 * @return array [rows, error] - rows on success, null on error with error message
 */
function safeQuery($sql, $params = []) {
    if (!Db::isConnected()) {
        return [null, 'Database not connected. Check .env configuration.'];
    }

    try {
        $pdo = Db::getDb();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return [$rows, null];
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        
        // Check for common errors
        if (strpos($errorMsg, "doesn't exist") !== false) {
            return [null, 'Table not loaded yet. Run data loading scripts.'];
        } elseif (strpos($errorMsg, 'Unknown column') !== false) {
            return [null, 'Column not found. Data schema may need updating.'];
        } else {
            return [null, 'Query error: ' . $errorMsg];
        }
    }
}

/**
 * Format a number as percentage
 * 
 * @param float $value Value to format (0.0 to 1.0)
 * @param int $decimals Number of decimal places
 * @return string Formatted percentage
 */
function formatPercent($value, $decimals = 1) {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return number_format($value * 100, $decimals) . '%';
}

/**
 * Format an integer with thousands separator
 * 
 * @param mixed $value Value to format
 * @return string Formatted integer
 */
function formatInt($value) {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return number_format((int)$value, 0);
}

/**
 * Format a decimal number
 * 
 * @param float $value Value to format
 * @param int $decimals Number of decimal places
 * @return string Formatted number
 */
function formatDecimal($value, $decimals = 2) {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return number_format((float)$value, $decimals);
}

/**
 * Format money value
 * 
 * @param float $value Value to format
 * @return string Formatted money string
 */
function formatMoney($value) {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return '$' . number_format((float)$value, 0);
}

/**
 * Escape HTML to prevent XSS
 * 
 * @param string $value Value to escape
 * @return string Escaped value
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if a table exists in the database
 * 
 * @param string $tableName Name of the table to check
 * @return bool True if table exists
 */
function tableExists($tableName) {
    if (!Db::isConnected()) {
        return false;
    }

    try {
        $pdo = Db::getDb();
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get data status for a list of tables
 * 
 * @param array $tables Array of table names to check
 * @return array Array with 'ready' and 'missing' keys
 */
function getDataStatus($tables) {
    $ready = [];
    $missing = [];

    foreach ($tables as $table) {
        if (tableExists($table)) {
            $ready[] = $table;
        } else {
            $missing[] = $table;
        }
    }

    return [
        'ready' => $ready,
        'missing' => $missing,
        'allReady' => count($missing) === 0
    ];
}
