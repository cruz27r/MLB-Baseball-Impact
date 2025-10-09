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

set -e  # Exit on error

# Get database name from argument or use default
DB_NAME="${1:-mlb}"

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_DIR="$SCRIPT_DIR/../sql"

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
    echo "⚠ Warning: Directory ~/mlb_data/lahman not found. Skipping data load."
    echo "  To load data, create the directory and place Lahman CSV files there."
fi
echo ""

echo "Step 3: Creating analysis views and materialized views..."
psql -d "$DB_NAME" -f "$SQL_DIR/04_views_analysis.sql"
echo "✓ Analysis views created"
echo ""

echo "Step 4: Loading existing materialized views (if views.sql exists)..."
if [ -f "$SQL_DIR/views.sql" ]; then
    psql -d "$DB_NAME" -f "$SQL_DIR/views.sql" 2>/dev/null || echo "⚠ views.sql had errors (may be expected if tables don't match)"
fi
echo ""

echo "============================================"
echo "Database refresh complete!"
echo "============================================"
echo ""
echo "Next steps:"
echo "  1. Verify data: psql -d $DB_NAME -c 'SELECT COUNT(*) FROM core.people;'"
echo "  2. Start web server: php -S localhost:8080 -t public"
echo ""
