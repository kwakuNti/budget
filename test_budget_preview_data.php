<?php
session_start();

// Set up a fake session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Assuming user 1 exists
    $_SESSION['first_name'] = 'Test';
    $_SESSION['last_name'] = 'User';
}

echo "=== Testing API Responses for Budget Preview ===\n\n";

// Test 1: Personal Dashboard API
echo "1. Personal Dashboard API Response:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/budget/api/personal_dashboard_data.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$dashboardResponse = curl_exec($ch);
curl_close($ch);

$dashboardData = json_decode($dashboardResponse, true);
echo json_encode($dashboardData, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Budget Data API  
echo "2. Budget Data API Response:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/budget/api/budget_data.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$budgetResponse = curl_exec($ch);
curl_close($ch);

$budgetData = json_decode($budgetResponse, true);
echo json_encode($budgetData, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Direct Database Query for Budget Allocation
echo "3. Direct Database Query - Budget Allocation:\n";
include_once 'config/connection.php';
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        needs_percentage,
        wants_percentage,
        savings_percentage,
        monthly_salary,
        needs_amount,
        wants_amount,
        savings_amount,
        created_at
    FROM personal_budget_allocation 
    WHERE user_id = ? AND is_active = TRUE
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Salary Data
echo "4. Salary Data Query:\n";
$stmt = $conn->prepare("
    SELECT 
        monthly_salary,
        next_pay_date,
        pay_frequency
    FROM salaries 
    WHERE user_id = ? AND is_active = TRUE
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$salaryResult = $stmt->get_result()->fetch_assoc();
echo json_encode($salaryResult, JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Calculate Correct Values
echo "5. Correct Calculation Logic:\n";
if ($dashboardData && $dashboardData['success'] && $result) {
    $totalIncome = floatval($dashboardData['financial_overview']['monthly_income']);
    $salary = floatval($salaryResult['monthly_salary'] ?? 0);
    $additionalIncome = $totalIncome - $salary;
    
    $needsPercent = floatval($result['needs_percentage']);
    $wantsPercent = floatval($result['wants_percentage']);
    $savingsPercent = floatval($result['savings_percentage']);
    
    $needsAmount = ($totalIncome * $needsPercent) / 100;
    $wantsAmount = ($totalIncome * $wantsPercent) / 100;
    $savingsAmount = ($totalIncome * $savingsPercent) / 100;
    
    echo "Total Income: ₵" . number_format($totalIncome, 2) . "\n";
    echo "Primary Salary: ₵" . number_format($salary, 2) . "\n";
    echo "Additional Income: ₵" . number_format($additionalIncome, 2) . "\n";
    echo "Allocation Percentages: {$needsPercent}% / {$wantsPercent}% / {$savingsPercent}%\n";
    echo "Needs Amount: ₵" . number_format($needsAmount, 2) . "\n";
    echo "Wants Amount: ₵" . number_format($wantsAmount, 2) . "\n";
    echo "Savings Amount: ₵" . number_format($savingsAmount, 2) . "\n";
}
?>
