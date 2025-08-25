-- Nkansah Family Budget Manager Database
-- Cleaned and optimized version - FIXED

-- Drop database if exists and create new one
DROP DATABASE IF EXISTS budget;
CREATE DATABASE budget;
USE budget;

-- =============================================
-- CORE TABLES
-- =============================================

-- Users table (supports both family and personal accounts)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20),
    date_of_birth DATE,
    user_type ENUM('family', 'personal') DEFAULT 'personal',
    is_active BOOLEAN DEFAULT TRUE,
    profile_picture VARCHAR(255),
    reset_token VARCHAR(255) NULL,
    reset_token_expiry TIMESTAMP NULL,
    remember_token VARCHAR(255) NULL,
    remember_token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Family groups table
CREATE TABLE family_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_name VARCHAR(100) NOT NULL,
    family_code VARCHAR(20) UNIQUE NOT NULL,
    total_pool DECIMAL(12,2) DEFAULT 0.00,
    monthly_target DECIMAL(12,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'GHS',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Family members table (relationship between users and family groups)
CREATE TABLE family_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    family_id INT NOT NULL,
    role ENUM('admin', 'head', 'member', 'child') DEFAULT 'member',
    display_name VARCHAR(100),
    monthly_contribution_goal DECIMAL(10,2) DEFAULT 0.00,
    total_contributed DECIMAL(12,2) DEFAULT 0.00,
    current_month_contributed DECIMAL(10,2) DEFAULT 0.00,
    accumulated_debt DECIMAL(10,2) DEFAULT 0.00,
    months_behind INT DEFAULT 0,
    goal_met_this_month BOOLEAN DEFAULT FALSE,
    last_payment_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_family (user_id, family_id)
);

-- Family members who don't have user accounts
CREATE TABLE family_members_only (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20),
    role ENUM('parent', 'child', 'spouse', 'sibling', 'other') DEFAULT 'other',
    monthly_contribution_goal DECIMAL(10,2) DEFAULT 0.00,
    total_contributed DECIMAL(12,2) DEFAULT 0.00,
    current_month_contributed DECIMAL(10,2) DEFAULT 0.00,
    accumulated_debt DECIMAL(10,2) DEFAULT 0.00,
    months_behind INT DEFAULT 0,
    goal_met_this_month BOOLEAN DEFAULT FALSE,
    last_payment_date DATE NULL,
    momo_network ENUM('mtn', 'vodafone', 'airteltigo'),
    momo_number VARCHAR(20),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- MONTHLY CYCLE TABLES
-- =============================================

-- Monthly cycles table
CREATE TABLE monthly_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    cycle_month VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    cycle_year YEAR NOT NULL,
    cycle_month_num TINYINT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT TRUE,
    is_closed BOOLEAN DEFAULT FALSE,
    closed_by INT NULL,
    closed_at TIMESTAMP NULL,
    total_collected DECIMAL(12,2) DEFAULT 0.00,
    total_target DECIMAL(12,2) DEFAULT 0.00,
    members_completed INT DEFAULT 0,
    members_pending INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_family_month (family_id, cycle_month)
);

-- Track individual member performance per cycle
CREATE TABLE member_monthly_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_id INT NOT NULL,
    family_id INT NOT NULL,
    member_id INT NULL,
    member_only_id INT NULL,
    member_type ENUM('user', 'member') DEFAULT 'user',
    target_amount DECIMAL(10,2) NOT NULL,
    contributed_amount DECIMAL(10,2) DEFAULT 0.00,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_date DATE NULL,
    deficit_amount DECIMAL(10,2) DEFAULT 0.00,
    contribution_count INT DEFAULT 0,
    first_contribution_date DATE NULL,
    last_contribution_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cycle_id) REFERENCES monthly_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE,
    FOREIGN KEY (member_only_id) REFERENCES family_members_only(id) ON DELETE CASCADE
);

-- Track debt history
CREATE TABLE member_debt_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    member_id INT NULL,
    member_only_id INT NULL,
    member_type ENUM('user', 'member') DEFAULT 'user',
    cycle_month VARCHAR(7) NOT NULL,
    deficit_amount DECIMAL(10,2) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    contributed_amount DECIMAL(10,2) DEFAULT 0.00,
    is_cleared BOOLEAN DEFAULT FALSE,
    cleared_date DATE NULL,
    cleared_by_cycle_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE,
    FOREIGN KEY (member_only_id) REFERENCES family_members_only(id) ON DELETE CASCADE,
    FOREIGN KEY (cleared_by_cycle_id) REFERENCES monthly_cycles(id) ON DELETE SET NULL
);

-- =============================================
-- FINANCIAL TRANSACTIONS
-- =============================================

-- Family contributions (specific to family fund)
CREATE TABLE family_contributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    member_id INT,
    contributor_type ENUM('user', 'member') DEFAULT 'user',
    member_only_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    contribution_date DATE NOT NULL,
    payment_method ENUM('cash', 'momo', 'bank_transfer') DEFAULT 'momo',
    transaction_reference VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE,
    FOREIGN KEY (member_only_id) REFERENCES family_members_only(id) ON DELETE CASCADE
);

-- Family expenses (money spent from family pool)
CREATE TABLE family_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    expense_type ENUM('dstv', 'wifi', 'utilities', 'dining', 'maintenance', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    paid_by INT,
    payer_type ENUM('user', 'member') DEFAULT 'user',
    member_only_id INT NULL,
    payment_method ENUM('cash', 'momo', 'bank_transfer') DEFAULT 'momo',
    receipt_image VARCHAR(255),
    is_approved BOOLEAN DEFAULT TRUE,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES family_members(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES family_members(id) ON DELETE SET NULL,
    FOREIGN KEY (member_only_id) REFERENCES family_members_only(id) ON DELETE SET NULL
);

-- =============================================
-- PERSONAL BUDGET TABLES
-- =============================================

-- Salaries table for personal account income tracking
CREATE TABLE salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    monthly_salary DECIMAL(12,2) NOT NULL,
    pay_frequency ENUM('weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    next_pay_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Budget categories for personal accounts
CREATE TABLE budget_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category_type ENUM('needs', 'wants', 'savings') DEFAULT 'needs',
    icon VARCHAR(10) DEFAULT '📝',
    color VARCHAR(7) DEFAULT '#3498db',
    budget_limit DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name)
);

-- Personal expenses (for personal account budget tracking)
CREATE TABLE personal_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'card', 'momo', 'other') DEFAULT 'cash',
    receipt_url VARCHAR(255),
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES budget_categories(id) ON DELETE SET NULL
);

-- Personal income tracking
CREATE TABLE personal_income (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    source VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    income_date DATE NOT NULL,
    income_type ENUM('salary', 'bonus', 'freelance', 'investment', 'other') DEFAULT 'salary',
    description TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('weekly', 'monthly', 'quarterly', 'yearly') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Personal budget allocation (50-30-20 rule tracking)
CREATE TABLE personal_budget_allocation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    needs_percentage INT DEFAULT 50,
    wants_percentage INT DEFAULT 30,
    savings_percentage INT DEFAULT 20,
    monthly_salary DECIMAL(12,2) NOT NULL,
    needs_amount DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary * needs_percentage / 100) STORED,
    wants_amount DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary * wants_percentage / 100) STORED,
    savings_amount DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary * savings_percentage / 100) STORED,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Personal goals and savings targets
CREATE TABLE personal_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    target_date DATE,
    goal_type ENUM('emergency_fund', 'vacation', 'car', 'house', 'education', 'other') DEFAULT 'other',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- MoMo accounts table for family mobile money management
CREATE TABLE momo_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    network ENUM('mtn', 'vodafone', 'airteltigo') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    account_holder_name VARCHAR(100) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    balance DECIMAL(12,2) DEFAULT 0.00,
    last_transaction_date TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_family_phone (family_id, phone_number)
);

-- MoMo transactions table for tracking mobile money operations
CREATE TABLE momo_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    momo_account_id INT NOT NULL,
    family_id INT NOT NULL,
    transaction_type ENUM('send', 'receive', 'deposit', 'withdrawal', 'transfer') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    recipient_number VARCHAR(20),
    recipient_name VARCHAR(100),
    transaction_reference VARCHAR(100),
    transaction_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    fees_charged DECIMAL(8,2) DEFAULT 0.00,
    balance_before DECIMAL(12,2) DEFAULT 0.00,
    balance_after DECIMAL(12,2) DEFAULT 0.00,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT,
    initiated_by INT,
    api_response JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (momo_account_id) REFERENCES momo_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- MoMo API configurations for different networks
CREATE TABLE momo_api_configs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    network ENUM('mtn', 'vodafone', 'airteltigo') NOT NULL UNIQUE,
    api_endpoint VARCHAR(255) NOT NULL,
    api_key VARCHAR(255),
    secret_key VARCHAR(255),
    subscription_key VARCHAR(255),
    is_sandbox BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    rate_limit_per_minute INT DEFAULT 60,
    timeout_seconds INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- SYSTEM TABLES
-- =============================================

-- Activity logs
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    family_id INT,
    action_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (family_id) REFERENCES family_groups(id) ON DELETE SET NULL
);

-- System settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User preferences
CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
);





-- Create income sources table
CREATE TABLE personal_income_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    source_name VARCHAR(100) NOT NULL,
    income_type ENUM('salary', 'bonus', 'freelance', 'investment', 'other') 
    DEFAULT 'other',
    monthly_amount DECIMAL(12,2) NOT NULL,
    payment_frequency ENUM('weekly', 'bi-weekly', 'monthly', 'variable', 'one-time') DEFAULT 'monthly',
    payment_method ENUM('bank', 'mobile') DEFAULT 'bank',
    description TEXT,
    include_in_budget BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT
    CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create index for faster lookups
CREATE INDEX idx_income_sources_user_active ON personal_income_sources(user_id, is_active);
-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- User indexes
CREATE INDEX idx_users_reset_token ON users(reset_token);
CREATE INDEX idx_users_remember_token ON users(remember_token);

-- Family contribution indexes
CREATE INDEX idx_contributions_family_date ON family_contributions(family_id, contribution_date);
CREATE INDEX idx_contributions_member ON family_contributions(member_id);
CREATE INDEX idx_contributions_contributor_type ON family_contributions(contributor_type, family_id);
CREATE INDEX idx_contributions_member_only ON family_contributions(member_only_id);
CREATE INDEX idx_contributions_date ON family_contributions(contribution_date);

-- Family expense indexes
CREATE INDEX idx_expenses_family_date ON family_expenses(family_id, expense_date);
CREATE INDEX idx_expenses_type ON family_expenses(expense_type);
CREATE INDEX idx_expenses_payer_type ON family_expenses(payer_type, family_id);

-- Family members indexes
CREATE INDEX idx_family_members_only_family ON family_members_only(family_id);
CREATE INDEX idx_family_members_only_phone ON family_members_only(phone_number);

-- Monthly cycle indexes
CREATE INDEX idx_monthly_cycles_family_current ON monthly_cycles(family_id, is_current);
CREATE INDEX idx_monthly_cycles_family_month ON monthly_cycles(family_id, cycle_month);
CREATE INDEX idx_member_performance_cycle ON member_monthly_performance(cycle_id);
CREATE INDEX idx_member_performance_member ON member_monthly_performance(member_id, member_only_id);
CREATE INDEX idx_debt_history_member ON member_debt_history(family_id, member_id, member_only_id);
CREATE INDEX idx_debt_history_uncleared ON member_debt_history(family_id, is_cleared);

-- Indexes for personal budget tables
CREATE INDEX idx_salaries_user ON salaries(user_id, is_active);
CREATE INDEX idx_budget_categories_user ON budget_categories(user_id, is_active);
CREATE INDEX idx_personal_expenses_user_date ON personal_expenses(user_id, expense_date);
CREATE INDEX idx_personal_expenses_category ON personal_expenses(category_id);
CREATE INDEX idx_personal_income_user_date ON personal_income(user_id, income_date);
CREATE INDEX idx_personal_budget_allocation_user ON personal_budget_allocation(user_id, is_active);
CREATE INDEX idx_personal_goals_user ON personal_goals(user_id, is_completed);

-- Indexes for MoMo tables
CREATE INDEX idx_momo_accounts_family ON momo_accounts(family_id, is_active);
CREATE INDEX idx_momo_accounts_primary ON momo_accounts(family_id, is_primary);
CREATE INDEX idx_momo_accounts_phone ON momo_accounts(phone_number);
CREATE INDEX idx_momo_transactions_account ON momo_transactions(momo_account_id, transaction_date);
CREATE INDEX idx_momo_transactions_family ON momo_transactions(family_id, transaction_date);
CREATE INDEX idx_momo_transactions_status ON momo_transactions(transaction_status);
CREATE INDEX idx_momo_transactions_type ON momo_transactions(transaction_type, transaction_date);

-- =============================================
-- TRIGGERS
-- =============================================

DELIMITER //

-- Update family pool and member totals when contribution is added
CREATE TRIGGER after_contribution_insert 
AFTER INSERT ON family_contributions
FOR EACH ROW
BEGIN
    -- Update family pool
    UPDATE family_groups 
    SET total_pool = total_pool + NEW.amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.family_id;
    
    -- Update member totals based on contributor type
    IF NEW.contributor_type = 'user' AND NEW.member_id IS NOT NULL THEN
        UPDATE family_members 
        SET total_contributed = total_contributed + NEW.amount
        WHERE id = NEW.member_id;
    ELSEIF NEW.contributor_type = 'member' AND NEW.member_only_id IS NOT NULL THEN
        UPDATE family_members_only 
        SET total_contributed = total_contributed + NEW.amount
        WHERE id = NEW.member_only_id;
    END IF;
END//

-- Update family pool when expense is added
CREATE TRIGGER after_expense_insert 
AFTER INSERT ON family_expenses
FOR EACH ROW
BEGIN
    UPDATE family_groups 
    SET total_pool = total_pool - NEW.amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.family_id;
END//

-- Update MoMo account balance when transaction is completed
CREATE TRIGGER after_momo_transaction_update
AFTER UPDATE ON momo_transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_status = 'completed' AND OLD.transaction_status != 'completed' THEN
        UPDATE momo_accounts
        SET balance = NEW.balance_after,
            last_transaction_date = NEW.transaction_date
        WHERE id = NEW.momo_account_id;
    END IF;
END//

-- Update MoMo account balance when transaction is inserted as completed
CREATE TRIGGER after_momo_transaction_insert
AFTER INSERT ON momo_transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_status = 'completed' THEN
        UPDATE momo_accounts
        SET balance = NEW.balance_after,
            last_transaction_date = NEW.transaction_date
        WHERE id = NEW.momo_account_id;
    END IF;
END//

DELIMITER ;

-- =============================================
-- STORED PROCEDURES
-- =============================================

DELIMITER //

CREATE PROCEDURE AddFamilyMember(
    IN p_family_id INT,
    IN p_first_name VARCHAR(50),
    IN p_last_name VARCHAR(50),
    IN p_phone_number VARCHAR(20),
    IN p_role ENUM('parent', 'child', 'spouse', 'sibling', 'other'),
    IN p_monthly_goal DECIMAL(10,2),
    IN p_momo_network ENUM('mtn', 'vodafone', 'airteltigo'),
    IN p_momo_number VARCHAR(20),
    IN p_added_by INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    INSERT INTO family_members_only (
        family_id,
        first_name,
        last_name,
        phone_number,
        role,
        monthly_contribution_goal,
        total_contributed,
        current_month_contributed,
        accumulated_debt,
        months_behind,
        goal_met_this_month,
        last_payment_date,
        momo_network,
        momo_number,
        notes,
        is_active,
        added_by,
        added_at,
        updated_at
    ) VALUES (
        p_family_id,
        p_first_name,
        p_last_name,
        p_phone_number,
        p_role,
        p_monthly_goal,
        0.00,
        0.00,
        0.00,
        0,
        FALSE,
        NULL,
        p_momo_network,
        p_momo_number,
        NULL,
        TRUE,
        p_added_by,
        NOW(),
        NOW()
    );
    
    COMMIT;
END //

-- Create new monthly cycle
CREATE PROCEDURE CreateNewMonthlyCycle(
    IN p_family_id INT,
    IN p_year YEAR,
    IN p_month TINYINT
)
BEGIN
    DECLARE v_cycle_month VARCHAR(7);
    DECLARE v_start_date DATE;
    DECLARE v_end_date DATE;
    DECLARE v_total_target DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_member_count INT DEFAULT 0;
    
    SET v_cycle_month = CONCAT(p_year, '-', LPAD(p_month, 2, '0'));
    SET v_start_date = DATE(CONCAT(p_year, '-', LPAD(p_month, 2, '0'), '-01'));
    SET v_end_date = LAST_DAY(v_start_date);
    
    -- Use family's monthly target instead of summing individual member goals
    SELECT 
        monthly_target,
        (SELECT COUNT(fm.id) + COUNT(fmo.id)
         FROM family_members fm
         LEFT JOIN family_members_only fmo ON fm.family_id = fmo.family_id
         WHERE fm.family_id = p_family_id AND fm.is_active = TRUE
            OR fmo.family_id = p_family_id AND fmo.is_active = TRUE)
    INTO v_total_target, v_member_count
    FROM family_groups 
    WHERE id = p_family_id;
    
    -- Mark previous cycle as not current
    UPDATE monthly_cycles 
    SET is_current = FALSE 
    WHERE family_id = p_family_id AND is_current = TRUE;
    
    -- Create new cycle
    INSERT INTO monthly_cycles (
        family_id,
        cycle_month,
        cycle_year,
        cycle_month_num,
        start_date,
        end_date,
        total_target,
        members_pending
    ) VALUES (
        p_family_id,
        v_cycle_month,
        p_year,
        p_month,
        v_start_date,
        v_end_date,
        v_total_target,
        v_member_count
    );
    
    -- Create performance records for each member
    CALL CreateMemberPerformanceRecords(LAST_INSERT_ID(), p_family_id);
END//

-- Create performance records for all family members
CREATE PROCEDURE CreateMemberPerformanceRecords(
    IN p_cycle_id INT,
    IN p_family_id INT
)
BEGIN
    -- Insert records for registered family members
    INSERT INTO member_monthly_performance (
        cycle_id,
        family_id,
        member_id,
        member_type,
        target_amount
    )
    SELECT 
        p_cycle_id,
        p_family_id,
        fm.id,
        'user',
        fm.monthly_contribution_goal
    FROM family_members fm
    WHERE fm.family_id = p_family_id 
    AND fm.is_active = TRUE
    AND fm.monthly_contribution_goal > 0;
    
    -- Insert records for non-registered family members
    INSERT INTO member_monthly_performance (
        cycle_id,
        family_id,
        member_only_id,
        member_type,
        target_amount
    )
    SELECT 
        p_cycle_id,
        p_family_id,
        fmo.id,
        'member',
        fmo.monthly_contribution_goal
    FROM family_members_only fmo
    WHERE fmo.family_id = p_family_id 
    AND fmo.is_active = TRUE
    AND fmo.monthly_contribution_goal > 0;
END//

-- Close monthly cycle and calculate debts
CREATE PROCEDURE CloseMonthlyCycle(
    IN p_cycle_id INT,
    IN p_closed_by INT
)
BEGIN
    DECLARE v_family_id INT;
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_member_id INT;
    DECLARE v_member_only_id INT;
    DECLARE v_member_type VARCHAR(10);
    DECLARE v_target DECIMAL(10,2);
    DECLARE v_contributed DECIMAL(10,2);
    DECLARE v_deficit DECIMAL(10,2);
    DECLARE v_cycle_month VARCHAR(7);
    
    -- Cursor for incomplete members
    DECLARE incomplete_cursor CURSOR FOR
        SELECT 
            member_id,
            member_only_id,
            member_type,
            target_amount,
            contributed_amount,
            target_amount - contributed_amount AS deficit
        FROM member_monthly_performance 
        WHERE cycle_id = p_cycle_id 
        AND is_completed = FALSE
        AND target_amount > contributed_amount;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Get cycle info
    SELECT family_id, cycle_month INTO v_family_id, v_cycle_month
    FROM monthly_cycles 
    WHERE id = p_cycle_id;
    
    -- Update cycle as closed
    UPDATE monthly_cycles 
    SET 
        is_closed = TRUE,
        closed_by = p_closed_by,
        closed_at = CURRENT_TIMESTAMP,
        is_current = FALSE
    WHERE id = p_cycle_id;
    
    -- Process incomplete members
    OPEN incomplete_cursor;
    
    read_loop: LOOP
        FETCH incomplete_cursor INTO v_member_id, v_member_only_id, v_member_type, v_target, v_contributed, v_deficit;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Add to debt history
        INSERT INTO member_debt_history (
            family_id,
            member_id,
            member_only_id,
            member_type,
            cycle_month,
            deficit_amount,
            target_amount,
            contributed_amount
        ) VALUES (
            v_family_id,
            v_member_id,
            v_member_only_id,
            v_member_type,
            v_cycle_month,
            v_deficit,
            v_target,
            v_contributed
        );
        
        -- Update member debt tracking
        IF v_member_type = 'user' THEN
            UPDATE family_members 
            SET 
                accumulated_debt = accumulated_debt + v_deficit,
                months_behind = months_behind + 1,
                current_month_contributed = 0.00,
                goal_met_this_month = FALSE
            WHERE id = v_member_id;
        ELSE
            UPDATE family_members_only 
            SET 
                accumulated_debt = accumulated_debt + v_deficit,
                months_behind = months_behind + 1,
                current_month_contributed = 0.00,
                goal_met_this_month = FALSE
            WHERE id = v_member_only_id;
        END IF;
    END LOOP;
    
    CLOSE incomplete_cursor;
    
    -- Reset current month contributions for completed members too
    UPDATE family_members fm
    JOIN member_monthly_performance mp ON fm.id = mp.member_id
    SET 
        fm.current_month_contributed = 0.00,
        fm.goal_met_this_month = FALSE
    WHERE mp.cycle_id = p_cycle_id AND mp.member_type = 'user';
    
    UPDATE family_members_only fmo
    JOIN member_monthly_performance mp ON fmo.id = mp.member_only_id
    SET 
        fmo.current_month_contributed = 0.00,
        fmo.goal_met_this_month = FALSE
    WHERE mp.cycle_id = p_cycle_id AND mp.member_type = 'member';
END//

-- Cleanup old cycles
CREATE PROCEDURE CleanupOldCycles(IN p_months_to_keep INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_cycle_id INT;
    
    DECLARE cycle_cursor CURSOR FOR
        SELECT id FROM monthly_cycles 
        WHERE created_at < DATE_SUB(CURDATE(), INTERVAL p_months_to_keep MONTH)
        AND is_closed = TRUE;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cycle_cursor;

    cleanup_loop: LOOP
        FETCH cycle_cursor INTO v_cycle_id;
        IF done THEN
            LEAVE cleanup_loop;
        END IF;

        DELETE FROM member_monthly_performance WHERE cycle_id = v_cycle_id;
        DELETE FROM monthly_cycles WHERE id = v_cycle_id;
    END LOOP;

    CLOSE cycle_cursor;

    INSERT INTO activity_logs (
        action_type,
        description,
        created_at
    ) VALUES (
        'system_cleanup',
        CONCAT('Cleaned up cycles older than ', p_months_to_keep, ' months'),
        NOW()
    );
END //

DELIMITER ;

-- =============================================
-- VIEWS
-- =============================================

-- Create helpful view for dashboard queries
CREATE OR REPLACE VIEW v_current_cycle_summary AS
SELECT 
    mc.family_id,
    mc.id as cycle_id,
    mc.cycle_month,
    mc.start_date,
    mc.end_date,
    mc.is_closed,
    mc.total_target,
    mc.total_collected,
    mc.members_completed,
    mc.members_pending,
    DATEDIFF(mc.end_date, CURDATE()) as days_remaining,
    CASE 
        WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
        ELSE 0 
    END as completion_percentage,
    fg.family_name,
    fg.total_pool as family_pool_balance
FROM monthly_cycles mc
JOIN family_groups fg ON mc.family_id = fg.id
WHERE mc.is_current = TRUE;

-- Create view for member performance summary
CREATE OR REPLACE VIEW v_member_performance_summary AS
SELECT 
    mmp.cycle_id,
    mmp.family_id,
    mmp.member_id,
    mmp.member_only_id,
    mmp.member_type,
    mmp.target_amount,
    mmp.contributed_amount,
    mmp.is_completed,
    mmp.completed_date,
    mmp.contribution_count,
    CASE 
        WHEN mmp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
        WHEN mmp.member_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
        ELSE 'Unknown'
    END as member_name,
    CASE 
        WHEN mmp.member_type = 'user' THEN u.phone_number
        WHEN mmp.member_type = 'member' THEN fmo.phone_number
        ELSE NULL
    END as phone_number,
    CASE 
        WHEN mmp.member_type = 'user' THEN fm.role
        WHEN mmp.member_type = 'member' THEN fmo.role
        ELSE NULL
    END as role,
    CASE 
        WHEN mmp.member_type = 'user' THEN fm.accumulated_debt
        WHEN mmp.member_type = 'member' THEN fmo.accumulated_debt
        ELSE 0
    END as accumulated_debt,
    CASE 
        WHEN mmp.target_amount > 0 THEN ROUND((mmp.contributed_amount / mmp.target_amount) * 100, 2)
        ELSE 0 
    END as progress_percentage
FROM member_monthly_performance mmp
LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
LEFT JOIN users u ON fm.user_id = u.id
LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member';

-- =============================================
-- INITIAL DATA SETUP
-- =============================================

-- Insert the family admin user
INSERT INTO users (
    username, 
    email,
    password_hash,
    first_name,
    last_name,
    phone_number,
    date_of_birth,
    user_type,
    is_active
) VALUES (
    'nkansah_admin',
    'nkansahfamily@gmail.com',
    '$2y$10$YourHashedPasswordHere', -- Use password_hash('family123', PASSWORD_DEFAULT);
    'Nkansah',
    'Family',
    '+233241234567',
    '1980-01-01',
    'family',
    1
);

-- Create family group
INSERT INTO family_groups (
    family_name,
    family_code,
    total_pool,
    monthly_target,
    currency,
    created_by
) VALUES (
    'Nkansah Family',
    'NKANSAH2025',
    0.00,
    15000.00,
    'GHS',
    1
);

-- Add family admin as a family member
INSERT INTO family_members (
    user_id,
    family_id,
    role,
    display_name,
    monthly_contribution_goal,
    is_active
) VALUES (
    1,
    1,
    'admin',
    'Nkansah Family Admin',
    0.00,
    1
);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('default_currency', 'GHS', 'Default currency for the system'),
('family_max_members', '10', 'Maximum number of members per family'),
('momo_transaction_fee', '0.01', 'Transaction fee percentage for MoMo transfers'),
('allow_non_registered_members', '1', 'Allow adding family members without requiring them to register'),
('family_member_limit', '20', 'Maximum number of family members (registered + non-registered)'),
('require_member_approval', '0', 'Require admin approval for new family members'),
('schema_version', '2.0', 'Database schema version'),
('auto_create_cycles', '1', 'Automatically create monthly cycles'),
('cycle_reminder_days', '3', 'Days before cycle end to send reminders'),
('debt_tracking_enabled', '1', 'Enable debt tracking for missed contributions'),
('max_debt_months', '6', 'Maximum months of debt before special handling'),
('cycle_close_grace_days', '3', 'Grace period after month end before auto-closing cycle'),
('member_goal_min_amount', '10', 'Minimum monthly contribution goal amount');

-- Initialize current month cycle for existing family
INSERT INTO monthly_cycles (
    family_id,
    cycle_month,
    cycle_year,
    cycle_month_num,
    start_date,
    end_date,
    total_target,
    members_pending
)
SELECT 
    fg.id,
    DATE_FORMAT(CURDATE(), '%Y-%m'),
    YEAR(CURDATE()),
    MONTH(CURDATE()),
    DATE_FORMAT(CURDATE(), '%Y-%m-01'),
    LAST_DAY(CURDATE()),
    fg.monthly_target,
    COALESCE(COUNT(fm.id), 0) + COALESCE(COUNT(fmo.id), 0)
FROM family_groups fg
LEFT JOIN family_members fm ON fg.id = fm.family_id AND fm.is_active = TRUE
LEFT JOIN family_members_only fmo ON fg.id = fmo.family_id AND fmo.is_active = TRUE
WHERE fg.id = 1
GROUP BY fg.id;

-- Log the database creation
INSERT INTO activity_logs (
    family_id,
    action_type,
    description,
    created_at
) VALUES (
    1,
    'system_setup',
    'Nkansah Family Budget Manager database created',
    NOW()
);

-- Insert MoMo API configurations (sandbox mode)
INSERT INTO momo_api_configs (network, api_endpoint, is_sandbox, is_active) VALUES
('mtn', 'https://sandbox.momodeveloper.mtn.com', TRUE, TRUE),
('vodafone', 'https://api.vodafone.com.gh/momo/sandbox', TRUE, TRUE),
('airteltigo', 'https://api.airteltigo.com.gh/momo/sandbox', TRUE, TRUE);

-- Create a sample MoMo account for the Nkansah family
INSERT INTO momo_accounts (
    family_id,
    account_name,
    network,
    phone_number,
    account_holder_name,
    is_primary,
    is_active,
    balance,
    created_by
) VALUES (
    1,
    'Nkansah Family MTN MoMo',
    'mtn',
    '+233241234567',
    'Nkansah Family Admin',
    TRUE,
    TRUE,
    0.00,
    1
);

-- =============================================
-- SAMPLE DATA FOR TESTING
-- =============================================


-- Create performance records for current cycle
INSERT INTO member_monthly_performance (cycle_id, family_id, member_only_id, member_type, target_amount, contributed_amount, is_completed)
SELECT 
    mc.id,
    mc.family_id,
    fmo.id,
    'member',
    fmo.monthly_contribution_goal,
    CASE 
        WHEN fmo.id = 1 THEN 800.00
        WHEN fmo.id = 2 THEN 600.00
        WHEN fmo.id = 3 THEN 150.00
        WHEN fmo.id = 4 THEN 200.00
        ELSE 0.00
    END,
    CASE 
        WHEN fmo.id IN (1, 2, 4) THEN TRUE
        ELSE FALSE
    END
FROM monthly_cycles mc
CROSS JOIN family_members_only fmo
WHERE mc.family_id = 1 AND mc.is_current = TRUE AND fmo.family_id = 1;

-- Update cycle totals
UPDATE monthly_cycles 
SET 
    total_collected = (SELECT COALESCE(SUM(amount), 0) FROM family_contributions WHERE family_id = 1),
    members_completed = (SELECT COUNT(*) FROM member_monthly_performance WHERE cycle_id = 1 AND is_completed = TRUE),
    members_pending = (SELECT COUNT(*) FROM member_monthly_performance WHERE cycle_id = 1 AND is_completed = FALSE)
WHERE id = 1;

COMMIT;

-- Final status message
SELECT 'Nkansah Family Budget Manager Database setup completed successfully!' as status,
       'Database includes sample data for testing' as note;