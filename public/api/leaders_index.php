<?php
/**
 * CS437 MLB Global Era - Leaders Index API
 * 
 * Returns statistical leaders data filtered by various criteria.
 */

header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/MLBData.php';

try {
    // Get query parameters
    $year = isset($_GET['year']) ? intval($_GET['year']) : null;
    $country = isset($_GET['country']) ? $_GET['country'] : null;
    $category = isset($_GET['category']) ? $_GET['category'] : 'batting';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    // Initialize data handler
    $mlbData = new MLBData();
    
    // Fetch leaders data
    $data = $mlbData->getLeadersIndex($year, $country, $category, $limit);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch leaders data',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
