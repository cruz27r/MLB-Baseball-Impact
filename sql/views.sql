-- CS437 MLB Global Era - PostgreSQL Materialized Views
-- 
-- This file defines materialized views for efficient querying of MLB data.
-- Materialized views pre-compute aggregations and joins for better performance.

-- ==========================================================================
-- Foreign Players Summary View
-- ==========================================================================

-- Aggregates statistics for foreign (non-US) players by country and year
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_foreign_players_summary AS
SELECT 
    p.birth_country,
    p.birth_year,
    COUNT(DISTINCT p.player_id) AS player_count,
    AVG(s.batting_average) AS avg_batting_average,
    AVG(s.home_runs) AS avg_home_runs,
    AVG(s.rbi) AS avg_rbi,
    SUM(s.games_played) AS total_games
FROM players p
LEFT JOIN statistics s ON p.player_id = s.player_id
WHERE p.birth_country != 'USA'
GROUP BY p.birth_country, p.birth_year
ORDER BY p.birth_year DESC, player_count DESC;

-- Index for fast lookups
CREATE INDEX IF NOT EXISTS idx_foreign_players_country 
    ON mv_foreign_players_summary(birth_country);
CREATE INDEX IF NOT EXISTS idx_foreign_players_year 
    ON mv_foreign_players_summary(birth_year);


-- ==========================================================================
-- Awards by Foreign Players View
-- ==========================================================================

-- Summarizes awards won by foreign players
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_foreign_awards AS
SELECT 
    p.birth_country,
    a.award_type,
    a.award_year,
    COUNT(*) AS award_count,
    STRING_AGG(DISTINCT p.player_name, ', ') AS recipients
FROM players p
INNER JOIN awards a ON p.player_id = a.player_id
WHERE p.birth_country != 'USA'
GROUP BY p.birth_country, a.award_type, a.award_year
ORDER BY a.award_year DESC, award_count DESC;

-- Index for fast lookups
CREATE INDEX IF NOT EXISTS idx_foreign_awards_country 
    ON mv_foreign_awards(birth_country);
CREATE INDEX IF NOT EXISTS idx_foreign_awards_year 
    ON mv_foreign_awards(award_year);


-- ==========================================================================
-- Team Composition by Year View
-- ==========================================================================

-- Calculates percentage of foreign vs domestic players per team per year
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_team_composition AS
SELECT 
    t.team_name,
    t.year,
    COUNT(*) AS total_players,
    COUNT(*) FILTER (WHERE p.birth_country != 'USA') AS foreign_players,
    COUNT(*) FILTER (WHERE p.birth_country = 'USA') AS domestic_players,
    ROUND(
        100.0 * COUNT(*) FILTER (WHERE p.birth_country != 'USA') / NULLIF(COUNT(*), 0), 
        2
    ) AS foreign_player_percentage
FROM teams t
INNER JOIN team_rosters tr ON t.team_id = tr.team_id
INNER JOIN players p ON tr.player_id = p.player_id
GROUP BY t.team_name, t.year
ORDER BY t.year DESC, foreign_player_percentage DESC;

-- Index for fast lookups
CREATE INDEX IF NOT EXISTS idx_team_composition_team 
    ON mv_team_composition(team_name);
CREATE INDEX IF NOT EXISTS idx_team_composition_year 
    ON mv_team_composition(year);


-- ==========================================================================
-- Statistical Leaders View
-- ==========================================================================

-- Top performers by various categories, highlighting foreign players
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_statistical_leaders AS
WITH ranked_players AS (
    SELECT 
        p.player_id,
        p.player_name,
        p.birth_country,
        s.year,
        s.batting_average,
        s.home_runs,
        s.rbi,
        s.era,
        s.strikeouts,
        ROW_NUMBER() OVER (PARTITION BY s.year ORDER BY s.batting_average DESC) AS ba_rank,
        ROW_NUMBER() OVER (PARTITION BY s.year ORDER BY s.home_runs DESC) AS hr_rank,
        ROW_NUMBER() OVER (PARTITION BY s.year ORDER BY s.rbi DESC) AS rbi_rank
    FROM players p
    INNER JOIN statistics s ON p.player_id = s.player_id
    WHERE s.games_played >= 100  -- Minimum games threshold
)
SELECT * FROM ranked_players
WHERE ba_rank <= 50 OR hr_rank <= 50 OR rbi_rank <= 50;

-- Index for fast lookups
CREATE INDEX IF NOT EXISTS idx_leaders_year 
    ON mv_statistical_leaders(year);
CREATE INDEX IF NOT EXISTS idx_leaders_country 
    ON mv_statistical_leaders(birth_country);


-- ==========================================================================
-- Refresh Functions
-- ==========================================================================

-- Function to refresh all materialized views
CREATE OR REPLACE FUNCTION refresh_all_mlb_views()
RETURNS void AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_foreign_players_summary;
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_foreign_awards;
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_team_composition;
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_statistical_leaders;
    
    RAISE NOTICE 'All MLB materialized views refreshed at %', NOW();
END;
$$ LANGUAGE plpgsql;


-- ==========================================================================
-- Comments
-- ==========================================================================

COMMENT ON MATERIALIZED VIEW mv_foreign_players_summary IS 
    'Summary statistics for foreign players aggregated by country and year';

COMMENT ON MATERIALIZED VIEW mv_foreign_awards IS 
    'Awards won by foreign players, grouped by country and award type';

COMMENT ON MATERIALIZED VIEW mv_team_composition IS 
    'Team roster composition showing foreign vs domestic player distribution';

COMMENT ON MATERIALIZED VIEW mv_statistical_leaders IS 
    'Top statistical leaders by year, highlighting foreign players';

COMMENT ON FUNCTION refresh_all_mlb_views IS 
    'Refresh all MLB materialized views concurrently';
