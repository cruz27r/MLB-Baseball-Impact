#!/usr/bin/env bash
# CS437 MLB Global Era - Update All Script
#
# This script orchestrates downloading all data sources and refreshing the database.
# It handles SABR Lahman, Retrosheet, and Baseball-Reference WAR data.
#
# Usage:
#   DB=mlb LAHMAN_URL="<url>" RETRO_URL="<url>" ./scripts/update_all.sh
#
# Environment Variables:
#   DB          - Database name (default: mlb)
#   LAHMAN_URL  - URL to Lahman CSV ZIP file (optional)
#   RETRO_URL   - URL to Retrosheet CSV ZIP file (optional)
#
# Example:
#   LAHMAN_URL="https://sabr.org/path/to/lahman.zip" \
#   RETRO_URL="https://retrosheet.org/path/to/retrosheet.zip" \
#   DB=mlb ./scripts/update_all.sh

set -euo pipefail

# Configuration from environment or defaults
DB="${DB:-mlb}"
LAHMAN_URL="${LAHMAN_URL:-}"
RETRO_URL="${RETRO_URL:-}"

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "============================================"
echo "MLB Database Update - All Sources"
echo "============================================"
echo "Database: $DB"
echo ""

# Create data directories
echo "Creating data directories..."
mkdir -p ~/mlb_data/{lahman,retrosheet,bref_war}
echo "✓ Directories created"
echo ""

# Download Lahman data if URL provided
if [ -n "$LAHMAN_URL" ]; then
    echo "Downloading SABR Lahman data..."
    "$SCRIPT_DIR/download_lahman_sabr.sh" "$LAHMAN_URL"
    echo ""
else
    echo "⚠ No LAHMAN_URL provided, skipping Lahman download"
    echo "  Set LAHMAN_URL environment variable to download"
    echo ""
fi

# Download Retrosheet data if URL provided
if [ -n "$RETRO_URL" ]; then
    echo "Downloading Retrosheet data..."
    "$SCRIPT_DIR/download_retrosheet.sh" "$RETRO_URL"
    echo ""
else
    echo "⚠ No RETRO_URL provided, skipping Retrosheet download"
    echo "  Set RETRO_URL environment variable to download"
    echo ""
fi

# Always download Baseball-Reference WAR data (public API)
echo "Downloading Baseball-Reference WAR data..."
"$SCRIPT_DIR/download_bref_war.sh"
echo ""

# Refresh database with all available data
echo "Refreshing database..."
"$SCRIPT_DIR/refresh_db.sh" "$DB"
echo ""

echo "============================================"
echo "✅ Update Complete!"
echo "============================================"
echo ""
echo "Database '$DB' has been updated with:"
if [ -n "$LAHMAN_URL" ]; then
    echo "  ✓ SABR Lahman data"
else
    echo "  - SABR Lahman data (skipped)"
fi
if [ -n "$RETRO_URL" ]; then
    echo "  ✓ Retrosheet data"
else
    echo "  - Retrosheet data (skipped)"
fi
echo "  ✓ Baseball-Reference WAR data"
echo ""
echo "Next steps:"
echo "  1. Verify: psql -d $DB -c 'SELECT COUNT(*) FROM core.people;'"
echo "  2. View Impact Index: psql -d $DB -c 'SELECT * FROM core.mv_impact_index LIMIT 10;'"
echo "  3. Start server: php -S localhost:8080 -t public"
echo ""
