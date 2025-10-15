-- CS437 MLB Global Era - WAR Analysis by Player Origin
-- 
-- This file creates materialized views for analyzing Wins Above Replacement (WAR)
-- by player origin and computing the Impact Index metric.
--
-- Prerequisites:
--   - 01_create_db.sql, 03_load_bref_war.sql, and 04_views_analysis.sql must be run first
--   - WAR data must be loaded and parsed via etl/ingest_bref_war.py
--
-- Usage:
--   psql -d mlb -f 06_war_by_origin.sql

-- ==========================================================================
-- WAR by Origin Materialized View
-- ==========================================================================

\echo 'Creating WAR by origin materialized view...'

-- Materialized view aggregating WAR by year and player origin
CREATE MATERIALIZED VIEW IF NOT EXISTS core.mv_war_by_origin AS
WITH war_union AS (
    SELECT playerid, yearid, WAR 
    FROM bref.war_bat
    WHERE WAR IS NOT NULL
    UNION ALL
    SELECT playerid, yearid, WAR 
    FROM bref.war_pitch
    WHERE WAR IS NOT NULL
),
war_by_player_year AS (
    SELECT 
        playerid, 
        yearid, 
        SUM(WAR) AS war_year
    FROM war_union 
    GROUP BY playerid, yearid
)
SELECT 
    w.yearid AS year,
    po.origin,
    SUM(w.war_year) AS total_war,
    COUNT(*) AS players,
    AVG(w.war_year) AS avg_war,
    MIN(w.war_year) AS min_war,
    MAX(w.war_year) AS max_war,
    STDDEV(w.war_year) AS stddev_war
FROM war_by_player_year w
JOIN core.player_origin po ON po.player_id = w.playerid
GROUP BY w.yearid, po.origin
ORDER BY year DESC, origin;

-- Index on war_by_origin
CREATE UNIQUE INDEX IF NOT EXISTS idx_war_by_origin_year_origin 
    ON core.mv_war_by_origin(year, origin);
CREATE INDEX IF NOT EXISTS idx_war_by_origin_year 
    ON core.mv_war_by_origin(year);
CREATE INDEX IF NOT EXISTS idx_war_by_origin_origin 
    ON core.mv_war_by_origin(origin);

COMMENT ON MATERIALIZED VIEW core.mv_war_by_origin IS 
    'Aggregated WAR statistics by year and player origin (USA, Latin, Other)';

-- ==========================================================================
-- Impact Index Materialized View
-- ==========================================================================

\echo 'Creating Impact Index materialized view...'

-- Materialized view computing the Impact Index for each origin group by year
-- Impact Index = (WAR Share) / (Roster Share)
-- A value > 1 means the group contributes more WAR than their roster representation
-- A value < 1 means the group contributes less WAR than their roster representation
CREATE MATERIALIZED VIEW IF NOT EXISTS core.mv_impact_index AS
WITH comp AS (
    SELECT 
        year, 
        total_players, 
        us_players, 
        latin_players, 
        foreign_players
    FROM core.mv_yearly_composition
),
war_tot AS (
    SELECT 
        year, 
        SUM(total_war) AS war_all 
    FROM core.mv_war_by_origin 
    GROUP BY year
),
shares AS (
    SELECT 
        c.year,
        mb.origin,
        CASE mb.origin 
            WHEN 'USA' THEN us_players::float
            WHEN 'Latin' THEN latin_players::float
            ELSE foreign_players::float 
        END / NULLIF(c.total_players, 0) AS roster_share,
        mb.total_war / NULLIF(w.war_all, 0) AS war_share
    FROM war_tot w
    JOIN core.mv_war_by_origin mb ON mb.year = w.year
    JOIN comp c ON c.year = w.year
)
SELECT 
    year,
    origin,
    roster_share,
    war_share,
    CASE 
        WHEN roster_share > 0 THEN war_share / roster_share 
        ELSE NULL 
    END AS impact_index,
    ROUND(roster_share * 100, 2) AS roster_percentage,
    ROUND(war_share * 100, 2) AS war_percentage
FROM shares
ORDER BY year DESC, origin;

-- Index on impact_index
CREATE UNIQUE INDEX IF NOT EXISTS idx_impact_index_year_origin 
    ON core.mv_impact_index(year, origin);
CREATE INDEX IF NOT EXISTS idx_impact_index_year 
    ON core.mv_impact_index(year);
CREATE INDEX IF NOT EXISTS idx_impact_index_origin 
    ON core.mv_impact_index(origin);

COMMENT ON MATERIALIZED VIEW core.mv_impact_index IS 
    'Impact Index metric showing relative contribution to WAR compared to roster share';

-- ==========================================================================
-- Top WAR Contributors by Origin
-- ==========================================================================

\echo 'Creating top WAR contributors view...'

-- Materialized view showing top individual WAR contributors by origin
CREATE MATERIALIZED VIEW IF NOT EXISTS core.mv_top_war_contributors AS
WITH war_union AS (
    SELECT playerid, yearid, WAR 
    FROM bref.war_bat
    WHERE WAR IS NOT NULL
    UNION ALL
    SELECT playerid, yearid, WAR 
    FROM bref.war_pitch
    WHERE WAR IS NOT NULL
),
war_by_player_year AS (
    SELECT 
        playerid, 
        yearid, 
        SUM(WAR) AS war_year
    FROM war_union 
    GROUP BY playerid, yearid
),
ranked AS (
    SELECT 
        w.playerid,
        po.name_first,
        po.name_last,
        po.origin,
        po.birth_country,
        w.yearid AS year,
        w.war_year AS war,
        ROW_NUMBER() OVER (
            PARTITION BY w.yearid, po.origin 
            ORDER BY w.war_year DESC
        ) AS rank_in_origin
    FROM war_by_player_year w
    JOIN core.player_origin po ON po.player_id = w.playerid
    WHERE w.war_year IS NOT NULL
)
SELECT 
    playerid,
    name_first,
    name_last,
    origin,
    birth_country,
    year,
    war,
    rank_in_origin
FROM ranked
WHERE rank_in_origin <= 10  -- Top 10 per origin per year
ORDER BY year DESC, origin, rank_in_origin;

-- Index on top_war_contributors
CREATE INDEX IF NOT EXISTS idx_top_war_year 
    ON core.mv_top_war_contributors(year);
CREATE INDEX IF NOT EXISTS idx_top_war_origin 
    ON core.mv_top_war_contributors(origin);
CREATE INDEX IF NOT EXISTS idx_top_war_player 
    ON core.mv_top_war_contributors(playerid);

COMMENT ON MATERIALIZED VIEW core.mv_top_war_contributors IS 
    'Top 10 WAR contributors per year per origin group';

-- ==========================================================================
-- Refresh Function
-- ==========================================================================

\echo 'Creating WAR views refresh function...'

-- Function to refresh all WAR-related materialized views
CREATE OR REPLACE FUNCTION core.refresh_war_views()
RETURNS void AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_war_by_origin;
    REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_impact_index;
    REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_top_war_contributors;
    
    RAISE NOTICE 'WAR materialized views refreshed at %', NOW();
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION core.refresh_war_views IS 
    'Refresh all WAR-related materialized views concurrently';

\echo '✓ WAR analysis views created successfully!'
