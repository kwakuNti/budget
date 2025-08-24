<?php
// Test API endpoint simulation
echo "Testing Enhanced Insights API Endpoint...\n\n";

// Start output buffering to catch any HTML output
ob_start();

// Simulate session and request
session_start();
$_SESSION['user_id'] = 2;
$_GET['action'] = 'financial_health';

// Set content type header
header('Content-Type: application/json');

try {
    // Include the API file
    include 'api/enhanced_insights_data.php';
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// Get the captured output
$output = ob_get_clean();

echo "API Output:\n";
echo $output . "\n";

// Check if output is valid JSON
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\nOutput is valid JSON!\n";
    echo "Formatted output:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "\nOutput is NOT valid JSON!\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Raw output length: " . strlen($output) . "\n";
    echo "First 500 characters:\n";
    echo substr($output, 0, 500) . "\n";
}
?>
