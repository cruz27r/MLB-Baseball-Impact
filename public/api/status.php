<?php
/**
 * CS437 MLB Global Era - Database Status API
 * 
 * Returns database setup status and schema information.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once __DIR__ . '/../../app/db.php';

try {
    $db = Database::getInstance();
    
    // Get schemas
    $schemas = $db->fetchAll("
        SELECT schema_name 
        FROM information_schema.schemata 
        WHERE schema_name IN ('core', 'lahman', 'bref', 'retrosheet') 
        ORDER BY schema_name
    ");
    
    // Get core tables count
    $tables = $db->fetchAll("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'core' 
        AND table_type = 'BASE TABLE'
        ORDER BY table_name
    ");
    
    // Get materialized views
    $matviews = $db->fetchAll("
        SELECT matviewname 
        FROM pg_matviews 
        WHERE schemaname = 'core' 
        ORDER BY matviewname
    ");
    
    // Get regular views
    $views = $db->fetchAll("
        SELECT table_name 
        FROM information_schema.views 
        WHERE table_schema = 'core' 
        ORDER BY table_name
    ");
    
    // Get latin countries count
    $countries = $db->fetchOne("SELECT COUNT(*) as count FROM core.latin_countries");
    
    // Get people count (will be 0 until data is loaded)
    $people = $db->fetchOne("SELECT COUNT(*) as count FROM core.people");
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'database' => [
            'name' => 'mlb',
            'status' => 'connected',
            'people_count' => (int)$people['count']
        ],
        'schemas' => array_column($schemas, 'schema_name'),
        'tables' => array_column($tables, 'table_name'),
        'views' => array_column($views, 'table_name'),
        'materialized_views' => array_column($matviews, 'matviewname'),
        'reference_data' => [
            'latin_countries' => (int)$countries['count']
        ],
        'message' => $people['count'] > 0 
            ? 'Database is set up and loaded with data' 
            : 'Database is set up. Load data with: ./scripts/refresh_db.sh mlb',
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch database status',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>
