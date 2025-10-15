#!/usr/bin/env bash
# CS437 MLB Global Era - Download Retrosheet Data
#
# Downloads and extracts Retrosheet CSV files
#
# Usage:
#   ./scripts/download_retrosheet.sh <RETRO_URL> [OUTPUT_DIR]
#
# Example:
#   ./scripts/download_retrosheet.sh "https://retrosheet.org/path/to/retrosheet.zip" ~/mlb_data/retrosheet

set -euo pipefail

RETRO_URL="${1:-}"
OUT_DIR="${2:-$HOME/mlb_data/retrosheet}"

if [ -z "$RETRO_URL" ]; then
    echo "❌ Provide CSV ZIP URL from retrosheet.org"
    echo "Usage: $0 <RETRO_URL> [OUTPUT_DIR]"
    exit 1
fi

echo "============================================"
echo "Downloading Retrosheet Data"
echo "============================================"
echo "URL: $RETRO_URL"
echo "Output Directory: $OUT_DIR"
echo ""

mkdir -p "$OUT_DIR"

ZIP="$OUT_DIR/retrosheet_csv.zip"

echo "Downloading Retrosheet CSV ZIP file..."
curl -fL "$RETRO_URL" -o "$ZIP"

echo "Extracting CSV files..."
unzip -o "$ZIP" -d "$OUT_DIR"

echo ""
echo "✅ Retrosheet CSVs ready in $OUT_DIR"
echo ""
echo "Files downloaded:"
ls -lh "$OUT_DIR"/*.csv 2>/dev/null | awk '{print "  - " $9}' || echo "  (no CSV files found)"
