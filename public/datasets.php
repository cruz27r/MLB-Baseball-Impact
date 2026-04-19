<?php
/**
 * Datasets — Browse available Lahman database tables
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Datasets';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/metric-chip.php';

// ── Curated table groups to display ──────────────────────────────────────
$tableGroups = [
    'Core Lahman Tables' => [
        'stg_lahman_people'          => 'Players biographical data (birth country, debut, etc.)',
        'stg_lahman_batting'         => 'Season-by-season batting statistics',
        'stg_lahman_pitching'        => 'Season-by-season pitching statistics',
        'stg_lahman_fielding'        => 'Season-by-season fielding statistics',
        'stg_lahman_salaries'        => 'Player salaries (1985–2016)',
        'stg_lahman_teams'           => 'Team records and metadata',
        'stg_lahman_awards_players'  => 'Player award winners (MVP, Cy Young, etc.)',
        'stg_lahman_allstarfull'     => 'All-Star game appearances',
        'stg_lahman_halloffame'      => 'Hall of Fame inductees and voting data',
    ],
    'Postseason & Rosters' => [
        'stg_lahman_lahman_1871_2024_csv_seriespost_csv'   => 'Postseason series results (World Series, LCS, etc.)',
        'stg_lahman_lahman_1871_2024_csv_appearances_csv'  => 'Player roster appearances per team/season',
        'stg_lahman_lahman_1871_2024_csv_battingpost_csv'  => 'Postseason batting statistics',
        'stg_lahman_lahman_1871_2024_csv_pitchingpost_csv' => 'Postseason pitching statistics',
        'stg_lahman_lahman_1871_2024_csv_parks_csv'        => 'Ballpark information',
    ],
    'Analytics Views' => [
        'mv_team_composition'        => 'Foreign player % by team and season',
        'mv_statistical_leaders'     => 'Statistical leaders (BA, HR, RBI) by player/season',
        'mv_foreign_players_summary' => 'Summary of foreign-born players by country',
        'mv_foreign_awards'          => 'Award wins by foreign-born players',
        'player_country_summary'     => 'Aggregated player counts and stats by country',
        'percent_per_decade'         => 'Foreign player percentage by decade',
    ],
];

// ── Fetch row counts for all tables ──────────────────────────────────────
$rowCounts = [];
$allTables = array_merge(...array_values($tableGroups));
foreach (array_keys($allTables) as $tbl) {
    [$rows] = safeQuery("SELECT COUNT(*) AS cnt FROM `$tbl`");
    $rowCounts[$tbl] = isset($rows[0]) ? (int)$rows[0]['cnt'] : 0;
}

$totalRows = array_sum($rowCounts);
$totalTables = count($allTables);
?>

<?php renderSectionHero([
    'title'      => 'Browse Datasets',
    'subtitle'   => 'Explore the Lahman Baseball Database tables powering this analysis',
    'background' => 'gradient',
]); ?>

<main id="main-content">
<div class="container">

    <!-- ── Summary chips ──────────────────────────────────────────────── -->
    <div style="display:flex; flex-wrap:wrap; margin-bottom:var(--space-2xl);">
        <?php
        renderMetricChip('Tables', $totalTables, 'info');
        renderMetricChip('Total Rows', number_format($totalRows), 'accent');
        renderMetricChip('Source', 'Lahman Database', 'default');
        renderMetricChip('Coverage', '1871–2024', 'success');
        ?>
    </div>

    <!-- ── Table groups ────────────────────────────────────────────────── -->
    <?php foreach ($tableGroups as $groupName => $tables): ?>
    <div class="card" style="margin-bottom:var(--space-2xl);">
        <div class="card-header">
            <h2><?php echo $groupName; ?></h2>
            <span class="badge badge-muted"><?php echo count($tables); ?> tables</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Description</th>
                        <th style="text-align:right;">Rows</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $tbl => $desc):
                        $cnt = $rowCounts[$tbl] ?? 0;
                        $hasData = $cnt > 0;
                    ?>
                    <tr>
                        <td data-label="Table" style="font-family:var(--font-display); letter-spacing:0.02em;
                            color:<?php echo $hasData ? 'var(--primary)' : 'var(--text-muted)'; ?>; font-size:0.9rem;">
                            <?php echo htmlspecialchars($tbl); ?>
                        </td>
                        <td data-label="Description" style="color:var(--text-secondary); font-size:0.875rem;">
                            <?php echo htmlspecialchars($desc); ?>
                        </td>
                        <td data-label="Rows" style="text-align:right; font-family:var(--font-display);
                            font-size:1.2rem; color:<?php echo $hasData ? 'var(--gold)' : 'var(--muted)'; ?>;">
                            <?php echo $hasData ? number_format($cnt) : '—'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ── Data note ──────────────────────────────────────────────────── -->
    <div class="alert alert-info">
        <h4>About the Lahman Baseball Database</h4>
        <p>
            The Sean Lahman Baseball Database is the authoritative open-source baseball statistics dataset,
            covering Major League Baseball from 1871 through 2024. It includes player biographical data,
            season statistics, awards, salaries, postseason results, and more.
            All analysis on this site is derived from this dataset.
        </p>
    </div>

</div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
