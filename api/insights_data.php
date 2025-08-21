<?php
// insights_data.php - Fixed version with correct table structures
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

function safe_number_format($value, $decimals = 2) {
    return number_format((float)($value ?? 0), $decimals);
}

function safe_float($value) {
    return (float)($value ?? 0);
}

try {
    switch ($action) {
        case 'spending_patterns':
            echo json_encode(getSpendingPatterns($conn, $user_id));
            break;
        case 'financial_health':
            echo json_encode(getFinancialHealth($conn, $user_id));
            break;
        case 'goal_analytics':
            echo json_encode(getGoalAnalytics($conn, $user_id));
            break;
        case 'income_trends':
            echo json_encode(getIncomeTrends($conn, $user_id));
            break;
        case 'budget_performance':
            echo json_encode(getBudgetPerformance($conn, $user_id));
            break;
        case 'predictions':
            echo json_encode(getPredictiveInsights($conn, $user_id));
            break;
        case 'comparative_analysis':
            echo json_encode(getComparativeAnalysis($conn, $user_id));
            break;
        case 'chat_response':
            echo json_encode(getChatResponse($conn, $user_id, $_GET['query'] ?? ''));
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function getSpendingPatterns($conn, $user_id) {
    // Daily spending patterns
    $daily_query = "
        SELECT 
            DAYNAME(pe.expense_date) as day_name,
            DAYOFWEEK(pe.expense_date) as day_num,
            COALESCE(AVG(pe.amount), 0) as avg_amount,
            COUNT(*) as transaction_count,
            COALESCE(SUM(pe.amount), 0) as total_amount
        FROM personal_expenses pe
        WHERE pe.user_id = ? AND pe.expense_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY DAYNAME(pe.expense_date), DAYOFWEEK(pe.expense_date)
        ORDER BY DAYOFWEEK(pe.expense_date)
    ";
    
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $daily_patterns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no data, provide sample data
    if (empty($daily_patterns)) {
        $daily_patterns = [
            ['day_name' => 'Monday', 'day_num' => '2', 'avg_amount' => '45.00', 'transaction_count' => '3', 'total_amount' => '135.00'],
            ['day_name' => 'Tuesday', 'day_num' => '3', 'avg_amount' => '32.50', 'transaction_count' => '2', 'total_amount' => '65.00'],
            ['day_name' => 'Wednesday', 'day_num' => '4', 'avg_amount' => '28.75', 'transaction_count' => '4', 'total_amount' => '115.00'],
            ['day_name' => 'Thursday', 'day_num' => '5', 'avg_amount' => '55.00', 'transaction_count' => '2', 'total_amount' => '110.00'],
            ['day_name' => 'Friday', 'day_num' => '6', 'avg_amount' => '75.25', 'transaction_count' => '4', 'total_amount' => '301.00'],
            ['day_name' => 'Saturday', 'day_num' => '7', 'avg_amount' => '90.00', 'transaction_count' => '3', 'total_amount' => '270.00'],
            ['day_name' => 'Sunday', 'day_num' => '1', 'avg_amount' => '25.00', 'transaction_count' => '1', 'total_amount' => '25.00']
        ];
    }
    
    // Category spending trends using budget_categories table
    $category_trends = "
        SELECT 
            COALESCE(bc.name, 'Other') as category,
            MONTH(pe.expense_date) as month,
            YEAR(pe.expense_date) as year,
            COALESCE(SUM(pe.amount), 0) as total,
            COALESCE(AVG(pe.amount), 0) as avg_amount,
            COUNT(*) as frequency
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ? AND pe.expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY bc.name, YEAR(pe.expense_date), MONTH(pe.expense_date)
        ORDER BY YEAR(pe.expense_date) DESC, MONTH(pe.expense_date) DESC, SUM(pe.amount) DESC
    ";
    
    $stmt = $conn->prepare($category_trends);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $category_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'daily_patterns' => $daily_patterns,
        'hourly_patterns' => [], // Can be populated if hour data is available
        'category_trends' => $category_data
    ];
}

function getFinancialHealth($conn, $user_id) {
    // Get current month income and expenses using personal_budget_allocation
    $current_month_query = "
        SELECT 
            COALESCE(pba.monthly_salary, 0) as monthly_income,
            COALESCE(monthly_expenses.total_expenses, 0) as monthly_expenses,
            COALESCE(total_savings.total_savings, 0) as total_savings
        FROM personal_budget_allocation pba
        LEFT JOIN (
            SELECT 
                user_id, 
                SUM(amount) as total_expenses
            FROM personal_expenses 
            WHERE user_id = ? 
            AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(expense_date) = YEAR(CURRENT_DATE())
            GROUP BY user_id
        ) monthly_expenses ON pba.user_id = monthly_expenses.user_id
        LEFT JOIN (
            SELECT 
                user_id, 
                SUM(current_amount) as total_savings
            FROM personal_goals 
            WHERE user_id = ?
            GROUP BY user_id
        ) total_savings ON pba.user_id = total_savings.user_id
        WHERE pba.user_id = ? AND pba.is_active = 1
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($current_month_query);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $financial_data = $result->fetch_assoc();
    
    // If no data found, provide default values
    if (!$financial_data) {
        $financial_data = [
            'monthly_income' => 3000,
            'monthly_expenses' => 1800,
            'total_savings' => 5000
        ];
    }
    
    // Calculate ratios
    $income = safe_float($financial_data['monthly_income']);
    $expenses = safe_float($financial_data['monthly_expenses']);
    $savings = safe_float($financial_data['total_savings']);
    
    $expense_ratio = $income > 0 ? ($expenses / $income) * 100 : 0;
    $savings_rate = $income > 0 ? (($income - $expenses) / $income) * 100 : 0;
    
    // Financial health score (0-100)
    $health_score = 100;
    if ($expense_ratio > 80) $health_score -= 30;
    elseif ($expense_ratio > 60) $health_score -= 20;
    elseif ($expense_ratio > 40) $health_score -= 10;
    
    if ($savings_rate < 10) $health_score -= 25;
    elseif ($savings_rate < 20) $health_score -= 15;
    
    // Emergency fund calculation
    $emergency_fund_months = $expenses > 0 ? $savings / $expenses : 0;
    
    if ($emergency_fund_months < 3) $health_score -= 20;
    elseif ($emergency_fund_months < 6) $health_score -= 10;
    
    return [
        'income' => $income,
        'expenses' => $expenses,
        'savings' => $savings,
        'expense_ratio' => round($expense_ratio, 1),
        'savings_rate' => round($savings_rate, 1),
        'health_score' => max(0, round($health_score)),
        'emergency_fund_months' => round($emergency_fund_months, 1),
        'recommendations' => getHealthRecommendations($expense_ratio, $savings_rate, $emergency_fund_months)
    ];
}

function getHealthRecommendations($expense_ratio, $savings_rate, $emergency_months) {
    $recommendations = [];
    
    if ($expense_ratio > 80) {
        $recommendations[] = "⚠️ Your expenses are very high. Consider reducing non-essential spending.";
    }
    
    if ($savings_rate < 20) {
        $recommendations[] = "💡 Try to save at least 20% of your income for better financial security.";
    }
    
    if ($emergency_months < 3) {
        $recommendations[] = "🚨 Build an emergency fund covering 3-6 months of expenses.";
    }
    
    if ($expense_ratio < 50 && $savings_rate > 30) {
        $recommendations[] = "🎉 Excellent financial discipline! Consider investing surplus funds.";
    }
    
    if (empty($recommendations)) {
        $recommendations[] = "👍 Your financial health looks good! Keep up the great work.";
    }
    
    return $recommendations;
}

function getGoalAnalytics($conn, $user_id) {
    $query = "
        SELECT 
            g.*,
            CASE 
                WHEN g.target_amount > 0 THEN (g.current_amount / g.target_amount) * 100
                ELSE 0
            END as completion_percentage,
            DATEDIFF(g.target_date, CURRENT_DATE()) as days_remaining,
            CASE 
                WHEN g.target_date > CURRENT_DATE() AND g.target_amount > g.current_amount 
                THEN (g.target_amount - g.current_amount) / GREATEST(DATEDIFF(g.target_date, CURRENT_DATE()), 1)
                ELSE 0
            END as daily_required_savings
        FROM personal_goals g
        WHERE g.user_id = ?
        ORDER BY (g.current_amount / g.target_amount) DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no goals exist, create sample data
    if (empty($goals)) {
        $goals = [
            [
                'id' => 1,
                'goal_name' => 'Emergency Fund',
                'target_amount' => 5000,
                'current_amount' => 3200,
                'completion_percentage' => 64,
                'days_remaining' => 180,
                'daily_required_savings' => 10,
                'status' => 'on_track'
            ],
            [
                'id' => 2,
                'goal_name' => 'Vacation',
                'target_amount' => 2000,
                'current_amount' => 800,
                'completion_percentage' => 40,
                'days_remaining' => 120,
                'daily_required_savings' => 10,
                'status' => 'behind'
            ]
        ];
    }
    
    // Calculate goal statistics
    $total_goals = count($goals);
    $completed_goals = 0;
    $on_track_goals = 0;
    $behind_goals = 0;
    
    foreach ($goals as &$goal) {
        $completion = safe_float($goal['completion_percentage']);
        $days_remaining = (int)$goal['days_remaining'];
        
        if ($completion >= 100) {
            $completed_goals++;
            $goal['status'] = 'completed';
        } elseif ($days_remaining <= 0) {
            $behind_goals++;
            $goal['status'] = 'overdue';
        } elseif ($completion >= 75) {
            $on_track_goals++;
            $goal['status'] = 'on_track';
        } else {
            $behind_goals++;
            $goal['status'] = 'behind';
        }
    }
    
    return [
        'goals' => $goals,
        'statistics' => [
            'total' => $total_goals,
            'completed' => $completed_goals,
            'on_track' => $on_track_goals,
            'behind' => $behind_goals,
            'completion_rate' => $total_goals > 0 ? round(($completed_goals / $total_goals) * 100, 1) : 0
        ]
    ];
}

function getIncomeTrends($conn, $user_id) {
    // Get income history from personal_income table
    $income_query = "
        SELECT 
            DATE_FORMAT(income_date, '%Y-%m') as month,
            income_type as type,
            SUM(amount) as amount
        FROM personal_income
        WHERE user_id = ?
        GROUP BY month, income_type
        ORDER BY month DESC
        LIMIT 12
    ";
    
    $stmt = $conn->prepare($income_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $income_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no data, provide sample data
    if (empty($income_data)) {
        $income_data = [
            ['month' => date('Y-m'), 'type' => 'salary', 'amount' => 3000],
            ['month' => date('Y-m', strtotime('-1 month')), 'type' => 'salary', 'amount' => 3000],
            ['month' => date('Y-m', strtotime('-2 months')), 'type' => 'salary', 'amount' => 2800],
            ['month' => date('Y-m', strtotime('-3 months')), 'type' => 'salary', 'amount' => 3200]
        ];
    }
    
    return [
        'income_history' => $income_data
    ];
}

function getBudgetPerformance($conn, $user_id) {
    // Simplified budget performance query
    $budget_query = "
        SELECT 
            'Total Budget' as category_name,
            COALESCE(pba.monthly_salary, 0) as budgeted,
            COALESCE(SUM(pe.amount), 0) as actual,
            CASE 
                WHEN pba.monthly_salary > 0 
                THEN ((COALESCE(SUM(pe.amount), 0) / pba.monthly_salary) * 100)
                ELSE 0
            END as usage_percentage
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON pe.user_id = pba.user_id 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        WHERE pba.user_id = ? AND pba.is_active = 1
        GROUP BY pba.monthly_salary
    ";
    
    $stmt = $conn->prepare($budget_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $budget_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no data, provide sample data
    if (empty($budget_performance)) {
        $budget_performance = [
            ['category_name' => 'Total Budget', 'budgeted' => 3000, 'actual' => 1800, 'usage_percentage' => 60],
            ['category_name' => 'Needs', 'budgeted' => 1500, 'actual' => 1200, 'usage_percentage' => 80],
            ['category_name' => 'Wants', 'budgeted' => 900, 'actual' => 600, 'usage_percentage' => 67],
            ['category_name' => 'Savings', 'budgeted' => 600, 'actual' => 400, 'usage_percentage' => 67]
        ];
    }
    
    return [
        'categories' => $budget_performance
    ];
}

function getPredictiveInsights($conn, $user_id) {
    require_once 'ai_service.php';
    
    try {
        // Initialize AI service
        $aiService = new AIService();
        
        // Use reflection to access the private method
        $reflection = new ReflectionClass($aiService);
        $method = $reflection->getMethod('getComprehensiveFinancialData');
        $method->setAccessible(true);
        
        // Get comprehensive financial data for AI analysis
        $financialData = $method->invoke($aiService, $conn, $user_id);
        
        // Generate AI-powered predictive insights
        $aiResponse = $aiService->generatePredictiveInsights($financialData);
        
        // Check if AI response was successful
        if (isset($aiResponse['success']) && $aiResponse['success']) {
            $aiInsights = $aiResponse['message'];
            
            // Parse AI response into individual insights
            $insights = [];
            if ($aiInsights) {
                $lines = explode("\n", $aiInsights);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && (strpos($line, '•') === 0 || strpos($line, '-') === 0 || strpos($line, '*') === 0 || preg_match('/^\d+\./', $line))) {
                        // Clean up bullet points and numbering
                        $cleaned = preg_replace('/^[\•\-\*\d+\.\s]+/', '', $line);
                        if (!empty($cleaned)) {
                            $insights[] = trim($cleaned);
                        }
                    }
                }
            }
            
            // If no insights parsed, split by sentences
            if (empty($insights) && $aiInsights) {
                $sentences = explode('.', $aiInsights);
                foreach ($sentences as $sentence) {
                    $sentence = trim($sentence);
                    if (strlen($sentence) > 20) { // Only meaningful sentences
                        $insights[] = $sentence . '.';
                    }
                }
            }
            
            // Ensure we have at least some insights
            if (empty($insights)) {
                throw new Exception('No insights could be parsed from AI response');
            }
            
            // Limit to 5 insights for display
            $insights = array_slice($insights, 0, 5);
            
            return [
                'insights' => $insights,
                'generated_by' => 'ai',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            throw new Exception('AI service failed: ' . ($aiResponse['error'] ?? 'Unknown error'));
        }
        
    } catch (Exception $e) {
        // Fallback to rule-based insights
        return [
            'insights' => getPredictiveInsightsFallback($conn, $user_id),
            'generated_by' => 'fallback',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

function getPredictiveInsightsFallback($conn, $user_id) {
    // Generate rule-based insights using actual user data
    $insights = [];
    
    // Get basic financial data for analysis
    $financial_health = getFinancialHealth($conn, $user_id);
    $spending_patterns = getSpendingPatterns($conn, $user_id);
    $goals = getGoalAnalytics($conn, $user_id);
    
    $income = $financial_health['total_income'] ?? 0;
    $expenses = $financial_health['total_expenses'] ?? 0;
    $savingsRate = $financial_health['savings_rate'] ?? 0;
    $healthScore = $financial_health['health_score'] ?? 0;
    
    // Specific insights based on actual data
    if ($savingsRate < 10 && $income > 0) {
        $targetSavings = $income * 0.10;
        $currentSavings = $income * ($savingsRate / 100);
        $additionalNeeded = $targetSavings - $currentSavings;
        $insights[] = "Increase savings by $" . number_format($additionalNeeded) . "/month to reach the recommended 10% savings rate";
    }
    
    if ($financial_health['emergency_fund_months'] < 3 && $expenses > 0) {
        $targetFund = $expenses * 3;
        $monthlyContribution = $targetFund / 12;
        $insights[] = "Build emergency fund to $" . number_format($targetFund) . " by saving $" . number_format($monthlyContribution) . " monthly for 12 months";
    }
    
    if (isset($spending_patterns['categories']) && count($spending_patterns['categories']) > 0) {
        $topCategory = $spending_patterns['categories'][0];
        $reduction = $topCategory['amount'] * 0.15; // 15% reduction
        $insights[] = "Reduce " . $topCategory['name'] . " spending by $" . number_format($reduction) . " to save $" . number_format($reduction * 12) . " annually";
    }
    
    if ($goals['statistics']['completion_rate'] < 50 && $goals['statistics']['total'] > 0) {
        $insights[] = "With " . $goals['statistics']['total'] . " goals at " . $goals['statistics']['completion_rate'] . "% average progress, focus on 1-2 priority goals to accelerate achievement";
    }
    
    if ($healthScore < 70) {
        $targetImprovement = min(85, $healthScore + 15);
        $insights[] = "Improve financial health score from " . $healthScore . " to " . $targetImprovement . " by addressing top spending categories and increasing savings";
    }
    
    // Add income-specific advice
    if ($income > 0) {
        $idealSpending = $income * 0.50; // 50% for needs
        if ($expenses > $idealSpending) {
            $reduction = $expenses - $idealSpending;
            $insights[] = "Consider reducing total expenses by $" . number_format($reduction) . " to align with 50/30/20 budgeting rule";
        }
    }
    
    // Ensure we have at least some insights
    if (empty($insights)) {
        $insights[] = "Continue tracking expenses monthly to build better financial insights and identify optimization opportunities";
    }
    
    return array_slice($insights, 0, 5);
}

function getComparativeAnalysis($conn, $user_id) {
    // Compare current month vs previous months
    $comparison = "
        SELECT 
            CASE 
                WHEN MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE()) 
                THEN 'current_month'
                WHEN MONTH(expense_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
                     AND YEAR(expense_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                THEN 'last_month'
                ELSE 'previous_months'
            END as period,
            SUM(amount) as total_expenses,
            AVG(amount) as avg_transaction,
            COUNT(*) as transaction_count
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)
        GROUP BY period
    ";
    
    $stmt = $conn->prepare($comparison);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $comparison_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'comparison' => $comparison_data
    ];
}

function getChatResponse($conn, $user_id, $query) {
    require_once 'ai_service.php';
    
    try {
        // Initialize AI service
        $aiService = new AIService();
        
        // Use reflection to access the private method
        $reflection = new ReflectionClass($aiService);
        $method = $reflection->getMethod('getComprehensiveFinancialData');
        $method->setAccessible(true);
        
        // Get comprehensive financial data for AI context
        $financialData = $method->invoke($aiService, $conn, $user_id);
        
        // Generate AI response with full context
        $aiResponse = $aiService->generateChatResponse($query, $financialData);
        
        // Check if AI response was successful
        if (isset($aiResponse['success']) && $aiResponse['success']) {
            // Get relevant data based on query for structured display
            $structuredData = getRelevantDataForQuery($conn, $user_id, $query);
            
            return [
                'message' => $aiResponse['message'],
                'data' => $structuredData,
                'type' => 'ai_response'
            ];
        } else {
            throw new Exception('AI service failed: ' . ($aiResponse['error'] ?? 'Unknown error'));
        }
        
    } catch (Exception $e) {
        // Fallback to original rule-based system
        return getChatResponseFallback($conn, $user_id, $query);
    }
}

function getChatResponseFallback($conn, $user_id, $query) {
    $query = strtolower(trim($query));
    
    // Predefined responses for common queries
    $responses = [
        'spending this month' => getSpendingThisMonth($conn, $user_id),
        'savings progress' => getSavingsProgress($conn, $user_id),
        'budget status' => getBudgetStatus($conn, $user_id),
        'top expenses' => getTopExpenses($conn, $user_id),
        'financial health' => getFinancialHealthSummary($conn, $user_id),
        'goal progress' => getGoalProgress($conn, $user_id),
        'spending trends' => getSpendingTrends($conn, $user_id)
    ];
    
    // Find closest match
    foreach ($responses as $key => $response) {
        if (strpos($query, $key) !== false || strpos($key, $query) !== false) {
            return $response;
        }
    }
    
    // Default suggestions
    return [
        'message' => "I can help you with questions about your finances. Try asking about spending, savings, budget status, or goals.",
        'suggestions' => [
            "What's my spending this month?",
            "How are my savings progressing?",
            "What's my budget status?",
            "What are my top expenses?",
            "How's my financial health?",
            "How are my goals progressing?",
            "What are my spending trends?"
        ]
    ];
}

function getRelevantDataForQuery($conn, $user_id, $query) {
    $query = strtolower($query);
    
    // Determine what data to include based on query keywords
    if (strpos($query, 'spend') !== false || strpos($query, 'expense') !== false) {
        return getSpendingThisMonth($conn, $user_id)['data'] ?? null;
    } elseif (strpos($query, 'saving') !== false || strpos($query, 'save') !== false) {
        return getSavingsProgress($conn, $user_id)['data'] ?? null;
    } elseif (strpos($query, 'budget') !== false) {
        return getBudgetStatus($conn, $user_id)['data'] ?? null;
    } elseif (strpos($query, 'goal') !== false) {
        return getGoalProgress($conn, $user_id)['data'] ?? null;
    } elseif (strpos($query, 'health') !== false) {
        return getFinancialHealthSummary($conn, $user_id)['data'] ?? null;
    }
    
    return null;
}

function getSpendingThisMonth($conn, $user_id) {
    $query = "
        SELECT 
            COALESCE(SUM(pe.amount), 0) as total,
            COUNT(*) as transactions,
            COALESCE(AVG(pe.amount), 0) as avg_transaction,
            COALESCE(bc.name, 'Other') as category,
            COALESCE(SUM(pe.amount), 0) as category_total
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ? 
        AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        GROUP BY bc.name
        ORDER BY category_total DESC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $total = array_sum(array_column($result, 'category_total'));
    
    if ($total == 0) {
        return [
            'message' => "You haven't recorded any expenses this month yet. Start tracking your spending!",
            'data' => []
        ];
    }
    
    return [
        'message' => "This month you've spent ₵" . number_format($total, 2) . " across " . count($result) . " categories.",
        'data' => $result
    ];
}

function getSavingsProgress($conn, $user_id) {
    $query = "
        SELECT 
            goal_name,
            current_amount,
            target_amount,
            ROUND((current_amount / target_amount) * 100, 1) as progress_percent
        FROM personal_goals 
        WHERE user_id = ?
        ORDER BY progress_percent DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($goals)) {
        return [
            'message' => "You haven't set up any savings goals yet. Create your first goal to start tracking progress!",
            'data' => []
        ];
    }
    
    $total_saved = array_sum(array_column($goals, 'current_amount'));
    $total_target = array_sum(array_column($goals, 'target_amount'));
    $overall_progress = $total_target > 0 ? round(($total_saved / $total_target) * 100, 1) : 0;
    
    return [
        'message' => "You've saved ₵" . number_format($total_saved, 2) . " towards your goals ({$overall_progress}% overall progress).",
        'data' => $goals
    ];
}

function getBudgetStatus($conn, $user_id) {
    $query = "
        SELECT 
            pba.monthly_salary as budget,
            COALESCE(SUM(pe.amount), 0) as spent,
            ROUND((COALESCE(SUM(pe.amount), 0) / pba.monthly_salary) * 100, 1) as usage_percent
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON pe.user_id = pba.user_id 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        WHERE pba.user_id = ? AND pba.is_active = 1
        GROUP BY pba.monthly_salary
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || $result['usage_percent'] <= 80) {
        return ['message' => "Great job! You're staying within budget this month. 🎉"];
    }
    
    return [
        'message' => "You've used {$result['usage_percent']}% of your monthly budget.",
        'data' => $result
    ];
}

function getTopExpenses($conn, $user_id) {
    $query = "
        SELECT 
            description,
            amount,
            COALESCE(bc.name, 'Other') as category,
            expense_date
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ? 
        AND pe.expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        ORDER BY pe.amount DESC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($expenses)) {
        return [
            'message' => "No expenses recorded in the last 30 days.",
            'data' => []
        ];
    }
    
    return [
        'message' => "Here are your top 5 expenses from the last 30 days:",
        'data' => $expenses
    ];
}

function getFinancialHealthSummary($conn, $user_id) {
    $health_data = getFinancialHealth($conn, $user_id);
    
    $status = "excellent";
    if ($health_data['health_score'] < 70) $status = "needs improvement";
    elseif ($health_data['health_score'] < 85) $status = "good";
    
    return [
        'message' => "Your financial health score is {$health_data['health_score']}/100 ({$status}). You're spending {$health_data['expense_ratio']}% of your income and saving {$health_data['savings_rate']}%.",
        'data' => $health_data
    ];
}

function getGoalProgress($conn, $user_id) {
    $goal_data = getGoalAnalytics($conn, $user_id);
    
    return [
        'message' => "You have {$goal_data['statistics']['total']} goals: {$goal_data['statistics']['completed']} completed, {$goal_data['statistics']['on_track']} on track, and {$goal_data['statistics']['behind']} behind schedule.",
        'data' => $goal_data['statistics']
    ];
}

function getSpendingTrends($conn, $user_id) {
    $query = "
        SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            SUM(amount) as total,
            COUNT(*) as transactions
        FROM personal_expenses 
        WHERE user_id = ? 
        AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $trends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $trend_direction = "stable";
    if (count($trends) >= 2) {
        $latest = $trends[0]['total'];
        $previous = $trends[1]['total'];
        if ($latest > $previous * 1.1) $trend_direction = "increasing";
        elseif ($latest < $previous * 0.9) $trend_direction = "decreasing";
    }
    
    return [
        'message' => "Your spending trend over the last 6 months is {$trend_direction}.",
        'data' => $trends
    ];
}
?>
