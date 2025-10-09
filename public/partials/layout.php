<?php
/**
 * CS437 MLB Global Era - Layout Wrapper
 * 
 * Base layout structure for all pages.
 */

function renderLayout($content, $title = 'MLB Baseball Impact') {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - CS437 MLB Global Era</title>
        <link rel="stylesheet" href="/assets/styles.css">
    </head>
    <body>
        <?php echo $content; ?>
        <script src="/assets/main.js"></script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
