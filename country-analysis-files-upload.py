import os
import sys
import csv
import argparse
from pathlib import Path
from typing import List, Tuple

import pymysql

# ---------------------- CONFIG ----------------------
# Default DB connection values – override with CLI flags if needed
DEFAULT_DB_HOST = "localhost"
DEFAULT_DB_PORT = 3306
DEFAULT_DB_USER = "root"
DEFAULT_DB_PASSWORD = "Ricky072701"
DEFAULT_DB_NAME = "mlb_impact"

# Folder (relative to project root) that holds the main CSV files
MAINFILES_DIR = Path(__file__).resolve().parent / "mainfiles"

# Explicit list of CSVs we care about (so we control table names)
TARGET_CSV_FILES = [
    "allplayers.csv",
    "batting.csv",
    "discreps.csv",
    "ejections.csv",
    "fielding.csv",
    "gameinfo.csv",
    "pitching.csv",
    "plays.csv",
    "teamstats.csv",
]

SIMPLE_TABLE_NAMES = {
    "allplayers.csv": "allplayers_1899_2024",
    "batting.csv": "batting_1899_2024",
    "discreps.csv": "discreps_1899_2024",
    "ejections.csv": "ejections_1899_2024",
    "fielding.csv": "fielding_1899_2024",
    "gameinfo.csv": "gameinfo_1899_2024",
    "pitching.csv": "pitching_1899_2024",
    "plays.csv": "plays_1899_2024",
    "teamstats.csv": "teamstats_1899_2024",
}

MASTER_BASE_FILES = {
    "allplayers",
    "batting",
    "discreps",
    "ejections",
    "fielding",
    "gameinfo",
    "pitching",
    "plays",
    "teamstats",
}

ALLOWED_FILE_SUFFIXES = {".csv", ".ros"}


# ---------------------- HELPERS ----------------------

def debug(msg: str) -> None:
    print(f"[DEBUG] {msg}")


def connect_db(args) -> pymysql.connections.Connection:
    """Open a MySQL connection using CLI args or defaults."""
    debug(
        f"Opening MySQL connection to {args.host}:{args.port} as {args.user}, db={args.database}"
    )
    conn = pymysql.connect(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        autocommit=False,
    )
    return conn


def normalize_column_name(raw_name: str) -> str:
    """Turn a CSV header into a safe MySQL column name.

    - lowercases
    - replaces non-alphanumeric characters with underscore
    - prefixes with c_ if it starts with a digit
    """
    name = raw_name.strip().lower()
    cleaned_chars = []
    for ch in name:
        if ch.isalnum():
            cleaned_chars.append(ch)
        else:
            cleaned_chars.append("_")
    cleaned = "".join(cleaned_chars).strip("_") or "col"
    if cleaned[0].isdigit():
        cleaned = "c_" + cleaned
    return cleaned


def build_table_schema(header: List[str]) -> List[Tuple[str, str]]:
    """Given CSV headers, build a list of (column_name, sql_type).

    By default we use VARCHAR(255), but if the file has many columns,
    using VARCHAR(255) for all of them can exceed MySQL's maximum row
    size (~65KB for InnoDB with utf8mb4). To avoid
    "Row size too large" errors (errno 1118), we switch to TEXT for
    very wide tables so that MySQL stores the data off-page.

    This keeps the loader generic and safe for wide Retrosheet CSVs
    like plays.csv.
    """
    seen = set()
    schema: List[Tuple[str, str]] = []

    num_cols = len(header)
    # Rough check: if we used VARCHAR(255) with utf8mb4 (4 bytes/char),
    # approximate row size would be num_cols * 255 * 4 bytes.
    # If this exceeds ~60k, we switch to TEXT for all columns to
    # avoid InnoDB's 65,535 byte limit on row size.
    approx_row_size = num_cols * 255 * 4
    use_text = approx_row_size > 60000

    for raw in header:
        col = normalize_column_name(raw)
        # Ensure uniqueness if there are duplicate names
        base = col
        idx = 1
        while col in seen:
            col = f"{base}_{idx}"
            idx += 1
        seen.add(col)

        col_type = "TEXT" if use_text else "VARCHAR(255)"
        schema.append((col, col_type))

    return schema


def iter_data_files(directory: Path):
    """Yield data files (CSV/ROS) in a directory, sorted by name."""
    return sorted(
        p
        for p in directory.iterdir()
        if p.is_file() and p.suffix.lower() in ALLOWED_FILE_SUFFIXES
    )


def ensure_table_index(cur) -> None:
    """
    Ensure we have an index table that records every table we (re)create
    from CSVs, so later analyses / scripts can easily see what exists.
    """
    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS `table_index` (
            table_name   VARCHAR(128) PRIMARY KEY,
            source_csv   VARCHAR(512),
            row_count    INT,
            last_loaded  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                         ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """
    )


def update_table_index(cur, table_name: str, csv_path: Path, row_count: int) -> None:
    """
    Upsert a record into table_index for the given table.
    """
    cur.execute(
        """
        REPLACE INTO `table_index` (table_name, source_csv, row_count)
        VALUES (%s, %s, %s)
        """,
        (table_name, str(csv_path), int(row_count)),
    )


def create_table_from_schema(cur, table_name: str, schema: List[Tuple[str, str]]) -> None:
    """Drop existing table and create a new one with the given schema."""
    debug(f"Dropping (if exists) and creating table `{table_name}`")
    cur.execute(f"DROP TABLE IF EXISTS `{table_name}`")

    cols_sql = ",\n  ".join([f"`{name}` {col_type}" for name, col_type in schema])
    create_sql = f"""
        CREATE TABLE `{table_name}` (
          {cols_sql}
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    """
    cur.execute(create_sql)


def load_csv_into_table(cur, csv_path: Path, table_name: str) -> int:
    """Create table and load all rows from csv_path into it.

    Returns the number of rows inserted.
    """
    debug(f"Loading CSV `{csv_path.name}` into table `{table_name}`")

    with csv_path.open("r", newline="", encoding="utf-8-sig") as f:
        reader = csv.reader(f)
        try:
            header = next(reader)
        except StopIteration:
            print(f"[WARN] CSV `{csv_path}` is empty; skipping.")
            return 0

        schema = build_table_schema(header)
        create_table_from_schema(cur, table_name, schema)

        col_names = [name for name, _ in schema]
        placeholders = ",".join(["%s"] * len(col_names))
        insert_sql = (
            f"INSERT INTO `{table_name}` (" + ",".join(f"`{c}`" for c in col_names) + ") "
            f"VALUES ({placeholders})"
        )

        row_count = 0
        batch: List[Tuple[str, ...]] = []
        batch_size = 1000

        for row in reader:
            # Pad or trim row length to match header length
            if len(row) < len(col_names):
                row = row + [None] * (len(col_names) - len(row))
            elif len(row) > len(col_names):
                row = row[: len(col_names)]

            batch.append(tuple(row))
            row_count += 1

            if len(batch) >= batch_size:
                cur.executemany(insert_sql, batch)
                batch.clear()

        if batch:
            cur.executemany(insert_sql, batch)

    debug(f"Inserted {row_count} row(s) into `{table_name}`")
    return row_count


# ---------------------- MAIN LOGIC ----------------------


def parse_args(argv=None):
    parser = argparse.ArgumentParser(
        description=(
            "Load CSVs from the 'mainfiles' folder into MySQL tables named after each file."
        )
    )
    parser.add_argument("--host", default=DEFAULT_DB_HOST)
    parser.add_argument("--port", type=int, default=DEFAULT_DB_PORT)
    parser.add_argument("--user", default=DEFAULT_DB_USER)
    parser.add_argument("--password", default=DEFAULT_DB_PASSWORD)
    parser.add_argument("--database", default=DEFAULT_DB_NAME)
    return parser.parse_args(argv)


def main(argv=None) -> None:
    args = parse_args(argv)

    if not MAINFILES_DIR.exists():
        print(f"[ERROR] mainfiles directory not found: {MAINFILES_DIR}")
        sys.exit(1)

    print(f"[INFO] Using mainfiles directory: {MAINFILES_DIR}")

    conn = connect_db(args)
    try:
        with conn.cursor() as cur:
            # Make sure our index table exists
            ensure_table_index(cur)

            total_rows_across_files = 0
            total_tables_created = 0

            for filename in TARGET_CSV_FILES:
                csv_path = MAINFILES_DIR / filename
                if not csv_path.exists():
                    print(f"[WARN] CSV file not found, skipping: {csv_path}")
                    continue

                # Prefer our simple, explicit table names if configured
                if filename in SIMPLE_TABLE_NAMES:
                    table_name = SIMPLE_TABLE_NAMES[filename]
                else:
                    # Fallback: use file name without extension
                    table_name = os.path.splitext(filename)[0]
                prefixed_table_name = f"main-{table_name}"
                rows = load_csv_into_table(cur, csv_path, prefixed_table_name)
                update_table_index(cur, prefixed_table_name, csv_path, rows)
                total_rows_across_files += rows
                total_tables_created += 1

            # Now process subfolders inside mainfiles (allstar, biodata, postseason, tiebreakers,
            # regular, basiccsv, roster, etc.)
            for subdir in sorted(MAINFILES_DIR.iterdir()):
                if not subdir.is_dir():
                    continue

                folder_name = subdir.name

                # Special handling for roster/rosters: one table per file (CSV or ROS),
                # named "<folder>-<basename>".
                if folder_name.lower() in {"roster", "rosters"}:
                    for data_path in iter_data_files(subdir):
                        base = data_path.stem  # e.g., BOS1916, nyy_2024, etc.
                        table_name = f"{folder_name}-{base}"
                        rows = load_csv_into_table(cur, data_path, table_name)
                        update_table_index(cur, table_name, data_path, rows)
                        total_rows_across_files += rows
                        total_tables_created += 1
                    continue

                # All other subfolders get tables named "<folder>-<base>_1899_2024" when the base
                # is one of the main Retrosheet master files; otherwise "<folder>-<base>".
                for data_path in iter_data_files(subdir):
                    base = data_path.stem  # e.g., allplayers, batting, fielding, etc.
                    if base in MASTER_BASE_FILES:
                        table_base = f"{base}_1899_2024"
                    else:
                        table_base = base

                    table_name = f"{folder_name}-{table_base}"
                    rows = load_csv_into_table(cur, data_path, table_name)
                    update_table_index(cur, table_name, data_path, rows)
                    total_rows_across_files += rows
                    total_tables_created += 1

            conn.commit()
            print("[INFO] All CSVs processed.")
            print(f"       Total tables created/updated: {total_tables_created}")
            print(f"       Total rows inserted across files: {total_rows_across_files}")

    except Exception as e:
        conn.rollback()
        print("[ERROR] Rolling back transaction due to exception:", e)
        raise
    finally:
        conn.close()
        print("[INFO] MySQL connection closed.")


if __name__ == "__main__":
    main()
