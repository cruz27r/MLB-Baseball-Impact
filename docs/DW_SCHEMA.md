# Data Warehouse (dw) Schema Documentation

## Overview

The `dw` (data warehouse) schema is a unified, analysis-ready database layer that merges data from:
- **SABR/Lahman Database**: Player demographics, statistics, and awards
- **Baseball-Reference WAR**: Wins Above Replacement metrics
- **Retrosheet** (optional): Play-by-play game data

This schema consolidates these sources into a star schema design optimized for analytical queries and reporting.

## Architecture

### Star Schema Design

```
         dw.players (dimension)
                |
                v
    dw.player_season (fact/wide table)
        |       |       |
        v       v       v
    dw.teams  dw.war_season  dw.awards_season
  (dimension)   (fact)        (fact)
```

## Tables

### Dimension Tables

#### `dw.countries`
Country reference with Latin American classification.
- **Primary Key**: `code`
- **Columns**: code, name, is_latin, region
- **Purpose**: Normalize country codes and classify Latin/Caribbean nations

#### `dw.players`
Player master dimension with origin classification.
- **Primary Key**: `player_id`
- **Key Fields**: 
  - `bref_id`: Baseball-Reference player ID
  - `country_code`: Normalized country code
  - `origin_group`: Classification (USA, Latin, Other)
- **Purpose**: Single source of truth for player identity and origin

#### `dw.teams`
Team dimension by year.
- **Primary Key**: `(year, team_id)`
- **Key Fields**: franch_id, team_name, attendance, wins, losses
- **Purpose**: Team metadata including attendance for interest proxies

### Bridge Tables

#### `dw.player_teams`
Player-team associations by year (many-to-many bridge).
- **Primary Key**: `(year, player_id, team_id)`
- **Purpose**: Handle players who played for multiple teams in a season

### Fact Tables

#### `dw.batting_season`
Aggregated batting statistics by player-season.
- **Primary Key**: `(year, player_id)`
- **Metrics**: ab, h, hr, rbi, bb, so, sb, cs, obp, slg, ops

#### `dw.pitching_season`
Aggregated pitching statistics by player-season.
- **Primary Key**: `(year, player_id)`
- **Metrics**: ip_outs, er, so, bb, hr_allowed, era, sv, w, l

#### `dw.fielding_season`
Aggregated fielding statistics by player-season-position (optional).
- **Primary Key**: `(year, player_id, pos)`
- **Metrics**: g, gs, inn_outs, po, a, e, fld_pct

#### `dw.awards_season`
Awards won by player-season.
- **Primary Key**: `(year, player_id, award_id)`
- **Metrics**: count, allstar_count

#### `dw.war_season`
Baseball-Reference WAR by player-season.
- **Primary Key**: `(year, player_id)`
- **Metrics**: war_bat, war_pitch, war_total

#### `dw.postseason_team`
Postseason participation and results.
- **Primary Key**: `(year, round, team_id)`
- **Key Fields**: wins, losses, is_champion

### Canonical Wide Table

#### `dw.player_season`
**The primary analysis table** - denormalized player-season with all metrics.

**Key Features**:
- Combines biographical, performance, awards, WAR, and postseason data
- One row per player-season
- Pre-calculated origin groups and team associations
- Optimized for analytical queries

**Core Fields**:
- **Identity**: player_id, bref_id, name_first, name_last
- **Origin**: country_code, origin_group
- **Team Context**: teams_played, avg_team_attendance
- **Postseason Flags**: made_postseason, deep_run, is_champion
- **Batting**: ab, h, hr, rbi, bb, so, sb, cs, obp, slg, ops
- **Pitching**: ip_outs, er, so_p, bb_p, hr_allowed, era, sv, w, l
- **Awards**: awards_total, mvp_count, cy_count, roy_count, allstar_count
- **WAR**: war_bat, war_pitch, war_total
- **Quality**: id_confidence, missing_fields

## Materialized Views

### `dw.mv_yearly_composition`
Yearly player composition by origin (USA, Latin, Other).
- **Metrics**: total_players, us_players, latin_players, other_players, shares
- **Use Case**: Tracking diversity trends over time

### `dw.mv_war_by_origin`
WAR aggregated by origin and year.
- **Metrics**: total_war, players, avg_war, war_share
- **Use Case**: Performance contribution by origin

### `dw.mv_awards_share`
Awards distribution by origin with share percentages.
- **Metrics**: awards_total, mvp, cy, roy, allstar_total, shares
- **Use Case**: Recognition patterns by origin

### `dw.mv_hr25_by_origin`
Count of players with 25+ home runs by origin.
- **Metrics**: count_25hr, share_25hr
- **Use Case**: Power hitting distribution

### `dw.mv_championship_contrib`
WAR contribution on contending and championship teams.
- **Metrics**: war_on_contenders, war_on_champions, player counts
- **Use Case**: Impact on winning teams

### `dw.mv_interest_proxies`
Attendance metrics aggregated by origin.
- **Metrics**: avg_attendance, weighted_attendance
- **Use Case**: Fan interest indicators

### `dw.mv_impact_index`
**The signature metric**: WAR share divided by roster share.
- **Metrics**: roster_share, war_share, impact_index
- **Interpretation**: 
  - impact_index > 1: Group contributes more WAR than representation
  - impact_index = 1: Proportional contribution
  - impact_index < 1: Lower contribution than representation
- **Use Case**: Primary evidence for the research claim

### `dw.mv_top_war_contributors`
Top 10 WAR contributors per origin per year.
- **Purpose**: Identify elite performers by origin

## Build Process

### 1. Schema Creation
```bash
psql -d mlb -f sql/10_dw_schema.sql
```
Creates all tables and indexes in the `dw` schema.

### 2. Build Dimensions
```bash
psql -d mlb -f sql/11_dw_build_dimensions.sql
```
- Loads country mapping from `config/country_map.csv`
- Populates players dimension with origin classification
- Loads teams dimension

### 3. Build Facts
```bash
psql -d mlb -f sql/12_dw_build_facts.sql
```
- Aggregates batting, pitching, fielding statistics
- Compiles awards and WAR data
- Creates postseason flags
- Builds canonical `player_season` table

### 4. Create Materialized Views
```bash
psql -d mlb -f sql/13_dw_materialized_views.sql
```
- Creates all analytical materialized views
- Builds indexes for performance

### 5. Validation
```bash
psql -d mlb -f sql/99_validate_dw.sql
```
- Validates record counts
- Checks coverage metrics
- Verifies WAR aggregation accuracy
- Reports ID crosswalk quality

## Refresh Process

### Full Refresh
```bash
./scripts/refresh_db.sh mlb
```
Runs the complete ETL pipeline including DW build.

### Refresh Materialized Views Only
```sql
SELECT dw.refresh_materialized_views();
```
Refreshes all materialized views concurrently without rebuilding base tables.

## Configuration Files

### `config/country_map.csv`
Maps raw country names to ISO codes with Latin classification.
- **Format**: raw_country, iso_code, name, is_latin, region
- **Usage**: Normalizes inconsistent country names from source data
- **Customization**: Add new mappings as needed for data quality

### `config/id_overrides.csv`
Manual corrections for B-Ref to Lahman player ID mapping.
- **Format**: bref_id, player_id, reason
- **Usage**: Override automated crosswalk for edge cases
- **Maintenance**: Add entries when fuzzy matching fails

## Data Quality

### Origin Classification
Players are classified into three origin groups:
- **USA**: Born in the United States
- **Latin**: Born in Latin America or Caribbean (see `is_latin` in countries table)
- **Other**: All other countries (Canada, Japan, Europe, etc.)

### ID Confidence Levels
The `id_confidence` field in `dw.player_season` indicates crosswalk quality:
- **high**: Direct match or manual override
- **medium**: Fuzzy match by name and birth year
- **low**: No match, using B-Ref ID only

### Missing Fields Tracking
The `missing_fields` field flags data gaps:
- **NULL**: Complete record
- **no_stats**: No batting or pitching statistics
- **no_war**: No WAR data available

## Query Examples

### Get Impact Index for Recent Years
```sql
SELECT year, origin_group, roster_share, war_share, impact_index
FROM dw.mv_impact_index
WHERE year >= 2015
ORDER BY year DESC, origin_group;
```

### Find Top Latin Players by WAR (2020)
```sql
SELECT name_first, name_last, country_code, war_total
FROM dw.player_season
WHERE year = 2020 AND origin_group = 'Latin'
ORDER BY war_total DESC NULLS LAST
LIMIT 10;
```

### Compare MVP Awards by Origin (All Time)
```sql
SELECT origin_group, SUM(mvp_count) AS total_mvps
FROM dw.player_season
GROUP BY origin_group
ORDER BY total_mvps DESC;
```

### Team Composition Analysis
```sql
SELECT t.year, t.team_name,
       COUNT(DISTINCT ps.player_id) AS roster_size,
       SUM(CASE WHEN ps.origin_group = 'Latin' THEN 1 ELSE 0 END) AS latin_players,
       AVG(ps.war_total) AS avg_war
FROM dw.player_season ps
JOIN dw.teams t ON ps.year = t.year 
WHERE ps.teams_played LIKE '%' || t.team_id || '%'
  AND ps.year = 2020
GROUP BY t.year, t.team_name
ORDER BY latin_players DESC;
```

## Performance Considerations

### Indexes
All key lookup paths are indexed:
- Player lookups: `player_id`, `bref_id`
- Origin analysis: `(year, origin_group)`
- WAR queries: `war_total`
- Temporal queries: `year`

### Materialized Views
- Use `REFRESH MATERIALIZED VIEW CONCURRENTLY` for zero-downtime refreshes
- Unique indexes enable concurrent refresh
- Refresh after data updates or periodically

### Query Optimization
- Use `dw.player_season` for most queries (pre-joined, indexed)
- Use specific fact tables only when filtering by specific metrics
- Leverage materialized views for aggregated queries

## Maintenance

### Adding New Years
1. Load new source data (Lahman, WAR)
2. Run ETL pipeline: `./scripts/refresh_db.sh mlb`
3. Materialized views auto-update or run: `SELECT dw.refresh_materialized_views();`

### Updating Country Mappings
1. Edit `config/country_map.csv`
2. Rebuild dimensions: `psql -d mlb -f sql/11_dw_build_dimensions.sql`
3. Rebuild facts: `psql -d mlb -f sql/12_dw_build_facts.sql`

### Adding Manual ID Overrides
1. Edit `config/id_overrides.csv`
2. Re-run WAR ingestion: `python3 etl/ingest_bref_war.py`
3. Rebuild WAR facts: `psql -d mlb -f sql/12_dw_build_facts.sql`

## API Integration

The existing PHP APIs can read from `dw.mv_*` views with minimal changes:
- Replace `core.mv_*` references with `dw.mv_*`
- Environment variables remain unchanged
- Response formats compatible

## Support for Retrosheet

The schema is designed to accommodate Retrosheet play-by-play data:
- Add `dw.postseason_player` table for per-round clutch stats
- Extend `dw.player_season` with additional playoff metrics
- Current implementation focuses on Lahman + B-Ref WAR (core claim)

## Schema Evolution

Future enhancements can be added without breaking existing queries:
- Additional dimension attributes (e.g., player position groups)
- New fact tables (e.g., defensive metrics, baserunning)
- Additional materialized views (e.g., career trajectories)
- The canonical `player_season` table can be extended with new columns
