<?php
/**
 * Class-Compliant Analytics Portal - Datasets List
 * 
 * Lists available datasets with ORDER BY and pagination.
 */
$pageTitle = 'Datasets';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/db.php';

// Get list of available tables using mysqli
$tables = [];
$result = mysqli_query($dbc, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_row($result)) {
        $table_name = $row[0];
        // Filter to show only relevant tables
        if (strpos($table_name, 'staging_') === 0 || strpos($table_name, 'dw_') === 0) {
            $tables[] = $table_name;
        }
    }
    mysqli_free_result($result);
}

// Sort tables alphabetically
sort($tables);
?>

<section class="hero">
    <div class="container">
        <h1>Browse Datasets</h1>
        <p class="lead">Select a dataset to explore with filtering, sorting, and statistical analysis.</p>
    </div>
</section>

<section class="container">
    <?php if (empty($tables)): ?>
        <div class="alert alert-info">
            <strong>No datasets found.</strong><br>
            Please ensure the database is set up with staging and data warehouse tables.
            <br><br>
            Run the setup scripts:
            <pre style="background: rgba(0,0,0,0.05); padding: 1rem; border-radius: 4px; margin-top: 1rem;">
./scripts/download_lahman_sabr.sh
./scripts/load_mysql.sh analytics root '' localhost 3306
            </pre>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($tables as $table): ?>
                <?php
                // Get row count
                $count_result = mysqli_query($dbc, "SELECT COUNT(*) as cnt FROM " . db_escape_identifier($table));
                $count = 0;
                if ($count_result) {
                    $count_row = mysqli_fetch_assoc($count_result);
                    $count = number_format($count_row['cnt']);
                    mysqli_free_result($count_result);
                }
                
                // Format table name for display
                $display_name = str_replace(['staging_', 'dw_', '_'], ['Staging: ', 'DW: ', ' '], $table);
                $display_name = ucwords($display_name);
                ?>
                <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>" class="card">
                    <h3><?php echo htmlspecialchars($display_name); ?></h3>
                    <p><strong><?php echo $count; ?></strong> rows</p>
                    <p style="font-size: 0.875rem; color: var(--color-text-light);">
                        Click to view with filters and statistics
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
