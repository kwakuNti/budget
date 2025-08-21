<?php
// insights_data.php
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
            DAYNAME(expense_date) as day_name,
            DAYOFWEEK(expense_date) as day_num,
            AVG(amount) as avg_amount,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY day_name, day_num
        ORDER BY day_num
    ";
    
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $daily_patterns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Hourly spending patterns (if timestamp data available)
    $hourly_query = "
        SELECT 
            HOUR(created_at) as hour,
            AVG(amount) as avg_amount,
            COUNT(*) as transaction_count
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        GROUP BY hour
        ORDER BY hour
    ";
    
    $stmt = $conn->prepare($hourly_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $hourly_patterns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Category spending trends - get category name from category_id
    $category_trends = "
        SELECT 
            COALESCE(bc.category_name, 'Other') as category,
            MONTH(pe.expense_date) as month,
            YEAR(pe.expense_date) as year,
            SUM(pe.amount) as total,
            AVG(pe.amount) as avg_amount,
            COUNT(*) as frequency
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ? AND pe.expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY bc.category_name, year, month
        ORDER BY year DESC, month DESC, total DESC
    ";
    
    $stmt = $conn->prepare($category_trends);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $category_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'daily_patterns' => $daily_patterns,
        'hourly_patterns' => $hourly_patterns,
        'category_trends' => $category_data
    ];
}

function getFinancialHealth($conn, $user_id) {
    // Get current month income and expenses using personal_income and personal_budget_allocation
    $current_month = "
        SELECT 
            COALESCE((SELECT monthly_salary FROM personal_budget_allocation WHERE user_id = ? AND is_active = 1 LIMIT 1), 0) as monthly_income,
            COALESCE((SELECT SUM(amount) FROM personal_expenses 
                WHERE user_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(expense_date) = YEAR(CURRENT_DATE())), 0) as monthly_expenses,
            COALESCE((SELECT SUM(current_amount) FROM personal_goals WHERE user_id = ?), 0) as total_savings
    ";
    
    $stmt = $conn->prepare($current_month);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $financial_data = $stmt->get_result()->fetch_assoc();
    
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
                THEN (g.target_amount - g.current_amount) / DATEDIFF(g.target_date, CURRENT_DATE())
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
    
    return [
        'income_history' => $income_data
    ];
}

function getBudgetPerformance($conn, $user_id) {
    // Get budget vs actual spending for current month using personal_budget_allocation
    $budget_query = "
        SELECT 
            'Needs' as category_name,
            pba.needs_amount as budgeted,
            COALESCE(SUM(CASE WHEN bc.category_type = 'needs' THEN pe.amount ELSE 0 END), 0) as actual,
            CASE 
                WHEN pba.needs_amount > 0 
                THEN ((COALESCE(SUM(CASE WHEN bc.category_type = 'needs' THEN pe.amount ELSE 0 END), 0) / pba.needs_amount) * 100)
                ELSE 0
            END as usage_percentage
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON pe.user_id = pba.user_id 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pba.user_id = ? AND pba.is_active = 1
        GROUP BY pba.needs_amount
        
        UNION ALL
        
        SELECT 
            'Wants' as category_name,
            pba.wants_amount as budgeted,
            COALESCE(SUM(CASE WHEN bc.category_type = 'wants' THEN pe.amount ELSE 0 END), 0) as actual,
            CASE 
                WHEN pba.wants_amount > 0 
                THEN ((COALESCE(SUM(CASE WHEN bc.category_type = 'wants' THEN pe.amount ELSE 0 END), 0) / pba.wants_amount) * 100)
                ELSE 0
            END as usage_percentage
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON pe.user_id = pba.user_id 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pba.user_id = ? AND pba.is_active = 1
        GROUP BY pba.wants_amount
        
        UNION ALL
        
        SELECT 
            'Savings' as category_name,
            pba.savings_amount as budgeted,
            COALESCE(SUM(pgc.amount), 0) as actual,
            CASE 
                WHEN pba.savings_amount > 0 
                THEN ((COALESCE(SUM(pgc.amount), 0) / pba.savings_amount) * 100)
                ELSE 0
            END as usage_percentage
        FROM personal_budget_allocation pba
        LEFT JOIN personal_goals pg ON pg.user_id = pba.user_id
        LEFT JOIN personal_goal_contributions pgc ON pg.id = pgc.goal_id
            AND MONTH(pgc.contribution_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pgc.contribution_date) = YEAR(CURRENT_DATE())
        WHERE pba.user_id = ? AND pba.is_active = 1
        GROUP BY pba.savings_amount
        
        ORDER BY usage_percentage DESC
    ";
    
    $stmt = $conn->prepare($budget_query);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $budget_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'categories' => $budget_performance
    ];
}
        
        SELECT 
            'Wants' as category_name,
            pba.wants_amount as budgeted,
            COALESCE(SUM(CASE WHEN bc.category_type = 'wants' THEN pe.amount ELSE 0 END), 0) as actual,
            CASE 
                WHEN pba.wants_amount > 0 
                THEN ((COALESCE(SUM(CASE WHEN bc.category_type = 'wants' THEN pe.amount ELSE 0 END), 0) / pba.wants_amount) * 100)
                ELSE 0
            END as usage_percentage
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON pe.user_id = pba.user_id 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pba.user_id = ? AND pba.is_active = 1
        
        UNION ALL
        
        SELECT 
            'Savings' as category_name,
            pba.savings_amount as budgeted,
            COALESCE(SUM(pg.current_amount), 0) as actual,
            CASE 
                WHEN pba.savings_amount > 0 
                THEN ((COALESCE(SUM(pg.current_amount), 0) / pba.savings_amount) * 100)
                ELSE 0
            END as usage_percentage
        FROM personal_budget_allocation pba
        LEFT JOIN personal_goals pg ON pg.user_id = pba.user_id
        WHERE pba.user_id = ? AND pba.is_active = 1
        
        ORDER BY usage_percentage DESC
    ";
    
    $stmt = $conn->prepare($budget_query);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $budget_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'categories' => $budget_performance
    ];
}

function getPredictiveInsights($conn, $user_id) {
    // Simple predictions based on historical data
    $predictions = [];
    
    // Monthly expense prediction
    $expense_trend = "
        SELECT 
            MONTH(expense_date) as month,
            SUM(amount) as total
        FROM personal_expenses 
        WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month
    ";
    
    $stmt = $conn->prepare($expense_trend);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $expense_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (count($expense_data) >= 3) {
        $avg_expense = array_sum(array_column($expense_data, 'total')) / count($expense_data);
        $predictions['next_month_expense'] = round($avg_expense, 2);
        
        // Goal completion predictions
        $goal_prediction = "
            SELECT 
                goal_name,
                target_amount,
                current_amount,
                target_date,
                DATEDIFF(target_date, CURRENT_DATE()) as days_left
            FROM personal_goals 
            WHERE user_id = ? AND target_date > CURRENT_DATE()
        ";
        
        $stmt = $conn->prepare($goal_prediction);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $predictions['goal_predictions'] = [];
        foreach ($goals as $goal) {
            $remaining = safe_float($goal['target_amount']) - safe_float($goal['current_amount']);
            $days_left = (int)$goal['days_left'];
            
            if ($days_left > 0) {
                $daily_needed = $remaining / $days_left;
                $predictions['goal_predictions'][] = [
                    'goal_name' => $goal['goal_name'],
                    'daily_savings_needed' => round($daily_needed, 2),
                    'likelihood' => $daily_needed <= 50 ? 'high' : ($daily_needed <= 150 ? 'medium' : 'low')
                ];
            }
        }
    }
    
    return $predictions;
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
            COUNT(*) as transaction_count,
            COUNT(DISTINCT category) as categories_used
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
        'message' => "I can help you with questions about:",
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

function getSpendingThisMonth($conn, $user_id) {
    $query = "
        SELECT 
            SUM(amount) as total,
            COUNT(*) as transactions,
            AVG(amount) as avg_transaction,
            category,
            SUM(amount) as category_total
        FROM personal_expenses 
        WHERE user_id = ? 
        AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())
        GROUP BY category
        ORDER BY category_total DESC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $total = array_sum(array_column($result, 'category_total'));
    
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
            'Overall Budget' as category_name,
            pba.monthly_salary as budget,
            COALESCE(SUM(pe.amount), 0) as spent,
            ROUND((COALESCE(SUM(pe.amount), 0) / pba.monthly_salary) * 100, 1) as usage_percent
        FROM personal_budget_allocation pba
        LEFT JOIN personal_expenses pe ON pe.user_id = pba.user_id 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        WHERE pba.user_id = ? AND pba.is_active = 1
        GROUP BY pba.monthly_salary
        HAVING usage_percent > 80
        ORDER BY usage_percent DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $overbudget = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($overbudget)) {
        return ['message' => "Great job! You're staying within budget this month. 🎉"];
    }
    
    return [
        'message' => "You're using " . $overbudget[0]['usage_percent'] . "% of your monthly budget.",
        'data' => $overbudget
    ];
}

function getTopExpenses($conn, $user_id) {
    $query = "
        SELECT 
            description,
            amount,
            category,
            expense_date
        FROM personal_expenses 
        WHERE user_id = ? 
        AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        ORDER BY amount DESC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
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
