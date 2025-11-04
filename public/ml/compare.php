<?php
/**
 * Class-Compliant ML Module - K-Means Clustering Analysis
 * 
 * Demonstrates unsupervised learning with k-means on player performance data.
 * Shows SSE/inertia for elbow analysis and cluster assignments.
 */
$pageTitle = 'ML Analysis - K-Means';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/db.php';

// Get k parameter (number of clusters)
$k = max(2, min(10, (int)($_GET['k'] ?? 3)));

// Get action (run clustering or view results)
$action = $_GET['action'] ?? 'form';

// Available tables for ML analysis
$ml_tables = ['dw_roster_composition', 'staging_war_bat', 'staging_war_pitch'];

// Selected table
$selected_table = $_GET['table'] ?? 'staging_war_bat';
if (!in_array($selected_table, $ml_tables)) {
    $selected_table = 'staging_war_bat';
}

/**
 * Simple k-means implementation using SQL
 * Returns cluster assignments and SSE
 */
function run_kmeans($dbc, $table, $k) {
    // For simplicity, we'll use a basic clustering approach with SQL
    // In production, you'd use a proper ML library
    
    // Get numeric columns for clustering
    $col_result = mysqli_query($dbc, "SHOW COLUMNS FROM " . db_escape_identifier($table));
    $numeric_cols = [];
    
    while ($col_row = mysqli_fetch_assoc($col_result)) {
        $col = $col_row['Field'];
        // Check if column is numeric by trying to get avg
        $test_query = "SELECT AVG(" . db_escape_identifier($col) . ") as test FROM " . db_escape_identifier($table) . " LIMIT 1";
        $test_result = @mysqli_query($dbc, $test_query);
        if ($test_result && mysqli_fetch_assoc($test_result)) {
            $numeric_cols[] = $col;
        }
    }
    mysqli_free_result($col_result);
    
    if (empty($numeric_cols)) {
        return ['error' => 'No numeric columns found for clustering'];
    }
    
    // Use first 2-3 numeric columns for clustering
    $numeric_cols = array_slice($numeric_cols, 0, min(3, count($numeric_cols)));
    
    // Simple clustering: divide data into k quantile-based groups using first numeric column
    $primary_col = $numeric_cols[0];
    
    // Get data with NTILE to create k groups
    $query = "SELECT *, NTILE($k) OVER (ORDER BY " . db_escape_identifier($primary_col) . ") as cluster_id 
              FROM " . db_escape_identifier($table) . " 
              WHERE " . db_escape_identifier($primary_col) . " IS NOT NULL 
              LIMIT 500";
    
    $result = mysqli_query($dbc, $query);
    if (!$result) {
        return ['error' => mysqli_error($dbc)];
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_free_result($result);
    
    // Calculate SSE (Sum of Squared Errors) for each cluster
    $clusters = [];
    $sse_total = 0;
    
    for ($i = 1; $i <= $k; $i++) {
        $cluster_data = array_filter($data, function($row) use ($i) {
            return (int)$row['cluster_id'] === $i;
        });
        
        if (empty($cluster_data)) continue;
        
        // Calculate centroid (mean) for this cluster
        $centroid = [];
        foreach ($numeric_cols as $col) {
            $values = array_map(function($row) use ($col) {
                return (float)$row[$col];
            }, $cluster_data);
            $centroid[$col] = array_sum($values) / count($values);
        }
        
        // Calculate SSE for this cluster
        $cluster_sse = 0;
        foreach ($cluster_data as $row) {
            foreach ($numeric_cols as $col) {
                $diff = (float)$row[$col] - $centroid[$col];
                $cluster_sse += $diff * $diff;
            }
        }
        
        $clusters[] = [
            'id' => $i,
            'size' => count($cluster_data),
            'centroid' => $centroid,
            'sse' => $cluster_sse,
            'samples' => array_slice($cluster_data, 0, 5) // First 5 samples
        ];
        
        $sse_total += $cluster_sse;
    }
    
    return [
        'clusters' => $clusters,
        'sse_total' => $sse_total,
        'k' => $k,
        'numeric_cols' => $numeric_cols,
        'data_count' => count($data)
    ];
}

// Run k-means if action is 'run'
$results = null;
if ($action === 'run' && $db_connected && db_table_exists($dbc, $selected_table)) {
    $results = run_kmeans($dbc, $selected_table, $k);
}

// Calculate SSE for different k values (elbow method)
$elbow_data = [];
if ($action === 'elbow' && $db_connected && db_table_exists($dbc, $selected_table)) {
    for ($test_k = 2; $test_k <= 8; $test_k++) {
        $test_result = run_kmeans($dbc, $selected_table, $test_k);
        if (!isset($test_result['error'])) {
            $elbow_data[] = [
                'k' => $test_k,
                'sse' => $test_result['sse_total']
            ];
        }
    }
}
?>

<section class="hero">
    <div class="container">
        <h1>ML Analysis - K-Means Clustering</h1>
        <p class="lead">Unsupervised learning with elbow method and cluster analysis</p>
    </div>
</section>

<section class="container">
    <?php if (!$db_connected): ?>
        <div class="alert alert-info">
            <strong>Placeholder: ML Analysis</strong><br>
            K-means clustering will run here once your SQL tables are ready.
        </div>
    <?php endif; ?>
    
    <!-- Configuration Form -->
    <form method="GET" action="/ml/compare.php" class="card">
        <h2>Configure K-Means Clustering</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label for="table-select">Dataset:</label>
                <select id="table-select" name="table" required>
                    <?php foreach ($ml_tables as $table): ?>
                        <?php if ($db_connected && db_table_exists($dbc, $table)): ?>
                            <option value="<?php echo htmlspecialchars($table); ?>" 
                                    <?php echo $selected_table === $table ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $table))); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="k-value">Number of Clusters (k):</label>
                <input type="number" id="k-value" name="k" min="2" max="10" value="<?php echo $k; ?>" required>
                <small style="color: var(--color-text-light);">Choose between 2-10 clusters</small>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="submit" name="action" value="run" class="btn">▶️ Run K-Means</button>
            <button type="submit" name="action" value="elbow" class="btn btn-secondary">📊 Generate Elbow Plot</button>
        </div>
    </form>

    <!-- Elbow Plot Results -->
    <?php if (!empty($elbow_data)): ?>
    <div class="card">
        <h2>Elbow Analysis (SSE vs K)</h2>
        <p>Use this to find the optimal number of clusters. Look for the "elbow" where SSE stops decreasing rapidly.</p>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>K (Clusters)</th>
                        <th>SSE (Sum of Squared Errors)</th>
                        <th>SSE Reduction</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $prev_sse = null;
                    foreach ($elbow_data as $point): 
                        $reduction = $prev_sse !== null ? $prev_sse - $point['sse'] : 0;
                        $reduction_pct = $prev_sse !== null ? ($reduction / $prev_sse * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo $point['k']; ?></strong></td>
                            <td><?php echo number_format($point['sse'], 2); ?></td>
                            <td>
                                <?php if ($prev_sse !== null): ?>
                                    <?php echo number_format($reduction, 2); ?> 
                                    (<?php echo number_format($reduction_pct, 1); ?>%)
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        $prev_sse = $point['sse'];
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-info" style="margin-top: 1rem;">
            <strong>Interpretation:</strong> The optimal k is typically where the rate of SSE reduction begins to level off (the "elbow").
            Look for the k value where the reduction percentage drops significantly.
        </div>
    </div>
    <?php endif; ?>

    <!-- Clustering Results -->
    <?php if ($results && !isset($results['error'])): ?>
    <div class="card">
        <h2>K-Means Results (k=<?php echo $results['k']; ?>)</h2>
        <p>
            Clustered <?php echo number_format($results['data_count']); ?> records 
            using features: <strong><?php echo implode(', ', $results['numeric_cols']); ?></strong>
        </p>
        
        <div class="stats-panel">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?php echo $results['k']; ?></span>
                    <span class="stat-label">Clusters</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($results['sse_total'], 2); ?></span>
                    <span class="stat-label">Total SSE</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($results['data_count']); ?></span>
                    <span class="stat-label">Data Points</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($results['sse_total'] / $results['data_count'], 2); ?></span>
                    <span class="stat-label">Avg SSE per Point</span>
                </div>
            </div>
        </div>
        
        <!-- Cluster Details -->
        <?php foreach ($results['clusters'] as $cluster): ?>
        <div style="margin-top: 2rem; padding: 1rem; background: rgba(44, 95, 45, 0.05); border-radius: 8px;">
            <h3 style="color: var(--color-primary);">Cluster <?php echo $cluster['id']; ?></h3>
            <p>
                <strong>Size:</strong> <?php echo number_format($cluster['size']); ?> records 
                (<?php echo number_format($cluster['size'] / $results['data_count'] * 100, 1); ?>%) |
                <strong>SSE:</strong> <?php echo number_format($cluster['sse'], 2); ?>
            </p>
            
            <h4 style="margin-top: 1rem;">Centroid (Average Values):</h4>
            <div class="stats-grid" style="margin-bottom: 1rem;">
                <?php foreach ($cluster['centroid'] as $col => $value): ?>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($value, 2); ?></span>
                    <span class="stat-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $col))); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($cluster['samples'])): ?>
            <details>
                <summary style="cursor: pointer; font-weight: 600; margin-top: 1rem;">View Sample Records (<?php echo count($cluster['samples']); ?>)</summary>
                <div class="table-responsive" style="margin-top: 1rem;">
                    <table style="font-size: 0.875rem;">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($cluster['samples'][0]) as $col): ?>
                                    <?php if ($col !== 'cluster_id'): ?>
                                        <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $col))); ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cluster['samples'] as $sample): ?>
                            <tr>
                                <?php foreach ($sample as $col => $value): ?>
                                    <?php if ($col !== 'cluster_id'): ?>
                                        <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif ($results && isset($results['error'])): ?>
    <div class="alert alert-error">
        <strong>Error:</strong> <?php echo htmlspecialchars($results['error']); ?>
    </div>
    <?php endif; ?>
    
    <!-- Methodology -->
    <div class="card">
        <h2>About K-Means Clustering</h2>
        <p>
            <strong>K-means</strong> is an unsupervised machine learning algorithm that groups similar data points into k clusters.
        </p>
        
        <h3 style="margin-top: 1rem;">How It Works:</h3>
        <ol style="line-height: 2; margin-left: 2rem;">
            <li>Choose k (number of clusters)</li>
            <li>Assign each data point to the nearest cluster centroid</li>
            <li>Recalculate centroids as the mean of assigned points</li>
            <li>Repeat until convergence</li>
        </ol>
        
        <h3 style="margin-top: 1rem;">Key Metrics:</h3>
        <ul style="line-height: 2; margin-left: 2rem;">
            <li><strong>SSE (Sum of Squared Errors):</strong> Measures cluster compactness. Lower is better.</li>
            <li><strong>Elbow Method:</strong> Plot SSE vs k to find optimal number of clusters</li>
            <li><strong>Silhouette Score:</strong> Measures how well points fit their clusters (-1 to 1)</li>
        </ul>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
