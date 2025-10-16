-- CS437 MLB Global Era - Data Warehouse Materialized Views
-- 
-- This file creates materialized views for analysis and storytelling
-- from the dw schema canonical tables.
--
-- Prerequisites:
--   - 12_dw_build_facts.sql must be run first
--
-- Usage:
--   psql -d mlb -f 13_dw_materialized_views.sql

-- ==========================================================================
-- Yearly Composition Materialized View
-- ==========================================================================

\echo 'Creating yearly composition materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_yearly_composition CASCADE;
CREATE MATERIALIZED VIEW dw.mv_yearly_composition AS
WITH yearly_totals AS (
    SELECT 
        year,
        COUNT(DISTINCT player_id) AS total_players
    FROM dw.player_season
    WHERE was_on_roster = true
    GROUP BY year
),
yearly_by_origin AS (
    SELECT 
        year,
        origin_group,
        COUNT(DISTINCT player_id) AS player_count
    FROM dw.player_season
    WHERE was_on_roster = true AND origin_group IS NOT NULL
    GROUP BY year, origin_group
)
SELECT 
    t.year,
    t.total_players,
    SUM(CASE WHEN o.origin_group = 'USA' THEN o.player_count ELSE 0 END) AS us_players,
    SUM(CASE WHEN o.origin_group = 'Latin' THEN o.player_count ELSE 0 END) AS latin_players,
    SUM(CASE WHEN o.origin_group = 'Other' THEN o.player_count ELSE 0 END) AS other_players,
    ROUND(100.0 * SUM(CASE WHEN o.origin_group = 'USA' THEN o.player_count ELSE 0 END) / 
          NULLIF(t.total_players, 0), 2) AS us_share,
    ROUND(100.0 * SUM(CASE WHEN o.origin_group = 'Latin' THEN o.player_count ELSE 0 END) / 
          NULLIF(t.total_players, 0), 2) AS latin_share,
    ROUND(100.0 * SUM(CASE WHEN o.origin_group = 'Other' THEN o.player_count ELSE 0 END) / 
          NULLIF(t.total_players, 0), 2) AS other_share
FROM yearly_totals t
LEFT JOIN yearly_by_origin o ON t.year = o.year
GROUP BY t.year, t.total_players
ORDER BY t.year;

CREATE UNIQUE INDEX idx_mv_yearly_composition_year ON dw.mv_yearly_composition(year);

COMMENT ON MATERIALIZED VIEW dw.mv_yearly_composition IS 
    'Yearly player composition by origin (USA, Latin, Other)';

-- ==========================================================================
-- WAR by Origin Materialized View
-- ==========================================================================

\echo 'Creating WAR by origin materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_war_by_origin CASCADE;
CREATE MATERIALIZED VIEW dw.mv_war_by_origin AS
SELECT 
    year,
    origin_group,
    SUM(war_total) AS total_war,
    COUNT(DISTINCT player_id) AS players,
    ROUND(AVG(war_total), 3) AS avg_war,
    ROUND(100.0 * SUM(war_total) / SUM(SUM(war_total)) OVER (PARTITION BY year), 2) AS war_share
FROM dw.player_season
WHERE war_total IS NOT NULL AND origin_group IS NOT NULL
GROUP BY year, origin_group
ORDER BY year, origin_group;

CREATE INDEX idx_mv_war_by_origin_year ON dw.mv_war_by_origin(year);
CREATE INDEX idx_mv_war_by_origin_origin ON dw.mv_war_by_origin(origin_group);
CREATE INDEX idx_mv_war_by_origin_year_origin ON dw.mv_war_by_origin(year, origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_war_by_origin IS 
    'WAR aggregated by player origin and year';

-- ==========================================================================
-- Awards Share Materialized View
-- ==========================================================================

\echo 'Creating awards share materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_awards_share CASCADE;
CREATE MATERIALIZED VIEW dw.mv_awards_share AS
WITH yearly_awards AS (
    SELECT 
        year,
        origin_group,
        SUM(awards_total) AS awards_total,
        SUM(mvp_count) AS mvp,
        SUM(cy_count) AS cy,
        SUM(roy_count) AS roy,
        SUM(allstar_count) AS allstar_total
    FROM dw.player_season
    WHERE origin_group IS NOT NULL
    GROUP BY year, origin_group
)
SELECT 
    year,
    origin_group,
    awards_total,
    mvp,
    cy,
    roy,
    allstar_total,
    ROUND(100.0 * awards_total / NULLIF(SUM(awards_total) OVER (PARTITION BY year), 0), 2) AS awards_share,
    ROUND(100.0 * mvp / NULLIF(SUM(mvp) OVER (PARTITION BY year), 0), 2) AS mvp_share,
    ROUND(100.0 * cy / NULLIF(SUM(cy) OVER (PARTITION BY year), 0), 2) AS cy_share,
    ROUND(100.0 * roy / NULLIF(SUM(roy) OVER (PARTITION BY year), 0), 2) AS roy_share,
    ROUND(100.0 * allstar_total / NULLIF(SUM(allstar_total) OVER (PARTITION BY year), 0), 2) AS allstar_share
FROM yearly_awards
ORDER BY year, origin_group;

CREATE INDEX idx_mv_awards_share_year ON dw.mv_awards_share(year);
CREATE INDEX idx_mv_awards_share_origin ON dw.mv_awards_share(origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_awards_share IS 
    'Awards distribution by origin with share percentages';

-- ==========================================================================
-- HR25+ by Origin Materialized View
-- ==========================================================================

\echo 'Creating HR25+ by origin materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_hr25_by_origin CASCADE;
CREATE MATERIALIZED VIEW dw.mv_hr25_by_origin AS
SELECT 
    year,
    origin_group,
    COUNT(DISTINCT player_id) AS count_25hr,
    ROUND(100.0 * COUNT(DISTINCT player_id) / 
          SUM(COUNT(DISTINCT player_id)) OVER (PARTITION BY year), 2) AS share_25hr
FROM dw.player_season
WHERE hr >= 25 AND origin_group IS NOT NULL
GROUP BY year, origin_group
ORDER BY year, origin_group;

CREATE INDEX idx_mv_hr25_by_origin_year ON dw.mv_hr25_by_origin(year);
CREATE INDEX idx_mv_hr25_by_origin_origin ON dw.mv_hr25_by_origin(origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_hr25_by_origin IS 
    'Count of players with 25+ home runs by origin';

-- ==========================================================================
-- Championship Contribution Materialized View
-- ==========================================================================

\echo 'Creating championship contribution materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_championship_contrib CASCADE;
CREATE MATERIALIZED VIEW dw.mv_championship_contrib AS
SELECT 
    year,
    origin_group,
    SUM(CASE WHEN deep_run = true THEN war_total ELSE 0 END) AS war_on_contenders,
    SUM(CASE WHEN is_champion = true THEN war_total ELSE 0 END) AS war_on_champions,
    COUNT(DISTINCT CASE WHEN deep_run = true THEN player_id END) AS contender_players,
    COUNT(DISTINCT CASE WHEN is_champion = true THEN player_id END) AS champion_players
FROM dw.player_season
WHERE war_total IS NOT NULL AND origin_group IS NOT NULL
GROUP BY year, origin_group
ORDER BY year, origin_group;

CREATE INDEX idx_mv_championship_contrib_year ON dw.mv_championship_contrib(year);
CREATE INDEX idx_mv_championship_contrib_origin ON dw.mv_championship_contrib(origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_championship_contrib IS 
    'WAR contribution by origin on contending and championship teams';

-- ==========================================================================
-- Interest Proxies (Attendance) Materialized View
-- ==========================================================================

\echo 'Creating interest proxies materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_interest_proxies CASCADE;
CREATE MATERIALIZED VIEW dw.mv_interest_proxies AS
SELECT 
    year,
    origin_group,
    AVG(avg_team_attendance) AS avg_attendance,
    SUM(avg_team_attendance * COALESCE(war_total, 0)) / 
        NULLIF(SUM(COALESCE(war_total, 0)), 0) AS weighted_attendance
FROM dw.player_season
WHERE avg_team_attendance IS NOT NULL AND origin_group IS NOT NULL
GROUP BY year, origin_group
ORDER BY year, origin_group;

CREATE INDEX idx_mv_interest_proxies_year ON dw.mv_interest_proxies(year);
CREATE INDEX idx_mv_interest_proxies_origin ON dw.mv_interest_proxies(origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_interest_proxies IS 
    'Attendance metrics aggregated by player origin';

-- ==========================================================================
-- Impact Index Materialized View
-- ==========================================================================

\echo 'Creating impact index materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_impact_index CASCADE;
CREATE MATERIALIZED VIEW dw.mv_impact_index AS
WITH composition AS (
    SELECT 
        year,
        origin_group,
        CASE origin_group
            WHEN 'USA' THEN us_share
            WHEN 'Latin' THEN latin_share
            WHEN 'Other' THEN other_share
            ELSE 0
        END AS roster_share
    FROM dw.mv_yearly_composition
    CROSS JOIN (VALUES ('USA'), ('Latin'), ('Other')) AS origins(origin_group)
),
war_shares AS (
    SELECT 
        year,
        origin_group,
        war_share
    FROM dw.mv_war_by_origin
)
SELECT 
    c.year,
    c.origin_group,
    c.roster_share,
    COALESCE(w.war_share, 0) AS war_share,
    CASE 
        WHEN c.roster_share > 0 
        THEN ROUND(COALESCE(w.war_share, 0) / c.roster_share, 3)
        ELSE 0
    END AS impact_index
FROM composition c
LEFT JOIN war_shares w ON c.year = w.year AND c.origin_group = w.origin_group
WHERE c.roster_share > 0
ORDER BY c.year, c.origin_group;

CREATE INDEX idx_mv_impact_index_year ON dw.mv_impact_index(year);
CREATE INDEX idx_mv_impact_index_origin ON dw.mv_impact_index(origin_group);
CREATE INDEX idx_mv_impact_index_year_origin ON dw.mv_impact_index(year, origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_impact_index IS 
    'Impact Index: WAR share divided by roster share (>1 means outperforming representation)';

-- ==========================================================================
-- Top WAR Contributors by Origin
-- ==========================================================================

\echo 'Creating top WAR contributors materialized view...'

DROP MATERIALIZED VIEW IF EXISTS dw.mv_top_war_contributors CASCADE;
CREATE MATERIALIZED VIEW dw.mv_top_war_contributors AS
WITH ranked_players AS (
    SELECT 
        year,
        origin_group,
        player_id,
        name_first,
        name_last,
        war_total,
        ROW_NUMBER() OVER (PARTITION BY year, origin_group ORDER BY war_total DESC) AS rank_in_group
    FROM dw.player_season
    WHERE war_total IS NOT NULL AND origin_group IS NOT NULL
)
SELECT 
    year,
    origin_group,
    player_id,
    name_first,
    name_last,
    war_total,
    rank_in_group
FROM ranked_players
WHERE rank_in_group <= 10  -- Top 10 per origin per year
ORDER BY year DESC, origin_group, rank_in_group;

CREATE INDEX idx_mv_top_war_contributors_year ON dw.mv_top_war_contributors(year);
CREATE INDEX idx_mv_top_war_contributors_origin ON dw.mv_top_war_contributors(origin_group);

COMMENT ON MATERIALIZED VIEW dw.mv_top_war_contributors IS 
    'Top 10 WAR contributors per origin group per year';

-- ==========================================================================
-- Refresh Function
-- ==========================================================================

\echo 'Creating refresh function...'

CREATE OR REPLACE FUNCTION dw.refresh_materialized_views()
RETURNS void AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_yearly_composition;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_war_by_origin;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_awards_share;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_hr25_by_origin;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_championship_contrib;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_interest_proxies;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_impact_index;
    REFRESH MATERIALIZED VIEW CONCURRENTLY dw.mv_top_war_contributors;
    
    RAISE NOTICE 'All DW materialized views refreshed at %', NOW();
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION dw.refresh_materialized_views IS 
    'Refresh all data warehouse materialized views concurrently';

-- ==========================================================================
-- Summary Statistics
-- ==========================================================================

\echo ''
\echo '==================================================================='
\echo 'Materialized Views Summary'
\echo '==================================================================='

SELECT 'Yearly Composition' AS view_name, COUNT(*) AS record_count FROM dw.mv_yearly_composition
UNION ALL
SELECT 'WAR by Origin' AS view_name, COUNT(*) AS record_count FROM dw.mv_war_by_origin
UNION ALL
SELECT 'Awards Share' AS view_name, COUNT(*) AS record_count FROM dw.mv_awards_share
UNION ALL
SELECT 'HR25+ by Origin' AS view_name, COUNT(*) AS record_count FROM dw.mv_hr25_by_origin
UNION ALL
SELECT 'Championship Contribution' AS view_name, COUNT(*) AS record_count FROM dw.mv_championship_contrib
UNION ALL
SELECT 'Interest Proxies' AS view_name, COUNT(*) AS record_count FROM dw.mv_interest_proxies
UNION ALL
SELECT 'Impact Index' AS view_name, COUNT(*) AS record_count FROM dw.mv_impact_index
UNION ALL
SELECT 'Top WAR Contributors' AS view_name, COUNT(*) AS record_count FROM dw.mv_top_war_contributors
ORDER BY view_name;

\echo ''
\echo 'Sample Impact Index (Recent Years):'
SELECT 
    year,
    origin_group,
    roster_share,
    war_share,
    impact_index
FROM dw.mv_impact_index
WHERE year >= (SELECT MAX(year) - 5 FROM dw.mv_impact_index)
ORDER BY year DESC, origin_group
LIMIT 15;

\echo ''
\echo 'Materialized views created successfully!'
\echo 'To refresh: SELECT dw.refresh_materialized_views();'
