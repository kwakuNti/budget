<?php
// Script to add missing tables to the budget database
require_once 'config/connection.php';

echo "Creating missing tables...\n";

try {
    // Create salaries table if it doesn't exist
    $salariesTable = "
    CREATE TABLE IF NOT EXISTS salaries (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        monthly_salary DECIMAL(12,2) NOT NULL,
        pay_frequency ENUM('weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
        next_pay_date DATE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($salariesTable)) {
        echo "✓ Salaries table created/verified\n";
    } else {
        echo "✗ Error creating salaries table: " . $conn->error . "\n";
    }
    
    // Create budget_categories table if it doesn't exist
    $budgetCategoriesTable = "
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
    
    if ($conn->query($budgetCategoriesTable)) {
        echo "✓ Budget categories table created/verified\n";
    } else {
        echo "✗ Error creating budget categories table: " . $conn->error . "\n";
    }
    
    // Create personal_expenses table if it doesn't exist
    $personalExpensesTable = "
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
    
    if ($conn->query($personalExpensesTable)) {
        echo "✓ Personal expenses table created/verified\n";
    } else {
        echo "✗ Error creating personal expenses table: " . $conn->error . "\n";
    }
    
    // Create personal_income table if it doesn't exist
    $personalIncomeTable = "
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
    
    if ($conn->query($personalIncomeTable)) {
        echo "✓ Personal income table created/verified\n";
    } else {
        echo "✗ Error creating personal income table: " . $conn->error . "\n";
    }
    
    // Add indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_salaries_user ON salaries(user_id, is_active)",
        "CREATE INDEX IF NOT EXISTS idx_budget_categories_user ON budget_categories(user_id, is_active)",
        "CREATE INDEX IF NOT EXISTS idx_personal_expenses_user_date ON personal_expenses(user_id, expense_date)",
        "CREATE INDEX IF NOT EXISTS idx_personal_expenses_category ON personal_expenses(category_id)",
        "CREATE INDEX IF NOT EXISTS idx_personal_income_user_date ON personal_income(user_id, income_date)"
    ];
    
    foreach ($indexes as $index) {
        if ($conn->query($index)) {
            echo "✓ Index created/verified\n";
        } else {
            echo "✗ Error creating index: " . $conn->error . "\n";
        }
    }
    
    echo "\nAll tables and indexes created successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
