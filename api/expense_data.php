<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/expense_functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$family_id = $_SESSION['family_id'];

try {
    // Get expense statistics
    $expenseStats = getExpenseStats($conn, $family_id);
    
    // Get recent expenses
    $recentExpenses = getAllExpenses($conn, $family_id, 10);
    
    // Get chart data
    $expenseTrendsData = getExpenseTrendsData($conn, $family_id, '6m');
    $categoryData = getCategoryBreakdownData($conn, $family_id, 'current');
    
    // Get quick add suggestions
    $quickSuggestions = getQuickAddSuggestions($conn, $family_id, 6);
    
    echo json_encode([
        'success' => true,
        'expense_stats' => $expenseStats,
        'recent_expenses' => $recentExpenses,
        'expense_trends_data' => $expenseTrendsData,
        'category_data' => $categoryData,
        'quick_suggestions' => $quickSuggestions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving expense data: ' . $e->getMessage()
    ]);
}
?>
