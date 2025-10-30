<?php
/**
 * API Endpoint: Final Outcome Data
 * 
 * Returns final statistical outcome data for analysis page
 * GET /api/redsox/final-outcome.php?limit=1000
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../../app/db.php';

// Input validation
$limit = (int)($_GET['limit'] ?? 1000);
$limit = min(max($limit, 1), 10000); // Between 1 and 10000

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
    
    // Try to fetch from v_roster_share view first (preferred)
    $rows = [];
    $source = null;
    
    try {
        $stmt = $pdo->query("
            SELECT year, origin, players, share 
            FROM v_roster_share 
            WHERE year >= 1900
            ORDER BY year DESC, share DESC
            LIMIT " . $limit
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $source = 'v_roster_share';
    } catch (PDOException $e) {
        // Fallback to dw_roster_composition table
        try {
            $stmt = $pdo->query("
                SELECT year, origin, players
                FROM dw_roster_composition
                WHERE year >= 1900
                ORDER BY year DESC, players DESC
                LIMIT " . $limit
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $source = 'dw_roster_composition';
        } catch (PDOException $e2) {
            // No data available
            http_response_code(404);
            echo json_encode([
                'error' => 'Data not available',
                'message' => 'Neither v_roster_share view nor dw_roster_composition table exists',
                'details' => $e2->getMessage()
            ]);
            exit;
        }
    }
    
    // Calculate summary statistics
    $summary = [
        'totalRecords' => count($rows),
        'years' => count(array_unique(array_column($rows, 'year'))),
        'origins' => array_unique(array_column($rows, 'origin')),
        'latestYear' => !empty($rows) ? max(array_column($rows, 'year')) : null,
        'earliestYear' => !empty($rows) ? min(array_column($rows, 'year')) : null
    ];
    
    // Format numeric values
    foreach ($rows as &$row) {
        $row['year'] = (int)$row['year'];
        $row['players'] = (int)$row['players'];
        if (isset($row['share'])) {
            $row['share'] = (float)$row['share'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'source' => $source,
        'limit' => $limit,
        'summary' => $summary,
        'data' => $rows
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database query failed',
        'message' => $e->getMessage()
    ]);
}
