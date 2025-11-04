#!/usr/bin/env python3
"""
Merge Retrosheet roster shards into per-team history tables.

Looks for tables named: stg_retrosheet_rosters_rosters_<team3><yyyy>_ros
e.g. stg_retrosheet_rosters_rosters_wse1924_ros

Per-team output tables:
  stg_rs_rosters_team_<team3>

Each row stamped with:
  year_guess INT
  role ENUM('PLAYER','UMPIRE')

Dependencies: PyMySQL  (pip install pymysql)
"""

import re
import sys
import pymysql
from contextlib import closing

# ----------------------------
# Config — update as needed
# ----------------------------
MYSQL = dict(
    host="127.0.0.1",
    port=3306,
    user="root",
    password="Ricky072701",
    database="mlb_impact",
    cursorclass=pymysql.cursors.DictCursor,
    autocommit=False,
)

TABLE_PREFIX = "stg_retrosheet_rosters_rosters_"
TABLE_SUFFIX = "_ros"
TEAM_TABLE_PREFIX = "stg_rs_rosters_team_"

# Behavior switches
DRY_RUN = False          # set True to just print what would happen
KEEP_SHARDS = False      # set False to drop shard tables after merging
ADD_SRC_TABLE = False    # set True to keep lineage of shard table name

# Regex to capture team & year from table name
NAME_RE = re.compile(
    rf"^{re.escape(TABLE_PREFIX)}([A-Za-z0-9]{3})(\d{{4}}){re.escape(TABLE_SUFFIX)}$",
    re.IGNORECASE,
)

def parse_team_year(table_name: str):
    m = NAME_RE.match(table_name)
    if not m:
        return None
    team3 = m.group(1).lower()
    year = int(m.group(2))
    return team3, year

def is_umpire_table(table_name: str) -> bool:
    up = table_name.upper()
    return ("UMP" in up) or ("UMPIRE" in up)

def ensure_team_table(cur, team3: str):
    """Create the per-team table if it doesn't exist."""
    cols = [
        "retro_id   VARCHAR(16)  NOT NULL",
        "last_name  VARCHAR(64)  NOT NULL",
        "first_name VARCHAR(64)  NOT NULL",
        "bats       CHAR(1)      NULL",
        "throws     CHAR(1)      NULL",
        "team_code  VARCHAR(8)   NULL",
        "debut_date DATE         NULL",
        "role       ENUM('PLAYER','UMPIRE') NOT NULL",
        "year_guess INT          NOT NULL",
        "load_ts    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ]
    if ADD_SRC_TABLE:
        cols.insert(0, "src_table VARCHAR(128) NOT NULL")

    pk_cols = ["retro_id", "year_guess", "role"]
    table_sql = f"""
        CREATE TABLE IF NOT EXISTS `{TEAM_TABLE_PREFIX}{team3}` (
            {", ".join(cols)},
            PRIMARY KEY ({", ".join(pk_cols)}),
            KEY idx_role_year (role, year_guess),
            KEY idx_last_first (last_name, first_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """
    cur.execute(table_sql)

def ensure_umpire_table(cur):
    """Create the global umpire roster table if it doesn't exist."""
    cols = [
        "retro_id   VARCHAR(16)  NOT NULL",
        "last_name  VARCHAR(64)  NOT NULL",
        "first_name VARCHAR(64)  NOT NULL",
        "crew_code  VARCHAR(16)  NULL",
        "year_guess INT          NOT NULL",
        "load_ts    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ]
    if ADD_SRC_TABLE:
        cols.insert(0, "src_table VARCHAR(128) NOT NULL")

    pk_cols = ["retro_id", "year_guess"]
    table_sql = f"""
        CREATE TABLE IF NOT EXISTS `stg_rs_rosters_umpires` (
            {", ".join(cols)},
            PRIMARY KEY ({", ".join(pk_cols)}),
            KEY idx_year (year_guess),
            KEY idx_last_first (last_name, first_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """
    cur.execute(table_sql)

def merge_one_shard_into_umpires(cur, shard_table: str, year: int):
    """
    Insert rows from one umpire shard into the global umpire table.
    Assumes the shard has columns:
      c1 retro_id, c2 last, c3 first, c6 crew_code (or blank), c7 debut (ignored)
    """
    dest = "stg_rs_rosters_umpires"
    select_cols = [
        "c1 AS retro_id",
        "c2 AS last_name",
        "c3 AS first_name",
        "c6 AS crew_code",
        f"{year} AS year_guess",
    ]
    insert_cols = ["retro_id", "last_name", "first_name", "crew_code", "year_guess"]

    if ADD_SRC_TABLE:
        select_cols.insert(0, f"'{shard_table}' AS src_table")
        insert_cols.insert(0, "src_table")

    sql = f"""
        INSERT IGNORE INTO `{dest}` ({", ".join(insert_cols)})
        SELECT {", ".join(select_cols)}
        FROM `{shard_table}`;
    """
    cur.execute(sql)

def merge_one_shard_into_team(cur, shard_table: str, team3: str, year: int, role: str):
    """Insert rows from one shard into the per-team table."""
    dest = f"{TEAM_TABLE_PREFIX}{team3}"
    # c1..c7 are the raw shard columns (retro_id, last, first, bats, throws, team_code, debut_date)
    select_cols = [
        "c1 AS retro_id",
        "c2 AS last_name",
        "c3 AS first_name",
        "c4 AS bats",
        "c5 AS throws",
        "c6 AS team_code",
        "CASE\n        WHEN c7 IS NULL OR c7 = '' OR c7 = '0000-00-00' THEN NULL\n        WHEN c7 REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN STR_TO_DATE(c7, '%Y-%m-%d')\n        WHEN c7 REGEXP '^[0-9]{8}$' THEN STR_TO_DATE(c7, '%Y%m%d')\n        ELSE NULL\n      END AS debut_date",
        f"'{role}' AS role",
        f"{year} AS year_guess",
    ]

    insert_cols = [
        "retro_id", "last_name", "first_name", "bats", "throws",
        "team_code", "debut_date", "role", "year_guess"
    ]

    if ADD_SRC_TABLE:
        select_cols.insert(0, f"'{shard_table}' AS src_table")
        insert_cols.insert(0, "src_table")

    sql = f"""
        INSERT IGNORE INTO `{dest}` ({", ".join(insert_cols)})
        SELECT {", ".join(select_cols)}
        FROM `{shard_table}`;
    """
    cur.execute(sql)

def drop_shard(cur, table_name: str):
    cur.execute(f"DROP TABLE IF EXISTS `{table_name}`;")

def main():
    with closing(pymysql.connect(**MYSQL)) as conn, closing(conn.cursor()) as cur:
        # 1) discover shard tables with proper underscore escaping
        like_pattern = (
            TABLE_PREFIX.replace("_", r"\_")
            + "%"
            + TABLE_SUFFIX.replace("_", r"\_")
        )
        print(f"Searching in schema: {MYSQL['database']} with LIKE: {like_pattern} (underscores escaped)")

        cur.execute(
            """
            SELECT t.TABLE_NAME AS tbl
            FROM information_schema.tables AS t
            WHERE t.TABLE_SCHEMA = %s
              AND t.TABLE_NAME LIKE %s ESCAPE '\\\\'
            """,
            (MYSQL["database"], like_pattern),
        )
        shard_rows = cur.fetchall()
        shard_names = [r["tbl"] for r in shard_rows]
        print(f"Discovered {len(shard_names)} shard table(s) matching pattern.")
        if shard_names[:10]:
            print("Sample:", ", ".join(shard_names[:10]))

        if not shard_names:
            print("No roster shard tables found; nothing to do.")
            return 0

        # group shards by team
        by_team = {}
        umpire_shards = []  # list of tuples: (shard_table, year)
        bad = []
        to_drop_after_commit = []  # defer dropping shards until successful commit
        for tname in shard_names:
            parsed = parse_team_year(tname)
            if not parsed:
                bad.append(tname)
                continue
            team3, year = parsed
            if is_umpire_table(tname) or team3.lower() == "ump":
                umpire_shards.append((tname, year))
                continue
            by_team.setdefault(team3, []).append((tname, team3, year, False))

        if bad:
            print("Skipping tables that don't match expected name pattern:")
            print("  Expected regex:", NAME_RE.pattern)
            for n in bad[:25]:
                print("   -", n)
            if len(bad) > 25:
                print(f"   ... and {len(bad)-25} more")

        print(f"Found {sum(len(v) for v in by_team.values())} shard(s) across {len(by_team)} team(s).")
        if DRY_RUN:
            for team3, shards in sorted(by_team.items()):
                print(f"[DRY RUN] {team3}: {len(shards)} shard(s)")
            return 0

        # 2) process global umpire shards first
        if umpire_shards:
            print(f"==> Umpires: {len(umpire_shards)} shard(s)")
            ensure_umpire_table(cur)
            for shard_table, year in sorted(umpire_shards, key=lambda x: x[1]):
                print(f"   merging {shard_table}  ->  stg_rs_rosters_umpires  (year={year})")
                merge_one_shard_into_umpires(cur, shard_table, year)
                if not KEEP_SHARDS:
                    to_drop_after_commit.append(shard_table)
            cur.execute("SELECT COUNT(*) AS c FROM `stg_rs_rosters_umpires`;")
            c = cur.fetchone()["c"]
            print(f"   current rows in stg_rs_rosters_umpires: {c:,}")

        # 3) process per-team
        total_inserted = 0
        for team3, shards in sorted(by_team.items()):
            print(f"==> Team {team3}: {len(shards)} shard(s)")
            ensure_team_table(cur, team3)

            for shard_table, _team3, year, ump in sorted(shards, key=lambda x: x[2]):
                role = "PLAYER"
                print(f"   merging {shard_table}  ->  {TEAM_TABLE_PREFIX}{team3}  (year={year}, role={role})")
                merge_one_shard_into_team(cur, shard_table, team3, year, role)

                if not KEEP_SHARDS:
                    to_drop_after_commit.append(shard_table)

            # quick count for this team
            cur.execute(f"SELECT COUNT(*) AS c FROM `{TEAM_TABLE_PREFIX}{team3}`;")
            c = cur.fetchone()["c"]
            total_inserted += c
            print(f"   current rows in {TEAM_TABLE_PREFIX}{team3}: {c:,}")

        conn.commit()

        # 4) Only now (after a successful commit) drop original shard tables
        if not KEEP_SHARDS and to_drop_after_commit:
            print("\nDropping original shard tables after successful commit...")
            for tname in to_drop_after_commit:
                print(f"   dropping {tname}")
                drop_shard(cur, tname)
            print(f"Dropped {len(to_drop_after_commit)} shard table(s).")

        print("\nAll done.")
        print(f"Per-team tables created/updated: {len(by_team)}")
        if umpire_shards:
            print(f"Umpire shards processed: {len(umpire_shards)} (merged into stg_rs_rosters_umpires)")
        if not KEEP_SHARDS and (umpire_shards or by_team):
            print("Original shard tables were dropped.")
        return 0

if __name__ == "__main__":
    sys.exit(main())