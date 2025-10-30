<?php
/**
 * API Endpoint: List Tables
 * 
 * Returns list of tables with metadata
 * GET /api/redsox/tables.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../../app/db.php';

// Validate connection
if (!Db::isConnected()) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => Db::getHelp() ?: 'Unable to connect to database'
    ]);
    exit;
}

try {
    $pdo = Db::getDb();
    
    // Get tables from dw schema
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME as name,
            TABLE_ROWS as rowCount,
            UPDATE_TIME as updatedAt,
            CREATE_TIME as createdAt
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'dw'
        ORDER BY TABLE_NAME
    ");
    
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get columns for each table
    foreach ($tables as &$table) {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME, DATA_TYPE
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = 'dw' 
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $stmt->execute([$table['name']]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $table['columns'] = array_map(function($col) {
            return [
                'name' => $col['COLUMN_NAME'],
                'type' => $col['DATA_TYPE']
            ];
        }, $columns);
        
        // Format dates
        $table['rowCount'] = (int)$table['rowCount'];
        $table['updatedAt'] = $table['updatedAt'] ? date('c', strtotime($table['updatedAt'])) : null;
        $table['createdAt'] = $table['createdAt'] ? date('c', strtotime($table['createdAt'])) : null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tables,
        'count' => count($tables)
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database query failed',
        'message' => $e->getMessage()
    ]);
}
