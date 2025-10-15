#!/usr/bin/env bash
# CS437 MLB Global Era - Download SABR Lahman Database
#
# Downloads and extracts Lahman Baseball Database CSV files from SABR.org
#
# Usage:
#   ./scripts/download_lahman_sabr.sh <LAHMAN_URL> [OUTPUT_DIR]
#
# Example:
#   ./scripts/download_lahman_sabr.sh "https://sabr.org/path/to/lahman.zip" ~/mlb_data/lahman

set -euo pipefail

LAHMAN_URL="${1:-}"
OUT_DIR="${2:-$HOME/mlb_data/lahman}"

if [ -z "$LAHMAN_URL" ]; then
    echo "❌ Provide direct CSV ZIP link from sabr.org/lahman-database"
    echo "Usage: $0 <LAHMAN_URL> [OUTPUT_DIR]"
    exit 1
fi

echo "============================================"
echo "Downloading SABR Lahman Database"
echo "============================================"
echo "URL: $LAHMAN_URL"
echo "Output Directory: $OUT_DIR"
echo ""

mkdir -p "$OUT_DIR"

ZIP="$OUT_DIR/lahman_csv.zip"

echo "Downloading Lahman CSV ZIP file..."
curl -fL "$LAHMAN_URL" -o "$ZIP"

echo "Extracting CSV files..."
unzip -o "$ZIP" -d "$OUT_DIR"

# Check if files were extracted into a subdirectory and move them up
SUB="$(find "$OUT_DIR" -mindepth 1 -maxdepth 1 -type d | head -n1 || true)"
if [ -n "$SUB" ]; then
    echo "Moving CSV files from subdirectory..."
    mv -f "$SUB"/*.csv "$OUT_DIR"/ 2>/dev/null || true
    rmdir "$SUB" 2>/dev/null || true
fi

echo ""
echo "✅ Lahman CSVs ready in $OUT_DIR"
echo ""
echo "Files downloaded:"
ls -lh "$OUT_DIR"/*.csv 2>/dev/null | awk '{print "  - " $9}' || echo "  (no CSV files found)"
