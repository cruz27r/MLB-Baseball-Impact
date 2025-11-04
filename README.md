# MLB-Baseball-Impact

**CS437 MLB Global Era Project**

Analyzing the impact of foreign players on Major League Baseball through comprehensive data analysis and visualization.

## Class-Compliant Analytics Portal

This project includes a **class-compliant data analytics website** that follows the taught structure with semantic HTML/CSS, PHP/mysqli integration, SQL queries, descriptive statistics, and machine learning.

### Quick Start (Analytics Portal)

```bash
# From the project root directory
cd public
php -S localhost:8080

# Open in browser
# http://localhost:8080/
```

**Database Configuration:**

The portal uses these default database credentials (can be overridden with environment variables):

```bash
DB_HOST=127.0.0.1
DB_USER=rafacruz
DB_PASS=Ricky072701
DB_NAME=mlb_impact
```

To use different credentials, set environment variables before starting the server:

```bash
export MLB_DB_HOST=127.0.0.1
export MLB_DB_USER=root
export MLB_DB_PASS=Ricky072701
export MLB_DB_NAME=mlb_impact
```

**Note:** The user can be `root` with the same password for full privileges.

### Portal Features (L1-L13 Compliant)

**Pages:**
- `/index.php` - Landing page with navigation
- `/datasets.php` - List of available datasets
- `/datasets/view.php?table=...` - Dataset viewer with filters and stats
- `/reports/final.php` - Final report with executive summary
- `/ml/compare.php` - K-means clustering analysis

**Technical Features:**
- ✅ **Semantic HTML/CSS** (L1-L6): Accessible forms, tables, responsive Grid/Flex
- ✅ **mysqli Integration** (L11): Prepared statements, mysqli_connect/close
- ✅ **SQL Features** (L11): WHERE, ORDER BY, GROUP BY, HAVING, JOINs, pagination
- ✅ **Descriptive Statistics** (L12): Count, min, max, mean, std dev, distributions
- ✅ **ML Module** (L13): K-means clustering with SSE/elbow analysis
- ✅ **Forms**: GET (shareable URLs) and POST methods with proper labels
- ✅ **CSV Export**: Download filtered datasets
- ✅ **Data Cleaning**: NULL handling, regex validation, outlier detection

### SQL Features Demonstrated

**WHERE with IS NULL:**
```sql
WHERE year_id >= 1990 AND birth_country IS NOT NULL
```

**ORDER BY with pagination:**
```sql
ORDER BY year_id DESC LIMIT 50 OFFSET 0
```

**GROUP BY with HAVING:**
```sql
SELECT birth_country, COUNT(*) as count
FROM dw_player_origin
GROUP BY birth_country
HAVING COUNT(*) >= 10
```

**JOINs:**
```sql
SELECT a.year_id, o.origin, COUNT(*)
FROM staging_appearances a
JOIN dw_player_origin o ON o.retro_id = a.retro_id
GROUP BY a.year_id, o.origin
```

### Data Cleaning Documentation

The analytics portal handles data quality issues:

1. **Missing Values:**
   - Empty birth_country → 'Unknown'
   - NULL player IDs are filtered out
   - Numeric fields validated with REGEXP before calculations

2. **Standardization:**
   - Country names normalized (USA, United States → USA)
   - Year values validated (must be 4 digits)
   - Whitespace trimmed from text fields

3. **Outliers:**
   - Statistical calculations only include validated numeric columns
   - Extreme values are visible in min/max stats
   - User can filter/sort to investigate outliers

## Project Overview

This project explores how international players have transformed Major League Baseball by examining:
- Awards and recognition received by foreign players
- Performance metrics and statistical trends
- Team composition changes over time
- Comparative analysis across different eras

### Diamond UI - Interactive Baseball Field Navigation

The project includes a **Diamond UI** - a Fenway-inspired, interactive baseball diamond layout that serves as a visual navigation metaphor. Each field zone (Home, 1B, 2B, 3B, LF, CF, RF) is clickable and reveals related baseball data and statistics.

**Key Features:**
- **CSS-only interactivity** using `:target` pseudo-class (no JavaScript required)
- **Zoomable field zones** that expand to reveal data panels with HTML tables
- **Responsive design** using Flexbox and Grid layouts
- **Keyboard navigation** with full accessibility support
- **Search/filter forms** demonstrating HTML form patterns (GET method)
- **Semantic HTML** with proper table structure and form elements

## Quick Start

### Prerequisites

- **PHP 7.4+** with MySQL support (pdo_mysql extension)
- **Python 3.8+** for ETL scripts
- **MySQL 8.0+** for data storage
- **curl** and **unzip** for data downloads
- **Polars library** (optional, for legacy ETL)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/cruz27r/MLB-Baseball-Impact.git
   cd MLB-Baseball-Impact
   ```

2. **Install Python dependencies (optional):**
   ```bash
   # For legacy Polars ETL (optional)
   pip install polars
   ```

3. **Configure database connection:**
   
   Copy the example config and update with your MySQL credentials:
   ```bash
   cp app/config.example.php app/config.php
   # Edit app/config.php with your MySQL connection details
   ```

4. **Initialize the database:**
   ```bash
   # Create MySQL database
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mlb;"
   mysql -u root -p -e "CREATE USER IF NOT EXISTS 'mlbuser'@'localhost' IDENTIFIED BY 'mlbpass';"
   mysql -u root -p -e "GRANT ALL PRIVILEGES ON mlb.* TO 'mlbuser'@'localhost';"
   
   # Download data sources
   ./scripts/download_lahman_sabr.sh
   ./scripts/download_retrosheet.sh
   ./scripts/download_bref_war.sh
   
   # Load data into MySQL
   ./scripts/load_mysql.sh mlb mlbuser mlbpass localhost 3306
   ```

### Running the ETL Pipeline

#### Option 1: Full Data Pipeline (Recommended)

The automated pipeline downloads data from SABR Lahman, Retrosheet, and Baseball-Reference, then loads it into MySQL:

```bash
# Download data sources
./scripts/download_lahman_sabr.sh
./scripts/download_retrosheet.sh
./scripts/download_bref_war.sh

# Load into MySQL
./scripts/load_mysql.sh mlb mlbuser mlbpass localhost 3306
```

**What the pipeline does:**
1. **Download**: Fetches Lahman, Retrosheet, and Baseball-Reference WAR data
2. **Load**: Imports raw data into MySQL staging tables
3. **Transform**: Builds data warehouse tables from staging data
4. **Analyze**: Ready for querying and visualization

**Data Sources:**
- **SABR Lahman Database**: Player demographics, statistics, and awards
- **Retrosheet**: Play-by-play game data (optional)
- **Baseball-Reference WAR**: Daily WAR (Wins Above Replacement) data

#### Option 2: Individual Scripts

You can also run individual components:

```bash
# Download specific data sources
./scripts/download_lahman_sabr.sh
./scripts/download_retrosheet.sh
./scripts/download_bref_war.sh

# Load data into MySQL
./scripts/load_mysql.sh mlb mlbuser mlbpass localhost 3306
```

#### Option 3: Legacy Polars ETL

The original ETL pipeline using Polars for local data processing:

```bash
cd etl
python mlb_metrics_polars.py --input ./raw_data --output ../mlb_out
```

**ETL Options:**
- `--input`: Directory containing raw MLB data files (default: ./raw_data)
- `--output`: Directory for processed output files (default: ../mlb_out)

### Running the Website

#### Quick Start

1. **Configure database connection:**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   
   # Edit .env with your MySQL credentials
   # Update MLB_DB_HOST, MLB_DB_PORT, MLB_DB_NAME, MLB_DB_USER, MLB_DB_PASS
   nano .env
   ```

2. **Start the PHP development server:**
   ```bash
   # From the project root directory
   php -S localhost:8080 -t public
   
   # Or from the public directory
   cd public
   php -S localhost:8080
   ```

3. **Open in browser:**
   ```
   http://localhost:8080
   ```

#### Stadium-Themed Website

The website features a baseball stadium-inspired design with:

- **Deep green field colors** with grass stripe texture background
- **Scoreboard-style panels** for KPIs and key statistics
- **Clay/baseline brown accents** for navigation and cards
- **Responsive design** that works on desktop, tablet, and mobile
- **Six analysis pages:**
  - **Home** - Project overview and quick KPIs
  - **Players** - Roster composition by origin over time
  - **Performance** - WAR and statistical analysis
  - **Awards** - MVP, Cy Young, All-Star selections
  - **Championships** - World Series team composition
  - **Play-by-Play** - Retrosheet game logs and events

#### Environment Configuration

The `.env` file configures the MySQL database connection:

```bash
MLB_DB_HOST=localhost      # Database host
MLB_DB_PORT=3306          # Database port (default MySQL port)
MLB_DB_NAME=mlb           # Database name
MLB_DB_USER=your_user     # Database username
MLB_DB_PASS=your_pass     # Database password
```

**Important:** Never commit `.env` to version control. Use `.env.example` as a template.

#### Graceful Error Handling

The website is designed to work even when database tables aren't loaded:

- **Database connection errors** show helpful messages with setup instructions
- **Missing tables** display "Data not loaded yet" banners instead of fatal errors
- **Each page includes data status badges** showing which tables are ready
- **Sample/placeholder data** provides visual examples of final output

This allows you to:
1. Set up and view the website immediately
2. Load data incrementally as scripts complete
3. Test the design before data is fully populated

#### Testing Without Data

To see the website design without loading data:

```bash
# Just configure a valid MySQL connection in .env
# Tables don't need to exist yet
php -S localhost:8080 -t public
```

Visit each page to see the stadium theme and placeholder content.

## Diamond UI - Interactive Field Layout

### Overview

The **Diamond UI** provides an innovative way to explore baseball data through an interactive baseball field metaphor. Each zone on the field represents a different category of data, creating an intuitive and engaging navigation experience.

### Accessing Diamond UI

The Diamond UI is available at:
```
http://localhost:8080/index.html
```

Or simply open `public/index.html` directly in your web browser (no server required for basic functionality).

### How to Navigate

1. **View the Diamond**: The main page displays a baseball field layout with clickable zones
2. **Click a Zone**: Click any field position (Home, 1B, 2B, 3B, LF, CF, RF) to explore its data
3. **View Data**: Each zone reveals a panel with:
   - Sample data tables with baseball statistics
   - Filter forms to search and refine data
   - Relevant metrics for that field position
4. **Return to Field**: Click "✕ Back to Diamond" to return to the main field view
5. **Keyboard Navigation**: Use Tab to move between zones, Enter to activate

### Field Zone Map

- **Home Plate (⚾)**: Player directory and demographics
- **First Base (1B)**: Batting statistics and offensive metrics
- **Second Base (2B)**: Awards and recognition (MVP, Cy Young, All-Star)
- **Third Base (3B)**: Pitching and defensive statistics
- **Left Field (LF)**: Team standings and statistics
- **Center Field (CF)**: Defensive metrics and fielding data
- **Right Field (RF)**: Power statistics (home runs, slugging)

### Features

**CSS-Only Interactions**:
- Uses `:target` pseudo-class for zone expansion (no JavaScript)
- `:hover` and `:focus` states for visual feedback
- Smooth animations via CSS transitions

**Responsive Design**:
- Flexbox and Grid layouts adapt to screen size
- Mobile-friendly with touch-optimized interactions
- Tables stack on narrow screens for readability

**Accessibility**:
- Semantic HTML structure
- Keyboard navigation support
- Screen reader friendly labels
- Focus indicators for keyboard users
- Skip links for quick navigation

**Forms and Tables**:
- HTML tables with `<caption>`, `<thead>`, `<tbody>`, `<th>`, `<td>`
- Search/filter forms using GET method with query strings
- Form validation with `required`, `min`, `max`, `pattern` attributes
- Proper `<label>` associations with `for` attributes

### Additional Pages

- **`people.html`**: Detailed player directory with sample People table data
- **`forms.html`**: Comprehensive search and filter forms for various data types

### Technical Implementation

The Diamond UI follows class-approved techniques:

**HTML**:
- Semantic elements (`<section>`, `<nav>`, `<header>`, `<footer>`, `<main>`)
- Accessible forms with proper labels and attributes
- HTML tables with proper structure
- ID anchors for in-page navigation

**CSS**:
- External stylesheets (`base.css`, `diamond.css`, `styles.css`)
- CSS Variables for design tokens
- Box Model (margin, padding, border, border-radius)
- Positioning (relative, absolute) for field layout
- Flexbox and Grid for responsive layouts
- Pseudo-classes (`:hover`, `:focus`, `:target`, `:nth-child`)
- Pseudo-elements (`::before`, `::after`) for decorative elements
- Transitions and animations (with `prefers-reduced-motion` support)

**No JavaScript** for core functionality (optional enhancement scripts use progressive enhancement)

### Files Structure

```
public/
├── index.html          # Diamond UI main page
├── people.html         # People directory stub
├── forms.html          # Search/filter forms
└── assets/
    └── css/
        ├── base.css     # Design system & base styles
        ├── diamond.css  # Diamond field layout (optional)
        └── styles.css   # Main Diamond UI styles
```

### Browser Compatibility

Works in all modern browsers that support:
- CSS Grid and Flexbox
- CSS Variables (Custom Properties)
- `:target` pseudo-class
- HTML5 form validation

Tested in: Chrome, Firefox, Safari, Edge

### Future Enhancements

The Diamond UI is designed to integrate with the MySQL backend:
- Dynamic data loading from database
- Real-time filtering and search
- Chart visualizations
- Play-by-play integration

Current implementation uses static mock data to demonstrate the interface patterns.

## Project Structure

```
MLB-Baseball-Impact/
├── public/                 # Web application files
│   ├── index.php          # Home page
│   ├── findings.php       # Key findings and insights
│   ├── explore.php        # Interactive data exploration
│   ├── methods.php        # Methodology documentation
│   ├── partials/          # Reusable PHP components
│   │   ├── header.php     # Site header with navigation
│   │   ├── footer.php     # Site footer
│   │   ├── layout.php     # Base layout wrapper
│   │   └── table.php      # Data table component
│   ├── assets/            # Static assets
│   │   ├── styles.css     # Main stylesheet
│   │   └── main.js        # JavaScript functionality
│   └── api/               # REST API endpoints
│       ├── composition.php    # Team composition data
│       ├── awards_index.php   # Awards data
│       ├── leaders_index.php  # Statistical leaders data
│       ├── impact_index.php   # Impact Index metrics (NEW)
│       └── status.php         # API status
├── etl/                   # ETL pipeline
│   ├── mlb_metrics_polars.py # Python ETL script using Polars
│   └── ingest_bref_war.py     # Baseball-Reference WAR parser (NEW)
├── scripts/               # Data download and database loading scripts
│   ├── download_lahman_sabr.sh   # Download SABR Lahman data
│   ├── download_retrosheet.sh    # Download Retrosheet data
│   ├── download_bref_war.sh      # Download Baseball-Reference WAR data
│   ├── load_mysql.sh             # Load data into MySQL database
│   └── list_all_data_files.py    # Utility to list downloaded data files
├── sql_mysql/             # MySQL database schema and queries
│   ├── 01_create_schemas.sql     # Schema and table definitions
│   ├── 02_create_staging.sql     # Staging tables
│   ├── 03_load_helpers.sql       # Helper procedures and indexes
│   └── 04_build_dw.sql           # Data warehouse build script
├── app/                   # Database connection layer
│   ├── db.php             # PDO database connection singleton
│   └── MLBData.php        # Data access methods
├── load_all_data.py       # Alternative Python-based data loader
├── mlb_out/               # Output directory (gitignored)
├── .gitignore             # Git ignore rules
└── README.md              # This file
```

## Data Warehouse (dw) Schema

The project includes a **unified data warehouse (dw) schema** that merges Lahman, Baseball-Reference WAR, and optional Retrosheet data into analysis-ready tables:

### Key Features
- **Star schema design** optimized for analytical queries
- **Canonical player_season table** with all metrics in one place
- **Origin classification** (USA, Latin, Other) for diversity analysis
- **Impact Index metric** (WAR share / roster share) as primary evidence
- **8 materialized views** for pre-aggregated insights

### Quick Start with MySQL

```bash
# Download data sources
./scripts/download_lahman_sabr.sh
./scripts/download_retrosheet.sh
./scripts/download_bref_war.sh

# Load into MySQL database
./scripts/load_mysql.sh mlb mlbuser mlbpass localhost 3306
```

## Site Layout

### Pages

- **Home (`index.php`)**: Landing page with project overview and quick navigation
- **Findings (`findings.php`)**: Key insights and statistical findings
- **Explore (`explore.php`)**: Interactive data tables with filtering capabilities
- **Methods (`methods.php`)**: Detailed methodology and technical approach

### API Endpoints

All API endpoints return JSON responses:

- **`/api/composition.php`**: Team composition statistics
  - Parameters: `year`, `team`
  
- **`/api/awards_index.php`**: Awards data for foreign players
  - Parameters: `year`, `country`, `award_type`
  
- **`/api/leaders_index.php`**: Statistical leaders
  - Parameters: `year`, `country`, `category`, `limit`

- **`/api/impact_index.php`**: Impact Index metrics (NEW)
  - Parameters: `year`, `origin`, `limit`
  - Returns WAR contribution vs roster share by player origin
  - Impact Index > 1 means group contributes more WAR than roster representation
  - Example: `/api/impact_index.php?year=2020&origin=Latin`

### Database Schema

The project uses MySQL schemas for data storage and analysis:

**Schemas:**
- `staging`: Temporary tables for data loading from CSV files
- `dw`: **Data warehouse** - analysis-ready tables and views

**Data Warehouse (dw) Tables:**
- `dw.player_season`: Canonical wide table with all metrics per player-year
- `dw.players`: Player dimension with origin classification
- `dw.teams`: Team dimension
- `dw.batting_season`, `dw.pitching_season`: Aggregated statistics
- `dw.awards_season`: Awards won by player-season

**Staging Tables:**
- `staging_people`: Player demographics from Retrosheet
- `staging_appearances`: Player appearances by team/year
- `staging_teams`: Team information
- `staging_war_bat`: Batting WAR data from Baseball-Reference
- `staging_war_pitch`: Pitching WAR data from Baseball-Reference

## ETL Usage

### Input Data Format

The ETL script expects CSV files in the input directory:
- `players.csv`: Player demographics and birthplace information
- `statistics.csv`: Performance statistics by player and year
- `awards.csv`: Awards and recognition data

### Output Data

Processed data is saved in the `mlb_out/` directory:
- CSV files for easy viewing and analysis
- Parquet files for efficient storage and loading
- Summary report with ETL metadata

### Verifying the Pipeline

After running the pipeline, verify the data:

```bash
# Check if data was loaded (using MySQL)
mysql -u mlbuser -p mlb -e "SELECT COUNT(*) FROM staging_people;"
mysql -u mlbuser -p mlb -e "SELECT COUNT(*) FROM staging_war_bat;"
mysql -u mlbuser -p mlb -e "SELECT COUNT(*) FROM staging_war_pitch;"

# Test API endpoint
curl http://localhost:8000/api/status.php
```

## Development

### Local Development Server

Use PHP's built-in server for development:

```bash
cd public
php -S localhost:8000
```

### Database Configuration

Database settings are configured in `app/config.php`:

- `DB_HOST`: Database host (default: localhost)
- `DB_NAME`: Database name (default: mlb)
- `DB_USER`: Database user (default: mlbuser)
- `DB_PASS`: Database password (default: mlbpass)
- `DB_PORT`: Database port (default: 3306)

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **ETL**: Python 3.8+ with Polars library (optional)
- **Data Formats**: CSV

## Contributing

This is an academic project for CS437. Contributions should follow the existing code structure and documentation standards.

## License

MIT License - See LICENSE file for details

## Contact

Rafael Cruz - CS437 MLB Global Era Project
