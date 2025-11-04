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
        <h1>Foreign players in the MLB</h1>
        <p class="lead">Are the foreign born players the most impactful?</p>
    </div>
</section>

<section class="container">
    <nav class="card-grid">
        <a class="card" href="/datasets.php">
            <h2>📊 Browse Datasets</h2>
            <p>Explore MLB player data with advanced filtering, sorting, and statistical analysis.</p>
        </a>
        <a class="card" href="/reports/final.php">
            <h2>📄 View Final Report</h2>
            <p>Executive summary with methodology, key findings, and reproducible visualizations.</p>
        </a>
    </nav>
</section>

<section class="container">
    <div class="card">
        <h2>The Global Impact on America's Pastime</h2>
        <p>
            Major League Baseball has long been considered America's pastime, operated and managed by a USA-based organization. However, as time has progressed, the sport has evolved into a truly international phenomenon. Players from dozens of countries now compete at the highest level, bringing diverse skills, perspectives, and playing styles that have fundamentally transformed the game.
        </p>
        <p style="margin-top: 1rem;">
            The question we explore: <strong>Despite MLB being a USA-run company, is the sport's success and impact most driven by foreign-born players?</strong>
        </p>
        <p style="margin-top: 1rem;">
            Recent evidence speaks volumes. Just days ago, the Los Angeles Dodgers captured another World Series championship, powered significantly by international talent. <strong>Shohei Ohtani</strong>, the two-way superstar from Japan, and <strong>Yoshinobu Yamamoto</strong>, the elite starting pitcher also from Japan, were instrumental in bringing the trophy home. Their performances exemplify how foreign-born players are not just participating—they're leading, dominating, and redefining what excellence looks like in Major League Baseball.
        </p>
        <p style="margin-top: 1rem;">
            This analysis examines decades of data to understand the true scope of foreign players' contributions to MLB's success, from everyday roster composition to championship-winning performances.
        </p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
