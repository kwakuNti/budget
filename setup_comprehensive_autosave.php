<?php
/**
 * Comprehensive Auto-Save System Setup
 * Creates enhanced auto-save tables with per-goal configuration
 */

require_once 'config/connection.php';

echo "<h2>Setting up Comprehensive Auto-Save System...</h2>\n";

try {
    // 1. Enhanced Personal Auto-Save Configuration (per goal)
    $sql = "
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
    );";
    
    $conn->query($sql);
    echo "✓ Personal Goal Auto-Save table created\n";

    // 2. Auto-Save Execution History
    $sql = "
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
    );";
    
    $conn->query($sql);
    echo "✓ Auto-Save History table created\n";

    // 3. Goal Allocation Rules (for advanced allocation)
    $sql = "
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
    );";
    
    $conn->query($sql);
    echo "✓ Goal Allocation Rules table created\n";

    // 4. Enhanced Personal Goals status column
    $sql = "ALTER TABLE personal_goals 
            ADD COLUMN status ENUM('active', 'paused', 'inactive', 'completed') DEFAULT 'active'";
    try {
        $conn->query($sql);
        echo "✓ Personal Goals status column added\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Personal Goals status column already exists\n";
        } else {
            throw $e;
        }
    }

    // 5. Add progress tracking to personal_goals
    $sql = "ALTER TABLE personal_goals 
            ADD COLUMN current_amount DECIMAL(10,2) DEFAULT 0.00,
            ADD COLUMN progress_percentage DECIMAL(5,2) DEFAULT 0.00";
    try {
        $conn->query($sql);
        echo "✓ Progress tracking columns added\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Progress tracking columns already exist\n";
        } else {
            throw $e;
        }
    }

    // 6. Create default global auto-save settings for existing users
    $sql = "
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
    )";
    
    $conn->query($sql);
    echo "✓ Default global auto-save settings created\n";

    // 7. Update existing goals progress
    $sql = "
    UPDATE personal_goals pg
    SET 
        current_amount = COALESCE((
            SELECT SUM(pgc.amount) 
            FROM personal_goal_contributions pgc 
            WHERE pgc.goal_id = pg.id
        ), 0)
    WHERE pg.target_amount > 0";
    
    $conn->query($sql);
    echo "✓ Goal current amounts updated\n";

    // Update progress percentage if column exists
    $checkSql = "SHOW COLUMNS FROM personal_goals LIKE 'progress_percentage'";
    $result = $conn->query($checkSql);
    if ($result->num_rows > 0) {
        $sql = "
        UPDATE personal_goals pg
        SET 
            progress_percentage = CASE 
                WHEN pg.target_amount > 0 THEN 
                    LEAST(100, (pg.current_amount / pg.target_amount) * 100)
                ELSE 0 
            END
        WHERE pg.target_amount > 0";
        
        $conn->query($sql);
        echo "✓ Goal progress percentages updated\n";
    }

    echo "<h3>✅ Comprehensive Auto-Save System Setup Complete!</h3>\n";
    echo "<p>Features enabled:</p>\n";
    echo "<ul>\n";
    echo "<li>✓ Global and per-goal auto-save configuration</li>\n";
    echo "<li>✓ Multiple trigger types (salary, additional income, scheduled)</li>\n";
    echo "<li>✓ Flexible allocation methods (equal split, priority-based, percentage-based)</li>\n";
    echo "<li>✓ Advanced conditions and limits</li>\n";
    echo "<li>✓ Comprehensive execution history</li>\n";
    echo "<li>✓ Goal progress tracking with real-time updates</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "SQL Error: " . $conn->error . "\n";
}

$conn->close();
?>
