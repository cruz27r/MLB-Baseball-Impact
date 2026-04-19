<?php
/**
 * Salaries — Salary Efficiency & Value Analysis
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Salary Efficiency';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';

$FOREIGN_CASE = "CASE
    WHEN UPPER(TRIM(COALESCE(p.birthcountry,''))) IN ('USA','UNITED STATES','UNITED STATES OF AMERICA') THEN 'USA'
    WHEN TRIM(COALESCE(p.birthcountry,'')) = '' THEN 'Unknown'
    ELSE 'Foreign'
END";

// ── Overall salary by origin ─────────────────────────────────────────────
[$originSalary] = safeQuery("
    SELECT
        $FOREIGN_CASE AS origin,
        COUNT(DISTINCT s.playerid) AS players,
        ROUND(AVG(s.salary))       AS avg_salary,
        ROUND(MIN(s.salary))       AS min_salary,
        ROUND(MAX(s.salary))       AS max_salary
    FROM stg_lahman_salaries s
    JOIN stg_lahman_people p ON p.playerid = s.playerid
    WHERE s.salary > 0
    GROUP BY origin
    HAVING origin != 'Unknown'
    ORDER BY avg_salary DESC
");

// ── Decade trend by origin ───────────────────────────────────────────────
[$decadeSalary] = safeQuery("
    SELECT
        FLOOR(s.yearid/10)*10 AS decade,
        $FOREIGN_CASE          AS origin,
        COUNT(DISTINCT s.playerid) AS players,
        ROUND(AVG(s.salary))       AS avg_salary
    FROM stg_lahman_salaries s
    JOIN stg_lahman_people p ON p.playerid = s.playerid
    WHERE s.salary > 0
      AND TRIM(COALESCE(p.birthcountry,'')) != ''
    GROUP BY decade, origin
    ORDER BY decade, origin
");

// ── Top earning foreign countries ────────────────────────────────────────
[$topPayCountries] = safeQuery("
    SELECT
        COALESCE(NULLIF(TRIM(p.birthcountry),''),'Unknown') AS birth_country,
        COUNT(DISTINCT s.playerid)                           AS players,
        ROUND(AVG(s.salary))                                AS avg_salary,
        ROUND(MAX(s.salary))                                AS max_salary
    FROM stg_lahman_salaries s
    JOIN stg_lahman_people p ON p.playerid = s.playerid
    WHERE s.salary > 0
      AND UPPER(TRIM(COALESCE(p.birthcountry,''))) NOT IN ('USA','UNITED STATES','UNITED STATES OF AMERICA')
      AND TRIM(COALESCE(p.birthcountry,'')) != ''
    GROUP BY birth_country
    HAVING players >= 10
    ORDER BY avg_salary DESC
    LIMIT 10
");

// ── Compute key numbers ──────────────────────────────────────────────────
$usaAvg     = 0;
$foreignAvg = 0;
foreach ($originSalary ?: [] as $r) {
    if ($r['origin'] === 'USA')     $usaAvg     = (int)$r['avg_salary'];
    if ($r['origin'] === 'Foreign') $foreignAvg = (int)$r['avg_salary'];
}
$premiumPct = $usaAvg > 0 ? round(($foreignAvg - $usaAvg) / $usaAvg * 100, 1) : 0;
?>

<?php renderSectionHero([
    'title'      => 'Salary Efficiency',
    'subtitle'   => 'Economic value and salary analysis of international players vs domestic players',
    'background' => 'gradient',
]); ?>

<main id="main-content">
<div class="container">

    <!-- ── Summary chips ──────────────────────────────────────────────── -->
    <div style="display:flex; flex-wrap:wrap; margin-bottom:var(--space-2xl);">
        <?php
        renderMetricChip('Foreign Avg Salary', '$' . number_format($foreignAvg), 'accent');
        renderMetricChip('USA Avg Salary', '$' . number_format($usaAvg), 'info');
        renderMetricChip('Foreign Premium', ($premiumPct >= 0 ? '+' : '') . $premiumPct . '%', $premiumPct >= 0 ? 'success' : 'warning');
        renderMetricChip('Coverage', '1985–2016', 'default');
        ?>
    </div>

    <!-- ── Origin comparison ──────────────────────────────────────────── -->
    <?php if ($originSalary && count($originSalary) > 0): ?>
    <div class="card-grid card-grid-2" style="margin-bottom:var(--space-2xl);">
        <?php foreach ($originSalary as $r):
            $isFor = $r['origin'] === 'Foreign';
            $color = $isFor ? 'var(--primary)' : 'var(--cyan)';
        ?>
        <div class="stat-card">
            <div style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em; color:<?php echo $color; ?>; margin-bottom:var(--space-md); font-weight:700;">
                <?php echo $r['origin'] === 'Foreign' ? '🌍 Foreign-Born Players' : '🇺🇸 US-Born Players'; ?>
            </div>
            <div class="stat-value" style="color:<?php echo $color; ?>; font-size:2.2rem;">
                $<?php echo number_format((int)$r['avg_salary']); ?>
            </div>
            <div class="stat-label">Average Salary</div>
            <div class="stat-sub" style="margin-top:var(--space-md); display:grid; gap:var(--space-xs); font-size:0.8rem; text-align:left;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted);">Unique players</span>
                    <span style="color:var(--text-secondary);"><?php echo number_format((int)$r['players']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted);">Max salary</span>
                    <span style="color:var(--text-secondary);">$<?php echo number_format((int)$r['max_salary']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Decade trend ───────────────────────────────────────────────── -->
    <?php
    // Pivot decade data: [decade => [origin => avg_salary]]
    $decades = [];
    foreach ($decadeSalary ?: [] as $r) {
        $decades[$r['decade']][$r['origin']] = (int)$r['avg_salary'];
    }
    ksort($decades);
    ?>
    <?php if (!empty($decades)):
        $chartDecadeLabels  = [];
        $chartForeignSal    = [];
        $chartUsaSal        = [];
        foreach ($decades as $dec => $origs) {
            $chartDecadeLabels[] = $dec . 's';
            $chartForeignSal[]   = $origs['Foreign'] ?? 0;
            $chartUsaSal[]       = $origs['USA'] ?? 0;
        }
    ?>
    <div class="card">
        <div class="card-header">
            <h2>Average Salary by Decade — Foreign vs US Players</h2>
        </div>
        <div style="position:relative; height:280px; margin-bottom:var(--space-lg);">
            <canvas id="salaryDecadeChart"></canvas>
        </div>
        <script>
        (function() {
            var ctx = document.getElementById('salaryDecadeChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartDecadeLabels); ?>,
                    datasets: [
                        {
                            label: 'Foreign-Born',
                            data: <?php echo json_encode($chartForeignSal); ?>,
                            backgroundColor: 'rgba(217, 32, 32, 0.8)',
                            borderColor: '#d92020',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'US-Born',
                            data: <?php echo json_encode($chartUsaSal); ?>,
                            backgroundColor: 'rgba(34, 211, 238, 0.7)',
                            borderColor: '#22d3ee',
                            borderWidth: 1,
                            borderRadius: 4
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
                            borderColor: '#1a2d45',
                            borderWidth: 1,
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': $' + ctx.parsed.y.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8ba4bf' } },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: {
                                color: '#8ba4bf',
                                callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v/1000).toFixed(0) + 'K')
                            }
                        }
                    }
                }
            });
        })();
        </script>
        <div class="table-wrapper" style="margin-top:var(--space-md);">
            <table>
                <thead>
                    <tr>
                        <th>Decade</th>
                        <th>Foreign Avg</th>
                        <th>USA Avg</th>
                        <th>Foreign Premium</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($decades as $decade => $origs):
                        $for = $origs['Foreign'] ?? 0;
                        $usa = $origs['USA']     ?? 0;
                        $diff = $usa > 0 ? round(($for - $usa) / $usa * 100, 1) : 0;
                        $abovePar = $diff >= 0;
                    ?>
                    <tr>
                        <td data-label="Decade"
                            style="font-family:var(--font-display); font-size:1.3rem; color:var(--text-primary);">
                            <?php echo $decade; ?>s
                        </td>
                        <td data-label="Foreign"
                            style="font-family:var(--font-display); font-size:1.1rem; color:var(--primary);">
                            <?php echo $for ? '$' . number_format($for) : '—'; ?>
                        </td>
                        <td data-label="USA"
                            style="font-family:var(--font-display); font-size:1.1rem; color:var(--cyan);">
                            <?php echo $usa ? '$' . number_format($usa) : '—'; ?>
                        </td>
                        <td data-label="Premium"
                            style="font-weight:700; color:<?php echo $abovePar ? 'var(--success)' : 'var(--warning)'; ?>;">
                            <?php echo $for && $usa ? (($diff >= 0 ? '+' : '') . $diff . '%') : '—'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin-top:var(--space-md); margin-bottom:0;">
            Foreign premium = how much more (or less) foreign-born players earned vs US players in that decade.
        </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Top paying countries ──────────────────────────────────────── -->
    <?php if ($topPayCountries && count($topPayCountries) > 0):
        $maxAvg = max(array_column($topPayCountries, 'avg_salary'));
    ?>
    <div class="card">
        <div class="card-header">
            <h2>Highest-Earning Foreign Countries</h2>
            <span class="badge badge-muted">Min 10 players · 1985–2016</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Country</th>
                        <th>Players</th>
                        <th>Avg Salary</th>
                        <th>Max Salary</th>
                        <th style="min-width:140px;">Relative</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPayCountries as $i => $r):
                        $barW = $maxAvg > 0 ? round((int)$r['avg_salary'] / $maxAvg * 100) : 0;
                    ?>
                    <tr>
                        <td class="rank-cell" data-label="#">
                            <?php echo $i < 3 ? '<span style="color:var(--gold);">'. ($i+1) .'</span>' : ($i+1); ?>
                        </td>
                        <td data-label="Country" style="font-weight:600; color:var(--text-primary);">
                            <?php echo e($r['birth_country']); ?>
                        </td>
                        <td data-label="Players" style="color:var(--text-secondary);">
                            <?php echo (int)$r['players']; ?>
                        </td>
                        <td data-label="Avg Salary"
                            style="font-family:var(--font-display); font-size:1.1rem;
                                   color:<?php echo $i < 3 ? 'var(--primary)' : 'var(--text-secondary)'; ?>;">
                            $<?php echo number_format((int)$r['avg_salary']); ?>
                        </td>
                        <td data-label="Max" style="color:var(--text-muted); font-size:0.875rem;">
                            $<?php echo number_format((int)$r['max_salary']); ?>
                        </td>
                        <td data-label="Relative" class="bar-cell">
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

    <!-- ── Context note ──────────────────────────────────────────────── -->
    <div class="alert alert-info">
        <h4>Data Coverage Note</h4>
        <p>
            Lahman salary data covers 1985–2016. Modern top-tier contracts (e.g., Shohei Ohtani's $700M deal)
            are not reflected. The foreign salary premium has likely grown substantially since 2016 as elite
            international stars have commanded record-breaking contracts.
        </p>
    </div>

</div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
