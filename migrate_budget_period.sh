#!/bin/bash

# Budget Period Migration Script
# This script adds the budget_period and original_budget_limit columns to the budget_categories table

echo "Starting Budget Period Migration..."

# Check if MySQL is running
if ! pgrep -x "mysqld" > /dev/null; then
    echo "MySQL is not running. Please start MySQL and try again."
    exit 1
fi

# Run the migration SQL file
mysql -u root -p budget < db/add_budget_period.sql

if [ $? -eq 0 ]; then
    echo "✓ Budget Period Migration completed successfully!"
    echo "✓ Added budget_period column (ENUM: 'weekly', 'monthly')"
    echo "✓ Added original_budget_limit column"
    echo "✓ Updated existing records with default values"
    echo ""
    echo "The budget system now supports:"
    echo "- Weekly budget limits (converted to monthly for storage)"
    echo "- Monthly budget limits"
    echo "- Original user input preservation"
    echo "- Period indicators in UI"
else
    echo "✗ Migration failed. Please check the error messages above."
    exit 1
fi
