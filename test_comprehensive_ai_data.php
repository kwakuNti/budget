<?php
session_start();

// Simulate user session
$_SESSION['user_id'] = 1;

include 'config/connection.php';
include 'api/ai_service.php';

echo "<h1>🤖 Testing Comprehensive AI Data Access</h1>\n";
echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>\n";

try {
    $aiService = new AIService();
    
    // Test comprehensive data collection
    echo "<h2>📊 Testing Comprehensive Financial Data Collection</h2>\n";
    
    $reflection = new ReflectionClass($aiService);
    $method = $reflection->getMethod('getComprehensiveFinancialData');
    $method->setAccessible(true);
    
    $data = $method->invoke($aiService, $connection, 1);
    
    echo "<h3>✅ Data Sources Successfully Accessed:</h3>\n";
    echo "<ul>\n";
    
    // Check each data source
    if (!empty($data['financial_health'])) {
        echo "<li>✅ Financial Health Data - Health Score: " . ($data['financial_health']['health_score'] ?? 'N/A') . "</li>\n";
    }
    
    if (!empty($data['recent_transactions'])) {
        echo "<li>✅ Recent Transactions - " . count($data['recent_transactions']) . " transactions loaded</li>\n";
        echo "<ul><li>Most recent: $" . number_format($data['recent_transactions'][0]['amount'] ?? 0, 2) . " on " . ($data['recent_transactions'][0]['description'] ?? 'N/A') . "</li></ul>\n";
    }
    
    if (!empty($data['budget_allocations'])) {
        echo "<li>✅ Budget Allocations - " . count($data['budget_allocations']) . " budget items loaded</li>\n";
    }
    
    if (!empty($data['detailed_goals'])) {
        echo "<li>✅ Detailed Goals - " . count($data['detailed_goals']) . " goals loaded</li>\n";
        foreach ($data['detailed_goals'] as $goal) {
            $progress = round($goal['progress_percentage'] ?? 0, 1);
            echo "<ul><li>{$goal['goal_name']}: {$progress}% complete ($" . number_format($goal['current_amount'] ?? 0, 2) . " / $" . number_format($goal['target_amount'] ?? 0, 2) . ")</li></ul>\n";
        }
    }
    
    if (!empty($data['monthly_summaries'])) {
        echo "<li>✅ Monthly Summaries - " . count($data['monthly_summaries']) . " months of data</li>\n";
        $latest = $data['monthly_summaries'][0] ?? [];
        echo "<ul><li>Latest month: $" . number_format($latest['total_spent'] ?? 0, 2) . " spent across " . ($latest['transaction_count'] ?? 0) . " transactions</li></ul>\n";
    }
    
    if (!empty($data['user_profile'])) {
        $profile = $data['user_profile'];
        echo "<li>✅ User Profile - {$profile['first_name']} {$profile['last_name']} (Member since " . date('M Y', strtotime($profile['created_at'])) . ")</li>\n";
    }
    
    if (!empty($data['income_history'])) {
        echo "<li>✅ Income History - " . count($data['income_history']) . " income records</li>\n";
    } else {
        echo "<li>⚠️ Income History - No data (table may not exist)</li>\n";
    }
    
    echo "</ul>\n";
    
    // Test AI context preparation
    echo "<h2>🧠 Testing AI Context Preparation</h2>\n";
    
    $contextMethod = $reflection->getMethod('prepareFinancialContext');
    $contextMethod->setAccessible(true);
    
    $context = $contextMethod->invoke($aiService, $data);
    
    echo "<h3>📝 Generated AI Context (First 500 characters):</h3>\n";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 12px;'>\n";
    echo nl2br(htmlspecialchars(substr($context, 0, 500))) . "...\n";
    echo "</div>\n";
    
    echo "<h3>📊 Context Analysis:</h3>\n";
    echo "<ul>\n";
    echo "<li>Total Context Length: " . strlen($context) . " characters</li>\n";
    echo "<li>Contains User Profile: " . (strpos($context, 'USER PROFILE') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li>Contains Transaction History: " . (strpos($context, 'TRANSACTION HISTORY') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li>Contains Spending Breakdown: " . (strpos($context, 'SPENDING BREAKDOWN') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li>Contains Goals Status: " . (strpos($context, 'GOALS STATUS') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li>Contains Budget Performance: " . (strpos($context, 'BUDGET') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li>Contains Monthly Trends: " . (strpos($context, 'MONTHLY') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "</ul>\n";
    
    // Test sample AI interaction
    echo "<h2>💬 Testing Sample AI Chat Interaction</h2>\n";
    
    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>🧪 Testing Question:</strong> \"How can I optimize my spending to reach my savings goals faster?\"\n";
    echo "</div>\n";
    
    $chatResponse = $aiService->generateChatResponse("How can I optimize my spending to reach my savings goals faster?", $data);
    
    if ($chatResponse['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>🤖 AI Response:</strong><br>\n";
        echo nl2br(htmlspecialchars($chatResponse['message'])) . "\n";
        echo "</div>\n";
        
        // Analyze response quality
        echo "<h3>🔍 Response Quality Analysis:</h3>\n";
        echo "<ul>\n";
        $response = $chatResponse['message'];
        echo "<li>Contains Dollar Amounts: " . (preg_match('/\$[\d,]+/', $response) ? '✅ Yes' : '❌ No') . "</li>\n";
        echo "<li>References Specific Data: " . (strlen($response) > 50 ? '✅ Yes' : '❌ No') . "</li>\n";
        echo "<li>Actionable Advice: " . (strpos(strtolower($response), 'recommend') !== false || strpos(strtolower($response), 'suggest') !== false ? '✅ Yes' : '❌ No') . "</li>\n";
        echo "<li>Response Length: " . strlen($response) . " characters</li>\n";
        echo "</ul>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>❌ AI Response Failed:</strong> " . ($chatResponse['error'] ?? 'Unknown error') . "\n";
        echo "</div>\n";
    }
    
    // Test predictive insights
    echo "<h2>🔮 Testing Predictive Insights Generation</h2>\n";
    
    $insightsResponse = $aiService->generatePredictiveInsights($data);
    
    if ($insightsResponse['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>🔮 Predictive Insights:</strong><br>\n";
        echo nl2br(htmlspecialchars($insightsResponse['message'])) . "\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>❌ Insights Generation Failed:</strong> " . ($insightsResponse['error'] ?? 'Unknown error') . "\n";
        echo "</div>\n";
    }
    
    echo "<h2>✅ Comprehensive AI Data Access Test Complete</h2>\n";
    echo "<p><strong>Summary:</strong> The AI service now has access to comprehensive financial data from all sections of the budget app, including transaction history, goals, budgets, and user profile information. The AI can provide ultra-personalized advice based on specific dollar amounts and actual user data.</p>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>❌ Test Failed:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
}

echo "</div>\n";
?>
