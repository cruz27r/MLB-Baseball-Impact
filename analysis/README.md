# MLB Analysis Outputs

This module generates statistical evidence and visualizations to support the claim that foreign (non-USA) players have had the largest impact on Major League Baseball.

## Overview

The analysis pipeline:
1. Executes SQL queries against the unified data warehouse (`dw` schema)
2. Exports results as clean CSV tables
3. Generates publication-ready PNG charts

## Prerequisites

- **Python 3.8+**
- **PostgreSQL database** with the `dw` schema populated (see main README for setup)
- **Required Python packages** (see requirements.txt)

## Installation

1. **Create a virtual environment:**
   ```bash
   python3 -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

2. **Install dependencies:**
   ```bash
   pip install -r analysis/requirements.txt
   ```

3. **Configure database connection:**

   Set environment variables for database access:
   ```bash
   export MLB_DB_HOST=localhost
   export MLB_DB_PORT=5432
   export MLB_DB_USER=postgres
   export MLB_DB_PASS=your_password
   export MLB_DB_NAME=mlb
   ```

   Alternatively, create a `.env` file in the project root:
   ```env
   MLB_DB_HOST=localhost
   MLB_DB_PORT=5432
   MLB_DB_USER=postgres
   MLB_DB_PASS=your_password
   MLB_DB_NAME=mlb
   ```

## Usage

Run the analysis pipeline:

```bash
python3 analysis/run_analysis.py
```

The script will:
- Connect to the PostgreSQL database
- Execute all SQL queries in `analysis/sql/` (in sorted order)
- Generate CSV files in `analysis/out/`
- Generate PNG charts in `analysis/out/`
- Print a summary of generated outputs

## Outputs

### CSV Files

All CSV files are saved to `analysis/out/`:

1. **01_composition.csv** - Player composition by origin and year
   - Columns: `year`, `total_players`, `us_players`, `latin_players`, `other_players`, `us_share`, `latin_share`, `other_share`

2. **02_war_share.csv** - WAR aggregated by player origin
   - Columns: `year`, `origin_group`, `total_war`, `players`, `avg_war`

3. **03_impact_index.csv** - Impact Index metric (WAR share / roster share)
   - Columns: `year`, `origin_group`, `roster_share`, `war_share`, `impact_index`
   - Impact Index > 1.0 means the group contributes more WAR than their roster representation

4. **04_awards_share.csv** - Awards distribution by origin
   - Columns: `year`, `origin_group`, `awards_total`, `mvp`, `cy`, `roy`, `allstar_total`

5. **05_championship_contrib.csv** - WAR on contending and championship teams
   - Columns: `year`, `origin_group`, `war_on_contenders`, `war_on_champions`

6. **06_salary_efficiency.csv** - Salary efficiency metrics (optional, only if salary data exists)
   - Columns: `year`, `origin_group`, `avg_salary_usd`, `avg_war`, `avg_cost_per_war`

### PNG Charts

All charts are saved to `analysis/out/` at 1600x900 resolution:

1. **composition_share.png** - Roster share by origin over time (line chart)
   - Shows trends in USA, Latin America, and Other player representation

2. **war_share_vs_roster_share.png** - WAR share vs roster share comparison
   - Three subplots (one per origin) comparing WAR contribution to roster representation

3. **impact_index.png** - Impact Index by origin over time
   - Shows which groups outperform (>1.0) or underperform (<1.0) their representation

4. **awards_share.png** - Awards distribution by origin
   - Four subplots showing MVP, Cy Young, Rookie of Year, and All-Star awards

5. **championship_contrib.png** - Championship contribution by origin
   - Two subplots showing WAR on contending teams and championship teams

6. **salary_efficiency.png** - Salary efficiency by origin (optional)
   - Shows average cost per WAR for each origin group over time

## SQL Queries

The SQL files in `analysis/sql/` query materialized views from the `dw` schema:

- **01_composition.sql** - Queries `dw.mv_yearly_composition`
- **02_war_share.sql** - Queries `dw.mv_war_by_origin`
- **03_impact_index.sql** - Queries `dw.mv_impact_index`
- **04_awards_share.sql** - Queries `dw.mv_awards_share`
- **05_championship_contrib.sql** - Queries `dw.mv_championship_contrib`
- **06_salary_efficiency.sql** - Queries `dw.player_season` and `dw.salaries` (optional)

These views are created by the data warehouse setup scripts (see main README).

## Handling Missing Data

The script gracefully handles missing data:

- If a query returns no rows, it logs a warning and continues
- If the `dw.salaries` table doesn't exist, salary queries are skipped
- Charts are only generated if the corresponding CSV data exists

## Interpreting the Results

### Impact Index

The **Impact Index** is the primary metric for measuring player origin impact:

```
Impact Index = WAR Share / Roster Share
```

- **Index = 1.0**: Group contributes WAR proportional to their roster share
- **Index > 1.0**: Group contributes more WAR than their representation (high impact)
- **Index < 1.0**: Group contributes less WAR than their representation

Example: If Latin players are 30% of rosters but contribute 40% of WAR, their Impact Index is 1.33.

### Expected Results

For a properly populated database with 50+ years of data:

- **01_composition.csv**: ≥50 rows showing player composition trends
- **03_impact_index.csv**: Clear separation between origin groups
- Charts showing:
  - Increasing Latin American player representation over time
  - Latin players with Impact Index > 1.0 in recent decades
  - Growing awards share for foreign players

## Troubleshooting

### Database Connection Issues

If you get connection errors:

1. Verify PostgreSQL is running: `pg_isready`
2. Check database exists: `psql -l | grep mlb`
3. Verify environment variables are set correctly
4. Test connection: `psql -h $MLB_DB_HOST -U $MLB_DB_USER -d $MLB_DB_NAME`

### Missing Data

If queries return no rows:

1. Verify data warehouse is populated:
   ```bash
   psql -d mlb -c "SELECT COUNT(*) FROM dw.player_season;"
   ```

2. Refresh materialized views:
   ```bash
   psql -d mlb -f sql/13_dw_materialized_views.sql
   ```

3. Check for data in source tables:
   ```bash
   psql -d mlb -f sql/99_validate_dw.sql
   ```

### Import Errors

If you get module import errors:

```bash
# Ensure virtual environment is activated
source venv/bin/activate

# Reinstall dependencies
pip install -r analysis/requirements.txt
```

## Re-running the Analysis

The analysis can be re-run at any time:

1. After loading new data into the database
2. After refreshing materialized views
3. To regenerate charts with different styling

Simply run:
```bash
python3 analysis/run_analysis.py
```

Previous outputs in `analysis/out/` will be overwritten.

## Using the Outputs

The generated CSV files and PNG charts are ready for:

- **Project reports** - Direct citation of statistical evidence
- **Presentations** - High-quality visualizations
- **Web integration** - Import CSVs into frontend data tables
- **Further analysis** - Load CSVs into Excel, R, or other tools

## Optional: Adding Salary Data

To enable salary efficiency analysis:

1. Create the `dw.salaries` table:
   ```sql
   CREATE TABLE dw.salaries (
       year INT,
       player_id TEXT,
       salary_usd NUMERIC,
       PRIMARY KEY (year, player_id)
   );
   ```

2. Load salary data from a CSV source (Baseball-Reference or MLBPA)

3. Re-run the analysis:
   ```bash
   python3 analysis/run_analysis.py
   ```

The salary efficiency outputs will now be generated.

## Notes

- This module does **not** modify the database or materialized views
- Outputs are analysis artifacts only (CSVs and PNGs)
- No frontend work is included - outputs are standalone
- Charts use matplotlib with sensible defaults (no seaborn dependency)

## License

MIT License - See main project LICENSE file
