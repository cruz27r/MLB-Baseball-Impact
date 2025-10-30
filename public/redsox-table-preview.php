<?php
/**
 * Red Sox Table Preview Page
 * 
 * Displays sample data from a specific table
 */

require_once __DIR__ . '/../app/db.php';

$tableName = $_GET['table'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);
$limit = min(max($limit, 1), 100); // Between 1 and 100

$pageTitle = 'Table Preview: ' . htmlspecialchars($tableName);

// Fetch table data
$tableData = [];
$columns = [];
$totalRows = 0;
$db_connected = Db::isConnected();
$error_message = null;

if ($db_connected && $tableName) {
    try {
        $pdo = Db::getDb();
        
        // Validate table name (security check)
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME, TABLE_ROWS
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = 'dw' 
              AND TABLE_NAME = ?
        ");
        $stmt->execute([$tableName]);
        $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tableInfo) {
            $totalRows = (int)$tableInfo['TABLE_ROWS'];
            
            // Get columns
            $stmt = $pdo->prepare("
                SELECT COLUMN_NAME, DATA_TYPE
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = 'dw' 
                  AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ");
            $stmt->execute([$tableName]);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get sample data - use prepared statement with identifier backticks
            $sql = "SELECT * FROM `dw`.`" . $tableName . "` LIMIT " . (int)$limit;
            $stmt = $pdo->query($sql);
            $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Table not found in database";
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching data: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MLB Baseball Impact</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Red Sox Theme CSS -->
    <link rel="stylesheet" href="/assets/css/redsox.css">
</head>
<body class="redsox-theme">
    <!-- Sticky Navbar -->
    <nav class="rs-navbar">
        <div class="rs-container">
            <div class="rs-navbar-content">
                <a href="/redsox-landing.php" class="rs-navbar-logo">⚾ MLB Impact</a>
                
                <div class="rs-navbar-links">
                    <a href="/redsox-landing.php#tables-explorer" class="rs-navbar-link">Tables</a>
                    <a href="/redsox-analysis.php" class="rs-navbar-link">Analysis</a>
                    <a href="/index.php" class="rs-navbar-link">Classic View</a>
                </div>
                
                <a href="/redsox-landing.php" class="rs-btn rs-btn-secondary rs-btn-sm rs-navbar-cta">
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <main>
        <!-- Page Header -->
        <section class="rs-section rs-section-dark" style="padding-top: 4rem; padding-bottom: 3rem;">
            <div class="rs-container">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                    <a href="/redsox-landing.php#tables-explorer" class="rs-btn rs-btn-ghost" style="color: white; padding: 0.5rem 1rem;">
                        ← Back
                    </a>
                    <h1 class="rs-section-title" style="margin: 0;">
                        <?php echo htmlspecialchars($tableName); ?>
                    </h1>
                </div>
                <p class="rs-section-subtitle" style="margin-bottom: 0;">
                    Preview of table data (showing <?php echo $limit; ?> rows)
                </p>
            </div>
        </section>

        <?php if ($db_connected && !empty($tableData)): ?>
            <!-- Table Info -->
            <section class="rs-section rs-section-light" style="padding-top: 3rem;">
                <div class="rs-container">
                    <div class="rs-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); max-width: 900px; margin: 0 auto;">
                        <div class="rs-kpi-tile">
                            <div class="rs-kpi-value"><?php echo number_format($totalRows); ?></div>
                            <div class="rs-kpi-label">Total Rows</div>
                        </div>
                        <div class="rs-kpi-tile">
                            <div class="rs-kpi-value"><?php echo count($columns); ?></div>
                            <div class="rs-kpi-label">Columns</div>
                        </div>
                        <div class="rs-kpi-tile">
                            <div class="rs-kpi-value"><?php echo count($tableData); ?></div>
                            <div class="rs-kpi-label">Rows Shown</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Schema Info -->
            <section class="rs-section rs-section-light" style="padding-top: 2rem;">
                <div class="rs-container">
                    <div class="rs-card" style="max-width: 1200px; margin: 0 auto;">
                        <h2 style="color: var(--redsox-navy); margin-bottom: 1rem; font-size: 1.5rem;">Table Schema</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php foreach ($columns as $col): ?>
                                <div style="display: inline-flex; flex-direction: column; padding: 0.5rem 1rem; background-color: var(--redsox-off); border-radius: var(--rs-radius-md); border-left: 3px solid var(--redsox-navy);">
                                    <span style="font-weight: 600; color: var(--redsox-slate);"><?php echo htmlspecialchars($col['COLUMN_NAME']); ?></span>
                                    <span style="font-size: 0.75rem; color: var(--redsox-gray);"><?php echo htmlspecialchars($col['DATA_TYPE']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Data Table -->
            <section class="rs-section rs-section-light" style="padding-top: 2rem; padding-bottom: 4rem;">
                <div class="rs-container">
                    <div class="rs-card" style="max-width: 1200px; margin: 0 auto;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                            <h2 style="color: var(--redsox-navy); margin: 0; font-size: 1.5rem;">Sample Data</h2>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?table=<?php echo urlencode($tableName); ?>&limit=10" class="rs-btn rs-btn-ghost rs-btn-sm <?php echo $limit === 10 ? 'rs-btn-primary' : ''; ?>">10</a>
                                <a href="?table=<?php echo urlencode($tableName); ?>&limit=25" class="rs-btn rs-btn-ghost rs-btn-sm <?php echo $limit === 25 ? 'rs-btn-primary' : ''; ?>">25</a>
                                <a href="?table=<?php echo urlencode($tableName); ?>&limit=50" class="rs-btn rs-btn-ghost rs-btn-sm <?php echo $limit === 50 ? 'rs-btn-primary' : ''; ?>">50</a>
                                <a href="?table=<?php echo urlencode($tableName); ?>&limit=100" class="rs-btn rs-btn-ghost rs-btn-sm <?php echo $limit === 100 ? 'rs-btn-primary' : ''; ?>">100</a>
                            </div>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--redsox-navy);">
                                        <?php foreach (array_keys($tableData[0]) as $colName): ?>
                                        <th style="text-align: left; padding: 0.75rem; color: var(--redsox-slate); font-weight: 600; white-space: nowrap;">
                                            <?php echo htmlspecialchars($colName); ?>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableData as $row): ?>
                                    <tr style="border-bottom: 1px solid var(--redsox-gray-light);">
                                        <?php foreach ($row as $value): ?>
                                        <td style="padding: 0.75rem; color: var(--redsox-slate); white-space: nowrap;">
                                            <?php echo $value !== null ? htmlspecialchars($value) : '<span style="color: var(--redsox-gray); font-style: italic;">NULL</span>'; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <p style="margin-top: 1.5rem; color: var(--redsox-gray); font-size: 0.875rem; text-align: center;">
                            Showing <?php echo count($tableData); ?> of <?php echo number_format($totalRows); ?> total rows
                        </p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Error State -->
            <section class="rs-section rs-section-light" style="padding-top: 3rem; padding-bottom: 4rem;">
                <div class="rs-container">
                    <div class="rs-card" style="max-width: 700px; margin: 0 auto; text-align: center; padding: 3rem;">
                        <h2 style="color: var(--redsox-red); margin-bottom: 1rem; font-size: 1.75rem;">
                            <?php echo $error_message ? 'Error' : 'No Data Available'; ?>
                        </h2>
                        <p style="color: var(--redsox-gray); line-height: 1.75; margin-bottom: 1.5rem;">
                            <?php 
                            if ($error_message) {
                                echo htmlspecialchars($error_message);
                            } elseif (!$db_connected) {
                                echo 'Database connection error. Please check your configuration.';
                                if (Db::getHelp()) {
                                    echo '<br><br>' . htmlspecialchars(Db::getHelp());
                                }
                            } elseif (!$tableName) {
                                echo 'No table specified.';
                            } else {
                                echo 'Unable to load table data.';
                            }
                            ?>
                        </p>
                        <a href="/redsox-landing.php#tables-explorer" class="rs-btn rs-btn-primary">Back to Tables</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="rs-footer">
        <div class="rs-container">
            <div class="rs-footer-content">
                <div>
                    <h3 class="rs-footer-title">MLB Baseball Impact</h3>
                    <p style="font-size: 0.875rem; line-height: 1.6;">
                        Comprehensive baseball analytics platform analyzing the impact and influence of 
                        international players on Major League Baseball.
                    </p>
                </div>
                
                <div>
                    <h3 class="rs-footer-title">Quick Links</h3>
                    <ul class="rs-footer-links">
                        <li><a href="/redsox-landing.php" class="rs-footer-link">Home</a></li>
                        <li><a href="/redsox-analysis.php" class="rs-footer-link">Final Analysis</a></li>
                        <li><a href="/index.php" class="rs-footer-link">Classic View</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="rs-footer-title">Resources</h3>
                    <ul class="rs-footer-links">
                        <li><a href="/players.php" class="rs-footer-link">Players</a></li>
                        <li><a href="/performance.php" class="rs-footer-link">Performance</a></li>
                        <li><a href="/awards.php" class="rs-footer-link">Awards</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="rs-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> MLB Baseball Impact. CS437 Project. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
