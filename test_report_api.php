<?php
// Quick test script to validate the comprehensive report API database queries
require_once 'config/connection.php';

echo "Testing Comprehensive Report API Database Queries\n";
echo "================================================\n\n";

// Test user ID (assuming user 1 exists)
$userId = 1;

try {
    // Test 1: Check if user exists
    $stmt = $conn->prepare("SELECT id, first_name, user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        echo "✅ User found: {$user['first_name']} (Type: {$user['user_type']})\n";
    } else {
        echo "❌ No user found with ID: $userId\n";
        exit;
    }
    
    // Test 2: Monthly income query
    echo "\n🔍 Testing income query...\n";
    $incomeStmt = $conn->prepare("
        SELECT COALESCE(SUM(monthly_salary), 0) as total_income 
        FROM personal_budget_allocation 
        WHERE user_id = ? AND is_active = 1
        LIMIT 1
    ");
    $incomeStmt->bind_param("i", $userId);
    $incomeStmt->execute();
    $monthlyIncome = $incomeStmt->get_result()->fetch_assoc()['total_income'] ?? 0;
    echo "Monthly Income from budget allocation: ₵$monthlyIncome\n";
    
    // Try salaries table if no budget allocation
    if ($monthlyIncome == 0) {
        $salaryStmt = $conn->prepare("
            SELECT COALESCE(monthly_salary, 0) as total_income 
            FROM salaries 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $salaryStmt->bind_param("i", $userId);
        $salaryStmt->execute();
        $monthlyIncome = $salaryStmt->get_result()->fetch_assoc()['total_income'] ?? 0;
        echo "Monthly Income from salaries table: ₵$monthlyIncome\n";
    }
    
    // Test 3: Expenses query
    echo "\n🔍 Testing expenses query...\n";
    $currentMonth = date('Y-m');
    $expenseStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses 
        FROM personal_expenses 
        WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $expenseStmt->bind_param("is", $userId, $currentMonth);
    $expenseStmt->execute();
    $totalExpenses = $expenseStmt->get_result()->fetch_assoc()['total_expenses'] ?? 0;
    echo "Current month expenses: ₵$totalExpenses\n";
    
    // Test 4: Budget categories query
    echo "\n🔍 Testing budget categories query...\n";
    $budgetStmt = $conn->prepare("
        SELECT 
            bc.category_name,
            bc.category_type,
            bc.budget_limit as budgeted_amount,
            COALESCE(SUM(pe.amount), 0) as actual_spent
        FROM budget_categories bc
        LEFT JOIN personal_expenses pe ON bc.category_name = pe.category 
            AND pe.user_id = ? 
            AND DATE_FORMAT(pe.expense_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        WHERE bc.user_id = ? AND bc.is_active = 1
        GROUP BY bc.id, bc.category_name, bc.category_type, bc.budget_limit
        ORDER BY bc.category_type, bc.category_name
    ");
    $budgetStmt->bind_param("ii", $userId, $userId);
    $budgetStmt->execute();
    $budgetCategories = $budgetStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($budgetCategories) . " budget categories\n";
    
    // Test 5: Goals query
    echo "\n🔍 Testing goals query...\n";
    $goalsStmt = $conn->prepare("
        SELECT 
            goal_name,
            target_amount,
            current_amount,
            (current_amount / target_amount * 100) as progress_percentage
        FROM personal_goals 
        WHERE user_id = ?
        ORDER BY progress_percentage DESC
    ");
    $goalsStmt->bind_param("i", $userId);
    $goalsStmt->execute();
    $goals = $goalsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($goals) . " personal goals\n";
    
    // Summary
    echo "\n📊 Summary:\n";
    echo "==========\n";
    echo "Monthly Income: ₵" . number_format($monthlyIncome, 2) . "\n";
    echo "Monthly Expenses: ₵" . number_format($totalExpenses, 2) . "\n";
    echo "Net Savings: ₵" . number_format($monthlyIncome - $totalExpenses, 2) . "\n";
    echo "Budget Categories: " . count($budgetCategories) . "\n";
    echo "Personal Goals: " . count($goals) . "\n";
    
    if (count($goals) > 0) {
        echo "\nGoals Details:\n";
        foreach ($goals as $goal) {
            echo "- {$goal['goal_name']}: " . round($goal['progress_percentage'], 1) . "% complete\n";
        }
    }
    
    echo "\n✅ All database queries executed successfully!\n";
    echo "The API should work properly now.\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}
?>
