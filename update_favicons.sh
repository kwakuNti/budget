#!/bin/bash

# Script to update favicon references in all template files
# This script adds the favicon include to all PHP template files

echo "Starting favicon update for all template files..."

# Array of template files to update
template_files=(
    "templates/analytics.php"
    "templates/budget.php"
    "templates/contribution.php"
    "templates/cycle.php"
    "templates/feedback.php"
    "templates/feedback-admin.php"
    "templates/goal_tracker.php"
    "templates/insights.php"
    "templates/insights_new.php"
    "templates/insights_backup.php"
    "templates/personal-expense.php"
    "templates/report.php"
    "templates/reset-password.php"
    "templates/savings.php"
    "templates/summary.php"
    "templates/test_walkthrough.php"
    "templates/test_walkthrough_session.php"
)

# Function to update favicon in a file
update_favicon() {
    local file="$1"
    if [ -f "$file" ]; then
        echo "Updating favicon in: $file"
        
        # Check if file contains DOCTYPE html
        if grep -q "<!DOCTYPE html>" "$file"; then
            # Check if favicon is already included
            if ! grep -q "favicon.php" "$file"; then
                # Find the line with <title> and add favicon include after it
                sed -i '' '/^[[:space:]]*<title>/a\
    <?php include '"'"'../includes/favicon.php'"'"'; ?>
' "$file"
                echo "✓ Updated: $file"
            else
                echo "⚠ Favicon already included in: $file"
            fi
        else
            echo "⚠ No DOCTYPE found in: $file (might not be an HTML file)"
        fi
    else
        echo "✗ File not found: $file"
    fi
}

# Update each template file
for file in "${template_files[@]}"; do
    update_favicon "$file"
done

echo ""
echo "Favicon update completed!"
echo "Don't forget to manually check files that may need special handling."
