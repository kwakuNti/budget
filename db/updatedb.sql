-- ============================================================================
-- Personal Budget System - Database Updates
-- ============================================================================
-- This file ---- ============================================================================
-- 7. COMPREHENSIVE AUTO-SAVE SYSTEM TABLES
-- ============================================================================

-- Enhanced Personal Auto-Save Configuration (per goal)
CREATE TABLE IF NOT EXISTS personal_goal_autosave (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_id INT NULL, -- NULL means global settings, specific ID means per-goal
    enabled BOOLEAN DEFAULT FALSE,
    
    -- Save triggers
    trigger_salary BOOLEAN DEFAULT TRUE,
    trigger_additional_income BOOLEAN DEFAULT FALSE,
    trigger_schedule BOOLEAN DEFAULT FALSE,
    
    -- Schedule settings
    schedule_frequency ENUM('daily', 'weekly', 'biweekly', 'monthly') DEFAULT 'monthly',
    schedule_day INT DEFAULT 1, -- Day of week (1-7) or day of month (1-31)
    schedule_time TIME DEFAULT '09:00:00',
    
    -- Amount settings
    save_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage',
    save_percentage DECIMAL(5,2) DEFAULT 10.00, -- Percentage of income/trigger amount
    save_amount DECIMAL(10,2) DEFAULT 0.00, -- Fixed amount
    
    -- Allocation settings (for global auto-save)
    allocation_method ENUM('equal_split', 'priority_based', 'percentage_based') DEFAULT 'priority_based',
    max_per_goal DECIMAL(10,2) DEFAULT 0.00, -- Maximum amount per goal (0 = no limit)
    
    -- Conditions
    min_income_threshold DECIMAL(10,2) DEFAULT 0.00, -- Only save if income is above this
    preserve_emergency_amount DECIMAL(10,2) DEFAULT 500.00, -- Keep this much available
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES personal_goals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_goal_autosave (user_id, goal_id)
);

-- Auto-Save Execution History
CREATE TABLE IF NOT EXISTS personal_autosave_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    execution_date DATETIME NOT NULL,
    trigger_type ENUM('salary', 'additional_income', 'scheduled', 'manual') NOT NULL,
    trigger_amount DECIMAL(12,2) DEFAULT 0.00, -- Income amount that triggered the save
    
    total_saved DECIMAL(10,2) NOT NULL,
    goals_affected INT DEFAULT 0,
    
    -- Breakdown
    breakdown JSON, -- Detailed breakdown of how much went to each goal
    
    -- Execution details
    remaining_balance DECIMAL(12,2) DEFAULT 0.00,
    emergency_preserved DECIMAL(10,2) DEFAULT 0.00,
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_autosave_history_user_date (user_id, execution_date),
    INDEX idx_autosave_trigger (trigger_type, execution_date)
);

-- Goal Allocation Rules (for advanced allocation)
CREATE TABLE IF NOT EXISTS personal_goal_allocation_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_id INT NOT NULL,
    
    -- Priority and allocation
    priority_order INT DEFAULT 1, -- Lower number = higher priority
    allocation_percentage DECIMAL(5,2) DEFAULT 0.00, -- For percentage-based allocation
    min_allocation DECIMAL(10,2) DEFAULT 0.00, -- Minimum amount this goal should get
    max_allocation DECIMAL(10,2) DEFAULT 0.00, -- Maximum amount (0 = no limit)
    
    -- Conditions
    only_when_target_below DECIMAL(10,2) DEFAULT 0.00, -- Only allocate when goal below this amount
    active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES personal_goals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_goal_allocation (user_id, goal_id)
);

-- Add progress tracking to personal_goals
ALTER TABLE personal_goals 
ADD COLUMN current_amount DECIMAL(10,2) DEFAULT 0.00;




-- Create default global auto-save settings for existing users
INSERT IGNORE INTO personal_goal_autosave (user_id, goal_id, enabled, trigger_salary, save_type, save_percentage)
SELECT 
    u.id, 
    NULL, -- Global settings
    FALSE, 
    TRUE, 
    'percentage', 
    10.00
FROM users u 
WHERE u.user_type = 'personal'
AND NOT EXISTS (
    SELECT 1 FROM personal_goal_autosave pga 
    WHERE pga.user_id = u.id AND pga.goal_id IS NULL
);

-- ============================================================================
-- 8. FINANCIAL SUMMARY VIEW (UPDATED)
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

-- Update existing goals progress
UPDATE personal_goals pg
SET current_amount = COALESCE((
    SELECT SUM(pgc.amount) 
    FROM personal_goal_contributions pgc 
    WHERE pgc.goal_id = pg.id
), 0)
WHERE pg.target_amount > 0;
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

-- ============================================================================
-- 12. USER WALKTHROUGH SYSTEM
-- ============================================================================

-- User walkthrough progress tracking
CREATE TABLE IF NOT EXISTS user_walkthrough_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    walkthrough_type ENUM('initial_setup', 'new_feature', 'help_guide') DEFAULT 'initial_setup',
    current_step VARCHAR(50) NOT NULL,
    steps_completed JSON NULL, -- no default, handle in app
    is_completed BOOLEAN DEFAULT FALSE,
    can_skip BOOLEAN DEFAULT FALSE,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    last_shown_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_walkthrough (user_id, walkthrough_type),
    INDEX idx_walkthrough_progress (user_id, walkthrough_type, is_completed)
);

-- Walkthrough step definitions
CREATE TABLE IF NOT EXISTS walkthrough_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    walkthrough_type ENUM('initial_setup', 'new_feature', 'help_guide') NOT NULL,
    step_name VARCHAR(50) NOT NULL,
    step_order INT NOT NULL,
    page_url VARCHAR(255) NOT NULL,
    target_element VARCHAR(255) NOT NULL, -- CSS selector for the element to highlight
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    action_required BOOLEAN DEFAULT TRUE, -- Whether user must perform action to continue
    can_skip BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_step (walkthrough_type, step_name),
    INDEX idx_walkthrough_order (walkthrough_type, step_order, is_active)
);

-- Insert initial setup walkthrough steps
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip) VALUES
('initial_setup', 'setup_income', 1, '/dashboard', '.setup-salary-btn-hero', 'Set Up Your Income', 'Welcome! Let\'s start by setting up your income. This is essential for budget planning and goal tracking. Click "Set Up Income" to begin.', TRUE, FALSE),
('initial_setup', 'configure_salary', 2, '/salary', '#salaryActionBtn', 'Configure Your Salary', 'Great! Now enter your salary details. This will help us calculate your available budget and auto-save for your goals.', TRUE, FALSE),
('initial_setup', 'setup_budget', 3, '/budgets', 'button[onclick="showBudgetTemplateModal()"]', 'Set Up Your Budget', 'Perfect! Now let\'s set up your budget. You can click "Use Template" to choose from our popular templates, or you can skip this step to create a custom budget later.', FALSE, TRUE);

-- Insert page-specific help tours with reliable target elements
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip) VALUES
-- Dashboard page tour
('help_guide', 'dashboard_overview', 1, 'templates/personal-dashboard.php', '.main-content', 'Welcome to Your Dashboard', 'This is your personal finance dashboard. Here you can see your financial overview, track goals, and manage your budget.', FALSE, TRUE),
('help_guide', 'dashboard_navigation', 2, 'templates/personal-dashboard.php', '.header-nav', 'Navigation Menu', 'Use the navigation menu to access different sections: Dashboard, Budget, Expenses, Salary, Goals, and Analytics.', FALSE, TRUE),
('help_guide', 'dashboard_actions', 3, 'templates/personal-dashboard.php', '.quick-actions', 'Quick Actions', 'Use these quick action buttons to add expenses, income, or access key features quickly.', FALSE, TRUE),

-- Budget page tour  
('help_guide', 'budget_overview', 1, '/budgets', '.page-header', 'Budget Management', 'This is where you manage your budget categories and allocations. You can create templates or custom budgets.', FALSE, TRUE),
('help_guide', 'budget_templates', 2, '/budgets', '.template-section', 'Budget Templates', 'Choose from popular budget templates like 50/30/20 rule, envelope method, or zero-based budgeting.', FALSE, TRUE),
('help_guide', 'budget_categories', 3, '/budgets', '.budget-categories', 'Budget Categories', 'View and manage your spending categories. Set limits for each category to stay within your budget.', FALSE, TRUE),

-- Salary page tour
('help_guide', 'salary_overview', 1, '/salary', '.page-header', 'Income Management', 'Set up and manage your income sources here. This is essential for accurate budget planning.', FALSE, TRUE),
('help_guide', 'salary_setup', 2, '/salary', '#salaryActionBtn', 'Set Up Salary', 'Click here to configure your primary salary. Include your monthly amount and pay frequency.', FALSE, TRUE),
('help_guide', 'salary_schedule', 3, '/salary', '.salary-overview', 'Payment Schedule', 'View your upcoming payments and manage additional income sources if you have multiple income streams.', FALSE, TRUE),

-- Expenses page tour
('help_guide', 'expenses_overview', 1, '/personal-expense', '.page-header', 'Expense Tracking', 'Track your daily expenses here. Categorize spending to see where your money goes.', FALSE, TRUE),
('help_guide', 'expenses_add', 2, '/personal-expense', '.page-actions', 'Add Expense', 'Click to add a new expense. Choose the category and amount to keep your budget on track.', FALSE, TRUE),
('help_guide', 'expenses_categories', 3, '/personal-expense', '.main-content', 'Expense Categories', 'View your expenses organized by categories to understand your spending patterns.', FALSE, TRUE),

-- Savings page tour
('help_guide', 'savings_overview', 1, '/savings', '.page-header', 'Savings Goals', 'Set and track progress towards your financial goals. Whether it\'s an emergency fund, vacation, or major purchase.', FALSE, TRUE),
('help_guide', 'savings_create', 2, '/savings', '.page-actions', 'Create Goal', 'Click to create a new savings goal. Set your target amount and deadline.', FALSE, TRUE),
('help_guide', 'savings_autosave', 3, '/savings', '.auto-save-section', 'Auto-Save System', 'Configure automatic savings to reach your goals faster with smart allocation rules.', FALSE, TRUE),

-- Reports page tour
('help_guide', 'reports_overview', 1, '/report', '.page-header', 'Financial Reports', 'View detailed reports and analytics about your spending patterns and financial health.', FALSE, TRUE),
('help_guide', 'reports_insights', 2, '/report', '.main-content', 'Financial Insights', 'Get personalized insights and recommendations to improve your financial habits.', FALSE, TRUE);

-- ============================================================================
-- 11. USER FEEDBACK SYSTEM
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    feedback_type ENUM('bug_report', 'feature_request', 'general', 'complaint', 'compliment') DEFAULT 'general',
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    page_url VARCHAR(500) NULL,
    browser_info TEXT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
    admin_response TEXT NULL,
    admin_user_id INT NULL,
    rating INT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_feedback_user (user_id),
    INDEX idx_feedback_status (status, created_at),
    INDEX idx_feedback_type (feedback_type, created_at),
    INDEX idx_feedback_priority (priority, status)
);

-- Create feedback attachments table for future use
CREATE TABLE IF NOT EXISTS feedback_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    feedback_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (feedback_id) REFERENCES user_feedback(id) ON DELETE CASCADE,
    INDEX idx_attachment_feedback (feedback_id)
);

SELECT 
    'Personal Budget Database Update Complete!' as STATUS,
    'Savings/Expense separation has been fixed! Savings are no longer counted as expenses.' as MESSAGE,
    'Available Balance = Income - Expenses - Savings (correctly calculated)' as NOTE,
    'User Feedback System has been added!' as FEEDBACK_STATUS,
    NOW() as COMPLETED_AT;


ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN verification_token VARCHAR(255);
ALTER TABLE users ADD COLUMN token_expires_at DATETIME;

-- ============================================================================
-- 13. GOOGLE OAUTH SYSTEM TABLES
-- ============================================================================

-- Add Google OAuth support to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL UNIQUE AFTER email;

-- OAuth State Tokens (for CSRF protection)
CREATE TABLE IF NOT EXISTS oauth_state_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    state_token VARCHAR(255) NOT NULL UNIQUE,
    provider_name VARCHAR(50) NOT NULL DEFAULT 'google',
    redirect_url VARCHAR(500) NULL,
    ip_address VARCHAR(45) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_oauth_state_token (state_token),
    INDEX idx_oauth_state_expiry (expires_at),
    INDEX idx_oauth_state_provider (provider_name)
);

-- User OAuth Accounts (linking table)
CREATE TABLE IF NOT EXISTS user_oauth_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider_name VARCHAR(50) NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    provider_email VARCHAR(255) NOT NULL,
    provider_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_account (provider_name, provider_user_id),
    INDEX idx_oauth_accounts_user (user_id),
    INDEX idx_oauth_accounts_provider (provider_name, provider_email)
);

-- OAuth Login Attempts (for security and audit)
CREATE TABLE IF NOT EXISTS oauth_login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_name VARCHAR(50) NOT NULL,
    provider_user_id VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    failure_reason VARCHAR(255) NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_oauth_attempts_provider (provider_name, created_at),
    INDEX idx_oauth_attempts_ip (ip_address, created_at),
    INDEX idx_oauth_attempts_email (email, created_at),
    INDEX idx_oauth_attempts_success (success, created_at)
);

-- System Settings for OAuth Configuration
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('google_oauth_enabled', '1', 'Enable Google OAuth login system'),
('oauth_auto_registration', '1', 'Allow automatic user registration via OAuth'),
('oauth_account_linking', '1', 'Allow linking OAuth accounts to existing users'),
('oauth_require_email_verification', '0', 'Require email verification for OAuth users'),
('oauth_session_timeout', '7200', 'OAuth session timeout in seconds (2 hours)')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

-- Create view for OAuth account information
CREATE OR REPLACE VIEW v_user_oauth_accounts AS
SELECT 
    u.id as user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.google_id,
    u.profile_picture,
    uoa.provider_name,
    uoa.provider_user_id,
    uoa.provider_email,
    uoa.last_login_at as oauth_last_login,
    uoa.created_at as oauth_linked_at,
    CASE 
        WHEN u.google_id IS NOT NULL THEN TRUE 
        ELSE FALSE 
    END as has_google_account
FROM users u
LEFT JOIN user_oauth_accounts uoa ON u.id = uoa.user_id
WHERE u.email IS NOT NULL;

SELECT 
    'Google OAuth System Added!' as STATUS,
    'OAuth tables created successfully' as MESSAGE,
    'Users can now login with Google accounts' as FEATURE,
    NOW() as COMPLETED_AT;