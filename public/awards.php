<?php
/**
 * CS437 MLB Global Era - Awards Page
 * 
 * Award counts and shares by origin (MVP, Cy Young, ROY, All-Star).
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Awards Analysis';
include __DIR__ . '/partials/header.php';

// Get selected award type
$selectedAward = $_GET['award'] ?? 'all';
?>

<main>
    <div class="page-title">
        <div class="container">
            <h1>Awards & Recognition</h1>
            <p class="page-subtitle">MVP, Cy Young, Rookie of the Year, and All-Star selections by origin</p>
        </div>
    </div>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>International Excellence in Recognition</h2>
            <p>
                This section tracks how players from different countries have been recognized for their 
                excellence through baseball's most prestigious awards and selections.
            </p>
        </div>

        <!-- Award Type Tabs -->
        <div class="tabs" data-tab-group="awards">
            <ul class="tab-list">
                <li><button class="tab-button active" data-tab="all">All Awards</button></li>
                <li><button class="tab-button" data-tab="mvp">MVP</button></li>
                <li><button class="tab-button" data-tab="cy-young">Cy Young</button></li>
                <li><button class="tab-button" data-tab="roy">Rookie of the Year</button></li>
                <li><button class="tab-button" data-tab="all-star">All-Star</button></li>
            </ul>
        </div>

        <!-- All Awards Tab -->
        <div class="tab-content active" data-tab="all" data-tab-group="awards">
            <div class="card">
                <div class="card-header">
                    <h2>🏆 All Awards by Country</h2>
                </div>
                
                <?php
                if (Db::isConnected()) {
                    list($rows, $error) = safeQuery("
                        SELECT 
                            COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                            ap.award_id,
                            COUNT(*) AS award_count
                        FROM staging_awards_players ap
                        JOIN staging_people p ON ap.player_id = p.player_id
                        WHERE ap.award_id IN ('MVP', 'Cy Young Award', 'Rookie of the Year', 'All-Star Game')
                        GROUP BY birth_country, ap.award_id
                        ORDER BY birth_country, award_count DESC
                        LIMIT 100
                    ");
                    
                    if ($error) {
                        echo "<div class='alert alert-warning'>";
                        echo "<strong>Data Not Available:</strong> {$error}";
                        echo "</div>";
                    } elseif ($rows && count($rows) > 0) {
                        echo "<div class='table-wrapper'>";
                        echo "<table>";
                        echo "<thead><tr>";
                        echo "<th>Country</th><th>Award Type</th><th>Count</th>";
                        echo "</tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($rows as $row) {
                            echo "<tr>";
                            echo "<td>" . e($row['birth_country']) . "</td>";
                            echo "<td>" . e($row['award_id']) . "</td>";
                            echo "<td>" . formatInt($row['award_count']) . "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                    } else {
                        echo "<p>No award data available. Please load the Lahman database.</p>";
                    }
                } else {
                    echo "<div class='alert alert-warning'>";
                    echo "<p><strong>Database not connected.</strong> Configure your .env file.</p>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- MVP Tab -->
        <div class="tab-content" data-tab="mvp" data-tab-group="awards">
            <div class="card">
                <div class="card-header">
                    <h2>Most Valuable Player (MVP)</h2>
                </div>
                
                <?php
                if (Db::isConnected()) {
                    list($rows, $error) = safeQuery("
                        SELECT 
                            COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                            COUNT(*) AS mvp_count,
                            GROUP_CONCAT(DISTINCT CONCAT(p.name_first, ' ', p.name_last) ORDER BY ap.year_id DESC SEPARATOR ', ') AS players
                        FROM staging_awards_players ap
                        JOIN staging_people p ON ap.player_id = p.player_id
                        WHERE ap.award_id = 'MVP'
                        GROUP BY birth_country
                        ORDER BY mvp_count DESC
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
                        echo "<th>Country</th><th>MVP Awards</th><th>Recent Winners</th>";
                        echo "</tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($rows as $row) {
                            $players = $row['players'] ?? '';
                            $playerList = strlen($players) > 100 ? substr($players, 0, 100) . '...' : $players;
                            echo "<tr>";
                            echo "<td>" . e($row['birth_country']) . "</td>";
                            echo "<td>" . formatInt($row['mvp_count']) . "</td>";
                            echo "<td style='font-size: 0.9rem;'>" . e($playerList) . "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                    } else {
                        echo "<p>No MVP data available.</p>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- Cy Young Tab -->
        <div class="tab-content" data-tab="cy-young" data-tab-group="awards">
            <div class="card">
                <div class="card-header">
                    <h2>Cy Young Award</h2>
                </div>
                
                <?php
                if (Db::isConnected()) {
                    list($rows, $error) = safeQuery("
                        SELECT 
                            COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                            COUNT(*) AS cy_count,
                            GROUP_CONCAT(DISTINCT CONCAT(p.name_first, ' ', p.name_last) ORDER BY ap.year_id DESC SEPARATOR ', ') AS players
                        FROM staging_awards_players ap
                        JOIN staging_people p ON ap.player_id = p.player_id
                        WHERE ap.award_id = 'Cy Young Award'
                        GROUP BY birth_country
                        ORDER BY cy_count DESC
                        LIMIT 20
                    ");
                    
                    if ($error || !$rows || count($rows) === 0) {
                        echo "<div class='alert alert-info'>";
                        echo "<p>No Cy Young data available yet.</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='table-wrapper'>";
                        echo "<table>";
                        echo "<thead><tr>";
                        echo "<th>Country</th><th>Cy Young Awards</th><th>Recent Winners</th>";
                        echo "</tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($rows as $row) {
                            $players = $row['players'] ?? '';
                            $playerList = strlen($players) > 100 ? substr($players, 0, 100) . '...' : $players;
                            echo "<tr>";
                            echo "<td>" . e($row['birth_country']) . "</td>";
                            echo "<td>" . formatInt($row['cy_count']) . "</td>";
                            echo "<td style='font-size: 0.9rem;'>" . e($playerList) . "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- ROY Tab -->
        <div class="tab-content" data-tab="roy" data-tab-group="awards">
            <div class="card">
                <div class="card-header">
                    <h2>Rookie of the Year</h2>
                </div>
                
                <?php
                if (Db::isConnected()) {
                    list($rows, $error) = safeQuery("
                        SELECT 
                            COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                            COUNT(*) AS roy_count
                        FROM staging_awards_players ap
                        JOIN staging_people p ON ap.player_id = p.player_id
                        WHERE ap.award_id = 'Rookie of the Year'
                        GROUP BY birth_country
                        ORDER BY roy_count DESC
                        LIMIT 20
                    ");
                    
                    if ($error || !$rows || count($rows) === 0) {
                        echo "<div class='alert alert-info'>";
                        echo "<p>No Rookie of the Year data available yet.</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='table-wrapper'>";
                        echo "<table>";
                        echo "<thead><tr>";
                        echo "<th>Country</th><th>ROY Awards</th>";
                        echo "</tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($rows as $row) {
                            echo "<tr>";
                            echo "<td>" . e($row['birth_country']) . "</td>";
                            echo "<td>" . formatInt($row['roy_count']) . "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- All-Star Tab -->
        <div class="tab-content" data-tab="all-star" data-tab-group="awards">
            <div class="card">
                <div class="card-header">
                    <h2>All-Star Game Selections</h2>
                </div>
                
                <?php
                if (Db::isConnected()) {
                    list($rows, $error) = safeQuery("
                        SELECT 
                            COALESCE(NULLIF(TRIM(p.birth_country),''), 'Unknown') AS birth_country,
                            COUNT(*) AS allstar_count
                        FROM staging_awards_players ap
                        JOIN staging_people p ON ap.player_id = p.player_id
                        WHERE ap.award_id = 'All-Star Game'
                        GROUP BY birth_country
                        ORDER BY allstar_count DESC
                        LIMIT 20
                    ");
                    
                    if ($error || !$rows || count($rows) === 0) {
                        echo "<div class='alert alert-info'>";
                        echo "<p>No All-Star data available yet.</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='table-wrapper'>";
                        echo "<table>";
                        echo "<thead><tr>";
                        echo "<th>Country</th><th>All-Star Selections</th>";
                        echo "</tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($rows as $row) {
                            echo "<tr>";
                            echo "<td>" . e($row['birth_country']) . "</td>";
                            echo "<td>" . formatInt($row['allstar_count']) . "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- Chart Placeholder -->
        <div class="card">
            <div class="card-header">
                <h3>Award Share by Origin (Sample)</h3>
            </div>
            <div class="chart-placeholder" 
                 data-values='[542, 123, 89, 67, 45]'
                 data-labels='USA,D.R.,Venezuela,Cuba,P.R.'>
                📊 Chart: Total awards by country
            </div>
            <p style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                <em>Note: Sample data shown. Will update with actual award counts once data is loaded.</em>
            </p>
        </div>

        <!-- Data Status -->
        <?php
        $requiredTables = ['staging_awards_players', 'staging_people'];
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
