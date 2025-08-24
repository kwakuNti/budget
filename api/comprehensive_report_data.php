<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection with multiple fallback paths
$connection_paths = [
    '../config/connection.php',
    '../../config/connection.php',
    dirname(__DIR__) . '/config/connection.php'
];

$connection_included = false;
foreach ($connection_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $connection_included = true;
        break;
    }
}

if (!$connection_included) {
    echo json_encode(['success' => false, 'message' => 'Database connection file not found']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Comprehensive report data structure
    $reportData = [
        'success' => true,
        'data' => [
            'user_info' => getUserInfo($conn, $userId),
            'financial_health' => getFinancialHealth($conn, $userId),
            'income_analysis' => getIncomeAnalysis($conn, $userId),
            'expense_analysis' => getExpenseAnalysis($conn, $userId),
            'budget_performance' => getBudgetPerformance($conn, $userId),
            'spending_analytics' => getSpendingAnalytics($conn, $userId),
            'goals_progress' => getGoalsProgress($conn, $userId),
            'savings_analysis' => getSavingsAnalysis($conn, $userId),
            'trends_forecasts' => getTrendsAndForecasts($conn, $userId),
            'benchmarks' => getBenchmarkData($conn, $userId),
            'recommendations' => getActionableRecommendations($conn, $userId),
            'report_metadata' => getReportMetadata()
        ]
    ];
    
    echo json_encode($reportData);

} catch (Exception $e) {
    error_log("Report API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
}

function getUserInfo($conn, $userId) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return [
        'name' => $result['first_name'] . ' ' . $result['last_name'],
        'first_name' => $result['first_name'],
        'email' => $result['email'],
        'user_type' => $result['user_type']
    ];
}

function getFinancialHealth($conn, $userId) {
    // Get current month income and expenses
    $currentMonth = date('Y-m');
    
    // Get total monthly income from salary and personal income
    $incomeStmt = $conn->prepare("
        SELECT COALESCE(SUM(monthly_salary), 0) as total_income 
        FROM personal_budget_allocation 
        WHERE user_id = ? AND is_active = 1
        LIMIT 1
    ");
    $incomeStmt->bind_param("i", $userId);
    $incomeStmt->execute();
    $monthlyIncome = $incomeStmt->get_result()->fetch_assoc()['total_income'] ?? 0;
    
    // If no budget allocation, try to get from salaries table
    if ($monthlyIncome == 0) {
        $salaryStmt = $conn->prepare("
            SELECT COALESCE(monthly_salary, 0) as total_income 
            FROM salaries 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $salaryStmt->bind_param("i", $userId);
        $salaryStmt->execute();
        $monthlyIncome = $salaryStmt->get_result()->fetch_assoc()['total_income'] ?? 0;
    }
    
    // Get total monthly expenses
    $expenseStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses 
        FROM personal_expenses 
        WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $expenseStmt->bind_param("is", $userId, $currentMonth);
    $expenseStmt->execute();
    $totalExpenses = $expenseStmt->get_result()->fetch_assoc()['total_expenses'] ?? 0;
    
    // Calculate financial health metrics
    $netSavings = $monthlyIncome - $totalExpenses;
    $savingsRate = $monthlyIncome > 0 ? ($netSavings / $monthlyIncome) * 100 : 0;
    $expenseRatio = $monthlyIncome > 0 ? ($totalExpenses / $monthlyIncome) * 100 : 0;
    
    // Calculate health score (0-100)
    $healthScore = calculateHealthScore($savingsRate, $expenseRatio, $monthlyIncome, $totalExpenses);
    
    // Get emergency fund status
    $emergencyFund = getEmergencyFundStatus($conn, $userId, $totalExpenses);
    
    return [
        'health_score' => round($healthScore),
        'monthly_income' => $monthlyIncome,
        'total_expenses' => $totalExpenses,
        'net_savings' => $netSavings,
        'savings_rate' => round($savingsRate, 1),
        'expense_ratio' => round($expenseRatio, 1),
        'emergency_fund' => $emergencyFund,
        'health_breakdown' => [
            'emergency_fund_score' => min(100, ($emergencyFund['months_covered'] / 6) * 100),
            'expense_management_score' => max(0, 100 - $expenseRatio),
            'savings_rate_score' => min(100, $savingsRate * 5), // 20% savings = 100 score
            'goal_progress_score' => getGoalProgressScore($conn, $userId)
        ]
    ];
}

function calculateHealthScore($savingsRate, $expenseRatio, $income, $expenses) {
    $score = 100;
    
    // Deduct for high expense ratio
    if ($expenseRatio > 80) {
        $score -= 25;
    } elseif ($expenseRatio > 70) {
        $score -= 15;
    } elseif ($expenseRatio > 60) {
        $score -= 5;
    }
    
    // Add for good savings rate
    if ($savingsRate >= 20) {
        $score += 10;
    } elseif ($savingsRate >= 15) {
        $score += 5;
    } elseif ($savingsRate < 5) {
        $score -= 15;
    }
    
    // Ensure income is positive
    if ($income <= 0) {
        $score -= 30;
    }
    
    return max(0, min(100, $score));
}

function getEmergencyFundStatus($conn, $userId, $monthlyExpenses) {
    // Check for emergency fund goal
    $stmt = $conn->prepare("
        SELECT current_amount, target_amount 
        FROM personal_goals 
        WHERE user_id = ? AND goal_name LIKE '%emergency%' OR goal_name LIKE '%Emergency%'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $emergencyAmount = $result['current_amount'];
        $monthsCovered = $monthlyExpenses > 0 ? $emergencyAmount / $monthlyExpenses : 0;
        
        return [
            'current_amount' => $emergencyAmount,
            'target_amount' => $result['target_amount'],
            'months_covered' => round($monthsCovered, 1),
            'is_adequate' => $monthsCovered >= 3
        ];
    }
    
    return [
        'current_amount' => 0,
        'target_amount' => $monthlyExpenses * 6,
        'months_covered' => 0,
        'is_adequate' => false
    ];
}

function getGoalProgressScore($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT AVG((current_amount / target_amount) * 100) as avg_progress 
        FROM personal_goals 
        WHERE user_id = ? AND target_amount > 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return round($result['avg_progress'] ?? 0);
}

function getIncomeAnalysis($conn, $userId) {
    // Get current monthly income from budget allocation
    $stmt = $conn->prepare("
        SELECT 
            monthly_salary as total_income,
            created_at,
            DATE_FORMAT(created_at, '%Y-%m') as month
        FROM personal_budget_allocation 
        WHERE user_id = ? AND is_active = 1
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $monthlyIncome = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no budget allocation data, try salaries table
    if (empty($monthlyIncome)) {
        $stmt = $conn->prepare("
            SELECT 
                monthly_salary as total_income,
                created_at,
                DATE_FORMAT(created_at, '%Y-%m') as month
            FROM salaries 
            WHERE user_id = ? AND is_active = 1
            ORDER BY created_at DESC
            LIMIT 6
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $monthlyIncome = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Calculate trends
    $trend = calculateTrend($monthlyIncome, 'total_income');
    
    return [
        'monthly_data' => $monthlyIncome,
        'current_month' => $monthlyIncome[0]['total_income'] ?? 0,
        'previous_month' => $monthlyIncome[1]['total_income'] ?? 0,
        'trend_percentage' => $trend,
        'average_monthly' => array_sum(array_column($monthlyIncome, 'total_income')) / max(1, count($monthlyIncome)),
        'stability_score' => calculateStabilityScore($monthlyIncome, 'total_income')
    ];
}

function getExpenseAnalysis($conn, $userId) {
    // Get last 6 months of expense data
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            SUM(amount) as total_expenses
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $monthlyExpenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get expense by category
    $categoryStmt = $conn->prepare("
        SELECT 
            COALESCE(bc.name, 'Uncategorized') as category,
            SUM(pe.amount) as total_amount,
            COUNT(*) as transaction_count
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ? AND pe.expense_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        GROUP BY COALESCE(bc.name, 'Uncategorized')
        ORDER BY total_amount DESC
    ");
    $categoryStmt->bind_param("i", $userId);
    $categoryStmt->execute();
    $categoryBreakdown = $categoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate trends
    $trend = calculateTrend($monthlyExpenses, 'total_expenses');
    
    return [
        'monthly_data' => $monthlyExpenses,
        'current_month' => $monthlyExpenses[0]['total_expenses'] ?? 0,
        'previous_month' => $monthlyExpenses[1]['total_expenses'] ?? 0,
        'trend_percentage' => $trend,
        'category_breakdown' => $categoryBreakdown,
        'highest_category' => $categoryBreakdown[0] ?? null,
        'average_monthly' => array_sum(array_column($monthlyExpenses, 'total_expenses')) / max(1, count($monthlyExpenses))
    ];
}

function getBudgetPerformance($conn, $userId) {
    // Get user's actual budget allocation first
    $allocationStmt = $conn->prepare("
        SELECT 
            needs_percentage,
            wants_percentage,
            savings_percentage,
            monthly_salary
        FROM personal_budget_allocation 
        WHERE user_id = ? AND is_active = TRUE
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $allocationStmt->bind_param("i", $userId);
    $allocationStmt->execute();
    $allocation = $allocationStmt->get_result()->fetch_assoc();
    
    // Get budget allocations vs actual expenses - fix table structure
    $stmt = $conn->prepare("
        SELECT 
            bc.name as category_name,
            bc.category_type,
            bc.budget_limit as budgeted_amount,
            COALESCE(SUM(pe.amount), 0) as actual_spent
        FROM budget_categories bc
        LEFT JOIN personal_expenses pe ON bc.id = pe.category_id 
            AND pe.user_id = ? 
            AND DATE_FORMAT(pe.expense_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        WHERE bc.user_id = ? AND bc.is_active = 1
        GROUP BY bc.id, bc.name, bc.category_type, bc.budget_limit
        ORDER BY bc.category_type, bc.name
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $budgetPerformance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate performance metrics
    $totalBudgeted = array_sum(array_column($budgetPerformance, 'budgeted_amount'));
    $totalSpent = array_sum(array_column($budgetPerformance, 'actual_spent'));
    $adherenceRate = $totalBudgeted > 0 ? (($totalBudgeted - abs($totalSpent - $totalBudgeted)) / $totalBudgeted) * 100 : 0;
    
    return [
        'user_allocation' => $allocation, // Add user's actual allocation
        'category_performance' => $budgetPerformance,
        'total_budgeted' => $totalBudgeted,
        'total_spent' => $totalSpent,
        'adherence_rate' => round($adherenceRate, 1),
        'over_budget_categories' => array_filter($budgetPerformance, function($cat) {
            return $cat['actual_spent'] > $cat['budgeted_amount'];
        }),
        'under_budget_categories' => array_filter($budgetPerformance, function($cat) {
            return $cat['actual_spent'] < $cat['budgeted_amount'];
        })
    ];
}

function getSpendingAnalytics($conn, $userId) {
    // Weekly spending patterns
    $weeklyStmt = $conn->prepare("
        SELECT 
            DAYNAME(expense_date) as day_name,
            DAYOFWEEK(expense_date) as day_number,
            AVG(amount) as avg_amount,
            COUNT(*) as transaction_count
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
        GROUP BY DAYOFWEEK(expense_date), DAYNAME(expense_date)
        ORDER BY day_number
    ");
    $weeklyStmt->bind_param("i", $userId);
    $weeklyStmt->execute();
    $weeklyPatterns = $weeklyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Top merchants/descriptions
    $merchantStmt = $conn->prepare("
        SELECT 
            description,
            COUNT(*) as frequency,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY description
        HAVING COUNT(*) > 1
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $merchantStmt->bind_param("i", $userId);
    $merchantStmt->execute();
    $topMerchants = $merchantStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'weekly_patterns' => $weeklyPatterns,
        'top_merchants' => $topMerchants,
        'peak_spending_day' => getPeakSpendingDay($weeklyPatterns),
        'spending_velocity' => calculateSpendingVelocity($conn, $userId),
        'category_trends' => getCategoryTrends($conn, $userId)
    ];
}

function getPeakSpendingDay($weeklyPatterns) {
    if (empty($weeklyPatterns)) return null;
    
    $maxSpending = max(array_column($weeklyPatterns, 'avg_amount'));
    foreach ($weeklyPatterns as $day) {
        if ($day['avg_amount'] == $maxSpending) {
            return $day['day_name'];
        }
    }
    return null;
}

function calculateSpendingVelocity($conn, $userId) {
    // Calculate average time between transactions using a simpler approach
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as transaction_count,
            DATEDIFF(MAX(expense_date), MIN(expense_date)) as date_range
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $transactionCount = $result['transaction_count'] ?? 0;
    $dateRange = $result['date_range'] ?? 0;
    
    if ($transactionCount > 1 && $dateRange > 0) {
        return round($dateRange / ($transactionCount - 1), 1);
    }
    
    return 0;
}

function getCategoryTrends($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(bc.name, 'Uncategorized') as category,
            DATE_FORMAT(pe.expense_date, '%Y-%m') as month,
            SUM(pe.amount) as monthly_total
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ? AND pe.expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY COALESCE(bc.name, 'Uncategorized'), DATE_FORMAT(pe.expense_date, '%Y-%m')
        ORDER BY category, month
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $trends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Group by category
    $categoryTrends = [];
    foreach ($trends as $trend) {
        $categoryTrends[$trend['category']][] = [
            'month' => $trend['month'],
            'amount' => $trend['monthly_total']
        ];
    }
    
    return $categoryTrends;
}

function getGoalsProgress($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            goal_name,
            target_amount,
            current_amount,
            target_date,
            goal_type,
            created_at,
            (current_amount / target_amount * 100) as progress_percentage,
            DATEDIFF(target_date, NOW()) as days_remaining
        FROM personal_goals 
        WHERE user_id = ?
        ORDER BY progress_percentage DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'goals' => $goals,
        'total_goals' => count($goals),
        'completed_goals' => count(array_filter($goals, function($g) { return $g['progress_percentage'] >= 100; })),
        'average_progress' => count($goals) > 0 ? array_sum(array_column($goals, 'progress_percentage')) / count($goals) : 0,
        'upcoming_deadlines' => array_filter($goals, function($g) { 
            return $g['days_remaining'] > 0 && $g['days_remaining'] <= 30; 
        })
    ];
}

function getSavingsAnalysis($conn, $userId) {
    // Calculate monthly savings from income - expenses
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(COALESCE(pe.expense_date, pba.created_at), '%Y-%m') as month,
            COALESCE(pba.monthly_salary, 0) as monthly_income,
            COALESCE(SUM(pe.amount), 0) as monthly_expenses,
            (COALESCE(pba.monthly_salary, 0) - COALESCE(SUM(pe.amount), 0)) as net_savings
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON DATE_FORMAT(pe.expense_date, '%Y-%m') = DATE_FORMAT(pba.created_at, '%Y-%m') 
            AND pe.user_id = pba.user_id
        WHERE pba.user_id = ? AND pba.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(COALESCE(pe.expense_date, pba.created_at), '%Y-%m'), pba.monthly_salary
        ORDER BY month DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $savingsHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate cumulative savings
    $cumulativeSavings = 0;
    foreach ($savingsHistory as &$month) {
        $cumulativeSavings += $month['net_savings'];
        $month['cumulative_savings'] = $cumulativeSavings;
    }
    
    return [
        'monthly_savings' => $savingsHistory,
        'current_savings_rate' => calculateCurrentSavingsRate($savingsHistory),
        'savings_trend' => calculateTrend($savingsHistory, 'net_savings'),
        'projected_annual_savings' => ($savingsHistory[0]['net_savings'] ?? 0) * 12,
        'best_savings_month' => getBestSavingsMonth($savingsHistory)
    ];
}

function calculateCurrentSavingsRate($savingsHistory) {
    if (empty($savingsHistory)) return 0;
    
    $current = $savingsHistory[0];
    if ($current['monthly_income'] > 0) {
        return ($current['net_savings'] / $current['monthly_income']) * 100;
    }
    return 0;
}

function getBestSavingsMonth($savingsHistory) {
    if (empty($savingsHistory)) return null;
    
    $maxSavings = max(array_column($savingsHistory, 'net_savings'));
    foreach ($savingsHistory as $month) {
        if ($month['net_savings'] == $maxSavings) {
            return $month;
        }
    }
    return null;
}

function getTrendsAndForecasts($conn, $userId) {
    // This would implement more sophisticated forecasting
    // For now, return basic trend analysis
    
    $healthData = getFinancialHealth($conn, $userId);
    $incomeData = getIncomeAnalysis($conn, $userId);
    $expenseData = getExpenseAnalysis($conn, $userId);
    
    return [
        'income_forecast' => [
            'next_month' => $incomeData['current_month'] * (1 + $incomeData['trend_percentage'] / 100),
            'confidence' => 'Medium',
            'trend' => $incomeData['trend_percentage'] > 0 ? 'increasing' : 'decreasing'
        ],
        'expense_forecast' => [
            'next_month' => $expenseData['current_month'] * (1 + $expenseData['trend_percentage'] / 100),
            'confidence' => 'High',
            'trend' => $expenseData['trend_percentage'] > 0 ? 'increasing' : 'decreasing'
        ],
        'savings_forecast' => [
            'next_month' => $healthData['net_savings'],
            'six_month_projection' => $healthData['net_savings'] * 6,
            'goal_achievement_timeline' => calculateGoalTimeline($conn, $userId, $healthData['net_savings'])
        ]
    ];
}

function calculateGoalTimeline($conn, $userId, $monthlySavings) {
    if ($monthlySavings <= 0) return [];
    
    $stmt = $conn->prepare("
        SELECT goal_name, target_amount, current_amount 
        FROM personal_goals 
        WHERE user_id = ? AND current_amount < target_amount
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $timeline = [];
    foreach ($goals as $goal) {
        $remaining = $goal['target_amount'] - $goal['current_amount'];
        $monthsToComplete = ceil($remaining / $monthlySavings);
        
        $timeline[] = [
            'goal_name' => $goal['goal_name'],
            'months_to_complete' => $monthsToComplete,
            'completion_date' => date('Y-m-d', strtotime("+{$monthsToComplete} months"))
        ];
    }
    
    return $timeline;
}

function getBenchmarkData($conn, $userId) {
    // Industry benchmarks and comparisons
    $healthData = getFinancialHealth($conn, $userId);
    
    return [
        'savings_rate' => [
            'your_rate' => $healthData['savings_rate'],
            'recommended' => 20,
            'average' => 15,
            'status' => $healthData['savings_rate'] >= 20 ? 'excellent' : 
                       ($healthData['savings_rate'] >= 15 ? 'good' : 'needs_improvement')
        ],
        'emergency_fund' => [
            'your_months' => $healthData['emergency_fund']['months_covered'],
            'recommended' => 6,
            'minimum' => 3,
            'status' => $healthData['emergency_fund']['months_covered'] >= 6 ? 'excellent' :
                       ($healthData['emergency_fund']['months_covered'] >= 3 ? 'good' : 'needs_improvement')
        ],
        'expense_ratio' => [
            'your_ratio' => $healthData['expense_ratio'],
            'recommended_max' => 70,
            'average' => 75,
            'status' => $healthData['expense_ratio'] <= 70 ? 'excellent' :
                       ($healthData['expense_ratio'] <= 80 ? 'good' : 'needs_improvement')
        ]
    ];
}

function getActionableRecommendations($conn, $userId) {
    $recommendations = [];
    
    // Get user data for analysis
    $healthData = getFinancialHealth($conn, $userId);
    $expenseData = getExpenseAnalysis($conn, $userId);
    $budgetData = getBudgetPerformance($conn, $userId);
    
    // Emergency fund recommendations
    if ($healthData['emergency_fund']['months_covered'] < 3) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'Emergency Fund',
            'title' => 'Build Emergency Fund',
            'description' => 'Your emergency fund covers only ' . $healthData['emergency_fund']['months_covered'] . ' months of expenses. Aim for at least 3-6 months.',
            'action' => 'Set up automatic transfers of ₵' . round($healthData['total_expenses'] * 0.1) . '/month to emergency savings',
            'impact' => 'Financial security'
        ];
    }
    
    // Savings rate recommendations
    if ($healthData['savings_rate'] < 15) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'Savings',
            'title' => 'Increase Savings Rate',
            'description' => 'Your current savings rate is ' . $healthData['savings_rate'] . '%. Aim for at least 15-20%.',
            'action' => 'Review expenses and identify areas to cut back',
            'impact' => 'Save ₵' . round($healthData['monthly_income'] * 0.05) . '+/month'
        ];
    }
    
    // High expense categories
    if (!empty($expenseData['category_breakdown'])) {
        $highestCategory = $expenseData['category_breakdown'][0];
        if ($highestCategory['total_amount'] > $healthData['monthly_income'] * 0.3) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'Expense Management',
                'title' => 'Optimize ' . $highestCategory['category'] . ' Spending',
                'description' => $highestCategory['category'] . ' represents a large portion of your expenses (₵' . $highestCategory['total_amount'] . ').',
                'action' => 'Review and reduce ' . $highestCategory['category'] . ' expenses by 10-15%',
                'impact' => 'Save ₵' . round($highestCategory['total_amount'] * 0.1) . '+/month'
            ];
        }
    }
    
    // Budget adherence
    if ($budgetData['adherence_rate'] < 80) {
        $recommendations[] = [
            'priority' => 'medium',
            'category' => 'Budget Management',
            'title' => 'Improve Budget Adherence',
            'description' => 'Your budget adherence rate is ' . $budgetData['adherence_rate'] . '%. Focus on staying within budget limits.',
            'action' => 'Set up spending alerts and track expenses weekly',
            'impact' => 'Better financial control'
        ];
    }
    
    // Optimization recommendations
    $recommendations[] = [
        'priority' => 'low',
        'category' => 'Optimization',
        'title' => 'Automate Savings',
        'description' => 'Set up automatic transfers to savings accounts',
        'action' => 'Schedule automatic transfers right after payday',
        'impact' => 'Consistent savings growth'
    ];
    
    $recommendations[] = [
        'priority' => 'low',
        'category' => 'Optimization',
        'title' => 'Review Subscriptions',
        'description' => 'Cancel unused subscriptions and services',
        'action' => 'Audit all recurring payments monthly',
        'impact' => 'Reduce unnecessary expenses'
    ];
    
    return $recommendations;
}

function getReportMetadata() {
    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'report_period' => date('F Y'),
        'version' => '1.0',
        'data_sources' => [
            'personal_budget_allocation',
            'personal_expenses', 
            'personal_goals',
            'budget_categories'
        ]
    ];
}

function calculateTrend($data, $field) {
    if (count($data) < 2) return 0;
    
    $current = $data[0][$field] ?? 0;
    $previous = $data[1][$field] ?? 0;
    
    if ($previous == 0) return 0;
    
    return round((($current - $previous) / $previous) * 100, 1);
}

function calculateStabilityScore($data, $field) {
    if (count($data) < 3) return 50;
    
    $values = array_column($data, $field);
    $mean = array_sum($values) / count($values);
    $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $values)) / count($values);
    $stdDev = sqrt($variance);
    
    $coefficientOfVariation = $mean > 0 ? ($stdDev / $mean) * 100 : 100;
    
    // Lower CV = higher stability (inverse relationship)
    return max(0, min(100, 100 - $coefficientOfVariation));
}
?>
