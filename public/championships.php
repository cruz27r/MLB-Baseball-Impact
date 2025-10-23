<?php
/**
 * Fenway Modern - Championships Page
 * 
 * World Series championship contributions by international players.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Championships';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';
?>

<main id="main-content">
    <?php renderSectionHero([
        'title' => 'Championship Contributions',
        'subtitle' => 'Analyzing international player impact in World Series victories',
        'background' => 'gradient'
    ]); ?>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>The Road to the Championship</h2>
            <p>
                This section examines the role of international players in World Series championship teams. 
                We analyze roster composition, postseason WAR contributions, and key performances that led 
                to championship victories.
            </p>
        </div>

        <!-- Key Metrics -->
        <div class="card">
            <div class="card-header">
                <h3>Championship Stats</h3>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-md); margin-bottom: var(--space-lg);">
                <?php 
                renderMetricChip('Avg Intl. Share', '~32%', 'success');
                renderMetricChip('Recent Trend', 'Increasing', 'info');
                renderMetricChip('Top Contributors', 'Latin America', 'accent');
                ?>
            </div>
            <p style="font-size: 0.875rem; color: var(--text-muted);">
                <em>Sample metrics. Connect data to see actual World Series team compositions.</em>
            </p>
        </div>

        <!-- Data Visualization Placeholders -->
        <div class="card-grid card-grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>World Series Teams Composition</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '🏆',
                    'title' => 'Chart Placeholder',
                    'message' => 'International player percentage on championship teams over time.',
                    'hint' => 'Timeline showing trend from 1950s to present.'
                ]); ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Postseason WAR Leaders</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '⭐',
                    'title' => 'Table Placeholder',
                    'message' => 'Top international players by postseason WAR contributions.',
                    'hint' => 'Ranked list with country of origin.'
                ]); ?>
            </div>
        </div>

        <!-- Recent Champions -->
        <div class="card">
            <div class="card-header">
                <h3>Recent World Series Champions (2015-2024)</h3>
            </div>
            <?php renderEmptyState([
                'icon' => '🏅',
                'title' => 'Team Analysis',
                'message' => 'Breakdown of international players on recent championship rosters.',
                'hint' => 'CSV data showing team, year, and player origins.'
            ]); ?>
        </div>

        <!-- Key Findings -->
        <div class="card">
            <div class="card-header">
                <h3>Key Insights</h3>
            </div>
            <ul style="line-height: 2; color: var(--text-secondary);">
                <li><strong style="color: var(--chalk-cream);">Rising Representation:</strong> International players comprise 30-40% of recent World Series rosters</li>
                <li><strong style="color: var(--chalk-cream);">Critical Roles:</strong> Foreign-born players often fill key positions (starting pitching, middle infield)</li>
                <li><strong style="color: var(--chalk-cream);">Postseason Excellence:</strong> International stars frequently deliver in high-pressure playoff situations</li>
                <li><strong style="color: var(--chalk-cream);">Regional Patterns:</strong> Dominican and Venezuelan players particularly prominent on championship teams</li>
            </ul>
        </div>

        <!-- Methodology -->
        <div class="card">
            <div class="card-header">
                <h3>Analysis Methodology</h3>
            </div>
            <p>
                Championship analysis combines regular season roster data with postseason performance metrics. 
                We identify international players on World Series-winning teams and measure their contributions 
                through WAR and traditional statistics.
            </p>
            <p style="margin-top: var(--space-md);">
                <strong>Data Sources:</strong> SABR Lahman Database (teams, rosters) • Baseball-Reference (postseason stats)
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
