CREATE INDEX idx_people_retro ON staging_people(retro_id(16));
CREATE INDEX idx_app_retro ON staging_appearances(retro_id(16));
CREATE INDEX idx_app_year ON staging_appearances(year_id(4));
CREATE INDEX idx_teams_year_team ON staging_teams(year_id(4), team_id(8));
