# Fenway-Inspired Stadium UI Theme Guide

## Overview

This theme provides a Fenway Park-inspired visual design for the MLB Baseball Impact site, emphasizing the iconic Green Monster wall, manual scoreboard aesthetics, and Boston brick/steel architectural elements—all without using trademarked logos or copyrighted photos.

## Design Principles

### Color Palette

The theme uses CSS custom properties (variables) for consistent theming:

#### Primary Colors
- `--field-green: #0e5135` - Deep field green
- `--monster-green: #1a5e3b` - Green Monster wall color
- `--monster-darker: #12442a` - Darker shade for depth
- `--clay-brown: #9a5b3c` - Infield clay/baseline brown
- `--brick-red: #7a2f2f` - Boston brick accent

#### Scoreboard Colors
- `--scoreboard-black: #111315` - Manual scoreboard dark panels
- `--chalk-white: #f6f6ef` - Chalk-like white for text
- `--foul-pole-yellow: #f8d24a` - Iconic yellow foul pole

#### Accent Colors
- `--sky-tint: #eaf3ff` - Sky blue tint for backgrounds
- `--accent-blue: #2a5ea8` - Active states and links

### Typography

**Headings:** System font stack with condensed, bold, uppercase styling
- `system-ui, -apple-system, "Segoe UI", Roboto, Arial`
- Uppercase with letter-spacing for emphasis

**Body Text:** Clean, readable system fonts
- `system-ui, -apple-system, "Segoe UI", Roboto, Arial`

**Numbers/Scoreboard:** Monospaced tabular numerals
- `ui-monospace, "SFMono-Regular", Menlo, Consolas`
- Used for KPI values and scoreboard displays

### SVG Assets

All custom SVG assets are lightweight (<3KB each) and original:

- **fenway_silhouette.svg** - Outfield wall silhouette for hero sections
- **green_monster_panel.svg** - Wall panel texture for backgrounds
- **scoreboard_tile.svg** - Manual scoreboard tile with inset effect
- **baseball_seams.svg** - Baseball with red seams
- **brick_pattern.svg** - Boston brick pattern
- **foul_pole.svg** - Yellow foul pole accent
- **pennant_corner.svg** - Championship pennant ribbon
- **favicon.svg** - Baseball icon (32x32)

## Components

### Hero Section (.hero--fenway)

Fenway-inspired hero with outfield silhouette and optional foul pole accent:

```html
<div class="hero--fenway">
    <div class="container">
        <h1>Your Title</h1>
        <p class="lead">Your subtitle</p>
    </div>
</div>
```

**Features:**
- Sky-to-green gradient background
- Fenway outfield silhouette overlay
- Foul pole accent on right edge (≥1024px)
- Responsive text sizing

### Scoreboard Components

#### KPI Cards (.kpi-card)

Scoreboard-style statistics cards:

```html
<div class="kpi-card">
    <div class="kpi-label">Metric Name</div>
    <div class="kpi-value">42</div>
    <div class="kpi-note">Additional context</div>
</div>
```

**Styling:**
- Black scoreboard tile background
- Yellow glowing numbers
- Subtle hover lift animation (respects prefers-reduced-motion)

#### Scoreboard Tabs

For award categories or similar navigation:

```html
<div class="tabs scoreboard" data-tab-group="awards" role="tablist">
    <ul class="tab-list">
        <li role="presentation">
            <button class="tab-button scoreboard__tile" 
                    data-tab="mvp" 
                    role="tab">MVP</button>
        </li>
    </ul>
</div>
```

### Wall Components

Green Monster wall-themed sections:

```html
<div class="card wall">
    <div class="card-header wall__panel">
        <h2>Section Title</h2>
    </div>
    <p>Content here...</p>
</div>
```

### Ticket-Style Filters (.ticket)

Vintage baseball ticket aesthetic for filter forms:

```html
<div class="filters ticket">
    <h3>Filter Options</h3>
    <form>
        <!-- form fields -->
    </form>
</div>
```

**Features:**
- Perforation dots on left border
- Clay brown border
- Rounded corners

### Championship Cards

Cards with pennant corner ribbons:

```html
<div class="card championship-card">
    <h3>World Series 2024</h3>
    <p>Championship details...</p>
</div>
```

### Tables

Scorebook-style data tables:

```html
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data 1</td>
                <td>Data 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

**Features:**
- Green Monster header background
- Sky tint zebra striping
- Chalk-white gridlines
- Focus-visible outlines for accessibility

### Buttons

Styled with scoreboard tile inset effects:

```html
<button class="btn btn-primary">Primary Action</button>
<button class="btn btn-secondary">Secondary Action</button>
```

**Variants:**
- `.btn-primary` - Monster green with inset shadow
- `.btn-secondary` - Scoreboard black

## Accessibility Features

### WCAG AA Compliance

All color combinations meet WCAG AA contrast requirements:
- Text on backgrounds: 4.5:1 minimum
- Large text: 3:1 minimum
- Interactive elements: clearly distinguishable

### Keyboard Navigation

- **Tab order:** Logical flow through all interactive elements
- **Focus indicators:** High-contrast outline + subtle glow
- **Skip link:** Jump to main content (hidden until focused)
- **Arrow keys:** Navigate through tab controls

### Screen Reader Support

- Semantic HTML5 elements
- ARIA labels on navigation and tabs
- ARIA roles for tab interface
- Descriptive link text
- Alt text on all meaningful images

### Motion Preferences

Respects `prefers-reduced-motion` user setting:

```css
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```

When enabled:
- Disables parallax effects
- Removes hover animations
- Prevents flip transitions
- Uses instant scroll

## Performance Optimization

### Critical CSS

Inline critical CSS in `<head>` for first paint (~6KB):
- Color variables
- Base reset
- Header/navigation
- Skip link
- Container

### Deferred Loading

Full CSS loaded with media="print" trick:

```html
<link rel="stylesheet" href="/assets/css/ballpark.css" 
      media="print" onload="this.media='all'">
```

### Asset Optimization

- **CSS size:** 27KB (under 30KB target)
- **SVG assets:** All <3KB each
- **No external fonts:** System font stack only
- **No CDN dependencies:** All assets self-hosted
- **Favicon preload:** Critical SVG preloaded

### HTTP Requests

Minimized to essential resources:
1. HTML document
2. One CSS file
3. One JS file
4. Favicon
5. SVG assets (loaded on-demand via CSS)

## Progressive Enhancement

Site works without JavaScript:
- ✅ All content accessible
- ✅ Navigation functional
- ✅ Forms submit correctly
- ✅ Tables display properly
- ⚠️ Tabs show all content (no hiding)
- ⚠️ Mobile menu always visible

With JavaScript enabled:
- Tab switching
- Mobile menu toggle
- Smooth scrolling
- Chart rendering
- Optional parallax (if motion allowed)

## Responsive Design

### Mobile-First Approach

**Breakpoints:**
- `< 480px` - Small mobile
- `480px - 768px` - Mobile/phablet
- `768px - 1024px` - Tablet
- `≥ 1024px` - Desktop

### Layout Grid

Content constrained to 1200px max-width with responsive padding:
- Desktop: 20px horizontal padding
- Mobile: 15px horizontal padding

### Component Behavior

**Navigation:**
- Desktop: Horizontal with bullet separators
- Mobile: Vertical hamburger menu

**KPI Grid:**
- Desktop: 3 columns
- Tablet: 2 columns
- Mobile: 1 column

**Tables:**
- Horizontal scroll on overflow
- Reduced padding on mobile
- Smaller font sizes

**Hero:**
- Desktop: 3.5rem heading + foul pole
- Tablet: 2.5rem heading
- Mobile: 1.75rem heading, no foul pole

## Browser Support

Tested and supported:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

Graceful degradation for older browsers:
- CSS variables fallback to defaults
- System fonts always available
- No critical feature dependencies

## Legal/Attribution

**Important:** This theme is inspired by classic ballpark architecture but:
- ❌ No team names used
- ❌ No trademarked logos
- ❌ No copyrighted photos
- ❌ No official branding
- ✅ Original SVG artwork only
- ✅ Generic "ballpark" terminology

Footer includes attribution:
> Design inspired by classic ballpark architecture. No team logos or copyrighted imagery used.

## Usage Example

Complete page structure:

```html
<?php
$pageTitle = 'Your Page';
include __DIR__ . '/partials/header.php';
?>

<main id="main-content">
    <div class="hero--fenway">
        <div class="container">
            <h1>Page Title</h1>
            <p class="lead">Subtitle text</p>
        </div>
    </div>

    <div class="container">
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Foreign Players</div>
                <div class="kpi-value">28%</div>
                <div class="kpi-note">Since 2020</div>
            </div>
        </div>

        <div class="card">
            <h2>Section Heading</h2>
            <p>Your content here...</p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
```

## Maintenance

### Adding New Colors

Add to `:root` in `ballpark.css`:

```css
:root {
    --your-color: #hexvalue;
}
```

### Creating New Components

Follow BEM naming convention:
- `.block` - Standalone component
- `.block__element` - Child of block
- `.block--modifier` - Variant of block

### Testing Checklist

- [ ] Mobile responsiveness (375px width)
- [ ] Tablet layout (768px width)
- [ ] Desktop layout (1280px+ width)
- [ ] Keyboard navigation (Tab, Arrow keys)
- [ ] Screen reader compatibility
- [ ] Color contrast (WCAG AA)
- [ ] Reduced motion preference
- [ ] Without JavaScript
- [ ] Print styles

## Resources

- WCAG 2.1 Guidelines: https://www.w3.org/WAI/WCAG21/quickref/
- System Font Stack: https://systemfontstack.com/
- MDN Accessibility: https://developer.mozilla.org/en-US/docs/Web/Accessibility
- Can I Use: https://caniuse.com/

---

**Version:** 1.0  
**Last Updated:** October 2025  
**License:** MIT (for theme code, not data)
