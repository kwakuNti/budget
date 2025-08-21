-- ============================================================================
-- Personal Budget System - Database Updates
-- ============================================================================
-- This file ---- ============================================================================
-- 7. FINANCIAL SUMMARY VIEW
-- =====================================================================================================================================================
-- 6. SAVINGS/EXPENSE SEPARATION FIXES  
-- ============================================================================tains all database schema changes for the personal budget system
-- Run this file after the initial budget.sql setup to add personal features
-- ============================================================================

-- ============================================================================
-- 1. PERSONAL GOALS TABLE ENHANCEMENTS
-- ============================================================================

-- Add status column to personal_goals table if it doesn't exist
ALTER TABLE personal_goals 
ADD COLUMN status ENUM('active', 'paused', 'inactive') DEFAULT 'active' AFTER priority;

-- Add budget_category_id column to link goals with budget categories
ALTER TABLE personal_goals 
ADD COLUMN budget_category_id INT NULL AFTER target_date;

-- Add foreign key constraint
ALTER TABLE personal_goals 
ADD CONSTRAINT fk_personal_goals_budget_category 
    FOREIGN KEY (budget_category_id) REFERENCES budget_categories(id) ON DELETE SET NULL;

-- Add additional columns for enhanced goal management
ALTER TABLE personal_goals
ADD COLUMN auto_save_enabled BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN save_method ENUM('percentage', 'fixed', 'manual') DEFAULT 'manual' AFTER auto_save_enabled,
ADD COLUMN save_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER save_method,
ADD COLUMN save_amount DECIMAL(10,2) DEFAULT 0.00 AFTER save_percentage,
ADD COLUMN deduct_from_income BOOLEAN DEFAULT FALSE AFTER save_amount;

-- Update existing goals to have 'active' status
UPDATE personal_goals 
SET status = 'active' 
WHERE status IS NULL;

-- ============================================================================
-- 2. FIX GOAL_TYPE ENUM VALUES
-- ============================================================================

-- Expand goal_type ENUM to include more options
ALTER TABLE personal_goals 
MODIFY COLUMN goal_type ENUM(
    'emergency_fund', 
    'vacation', 
    'car', 
    'house', 
    'education', 
    'retirement',
    'investment',
    'debt_payoff',
    'business',
    'technology',
    'health',
    'entertainment',
    'shopping',
    'travel',
    'wedding',
    'other'
) DEFAULT 'other';

-- ============================================================================
-- 5. BUDGET SYSTEM PRECISION AND CURRENCY FIXES
-- ============================================================================

CREATE TABLE IF NOT EXISTS personal_goal_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    goal_id INT NOT NULL,
    auto_save_enabled BOOLEAN DEFAULT FALSE,
    save_method ENUM('percentage', 'fixed', 'manual') DEFAULT 'manual',
    save_percentage DECIMAL(5,2) DEFAULT 0.00, -- Percentage of income
    save_amount DECIMAL(10,2) DEFAULT 0.00, -- Fixed amount
    deduct_from_income BOOLEAN DEFAULT FALSE, -- Whether to deduct from available income
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES personal_goals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_goal_settings (goal_id)
);

-- ============================================================================
-- 3. PERSONAL GOAL CONTRIBUTIONS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS personal_goal_contributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    contribution_date DATE NOT NULL,
    source ENUM('manual', 'auto_save', 'round_up', 'bonus') DEFAULT 'manual',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES personal_goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_goal_contributions_date (goal_id, contribution_date),
    INDEX idx_user_contributions (user_id, contribution_date)
);

-- ============================================================================
-- 4. AUTO-SAVE HISTORY TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS personal_auto_save_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    salary_date DATE NOT NULL,
    salary_amount DECIMAL(12,2) NOT NULL,
    total_auto_saved DECIMAL(10,2) NOT NULL,
    remaining_after_saves DECIMAL(12,2) NOT NULL,
    goals_processed TEXT, -- Store which goals were processed and amounts (JSON format)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_auto_save_history_user_date (user_id, salary_date)
);

-- ============================================================================
-- 5. WEEKLY CHALLENGES TABLE (for savings challenges feature)
-- ============================================================================

CREATE TABLE IF NOT EXISTS personal_weekly_challenges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    challenge_type ENUM('save_amount', 'no_spend', 'reduce_category', 'round_up') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_amount DECIMAL(10,2) DEFAULT 0.00,
    current_amount DECIMAL(10,2) DEFAULT 0.00,
    target_category_id INT NULL, -- For category-specific challenges
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'failed', 'abandoned') DEFAULT 'active',
    reward_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_category_id) REFERENCES budget_categories(id) ON DELETE SET NULL,
    INDEX idx_user_challenges_date (user_id, start_date, end_date),
    INDEX idx_challenge_status (status, end_date)
);

-- ============================================================================
-- 6. CHALLENGE PROGRESS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS personal_challenge_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    progress_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES personal_weekly_challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_challenge_progress_date (challenge_id, progress_date)
);

-- ============================================================================
-- 7. AUTOSAVINGS CONFIGURATION TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS personal_autosave_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    enabled BOOLEAN DEFAULT FALSE,
    save_frequency ENUM('weekly', 'biweekly', 'monthly') DEFAULT 'monthly',
    save_day INT DEFAULT 1, -- Day of week (1-7) or day of month (1-31)
    round_up_enabled BOOLEAN DEFAULT FALSE,
    round_up_threshold DECIMAL(10,2) DEFAULT 5.00, -- Round up to nearest X amount
    emergency_fund_priority BOOLEAN DEFAULT TRUE,
    emergency_fund_target DECIMAL(10,2) DEFAULT 1000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_autosave (user_id)
);

-- ============================================================================
-- 8. DATA INITIALIZATION
-- ============================================================================

-- Insert default settings for existing goals
INSERT IGNORE INTO personal_goal_settings (goal_id, auto_save_enabled, save_method, deduct_from_income)
SELECT 
    id, 
    FALSE, 
    'manual', 
    FALSE
FROM personal_goals 
WHERE id NOT IN (SELECT goal_id FROM personal_goal_settings);

-- Insert default autosave configuration for existing users
INSERT IGNORE INTO personal_autosave_config (user_id, enabled, save_frequency, emergency_fund_target)
SELECT 
    id,
    FALSE,
    'monthly',
    1000.00
FROM users 
WHERE id NOT IN (SELECT user_id FROM personal_autosave_config);

-- ============================================================================
-- 9. SYSTEM SETTINGS UPDATE
-- ============================================================================

-- Log the database updates
INSERT INTO system_settings (setting_key, setting_value, description) 
VALUES 
    ('personal_budget_db_version', '2.0', 'Personal budget database schema version'),
    ('savings_features_enabled', '1', 'Advanced savings features have been enabled'),
    ('challenges_system_enabled', '1', 'Weekly challenges system has been enabled'),
    ('autosavings_system_enabled', '1', 'Autosavings system has been enabled')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value), 
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- 10. SAVINGS/EXPENSE SEPARATION FIX
-- ============================================================================

-- Remove incorrectly categorized savings contributions from expenses table
-- These should only exist in personal_goal_contributions, not as expenses
DELETE pe FROM personal_expenses pe
JOIN budget_categories bc ON pe.category_id = bc.id
WHERE bc.category_type = 'savings' 
AND pe.description LIKE 'Contribution to %';

-- Add a view to properly calculate available balance
CREATE OR REPLACE VIEW v_user_financial_summary AS
SELECT 
    u.id as user_id,
    u.first_name,
    u.last_name,
    
    -- Income calculation
    COALESCE(monthly_income.total_income, 0) as monthly_income,
    
    -- Expenses calculation (excluding savings)
    COALESCE(monthly_expenses.total_expenses, 0) as monthly_expenses,
    
    -- Savings calculation
    COALESCE(monthly_savings.total_savings, 0) as monthly_savings,
    
    -- Available balance = Income - Expenses - Savings
    (COALESCE(monthly_income.total_income, 0) - 
     COALESCE(monthly_expenses.total_expenses, 0) - 
     COALESCE(monthly_savings.total_savings, 0)) as available_balance,
    
    -- Budget allocation info
    pba.needs_percentage,
    pba.wants_percentage,
    pba.savings_percentage,
    pba.monthly_salary as budgeted_salary
    
FROM users u

-- Monthly income (confirmed income for current month)
LEFT JOIN (
    SELECT 
        user_id,
        SUM(amount) as total_income
    FROM personal_income pi
    WHERE DATE_FORMAT(pi.income_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    GROUP BY user_id
) monthly_income ON u.id = monthly_income.user_id

-- Monthly expenses (actual expenses, not including savings)
LEFT JOIN (
    SELECT 
        pe.user_id,
        SUM(pe.amount) as total_expenses
    FROM personal_expenses pe
    LEFT JOIN budget_categories bc ON pe.category_id = bc.id
    WHERE DATE_FORMAT(pe.expense_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    AND (bc.category_type != 'savings' OR bc.category_type IS NULL)
    GROUP BY pe.user_id
) monthly_expenses ON u.id = monthly_expenses.user_id

-- Monthly savings (from goal contributions)
LEFT JOIN (
    SELECT 
        pg.user_id,
        SUM(pgc.amount) as total_savings
    FROM personal_goal_contributions pgc
    JOIN personal_goals pg ON pgc.goal_id = pg.id
    WHERE DATE_FORMAT(pgc.contribution_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    GROUP BY pg.user_id
) monthly_savings ON u.id = monthly_savings.user_id

-- Budget allocation
LEFT JOIN personal_budget_allocation pba ON u.id = pba.user_id AND pba.is_active = 1

WHERE u.user_type = 'personal';

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================

SELECT 
    'Personal Budget Database Update Complete!' as STATUS,
    'Savings/Expense separation has been fixed! Savings are no longer counted as expenses.' as MESSAGE,
    'Available Balance = Income - Expenses - Savings (correctly calculated)' as NOTE,
    NOW() as COMPLETED_AT;
