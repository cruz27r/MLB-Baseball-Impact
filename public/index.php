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
        <h1>Analytics Portal</h1>
        <p class="lead">Explore datasets, run filters, view descriptive statistics, and generate final reports.</p>
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
        <a class="card" href="/ml/compare.php">
            <h2>🤖 ML Analysis</h2>
            <p>K-means clustering and machine learning insights on player performance data.</p>
        </a>
    </nav>
</section>

<section class="container">
    <div class="card">
        <h2>About This Project</h2>
        <p>
            Class-compliant data analytics portal with SQL connections configured.
        </p>
        <ul style="line-height: 2; margin-left: 2rem;">
            <li><strong>Semantic HTML/CSS:</strong> Responsive design ready</li>
            <li><strong>PHP with mysqli:</strong> Connection configured (rafacruz/mlb_impact)</li>
            <li><strong>SQL Features:</strong> Queries ready for WHERE, ORDER BY, GROUP BY, HAVING, JOINs</li>
            <li><strong>Descriptive Statistics:</strong> Calculations ready for mean, median, std dev</li>
            <li><strong>Machine Learning:</strong> K-means clustering structure in place</li>
        </ul>
        <p style="margin-top: 1rem; padding: 1rem; background: rgba(44, 95, 45, 0.1); border-radius: 4px;">
            <strong>Ready for Data:</strong> Add your SQL tables to mlb_impact database to populate the pages.
        </p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
