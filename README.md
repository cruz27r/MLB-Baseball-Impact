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
- **Polars library** for Python data processing

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/cruz27r/MLB-Baseball-Impact.git
   cd MLB-Baseball-Impact
   ```

2. **Install Python dependencies:**
   ```bash
   pip install polars
   ```

3. **Configure database connection:**
   
   Set environment variables for database access:
   ```bash
   export DB_HOST=localhost
   export DB_NAME=mlb_global_era
   export DB_USER=postgres
   export DB_PASSWORD=your_password
   export DB_PORT=5432
   ```

4. **Initialize the database:**
   ```bash
   psql -U postgres -d mlb_global_era -f sql/views.sql
   ```

### Running the ETL Pipeline

The ETL (Extract, Transform, Load) pipeline processes raw MLB data using Python and Polars:

```bash
cd etl
python mlb_metrics_polars.py --input ./raw_data --output ../mlb_out
```

**ETL Options:**
- `--input`: Directory containing raw MLB data files (default: ./raw_data)
- `--output`: Directory for processed output files (default: ../mlb_out)

**What the ETL does:**
1. **Extract**: Reads raw MLB data from CSV files
2. **Transform**: Filters, aggregates, and enriches the data
3. **Load**: Outputs processed data in CSV and Parquet formats

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
│       └── leaders_index.php  # Statistical leaders data
├── etl/                   # ETL pipeline
│   └── mlb_metrics_polars.py # Python ETL script using Polars
├── sql/                   # Database schema and queries
│   └── views.sql          # PostgreSQL materialized views
├── app/                   # Database connection layer
│   ├── db.php             # PDO database connection singleton
│   └── MLBData.php        # Data access methods
├── mlb_out/               # Output directory (gitignored)
├── .gitignore             # Git ignore rules
└── README.md              # This file
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

### Database Schema

The project uses PostgreSQL materialized views for optimized queries:

- `mv_foreign_players_summary`: Aggregated statistics by country and year
- `mv_foreign_awards`: Awards won by foreign players
- `mv_team_composition`: Foreign vs domestic player distribution per team
- `mv_statistical_leaders`: Top performers with rankings

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
SELECT refresh_all_mlb_views();
```

Or refresh individual views:

```sql
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_foreign_players_summary;
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
