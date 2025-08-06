<?php
// Script to add missing budget_categories table
require_once 'config/connection.php';

try {
    // Create budget_categories table
    $sql = "
    CREATE TABLE IF NOT EXISTS budget_categories (
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
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Budget categories table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    // Create personal_expenses table
    $sql2 = "
    CREATE TABLE IF NOT EXISTS personal_expenses (
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
    )";
    
    if ($conn->query($sql2) === TRUE) {
        echo "Personal expenses table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    // Create personal_income table
    $sql3 = "
    CREATE TABLE IF NOT EXISTS personal_income (
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
    )";
    
    if ($conn->query($sql3) === TRUE) {
        echo "Personal income table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    echo "All personal budget tables have been created!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
