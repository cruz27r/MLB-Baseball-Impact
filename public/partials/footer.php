<?php
/**
 * CS437 MLB Global Era - Footer Partial
 * 
 * Site footer with credits, data attribution, and links.
 */
?>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> CS437 MLB Baseball Impact Project
                </p>
                <div class="footer-attribution">
                    <p><strong>Data Sources:</strong></p>
                    <p>
                        <a href="https://sabr.org/sabermetrics/data" target="_blank" rel="noopener">Lahman Baseball Database</a> (SABR) • 
                        <a href="https://www.retrosheet.org" target="_blank" rel="noopener">Retrosheet</a> • 
                        <a href="https://www.baseball-reference.com" target="_blank" rel="noopener">Baseball-Reference</a>
                    </p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem;">
                        This site uses data from SABR's Lahman Database, Retrosheet game data, and Baseball-Reference. 
                        See our data usage policies for more information.
                    </p>
                    <p style="font-size: 0.8rem; margin-top: 1rem; color: var(--text-secondary);">
                        <em>Design inspired by classic ballpark architecture. No team logos or copyrighted imagery used.</em>
                    </p>
                </div>
                <nav class="footer-nav" aria-label="Footer Navigation">
                    <ul>
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/players.php">Players</a></li>
                        <li><a href="/performance.php">Performance</a></li>
                        <li><a href="/awards.php">Awards</a></li>
                        <li><a href="/championships.php">Championships</a></li>
                        <li><a href="/playbyplay.php">Play-by-Play</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </footer>
    <script src="/assets/js/site.js"></script>
</body>
</html>
