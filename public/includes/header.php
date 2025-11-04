<?php
/**
 * Class-Compliant Analytics Portal - Header
 * 
 * Semantic HTML header with accessible navigation and external CSS.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Foreign players in the MLB</title>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">
                <a href="/index.php">⚾ Foreign players in the MLB</a>
            </h1>
            
            <nav class="main-nav" aria-label="Main navigation">
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/datasets.php">Datasets</a></li>
                    <li><a href="/reports/final.php">Final Report</a></li>
                    <li><a href="/ml/compare.php">ML Analysis</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <main id="main-content" class="main-content">
