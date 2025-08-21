<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['id'];

try {
    // Get total income (salary + additional income)
    $salary_sql = "SELECT 
        COALESCE(salary, 0) as salary,
        COALESCE(additional_income, 0) as additional_income,
        (COALESCE(salary, 0) + COALESCE(additional_income, 0)) as total_income
        FROM member_salary 
        WHERE member_id = ?";
    
    $salary_stmt = $conn->prepare($salary_sql);
    $salary_stmt->bind_param("i", $user_id);
    $salary_stmt->execute();
    $salary_result = $salary_stmt->get_result();
    $salary_data = $salary_result->fetch_assoc();
    
    if (!$salary_data) {
        $salary_data = [
            'salary' => 0,
            'additional_income' => 0,
            'total_income' => 0
        ];
    }
    
    // Get total allocated budget
    $budget_sql = "SELECT COALESCE(SUM(budget_limit), 0) as total_allocated 
                   FROM budget_categories 
                   WHERE member_id = ?";
    
    $budget_stmt = $conn->prepare($budget_sql);
    $budget_stmt->bind_param("i", $user_id);
    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();
    $budget_data = $budget_result->fetch_assoc();
    
    $total_allocated = $budget_data['total_allocated'] ?? 0;
    $remaining_income = $salary_data['total_income'] - $total_allocated;
    
    // Get allocation percentage
    $allocation_percentage = $salary_data['total_income'] > 0 ? 
        round(($total_allocated / $salary_data['total_income']) * 100, 1) : 0;
    
    // Get number of budget categories
    $categories_sql = "SELECT COUNT(*) as category_count FROM budget_categories WHERE member_id = ?";
    $categories_stmt = $conn->prepare($categories_sql);
    $categories_stmt->bind_param("i", $user_id);
    $categories_stmt->execute();
    $categories_result = $categories_stmt->get_result();
    $categories_data = $categories_result->fetch_assoc();
    
    $response = [
        'salary' => floatval($salary_data['salary']),
        'additional_income' => floatval($salary_data['additional_income']),
        'total_income' => floatval($salary_data['total_income']),
        'total_allocated' => floatval($total_allocated),
        'remaining_income' => floatval($remaining_income),
        'allocation_percentage' => floatval($allocation_percentage),
        'category_count' => intval($categories_data['category_count'] ?? 0)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Salary data API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
