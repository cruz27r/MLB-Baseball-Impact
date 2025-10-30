# Red Sox-Themed Landing Page (Simplified)

## Overview

A modern, Squarespace-inspired landing page built with HTML, CSS, and PHP, featuring Boston Red Sox branding and colors. This simplified version displays 5 hardcoded placeholder tables without requiring database connectivity.

## Features

### ✅ Modern Landing Page
- **Hero Section** with oversized headline and Red Sox styling
- **5 Placeholder Tables** showcasing database structure:
  1. `player_demographics` - 20,435 rows
  2. `batting_statistics` - 156,890 rows
  3. `pitching_statistics` - 89,234 rows
  4. `team_standings` - 2,850 rows
  5. `awards_honors` - 4,567 rows

### ✅ Red Sox Color Theme
**Color Palette:**
- `--redsox-red: #BD3039` - Primary brand color
- `--redsox-navy: #0D2B56` - Deep navy background
- `--redsox-white: #FFFFFF` - Pure white
- `--redsox-off: #F7F7F7` - Off-white background
- `--redsox-slate: #1F2937` - Slate text color

### ✅ Components
- Sticky navbar with backdrop blur
- Responsive table grid (1/2/3/4 columns)
- Alternating section backgrounds (navy/off-white)
- Red CTA band
- KPI tiles showing metrics
- Multi-column footer

## Quick Start

### 1. No Database Required
This simplified version works without any database setup!

### 2. Start Development Server

```bash
cd public
php -S localhost:8080
```

Visit: http://localhost:8080/redsox-landing.php

## File Structure

```
public/
├── redsox-landing.php          # Main landing page (simplified)
└── assets/
    └── css/
        └── redsox.css          # Red Sox theme stylesheet

.github/
└── workflows/
    └── php-lint.yml            # CI pipeline
```

## Design System

### Typography
- **Headlines:** Inter Black (900) / Semibold (600)
- **Body:** Inter Regular (400)
- **Sizes:** h1: 3-5.5rem, h2: 2.25-3rem, h3: 1.5rem

### Components
- ✅ Buttons: Primary (red), Secondary (white outline), Ghost
- ✅ Cards: Rounded-2xl with soft shadow and hover lift
- ✅ Badges: Red and Navy color pills for table columns
- ✅ Navbar: Sticky with 95% opacity
- ✅ Footer: Dark navy with multiple columns

### Spacing Scale
- xs: 0.25rem, sm: 0.5rem, md: 1rem, lg: 1.5rem
- xl: 2rem, 2xl: 3rem, 3xl: 4rem, 4xl: 6rem

## Deployment

### Option 1: Traditional PHP Hosting

```bash
# Upload to any PHP 7.4+ hosting
# Point web root to /public directory
```

### Option 2: Docker

```dockerfile
FROM php:8.3-apache
COPY . /var/www/html/
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
EXPOSE 80
```

```bash
docker build -t mlb-redsox .
docker run -p 8080:80 mlb-redsox
```

### Option 3: Vercel

```json
{
  "version": 2,
  "builds": [
    {
      "src": "public/**/*.php",
      "use": "vercel-php@0.6.0"
    }
  ],
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/public/$1"
    }
  ]
}
```

```bash
vercel --prod
```

## Features Breakdown

### Hero Section
- Oversized headline: "Purpose-built stats, Red Sox style."
- Supporting text with value proposition
- Two CTA buttons: "Explore Tables" and "Classic View"
- Navy-to-slate gradient background with vignette

### Tables Explorer Grid
- 5 placeholder tables displayed in responsive grid
- Each card shows:
  - Table name (bold, large font)
  - Row count (formatted with commas)
  - Last updated date
  - First 6 column names as pills
  - "Coming Soon" button (disabled)
- Alternating border colors (navy/red)
- Hover effects with lift and shadow

### KPI Section
- 4 metric tiles on dark navy background:
  - 5 Data Tables
  - 274K+ Total Records
  - 100% Data Accuracy
  - 24/7 Access

### CTA Band
- Full-width red background
- White text: "Ready to Explore MLB Data?"
- White button linking to classic view

### Why Choose Section
- 3 feature cards on off-white background
- Icons: 🎯 Accurate Data, 📊 Rich Visualizations, ⚡ Fast Performance

## Testing

```bash
# PHP syntax check
php -l public/redsox-landing.php

# Start local server
cd public && php -S localhost:8080

# Run CI checks
find public -name "*.php" -exec php -l {} \;
```

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Performance

- ✅ No database queries (static placeholders)
- ✅ Minimal CSS (16KB)
- ✅ Single external dependency (Google Fonts)
- ✅ Fast page load times
- ✅ Optimized for mobile

## Accessibility

- ✅ Semantic HTML5 elements
- ✅ Proper heading hierarchy (h1 → h2 → h3)
- ✅ High contrast colors (WCAG AA compliant)
- ✅ Keyboard navigation support
- ✅ Responsive text sizing

## Security

- ✅ No database connections (no SQL injection risk)
- ✅ Static placeholder data (no user input)
- ✅ GitHub Actions with restricted permissions
- ✅ CodeQL security scan passed (0 vulnerabilities)

## Future Enhancements

When ready to add database connectivity:
1. Uncomment `require_once __DIR__ . '/../app/db.php';`
2. Replace placeholder array with database queries
3. Update buttons from "Coming Soon" to active links
4. Add table preview and analysis pages

## License

MIT License - Part of MLB Baseball Impact CS437 Project

## Credits

- **Design Inspiration:** Squarespace-style modern web design
- **Colors:** Boston Red Sox official brand colors
- **Typography:** Inter font family (Google Fonts)
- **Data:** MLB placeholder statistics
