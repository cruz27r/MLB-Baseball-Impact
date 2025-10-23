<?php
/**
 * Fenway Modern - Salaries Page
 * 
 * Salary efficiency analysis with placeholder for future data.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Salary Efficiency';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';
?>

<main id="main-content">
    <?php renderSectionHero([
        'title' => 'Salary Efficiency',
        'subtitle' => 'Economic impact and value analysis of international players',
        'background' => 'gradient'
    ]); ?>

    <div class="container">
        <!-- Overview -->
        <div class="card">
            <div class="card-header">
                <h2>Economic Analysis</h2>
            </div>
            <p>
                This section examines the salary efficiency of international players compared to their 
                on-field contributions. By comparing salary data with performance metrics (WAR), we can 
                identify players and regions that provide exceptional value to their teams.
            </p>
            <p>
                <strong>Note:</strong> Salary data is optional and may be incomplete. Historical salary 
                information is not available for all eras and players.
            </p>
        </div>

        <!-- Sample Metrics (Placeholder) -->
        <div class="card">
            <div class="card-header">
                <h3>Key Metrics</h3>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-md); margin-bottom: var(--space-lg);">
                <?php 
                renderMetricChip('Avg Salary/WAR', 'Pending', 'default');
                renderMetricChip('Value Leaders', 'Latin America', 'success');
                renderMetricChip('Data Coverage', '70%', 'warning');
                ?>
            </div>
            <p style="font-size: 0.875rem; color: var(--text-muted);">
                <em>Sample metrics shown. Connect salary database to see actual values.</em>
            </p>
        </div>

        <!-- Salary Efficiency Chart Placeholder -->
        <div class="card-grid card-grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>Salary vs WAR by Origin</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '💰',
                    'title' => 'Salary Data Pending',
                    'message' => 'Connect salary database to visualize salary efficiency metrics.',
                    'hint' => 'CSV/PNG output from analysis will be displayed here.'
                ]); ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Value Leaders by Country</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '📊',
                    'title' => 'Chart Placeholder',
                    'message' => 'Top value players (WAR per dollar) will be ranked here.',
                    'hint' => 'Table format with player names, countries, and efficiency scores.'
                ]); ?>
            </div>
        </div>

        <!-- Historical Trends -->
        <div class="card">
            <div class="card-header">
                <h3>Salary Trends Over Time</h3>
            </div>
            <?php renderEmptyState([
                'icon' => '📈',
                'title' => 'Timeline Analysis',
                'message' => 'Salary evolution for international vs domestic players across decades.',
                'hint' => 'Line chart showing average salaries adjusted for inflation.'
            ]); ?>
        </div>

        <!-- Methodology -->
        <div class="card">
            <div class="card-header">
                <h3>Data Sources & Methodology</h3>
            </div>
            <p>
                Salary data is sourced from publicly available databases including:
            </p>
            <ul style="line-height: 1.8; color: var(--text-secondary);">
                <li>Sean Lahman's Baseball Database (partial salary records)</li>
                <li>Baseball-Reference salary pages (where available)</li>
                <li>USA Today MLB Salary Database (recent years)</li>
            </ul>
            <p style="margin-top: var(--space-md);">
                <strong>Limitations:</strong> Historical salary data is incomplete, particularly before 1985. 
                International comparisons may be affected by varying contract structures and exchange rates.
            </p>
        </div>

        <!-- Data Status Note -->
        <div class="alert alert-info">
            <h4>Data Collection Status</h4>
            <p>
                Salary analysis is optional for this project and depends on data availability. 
                The core analysis (roster composition, performance, awards, championships) does not 
                require salary data.
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
