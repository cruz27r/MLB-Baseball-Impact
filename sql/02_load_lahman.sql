-- CS437 MLB Global Era - Lahman Data Loader
-- 
-- This file contains \copy commands to load Lahman Baseball Database CSV files
-- into the core schema tables.
--
-- Prerequisites:
--   - 01_create_db.sql must be run first to create tables
--   - Lahman CSV files must be in ~/mlb_data/lahman/ directory
--
-- Usage:
--   psql -d mlb -f 02_load_lahman.sql

-- ==========================================================================
-- Load People (Players) Data
-- ==========================================================================

\echo 'Loading People data...'
\copy core.people FROM '~/mlb_data/lahman/People.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Appearances Data
-- ==========================================================================

\echo 'Loading Appearances data...'
\copy core.appearances FROM '~/mlb_data/lahman/Appearances.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Batting Data
-- ==========================================================================

\echo 'Loading Batting data...'
\copy core.batting FROM '~/mlb_data/lahman/Batting.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Pitching Data
-- ==========================================================================

\echo 'Loading Pitching data...'
\copy core.pitching FROM '~/mlb_data/lahman/Pitching.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Awards Players Data
-- ==========================================================================

\echo 'Loading AwardsPlayers data...'
\copy core.awardsplayers FROM '~/mlb_data/lahman/AwardsPlayers.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Teams Data
-- ==========================================================================

\echo 'Loading Teams data...'
\copy core.teams FROM '~/mlb_data/lahman/Teams.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Fielding Data
-- ==========================================================================

\echo 'Loading Fielding data...'
\copy core.fielding FROM '~/mlb_data/lahman/Fielding.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load All-Star Full Data
-- ==========================================================================

\echo 'Loading AllstarFull data...'
\copy core.allstarfull FROM '~/mlb_data/lahman/AllstarFull.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Load Series Post Data
-- ==========================================================================

\echo 'Loading SeriesPost data...'
\copy core.seriespost FROM '~/mlb_data/lahman/SeriesPost.csv' WITH (FORMAT csv, HEADER true, DELIMITER ',', NULL '');

-- ==========================================================================
-- Analyze Tables for Query Optimization
-- ==========================================================================

\echo 'Analyzing tables for query optimization...'
ANALYZE core.people;
ANALYZE core.appearances;
ANALYZE core.batting;
ANALYZE core.pitching;
ANALYZE core.awardsplayers;
ANALYZE core.teams;
ANALYZE core.fielding;
ANALYZE core.allstarfull;
ANALYZE core.seriespost;

\echo 'Lahman data loading complete!'
