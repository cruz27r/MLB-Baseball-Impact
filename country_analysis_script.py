import sys
import argparse
import pymysql
import sys
import argparse
import pymysql

# --- Configuration / constants ---

DB_DEFAULT_NAME = "mlb_impact"

# Source tables
PEOPLE_TABLE = "stg_lahman_people"           # Lahman people table (has birthCountry, birthYear, retroID, playerID)
ALLPLAYERS_TABLE = "stg_retrosheet_allplayers"  # Retrosheet allplayers (has id, first_g, etc.)

# Output (summary) tables
COUNTRY_SUMMARY_TABLE = "player_country_summary"
COUNTRY_DECADE_SUMMARY_TABLE = "player_country_decade_summary"
COUNTRY_DEBUT_DECADE_SUMMARY_TABLE = "player_country_debut_decade_summary"  # debut by decade based on first_g
PLAYER_DEBUT_PERCENT_PIVOT_TABLE = "player_debut_percent_by_country"


# --- Connection helper ---

def get_connection(args):
    """Open a connection to local MySQL using your root credentials."""
    print("[DEBUG] Opening MySQL connection to localhost:3306 as root...")
    conn = pymysql.connect(
        host="127.0.0.1",
        user="root",
        password="Ricky072701",
        db=args.db,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.Cursor,
    )
    return conn


# --- Table creation helpers ---


def ensure_country_summary_table(cur):
    """Overall player distribution by *birth* country."""
    cur.execute(f"DROP TABLE IF EXISTS {COUNTRY_SUMMARY_TABLE}")
    cur.execute(f"""
        CREATE TABLE IF NOT EXISTS {COUNTRY_SUMMARY_TABLE} (
            birth_country  VARCHAR(64)   NOT NULL,
            player_count   INT           NOT NULL,
            pct_of_total   DECIMAL(7,4)  NOT NULL,
            PRIMARY KEY (birth_country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """)
    print(f"[OK] Ensured summary table '{COUNTRY_SUMMARY_TABLE}' exists.")


def ensure_country_decade_summary_table(cur):
    """Birth-decade-by-country distribution."""
    cur.execute(f"DROP TABLE IF EXISTS {COUNTRY_DECADE_SUMMARY_TABLE}")
    cur.execute(f"""
        CREATE TABLE IF NOT EXISTS {COUNTRY_DECADE_SUMMARY_TABLE} (
            birth_country      VARCHAR(64)   NOT NULL,
            decade_start_year  INT           NOT NULL,
            player_count       INT           NOT NULL,
            pct_of_decade      DECIMAL(7,4)  NOT NULL,
            PRIMARY KEY (birth_country, decade_start_year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """)
    print(f"[OK] Ensured summary table '{COUNTRY_DECADE_SUMMARY_TABLE}' exists.")


def ensure_country_debut_decade_summary_table(cur):
    """Debut-decade-by-country distribution based on first recorded game in Retrosheet.

    Results go into table 'playercountry_debut'.
    """
    cur.execute(f"DROP TABLE IF EXISTS {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}")
    cur.execute(f"""
        CREATE TABLE IF NOT EXISTS {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE} (
            birth_country      VARCHAR(64)   NOT NULL,
            decade_start_year  INT           NOT NULL,
            player_count       INT           NOT NULL,
            pct_of_decade      DECIMAL(7,4)  NOT NULL,
            PRIMARY KEY (birth_country, decade_start_year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """)
    print(f"[OK] Ensured summary table '{COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}' exists.")


def build_debut_percent_pivot_table(cur):
    """
    Build a single pivot table of debut percentages by country and decade:

      Table: player_debut_percent_by_country

      - country (PK)
      - one column per decade, named like d1870s, d1880s, ..., d2020s
      - value = pct_of_decade from player_country_debut_decade_summary

    Uses the data already stored in COUNTRY_DEBUT_DECADE_SUMMARY_TABLE.
    """
    print("[STEP] Building pivot table 'player_debut_percent_by_country' from debut summary...")

    # Make sure the source summary table has data
    cur.execute(f"""
        SELECT DISTINCT decade_start_year
        FROM {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}
        WHERE decade_start_year IS NOT NULL
        ORDER BY decade_start_year
    """)
    rows = cur.fetchall()
    decades = [r[0] for r in rows]

    if not decades:
        print(f"[WARN] No decades found in {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}; "
              "creating an empty pivot table with only 'country' column.")
    else:
        print(f"[INFO] Decades found in {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}: {decades}")

    # Drop old pivot table (if any)
    cur.execute(f"DROP TABLE IF EXISTS `{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}`")

    # Create pivot table structure
    if not decades:
        create_sql = f"""
            CREATE TABLE `{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}` (
                country VARCHAR(64) NOT NULL,
                PRIMARY KEY (country)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """
        cur.execute(create_sql)
        print(f"[OK] Created empty pivot table '{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}'.")
        return

    decade_cols = []
    for d in decades:
        col_name = f"d{d}s"  # e.g. 1990 -> d1990s
        decade_cols.append(f"`{col_name}` DECIMAL(7,4) NULL")

    create_sql = f"""
        CREATE TABLE `{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}` (
            country VARCHAR(64) NOT NULL,
            {", ".join(decade_cols)},
            PRIMARY KEY (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """
    cur.execute(create_sql)
    print(f"[OK] Recreated '{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}' with decade columns: "
          f"{[f'd{d}s' for d in decades]}")

    # Load debut-based percentages from the summary table
    cur.execute(f"""
        SELECT birth_country, decade_start_year, pct_of_decade
        FROM {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}
        WHERE decade_start_year IS NOT NULL
    """)
    rows = cur.fetchall()

    if not rows:
        print(f"[WARN] No rows found in {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}; "
              "pivot table will remain empty.")
        return

    # Pivot into a dict: { country: {decade_start_year: pct_of_decade} }
    data = {}
    for country, decade_start_year, pct in rows:
        if country is None or decade_start_year is None or pct is None:
            continue
        decade = int(decade_start_year)
        data.setdefault(country, {})[decade] = float(pct)

    print(f"[INFO] Pivoting debut percentages for {len(data)} countries...")

    # Prepare INSERT
    col_names = ["country"] + [f"d{d}s" for d in decades]
    col_list_sql = ", ".join(f"`{c}`" for c in col_names)
    placeholders = ", ".join(["%s"] * len(col_names))
    insert_sql = f"""
        INSERT INTO `{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}` ({col_list_sql})
        VALUES ({placeholders})
    """

    inserted = 0
    for country, decade_map in data.items():
        values = [country]
        for d in decades:
            values.append(decade_map.get(d))  # None if no players from that country/decade
        cur.execute(insert_sql, values)
        inserted += 1

    print(f"[OK] Inserted {inserted} row(s) into '{PLAYER_DEBUT_PERCENT_PIVOT_TABLE}'.")
    print("      Example query:")
    print(f"        SELECT * FROM {PLAYER_DEBUT_PERCENT_PIVOT_TABLE} LIMIT 20;")
    print(f"        SELECT * FROM {PLAYER_DEBUT_PERCENT_PIVOT_TABLE} WHERE country = 'USA';")


# --- Computation helpers ---


def compute_country_summary(cur):
    """Compute overall player distribution by birth country from Lahman people."""
    print("[STEP] Computing overall player distribution by country (birthCountry)...")

    # Clear previous run
    cur.execute(f"TRUNCATE TABLE {COUNTRY_SUMMARY_TABLE}")

    # Insert counts by country
    insert_sql = f"""
        INSERT INTO {COUNTRY_SUMMARY_TABLE} (birth_country, player_count, pct_of_total)
        SELECT
            birthCountry AS birth_country,
            COUNT(*)     AS player_count,
            0.0          AS pct_of_total
        FROM {PEOPLE_TABLE}
        WHERE birthCountry IS NOT NULL
          AND birthCountry <> ''
        GROUP BY birthCountry
        ORDER BY player_count DESC;
    """
    cur.execute(insert_sql)

    # Update pct_of_total using a single total
    update_sql = f"""
        UPDATE {COUNTRY_SUMMARY_TABLE} AS s
        JOIN (
            SELECT SUM(player_count) AS total_players
            FROM {COUNTRY_SUMMARY_TABLE}
        ) AS t
        SET s.pct_of_total = 100.0 * s.player_count / t.total_players;
    """
    cur.execute(update_sql)

    print("[OK] Inserted country summary for" , cur.rowcount , "row(s).")
    print("      Example: SELECT * FROM player_country_summary ORDER BY player_count DESC LIMIT 10;")


def compute_country_decade_summary(cur):
    """Compute per-birth-decade distribution by country."""
    print("[STEP] Computing per-decade player distribution by country (birth year)...")

    # Clear previous run
    cur.execute(f"TRUNCATE TABLE {COUNTRY_DECADE_SUMMARY_TABLE}")

    # Insert counts by (country, birth-decade)
    insert_sql = f"""
        INSERT INTO {COUNTRY_DECADE_SUMMARY_TABLE}
            (birth_country, decade_start_year, player_count, pct_of_decade)
        SELECT
            birthCountry AS birth_country,
            FLOOR(birthYear / 10) * 10 AS decade_start_year,
            COUNT(*) AS player_count,
            0.0      AS pct_of_decade
        FROM {PEOPLE_TABLE}
        WHERE birthCountry IS NOT NULL
          AND birthCountry <> ''
          AND birthYear IS NOT NULL
          AND birthYear BETWEEN 1800 AND 2100
        GROUP BY
            birthCountry,
            FLOOR(birthYear / 10) * 10
        ORDER BY
            decade_start_year,
            player_count DESC;
    """
    cur.execute(insert_sql)

    # Update pct_of_decade within each decade
    update_sql = f"""
        UPDATE {COUNTRY_DECADE_SUMMARY_TABLE} AS s
        JOIN (
            SELECT
                decade_start_year,
                SUM(player_count) AS total_players_in_decade
            FROM {COUNTRY_DECADE_SUMMARY_TABLE}
            GROUP BY decade_start_year
        ) AS d
          ON s.decade_start_year = d.decade_start_year
        SET s.pct_of_decade = 100.0 * s.player_count / d.total_players_in_decade;
    """
    cur.execute(update_sql)

    print("[OK] Inserted country-by-decade summary for", cur.rowcount, "row(s).")
    print("      Example:\n        SELECT * FROM player_country_decade_summary\n        WHERE decade_start_year = 1990\n        ORDER BY player_count DESC LIMIT 10;")


def compute_country_debut_decade_summary(cur):
    """Build debut-decade-by-country summary based on players' *first recorded game*.

    We join Lahman people (for birthCountry) to Retrosheet allplayers (for first_g).
    first_g is an 8-digit yyyymmdd integer; we derive the debut year from this.
    decade_start_year = floor(year / 10) * 10
    """
    print("[STEP] Computing per-decade player distribution by country (based on first recorded game in Retrosheet)...")

    # Clear out old data in the debut-decade summary table
    cur.execute(f"TRUNCATE TABLE {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}")

    # Insert new debut-decade rows
    insert_sql = f"""
        INSERT INTO {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}
            (birth_country, decade_start_year, player_count, pct_of_decade)
        SELECT
            lp.birthCountry AS birth_country,
            FLOOR(ap.first_year / 10) * 10 AS decade_start_year,
            COUNT(DISTINCT ap.id) AS player_count,
            0.0 AS pct_of_decade
        FROM {PEOPLE_TABLE} AS lp
        JOIN (
            SELECT
                id,
                MIN(
                    CAST(
                        SUBSTRING(CAST(first_g AS CHAR(8)), 1, 4) AS UNSIGNED
                    )
                ) AS first_year
            FROM {ALLPLAYERS_TABLE}
            WHERE first_g IS NOT NULL
              AND first_g <> 0
            GROUP BY id
        ) AS ap
          ON lp.retroID  = ap.id
          OR lp.playerID = ap.id
        WHERE lp.birthCountry IS NOT NULL
          AND lp.birthCountry <> ''
          AND ap.first_year BETWEEN 1871 AND 2100
        GROUP BY
            lp.birthCountry,
            FLOOR(ap.first_year / 10) * 10
        ORDER BY
            decade_start_year,
            player_count DESC;
    """
    cur.execute(insert_sql)

    # Update pct_of_decade using totals per decade
    update_sql = f"""
        UPDATE {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE} AS s
        JOIN (
            SELECT
                decade_start_year,
                SUM(player_count) AS total_players_in_decade
            FROM {COUNTRY_DEBUT_DECADE_SUMMARY_TABLE}
            GROUP BY decade_start_year
        ) AS d
          ON s.decade_start_year = d.decade_start_year
        SET s.pct_of_decade = 100.0 * s.player_count / d.total_players_in_decade;
    """
    cur.execute(update_sql)

    print("[OK] Inserted debut-based country-by-decade summary for", cur.rowcount, "row(s).")
    print("      Example:\n        SELECT * FROM player_country_debut_decade_summary\n        WHERE decade_start_year = 2010\n        ORDER BY player_count DESC LIMIT 10;")


# --- CLI + main ---


def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--db",
        default=DB_DEFAULT_NAME,
        help=f"MySQL database name (default: {DB_DEFAULT_NAME})",
    )
    return parser.parse_args()


def main():
    args = parse_args()
    conn = None
    try:
        conn = get_connection(args)
        with conn.cursor() as cur:
            # Confirm where we are connected
            cur.execute("SELECT DATABASE(), @@hostname, @@port")
            db_name, host_name, port_num = cur.fetchone()
            print(f"Connected to database: {db_name}")
            print(f"[DEBUG] MySQL server: {host_name}, port: {port_num}, database: {db_name}")

            # Ensure all summary tables exist
            ensure_country_summary_table(cur)
            ensure_country_decade_summary_table(cur)
            ensure_country_debut_decade_summary_table(cur)

            # Compute / refresh all summaries
            compute_country_summary(cur)
            compute_country_decade_summary(cur)
            compute_country_debut_decade_summary(cur)

            # Build pivot table of debut percentages by country/decade
            build_debut_percent_pivot_table(cur)

            conn.commit()
    finally:
        if conn is not None:
            conn.close()
            print("Connection closed.")


if __name__ == "__main__":
    sys.exit(main())