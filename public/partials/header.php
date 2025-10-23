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
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>MLB Baseball Impact</title>
    
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
                    <a href="/index.php">⚾ MLB Baseball Impact</a>
                </div>
                
                <button class="mobile-menu-toggle" aria-expanded="false" aria-label="Toggle navigation menu">
                    ☰ Menu
                </button>
                
                <nav class="main-nav" aria-label="Main navigation">
                    <ul>
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/players.php">Players</a></li>
                        <li><a href="/performance.php">Performance</a></li>
                        <li><a href="/awards.php">Awards</a></li>
                        <li><a href="/championships.php">Championships</a></li>
                        <li><a href="/salaries.php">Salaries</a></li>
                        <li><a href="/conclusion.php">Conclusion</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
