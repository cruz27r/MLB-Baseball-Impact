# Data Warehouse Views and Materialized Tables

This directory documents the expected views and materialized tables that will be built on top of the staging data to support the analysis pages.

## Overview

The data warehouse layer will transform raw staging data into analysis-ready views that aggregate and denormalize data for efficient querying. These views support the five main analysis dimensions of the MLB Baseball Impact project.

## Expected Views

### 1. `dw_yearly_composition`

**Purpose:** Aggregate player roster composition by country and year for the Players page.

**Schema:**
```sql
CREATE VIEW dw_yearly_composition AS
SELECT 
    YEAR(debut) AS year,
    COALESCE(NULLIF(TRIM(birth_country),''), 'Unknown') AS birth_country,
    COUNT(DISTINCT player_id) AS player_count,
    COUNT(DISTINCT player_id) * 100.0 / 
        (SELECT COUNT(DISTINCT player_id) FROM staging_people WHERE YEAR(debut) = YEAR(p.debut)) AS percentage
FROM staging_people p
WHERE debut IS NOT NULL
GROUP BY YEAR(debut), birth_country;
```

**Used By:** `players.php` - Roster composition over time

---

### 2. `dw_war_by_origin`

**Purpose:** Aggregate WAR contributions by player origin for the Performance page.

**Schema:**
```sql
CREATE VIEW dw_war_by_origin AS
SELECT 
    p.birth_country,
    w.year_id,
    COUNT(DISTINCT w.player_id) AS player_count,
    SUM(w.war) AS total_war,
    AVG(w.war) AS avg_war_per_player,
    SUM(w.war) * 100.0 / 
        (SELECT SUM(war) FROM staging_war_bat WHERE year_id = w.year_id) AS war_share
FROM staging_war_bat w
JOIN staging_people p ON w.player_id = p.player_id
GROUP BY p.birth_country, w.year_id
UNION ALL
SELECT 
    p.birth_country,
    w.year_id,
    COUNT(DISTINCT w.player_id) AS player_count,
    SUM(w.war) AS total_war,
    AVG(w.war) AS avg_war_per_player,
    SUM(w.war) * 100.0 / 
        (SELECT SUM(war) FROM staging_war_pitch WHERE year_id = w.year_id) AS war_share
FROM staging_war_pitch w
JOIN staging_people p ON w.player_id = p.player_id
GROUP BY p.birth_country, w.year_id;
```

**Used By:** `performance.php` - WAR analysis and Impact Index

---

### 3. `dw_awards_by_origin`

**Purpose:** Aggregate award counts by player origin and award type for the Awards page.

**Schema:**
```sql
CREATE VIEW dw_awards_by_origin AS
SELECT 
    p.birth_country,
    ap.award_id,
    ap.year_id,
    COUNT(*) AS award_count,
    COUNT(*) * 100.0 / 
        (SELECT COUNT(*) FROM staging_awards_players WHERE award_id = ap.award_id AND year_id = ap.year_id) AS award_share
FROM staging_awards_players ap
JOIN staging_people p ON ap.player_id = p.player_id
GROUP BY p.birth_country, ap.award_id, ap.year_id;
```

**Used By:** `awards.php` - Award distribution analysis

---

### 4. `dw_championship_rosters`

**Purpose:** Link World Series championship teams to their roster composition by origin.

**Schema:**
```sql
CREATE VIEW dw_championship_rosters AS
SELECT 
    t.year_id,
    t.team_id,
    t.name AS team_name,
    p.birth_country,
    COUNT(DISTINCT a.player_id) AS player_count,
    COUNT(DISTINCT a.player_id) * 100.0 / 
        (SELECT COUNT(DISTINCT player_id) FROM staging_appearances 
         WHERE team_id = t.team_id AND year_id = t.year_id) AS roster_percentage,
    SUM(a.g_all) AS total_games_played
FROM staging_teams t
JOIN staging_appearances a ON t.team_id = a.team_id AND t.year_id = a.year_id
JOIN staging_people p ON a.player_id = p.player_id
WHERE t.ws_win = 'Y'
GROUP BY t.year_id, t.team_id, t.name, p.birth_country;
```

**Used By:** `championships.php` - Championship team composition

---

### 5. `dw_player_game_contributions`

**Purpose:** Link Retrosheet play-by-play events to player demographics for detailed analysis.

**Schema:**
```sql
CREATE VIEW dw_player_game_contributions AS
SELECT 
    e.game_id,
    e.year,
    e.event_id,
    p.player_id,
    p.birth_country,
    p.name_first || ' ' || p.name_last AS player_name,
    e.bat_id,
    e.pit_id,
    e.event_cd,
    e.event_tx,
    e.event_runs_ct,
    e.inn_ct,
    e.outs_ct
FROM retro_events e
LEFT JOIN staging_people p ON (e.bat_id = p.retro_id OR e.pit_id = p.retro_id);
```

**Used By:** `playbyplay.php` - Detailed event analysis

---

### 6. `dw_impact_index`

**Purpose:** Calculate Impact Index (WAR share / roster share) by origin for comparative analysis.

**Schema:**
```sql
CREATE VIEW dw_impact_index AS
SELECT 
    w.birth_country,
    w.year_id,
    w.total_war,
    w.war_share,
    c.player_count AS roster_count,
    c.percentage AS roster_share,
    (w.war_share / NULLIF(c.percentage, 0)) AS impact_index
FROM dw_war_by_origin w
JOIN dw_yearly_composition c ON w.birth_country = c.birth_country AND w.year_id = c.year
WHERE w.war_share > 0 AND c.percentage > 0;
```

**Used By:** `performance.php`, `index.php` - Impact Index KPI

---

### 7. `dw_decade_summary`

**Purpose:** Pre-aggregate statistics by decade for faster page loading.

**Schema:**
```sql
CREATE VIEW dw_decade_summary AS
SELECT 
    FLOOR(year_id / 10) * 10 AS decade,
    birth_country,
    COUNT(DISTINCT player_id) AS total_players,
    SUM(total_war) AS decade_war,
    COUNT(DISTINCT CASE WHEN award_id IS NOT NULL THEN player_id END) AS award_winners
FROM dw_war_by_origin w
LEFT JOIN dw_awards_by_origin a ON w.birth_country = a.birth_country AND w.year_id = a.year_id
GROUP BY FLOOR(year_id / 10) * 10, birth_country;
```

**Used By:** All pages - Decade filtering

---

### 8. `dw_player_season`

**Purpose:** Canonical wide table with all player-season metrics in one place.

**Schema:**
```sql
CREATE TABLE dw_player_season AS
SELECT 
    p.player_id,
    p.birth_country,
    p.name_first,
    p.name_last,
    b.year_id,
    b.team_id,
    b.g AS games_batting,
    b.ab,
    b.r,
    b.h,
    b.hr,
    b.rbi,
    b.sb,
    b.bb,
    pi.g AS games_pitching,
    pi.w,
    pi.l,
    pi.so,
    pi.era,
    wb.war AS batting_war,
    wp.war AS pitching_war,
    COALESCE(wb.war, 0) + COALESCE(wp.war, 0) AS total_war
FROM staging_people p
LEFT JOIN staging_batting b ON p.player_id = b.player_id
LEFT JOIN staging_pitching pi ON p.player_id = pi.player_id AND b.year_id = pi.year_id
LEFT JOIN staging_war_bat wb ON p.player_id = wb.player_id AND b.year_id = wb.year_id
LEFT JOIN staging_war_pitch wp ON p.player_id = wp.player_id AND b.year_id = wp.year_id;
```

**Used By:** All analysis pages - Comprehensive player-season data

---

## Implementation Strategy

### Phase 1: Basic Views (Immediate)
- `dw_yearly_composition`
- `dw_awards_by_origin`

These views require only staging data and can be created immediately after data loading.

### Phase 2: WAR Integration (After Baseball-Reference load)
- `dw_war_by_origin`
- `dw_impact_index`
- `dw_player_season`

These views require WAR data from Baseball-Reference to be loaded into `staging_war_bat` and `staging_war_pitch`.

### Phase 3: Championship Analysis (After linking appearances)
- `dw_championship_rosters`
- `dw_decade_summary`

These views require the `staging_appearances` table to link players to teams by year.

### Phase 4: Play-by-Play Integration (After Retrosheet event loading)
- `dw_player_game_contributions`

This view requires Retrosheet event data and player ID mapping between Retrosheet and Lahman.

---

## Materialization Strategy

Some views should be materialized (stored as tables) for performance:

1. **`dw_player_season`** - Large join, benefits from indexing
2. **`dw_championship_rosters`** - Complex aggregation, rarely changes
3. **`dw_decade_summary`** - Pre-aggregated, excellent for filtering

Views that can remain as queries:
- `dw_yearly_composition` - Fast aggregation
- `dw_awards_by_origin` - Small dataset
- `dw_impact_index` - Built on other views

---

## Indexes

After creating materialized tables, add these indexes:

```sql
-- Player season indexes
CREATE INDEX idx_player_season_country ON dw_player_season(birth_country);
CREATE INDEX idx_player_season_year ON dw_player_season(year_id);
CREATE INDEX idx_player_season_war ON dw_player_season(total_war);

-- Championship roster indexes
CREATE INDEX idx_championship_year ON dw_championship_rosters(year_id);
CREATE INDEX idx_championship_country ON dw_championship_rosters(birth_country);

-- Decade summary indexes
CREATE INDEX idx_decade_summary ON dw_decade_summary(decade, birth_country);
```

---

## Data Quality Checks

Before building views, verify:

1. **Player IDs are consistent** across all staging tables
2. **Birth country normalization** - handle nulls, empty strings, spelling variations
3. **Year ranges are complete** - no gaps in data
4. **WAR data completeness** - ensure coverage matches batting/pitching data
5. **Retrosheet ID mapping** - link `retro_id` to `player_id`

---

## Next Steps

1. Load staging data using existing scripts
2. Run Phase 1 view creation scripts
3. Validate view results with test queries
4. Create materialized tables for Phase 2
5. Build indexes for query performance
6. Update analysis pages to use views instead of direct staging queries

---

## Maintenance

- **Weekly:** Refresh materialized tables if new data is loaded
- **Monthly:** Verify data quality and completeness
- **Seasonally:** Update views to include latest year's data

For questions or issues, see the main project README or the sql_mysql directory for base schema definitions.
