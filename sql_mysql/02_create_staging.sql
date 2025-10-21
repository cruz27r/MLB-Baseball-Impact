DROP TABLE IF EXISTS staging_people;
CREATE TABLE staging_people (
  retro_id TEXT, last TEXT, first TEXT, bats TEXT, throws TEXT,
  birth_date TEXT, birth_city TEXT, birth_state TEXT, birth_country TEXT,
  debut TEXT, final_game TEXT, height TEXT, weight TEXT, use_name TEXT
);

DROP TABLE IF EXISTS staging_appearances;
CREATE TABLE staging_appearances (
  year_id TEXT, team_id TEXT, lg_id TEXT, retro_id TEXT, g_all TEXT
);

DROP TABLE IF EXISTS staging_teams;
CREATE TABLE staging_teams (
  year_id TEXT, lg_id TEXT, team_id TEXT, franch_id TEXT, div_id TEXT,
  rank TEXT, g TEXT, w TEXT, l TEXT, name TEXT
);

DROP TABLE IF EXISTS staging_war_bat;
CREATE TABLE staging_war_bat (
  player_id TEXT, year_id TEXT, team_id TEXT, runs_bat TEXT, runs_def TEXT, war TEXT
);

DROP TABLE IF EXISTS staging_war_pitch;
CREATE TABLE staging_war_pitch (
  player_id TEXT, year_id TEXT, team_id TEXT, war TEXT
);
