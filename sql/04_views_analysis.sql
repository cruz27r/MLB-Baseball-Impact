-- CS437 MLB Global Era - Analysis Views and Tables
-- 
-- This file creates tables and views for analyzing player origins and
-- yearly composition of MLB players.
--
-- Prerequisites:
--   - 01_create_db.sql and 02_load_lahman.sql must be run first

-- ==========================================================================
-- Latin Countries Reference Table
-- ==========================================================================

-- Table defining Latin American and Caribbean countries
CREATE TABLE IF NOT EXISTS core.latin_countries (
    country_code VARCHAR(3) PRIMARY KEY,
    country_name VARCHAR(50) NOT NULL,
    region VARCHAR(50),
    is_latin_america BOOLEAN DEFAULT true
);

-- Insert Latin American and Caribbean countries
INSERT INTO core.latin_countries (country_code, country_name, region, is_latin_america)
VALUES
    ('MEX', 'Mexico', 'North America', true),
    ('CUB', 'Cuba', 'Caribbean', true),
    ('DOM', 'Dominican Republic', 'Caribbean', true),
    ('PRI', 'Puerto Rico', 'Caribbean', true),
    ('VEN', 'Venezuela', 'South America', true),
    ('COL', 'Colombia', 'South America', true),
    ('PAN', 'Panama', 'Central America', true),
    ('NIC', 'Nicaragua', 'Central America', true),
    ('BRA', 'Brazil', 'South America', true),
    ('ARG', 'Argentina', 'South America', true),
    ('CHL', 'Chile', 'South America', true),
    ('PER', 'Peru', 'South America', true),
    ('ECU', 'Ecuador', 'South America', true),
    ('URY', 'Uruguay', 'South America', true),
    ('BOL', 'Bolivia', 'South America', true),
    ('PRY', 'Paraguay', 'South America', true),
    ('CRI', 'Costa Rica', 'Central America', true),
    ('GTM', 'Guatemala', 'Central America', true),
    ('HND', 'Honduras', 'Central America', true),
    ('SLV', 'El Salvador', 'Central America', true),
    ('BLZ', 'Belize', 'Central America', true),
    ('JAM', 'Jamaica', 'Caribbean', true),
    ('HTI', 'Haiti', 'Caribbean', true),
    ('TTO', 'Trinidad and Tobago', 'Caribbean', true),
    ('BAH', 'Bahamas', 'Caribbean', true),
    ('BAR', 'Barbados', 'Caribbean', true),
    ('CUW', 'Curacao', 'Caribbean', true),
    ('ARU', 'Aruba', 'Caribbean', true)
ON CONFLICT (country_code) DO NOTHING;

-- Index on country name
CREATE INDEX IF NOT EXISTS idx_latin_countries_name ON core.latin_countries(country_name);
CREATE INDEX IF NOT EXISTS idx_latin_countries_region ON core.latin_countries(region);

COMMENT ON TABLE core.latin_countries IS 
    'Reference table of Latin American and Caribbean countries';

-- ==========================================================================
-- Player Origin View
-- ==========================================================================

-- View that categorizes players by origin (USA, Latin America, Other)
CREATE OR REPLACE VIEW core.player_origin AS
SELECT 
    p.player_id,
    p.name_first,
    p.name_last,
    p.birth_year,
    p.birth_country,
    p.birth_state,
    p.birth_city,
    CASE 
        WHEN p.birth_country = 'USA' THEN 'USA'
        WHEN lc.country_code IS NOT NULL THEN 'Latin America'
        ELSE 'Other'
    END AS origin_category,
    lc.country_name AS latin_country_name,
    lc.region AS latin_region,
    p.debut,
    p.final_game,
    EXTRACT(YEAR FROM p.debut) AS debut_year,
    EXTRACT(YEAR FROM p.final_game) AS final_year
FROM core.people p
LEFT JOIN core.latin_countries lc 
    ON p.birth_country = lc.country_code;

COMMENT ON VIEW core.player_origin IS 
    'Categorizes players by origin: USA, Latin America, or Other';

-- ==========================================================================
-- Yearly Composition Materialized View
-- ==========================================================================

-- Materialized view showing yearly composition of players by origin
CREATE MATERIALIZED VIEW IF NOT EXISTS core.mv_yearly_composition AS
WITH yearly_players AS (
    SELECT DISTINCT
        a.year_id,
        a.player_id,
        po.origin_category,
        po.birth_country,
        po.latin_country_name,
        po.latin_region
    FROM core.appearances a
    INNER JOIN core.player_origin po ON a.player_id = po.player_id
    WHERE a.g_all > 0  -- Only count players who actually appeared in games
)
SELECT 
    year_id,
    origin_category,
    COUNT(DISTINCT player_id) AS player_count,
    COUNT(DISTINCT birth_country) AS country_count,
    ROUND(
        100.0 * COUNT(DISTINCT player_id) / 
        SUM(COUNT(DISTINCT player_id)) OVER (PARTITION BY year_id), 
        2
    ) AS percentage,
    STRING_AGG(DISTINCT birth_country, ', ' ORDER BY birth_country) AS countries
FROM yearly_players
GROUP BY year_id, origin_category
ORDER BY year_id DESC, player_count DESC;

-- Index on materialized view for fast queries
CREATE UNIQUE INDEX IF NOT EXISTS idx_yearly_composition_year_origin 
    ON core.mv_yearly_composition(year_id, origin_category);
CREATE INDEX IF NOT EXISTS idx_yearly_composition_year 
    ON core.mv_yearly_composition(year_id);
CREATE INDEX IF NOT EXISTS idx_yearly_composition_origin 
    ON core.mv_yearly_composition(origin_category);

COMMENT ON MATERIALIZED VIEW core.mv_yearly_composition IS 
    'Yearly composition of MLB players by origin category (USA, Latin America, Other)';

-- ==========================================================================
-- Additional Analysis View - Latin American Players by Country
-- ==========================================================================

-- Materialized view for detailed Latin American player statistics by country
CREATE MATERIALIZED VIEW IF NOT EXISTS core.mv_latin_players_by_country AS
WITH latin_player_stats AS (
    SELECT 
        po.player_id,
        po.latin_country_name,
        po.latin_region,
        po.debut_year,
        a.year_id,
        a.g_all,
        b.ab,
        b.h,
        b.hr,
        b.rbi,
        p.w AS wins,
        p.l AS losses,
        p.so AS strikeouts,
        p.era
    FROM core.player_origin po
    INNER JOIN core.appearances a ON po.player_id = a.player_id
    LEFT JOIN core.batting b ON po.player_id = b.player_id AND a.year_id = b.year_id
    LEFT JOIN core.pitching p ON po.player_id = p.player_id AND a.year_id = p.year_id
    WHERE po.origin_category = 'Latin America'
        AND a.g_all > 0
)
SELECT 
    latin_country_name AS country,
    latin_region AS region,
    MIN(debut_year) AS first_debut_year,
    MAX(year_id) AS last_active_year,
    COUNT(DISTINCT player_id) AS total_players,
    SUM(g_all) AS total_games,
    SUM(ab) AS total_at_bats,
    SUM(h) AS total_hits,
    SUM(hr) AS total_home_runs,
    SUM(rbi) AS total_rbi,
    SUM(wins) AS total_wins,
    SUM(strikeouts) AS total_strikeouts
FROM latin_player_stats
GROUP BY latin_country_name, latin_region
ORDER BY total_players DESC;

-- Index on country materialized view
CREATE UNIQUE INDEX IF NOT EXISTS idx_latin_by_country_country 
    ON core.mv_latin_players_by_country(country);
CREATE INDEX IF NOT EXISTS idx_latin_by_country_region 
    ON core.mv_latin_players_by_country(region);

COMMENT ON MATERIALIZED VIEW core.mv_latin_players_by_country IS 
    'Aggregated statistics for Latin American players grouped by country';

-- ==========================================================================
-- Refresh Function
-- ==========================================================================

-- Function to refresh all analysis materialized views
CREATE OR REPLACE FUNCTION core.refresh_analysis_views()
RETURNS void AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_yearly_composition;
    REFRESH MATERIALIZED VIEW CONCURRENTLY core.mv_latin_players_by_country;
    
    RAISE NOTICE 'Analysis materialized views refreshed at %', NOW();
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION core.refresh_analysis_views IS 
    'Refresh all analysis materialized views concurrently';

\echo 'Analysis views and tables created successfully!'
