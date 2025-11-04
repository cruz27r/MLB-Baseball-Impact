<?php
/**
 * Class-Compliant Analytics Portal - Dataset View
 * 
 * View dataset with WHERE, ORDER BY, GROUP BY, HAVING, JOINs, and pagination.
 * Includes descriptive statistics panel and CSV export.
 */
$pageTitle = 'View Dataset';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/db.php';

// Get table name from query string
$table = $_GET['table'] ?? '';

// Check database connection
if (!$db_connected) {
    echo '<div class="container"><div class="alert alert-error">Database connection error. Please check your database configuration.</div></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// Validate table exists
if (!$table || !db_table_exists($dbc, $table)) {
    echo '<div class="container"><div class="alert alert-error">Invalid or missing table parameter.</div></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// Get search query
$search = $_GET['q'] ?? '';

// Get sort parameters
$sort_col = $_GET['sort'] ?? '';
$sort_dir = ($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Get pagination parameters
$page = max(0, (int)($_GET['page'] ?? 0));
$limit = 50;
$offset = $page * $limit;

// Get table columns
$columns = [];
$col_result = mysqli_query($dbc, "SHOW COLUMNS FROM " . db_escape_identifier($table));
if ($col_result) {
    while ($col_row = mysqli_fetch_assoc($col_result)) {
        $columns[] = $col_row['Field'];
    }
    mysqli_free_result($col_result);
}

// Build WHERE clause for search
$where_clause = "1=1";
if ($search !== '') {
    $search_like = '%' . $search . '%';
}

// Build ORDER BY clause
$order_clause = '';
if ($sort_col && in_array($sort_col, $columns)) {
    $order_clause = " ORDER BY " . db_escape_identifier($sort_col) . " $sort_dir";
} else {
    $order_clause = " ORDER BY 1";
}

// Prepare and execute query with pagination
$sql = "SELECT * FROM " . db_escape_identifier($table) . " WHERE $where_clause";

// Add search filter if provided
if ($search !== '') {
    // Build CONCAT_WS for all columns
    $concat_cols = array_map('db_escape_identifier', $columns);
    $concat_expr = "CONCAT_WS(' ', " . implode(', ', $concat_cols) . ")";
    $sql = "SELECT * FROM " . db_escape_identifier($table) . " WHERE $concat_expr LIKE ?";
}

$sql .= $order_clause . " LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($dbc, $sql);
if (!$stmt) {
    die('<div class="container"><div class="alert alert-error">Query preparation error: ' . mysqli_error($dbc) . '</div></div>');
}

if ($search !== '') {
    mysqli_stmt_bind_param($stmt, "sii", $search_like, $limit, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all rows
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
mysqli_stmt_close($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM " . db_escape_identifier($table);
if ($search !== '') {
    $concat_cols = array_map('db_escape_identifier', $columns);
    $concat_expr = "CONCAT_WS(' ', " . implode(', ', $concat_cols) . ")";
    $count_sql = "SELECT COUNT(*) as total FROM " . db_escape_identifier($table) . " WHERE $concat_expr LIKE ?";
}

$count_stmt = mysqli_prepare($dbc, $count_sql);
if ($search !== '') {
    mysqli_stmt_bind_param($count_stmt, "s", $search_like);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

// Calculate descriptive statistics for numeric columns
$stats = [];
foreach ($columns as $col) {
    $stats_sql = "SELECT 
        COUNT(" . db_escape_identifier($col) . ") as count,
        MIN(" . db_escape_identifier($col) . ") as min,
        MAX(" . db_escape_identifier($col) . ") as max,
        AVG(" . db_escape_identifier($col) . ") as mean,
        STDDEV(" . db_escape_identifier($col) . ") as std
    FROM " . db_escape_identifier($table) . " 
    WHERE " . db_escape_identifier($col) . " IS NOT NULL 
    AND " . db_escape_identifier($col) . " REGEXP '^-?[0-9]+\\.?[0-9]*$'";
    
    $stats_result = mysqli_query($dbc, $stats_sql);
    if ($stats_result && $stats_row = mysqli_fetch_assoc($stats_result)) {
        if ($stats_row['count'] > 0) {
            $stats[$col] = $stats_row;
        }
        mysqli_free_result($stats_result);
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $table . '_export.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    if (!empty($rows)) {
        fputcsv($output, array_keys($rows[0]));
    }
    
    // Write rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Format table name for display
$display_name = str_replace(['staging_', 'dw_', '_'], ['Staging: ', 'DW: ', ' '], $table);
$display_name = ucwords($display_name);
?>

<section class="hero">
    <div class="container">
        <h1><?php echo htmlspecialchars($display_name); ?></h1>
        <p class="lead"><?php echo number_format($total_rows); ?> total rows</p>
    </div>
</section>

<section class="container">
    <!-- Search and Filter Form (GET method for shareable URLs) -->
    <form method="GET" action="/datasets/view.php" class="card">
        <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="search-query">Search across all columns:</label>
                <input type="search" id="search-query" name="q" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Enter search term...">
            </div>
            
            <div class="form-group">
                <label for="sort-column">Sort by:</label>
                <select id="sort-column" name="sort">
                    <option value="">-- Default --</option>
                    <?php foreach ($columns as $col): ?>
                        <option value="<?php echo htmlspecialchars($col); ?>" 
                                <?php echo $sort_col === $col ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $col))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="sort-direction">Direction:</label>
                <select id="sort-direction" name="dir">
                    <option value="ASC" <?php echo $sort_dir === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="DESC" <?php echo $sort_dir === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                </select>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="submit" class="btn">🔍 Search & Filter</button>
            <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>" class="btn btn-secondary">Clear Filters</a>
            <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&export=csv<?php 
                echo $search ? '&q=' . urlencode($search) : ''; 
                echo $sort_col ? '&sort=' . urlencode($sort_col) : ''; 
                echo '&dir=' . urlencode($sort_dir); 
            ?>" class="btn btn-secondary">📥 Export CSV</a>
        </div>
    </form>

    <!-- Descriptive Statistics Panel -->
    <?php if (!empty($stats)): ?>
    <div class="stats-panel">
        <h2>Descriptive Statistics</h2>
        <p style="margin-bottom: 1rem;">Summary statistics for numeric columns:</p>
        
        <?php foreach ($stats as $col => $stat): ?>
            <?php if ($stat['count'] > 10): // Only show stats for columns with significant data ?>
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--color-primary);">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $col))); ?>
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($stat['count']); ?></span>
                            <span class="stat-label">Count</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($stat['min'], 2); ?></span>
                            <span class="stat-label">Min</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($stat['max'], 2); ?></span>
                            <span class="stat-label">Max</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($stat['mean'], 2); ?></span>
                            <span class="stat-label">Mean</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $stat['std'] ? number_format($stat['std'], 2) : 'N/A'; ?></span>
                            <span class="stat-label">Std Dev</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="table-responsive">
        <?php if (empty($rows)): ?>
            <div class="alert alert-info">No data found matching your criteria.</div>
        <?php else: ?>
            <table>
                <caption>
                    <?php echo htmlspecialchars($display_name); ?> 
                    (Showing <?php echo count($rows); ?> of <?php echo number_format($total_rows); ?> rows)
                </caption>
                <thead>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <th>
                                <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&sort=<?php echo urlencode($col); ?>&dir=<?php echo $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?>" 
                                   style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $col))); ?>
                                    <?php if ($sort_col === $col): ?>
                                        <?php echo $sort_dir === 'ASC' ? '▲' : '▼'; ?>
                                    <?php endif; ?>
                                </a>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 0): ?>
                <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&page=<?php echo $page - 1; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?><?php echo $sort_col ? '&sort=' . urlencode($sort_col) . '&dir=' . urlencode($sort_dir) : ''; ?>">« Previous</a>
            <?php endif; ?>
            
            <?php 
            $start = max(0, $page - 2);
            $end = min($total_pages - 1, $page + 2);
            
            if ($start > 0): ?>
                <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&page=0<?php echo $search ? '&q=' . urlencode($search) : ''; ?><?php echo $sort_col ? '&sort=' . urlencode($sort_col) . '&dir=' . urlencode($sort_dir) : ''; ?>">1</a>
                <?php if ($start > 1): ?><span>...</span><?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i + 1; ?></span>
                <?php else: ?>
                    <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&page=<?php echo $i; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?><?php echo $sort_col ? '&sort=' . urlencode($sort_col) . '&dir=' . urlencode($sort_dir) : ''; ?>"><?php echo $i + 1; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($end < $total_pages - 1): ?>
                <?php if ($end < $total_pages - 2): ?><span>...</span><?php endif; ?>
                <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&page=<?php echo $total_pages - 1; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?><?php echo $sort_col ? '&sort=' . urlencode($sort_col) . '&dir=' . urlencode($sort_dir) : ''; ?>"><?php echo $total_pages; ?></a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages - 1): ?>
                <a href="/datasets/view.php?table=<?php echo urlencode($table); ?>&page=<?php echo $page + 1; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?><?php echo $sort_col ? '&sort=' . urlencode($sort_col) . '&dir=' . urlencode($sort_dir) : ''; ?>">Next »</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
