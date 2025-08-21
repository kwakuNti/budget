<?php
/**
 * Add budget_category_id column to personal_goals table
 */

require_once 'config/connection.php';

try {
    echo "Adding budget_category_id column to personal_goals table...\n";
    
    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM personal_goals LIKE 'budget_category_id'");
    
    if ($result->num_rows == 0) {
        // Add budget_category_id column
        $sql = "ALTER TABLE personal_goals 
                ADD COLUMN budget_category_id INT NULL 
                AFTER target_date";
        
        if ($conn->query($sql)) {
            echo "✓ budget_category_id column added successfully\n";
        } else {
            echo "✗ Error adding budget_category_id column: " . $conn->error . "\n";
        }
        
        // Add foreign key constraint
        $sql = "ALTER TABLE personal_goals 
                ADD FOREIGN KEY (budget_category_id) REFERENCES budget_categories(id) ON DELETE SET NULL";
        
        if ($conn->query($sql)) {
            echo "✓ Foreign key constraint added successfully\n";
        } else {
            echo "✗ Error adding foreign key constraint: " . $conn->error . "\n";
        }
    } else {
        echo "✓ budget_category_id column already exists\n";
    }
    
    // Check and add additional columns for goal management
    $columnsToAdd = [
        'auto_save_enabled' => "BOOLEAN DEFAULT FALSE AFTER priority",
        'save_method' => "ENUM('percentage', 'fixed', 'manual') DEFAULT 'manual' AFTER auto_save_enabled",
        'save_percentage' => "DECIMAL(5,2) DEFAULT 0.00 AFTER save_method",
        'save_amount' => "DECIMAL(10,2) DEFAULT 0.00 AFTER save_percentage",
        'deduct_from_income' => "BOOLEAN DEFAULT FALSE AFTER save_amount"
    ];
    
    foreach ($columnsToAdd as $columnName => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM personal_goals LIKE '$columnName'");
        
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE personal_goals ADD COLUMN $columnName $definition";
            if ($conn->query($sql)) {
                echo "✓ $columnName column added successfully\n";
            } else {
                echo "✗ Error adding $columnName column: " . $conn->error . "\n";
            }
        } else {
            echo "✓ $columnName column already exists\n";
        }
    }
    
    echo "\nDatabase schema update completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
