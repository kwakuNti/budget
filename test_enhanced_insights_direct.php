<?php
// Test enhanced insights API directly
session_start();
require_once 'config/connection.php';

// Simulate being logged in as user_id 2 (which has data)
$_SESSION['user_id'] = 2;

echo "Testing Enhanced Insights API...\n\n";

// Include the enhanced insights functions manually to avoid path issues
include 'api/enhanced_insights_data.php';

try {
    echo "Testing getEnhancedFinancialHealth function...\n";
    $health_data = getEnhancedFinancialHealth($conn, 2);
    echo "Success! Financial Health Data:\n";
    echo json_encode($health_data, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Testing getAdvancedSpendingAnalytics function...\n";
    $spending_data = getAdvancedSpendingAnalytics($conn, 2);
    echo "Success! Spending Analytics Data:\n";
    echo json_encode($spending_data, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Testing getComprehensiveInsights function...\n";
    $comprehensive_data = getComprehensiveInsights($conn, 2);
    echo "Success! Comprehensive Insights Data:\n";
    echo json_encode($comprehensive_data, JSON_PRETTY_PRINT) . "\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
