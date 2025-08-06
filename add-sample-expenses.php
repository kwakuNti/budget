<?php
/**
 * Add sample expense data for testing Recent Transactions
 */

$conn = new mysqli('localhost', 'root', 'root', 'budget');
$userId = 2;

echo "=== ADDING SAMPLE EXPENSE DATA ===" . PHP_EOL . PHP_EOL;

// Sample expenses for the current month
$sampleExpenses = [
    ['description' => 'Grocery shopping at ShopRite', 'amount' => 250.00, 'date' => '2025-08-05', 'method' => 'card'],
    ['description' => 'Uber ride to office', 'amount' => 15.50, 'date' => '2025-08-04', 'method' => 'momo'],
    ['description' => 'Coffee at cafe', 'amount' => 12.00, 'date' => '2025-08-04', 'method' => 'cash'],
    ['description' => 'Lunch at KFC', 'amount' => 35.00, 'date' => '2025-08-03', 'method' => 'card'],
    ['description' => 'Mobile data bundle', 'amount' => 10.00, 'date' => '2025-08-03', 'method' => 'momo'],
    ['description' => 'Pharmacy - medication', 'amount' => 45.00, 'date' => '2025-08-02', 'method' => 'cash'],
    ['description' => 'Gas station fuel', 'amount' => 120.00, 'date' => '2025-08-01', 'method' => 'card'],
    ['description' => 'Movie tickets', 'amount' => 40.00, 'date' => '2025-07-31', 'method' => 'card'],
];

$successCount = 0;
foreach ($sampleExpenses as $expense) {
    $stmt = $conn->prepare("
        INSERT INTO personal_expenses 
        (user_id, amount, description, expense_date, payment_method) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("idsss", $userId, $expense['amount'], $expense['description'], $expense['date'], $expense['method']);
    
    if ($stmt->execute()) {
        echo "✅ Added: {$expense['description']} - ₵{$expense['amount']} ({$expense['date']})" . PHP_EOL;
        $successCount++;
    } else {
        echo "❌ Failed to add: {$expense['description']}" . PHP_EOL;
    }
}

echo PHP_EOL . "📊 SUMMARY:" . PHP_EOL;
echo "   - Added {$successCount} expense records" . PHP_EOL;
echo "   - Total expenses: ₵" . number_format(array_sum(array_column($sampleExpenses, 'amount')), 2) . PHP_EOL;

// Check total count
$result = $conn->query("SELECT COUNT(*) as count FROM personal_expenses WHERE user_id = $userId");
$row = $result->fetch_assoc();
echo "   - User now has {$row['count']} total expense records" . PHP_EOL;

echo PHP_EOL . "✅ Sample expense data added successfully!" . PHP_EOL;
echo "Recent Transactions should now display on the dashboard." . PHP_EOL;

$conn->close();
?>
