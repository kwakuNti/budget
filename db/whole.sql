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

-- Clear existing walkthrough steps and insert improved ones with template selection requirement
DELETE FROM walkthrough_steps WHERE walkthrough_type IN ('initial_setup', 'help_guide');

-- Insert improved initial setup walkthrough steps with TEMPLATE SELECTION requirement
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
-- Step 1: Setup Income (Dashboard)
('initial_setup', 'setup_income', 1, '/personal-dashboard', '.setup-salary-btn-hero', 'Set Up Your Income', 'Welcome to Budgetly! Let\'s start by setting up your income. This is essential for budget planning and goal tracking. Click the "Set Up Income" button to begin.', 1, 0, 1),

-- Step 2: Configure Salary (Salary Page)  
('initial_setup', 'configure_salary', 2, '/salary', '#salaryActionBtn', 'Configure Your Salary', 'Great! Now enter your salary details. Fill in your income information to help us calculate your available budget and auto-save for your goals.', 1, 0, 1),

-- Step 3: Choose Budget Template (Budget Page) - REQUIRED
('initial_setup', 'choose_template', 3, '/budgets', 'button[onclick="showBudgetTemplateModal()"]', 'Choose a Budget Template', 'Perfect! Now you MUST choose a budget template to get started. Templates help organize your finances with proven strategies. Click "Use Template" to see options.', 1, 0, 1),

-- Step 4: Complete Template Selection (Inside Modal) - REQUIRED
('initial_setup', 'select_template', 4, '/budgets', '.template-card', 'Select Your Template', 'Choose one of these popular budget templates. The 50/30/20 rule is great for beginners - 50% needs, 30% wants, 20% savings. Click on a template to select it.', 1, 0, 1);

-- Dashboard Help Guide - Specific Elements Only
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'dashboard_income_card', 1, '/personal-dashboard', '.income-card', 'Monthly Income', 'This card shows your total monthly income. Click "Set Up Income" if you need to add or modify your salary and other income sources.', 0, 1, 1),
('help_guide', 'dashboard_expenses_card', 2, '/personal-dashboard', '.expenses-card', 'Monthly Expenses', 'View your total monthly expenses here. This updates automatically as you add expenses throughout the month.', 0, 1, 1),
('help_guide', 'dashboard_balance_card', 3, '/personal-dashboard', '.balance-card', 'Available Balance', 'This shows how much money you have left after expenses and savings. Keep this positive to stay on budget!', 0, 1, 1),
('help_guide', 'dashboard_quick_actions', 4, '/personal-dashboard', '.quick-actions', 'Quick Actions', 'Use these buttons to quickly add expenses, record income, or create savings goals without navigating to other pages.', 0, 1, 1),

-- Budget Page Help Guide - Specific Elements
('help_guide', 'budget_template_btn', 1, '/budgets', 'button[onclick="showBudgetTemplateModal()"]', 'Budget Templates', 'Click here to use pre-built budget templates. Great for getting started quickly with proven budgeting strategies.', 0, 1, 1),
('help_guide', 'budget_categories_list', 2, '/budgets', '.budget-categories-container', 'Your Budget Categories', 'These are your spending categories with monthly limits. Green means on track, yellow is a warning, red means over budget.', 0, 1, 1),
('help_guide', 'budget_add_category', 3, '/budgets', '.add-category-btn', 'Add New Category', 'Create custom budget categories for your specific needs. Set spending limits to control your expenses.', 0, 1, 1),
('help_guide', 'budget_allocation_summary', 4, '/budgets', '.allocation-summary', 'Budget Summary', 'See how your total budget is allocated across Needs, Wants, and Savings. Aim for a balanced approach.', 0, 1, 1),

-- Salary Page Help Guide - Specific Elements
('help_guide', 'salary_setup_btn', 1, '/salary', '#salaryActionBtn', 'Set Up Primary Salary', 'Click here to configure your main salary. Enter your monthly amount, pay frequency, and any deductions.', 0, 1, 1),
('help_guide', 'salary_overview_card', 2, '/salary', '.salary-overview', 'Salary Overview', 'View your current salary settings and payment schedule. This affects your budget calculations.', 0, 1, 1),
('help_guide', 'salary_additional_income', 3, '/salary', '.additional-income-section', 'Additional Income', 'Add other income sources like freelance work, side jobs, or investments for a complete financial picture.', 0, 1, 1),

-- Expenses Page Help Guide - Specific Elements  
('help_guide', 'expenses_add_btn', 1, '/personal-expense', '.add-expense-btn', 'Add New Expense', 'Click to record a new expense. Choose the right category to track where your money goes.', 0, 1, 1),
('help_guide', 'expenses_recent_list', 2, '/personal-expense', '.recent-expenses', 'Recent Expenses', 'View and manage your recent expenses. You can edit or delete entries if needed.', 0, 1, 1),
('help_guide', 'expenses_category_filter', 3, '/personal-expense', '.category-filters', 'Filter by Category', 'Use these filters to view expenses by specific categories and analyze spending patterns.', 0, 1, 1),
('help_guide', 'expenses_monthly_summary', 4, '/personal-expense', '.monthly-summary', 'Monthly Summary', 'See your total expenses for the current month and compare against your budget.', 0, 1, 1),

-- Savings Page Help Guide - Specific Elements
('help_guide', 'savings_create_goal', 1, '/savings', '.create-goal-btn', 'Create Savings Goal', 'Start your savings journey by creating a specific goal. Set a target amount and deadline.', 0, 1, 1),
('help_guide', 'savings_goals_list', 2, '/savings', '.goals-container', 'Your Savings Goals', 'Track progress toward all your goals. Each goal shows current amount and target.', 0, 1, 1),
('help_guide', 'savings_contribute_btn', 3, '/savings', '.contribute-btn', 'Add Money to Goals', 'Make contributions to your savings goals. Every little bit helps you reach your targets!', 0, 1, 1),
('help_guide', 'savings_progress_bars', 4, '/savings', '.goal-progress', 'Progress Tracking', 'Visual progress bars show how close you are to reaching each savings goal.', 0, 1, 1),

-- Analytics Page Help Guide - Specific Elements
('help_guide', 'analytics_spending_chart', 1, '/analytics', '.spending-chart', 'Spending Analysis', 'Charts show your spending patterns over time. Look for trends and areas to improve.', 0, 1, 1),
('help_guide', 'analytics_category_breakdown', 2, '/analytics', '.category-breakdown', 'Category Breakdown', 'See what percentage of your budget goes to each category. Identify your biggest expenses.', 0, 1, 1),
('help_guide', 'analytics_insights', 3, '/analytics', '.insights-panel', 'Financial Insights', 'Get personalized recommendations to improve your financial health and reach goals faster.', 0, 1, 1);



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
-- USER FEEDBACK SYSTEM TABLES
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

SELECT 'Feedback System Tables Created Successfully!' as STATUS;

-- Privacy System for Budget App
-- Creates tables for PIN protection and privacy settings

CREATE TABLE IF NOT EXISTS user_privacy_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    privacy_pin VARCHAR(255), -- Hashed PIN
    privacy_enabled BOOLEAN DEFAULT FALSE,
    pin_set_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_pin_reset TIMESTAMP NULL,
    pin_reset_token VARCHAR(100) NULL,
    pin_reset_expires TIMESTAMP NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_privacy (user_id)
);

-- Privacy session tracking (for temporary access)
CREATE TABLE IF NOT EXISTS privacy_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, session_token),
    INDEX idx_expires (expires_at)
);

-- Insert default privacy settings for existing users
INSERT IGNORE INTO user_privacy_settings (user_id, privacy_enabled) 
SELECT id, FALSE FROM users;

-- Database Update Script for Cycle Integration
-- Run this on your existing database to add cycle functionality

USE budget;

-- Update triggers to handle cycle performance updates
DROP TRIGGER IF EXISTS after_contribution_insert;

DELIMITER //
CREATE TRIGGER after_contribution_insert 
AFTER INSERT ON family_contributions
FOR EACH ROW
BEGIN
    DECLARE v_member_id INT;
    DECLARE v_member_type VARCHAR(10);
    
    -- Update family pool
    UPDATE family_groups 
    SET total_pool = total_pool + NEW.amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.family_id;
    
    -- Determine member info
    IF NEW.contributor_type = 'user' AND NEW.member_id IS NOT NULL THEN
        SET v_member_id = NEW.member_id;
        SET v_member_type = 'user';
        
        UPDATE family_members 
        SET total_contributed = total_contributed + NEW.amount,
            current_month_contributed = current_month_contributed + NEW.amount,
            last_payment_date = NEW.contribution_date
        WHERE id = NEW.member_id;
        
    ELSEIF NEW.contributor_type = 'member' AND NEW.member_only_id IS NOT NULL THEN
        SET v_member_id = NEW.member_only_id;
        SET v_member_type = 'member';
        
        UPDATE family_members_only 
        SET total_contributed = total_contributed + NEW.amount,
            current_month_contributed = current_month_contributed + NEW.amount,
            last_payment_date = NEW.contribution_date
        WHERE id = NEW.member_only_id;
    END IF;
    
    -- Update member monthly performance if cycle exists
    IF v_member_id IS NOT NULL THEN
        UPDATE member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        SET 
            mmp.contributed_amount = mmp.contributed_amount + NEW.amount,
            mmp.contribution_count = mmp.contribution_count + 1,
            mmp.last_contribution_date = NEW.contribution_date,
            mmp.first_contribution_date = COALESCE(mmp.first_contribution_date, NEW.contribution_date),
            mmp.is_completed = (mmp.contributed_amount + NEW.amount >= mmp.target_amount),
            mmp.completed_date = CASE 
                WHEN (mmp.contributed_amount + NEW.amount >= mmp.target_amount) AND mmp.completed_date IS NULL 
                THEN NEW.contribution_date 
                ELSE mmp.completed_date 
            END
        WHERE mc.family_id = NEW.family_id 
        AND mc.is_current = TRUE 
        AND mc.is_closed = FALSE
        AND ((v_member_type = 'user' AND mmp.member_id = v_member_id) OR 
             (v_member_type = 'member' AND mmp.member_only_id = v_member_id));
             
        -- Update cycle totals
        UPDATE monthly_cycles mc
        SET 
            mc.total_collected = (
                SELECT COALESCE(SUM(mmp2.contributed_amount), 0) 
                FROM member_monthly_performance mmp2 
                WHERE mmp2.cycle_id = mc.id
            ),
            mc.members_completed = (
                SELECT COUNT(*) 
                FROM member_monthly_performance mmp2 
                WHERE mmp2.cycle_id = mc.id AND mmp2.is_completed = TRUE
            ),
            mc.members_pending = (
                SELECT COUNT(*) 
                FROM member_monthly_performance mmp2 
                WHERE mmp2.cycle_id = mc.id AND mmp2.is_completed = FALSE
            )
        WHERE mc.family_id = NEW.family_id 
        AND mc.is_current = TRUE 
        AND mc.is_closed = FALSE;
        
        -- Update member goal completion status
        IF v_member_type = 'user' THEN
            UPDATE family_members fm
            SET goal_met_this_month = (fm.current_month_contributed >= fm.monthly_contribution_goal)
            WHERE fm.id = v_member_id;
        ELSE
            UPDATE family_members_only fmo
            SET goal_met_this_month = (fmo.current_month_contributed >= fmo.monthly_contribution_goal)
            WHERE fmo.id = v_member_id;
        END IF;
    END IF;
END//

DELIMITER ;

-- Create initialization procedure for existing families
DELIMITER //

CREATE PROCEDURE InitializeExistingFamilyCycles()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_family_id INT;
    DECLARE v_current_month VARCHAR(7);
    DECLARE v_cycle_exists INT;
    
    DECLARE family_cursor CURSOR FOR
        SELECT id FROM family_groups WHERE id > 0;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SET v_current_month = DATE_FORMAT(CURDATE(), '%Y-%m');
    
    OPEN family_cursor;
    
    family_loop: LOOP
        FETCH family_cursor INTO v_family_id;
        
        IF done THEN
            LEAVE family_loop;
        END IF;
        
        -- Check if current cycle already exists
        SELECT COUNT(*) INTO v_cycle_exists
        FROM monthly_cycles 
        WHERE family_id = v_family_id 
        AND cycle_month = v_current_month;
        
        -- Create cycle if it doesn't exist
        IF v_cycle_exists = 0 THEN
            CALL CreateNewMonthlyCycle(v_family_id, YEAR(CURDATE()), MONTH(CURDATE()));
        END IF;
        
    END LOOP;
    
    CLOSE family_cursor;
END //

DELIMITER ;

-- Reset current month contributions (since we're implementing cycles)
UPDATE family_members SET current_month_contributed = 0.00, goal_met_this_month = FALSE;
UPDATE family_members_only SET current_month_contributed = 0.00, goal_met_this_month = FALSE;

-- Initialize cycles for all existing families
CALL InitializeExistingFamilyCycles();

-- Update current month contributions based on current month's actual contributions
UPDATE family_members fm
SET current_month_contributed = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_id = fm.id 
    AND fc.contributor_type = 'user'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
),
goal_met_this_month = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_id = fm.id 
    AND fc.contributor_type = 'user'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
) >= fm.monthly_contribution_goal;

UPDATE family_members_only fmo
SET current_month_contributed = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_only_id = fmo.id 
    AND fc.contributor_type = 'member'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
),
goal_met_this_month = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_only_id = fmo.id 
    AND fc.contributor_type = 'member'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
) >= fmo.monthly_contribution_goal;

-- Update member performance records with current month data
UPDATE member_monthly_performance mmp
JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
SET mmp.contributed_amount = (
    CASE 
        WHEN mmp.member_type = 'user' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_id = mmp.member_id 
            AND fc.contributor_type = 'user'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        WHEN mmp.member_type = 'member' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_only_id = mmp.member_only_id 
            AND fc.contributor_type = 'member'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        ELSE 0
    END
),
mmp.contribution_count = (
    CASE 
        WHEN mmp.member_type = 'user' THEN (
            SELECT COUNT(*)
            FROM family_contributions fc
            WHERE fc.member_id = mmp.member_id 
            AND fc.contributor_type = 'user'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        WHEN mmp.member_type = 'member' THEN (
            SELECT COUNT(*)
            FROM family_contributions fc
            WHERE fc.member_only_id = mmp.member_only_id 
            AND fc.contributor_type = 'member'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        ELSE 0
    END
),
mmp.is_completed = (
    CASE 
        WHEN mmp.member_type = 'user' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_id = mmp.member_id 
            AND fc.contributor_type = 'user'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        ) >= mmp.target_amount
        WHEN mmp.member_type = 'member' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_only_id = mmp.member_only_id 
            AND fc.contributor_type = 'member'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        ) >= mmp.target_amount
        ELSE FALSE
    END
)
WHERE mc.is_current = TRUE;

-- Update cycle totals
UPDATE monthly_cycles mc
SET 
    mc.total_collected = (
        SELECT COALESCE(SUM(mmp.contributed_amount), 0) 
        FROM member_monthly_performance mmp 
        WHERE mmp.cycle_id = mc.id
    ),
    mc.members_completed = (
        SELECT COUNT(*) 
        FROM member_monthly_performance mmp 
        WHERE mmp.cycle_id = mc.id AND mmp.is_completed = TRUE
    ),
    mc.members_pending = (
        SELECT COUNT(*) 
        FROM member_monthly_performance mmp 
        WHERE mmp.cycle_id = mc.id AND mmp.is_completed = FALSE
    )
WHERE mc.is_current = TRUE;

-- Create a maintenance job procedure
DELIMITER //

CREATE PROCEDURE RunCycleMaintenance()
BEGIN
    -- Auto-close overdue cycles (3 days after month end)
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_cycle_id INT;
    DECLARE v_family_id INT;
    DECLARE v_end_date DATE;
    
    DECLARE overdue_cursor CURSOR FOR
        SELECT id, family_id, end_date 
        FROM monthly_cycles 
        WHERE is_current = TRUE 
        AND is_closed = FALSE 
        AND end_date <= DATE_SUB(CURDATE(), INTERVAL 3 DAY);
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN overdue_cursor;
    
    maintenance_loop: LOOP
        FETCH overdue_cursor INTO v_cycle_id, v_family_id, v_end_date;
        
        IF done THEN
            LEAVE maintenance_loop;
        END IF;
        
        -- Auto-close cycle
        CALL CloseMonthlyCycle(v_cycle_id, 1); -- System user
        
        -- Log the auto-close
        INSERT INTO activity_logs (family_id, action_type, description, created_at)
        VALUES (v_family_id, 'cycle_auto_closed', 
                CONCAT('Cycle automatically closed after grace period: ', v_end_date), 
                NOW());
        
    END LOOP;
    
    CLOSE overdue_cursor;
    
    -- Ensure all families have current cycles
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
        COALESCE(SUM(fm.monthly_contribution_goal), 0) + COALESCE(SUM(fmo.monthly_contribution_goal), 0),
        COUNT(fm.id) + COUNT(fmo.id)
    FROM family_groups fg
    LEFT JOIN family_members fm ON fg.id = fm.family_id AND fm.is_active = TRUE
    LEFT JOIN family_members_only fmo ON fg.id = fmo.family_id AND fmo.is_active = TRUE
    LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id AND mc.is_current = TRUE
    WHERE mc.id IS NULL
    GROUP BY fg.id
    HAVING COUNT(fm.id) + COUNT(fmo.id) > 0;
    
    -- Create performance records for new cycles
    INSERT INTO member_monthly_performance (
        cycle_id,
        family_id,
        member_id,
        member_type,
        target_amount
    )
    SELECT 
        mc.id,
        mc.family_id,
        fm.id,
        'user',
        fm.monthly_contribution_goal
    FROM monthly_cycles mc
    JOIN family_members fm ON mc.family_id = fm.family_id
    LEFT JOIN member_monthly_performance mmp ON mc.id = mmp.cycle_id AND mmp.member_id = fm.id
    WHERE mc.is_current = TRUE 
    AND fm.is_active = TRUE 
    AND fm.monthly_contribution_goal > 0
    AND mmp.id IS NULL;
    
    INSERT INTO member_monthly_performance (
        cycle_id,
        family_id,
        member_only_id,
        member_type,
        target_amount
    )
    SELECT 
        mc.id,
        mc.family_id,
        fmo.id,
        'member',
        fmo.monthly_contribution_goal
    FROM monthly_cycles mc
    JOIN family_members_only fmo ON mc.family_id = fmo.family_id
    LEFT JOIN member_monthly_performance mmp ON mc.id = mmp.cycle_id AND mmp.member_only_id = fmo.id
    WHERE mc.is_current = TRUE 
    AND fmo.is_active = TRUE 
    AND fmo.monthly_contribution_goal > 0
    AND mmp.id IS NULL;
    
END //

DELIMITER ;

-- Create indexes for performance
-- Note: If you get "Duplicate key name" errors, it means indexes already exist - you can ignore these errors

-- Index for contribution queries by family and date
CREATE INDEX idx_contributions_current_month ON family_contributions(family_id, contribution_date);

-- Index for finding current active cycles  
CREATE INDEX idx_cycles_current ON monthly_cycles(family_id, is_current, is_closed);

-- Index for member performance lookups
CREATE INDEX idx_performance_cycle_member ON member_monthly_performance(cycle_id, member_id, member_only_id);

-- Create a view for easy dashboard queries
CREATE OR REPLACE VIEW v_family_dashboard_summary AS
SELECT 
    fg.id as family_id,
    fg.family_name,
    fg.total_pool,
    mc.id as current_cycle_id,
    mc.cycle_month,
    mc.total_target,
    mc.total_collected,
    mc.members_completed,
    mc.members_pending,
    DATEDIFF(mc.end_date, CURDATE()) as days_remaining,
    CASE 
        WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
        ELSE 0 
    END as completion_percentage,
    (SELECT COUNT(*) FROM family_members WHERE family_id = fg.id AND is_active = TRUE) +
    (SELECT COUNT(*) FROM family_members_only WHERE family_id = fg.id AND is_active = TRUE) as total_members,
    (SELECT COALESCE(SUM(deficit_amount), 0) FROM member_debt_history WHERE family_id = fg.id AND is_cleared = FALSE) as total_outstanding_debt,
    (SELECT COUNT(DISTINCT CASE 
        WHEN member_type = 'user' THEN member_id 
        ELSE member_only_id 
    END) FROM member_debt_history WHERE family_id = fg.id AND is_cleared = FALSE) as members_with_debt
FROM family_groups fg
LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id AND mc.is_current = TRUE AND mc.is_closed = FALSE;

-- Add system setting for auto-maintenance
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('cycle_auto_maintenance', '1', 'Enable automatic cycle maintenance (auto-close overdue cycles)'),
('cycle_grace_period_days', '3', 'Days after month end before auto-closing cycles'),
('debt_auto_clear', '1', 'Automatically clear oldest debt when member completes monthly goal'),
('cycle_reminder_enabled', '1', 'Send reminders before cycle ends'),
('cycle_reminder_days_before', '7,3,1', 'Days before cycle end to send reminders (comma-separated)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Log the update
INSERT INTO activity_logs (action_type, description, created_at)
VALUES ('system_update', 'Cycle management system installed and initialized', NOW());

-- Display completion message
SELECT 'Cycle management system successfully installed!' as status,
       'Run CALL RunCycleMaintenance(); periodically to maintain cycles' as maintenance_note,
       'All existing data has been preserved and integrated' as data_note;