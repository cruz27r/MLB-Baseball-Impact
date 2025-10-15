-- CS437 MLB Global Era - Data Warehouse Validation Script
-- 
-- This script validates the data warehouse implementation by checking
-- key metrics and data quality indicators.
--
-- Usage:
--   psql -d mlb -f sql/99_validate_dw.sql

\echo ''
\echo '==================================================================='
\echo 'DATA WAREHOUSE VALIDATION'
\echo '==================================================================='
\echo ''

-- ==========================================================================
-- Check Schema Existence
-- ==========================================================================

\echo 'Checking schema existence...'
SELECT 
    schema_name,
    CASE 
        WHEN schema_name IN (SELECT schema_name FROM information_schema.schemata) 
        THEN '✓ EXISTS'
        ELSE '✗ MISSING'
    END AS status
FROM (VALUES ('core'), ('bref'), ('lahman'), ('retrosheet'), ('dw')) AS s(schema_name);

\echo ''

-- ==========================================================================
-- Check Table Record Counts
-- ==========================================================================

\echo 'Checking table record counts...'
\echo ''

\echo 'Core Schema:'
SELECT 
    'core.people' AS table_name,
    COUNT(*) AS record_count,
    CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END AS status
FROM core.people
UNION ALL
SELECT 'core.batting', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.batting
UNION ALL
SELECT 'core.pitching', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.pitching
UNION ALL
SELECT 'core.appearances', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.appearances
UNION ALL
SELECT 'core.awardsplayers', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.awardsplayers
UNION ALL
SELECT 'core.teams', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.teams
UNION ALL
SELECT 'core.fielding', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.fielding
UNION ALL
SELECT 'core.allstarfull', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.allstarfull
UNION ALL
SELECT 'core.seriespost', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM core.seriespost;

\echo ''
\echo 'B-Ref WAR Schema:'
SELECT 
    'bref.war_bat' AS table_name,
    COUNT(*) AS record_count,
    CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END AS status
FROM bref.war_bat
UNION ALL
SELECT 'bref.war_pitch', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM bref.war_pitch;

\echo ''
\echo 'Data Warehouse Schema:'
SELECT 
    'dw.countries' AS table_name,
    COUNT(*) AS record_count,
    CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END AS status
FROM dw.countries
UNION ALL
SELECT 'dw.players', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.players
UNION ALL
SELECT 'dw.teams', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.teams
UNION ALL
SELECT 'dw.player_teams', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.player_teams
UNION ALL
SELECT 'dw.batting_season', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.batting_season
UNION ALL
SELECT 'dw.pitching_season', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.pitching_season
UNION ALL
SELECT 'dw.awards_season', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.awards_season
UNION ALL
SELECT 'dw.war_season', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.war_season
UNION ALL
SELECT 'dw.postseason_team', COUNT(*), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.postseason_team
UNION ALL
SELECT 'dw.player_season', COUNT(*), CASE WHEN COUNT(*) >= 100000 THEN '✓ (>=100k)' ELSE '⚠ (<100k)' END FROM dw.player_season;

\echo ''

-- ==========================================================================
-- Validate Player Season Coverage
-- ==========================================================================

\echo 'Player Season Coverage:'
SELECT 
    COUNT(*) AS total_player_seasons,
    COUNT(CASE WHEN war_total IS NOT NULL THEN 1 END) AS with_war,
    ROUND(100.0 * COUNT(CASE WHEN war_total IS NOT NULL THEN 1 END) / COUNT(*), 2) AS war_coverage_pct,
    COUNT(CASE WHEN country_code IS NOT NULL AND country_code != 'UNK' THEN 1 END) AS with_country,
    ROUND(100.0 * COUNT(CASE WHEN country_code IS NOT NULL AND country_code != 'UNK' THEN 1 END) / COUNT(*), 2) AS country_coverage_pct,
    COUNT(CASE WHEN origin_group IS NOT NULL THEN 1 END) AS with_origin,
    ROUND(100.0 * COUNT(CASE WHEN origin_group IS NOT NULL THEN 1 END) / COUNT(*), 2) AS origin_coverage_pct
FROM dw.player_season;

\echo ''

-- ==========================================================================
-- Validate Origin Group Distribution
-- ==========================================================================

\echo 'Origin Group Distribution:'
SELECT 
    origin_group,
    COUNT(*) AS player_seasons,
    ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (), 2) AS percentage
FROM dw.player_season
WHERE origin_group IS NOT NULL
GROUP BY origin_group
ORDER BY player_seasons DESC;

\echo ''

-- ==========================================================================
-- Validate Materialized Views
-- ==========================================================================

\echo 'Materialized Views:'
SELECT 
    'dw.mv_yearly_composition' AS view_name,
    COUNT(*) AS record_count,
    MIN(year) AS min_year,
    MAX(year) AS max_year,
    CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END AS status
FROM dw.mv_yearly_composition
UNION ALL
SELECT 'dw.mv_war_by_origin', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_war_by_origin
UNION ALL
SELECT 'dw.mv_awards_share', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_awards_share
UNION ALL
SELECT 'dw.mv_hr25_by_origin', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_hr25_by_origin
UNION ALL
SELECT 'dw.mv_championship_contrib', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_championship_contrib
UNION ALL
SELECT 'dw.mv_interest_proxies', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_interest_proxies
UNION ALL
SELECT 'dw.mv_impact_index', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_impact_index
UNION ALL
SELECT 'dw.mv_top_war_contributors', COUNT(*), MIN(year), MAX(year), CASE WHEN COUNT(*) > 0 THEN '✓' ELSE '✗' END FROM dw.mv_top_war_contributors;

\echo ''

-- ==========================================================================
-- Sample Impact Index (Recent Years)
-- ==========================================================================

\echo 'Sample Impact Index (5 Most Recent Years):'
SELECT 
    year,
    origin_group,
    roster_share,
    war_share,
    impact_index
FROM dw.mv_impact_index
WHERE year >= (SELECT MAX(year) - 4 FROM dw.mv_impact_index)
ORDER BY year DESC, origin_group;

\echo ''

-- ==========================================================================
-- Validate WAR Aggregation Accuracy
-- ==========================================================================

\echo 'WAR Aggregation Validation (2019 if available):'
WITH player_season_war AS (
    SELECT 
        year,
        SUM(war_total) AS ps_total_war
    FROM dw.player_season
    WHERE year = 2019
    GROUP BY year
),
mv_war AS (
    SELECT 
        year,
        SUM(total_war) AS mv_total_war
    FROM dw.mv_war_by_origin
    WHERE year = 2019
    GROUP BY year
)
SELECT 
    COALESCE(ps.year, mv.year) AS year,
    ps.ps_total_war AS player_season_war,
    mv.mv_total_war AS materialized_view_war,
    ABS(ps.ps_total_war - mv.mv_total_war) AS difference,
    CASE 
        WHEN ABS(ps.ps_total_war - mv.mv_total_war) < 0.01 THEN '✓ Match'
        ELSE '⚠ Mismatch'
    END AS validation
FROM player_season_war ps
FULL OUTER JOIN mv_war mv ON ps.year = mv.year;

\echo ''

-- ==========================================================================
-- ID Crosswalk Quality
-- ==========================================================================

\echo 'ID Crosswalk Quality (WAR to Lahman):'
WITH war_players AS (
    SELECT COUNT(DISTINCT playerid) AS total_war_players
    FROM (
        SELECT playerid FROM bref.war_bat WHERE yearid >= 2000
        UNION
        SELECT playerid FROM bref.war_pitch WHERE yearid >= 2000
    ) t
    WHERE playerid IS NOT NULL
),
mapped_players AS (
    SELECT COUNT(DISTINCT playerid) AS mapped_war_players
    FROM (
        SELECT b.playerid 
        FROM bref.war_bat b
        INNER JOIN dw.players p ON b.playerid = p.player_id
        WHERE b.yearid >= 2000
        UNION
        SELECT bp.playerid 
        FROM bref.war_pitch bp
        INNER JOIN dw.players p ON bp.playerid = p.player_id
        WHERE bp.yearid >= 2000
    ) t
)
SELECT 
    wp.total_war_players,
    mp.mapped_war_players,
    ROUND(100.0 * mp.mapped_war_players / NULLIF(wp.total_war_players, 0), 2) AS mapping_coverage_pct,
    CASE 
        WHEN ROUND(100.0 * mp.mapped_war_players / NULLIF(wp.total_war_players, 0), 2) >= 95 THEN '✓ >=95%'
        WHEN ROUND(100.0 * mp.mapped_war_players / NULLIF(wp.total_war_players, 0), 2) >= 90 THEN '⚠ >=90%'
        ELSE '✗ <90%'
    END AS status
FROM war_players wp, mapped_players mp;

\echo ''
\echo '==================================================================='
\echo 'VALIDATION COMPLETE'
\echo '==================================================================='
\echo ''
\echo 'If all checks show ✓, the data warehouse is ready for analysis.'
\echo 'To refresh materialized views: SELECT dw.refresh_materialized_views();'
\echo ''
