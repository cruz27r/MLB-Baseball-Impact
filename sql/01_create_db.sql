-- CS437 MLB Global Era - Database Schema Creation
-- 
-- This file creates the database schemas and core tables for MLB data.
-- Run this script first to set up the database structure.

-- ==========================================================================
-- Create Schemas
-- ==========================================================================

-- Lahman database schema
CREATE SCHEMA IF NOT EXISTS lahman;

-- Retrosheet data schema
CREATE SCHEMA IF NOT EXISTS retrosheet;

-- Baseball Reference data schema
CREATE SCHEMA IF NOT EXISTS bref;

-- Core/unified data schema
CREATE SCHEMA IF NOT EXISTS core;

-- ==========================================================================
-- Core Tables - People
-- ==========================================================================

-- People/Players table - core player information
CREATE TABLE IF NOT EXISTS core.people (
    player_id VARCHAR(10) PRIMARY KEY,
    birth_year INTEGER,
    birth_month INTEGER,
    birth_day INTEGER,
    birth_country VARCHAR(50),
    birth_state VARCHAR(2),
    birth_city VARCHAR(50),
    death_year INTEGER,
    death_month INTEGER,
    death_day INTEGER,
    death_country VARCHAR(50),
    death_state VARCHAR(2),
    death_city VARCHAR(50),
    name_first VARCHAR(50),
    name_last VARCHAR(50),
    name_given VARCHAR(100),
    weight INTEGER,
    height INTEGER,
    bats VARCHAR(1),
    throws VARCHAR(1),
    debut DATE,
    final_game DATE,
    retro_id VARCHAR(10),
    bbref_id VARCHAR(10)
);

-- Index on player demographics
CREATE INDEX IF NOT EXISTS idx_people_birth_country ON core.people(birth_country);
CREATE INDEX IF NOT EXISTS idx_people_birth_year ON core.people(birth_year);
CREATE INDEX IF NOT EXISTS idx_people_name ON core.people(name_last, name_first);

-- ==========================================================================
-- Core Tables - Appearances
-- ==========================================================================

-- Appearances table - tracks player appearances by team and year
CREATE TABLE IF NOT EXISTS core.appearances (
    year_id INTEGER NOT NULL,
    team_id VARCHAR(3) NOT NULL,
    lg_id VARCHAR(2),
    player_id VARCHAR(10) NOT NULL,
    g_all INTEGER,
    gs INTEGER,
    g_batting INTEGER,
    g_defense INTEGER,
    g_p INTEGER,
    g_c INTEGER,
    g_1b INTEGER,
    g_2b INTEGER,
    g_3b INTEGER,
    g_ss INTEGER,
    g_lf INTEGER,
    g_cf INTEGER,
    g_rf INTEGER,
    g_of INTEGER,
    g_dh INTEGER,
    g_ph INTEGER,
    g_pr INTEGER,
    PRIMARY KEY (year_id, team_id, player_id)
);

-- Index on appearances
CREATE INDEX IF NOT EXISTS idx_appearances_player ON core.appearances(player_id);
CREATE INDEX IF NOT EXISTS idx_appearances_year ON core.appearances(year_id);
CREATE INDEX IF NOT EXISTS idx_appearances_team ON core.appearances(team_id);

-- ==========================================================================
-- Core Tables - Batting
-- ==========================================================================

-- Batting statistics table
CREATE TABLE IF NOT EXISTS core.batting (
    player_id VARCHAR(10) NOT NULL,
    year_id INTEGER NOT NULL,
    stint INTEGER NOT NULL,
    team_id VARCHAR(3),
    lg_id VARCHAR(2),
    g INTEGER,
    ab INTEGER,
    r INTEGER,
    h INTEGER,
    double INTEGER,
    triple INTEGER,
    hr INTEGER,
    rbi INTEGER,
    sb INTEGER,
    cs INTEGER,
    bb INTEGER,
    so INTEGER,
    ibb INTEGER,
    hbp INTEGER,
    sh INTEGER,
    sf INTEGER,
    gidp INTEGER,
    PRIMARY KEY (player_id, year_id, stint)
);

-- Index on batting stats
CREATE INDEX IF NOT EXISTS idx_batting_player ON core.batting(player_id);
CREATE INDEX IF NOT EXISTS idx_batting_year ON core.batting(year_id);
CREATE INDEX IF NOT EXISTS idx_batting_team ON core.batting(team_id);

-- ==========================================================================
-- Core Tables - Pitching
-- ==========================================================================

-- Pitching statistics table
CREATE TABLE IF NOT EXISTS core.pitching (
    player_id VARCHAR(10) NOT NULL,
    year_id INTEGER NOT NULL,
    stint INTEGER NOT NULL,
    team_id VARCHAR(3),
    lg_id VARCHAR(2),
    w INTEGER,
    l INTEGER,
    g INTEGER,
    gs INTEGER,
    cg INTEGER,
    sho INTEGER,
    sv INTEGER,
    ipouts INTEGER,
    h INTEGER,
    er INTEGER,
    hr INTEGER,
    bb INTEGER,
    so INTEGER,
    baopp NUMERIC(5,3),
    era NUMERIC(5,2),
    ibb INTEGER,
    wp INTEGER,
    hbp INTEGER,
    bk INTEGER,
    bfp INTEGER,
    gf INTEGER,
    r INTEGER,
    sh INTEGER,
    sf INTEGER,
    gidp INTEGER,
    PRIMARY KEY (player_id, year_id, stint)
);

-- Index on pitching stats
CREATE INDEX IF NOT EXISTS idx_pitching_player ON core.pitching(player_id);
CREATE INDEX IF NOT EXISTS idx_pitching_year ON core.pitching(year_id);
CREATE INDEX IF NOT EXISTS idx_pitching_team ON core.pitching(team_id);

-- ==========================================================================
-- Core Tables - Awards Players
-- ==========================================================================

-- Awards won by players
CREATE TABLE IF NOT EXISTS core.awardsplayers (
    player_id VARCHAR(10) NOT NULL,
    award_id VARCHAR(50) NOT NULL,
    year_id INTEGER NOT NULL,
    lg_id VARCHAR(2),
    tie VARCHAR(1),
    notes VARCHAR(100),
    PRIMARY KEY (player_id, award_id, year_id, lg_id)
);

-- Index on awards
CREATE INDEX IF NOT EXISTS idx_awards_player ON core.awardsplayers(player_id);
CREATE INDEX IF NOT EXISTS idx_awards_year ON core.awardsplayers(year_id);
CREATE INDEX IF NOT EXISTS idx_awards_award ON core.awardsplayers(award_id);

-- ==========================================================================
-- Core Tables - Teams
-- ==========================================================================

-- Teams table - team information by year
CREATE TABLE IF NOT EXISTS core.teams (
    year_id INTEGER NOT NULL,
    lg_id VARCHAR(2),
    team_id VARCHAR(3) NOT NULL,
    franch_id VARCHAR(3),
    div_id VARCHAR(1),
    rank INTEGER,
    g INTEGER,
    g_home INTEGER,
    w INTEGER,
    l INTEGER,
    div_win VARCHAR(1),
    wc_win VARCHAR(1),
    lg_win VARCHAR(1),
    ws_win VARCHAR(1),
    r INTEGER,
    ab INTEGER,
    h INTEGER,
    double INTEGER,
    triple INTEGER,
    hr INTEGER,
    bb INTEGER,
    so INTEGER,
    sb INTEGER,
    cs INTEGER,
    hbp INTEGER,
    sf INTEGER,
    ra INTEGER,
    er INTEGER,
    era NUMERIC(5,2),
    cg INTEGER,
    sho INTEGER,
    sv INTEGER,
    ipouts INTEGER,
    ha INTEGER,
    hra INTEGER,
    bba INTEGER,
    soa INTEGER,
    e INTEGER,
    dp INTEGER,
    fp NUMERIC(5,3),
    name VARCHAR(50),
    park VARCHAR(100),
    attendance INTEGER,
    bpf INTEGER,
    ppf INTEGER,
    team_id_br VARCHAR(3),
    team_id_lahman45 VARCHAR(3),
    team_id_retro VARCHAR(3),
    PRIMARY KEY (year_id, team_id)
);

-- Index on teams
CREATE INDEX IF NOT EXISTS idx_teams_year ON core.teams(year_id);
CREATE INDEX IF NOT EXISTS idx_teams_team ON core.teams(team_id);
CREATE INDEX IF NOT EXISTS idx_teams_franch ON core.teams(franch_id);

-- ==========================================================================
-- Core Tables - Fielding
-- ==========================================================================

-- Fielding statistics table
CREATE TABLE IF NOT EXISTS core.fielding (
    player_id VARCHAR(10) NOT NULL,
    year_id INTEGER NOT NULL,
    stint INTEGER NOT NULL,
    team_id VARCHAR(3),
    lg_id VARCHAR(2),
    pos VARCHAR(2) NOT NULL,
    g INTEGER,
    gs INTEGER,
    inn_outs INTEGER,
    po INTEGER,
    a INTEGER,
    e INTEGER,
    dp INTEGER,
    pb INTEGER,
    wp INTEGER,
    sb INTEGER,
    cs INTEGER,
    zr NUMERIC(5,3),
    PRIMARY KEY (player_id, year_id, stint, pos)
);

-- Index on fielding stats
CREATE INDEX IF NOT EXISTS idx_fielding_player ON core.fielding(player_id);
CREATE INDEX IF NOT EXISTS idx_fielding_year ON core.fielding(year_id);
CREATE INDEX IF NOT EXISTS idx_fielding_pos ON core.fielding(pos);

-- ==========================================================================
-- Core Tables - All-Star Full
-- ==========================================================================

-- All-Star game appearances
CREATE TABLE IF NOT EXISTS core.allstarfull (
    player_id VARCHAR(10) NOT NULL,
    year_id INTEGER NOT NULL,
    game_num INTEGER NOT NULL,
    game_id VARCHAR(12),
    team_id VARCHAR(3),
    lg_id VARCHAR(2),
    gp INTEGER,
    starting_pos INTEGER,
    PRIMARY KEY (player_id, year_id, game_num)
);

-- Index on all-star appearances
CREATE INDEX IF NOT EXISTS idx_allstar_player ON core.allstarfull(player_id);
CREATE INDEX IF NOT EXISTS idx_allstar_year ON core.allstarfull(year_id);

-- ==========================================================================
-- Core Tables - Series Post
-- ==========================================================================

-- Postseason series results
CREATE TABLE IF NOT EXISTS core.seriespost (
    year_id INTEGER NOT NULL,
    round VARCHAR(5) NOT NULL,
    team_id_winner VARCHAR(3),
    lg_id_winner VARCHAR(2),
    team_id_loser VARCHAR(3),
    lg_id_loser VARCHAR(2),
    wins INTEGER,
    losses INTEGER,
    ties INTEGER,
    PRIMARY KEY (year_id, round, team_id_winner)
);

-- Index on series post
CREATE INDEX IF NOT EXISTS idx_seriespost_year ON core.seriespost(year_id);
CREATE INDEX IF NOT EXISTS idx_seriespost_winner ON core.seriespost(team_id_winner);
CREATE INDEX IF NOT EXISTS idx_seriespost_loser ON core.seriespost(team_id_loser);

-- ==========================================================================
-- Comments
-- ==========================================================================

COMMENT ON SCHEMA lahman IS 'Lahman Baseball Database schema';
COMMENT ON SCHEMA retrosheet IS 'Retrosheet play-by-play data schema';
COMMENT ON SCHEMA bref IS 'Baseball Reference data schema';
COMMENT ON SCHEMA core IS 'Core unified MLB data schema';

COMMENT ON TABLE core.people IS 'Player biographical and demographic information';
COMMENT ON TABLE core.appearances IS 'Player appearances by team, year, and position';
COMMENT ON TABLE core.batting IS 'Player batting statistics by year';
COMMENT ON TABLE core.pitching IS 'Player pitching statistics by year';
COMMENT ON TABLE core.awardsplayers IS 'Awards won by players';
COMMENT ON TABLE core.teams IS 'Team information and statistics by year';
COMMENT ON TABLE core.fielding IS 'Player fielding statistics by year and position';
COMMENT ON TABLE core.allstarfull IS 'All-Star game appearances by player';
COMMENT ON TABLE core.seriespost IS 'Postseason series results';
