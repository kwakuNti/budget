<?php
/**
 * Setup default categories and data for personal accounts
 */

function setupDefaultPersonalCategories($conn, $userId) {
    // Default categories with icons and colors
    $defaultCategories = [
        // NEEDS
        ['name' => 'Food & Groceries', 'type' => 'needs', 'icon' => '🛒', 'color' => '#e74c3c'],
        ['name' => 'Transportation', 'type' => 'needs', 'icon' => '⛽', 'color' => '#3498db'],
        ['name' => 'Utilities', 'type' => 'needs', 'icon' => '💡', 'color' => '#f39c12'],
        ['name' => 'Rent/Housing', 'type' => 'needs', 'icon' => '🏠', 'color' => '#2ecc71'],
        ['name' => 'Healthcare', 'type' => 'needs', 'icon' => '🏥', 'color' => '#e67e22'],
        ['name' => 'Insurance', 'type' => 'needs', 'icon' => '🛡️', 'color' => '#9b59b6'],
        
        // WANTS
        ['name' => 'Entertainment', 'type' => 'wants', 'icon' => '🎬', 'color' => '#1abc9c'],
        ['name' => 'Shopping', 'type' => 'wants', 'icon' => '🛍️', 'color' => '#e91e63'],
        ['name' => 'Dining Out', 'type' => 'wants', 'icon' => '🍽️', 'color' => '#ff5722'],
        ['name' => 'Hobbies', 'type' => 'wants', 'icon' => '🎮', 'color' => '#795548'],
        ['name' => 'Travel', 'type' => 'wants', 'icon' => '✈️', 'color' => '#607d8b'],
        
        // SAVINGS
        ['name' => 'Emergency Fund', 'type' => 'savings', 'icon' => '🚨', 'color' => '#ff9800'],
        ['name' => 'Investments', 'type' => 'savings', 'icon' => '📈', 'color' => '#4caf50'],
        ['name' => 'Future Goals', 'type' => 'savings', 'icon' => '🎯', 'color' => '#2196f3']
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO budget_categories (user_id, name, category_type, icon, color, budget_limit) 
        VALUES (?, ?, ?, ?, ?, 0.00)
    ");
    
    foreach ($defaultCategories as $category) {
        $stmt->bind_param("issss", 
            $userId, 
            $category['name'], 
            $category['type'], 
            $category['icon'], 
            $category['color']
        );
        $stmt->execute();
    }
    
    return count($defaultCategories);
}

function setupDefaultBudgetAllocation($conn, $userId, $monthlySalary = 0) {
    // Default 50-30-20 allocation with no preset salary
    $stmt = $conn->prepare("
        INSERT INTO personal_budget_allocation 
        (user_id, needs_percentage, wants_percentage, savings_percentage, monthly_salary) 
        VALUES (?, 50, 30, 20, ?)
    ");
    $stmt->bind_param("id", $userId, $monthlySalary);
    return $stmt->execute();
}

function setupDefaultGoals($conn, $userId) {
    $defaultGoals = [
        ['name' => 'Emergency Fund', 'amount' => 5000, 'type' => 'emergency_fund', 'priority' => 'high'],
        ['name' => 'Vacation Savings', 'amount' => 3000, 'type' => 'vacation', 'priority' => 'medium'],
        ['name' => 'Future Goals', 'amount' => 2000, 'type' => 'other', 'priority' => 'low']
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO personal_goals (user_id, goal_name, target_amount, goal_type, priority, target_date, current_amount) 
        VALUES (?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 0)
    ");
    
    foreach ($defaultGoals as $goal) {
        $stmt->bind_param("isdss", 
            $userId, 
            $goal['name'], 
            $goal['amount'], 
            $goal['type'], 
            $goal['priority']
        );
        $stmt->execute();
    }
    
    return count($defaultGoals);
}

function addSampleData($conn, $userId) {
    // Only add basic salary setup - no sample expenses or income
    $stmt = $conn->prepare("
        INSERT INTO salaries (user_id, monthly_salary, pay_frequency, next_pay_date) 
        VALUES (?, 0, 'monthly', DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // No sample expenses or income - user starts with clean slate
}

// Function to check if user needs setup
function needsPersonalSetup($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM budget_categories WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] == 0;
}

// Main setup function
function setupPersonalAccount($conn, $userId, $includeSampleData = true) {
    try {
        $conn->begin_transaction();
        
        $results = [];
        
        // Setup categories
        $categoriesCreated = setupDefaultPersonalCategories($conn, $userId);
        $results['categories_created'] = $categoriesCreated;
        
        // Setup budget allocation
        setupDefaultBudgetAllocation($conn, $userId);
        $results['budget_allocation_created'] = true;
        
        // Setup goals
        $goalsCreated = setupDefaultGoals($conn, $userId);
        $results['goals_created'] = $goalsCreated;
        
        // Add sample data if requested
        if ($includeSampleData) {
            addSampleData($conn, $userId);
            $results['sample_data_added'] = true;
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Personal account setup completed successfully',
            'results' => $results
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Personal setup error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Setup failed: ' . $e->getMessage()
        ];
    }
}
?>
