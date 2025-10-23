<?php
/**
 * Fenway Modern - Home Page
 * 
 * Landing page with baseball-diamond navigation and project introduction.
 */

require_once __DIR__ . '/../app/helpers.php';

$pageTitle = 'Home';
include __DIR__ . '/partials/header.php';
?>

<main id="main-content">
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Foreign Players, Major Impact</h1>
                <p class="lead">An evidence-driven exploration of international influence on Major League Baseball</p>
            </div>
        </div>
    </section>

    <!-- Baseball Diamond Navigation -->
    <section class="diamond-section">
        <div class="container">
            <div class="diamond-intro">
                <h2>Navigate the Analysis</h2>
                <p>Explore our research through the baseball diamond metaphor. Each base represents a key pillar of analysis.</p>
            </div>

            <div class="diamond-container">
                <div class="stadium-lights" aria-hidden="true"></div>
                
                <div class="diamond-grid">
                    <!-- First Base: Players -->
                    <a href="/players.php" class="base base-first" 
                       aria-label="First Base: Roster Composition and Player Origins"
                       tabindex="0">
                        <span class="base-number">1B</span>
                        <div class="base-title">Players</div>
                        <div class="base-subtitle">Roster & Origins</div>
                    </a>

                    <!-- Second Base: Performance -->
                    <a href="/performance.php" class="base base-second"
                       aria-label="Second Base: On-Field Performance Analysis"
                       tabindex="0">
                        <span class="base-number">2B</span>
                        <div class="base-title">Performance</div>
                        <div class="base-subtitle">WAR & Impact</div>
                    </a>

                    <!-- Third Base: Awards -->
                    <a href="/awards.php" class="base base-third"
                       aria-label="Third Base: Awards and Accolades"
                       tabindex="0">
                        <span class="base-number">3B</span>
                        <div class="base-title">Awards</div>
                        <div class="base-subtitle">MVP, Cy Young, All-Stars</div>
                    </a>

                    <!-- Home Plate: Conclusion -->
                    <a href="/conclusion.php" class="base base-home"
                       aria-label="Home Plate: Conclusion and Final Claim"
                       tabindex="0">
                        <span class="base-number">⚾</span>
                        <div class="base-title">Conclusion</div>
                        <div class="base-subtitle">The Final Claim</div>
                    </a>
                </div>
            </div>

            <!-- Outfield Supporting Sections -->
            <div class="outfield-section">
                <h3 class="outfield-title">Supporting Analysis</h3>
                <div class="outfield-cards">
                    <a href="/championships.php" class="outfield-card">
                        <span class="outfield-card-icon">🏆</span>
                        <h4 class="outfield-card-title">Championships</h4>
                        <p class="outfield-card-description">World Series contributions and postseason impact</p>
                    </a>

                    <a href="/salaries.php" class="outfield-card">
                        <span class="outfield-card-icon">💰</span>
                        <h4 class="outfield-card-title">Salaries</h4>
                        <p class="outfield-card-description">Salary efficiency and economic impact</p>
                    </a>

                    <a href="/playbyplay.php" class="outfield-card">
                        <span class="outfield-card-icon">⚡</span>
                        <h4 class="outfield-card-title">Play-by-Play</h4>
                        <p class="outfield-card-description">Game logs and key moments</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Project Introduction -->
    <section class="container">
        <div class="card">
            <div class="card-header">
                <h2>About This Project</h2>
            </div>
            <p class="lead">
                This project analyzes the profound impact of international players on Major League Baseball 
                through comprehensive data analysis spanning from the 1800s to the present day.
            </p>
            <p>
                Using data from the SABR Lahman Database, Retrosheet play-by-play records, and Baseball-Reference, 
                we examine how players from around the world have transformed America's pastime into a truly 
                global sport.
            </p>
        </div>

        <!-- Methodology Overview -->
        <div class="card">
            <div class="card-header">
                <h2>Our Approach</h2>
            </div>
            <p>We measure impact across five key dimensions:</p>
            <ul style="line-height: 2; color: var(--text-secondary);">
                <li><strong style="color: var(--chalk-cream);">Roster Composition:</strong> How player origins have evolved over time</li>
                <li><strong style="color: var(--chalk-cream);">Performance Metrics:</strong> WAR contributions and statistical excellence by origin</li>
                <li><strong style="color: var(--chalk-cream);">Awards & Recognition:</strong> MVP, Cy Young, All-Star selections by country</li>
                <li><strong style="color: var(--chalk-cream);">Championship Impact:</strong> World Series teams and their international contributors</li>
                <li><strong style="color: var(--chalk-cream);">Economic Analysis:</strong> Salary efficiency and value metrics</li>
            </ul>
        </div>
    </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

