<?php
/**
 * Players — Roster Composition & Origins
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Player Composition';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';

// Decade filter
$selectedDecade = (int)($_GET['decade'] ?? 0);

// ── Summary stats (all-time) ─────────────────────────────────────────────
$summary = null;
if (Db::isConnected()) {
    [$summary] = safeQuery("
        SELECT
            COUNT(*)                                                    AS total_players,
            COUNT(DISTINCT COALESCE(NULLIF(TRIM(birthcountry),''), 'Unknown')) AS total_countries,
            SUM(CASE
                    WHEN UPPER(TRIM(COALESCE(birthcountry,''))) NOT IN
                         ('USA','UNITED STATES','UNITED STATES OF AMERICA')
                         AND TRIM(COALESCE(birthcountry,'')) != ''
                    THEN 1 ELSE 0
                END)                                                    AS foreign_players
        FROM stg_lahman_people
    ");
    $summary = $summary[0] ?? null;
}

// ── Country leaderboard ──────────────────────────────────────────────────
$whereClause = '1=1';
$params = [];
if ($selectedDecade > 0) {
    $endYear = $selectedDecade + 9;
    $whereClause = "YEAR(debut) BETWEEN :start AND :end";
    $params[':start'] = $selectedDecade;
    $params[':end']   = $endYear;
}

[$rows, $queryError] = safeQuery("
    SELECT
        COALESCE(NULLIF(TRIM(birthcountry),''), 'Unknown') AS birth_country,
        COUNT(*)                                            AS player_count,
        MIN(YEAR(debut))                                    AS first_debut,
        MAX(YEAR(debut))                                    AS last_debut
    FROM stg_lahman_people
    WHERE $whereClause
    GROUP BY birth_country
    ORDER BY player_count DESC
    LIMIT 20
", $params);
?>

<?php renderSectionHero([
    'title'      => 'Roster Composition & Origins',
    'subtitle'   => 'Tracking the geographic evolution of Major League Baseball rosters from 1871 to today',
    'background' => 'gradient',
]); ?>

<main id="main-content">
<div class="container">

    <?php if (!Db::isConnected()): ?>
    <div class="alert alert-error">
        <h3>Database Connection Error</h3>
        <p><?php echo e(Db::getError()); ?></p>
        <?php if (Db::getHelp()): ?>
        <p><?php echo e(Db::getHelp()); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Summary Stat Cards ──────────────────────────────────────────── -->
    <?php if ($summary): ?>
    <div class="grid card-grid-4" style="margin-bottom:var(--space-2xl);">
        <div class="stat-card">
            <div class="stat-value" style="color:var(--text-primary);">
                <?php echo number_format((int)$summary['total_players']); ?>
            </div>
            <div class="stat-label">Total Players</div>
            <div class="stat-sub">in Lahman database</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--primary);">
                <?php echo number_format((int)$summary['foreign_players']); ?>
            </div>
            <div class="stat-label">Foreign-Born</div>
            <div class="stat-sub"><?php
                $pct = $summary['total_players'] > 0
                    ? round($summary['foreign_players'] / $summary['total_players'] * 100, 1)
                    : 0;
                echo $pct . '% of all players';
            ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--gold);">
                <?php echo (int)$summary['total_countries']; ?>
            </div>
            <div class="stat-label">Countries</div>
            <div class="stat-sub">represented in MLB</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--cyan);"><?php echo $pct ?? '~30'; ?>%</div>
            <div class="stat-label">Foreign Share</div>
            <div class="stat-sub">all-time historical</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Country Leaderboard ─────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h2>Players by Birth Country</h2>

            <!-- Decade filter form -->
            <form method="get" style="display:flex; align-items:center; gap:var(--space-sm);">
                <label for="decade" style="font-size:0.85rem; color:var(--text-muted); white-space:nowrap;">Filter by debut decade:</label>
                <select name="decade" id="decade" onchange="this.form.submit()"
                    style="background:var(--panel); border:1px solid var(--border-strong); color:var(--text-primary);
                           padding:0.35rem 0.75rem; border-radius:var(--radius-md); font-family:var(--font-sans);
                           font-size:0.875rem; cursor:pointer;">
                    <option value="0" <?php echo !$selectedDecade ? 'selected' : ''; ?>>All Time</option>
                    <?php foreach (range(1870, 2020, 10) as $d): ?>
                    <option value="<?php echo $d; ?>" <?php echo $selectedDecade === $d ? 'selected' : ''; ?>>
                        <?php echo $d; ?>s
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($queryError): ?>
            <?php renderEmptyState([
                'icon'    => '📊',
                'title'   => 'Data Not Available',
                'message' => $queryError,
                'hint'    => 'Run the data loading scripts to populate the database.',
            ]); ?>
        <?php elseif ($rows && count($rows) > 0): ?>

            <?php
            $totalInView = array_sum(array_column($rows, 'player_count'));
            $maxCount    = max(array_column($rows, 'player_count'));
            // Chart data — exclude USA for foreign focus, or show all with USA prominent
            $chartLabels = array_column($rows, 'birth_country');
            $chartCounts = array_map('intval', array_column($rows, 'player_count'));
            $chartColors = array_map(function($c) {
                return $c === 'USA' ? 'rgba(34, 211, 238, 0.8)' : 'rgba(217, 32, 32, 0.75)';
            }, $chartLabels);
            ?>

            <div style="position:relative; height:<?php echo max(220, count($rows) * 30); ?>px; margin-bottom:var(--space-lg);">
                <canvas id="countryChart"></canvas>
            </div>
            <script>
            (function() {
                var ctx = document.getElementById('countryChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Players',
                            data: <?php echo json_encode($chartCounts); ?>,
                            backgroundColor: <?php echo json_encode($chartColors); ?>,
                            borderColor: 'transparent',
                            borderRadius: 3
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0d1b2e',
                                borderColor: '#1a2d45',
                                borderWidth: 1,
                                callbacks: {
                                    label: ctx => ctx.parsed.x.toLocaleString() + ' players'
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: '#8ba4bf', callback: v => v.toLocaleString() }
                            },
                            y: { grid: { display: false }, ticks: { color: '#eff6ff' } }
                        }
                    }
                });
            })();
            </script>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="rank-cell">#</th>
                            <th>Country</th>
                            <th>Players</th>
                            <th>% of Total</th>
                            <th style="min-width:140px;">Share</th>
                            <th>Debut Range</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $i => $row):
                            $pct      = $totalInView > 0 ? round($row['player_count'] / $totalInView * 100, 1) : 0;
                            $barWidth = $maxCount > 0 ? round($row['player_count'] / $maxCount * 100) : 0;
                            $isTop    = $i < 3;
                        ?>
                        <tr>
                            <td class="rank-cell" data-label="#">
                                <?php if ($isTop): ?>
                                <span style="color:var(--gold);"><?php echo $i + 1; ?></span>
                                <?php else: ?>
                                <?php echo $i + 1; ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Country" style="font-weight:500; color:var(--text-primary);">
                                <?php echo e($row['birth_country']); ?>
                            </td>
                            <td data-label="Players" style="font-family:var(--font-display); font-size:1.2rem; color:var(--<?php echo $isTop ? 'primary' : 'text-secondary'; ?>);">
                                <?php echo number_format((int)$row['player_count']); ?>
                            </td>
                            <td data-label="% of Total" style="color:var(--text-secondary);">
                                <?php echo $pct; ?>%
                            </td>
                            <td data-label="Share" class="bar-cell">
                                <div class="bar-bg">
                                    <div class="bar-fill" style="width:<?php echo $barWidth; ?>%;"></div>
                                </div>
                            </td>
                            <td data-label="Debut Range" style="font-size:0.85rem; color:var(--text-muted);">
                                <?php echo (int)$row['first_debut']; ?>–<?php echo (int)$row['last_debut']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <?php renderEmptyState([
                'icon'    => '📊',
                'title'   => 'No Data Available',
                'message' => 'No player records found for the selected filters.',
                'hint'    => 'Try selecting a different decade or check that the database is loaded.',
            ]); ?>
        <?php endif; ?>
    </div>

    <!-- ── About the data ─────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><h3>Methodology</h3></div>
        <p>
            Players are classified by birth country as recorded in the SABR Lahman Database.
            "Foreign-born" excludes players born in the USA, United States, or United States of America.
            This analysis spans every player recorded from 1871 to 2024.
        </p>
        <p style="margin-bottom:0;">
            <strong>Source:</strong> SABR Lahman Baseball Database &nbsp;·&nbsp;
            <strong>Updated:</strong> 2024
        </p>
    </div>

</div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
