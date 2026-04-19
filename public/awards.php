<?php
/**
 * Awards — International Player Recognition
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Awards Analysis';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';

// ── Award query constants ────────────────────────────────────────────────
$FOREIGN_EXCLUDE = "UPPER(TRIM(COALESCE(p.birthcountry,''))) NOT IN ('USA','UNITED STATES','UNITED STATES OF AMERICA')
                    AND TRIM(COALESCE(p.birthcountry,'')) != ''";

// Major awards we care about
$majorAwards = ['Most Valuable Player', 'Rookie of the Year', 'TSN Pitcher of the Year',
                'TSN All-Star', 'Baseball Magazine All-Star', 'Babe Ruth Award'];

$awardsIn  = implode("','", $majorAwards);

// ── Total foreign award wins ─────────────────────────────────────────────
[$totals]  = safeQuery("
    SELECT COUNT(*) AS total_wins
    FROM stg_lahman_awards_players a
    JOIN stg_lahman_people p ON p.playerid = a.playerid
    WHERE $FOREIGN_EXCLUDE
");
$totalWins = $totals[0]['total_wins'] ?? 0;

// ── Top countries by all award wins ─────────────────────────────────────
[$topCountries] = safeQuery("
    SELECT
        COALESCE(NULLIF(TRIM(p.birthcountry),''),'Unknown') AS birth_country,
        COUNT(*) AS award_count
    FROM stg_lahman_awards_players a
    JOIN stg_lahman_people p ON p.playerid = a.playerid
    WHERE $FOREIGN_EXCLUDE
    GROUP BY birth_country
    ORDER BY award_count DESC
    LIMIT 12
");

// ── Major award breakdown by country ────────────────────────────────────
[$majorRows] = safeQuery("
    SELECT
        a.awardid                                                       AS award,
        COALESCE(NULLIF(TRIM(p.birthcountry),''),'Unknown')             AS birth_country,
        COUNT(*)                                                        AS wins,
        MIN(a.yearid)                                                   AS first_year,
        MAX(a.yearid)                                                   AS last_year
    FROM stg_lahman_awards_players a
    JOIN stg_lahman_people p ON p.playerid = a.playerid
    WHERE a.awardid IN ('$awardsIn')
      AND $FOREIGN_EXCLUDE
    GROUP BY a.awardid, birth_country
    ORDER BY a.awardid, wins DESC
");

// ── All-Star foreign winners (our awards table covers 1877–1951 only) ────
[$mvpRows] = safeQuery("
    SELECT
        CONCAT(p.namefirst, ' ', p.namelast) AS player_name,
        a.yearid                              AS year,
        a.awardid                             AS award,
        a.lgid                               AS league,
        COALESCE(NULLIF(TRIM(p.birthcountry),''),'Unknown') AS birth_country
    FROM stg_lahman_awards_players a
    JOIN stg_lahman_people p ON p.playerid = a.playerid
    WHERE $FOREIGN_EXCLUDE
    ORDER BY a.yearid DESC
    LIMIT 25
");
?>

<?php renderSectionHero([
    'title'      => 'Awards & Accolades',
    'subtitle'   => 'MVP, Cy Young, Rookie of the Year, and All-Star selections by player origin',
    'background' => 'gradient',
]); ?>

<main id="main-content">
<div class="container">

    <!-- ── Summary chips ──────────────────────────────────────────────── -->
    <div style="display:flex; flex-wrap:wrap; margin-bottom:var(--space-2xl);">
        <?php
        renderMetricChip('Foreign Award Wins', number_format($totalWins), 'accent');
        renderMetricChip('Countries Winning', count($topCountries ?: []), 'info');
        renderMetricChip('Award Entries', count($mvpRows ?: []), 'success');
        renderMetricChip('Coverage', '1877–1951', 'warning');
        ?>
    </div>

    <!-- ── Top Countries ─────────────────────────────────────────────── -->
    <?php if ($topCountries && count($topCountries) > 0):
        $aCtryNames  = array_column($topCountries, 'birth_country');
        $aCtryWins   = array_map('intval', array_column($topCountries, 'award_count'));
        $aCtryColors = ['#f5a623','#d92020','#22d3ee','#10b981','#a78bfa','#fb923c','#60a5fa','#fbbf24','#34d399','#f472b6','#4ade80','#38bdf8'];
        $maxWins = max($aCtryWins);
    ?>
    <div class="card-grid card-grid-2" style="margin-bottom:var(--space-2xl);">
        <div class="card">
            <div class="card-header">
                <h2>Award Wins by Country</h2>
                <span class="badge badge-red">Foreign Only</span>
            </div>
            <div style="position:relative; height:280px; display:flex; align-items:center; justify-content:center;">
                <canvas id="awardsDonutChart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Country Rankings</h2>
                <span class="badge badge-muted">Historical · 1877–1951</span>
            </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Country</th>
                        <th>Award Wins</th>
                        <th style="min-width:160px;">Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCountries as $i => $row):
                        $barW = $maxWins > 0 ? round($row['award_count'] / $maxWins * 100) : 0;
                    ?>
                    <tr>
                        <td class="rank-cell" data-label="#"><?php echo $i + 1; ?></td>
                        <td data-label="Country" style="font-weight:500; color:var(--text-primary);">
                            <?php echo e($row['birth_country']); ?>
                        </td>
                        <td data-label="Award Wins" style="font-family:var(--font-display); font-size:1.3rem;
                            color:<?php echo $i < 3 ? 'var(--gold)' : 'var(--text-secondary)'; ?>;">
                            <?php echo number_format((int)$row['award_count']); ?>
                        </td>
                        <td data-label="Share" class="bar-cell">
                            <div class="bar-bg">
                                <div class="bar-fill" style="width:<?php echo $barW; ?>%;
                                    background:<?php echo $i === 0 ? 'linear-gradient(90deg,var(--gold),var(--primary))' : 'linear-gradient(90deg,var(--primary),var(--gold))'; ?>;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
        </div>
    </div>
    <script>
    (function() {
        var ctx = document.getElementById('awardsDonutChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($aCtryNames); ?>,
                datasets: [{
                    data: <?php echo json_encode($aCtryWins); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($aCtryColors, 0, count($aCtryNames))); ?>,
                    borderColor: '#07101f',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#8ba4bf', padding: 10, font: { size: 11 }, usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: '#0d1b2e',
                        borderColor: '#1a2d45',
                        borderWidth: 1,
                        callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' wins' }
                    }
                }
            }
        });
    })();
    </script>
    <?php else: ?>
        <?php renderEmptyState([
            'icon' => '🏆', 'title' => 'No Data',
            'message' => 'Award data will appear once the database is fully loaded.',
        ]); ?>
    <?php endif; ?>

    <!-- ── Foreign Award Winners (Historical) ────────────────────────── -->
    <?php if ($mvpRows && count($mvpRows) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2>Foreign-Born Award Winners (Historical Record)</h2>
            <span class="badge badge-gold">1877–1951 · <?php echo count($mvpRows); ?> entries</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Player</th>
                        <th>Award</th>
                        <th>Country</th>
                        <th>League</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mvpRows as $row): ?>
                    <tr>
                        <td data-label="Year" style="font-family:var(--font-display); font-size:1.1rem; color:var(--gold);">
                            <?php echo (int)$row['year']; ?>
                        </td>
                        <td data-label="Player" style="font-weight:600; color:var(--text-primary);">
                            <?php echo e($row['player_name']); ?>
                        </td>
                        <td data-label="Award" style="font-size:0.8rem; color:var(--text-secondary);">
                            <?php echo e($row['award']); ?>
                        </td>
                        <td data-label="Country">
                            <span class="badge badge-muted"><?php echo e($row['birth_country']); ?></span>
                        </td>
                        <td data-label="League" style="color:var(--text-muted);">
                            <?php echo e($row['league']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Modern Awards Context ──────────────────────────────────────── -->
    <div class="alert alert-info">
        <h4>Modern Awards (2000–Present)</h4>
        <p>
            The Lahman awards dataset covers historical records through 1951. Modern foreign-born award winners — including
            <strong>Pedro Martínez</strong> (DR, Cy Young 1999–2000), <strong>Albert Pujols</strong> (DR, MVP 2005, 2008, 2009),
            <strong>Miguel Cabrera</strong> (VEN, MVP 2012–2013, Triple Crown 2012), <strong>Shohei Ohtani</strong> (JPN, MVP 2021, 2023) —
            represent a dominant modern trend not yet captured in this dataset's awards table.
            The performance and salary data on other pages reflect this modern dominance.
        </p>
    </div>

    <!-- ── Award Categories Info ──────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><h3>Award Categories Analyzed</h3></div>
        <div class="grid grid-2" style="gap:var(--space-md);">
            <?php $cats = [
                ['icon'=>'🥇','name'=>'Most Valuable Player','desc'=>'League award for best overall player'],
                ['icon'=>'⚾','name'=>'Pitcher of the Year','desc'=>'Best pitcher in each league (TSN)'],
                ['icon'=>'🌟','name'=>'Rookie of the Year','desc'=>'Top first-year player per league'],
                ['icon'=>'⭐','name'=>'All-Star Selections','desc'=>'Baseball Magazine & TSN All-Stars'],
                ['icon'=>'🥊','name'=>'Babe Ruth Award','desc'=>'World Series outstanding performance'],
                ['icon'=>'👑','name'=>'Triple Crown','desc'=>'Avg, HR, and RBI leader in same season'],
            ];
            foreach ($cats as $cat): ?>
            <div style="display:flex; gap:var(--space-md); padding:var(--space-md) 0; border-bottom:1px solid var(--border);">
                <div style="font-size:1.5rem; flex-shrink:0;"><?php echo $cat['icon']; ?></div>
                <div>
                    <div style="font-weight:600; color:var(--text-primary); margin-bottom:2px;"><?php echo $cat['name']; ?></div>
                    <div style="font-size:0.875rem; color:var(--text-muted);"><?php echo $cat['desc']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
