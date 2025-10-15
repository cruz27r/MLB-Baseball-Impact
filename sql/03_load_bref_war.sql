-- CS437 MLB Global Era - Load Baseball-Reference WAR Data
-- 
-- This file creates tables and loads Baseball-Reference WAR data into
-- the bref schema for analysis.
--
-- Prerequisites:
--   - 01_create_db.sql must be run first to create schemas
--   - WAR CSV files must be in ~/mlb_data/bref_war/ directory
--
-- Usage:
--   psql -d mlb -f 03_load_bref_war.sql

-- ==========================================================================
-- Create Raw WAR Tables
-- ==========================================================================

\echo 'Creating raw WAR tables...'

-- Raw batting WAR table (one line per row)
DROP TABLE IF EXISTS bref.war_bat_raw CASCADE;
CREATE TABLE bref.war_bat_raw(line text);

-- Raw pitching WAR table (one line per row)
DROP TABLE IF EXISTS bref.war_pitch_raw CASCADE;
CREATE TABLE bref.war_pitch_raw(line text);

-- ==========================================================================
-- Load Raw WAR Data
-- ==========================================================================

\echo 'Loading raw WAR data from CSV files...'
\copy bref.war_bat_raw FROM '~/mlb_data/bref_war/war_daily_bat.csv' CSV;
\copy bref.war_pitch_raw FROM '~/mlb_data/bref_war/war_daily_pitch.csv' CSV;

-- ==========================================================================
-- Create Parsed WAR Tables
-- ==========================================================================

\echo 'Creating parsed WAR tables...'

-- Batting WAR table structure
DROP TABLE IF EXISTS bref.war_bat CASCADE;
CREATE TABLE bref.war_bat (
    name_common TEXT,
    mlb_ID TEXT,
    playerid TEXT,
    yearid INTEGER,
    team_ID TEXT,
    stint INTEGER,
    lg_ID TEXT,
    PA INTEGER,
    G INTEGER,
    Inn NUMERIC,
    runs_bat NUMERIC,
    runs_br NUMERIC,
    runs_dp NUMERIC,
    runs_field NUMERIC,
    runs_infield NUMERIC,
    runs_outfield NUMERIC,
    runs_catcher NUMERIC,
    runs_good_plays NUMERIC,
    runs_defense NUMERIC,
    runs_position NUMERIC,
    runs_position_p NUMERIC,
    runs_replacement NUMERIC,
    runs_above_rep NUMERIC,
    runs_above_avg NUMERIC,
    runs_above_avg_off NUMERIC,
    runs_above_avg_def NUMERIC,
    WAA NUMERIC,
    WAA_off NUMERIC,
    WAA_def NUMERIC,
    WAR NUMERIC,
    WAR_def NUMERIC,
    WAR_off NUMERIC,
    WAR_rep NUMERIC,
    salary NUMERIC,
    pitcher TEXT,
    teamRpG NUMERIC,
    oppRpG NUMERIC,
    oppRpPA_rep NUMERIC,
    oppRpG_rep NUMERIC,
    pyth_exponent NUMERIC,
    pyth_exponent_rep NUMERIC,
    waa_win_perc NUMERIC,
    waa_win_perc_off NUMERIC,
    waa_win_perc_def NUMERIC,
    waa_win_perc_rep NUMERIC
);

-- Pitching WAR table structure
DROP TABLE IF EXISTS bref.war_pitch CASCADE;
CREATE TABLE bref.war_pitch (
    name_common TEXT,
    mlb_ID TEXT,
    playerid TEXT,
    yearid INTEGER,
    team_ID TEXT,
    stint INTEGER,
    lg_ID TEXT,
    G INTEGER,
    GS INTEGER,
    IPouts INTEGER,
    IPouts_start INTEGER,
    IPouts_relief INTEGER,
    RA NUMERIC,
    xRA NUMERIC,
    xRA_sprp_adj NUMERIC,
    xRA_def_pitcher NUMERIC,
    PPF NUMERIC,
    PPF_custom NUMERIC,
    xRA_final NUMERIC,
    BIP NUMERIC,
    BIP_perc NUMERIC,
    salary NUMERIC,
    runs_above_avg NUMERIC,
    runs_above_avg_adj NUMERIC,
    runs_above_rep NUMERIC,
    RpO_replacement NUMERIC,
    GR_leverage_index_avg NUMERIC,
    WAR NUMERIC,
    salary_season NUMERIC,
    runs_above_avg_season NUMERIC,
    runs_above_avg_adj_season NUMERIC,
    runs_above_rep_season NUMERIC,
    WAR_season NUMERIC,
    teamRpG NUMERIC,
    oppRpG NUMERIC,
    pyth_exponent NUMERIC,
    waa_win_perc NUMERIC,
    WAA NUMERIC
);

-- ==========================================================================
-- Indexes on Parsed WAR Tables
-- ==========================================================================

\echo 'Creating indexes on WAR tables...'

CREATE INDEX IF NOT EXISTS idx_war_bat_playerid ON bref.war_bat(playerid);
CREATE INDEX IF NOT EXISTS idx_war_bat_yearid ON bref.war_bat(yearid);
CREATE INDEX IF NOT EXISTS idx_war_bat_player_year ON bref.war_bat(playerid, yearid);

CREATE INDEX IF NOT EXISTS idx_war_pitch_playerid ON bref.war_pitch(playerid);
CREATE INDEX IF NOT EXISTS idx_war_pitch_yearid ON bref.war_pitch(yearid);
CREATE INDEX IF NOT EXISTS idx_war_pitch_player_year ON bref.war_pitch(playerid, yearid);

-- ==========================================================================
-- Comments
-- ==========================================================================

COMMENT ON TABLE bref.war_bat_raw IS 'Raw Baseball-Reference batting WAR data (one line per row)';
COMMENT ON TABLE bref.war_pitch_raw IS 'Raw Baseball-Reference pitching WAR data (one line per row)';
COMMENT ON TABLE bref.war_bat IS 'Parsed Baseball-Reference batting WAR data';
COMMENT ON TABLE bref.war_pitch IS 'Parsed Baseball-Reference pitching WAR data';

\echo 'WAR table structures created. Run etl/ingest_bref_war.py to parse and load data.'
