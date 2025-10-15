#!/usr/bin/env bash
# CS437 MLB Global Era - Download Baseball-Reference WAR Data
#
# Downloads Baseball-Reference WAR (Wins Above Replacement) data files
#
# Usage:
#   ./scripts/download_bref_war.sh [OUTPUT_DIR]
#
# Example:
#   ./scripts/download_bref_war.sh ~/mlb_data/bref_war

set -euo pipefail

OUT_DIR="${1:-$HOME/mlb_data/bref_war}"

echo "============================================"
echo "Downloading Baseball-Reference WAR Data"
echo "============================================"
echo "Output Directory: $OUT_DIR"
echo ""

mkdir -p "$OUT_DIR"

echo "Downloading WAR data for batters..."
curl -fL "https://www.baseball-reference.com/data/war_daily_bat.txt" -o "$OUT_DIR/war_daily_bat.csv"

echo "Downloading WAR data for pitchers..."
curl -fL "https://www.baseball-reference.com/data/war_daily_pitch.txt" -o "$OUT_DIR/war_daily_pitch.csv"

echo ""
echo "✅ WAR files saved in $OUT_DIR"
echo ""
echo "Files downloaded:"
ls -lh "$OUT_DIR"/*.csv 2>/dev/null | awk '{print "  - " $9 " (" $5 ")"}'
