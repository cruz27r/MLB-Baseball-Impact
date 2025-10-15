#!/usr/bin/env python3
"""
CS437 MLB Global Era - Baseball-Reference WAR Data Ingestion

This script parses raw Baseball-Reference WAR data from the raw tables
and loads it into structured tables for analysis. It also builds a
crosswalk from B-Ref player IDs to Lahman player IDs.

Usage:
    python3 etl/ingest_bref_war.py [--db-name DB_NAME]

Prerequisites:
    - PostgreSQL with bref.war_bat_raw and bref.war_pitch_raw tables populated
    - psycopg2 or psycopg2-binary Python package
"""

import sys
import os
import argparse
import csv
from io import StringIO

# Database connection setup
try:
    import psycopg2
except ImportError:
    print("Error: psycopg2 package not found. Install with: pip install psycopg2-binary")
    sys.exit(1)


def get_db_connection(db_name='mlb'):
    """Create and return a database connection."""
    try:
        conn = psycopg2.connect(
            host=os.getenv('MLB_DB_HOST', os.getenv('DB_HOST', 'localhost')),
            database=db_name,
            user=os.getenv('MLB_DB_USER', os.getenv('DB_USER', 'postgres')),
            password=os.getenv('MLB_DB_PASSWORD', os.getenv('DB_PASSWORD', '')),
            port=os.getenv('MLB_DB_PORT', os.getenv('DB_PORT', '5432'))
        )
        return conn
    except Exception as e:
        print(f"Error connecting to database: {e}")
        sys.exit(1)


def parse_and_load_batting_war(conn):
    """Parse batting WAR data from raw table and load into structured table."""
    print("Processing batting WAR data...")
    
    cur = conn.cursor()
    
    # Get raw data
    cur.execute("SELECT line FROM bref.war_bat_raw")
    rows = cur.fetchall()
    
    if not rows:
        print("  ⚠ No batting WAR data found in raw table")
        return 0
    
    # Parse CSV data
    csv_data = '\n'.join([row[0] for row in rows])
    csv_reader = csv.DictReader(StringIO(csv_data))
    
    # Clear existing data
    cur.execute("TRUNCATE TABLE bref.war_bat")
    
    # Prepare insert statement
    insert_query = """
        INSERT INTO bref.war_bat (
            name_common, mlb_ID, playerid, yearid, team_ID, stint, lg_ID,
            PA, G, Inn, runs_bat, runs_br, runs_dp, runs_field,
            runs_infield, runs_outfield, runs_catcher, runs_good_plays,
            runs_defense, runs_position, runs_position_p, runs_replacement,
            runs_above_rep, runs_above_avg, runs_above_avg_off, runs_above_avg_def,
            WAA, WAA_off, WAA_def, WAR, WAR_def, WAR_off, WAR_rep,
            salary, pitcher, teamRpG, oppRpG, oppRpPA_rep, oppRpG_rep,
            pyth_exponent, pyth_exponent_rep, waa_win_perc, waa_win_perc_off,
            waa_win_perc_def, waa_win_perc_rep
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
            %s, %s, %s, %s, %s, %s, %s, %s, %s
        )
    """
    
    count = 0
    for row in csv_reader:
        try:
            # Convert numeric fields, handling empty strings
            def to_num(val, is_int=False):
                if val == '' or val is None:
                    return None
                try:
                    return int(val) if is_int else float(val)
                except ValueError:
                    return None
            
            values = (
                row.get('name_common', ''),
                row.get('mlb_ID', ''),
                row.get('player_ID', ''),
                to_num(row.get('year_ID'), True),
                row.get('team_ID', ''),
                to_num(row.get('stint'), True),
                row.get('lg_ID', ''),
                to_num(row.get('PA'), True),
                to_num(row.get('G'), True),
                to_num(row.get('Inn')),
                to_num(row.get('runs_bat')),
                to_num(row.get('runs_br')),
                to_num(row.get('runs_dp')),
                to_num(row.get('runs_field')),
                to_num(row.get('runs_infield')),
                to_num(row.get('runs_outfield')),
                to_num(row.get('runs_catcher')),
                to_num(row.get('runs_good_plays')),
                to_num(row.get('runs_defense')),
                to_num(row.get('runs_position')),
                to_num(row.get('runs_position_p')),
                to_num(row.get('runs_replacement')),
                to_num(row.get('runs_above_rep')),
                to_num(row.get('runs_above_avg')),
                to_num(row.get('runs_above_avg_off')),
                to_num(row.get('runs_above_avg_def')),
                to_num(row.get('WAA')),
                to_num(row.get('WAA_off')),
                to_num(row.get('WAA_def')),
                to_num(row.get('WAR')),
                to_num(row.get('WAR_def')),
                to_num(row.get('WAR_off')),
                to_num(row.get('WAR_rep')),
                to_num(row.get('salary')),
                row.get('pitcher', ''),
                to_num(row.get('teamRpG')),
                to_num(row.get('oppRpG')),
                to_num(row.get('oppRpPA_rep')),
                to_num(row.get('oppRpG_rep')),
                to_num(row.get('pyth_exponent')),
                to_num(row.get('pyth_exponent_rep')),
                to_num(row.get('waa_win_perc')),
                to_num(row.get('waa_win_perc_off')),
                to_num(row.get('waa_win_perc_def')),
                to_num(row.get('waa_win_perc_rep'))
            )
            
            cur.execute(insert_query, values)
            count += 1
            
        except Exception as e:
            print(f"  ⚠ Error processing row: {e}")
            continue
    
    conn.commit()
    print(f"  ✓ Loaded {count} batting WAR records")
    return count


def parse_and_load_pitching_war(conn):
    """Parse pitching WAR data from raw table and load into structured table."""
    print("Processing pitching WAR data...")
    
    cur = conn.cursor()
    
    # Get raw data
    cur.execute("SELECT line FROM bref.war_pitch_raw")
    rows = cur.fetchall()
    
    if not rows:
        print("  ⚠ No pitching WAR data found in raw table")
        return 0
    
    # Parse CSV data
    csv_data = '\n'.join([row[0] for row in rows])
    csv_reader = csv.DictReader(StringIO(csv_data))
    
    # Clear existing data
    cur.execute("TRUNCATE TABLE bref.war_pitch")
    
    # Prepare insert statement
    insert_query = """
        INSERT INTO bref.war_pitch (
            name_common, mlb_ID, playerid, yearid, team_ID, stint, lg_ID,
            G, GS, IPouts, IPouts_start, IPouts_relief, RA, xRA,
            xRA_sprp_adj, xRA_def_pitcher, PPF, PPF_custom, xRA_final,
            BIP, BIP_perc, salary, runs_above_avg, runs_above_avg_adj,
            runs_above_rep, RpO_replacement, GR_leverage_index_avg, WAR,
            salary_season, runs_above_avg_season, runs_above_avg_adj_season,
            runs_above_rep_season, WAR_season, teamRpG, oppRpG,
            pyth_exponent, waa_win_perc, WAA
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
            %s, %s
        )
    """
    
    count = 0
    for row in csv_reader:
        try:
            # Convert numeric fields, handling empty strings
            def to_num(val, is_int=False):
                if val == '' or val is None:
                    return None
                try:
                    return int(val) if is_int else float(val)
                except ValueError:
                    return None
            
            values = (
                row.get('name_common', ''),
                row.get('mlb_ID', ''),
                row.get('player_ID', ''),
                to_num(row.get('year_ID'), True),
                row.get('team_ID', ''),
                to_num(row.get('stint'), True),
                row.get('lg_ID', ''),
                to_num(row.get('G'), True),
                to_num(row.get('GS'), True),
                to_num(row.get('IPouts'), True),
                to_num(row.get('IPouts_start'), True),
                to_num(row.get('IPouts_relief'), True),
                to_num(row.get('RA')),
                to_num(row.get('xRA')),
                to_num(row.get('xRA_sprp_adj')),
                to_num(row.get('xRA_def_pitcher')),
                to_num(row.get('PPF')),
                to_num(row.get('PPF_custom')),
                to_num(row.get('xRA_final')),
                to_num(row.get('BIP')),
                to_num(row.get('BIP_perc')),
                to_num(row.get('salary')),
                to_num(row.get('runs_above_avg')),
                to_num(row.get('runs_above_avg_adj')),
                to_num(row.get('runs_above_rep')),
                to_num(row.get('RpO_replacement')),
                to_num(row.get('GR_leverage_index_avg')),
                to_num(row.get('WAR')),
                to_num(row.get('salary_season')),
                to_num(row.get('runs_above_avg_season')),
                to_num(row.get('runs_above_avg_adj_season')),
                to_num(row.get('runs_above_rep_season')),
                to_num(row.get('WAR_season')),
                to_num(row.get('teamRpG')),
                to_num(row.get('oppRpG')),
                to_num(row.get('pyth_exponent')),
                to_num(row.get('waa_win_perc')),
                to_num(row.get('WAA'))
            )
            
            cur.execute(insert_query, values)
            count += 1
            
        except Exception as e:
            print(f"  ⚠ Error processing row: {e}")
            continue
    
    conn.commit()
    print(f"  ✓ Loaded {count} pitching WAR records")
    return count


def load_id_overrides(conn):
    """Load manual ID overrides from config file."""
    overrides = {}
    config_path = 'config/id_overrides.csv'
    
    if not os.path.exists(config_path):
        print("  ℹ No id_overrides.csv found, skipping manual overrides")
        return overrides
    
    try:
        with open(config_path, 'r') as f:
            reader = csv.DictReader(f)
            for row in reader:
                bref_id = row.get('bref_id', '').strip()
                player_id = row.get('player_id', '').strip()
                if bref_id and player_id:
                    overrides[bref_id] = player_id
        print(f"  ✓ Loaded {len(overrides)} ID overrides")
    except Exception as e:
        print(f"  ⚠ Error loading ID overrides: {e}")
    
    return overrides


def build_player_crosswalk(conn):
    """Build crosswalk from B-Ref player IDs to Lahman player IDs."""
    print("Building player ID crosswalk...")
    
    cur = conn.cursor()
    overrides = load_id_overrides(conn)
    
    # Get all unique player IDs from WAR data
    cur.execute("""
        SELECT DISTINCT playerid, name_common, yearid
        FROM (
            SELECT playerid, name_common, yearid FROM bref.war_bat WHERE playerid IS NOT NULL
            UNION
            SELECT playerid, name_common, yearid FROM bref.war_pitch WHERE playerid IS NOT NULL
        ) t
        ORDER BY playerid, yearid
    """)
    
    bref_players = cur.fetchall()
    print(f"  Found {len(bref_players)} unique B-Ref player-seasons")
    
    # Create crosswalk mapping
    crosswalk = {}
    high_confidence = 0
    medium_confidence = 0
    low_confidence = 0
    no_match = 0
    
    for bref_id, name_common, year in bref_players:
        if not bref_id:
            continue
        
        # Check if already mapped
        if bref_id in crosswalk:
            continue
        
        # Check for manual override first
        if bref_id in overrides:
            crosswalk[bref_id] = (overrides[bref_id], 'override')
            high_confidence += 1
            continue
        
        # Try direct match in dw.players if bref_id column exists
        try:
            cur.execute("""
                SELECT player_id FROM dw.players 
                WHERE bref_id = %s LIMIT 1
            """, (bref_id,))
            result = cur.fetchone()
            if result:
                crosswalk[bref_id] = (result[0], 'high')
                high_confidence += 1
                continue
        except:
            pass
        
        # Try match via core.people bbref_id
        try:
            cur.execute("""
                SELECT player_id FROM core.people 
                WHERE bbref_id = %s LIMIT 1
            """, (bref_id,))
            result = cur.fetchone()
            if result:
                crosswalk[bref_id] = (result[0], 'high')
                high_confidence += 1
                continue
        except:
            pass
        
        # Try fuzzy match by name and birth year
        if name_common and year:
            try:
                # Parse name (usually "FirstName LastName")
                name_parts = name_common.strip().split()
                if len(name_parts) >= 2:
                    first_name = name_parts[0]
                    last_name = ' '.join(name_parts[1:])
                    
                    # Estimate birth year (assume debut around age 22-25)
                    est_birth_year_min = year - 30
                    est_birth_year_max = year - 18
                    
                    cur.execute("""
                        SELECT player_id FROM core.people 
                        WHERE LOWER(name_first) = LOWER(%s) 
                          AND LOWER(name_last) = LOWER(%s)
                          AND birth_year BETWEEN %s AND %s
                        LIMIT 1
                    """, (first_name, last_name, est_birth_year_min, est_birth_year_max))
                    
                    result = cur.fetchone()
                    if result:
                        crosswalk[bref_id] = (result[0], 'medium')
                        medium_confidence += 1
                        continue
            except:
                pass
        
        # No match found
        crosswalk[bref_id] = (None, 'none')
        no_match += 1
    
    print(f"  ✓ Built crosswalk with {len(crosswalk)} mappings:")
    print(f"    - High confidence: {high_confidence}")
    print(f"    - Medium confidence: {medium_confidence}")
    print(f"    - No match: {no_match}")
    
    return crosswalk


def update_war_with_crosswalk(conn, crosswalk):
    """Update WAR tables with Lahman player IDs from crosswalk."""
    print("Updating WAR tables with player ID crosswalk...")
    
    cur = conn.cursor()
    
    # Update batting WAR
    bat_updated = 0
    for bref_id, (player_id, confidence) in crosswalk.items():
        if player_id:
            try:
                cur.execute("""
                    UPDATE bref.war_bat 
                    SET playerid = %s 
                    WHERE playerid = %s
                """, (player_id, bref_id))
                bat_updated += cur.rowcount
            except:
                pass
    
    # Update pitching WAR
    pitch_updated = 0
    for bref_id, (player_id, confidence) in crosswalk.items():
        if player_id:
            try:
                cur.execute("""
                    UPDATE bref.war_pitch 
                    SET playerid = %s 
                    WHERE playerid = %s
                """, (player_id, bref_id))
                pitch_updated += cur.rowcount
            except:
                pass
    
    conn.commit()
    print(f"  ✓ Updated {bat_updated} batting WAR records")
    print(f"  ✓ Updated {pitch_updated} pitching WAR records")
    
    # Update dw.players with bref_id where missing
    if crosswalk:
        updated_players = 0
        try:
            cur.execute("SELECT 1 FROM information_schema.tables WHERE table_schema = 'dw' AND table_name = 'players'")
            if cur.fetchone():
                for bref_id, (player_id, confidence) in crosswalk.items():
                    if player_id and confidence in ('high', 'override'):
                        try:
                            cur.execute("""
                                UPDATE dw.players 
                                SET bref_id = %s 
                                WHERE player_id = %s AND (bref_id IS NULL OR bref_id = '')
                            """, (bref_id, player_id))
                            updated_players += cur.rowcount
                        except:
                            pass
                conn.commit()
                if updated_players > 0:
                    print(f"  ✓ Updated {updated_players} dw.players records with bref_id")
        except:
            pass


def main():
    """Main execution function."""
    parser = argparse.ArgumentParser(
        description='Parse and load Baseball-Reference WAR data into PostgreSQL'
    )
    parser.add_argument(
        '--db-name',
        default='mlb',
        help='Database name (default: mlb)'
    )
    args = parser.parse_args()
    
    print("=" * 50)
    print("Baseball-Reference WAR Data Ingestion")
    print("=" * 50)
    print(f"Database: {args.db_name}")
    print()
    
    # Connect to database
    conn = get_db_connection(args.db_name)
    
    try:
        # Parse and load batting WAR
        bat_count = parse_and_load_batting_war(conn)
        
        # Parse and load pitching WAR
        pitch_count = parse_and_load_pitching_war(conn)
        
        # Build player ID crosswalk
        crosswalk = build_player_crosswalk(conn)
        
        # Update WAR tables with crosswalk
        update_war_with_crosswalk(conn, crosswalk)
        
        print()
        print("=" * 50)
        print("✓ WAR Data Ingestion Complete")
        print("=" * 50)
        print(f"Total batting records: {bat_count}")
        print(f"Total pitching records: {pitch_count}")
        print(f"Player ID mappings: {len([x for x in crosswalk.values() if x[0]])}")
        print()
        
    except Exception as e:
        print(f"Error during ingestion: {e}")
        conn.rollback()
        sys.exit(1)
    finally:
        conn.close()


if __name__ == '__main__':
    main()
