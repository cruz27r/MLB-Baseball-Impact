<?php
/**
 * CS437 MLB Global Era - Home Page
 * 
 * Landing page with project introduction, KPIs, and navigation to analysis sections.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Home';
include __DIR__ . '/partials/header.php';

// Check database connection
$dbError = null;
if (!Db::isConnected()) {
    $dbError = Db::getError();
    $dbHelp = Db::getHelp();
}

// Fetch sample KPIs (with graceful fallback)
$foreignPlayersPercent = 'N/A';
$topAwardsCountry = 'N/A';
$championshipContrib = 'N/A';

if (Db::isConnected()) {
    // Try to get foreign player percentage from recent year
    list($rows, $error) = safeQuery("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN COALESCE(NULLIF(TRIM(birth_country),''), 'USA') != 'USA' THEN 1 ELSE 0 END) as foreign
        FROM staging_people
        WHERE debut >= '2020-01-01'
    ");
    
    if ($rows && count($rows) > 0 && $rows[0]['total'] > 0) {
        $foreignPlayersPercent = formatPercent($rows[0]['foreign'] / $rows[0]['total']);
    }

    // Try to get top award-winning country
    list($rows, $error) = safeQuery("
        SELECT p.birth_country, COUNT(*) as awards
        FROM staging_awards_players ap
        JOIN staging_people p ON ap.player_id = p.player_id
        WHERE ap.year_id >= 2010 
        AND COALESCE(NULLIF(TRIM(p.birth_country),''), 'USA') != 'USA'
        GROUP BY p.birth_country
        ORDER BY awards DESC
        LIMIT 1
    ");
    
    if ($rows && count($rows) > 0) {
        $topAwardsCountry = htmlspecialchars($rows[0]['birth_country']);
    }
}
?>

<main>
    <div class="page-title">
        <div class="container">
            <h1>MLB Baseball Impact Analysis</h1>
            <p class="page-subtitle">Exploring the Global Transformation of Major League Baseball</p>
        </div>
    </div>

    <div class="container">
        <?php if ($dbError): ?>
        <div class="alert alert-error">
            <h3>Database Connection Error</h3>
            <p><strong>Error:</strong> <?php echo htmlspecialchars($dbError); ?></p>
            <?php if ($dbHelp): ?>
            <p><strong>Help:</strong> <?php echo htmlspecialchars($dbHelp); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Project Introduction -->
        <div class="card">
            <div class="card-header">
                <h2>Welcome to the MLB Baseball Impact Project</h2>
            </div>
            <p class="lead">
                This project analyzes the profound impact of international players on Major League Baseball 
                through comprehensive data analysis spanning from the 1800s to the present day.
            </p>
            <p>
                Using data from the SABR Lahman Database, Retrosheet play-by-play records, and Baseball-Reference, 
                we examine how players from around the world have transformed America's pastime into a truly 
                global sport.
            </p>
        </div>

        <!-- How We Show Impact -->
        <div class="card">
            <div class="card-header">
                <h2>How We Measure Impact</h2>
            </div>
            <p>Our analysis focuses on five key dimensions:</p>
            <ul style="line-height: 2;">
                <li><strong>Roster Composition:</strong> How player origins have evolved over time</li>
                <li><strong>Performance Metrics:</strong> WAR contributions and statistical excellence by origin</li>
                <li><strong>Awards & Recognition:</strong> MVP, Cy Young, All-Star selections by country</li>
                <li><strong>Championship Impact:</strong> World Series teams and their international contributors</li>
                <li><strong>Play-by-Play Evidence:</strong> Game-level data showing key moments and contributions</li>
            </ul>
        </div>

        <!-- Quick KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Foreign-Born Players</div>
                <div class="kpi-value"><?php echo $foreignPlayersPercent; ?></div>
                <div class="kpi-note">Of MLB debuts since 2020</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Top Awards Origin</div>
                <div class="kpi-value"><?php echo $topAwardsCountry; ?></div>
                <div class="kpi-note">Most awards 2010-present (non-USA)</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Data Tables</div>
                <div class="kpi-value"><?php 
                    if (Db::isConnected()) {
                        $status = getDataStatus(['staging_people', 'staging_batting', 'staging_pitching', 'staging_teams']);
                        echo count($status['ready']) . '/' . (count($status['ready']) + count($status['missing']));
                    } else {
                        echo 'N/A';
                    }
                ?></div>
                <div class="kpi-note">Loaded and ready for analysis</div>
            </div>
        </div>

        <?php if (!Db::isConnected() || !tableExists('staging_people')): ?>
        <div class="banner">
            <h2>⚾ Data Loading Required</h2>
            <p>To see live statistics, run the data loading scripts:</p>
            <pre style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 4px; margin: 1rem 0;">
./scripts/download_lahman_sabr.sh
./scripts/download_retrosheet.sh
./scripts/load_mysql.sh mlb mlbuser mlbpass localhost 3306
            </pre>
            <p>Once data is loaded, refresh this page to see actual metrics.</p>
        </div>
        <?php endif; ?>

        <!-- Section Links -->
        <div class="card">
            <div class="card-header">
                <h2>Explore the Analysis</h2>
            </div>
            <div class="btn-group">
                <a href="/players.php" class="btn btn-primary">
                    👥 Player Composition
                    <div style="font-size: 0.875rem; margin-top: 0.25rem;">Roster origins over time</div>
                </a>
                <a href="/performance.php" class="btn btn-primary">
                    📊 Performance Analysis
                    <div style="font-size: 0.875rem; margin-top: 0.25rem;">WAR and statistical leaders</div>
                </a>
                <a href="/awards.php" class="btn btn-primary">
                    🏆 Awards & Recognition
                    <div style="font-size: 0.875rem; margin-top: 0.25rem;">MVP, Cy Young, All-Stars</div>
                </a>
            </div>
            <div class="btn-group">
                <a href="/championships.php" class="btn btn-primary">
                    🏅 Championships
                    <div style="font-size: 0.875rem; margin-top: 0.25rem;">World Series contributors</div>
                </a>
                <a href="/playbyplay.php" class="btn btn-primary">
                    ⚡ Play-by-Play
                    <div style="font-size: 0.875rem; margin-top: 0.25rem;">Game logs and events</div>
                </a>
            </div>
        </div>

        <!-- Data Status Section -->
        <?php if (Db::isConnected()): ?>
        <div class="card">
            <div class="card-header">
                <h3>Data Status</h3>
            </div>
            <?php
            $requiredTables = [
                'staging_people' => 'Player Demographics',
                'staging_batting' => 'Batting Statistics',
                'staging_pitching' => 'Pitching Statistics',
                'staging_teams' => 'Team Information',
                'staging_awards_players' => 'Awards Data',
                'retro_gamelogs' => 'Game Logs (Retrosheet)',
                'retro_events' => 'Event Data (Retrosheet)'
            ];
            
            foreach ($requiredTables as $table => $label) {
                $exists = tableExists($table);
                $statusClass = $exists ? 'ready' : 'pending';
                $statusText = $exists ? 'Ready' : 'Pending';
                echo "<div style='display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;'>";
                echo "<span>{$label}</span>";
                echo "<span class='data-status {$statusClass}'>{$statusText}</span>";
                echo "</div>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

