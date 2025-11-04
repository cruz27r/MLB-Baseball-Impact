#!/usr/bin/env python3
import csv, os, re, sys, time
from datetime import datetime
from decimal import Decimal, InvalidOperation
from unidecode import unidecode
from dotenv import load_dotenv
import pymysql

# -------------------------------------------------------
# CONFIG
# -------------------------------------------------------
# best-effort .env load (ignore malformed lines)
try:
    load_dotenv(override=False)
except Exception:
    pass
HOST = os.getenv("MYSQL_HOST", "localhost")
PORT = int(os.getenv("MYSQL_PORT", "3306"))
DB   = os.getenv("MYSQL_DB", "mlb_impact")
USER = os.getenv("MYSQL_USER", "rafacruz")
PASS = os.getenv("MYSQL_PASS", "Ricky072701")
DATA_ROOT = os.path.abspath(os.getenv("DATA_ROOT", "./data"))

ALLOWED_EXTS = {".csv", ".txt", ".tsv", ".ros", ".eva", ".evn"}
HEADERLESS_PREFIXES = {"UMPIRES"}  # Retrosheet UMPIRESYYYY.txt files have no header
HEADERLESS_EXTS = {".ros", ".eva", ".evn"}  # rosters and event files are headerless
TABLE_PREFIX  = "stg_"      # prefix for staging tables
TARGET_SCHEMA = DB          # use mlb_impact directly

# canonical mappings (for cleaner table names)
CANON = {
    "people.csv": "lahman_people",
    "batting.csv": "lahman_batting",
    "pitching.csv": "lahman_pitching",
    "fielding.csv": "lahman_fielding",
    "teams.csv": "lahman_teams",
    "teamsfranchises.csv": "lahman_teams_franchises",
    "allstarfull.csv": "lahman_allstarfull",
    "managers.csv": "lahman_managers",
    "awardsplayers.csv": "lahman_awards_players",
    "salaries.csv": "lahman_salaries",
    "schools.csv": "lahman_schools",
    "halloffame.csv": "lahman_halloffame",
    "allplayers.csv": "retrosheet_allplayers",
    "batting.csv_rs": "retrosheet_batting",
    "pitching.csv_rs": "retrosheet_pitching",
    "fielding.csv_rs": "retrosheet_fielding",
    "plays.csv": "retrosheet_plays",
    "gameinfo.csv": "retrosheet_gameinfo",
    "teamstats.csv": "retrosheet_teamstats",
    "war_daily_bat.csv": "bref_war_daily_bat",
    "war_daily_pitch.csv": "bref_war_daily_pitch",
}

# -------------------------------------------------------
# HELPERS
# -------------------------------------------------------
def snake(s): return re.sub(r'[^A-Za-z0-9]+', '_', unidecode(s)).strip('_').lower()[:64]

def detect_delimiter(path):
    try:
        with open(path, "r", encoding="utf-8", errors="ignore") as f:
            sniff = csv.Sniffer().sniff(f.read(4096), delimiters=[",", "\t", ";", "|"])
        return sniff.delimiter
    except Exception:
        return ","

def read_header(path, delim, has_header=True):
    """
    Return column names. If the file has no header, synthesize names c1..cN based on first row width.
    """
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        reader = csv.reader(f, delimiter=delim)
        first = next(reader, [])
    if has_header and first:
        seen, cols = set(), []
        for h in first:
            c = snake(h) or "col"
            base, i = c, 2
            while c in seen:
                c = f"{base}_{i}"
                i += 1
            seen.add(c)
            cols.append(c)
        return cols
    # headerless: generate c1..cN
    width = len(first)
    return [f"c{i+1}" for i in range(width)]

def infer_types(path, delim, header_cols, limit=1500, has_header=True):
    stats = [dict(int=0, dec=0, date=0, other=0) for _ in header_cols]
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        reader = csv.reader(f, delimiter=delim)
        if has_header:
            next(reader, None)  # skip header row
        for i,row in enumerate(reader):
            if i>=limit: break
            for j,v in enumerate(row[:len(header_cols)]):
                v=v.strip()
                if not v or v.upper()=="NULL": continue
                if re.fullmatch(r"-?\d+",v): stats[j]["int"]+=1
                elif re.fullmatch(r"-?\d+\.\d*",v): stats[j]["dec"]+=1
                elif re.match(r"^\d{4}[-/]?\d{2}[-/]?\d{2}$",v): stats[j]["date"]+=1
                else: stats[j]["other"]+=1
    types=[]
    for s in stats:
        if s["date"]>=max(s.values()): t="DATE NULL"
        elif s["dec"]>=max(s.values()): t="DECIMAL(20,6) NULL"
        elif s["int"]>=max(s.values()): t="BIGINT NULL"
        else: t="VARCHAR(255) NULL"
        types.append(t)
    return types

def connect():
    return pymysql.connect(
        host=HOST, port=PORT, database=DB,
        user=USER, password=PASS,
        autocommit=True, charset="utf8mb4"
    )

def make_table_name(path):
    rel=os.path.relpath(path,DATA_ROOT)
    base=os.path.basename(path)
    key=base.lower()
    if "retrosheet/csv/" in rel and key in {"batting.csv","pitching.csv","fielding.csv"}:
        key=key.replace(".csv",".csv_rs")
    name=CANON.get(key, snake(rel))
    name=re.sub(r"^data_","",name)
    m=re.search(r"gl(\d{4})",base.lower())
    if m and not name.endswith(m.group(1)): name=f"{name}_{m.group(1)}"
    return name

def file_has_header(path, delim):
    base = os.path.basename(path)
    ext = os.path.splitext(base)[1].lower()
    # rule-based headerless detection
    if ext in HEADERLESS_EXTS:
        return False
    if any(base.upper().startswith(pfx) for pfx in HEADERLESS_PREFIXES):
        return False
    # otherwise, assume header present
    return True

def exec(cur,sql,params=None): cur.execute(sql if params is None else sql, params or ())

def create_table(cur,name,cols,types):
    sql="CREATE TABLE IF NOT EXISTS `{}` ({});".format(
        name, ", ".join(f"`{c}` {t}" for c,t in zip(cols,types))
    )
    exec(cur,sql)

def load_file(cur, table, path, delim, header=True, cols=None, batch_size=2000):
    """
    Load a delimited text file into `table` using batched INSERTs (no LOAD DATA).
    - `cols` must be the column list used when creating the table.
    - `header` indicates whether the first line is a header row to skip.
    """
    if cols is None or not cols:
        raise ValueError("load_file requires `cols` list of column names")

    placeholders = ", ".join(["%s"] * len(cols))
    col_list = ", ".join(f"`{c}`" for c in cols)
    insert_sql = f"INSERT INTO `{table}` ({col_list}) VALUES ({placeholders})"

    total = 0
    batch = []

    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        reader = csv.reader(f, delimiter=delim)
        if header:
            next(reader, None)  # skip header row

        for row in reader:
            # normalize row length to match table width
            if len(row) < len(cols):
                row = row + [""] * (len(cols) - len(row))
            elif len(row) > len(cols):
                row = row[:len(cols)]

            # normalize empty strings to None so MySQL stores NULL
            norm = [v.strip() if isinstance(v, str) else v for v in row]
            norm = [None if (v == "" or str(v).upper() == "NULL") else v for v in norm]

            batch.append(tuple(norm))
            if len(batch) >= batch_size:
                cur.executemany(insert_sql, batch)
                batch.clear()
                total += batch_size

        if batch:
            cur.executemany(insert_sql, batch)
            total += len(batch)

    return total

def record_manifest(cur,src,table,rows,notes):
    exec(cur,"INSERT INTO stg_load_manifest (source_path,table_name,rows_loaded,notes) VALUES (%s,%s,%s,%s)",
         (src,table,rows,notes))

def skip(p):
    lp = p.lower()
    # obvious non-data artifacts
    if lp.endswith((".zip", ".json", ".md", ".icloud")) or os.path.basename(lp) in {".ds_store", "readme2024.txt", "readme.txt"}:
        return True
    # Avoid duplicate WAR .txt files; keep retrosheet .txt files
    if "/bref_war/" in lp and lp.endswith(".txt"):
        return True
    return False

# -------------------------------------------------------
# MAIN
# -------------------------------------------------------
def main():
    cn=connect(); cur=cn.cursor()
    print(f"Scanning DATA_ROOT: {DATA_ROOT}")
    exec(cur,"""CREATE TABLE IF NOT EXISTS stg_load_manifest(
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        source_path TEXT, table_name VARCHAR(128),
        rows_loaded BIGINT, loaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT) ENGINE=InnoDB;""")

    loaded,errors=0,0
    for root,_,files in os.walk(DATA_ROOT):
        for f in files:
            fp=os.path.join(root,f)
            if skip(fp): continue
            ext=os.path.splitext(f)[1].lower()
            if ext not in ALLOWED_EXTS: continue
            try:
                delim = detect_delimiter(fp)
                has_header = file_has_header(fp, delim)
                cols = read_header(fp, delim, has_header)
                types = infer_types(fp, delim, cols, has_header=has_header)
                tname = f"{TABLE_PREFIX}{make_table_name(fp)}"
                print(f"\n→ {fp}\n   table: {tname}")
                create_table(cur, tname, cols, types)
                before = time.time()
                rows = load_file(cur, tname, fp, delim, header=has_header, cols=cols)
                record_manifest(cur, fp, tname, rows, f"loaded in {time.time()-before:.1f}s")
                print(f"   loaded {rows} rows")
                loaded+=1
            except Exception as e:
                errors+=1
                record_manifest(cur,fp,tname,None,f"ERROR: {e}")
                print(f"   ERROR: {e}",file=sys.stderr)
    print(f"\nDone. Files loaded: {loaded}, errors: {errors}")
    cur.close(); cn.close()

if __name__=="__main__": main()