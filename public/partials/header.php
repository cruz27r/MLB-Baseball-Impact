<?php
/**
 * MLB Baseball Impact — Site Header
 * Stadium Night design system · Bebas Neue + DM Sans
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : ''; ?>MLB Baseball Impact</title>
    <meta name="description" content="Data-driven analysis examining how foreign-born players have transformed Major League Baseball.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Google Fonts: Bebas Neue (display) + DM Sans (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

    <!-- Stadium Night Design System -->
    <link rel="stylesheet" href="/assets/css/base.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    // Shared Chart.js dark-theme defaults matching Stadium Night design system
    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.color = '#8ba4bf';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.07)';
        Chart.defaults.font.family = "'DM Sans', sans-serif";
        Chart.defaults.font.size = 12;
    });
    </script>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="site-header">
        <div class="container">
            <div class="header-content">

                <div class="site-title">
                    <a href="/index.php">⚾ MLB Baseball Impact</a>
                </div>

                <button class="mobile-menu-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Toggle navigation">
                    ☰ Menu
                </button>

                <nav class="main-nav" id="main-nav" aria-label="Main navigation">
                    <ul>
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/players.php">Players</a></li>
                        <li><a href="/performance.php">Performance</a></li>
                        <li><a href="/awards.php">Awards</a></li>
                        <li><a href="/championships.php">Championships</a></li>
                        <li><a href="/salaries.php">Salaries</a></li>
                        <li><a href="/conclusion.php">Conclusion</a></li>
                        <li><a href="/datasets.php">Datasets</a></li>
                    </ul>
                </nav>

            </div>
        </div>
    </header>
