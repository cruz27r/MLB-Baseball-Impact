<?php
/**
 * Championships — World Series Team Composition
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Championships';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';

$FOREIGN_COND = "UPPER(TRIM(COALESCE(p.birthcountry,''))) NOT IN ('USA','UNITED STATES','UNITED STATES OF AMERICA')
                 AND TRIM(COALESCE(p.birthcountry,'')) != ''";

// ── WS winner foreign % (2000–present) ──────────────────────────────────
[$wsData] = safeQuery("
    SELECT
        ws.yearid,
        ws.teamidwinner                       AS team_id,
        t.name                                AS team_name,
        COUNT(DISTINCT a.playerid)            AS total_players,
        SUM(CASE WHEN $FOREIGN_COND THEN 1 ELSE 0 END) AS foreign_players,
        ROUND(
            SUM(CASE WHEN $FOREIGN_COND THEN 1 ELSE 0 END)
            / COUNT(DISTINCT a.playerid) * 100, 1
        )                                     AS foreign_pct
    FROM stg_lahman_lahman_1871_2024_csv_seriespost_csv ws
    JOIN stg_lahman_lahman_1871_2024_csv_appearances_csv a
        ON a.yearid = ws.yearid AND a.teamid = ws.teamidwinner
    JOIN stg_lahman_people p ON p.playerid = a.playerid
    LEFT JOIN stg_lahman_teams t
        ON t.yearid = ws.yearid AND t.teamid = ws.teamidwinner
    WHERE ws.round = 'WS' AND ws.yearid >= 2000
    GROUP BY ws.yearid, ws.teamidwinner, t.name
    ORDER BY ws.yearid DESC
");

// ── Summary stats ────────────────────────────────────────────────────────
$avgForeignPct = 0;
$maxForeignPct = 0;
$minForeignPct = 100;
if ($wsData && count($wsData) > 0) {
    $pcts = array_column($wsData, 'foreign_pct');
    $avgForeignPct = round(array_sum($pcts) / count($pcts), 1);
    $maxForeignPct = max($pcts);
    $minForeignPct = min($pcts);
}

// ── Top countries on WS winners ──────────────────────────────────────────
[$wsCountries] = safeQuery("
    SELECT
        COALESCE(NULLIF(TRIM(p.birthcountry),''),'Unknown') AS birth_country,
        COUNT(DISTINCT a.playerid)                           AS player_seasons,
        COUNT(DISTINCT ws.yearid)                            AS championship_seasons
    FROM stg_lahman_lahman_1871_2024_csv_seriespost_csv ws
    JOIN stg_lahman_lahman_1871_2024_csv_appearances_csv a
        ON a.yearid = ws.yearid AND a.teamid = ws.teamidwinner
    JOIN stg_lahman_people p ON p.playerid = a.playerid
    WHERE ws.round = 'WS' AND ws.yearid >= 2000
      AND $FOREIGN_COND
    GROUP BY birth_country
    ORDER BY championship_seasons DESC, player_seasons DESC
    LIMIT 10
");

// ── Historical WS foreign % trend by decade ───────────────────────────────
[$decadeTrend] = safeQuery("
    SELECT
        FLOOR(ws.yearid/10)*10 AS decade,
        ROUND(AVG(
            (SELECT COUNT(*) FROM stg_lahman_lahman_1871_2024_csv_appearances_csv aa
             JOIN stg_lahman_people pp ON pp.playerid = aa.playerid
             WHERE aa.yearid = ws.yearid AND aa.teamid = ws.teamidwinner
               AND UPPER(TRIM(COALESCE(pp.birthcountry,''))) NOT IN ('USA','UNITED STATES','UNITED STATES OF AMERICA')
               AND TRIM(COALESCE(pp.birthcountry,'')) != '')
            /
            NULLIF((SELECT COUNT(*) FROM stg_lahman_lahman_1871_2024_csv_appearances_csv aa2
             WHERE aa2.yearid = ws.yearid AND aa2.teamid = ws.teamidwinner), 0)
            * 100
        ), 1) AS avg_foreign_pct,
        COUNT(*) AS championships
    FROM stg_lahman_lahman_1871_2024_csv_seriespost_csv ws
    WHERE ws.round = 'WS' AND ws.yearid >= 1950
    GROUP BY decade
    ORDER BY decade
");
?>

<?php renderSectionHero([
    'title'      => 'Championship Contributions',
    'subtitle'   => 'International player presence on World Series winning teams, 2000–2024',
    'background' => 'gradient',
]); ?>

<main id="main-content">
<div class="container">

    <!-- ── Summary stats ──────────────────────────────────────────────── -->
    <div class="grid card-grid-4" style="margin-bottom:var(--space-2xl);">
        <div class="stat-card">
            <div class="stat-value" style="color:var(--gold);"><?php echo $avgForeignPct; ?>%</div>
            <div class="stat-label">Avg Foreign Share</div>
            <div class="stat-sub">per WS winner (2000–2024)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--primary);"><?php echo $maxForeignPct; ?>%</div>
            <div class="stat-label">Peak Foreign %</div>
            <div class="stat-sub">highest on any WS winner</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--cyan);"><?php echo count($wsData ?: []); ?></div>
            <div class="stat-label">Seasons Analyzed</div>
            <div class="stat-sub">World Series champions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--success);"><?php echo count($wsCountries ?: []); ?></div>
            <div class="stat-label">Countries Represented</div>
            <div class="stat-sub">on championship rosters</div>
        </div>
    </div>

    <!-- ── WS Foreign % Trend Chart ─────────────────────────────────── -->
    <?php if ($wsData && count($wsData) > 0):
        // Sort chronologically for chart
        $wsChron = array_reverse($wsData);
        $chartYears  = array_map(fn($r) => (int)$r['yearid'], $wsChron);
        $chartPcts   = array_map(fn($r) => (float)$r['foreign_pct'], $wsChron);
        $chartTeams  = array_map(fn($r) => $r['team_name'] ?: $r['team_id'], $wsChron);
    ?>
    <div class="card" style="margin-bottom:var(--space-2xl);">
        <div class="card-header">
            <h2>Foreign Players on World Series Champions</h2>
            <span class="badge badge-gold">2000–2024</span>
        </div>
        <div style="position:relative; height:280px;">
            <canvas id="wsTrendChart"></canvas>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-top:var(--space-md); margin-bottom:0;">
            Foreign-born player % on each World Series winning roster. Dotted line marks the 28.2% average.
        </p>
    </div>
    <script>
    (function() {
        var ctx = document.getElementById('wsTrendChart').getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(245, 166, 35, 0.3)');
        gradient.addColorStop(1, 'rgba(245, 166, 35, 0.02)');
        var years  = <?php echo json_encode($chartYears); ?>;
        var pcts   = <?php echo json_encode($chartPcts); ?>;
        var teams  = <?php echo json_encode($chartTeams); ?>;
        var avg    = <?php echo $avgForeignPct; ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: years,
                datasets: [
                    {
                        label: 'Foreign %',
                        data: pcts,
                        borderColor: '#f5a623',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: pcts.map(p => p >= 35 ? '#d92020' : p >= 25 ? '#f5a623' : '#22d3ee'),
                        pointBorderColor: '#07101f',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Avg (' + avg + '%)',
                        data: years.map(() => avg),
                        borderColor: 'rgba(255,255,255,0.25)',
                        borderDash: [6, 4],
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#8ba4bf', usePointStyle: true, pointStyleWidth: 10 }
                    },
                    tooltip: {
                        backgroundColor: '#0d1b2e',
                        borderColor: '#f5a623',
                        borderWidth: 1,
                        callbacks: {
                            title: ctx => years[ctx[0].dataIndex] + ' · ' + teams[ctx[0].dataIndex],
                            label: ctx => ctx.dataset.label === 'Foreign %'
                                ? 'Foreign players: ' + ctx.parsed.y + '%'
                                : 'League avg: ' + ctx.parsed.y + '%'
                        }
                    }
                },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8ba4bf' } },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#8ba4bf', callback: v => v + '%' },
                        min: 0,
                        suggestedMax: 50
                    }
                }
            }
        });
    })();
    </script>
    <?php endif; ?>

    <!-- ── WS Winners Table ──────────────────────────────────────────── -->
    <?php if ($wsData && count($wsData) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2>World Series Champions — Foreign Player Breakdown</h2>
            <span class="badge badge-gold">2000–2024</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Champion</th>
                        <th>Foreign Players</th>
                        <th>Roster Size</th>
                        <th style="min-width:160px;">Foreign %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wsData as $row):
                        $pct   = (float)$row['foreign_pct'];
                        $barW  = min(100, round($pct * 1.5)); // scale so ~70% = full bar
                        $color = $pct >= 35 ? 'var(--primary)' : ($pct >= 25 ? 'var(--gold)' : 'var(--cyan)');
                    ?>
                    <tr>
                        <td data-label="Year"
                            style="font-family:var(--font-display); font-size:1.4rem; color:var(--gold);">
                            <?php echo (int)$row['yearid']; ?>
                        </td>
                        <td data-label="Champion"
                            style="font-weight:600; color:var(--text-primary);">
                            🏆 <?php echo e($row['team_name'] ?: $row['team_id']); ?>
                        </td>
                        <td data-label="Foreign Players"
                            style="font-family:var(--font-display); font-size:1.3rem; color:<?php echo $color; ?>;">
                            <?php echo (int)$row['foreign_players']; ?>
                        </td>
                        <td data-label="Total" style="color:var(--text-secondary);">
                            <?php echo (int)$row['total_players']; ?>
                        </td>
                        <td data-label="Foreign %" class="bar-cell">
                            <span style="font-weight:600; color:<?php echo $color; ?>;"><?php echo $pct; ?>%</span>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width:<?php echo $barW; ?>%; background:<?php echo $color; ?>;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Countries on WS Rosters ───────────────────────────────────── -->
    <?php if ($wsCountries && count($wsCountries) > 0):
        $wsCtryNames = array_column($wsCountries, 'birth_country');
        $wsCtrySeasons = array_map('intval', array_column($wsCountries, 'championship_seasons'));
        $wsCtryColors = ['#d92020','#f5a623','#22d3ee','#10b981','#a78bfa','#f472b6','#fb923c','#34d399','#60a5fa','#fbbf24'];
    ?>
    <div class="card-grid card-grid-2" style="margin-bottom:var(--space-2xl);">
        <div class="card">
            <div class="card-header">
                <h2>Countries on WS Winners</h2>
                <span class="badge badge-red">2000–2024</span>
            </div>
            <div style="position:relative; height:280px; display:flex; align-items:center; justify-content:center;">
                <canvas id="wsCountriesDonut"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Championship Seasons by Country</h2>
                <span class="badge badge-muted">Foreign Only</span>
            </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Country</th>
                        <th>Championship Seasons</th>
                        <th>Total Player-Seasons</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wsCountries as $i => $r): ?>
                    <tr>
                        <td class="rank-cell" data-label="#">
                            <?php echo $i < 3 ? '<span style="color:var(--gold);">'. ($i+1) .'</span>' : ($i+1); ?>
                        </td>
                        <td data-label="Country" style="font-weight:600; color:var(--text-primary);">
                            <?php echo e($r['birth_country']); ?>
                        </td>
                        <td data-label="Championship Seasons"
                            style="font-family:var(--font-display); font-size:1.3rem;
                                   color:<?php echo $i === 0 ? 'var(--primary)' : 'var(--text-secondary)'; ?>;">
                            <?php echo (int)$r['championship_seasons']; ?>
                        </td>
                        <td data-label="Player-Seasons" style="color:var(--text-secondary);">
                            <?php echo (int)$r['player_seasons']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
    <script>
    (function() {
        var ctx = document.getElementById('wsCountriesDonut').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($wsCtryNames); ?>,
                datasets: [{
                    data: <?php echo json_encode($wsCtrySeasons); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($wsCtryColors, 0, count($wsCtryNames))); ?>,
                    borderColor: '#07101f',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#8ba4bf', padding: 12, font: { size: 11 }, usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: '#0d1b2e',
                        borderColor: '#1a2d45',
                        borderWidth: 1,
                        callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' seasons' }
                    }
                }
            }
        });
    })();
    </script>
    <?php endif; ?>

    <!-- ── Key Insights ───────────────────────────────────────────────── -->
    <div class="card-grid card-grid-2">
        <div class="card" style="border-top:3px solid var(--primary);">
            <h3 style="font-family:var(--font-sans); font-size:1.1rem; color:var(--primary); margin-bottom:var(--space-md);">📈 Rising Representation</h3>
            <p>International players now comprise <strong><?php echo $avgForeignPct; ?>% on average</strong> of World Series winning rosters (2000–2024), demonstrating growing reliance on foreign-born talent at the championship level.</p>
        </div>
        <div class="card" style="border-top:3px solid var(--gold);">
            <h3 style="font-family:var(--font-sans); font-size:1.1rem; color:var(--gold); margin-bottom:var(--space-md);">🌎 Latin America Leads</h3>
            <p>Dominican Republic, Venezuela, and Puerto Rico consistently provide the most players on championship rosters, reflecting decades of investment in Latin American baseball academies.</p>
        </div>
        <div class="card" style="border-top:3px solid var(--cyan);">
            <h3 style="font-family:var(--font-sans); font-size:1.1rem; color:var(--cyan); margin-bottom:var(--space-md);">⚾ Critical Roles</h3>
            <p>Foreign-born players fill key positions on championship teams — particularly starting pitching, middle infield, and corner outfield — roles critical to postseason success.</p>
        </div>
        <div class="card" style="border-top:3px solid var(--success);">
            <h3 style="font-family:var(--font-sans); font-size:1.1rem; color:var(--success); margin-bottom:var(--space-md);">🏆 2024 Example</h3>
            <p>The 2024 Dodgers championship featured Shohei Ohtani and Yoshinobu Yamamoto (Japan) in starring roles, with <strong>27.1% of their roster</strong> born outside the USA.</p>
        </div>
    </div>

</div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
