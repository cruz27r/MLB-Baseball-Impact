<?php
/**
 * Class-Compliant Database Connection
 * 
 * Uses mysqli_connect/mysqli_close as taught in L11.
 * Registers shutdown function to auto-close connection.
 */

// Database configuration
$DB_HOST = getenv('MLB_DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('MLB_DB_USER') ?: 'rafacruz';
$DB_PASS = getenv('MLB_DB_PASS') ?: 'Ricky072701';
$DB_NAME = getenv('MLB_DB_NAME') ?: 'mlb_impact';

// Establish mysqli connection with proper error handling
$dbc = false;
$db_connected = false;

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $dbc = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $db_connected = true;
    
    // Set charset to utf8mb4
    mysqli_set_charset($dbc, 'utf8mb4');
    
    // Register shutdown function to close connection
    register_shutdown_function(function() use ($dbc) {
        if ($dbc && @mysqli_ping($dbc)) {
            mysqli_close($dbc);
        }
    });
} catch (mysqli_sql_exception $e) {
    // Connection failed, but we'll handle it gracefully
    $db_connected = false;
    $dbc = false;
}

/**
 * Helper function to safely escape table/column names
 */
function db_escape_identifier($str) {
    return '`' . str_replace('`', '``', $str) . '`';
}

/**
 * Helper function to check if a table exists
 */
function db_table_exists($dbc, $table) {
    $table_escaped = mysqli_real_escape_string($dbc, $table);
    $result = mysqli_query($dbc, "SHOW TABLES LIKE '$table_escaped'");
    return $result && mysqli_num_rows($result) > 0;
}
