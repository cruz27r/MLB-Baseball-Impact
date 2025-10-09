<?php
/**
 * CS437 MLB Global Era - Methods Page
 * 
 * Explains the methodology, data sources, and technical approach.
 */

require_once __DIR__ . '/partials/header.php';
?>

<main>
    <div class="container">
        <h1>Methodology</h1>
        <p class="lead">
            Learn about our data sources, processing pipeline, and analytical methods.
        </p>

        <section class="methodology">
            <h2>Data Sources</h2>
            <p>
                Our analysis draws from comprehensive MLB statistical databases, 
                including player demographics, performance metrics, and awards data.
            </p>

            <h2>ETL Pipeline</h2>
            <p>
                We use Python with Polars for efficient data extraction, transformation, 
                and loading (ETL). The pipeline processes raw baseball statistics and 
                prepares them for analysis and visualization.
            </p>

            <h2>Database Architecture</h2>
            <p>
                PostgreSQL materialized views enable fast querying of aggregated statistics. 
                The database schema is optimized for analytical queries across multiple dimensions.
            </p>

            <h2>Analysis Techniques</h2>
            <p>
                Statistical analysis includes trend analysis, comparative metrics, 
                and performance indicators across different time periods and player origins.
            </p>

            <h2>Technologies Used</h2>
            <ul>
                <li>PHP for web application</li>
                <li>Python with Polars for ETL</li>
                <li>PostgreSQL for data storage</li>
                <li>JavaScript for interactive visualizations</li>
                <li>CSS for styling and layout</li>
            </ul>
        </section>
    </div>
</main>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
