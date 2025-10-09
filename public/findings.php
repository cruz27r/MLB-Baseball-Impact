<?php
/**
 * CS437 MLB Global Era - Findings Page
 * 
 * Displays key findings and insights from the data analysis.
 */

require_once __DIR__ . '/partials/header.php';
?>

<main>
    <div class="container">
        <h1>Key Findings</h1>
        <p class="lead">
            Insights from our analysis of foreign players' impact on MLB.
        </p>

        <section class="findings">
            <h2>Overview</h2>
            <p>
                Our analysis reveals significant trends in the participation and performance 
                of international players in Major League Baseball.
            </p>

            <div class="findings-grid">
                <!-- Findings content will be populated with data -->
                <div class="finding-card">
                    <h3>Awards & Recognition</h3>
                    <p>Statistics on awards won by foreign players.</p>
                </div>
                
                <div class="finding-card">
                    <h3>Performance Metrics</h3>
                    <p>Key performance indicators across different eras.</p>
                </div>
                
                <div class="finding-card">
                    <h3>Team Composition</h3>
                    <p>Changes in roster composition over time.</p>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
