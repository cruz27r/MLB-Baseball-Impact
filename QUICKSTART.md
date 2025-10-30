# Quick Start Guide - Red Sox Landing Page

## Instant Setup (30 seconds)

### Step 1: Start Server
```bash
cd public
php -S localhost:8080
```

### Step 2: Open Browser
```
http://localhost:8080/redsox-landing.php
```

That's it! No configuration needed.

## What You'll See

✅ **Modern Red Sox-themed landing page**
- Hero with "Purpose-built stats, Red Sox style"
- 5 placeholder tables in responsive grid
- KPI metrics section
- Red CTA band
- Feature cards
- Professional footer

## The 5 Tables

1. **player_demographics** - Player information (20,435 rows)
2. **batting_statistics** - Batting stats (156,890 rows)
3. **pitching_statistics** - Pitching stats (89,234 rows)
4. **team_standings** - Team records (2,850 rows)
5. **awards_honors** - Awards & honors (4,567 rows)

## Customization

### Change Hero Text
Edit `public/redsox-landing.php` line 72-76:
```php
<h1 class="rs-hero-headline">Your headline here</h1>
```

### Update Table Data
Edit `public/redsox-landing.php` line 113-151:
```php
$placeholderTables = [
    [
        'name' => 'your_table',
        'rowCount' => '10,000',
        'lastUpdated' => 'Dec 1, 2024',
        'columns' => ['col1', 'col2', 'col3']
    ]
];
```

### Modify Colors
Edit `public/assets/css/redsox.css` line 23-27:
```css
:root {
    --redsox-red: #BD3039;    /* Your red */
    --redsox-navy: #0D2B56;   /* Your navy */
}
```

## Deployment

### Deploy to Production

**Shared Hosting:**
```bash
# Upload public/ directory via FTP/SFTP
# Point domain to public/redsox-landing.php
```

**Docker:**
```bash
docker build -t redsox-landing .
docker run -p 80:80 redsox-landing
```

**Vercel:**
```bash
npm i -g vercel
vercel --prod
```

## Features

✅ No database required
✅ No configuration files
✅ Works out of the box
✅ Fully responsive
✅ Red Sox colors
✅ Modern design
✅ Fast loading
✅ SEO optimized
✅ Accessible
✅ Production ready

## Support Files

- `REDSOX_README.md` - Full documentation
- `DEPLOYMENT.md` - Deployment guides
- `PROJECT_SUMMARY.md` - Technical details

## Troubleshooting

**Q: Page doesn't load?**
A: Check PHP version (`php -v`). Need 7.4+

**Q: CSS not loading?**
A: Check path: `public/assets/css/redsox.css` exists

**Q: Want to add database?**
A: See "Adding Real Database" in REDSOX_README.md

## Next Steps

1. ✅ View the page (done in 30 seconds!)
2. 📝 Customize text/colors if needed
3. 🚀 Deploy to production
4. 🎉 Share with team

---

**That's it! You now have a production-ready Red Sox landing page.**
