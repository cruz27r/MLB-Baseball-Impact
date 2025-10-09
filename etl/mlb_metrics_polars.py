#!/usr/bin/env python3
"""
CS437 MLB Global Era - ETL Script with Polars

This script performs Extract, Transform, Load (ETL) operations for MLB data
using the Polars library for high-performance data processing.

Usage:
    python mlb_metrics_polars.py [--input INPUT_DIR] [--output OUTPUT_DIR]

Example:
    python mlb_metrics_polars.py --input ./raw_data --output ../mlb_out
"""

import argparse
import sys
from pathlib import Path
from datetime import datetime

try:
    import polars as pl
except ImportError:
    print("Error: Polars library not installed.")
    print("Install it with: pip install polars")
    sys.exit(1)


class MLBMetricsETL:
    """ETL pipeline for MLB metrics data processing."""
    
    def __init__(self, input_dir, output_dir):
        """
        Initialize the ETL pipeline.
        
        Args:
            input_dir (str): Directory containing raw MLB data files
            output_dir (str): Directory for processed output files
        """
        self.input_dir = Path(input_dir)
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(exist_ok=True, parents=True)
        
    def extract(self):
        """
        Extract data from source files.
        
        Returns:
            dict: Dictionary of DataFrames keyed by data type
        """
        print(f"[{datetime.now()}] Extracting data from {self.input_dir}")
        
        data = {}
        
        # Placeholder: Extract player data
        # data['players'] = pl.read_csv(self.input_dir / 'players.csv')
        
        # Placeholder: Extract statistics data
        # data['stats'] = pl.read_csv(self.input_dir / 'statistics.csv')
        
        # Placeholder: Extract awards data
        # data['awards'] = pl.read_csv(self.input_dir / 'awards.csv')
        
        print(f"Extracted {len(data)} datasets")
        return data
    
    def transform(self, data):
        """
        Transform and clean the data.
        
        Args:
            data (dict): Dictionary of raw DataFrames
            
        Returns:
            dict: Dictionary of transformed DataFrames
        """
        print(f"[{datetime.now()}] Transforming data")
        
        transformed = {}
        
        # Placeholder transformations:
        # 1. Filter for foreign players
        # 2. Calculate aggregated metrics
        # 3. Join datasets
        # 4. Create derived columns
        
        # Example transformation (when data is available):
        # if 'players' in data:
        #     transformed['foreign_players'] = (
        #         data['players']
        #         .filter(pl.col('birthCountry') != 'USA')
        #         .with_columns([
        #             pl.col('birthCountry').alias('country')
        #         ])
        #     )
        
        print(f"Transformed {len(transformed)} datasets")
        return transformed
    
    def load(self, data):
        """
        Load processed data to output files.
        
        Args:
            data (dict): Dictionary of transformed DataFrames
        """
        print(f"[{datetime.now()}] Loading data to {self.output_dir}")
        
        # Save each dataset as CSV and Parquet
        for name, df in data.items():
            csv_path = self.output_dir / f"{name}.csv"
            parquet_path = self.output_dir / f"{name}.parquet"
            
            # df.write_csv(csv_path)
            # df.write_parquet(parquet_path)
            
            print(f"  Saved {name} to {csv_path} and {parquet_path}")
        
        # Create summary report
        summary_path = self.output_dir / "etl_summary.txt"
        with open(summary_path, 'w') as f:
            f.write(f"ETL Summary - {datetime.now()}\n")
            f.write("=" * 50 + "\n\n")
            f.write(f"Input directory: {self.input_dir}\n")
            f.write(f"Output directory: {self.output_dir}\n")
            f.write(f"Datasets processed: {len(data)}\n")
            
        print(f"Summary saved to {summary_path}")
    
    def run(self):
        """Execute the full ETL pipeline."""
        print("=" * 60)
        print("MLB Metrics ETL Pipeline - Starting")
        print("=" * 60)
        
        try:
            # Extract
            raw_data = self.extract()
            
            # Transform
            transformed_data = self.transform(raw_data)
            
            # Load
            self.load(transformed_data)
            
            print("\n" + "=" * 60)
            print("ETL Pipeline Completed Successfully")
            print("=" * 60)
            
        except Exception as e:
            print(f"\nError during ETL process: {e}", file=sys.stderr)
            sys.exit(1)


def main():
    """Main entry point for the ETL script."""
    parser = argparse.ArgumentParser(
        description='MLB Metrics ETL Pipeline using Polars'
    )
    parser.add_argument(
        '--input',
        default='./raw_data',
        help='Input directory containing raw MLB data (default: ./raw_data)'
    )
    parser.add_argument(
        '--output',
        default='../mlb_out',
        help='Output directory for processed data (default: ../mlb_out)'
    )
    
    args = parser.parse_args()
    
    # Run ETL pipeline
    etl = MLBMetricsETL(args.input, args.output)
    etl.run()


if __name__ == '__main__':
    main()
