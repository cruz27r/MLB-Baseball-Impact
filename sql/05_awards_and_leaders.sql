-- CS437 MLB Global Era - Awards and Leaders Analysis
-- 
-- This file contains additional analysis views for awards and statistical leaders
-- integrated with the WAR data and player origins.
--
-- Prerequisites:
--   - Previous SQL files must be run first
--
-- Usage:
--   psql -d mlb -f 05_awards_and_leaders.sql

\echo 'Creating awards and leaders analysis views...'

-- ==========================================================================
-- Awards with Player Origin
-- ==========================================================================

-- Materialized view joining awards with player origin information
CREATE MATERIALIZED VIEW IF NOT EXISTS core.mv_awards_with_origin AS
SELECT 
    a.player_id,
    a.award_id,
    a.year_id,
    a.lg_id,
    po.origin_category,
    po.birth_country,
    po.latin_country_name,
    po.name_first,
    po.name_last
FROM core.awardsplayers a
INNER JOIN core.player_origin po ON a.player_id = po.player_id
ORDER BY a.year_id DESC, a.award_id;

-- Index on awards with origin
CREATE INDEX IF NOT EXISTS idx_awards_origin_year ON core.mv_awards_with_origin(year_id);
CREATE INDEX IF NOT EXISTS idx_awards_origin_category ON core.mv_awards_with_origin(origin_category);
CREATE INDEX IF NOT EXISTS idx_awards_origin_award ON core.mv_awards_with_origin(award_id);

COMMENT ON MATERIALIZED VIEW core.mv_awards_with_origin IS 
    'Awards won by players with their origin classification';

\echo '✓ Awards and leaders views created.'
