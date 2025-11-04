#!/usr/bin/env python3
from pathlib import Path
import csv
import os
import subprocess
from collections import defaultdict

# Root: project/scripts -> project/, then /data
DATA_ROOT = (Path(__file__).resolve().parents[1] / "data").resolve()
OUT_CSV   = (Path(__file__).resolve().parents[1] / "data_file_list.csv").resolve()

INCLUDE_EXTS = {".csv", ".txt", ".zip"}  # include zips just for visibility

def has_icloud_xattr(p: Path) -> bool:
    try:
        # if this returns non-zero, it's probably an iCloud-managed file
        out = subprocess.run(
            ["xattr", "-p", "com.apple.iCloud", str(p)],
            capture_output=True, text=True
        )
        return out.returncode == 0
    except Exception:
        return False

def main():
    if not DATA_ROOT.exists():
        raise SystemExit(f"❌ Data root not found: {DATA_ROOT}")

    rows = []
    per_dir_counts = defaultdict(int)
    missing_local = []

    for fp in DATA_ROOT.rglob("*"):
        if not fp.is_file():
            continue
        if fp.suffix.lower() not in INCLUDE_EXTS:
            continue

        rel = fp.relative_to(DATA_ROOT)
        size = fp.stat().st_size if fp.exists() else 0
        icloud = has_icloud_xattr(fp)
        note = ""
        if size == 0 or icloud:
            note = "icloud_placeholder?"

        rows.append({
            "relative_path": str(rel),
            "name": fp.name,
            "ext": fp.suffix.lower(),
            "size_bytes": size,
            "icloud_placeholder": "Y" if (size == 0 or icloud) else "",
            "parent_dir": str(rel.parent)
        })
        per_dir_counts[str(rel.parent)] += 1
        if note:
            missing_local.append(str(rel))

    OUT_CSV.parent.mkdir(parents=True, exist_ok=True)
    with OUT_CSV.open("w", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=list(rows[0].keys()) if rows else
                                ["relative_path","name","ext","size_bytes","icloud_placeholder","parent_dir"])
        writer.writeheader()
        writer.writerows(rows)

    # Console summary
    print(f"📄 Wrote: {OUT_CSV}")
    print(f"📦 Total files: {len(rows)} (csv/txt/zip)")
    print("\n📊 Files per subfolder (top 20):")
    for d, c in sorted(per_dir_counts.items(), key=lambda x: (-x[1], x[0]))[:20]:
        print(f"  {c:4d}  {d}")

    if missing_local:
        print("\n⚠️  Files that look like iCloud placeholders (download needed):")
        for r in missing_local[:30]:
            print("   ", r)
        if len(missing_local) > 30:
            print(f"   …and {len(missing_local)-30} more")

if __name__ == "__main__":
    main()