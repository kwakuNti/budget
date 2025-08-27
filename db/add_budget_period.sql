-- Add budget_period column to budget_categories table
-- This allows users to specify if their budget limit is set weekly or monthly


-- Add budget_period column to budget_categories table
ALTER TABLE budget_categories 
ADD COLUMN budget_period ENUM('weekly', 'monthly') DEFAULT 'monthly' AFTER budget_limit;

-- Add original_budget_limit to store the user's original input
ALTER TABLE budget_categories 
ADD COLUMN original_budget_limit DECIMAL(10,2) DEFAULT 0.00 AFTER budget_period;

-- Update existing records to have the original limit same as current limit
UPDATE budget_categories 
SET original_budget_limit = budget_limit, budget_period = 'monthly' 
WHERE original_budget_limit = 0.00;

-- Add comment to table for documentation
ALTER TABLE budget_categories 
COMMENT = 'Budget categories with weekly/monthly period support. budget_limit is always stored as monthly for calculations, original_budget_limit stores user input';
