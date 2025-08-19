<?php
require_once __DIR__ . '/config/connection.php';

// SQL to create the savings tables
$sql = "
-- Additional tables for enhanced savings functionality

-- Personal goal settings for auto-save configuration
CREATE TABLE IF NOT EXISTS personal_goal_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    goal_id INT NOT NULL,
    auto_save_enabled BOOLEAN DEFAULT FALSE,
    save_method ENUM('percentage', 'fixed', 'manual') DEFAULT 'manual',
    save_percentage DECIMAL(5,2) DEFAULT 0.00,
    save_amount DECIMAL(10,2) DEFAULT 0.00,
    deduct_from_income BOOLEAN DEFAULT FALSE,
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
    goals_processed JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_auto_save_history_user_date (user_id, salary_date)
);
";

try {
    // Execute the SQL
    if ($conn->multi_query($sql)) {
        do {
            // Store the result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "✅ Savings tables created successfully!\n";
        
        // Insert default settings for existing goals
        $insertDefaultsSQL = "
        INSERT INTO personal_goal_settings (goal_id, auto_save_enabled, save_method, deduct_from_income)
        SELECT 
            id, 
            FALSE, 
            'manual', 
            FALSE
        FROM personal_goals 
        WHERE id NOT IN (SELECT goal_id FROM personal_goal_settings);
        ";
        
        if ($conn->query($insertDefaultsSQL)) {
            echo "✅ Default settings added for existing goals!\n";
        } else {
            echo "⚠️ Warning: Could not add default settings: " . $conn->error . "\n";
        }
        
    } else {
        throw new Exception("Error creating tables: " . $conn->error);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
