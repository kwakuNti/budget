<?php
// Direct test of the API to see exact output
session_start();
$_SESSION['user_id'] = 2; // Set user session

// Test different actions
$actions = ['financial_health', 'comprehensive_insights', 'dashboard_insights'];

foreach ($actions as $action) {
    echo "=== Testing action: $action ===\n";
    
    // Capture the API output
    ob_start();
    
    $_GET['action'] = $action;
    
    try {
        include 'api/enhanced_insights_data.php';
        $output = ob_get_clean();
        
        echo "Raw output:\n";
        echo $output . "\n";
        
        // Test if it's valid JSON
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Valid JSON\n";
        } else {
            echo "❌ Invalid JSON - Error: " . json_last_error_msg() . "\n";
            echo "First 200 chars: " . substr($output, 0, 200) . "\n";
        }
        
    } catch (Exception $e) {
        $output = ob_get_clean();
        echo "Exception: " . $e->getMessage() . "\n";
        echo "Output: " . $output . "\n";
    }
    
    echo "\n";
}
?>
