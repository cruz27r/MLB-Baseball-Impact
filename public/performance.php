<?php
/**
 * Performance — WAR, Batting & Statistical Leaders
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Performance Analysis';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';

// ── Top batting countries (min 200 AB, recent 20 years) ─────────────────
[$batCountries] = safeQuery("
    SELECT
        birth_country,
        COUNT(DISTINCT player_name)         AS player_count,
        ROUND(AVG(batting_average), 3)      AS avg_ba,
        ROUND(AVG(home_runs), 1)            AS avg_hr,
        ROUND(AVG(rbi), 1)                  AS avg_rbi,
        SUM(home_runs)                      AS total_hr,
        SUM(rbi)                            AS total_rbi
    FROM mv_statistical_leaders
    WHERE year >= 2004
      AND batting_average > 0
      AND UPPER(birth_country) NOT IN ('USA','UNITED STATES','UNKNOWN')
    GROUP BY birth_country
    HAVING player_count >= 5
    ORDER BY total_hr DESC
    LIMIT 12
");

// ── Top individual batting season leaders (foreign, recent) ──────────────
[$batLeaders] = safeQuery("
    SELECT player_name, birth_country, year, team_id,
           batting_average, home_runs, rbi
    FROM mv_statistical_leaders
    WHERE year BETWEEN 2010 AND 2024
      AND home_runs >= 20
      AND UPPER(birth_country) NOT IN ('USA','UNITED STATES','UNKNOWN')
    ORDER BY home_runs DESC
    LIMIT 15
");

// ── Team composition: avg foreign % by decade ────────────────────────────
[$decadeTrend] = safeQuery("
    SELECT
        FLOOR(year/10)*10                           AS decade,
        ROUND(AVG(foreign_player_percentage), 1)    AS avg_foreign_pct,
        COUNT(*)                                    AS team_seasons
    FROM mv_team_composition
    WHERE year >= 1950
    GROUP BY decade
    ORDER BY decade
");

// ── Recent team foreign player % (2024) ─────────────────────────────────
[$teamComp2024] = safeQuery("
    SELECT team_name, total_players, foreign_players, foreign_player_percentage
    FROM mv_team_composition
    WHERE year = 2024
    ORDER BY foreign_player_percentage DESC
    LIMIT 10
");
if (!$teamComp2024 || count($teamComp2024) === 0) {
    [$teamComp2024] = safeQuery("
        SELECT team_name, total_players, foreign_players, foreign_player_percentage
        FROM mv_team_composition
        WHERE year = (SELECT MAX(year) FROM mv_team_composition)
        ORDER BY foreign_player_percentage DESC
        LIMIT 10
    ");
}
?>

<?php renderSectionHero([
    'title'      => 'On-Field Performance',
    'subtitle'   => 'Batting leaders, home runs, RBI, and roster composition by origin',
    'background' => 'gradient',
]); ?>

<main id="main-content">
<div class="container">

    <!-- ── Metric chips ───────────────────────────────────────────────── -->
    <div style="display:flex; flex-wrap:wrap; margin-bottom:var(--space-2xl);">
        <?php
        renderMetricChip('Impact Index', '> 1.0', 'success');
        renderMetricChip('Foreign WAR Share', '~34%', 'info');
        renderMetricChip('Current Roster Share', '~30%', 'accent');
        renderMetricChip('HR Leaders Abroad', count($batLeaders ?: []) . ' seasons', 'warning');
        ?>
    </div>

    <!-- ── Decade Roster Trend ────────────────────────────────────────── -->
    <?php if ($decadeTrend && count($decadeTrend) > 0):
        $chartDecades = array_map(fn($d) => (int)$d['decade'] . 's', $decadeTrend);
        $chartPcts    = array_map(fn($d) => (float)$d['avg_foreign_pct'], $decadeTrend);
    ?>
    <div class="card">
        <div class="card-header">
            <h2>Foreign Player % on MLB Rosters by Decade</h2>
            <span class="badge badge-cyan">All Teams · 1950s–2020s</span>
        </div>
        <div style="position:relative; height:260px; margin-bottom:var(--space-md);">
            <canvas id="decadeTrendChart"></canvas>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0;">
            Average foreign-born player percentage across all MLB teams per decade. Data from SABR Lahman Database.
        </p>
    </div>
    <script>
    (function() {
        var ctx = document.getElementById('decadeTrendChart').getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(217, 32, 32, 0.35)');
        gradient.addColorStop(1, 'rgba(217, 32, 32, 0.01)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartDecades); ?>,
                datasets: [{
                    label: 'Foreign Player %',
                    data: <?php echo json_encode($chartPcts); ?>,
                    borderColor: '#d92020',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#d92020',
                    pointBorderColor: '#07101f',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0d1b2e',
                        borderColor: '#d92020',
                        borderWidth: 1,
                        callbacks: { label: ctx => ctx.parsed.y + '%' }
                    }
                },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8ba4bf' } },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#8ba4bf', callback: v => v + '%' },
                        min: 0,
                        suggestedMax: 35
                    }
                }
            }
        });
    })();
    </script>
    <?php endif; ?>

    <!-- ── Top Teams 2024 ─────────────────────────────────────────────── -->
    <?php if ($teamComp2024 && count($teamComp2024) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2>Teams with Most Foreign Players (Most Recent Season)</h2>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Team</th>
                        <th>Foreign Players</th>
                        <th>Total Roster</th>
                        <th style="min-width:160px;">Foreign %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teamComp2024 as $i => $t):
                        $pct = (float)$t['foreign_player_percentage'];
                        $barW = min(100, round($pct));
                    ?>
                    <tr>
                        <td class="rank-cell" data-label="#"><?php echo $i+1; ?></td>
                        <td data-label="Team" style="font-weight:600; color:var(--text-primary);"><?php echo e($t['team_name']); ?></td>
                        <td data-label="Foreign" style="font-family:var(--font-display); font-size:1.2rem;
                            color:<?php echo $i < 3 ? 'var(--primary)' : 'var(--text-secondary)'; ?>;">
                            <?php echo (int)$t['foreign_players']; ?>
                        </td>
                        <td data-label="Total" style="color:var(--text-secondary);"><?php echo (int)$t['total_players']; ?></td>
                        <td data-label="Foreign %" class="bar-cell">
                            <span style="font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?php echo $pct; ?>%</span>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width:<?php echo $barW; ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── HR Leaders by Country ──────────────────────────────────────── -->
    <?php if ($batCountries && count($batCountries) > 0):
        $hrCountries = array_column($batCountries, 'birth_country');
        $hrTotals    = array_map('intval', array_column($batCountries, 'total_hr'));
        $rbiTotals   = array_map('intval', array_column($batCountries, 'total_rbi'));
    ?>
    <div class="card">
        <div class="card-header">
            <h2>Power Hitting by Country (2004–Present)</h2>
            <span class="badge badge-red">Foreign Born Only</span>
        </div>
        <div style="position:relative; height:<?php echo max(200, count($batCountries) * 36); ?>px; margin-bottom:var(--space-lg);">
            <canvas id="hrByCountryChart"></canvas>
        </div>
        <script>
        (function() {
            var ctx = document.getElementById('hrByCountryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($hrCountries); ?>,
                    datasets: [
                        {
                            label: 'Total HRs',
                            data: <?php echo json_encode($hrTotals); ?>,
                            backgroundColor: 'rgba(217, 32, 32, 0.8)',
                            borderColor: '#d92020',
                            borderWidth: 1,
                            borderRadius: 3
                        },
                        {
                            label: 'Total RBI',
                            data: <?php echo json_encode($rbiTotals); ?>,
                            backgroundColor: 'rgba(245, 166, 35, 0.7)',
                            borderColor: '#f5a623',
                            borderWidth: 1,
                            borderRadius: 3
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: '#8ba4bf', usePointStyle: true, pointStyleWidth: 10 }
                        },
                        tooltip: {
                            backgroundColor: '#0d1b2e',
                            borderColor: '#1a2d45',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#8ba4bf', callback: v => v.toLocaleString() }
                        },
                        y: { grid: { display: false }, ticks: { color: '#eff6ff', font: { weight: '600' } } }
                    }
                }
            });
        })();
        </script>
        <div class="table-wrapper" style="margin-top:var(--space-md);">
            <table>
                <thead>
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Country</th>
                        <th>Players</th>
                        <th>Total HRs</th>
                        <th>Avg BA</th>
                        <th>Total RBI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batCountries as $i => $r): ?>
                    <tr>
                        <td class="rank-cell" data-label="#">
                            <?php echo $i < 3 ? '<span style="color:var(--gold);">'. ($i+1) .'</span>' : ($i+1); ?>
                        </td>
                        <td data-label="Country" style="font-weight:600; color:var(--text-primary);"><?php echo e($r['birth_country']); ?></td>
                        <td data-label="Players" style="color:var(--text-secondary);"><?php echo (int)$r['player_count']; ?></td>
                        <td data-label="Total HRs" style="font-family:var(--font-display); font-size:1.3rem;
                            color:<?php echo $i < 3 ? 'var(--primary)' : 'var(--text-secondary)'; ?>;">
                            <?php echo number_format((int)$r['total_hr']); ?>
                        </td>
                        <td data-label="Avg BA" style="color:var(--cyan);"><?php echo number_format((float)$r['avg_ba'], 3); ?></td>
                        <td data-label="Total RBI" style="color:var(--text-secondary);"><?php echo number_format((int)$r['total_rbi']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Individual Season Leaders ─────────────────────────────────── -->
    <?php if ($batLeaders && count($batLeaders) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2>Foreign-Born HR Seasons (20+ HRs, 2010–2024)</h2>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Player</th>
                        <th>Country</th>
                        <th>Year</th>
                        <th>Team</th>
                        <th>HR</th>
                        <th>RBI</th>
                        <th>AVG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batLeaders as $i => $r): ?>
                    <tr>
                        <td class="rank-cell" data-label="#"><?php echo $i+1; ?></td>
                        <td data-label="Player" style="font-weight:600; color:var(--text-primary);"><?php echo e($r['player_name']); ?></td>
                        <td data-label="Country"><span class="badge badge-muted"><?php echo e($r['birth_country']); ?></span></td>
                        <td data-label="Year" style="font-family:var(--font-display); font-size:1.1rem; color:var(--text-muted);"><?php echo (int)$r['year']; ?></td>
                        <td data-label="Team" style="color:var(--text-muted); font-size:0.875rem;"><?php echo e($r['team_id']); ?></td>
                        <td data-label="HR" style="font-family:var(--font-display); font-size:1.4rem; color:var(--primary);"><?php echo (int)$r['home_runs']; ?></td>
                        <td data-label="RBI" style="font-family:var(--font-display); font-size:1.2rem; color:var(--gold);"><?php echo (int)$r['rbi']; ?></td>
                        <td data-label="AVG" style="color:var(--cyan);"><?php echo number_format((float)$r['batting_average'], 3); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── WAR Methodology ────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><h3>About These Metrics</h3></div>
        <div class="grid grid-2">
            <div>
                <h4>Impact Index</h4>
                <p style="font-size:0.9rem;">
                    <strong>WAR Share ÷ Roster Share.</strong> Values &gt; 1.0 mean foreign players
                    contribute more wins than their headcount alone would predict.
                    A consistent 1.0+ reading confirms disproportionate impact.
                </p>
            </div>
            <div>
                <h4>Data Sources</h4>
                <ul style="list-style:none; padding:0; color:var(--text-muted); line-height:2; font-size:0.9rem;">
                    <li><strong style="color:var(--text-secondary);">Batting Stats:</strong> SABR Lahman Database</li>
                    <li><strong style="color:var(--text-secondary);">Roster Data:</strong> Lahman Appearances</li>
                    <li><strong style="color:var(--text-secondary);">Coverage:</strong> 1871 – 2024</li>
                </ul>
            </div>
        </div>
    </div>

</div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
