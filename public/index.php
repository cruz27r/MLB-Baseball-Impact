<?php 
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php'; 
?>

<!-- XL Hero Section -->
<section class="hero container">
    <h1 class="display">International Players Are Transforming Baseball</h1>
    <p class="lede">
        A comprehensive data-driven analysis examining how foreign-born players have become essential 
        to Major League Baseball's competitive landscape and global identity.
    </p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/reports/final.php">Read Full Analysis</a>
        <a class="btn" href="/datasets.php">Explore Data</a>
    </div>
</section>

<!-- Key Findings Grid -->
<section class="section container">
    <div class="section-title">
        <h2>Key Findings</h2>
        <p class="lede">Our analysis reveals the profound impact of international talent on America's pastime.</p>
    </div>
    
    <div class="grid grid-3">
        <div class="card">
            <h3>📈 Growing Representation</h3>
            <p>Foreign-born players have increased from less than 5% in the 1960s to approximately 28-30% of MLB rosters today.</p>
        </div>
        <div class="card">
            <h3>⚡ Performance Excellence</h3>
            <p>International players contribute more WAR than their roster share would suggest, with Impact Index consistently exceeding 1.0.</p>
        </div>
        <div class="card">
            <h3>🏆 Championship Impact</h3>
            <p>World Series champions increasingly rely on international talent, with 30-40% foreign-born players in key roles.</p>
        </div>
    </div>
</section>

<!-- Recent Evidence: Two-Column Split -->
<section class="section container">
    <div class="split">
        <div class="image-container">
            <img src="/assets/img/baseball-globe.svg" alt="Global Baseball Impact">
        </div>
        <div>
            <h2>2024 World Series: A Global Showcase</h2>
            <p>
                The Los Angeles Dodgers' 2024 World Series championship exemplifies the international 
                transformation of baseball. <strong style="color: var(--accent);">Shohei Ohtani</strong>, 
                the revolutionary two-way superstar from Japan, and 
                <strong style="color: var(--accent);">Yoshinobu Yamamoto</strong>, 
                the elite starting pitcher also from Japan, were instrumental in bringing the trophy home.
            </p>
            <p>
                Their performances demonstrate how foreign-born players are not just participating—they're 
                leading, dominating, and redefining excellence in Major League Baseball.
            </p>
            <a href="/reports/final.php" class="btn">View Detailed Report →</a>
        </div>
    </div>
</section>

<!-- Research Question Highlight -->
<section class="section container">
    <div class="split">
        <div>
            <h2>The Research Question</h2>
            <p class="lede" style="max-width: none;">
                Despite MLB being a USA-run organization, is the sport's success and impact 
                most driven by foreign-born players?
            </p>
            <p>
                This analysis examines decades of data from the SABR Lahman Database, 
                Baseball-Reference WAR metrics, and Retrosheet records to understand the 
                true scope of foreign players' contributions to MLB's success.
            </p>
            <ul style="list-style: none; padding: 0; margin: var(--space-4) 0;">
                <li style="padding: var(--space-2) 0;">✓ Roster composition trends over time</li>
                <li style="padding: var(--space-2) 0;">✓ Performance metrics and WAR analysis</li>
                <li style="padding: var(--space-2) 0;">✓ Awards and recognition patterns</li>
                <li style="padding: var(--space-2) 0;">✓ Championship team compositions</li>
            </ul>
        </div>
        <div class="image-container" style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%); padding: var(--space-8); color: white;">
            <div style="text-align: center;">
                <div style="font-size: 4rem; font-weight: 900; color: var(--accent); margin-bottom: var(--space-4);">~30%</div>
                <h3 style="color: white; margin-bottom: var(--space-2);">Current Foreign Player Share</h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0;">Representing 50+ countries worldwide</p>
            </div>
        </div>
    </div>
</section>

<!-- Regional Contributions -->
<section class="grid-block container">
    <h2>Top Contributing Regions</h2>
    <div class="grid grid-4">
        <div class="card">
            <h3>🇩🇴 Dominican Republic</h3>
            <p>Highest per-capita MLB representation with consistent All-Star contributors.</p>
        </div>
        <div class="card">
            <h3>🇻🇪 Venezuela</h3>
            <p>Strong pitching tradition and elite defensive talent.</p>
        </div>
        <div class="card">
            <h3>🇯🇵 Japan</h3>
            <p>Elite starting pitchers and position players transforming the game.</p>
        </div>
        <div class="card">
            <h3>🇨🇺 Cuba</h3>
            <p>Historical excellence spanning multiple eras and positions.</p>
        </div>
    </div>
</section>

<!-- Data & Methodology -->
<section class="section container" style="background: var(--bg-alt); border-radius: var(--radius-lg); padding: var(--space-8);">
    <div class="section-title">
        <h2>Comprehensive Data Analysis</h2>
        <p class="lede">Built on authoritative sources and rigorous methodology</p>
    </div>
    
    <div class="grid grid-3">
        <div>
            <h3>📚 Data Sources</h3>
            <ul style="list-style: none; padding: 0; color: var(--muted);">
                <li style="padding: var(--space-2) 0;">SABR Lahman Database</li>
                <li style="padding: var(--space-2) 0;">Baseball-Reference WAR</li>
                <li style="padding: var(--space-2) 0;">Retrosheet Records</li>
            </ul>
        </div>
        <div>
            <h3>🔬 Analysis Methods</h3>
            <ul style="list-style: none; padding: 0; color: var(--muted);">
                <li style="padding: var(--space-2) 0;">Descriptive Statistics</li>
                <li style="padding: var(--space-2) 0;">Trend Analysis</li>
                <li style="padding: var(--space-2) 0;">K-means Clustering</li>
            </ul>
        </div>
        <div>
            <h3>📊 Coverage</h3>
            <ul style="list-style: none; padding: 0; color: var(--muted);">
                <li style="padding: var(--space-2) 0;">1871 - Present</li>
                <li style="padding: var(--space-2) 0;">50+ Countries</li>
                <li style="padding: var(--space-2) 0;">Millions of Records</li>
            </ul>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section container text-center">
    <h2>Explore the Complete Analysis</h2>
    <p class="lede" style="margin: var(--space-4) auto var(--space-6);">
        Dive deeper into the data and discover how international players have elevated Major League Baseball.
    </p>
    <div class="cta-row" style="justify-content: center;">
        <a href="/reports/final.php" class="btn btn-primary">View Final Report</a>
        <a href="/datasets.php" class="btn">Browse Datasets</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
