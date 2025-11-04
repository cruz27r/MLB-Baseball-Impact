<?php
/**
 * Class-Compliant Analytics Portal - Home Page
 * 
 * Landing page with premise and navigation to datasets and reports.
 */
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>Foreign Players in the MLB</h1>
        <p class="lead">Data-Driven Analysis of International Impact on Baseball</p>
    </div>
</section>

<!-- Introduction Section: Image Left, Description Right -->
<section class="container section-spacing">
    <div class="two-column-layout">
        <div class="column-left">
            <div class="image-container">
                <img src="/assets/img/baseball-globe.svg" alt="Global Baseball" class="feature-image">
                <div class="stat-overlay">
                    <div class="stat-item">
                        <span class="stat-number">~30%</span>
                        <span class="stat-label">Foreign Players</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="column-right">
            <h2 class="section-title">The Global Impact on America's Pastime</h2>
            <div class="content-text">
                <p>
                    Major League Baseball has long been considered America's pastime, operated and managed by a USA-based organization. However, as time has progressed, the sport has evolved into a truly international phenomenon.
                </p>
                <p>
                    Players from dozens of countries now compete at the highest level, bringing diverse skills, perspectives, and playing styles that have fundamentally transformed the game.
                </p>
                <div class="highlight-box">
                    <p><strong>Research Question:</strong> Despite MLB being a USA-run company, is the sport's success and impact most driven by foreign-born players?</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Evidence Section: Image Left, Analysis Right -->
<section class="container section-spacing">
    <div class="two-column-layout reverse">
        <div class="column-left">
            <div class="image-container">
                <div class="player-showcase">
                    <div class="player-card">
                        <h3>⚾ 2024 World Series</h3>
                        <p class="player-name">Shohei Ohtani</p>
                        <p class="player-detail">Two-Way Superstar • Japan</p>
                    </div>
                    <div class="player-card">
                        <p class="player-name">Yoshinobu Yamamoto</p>
                        <p class="player-detail">Elite Pitcher • Japan</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="column-right">
            <h2 class="section-title">Recent Championship Impact</h2>
            <div class="content-text">
                <p>
                    Recent evidence speaks volumes. The Los Angeles Dodgers captured the 2024 World Series championship, powered significantly by international talent.
                </p>
                <p>
                    <strong>Shohei Ohtani</strong>, the two-way superstar from Japan, and <strong>Yoshinobu Yamamoto</strong>, the elite starting pitcher also from Japan, were instrumental in bringing the trophy home.
                </p>
                <p>
                    Their performances exemplify how foreign-born players are not just participating—they're leading, dominating, and redefining what excellence looks like in Major League Baseball.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Navigation Cards -->
<section class="container section-spacing">
    <h2 class="section-title center">Explore the Data</h2>
    <nav class="card-grid">
        <a class="card interactive-card" href="/datasets.php">
            <div class="card-icon">📊</div>
            <h3>Browse Datasets</h3>
            <p>Explore MLB player data with advanced filtering, sorting, and statistical analysis.</p>
        </a>
        <a class="card interactive-card" href="/reports/final.php">
            <div class="card-icon">📄</div>
            <h3>View Final Report</h3>
            <p>Executive summary with methodology, key findings, and reproducible visualizations.</p>
        </a>
    </nav>
</section>

<!-- Analysis Scope Section: Table Left, Description Right -->
<section class="container section-spacing">
    <div class="two-column-layout">
        <div class="column-left">
            <div class="data-preview">
                <h4>Analysis Coverage</h4>
                <table class="mini-table">
                    <tr>
                        <td><strong>Time Period</strong></td>
                        <td>1871 - Present</td>
                    </tr>
                    <tr>
                        <td><strong>Data Points</strong></td>
                        <td>Millions of records</td>
                    </tr>
                    <tr>
                        <td><strong>Countries</strong></td>
                        <td>50+ nations</td>
                    </tr>
                    <tr>
                        <td><strong>Metrics</strong></td>
                        <td>WAR, Awards, Stats</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="column-right">
            <h2 class="section-title">Comprehensive Data Analysis</h2>
            <div class="content-text">
                <p>
                    This analysis examines decades of data to understand the true scope of foreign players' contributions to MLB's success, from everyday roster composition to championship-winning performances.
                </p>
                <p>
                    Using data from the SABR Lahman Database, Baseball-Reference WAR metrics, and Retrosheet records, we analyze roster trends, performance metrics, awards distribution, and championship impact.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
