-- Additional tables for enhanced savings functionality

-- Personal goal settings for auto-save configuration
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

-- Personal goal contributions tracking
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

-- Auto-save history for tracking deductions from income
CREATE TABLE IF NOT EXISTS personal_auto_save_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    salary_date DATE NOT NULL,
    salary_amount DECIMAL(12,2) NOT NULL,
    total_auto_saved DECIMAL(10,2) NOT NULL,
    remaining_after_saves DECIMAL(12,2) NOT NULL,
    goals_processed JSON, -- Store which goals were processed and amounts
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_auto_save_history_user_date (user_id, salary_date)
);

-- Insert default settings for existing goals
INSERT INTO personal_goal_settings (goal_id, auto_save_enabled, save_method, deduct_from_income)
SELECT 
    id, 
    FALSE, 
    'manual', 
    FALSE
FROM personal_goals 
WHERE id NOT IN (SELECT goal_id FROM personal_goal_settings);
