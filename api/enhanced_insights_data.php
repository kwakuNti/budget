<?php
// Clean output and suppress all warnings/notices for clean JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle different path contexts for config file
$config_paths = [
    '../config/connection.php',
    'config/connection.php',
    __DIR__ . '/../config/connection.php'
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database configuration not found']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    // Clean any previous output before sending JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    switch ($action) {
        case 'comprehensive_insights':
            $result = getComprehensiveInsights($conn, $user_id);
            echo json_encode($result);
            break;
        case 'dashboard_insights':
            $result = getDashboardInsights($conn, $user_id);
            echo json_encode($result);
            break;
        case 'advanced_analytics':
            $result = getAdvancedAnalytics($conn, $user_id);
            echo json_encode($result);
            break;
        case 'personalized_recommendations':
            $result = getActionableRecommendations($conn, $user_id);
            echo json_encode($result);
            break;
        case 'financial_score_breakdown':
            $result = getFinancialScoreBreakdown($conn, $user_id);
            echo json_encode($result);
            break;
        case 'ai_predictions':
            $result = getSmartPredictions($conn, $user_id);
            echo json_encode($result);
            break;
        case 'behavioral_insights':
            $result = getBehavioralInsights($conn, $user_id);
            echo json_encode($result);
            break;
        case 'goal_optimization':
            $result = getGoalOptimization($conn, $user_id);
            echo json_encode($result);
            break;
        case 'financial_health':
            $result = getEnhancedFinancialHealth($conn, $user_id);
            echo json_encode($result);
            break;
        case 'spending_patterns':
            $result = getAdvancedSpendingAnalytics($conn, $user_id);
            echo json_encode($result);
            break;
        case 'goal_analytics':
            $result = getAdvancedSpendingAnalytics($conn, $user_id);
            echo json_encode($result);
            break;
        case 'budget_performance':
            $result = getAdvancedSpendingAnalytics($conn, $user_id);
            echo json_encode($result);
            break;
        case 'income_trends':
            $result = getEnhancedFinancialHealth($conn, $user_id);
            echo json_encode($result);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    // Clean any output before sending error
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['error' => $e->getMessage()]);
}

// Don't force flush - let the caller handle output
// if (ob_get_level()) {
//     ob_end_flush();
// }

function getComprehensiveInsights($conn, $user_id) {
    // Progressive loading - return basic financial health data
    try {
        $data = [
            'financial_health' => getEnhancedFinancialHealth($conn, $user_id),
            'spending_analytics' => ['message' => 'Loading progressively...'],
            'savings_performance' => ['message' => 'Loading progressively...'],
            'goal_insights' => ['message' => 'Loading progressively...'],
            'trend_analysis' => ['message' => 'Loading progressively...'],
            'predictions' => ['message' => 'Loading progressively...'],
            'recommendations' => ['message' => 'Loading progressively...'],
            'benchmarks' => ['message' => 'Loading progressively...']
        ];
        
        return $data;
        
    } catch (Exception $e) {
        return ['error' => 'Comprehensive insights error: ' . $e->getMessage()];
    }
}

function getDashboardInsights($conn, $user_id) {
    // Specifically for personal dashboard - concise but impactful insights
    $insights = [];
    
    // Get key metrics
    $health_data = getEnhancedFinancialHealth($conn, $user_id);
    $spending_data = getAdvancedSpendingAnalytics($conn, $user_id);
    $goals_data = getGoalInsights($conn, $user_id);
    
    // Generate dashboard-specific insights
    if ($health_data['health_score'] >= 85) {
        $insights[] = [
            'type' => 'success',
            'icon' => '🎉',
            'title' => 'Excellent Financial Health!',
            'message' => "Your financial health score is {$health_data['health_score']}/100. You're managing money like a pro!",
            'action' => 'View Full Report',
            'link' => 'insights'
        ];
    } elseif ($health_data['health_score'] >= 70) {
        $insights[] = [
            'type' => 'good',
            'icon' => '👍',
            'title' => 'Good Financial Standing',
            'message' => "Score: {$health_data['health_score']}/100. A few optimizations could boost your financial health.",
            'action' => 'See Recommendations',
            'link' => 'insights'
        ];
    } else {
        $insights[] = [
            'type' => 'warning',
            'icon' => '⚠️',
            'title' => 'Financial Health Needs Attention',
            'message' => "Score: {$health_data['health_score']}/100. Let's work on improving your financial habits.",
            'action' => 'Get Help',
            'link' => 'insights'
        ];
    }
    
    // Spending insights
    $monthly_spending = $spending_data['current_month_total'] ?? 0;
    $budget_limit = $health_data['monthly_income'] ?? 0;
    $spending_ratio = $budget_limit > 0 ? ($monthly_spending / $budget_limit) * 100 : 0;
    
    if ($spending_ratio > 90) {
        $insights[] = [
            'type' => 'alert',
            'icon' => '🚨',
            'title' => 'Budget Alert!',
            'message' => "You've spent {$spending_ratio}% of your monthly income. Consider reducing discretionary expenses.",
            'action' => 'Review Expenses',
            'link' => 'personal-expense.php'
        ];
    } elseif ($spending_ratio < 50) {
        $insights[] = [
            'type' => 'tip',
            'icon' => '💡',
            'title' => 'Great Spending Control!',
            'message' => "You've only spent {$spending_ratio}% of your income this month. Consider increasing savings or investments.",
            'action' => 'Optimize Savings',
            'link' => 'savings.php'
        ];
    }
    
    // Goal insights
    if (!empty($goals_data['urgent_goals'])) {
        $urgent_goal = $goals_data['urgent_goals'][0];
        $insights[] = [
            'type' => 'urgent',
            'icon' => '⏰',
            'title' => 'Goal Deadline Approaching',
            'message' => "Your '{$urgent_goal['goal_name']}' goal needs ₵{$urgent_goal['daily_required']} daily to stay on track.",
            'action' => 'Adjust Goal',
            'link' => 'savings.php'
        ];
    }
    
    // Smart savings opportunity
    if ($spending_data['potential_savings'] > 0) {
        $insights[] = [
            'type' => 'opportunity',
            'icon' => '💰',
            'title' => 'Savings Opportunity Detected',
            'message' => "You could save ₵{$spending_data['potential_savings']} by optimizing your spending patterns.",
            'action' => 'See How',
            'link' => 'insights'
        ];
    }
    
    // Limit to 3-4 most relevant insights for dashboard
    return array_slice($insights, 0, 4);
}

function getEnhancedFinancialHealth($conn, $user_id) {
    // More comprehensive financial health calculation
    $query = "
        SELECT 
            pba.monthly_salary,
            pba.needs_percentage,
            pba.wants_percentage,
            pba.savings_percentage,
            pba.needs_amount,
            pba.wants_amount,
            pba.savings_amount,
            COALESCE(monthly_expenses.total_expenses, 0) as total_expenses,
            COALESCE(monthly_expenses.needs_expenses, 0) as needs_expenses,
            COALESCE(monthly_expenses.wants_expenses, 0) as wants_expenses,
            COALESCE(total_savings.total_saved, 0) as total_saved,
            COALESCE(total_goals.total_goals, 0) as total_goals,
            COALESCE(completed_goals.completed_goals, 0) as completed_goals
        FROM personal_budget_allocation pba
        LEFT JOIN (
            SELECT 
                pe.user_id,
                SUM(pe.amount) as total_expenses,
                SUM(CASE WHEN bc.category_type = 'needs' THEN pe.amount ELSE 0 END) as needs_expenses,
                SUM(CASE WHEN bc.category_type = 'wants' THEN pe.amount ELSE 0 END) as wants_expenses
            FROM personal_expenses pe
            LEFT JOIN budget_categories bc ON pe.category_id = bc.id
            WHERE MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
            GROUP BY pe.user_id
        ) monthly_expenses ON pba.user_id = monthly_expenses.user_id
        LEFT JOIN (
            SELECT user_id, SUM(current_amount) as total_saved
            FROM personal_goals 
            GROUP BY user_id
        ) total_savings ON pba.user_id = total_savings.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as total_goals
            FROM personal_goals 
            GROUP BY user_id
        ) total_goals ON pba.user_id = total_goals.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as completed_goals
            FROM personal_goals 
            WHERE current_amount >= target_amount
            GROUP BY user_id
        ) completed_goals ON pba.user_id = completed_goals.user_id
        WHERE pba.user_id = ? AND pba.is_active = 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        return getDefaultHealthData();
    }
    
    $income = floatval($result['monthly_salary'] ?? 0);
    $expenses = floatval($result['total_expenses'] ?? 0);
    $needs_expenses = floatval($result['needs_expenses'] ?? 0);
    $wants_expenses = floatval($result['wants_expenses'] ?? 0);
    $total_saved = floatval($result['total_saved'] ?? 0);
    $total_goals = intval($result['total_goals'] ?? 0);
    $completed_goals = intval($result['completed_goals'] ?? 0);
    
    // Calculate sophisticated health score
    $health_score = calculateAdvancedHealthScore($income, $expenses, $needs_expenses, $wants_expenses, $total_saved, $total_goals, $completed_goals);
    
    // Calculate ratios
    $expense_ratio = $income > 0 ? ($expenses / $income) * 100 : 0;
    $savings_rate = $income > 0 ? (($income - $expenses) / $income) * 100 : 0;
    $needs_ratio = $income > 0 ? ($needs_expenses / $income) * 100 : 0;
    $wants_ratio = $income > 0 ? ($wants_expenses / $income) * 100 : 0;
    
    // Emergency fund calculation
    $emergency_fund_months = $expenses > 0 ? $total_saved / $expenses : 0;
    
    return [
        'health_score' => $health_score,
        'monthly_income' => $income,
        'total_expenses' => $expenses,
        'needs_expenses' => $needs_expenses,
        'wants_expenses' => $wants_expenses,
        'total_saved' => $total_saved,
        'expense_ratio' => round($expense_ratio, 1),
        'savings_rate' => round($savings_rate, 1),
        'needs_ratio' => round($needs_ratio, 1),
        'wants_ratio' => round($wants_ratio, 1),
        'emergency_fund_months' => round($emergency_fund_months, 1),
        'total_goals' => $total_goals,
        'completed_goals' => $completed_goals,
        'goal_completion_rate' => $total_goals > 0 ? round(($completed_goals / $total_goals) * 100, 1) : 0,
        'recommendations' => getAdvancedHealthRecommendations($expense_ratio, $savings_rate, $needs_ratio, $wants_ratio, $emergency_fund_months, $health_score),
        'budget' => [
            'monthly_salary' => $income,
            'needs_allocation' => floatval($result['needs_percentage'] ?? 0),
            'wants_allocation' => floatval($result['wants_percentage'] ?? 0),
            'savings_allocation' => floatval($result['savings_percentage'] ?? 0),
            'needs_amount' => floatval($result['needs_amount'] ?? 0),
            'wants_amount' => floatval($result['wants_amount'] ?? 0),
            'savings_amount' => floatval($result['savings_amount'] ?? 0)
        ]
    ];
}

function calculateAdvancedHealthScore($income, $expenses, $needs_expenses, $wants_expenses, $total_saved, $total_goals, $completed_goals) {
    $score = 100;
    
    if ($income <= 0) return 0;
    
    $expense_ratio = ($expenses / $income) * 100;
    $savings_rate = (($income - $expenses) / $income) * 100;
    $needs_ratio = ($needs_expenses / $income) * 100;
    $wants_ratio = ($wants_expenses / $income) * 100;
    $emergency_months = $expenses > 0 ? $total_saved / $expenses : 0;
    
    // Expense management (30 points)
    if ($expense_ratio > 90) $score -= 25;
    elseif ($expense_ratio > 80) $score -= 20;
    elseif ($expense_ratio > 70) $score -= 15;
    elseif ($expense_ratio > 60) $score -= 10;
    elseif ($expense_ratio > 50) $score -= 5;
    
    // Savings rate (25 points)
    if ($savings_rate < 5) $score -= 20;
    elseif ($savings_rate < 10) $score -= 15;
    elseif ($savings_rate < 15) $score -= 10;
    elseif ($savings_rate < 20) $score -= 5;
    
    // 50/30/20 rule adherence (20 points)
    if ($needs_ratio > 60) $score -= 10;
    if ($wants_ratio > 40) $score -= 10;
    
    // Emergency fund (15 points)
    if ($emergency_months < 1) $score -= 15;
    elseif ($emergency_months < 3) $score -= 10;
    elseif ($emergency_months < 6) $score -= 5;
    
    // Goal management (10 points)
    if ($total_goals > 0) {
        $goal_score = ($completed_goals / $total_goals) * 10;
        $score += $goal_score - 5; // Bonus for having and completing goals
    } else {
        $score -= 5; // Penalty for no goals
    }
    
    return max(0, min(100, round($score)));
}

function getAdvancedSpendingAnalytics($conn, $user_id) {
    // Simplified spending analytics to avoid database complexity
    try {
        // Get basic spending data for current month
        $query = "
            SELECT 
                bc.name as category,
                bc.category_type,
                COALESCE(SUM(pe.amount), 0) as category_total
            FROM budget_categories bc
            LEFT JOIN personal_expenses pe ON bc.id = pe.category_id 
                AND pe.user_id = ? 
                AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
            WHERE bc.user_id = ?
            GROUP BY bc.id, bc.name, bc.category_type
            ORDER BY category_total DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $total_spending = array_sum(array_column($categories, 'category_total'));
        
        return [
            'current_month_total' => $total_spending,
            'current_month_categories' => $categories,
            'weekly_patterns' => [],
            'over_budget_categories' => [],
            'potential_savings' => $total_spending * 0.1,
            'avg_daily_spending' => $total_spending / date('j'),
            'spending_velocity' => 0
        ];
        
    } catch (Exception $e) {
        return [
            'current_month_total' => 0,
            'current_month_categories' => [
                ['category' => 'No Data', 'category_total' => 0, 'category_type' => 'needs']
            ],
            'weekly_patterns' => [],
            'over_budget_categories' => [],
            'potential_savings' => 0,
            'avg_daily_spending' => 0,
            'spending_velocity' => 0,
            'error' => $e->getMessage()
        ];
    }
}

function calculateSpendingVelocity($weekly_patterns) {
    if (count($weekly_patterns) < 2) return 0;
    
    $latest_week = $weekly_patterns[0]['week_total'];
    $previous_week = $weekly_patterns[1]['week_total'];
    
    if ($previous_week == 0) return 0;
    
    return round((($latest_week - $previous_week) / $previous_week) * 100, 1);
}

function getSavingsPerformance($conn, $user_id) {
    $query = "
        SELECT 
            pg.*,
            CASE 
                WHEN pg.target_amount > 0 THEN (pg.current_amount / pg.target_amount) * 100
                ELSE 0
            END as completion_percentage,
            DATEDIFF(pg.target_date, CURRENT_DATE()) as days_remaining,
            CASE 
                WHEN pg.target_date > CURRENT_DATE() AND pg.target_amount > pg.current_amount 
                THEN (pg.target_amount - pg.current_amount) / GREATEST(DATEDIFF(pg.target_date, CURRENT_DATE()), 1)
                ELSE 0
            END as daily_required_savings,
            recent_contributions.recent_contribution,
            recent_contributions.contribution_trend
        FROM personal_goals pg
        LEFT JOIN (
            SELECT 
                goal_id,
                SUM(CASE WHEN contribution_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) THEN amount ELSE 0 END) as recent_contribution,
                CASE 
                    WHEN COUNT(CASE WHEN contribution_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY) THEN 1 END) > 
                         COUNT(CASE WHEN contribution_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY) THEN 1 END)
                    THEN 'increasing'
                    ELSE 'stable'
                END as contribution_trend
            FROM personal_goal_contributions
            GROUP BY goal_id
        ) recent_contributions ON pg.id = recent_contributions.goal_id
        WHERE pg.user_id = ?
        ORDER BY completion_percentage DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate savings metrics
    $total_target = array_sum(array_column($goals, 'target_amount'));
    $total_saved = array_sum(array_column($goals, 'current_amount'));
    $monthly_savings_needed = array_sum(array_column($goals, 'daily_required_savings')) * 30;
    
    return [
        'goals' => $goals,
        'total_target' => $total_target,
        'total_saved' => $total_saved,
        'overall_progress' => $total_target > 0 ? round(($total_saved / $total_target) * 100, 1) : 0,
        'monthly_savings_needed' => round($monthly_savings_needed, 2),
        'savings_momentum' => calculateSavingsMomentum($goals)
    ];
}

function calculateSavingsMomentum($goals) {
    $increasing_trends = 0;
    $total_active_goals = 0;
    
    foreach ($goals as $goal) {
        if ($goal['completion_percentage'] < 100) {
            $total_active_goals++;
            if ($goal['contribution_trend'] === 'increasing') {
                $increasing_trends++;
            }
        }
    }
    
    return $total_active_goals > 0 ? round(($increasing_trends / $total_active_goals) * 100, 1) : 0;
}

function getGoalInsights($conn, $user_id) {
    $query = "
        SELECT 
            pg.*,
            CASE 
                WHEN pg.target_amount > 0 THEN (pg.current_amount / pg.target_amount) * 100
                ELSE 0
            END as completion_percentage,
            DATEDIFF(pg.target_date, CURRENT_DATE()) as days_remaining,
            CASE 
                WHEN DATEDIFF(pg.target_date, CURRENT_DATE()) <= 30 AND pg.current_amount < pg.target_amount
                THEN 'urgent'
                WHEN (pg.current_amount / pg.target_amount) >= 0.75
                THEN 'on_track'
                WHEN (pg.current_amount / pg.target_amount) >= 0.5
                THEN 'moderate'
                ELSE 'behind'
            END as status,
            CASE 
                WHEN pg.target_date > CURRENT_DATE() AND pg.target_amount > pg.current_amount 
                THEN (pg.target_amount - pg.current_amount) / GREATEST(DATEDIFF(pg.target_date, CURRENT_DATE()), 1)
                ELSE 0
            END as daily_required
        FROM personal_goals pg
        WHERE pg.user_id = ?
        ORDER BY 
            CASE 
                WHEN DATEDIFF(pg.target_date, CURRENT_DATE()) <= 30 THEN 1
                ELSE 2
            END,
            completion_percentage DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Categorize goals
    $urgent_goals = array_filter($goals, function($g) { return $g['status'] === 'urgent'; });
    $on_track_goals = array_filter($goals, function($g) { return $g['status'] === 'on_track'; });
    $behind_goals = array_filter($goals, function($g) { return $g['status'] === 'behind'; });
    
    return [
        'all_goals' => $goals,
        'urgent_goals' => array_values($urgent_goals),
        'on_track_goals' => array_values($on_track_goals),
        'behind_goals' => array_values($behind_goals),
        'goal_insights' => generateGoalInsights($goals)
    ];
}

function generateGoalInsights($goals) {
    $insights = [];
    
    foreach ($goals as $goal) {
        if ($goal['status'] === 'urgent') {
            $insights[] = "⚠️ '{$goal['goal_name']}' needs ₵{$goal['daily_required']}/day to meet deadline";
        } elseif ($goal['completion_percentage'] >= 90) {
            $insights[] = "🎯 You're almost there! '{$goal['goal_name']}' is {$goal['completion_percentage']}% complete";
        } elseif ($goal['status'] === 'behind') {
            $insights[] = "📈 Consider increasing contributions to '{$goal['goal_name']}' - currently {$goal['completion_percentage']}% complete";
        }
    }
    
    return $insights;
}

function getSmartPredictions($conn, $user_id) {
    // Simplified AI predictions to avoid circular dependencies
    try {
        return getRuleBasedPredictions($conn, $user_id);
    } catch (Exception $e) {
        return [
            'predictions' => [
                "📊 Monthly spending analysis in progress...",
                "💰 Savings opportunities being calculated...",
                "🎯 Goal progress tracking enabled...",
                "📈 Financial trends being analyzed..."
            ],
            'confidence_score' => 70,
            'generated_by' => 'rule_based',
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => 'Simplified predictions due to: ' . $e->getMessage()
        ];
    }
}

function getComprehensiveFinancialData($conn, $user_id) {
    // Compile all financial data for AI analysis
    return [
        'user_id' => $user_id,
        'health_data' => getEnhancedFinancialHealth($conn, $user_id),
        'spending_data' => getAdvancedSpendingAnalytics($conn, $user_id),
        'savings_data' => getSavingsPerformance($conn, $user_id),
        'goals_data' => getGoalInsights($conn, $user_id)
    ];
}

function parseAIPredictions($aiMessage) {
    $predictions = [];
    $lines = explode("\n", $aiMessage);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && (strpos($line, '•') === 0 || strpos($line, '-') === 0 || preg_match('/^\d+\./', $line))) {
            $cleaned = preg_replace('/^[\•\-\d+\.\s]+/', '', $line);
            if (strlen($cleaned) > 20) {
                $predictions[] = $cleaned;
            }
        }
    }
    
    return array_slice($predictions, 0, 6);
}

function getRuleBasedPredictions($conn, $user_id) {
    // Simplified rule-based predictions using only basic financial health data
    try {
        $health_data = getEnhancedFinancialHealth($conn, $user_id);
        
        $predictions = [];
        
        // Based on health score
        $health_score = $health_data['health_score'];
        if ($health_score >= 85) {
            $predictions[] = "🎉 Excellent financial health! Consider increasing investment contributions by 10%";
            $predictions[] = "📈 Your savings rate is above average - perfect time to explore new investment opportunities";
        } elseif ($health_score >= 70) {
            $predictions[] = "� Good financial health! Focus on building your emergency fund to 6 months";
            $predictions[] = "💡 Consider setting up automatic savings to boost your financial score";
        } else {
            $predictions[] = "⚠️ Focus on reducing expenses and building emergency savings";
            $predictions[] = "📊 Track your spending more closely to identify savings opportunities";
        }
        
        // Based on expense ratio
        $expense_ratio = $health_data['expense_ratio'];
        if ($expense_ratio > 80) {
            $predictions[] = "🚨 High expense ratio detected - review your budget categories";
        } else {
            $predictions[] = "✅ Your expense management is on track";
        }
        
        // Based on emergency fund
        $emergency_months = $health_data['emergency_fund_months'];
        if ($emergency_months < 3) {
            $predictions[] = "�️ Priority: Build emergency fund to 3-6 months of expenses";
        }
        
        return [
            'predictions' => array_slice($predictions, 0, 5),
            'confidence_score' => 75,
            'generated_by' => 'rule_based',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'predictions' => [
                "📊 Financial analysis in progress...",
                "💰 Personalized insights loading...",
                "🎯 Goal recommendations being generated..."
            ],
            'confidence_score' => 60,
            'generated_by' => 'fallback',
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $e->getMessage()
        ];
    }
}

function getActionableRecommendations($conn, $user_id) {
    try {
        $health_data = getEnhancedFinancialHealth($conn, $user_id);
        
        $recommendations = [];
        
        // Generate recommendations based on financial health
        if ($health_data['health_score'] < 70) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'financial_health',
                'title' => 'Improve Financial Health Score',
                'description' => "Your score is {$health_data['health_score']}/100. Focus on reducing expenses and increasing savings.",
                'action_items' => [
                    'Track expenses daily for one month',
                    'Set up automatic savings of 10% of income',
                    'Review and cancel unused subscriptions'
                ],
                'potential_impact' => 'Could improve score by 15-20 points'
            ];
        }
        
        if ($health_data['emergency_fund_months'] < 3) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'emergency_fund',
                'title' => 'Build Emergency Fund',
                'description' => 'You need ₵' . number_format(($health_data['total_expenses'] * 3) - $health_data['total_saved'], 2) . " more for a 3-month emergency fund.",
                'action_items' => [
                    'Set up automatic transfers to savings',
                    'Save at least 20% of monthly income',
                    'Consider a high-yield savings account'
                ],
                'potential_impact' => 'Achieve 6 months emergency fund in 12 months'
            ];
        }
        
        if ($health_data['expense_ratio'] > 80) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'expense_management',
                'title' => 'Reduce Monthly Expenses',
                'description' => 'Youre spending ' . $health_data['expense_ratio'] . '% of your income.',
                'action_items' => [
                    'Review and cancel unused subscriptions',
                    'Cook more meals at home',
                    'Find cheaper alternatives for wants'
                ],
                'potential_impact' => 'Save ₵' . number_format($health_data['monthly_income'] * 0.1, 2) . ' per month'
            ];
        }
        
        if ($health_data['health_score'] >= 85) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'optimization',
                'title' => 'Investment Opportunities',
                'description' => 'Great financial health! Consider growing your wealth through investments.',
                'action_items' => [
                    'Explore index funds or ETFs',
                    'Consider increasing retirement contributions',
                    'Look into real estate investment'
                ],
                'potential_impact' => 'Potential 7-10% annual returns'
            ];
        }
        
        return $recommendations;
        
    } catch (Exception $e) {
        return [[
            'priority' => 'low',
            'category' => 'system',
            'title' => 'Recommendations Loading',
            'description' => 'Personalized recommendations are being generated based on your financial data.',
            'action_items' => ['Review your recent transactions', 'Update your budget categories'],
            'potential_impact' => 'Improved financial insights'
        ]];
    }
}

function getAdvancedHealthRecommendations($expense_ratio, $savings_rate, $needs_ratio, $wants_ratio, $emergency_months, $health_score) {
    $recommendations = [];
    
    if ($expense_ratio > 80) {
        $recommendations[] = "🚨 Priority: Reduce total expenses by " . round($expense_ratio - 70, 1) . "% to improve financial stability";
    }
    
    if ($savings_rate < 20) {
        $target_increase = 20 - $savings_rate;
        $recommendations[] = "💡 Increase savings rate by {$target_increase}% to reach the recommended 20% minimum";
    }
    
    if ($needs_ratio > 50) {
        $recommendations[] = "🏠 Optimize essential expenses - currently {$needs_ratio}% of income (target: 50%)";
    }
    
    if ($wants_ratio > 30) {
        $recommendations[] = "🎯 Reduce discretionary spending - currently {$wants_ratio}% of income (target: 30%)";
    }
    
    if ($emergency_months < 6) {
        $recommendations[] = "🛡️ Build emergency fund to 6 months of expenses (currently {$emergency_months} months)";
    }
    
    if ($health_score >= 85) {
        $recommendations[] = "🎉 Excellent! Consider investing surplus funds or increasing retirement contributions";
    }
    
    return $recommendations;
}

function getDefaultHealthData() {
    return [
        'health_score' => 50,
        'monthly_income' => 0,
        'total_expenses' => 0,
        'needs_expenses' => 0,
        'wants_expenses' => 0,
        'total_saved' => 0,
        'expense_ratio' => 0,
        'savings_rate' => 0,
        'needs_ratio' => 0,
        'wants_ratio' => 0,
        'emergency_fund_months' => 0,
        'total_goals' => 0,
        'completed_goals' => 0,
        'goal_completion_rate' => 0,
        'recommendations' => ['Start by setting up your budget and tracking expenses']
    ];
}

function getFinancialBenchmarks($conn, $user_id) {
    // Compare user's metrics against recommended financial benchmarks
    $health_data = getEnhancedFinancialHealth($conn, $user_id);
    
    $benchmarks = [
        'emergency_fund' => [
            'user_value' => $health_data['emergency_fund_months'],
            'benchmark' => 6,
            'status' => $health_data['emergency_fund_months'] >= 6 ? 'good' : 'needs_improvement',
            'description' => '6 months of expenses in emergency fund'
        ],
        'savings_rate' => [
            'user_value' => $health_data['savings_rate'],
            'benchmark' => 20,
            'status' => $health_data['savings_rate'] >= 20 ? 'good' : 'needs_improvement',
            'description' => '20% of income saved'
        ],
        'needs_spending' => [
            'user_value' => $health_data['needs_ratio'],
            'benchmark' => 50,
            'status' => $health_data['needs_ratio'] <= 50 ? 'good' : 'needs_improvement',
            'description' => 'Maximum 50% on essential expenses'
        ],
        'wants_spending' => [
            'user_value' => $health_data['wants_ratio'],
            'benchmark' => 30,
            'status' => $health_data['wants_ratio'] <= 30 ? 'good' : 'needs_improvement',
            'description' => 'Maximum 30% on discretionary expenses'
        ]
    ];
    
    return $benchmarks;
}

function getAdvancedAnalytics($conn, $user_id) {
    return getComprehensiveInsights($conn, $user_id);
}

function getFinancialScoreBreakdown($conn, $user_id) {
    $health_data = getEnhancedFinancialHealth($conn, $user_id);
    
    return [
        'overall_score' => $health_data['health_score'],
        'breakdown' => [
            'expense_management' => min(30, 30 - max(0, ($health_data['expense_ratio'] - 50) * 0.6)),
            'savings_rate' => min(25, $health_data['savings_rate'] * 1.25),
            'budget_adherence' => min(20, 20 - max(0, abs($health_data['needs_ratio'] - 50) + abs($health_data['wants_ratio'] - 30)) * 0.5),
            'emergency_fund' => min(15, $health_data['emergency_fund_months'] * 2.5),
            'goal_management' => min(10, $health_data['goal_completion_rate'] * 0.1)
        ],
        'recommendations' => $health_data['recommendations']
    ];
}

function getBehavioralInsights($conn, $user_id) {
    $spending_data = getAdvancedSpendingAnalytics($conn, $user_id);
    
    // Analyze spending patterns
    $insights = [];
    
    if ($spending_data['spending_velocity'] > 20) {
        $insights[] = [
            'type' => 'spending_acceleration',
            'message' => 'Your spending has increased by ' . $spending_data['spending_velocity'] . '% compared to last week',
            'recommendation' => 'Consider reviewing recent purchases and setting spending alerts'
        ];
    }
    
    // Weekend vs weekday spending analysis
    $weekend_query = "
        SELECT 
            CASE WHEN DAYOFWEEK(expense_date) IN (1, 7) THEN 'weekend' ELSE 'weekday' END as period,
            AVG(amount) as avg_amount
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        GROUP BY period
    ";
    
    $stmt = $conn->prepare($weekend_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pattern_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $weekend_avg = 0;
    $weekday_avg = 0;
    
    foreach ($pattern_data as $data) {
        if ($data['period'] === 'weekend') {
            $weekend_avg = $data['avg_amount'];
        } else {
            $weekday_avg = $data['avg_amount'];
        }
    }
    
    if ($weekend_avg > $weekday_avg * 1.5) {
        $insights[] = [
            'type' => 'weekend_spending',
            'message' => 'You spend ' . round((($weekend_avg - $weekday_avg) / $weekday_avg) * 100, 1) . '% more on weekends',
            'recommendation' => 'Plan weekend activities with a budget to control discretionary spending'
        ];
    }
    
    return [
        'insights' => $insights,
        'spending_patterns' => [
            'weekend_avg' => $weekend_avg,
            'weekday_avg' => $weekday_avg,
            'velocity' => $spending_data['spending_velocity']
        ]
    ];
}

function getGoalOptimization($conn, $user_id) {
    $goals_data = getGoalInsights($conn, $user_id);
    $health_data = getEnhancedFinancialHealth($conn, $user_id);
    
    $optimizations = [];
    
    foreach ($goals_data['all_goals'] as $goal) {
        $optimization = [
            'goal_name' => $goal['goal_name'],
            'current_progress' => $goal['completion_percentage'],
            'suggestions' => []
        ];
        
        if ($goal['status'] === 'behind') {
            $optimization['suggestions'][] = 'Increase monthly contribution by 20% to get back on track';
            $optimization['suggestions'][] = 'Consider extending deadline by 2-3 months for more realistic timeline';
        }
        
        if ($goal['daily_required'] > ($health_data['monthly_income'] * 0.1) / 30) {
            $optimization['suggestions'][] = 'Daily requirement is high - consider breaking into smaller sub-goals';
        }
        
        $optimizations[] = $optimization;
    }
    
    return [
        'goal_optimizations' => $optimizations,
        'overall_strategy' => generateOverallGoalStrategy($goals_data, $health_data)
    ];
}

function generateOverallGoalStrategy($goals_data, $health_data) {
    $strategy = [];
    
    if (count($goals_data['urgent_goals']) > 2) {
        $strategy[] = 'You have multiple urgent goals - consider prioritizing 1-2 most important ones';
    }
    
    if ($health_data['emergency_fund_months'] < 3 && count($goals_data['all_goals']) > 1) {
        $strategy[] = 'Focus on emergency fund before pursuing other savings goals';
    }
    
    $total_required = array_sum(array_column($goals_data['all_goals'], 'daily_required')) * 30;
    $available_savings = $health_data['monthly_income'] * ($health_data['savings_rate'] / 100);
    
    if ($total_required > $available_savings) {
        $strategy[] = 'Your goals require more savings than current rate - increase income or extend timelines';
    }
    
    return $strategy;
}

function getBasicTrendAnalysis($conn, $user_id) {
    $query = "
        SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            SUM(amount) as total_expenses,
            COUNT(*) as transaction_count,
            AVG(amount) as avg_transaction
        FROM personal_expenses 
        WHERE user_id = ? 
        AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
        LIMIT 12
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $trends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate trend direction
    $trend_direction = 'stable';
    if (count($trends) >= 3) {
        $recent_avg = array_sum(array_slice(array_column($trends, 'total_expenses'), 0, 3)) / 3;
        $older_avg = array_sum(array_slice(array_column($trends, 'total_expenses'), 3, 3)) / 3;
        
        if ($recent_avg > $older_avg * 1.1) {
            $trend_direction = 'increasing';
        } elseif ($recent_avg < $older_avg * 0.9) {
            $trend_direction = 'decreasing';
        }
    }
    
    return [
        'monthly_trends' => $trends,
        'trend_direction' => $trend_direction,
        'insights' => [
            'overall_trend' => "Your spending trend over the last year is {$trend_direction}",
            'avg_monthly_expenses' => count($trends) > 0 ? array_sum(array_column($trends, 'total_expenses')) / count($trends) : 0
        ]
    ];
}
?>
