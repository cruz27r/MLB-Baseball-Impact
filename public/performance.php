<?php
/**
 * Fenway Modern - Performance Page
 * 
 * WAR and performance metrics by origin.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Performance Analysis';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';
?>

<main id="main-content">
    <?php renderSectionHero([
        'title' => 'On-Field Performance (WAR & Wins)',
        'subtitle' => 'Analyzing contributions through Wins Above Replacement and statistical excellence',
        'background' => 'gradient'
    ]); ?>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>Measuring On-Field Impact</h2>
            <p>
                This section analyzes player performance using Wins Above Replacement (WAR) and key statistical 
                indicators. We compare contributions across different player origins to understand the relative 
                impact of international talent.
            </p>
        </div>

        <!-- Key Metrics -->
        <div class="card">
            <div class="card-header">
                <h3>Impact Index Overview</h3>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-md); margin-bottom: var(--space-lg);">
                <?php 
                renderMetricChip('Impact Index', '1.42', 'success');
                renderMetricChip('WAR Share', '34.5%', 'info');
                renderMetricChip('Roster Share', '29%', 'default');
                ?>
            </div>
            <p>
                <strong>Impact Index</strong> = WAR Share / Roster Share. Values > 1.0 indicate above-average 
                contributions relative to representation.
            </p>
        </div>

        <!-- Data Visualization Placeholders -->
        <div class="card-grid card-grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>WAR Share vs Roster Share</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '📊',
                    'title' => 'Chart Placeholder',
                    'message' => 'Comparison chart showing WAR contributions vs roster representation.',
                    'hint' => 'PNG output from analysis/out/ directory.'
                ]); ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Impact Index Over Time</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '📈',
                    'title' => 'Timeline Chart',
                    'message' => 'Historical trend of Impact Index by decade.',
                    'hint' => 'CSV data will generate line chart.'
                ]); ?>
            </div>
        </div>

        <!-- Methodology -->
        <div class="card">
            <div class="card-header">
                <h3>About WAR (Wins Above Replacement)</h3>
            </div>
            <p>
                WAR is a comprehensive metric that estimates a player's total value in wins contributed to their team. 
                It accounts for batting, baserunning, fielding, and pitching contributions relative to a replacement-level player.
            </p>
            <ul style="line-height: 1.8; color: var(--text-secondary); margin-top: var(--space-md);">
                <li><strong style="color: var(--chalk-cream);">Data Source:</strong> Baseball-Reference WAR calculations</li>
                <li><strong style="color: var(--chalk-cream);">Scope:</strong> All player-seasons from 1871 to present</li>
                <li><strong style="color: var(--chalk-cream);">Calculation:</strong> Includes offense, defense, baserunning, and pitching</li>
            </ul>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
