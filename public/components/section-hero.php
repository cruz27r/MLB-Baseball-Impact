<?php
/**
 * Section Hero Banner Component
 * 
 * Large hero banner for section pages with title, subtitle, and optional CTA.
 * 
 * Usage:
 * include 'components/section-hero.php';
 * renderSectionHero([
 *     'title' => 'Performance Analysis',
 *     'subtitle' => 'WAR contributions and impact metrics',
 *     'background' => 'gradient' // or 'image', 'solid'
 * ]);
 */

function renderSectionHero($options = []) {
    $defaults = [
        'title' => 'Section Title',
        'subtitle' => '',
        'background' => 'gradient',
        'class' => ''
    ];
    
    $opts = array_merge($defaults, $options);
    $heroClass = 'section-hero section-hero-' . $opts['background'] . ' ' . $opts['class'];
    ?>
    <section class="<?php echo htmlspecialchars($heroClass); ?>">
        <div class="container">
            <div class="section-hero-content">
                <h1><?php echo htmlspecialchars($opts['title']); ?></h1>
                <?php if ($opts['subtitle']): ?>
                    <p class="lead"><?php echo htmlspecialchars($opts['subtitle']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}
?>

<style>
.section-hero {
    position: relative;
    padding: var(--space-3xl) 0 var(--space-2xl);
    margin-bottom: var(--space-2xl);
    border-bottom: 3px solid var(--fenway-green);
    overflow: hidden;
}

.section-hero-gradient {
    background: linear-gradient(135deg, var(--panel) 0%, var(--bg-elevated) 100%);
}

.section-hero-solid {
    background: var(--panel);
}

.section-hero-image {
    background: var(--panel);
    background-size: cover;
    background-position: center;
}

.section-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(14, 81, 53, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 80% 50%, rgba(138, 90, 59, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.section-hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
}

.section-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    margin-bottom: var(--space-md);
    color: var(--chalk-cream);
}

.section-hero .lead {
    font-size: 1.125rem;
    color: var(--text-secondary);
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .section-hero {
        padding: var(--space-2xl) 0 var(--space-lg);
        margin-bottom: var(--space-lg);
    }
}
</style>
