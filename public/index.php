<?php
/**
 * CS437 MLB Global Era - Home Page
 * 
 * Main landing page for the MLB Baseball Impact project.
 * Displays overview of foreign players' impact on MLB.
 */

require_once __DIR__ . '/partials/header.php';
?>

<main>
    <div class="container">
        <h1>MLB Baseball Impact: The Global Era</h1>
        <p class="lead">
            Exploring how foreign players have transformed Major League Baseball through data-driven analysis.
        </p>
        
        <section class="intro">
            <h2>Welcome</h2>
            <p>
                This project analyzes the significant contributions of international players 
                to Major League Baseball, examining their impact through comprehensive statistics, 
                awards, and performance metrics.
            </p>
        </section>

        <nav class="quick-links">
            <a href="findings.php" class="btn">View Findings</a>
            <a href="explore.php" class="btn">Explore Data</a>
            <a href="methods.php" class="btn">Our Methods</a>
        </nav>
    </div>
</main>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
