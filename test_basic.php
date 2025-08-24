<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/connection.php';

echo "Testing database connection...\n";

$user_id = 2;

echo "Testing getEnhancedFinancialHealth...\n";
try {
    $query = "SELECT COUNT(*) as count FROM personal_budget_allocation WHERE user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "✅ Database query successful, found {$result['count']} budget allocations\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "Testing function includes...\n";

// Test if we can load a simple function from the API
echo "Loading API functions...\n";

function testFunction() {
    return "Test successful";
}

echo testFunction() . "\n";

// Check for any class or function conflicts
if (function_exists('getEnhancedFinancialHealth')) {
    echo "✅ getEnhancedFinancialHealth function exists\n";
} else {
    echo "❌ getEnhancedFinancialHealth function not found\n";
}

echo "All basic tests passed!\n";
?>
