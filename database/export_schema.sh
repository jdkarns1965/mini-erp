#!/bin/bash
# Export database schema for sharing with other developers

DB_NAME="mini_erp"
OUTPUT_FILE="current_schema.sql"

echo "Exporting database schema..."

# Export structure only (no data)
mysqldump -u mini_erp_user -p --no-data --routines --triggers $DB_NAME > $OUTPUT_FILE

echo "✓ Schema exported to $OUTPUT_FILE"
echo "Other developers can import this with:"
echo "mysql -u mini_erp_user -p mini_erp < $OUTPUT_FILE"