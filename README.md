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
├── scripts/               # Automation scripts
│   ├── download_lahman_sabr.sh   # Download SABR Lahman data
│   ├── download_retrosheet.sh    # Download Retrosheet data
│   ├── download_bref_war.sh      # Download B-Ref WAR data
│   └── load_mysql.sh             # Load data into MySQL database
├── sql_mysql/             # MySQL database schema and queries
│   ├── 01_create_schemas.sql     # Schema and table definitions
│   ├── 02_create_staging.sql     # Staging tables
│   ├── 03_load_helpers.sql       # Helper procedures and indexes
│   └── 04_build_dw.sql           # Data warehouse build script
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
