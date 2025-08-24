<?php
// Simple test to simulate browser request
session_start();
$_SESSION['user_id'] = 2;

// Simulate a GET request
$_GET['action'] = 'financial_health';

// Clear any previous output
ob_clean();
ob_start();

// Include the API and capture output
include 'api/enhanced_insights_data.php';
$output = ob_get_clean();

echo "=== API Response Test ===\n";
echo "Content-Type: application/json\n";
echo "Content-Length: " . strlen($output) . "\n\n";
echo $output . "\n\n";

// Test JSON validity
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Valid JSON!\n";
    echo "Keys: " . implode(', ', array_keys($decoded)) . "\n";
} else {
    echo "❌ Invalid JSON: " . json_last_error_msg() . "\n";
    echo "First 100 chars: " . substr($output, 0, 100) . "\n";
}
?>
