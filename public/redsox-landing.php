<?php
/**
 * Red Sox Landing Page
 * 
 * Modern Squarespace-inspired landing page with Red Sox branding
 */

require_once __DIR__ . '/../app/db.php';

$pageTitle = 'Red Sox Analytics Platform';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MLB Baseball Impact</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Purpose-built baseball statistics and analysis platform featuring Red Sox style design. Explore comprehensive MLB data with clean visualizations and final statistical outcomes.">
    <meta name="keywords" content="MLB, Baseball, Red Sox, Statistics, Analytics, Data Visualization">
    <meta name="author" content="MLB Baseball Impact Team">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="Clean landing, clear data, final outcomes that matter. Explore MLB statistics with Red Sox-inspired design.">
    <meta property="og:site_name" content="MLB Baseball Impact">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Red Sox Theme CSS -->
    <link rel="stylesheet" href="/assets/css/redsox.css">
    
    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "MLB Baseball Impact",
        "url": "<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']); ?>",
        "description": "MLB Baseball Impact Analysis Platform",
        "publisher": {
            "@type": "Organization",
            "name": "MLB Baseball Impact Team"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [{
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']); ?>/redsox-landing.php"
        }]
    }
    </script>
</head>
<body class="redsox-theme">
    <!-- Sticky Navbar -->
    <nav class="rs-navbar">
        <div class="rs-container">
            <div class="rs-navbar-content">
                <a href="/redsox-landing.php" class="rs-navbar-logo">⚾ MLB Impact</a>
                
                <div class="rs-navbar-links">
                    <a href="#tables-explorer" class="rs-navbar-link">Tables</a>
                    <a href="/redsox-analysis.php" class="rs-navbar-link">Analysis</a>
                    <a href="/index.php" class="rs-navbar-link">Classic View</a>
                </div>
                
                <a href="/redsox-analysis.php" class="rs-btn rs-btn-primary rs-btn-sm rs-navbar-cta">
                    Get Started
                </a>
            </div>
        </div>
    </nav>

    <main>
        <!-- Hero Section -->
        <section class="rs-hero">
            <div class="rs-container">
                <div class="rs-hero-content">
                    <h1 class="rs-hero-headline">Purpose-built stats, Red Sox style.</h1>
                    <p class="rs-hero-subtext">
                        Clean landing, clear data, final outcomes that matter. 
                        Explore comprehensive MLB statistics with modern analytics and visualizations.
                    </p>
                    <div class="rs-hero-actions">
                        <a href="/redsox-analysis.php" class="rs-btn rs-btn-primary rs-btn-lg">Get Started</a>
                        <a href="#tables-explorer" class="rs-btn rs-btn-secondary rs-btn-lg">Explore Tables</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tables Explorer Section -->
        <section id="tables-explorer" class="rs-section rs-section-light">
            <div class="rs-container">
                <div class="rs-text-center">
                    <h2 class="rs-section-title">Database Tables Explorer</h2>
                    <p class="rs-section-subtitle" style="margin-left: auto; margin-right: auto;">
                        Explore the structure of our MLB database. Each table contains carefully curated data 
                        ready for analysis. Click any card to preview sample data.
                    </p>
                </div>

                <?php
                // Fetch available tables
                $tables = [];
                $db_connected = Db::isConnected();
                
                if ($db_connected) {
                    try {
                        $pdo = Db::getDb();
                        
                        // Get tables from dw schema
                        $stmt = $pdo->query("
                            SELECT 
                                TABLE_NAME,
                                TABLE_ROWS,
                                UPDATE_TIME
                            FROM information_schema.TABLES 
                            WHERE TABLE_SCHEMA = 'dw'
                            ORDER BY TABLE_NAME
                        ");
                        
                        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Get column info for each table
                        foreach ($tables as &$table) {
                            $stmt = $pdo->prepare("
                                SELECT COLUMN_NAME 
                                FROM information_schema.COLUMNS 
                                WHERE TABLE_SCHEMA = 'dw' 
                                  AND TABLE_NAME = ?
                                ORDER BY ORDINAL_POSITION
                                LIMIT 6
                            ");
                            $stmt->execute([$table['TABLE_NAME']]);
                            $table['columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        }
                    } catch (PDOException $e) {
                        $tables = [];
                    }
                }
                
                if (!empty($tables)) {
                    echo '<div class="rs-tables-grid">';
                    $index = 0;
                    foreach ($tables as $table) {
                        $borderClass = ($index % 2 === 0) ? 'rs-table-card-navy' : 'rs-table-card-red';
                        $tableName = htmlspecialchars($table['TABLE_NAME']);
                        $rowCount = number_format((int)$table['TABLE_ROWS']);
                        $lastUpdated = $table['UPDATE_TIME'] ? date('M j, Y', strtotime($table['UPDATE_TIME'])) : 'N/A';
                        
                        echo '<div class="rs-table-card ' . $borderClass . '">';
                        echo '    <div class="rs-table-name">' . $tableName . '</div>';
                        echo '    <div class="rs-table-meta">';
                        echo '        <span><strong>' . $rowCount . '</strong> rows</span>';
                        echo '        <span>Updated: ' . htmlspecialchars($lastUpdated) . '</span>';
                        echo '    </div>';
                        
                        if (!empty($table['columns'])) {
                            echo '    <div class="rs-table-columns">';
                            foreach (array_slice($table['columns'], 0, 6) as $col) {
                                echo '<span class="rs-table-column-pill">' . htmlspecialchars($col) . '</span>';
                            }
                            echo '    </div>';
                        }
                        
                        echo '    <div class="rs-table-actions">';
                        echo '        <a href="/redsox-table-preview.php?table=' . urlencode($tableName) . '" class="rs-btn rs-btn-ghost rs-btn-sm">Preview →</a>';
                        echo '    </div>';
                        echo '</div>';
                        
                        $index++;
                    }
                    echo '</div>';
                } else {
                    echo '<div class="rs-card" style="text-align: center; margin-top: 2rem;">';
                    echo '    <h3 style="color: var(--redsox-slate); margin-bottom: 1rem;">Database Not Connected</h3>';
                    echo '    <p style="color: var(--redsox-gray);">Please configure your database connection in .env file to view available tables.</p>';
                    if (!$db_connected && Db::getError()) {
                        echo '    <p style="color: var(--redsox-red); margin-top: 1rem; font-size: 0.875rem;">' . htmlspecialchars(Db::getHelp()) . '</p>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        <!-- Feature Section (Alternating) -->
        <section class="rs-section rs-section-dark">
            <div class="rs-container">
                <div class="rs-text-center">
                    <h2 class="rs-section-title">Built for Baseball Analytics</h2>
                    <p class="rs-section-subtitle" style="margin-left: auto; margin-right: auto;">
                        Our platform combines comprehensive MLB data with powerful visualization tools, 
                        giving you the insights you need to understand the game at a deeper level.
                    </p>
                </div>
                
                <div class="rs-kpi-grid">
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value"><?php echo $db_connected && !empty($tables) ? count($tables) : '0'; ?></div>
                        <div class="rs-kpi-label">Data Tables</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value">
                            <?php 
                            if ($db_connected && !empty($tables)) {
                                echo number_format(array_sum(array_column($tables, 'TABLE_ROWS')));
                            } else {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="rs-kpi-label">Total Records</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value">100%</div>
                        <div class="rs-kpi-label">Data Accuracy</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value">24/7</div>
                        <div class="rs-kpi-label">Access</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Band -->
        <section class="rs-cta-band">
            <div class="rs-container">
                <h2 class="rs-cta-band-title">View the Final Analysis →</h2>
                <p class="rs-cta-band-text">
                    Dive into comprehensive statistical outcomes and insights from our complete dataset
                </p>
                <a href="/redsox-analysis.php" class="rs-btn rs-btn-secondary rs-btn-lg" style="background-color: white; color: var(--redsox-red); border-color: white;">
                    Open Analysis Dashboard
                </a>
            </div>
        </section>

        <!-- Additional Info Section -->
        <section class="rs-section rs-section-light">
            <div class="rs-container">
                <div class="rs-text-center">
                    <h2 class="rs-section-title">Why Choose Our Platform?</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-top: 3rem;">
                    <div class="rs-card" style="text-align: center;">
                        <h3 style="color: var(--redsox-navy); margin-bottom: 1rem; font-size: 1.5rem;">🎯 Accurate Data</h3>
                        <p style="color: var(--redsox-gray); line-height: 1.75;">
                            Our database is sourced from official MLB records, ensuring accuracy and reliability for all your analytics needs.
                        </p>
                    </div>
                    
                    <div class="rs-card" style="text-align: center;">
                        <h3 style="color: var(--redsox-navy); margin-bottom: 1rem; font-size: 1.5rem;">📊 Rich Visualizations</h3>
                        <p style="color: var(--redsox-gray); line-height: 1.75;">
                            Transform raw data into meaningful insights with our interactive charts and comprehensive statistical analysis.
                        </p>
                    </div>
                    
                    <div class="rs-card" style="text-align: center;">
                        <h3 style="color: var(--redsox-navy); margin-bottom: 1rem; font-size: 1.5rem;">⚡ Fast Performance</h3>
                        <p style="color: var(--redsox-gray); line-height: 1.75;">
                            Optimized queries and efficient data structures ensure lightning-fast access to millions of records.
                        </p>
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
</body>
</html>
