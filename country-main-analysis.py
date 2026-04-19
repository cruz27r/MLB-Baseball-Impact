#!/usr/bin/env python3
"""country-main-analysis.py

End-to-end country/foreign-born analysis over Retrosheet-derived tables.

This script is designed to be robust to small schema differences across your
CSV imports. It:

1) Detects key columns in each <prefix>-allplayers_1899_2024 (birthCountry, birthYear,
   firstGame/debut, playerID, etc.) and builds a clean staging view per prefix.
2) Computes country distributions overall, by birth decade, and by debut decade per prefix.
3) Computes foreign-vs-US batting/pitching performance summaries by decade per prefix.

Note: All output tables are created per prefix (e.g., main_country_overall_pct, allstar_country_overall_pct, etc.).

You can pass multiple prefixes (e.g., regular, postseason) to analyze multiple sets. If both "regular" and "postseason"
prefixes are included, the script will optionally create regular-vs-postseason comparison tables for key stats.

You can extend this with more subparts later without breaking existing tables.

Run:
  python3 country-main-analysis.py --db mlb_impact --user root --password '...'

"""

from __future__ import annotations

import argparse
import sys
import re
from typing import Dict, List, Optional, Tuple

import pymysql


# ----------------------------- CLI / Connection -----------------------------

def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Main Retrosheet country/foreign-born analysis")
    p.add_argument("--host", default="127.0.0.1")
    p.add_argument("--port", type=int, default=3306)
    p.add_argument("--db", default="mlb_impact")
    p.add_argument("--user", default="root")
    p.add_argument("--password", default="Ricky072701")
    p.add_argument("--charset", default="utf8mb4")
    p.add_argument("--rebuild", action="store_true", help="Drop/recreate summary tables")
    p.add_argument("--debug", action="store_true")
    p.add_argument(
        "--prefixes",
        default="main",
        help="Comma-separated table prefixes/folders to analyze (e.g., main,regular,basiccsvs,allstar). Default: main",
    )
    return p.parse_args()


# ----------------- Database Connection Function -----------------
def connect_db(args: argparse.Namespace) -> pymysql.Connection:
    if args.debug:
        print(f"[DEBUG] Opening MySQL connection to {args.host}:{args.port} as {args.user}, db={args.db}")
    return pymysql.connect(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.db,
        charset=args.charset,
        autocommit=False,
        cursorclass=pymysql.cursors.DictCursor,
    )

# --------------------- Regular vs Postseason Comparison Tables ---------------------

def ensure_regular_postseason_comparison_tables(cur, rebuild: bool = False, debug: bool = False):
    """Create regular-vs-postseason comparison tables using the already-derived
    foreign_vs_us_*_year summary tables for the 'regular' and 'postseason' prefixes.

    This only reads from existing summary tables such as
      - regular_foreign_vs_us_batting_year
      - postseason_foreign_vs_us_batting_year
      - regular_foreign_vs_us_pitching_year
      - postseason_foreign_vs_us_pitching_year

    It never assumes new raw Retrosheet/Lahman tables or extra columns.
    """
    bat_reg = "regular_foreign_vs_us_batting_year"
    bat_post = "postseason_foreign_vs_us_batting_year"
    pit_reg = "regular_foreign_vs_us_pitching_year"
    pit_post = "postseason_foreign_vs_us_pitching_year"

    # ----------------- Batting comparison table -----------------
    if table_exists(cur, bat_reg) and table_exists(cur, bat_post):
        target = "regular_vs_postseason_foreign_vs_us_batting_year"
        if rebuild:
            cur.execute(f"DROP TABLE IF EXISTS {target}")

        cur.execute(
            f"""
            CREATE TABLE IF NOT EXISTS {target} (
                year INT NOT NULL,
                group_type ENUM('US','FOREIGN') NOT NULL,
                reg_player_count BIGINT NULL,
                reg_games BIGINT NULL,
                reg_ab BIGINT NULL,
                reg_h BIGINT NULL,
                reg_hr BIGINT NULL,
                reg_bb BIGINT NULL,
                reg_so BIGINT NULL,
                reg_avg DOUBLE NULL,
                reg_hr_rate DOUBLE NULL,
                reg_bb_rate DOUBLE NULL,
                reg_so_rate DOUBLE NULL,
                ps_player_count BIGINT NULL,
                ps_games BIGINT NULL,
                ps_ab BIGINT NULL,
                ps_h BIGINT NULL,
                ps_hr BIGINT NULL,
                ps_bb BIGINT NULL,
                ps_so BIGINT NULL,
                ps_avg DOUBLE NULL,
                ps_hr_rate DOUBLE NULL,
                ps_bb_rate DOUBLE NULL,
                ps_so_rate DOUBLE NULL,
                diff_avg DOUBLE NULL,
                diff_hr_rate DOUBLE NULL,
                diff_bb_rate DOUBLE NULL,
                diff_so_rate DOUBLE NULL,
                PRIMARY KEY (year, group_type)
            ) ENGINE=InnoDB;
            """
        )

        cur.execute(f"TRUNCATE TABLE {target}")

        cur.execute(
            f"""
            INSERT INTO {target}
            (year, group_type,
             reg_player_count, reg_games, reg_ab, reg_h, reg_hr, reg_bb, reg_so, reg_avg, reg_hr_rate, reg_bb_rate, reg_so_rate,
             ps_player_count, ps_games, ps_ab, ps_h, ps_hr, ps_bb, ps_so, ps_avg, ps_hr_rate, ps_bb_rate, ps_so_rate,
             diff_avg, diff_hr_rate, diff_bb_rate, diff_so_rate)
            SELECT
                r.year,
                r.group_type,
                r.player_count AS reg_player_count,
                r.games AS reg_games,
                r.ab AS reg_ab,
                r.h AS reg_h,
                r.hr AS reg_hr,
                r.bb AS reg_bb,
                r.so AS reg_so,
                r.avg AS reg_avg,
                r.hr_rate AS reg_hr_rate,
                r.bb_rate AS reg_bb_rate,
                r.so_rate AS reg_so_rate,
                p.player_count AS ps_player_count,
                p.games AS ps_games,
                p.ab AS ps_ab,
                p.h AS ps_h,
                p.hr AS ps_hr,
                p.bb AS ps_bb,
                p.so AS ps_so,
                p.avg AS ps_avg,
                p.hr_rate AS ps_hr_rate,
                p.bb_rate AS ps_bb_rate,
                p.so_rate AS ps_so_rate,
                (p.avg - r.avg) AS diff_avg,
                (p.hr_rate - r.hr_rate) AS diff_hr_rate,
                (p.bb_rate - r.bb_rate) AS diff_bb_rate,
                (p.so_rate - r.so_rate) AS diff_so_rate
            FROM {bat_reg} r
            JOIN {bat_post} p
              ON p.year = r.year AND p.group_type = r.group_type;
            """
        )
    elif debug:
        print("[INFO] Skipping regular_vs_postseason batting comparison (missing regular/postseason batting summary tables)")

    # ----------------- Pitching comparison table -----------------
    if table_exists(cur, pit_reg) and table_exists(cur, pit_post):
        target = "regular_vs_postseason_foreign_vs_us_pitching_year"
        if rebuild:
            cur.execute(f"DROP TABLE IF EXISTS {target}")

        cur.execute(
            f"""
            CREATE TABLE IF NOT EXISTS {target} (
                year INT NOT NULL,
                group_type ENUM('US','FOREIGN') NOT NULL,
                reg_player_count BIGINT NULL,
                reg_games BIGINT NULL,
                reg_ip_outs BIGINT NULL,
                reg_h_allowed BIGINT NULL,
                reg_hr_allowed BIGINT NULL,
                reg_bb_allowed BIGINT NULL,
                reg_so BIGINT NULL,
                reg_era DOUBLE NULL,
                reg_hr9 DOUBLE NULL,
                reg_bb9 DOUBLE NULL,
                reg_so9 DOUBLE NULL,
                ps_player_count BIGINT NULL,
                ps_games BIGINT NULL,
                ps_ip_outs BIGINT NULL,
                ps_h_allowed BIGINT NULL,
                ps_hr_allowed BIGINT NULL,
                ps_bb_allowed BIGINT NULL,
                ps_so BIGINT NULL,
                ps_era DOUBLE NULL,
                ps_hr9 DOUBLE NULL,
                ps_bb9 DOUBLE NULL,
                ps_so9 DOUBLE NULL,
                diff_era DOUBLE NULL,
                diff_hr9 DOUBLE NULL,
                diff_bb9 DOUBLE NULL,
                diff_so9 DOUBLE NULL,
                PRIMARY KEY (year, group_type)
            ) ENGINE=InnoDB;
            """
        )

        cur.execute(f"TRUNCATE TABLE {target}")

        cur.execute(
            f"""
            INSERT INTO {target}
            (year, group_type,
             reg_player_count, reg_games, reg_ip_outs, reg_h_allowed, reg_hr_allowed, reg_bb_allowed, reg_so, reg_era, reg_hr9, reg_bb9, reg_so9,
             ps_player_count, ps_games, ps_ip_outs, ps_h_allowed, ps_hr_allowed, ps_bb_allowed, ps_so, ps_era, ps_hr9, ps_bb9, ps_so9,
             diff_era, diff_hr9, diff_bb9, diff_so9)
            SELECT
                r.year,
                r.group_type,
                r.player_count AS reg_player_count,
                r.games AS reg_games,
                r.ip_outs AS reg_ip_outs,
                r.h_allowed AS reg_h_allowed,
                r.hr_allowed AS reg_hr_allowed,
                r.bb_allowed AS reg_bb_allowed,
                r.so AS reg_so,
                r.era AS reg_era,
                r.hr9 AS reg_hr9,
                r.bb9 AS reg_bb9,
                r.so9 AS reg_so9,
                p.player_count AS ps_player_count,
                p.games AS ps_games,
                p.ip_outs AS ps_ip_outs,
                p.h_allowed AS ps_h_allowed,
                p.hr_allowed AS ps_hr_allowed,
                p.bb_allowed AS ps_bb_allowed,
                p.so AS ps_so,
                p.era AS ps_era,
                p.hr9 AS ps_hr9,
                p.bb9 AS ps_bb9,
                p.so9 AS ps_so9,
                (p.era - r.era) AS diff_era,
                (p.hr9 - r.hr9) AS diff_hr9,
                (p.bb9 - r.bb9) AS diff_bb9,
                (p.so9 - r.so9) AS diff_so9
            FROM {pit_reg} r
            JOIN {pit_post} p
              ON p.year = r.year AND p.group_type = r.group_type;
            """
        )
    elif debug:
        print("[INFO] Skipping regular_vs_postseason pitching comparison (missing regular/postseason pitching summary tables)")

# --------------------------- Staging / Cleaning ----------------------------

def table_name(prefix: str, base: str) -> str:
    """Build full table name from a prefix and a base name.

    Examples:
      prefix='main', base='allplayers_1899_2024' -> 'main-allplayers_1899_2024'
      prefix='allstar', base='batting_1899_2024' -> 'allstar-batting_1899_2024'
    """
    return f"{prefix}-{base}"


# ----------------------------- Schema Helpers ------------------------------

def get_columns(cur, table_name: str) -> List[str]:
    cur.execute(
        """
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
        """,
        (table_name,),
    )
    return [r["COLUMN_NAME"] for r in cur.fetchall()]

# ---------------------- Table Listing Helpers -----------------------

def list_tables(cur, like_pattern: str) -> List[str]:
    cur.execute(
        """
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE %s
        """,
        (like_pattern,),
    )
    return [r["TABLE_NAME"] for r in cur.fetchall()]


def table_exists(cur, name: str) -> bool:
    cur.execute(
        """
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
        LIMIT 1
        """,
        (name,),
    )
    return cur.fetchone() is not None


def pick_first_table(cur, patterns: List[str]) -> Optional[str]:
    for pat in patterns:
        tbls = list_tables(cur, pat)
        if tbls:
            return tbls[0]
    return None
# --------------------------- Summary Table DDL -----------------------------

# --------------------------- Biodata / Roster Helpers ----------------------

def ensure_bio_view(cur, prefix: str, rebuild: bool = False, debug: bool = False) -> Optional[str]:
    """Create a VIEW <prefix>_bio_clean that UNIONs all biodata/roster tables for this prefix.

    We look for any tables matching biodata/bio/roster patterns inside this prefix.
    Each SELECT standardizes to:
      - player_id
      - bats
      - throws_hand
      - height
      - weight
      - source_table  (the original table name)

    Tables that don't contain any of these columns besides a player id are still included
    (their missing columns become NULL). This lets you analyze ALL biodata/roster sources
    in one combined output table.

    Returns the view name if created, else None.
    """
    bio_tables: List[str] = []
    # Prefixed biodata/roster tables (from subfolders)
    for pat in [
        f"{prefix}-biodata%",
        f"{prefix}-bio%",
        f"{prefix}-roster%",
        f"{prefix}-rosters%",
    ]:
        bio_tables.extend(list_tables(cur, pat))

    # Also include any unprefixed biodata/roster tables in the DB (older imports)
    for pat in [
        "biodata%",
        "bio%",
        "roster%",
        "rosters%",
    ]:
        bio_tables.extend(list_tables(cur, pat))

    # Keep only tables that are actually player biodata/rosters.
    # Skip facility/staff/other context tables that don't help foreign-vs-US player analysis.
    deny_keywords = {
        "relatives", "relative",
        "ballparks", "ballpark",
        "umpires", "umpire",
        "teams", "team",
        "coaches", "coach",
        "managers", "manager",
    }

    filtered: List[str] = []
    for t in sorted(set(bio_tables)):
        base = t.split(f"{prefix}-", 1)[-1].lower()
        if any(k in base for k in deny_keywords):
            continue
        filtered.append(t)

    bio_tables = filtered
    if not bio_tables:
        if debug:
            print(f"[INFO] No biodata/roster tables found for prefix={prefix}; skipping bio summaries")
        return None

    selects: List[str] = []
    for bio_table in bio_tables:
        cols = get_columns(cur, bio_table)

        # If a table doesn't appear to be player-oriented, skip it.
        pid_candidate = pick_column(cols, ["playerID", "playerId", "retroID", "id", "player_id"])
        has_any_useful = any(
            pick_column(cols, [c]) is not None
            for c in ["bats", "batHand", "bathand", "throws", "pitchHand", "pithand", "height", "ht", "weight", "wt"]
        )
        if not pid_candidate and not has_any_useful:
            if debug:
                print(f"[INFO] Skipping non-player biodata table {bio_table} (no player id / useful columns)")
            continue

        pid = pid_candidate or "playerID"
        bats = pick_column(cols, ["bats", "batHand", "bathand"])
        throws = pick_column(cols, ["throws", "pitchHand", "pithand"])
        height = pick_column(cols, ["height", "ht", "height_in", "height_cm"])
        weight = pick_column(cols, ["weight", "wt", "weight_lb", "weight_kg"])

        def sel(col: Optional[str], cast: Optional[str] = None) -> str:
            if not col or col not in cols:
                return "NULL"
            if cast:
                return f"CAST(NULLIF({col}, '') AS {cast})"
            return f"NULLIF(TRIM({col}), '')"

        if debug:
            print(f"[DEBUG] Using biodata table {bio_table} for prefix={prefix}")
            print("        pid=", pid, "bats=", bats, "throws=", throws, "height=", height, "weight=", weight)

        selects.append(
            f"""
            SELECT
                {pid} AS player_id,
                {sel(bats)} AS bats,
                {sel(throws)} AS throws_hand,
                {sel(height, 'DOUBLE')} AS height,
                {sel(weight, 'DOUBLE')} AS weight,
                '{bio_table}' AS source_table
            FROM `{bio_table}`
            """.strip()
        )

    view_name = f"{prefix}_bio_clean"

    # Always ensure we can create the view name:
    # - Drop any TABLE with this name (from older runs).
    # - Use CREATE OR REPLACE VIEW so existing views are overwritten cleanly.
    cur.execute(f"DROP TABLE IF EXISTS `{view_name}`")

    union_sql = "\nUNION ALL\n".join(selects)
    cur.execute(f"CREATE OR REPLACE VIEW {view_name} AS\n{union_sql};")
    return view_name


def ensure_bio_summary_table(cur, prefix: str, rebuild: bool = False):
    if rebuild:
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_foreign_vs_us_bio_overall")

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_foreign_vs_us_bio_overall (
            source_table VARCHAR(128) NOT NULL DEFAULT 'ALL',
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            avg_height DOUBLE NULL,
            avg_weight DOUBLE NULL,
            bats_L_pct DOUBLE NULL,
            bats_R_pct DOUBLE NULL,
            bats_B_pct DOUBLE NULL,
            throws_L_pct DOUBLE NULL,
            throws_R_pct DOUBLE NULL,
            PRIMARY KEY (source_table, group_type)
        ) ENGINE=InnoDB;
        """
    )


def compute_foreign_vs_us_bio_overall(cur, prefix: str, bio_view: str, debug: bool = False):
    target = f"{prefix}_foreign_vs_us_bio_overall"
    players_view = f"{prefix}_players_clean"

    if debug:
        print(f"[STEP] Computing foreign vs US biodata summary for {prefix} from {bio_view}")

    cur.execute(f"TRUNCATE TABLE {target}")

    # Per-source rows
    cur.execute(
        f"""
        INSERT INTO {target}
        (source_table, group_type, player_count, avg_height, avg_weight,
         bats_L_pct, bats_R_pct, bats_B_pct, throws_L_pct, throws_R_pct)
        WITH joined AS (
            SELECT
                p.player_id,
                {_country_group_expr('p.birth_country')} AS group_type,
                b.bats,
                b.throws_hand,
                b.height,
                b.weight,
                b.source_table
            FROM {players_view} p
            LEFT JOIN {bio_view} b
              ON p.player_id = b.player_id
            WHERE {_country_group_expr('p.birth_country')} IS NOT NULL
        )
        SELECT
            source_table,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            AVG(height) AS avg_height,
            AVG(weight) AS avg_weight,
            AVG(CASE WHEN UPPER(bats)='L' THEN 1 ELSE 0 END) AS bats_L_pct,
            AVG(CASE WHEN UPPER(bats)='R' THEN 1 ELSE 0 END) AS bats_R_pct,
            AVG(CASE WHEN UPPER(bats) IN ('B','S') THEN 1 ELSE 0 END) AS bats_B_pct,
            AVG(CASE WHEN UPPER(throws_hand)='L' THEN 1 ELSE 0 END) AS throws_L_pct,
            AVG(CASE WHEN UPPER(throws_hand)='R' THEN 1 ELSE 0 END) AS throws_R_pct
        FROM joined
        WHERE source_table IS NOT NULL
        GROUP BY source_table, group_type;
        """
    )

    # Overall ALL-sources rollup
    cur.execute(
        f"""
        INSERT INTO {target}
        (source_table, group_type, player_count, avg_height, avg_weight,
         bats_L_pct, bats_R_pct, bats_B_pct, throws_L_pct, throws_R_pct)
        WITH joined AS (
            SELECT
                p.player_id,
                {_country_group_expr('p.birth_country')} AS group_type,
                b.bats,
                b.throws_hand,
                b.height,
                b.weight
            FROM {players_view} p
            LEFT JOIN {bio_view} b
              ON p.player_id = b.player_id
            WHERE {_country_group_expr('p.birth_country')} IS NOT NULL
        )
        SELECT
            'ALL' AS source_table,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            AVG(height) AS avg_height,
            AVG(weight) AS avg_weight,
            AVG(CASE WHEN UPPER(bats)='L' THEN 1 ELSE 0 END) AS bats_L_pct,
            AVG(CASE WHEN UPPER(bats)='R' THEN 1 ELSE 0 END) AS bats_R_pct,
            AVG(CASE WHEN UPPER(bats) IN ('B','S') THEN 1 ELSE 0 END) AS bats_B_pct,
            AVG(CASE WHEN UPPER(throws_hand)='L' THEN 1 ELSE 0 END) AS throws_L_pct,
            AVG(CASE WHEN UPPER(throws_hand)='R' THEN 1 ELSE 0 END) AS throws_R_pct
        FROM joined
        GROUP BY group_type;
        """
    )


def pick_column(columns: List[str], candidates: List[str]) -> Optional[str]:
    lower_map = {c.lower(): c for c in columns}
    for cand in candidates:
        if cand.lower() in lower_map:
            return lower_map[cand.lower()]
    return None


def ensure_index_table(cur, rebuild: bool = False):
    if rebuild:
        cur.execute("DROP TABLE IF EXISTS table_index")

    # Create a minimal version if it does not exist yet.
    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS table_index (
            table_name VARCHAR(128) PRIMARY KEY,
            row_count BIGINT NULL
        ) ENGINE=InnoDB;
        """
    )

    # Make sure optional columns exist without assuming an exact prior schema.
    cols = get_columns(cur, "table_index")

    if "source_folder" not in cols:
        cur.execute("ALTER TABLE table_index ADD COLUMN source_folder VARCHAR(64) NULL")

    if "source_file" not in cols:
        cur.execute("ALTER TABLE table_index ADD COLUMN source_file VARCHAR(128) NULL")

    if "notes" not in cols:
        cur.execute("ALTER TABLE table_index ADD COLUMN notes TEXT NULL")

    if "created_at" not in cols:
        cur.execute("ALTER TABLE table_index ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP")


def upsert_table_index(cur, table_name: str, source_folder: str = None, source_file: str = None, notes: str = None):
    cur.execute(
        """
        INSERT INTO table_index (table_name, source_folder, source_file, row_count, notes)
        VALUES (%s, %s, %s, (SELECT COUNT(*) FROM `{}`), %s)
        ON DUPLICATE KEY UPDATE
            source_folder = VALUES(source_folder),
            source_file = VALUES(source_file),
            row_count = VALUES(row_count),
            notes = VALUES(notes);
        """.format(table_name),
        (table_name, source_folder, source_file, notes),
    )


# --------------------------- Staging / Cleaning ----------------------------

def ensure_players_view(cur, prefix: str, rebuild: bool = False, debug: bool = False):
    """Create a VIEW <prefix>_players_clean over <prefix>-allplayers_1899_2024.

    We standardize:
      - player_id
      - birth_country
      - birth_year
      - debut_year (derived from firstGame / debut / first_game)

    The view uses COALESCE over detected columns so it survives schema drift.
    """

    base = table_name(prefix, "allplayers_1899_2024")
    cols = get_columns(cur, base)

    player_id = pick_column(cols, ["playerID", "playerId", "retroID", "id", "player_id"])
    if not player_id or player_id not in cols:
        raise RuntimeError(f"No suitable player id column found in {base}")
    birth_country_col = pick_column(cols, ["birthCountry", "birth_country", "bthCountry", "country"])
    birth_year = pick_column(cols, ["birthYear", "birth_year", "bthYear"])  # nullable
    first_game = pick_column(cols, ["firstGame", "first_game", "debut", "debutDate", "first_game_date"])  # nullable

    if debug:
        print(f"[DEBUG] Detected columns in {base}")
        print("        player_id=", player_id)
        print("        birth_country=", birth_country_col)
        print("        birth_year=", birth_year)
        print("        first_game=", first_game)

    # Build birth_country expression (safe even if column missing)
    if birth_country_col and birth_country_col in cols:
        birth_country_expr = f"NULLIF(TRIM({birth_country_col}), '')"
    else:
        birth_country_expr = "NULL"

    # Build debut_year expression (safe even if column missing)
    debut_expr = "NULL"
    if first_game and first_game in cols:
        # firstGame is usually YYYY-MM-DD; take first 4 chars if numeric
        debut_expr = f"NULLIF(SUBSTRING({first_game},1,4), '')"

    birth_year_expr = "NULL"
    if birth_year and birth_year in cols:
        birth_year_expr = f"NULLIF({birth_year}, '')"

    view_name = f"{prefix}_players_clean"

    # Always ensure the name can be used for a view
    cur.execute(f"DROP TABLE IF EXISTS `{view_name}`")
    # (No need to drop existing view because CREATE OR REPLACE handles it)

    cur.execute(
        f"""
        CREATE OR REPLACE VIEW {view_name} AS
        SELECT
            {player_id} AS player_id,
            {birth_country_expr} AS birth_country,
            CAST({birth_year_expr} AS UNSIGNED) AS birth_year,
            CAST({debut_expr} AS UNSIGNED) AS debut_year
        FROM `{base}`;
        """
    )


# --------------------------- Summary Table DDL -----------------------------

def ensure_summary_tables_for_prefix(cur, prefix: str, rebuild: bool = False):
    # % tables
    if rebuild:
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_overall_pct")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_birth_year_pct")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_debut_year_pct")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_foreign_vs_us_batting_year")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_foreign_vs_us_pitching_year")
        # Clean up old decade tables if present
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_birth_decade_pct")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_debut_decade_pct")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_foreign_vs_us_batting_decade")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_foreign_vs_us_pitching_decade")
        # Drop new summary/career/top-player tables if present
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_player_career_batting")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_player_career_pitching")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_batting_career_summary")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_pitching_career_summary")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_batting_top_players")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_pitching_top_players")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_player_career_span")
        cur.execute(f"DROP TABLE IF EXISTS {prefix}_country_primary_position")

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_overall_pct (
            birth_country VARCHAR(64) PRIMARY KEY,
            player_count BIGINT NOT NULL,
            pct_of_total DOUBLE NOT NULL
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_birth_year_pct (
            birth_country VARCHAR(64) NOT NULL,
            year INT NOT NULL,
            player_count BIGINT NOT NULL,
            pct_of_year DOUBLE NOT NULL,
            PRIMARY KEY (birth_country, year)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_debut_year_pct (
            birth_country VARCHAR(64) NOT NULL,
            year INT NOT NULL,
            player_count BIGINT NOT NULL,
            pct_of_year DOUBLE NOT NULL,
            PRIMARY KEY (birth_country, year)
        ) ENGINE=InnoDB;
        """
    )

    # performance comparison tables
    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_foreign_vs_us_batting_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            games BIGINT NULL,
            ab BIGINT NULL,
            h BIGINT NULL,
            hr BIGINT NULL,
            bb BIGINT NULL,
            so BIGINT NULL,
            avg DOUBLE NULL,
            hr_rate DOUBLE NULL,
            bb_rate DOUBLE NULL,
            so_rate DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_foreign_vs_us_pitching_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            games BIGINT NULL,
            ip_outs BIGINT NULL,
            h_allowed BIGINT NULL,
            hr_allowed BIGINT NULL,
            bb_allowed BIGINT NULL,
            so BIGINT NULL,
            era DOUBLE NULL,
            hr9 DOUBLE NULL,
            bb9 DOUBLE NULL,
            so9 DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )

    # New summary/career/top-player tables
    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_birth_decade_pct (
            birth_country VARCHAR(64) NOT NULL,
            decade INT NOT NULL,
            player_count BIGINT NOT NULL,
            pct_of_decade DOUBLE NOT NULL,
            PRIMARY KEY (birth_country, decade)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_debut_decade_pct (
            birth_country VARCHAR(64) NOT NULL,
            decade INT NOT NULL,
            player_count BIGINT NOT NULL,
            pct_of_decade DOUBLE NOT NULL,
            PRIMARY KEY (birth_country, decade)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_player_career_batting (
            player_id VARCHAR(32) NOT NULL,
            birth_country VARCHAR(64) NULL,
            group_type ENUM('US','FOREIGN') NULL,
            seasons INT NOT NULL,
            games BIGINT NULL,
            ab BIGINT NULL,
            h BIGINT NULL,
            hr BIGINT NULL,
            bb BIGINT NULL,
            so BIGINT NULL,
            avg DOUBLE NULL,
            hr_rate DOUBLE NULL,
            bb_rate DOUBLE NULL,
            so_rate DOUBLE NULL,
            PRIMARY KEY (player_id)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_player_career_pitching (
            player_id VARCHAR(32) NOT NULL,
            birth_country VARCHAR(64) NULL,
            group_type ENUM('US','FOREIGN') NULL,
            seasons INT NOT NULL,
            games BIGINT NULL,
            ip_outs BIGINT NULL,
            h_allowed BIGINT NULL,
            hr_allowed BIGINT NULL,
            bb_allowed BIGINT NULL,
            so BIGINT NULL,
            era DOUBLE NULL,
            hr9 DOUBLE NULL,
            bb9 DOUBLE NULL,
            so9 DOUBLE NULL,
            PRIMARY KEY (player_id)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_batting_career_summary (
            birth_country VARCHAR(64) NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            total_games BIGINT NULL,
            total_ab BIGINT NULL,
            total_h BIGINT NULL,
            total_hr BIGINT NULL,
            total_bb BIGINT NULL,
            total_so BIGINT NULL,
            avg_career_seasons DOUBLE NULL,
            avg_career_avg DOUBLE NULL,
            avg_career_hr_rate DOUBLE NULL,
            PRIMARY KEY (birth_country, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_pitching_career_summary (
            birth_country VARCHAR(64) NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            total_games BIGINT NULL,
            total_ip_outs BIGINT NULL,
            total_h_allowed BIGINT NULL,
            total_hr_allowed BIGINT NULL,
            total_bb_allowed BIGINT NULL,
            total_so BIGINT NULL,
            avg_career_seasons DOUBLE NULL,
            avg_career_era DOUBLE NULL,
            avg_career_so9 DOUBLE NULL,
            PRIMARY KEY (birth_country, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_batting_top_players (
            birth_country VARCHAR(64) NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_id VARCHAR(32) NOT NULL,
            rank_in_country INT NOT NULL,
            seasons INT NOT NULL,
            career_games BIGINT NULL,
            career_ab BIGINT NULL,
            career_h BIGINT NULL,
            career_hr BIGINT NULL,
            career_bb BIGINT NULL,
            career_so BIGINT NULL,
            career_avg DOUBLE NULL,
            career_hr_rate DOUBLE NULL,
            PRIMARY KEY (birth_country, group_type, rank_in_country, player_id)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_pitching_top_players (
            birth_country VARCHAR(64) NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_id VARCHAR(32) NOT NULL,
            rank_in_country INT NOT NULL,
            seasons INT NOT NULL,
            career_games BIGINT NULL,
            career_ip_outs BIGINT NULL,
            career_h_allowed BIGINT NULL,
            career_hr_allowed BIGINT NULL,
            career_bb_allowed BIGINT NULL,
            career_so BIGINT NULL,
            career_era DOUBLE NULL,
            career_so9 DOUBLE NULL,
            PRIMARY KEY (birth_country, group_type, rank_in_country, player_id)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_player_career_span (
            player_id VARCHAR(32) NOT NULL,
            birth_country VARCHAR(64) NULL,
            group_type ENUM('US','FOREIGN') NULL,
            debut_year INT NULL,
            last_year INT NULL,
            seasons INT NULL,
            span_years INT NULL,
            PRIMARY KEY (player_id)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_country_primary_position (
            birth_country VARCHAR(64) NOT NULL,
            primary_pos VARCHAR(8) NOT NULL,
            player_count BIGINT NOT NULL,
            pct_of_country DOUBLE NULL,
            PRIMARY KEY (birth_country, primary_pos)
        ) ENGINE=InnoDB;
        """
    )

    # Biodata summary table
    ensure_bio_summary_table(cur, prefix, rebuild=rebuild)


# ------------------------ Country % Computations ---------------------------

def compute_overall_country_pct(cur, prefix: str, debug: bool = False):
    view = f"{prefix}_players_clean"
    target = f"{prefix}_country_overall_pct"
    if debug:
        print(f"[STEP] Computing overall country percentages from {view}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target} (birth_country, player_count, pct_of_total)
        SELECT
            birth_country,
            COUNT(DISTINCT player_id) AS player_count,
            COUNT(DISTINCT player_id) / total.total_players AS pct_of_total
        FROM {view}
        JOIN (SELECT COUNT(DISTINCT player_id) AS total_players FROM {view} WHERE birth_country IS NOT NULL) total
        WHERE birth_country IS NOT NULL
        GROUP BY birth_country, total.total_players;
        """
    )


def compute_birth_year_country_pct(cur, prefix: str, debug: bool = False):
    view = f"{prefix}_players_clean"
    target = f"{prefix}_country_birth_year_pct"
    if debug:
        print(f"[STEP] Computing birth-year country percentages for {prefix}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target} (birth_country, year, player_count, pct_of_year)
        WITH births AS (
            SELECT
                birth_country,
                birth_year AS year,
                player_id
            FROM {view}
            WHERE birth_country IS NOT NULL AND birth_year IS NOT NULL
        ), year_totals AS (
            SELECT year, COUNT(DISTINCT player_id) AS total_players
            FROM births
            GROUP BY year
        )
        SELECT
            b.birth_country,
            b.year,
            COUNT(DISTINCT b.player_id) AS player_count,
            COUNT(DISTINCT b.player_id) / yt.total_players AS pct_of_year
        FROM births b
        JOIN year_totals yt USING (year)
        GROUP BY b.birth_country, b.year, yt.total_players
        ORDER BY b.year, player_count DESC;
        """
    )


def compute_debut_year_country_pct(cur, prefix: str, debug: bool = False):
    view = f"{prefix}_players_clean"
    target = f"{prefix}_country_debut_year_pct"
    if debug:
        print(f"[STEP] Computing debut-year country percentages for {prefix}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target} (birth_country, year, player_count, pct_of_year)
        WITH debuts AS (
            SELECT
                birth_country,
                debut_year AS year,
                player_id
            FROM {view}
            WHERE birth_country IS NOT NULL AND debut_year IS NOT NULL
        ), year_totals AS (
            SELECT year, COUNT(DISTINCT player_id) AS total_players
            FROM debuts
            GROUP BY year
        )
        SELECT
            d.birth_country,
            d.year,
            COUNT(DISTINCT d.player_id) AS player_count,
            COUNT(DISTINCT d.player_id) / yt.total_players AS pct_of_year
        FROM debuts d
        JOIN year_totals yt USING (year)
        GROUP BY d.birth_country, d.year, yt.total_players
        ORDER BY d.year, player_count DESC;
        """
    )


def compute_birth_decade_country_pct(cur, prefix: str, debug: bool = False):
    view = f"{prefix}_players_clean"
    target = f"{prefix}_country_birth_decade_pct"
    if debug:
        print(f"[STEP] Computing birth-decade country percentages for {prefix}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target} (birth_country, decade, player_count, pct_of_decade)
        WITH births AS (
            SELECT
                birth_country,
                birth_year,
                player_id
            FROM {view}
            WHERE birth_country IS NOT NULL AND birth_year IS NOT NULL
        ), births_decade AS (
            SELECT
                birth_country,
                (birth_year DIV 10) * 10 AS decade,
                player_id
            FROM births
        ), decade_totals AS (
            SELECT
                decade,
                COUNT(DISTINCT player_id) AS total_players
            FROM births_decade
            GROUP BY decade
        )
        SELECT
            b.birth_country,
            b.decade,
            COUNT(DISTINCT b.player_id) AS player_count,
            COUNT(DISTINCT b.player_id) / dt.total_players AS pct_of_decade
        FROM births_decade b
        JOIN decade_totals dt USING (decade)
        GROUP BY b.birth_country, b.decade, dt.total_players
        ORDER BY b.decade, player_count DESC;
        """
    )


def compute_debut_decade_country_pct(cur, prefix: str, debug: bool = False):
    view = f"{prefix}_players_clean"
    target = f"{prefix}_country_debut_decade_pct"
    if debug:
        print(f"[STEP] Computing debut-decade country percentages for {prefix}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target} (birth_country, decade, player_count, pct_of_decade)
        WITH debuts AS (
            SELECT
                birth_country,
                debut_year,
                player_id
            FROM {view}
            WHERE birth_country IS NOT NULL AND debut_year IS NOT NULL
        ), debuts_decade AS (
            SELECT
                birth_country,
                (debut_year DIV 10) * 10 AS decade,
                player_id
            FROM debuts
        ), decade_totals AS (
            SELECT
                decade,
                COUNT(DISTINCT player_id) AS total_players
            FROM debuts_decade
            GROUP BY decade
        )
        SELECT
            d.birth_country,
            d.decade,
            COUNT(DISTINCT d.player_id) AS player_count,
            COUNT(DISTINCT d.player_id) / dt.total_players AS pct_of_decade
        FROM debuts_decade d
        JOIN decade_totals dt USING (decade)
        GROUP BY d.birth_country, d.decade, dt.total_players
        ORDER BY d.decade, player_count DESC;
        """
    )


# ------------------ Foreign vs US Performance Summaries --------------------

def _country_group_expr(country_col: str = "birth_country") -> str:
    # "USA" sometimes appears as "United States", "U.S.A.", etc. Normalize.
    return (
        "CASE "
        f"WHEN {country_col} IS NULL THEN NULL "
        f"WHEN UPPER({country_col}) IN ('UNITED STATES','USA','U.S.A.','US') THEN 'US' "
        "ELSE 'FOREIGN' END"
    )


def compute_foreign_vs_us_batting_year(cur, prefix: str, debug: bool = False):
    """Summarize batting from <prefix>-batting_1899_2024 season-by-season."""
    base_bat = table_name(prefix, "batting_1899_2024")
    if not table_exists(cur, base_bat):
        if debug:
            print(f"[INFO] No batting table {base_bat} for prefix={prefix}; skipping foreign_vs_us_batting_year")
        return
    cols = get_columns(cur, base_bat)

    # Detect common batting stat column names
    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {base_bat}; skipping foreign_vs_us_batting_year for {prefix}")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {base_bat}; skipping foreign_vs_us_batting_year for {prefix}")
        return
    g_col = pick_column(cols, ["G", "games", "g"]) or None
    ab_col = pick_column(cols, ["AB", "ab"]) or None
    h_col = pick_column(cols, ["H", "h"]) or None
    hr_col = pick_column(cols, ["HR", "hr"]) or None
    bb_col = pick_column(cols, ["BB", "bb"]) or None
    so_col = pick_column(cols, ["SO", "so", "K"]) or None

    if debug:
        print(f"[STEP] Computing foreign vs US batting by season for {prefix}")
        print("[DEBUG] Batting cols:", {"pid": pid, "year": year_col, "G": g_col, "AB": ab_col, "H": h_col, "HR": hr_col, "BB": bb_col, "SO": so_col})

    target = f"{prefix}_foreign_vs_us_batting_year"
    view = f"{prefix}_players_clean"
    cur.execute(f"TRUNCATE TABLE {target}")

    def safe(col: Optional[str], default: str = "0") -> str:
        return col if col else default

    insert_sql = f"""
        INSERT INTO {target}
        (year, group_type, player_count, games, ab, h, hr, bb, so, avg, hr_rate, bb_rate, so_rate)
        WITH joined AS (
            SELECT
                p.player_id,
                p.birth_country,
                b.{year_col} AS season_year,
                {safe(g_col)} AS G,
                {safe(ab_col)} AS AB,
                {safe(h_col)} AS H,
                {safe(hr_col)} AS HR,
                {safe(bb_col)} AS BB,
                {safe(so_col)} AS SO
            FROM `{base_bat}` b
            JOIN {view} p
              ON b.{pid} = p.player_id
            WHERE b.{year_col} IS NOT NULL
        ), year_joined AS (
            SELECT
                season_year AS year,
                {_country_group_expr('birth_country')} AS group_type,
                player_id,
                SUM(G) AS games,
                SUM(AB) AS ab,
                SUM(H) AS h,
                SUM(HR) AS hr,
                SUM(BB) AS bb,
                SUM(SO) AS so
            FROM joined
            WHERE {_country_group_expr('birth_country')} IS NOT NULL
            GROUP BY year, group_type, player_id
        )
        SELECT
            year,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            SUM(games) AS games,
            SUM(ab) AS ab,
            SUM(h) AS h,
            SUM(hr) AS hr,
            SUM(bb) AS bb,
            SUM(so) AS so,
            CASE WHEN SUM(ab) > 0 THEN SUM(h)/SUM(ab) ELSE NULL END AS avg,
            CASE WHEN SUM(ab) > 0 THEN SUM(hr)/SUM(ab) ELSE NULL END AS hr_rate,
            CASE WHEN SUM(ab) > 0 THEN SUM(bb)/SUM(ab) ELSE NULL END AS bb_rate,
            CASE WHEN SUM(ab) > 0 THEN SUM(so)/SUM(ab) ELSE NULL END AS so_rate
        FROM year_joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
    """

    cur.execute(insert_sql)


def compute_foreign_vs_us_pitching_year(cur, prefix: str, debug: bool = False):
    """Summarize pitching from <prefix>-pitching_1899_2024 season-by-season."""
    base_pit = table_name(prefix, "pitching_1899_2024")
    if not table_exists(cur, base_pit):
        if debug:
            print(f"[INFO] No pitching table {base_pit} for prefix={prefix}; skipping foreign_vs_us_pitching_year")
        return
    cols = get_columns(cur, base_pit)

    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {base_pit}; skipping foreign_vs_us_pitching_year for {prefix}")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {base_pit}; skipping foreign_vs_us_pitching_year for {prefix}")
        return

    g_col = pick_column(cols, ["G", "games", "g"]) or None
    ipouts_col = pick_column(cols, ["IPouts", "ipouts", "outs_pitched", "IP_OUTS"]) or None
    h_col = pick_column(cols, ["H", "h", "H_allowed"]) or None
    hr_col = pick_column(cols, ["HR", "hr", "HR_allowed"]) or None
    bb_col = pick_column(cols, ["BB", "bb"]) or None
    so_col = pick_column(cols, ["SO", "so", "K"]) or None
    er_col = pick_column(cols, ["ER", "er", "earnedRuns"]) or None

    if debug:
        print(f"[STEP] Computing foreign vs US pitching by season for {prefix}")
        print("[DEBUG] Pitching cols:", {"pid": pid, "year": year_col, "G": g_col, "IPouts": ipouts_col, "H": h_col, "HR": hr_col, "BB": bb_col, "SO": so_col, "ER": er_col})

    target = f"{prefix}_foreign_vs_us_pitching_year"
    view = f"{prefix}_players_clean"
    cur.execute(f"TRUNCATE TABLE {target}")

    def safe(col: Optional[str], default: str = "0") -> str:
        return col if col else default

    insert_sql = f"""
        INSERT INTO {target}
        (year, group_type, player_count, games, ip_outs, h_allowed, hr_allowed, bb_allowed, so, era, hr9, bb9, so9)
        WITH joined AS (
            SELECT
                p.player_id,
                p.birth_country,
                pit.{year_col} AS season_year,
                {safe(g_col)} AS G,
                {safe(ipouts_col)} AS IPouts,
                {safe(h_col)} AS H_allowed,
                {safe(hr_col)} AS HR_allowed,
                {safe(bb_col)} AS BB_allowed,
                {safe(so_col)} AS SO,
                {safe(er_col)} AS ER
            FROM `{base_pit}` pit
            JOIN {view} p
              ON pit.{pid} = p.player_id
            WHERE pit.{year_col} IS NOT NULL
        ), year_joined AS (
            SELECT
                season_year AS year,
                {_country_group_expr('birth_country')} AS group_type,
                player_id,
                SUM(G) AS games,
                SUM(IPouts) AS ip_outs,
                SUM(H_allowed) AS h_allowed,
                SUM(HR_allowed) AS hr_allowed,
                SUM(BB_allowed) AS bb_allowed,
                SUM(SO) AS so,
                SUM(ER) AS er
            FROM joined
            WHERE {_country_group_expr('birth_country')} IS NOT NULL
            GROUP BY year, group_type, player_id
        )
        SELECT
            year,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            SUM(games) AS games,
            SUM(ip_outs) AS ip_outs,
            SUM(h_allowed) AS h_allowed,
            SUM(hr_allowed) AS hr_allowed,
            SUM(bb_allowed) AS bb_allowed,
            SUM(so) AS so,
            CASE WHEN SUM(ip_outs) > 0 THEN (SUM(er) * 27.0) / SUM(ip_outs) ELSE NULL END AS era,
            CASE WHEN SUM(ip_outs) > 0 THEN (SUM(hr_allowed) * 27.0) / SUM(ip_outs) ELSE NULL END AS hr9,
            CASE WHEN SUM(ip_outs) > 0 THEN (SUM(bb_allowed) * 27.0) / SUM(ip_outs) ELSE NULL END AS bb9,
            CASE WHEN SUM(ip_outs) > 0 THEN (SUM(so) * 27.0) / SUM(ip_outs) ELSE NULL END AS so9
        FROM year_joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
    """

    cur.execute(insert_sql)


#
# ------------------- Foreign vs US Fielding Summary (optional) --------------------

def compute_foreign_vs_us_fielding_year(cur, prefix: str, debug: bool = False):
    """Summarize fielding from `<prefix>-fielding_1899_2024` season-by-season.

    Works with either Retrosheet-style or Lahman-style column names.
    Produces totals and simple rate stats.
    """
    base_fld = table_name(prefix, "fielding_1899_2024")
    if not table_exists(cur, base_fld):
        if debug:
            print(f"[INFO] No fielding table for prefix={prefix}; skipping foreign_vs_us_fielding_year")
        return

    cols = get_columns(cur, base_fld)

    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {base_fld}; skipping foreign_vs_us_fielding_year for {prefix}")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {base_fld}; skipping foreign_vs_us_fielding_year for {prefix}")
        return

    g_col = pick_column(cols, ["G", "games", "g"]) or None
    inn_col = pick_column(cols, ["InnOuts", "innouts", "innings_outs", "IPouts"]) or None
    po_col = pick_column(cols, ["PO", "po", "putouts"]) or None
    a_col  = pick_column(cols, ["A", "a", "assists"]) or None
    e_col  = pick_column(cols, ["E", "e", "errors"]) or None

    if debug:
        print(f"[STEP] Computing foreign vs US fielding by season for {prefix}")
        print("[DEBUG] Fielding cols:", {"pid": pid, "year": year_col, "G": g_col,
                                        "InnOuts": inn_col, "PO": po_col,
                                        "A": a_col, "E": e_col})

    # Ensure table exists
    cur.execute(
        f"""
        CREATE TABLE IF NOT EXISTS {prefix}_foreign_vs_us_fielding_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            games BIGINT NULL,
            inn_outs BIGINT NULL,
            putouts BIGINT NULL,
            assists BIGINT NULL,
            errors BIGINT NULL,
            fld_pct DOUBLE NULL,
            errors_per_game DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(f"TRUNCATE TABLE {prefix}_foreign_vs_us_fielding_year")

    def safe(col: Optional[str], default: str = "0") -> str:
        return col if col else default

    insert_sql = f"""
        INSERT INTO {prefix}_foreign_vs_us_fielding_year
        (year, group_type, player_count, games, inn_outs, putouts, assists, errors, fld_pct, errors_per_game)
        WITH joined AS (
            SELECT
                p.player_id,
                p.birth_country,
                f.{year_col} AS season_year,
                {safe(g_col)} AS G,
                {safe(inn_col)} AS InnOuts,
                {safe(po_col)} AS PO,
                {safe(a_col)}  AS A,
                {safe(e_col)}  AS E
            FROM `{base_fld}` f
            JOIN {prefix}_players_clean p
              ON f.{pid} = p.player_id
            WHERE f.{year_col} IS NOT NULL
        ), year_joined AS (
            SELECT
                season_year AS year,
                {_country_group_expr('birth_country')} AS group_type,
                player_id,
                SUM(G) AS games,
                SUM(InnOuts) AS inn_outs,
                SUM(PO) AS putouts,
                SUM(A) AS assists,
                SUM(E) AS errors
            FROM joined
            WHERE {_country_group_expr('birth_country')} IS NOT NULL
            GROUP BY year, group_type, player_id
        )
        SELECT
            year,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            SUM(games) AS games,
            SUM(inn_outs) AS inn_outs,
            SUM(putouts) AS putouts,
            SUM(assists) AS assists,
            SUM(errors) AS errors,
            CASE WHEN (SUM(putouts) + SUM(assists) + SUM(errors)) > 0
                 THEN (SUM(putouts) + SUM(assists)) / (SUM(putouts) + SUM(assists) + SUM(errors))
                 ELSE NULL END AS fld_pct,
            CASE WHEN SUM(games) > 0 THEN SUM(errors)/SUM(games) ELSE NULL END AS errors_per_game
        FROM year_joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
    """

    cur.execute(insert_sql)


def compute_player_career_batting(cur, prefix: str, debug: bool = False):
    """Compute career batting totals per player for this prefix."""
    base_bat = table_name(prefix, "batting_1899_2024")
    if not table_exists(cur, base_bat):
        if debug:
            print(f"[INFO] No batting table {base_bat} for prefix={prefix}; skipping player career batting for {prefix}")
        return
    cols = get_columns(cur, base_bat)

    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {base_bat}; skipping player career batting for {prefix}")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {base_bat}; skipping player career batting for {prefix}")
        return
    g_col = pick_column(cols, ["G", "games", "g"]) or None
    ab_col = pick_column(cols, ["AB", "ab"]) or None
    h_col = pick_column(cols, ["H", "h"]) or None
    hr_col = pick_column(cols, ["HR", "hr"]) or None
    bb_col = pick_column(cols, ["BB", "bb"]) or None
    so_col = pick_column(cols, ["SO", "so", "K"]) or None

    if debug:
        print(f"[STEP] Computing player career batting totals for {prefix}")
        print("[DEBUG] Career batting cols:", {"pid": pid, "year": year_col, "G": g_col, "AB": ab_col, "H": h_col, "HR": hr_col, "BB": bb_col, "SO": so_col})

    target = f"{prefix}_player_career_batting"
    view = f"{prefix}_players_clean"
    cur.execute(f"TRUNCATE TABLE {target}")

    def safe(col: Optional[str], default: str = "0") -> str:
        return col if col else default

    cur.execute(
        f"""
        INSERT INTO {target}
        (player_id, birth_country, group_type, seasons, games, ab, h, hr, bb, so,
         avg, hr_rate, bb_rate, so_rate)
        WITH joined AS (
            SELECT
                p.player_id,
                p.birth_country,
                {_country_group_expr('p.birth_country')} AS group_type,
                b.{year_col} AS season_year,
                {safe(g_col)} AS G,
                {safe(ab_col)} AS AB,
                {safe(h_col)} AS H,
                {safe(hr_col)} AS HR,
                {safe(bb_col)} AS BB,
                {safe(so_col)} AS SO
            FROM `{base_bat}` b
            JOIN {view} p
              ON b.{pid} = p.player_id
            WHERE b.{year_col} IS NOT NULL
        ), agg AS (
            SELECT
                player_id,
                birth_country,
                group_type,
                COUNT(DISTINCT season_year) AS seasons,
                SUM(G) AS games,
                SUM(AB) AS ab,
                SUM(H) AS h,
                SUM(HR) AS hr,
                SUM(BB) AS bb,
                SUM(SO) AS so
            FROM joined
            WHERE group_type IS NOT NULL
            GROUP BY player_id, birth_country, group_type
        )
        SELECT
            player_id,
            birth_country,
            group_type,
            seasons,
            games,
            ab,
            h,
            hr,
            bb,
            so,
            CASE WHEN ab > 0 THEN h / ab ELSE NULL END AS avg,
            CASE WHEN ab > 0 THEN hr / ab ELSE NULL END AS hr_rate,
            CASE WHEN ab > 0 THEN bb / ab ELSE NULL END AS bb_rate,
            CASE WHEN ab > 0 THEN so / ab ELSE NULL END AS so_rate
        FROM agg;
        """
    )


def compute_player_career_pitching(cur, prefix: str, debug: bool = False):
    """Compute career pitching totals per player for this prefix."""
    base_pit = table_name(prefix, "pitching_1899_2024")
    if not table_exists(cur, base_pit):
        if debug:
            print(f"[INFO] No pitching table {base_pit} for prefix={prefix}; skipping player career pitching for {prefix}")
        return
    cols = get_columns(cur, base_pit)

    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {base_pit}; skipping player career pitching for {prefix}")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {base_pit}; skipping player career pitching for {prefix}")
        return

    g_col = pick_column(cols, ["G", "games", "g"]) or None
    ipouts_col = pick_column(cols, ["IPouts", "ipouts", "outs_pitched", "IP_OUTS"]) or None
    h_col = pick_column(cols, ["H", "h", "H_allowed"]) or None
    hr_col = pick_column(cols, ["HR", "hr", "HR_allowed"]) or None
    bb_col = pick_column(cols, ["BB", "bb"]) or None
    so_col = pick_column(cols, ["SO", "so", "K"]) or None
    er_col = pick_column(cols, ["ER", "er", "earnedRuns"]) or None

    if debug:
        print(f"[STEP] Computing player career pitching totals for {prefix}")
        print("[DEBUG] Career pitching cols:", {"pid": pid, "year": year_col, "G": g_col, "IPouts": ipouts_col, "H": h_col, "HR": hr_col, "BB": bb_col, "SO": so_col, "ER": er_col})

    target = f"{prefix}_player_career_pitching"
    view = f"{prefix}_players_clean"
    cur.execute(f"TRUNCATE TABLE {target}")

    def safe(col: Optional[str], default: str = "0") -> str:
        return col if col else default

    cur.execute(
        f"""
        INSERT INTO {target}
        (player_id, birth_country, group_type, seasons, games, ip_outs,
         h_allowed, hr_allowed, bb_allowed, so,
         era, hr9, bb9, so9)
        WITH joined AS (
            SELECT
                p.player_id,
                p.birth_country,
                {_country_group_expr('p.birth_country')} AS group_type,
                pit.{year_col} AS season_year,
                {safe(g_col)} AS G,
                {safe(ipouts_col)} AS IPouts,
                {safe(h_col)} AS H_allowed,
                {safe(hr_col)} AS HR_allowed,
                {safe(bb_col)} AS BB_allowed,
                {safe(so_col)} AS SO,
                {safe(er_col)} AS ER
            FROM `{base_pit}` pit
            JOIN {view} p
              ON pit.{pid} = p.player_id
            WHERE pit.{year_col} IS NOT NULL
        ), agg AS (
            SELECT
                player_id,
                birth_country,
                group_type,
                COUNT(DISTINCT season_year) AS seasons,
                SUM(G) AS games,
                SUM(IPouts) AS ip_outs,
                SUM(H_allowed) AS h_allowed,
                SUM(HR_allowed) AS hr_allowed,
                SUM(BB_allowed) AS bb_allowed,
                SUM(SO) AS so,
                SUM(ER) AS er
            FROM joined
            WHERE group_type IS NOT NULL
            GROUP BY player_id, birth_country, group_type
        )
        SELECT
            player_id,
            birth_country,
            group_type,
            seasons,
            games,
            ip_outs,
            h_allowed,
            hr_allowed,
            bb_allowed,
            so,
            CASE WHEN ip_outs > 0 THEN (er * 27.0) / ip_outs ELSE NULL END AS era,
            CASE WHEN ip_outs > 0 THEN (hr_allowed * 27.0) / ip_outs ELSE NULL END AS hr9,
            CASE WHEN ip_outs > 0 THEN (bb_allowed * 27.0) / ip_outs ELSE NULL END AS bb9,
            CASE WHEN ip_outs > 0 THEN (so * 27.0) / ip_outs ELSE NULL END AS so9
        FROM agg;
        """
    )


def compute_country_batting_career_summary(cur, prefix: str, debug: bool = False):
    target = f"{prefix}_country_batting_career_summary"
    source = f"{prefix}_player_career_batting"
    if debug:
        print(f"[STEP] Computing country batting career summary for {prefix}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target}
        (birth_country, group_type, player_count,
         total_games, total_ab, total_h, total_hr, total_bb, total_so,
         avg_career_seasons, avg_career_avg, avg_career_hr_rate)
        SELECT
            birth_country,
            group_type,
            COUNT(*) AS player_count,
            SUM(games) AS total_games,
            SUM(ab) AS total_ab,
            SUM(h) AS total_h,
            SUM(hr) AS total_hr,
            SUM(bb) AS total_bb,
            SUM(so) AS total_so,
            AVG(seasons) AS avg_career_seasons,
            AVG(avg) AS avg_career_avg,
            AVG(hr_rate) AS avg_career_hr_rate
        FROM {source}
        WHERE birth_country IS NOT NULL AND group_type IS NOT NULL
        GROUP BY birth_country, group_type;
        """
    )


def compute_country_pitching_career_summary(cur, prefix: str, debug: bool = False):
    target = f"{prefix}_country_pitching_career_summary"
    source = f"{prefix}_player_career_pitching"
    if debug:
        print(f"[STEP] Computing country pitching career summary for {prefix}")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target}
        (birth_country, group_type, player_count,
         total_games, total_ip_outs, total_h_allowed, total_hr_allowed, total_bb_allowed, total_so,
         avg_career_seasons, avg_career_era, avg_career_so9)
        SELECT
            birth_country,
            group_type,
            COUNT(*) AS player_count,
            SUM(games) AS total_games,
            SUM(ip_outs) AS total_ip_outs,
            SUM(h_allowed) AS total_h_allowed,
            SUM(hr_allowed) AS total_hr_allowed,
            SUM(bb_allowed) AS total_bb_allowed,
            SUM(so) AS total_so,
            AVG(seasons) AS avg_career_seasons,
            AVG(era) AS avg_career_era,
            AVG(so9) AS avg_career_so9
        FROM {source}
        WHERE birth_country IS NOT NULL AND group_type IS NOT NULL
        GROUP BY birth_country, group_type;
        """
    )


def compute_country_batting_top_players(cur, prefix: str, debug: bool = False, top_n: int = 10):
    target = f"{prefix}_country_batting_top_players"
    source = f"{prefix}_player_career_batting"
    if debug:
        print(f"[STEP] Computing top batting players per country for {prefix} (top {top_n})")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target}
        (birth_country, group_type, player_id, rank_in_country,
         seasons, career_games, career_ab, career_h, career_hr, career_bb, career_so,
         career_avg, career_hr_rate)
        WITH ranked AS (
            SELECT
                birth_country,
                group_type,
                player_id,
                seasons,
                games,
                ab,
                h,
                hr,
                bb,
                so,
                avg,
                hr_rate,
                ROW_NUMBER() OVER (
                    PARTITION BY birth_country, group_type
                    ORDER BY hr DESC, ab DESC
                ) AS rk
            FROM {source}
            WHERE birth_country IS NOT NULL AND group_type IS NOT NULL
        )
        SELECT
            birth_country,
            group_type,
            player_id,
            rk AS rank_in_country,
            seasons,
            games AS career_games,
            ab AS career_ab,
            h AS career_h,
            hr AS career_hr,
            bb AS career_bb,
            so AS career_so,
            avg AS career_avg,
            hr_rate AS career_hr_rate
        FROM ranked
        WHERE rk <= {top_n};
        """
    )


def compute_country_pitching_top_players(cur, prefix: str, debug: bool = False, top_n: int = 10):
    target = f"{prefix}_country_pitching_top_players"
    source = f"{prefix}_player_career_pitching"
    if debug:
        print(f"[STEP] Computing top pitching players per country for {prefix} (top {top_n})")

    cur.execute(f"TRUNCATE TABLE {target}")
    cur.execute(
        f"""
        INSERT INTO {target}
        (birth_country, group_type, player_id, rank_in_country,
         seasons, career_games, career_ip_outs, career_h_allowed,
         career_hr_allowed, career_bb_allowed, career_so,
         career_era, career_so9)
        WITH ranked AS (
            SELECT
                birth_country,
                group_type,
                player_id,
                seasons,
                games,
                ip_outs,
                h_allowed,
                hr_allowed,
                bb_allowed,
                so,
                era,
                so9,
                ROW_NUMBER() OVER (
                    PARTITION BY birth_country, group_type
                    ORDER BY so DESC, games DESC
                ) AS rk
            FROM {source}
            WHERE birth_country IS NOT NULL AND group_type IS NOT NULL
        )
        SELECT
            birth_country,
            group_type,
            player_id,
            rk AS rank_in_country,
            seasons,
            games AS career_games,
            ip_outs AS career_ip_outs,
            h_allowed AS career_h_allowed,
            hr_allowed AS career_hr_allowed,
            bb_allowed AS career_bb_allowed,
            so AS career_so,
            era AS career_era,
            so9 AS career_so9
        FROM ranked
        WHERE rk <= {top_n};
        """
    )


def compute_player_career_span(cur, prefix: str, debug: bool = False):
    """Compute simple career span (debut to last appearance year) per player."""
    base_bat = table_name(prefix, "batting_1899_2024")
    base_pit = table_name(prefix, "pitching_1899_2024")

    view = f"{prefix}_players_clean"
    target = f"{prefix}_player_career_span"

    if debug:
        print(f"[STEP] Computing player career span for {prefix}")

    cols_bat = get_columns(cur, base_bat) if table_exists(cur, base_bat) else []
    cols_pit = get_columns(cur, base_pit) if table_exists(cur, base_pit) else []

    year_bat = pick_column(cols_bat, ["yearID", "year", "season", "yr"]) if cols_bat else None
    year_pit = pick_column(cols_pit, ["yearID", "year", "season", "yr"]) if cols_pit else None
    pid_bat = pick_column(cols_bat, ["playerID", "playerId", "retroID", "id"]) if cols_bat else None
    pid_pit = pick_column(cols_pit, ["playerID", "playerId", "retroID", "id"]) if cols_pit else None

    cur.execute(f"TRUNCATE TABLE {target}")

    parts = []
    if cols_bat and year_bat and pid_bat:
        parts.append(f"SELECT {pid_bat} AS player_id, {year_bat} AS year FROM `{base_bat}` WHERE {year_bat} IS NOT NULL")
    if cols_pit and year_pit and pid_pit:
        parts.append(f"SELECT {pid_pit} AS player_id, {year_pit} AS year FROM `{base_pit}` WHERE {year_pit} IS NOT NULL")

    if not parts:
        if debug:
            print(f"[WARN] No batting/pitching tables found for {prefix}; skipping career span computation")
        return

    union_sql = "\nUNION ALL\n".join(parts)

    cur.execute(
        f"""
        INSERT INTO {target}
        (player_id, birth_country, group_type, debut_year, last_year, seasons, span_years)
        WITH years AS (
            {union_sql}
        ), per_player AS (
            SELECT
                player_id,
                MIN(year) AS first_year_data,
                MAX(year) AS last_year_data
            FROM years
            GROUP BY player_id
        ), joined AS (
            SELECT
                p.player_id,
                p.birth_country,
                {_country_group_expr('p.birth_country')} AS group_type,
                p.debut_year,
                per.last_year_data
            FROM {view} p
            LEFT JOIN per_player per
              ON p.player_id = per.player_id
        )
        SELECT
            player_id,
            birth_country,
            group_type,
            debut_year,
            last_year_data AS last_year,
            CASE
                WHEN debut_year IS NOT NULL AND last_year_data IS NOT NULL
                THEN (last_year_data - debut_year) + 1
                ELSE NULL
            END AS seasons,
            CASE
                WHEN debut_year IS NOT NULL AND last_year_data IS NOT NULL
                THEN (last_year_data - debut_year)
                ELSE NULL
            END AS span_years
        FROM joined
        WHERE group_type IS NOT NULL AND birth_country IS NOT NULL;
        """
    )


def compute_country_primary_position(cur, prefix: str, debug: bool = False):
    """Compute primary position distribution per country using fielding data."""
    base_fld = table_name(prefix, "fielding_1899_2024")
    if not table_exists(cur, base_fld):
        if debug:
            print(f"[INFO] No fielding table for prefix={prefix}; skipping primary position computation")
        return

    cols = get_columns(cur, base_fld)

    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {base_fld}; skipping primary position computation for {prefix}")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {base_fld}; skipping primary position computation for {prefix}")
        return
    g_col = pick_column(cols, ["G", "games", "g"]) or None
    pos_col = pick_column(cols, ["POS", "pos", "position"])

    if not pos_col:
        if debug:
            print(f"[INFO] No position column in {base_fld}; skipping primary position computation")
        return

    if debug:
        print(f"[STEP] Computing primary position per country for {prefix}")
        print("[DEBUG] Fielding position cols:", {"pid": pid, "year": year_col, "G": g_col, "POS": pos_col})

    target = f"{prefix}_country_primary_position"
    view = f"{prefix}_players_clean"
    cur.execute(f"TRUNCATE TABLE {target}")

    def safe(col: Optional[str], default: str = "0") -> str:
        return col if col else default

    cur.execute(
        f"""
        INSERT INTO {target}
        (birth_country, primary_pos, player_count, pct_of_country)
        WITH raw AS (
            SELECT
                p.player_id,
                p.birth_country,
                {_country_group_expr('p.birth_country')} AS group_type,
                f.{pos_col} AS pos,
                {safe(g_col)} AS G
            FROM `{base_fld}` f
            JOIN {view} p
              ON f.{pid} = p.player_id
            WHERE f.{year_col} IS NOT NULL AND f.{pos_col} IS NOT NULL
        ), per_player_pos AS (
            SELECT
                player_id,
                birth_country,
                group_type,
                pos,
                SUM(G) AS games_at_pos,
                ROW_NUMBER() OVER (
                    PARTITION BY player_id
                    ORDER BY SUM(G) DESC
                ) AS rk
            FROM raw
            WHERE group_type IS NOT NULL AND birth_country IS NOT NULL
            GROUP BY player_id, birth_country, group_type, pos
        ), primary_pos AS (
            SELECT
                player_id,
                birth_country,
                group_type,
                pos AS primary_pos
            FROM per_player_pos
            WHERE rk = 1
        ), country_counts AS (
            SELECT
                birth_country,
                primary_pos,
                COUNT(DISTINCT player_id) AS player_count
            FROM primary_pos
            GROUP BY birth_country, primary_pos
        ), country_totals AS (
            SELECT
                birth_country,
                SUM(player_count) AS total_players
            FROM country_counts
            GROUP BY birth_country
        )
        SELECT
            c.birth_country,
            c.primary_pos,
            c.player_count,
            c.player_count / t.total_players AS pct_of_country
        FROM country_counts c
        JOIN country_totals t USING (birth_country);
        """
    )



# -------- Optional Global Staging (Lahman / BRef / Retrosheet stg_*) Analyses --------

def ensure_global_summary_tables(cur, rebuild: bool = False):
    if rebuild:
        cur.execute("DROP TABLE IF EXISTS global_foreign_vs_us_war_year")
        cur.execute("DROP TABLE IF EXISTS global_foreign_vs_us_awards_year")
        cur.execute("DROP TABLE IF EXISTS global_foreign_vs_us_allstar_year")
        cur.execute("DROP TABLE IF EXISTS global_foreign_vs_us_salaries_year")

    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS global_foreign_vs_us_war_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            war_total DOUBLE NULL,
            war_per_player DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS global_foreign_vs_us_awards_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            award_count BIGINT NOT NULL,
            player_count BIGINT NOT NULL,
            awards_per_player DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS global_foreign_vs_us_allstar_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            selection_count BIGINT NOT NULL,
            player_count BIGINT NOT NULL,
            selections_per_player DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )

    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS global_foreign_vs_us_salaries_year (
            year INT NOT NULL,
            group_type ENUM('US','FOREIGN') NOT NULL,
            player_count BIGINT NOT NULL,
            salary_total BIGINT NULL,
            salary_avg DOUBLE NULL,
            PRIMARY KEY (year, group_type)
        ) ENGINE=InnoDB;
        """
    )


def compute_global_war_year(cur, players_view: str = "main_players_clean", debug: bool = False):
    """Compute foreign vs US WAR totals by season using BRef daily WAR staging if present."""
    bat_tbl = "stg_bref_war_daily_bat"
    pit_tbl = "stg_bref_war_daily_pitch"
    if not table_exists(cur, bat_tbl) and not table_exists(cur, pit_tbl):
        if debug:
            print("[INFO] No BRef WAR staging tables found; skipping WAR summaries")
        return

    cur.execute("TRUNCATE TABLE global_foreign_vs_us_war_year")

    selects = []
    for t in [bat_tbl, pit_tbl]:
        if not table_exists(cur, t):
            continue
        cols = get_columns(cur, t)
        pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
        year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
        war_col = pick_column(cols, ["WAR", "war", "war_total", "bWAR", "pitchWAR"])
        if not pid or pid not in cols:
            if debug:
                print(f"[WARN] No player id column detected in {t}; skipping this WAR table")
            continue
        if not year_col or year_col not in cols:
            if debug:
                print(f"[WARN] No year column detected in {t}; skipping this WAR table")
            continue
        if not war_col or war_col not in cols:
            if debug:
                print(f"[WARN] No WAR column detected in {t}; skipping this WAR table")
            continue
        selects.append(
            f"SELECT {pid} AS player_id, {year_col} AS year, CAST(NULLIF({war_col},'') AS DOUBLE) AS war FROM `{t}`"
        )

    if not selects:
        return

    union_sql = "\nUNION ALL\n".join(selects)

    cur.execute(
        f"""
        INSERT INTO global_foreign_vs_us_war_year (year, group_type, player_count, war_total, war_per_player)
        WITH war_rows AS (
            {union_sql}
        ), joined AS (
            SELECT
                w.year,
                {_country_group_expr('p.birth_country')} AS group_type,
                w.player_id,
                w.war
            FROM war_rows w
            JOIN {players_view} p ON p.player_id = w.player_id
            WHERE w.year IS NOT NULL AND {_country_group_expr('p.birth_country')} IS NOT NULL
        )
        SELECT
            year,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            SUM(war) AS war_total,
            CASE WHEN COUNT(DISTINCT player_id) > 0 THEN SUM(war)/COUNT(DISTINCT player_id) ELSE NULL END AS war_per_player
        FROM joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
        """
    )


def compute_global_awards_year(cur, players_view: str = "main_players_clean", debug: bool = False):
    """Compute foreign vs US awards by season using Lahman awards_player/share staging if present."""
    awards_tbl = pick_first_table(cur, ["stg_lahman_awards_players%", "stg_lahman_%awardsplayers%", "stg_lahman_lahman_1871_2024_csv_awardsplayers_csv"]) or "stg_lahman_awards_players"
    if not table_exists(cur, awards_tbl):
        if debug:
            print("[INFO] No Lahman awards players table found; skipping awards summaries")
        return

    cols = get_columns(cur, awards_tbl)
    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {awards_tbl}; skipping awards summaries")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {awards_tbl}; skipping awards summaries")
        return

    cur.execute("TRUNCATE TABLE global_foreign_vs_us_awards_year")

    cur.execute(
        f"""
        INSERT INTO global_foreign_vs_us_awards_year (year, group_type, award_count, player_count, awards_per_player)
        WITH joined AS (
            SELECT
                a.{year_col} AS year,
                {_country_group_expr('p.birth_country')} AS group_type,
                a.{pid} AS player_id
            FROM `{awards_tbl}` a
            JOIN {players_view} p ON p.player_id = a.{pid}
            WHERE a.{year_col} IS NOT NULL AND {_country_group_expr('p.birth_country')} IS NOT NULL
        )
        SELECT
            year,
            group_type,
            COUNT(*) AS award_count,
            COUNT(DISTINCT player_id) AS player_count,
            CASE WHEN COUNT(DISTINCT player_id) > 0 THEN COUNT(*)/COUNT(DISTINCT player_id) ELSE NULL END AS awards_per_player
        FROM joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
        """
    )


def compute_global_allstar_year(cur, players_view: str = "main_players_clean", debug: bool = False):
    """Compute foreign vs US all-star selections by season using Lahman allstarfull staging if present."""
    allstar_tbl = pick_first_table(cur, ["stg_lahman_allstarfull%", "stg_lahman_%allstarfull%", "stg_lahman_lahman_1871_2024_csv_allstarfull_csv"]) or "stg_lahman_allstarfull"
    if not table_exists(cur, allstar_tbl):
        if debug:
            print("[INFO] No Lahman allstarfull table found; skipping all-star summaries")
        return

    cols = get_columns(cur, allstar_tbl)
    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {allstar_tbl}; skipping all-star summaries")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {allstar_tbl}; skipping all-star summaries")
        return

    cur.execute("TRUNCATE TABLE global_foreign_vs_us_allstar_year")

    cur.execute(
        f"""
        INSERT INTO global_foreign_vs_us_allstar_year (year, group_type, selection_count, player_count, selections_per_player)
        WITH joined AS (
            SELECT
                a.{year_col} AS year,
                {_country_group_expr('p.birth_country')} AS group_type,
                a.{pid} AS player_id
            FROM `{allstar_tbl}` a
            JOIN {players_view} p ON p.player_id = a.{pid}
            WHERE a.{year_col} IS NOT NULL AND {_country_group_expr('p.birth_country')} IS NOT NULL
        )
        SELECT
            year,
            group_type,
            COUNT(*) AS selection_count,
            COUNT(DISTINCT player_id) AS player_count,
            CASE WHEN COUNT(DISTINCT player_id) > 0 THEN COUNT(*)/COUNT(DISTINCT player_id) ELSE NULL END AS selections_per_player
        FROM joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
        """
    )


def compute_global_salaries_year(cur, players_view: str = "main_players_clean", debug: bool = False):
    """Compute foreign vs US salary totals/averages by season if Lahman salaries staging exists."""
    sal_tbl = pick_first_table(cur, ["stg_lahman_salaries%", "stg_lahman_%salaries%", "stg_lahman_lahman_1871_2024_csv_salaries_csv"]) or "stg_lahman_salaries"
    if not table_exists(cur, sal_tbl):
        if debug:
            print("[INFO] No Lahman salaries table found; skipping salary summaries")
        return

    cols = get_columns(cur, sal_tbl)
    pid = pick_column(cols, ["playerID", "playerId", "retroID", "id"])
    year_col = pick_column(cols, ["yearID", "year", "season", "yr"])
    sal_col = pick_column(cols, ["salary", "Salary", "sal"])
    if not pid or pid not in cols:
        if debug:
            print(f"[WARN] No player id column detected in {sal_tbl}; skipping salary summaries")
        return
    if not year_col or year_col not in cols:
        if debug:
            print(f"[WARN] No year column detected in {sal_tbl}; skipping salary summaries")
        return
    if not sal_col or sal_col not in cols:
        if debug:
            print(f"[WARN] No salary column detected in {sal_tbl}; skipping salary summaries")
        return

    cur.execute("TRUNCATE TABLE global_foreign_vs_us_salaries_year")

    cur.execute(
        f"""
        INSERT INTO global_foreign_vs_us_salaries_year (year, group_type, player_count, salary_total, salary_avg)
        WITH joined AS (
            SELECT
                s.{year_col} AS year,
                {_country_group_expr('p.birth_country')} AS group_type,
                s.{pid} AS player_id,
                CAST(NULLIF(s.{sal_col},'') AS SIGNED) AS salary
            FROM `{sal_tbl}` s
            JOIN {players_view} p ON p.player_id = s.{pid}
            WHERE s.{year_col} IS NOT NULL AND {_country_group_expr('p.birth_country')} IS NOT NULL
        )
        SELECT
            year,
            group_type,
            COUNT(DISTINCT player_id) AS player_count,
            SUM(salary) AS salary_total,
            AVG(salary) AS salary_avg
        FROM joined
        GROUP BY year, group_type
        ORDER BY year, group_type;
        """
    )


def run_global_analyses(cur, rebuild: bool = False, debug: bool = False):
    """Run optional analyses over global stg_* tables if present."""
    ensure_global_summary_tables(cur, rebuild=rebuild)
    # Use main_players_clean as the canonical players+country map.
    players_view = "main_players_clean"
    if not table_exists(cur, players_view):
        if debug:
            print("[WARN] main_players_clean does not exist yet; skipping stg_* analyses")
        return

    compute_global_war_year(cur, players_view=players_view, debug=debug)
    compute_global_awards_year(cur, players_view=players_view, debug=debug)
    compute_global_allstar_year(cur, players_view=players_view, debug=debug)
    compute_global_salaries_year(cur, players_view=players_view, debug=debug)

# --------------------------------- Main ------------------------------------

def main() -> int:
    args = parse_args()
    conn = connect_db(args)

    prefixes = [p.strip() for p in args.prefixes.split(",") if p.strip()]

    try:
        with conn.cursor() as cur:
            # Make sure the lightweight table index exists and has all optional columns.
            ensure_index_table(cur, rebuild=args.rebuild)

            for prefix in prefixes:
                if args.debug:
                    print(f"[STEP] Running country/foreign-born analysis for prefix={prefix}")

                # 1) Core players view (prefix-allplayers_1899_2024)
                ensure_players_view(cur, prefix, rebuild=args.rebuild, debug=args.debug)

                # 2) Summary tables for this prefix (country %, foreign-vs-US by year, careers, etc.)
                ensure_summary_tables_for_prefix(cur, prefix, rebuild=args.rebuild)

                # 3) Country distribution tables
                compute_overall_country_pct(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_overall_pct",
                    source_folder="derived",
                    notes=f"Overall country distribution from {prefix}-allplayers_1899_2024",
                )

                compute_birth_year_country_pct(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_birth_year_pct",
                    source_folder="derived",
                    notes="Birth-year country distribution",
                )

                compute_debut_year_country_pct(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_debut_year_pct",
                    source_folder="derived",
                    notes="Debut-year country distribution using firstGame/debut",
                )

                compute_birth_decade_country_pct(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_birth_decade_pct",
                    source_folder="derived",
                    notes="Birth-decade country distribution",
                )

                compute_debut_decade_country_pct(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_debut_decade_pct",
                    source_folder="derived",
                    notes="Debut-decade country distribution",
                )

                # 4) Foreign vs US by season (batting, pitching, fielding)
                compute_foreign_vs_us_batting_year(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_foreign_vs_us_batting_year",
                    source_folder="derived",
                    notes="Batting foreign vs US by season",
                )

                compute_foreign_vs_us_pitching_year(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_foreign_vs_us_pitching_year",
                    source_folder="derived",
                    notes="Pitching foreign vs US by season",
                )

                # Optional: fielding summary only if the fielding table exists
                compute_foreign_vs_us_fielding_year(cur, prefix, debug=args.debug)
                if table_exists(cur, f"{prefix}_foreign_vs_us_fielding_year"):
                    upsert_table_index(
                        cur,
                        f"{prefix}_foreign_vs_us_fielding_year",
                        source_folder="derived",
                        notes="Fielding foreign vs US by season",
                    )

                # 5) Career / top player / span / primary-position summaries
                compute_player_career_batting(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_player_career_batting",
                    source_folder="derived",
                    notes="Career batting totals per player",
                )

                compute_player_career_pitching(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_player_career_pitching",
                    source_folder="derived",
                    notes="Career pitching totals per player",
                )

                compute_country_batting_career_summary(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_batting_career_summary",
                    source_folder="derived",
                    notes="Country-level batting career summary",
                )

                compute_country_pitching_career_summary(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_pitching_career_summary",
                    source_folder="derived",
                    notes="Country-level pitching career summary",
                )

                compute_country_batting_top_players(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_batting_top_players",
                    source_folder="derived",
                    notes="Top batting players per country",
                )

                compute_country_pitching_top_players(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_pitching_top_players",
                    source_folder="derived",
                    notes="Top pitching players per country",
                )

                compute_player_career_span(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_player_career_span",
                    source_folder="derived",
                    notes="Career span (debut to last year) per player",
                )

                compute_country_primary_position(cur, prefix, debug=args.debug)
                upsert_table_index(
                    cur,
                    f"{prefix}_country_primary_position",
                    source_folder="derived",
                    notes="Primary position distribution per country",
                )

                # 6) Biodata (height/weight/bats/throws) summaries if we can build a bio view
                bio_view = ensure_bio_view(cur, prefix, rebuild=args.rebuild, debug=args.debug)
                if bio_view is not None:
                    compute_foreign_vs_us_bio_overall(cur, prefix, bio_view=bio_view, debug=args.debug)
                    upsert_table_index(
                        cur,
                        f"{prefix}_foreign_vs_us_bio_overall",
                        source_folder="derived",
                        notes="Foreign vs US biodata summary (height/weight/bats/throws)",
                    )

            # 7) Optional global stg_* analyses (WAR, awards, all-star, salaries)
            #    Uses main_players_clean as the canonical player-country map.
            run_global_analyses(cur, rebuild=args.rebuild, debug=args.debug)

            # 8) Optional regular vs postseason comparison tables, if those prefixes were requested
            if "regular" in prefixes and "postseason" in prefixes:
                ensure_regular_postseason_comparison_tables(cur, rebuild=args.rebuild, debug=args.debug)
                if table_exists(cur, "regular_vs_postseason_foreign_vs_us_batting_year"):
                    upsert_table_index(
                        cur,
                        "regular_vs_postseason_foreign_vs_us_batting_year",
                        source_folder="derived",
                        notes="Regular vs postseason foreign vs US batting by season",
                    )
                if table_exists(cur, "regular_vs_postseason_foreign_vs_us_pitching_year"):
                    upsert_table_index(
                        cur,
                        "regular_vs_postseason_foreign_vs_us_pitching_year",
                        source_folder="derived",
                        notes="Regular vs postseason foreign vs US pitching by season",
                    )

            conn.commit()
    except Exception as exc:  # noqa: BLE001
        conn.rollback()
        print(f"[ERROR] country-main-analysis failed: {exc}", file=sys.stderr)
        if args.debug:
            import traceback

            traceback.print_exc()
        return 1
    finally:
        conn.close()

    return 0


if __name__ == "__main__":
    raise SystemExit(main())