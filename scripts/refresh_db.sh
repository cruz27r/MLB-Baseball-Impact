#!/bin/bash
# CS437 MLB Global Era - Database Refresh Script
# 
# This script runs all SQL files in order to set up and populate the MLB database.
#
# Usage:
#   ./scripts/refresh_db.sh [database_name]
#
# Example:
#   ./scripts/refresh_db.sh mlb
#
# Prerequisites:
#   - PostgreSQL must be installed and running
#   - Database must already be created (createdb mlb)
#   - Lahman CSV files in ~/mlb_data/lahman/ directory
#   - WAR CSV files in ~/mlb_data/bref_war/ directory (optional)

set -euo pipefail

# Get database name from argument or use default
DB_NAME="${1:-mlb}"

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_DIR="$SCRIPT_DIR/../sql"
ETL_DIR="$SCRIPT_DIR/../etl"

echo "============================================"
echo "MLB Database Refresh Script"
echo "============================================"
echo "Database: $DB_NAME"
echo "SQL Directory: $SQL_DIR"
echo ""

# Check if database exists
if ! psql -lqt | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
    echo "Error: Database '$DB_NAME' does not exist."
    echo "Please create it first with: createdb $DB_NAME"
    exit 1
fi

echo "Step 1: Creating database schema and tables..."
psql -d "$DB_NAME" -f "$SQL_DIR/01_create_db.sql"
echo "✓ Schema and tables created"
echo ""

echo "Step 2: Loading Lahman data from CSV files..."
if [ -d "$HOME/mlb_data/lahman" ]; then
    psql -d "$DB_NAME" -f "$SQL_DIR/02_load_lahman.sql"
    echo "✓ Lahman data loaded"
else
    echo "⚠ Warning: Directory ~/mlb_data/lahman not found. Skipping Lahman data load."
    echo "  To load data, create the directory and place Lahman CSV files there."
fi
echo ""

echo "Step 3: Loading Baseball-Reference WAR data..."
if [ -d "$HOME/mlb_data/bref_war" ]; then
    psql -d "$DB_NAME" -f "$SQL_DIR/03_load_bref_war.sql"
    echo "✓ WAR raw tables created and loaded"
    
    echo ""
    echo "Step 3a: Parsing and ingesting WAR data..."
    python3 "$ETL_DIR/ingest_bref_war.py" --db-name "$DB_NAME"
    echo "✓ WAR data parsed and ingested"
else
    echo "⚠ Warning: Directory ~/mlb_data/bref_war not found. Skipping WAR data load."
    echo "  To load WAR data, run: ./scripts/download_bref_war.sh"
fi
echo ""

echo "Step 4: Creating analysis views and materialized views..."
psql -d "$DB_NAME" -f "$SQL_DIR/04_views_analysis.sql"
echo "✓ Analysis views created"
echo ""

echo "Step 5: Creating awards and leaders views..."
psql -d "$DB_NAME" -f "$SQL_DIR/05_awards_and_leaders.sql" 2>/dev/null || echo "⚠ Some awards views may not be available yet"
echo ""

echo "Step 6: Creating WAR analysis views..."
if [ -d "$HOME/mlb_data/bref_war" ]; then
    psql -d "$DB_NAME" -f "$SQL_DIR/06_war_by_origin.sql"
    echo "✓ WAR analysis views created"
else
    echo "⚠ Skipping WAR analysis views (no WAR data available)"
fi
echo ""

echo "Step 7: Loading existing materialized views (if views.sql exists)..."
if [ -f "$SQL_DIR/views.sql" ]; then
    psql -d "$DB_NAME" -f "$SQL_DIR/views.sql" 2>/dev/null || echo "⚠ views.sql had errors (may be expected if tables don't match)"
fi
echo ""

echo "============================================"
echo "✅ Database refresh complete!"
echo "============================================"
echo ""
echo "Next steps:"
echo "  1. Verify data: psql -d $DB_NAME -c 'SELECT COUNT(*) FROM core.people;'"
echo "  2. Check WAR data: psql -d $DB_NAME -c 'SELECT COUNT(*) FROM bref.war_bat;'"
echo "  3. View Impact Index: psql -d $DB_NAME -c 'SELECT * FROM core.mv_impact_index LIMIT 10;'"
echo "  4. Start web server: php -S localhost:8080 -t public"
echo ""
