<?php
session_start();

// Set up session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'personal';
    $_SESSION['first_name'] = 'Test';
}

include_once 'config/connection.php';

echo "=== Current Database State ===\n\n";

$userId = $_SESSION['user_id'];

// Check salary data
echo "1. Salary Data:\n";
$stmt = $conn->prepare("SELECT * FROM salaries WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();
echo json_encode($salary, JSON_PRETTY_PRINT) . "\n\n";

// Check budget allocation
echo "2. Budget Allocation:\n";
$stmt = $conn->prepare("SELECT * FROM personal_budget_allocation WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$allocation = $stmt->get_result()->fetch_assoc();
echo json_encode($allocation, JSON_PRETTY_PRINT) . "\n\n";

// Check income sources
echo "3. Income Sources:\n";
$stmt = $conn->prepare("SELECT * FROM personal_income_sources WHERE user_id = ? AND is_active = TRUE");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$incomeSources = [];
while ($row = $result->fetch_assoc()) {
    $incomeSources[] = $row;
}
echo json_encode($incomeSources, JSON_PRETTY_PRINT) . "\n\n";

// Check if data needs to be inserted
if (!$salary) {
    echo "4. Inserting correct salary data:\n";
    $stmt = $conn->prepare("INSERT INTO salaries (user_id, monthly_salary, pay_frequency, next_pay_date) VALUES (?, 4500.00, 'monthly', '2025-10-10')");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        echo "✓ Salary inserted\n";
    } else {
        echo "✗ Error: " . $stmt->error . "\n";
    }
}

if (!$allocation) {
    echo "5. Inserting correct budget allocation:\n";
    $stmt = $conn->prepare("INSERT INTO personal_budget_allocation (user_id, needs_percentage, wants_percentage, savings_percentage, monthly_salary) VALUES (?, 60, 20, 20, 4500.00)");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        echo "✓ Budget allocation inserted\n";
    } else {
        echo "✗ Error: " . $stmt->error . "\n";
    }
}

if (count($incomeSources) == 0) {
    echo "6. Inserting income source:\n";
    $stmt = $conn->prepare("INSERT INTO personal_income_sources (user_id, source_name, income_type, monthly_amount, payment_frequency, include_in_budget) VALUES (?, 'Other Income', 'other', 2000.00, 'monthly', 1)");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        echo "✓ Income source inserted\n";
    } else {
        echo "✗ Error: " . $stmt->error . "\n";
    }
}

echo "\n=== Data after updates ===\n";

// Re-check all data
$stmt = $conn->prepare("SELECT * FROM salaries WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();
echo "Updated Salary: " . json_encode($salary, JSON_PRETTY_PRINT) . "\n\n";

$stmt = $conn->prepare("SELECT * FROM personal_budget_allocation WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$allocation = $stmt->get_result()->fetch_assoc();
echo "Updated Allocation: " . json_encode($allocation, JSON_PRETTY_PRINT) . "\n\n";

$stmt = $conn->prepare("SELECT * FROM personal_income_sources WHERE user_id = ? AND is_active = TRUE");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$incomeSources = [];
while ($row = $result->fetch_assoc()) {
    $incomeSources[] = $row;
}
echo "Updated Income Sources: " . json_encode($incomeSources, JSON_PRETTY_PRINT) . "\n";
?>
