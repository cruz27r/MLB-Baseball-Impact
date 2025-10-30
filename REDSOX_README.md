# Red Sox-Themed Landing Page

## Overview

A modern, Squarespace-inspired landing page and analysis platform built with HTML, CSS, and PHP, featuring Boston Red Sox branding and colors.

## Features

### Issue 1: Project Setup ✅
- **Pure PHP/HTML/CSS** - No JavaScript frameworks required
- **GitHub Actions CI** - Automated PHP syntax checking and CSS validation
- **Environment Configuration** - `.env.example` with DATABASE_URL format

### Issue 2: Red Sox Color Theme ✅
**Color Palette:**
- `--redsox-red: #BD3039` - Primary brand color
- `--redsox-navy: #0D2B56` - Deep navy background
- `--redsox-white: #FFFFFF` - Pure white
- `--redsox-off: #F7F7F7` - Off-white background
- `--redsox-slate: #1F2937` - Slate text color

**Components:**
- ✅ Buttons: Primary (red), Secondary (white border on navy), Ghost
- ✅ Cards: Soft shadow, rounded-2xl, hover lift effect
- ✅ Badges: Red, Navy, and Outline variants
- ✅ Container: Responsive max-width layout
- ✅ Navbar: Sticky with 95% opacity
- ✅ Footer: Dark navy with multiple columns

### Issue 3: Landing Page with Hero ✅
**Navbar Features:**
- Sticky navigation with Red Sox navy background
- Logo on left, navigation links in center
- "Get Started" CTA button on right
- Fully responsive with mobile menu toggle

**Hero Section:**
- Oversized headline: "Purpose-built stats, Red Sox style."
- Compelling subcopy describing the platform
- Primary & secondary CTA buttons
- Gradient background (navy-to-slate) with subtle vignette
- Fully responsive text wrapping
- High contrast for accessibility (Lighthouse-ready)

### Issue 4: Tables Explorer Section ✅
**Features:**
- Dynamic table fetching from MySQL `dw` schema
- Responsive grid: 1/2/3/4 columns based on screen size
- Each card displays:
  - Table name (bold, large)
  - Row count and last updated date
  - First 6 column names as "pills"
  - Preview action button
- Alternating border colors (navy vs red)
- Hover effects with lift and shadow
- "Preview 10 rows" link opens dedicated preview page

### Issue 5: Analysis Page - Final Statistical Outcome ✅
**Route:** `/redsox-analysis.php`

**Features:**
- Executive summary card with overview
- 4 KPI tiles showing key metrics
- Interactive trend chart using Chart.js
- Full data table with sorting capability
- Export to CSV functionality
- Methodology accordion with expandable sections
- Fallback empty state if data unavailable
- Fetches from `v_roster_share` view or `dw_roster_composition` table

**Performance:**
- Optimized SQL queries with LIMIT clauses
- Indexed database access
- Efficient data rendering

### Issue 6: Database Integration & API Routes ✅
**API Endpoints:**

1. **GET `/api/redsox/tables.php`**
   - Returns all tables in `dw` schema
   - Includes: name, rowCount, updatedAt, columns[]
   - JSON response with error handling

2. **GET `/api/redsox/table-sample.php?table=TABLE_NAME&limit=10`**
   - Returns sample rows from specified table
   - Input validation with Zod-like pattern (regex)
   - Limit parameter (1-100 rows)
   - Read-only access enforced

3. **GET `/api/redsox/final-outcome.php?limit=1000`**
   - Returns final statistical outcome data
   - Tries `v_roster_share` view first, falls back to table
   - Summary statistics included
   - Limit parameter (1-10000 rows)

**Security:**
- Input validation on all parameters
- Prepared SQL statements to prevent injection
- Read-only database access
- Error messages don't expose sensitive info
- CORS headers for API access

### Issue 7: Squarespace-like Sectioning ✅
**Layout Features:**
- Alternating backgrounds: dark navy → off-white → dark navy
- Each section has consistent padding (4rem top/bottom)
- Section titles with oversized typography
- Supporting subtitles with max-width for readability
- Red CTA band: full-width, white text, centered content
- Visual rhythm balanced on mobile & desktop
- Smooth transitions between sections

**CTA Band:**
- Red background (#BD3039)
- White text with high contrast
- Large heading: "View the Final Analysis →"
- Supporting text explaining the action
- White button with red text (inverted style)

### Issue 8: SEO & Analytics ✅
**Meta Tags:**
- Title with page-specific content
- Description meta tag (155 characters)
- Keywords meta tag
- Author meta tag
- Open Graph tags (og:type, og:title, og:description, og:site_name)

**Schema.org JSON-LD:**
- WebSite schema with name, URL, description
- BreadcrumbList schema for navigation
- Proper structured data format
- Valid JSON-LD syntax

**Additional SEO:**
- Semantic HTML5 elements
- Proper heading hierarchy (h1 → h2 → h3)
- Alt text ready for images
- Mobile-responsive viewport meta tag
- Favicon configured

**Analytics:**
- Ready for Google Analytics 4 integration
- Ready for Vercel Analytics
- Ready for Umami Analytics
- Event tracking points identified in code

### Issue 9: Deployment Configuration ✅
**Files Created:**
- `.github/workflows/php-lint.yml` - CI/CD pipeline
- `.env.example` - Environment configuration template
- Documentation in this README

**Deployment Options:**

1. **Traditional PHP Hosting (Recommended)**
   ```bash
   # Upload to any PHP 7.4+ hosting
   # Configure .env with database credentials
   # Point web root to /public directory
   ```

2. **Vercel (Serverless PHP)**
   ```bash
   # Install Vercel CLI
   npm i -g vercel
   
   # Deploy
   vercel --prod
   
   # Add environment variables in Vercel dashboard
   ```

3. **Docker**
   ```bash
   # Use PHP + Apache/Nginx image
   # Mount /public as web root
   # Configure DATABASE_URL env var
   ```

**Environment Variables for Production:**
```bash
MLB_DB_HOST=your-production-db-host
MLB_DB_PORT=3306
MLB_DB_NAME=mlb
MLB_DB_USER=readonly_user
MLB_DB_PASS=secure_password
```

## File Structure

```
public/
├── redsox-landing.php          # Main landing page
├── redsox-analysis.php         # Final analysis page
├── redsox-table-preview.php    # Table data preview
├── assets/
│   └── css/
│       └── redsox.css          # Red Sox theme stylesheet
└── api/
    └── redsox/
        ├── tables.php          # List all tables API
        ├── table-sample.php    # Sample table data API
        └── final-outcome.php   # Final analysis data API

.github/
└── workflows/
    └── php-lint.yml            # CI pipeline

app/
├── db.php                      # Database connection class
└── helpers.php                 # Helper functions
```

## Quick Start

### 1. Configure Database

```bash
# Copy environment template
cp .env.example .env

# Edit .env with your MySQL credentials
nano .env
```

### 2. Ensure Database Schema

The application expects a MySQL database with a `dw` schema containing analysis tables:
- `dw_player_origin`
- `dw_roster_composition`
- `v_roster_share` (view, optional)

Run the provided SQL scripts to create these:
```bash
mysql -u root -p < sql_mysql/01_create_schemas.sql
mysql -u root -p < sql_mysql/02_create_staging.sql
mysql -u root -p < sql_mysql/04_build_dw.sql
```

### 3. Start Development Server

```bash
cd public
php -S localhost:8080
```

Visit: http://localhost:8080/redsox-landing.php

## Pages

### Landing Page (`/redsox-landing.php`)
- Hero with oversized headline
- Tables Explorer with dynamic table cards
- Feature sections with KPI tiles
- Red CTA band
- Footer with links

### Analysis Page (`/redsox-analysis.php`)
- Executive summary
- KPI dashboard
- Interactive Chart.js visualization
- Full data table with export
- Methodology accordion

### Table Preview (`/redsox-table-preview.php`)
- Table metadata display
- Schema information with column types
- Paginated sample data (10/25/50/100 rows)
- Responsive table layout

## API Endpoints

All endpoints return JSON with consistent error handling:

### List Tables
```bash
GET /api/redsox/tables.php

Response:
{
  "success": true,
  "data": [
    {
      "name": "dw_player_origin",
      "rowCount": 20435,
      "updatedAt": "2024-01-15T10:30:00+00:00",
      "columns": [
        {"name": "retro_id", "type": "text"},
        {"name": "origin", "type": "text"}
      ]
    }
  ],
  "count": 3
}
```

### Table Sample
```bash
GET /api/redsox/table-sample.php?table=dw_player_origin&limit=10

Response:
{
  "success": true,
  "table": "dw_player_origin",
  "totalRows": 20435,
  "limit": 10,
  "count": 10,
  "data": [...]
}
```

### Final Outcome
```bash
GET /api/redsox/final-outcome.php?limit=100

Response:
{
  "success": true,
  "source": "v_roster_share",
  "limit": 100,
  "summary": {
    "totalRecords": 100,
    "years": 25,
    "origins": ["USA", "Foreign", "Unknown"]
  },
  "data": [...]
}
```

## Design System

### Typography
- **Headlines:** Inter Black (900) / Semibold (600)
- **Body:** Inter Regular (400)
- **Sizes:** h1: 3-5.5rem, h2: 2.25-3rem, h3: 1.5rem, body: 1rem

### Spacing Scale
- xs: 0.25rem
- sm: 0.5rem
- md: 1rem
- lg: 1.5rem
- xl: 2rem
- 2xl: 3rem
- 3xl: 4rem
- 4xl: 6rem

### Border Radius
- sm: 0.375rem
- md: 0.5rem
- lg: 0.75rem
- xl: 1rem
- 2xl: 1.5rem

### Shadows
Cards use soft shadows with hover lift effects for depth and interactivity.

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

All modern browsers with CSS Grid, Flexbox, and CSS Custom Properties support.

## Accessibility

- ✅ Semantic HTML5
- ✅ ARIA labels where needed
- ✅ Keyboard navigation
- ✅ High contrast colors (WCAG AA compliant)
- ✅ Focus indicators
- ✅ Responsive text sizing
- ✅ Alt text ready for images

## Performance

- ✅ No external dependencies except Chart.js
- ✅ Optimized CSS with minimal specificity
- ✅ Efficient database queries with LIMIT
- ✅ Responsive images ready
- ✅ Minimal JavaScript
- ✅ Fast page load times

## Security

- ✅ Prepared SQL statements (no injection)
- ✅ Input validation on all parameters
- ✅ Read-only database access
- ✅ CORS headers properly configured
- ✅ No sensitive data in errors
- ✅ Environment variables for credentials

## Testing

Run the CI pipeline locally:

```bash
# PHP syntax check
find public app -name "*.php" -exec php -l {} \;

# Check specific files
php -l public/redsox-landing.php
php -l public/redsox-analysis.php
php -l public/redsox-table-preview.php

# Test API endpoints
curl http://localhost:8080/api/redsox/tables.php
curl "http://localhost:8080/api/redsox/table-sample.php?table=dw_player_origin&limit=5"
curl "http://localhost:8080/api/redsox/final-outcome.php?limit=100"
```

## License

MIT License - Part of MLB Baseball Impact CS437 Project

## Credits

- **Design Inspiration:** Squarespace-style modern web design
- **Colors:** Boston Red Sox official brand colors
- **Typography:** Inter font family
- **Charts:** Chart.js library
- **Data:** SABR Lahman Database, Retrosheet, Baseball-Reference
