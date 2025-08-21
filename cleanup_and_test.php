<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'personal';
}

include_once 'config/connection.php';

$userId = $_SESSION['user_id'];

echo "=== Cleaning up old data and ensuring latest is active ===\n\n";

// 1. Deactivate all old salary entries except the latest
echo "1. Cleaning salary data...\n";
$conn->query("UPDATE salaries SET is_active = 0 WHERE user_id = $userId");
$stmt = $conn->prepare("UPDATE salaries SET is_active = 1 WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
echo "✓ Latest salary entry activated\n";

// 2. Deactivate all old budget allocation entries except the latest  
echo "2. Cleaning budget allocation data...\n";
$conn->query("UPDATE personal_budget_allocation SET is_active = 0 WHERE user_id = $userId");
$stmt = $conn->prepare("UPDATE personal_budget_allocation SET is_active = 1 WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
echo "✓ Latest budget allocation activated\n";

// 3. Test the API calls
echo "\n3. Testing API responses...\n";

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Test personal dashboard API
echo "Testing personal_dashboard_data.php:\n";
$url = "http://localhost/budget/api/personal_dashboard_data.php";
$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . session_name() . "=" . session_id()
    ]
]);
$response = @file_get_contents($url, false, $context);
if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ API working\n";
        echo "Salary: ₵" . ($data['salary']['monthly_salary'] ?? 'N/A') . "\n";
        echo "Monthly Income: ₵" . ($data['financial_overview']['monthly_income'] ?? 'N/A') . "\n";
        echo "Budget Allocation: " . 
             ($data['budget_allocation'][0]['needs_percentage'] ?? 'N/A') . "% / " .
             ($data['budget_allocation'][0]['wants_percentage'] ?? 'N/A') . "% / " .
             ($data['budget_allocation'][0]['savings_percentage'] ?? 'N/A') . "%\n";
    } else {
        echo "✗ API returned error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ Failed to call API\n";
}

echo "\n4. Direct database verification:\n";
// Final verification
$stmt = $conn->prepare("
    SELECT s.monthly_salary, 
           pba.needs_percentage, pba.wants_percentage, pba.savings_percentage,
           COALESCE(SUM(pis.monthly_amount), 0) as additional_income
    FROM salaries s
    LEFT JOIN personal_budget_allocation pba ON s.user_id = pba.user_id AND pba.is_active = 1
    LEFT JOIN personal_income_sources pis ON s.user_id = pis.user_id AND pis.is_active = 1 AND pis.include_in_budget = 1
    WHERE s.user_id = ? AND s.is_active = 1
    GROUP BY s.id, pba.id
    ORDER BY s.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    $salary = floatval($result['monthly_salary']);
    $additional = floatval($result['additional_income']);
    $total = $salary + $additional;
    
    echo "Salary: ₵" . number_format($salary, 2) . "\n";
    echo "Additional Income: ₵" . number_format($additional, 2) . "\n";
    echo "Total Income: ₵" . number_format($total, 2) . "\n";
    echo "Allocation: {$result['needs_percentage']}% / {$result['wants_percentage']}% / {$result['savings_percentage']}%\n";
    
    $needsAmount = ($total * $result['needs_percentage']) / 100;
    $wantsAmount = ($total * $result['wants_percentage']) / 100;
    $savingsAmount = ($total * $result['savings_percentage']) / 100;
    
    echo "Needs Amount: ₵" . number_format($needsAmount, 2) . "\n";
    echo "Wants Amount: ₵" . number_format($wantsAmount, 2) . "\n";
    echo "Savings Amount: ₵" . number_format($savingsAmount, 2) . "\n";
} else {
    echo "✗ No data found\n";
}

echo "\n=== Clean up complete. Refresh your salary page now! ===\n";
?>
