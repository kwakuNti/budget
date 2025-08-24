<?php
// Test enhanced insights functions directly without session issues
require_once 'config/connection.php';

echo "Testing Enhanced Insights Functions...\n\n";

// Copy the function definitions here to test them directly
function getEnhancedFinancialHealth($conn, $user_id) {
    // More comprehensive financial health calculation
    $query = "
        SELECT 
            pba.monthly_salary,
            pba.needs_percentage,
            pba.wants_percentage,
            pba.savings_percentage,
            pba.needs_amount,
            pba.wants_amount,
            pba.savings_amount,
            COALESCE(monthly_expenses.total_expenses, 0) as total_expenses,
            COALESCE(monthly_expenses.needs_expenses, 0) as needs_expenses,
            COALESCE(monthly_expenses.wants_expenses, 0) as wants_expenses,
            COALESCE(total_savings.total_saved, 0) as total_saved,
            COALESCE(total_goals.total_goals, 0) as total_goals,
            COALESCE(completed_goals.completed_goals, 0) as completed_goals
        FROM personal_budget_allocation pba
        LEFT JOIN (
            SELECT 
                pe.user_id,
                SUM(pe.amount) as total_expenses,
                SUM(CASE WHEN bc.category_type = 'needs' THEN pe.amount ELSE 0 END) as needs_expenses,
                SUM(CASE WHEN bc.category_type = 'wants' THEN pe.amount ELSE 0 END) as wants_expenses
            FROM personal_expenses pe
            LEFT JOIN budget_categories bc ON pe.category_id = bc.id
            WHERE MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
            GROUP BY pe.user_id
        ) monthly_expenses ON pba.user_id = monthly_expenses.user_id
        LEFT JOIN (
            SELECT user_id, SUM(current_amount) as total_saved
            FROM personal_goals 
            GROUP BY user_id
        ) total_savings ON pba.user_id = total_savings.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as total_goals
            FROM personal_goals 
            GROUP BY user_id
        ) total_goals ON pba.user_id = total_goals.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as completed_goals
            FROM personal_goals 
            WHERE current_amount >= target_amount
            GROUP BY user_id
        ) completed_goals ON pba.user_id = completed_goals.user_id
        WHERE pba.user_id = ? AND pba.is_active = 1
        ORDER BY pba.created_at DESC 
        LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        return ['error' => 'No budget allocation found for user'];
    }
    
    $income = floatval($result['monthly_salary']);
    $expenses = floatval($result['total_expenses']);
    $needs_expenses = floatval($result['needs_expenses']);
    $wants_expenses = floatval($result['wants_expenses']);
    $total_saved = floatval($result['total_saved']);
    $total_goals = intval($result['total_goals']);
    $completed_goals = intval($result['completed_goals']);
    
    return [
        'health_score' => 85, // placeholder
        'monthly_income' => $income,
        'total_expenses' => $expenses,
        'needs_expenses' => $needs_expenses,
        'wants_expenses' => $wants_expenses,
        'total_saved' => $total_saved,
        'budget' => [
            'monthly_salary' => $income,
            'needs_allocation' => floatval($result['needs_percentage'] ?? 0),
            'wants_allocation' => floatval($result['wants_percentage'] ?? 0),
            'savings_allocation' => floatval($result['savings_percentage'] ?? 0),
            'needs_amount' => floatval($result['needs_amount'] ?? 0),
            'wants_amount' => floatval($result['wants_amount'] ?? 0),
            'savings_amount' => floatval($result['savings_amount'] ?? 0)
        ]
    ];
}

try {
    echo "Testing getEnhancedFinancialHealth function...\n";
    $health_data = getEnhancedFinancialHealth($conn, 2);
    echo "Success! Financial Health Data:\n";
    echo json_encode($health_data, JSON_PRETTY_PRINT) . "\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
