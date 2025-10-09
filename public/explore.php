<?php
/**
 * CS437 MLB Global Era - Explore Page
 * 
 * Interactive data exploration page with tables and visualizations.
 */

require_once __DIR__ . '/partials/header.php';
?>

<main>
    <div class="container">
        <h1>Explore the Data</h1>
        <p class="lead">
            Interactive exploration of MLB statistics and trends.
        </p>

        <section class="data-explorer">
            <div class="filters">
                <h2>Filter Options</h2>
                <!-- Filter controls will be added here -->
                <form id="filter-form">
                    <label for="year">Year Range:</label>
                    <input type="text" id="year" name="year" placeholder="e.g., 2000-2020">
                    
                    <label for="country">Country:</label>
                    <select id="country" name="country">
                        <option value="">All Countries</option>
                        <!-- Options will be populated dynamically -->
                    </select>
                    
                    <button type="submit">Apply Filters</button>
                </form>
            </div>

            <div class="data-display">
                <h2>Results</h2>
                <div id="table-container">
                    <?php
                    // Table content will be loaded here
                    require_once __DIR__ . '/partials/table.php';
                    ?>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
