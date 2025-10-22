<?php
/**
 * Fenway Modern - Conclusion Page
 * 
 * Final claim and summary of key findings.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Conclusion';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/components/section-hero.php';
include __DIR__ . '/components/metric-chip.php';
?>

<main id="main-content">
    <?php renderSectionHero([
        'title' => 'Home Plate: The Final Claim',
        'subtitle' => 'Synthesizing our findings into a comprehensive conclusion',
        'background' => 'gradient'
    ]); ?>

    <div class="container">
        <!-- Main Thesis -->
        <div class="card" style="border-left: 4px solid var(--accent-red); background: linear-gradient(135deg, var(--panel) 0%, var(--bg-elevated) 100%);">
            <h2 style="font-size: 2rem; margin-bottom: var(--space-lg);">
                International Players Have Fundamentally Transformed Major League Baseball
            </h2>
            <p class="lead">
                Through comprehensive analysis of roster composition, performance metrics, awards, and 
                championship contributions, the data reveals that foreign-born players have become 
                essential to the sport's competitive landscape and cultural identity.
            </p>
        </div>

        <!-- Key Findings Summary -->
        <div class="card">
            <div class="card-header">
                <h2>Top Supporting Findings</h2>
            </div>
            <div style="display: grid; gap: var(--space-lg);">
                <!-- Finding 1: Roster Share -->
                <div style="padding: var(--space-lg); background: rgba(14, 81, 53, 0.1); border-radius: var(--radius-md); border-left: 3px solid var(--fenway-green);">
                    <h3 style="color: var(--fenway-green); margin-bottom: var(--space-sm);">
                        1. Growing International Representation
                    </h3>
                    <p style="margin-bottom: var(--space-md);">
                        Foreign-born players have increased from less than 5% in the 1960s to approximately 
                        28-30% of MLB rosters today. This represents a fundamental shift in the league's 
                        demographic composition.
                    </p>
                    <div>
                        <?php 
                        renderMetricChip('Current Share', '~29%', 'success');
                        renderMetricChip('Trend', 'Rising', 'info');
                        ?>
                    </div>
                </div>

                <!-- Finding 2: Performance Impact -->
                <div style="padding: var(--space-lg); background: rgba(138, 90, 59, 0.1); border-radius: var(--radius-md); border-left: 3px solid var(--infield-clay);">
                    <h3 style="color: var(--infield-clay); margin-bottom: var(--space-sm);">
                        2. Disproportionate Performance Contributions
                    </h3>
                    <p style="margin-bottom: var(--space-md);">
                        International players contribute more WAR than their roster share would suggest, 
                        indicating higher average performance levels. The Impact Index consistently exceeds 
                        1.0 for foreign-born players across recent decades.
                    </p>
                    <div>
                        <?php 
                        renderMetricChip('Impact Index', '>1.0', 'success');
                        renderMetricChip('WAR Share', '>30%', 'info');
                        ?>
                    </div>
                </div>

                <!-- Finding 3: Awards Recognition -->
                <div style="padding: var(--space-lg); background: rgba(178, 31, 45, 0.1); border-radius: var(--radius-md); border-left: 3px solid var(--accent-red);">
                    <h3 style="color: var(--accent-red); margin-bottom: var(--space-sm);">
                        3. Excellence in Awards and Recognition
                    </h3>
                    <p style="margin-bottom: var(--space-md);">
                        Foreign-born players earn MVP, Cy Young, and All-Star selections at rates proportional 
                        to or exceeding their roster representation. Latin American players, in particular, 
                        dominate certain award categories.
                    </p>
                    <div>
                        <?php 
                        renderMetricChip('MVP Awards', 'Significant', 'accent');
                        renderMetricChip('All-Star Rate', 'High', 'info');
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regional Breakdown -->
        <div class="card-grid card-grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>Regional Contributions</h3>
                </div>
                <ul style="line-height: 2; color: var(--text-secondary);">
                    <li><strong style="color: var(--chalk-cream);">Dominican Republic:</strong> Highest per-capita MLB representation</li>
                    <li><strong style="color: var(--chalk-cream);">Venezuela:</strong> Strong pitching tradition</li>
                    <li><strong style="color: var(--chalk-cream);">Japan:</strong> Elite starting pitchers and position players</li>
                    <li><strong style="color: var(--chalk-cream);">Cuba:</strong> Historical excellence across eras</li>
                    <li><strong style="color: var(--chalk-cream);">Puerto Rico:</strong> Consistent All-Star contributors</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Championship Impact</h3>
                </div>
                <p>
                    World Series champions increasingly rely on international talent. Recent championship 
                    teams feature 30-40% foreign-born players in key roles, demonstrating their importance 
                    in postseason success.
                </p>
                <div style="margin-top: var(--space-md);">
                    <?php 
                    renderMetricChip('WS Teams', 'High Intl. Share', 'success');
                    ?>
                </div>
            </div>
        </div>

        <!-- Implications -->
        <div class="card">
            <div class="card-header">
                <h2>Broader Implications</h2>
            </div>
            <p>
                The transformation of MLB into a truly global league has implications beyond statistics:
            </p>
            <ul style="line-height: 2; color: var(--text-secondary); margin-top: var(--space-md);">
                <li><strong style="color: var(--chalk-cream);">Cultural Impact:</strong> Baseball has become a bridge between nations and cultures</li>
                <li><strong style="color: var(--chalk-cream);">Economic Development:</strong> MLB success drives investment in baseball infrastructure worldwide</li>
                <li><strong style="color: var(--chalk-cream);">Competitive Balance:</strong> International talent pools deepen competition across all teams</li>
                <li><strong style="color: var(--chalk-cream);">Fan Engagement:</strong> Global stars attract international audiences and expand the sport's reach</li>
            </ul>
        </div>

        <!-- Future Outlook -->
        <div class="card">
            <div class="card-header">
                <h2>Looking Forward</h2>
            </div>
            <p>
                Current trends suggest that international representation will continue to grow. Emerging 
                markets in Asia, Europe, and Africa may produce the next generation of MLB stars, further 
                globalizing the sport.
            </p>
            <p style="margin-top: var(--space-md);">
                The evidence is clear: <strong style="color: var(--fenway-green);">foreign players have not 
                just participated in Major League Baseball—they have elevated it.</strong>
            </p>
        </div>

        <!-- Call to Action -->
        <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--fenway-green) 0%, var(--ok) 100%); border: none;">
            <h3 style="color: var(--chalk-cream); font-size: 1.5rem; margin-bottom: var(--space-md);">
                Explore the Data
            </h3>
            <p style="color: var(--chalk-cream); opacity: 0.95; margin-bottom: var(--space-lg);">
                Dive deeper into each analysis section to see the complete evidence behind these conclusions.
            </p>
            <div class="btn-group" style="justify-content: center;">
                <a href="/players.php" class="btn btn-secondary">Players Analysis</a>
                <a href="/performance.php" class="btn btn-secondary">Performance Data</a>
                <a href="/awards.php" class="btn btn-secondary">Awards Overview</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
