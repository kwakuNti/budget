-- Fix budget allocation precision issue
-- This script modifies the computed columns to use ROUND() function for proper decimal precision

USE budget;

-- Drop the existing computed columns and recreate them with ROUND() function
ALTER TABLE personal_budget_allocation 
DROP COLUMN needs_amount,
DROP COLUMN wants_amount,
DROP COLUMN savings_amount;

-- Add the columns back with proper rounding
ALTER TABLE personal_budget_allocation 
ADD COLUMN needs_amount DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(monthly_salary * needs_percentage / 100, 2)) STORED,
ADD COLUMN wants_amount DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(monthly_salary * wants_percentage / 100, 2)) STORED,
ADD COLUMN savings_amount DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(monthly_salary * savings_percentage / 100, 2)) STORED;

-- Log the fix
INSERT INTO system_settings (setting_key, setting_value, description) 
VALUES ('budget_precision_fixed', '1', 'Budget allocation precision issue has been fixed')
ON DUPLICATE KEY UPDATE 
    setting_value = '1', 
    description = 'Budget allocation precision issue has been fixed',
    updated_at = CURRENT_TIMESTAMP;

SELECT 'Budget allocation precision issue has been fixed!' as status,
       'Computed columns now use ROUND() function for proper decimal precision' as details;
