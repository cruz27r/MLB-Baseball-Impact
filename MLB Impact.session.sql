-- One-time: canonical merged table without team/year/src_table
CREATE TABLE IF NOT EXISTS stg_rs_rosters_merged (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  retro_id      VARCHAR(16)  NOT NULL,
  last_name     VARCHAR(64)  NOT NULL,
  first_name    VARCHAR(64)  NOT NULL,
  bats          CHAR(1)      NULL,
  throws        CHAR(1)      NULL,
  team_code     VARCHAR(8)   NULL,
  debut_date    DATE         NULL,
  role          ENUM('PLAYER','UMPIRE') NOT NULL,
  load_batch_id INT          NOT NULL DEFAULT 0,
  load_ts       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Larger concat buffer for the UNION text
SET SESSION group_concat_max_len = 1000000;

-- Tag this run (optional)
SET @batch_id = UNIX_TIMESTAMP();

-- Build UNION ALL across all roster shards (no team/year)
SELECT GROUP_CONCAT(
         CONCAT(
           'SELECT ',
           'c1 AS retro_id, c2 AS last_name, c3 AS first_name, ',
           'c4 AS bats, c5 AS throws, c6 AS team_code, ',
           'NULLIF(NULLIF(c7, ''''), ''0000-00-00'') AS debut_date, ',
           QUOTE(m.role_guess), ' AS role, ',
           @batch_id, ' AS load_batch_id ',
           'FROM `', m.table_name, '`'
         )
         SEPARATOR ' UNION ALL ')
INTO @ros_union_select
FROM (
  -- derive role once from the table name
  SELECT
    t.table_name,
    CASE
      WHEN UPPER(t.table_name) LIKE '%UMPIRE%' OR UPPER(t.table_name) LIKE '%UMP%' THEN 'UMPIRE'
      ELSE 'PLAYER'
    END AS role_guess
  FROM information_schema.tables t
  WHERE t.table_schema = DATABASE()
    AND t.table_name LIKE 'stg_retrosheet_rosters_rosters\_%' ESCAPE '\\'
) AS m;

-- Merge (no-op if nothing to merge)
SET @ros_merge_sql =
  IF(@ros_union_select IS NULL OR LENGTH(@ros_union_select)=0,
     'SELECT ''No roster shards found'' AS msg',
     CONCAT(
       'INSERT INTO stg_rs_rosters_merged ',
       '(retro_id, last_name, first_name, bats, throws, team_code, debut_date, role, load_batch_id) ',
       'SELECT retro_id, last_name, first_name, bats, throws, team_code, debut_date, role, load_batch_id ',
       'FROM (', @ros_union_select, ') u'
     )
  );

PREPARE ros_mrg FROM @ros_merge_sql;
EXECUTE ros_mrg;
DEALLOCATE PREPARE ros_mrg;

-- Drop the original shard tables now that they’re merged
SELECT GROUP_CONCAT(CONCAT('`', t.table_name, '`') SEPARATOR ', ')
INTO @ros_drop_list
FROM information_schema.tables t
WHERE t.table_schema = DATABASE()
  AND t.table_name LIKE 'stg_retrosheet_rosters_rosters\_%' ESCAPE '\\';

SET @ros_drop_sql =
  IF(@ros_drop_list IS NULL OR LENGTH(@ros_drop_list)=0,
     'SELECT ''No shards to drop'' AS msg',
     CONCAT('DROP TABLE IF EXISTS ', @ros_drop_list)
  );

PREPARE ros_drop FROM @ros_drop_sql;
EXECUTE ros_drop;
DEALLOCATE PREPARE ros_drop;

-- Quick verification
SELECT COUNT(*) AS total_rows,
       MIN(load_batch_id) AS min_batch, MAX(load_batch_id) AS max_batch
FROM stg_rs_rosters_merged;