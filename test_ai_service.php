<?php
// test_ai_service.php - Simple test for AI service
require_once 'api/ai_service.php';

// Test AI service
$aiService = new AIService();

// Sample financial data for testing
$testData = [
    'financial_health' => [
        'health_score' => 75,
        'total_income' => 5000,
        'total_expenses' => 3500,
        'savings_rate' => 15,
        'emergency_fund_months' => 2
    ],
    'spending_patterns' => [
        'categories' => [
            ['name' => 'Food', 'amount' => 800],
            ['name' => 'Transportation', 'amount' => 600],
            ['name' => 'Entertainment', 'amount' => 400]
        ]
    ]
];

echo "Testing AI Chat Response:\n";
echo "========================\n";
$chatResponse = $aiService->generateChatResponse("How can I improve my savings?", $testData);
echo $chatResponse . "\n\n";

echo "Testing AI Predictive Insights:\n";
echo "==============================\n";
$insights = $aiService->generatePredictiveInsights($testData);
echo $insights . "\n\n";

echo "AI Service Test Complete!\n";
?>
