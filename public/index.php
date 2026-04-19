<?php
$pageTitle = 'Home';
include __DIR__ . '/partials/header.php';
?>

<main id="main-content">

<!-- ── Hero ──────────────────────────────────────────────────────────────── -->
<section class="hero">
    <div class="container">
        <p style="font-size:0.8rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--primary); margin-bottom:var(--space-md);">
            CS437 · Data Analytics Research
        </p>
        <h1 class="display">International Players<br>Are Transforming<br><span style="color:var(--primary);">Baseball</span></h1>
        <p class="lede" style="max-width:580px; margin-top:var(--space-lg);">
            A comprehensive data-driven analysis examining how foreign-born players
            have become essential to MLB's competitive landscape and global identity.
        </p>
        <div class="cta-row">
            <a class="btn btn-primary" href="/conclusion.php">Read Full Analysis</a>
            <a class="btn" href="/players.php">Explore Players</a>
            <a class="btn" href="/datasets.php">Browse Data</a>
        </div>
    </div>
</section>

<!-- ── Key stat summary ──────────────────────────────────────────────────── -->
<section class="container" style="margin-top:var(--space-3xl);">
    <div class="card-grid-4 grid">
        <div class="stat-card">
            <div class="stat-value" style="color:var(--primary);">~30%</div>
            <div class="stat-label">Foreign Player Share</div>
            <div class="stat-sub">of current MLB rosters</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--gold);">50+</div>
            <div class="stat-label">Countries Represented</div>
            <div class="stat-sub">in MLB history</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--cyan);">1871</div>
            <div class="stat-label">Data Coverage From</div>
            <div class="stat-sub">to present day</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--success);">1.0+</div>
            <div class="stat-label">Impact Index</div>
            <div class="stat-sub">WAR outperforms roster share</div>
        </div>
    </div>
</section>

<!-- ── Key Findings ───────────────────────────────────────────────────────── -->
<section class="section container">
    <div class="section-title">
        <h2>Key Findings</h2>
        <p class="lede">Our analysis reveals the profound impact of international talent on America's pastime.</p>
    </div>

    <div class="grid grid-3">
        <div class="card" style="border-top:3px solid var(--primary);">
            <h3 style="font-size:1.2rem; font-family:var(--font-sans); color:var(--primary);">📈 Growing Representation</h3>
            <p>Foreign-born players have increased from less than 5% in the 1960s to approximately 28–30% of MLB rosters today.</p>
        </div>
        <div class="card" style="border-top:3px solid var(--gold);">
            <h3 style="font-size:1.2rem; font-family:var(--font-sans); color:var(--gold);">⚡ Performance Excellence</h3>
            <p>International players contribute more WAR than their roster share would suggest, with Impact Index consistently exceeding 1.0.</p>
        </div>
        <div class="card" style="border-top:3px solid var(--success);">
            <h3 style="font-size:1.2rem; font-family:var(--font-sans); color:var(--success);">🏆 Championship Impact</h3>
            <p>World Series champions increasingly rely on international talent, with 30–40% foreign-born players in key roles.</p>
        </div>
    </div>
</section>

<!-- ── 2024 World Series Spotlight ──────────────────────────────────────── -->
<section class="section container">
    <div class="split">
        <div class="image-container" style="aspect-ratio:4/3; background:linear-gradient(135deg,var(--panel) 0%,var(--bg-elevated) 100%); display:flex; align-items:center; justify-content:center;">
            <img src="/assets/img/baseball-globe.svg" alt="Global Baseball Impact" style="width:80%; max-width:300px; opacity:0.9;">
        </div>
        <div>
            <p style="font-size:0.75rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--gold); margin-bottom:var(--space-sm);">2024 Case Study</p>
            <h2>World Series: A Global Showcase</h2>
            <p>
                The Los Angeles Dodgers' 2024 championship exemplifies the international transformation.
                <strong>Shohei Ohtani</strong> (Japan) and <strong>Yoshinobu Yamamoto</strong> (Japan)
                were instrumental in bringing the trophy home.
            </p>
            <p>Their performances demonstrate how foreign-born players are not just participating — they're leading, dominating, and redefining excellence in baseball.</p>
            <a href="/conclusion.php" class="btn btn-primary">Read the Full Report →</a>
        </div>
    </div>
</section>

<!-- ── Research Question ─────────────────────────────────────────────────── -->
<section class="section container">
    <div class="split">
        <div>
            <p style="font-size:0.75rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--primary); margin-bottom:var(--space-sm);">Research Question</p>
            <h2>The Central Thesis</h2>
            <p class="lede" style="max-width:none;">
                Despite MLB being a USA-run organization, is the sport's success
                most driven by foreign-born players?
            </p>
            <p>
                This analysis examines decades of data from the SABR Lahman Database,
                Baseball-Reference WAR metrics, and Retrosheet records.
            </p>
            <ul style="list-style:none; padding:0; margin:var(--space-lg) 0; display:grid; gap:var(--space-sm);">
                <li style="color:var(--text-secondary);">✓ &nbsp;Roster composition trends over time</li>
                <li style="color:var(--text-secondary);">✓ &nbsp;Performance metrics and WAR analysis</li>
                <li style="color:var(--text-secondary);">✓ &nbsp;Awards and recognition patterns</li>
                <li style="color:var(--text-secondary);">✓ &nbsp;Championship team compositions</li>
            </ul>
        </div>
        <div class="stat-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:280px;">
            <div class="stat-value" style="font-size:5rem; color:var(--primary);">~30%</div>
            <div class="stat-label" style="font-size:1rem; margin-bottom:var(--space-sm);">Current Foreign Player Share</div>
            <div class="stat-sub">Representing 50+ countries worldwide</div>
        </div>
    </div>
</section>

<!-- ── Top Regions ───────────────────────────────────────────────────────── -->
<section class="section container">
    <div class="section-title">
        <h2>Top Contributing Regions</h2>
    </div>
    <div class="grid grid-4">
        <div class="card" style="text-align:center; border-top:3px solid var(--primary);">
            <div style="font-size:2.5rem; margin-bottom:var(--space-sm);">🇩🇴</div>
            <h3 style="font-family:var(--font-sans); font-size:1rem; color:var(--text-primary);">Dominican Republic</h3>
            <p style="font-size:0.875rem;">Highest per-capita MLB representation with consistent All-Star contributors.</p>
        </div>
        <div class="card" style="text-align:center; border-top:3px solid var(--gold);">
            <div style="font-size:2.5rem; margin-bottom:var(--space-sm);">🇻🇪</div>
            <h3 style="font-family:var(--font-sans); font-size:1rem; color:var(--text-primary);">Venezuela</h3>
            <p style="font-size:0.875rem;">Strong pitching tradition and elite defensive talent.</p>
        </div>
        <div class="card" style="text-align:center; border-top:3px solid var(--cyan);">
            <div style="font-size:2.5rem; margin-bottom:var(--space-sm);">🇯🇵</div>
            <h3 style="font-family:var(--font-sans); font-size:1rem; color:var(--text-primary);">Japan</h3>
            <p style="font-size:0.875rem;">Elite starting pitchers and position players transforming the game.</p>
        </div>
        <div class="card" style="text-align:center; border-top:3px solid var(--success);">
            <div style="font-size:2.5rem; margin-bottom:var(--space-sm);">🇨🇺</div>
            <h3 style="font-family:var(--font-sans); font-size:1rem; color:var(--text-primary);">Cuba</h3>
            <p style="font-size:0.875rem;">Historical excellence spanning multiple eras and positions.</p>
        </div>
    </div>
</section>

<!-- ── Data sources ──────────────────────────────────────────────────────── -->
<section class="section container">
    <div class="card" style="background:var(--panel); border-color:var(--border-strong);">
        <div class="section-title" style="margin-bottom:var(--space-xl);">
            <h2>Comprehensive Data Analysis</h2>
            <p class="lede">Built on authoritative sources and rigorous methodology</p>
        </div>
        <div class="grid grid-3">
            <div>
                <h4>📚 Data Sources</h4>
                <ul style="list-style:none; padding:0; color:var(--text-muted); line-height:2;">
                    <li>SABR Lahman Database</li>
                    <li>Baseball-Reference WAR</li>
                    <li>Retrosheet Records</li>
                </ul>
            </div>
            <div>
                <h4>🔬 Analysis Methods</h4>
                <ul style="list-style:none; padding:0; color:var(--text-muted); line-height:2;">
                    <li>Descriptive Statistics</li>
                    <li>Trend Analysis</li>
                    <li>K-means Clustering</li>
                </ul>
            </div>
            <div>
                <h4>📊 Coverage</h4>
                <ul style="list-style:none; padding:0; color:var(--text-muted); line-height:2;">
                    <li>1871 – Present</li>
                    <li>50+ Countries</li>
                    <li>Millions of Records</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA ───────────────────────────────────────────────────────────────── -->
<section class="section container text-center">
    <h2>Explore the Complete Analysis</h2>
    <p class="lede" style="max-width:520px; margin: var(--space-lg) auto var(--space-xl);">
        Dive deeper into the data and discover how international players have elevated Major League Baseball.
    </p>
    <div class="cta-row" style="justify-content:center;">
        <a href="/conclusion.php" class="btn btn-primary">View Final Report</a>
        <a href="/players.php" class="btn">Player Data</a>
        <a href="/datasets.php" class="btn">Browse Datasets</a>
    </div>
</section>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
