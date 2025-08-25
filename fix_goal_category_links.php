<?php
/**
 * Fix existing goals that don't have budget_category_id set
 * This ensures all goals show up in the budget page's savings section
 */

require_once 'config/connection.php';

try {
    // Get all users
    $stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'personal'");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($users as $user) {
        $userId = $user['id'];
        
        echo "Processing user ID: $userId\n";
        
        // Find or create a "General Savings" category for this user
        $stmt = $conn->prepare("
            SELECT id FROM budget_categories 
            WHERE user_id = ? AND name = 'General Savings' AND category_type = 'savings'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        
        if (!$category) {
            // Create the General Savings category
            $stmt = $conn->prepare("
                INSERT INTO budget_categories 
                (user_id, name, category_type, icon, color, budget_limit, is_active) 
                VALUES (?, 'General Savings', 'savings', 'piggy-bank', '#10b981', 0, 1)
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $categoryId = $conn->insert_id;
            echo "  Created General Savings category with ID: $categoryId\n";
        } else {
            $categoryId = $category['id'];
            echo "  Using existing General Savings category with ID: $categoryId\n";
        }
        
        // Update all goals without budget_category_id to use this category
        $stmt = $conn->prepare("
            UPDATE personal_goals 
            SET budget_category_id = ? 
            WHERE user_id = ? AND (budget_category_id IS NULL OR budget_category_id = 0)
        ");
        $stmt->bind_param("ii", $categoryId, $userId);
        $stmt->execute();
        $updatedGoals = $stmt->affected_rows;
        
        if ($updatedGoals > 0) {
            echo "  Updated $updatedGoals goals to link to General Savings category\n";
        } else {
            echo "  No goals needed updating\n";
        }
    }
    
    echo "\nDone! All goals should now be properly linked to budget categories.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
