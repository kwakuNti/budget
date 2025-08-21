<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$family_id = $_SESSION['family_id'];

try {
    // Get expense statistics
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total_expenses,
            COALESCE(SUM(CASE WHEN MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) as this_month_expenses,
            COALESCE(AVG(monthly_total), 0) as average_monthly,
            0 as total_change,
            0 as monthly_change,
            0 as average_change
        FROM personal_expenses pe
        LEFT JOIN (
            SELECT 
                YEAR(expense_date) as year, 
                MONTH(expense_date) as month,
                SUM(amount) as monthly_total
            FROM personal_expenses 
            WHERE family_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(expense_date), MONTH(expense_date)
        ) monthly_avg ON 1=1
        WHERE pe.family_id = ?
    ");
    $stmt->bind_param("ii", $family_id, $family_id);
    $stmt->execute();
    $expense_stats = $stmt->get_result()->fetch_assoc();
    
    // Get top category
    $stmt = $conn->prepare("
        SELECT expense_type, SUM(amount) as total,
            (SUM(amount) / (SELECT SUM(amount) FROM personal_expenses WHERE family_id = ? AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE()))) * 100 as percentage
        FROM personal_expenses 
        WHERE family_id = ? AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())
        GROUP BY expense_type 
        ORDER BY total DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ii", $family_id, $family_id);
    $stmt->execute();
    $top_category_data = $stmt->get_result()->fetch_assoc();
    
    $expense_stats['top_category'] = $top_category_data ? ucfirst($top_category_data['expense_type']) : 'None';
    $expense_stats['top_category_amount'] = $top_category_data ? floatval($top_category_data['total']) : 0;
    $expense_stats['top_category_percent'] = $top_category_data ? round(floatval($top_category_data['percentage']), 1) : 0;
    
    // Get recent expenses
    $stmt = $conn->prepare("
        SELECT * FROM personal_expenses 
        WHERE family_id = ? 
        ORDER BY expense_date DESC, id DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $recent_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'expense_stats' => $expense_stats,
        'recent_expenses' => $recent_expenses
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading expense data: ' . $e->getMessage()
    ]);
}
?>
