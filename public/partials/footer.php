<?php
/**
 * Fenway Modern - Footer Partial
 * 
 * Site footer with credits, data attribution, and links.
 */
?>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <p class="copyright">
                        &copy; <?php echo date('Y'); ?> MLB Baseball Impact Project
                    </p>
                    <p style="font-size: 0.8rem; margin-top: 0.5rem; color: var(--text-muted);">
                        <em>Design inspired by Fenway Park. No team logos or copyrighted imagery used.</em>
                    </p>
                </div>
                
                <div class="footer-attribution">
                    <p><strong>Data Sources:</strong></p>
                    <p>
                        <a href="https://sabr.org/sabermetrics/data" target="_blank" rel="noopener">Lahman Baseball Database</a> (SABR) • 
                        <a href="https://www.retrosheet.org" target="_blank" rel="noopener">Retrosheet</a> • 
                        <a href="https://www.baseball-reference.com" target="_blank" rel="noopener">Baseball-Reference</a>
                    </p>
                    <p style="font-size: 0.875rem; margin-top: 0.5rem; color: var(--text-muted);">
                        This site uses data from SABR's Lahman Database, Retrosheet game data, and Baseball-Reference.
                    </p>
                </div>
                
                <nav class="footer-nav" aria-label="Footer navigation">
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
    </footer>
    <script src="/assets/js/main.js"></script>
</body>
</html>
