<?php
// Test all API endpoints to see which ones work
session_start();
$_SESSION['user_id'] = 2;

$endpoints = [
    'financial_health',
    'spending_patterns', 
    'personalized_recommendations',
    'goal_optimization',
    'behavioral_insights',
    'ai_predictions'
];

foreach ($endpoints as $endpoint) {
    echo "=== Testing: $endpoint ===\n";
    $_GET['action'] = $endpoint;
    
    ob_start();
    try {
        include 'api/enhanced_insights_data.php';
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Success - " . strlen($output) . " bytes\n";
            if (isset($decoded['error'])) {
                echo "❌ Error in response: " . $decoded['error'] . "\n";
            }
        } else {
            echo "❌ Invalid JSON - Error: " . json_last_error_msg() . "\n";
            echo "First 100 chars: " . substr($output, 0, 100) . "\n";
        }
    } catch (Exception $e) {
        ob_get_clean();
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
