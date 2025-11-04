<?php
/**
 * Reseda-Style Header - Floating Navigation
 * 
 * Modern, minimalist header with sticky navigation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : ''; ?>MLB Global Impact Analysis</title>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#content" class="skip-link">Skip to main content</a>
    
    <!-- Floating Header -->
    <header class="site-header">
        <nav class="nav container">
            <a class="logo" href="/index.php">
                <span class="logo-icon">⚾</span>
                <span class="logo-text">MLB Global Impact</span>
            </a>
            <div class="nav-links">
                <a href="/index.php" class="nav-link">Home</a>
                <a href="/datasets.php" class="nav-link">Data</a>
                <a href="/reports/final.php" class="nav-link">Analysis</a>
                <a href="/contact.php" class="btn btn-small">Contact</a>
            </div>
            <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>
    
    <main id="content">
