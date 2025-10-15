<?php
/**
 * CS437 MLB Global Era - Impact Index API
 * 
 * Returns Impact Index data showing relative contribution to WAR 
 * compared to roster share for each player origin group.
 * 
 * Impact Index = (WAR Share) / (Roster Share)
 * - Value > 1: Group contributes more WAR than their roster representation
 * - Value < 1: Group contributes less WAR than their roster representation
 * 
 * Endpoints:
 *   /api/impact_index.php              - Get all Impact Index data
 *   /api/impact_index.php?year=2020    - Filter by specific year
 *   /api/impact_index.php?origin=Latin - Filter by origin (USA, Latin, Other)
 *   /api/impact_index.php?limit=10     - Limit number of results
 * 
 * Response format:
 *   {
 *     "success": true,
 *     "data": [
 *       {
 *         "year": 2020,
 *         "origin": "Latin",
 *         "roster_share": 0.28,
 *         "war_share": 0.32,
 *         "impact_index": 1.14,
 *         "roster_percentage": 28.0,
 *         "war_percentage": 32.0
 *       },
 *       ...
 *     ],
 *     "count": 10
 *   }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../app/db.php';

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Build query
    $query = "SELECT 
                year,
                origin,
                roster_share,
                war_share,
                impact_index,
                roster_percentage,
                war_percentage
              FROM core.mv_impact_index
              WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if (isset($_GET['year']) && is_numeric($_GET['year'])) {
        $query .= " AND year = :year";
        $params[':year'] = (int)$_GET['year'];
    }
    
    if (isset($_GET['origin']) && in_array($_GET['origin'], ['USA', 'Latin', 'Other'])) {
        $query .= " AND origin = :origin";
        $params[':origin'] = $_GET['origin'];
    }
    
    // Apply ordering
    $query .= " ORDER BY year DESC, origin";
    
    // Apply limit
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = min((int)$_GET['limit'], 1000);  // Max 1000 records
        $query .= " LIMIT :limit";
        $params[':limit'] = $limit;
    }
    
    // Execute query
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else if ($key === ':year') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format numeric fields
    foreach ($results as &$row) {
        $row['year'] = (int)$row['year'];
        $row['roster_share'] = $row['roster_share'] !== null ? (float)$row['roster_share'] : null;
        $row['war_share'] = $row['war_share'] !== null ? (float)$row['war_share'] : null;
        $row['impact_index'] = $row['impact_index'] !== null ? (float)$row['impact_index'] : null;
        $row['roster_percentage'] = $row['roster_percentage'] !== null ? (float)$row['roster_percentage'] : null;
        $row['war_percentage'] = $row['war_percentage'] !== null ? (float)$row['war_percentage'] : null;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results),
        'filters' => [
            'year' => isset($_GET['year']) ? (int)$_GET['year'] : null,
            'origin' => isset($_GET['origin']) ? $_GET['origin'] : null,
            'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : null
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
