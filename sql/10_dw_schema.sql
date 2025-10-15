-- CS437 MLB Global Era - Data Warehouse Schema
-- 
-- This file creates the unified data warehouse (dw) schema that merges
-- SABR/Lahman, Baseball-Reference WAR, and optional Retrosheet data.
--
-- Prerequisites:
--   - 01_create_db.sql must be run first to create base schemas
--   - 02_load_lahman.sql must be run to load source data
--
-- Usage:
--   psql -d mlb -f 10_dw_schema.sql

-- ==========================================================================
-- Create Data Warehouse Schema
-- ==========================================================================

\echo 'Creating dw schema...'
CREATE SCHEMA IF NOT EXISTS dw;

COMMENT ON SCHEMA dw IS 'Unified data warehouse schema merging Lahman, B-Ref WAR, and Retrosheet';

-- ==========================================================================
-- Dimension Tables
-- ==========================================================================

\echo 'Creating dimension tables...'

-- Countries dimension
DROP TABLE IF EXISTS dw.countries CASCADE;
CREATE TABLE dw.countries (
    code TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    is_latin BOOLEAN DEFAULT false,
    region TEXT
);

CREATE INDEX IF NOT EXISTS idx_countries_region ON dw.countries(region);
CREATE INDEX IF NOT EXISTS idx_countries_is_latin ON dw.countries(is_latin);

COMMENT ON TABLE dw.countries IS 'Country dimension with Latin/Caribbean classification';

-- Players dimension
DROP TABLE IF EXISTS dw.players CASCADE;
CREATE TABLE dw.players (
    player_id TEXT PRIMARY KEY,
    bref_id TEXT,
    retro_id TEXT,
    name_first TEXT,
    name_last TEXT,
    birth_year INTEGER,
    birth_month INTEGER,
    birth_day INTEGER,
    country_raw TEXT,
    country_code TEXT,
    origin_group TEXT,
    debut_date DATE,
    final_game_date DATE
);

CREATE INDEX IF NOT EXISTS idx_players_country_code ON dw.players(country_code);
CREATE INDEX IF NOT EXISTS idx_players_origin_group ON dw.players(origin_group);
CREATE INDEX IF NOT EXISTS idx_players_country_origin ON dw.players(country_code, origin_group);
CREATE INDEX IF NOT EXISTS idx_players_bref_id ON dw.players(bref_id);
CREATE INDEX IF NOT EXISTS idx_players_name ON dw.players(name_last, name_first);

COMMENT ON TABLE dw.players IS 'Player dimension with origin classification';

-- Teams dimension
DROP TABLE IF EXISTS dw.teams CASCADE;
CREATE TABLE dw.teams (
    year INTEGER NOT NULL,
    team_id TEXT NOT NULL,
    franch_id TEXT,
    team_name TEXT,
    lg_id TEXT,
    park TEXT,
    attendance BIGINT,
    wins INTEGER,
    losses INTEGER,
    division TEXT,
    PRIMARY KEY (year, team_id)
);

CREATE INDEX IF NOT EXISTS idx_teams_year ON dw.teams(year);
CREATE INDEX IF NOT EXISTS idx_teams_attendance ON dw.teams(year, attendance);

COMMENT ON TABLE dw.teams IS 'Team dimension with attendance and performance';

-- ==========================================================================
-- Bridge Tables
-- ==========================================================================

\echo 'Creating bridge tables...'

-- Player-team bridge
DROP TABLE IF EXISTS dw.player_teams CASCADE;
CREATE TABLE dw.player_teams (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    team_id TEXT NOT NULL,
    primary_pos TEXT,
    g INTEGER,
    PRIMARY KEY (year, player_id, team_id)
);

CREATE INDEX IF NOT EXISTS idx_player_teams_player ON dw.player_teams(player_id);
CREATE INDEX IF NOT EXISTS idx_player_teams_year ON dw.player_teams(year);
CREATE INDEX IF NOT EXISTS idx_player_teams_team ON dw.player_teams(team_id);

COMMENT ON TABLE dw.player_teams IS 'Player-team associations by year';

-- ==========================================================================
-- Fact Tables (Season-Level)
-- ==========================================================================

\echo 'Creating fact tables...'

-- Batting season facts
DROP TABLE IF EXISTS dw.batting_season CASCADE;
CREATE TABLE dw.batting_season (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    ab INTEGER,
    h INTEGER,
    hr INTEGER,
    rbi INTEGER,
    bb INTEGER,
    so INTEGER,
    sb INTEGER,
    cs INTEGER,
    obp NUMERIC,
    slg NUMERIC,
    ops NUMERIC,
    PRIMARY KEY (year, player_id)
);

CREATE INDEX IF NOT EXISTS idx_batting_season_year ON dw.batting_season(year);
CREATE INDEX IF NOT EXISTS idx_batting_season_player ON dw.batting_season(player_id);

COMMENT ON TABLE dw.batting_season IS 'Aggregated batting statistics by player-season';

-- Pitching season facts
DROP TABLE IF EXISTS dw.pitching_season CASCADE;
CREATE TABLE dw.pitching_season (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    ip_outs INTEGER,
    er INTEGER,
    so INTEGER,
    bb INTEGER,
    hr_allowed INTEGER,
    era NUMERIC,
    sv INTEGER,
    w INTEGER,
    l INTEGER,
    PRIMARY KEY (year, player_id)
);

CREATE INDEX IF NOT EXISTS idx_pitching_season_year ON dw.pitching_season(year);
CREATE INDEX IF NOT EXISTS idx_pitching_season_player ON dw.pitching_season(player_id);

COMMENT ON TABLE dw.pitching_season IS 'Aggregated pitching statistics by player-season';

-- Fielding season facts (optional)
DROP TABLE IF EXISTS dw.fielding_season CASCADE;
CREATE TABLE dw.fielding_season (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    pos TEXT NOT NULL,
    g INTEGER,
    gs INTEGER,
    inn_outs INTEGER,
    po INTEGER,
    a INTEGER,
    e INTEGER,
    fld_pct NUMERIC,
    PRIMARY KEY (year, player_id, pos)
);

CREATE INDEX IF NOT EXISTS idx_fielding_season_year ON dw.fielding_season(year);
CREATE INDEX IF NOT EXISTS idx_fielding_season_player ON dw.fielding_season(player_id);

COMMENT ON TABLE dw.fielding_season IS 'Aggregated fielding statistics by player-season-position';

-- Awards season facts
DROP TABLE IF EXISTS dw.awards_season CASCADE;
CREATE TABLE dw.awards_season (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    award_id TEXT NOT NULL,
    count INTEGER DEFAULT 1,
    allstar_count INTEGER DEFAULT 0,
    PRIMARY KEY (year, player_id, award_id)
);

CREATE INDEX IF NOT EXISTS idx_awards_season_year ON dw.awards_season(year);
CREATE INDEX IF NOT EXISTS idx_awards_season_player ON dw.awards_season(player_id);

COMMENT ON TABLE dw.awards_season IS 'Awards won by player-season';

-- WAR season facts
DROP TABLE IF EXISTS dw.war_season CASCADE;
CREATE TABLE dw.war_season (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    war_bat NUMERIC,
    war_pitch NUMERIC,
    war_total NUMERIC,
    PRIMARY KEY (year, player_id)
);

CREATE INDEX IF NOT EXISTS idx_war_season_year ON dw.war_season(year);
CREATE INDEX IF NOT EXISTS idx_war_season_player ON dw.war_season(player_id);
CREATE INDEX IF NOT EXISTS idx_war_season_war_total ON dw.war_season(war_total);

COMMENT ON TABLE dw.war_season IS 'Baseball-Reference WAR by player-season';

-- Postseason team facts
DROP TABLE IF EXISTS dw.postseason_team CASCADE;
CREATE TABLE dw.postseason_team (
    year INTEGER NOT NULL,
    round TEXT NOT NULL,
    lg_id TEXT,
    team_id TEXT NOT NULL,
    opp_team_id TEXT,
    wins INTEGER,
    losses INTEGER,
    is_champion BOOLEAN DEFAULT false,
    PRIMARY KEY (year, round, team_id)
);

CREATE INDEX IF NOT EXISTS idx_postseason_team_year ON dw.postseason_team(year);
CREATE INDEX IF NOT EXISTS idx_postseason_team_team ON dw.postseason_team(team_id);
CREATE INDEX IF NOT EXISTS idx_postseason_team_champion ON dw.postseason_team(is_champion);

COMMENT ON TABLE dw.postseason_team IS 'Postseason participation and results by team';

-- ==========================================================================
-- Canonical Wide Table
-- ==========================================================================

\echo 'Creating canonical player_season table...'

DROP TABLE IF EXISTS dw.player_season CASCADE;
CREATE TABLE dw.player_season (
    year INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    bref_id TEXT,
    name_first TEXT,
    name_last TEXT,
    country_code TEXT,
    origin_group TEXT,
    teams_played TEXT,
    was_on_roster BOOLEAN DEFAULT false,
    made_postseason BOOLEAN DEFAULT false,
    deep_run BOOLEAN DEFAULT false,
    is_champion BOOLEAN DEFAULT false,
    ab INTEGER,
    h INTEGER,
    hr INTEGER,
    rbi INTEGER,
    bb INTEGER,
    so INTEGER,
    sb INTEGER,
    cs INTEGER,
    obp NUMERIC,
    slg NUMERIC,
    ops NUMERIC,
    ip_outs INTEGER,
    er INTEGER,
    so_p INTEGER,
    bb_p INTEGER,
    hr_allowed INTEGER,
    era NUMERIC,
    sv INTEGER,
    w INTEGER,
    l INTEGER,
    awards_total INTEGER DEFAULT 0,
    mvp_count INTEGER DEFAULT 0,
    cy_count INTEGER DEFAULT 0,
    roy_count INTEGER DEFAULT 0,
    allstar_count INTEGER DEFAULT 0,
    war_bat NUMERIC,
    war_pitch NUMERIC,
    war_total NUMERIC,
    avg_team_attendance NUMERIC,
    id_confidence TEXT,
    missing_fields TEXT,
    PRIMARY KEY (year, player_id)
);

CREATE INDEX IF NOT EXISTS idx_player_season_year ON dw.player_season(year);
CREATE INDEX IF NOT EXISTS idx_player_season_player ON dw.player_season(player_id);
CREATE INDEX IF NOT EXISTS idx_player_season_origin ON dw.player_season(year, origin_group);
CREATE INDEX IF NOT EXISTS idx_player_season_champion ON dw.player_season(is_champion);
CREATE INDEX IF NOT EXISTS idx_player_season_war ON dw.player_season(war_total);

COMMENT ON TABLE dw.player_season IS 'Canonical wide table combining all player-season data';

\echo 'Data warehouse schema created successfully!'
