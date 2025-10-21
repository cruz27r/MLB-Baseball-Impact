<?php
/**
 * CS437 MLB Global Era - Header Partial
 * 
 * Site header with navigation menu.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>CS437 MLB Baseball Impact</title>
    <link rel="stylesheet" href="/assets/css/ballpark.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <h1 class="site-title">
                    <a href="/index.php">MLB Baseball Impact</a>
                </h1>
                <?php include __DIR__ . '/nav.php'; ?>
            </div>
        </div>
    </header>
