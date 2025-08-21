<?php
// test_enhanced_ai.php - Test enhanced AI with detailed user data
require_once 'api/ai_service.php';

// Enhanced test data with more realistic user financial profile
$testData = [
    'financial_health' => [
        'health_score' => 68,
        'total_income' => 4500,
        'total_expenses' => 3200,
        'savings_rate' => 8,
        'emergency_fund_months' => 1.5,
        'expense_ratio' => 71
    ],
    'spending_patterns' => [
        'categories' => [
            ['name' => 'Rent', 'amount' => 1200],
            ['name' => 'Food & Dining', 'amount' => 650],
            ['name' => 'Transportation', 'amount' => 450],
            ['name' => 'Entertainment', 'amount' => 380],
            ['name' => 'Shopping', 'amount' => 320],
            ['name' => 'Utilities', 'amount' => 200]
        ],
        'daily_patterns' => [
            ['day_name' => 'Monday', 'avg_amount' => 45],
            ['day_name' => 'Friday', 'avg_amount' => 95],
            ['day_name' => 'Saturday', 'avg_amount' => 120],
            ['day_name' => 'Sunday', 'avg_amount' => 85]
        ]
    ],
    'goals' => [
        'total_goals' => 3,
        'active_goals' => 3,
        'completion_rate' => 35,
        'goals' => [
            ['goal_name' => 'Emergency Fund', 'target_amount' => 10000, 'current_amount' => 2500, 'completion_percentage' => 25],
            ['goal_name' => 'Vacation Fund', 'target_amount' => 3000, 'current_amount' => 1500, 'completion_percentage' => 50],
            ['goal_name' => 'New Car Down Payment', 'target_amount' => 8000, 'current_amount' => 2400, 'completion_percentage' => 30]
        ]
    ],
    'budget_performance' => [
        'budget_utilization' => 89,
        'variance' => 280 // over budget
    ],
    'income_trends' => [
        'monthly_income' => 4500,
        'income_trend' => 'stable'
    ]
];

echo "🧪 Testing Enhanced AI with Detailed User Profile\n";
echo "================================================\n\n";

$aiService = new AIService();

echo "💰 User Profile Summary:\n";
echo "- Income: $4,500/month\n";
echo "- Expenses: $3,200/month  \n";
echo "- Health Score: 68/100\n";
echo "- Savings Rate: 8%\n";
echo "- Emergency Fund: 1.5 months\n";
echo "- Top Expense: Rent ($1,200)\n\n";

echo "🤖 Testing Specific Chat Questions:\n";
echo "===================================\n\n";

$questions = [
    "How can I improve my savings rate?",
    "Should I be worried about my entertainment spending?",
    "When will I reach my emergency fund goal?",
    "What's the best way to optimize my budget?",
    "Am I spending too much on food and dining?"
];

foreach ($questions as $i => $question) {
    echo ($i + 1) . ". Question: \"$question\"\n";
    echo "   AI Response: ";
    $response = $aiService->generateChatResponse($question, $testData);
    echo $response . "\n\n";
}

echo "🔮 Testing Predictive Insights:\n";
echo "==============================\n";
$insights = $aiService->generatePredictiveInsights($testData);
echo $insights . "\n\n";

echo "✅ Enhanced AI Test Complete!\n";
echo "The AI should now provide highly specific advice based on the user's actual financial data.\n";
?>
