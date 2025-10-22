<?php
/**
 * Reusable Card Component
 * 
 * Usage:
 * include 'partials/card.php';
 * renderCard([
 *     'title' => 'Card Title',
 *     'content' => 'Card content here...',
 *     'class' => 'additional-class',
 *     'header' => true // optional, adds header styling
 * ]);
 */

function renderCard($options = []) {
    $defaults = [
        'title' => '',
        'content' => '',
        'class' => '',
        'header' => false,
        'footer' => ''
    ];
    
    $opts = array_merge($defaults, $options);
    $cardClass = 'card ' . $opts['class'];
    ?>
    <div class="<?php echo htmlspecialchars($cardClass); ?>">
        <?php if ($opts['title'] && $opts['header']): ?>
            <div class="card-header">
                <h3><?php echo htmlspecialchars($opts['title']); ?></h3>
            </div>
        <?php elseif ($opts['title']): ?>
            <h3><?php echo htmlspecialchars($opts['title']); ?></h3>
        <?php endif; ?>
        
        <div class="card-content">
            <?php echo $opts['content']; ?>
        </div>
        
        <?php if ($opts['footer']): ?>
            <div class="card-footer">
                <?php echo $opts['footer']; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
