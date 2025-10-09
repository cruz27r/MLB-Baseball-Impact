<?php
/**
 * CS437 MLB Global Era - Team Composition API
 * 
 * Returns team composition data showing foreign vs domestic player distribution.
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
    $team = isset($_GET['team']) ? $_GET['team'] : null;
    
    // Initialize data handler
    $mlbData = new MLBData();
    
    // Fetch composition data
    $data = $mlbData->getTeamComposition($year, $team);
    
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
        'error' => 'Failed to fetch composition data',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
