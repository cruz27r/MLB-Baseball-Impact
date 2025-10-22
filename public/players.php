<?php
/**
 * Fenway Modern - Players Page
 * 
 * Roster composition by origin over time.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Player Composition';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';

// Get filter parameters
$selectedDecade = $_GET['decade'] ?? 'all';

// Check database connection
if (!Db::isConnected()) {
    $dbError = Db::getError();
    $dbHelp = Db::getHelp();
}
?>

<main id="main-content">
    <?php renderSectionHero([
        'title' => 'Roster Composition & Origins',
        'subtitle' => 'Tracking the geographic evolution of Major League Baseball rosters',
        'background' => 'gradient'
    ]); ?>

    <div class="container">
        <?php if (!Db::isConnected()): ?>
        <div class="alert alert-error">
            <h3>Database Connection Error</h3>
            <p><?php echo htmlspecialchars($dbError); ?></p>
            <?php if ($dbHelp): ?>
            <p><?php echo htmlspecialchars($dbHelp); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Introduction -->
        <div class="card">
            <h2>Understanding Player Origins</h2>
            <p>
                This section analyzes how the geographic composition of Major League Baseball has evolved 
                since its inception. We track players by their birth country to understand the increasing 
                globalization of the sport.
            </p>
        </div>

        <!-- Data Visualization Placeholders -->
        <div class="card-grid card-grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>Roster Share by Origin</h3>
                </div>
                <?php 
                include __DIR__ . '/components/empty-state.php';
                renderEmptyState([
                    'icon' => '🌍',
                    'title' => 'Chart Placeholder',
                    'message' => 'CSV table showing roster percentages by country will be displayed here.',
                    'hint' => 'Connect to analysis/out/ directory for live data.'
                ]); 
                ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Geographic Distribution Map</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '🗺️',
                    'title' => 'Map Placeholder',
                    'message' => 'Choropleth map showing player origins will be rendered here.',
                    'hint' => 'PNG output from analysis pipeline.'
                ]); ?>
            </div>
        </div>

        <!-- Country Leaderboard -->
        <div class="card">
            <div class="card-header">
                <h2>Players by Birth Country</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                $whereClause = '';
                if ($selectedDecade !== 'all') {
                    $startYear = (int)$selectedDecade;
                    $endYear = $startYear + 9;
                    $whereClause = "WHERE YEAR(debut) BETWEEN $startYear AND $endYear";
                }
                
                list($rows, $error) = safeQuery("
                    SELECT 
                        COALESCE(NULLIF(TRIM(birth_country),''), 'Unknown') AS birth_country,
                        COUNT(*) AS player_count
                    FROM staging_people
                    $whereClause
                    GROUP BY birth_country
                    ORDER BY player_count DESC
                    LIMIT 15
                ");
                
                if ($error) {
                    renderEmptyState([
                        'icon' => '📊',
                        'title' => 'Data Not Available',
                        'message' => $error,
                        'hint' => 'Load the Lahman database to see live statistics.'
                    ]);
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table>";
                    echo "<thead><tr>";
                    echo "<th>Rank</th><th>Country</th><th>Players</th><th>Percentage</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    $total = array_sum(array_column($rows, 'player_count'));
                    foreach ($rows as $index => $row) {
                        $percentage = formatPercent($row['player_count'] / $total);
                        echo "<tr>";
                        echo "<td>" . ($index + 1) . "</td>";
                        echo "<td>" . e($row['birth_country']) . "</td>";
                        echo "<td>" . formatInt($row['player_count']) . "</td>";
                        echo "<td>" . $percentage . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    renderEmptyState([
                        'icon' => '📊',
                        'title' => 'No Data Available',
                        'message' => 'Load the Lahman database to see player statistics.',
                        'hint' => 'Run: ./scripts/load_mysql.sh'
                    ]);
                }
            } else {
                renderEmptyState([
                    'icon' => '⚠️',
                    'title' => 'Database Not Connected',
                    'message' => 'Configure your database connection to see live data.',
                    'hint' => 'Check your .env file and ensure MySQL is running.'
                ]);
            }
            ?>
        </div>

        <!-- Methodology -->
        <div class="card">
            <div class="card-header">
                <h3>Methodology</h3>
            </div>
            <p>
                Players are classified by their birth country as recorded in the SABR Lahman Database. 
                This analysis tracks the evolution of MLB's international composition from the league's 
                inception to the present day.
            </p>
            <p style="margin-top: var(--space-md);">
                <strong>Data Source:</strong> SABR Lahman Baseball Database • <strong>Last Updated:</strong> <?php echo date('Y'); ?>
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
