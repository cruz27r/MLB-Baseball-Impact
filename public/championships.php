<?php
/**
 * CS437 MLB Global Era - Championships Page
 * 
 * World Series championship teams and contributor breakdowns by origin.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Championships';
include __DIR__ . '/partials/header.php';

// Get filter parameters
$selectedYear = $_GET['year'] ?? 'all';
?>

<main id="main-content">
    <div class="page-title">
        <div class="container">
            <h1>Championship Analysis</h1>
            <p class="page-subtitle">World Series teams and international contributor analysis</p>
        </div>
    </div>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>Global Impact on Championship Teams</h2>
            <p>
                This section analyzes the composition of World Series championship teams to understand 
                how international players have contributed to baseball's ultimate achievement.
            </p>
        </div>

        <!-- Filters -->
        <div class="filters ticket">
            <h3>Filter by Era</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="year">Time Period:</label>
                        <select id="year" name="year">
                            <option value="all" <?php echo $selectedYear === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="2020s" <?php echo $selectedYear === '2020s' ? 'selected' : ''; ?>>2020s</option>
                            <option value="2010s" <?php echo $selectedYear === '2010s' ? 'selected' : ''; ?>>2010s</option>
                            <option value="2000s" <?php echo $selectedYear === '2000s' ? 'selected' : ''; ?>>2000s</option>
                            <option value="1990s" <?php echo $selectedYear === '1990s' ? 'selected' : ''; ?>>1990s</option>
                            <option value="1980s" <?php echo $selectedYear === '1980s' ? 'selected' : ''; ?>>1980s</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </form>
        </div>

        <!-- World Series Winners -->
        <div class="card championship-card">
            <div class="card-header">
                <h2>🏆 World Series Champions</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                // Determine year filter
                $whereClause = '';
                if ($selectedYear !== 'all') {
                    switch($selectedYear) {
                        case '2020s': $whereClause = "WHERE t.year_id >= 2020"; break;
                        case '2010s': $whereClause = "WHERE t.year_id BETWEEN 2010 AND 2019"; break;
                        case '2000s': $whereClause = "WHERE t.year_id BETWEEN 2000 AND 2009"; break;
                        case '1990s': $whereClause = "WHERE t.year_id BETWEEN 1990 AND 1999"; break;
                        case '1980s': $whereClause = "WHERE t.year_id BETWEEN 1980 AND 1989"; break;
                    }
                }
                
                list($rows, $error) = safeQuery("
                    SELECT 
                        t.year_id,
                        t.name AS team_name,
                        t.lg_id AS league
                    FROM staging_teams t
                    $whereClause
                    AND t.ws_win = 'Y'
                    ORDER BY t.year_id DESC
                    LIMIT 50
                ");
                
                if ($error) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Data Not Available:</strong> {$error}";
                    echo "</div>";
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table>";
                    echo "<thead><tr>";
                    echo "<th>Year</th><th>Team</th><th>League</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($rows as $row) {
                        echo "<tr>";
                        echo "<td>" . e($row['year_id']) . "</td>";
                        echo "<td>" . e($row['team_name']) . "</td>";
                        echo "<td>" . e($row['league']) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    echo "<p>No championship data available. Please load the Lahman database.</p>";
                }
            } else {
                echo "<div class='alert alert-warning'>";
                echo "<p><strong>Database not connected.</strong> Configure your .env file.</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Roster Composition Analysis (Placeholder) -->
        <div class="card card-scoreboard">
            <h2>📊 Championship Roster Analysis</h2>
            <p style="color: var(--text-secondary);">
                To analyze the roster composition of championship teams, we need to join team/year data 
                with player appearances. This requires building a view that combines:
            </p>
            <ul style="color: var(--text-secondary); line-height: 2;">
                <li><strong>staging_teams</strong> - Championship winners (ws_win = 'Y')</li>
                <li><strong>staging_appearances</strong> - Player-team-year relationships</li>
                <li><strong>staging_people</strong> - Player birth country</li>
                <li><strong>staging_batting/pitching</strong> - Performance contributions</li>
            </ul>
            
            <div class="alert alert-info" style="margin-top: 1rem;">
                <strong>Coming Soon:</strong> A materialized view will pre-aggregate this data for 
                fast querying of championship roster compositions by origin.
            </div>
        </div>

        <!-- Sample Query Structure -->
        <div class="card">
            <div class="card-header">
                <h3>Expected Analysis Structure</h3>
            </div>
            
            <p>Once the data warehouse views are built, this page will show:</p>
            
            <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4>For Each Championship Team:</h4>
                <ul style="line-height: 2;">
                    <li>Total roster size</li>
                    <li>Number of foreign-born players</li>
                    <li>Percentage of roster from each country</li>
                    <li>Key contributors (by WAR or games played)</li>
                    <li>Position breakdown by origin</li>
                </ul>
            </div>

            <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4>Aggregate Statistics:</h4>
                <ul style="line-height: 2;">
                    <li>Average foreign player percentage on championship teams by decade</li>
                    <li>Countries most represented on championship teams</li>
                    <li>Trend analysis showing increasing international presence</li>
                    <li>Comparison of championship vs. non-championship team compositions</li>
                </ul>
            </div>

            <pre style="background: #2a2a2a; color: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.9rem;">
-- Sample Query Structure (to be implemented in views)
SELECT 
    t.year_id,
    t.name AS team_name,
    COUNT(DISTINCT a.player_id) AS total_players,
    SUM(CASE WHEN p.birth_country != 'USA' THEN 1 ELSE 0 END) AS foreign_players,
    ROUND(100.0 * SUM(CASE WHEN p.birth_country != 'USA' THEN 1 ELSE 0 END) / 
          COUNT(DISTINCT a.player_id), 1) AS foreign_pct
FROM staging_teams t
JOIN staging_appearances a ON t.team_id = a.team_id AND t.year_id = a.year_id
JOIN staging_people p ON a.player_id = p.player_id
WHERE t.ws_win = 'Y'
GROUP BY t.year_id, t.name
ORDER BY t.year_id DESC;
            </pre>
        </div>

        <!-- Countries on Championship Teams (Sample Data) -->
        <div class="card">
            <div class="card-header">
                <h3>Top Countries on Championship Teams (Sample)</h3>
            </div>
            <div class="chart-placeholder" 
                 data-values='[892, 234, 156, 98, 76]'
                 data-labels='USA,D.R.,Venezuela,Cuba,Mexico'>
                📊 Chart: Player contributions to championships by country
            </div>
            <p style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                <em>Note: Sample data shown. Will update with actual championship roster data once views are built.</em>
            </p>
        </div>

        <!-- Notable International Champions -->
        <div class="card">
            <div class="card-header">
                <h2>🌍 International Impact Stories</h2>
            </div>
            <p>
                While we wait for the full roster data to be linked, here are some notable examples 
                of international players who have been key contributors to championship teams:
            </p>
            <ul style="line-height: 2; margin-top: 1rem;">
                <li><strong>Roberto Clemente</strong> (Puerto Rico) - Pittsburgh Pirates (1960, 1971)</li>
                <li><strong>Mariano Rivera</strong> (Panama) - New York Yankees (5 championships)</li>
                <li><strong>David Ortiz</strong> (Dominican Republic) - Boston Red Sox (3 championships)</li>
                <li><strong>Johan Santana</strong> (Venezuela) - Key contributor to multiple playoff runs</li>
                <li><strong>Shohei Ohtani</strong> (Japan) - Recent championship impact</li>
            </ul>
        </div>

        <!-- Data Status -->
        <?php
        $requiredTables = [
            'staging_teams',
            'staging_appearances', 
            'staging_people',
            'dw_championship_rosters'
        ];
        $status = getDataStatus($requiredTables);
        ?>
        
        <div class="card">
            <h3>Required Data Tables</h3>
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.5rem;">
                <?php foreach ($requiredTables as $table): ?>
                    <?php
                    $exists = in_array($table, $status['ready']);
                    $statusClass = $exists ? 'ready' : 'pending';
                    $statusText = $exists ? 'Ready' : 'Pending';
                    ?>
                    <div style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                        <?php echo e($table); ?>
                    </div>
                    <div style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                        <span class="data-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!in_array('staging_appearances', $status['ready'])): ?>
            <div class="alert alert-info" style="margin-top: 1rem;">
                <strong>Note:</strong> The <code>staging_appearances</code> table links players to 
                teams and years. Once loaded, we can build detailed championship roster analyses.
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
