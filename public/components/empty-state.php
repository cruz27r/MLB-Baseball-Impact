<?php
/**
 * Empty State Component
 * 
 * Friendly "no data yet" message with icon and helpful text.
 * 
 * Usage:
 * include 'components/empty-state.php';
 * renderEmptyState([
 *     'icon' => '📊',
 *     'title' => 'No Data Available',
 *     'message' => 'Run the data loading scripts to populate this section.',
 *     'action' => '<a href="#" class="btn btn-primary">Load Data</a>'
 * ]);
 */

function renderEmptyState($options = []) {
    $defaults = [
        'icon' => '📊',
        'title' => 'No Data Yet',
        'message' => 'Data will appear here once available.',
        'action' => '',
        'hint' => 'Connect data sources to see analytics and visualizations.'
    ];
    
    $opts = array_merge($defaults, $options);
    ?>
    <div class="empty-state" role="status" aria-live="polite">
        <div class="empty-state-icon" aria-hidden="true">
            <?php echo $opts['icon']; ?>
        </div>
        <h3 class="empty-state-title"><?php echo htmlspecialchars($opts['title']); ?></h3>
        <p class="empty-state-message"><?php echo htmlspecialchars($opts['message']); ?></p>
        <?php if ($opts['hint']): ?>
            <p class="empty-state-hint"><?php echo htmlspecialchars($opts['hint']); ?></p>
        <?php endif; ?>
        <?php if ($opts['action']): ?>
            <div class="empty-state-action">
                <?php echo $opts['action']; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<style>
.empty-state {
    text-align: center;
    padding: var(--space-3xl) var(--space-xl);
    background: var(--panel);
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    margin: var(--space-lg) 0;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: var(--space-lg);
    opacity: 0.7;
    line-height: 1;
}

.empty-state-title {
    font-size: 1.5rem;
    color: var(--chalk-cream);
    margin-bottom: var(--space-md);
}

.empty-state-message {
    font-size: 1rem;
    color: var(--text-secondary);
    margin-bottom: var(--space-sm);
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.empty-state-hint {
    font-size: 0.875rem;
    color: var(--text-muted);
    font-style: italic;
    margin-bottom: var(--space-lg);
}

.empty-state-action {
    margin-top: var(--space-lg);
}

@media (max-width: 768px) {
    .empty-state {
        padding: var(--space-2xl) var(--space-lg);
    }
    
    .empty-state-icon {
        font-size: 3rem;
    }
    
    .empty-state-title {
        font-size: 1.25rem;
    }
}
</style>
