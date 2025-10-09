<?php
/**
 * CS437 MLB Global Era - Awards Index API
 * 
 * Returns awards data for foreign players in MLB.
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
    $awardType = isset($_GET['award_type']) ? $_GET['award_type'] : null;
    
    // Initialize data handler
    $mlbData = new MLBData();
    
    // Fetch awards data
    $data = $mlbData->getAwardsIndex($year, $country, $awardType);
    
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
        'error' => 'Failed to fetch awards data',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
