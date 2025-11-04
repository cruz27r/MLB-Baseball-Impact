<?php
/**
 * Fenway Modern - Header Partial
 * 
 * Site header with navigation menu and accessibility features.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Foreign players in the MLB</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <!-- Fenway Modern CSS -->
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/diamond.css">
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="site-title">
                    <a href="/index.php">⚾ Foreign players in the MLB</a>
                </div>
                
                <button class="mobile-menu-toggle" aria-expanded="false" aria-label="Toggle navigation menu">
                    ☰ Menu
                </button>
                
                <nav class="main-nav" aria-label="Main navigation">
                    <ul>
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/datasets.php">Datasets</a></li>
                        <li><a href="/reports/final.php">Final Report</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
