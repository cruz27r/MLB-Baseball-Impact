<?php
/**
 * API Endpoint: Table Sample Data
 * 
 * Returns sample rows from a specific table
 * GET /api/redsox/table-sample.php?table=dw_player_origin&limit=10
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../../app/db.php';

// Input validation
$tableName = $_GET['table'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);
$limit = min(max($limit, 1), 100); // Between 1 and 100

// Validate table name (only allow alphanumeric and underscore)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid table name',
        'message' => 'Table name must be alphanumeric'
    ]);
    exit;
}

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
    
    // Verify table exists in dw schema
    $stmt = $pdo->prepare("
        SELECT TABLE_NAME, TABLE_ROWS
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'dw' 
          AND TABLE_NAME = ?
    ");
    $stmt->execute([$tableName]);
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tableInfo) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Table not found',
            'message' => 'The specified table does not exist in the dw schema'
        ]);
        exit;
    }
    
    // Get sample data
    $sql = "SELECT * FROM `dw`.`" . $tableName . "` LIMIT " . $limit;
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'table' => $tableName,
        'totalRows' => (int)$tableInfo['TABLE_ROWS'],
        'limit' => $limit,
        'count' => count($rows),
        'data' => $rows
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database query failed',
        'message' => $e->getMessage()
    ]);
}
