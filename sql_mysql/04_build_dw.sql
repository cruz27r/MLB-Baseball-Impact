DROP TABLE IF EXISTS dw_player_origin;
CREATE TABLE dw_player_origin AS
SELECT
  p.retro_id,
  CASE
    WHEN UPPER(TRIM(p.birth_country)) IN ('USA','UNITED STATES','UNITED STATES OF AMERICA') THEN 'USA'
    WHEN TRIM(p.birth_country) = '' OR p.birth_country IS NULL THEN 'Unknown'
    ELSE 'Foreign'
  END AS origin,
  COALESCE(NULLIF(TRIM(p.birth_country),''),'Unknown') AS birth_country,
  TRIM(p.first) AS first_name,
  TRIM(p.last)  AS last_name
FROM staging_people p;

CREATE INDEX IF NOT EXISTS idx_dw_origin_retro ON dw_player_origin(retro_id);

DROP TABLE IF EXISTS dw_roster_composition;
CREATE TABLE dw_roster_composition AS
SELECT
  CAST(a.year_id AS UNSIGNED) AS year,
  o.origin,
  COUNT(DISTINCT a.retro_id) AS players
FROM (
  SELECT * FROM staging_appearances WHERE year_id REGEXP '^[0-9]{4}$'
) a
JOIN dw_player_origin o ON o.retro_id = a.retro_id
GROUP BY year, o.origin;

DROP VIEW IF EXISTS v_roster_share;
CREATE VIEW v_roster_share AS
SELECT
  r.year,
  r.origin,
  r.players,
  ROUND(r.players / SUM(r.players) OVER (PARTITION BY r.year), 4) AS share
FROM dw_roster_composition r;

DROP VIEW IF EXISTS v_war_share;
CREATE VIEW v_war_share AS
SELECT
  CAST(wb.year_id AS UNSIGNED) AS year,
  o.origin,
  SUM(CASE WHEN wb.war REGEXP '^-?[0-9.]+$' THEN wb.war+0 ELSE 0 END) AS war_total
FROM staging_war_bat wb
JOIN dw_player_origin o ON o.retro_id = wb.player_id
GROUP BY year, o.origin;
