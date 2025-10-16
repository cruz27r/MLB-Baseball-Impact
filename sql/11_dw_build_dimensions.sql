-- CS437 MLB Global Era - Build Data Warehouse Dimensions
-- 
-- This file populates the dimension tables in the dw schema from source data.
--
-- Prerequisites:
--   - 10_dw_schema.sql must be run first
--   - config/country_map.csv must exist
--
-- Usage:
--   psql -d mlb -f 11_dw_build_dimensions.sql

-- ==========================================================================
-- Populate Countries Dimension
-- ==========================================================================

\echo 'Populating countries dimension...'

-- Create temporary table to load country mapping
CREATE TEMP TABLE IF NOT EXISTS temp_country_map (
    raw_country TEXT,
    iso_code TEXT,
    name TEXT,
    is_latin TEXT,
    region TEXT
);

-- Load country mapping from config file
\copy temp_country_map FROM 'config/country_map.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',');

-- Upsert into dw.countries
INSERT INTO dw.countries (code, name, is_latin, region)
SELECT DISTINCT
    iso_code AS code,
    name,
    CASE WHEN LOWER(is_latin) IN ('true', 't', 'yes', '1') THEN true ELSE false END AS is_latin,
    region
FROM temp_country_map
WHERE iso_code IS NOT NULL AND iso_code != ''
ON CONFLICT (code) DO UPDATE SET
    name = EXCLUDED.name,
    is_latin = EXCLUDED.is_latin,
    region = EXCLUDED.region;

\echo 'Countries dimension populated'

-- ==========================================================================
-- Populate Players Dimension
-- ==========================================================================

\echo 'Populating players dimension from Lahman data...'

-- Map country_raw to country_code using temp table
INSERT INTO dw.players (
    player_id,
    bref_id,
    retro_id,
    name_first,
    name_last,
    birth_year,
    birth_month,
    birth_day,
    country_raw,
    country_code,
    origin_group,
    debut_date,
    final_game_date
)
SELECT 
    p.player_id,
    p.bbref_id AS bref_id,
    p.retro_id,
    p.name_first,
    p.name_last,
    p.birth_year,
    p.birth_month,
    p.birth_day,
    p.birth_country AS country_raw,
    COALESCE(tcm.iso_code, 'UNK') AS country_code,
    CASE 
        WHEN COALESCE(tcm.iso_code, 'UNK') = 'USA' THEN 'USA'
        WHEN c.is_latin = true THEN 'Latin'
        ELSE 'Other'
    END AS origin_group,
    p.debut AS debut_date,
    p.final_game AS final_game_date
FROM core.people p
LEFT JOIN temp_country_map tcm 
    ON TRIM(UPPER(p.birth_country)) = TRIM(UPPER(tcm.raw_country))
LEFT JOIN dw.countries c 
    ON COALESCE(tcm.iso_code, 'UNK') = c.code
ON CONFLICT (player_id) DO UPDATE SET
    bref_id = EXCLUDED.bref_id,
    retro_id = EXCLUDED.retro_id,
    name_first = EXCLUDED.name_first,
    name_last = EXCLUDED.name_last,
    birth_year = EXCLUDED.birth_year,
    birth_month = EXCLUDED.birth_month,
    birth_day = EXCLUDED.birth_day,
    country_raw = EXCLUDED.country_raw,
    country_code = EXCLUDED.country_code,
    origin_group = EXCLUDED.origin_group,
    debut_date = EXCLUDED.debut_date,
    final_game_date = EXCLUDED.final_game_date;

\echo 'Players dimension populated'

-- ==========================================================================
-- Populate Teams Dimension
-- ==========================================================================

\echo 'Populating teams dimension from Lahman data...'

-- Check if lahman.teams or core.teams exists and load accordingly
DO $$ 
BEGIN
    -- First try lahman.teams
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'lahman' AND table_name = 'teams') THEN
        INSERT INTO dw.teams (
            year,
            team_id,
            franch_id,
            team_name,
            lg_id,
            park,
            attendance,
            wins,
            losses,
            division
        )
        SELECT 
            yearid AS year,
            teamid AS team_id,
            franchid AS franch_id,
            name AS team_name,
            lgid AS lg_id,
            park,
            attendance,
            w AS wins,
            l AS losses,
            divid AS division
        FROM lahman.teams
        ON CONFLICT (year, team_id) DO UPDATE SET
            franch_id = EXCLUDED.franch_id,
            team_name = EXCLUDED.team_name,
            lg_id = EXCLUDED.lg_id,
            park = EXCLUDED.park,
            attendance = EXCLUDED.attendance,
            wins = EXCLUDED.wins,
            losses = EXCLUDED.losses,
            division = EXCLUDED.division;
        
        RAISE NOTICE 'Teams dimension populated from lahman.teams';
    -- If not, check if core schema has teams table
    ELSIF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'core' AND table_name = 'teams') THEN
        INSERT INTO dw.teams (
            year,
            team_id,
            franch_id,
            team_name,
            lg_id,
            park,
            attendance,
            wins,
            losses,
            division
        )
        SELECT 
            year_id AS year,
            team_id,
            franch_id,
            name AS team_name,
            lg_id,
            park,
            attendance,
            w AS wins,
            l AS losses,
            div_id AS division
        FROM core.teams
        ON CONFLICT (year, team_id) DO UPDATE SET
            franch_id = EXCLUDED.franch_id,
            team_name = EXCLUDED.team_name,
            lg_id = EXCLUDED.lg_id,
            park = EXCLUDED.park,
            attendance = EXCLUDED.attendance,
            wins = EXCLUDED.wins,
            losses = EXCLUDED.losses,
            division = EXCLUDED.division;
        
        RAISE NOTICE 'Teams dimension populated from core.teams';
    ELSE
        RAISE NOTICE 'No teams table found, deriving from core.appearances...';
        
        -- Try to derive from core.appearances if no teams table exists
        INSERT INTO dw.teams (year, team_id, lg_id)
        SELECT DISTINCT 
            year_id AS year,
            team_id,
            lg_id
        FROM core.appearances
        WHERE team_id IS NOT NULL
        ON CONFLICT (year, team_id) DO NOTHING;
        
        RAISE NOTICE 'Teams dimension populated from core.appearances (limited data)';
    END IF;
END $$;

\echo 'Teams dimension populated'

-- ==========================================================================
-- Summary Statistics
-- ==========================================================================

\echo ''
\echo '==================================================================='
\echo 'Dimension Tables Summary'
\echo '==================================================================='

SELECT 'Countries' AS dimension, COUNT(*) AS record_count FROM dw.countries
UNION ALL
SELECT 'Players' AS dimension, COUNT(*) AS record_count FROM dw.players
UNION ALL
SELECT 'Teams' AS dimension, COUNT(*) AS record_count FROM dw.teams
ORDER BY dimension;

\echo ''
\echo 'Origin Group Distribution:'
SELECT 
    origin_group,
    COUNT(*) AS player_count,
    ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (), 2) AS percentage
FROM dw.players
WHERE origin_group IS NOT NULL
GROUP BY origin_group
ORDER BY player_count DESC;

\echo ''
\echo 'Dimension tables built successfully!'
