<?php
/**
 * CS437 MLB Global Era - Play-by-Play Page
 * 
 * Retrosheet game logs and event data for detailed analysis.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Play-by-Play Analysis';
include __DIR__ . '/partials/header.php';

// Get filter parameters
$selectedYear = $_GET['year'] ?? 2024;
$selectedTeam = $_GET['team'] ?? '';
?>

<main id="main-content">
    <div class="page-title">
        <div class="container">
            <h1>Play-by-Play Analysis</h1>
            <p class="page-subtitle">Game logs and event-level data from Retrosheet</p>
        </div>
    </div>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>Detailed Game-Level Evidence</h2>
            <p>
                This section provides access to play-by-play data from Retrosheet, allowing us to drill 
                down to individual games and events to see exactly how international players have contributed 
                to baseball history.
            </p>
            <div class="alert alert-info">
                <strong>Data Source:</strong> Retrosheet provides game logs (gl1871_2024.txt) and 
                detailed event files (csvdownloads) with play-by-play information for thousands of games.
            </div>
        </div>

        <!-- Filters -->
        <div class="filters ticket">
            <h3>Filter Options</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="year">Season:</label>
                        <select id="year" name="year">
                            <option value="2024" <?php echo $selectedYear == 2024 ? 'selected' : ''; ?>>2024</option>
                            <option value="2023" <?php echo $selectedYear == 2023 ? 'selected' : ''; ?>>2023</option>
                            <option value="2022" <?php echo $selectedYear == 2022 ? 'selected' : ''; ?>>2022</option>
                            <option value="2021" <?php echo $selectedYear == 2021 ? 'selected' : ''; ?>>2021</option>
                            <option value="2020" <?php echo $selectedYear == 2020 ? 'selected' : ''; ?>>2020</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="team">Team (optional):</label>
                        <input type="text" id="team" name="team" 
                               value="<?php echo htmlspecialchars($selectedTeam); ?>" 
                               placeholder="e.g., NYA, BOS">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
        </div>

        <!-- Game Logs -->
        <div class="card wall">
            <div class="card-header wall__panel">
                <h2>⚾ Game Logs</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                $params = [$selectedYear];
                $teamFilter = '';
                if (!empty($selectedTeam)) {
                    $teamFilter = "AND (home_team_id = ? OR visiting_team_id = ?)";
                    $params[] = strtoupper($selectedTeam);
                    $params[] = strtoupper($selectedTeam);
                }
                
                list($rows, $error) = safeQuery("
                    SELECT 
                        game_id,
                        game_dt AS game_date,
                        visiting_team_id AS visitor,
                        home_team_id AS home,
                        v_score,
                        h_score,
                        CONCAT(v_score, '-', h_score) AS score
                    FROM retro_gamelogs
                    WHERE year = ?
                    $teamFilter
                    ORDER BY game_dt DESC
                    LIMIT 50
                ", $params);
                
                if ($error) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Data Not Available:</strong> {$error}";
                    echo "<p style='margin-top: 0.5rem;'>Load Retrosheet game logs with: <code>./scripts/download_retrosheet.sh && ./scripts/load_mysql.sh</code></p>";
                    echo "</div>";
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table>";
                    echo "<thead><tr>";
                    echo "<th>Date</th><th>Game ID</th><th>Visitor</th><th>Home</th><th>Score</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($rows as $row) {
                        echo "<tr>";
                        echo "<td>" . e($row['game_date']) . "</td>";
                        echo "<td style='font-size: 0.85rem; font-family: monospace;'>" . e($row['game_id']) . "</td>";
                        echo "<td>" . e($row['visitor']) . "</td>";
                        echo "<td>" . e($row['home']) . "</td>";
                        echo "<td>" . e($row['score']) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    echo "<p>No game log data found for the selected filters.</p>";
                }
            } else {
                echo "<div class='alert alert-warning'>";
                echo "<p><strong>Database not connected.</strong> Configure your .env file.</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Event Data -->
        <div class="card">
            <div class="card-header">
                <h2>⚡ Play-by-Play Events</h2>
            </div>
            
            <?php
            if (Db::isConnected()) {
                $params = [$selectedYear];
                
                list($rows, $error) = safeQuery("
                    SELECT 
                        game_id,
                        event_id,
                        inn_ct AS inning,
                        bat_home_id AS bat_side,
                        bat_id AS batter,
                        pit_id AS pitcher,
                        event_cd AS event_code,
                        event_tx AS event_text
                    FROM retro_events
                    WHERE year = ?
                    ORDER BY game_id DESC, event_id ASC
                    LIMIT 50
                ", $params);
                
                if ($error) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Data Not Available:</strong> {$error}";
                    echo "<p style='margin-top: 0.5rem;'>Load Retrosheet event data from csvdownloads directory.</p>";
                    echo "</div>";
                } elseif ($rows && count($rows) > 0) {
                    echo "<div class='table-wrapper'>";
                    echo "<table>";
                    echo "<thead><tr>";
                    echo "<th>Game ID</th><th>Inn</th><th>Batter</th><th>Pitcher</th><th>Event</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($rows as $row) {
                        $batSide = $row['bat_side'] == 1 ? 'Home' : 'Away';
                        echo "<tr>";
                        echo "<td style='font-size: 0.85rem; font-family: monospace;'>" . e($row['game_id']) . "</td>";
                        echo "<td>" . e($row['inning']) . " (" . $batSide . ")</td>";
                        echo "<td>" . e($row['batter']) . "</td>";
                        echo "<td>" . e($row['pitcher']) . "</td>";
                        echo "<td style='font-size: 0.9rem;'>" . e($row['event_text']) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                } else {
                    echo "<p>No event data found for the selected season.</p>";
                }
            }
            ?>
        </div>

        <!-- Use Cases -->
        <div class="card card-scoreboard">
            <h2>🔍 Analysis Use Cases</h2>
            <p style="color: var(--text-secondary);">
                With play-by-play data, we can answer detailed questions like:
            </p>
            <ul style="color: var(--text-secondary); line-height: 2;">
                <li>Which international players had the most clutch hits in key situations?</li>
                <li>How do foreign-born pitchers perform in high-leverage situations?</li>
                <li>What types of plays (home runs, stolen bases, etc.) are most associated with different origins?</li>
                <li>Game-by-game contributions of international players to playoff success</li>
                <li>Historical moments: key plays by international players in championship games</li>
            </ul>
        </div>

        <!-- Example Queries -->
        <div class="card">
            <div class="card-header">
                <h3>Example Analysis Queries</h3>
            </div>
            
            <p>Once player IDs are linked to demographics, we can run sophisticated analyses:</p>
            
            <pre style="background: #2a2a2a; color: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.9rem; margin: 1rem 0;">
-- Home runs by international players in World Series games
SELECT 
    e.game_id,
    p.name_first || ' ' || p.name_last AS player_name,
    p.birth_country,
    e.event_tx
FROM retro_events e
JOIN staging_people p ON e.bat_id = p.retro_id
WHERE e.event_cd = 23  -- Home run event code
  AND e.game_id LIKE '%WS%'  -- World Series games
  AND p.birth_country != 'USA'
ORDER BY e.game_id;
            </pre>

            <pre style="background: #2a2a2a; color: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.9rem; margin: 1rem 0;">
-- Strikeouts by foreign-born pitchers in crucial situations
SELECT 
    p.birth_country,
    COUNT(*) AS strikeouts_in_key_situations
FROM retro_events e
JOIN staging_people p ON e.pit_id = p.retro_id
WHERE e.event_cd = 3  -- Strikeout
  AND e.outs_ct = 2  -- Two outs
  AND e.base2_run_id IS NOT NULL  -- Runner in scoring position
  AND p.birth_country != 'USA'
GROUP BY p.birth_country
ORDER BY strikeouts_in_key_situations DESC;
            </pre>
        </div>

        <!-- Retrosheet Attribution -->
        <div class="card">
            <div class="card-header">
                <h3>About Retrosheet Data</h3>
            </div>
            <p>
                The information used here was obtained free of charge from and is copyrighted by Retrosheet. 
                Interested parties may contact Retrosheet at <a href="http://www.retrosheet.org">www.retrosheet.org</a>.
            </p>
            <p style="margin-top: 1rem;">
                Retrosheet provides two main data products:
            </p>
            <ul style="line-height: 2;">
                <li><strong>Game Logs (gl1871_2024.txt):</strong> Summary statistics for every MLB game</li>
                <li><strong>Event Files (csvdownloads):</strong> Play-by-play data with detailed event information</li>
            </ul>
        </div>

        <!-- Data Status -->
        <?php
        $requiredTables = [
            'retro_gamelogs',
            'retro_events',
            'retro_rosters',
            'staging_people'
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
            
            <div class="alert alert-info" style="margin-top: 1rem;">
                <strong>To load Retrosheet data:</strong>
                <pre style="background: rgba(0,0,0,0.1); padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem;">
./scripts/download_retrosheet.sh
./scripts/load_mysql.sh mlb mlbuser mlbpass localhost 3306
                </pre>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
