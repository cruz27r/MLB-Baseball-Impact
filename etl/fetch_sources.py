#!/usr/bin/env python3
"""
fetch_sources.py
Downloads MLB datasets (Retrosheet, B-Ref WAR; Lahman kept manual),
extracts them into clean subfolders, and (optionally) purges ZIPs
and non-usable files for SQL analytics.

Features:
- Checksum skip logic for downloads (.md5 sidecars)
- Extraction markers to avoid re-unzipping the same archive
- Clean folder separation:
  data/retrosheet/{csv,gamelogs,events,boxscores,rosters}
  data/bref_war
  data/lahman
- Optional --purge-zips to delete archives after extraction
"""

import os, sys, io, argparse, hashlib, zipfile
from pathlib import Path
import requests

# ---------- Paths ----------
ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
RETRO_DIR = DATA / "retrosheet"
RETRO_CSV_DIR = RETRO_DIR / "csv"
RETRO_GAMELOGS_DIR = RETRO_DIR / "gamelogs"
RETRO_EVENTS_DIR = RETRO_DIR / "events"
RETRO_BOXSCORES_DIR = RETRO_DIR / "boxscores"
RETRO_ROSTERS_DIR = RETRO_DIR / "rosters"  # manual (your folder)
LAHMAN_DIR = DATA / "lahman"               # manual (your folder)
BREF_DIR = DATA / "bref_war"

for d in (
    DATA, RETRO_DIR, RETRO_CSV_DIR, RETRO_GAMELOGS_DIR,
    RETRO_EVENTS_DIR, RETRO_BOXSCORES_DIR, RETRO_ROSTERS_DIR,
    LAHMAN_DIR, BREF_DIR
):
    d.mkdir(parents=True, exist_ok=True)

# ---------- Helpers ----------
def http_get(url: str) -> bytes:
    print(f"🌐 GET {url}")
    r = requests.get(url, timeout=180, allow_redirects=True)
    r.raise_for_status()
    return r.content

def md5_bytes(b: bytes) -> str:
    return hashlib.md5(b).hexdigest()

def md5_file(path: Path) -> str:
    h = hashlib.md5()
    with path.open("rb") as f:
        for chunk in iter(lambda: f.read(1<<20), b""):
            h.update(chunk)
    return h.hexdigest()

def ensure_sidecar_md5(path: Path) -> str | None:
    if not path.exists():
        return None
    side = path.with_suffix(path.suffix + ".md5")
    if side.exists():
        return side.read_text().strip()
    h = md5_file(path)
    side.write_text(h)
    return h

def write_with_md5(path: Path, b: bytes) -> str:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(b)
    h = md5_bytes(b)
    (path.with_suffix(path.suffix + ".md5")).write_text(h)
    print(f"✅ saved {path.name} ({len(b)/1024:.1f} KB, md5={h[:8]})")
    return h

def unzip_bytes_to(b: bytes, dest: Path, keep_suffixes: tuple[str, ...]) -> int:
    keep = tuple(s.lower() for s in keep_suffixes)
    kept = 0
    with zipfile.ZipFile(io.BytesIO(b)) as z:
        for name in z.namelist():
            base = Path(name).name
            if not base:
                continue
            lower = base.lower()
            if keep and not any(lower.endswith(s) for s in keep):
                continue
            out = dest / base
            out.parent.mkdir(parents=True, exist_ok=True)
            out.write_bytes(z.read(name))
            kept += 1
    print(f"📂 extracted {kept} files → {dest}")
    return kept

def already_processed(zip_basename: str, outdir: Path) -> bool:
    # A prior successful extraction leaves this marker even if ZIP was purged.
    marker = outdir / f".unzipped.{zip_basename}.md5"
    return marker.exists()

def mark_processed(zip_basename: str, outdir: Path, zip_md5: str):
    (outdir / f".unzipped.{zip_basename}.md5").write_text(zip_md5)

def purge_zip(zip_path: Path):
    md5_side = zip_path.with_suffix(zip_path.suffix + ".md5")
    if zip_path.exists():
        zip_path.unlink(missing_ok=True)
    if md5_side.exists():
        md5_side.unlink(missing_ok=True)

def prune_non_usable(outdir: Path, allowed_suffixes: tuple[str, ...]):
    allowed = tuple(s.lower() for s in allowed_suffixes)
    removed = 0
    for p in outdir.iterdir():
        if p.is_dir() or p.name.startswith("."):
            continue
        if not any(p.name.lower().endswith(s) for s in allowed):
            p.unlink(missing_ok=True)
            md5 = p.with_suffix(p.suffix + ".md5")
            md5.unlink(missing_ok=True)
            removed += 1
    if removed:
        print(f"🧹 pruned {removed} non-usable files in {outdir}")

# ---------- Notes for manual folders ----------
def note_lahman_manual():
    print("📦 Lahman CSVs expected in data/lahman/ (manual download ok)")

def note_rosters_manual():
    print("🗂️  Your manual rosters live in data/retrosheet/rosters/ (kept as-is)")

# ---------- NEW: Retrosheet master CSV bundle ----------
def fetch_retrosheet_master_csv(force: bool, purge_zips: bool):
    """
    https://www.retrosheet.org/downloads/csvdownloads.zip
    Extracts into data/retrosheet/csv/ and keeps only .csv files.
    """
    print("📦 Retrosheet Master CSV bundle…")
    url = "https://www.retrosheet.org/downloads/csvdownloads.zip"
    fname = url.rsplit("/", 1)[-1]
    zip_path = RETRO_CSV_DIR / fname
    keep = (".csv",)

    if already_processed(fname, RETRO_CSV_DIR) and not force:
        print(f"  • {fname} (skip: already processed)")
        return

    # Download (or reuse)
    if zip_path.exists() and not force:
        print(f"  • {fname} (using existing ZIP)")
        zbytes = zip_path.read_bytes()
        zmd5 = ensure_sidecar_md5(zip_path) or md5_bytes(zbytes)
    else:
        try:
            zbytes = http_get(url)
            zmd5 = write_with_md5(zip_path, zbytes)
        except Exception as e:
            print(f"  ⚠️ {fname} download failed: {e}")
            return

    # Extract only CSVs
    try:
        unzip_bytes_to(zbytes, RETRO_CSV_DIR, keep_suffixes=keep)
        mark_processed(fname, RETRO_CSV_DIR, zmd5)
        if purge_zips:
            purge_zip(zip_path)
    except Exception as e:
        print(f"  ⚠️ {fname} extract failed: {e}")

    prune_non_usable(RETRO_CSV_DIR, allowed_suffixes=keep)
    print(f"✅ Retrosheet CSVs → {RETRO_CSV_DIR}")

# ---------- Retrosheet: Gamelogs ----------
def fetch_retrosheet_gamelogs(force: bool, purge_zips: bool):
    print("📦 Retrosheet Gamelogs (regular+postseason)…")
    urls = [
        "https://www.retrosheet.org/gamelogs/gl1871_2024.zip",
        "https://www.retrosheet.org/gamelogs/glws.zip",
        "https://www.retrosheet.org/gamelogs/glas.zip",
        "https://www.retrosheet.org/gamelogs/glwc.zip",
        "https://www.retrosheet.org/gamelogs/gldv.zip",
        "https://www.retrosheet.org/gamelogs/gllc.zip",
    ]
    keep = (".txt", ".csv")

    for u in urls:
        fname = u.rsplit("/",1)[-1]
        zip_path = RETRO_GAMELOGS_DIR / fname
        if already_processed(fname, RETRO_GAMELOGS_DIR) and not force:
            print(f"  • {fname} (skip: already processed)")
            continue

        if zip_path.exists() and not force:
            print(f"  • {fname} (using existing ZIP)")
            zbytes = zip_path.read_bytes()
            zmd5 = ensure_sidecar_md5(zip_path) or md5_bytes(zbytes)
        else:
            try:
                zbytes = http_get(u)
                zmd5 = write_with_md5(zip_path, zbytes)
            except Exception as e:
                print(f"  ⚠️ {fname} download failed: {e}")
                continue

        try:
            unzip_bytes_to(zbytes, RETRO_GAMELOGS_DIR, keep_suffixes=keep)
            mark_processed(fname, RETRO_GAMELOGS_DIR, zmd5)
            if purge_zips:
                purge_zip(zip_path)
        except Exception as e:
            print(f"  ⚠️ {fname} extract failed: {e}")

    prune_non_usable(RETRO_GAMELOGS_DIR, allowed_suffixes=keep)
    print(f"✅ gamelogs → {RETRO_GAMELOGS_DIR}")

# ---------- Retrosheet: Events & Boxscores ----------
def fetch_retrosheet_events_and_box(force: bool, purge_zips: bool):
    print("📦 Retrosheet Events + Boxscores by decade…")
    events = [
        "1910seve.zip","1920seve.zip","1930seve.zip","1940seve.zip",
        "1950seve.zip","1960seve.zip","1970seve.zip","1980seve.zip",
        "1990seve.zip","2000seve.zip","2010seve.zip","2020seve.zip",
    ]
    boxs = [
        "1900sbox.zip","1910sbox.zip","1920sbox.zip","1930sbox.zip",
        "1940sbox.zip","1950sbox.zip","1960sbox.zip","1970sbox.zip",
        "1980sbox.zip","1990sbox.zip","2000sbox.zip","2010sbox.zip","2020sbox.zip",
    ]
    base = "https://www.retrosheet.org/events"

    # Events
    ev_keep = (".evn", ".eva", ".ev", ".txt", ".csv")
    for fname in events:
        url = f"{base}/{fname}"
        zip_path = RETRO_EVENTS_DIR / fname
        if already_processed(fname, RETRO_EVENTS_DIR) and not force:
            print(f"  • {fname} (skip: already processed)")
            continue
        if zip_path.exists() and not force:
            print(f"  • {fname} (using existing ZIP)")
            zbytes = zip_path.read_bytes()
            zmd5 = ensure_sidecar_md5(zip_path) or md5_bytes(zbytes)
        else:
            try:
                zbytes = http_get(url)
                zmd5 = write_with_md5(zip_path, zbytes)
            except Exception as e:
                print(f"  ⚠️ {fname} download failed: {e}")
                continue
        try:
            unzip_bytes_to(zbytes, RETRO_EVENTS_DIR, keep_suffixes=ev_keep)
            mark_processed(fname, RETRO_EVENTS_DIR, zmd5)
            if purge_zips:
                purge_zip(zip_path)
        except Exception as e:
            print(f"  ⚠️ {fname} extract failed: {e}")
    prune_non_usable(RETRO_EVENTS_DIR, allowed_suffixes=ev_keep)
    print(f"✅ events → {RETRO_EVENTS_DIR}")

    # Boxscores
    box_keep = (".box", ".txt", ".csv")
    for fname in boxs:
        url = f"{base}/{fname}"
        zip_path = RETRO_BOXSCORES_DIR / fname
        if already_processed(fname, RETRO_BOXSCORES_DIR) and not force:
            print(f"  • {fname} (skip: already processed)")
            continue
        if zip_path.exists() and not force:
            print(f"  • {fname} (using existing ZIP)")
            zbytes = zip_path.read_bytes()
            zmd5 = ensure_sidecar_md5(zip_path) or md5_bytes(zbytes)
        else:
            try:
                zbytes = http_get(url)
                zmd5 = write_with_md5(zip_path, zbytes)
            except Exception as e:
                print(f"  ⚠️ {fname} download failed: {e}")
                continue
        try:
            extracted = unzip_bytes_to(zbytes, RETRO_BOXSCORES_DIR, keep_suffixes=box_keep)
            if extracted == 0:
                print("    ℹ️ archive had no .BOX/.TXT/.CSV (some decades may be sparse)")
            mark_processed(fname, RETRO_BOXSCORES_DIR, zmd5)
            if purge_zips:
                purge_zip(zip_path)
        except Exception as e:
            print(f"  ⚠️ {fname} extract failed: {e}")
    prune_non_usable(RETRO_BOXSCORES_DIR, allowed_suffixes=box_keep)
    print(f"✅ boxscores → {RETRO_BOXSCORES_DIR}")

# ---------- Baseball-Reference WAR ----------
def fetch_bref_war(force: bool):
    print("📦 Baseball-Reference WAR daily…")
    urls = {
        "war_daily_bat.txt":   "https://www.baseball-reference.com/data/war_daily_bat.txt",
        "war_daily_pitch.txt": "https://www.baseball-reference.com/data/war_daily_pitch.txt",
    }
    keep = (".txt", ".csv")
    for fname, url in urls.items():
        dest = BREF_DIR / fname
        if dest.exists() and not force:
            print(f"  • {fname} (skip: exists)")
            continue
        try:
            b = http_get(url)
            write_with_md5(dest, b)
            # .csv twin
            csvp = dest.with_suffix(".csv")
            if not csvp.exists():
                csvp.write_bytes(b)
                (csvp.with_suffix(csvp.suffix + ".md5")).write_text(md5_bytes(b))
        except Exception as e:
            print(f"  ⚠️ {fname} failed: {e}")
    prune_non_usable(BREF_DIR, allowed_suffixes=keep)
    print(f"✅ WAR files → {BREF_DIR}")

# ---------- Main ----------
def main():
    ap = argparse.ArgumentParser(description="Fetch MLB datasets w/ idempotent extraction & cleanup")
    ap.add_argument("--force", action="store_true", help="Re-download and re-extract everything")
    ap.add_argument("--purge-zips", action="store_true", help="Delete ZIP archives after extraction")
    args = ap.parse_args()

    print("🚀 Starting MLB data fetch…")
    note_lahman_manual()   # you keep Lahman in data/lahman
    note_rosters_manual()  # you keep manual rosters in data/retrosheet/rosters

    # NEW: Master CSV bundle first (core tables you'll query a lot)
    fetch_retrosheet_master_csv(force=args.force, purge_zips=args.purge_zips)

    # Then the others you already set up
    fetch_retrosheet_gamelogs(force=args.force, purge_zips=args.purge_zips)
    fetch_retrosheet_events_and_box(force=args.force, purge_zips=args.purge_zips)
    fetch_bref_war(force=args.force)

    total = sum(len(files) for _, _, files in os.walk(DATA))
    print(f"\n🎉 All done. {total} files under {DATA}")

if __name__ == "__main__":
    sys.exit(main())