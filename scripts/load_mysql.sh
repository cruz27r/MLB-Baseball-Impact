#!/usr/bin/env bash
set -euo pipefail

DB="${1:-mlb}"
USER="${2:-mlbuser}"
PASS="${3:-mlbpass}"
HOST="${4:-127.0.0.1}"
PORT="${5:-3306}"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

RETRO_CSV="$ROOT_DIR/data/retrosheet/csv"
BREF_DIR="$ROOT_DIR/data/bref_war"

mysql() { command mysql -u"$USER" -p"$PASS" -h"$HOST" -P"$PORT" "$DB" --local-infile=1 -e "$1"; }

echo "== Schemas =="
mysql "SOURCE $ROOT_DIR/sql_mysql/01_create_schemas.sql;"
mysql "SOURCE $ROOT_DIR/sql_mysql/02_create_staging.sql;"

echo "== Load Retrosheet CSVs =="
if [ -f "$RETRO_CSV/people.csv" ]; then
  mysql "TRUNCATE staging_people;"
  mysql "LOAD DATA LOCAL INFILE '$RETRO_CSV/people.csv'
        INTO TABLE staging_people
        FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES
        (retro_id,last,first,bats,throws,birth_date,birth_city,birth_state,birth_country,@debut,@final_game,height,weight,use_name);"
else
  echo "WARN: $RETRO_CSV/people.csv not found"
fi

if [ -f "$RETRO_CSV/appearances.csv" ]; then
  mysql "TRUNCATE staging_appearances;"
  mysql "LOAD DATA LOCAL INFILE '$RETRO_CSV/appearances.csv'
        INTO TABLE staging_appearances
        FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES
        (year_id,team_id,lg_id,retro_id,g_all);"
fi

if [ -f "$RETRO_CSV/teams.csv" ]; then
  mysql "TRUNCATE staging_teams;"
  mysql "LOAD DATA LOCAL INFILE '$RETRO_CSV/teams.csv'
        INTO TABLE staging_teams
        FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES
        (year_id,lg_id,team_id,franch_id,div_id,rank,g,w,l,name);"
fi

echo "== Load B-Ref WAR (optional) =="
if [ -f "$BREF_DIR/war_daily_bat.csv" ]; then
  mysql "TRUNCATE staging_war_bat;"
  mysql "LOAD DATA LOCAL INFILE '$BREF_DIR/war_daily_bat.csv'
        INTO TABLE staging_war_bat
        FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES
        (player_id,year_id,team_id,@runs_bat,@runs_def,@war)
        SET runs_bat=@runs_bat, runs_def=@runs_def, war=@war;"
fi

if [ -f "$BREF_DIR/war_daily_pitch.csv" ]; then
  mysql "TRUNCATE staging_war_pitch;"
  mysql "LOAD DATA LOCAL INFILE '$BREF_DIR/war_daily_pitch.csv'
        INTO TABLE staging_war_pitch
        FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES
        (player_id,year_id,team_id,@war)
        SET war=@war;"
fi

echo "== Indexes + DW =="
mysql "SOURCE $ROOT_DIR/sql_mysql/03_load_helpers.sql;"
mysql "SOURCE $ROOT_DIR/sql_mysql/04_build_dw.sql;"

echo "✅ MySQL load complete."
