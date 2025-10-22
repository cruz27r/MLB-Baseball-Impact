<?php
/**
 * CS437 MLB Global Era - Header Partial
 * 
 * Site header with navigation menu, critical CSS inline, and accessibility features.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>CS437 MLB Baseball Impact</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preload" href="/favicon.svg" as="image">
    
    <!-- Critical CSS inline for first paint -->
    <style>
        /* Critical CSS - Above the fold styles */
        :root {
            --field-green: #0e5135;
            --monster-green: #1a5e3b;
            --monster-darker: #12442a;
            --clay-brown: #9a5b3c;
            --brick-red: #7a2f2f;
            --scoreboard-black: #111315;
            --chalk-white: #f6f6ef;
            --foul-pole-yellow: #f8d24a;
            --sky-tint: #eaf3ff;
            --accent-blue: #2a5ea8;
            --text-primary: #f5f5f5;
            --text-dark: #2a2a2a;
            --card-bg: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: #0a3d28;
        }
        
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--monster-green);
            color: var(--chalk-white);
            padding: 8px 16px;
            text-decoration: none;
            z-index: 10000;
            font-weight: 600;
        }
        
        .skip-link:focus {
            top: 0;
            outline: 3px solid var(--foul-pole-yellow);
        }
        
        .site-header {
            background: linear-gradient(135deg, var(--scoreboard-black) 0%, var(--field-green) 100%);
            color: var(--text-primary);
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--clay-brown);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
    </style>
    
    <!-- Full CSS loaded deferred -->
    <link rel="stylesheet" href="/assets/css/ballpark.css" media="print" onload="this.media='all'; this.onload=null;">
    <noscript><link rel="stylesheet" href="/assets/css/ballpark.css"></noscript>
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
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
