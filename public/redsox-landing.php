<?php
/**
 * Red Sox Landing Page
 * 
 * Modern Squarespace-inspired landing page with Red Sox branding
 * Simplified version with placeholder tables
 */

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
                    <a href="/index.php" class="rs-navbar-link">Classic View</a>
                </div>
                
                <a href="#tables-explorer" class="rs-btn rs-btn-primary rs-btn-sm rs-navbar-cta">
                    Explore Data
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
                        <a href="#tables-explorer" class="rs-btn rs-btn-primary rs-btn-lg">Explore Tables</a>
                        <a href="/index.php" class="rs-btn rs-btn-secondary rs-btn-lg">Classic View</a>
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
                        ready for analysis.
                    </p>
                </div>

                <?php
                // Placeholder tables - hardcoded for display
                $placeholderTables = [
                    [
                        'name' => 'player_demographics',
                        'rowCount' => '20,435',
                        'lastUpdated' => 'Oct 15, 2024',
                        'columns' => ['player_id', 'first_name', 'last_name', 'birth_date', 'birth_country', 'position']
                    ],
                    [
                        'name' => 'batting_statistics',
                        'rowCount' => '156,890',
                        'lastUpdated' => 'Oct 20, 2024',
                        'columns' => ['player_id', 'year', 'team_id', 'games', 'at_bats', 'hits', 'home_runs', 'rbi']
                    ],
                    [
                        'name' => 'pitching_statistics',
                        'rowCount' => '89,234',
                        'lastUpdated' => 'Oct 20, 2024',
                        'columns' => ['player_id', 'year', 'team_id', 'wins', 'losses', 'era', 'strikeouts']
                    ],
                    [
                        'name' => 'team_standings',
                        'rowCount' => '2,850',
                        'lastUpdated' => 'Oct 18, 2024',
                        'columns' => ['team_id', 'year', 'league', 'wins', 'losses', 'division_rank']
                    ],
                    [
                        'name' => 'awards_honors',
                        'rowCount' => '4,567',
                        'lastUpdated' => 'Oct 10, 2024',
                        'columns' => ['player_id', 'award_name', 'year', 'league', 'category']
                    ]
                ];
                
                echo '<div class="rs-tables-grid">';
                $index = 0;
                foreach ($placeholderTables as $table) {
                    $borderClass = ($index % 2 === 0) ? 'rs-table-card-navy' : 'rs-table-card-red';
                    
                    echo '<div class="rs-table-card ' . $borderClass . '">';
                    echo '    <div class="rs-table-name">' . htmlspecialchars($table['name']) . '</div>';
                    echo '    <div class="rs-table-meta">';
                    echo '        <span><strong>' . $table['rowCount'] . '</strong> rows</span>';
                    echo '        <span>Updated: ' . htmlspecialchars($table['lastUpdated']) . '</span>';
                    echo '    </div>';
                    
                    echo '    <div class="rs-table-columns">';
                    foreach (array_slice($table['columns'], 0, 6) as $col) {
                        echo '<span class="rs-table-column-pill">' . htmlspecialchars($col) . '</span>';
                    }
                    echo '    </div>';
                    
                    echo '    <div class="rs-table-actions">';
                    echo '        <button class="rs-btn rs-btn-ghost rs-btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">Coming Soon</button>';
                    echo '    </div>';
                    echo '</div>';
                    
                    $index++;
                }
                echo '</div>';
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
                        <div class="rs-kpi-value">5</div>
                        <div class="rs-kpi-label">Data Tables</div>
                    </div>
                    <div class="rs-kpi-tile">
                        <div class="rs-kpi-value">274K+</div>
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
                <h2 class="rs-cta-band-title">Ready to Explore MLB Data? →</h2>
                <p class="rs-cta-band-text">
                    Access comprehensive baseball statistics and analytics
                </p>
                <a href="/index.php" class="rs-btn rs-btn-secondary rs-btn-lg" style="background-color: white; color: var(--redsox-red); border-color: white;">
                    View Classic Dashboard
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
