<?php
/**
 * Fenway Modern - Awards Page
 * 
 * Award counts and shares by origin (MVP, Cy Young, ROY, All-Star).
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Awards Analysis';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/empty-state.php';
include __DIR__ . '/components/metric-chip.php';
?>

<main id="main-content">
    <?php renderSectionHero([
        'title' => 'Awards & Accolades',
        'subtitle' => 'MVP, Cy Young, Rookie of the Year, and All-Star selections by player origin',
        'background' => 'gradient'
    ]); ?>

    <div class="container">
        <!-- Introduction -->
        <div class="card">
            <h2>International Excellence in Recognition</h2>
            <p>
                This section tracks how players from different countries have been recognized for their 
                excellence through baseball's most prestigious awards and selections.
            </p>
        </div>

        <!-- Awards Categories -->
        <div class="card">
            <div class="card-header">
                <h3>Award Categories</h3>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-md);">
                <?php 
                renderMetricChip('MVP Awards', 'Pending', 'accent');
                renderMetricChip('Cy Young', 'Pending', 'info');
                renderMetricChip('Rookie of Year', 'Pending', 'success');
                renderMetricChip('All-Stars', 'Pending', 'warning');
                ?>
            </div>
        </div>

        <!-- Data Visualization Placeholders -->
        <div class="card-grid card-grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>Awards Share by Origin</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '🏆',
                    'title' => 'Chart Placeholder',
                    'message' => 'CSV table showing award counts by country and type.',
                    'hint' => 'Data from staging_awards_players table.'
                ]); ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Historical Trends</h3>
                </div>
                <?php renderEmptyState([
                    'icon' => '📊',
                    'title' => 'Timeline Chart',
                    'message' => 'Awards won by international players over time.',
                    'hint' => 'PNG visualization from analysis pipeline.'
                ]); ?>
            </div>
        </div>

        <!-- Top Countries -->
        <div class="card">
            <div class="card-header">
                <h3>Leading Countries by Award Type</h3>
            </div>
            <?php renderEmptyState([
                'icon' => '🌍',
                'title' => 'Data Pending',
                'message' => 'Breakdown of award-winning countries across categories.',
                'hint' => 'Connect to database to see live statistics.'
            ]); ?>
        </div>

        <!-- Methodology -->
        <div class="card">
            <div class="card-header">
                <h3>Award Categories Analyzed</h3>
            </div>
            <ul style="line-height: 2; color: var(--text-secondary);">
                <li><strong style="color: var(--chalk-cream);">Most Valuable Player (MVP):</strong> League award for best overall player</li>
                <li><strong style="color: var(--chalk-cream);">Cy Young Award:</strong> Best pitcher in each league</li>
                <li><strong style="color: var(--chalk-cream);">Rookie of the Year:</strong> Top first-year player</li>
                <li><strong style="color: var(--chalk-cream);">All-Star Selections:</strong> Mid-season recognition game</li>
                <li><strong style="color: var(--chalk-cream);">Gold Glove:</strong> Defensive excellence</li>
                <li><strong style="color: var(--chalk-cream);">Silver Slugger:</strong> Offensive excellence by position</li>
            </ul>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
