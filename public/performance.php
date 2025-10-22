<?php
/**
 * CS437 MLB Global Era - Performance Page
 * 
 * WAR and performance metrics by origin.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Performance Analysis';
include __DIR__ . '/partials/header.php';

// Get filter parameters
$startYear = $_GET['start_year'] ?? 2015;
$endYear = $_GET['end_year'] ?? 2024;
?>

<main id="main-content">
    <div class="page-title">
        <div class="container">
            <h1>Performance Analysis</h1>
            <p class="page-subtitle">WAR contributions and statistical excellence by player origin</p>
        </div>
    </div>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>Measuring On-Field Impact</h2>
            <p>
                This section analyzes player performance using Wins Above Replacement (WAR) and key statistical 
                indicators. We compare contributions across different player origins to understand the relative 
                impact of international talent.
            </p>
            <div class="alert alert-info">
                <strong>Note:</strong> Until the data warehouse WAR views are fully built, we're showing 
                traditional rate statistics as performance proxies (Home Runs, Strikeouts, OPS components for hitters; 
                ERA, SO for pitchers).
            </div>
        </div>

        <!-- Filters -->
        <div class="filters ticket">
            <h3>Year Range</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="start_year">Start Year:</label>
                        <input type="number" id="start_year" name="start_year" 
                               value="<?php echo htmlspecialchars($startYear); ?>" 
                               min="1871" max="2024">
                    </div>
                    <div class="form-group">
                        <label for="end_year">End Year:</label>
                        <input type="number" id="end_year" name="end_year" 
                               value="<?php echo htmlspecialchars($endYear); ?>" 
                               min="1871" max="2024">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
        </div>

        <!-- Hitters Performance -->
        <div class="card wall">
            <div class="card-header wall__panel">
                <h2>⚾ Batting Performance by Origin</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                list($rows, $error) = safeQuery("
                    SELECT 
                        COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                        COUNT(DISTINCT b.player_id) AS player_count,
                        AVG(b.hr) AS avg_hr,
                        AVG(b.sb) AS avg_sb,
                        AVG(b.bb) AS avg_bb,
                        SUM(b.hr) AS total_hr,
                        SUM(b.h) AS total_hits
                    FROM staging_batting b
                    JOIN staging_people p ON b.player_id = p.player_id
                    WHERE b.year_id BETWEEN ? AND ?
                    AND b.ab > 0
                    GROUP BY birth_country
                    HAVING player_count >= 10
                    ORDER BY total_hr DESC
                    LIMIT 15
                ", [$startYear, $endYear]);
                
                if ($error) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Data Not Available:</strong> {$error}";
                    echo "</div>";
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table>";
                    echo "<thead><tr>";
                    echo "<th>Country</th><th>Players</th><th>Total HR</th><th>Total Hits</th><th>Avg HR/Season</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($rows as $row) {
                        echo "<tr>";
                        echo "<td>" . e($row['birth_country']) . "</td>";
                        echo "<td>" . formatInt($row['player_count']) . "</td>";
                        echo "<td>" . formatInt($row['total_hr']) . "</td>";
                        echo "<td>" . formatInt($row['total_hits']) . "</td>";
                        echo "<td>" . formatDecimal($row['avg_hr'], 1) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    echo "<p>No batting data available. Please load the Lahman database.</p>";
                }
            } else {
                echo "<div class='alert alert-warning'>";
                echo "<p><strong>Database not connected.</strong> Configure your .env file.</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Pitchers Performance -->
        <div class="card">
            <div class="card-header">
                <h2>⚾ Pitching Performance by Origin</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                list($rows, $error) = safeQuery("
                    SELECT 
                        COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                        COUNT(DISTINCT pi.player_id) AS player_count,
                        AVG(pi.so) AS avg_so,
                        AVG(pi.w) AS avg_wins,
                        SUM(pi.so) AS total_so,
                        SUM(pi.w) AS total_wins,
                        SUM(pi.ipouts) AS total_outs
                    FROM staging_pitching pi
                    JOIN staging_people p ON pi.player_id = p.player_id
                    WHERE pi.year_id BETWEEN ? AND ?
                    AND pi.ipouts > 0
                    GROUP BY birth_country
                    HAVING player_count >= 10
                    ORDER BY total_so DESC
                    LIMIT 15
                ", [$startYear, $endYear]);
                
                if ($error) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Data Not Available:</strong> {$error}";
                    echo "</div>";
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table>";
                    echo "<thead><tr>";
                    echo "<th>Country</th><th>Players</th><th>Total SO</th><th>Total Wins</th><th>Avg SO/Season</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($rows as $row) {
                        echo "<tr>";
                        echo "<td>" . e($row['birth_country']) . "</td>";
                        echo "<td>" . formatInt($row['player_count']) . "</td>";
                        echo "<td>" . formatInt($row['total_so']) . "</td>";
                        echo "<td>" . formatInt($row['total_wins']) . "</td>";
                        echo "<td>" . formatDecimal($row['avg_so'], 1) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    echo "<p>No pitching data available. Please load the Lahman database.</p>";
                }
            }
            ?>
        </div>

        <!-- WAR Section (Coming Soon) -->
        <div class="card card-scoreboard">
            <h2>📊 WAR Analysis</h2>
            <p style="color: var(--text-secondary);">
                Once the data warehouse is fully populated, this section will display:
            </p>
            <ul style="color: var(--text-secondary); line-height: 2;">
                <li>Total WAR contributions by country</li>
                <li>WAR per player comparisons</li>
                <li>Impact Index (WAR share / roster share)</li>
                <li>Top WAR leaders by origin</li>
                <li>Decade-by-decade WAR trends</li>
            </ul>
            <div class="alert alert-info" style="margin-top: 1rem;">
                <strong>Expected View:</strong> <code>dw_war_by_origin</code> will aggregate Baseball-Reference 
                WAR data with player demographics for comprehensive impact analysis.
            </div>
        </div>

        <!-- Sample Chart Placeholder -->
        <div class="card">
            <div class="card-header">
                <h3>WAR Contributions by Origin (Sample)</h3>
            </div>
            <div class="chart-placeholder" 
                 data-values='[1247, 892, 456, 234, 189]'
                 data-labels='USA,Dominican Republic,Venezuela,Cuba,Puerto Rico'>
                📊 Chart: Cumulative WAR by country
            </div>
            <p style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                <em>Note: Sample data shown. Will update with actual WAR calculations once warehouse is complete.</em>
            </p>
        </div>

        <!-- Data Status -->
        <?php
        $requiredTables = ['staging_batting', 'staging_pitching', 'staging_war_bat', 'staging_war_pitch', 'dw_war_by_origin'];
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
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
