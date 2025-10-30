<?php
/**
 * Red Sox Analysis Page - Final Statistical Outcome
 * 
 * Displays comprehensive statistical analysis and final outcomes
 */

require_once __DIR__ . '/../app/db.php';

$pageTitle = 'Final Analysis';

// Fetch final outcome data
$finalOutcomes = [];
$db_connected = Db::isConnected();
$error_message = null;

if ($db_connected) {
    try {
        $pdo = Db::getDb();
        
        // Try to fetch from views or tables
        // First, try v_roster_share
        try {
            $stmt = $pdo->query("
                SELECT year, origin, players, share 
                FROM v_roster_share 
                WHERE year >= 2000
                ORDER BY year DESC, share DESC
                LIMIT 100
            ");
            $finalOutcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If view doesn't exist, try dw_roster_composition
            $stmt = $pdo->query("
                SELECT year, origin, players
                FROM dw_roster_composition
                WHERE year >= 2000
                ORDER BY year DESC, players DESC
                LIMIT 100
            ");
            $finalOutcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching data: " . $e->getMessage();
    }
}

// Calculate summary statistics
$totalRecords = count($finalOutcomes);
$latestYear = !empty($finalOutcomes) ? max(array_column($finalOutcomes, 'year')) : date('Y');
$origins = !empty($finalOutcomes) ? array_unique(array_column($finalOutcomes, 'origin')) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MLB Baseball Impact</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Comprehensive statistical analysis of MLB player demographics and performance metrics. View final outcomes and trends in baseball data.">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?> - MLB Baseball Impact">
    <meta property="og:description" content="Final statistical outcomes and comprehensive analysis of MLB data">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Red Sox Theme CSS -->
    <link rel="stylesheet" href="/assets/css/redsox.css">
    
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                <h1 class="rs-section-title" style="margin-bottom: 0.5rem;">Final Statistical Outcome</h1>
                <p class="rs-section-subtitle" style="margin-bottom: 0;">
                    Comprehensive analysis of MLB roster composition by player origin
                </p>
            </div>
        </section>

        <!-- Executive Summary -->
        <section class="rs-section rs-section-light" style="padding-top: 3rem;">
            <div class="rs-container">
                <div class="rs-card" style="max-width: 900px; margin: 0 auto;">
                    <h2 style="color: var(--redsox-navy); margin-bottom: 1rem; font-size: 1.75rem;">Executive Summary</h2>
                    <p style="color: var(--redsox-gray); line-height: 1.75; margin-bottom: 1rem;">
                        This analysis examines the composition of Major League Baseball rosters from 2000 onwards, 
                        categorizing players by their country of origin. The data reveals trends in international 
                        player participation and their impact on the league.
                    </p>
                    <?php if ($db_connected && !empty($finalOutcomes)): ?>
                        <p style="color: var(--redsox-gray); line-height: 1.75;">
                            Our dataset includes <strong><?php echo number_format($totalRecords); ?> records</strong> 
                            spanning from 2000 to <strong><?php echo htmlspecialchars($latestYear); ?></strong>, 
                            covering <strong><?php echo count($origins); ?> player origin categories</strong>.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- KPI Tiles -->
        <section class="rs-section rs-section-light" style="padding-top: 2rem;">
            <div class="rs-container">
                <div class="rs-kpi-grid">
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value"><?php echo number_format($totalRecords); ?></div>
                        <div class="rs-kpi-label">Data Points</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value"><?php echo htmlspecialchars($latestYear); ?></div>
                        <div class="rs-kpi-label">Latest Year</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value"><?php echo count($origins); ?></div>
                        <div class="rs-kpi-label">Origin Types</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value">
                            <?php 
                            if (!empty($finalOutcomes)) {
                                $years = array_unique(array_column($finalOutcomes, 'year'));
                                echo count($years);
                            } else {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="rs-kpi-label">Years Covered</div>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($db_connected && !empty($finalOutcomes)): ?>
            <!-- Trend Chart -->
            <section class="rs-section rs-section-light" style="padding-top: 2rem;">
                <div class="rs-container">
                    <div class="rs-card" style="max-width: 1100px; margin: 0 auto;">
                        <h2 style="color: var(--redsox-navy); margin-bottom: 1.5rem; font-size: 1.5rem;">Player Roster Trends by Origin</h2>
                        <div style="position: relative; height: 400px;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Data Table -->
            <section class="rs-section rs-section-light" style="padding-top: 2rem;">
                <div class="rs-container">
                    <div class="rs-card" style="max-width: 1100px; margin: 0 auto;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2 style="color: var(--redsox-navy); margin: 0; font-size: 1.5rem;">Detailed Data</h2>
                            <div>
                                <button onclick="exportToCSV()" class="rs-btn rs-btn-ghost rs-btn-sm">Export CSV</button>
                            </div>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--redsox-gray-light);">
                                        <th style="text-align: left; padding: 0.75rem; color: var(--redsox-slate); font-weight: 600;">Year</th>
                                        <th style="text-align: left; padding: 0.75rem; color: var(--redsox-slate); font-weight: 600;">Origin</th>
                                        <th style="text-align: right; padding: 0.75rem; color: var(--redsox-slate); font-weight: 600;">Players</th>
                                        <?php if (isset($finalOutcomes[0]['share'])): ?>
                                        <th style="text-align: right; padding: 0.75rem; color: var(--redsox-slate); font-weight: 600;">Share</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($finalOutcomes, 0, 50) as $row): ?>
                                    <tr style="border-bottom: 1px solid var(--redsox-gray-light);">
                                        <td style="padding: 0.75rem; color: var(--redsox-slate);"><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td style="padding: 0.75rem; color: var(--redsox-slate);">
                                            <span class="rs-badge rs-badge-outline" style="color: var(--redsox-navy);">
                                                <?php echo htmlspecialchars($row['origin']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: right; color: var(--redsox-slate); font-weight: 600;">
                                            <?php echo number_format((int)$row['players']); ?>
                                        </td>
                                        <?php if (isset($row['share'])): ?>
                                        <td style="padding: 0.75rem; text-align: right; color: var(--redsox-slate);">
                                            <?php echo number_format($row['share'] * 100, 2); ?>%
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (count($finalOutcomes) > 50): ?>
                        <p style="margin-top: 1rem; color: var(--redsox-gray); font-size: 0.875rem; text-align: center;">
                            Showing 50 of <?php echo number_format($totalRecords); ?> records
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Empty State -->
            <section class="rs-section rs-section-light" style="padding-top: 2rem;">
                <div class="rs-container">
                    <div class="rs-card" style="max-width: 700px; margin: 0 auto; text-align: center; padding: 3rem;">
                        <h2 style="color: var(--redsox-navy); margin-bottom: 1rem; font-size: 1.75rem;">
                            <?php echo $db_connected ? 'No Data Available' : 'Database Not Connected'; ?>
                        </h2>
                        <p style="color: var(--redsox-gray); line-height: 1.75; margin-bottom: 1.5rem;">
                            <?php 
                            if (!$db_connected) {
                                echo 'Please configure your database connection in .env file to view the analysis.';
                                if (Db::getHelp()) {
                                    echo '<br><br>' . htmlspecialchars(Db::getHelp());
                                }
                            } else {
                                echo 'The required data tables or views are not yet populated. Please run the data loading scripts to populate the database.';
                            }
                            ?>
                        </p>
                        <a href="/redsox-landing.php" class="rs-btn rs-btn-primary">Back to Home</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Methodology Accordion -->
        <section class="rs-section rs-section-light" style="padding-bottom: 4rem;">
            <div class="rs-container">
                <div style="max-width: 1100px; margin: 0 auto;">
                    <h2 style="color: var(--redsox-slate); margin-bottom: 1.5rem; font-size: 1.75rem; text-align: center;">Methodology</h2>
                    
                    <div class="rs-accordion">
                        <div class="rs-accordion-item">
                            <button class="rs-accordion-header" onclick="toggleAccordion(this)">
                                <span>Data Sources</span>
                                <span>▼</span>
                            </button>
                            <div class="rs-accordion-content" style="display: none;">
                                <p>Our analysis is based on official MLB data sourced from:</p>
                                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                                    <li>SABR Lahman Database - Player demographics and statistics</li>
                                    <li>Retrosheet - Play-by-play game data</li>
                                    <li>Baseball-Reference WAR - Wins Above Replacement metrics</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="rs-accordion-item">
                            <button class="rs-accordion-header" onclick="toggleAccordion(this)">
                                <span>Classification Methodology</span>
                                <span>▼</span>
                            </button>
                            <div class="rs-accordion-content" style="display: none;">
                                <p>Players are classified into origin categories based on their birth country:</p>
                                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                                    <li><strong>USA:</strong> Born in the United States</li>
                                    <li><strong>Foreign:</strong> Born outside the United States</li>
                                    <li><strong>Unknown:</strong> Birth country not recorded</li>
                                </ul>
                                <p style="margin-top: 0.5rem;">This classification helps analyze the international composition of MLB rosters over time.</p>
                            </div>
                        </div>
                        
                        <div class="rs-accordion-item">
                            <button class="rs-accordion-header" onclick="toggleAccordion(this)">
                                <span>Statistical Methods</span>
                                <span>▼</span>
                            </button>
                            <div class="rs-accordion-content" style="display: none;">
                                <p>Our analysis uses the following statistical approaches:</p>
                                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                                    <li>Roster share: Percentage of players from each origin category per season</li>
                                    <li>Trend analysis: Year-over-year changes in player composition</li>
                                    <li>Aggregation: Summarized statistics at multiple levels (player, team, league)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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

    <script>
        // Accordion toggle
        function toggleAccordion(button) {
            const content = button.nextElementSibling;
            const arrow = button.querySelector('span:last-child');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                arrow.textContent = '▲';
            } else {
                content.style.display = 'none';
                arrow.textContent = '▼';
            }
        }

        // Export to CSV
        function exportToCSV() {
            const table = document.querySelector('table');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const rowData = Array.from(cols).map(col => {
                    return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
                });
                csv.push(rowData.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'mlb_analysis_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        <?php if (!empty($finalOutcomes)): ?>
        // Prepare chart data
        const chartData = <?php 
            // Group by year and origin for chart
            $chartDataByYear = [];
            foreach ($finalOutcomes as $row) {
                $year = $row['year'];
                $origin = $row['origin'];
                $players = (int)$row['players'];
                
                if (!isset($chartDataByYear[$year])) {
                    $chartDataByYear[$year] = [];
                }
                $chartDataByYear[$year][$origin] = $players;
            }
            
            // Sort by year
            ksort($chartDataByYear);
            
            // Prepare data for Chart.js
            $years = array_keys($chartDataByYear);
            $originsForChart = array_unique(array_column($finalOutcomes, 'origin'));
            $datasets = [];
            
            $colors = [
                'USA' => '#0D2B56',
                'Foreign' => '#BD3039',
                'Unknown' => '#6B7280'
            ];
            
            foreach ($originsForChart as $origin) {
                $data = [];
                foreach ($years as $year) {
                    $data[] = $chartDataByYear[$year][$origin] ?? 0;
                }
                
                $datasets[] = [
                    'label' => $origin,
                    'data' => $data,
                    'borderColor' => $colors[$origin] ?? '#6B7280',
                    'backgroundColor' => ($colors[$origin] ?? '#6B7280') . '33',
                    'tension' => 0.4
                ];
            }
            
            echo json_encode([
                'labels' => $years,
                'datasets' => $datasets
            ]);
        ?>;

        // Create chart
        const ctx = document.getElementById('trendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Players'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Year'
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>
