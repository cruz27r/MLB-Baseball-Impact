<?php
/**
 * Metric Chip Component
 * 
 * Displays a small labeled metric tag/pill.
 * 
 * Usage:
 * include 'components/metric-chip.php';
 * renderMetricChip('Impact Index', '1.42', 'success');
 * renderMetricChip('WAR Share', '34.5%', 'info');
 */

if (!function_exists('renderMetricChip')) {
    function renderMetricChip($label, $value, $variant = 'default') {
        $variantClass = 'metric-chip-' . $variant;
        ?>
        <span class="metric-chip <?php echo htmlspecialchars($variantClass); ?>" role="status">
            <span class="metric-chip-label"><?php echo htmlspecialchars($label); ?>:</span>
            <span class="metric-chip-value"><?php echo htmlspecialchars($value); ?></span>
        </span>
        <?php
    }
}
?>

<style>
.metric-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 999px;
    font-size: 0.875rem;
    white-space: nowrap;
    margin: 0.25rem;
}

.metric-chip-label {
    color: var(--text-muted);
    font-weight: 500;
}

.metric-chip-value {
    color: var(--chalk-cream);
    font-weight: 700;
}

.metric-chip-success {
    background: rgba(46, 135, 81, 0.15);
    border-color: var(--ok);
}

.metric-chip-success .metric-chip-value {
    color: var(--ok);
}

.metric-chip-warning {
    background: rgba(194, 123, 18, 0.15);
    border-color: var(--warn);
}

.metric-chip-warning .metric-chip-value {
    color: var(--warn);
}

.metric-chip-info {
    background: rgba(42, 94, 168, 0.15);
    border-color: var(--info);
}

.metric-chip-info .metric-chip-value {
    color: #5ba3e8;
}

.metric-chip-accent {
    background: rgba(178, 31, 45, 0.15);
    border-color: var(--accent-red);
}

.metric-chip-accent .metric-chip-value {
    color: var(--accent-red);
}
</style>
