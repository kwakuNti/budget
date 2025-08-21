<?php
session_start();
include_once 'config/connection.php';

echo "=== Testing Salary API Response ===\n\n";

// Test personal dashboard API
echo "1. Testing personal_dashboard_data.php:\n";
$url = "http://localhost/budget/api/personal_dashboard_data.php";
$response = file_get_contents($url);
$data = json_decode($response, true);
echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// Test salary actions API
echo "2. Testing salary_actions.php:\n";
$_POST['action'] = 'get_salary_data';
ob_start();
include 'actions/salary_actions.php';
$salaryResponse = ob_get_clean();
echo "Response: " . $salaryResponse . "\n\n";

// Test if budget allocation data exists
echo "3. Testing budget allocation query:\n";
$user_id = $_SESSION['user_id'] ?? 1;
$stmt = $conn->prepare("SELECT * FROM personal_budget_allocation WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$allocations = $result->fetch_all(MYSQLI_ASSOC);
echo "Budget allocations: " . json_encode($allocations, JSON_PRETTY_PRINT) . "\n\n";
?>
