<?php
/**
 * CS437 MLB Global Era - Players Page
 * 
 * Roster composition by origin over time.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Player Composition';
include __DIR__ . '/partials/header.php';

// Get filter parameters
$selectedDecade = $_GET['decade'] ?? 'all';

// Check database connection
if (!Db::isConnected()) {
    $dbError = Db::getError();
    $dbHelp = Db::getHelp();
}
?>

<main>
    <div class="page-title">
        <div class="container">
            <h1>Player Composition Analysis</h1>
            <p class="page-subtitle">Roster origins and international representation over time</p>
        </div>
    </div>

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

        <!-- Filters -->
        <div class="filters">
            <h3>Filter Options</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="decade">Decade:</label>
                        <select id="decade" name="decade">
                            <option value="all" <?php echo $selectedDecade === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="2020" <?php echo $selectedDecade === '2020' ? 'selected' : ''; ?>>2020s</option>
                            <option value="2010" <?php echo $selectedDecade === '2010' ? 'selected' : ''; ?>>2010s</option>
                            <option value="2000" <?php echo $selectedDecade === '2000' ? 'selected' : ''; ?>>2000s</option>
                            <option value="1990" <?php echo $selectedDecade === '1990' ? 'selected' : ''; ?>>1990s</option>
                            <option value="1980" <?php echo $selectedDecade === '1980' ? 'selected' : ''; ?>>1980s</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
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
                    LIMIT 20
                ");
                
                if ($error) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Data Not Available:</strong> {$error}";
                    echo "</div>";
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
                    echo "<p>No data available. Please load the Lahman database.</p>";
                }
            } else {
                echo "<div class='alert alert-warning'>";
                echo "<p><strong>Database not connected.</strong> Configure your .env file and ensure MySQL is running.</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Debut Timeline (if data available) -->
        <div class="card">
            <div class="card-header">
                <h2>Player Debuts by Country and Year</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                list($rows, $error) = safeQuery("
                    SELECT 
                        YEAR(debut) AS debut_year,
                        COALESCE(NULLIF(TRIM(birth_country),''), 'Unknown') AS birth_country,
                        COUNT(*) AS player_count
                    FROM staging_people
                    WHERE debut IS NOT NULL 
                    AND YEAR(debut) >= 2000
                    GROUP BY debut_year, birth_country
                    HAVING player_count >= 5
                    ORDER BY debut_year DESC, player_count DESC
                    LIMIT 100
                ");
                
                if ($error) {
                    echo "<div class='alert alert-info'>";
                    echo "<p><strong>Note:</strong> Timeline data not yet available. Run data loading scripts to populate.</p>";
                    echo "</div>";
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table class='table-striped'>";
                    echo "<thead><tr>";
                    echo "<th>Year</th><th>Country</th><th>Debuts</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($rows as $row) {
                        echo "<tr>";
                        echo "<td>" . e($row['debut_year']) . "</td>";
                        echo "<td>" . e($row['birth_country']) . "</td>";
                        echo "<td>" . formatInt($row['player_count']) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    echo "<p>No timeline data available for the selected filters.</p>";
                }
            }
            ?>
        </div>

        <!-- Chart Placeholder -->
        <div class="card">
            <div class="card-header">
                <h3>International Growth Over Time</h3>
            </div>
            <div class="chart-placeholder" 
                 data-values='[45, 78, 112, 156, 189, 234]'
                 data-labels='1980s,1990s,2000s,2010s,2020s'>
                📊 Chart: Foreign-born player debuts by decade
            </div>
            <p style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                <em>Note: Chart shows sample data. Will display actual values once data warehouse views are built.</em>
            </p>
        </div>

        <!-- Data Status -->
        <?php
        $requiredTables = ['staging_people', 'dw_yearly_composition'];
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
