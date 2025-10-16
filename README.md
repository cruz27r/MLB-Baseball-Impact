# MLB-Baseball-Impact

**CS437 MLB Global Era Project**

Analyzing the impact of foreign players on Major League Baseball through comprehensive data analysis and visualization.

## Project Overview

This project explores how international players have transformed Major League Baseball by examining:
- Awards and recognition received by foreign players
- Performance metrics and statistical trends
- Team composition changes over time
- Comparative analysis across different eras

## Quick Start

### Prerequisites

- **PHP 7.4+** with PostgreSQL support (pdo_pgsql extension)
- **Python 3.8+** for ETL scripts
- **PostgreSQL 12+** for data storage
- **Python packages**: `psycopg2-binary` (or `psycopg2`) for WAR ingestion
- **curl** and **unzip** for data downloads
- **Polars library** (optional, for legacy ETL)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/cruz27r/MLB-Baseball-Impact.git
   cd MLB-Baseball-Impact
   ```

2. **Install Python dependencies:**
   ```bash
   # For WAR ingestion (required)
   pip install psycopg2-binary
   
   # For legacy Polars ETL (optional)
   pip install polars
   ```

3. **Configure database connection:**
   
   Set environment variables for database access:
   ```bash
   export DB_HOST=localhost
   export DB_NAME=mlb        # Use 'mlb' for the new pipeline
   export DB_USER=postgres
   export DB_PASSWORD=your_password
   export DB_PORT=5432
   ```

4. **Initialize the database:**
   ```bash
   # Create the database
   createdb mlb
   
   # Run the complete pipeline (downloads data and sets up database)
   ./scripts/update_all.sh
   ```

### Running the ETL Pipeline

#### Option 1: Full Data Pipeline (Recommended)

The automated pipeline downloads data from SABR Lahman, Retrosheet, and Baseball-Reference, then loads it into PostgreSQL:

```bash
# Create database
createdb mlb || true

# Set environment variables (optional)
export LAHMAN_URL="https://sabr.org/path/to/lahman.zip"
export RETRO_URL="https://retrosheet.org/path/to/retrosheet.zip"

# Run full update pipeline
DB=mlb ./scripts/update_all.sh
```

**What the pipeline does:**
1. **Download**: Fetches Lahman, Retrosheet, and Baseball-Reference WAR data
2. **Load**: Imports raw data into PostgreSQL schemas
3. **Transform**: Parses and structures WAR data via Python ETL
4. **Analyze**: Creates materialized views with Impact Index metrics

**Data Sources:**
- **SABR Lahman Database**: Player demographics, statistics, and awards
- **Retrosheet**: Play-by-play game data (optional)
- **Baseball-Reference WAR**: Daily WAR (Wins Above Replacement) data

#### Option 2: Individual Scripts

You can also run individual components:

```bash
# Download specific data sources
./scripts/download_lahman_sabr.sh "<LAHMAN_URL>"
./scripts/download_retrosheet.sh "<RETRO_URL>"
./scripts/download_bref_war.sh

# Refresh database with downloaded data
./scripts/refresh_db.sh mlb
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

1. **Start a PHP development server:**
   ```bash
   cd public
   php -S localhost:8000
   ```

2. **Open in browser:**
   ```
   http://localhost:8000
   ```

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
├── scripts/               # Automation scripts (NEW)
│   ├── download_lahman_sabr.sh   # Download SABR Lahman data
│   ├── download_retrosheet.sh    # Download Retrosheet data
│   ├── download_bref_war.sh      # Download B-Ref WAR data
│   ├── update_all.sh             # Run complete pipeline
│   └── refresh_db.sh             # Refresh database
├── sql/                   # Database schema and queries
│   ├── 01_create_db.sql       # Schema and table definitions
│   ├── 02_load_lahman.sql     # Load Lahman CSV data
│   ├── 03_load_bref_war.sql   # Load Baseball-Reference WAR (NEW)
│   ├── 04_views_analysis.sql  # Player origin analysis views
│   ├── 05_awards_and_leaders.sql # Awards analysis (NEW)
│   ├── 06_war_by_origin.sql   # WAR and Impact Index views (NEW)
│   ├── 10_dw_schema.sql       # Data Warehouse schema (NEW)
│   ├── 11_dw_build_dimensions.sql  # DW dimension tables (NEW)
│   ├── 12_dw_build_facts.sql  # DW fact tables and player_season (NEW)
│   ├── 13_dw_materialized_views.sql # DW analytical views (NEW)
│   ├── 99_validate_dw.sql     # DW validation script (NEW)
│   └── views.sql              # Legacy materialized views
├── config/                # Configuration files (NEW)
│   ├── country_map.csv    # Country code mappings
│   └── id_overrides.csv   # Manual player ID overrides
├── docs/                  # Documentation (NEW)
│   └── DW_SCHEMA.md       # Data warehouse schema documentation
├── app/                   # Database connection layer
│   ├── db.php             # PDO database connection singleton
│   └── MLBData.php        # Data access methods
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

### Quick Start with DW
```bash
# Full pipeline (downloads data and builds DW)
./scripts/update_all.sh

# Or build DW from existing data
psql -d mlb -f sql/10_dw_schema.sql
psql -d mlb -f sql/11_dw_build_dimensions.sql
psql -d mlb -f sql/12_dw_build_facts.sql
psql -d mlb -f sql/13_dw_materialized_views.sql

# Validate
psql -d mlb -f sql/99_validate_dw.sql

# Query Impact Index
psql -d mlb -c "SELECT * FROM dw.mv_impact_index WHERE year >= 2015 ORDER BY year DESC, origin_group;"
```

See **[docs/DW_SCHEMA.md](docs/DW_SCHEMA.md)** for complete documentation.

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

The project uses PostgreSQL schemas and materialized views for optimized queries:

**Schemas:**
- `core`: Main player and statistics data (loaded from Lahman)
- `bref`: Baseball-Reference WAR data
- `dw`: **Unified data warehouse** - analysis-ready tables (NEW)
- `lahman`: Reserved for future use
- `retrosheet`: Retrosheet play-by-play data (optional)

**Data Warehouse (dw) Tables:**
- `dw.player_season`: Canonical wide table with all metrics per player-year
- `dw.players`: Player dimension with origin classification
- `dw.teams`: Team dimension with attendance
- `dw.countries`: Country reference with Latin/Caribbean classification
- `dw.batting_season`, `dw.pitching_season`: Aggregated statistics
- `dw.war_season`: Baseball-Reference WAR by player-season
- `dw.awards_season`: Awards won by player-season
- `dw.postseason_team`: Postseason participation and results

**Data Warehouse Materialized Views:**
- `dw.mv_yearly_composition`: Player composition by origin and year
- `dw.mv_war_by_origin`: WAR aggregated by player origin
- `dw.mv_impact_index`: **Impact Index metric** (WAR share / Roster share)
- `dw.mv_awards_share`: Awards distribution by origin
- `dw.mv_hr25_by_origin`: Power hitters (25+ HR) by origin
- `dw.mv_championship_contrib`: WAR on contending and championship teams
- `dw.mv_interest_proxies`: Attendance metrics by origin
- `dw.mv_top_war_contributors`: Top 10 WAR leaders per origin per year

**Legacy Views (core schema):**
- `core.mv_yearly_composition`: Player composition by origin and year
- `core.mv_war_by_origin`: WAR aggregated by player origin
- `core.mv_impact_index`: Impact Index metric
- `core.mv_latin_players_by_country`: Latin American player statistics
- `mv_foreign_players_summary`: Aggregated statistics by country and year
- `mv_foreign_awards`: Awards won by foreign players
- `mv_team_composition`: Foreign vs domestic player distribution per team
- `mv_statistical_leaders`: Top performers with rankings

**Recommendation**: Use `dw.*` tables and views for new analysis. Legacy `core.*` views maintained for backward compatibility.

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

### Refreshing Materialized Views

After loading new data, refresh the database views:

```sql
-- Refresh all legacy views
SELECT refresh_all_mlb_views();

-- Refresh analysis views
SELECT core.refresh_analysis_views();

-- Refresh WAR views (requires WAR data)
SELECT core.refresh_war_views();
```

Or refresh individual views:

```sql
REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_yearly_composition;
REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_war_by_origin;
REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_impact_index;
```

### Verifying the Pipeline

After running the pipeline, verify the data:

```bash
# Check if data was loaded
psql -d mlb -c "SELECT COUNT(*) FROM core.people;"
psql -d mlb -c "SELECT COUNT(*) FROM bref.war_bat;"
psql -d mlb -c "SELECT COUNT(*) FROM bref.war_pitch;"

# View Impact Index results
psql -d mlb -c "SELECT * FROM core.mv_impact_index ORDER BY year DESC LIMIT 10;"

# Test API endpoint
curl http://localhost:8080/api/impact_index.php?limit=5
```

## Development

### Local Development Server

Use PHP's built-in server for development:

```bash
cd public
php -S localhost:8000
```

### Database Configuration

Database settings can be configured via environment variables or by modifying `app/db.php`:

- `DB_HOST`: Database host (default: localhost)
- `DB_NAME`: Database name (default: mlb_global_era)
- `DB_USER`: Database user (default: postgres)
- `DB_PASSWORD`: Database password
- `DB_PORT`: Database port (default: 5432)

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP 7.4+
- **Database**: PostgreSQL 12+
- **ETL**: Python 3.8+ with Polars library
- **Data Formats**: CSV, Parquet

## Contributing

This is an academic project for CS437. Contributions should follow the existing code structure and documentation standards.

## License

MIT License - See LICENSE file for details

## Contact

Rafael Cruz - CS437 MLB Global Era Project
