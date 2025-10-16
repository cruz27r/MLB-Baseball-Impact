#!/usr/bin/env python3
"""
MLB Analysis Script
Executes SQL queries from analysis/sql/ and generates CSV reports and PNG charts.
"""

import os
import sys
import glob
from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt
import matplotlib
matplotlib.use('Agg')  # Non-interactive backend

try:
    import psycopg2
    from psycopg2 import sql
except ImportError:
    print("ERROR: psycopg2 not installed. Run: pip install psycopg2-binary")
    sys.exit(1)

# Optional: support .env files
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    pass  # dotenv is optional


def get_db_connection():
    """Create database connection from environment variables."""
    db_params = {
        'host': os.getenv('MLB_DB_HOST', 'localhost'),
        'port': os.getenv('MLB_DB_PORT', '5432'),
        'user': os.getenv('MLB_DB_USER', os.getenv('USER', 'postgres')),
        'password': os.getenv('MLB_DB_PASS', ''),
        'database': os.getenv('MLB_DB_NAME', 'mlb')
    }
    
    try:
        conn = psycopg2.connect(**db_params)
        return conn
    except psycopg2.Error as e:
        print(f"ERROR: Unable to connect to database: {e}")
        print(f"Connection params: host={db_params['host']}, port={db_params['port']}, "
              f"database={db_params['database']}, user={db_params['user']}")
        sys.exit(1)


def execute_sql_file(conn, sql_file_path, output_csv_path):
    """Execute SQL file and save results to CSV."""
    print(f"Processing {sql_file_path.name}...")
    
    with open(sql_file_path, 'r') as f:
        query = f.read()
    
    try:
        df = pd.read_sql_query(query, conn)
        
        if df.empty:
            print(f"  ⚠️  Query returned no rows: {sql_file_path.name}")
            return None
        
        df.to_csv(output_csv_path, index=False)
        print(f"  ✓ Saved {len(df)} rows to {output_csv_path.name}")
        return df
    
    except Exception as e:
        print(f"  ⚠️  Error executing {sql_file_path.name}: {e}")
        return None


def generate_composition_chart(csv_path, output_png):
    """Generate composition share line chart."""
    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(f"  ⚠️  No data for composition chart")
            return
        
        plt.figure(figsize=(16, 9))
        plt.plot(df['year'], df['us_share'], label='USA', linewidth=2, marker='o', markersize=3)
        plt.plot(df['year'], df['latin_share'], label='Latin America', linewidth=2, marker='s', markersize=3)
        plt.plot(df['year'], df['other_share'], label='Other', linewidth=2, marker='^', markersize=3)
        
        plt.xlabel('Year', fontsize=12)
        plt.ylabel('Roster Share (%)', fontsize=12)
        plt.title('MLB Player Composition by Origin Over Time', fontsize=14, fontweight='bold')
        plt.legend(fontsize=11)
        plt.grid(True, alpha=0.3)
        plt.tight_layout()
        plt.savefig(output_png, dpi=100)
        plt.close()
        print(f"  ✓ Generated {output_png.name}")
    except Exception as e:
        print(f"  ⚠️  Error generating composition chart: {e}")


def generate_war_vs_roster_chart(war_csv, impact_csv, output_png):
    """Generate WAR share vs roster share comparison chart."""
    try:
        war_df = pd.read_csv(war_csv)
        impact_df = pd.read_csv(impact_csv)
        
        if war_df.empty or impact_df.empty:
            print(f"  ⚠️  No data for WAR vs roster chart")
            return
        
        # Merge on year and origin_group
        merged = pd.merge(war_df, impact_df[['year', 'origin_group', 'roster_share']], 
                         on=['year', 'origin_group'], how='left')
        
        fig, axes = plt.subplots(1, 3, figsize=(16, 9))
        origins = ['USA', 'Latin', 'Other']
        
        for idx, origin in enumerate(origins):
            data = merged[merged['origin_group'] == origin]
            if not data.empty:
                ax = axes[idx]
                ax.plot(data['year'], data['war_share'], label='WAR Share', 
                       linewidth=2, marker='o', markersize=3)
                ax.plot(data['year'], data['roster_share'], label='Roster Share', 
                       linewidth=2, marker='s', markersize=3)
                ax.set_xlabel('Year', fontsize=10)
                ax.set_ylabel('Share (%)', fontsize=10)
                ax.set_title(f'{origin}', fontsize=12, fontweight='bold')
                ax.legend(fontsize=9)
                ax.grid(True, alpha=0.3)
        
        fig.suptitle('WAR Share vs Roster Share by Origin', fontsize=14, fontweight='bold')
        plt.tight_layout()
        plt.savefig(output_png, dpi=100)
        plt.close()
        print(f"  ✓ Generated {output_png.name}")
    except Exception as e:
        print(f"  ⚠️  Error generating WAR vs roster chart: {e}")


def generate_impact_index_chart(csv_path, output_png):
    """Generate Impact Index line chart."""
    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(f"  ⚠️  No data for impact index chart")
            return
        
        plt.figure(figsize=(16, 9))
        
        for origin in ['USA', 'Latin', 'Other']:
            data = df[df['origin_group'] == origin]
            if not data.empty:
                plt.plot(data['year'], data['impact_index'], label=origin, 
                        linewidth=2, marker='o', markersize=3)
        
        plt.axhline(y=1.0, color='black', linestyle='--', linewidth=1, alpha=0.5, 
                   label='Baseline (1.0)')
        plt.xlabel('Year', fontsize=12)
        plt.ylabel('Impact Index (WAR Share / Roster Share)', fontsize=12)
        plt.title('Impact Index by Origin Over Time', fontsize=14, fontweight='bold')
        plt.legend(fontsize=11)
        plt.grid(True, alpha=0.3)
        plt.tight_layout()
        plt.savefig(output_png, dpi=100)
        plt.close()
        print(f"  ✓ Generated {output_png.name}")
    except Exception as e:
        print(f"  ⚠️  Error generating impact index chart: {e}")


def generate_awards_chart(csv_path, output_png):
    """Generate awards share multi-series line chart."""
    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(f"  ⚠️  No data for awards chart")
            return
        
        fig, axes = plt.subplots(2, 2, figsize=(16, 9))
        awards_types = [('mvp', 'MVP'), ('cy', 'Cy Young'), ('roy', 'Rookie of Year'), 
                       ('allstar_total', 'All-Star')]
        
        for idx, (col, title) in enumerate(awards_types):
            ax = axes[idx // 2, idx % 2]
            
            for origin in ['USA', 'Latin', 'Other']:
                data = df[df['origin_group'] == origin]
                if not data.empty and col in data.columns:
                    ax.plot(data['year'], data[col], label=origin, 
                           linewidth=2, marker='o', markersize=3)
            
            ax.set_xlabel('Year', fontsize=10)
            ax.set_ylabel('Count', fontsize=10)
            ax.set_title(f'{title} Awards', fontsize=11, fontweight='bold')
            ax.legend(fontsize=9)
            ax.grid(True, alpha=0.3)
        
        fig.suptitle('Awards Distribution by Origin', fontsize=14, fontweight='bold')
        plt.tight_layout()
        plt.savefig(output_png, dpi=100)
        plt.close()
        print(f"  ✓ Generated {output_png.name}")
    except Exception as e:
        print(f"  ⚠️  Error generating awards chart: {e}")


def generate_championship_chart(csv_path, output_png):
    """Generate championship contribution chart."""
    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(f"  ⚠️  No data for championship chart")
            return
        
        fig, axes = plt.subplots(1, 2, figsize=(16, 9))
        
        # Contenders
        for origin in ['USA', 'Latin', 'Other']:
            data = df[df['origin_group'] == origin]
            if not data.empty:
                axes[0].plot(data['year'], data['war_on_contenders'], label=origin, 
                           linewidth=2, marker='o', markersize=3)
        
        axes[0].set_xlabel('Year', fontsize=11)
        axes[0].set_ylabel('Total WAR', fontsize=11)
        axes[0].set_title('WAR on Contending Teams', fontsize=12, fontweight='bold')
        axes[0].legend(fontsize=10)
        axes[0].grid(True, alpha=0.3)
        
        # Champions
        for origin in ['USA', 'Latin', 'Other']:
            data = df[df['origin_group'] == origin]
            if not data.empty:
                axes[1].plot(data['year'], data['war_on_champions'], label=origin, 
                           linewidth=2, marker='s', markersize=3)
        
        axes[1].set_xlabel('Year', fontsize=11)
        axes[1].set_ylabel('Total WAR', fontsize=11)
        axes[1].set_title('WAR on Championship Teams', fontsize=12, fontweight='bold')
        axes[1].legend(fontsize=10)
        axes[1].grid(True, alpha=0.3)
        
        fig.suptitle('Championship Contribution by Origin', fontsize=14, fontweight='bold')
        plt.tight_layout()
        plt.savefig(output_png, dpi=100)
        plt.close()
        print(f"  ✓ Generated {output_png.name}")
    except Exception as e:
        print(f"  ⚠️  Error generating championship chart: {e}")


def generate_salary_efficiency_chart(csv_path, output_png):
    """Generate salary efficiency chart (optional)."""
    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(f"  ℹ️  No salary data available - skipping salary efficiency chart")
            return
        
        plt.figure(figsize=(16, 9))
        
        for origin in ['USA', 'Latin', 'Other']:
            data = df[df['origin_group'] == origin]
            if not data.empty:
                plt.plot(data['year'], data['avg_cost_per_war'], label=origin, 
                        linewidth=2, marker='o', markersize=3)
        
        plt.xlabel('Year', fontsize=12)
        plt.ylabel('Avg Cost per WAR (USD)', fontsize=12)
        plt.title('Salary Efficiency by Origin (Average Cost per WAR)', fontsize=14, fontweight='bold')
        plt.legend(fontsize=11)
        plt.grid(True, alpha=0.3)
        plt.tight_layout()
        plt.savefig(output_png, dpi=100)
        plt.close()
        print(f"  ✓ Generated {output_png.name}")
    except FileNotFoundError:
        print(f"  ℹ️  Salary data not available - skipping salary efficiency chart")
    except Exception as e:
        print(f"  ⚠️  Error generating salary efficiency chart: {e}")


def main():
    """Main execution function."""
    print("=" * 70)
    print("MLB Analysis Pipeline")
    print("=" * 70)
    
    # Setup paths
    script_dir = Path(__file__).parent
    sql_dir = script_dir / "sql"
    out_dir = script_dir / "out"
    
    # Ensure output directory exists
    out_dir.mkdir(exist_ok=True)
    
    # Connect to database
    print(f"\nConnecting to database...")
    conn = get_db_connection()
    print(f"✓ Connected successfully")
    
    # Execute SQL files and generate CSVs
    print(f"\n{'=' * 70}")
    print("Executing SQL queries and generating CSVs...")
    print(f"{'=' * 70}\n")
    
    sql_files = sorted(sql_dir.glob("*.sql"))
    csv_outputs = []
    
    for sql_file in sql_files:
        output_csv = out_dir / sql_file.name.replace('.sql', '.csv')
        df = execute_sql_file(conn, sql_file, output_csv)
        csv_outputs.append((sql_file.stem, output_csv, df))
    
    conn.close()
    
    # Generate charts
    print(f"\n{'=' * 70}")
    print("Generating PNG charts...")
    print(f"{'=' * 70}\n")
    
    # Chart 1: Composition
    composition_csv = out_dir / "01_composition.csv"
    if composition_csv.exists():
        generate_composition_chart(composition_csv, out_dir / "composition_share.png")
    
    # Chart 2: WAR vs Roster Share
    war_csv = out_dir / "02_war_share.csv"
    impact_csv = out_dir / "03_impact_index.csv"
    if war_csv.exists() and impact_csv.exists():
        generate_war_vs_roster_chart(war_csv, impact_csv, 
                                     out_dir / "war_share_vs_roster_share.png")
    
    # Chart 3: Impact Index
    if impact_csv.exists():
        generate_impact_index_chart(impact_csv, out_dir / "impact_index.png")
    
    # Chart 4: Awards
    awards_csv = out_dir / "04_awards_share.csv"
    if awards_csv.exists():
        generate_awards_chart(awards_csv, out_dir / "awards_share.png")
    
    # Chart 5: Championship Contribution
    championship_csv = out_dir / "05_championship_contrib.csv"
    if championship_csv.exists():
        generate_championship_chart(championship_csv, 
                                    out_dir / "championship_contrib.png")
    
    # Chart 6: Salary Efficiency (optional)
    salary_csv = out_dir / "06_salary_efficiency.csv"
    if salary_csv.exists():
        generate_salary_efficiency_chart(salary_csv, 
                                        out_dir / "salary_efficiency.png")
    
    # Summary
    print(f"\n{'=' * 70}")
    print("Summary")
    print(f"{'=' * 70}\n")
    
    print("CSV Files Generated:")
    for name, csv_path, df in csv_outputs:
        if csv_path.exists():
            print(f"  ✓ {csv_path}")
    
    print("\nPNG Charts Generated:")
    for png_file in sorted(out_dir.glob("*.png")):
        print(f"  ✓ {png_file}")
    
    print(f"\n{'=' * 70}")
    print("✓ Analysis complete!")
    print(f"{'=' * 70}\n")
    print(f"All outputs saved to: {out_dir}")


if __name__ == "__main__":
    main()
