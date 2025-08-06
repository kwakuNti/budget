<?php
/**
 * Personal Dashboard Data API
 * Provides all data needed for the personal dashboard
 */

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';
require_once '../includes/personal_setup.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and has personal account
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access - please log in'
    ]);
    exit;
}

// Verify user has personal account
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type, first_name, last_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['user_type'] !== 'personal') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied - personal account required'
    ]);
    exit;
}

// Check if user needs initial setup
if (needsPersonalSetup($conn, $userId)) {
    $setupResult = setupPersonalAccount($conn, $userId, true);
    if (!$setupResult['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to setup personal account',
            'error' => $setupResult['message']
        ]);
        exit;
    }
}

try {
    $currentMonth = date('Y-m');
    $lastMonth = date('Y-m', strtotime('-1 month'));
    
    // ==========================================
    // USER INFORMATION
    // ==========================================
    $userInfo = [
        'name' => trim($user['first_name'] . ' ' . $user['last_name']),
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'initials' => strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1))
    ];
    
    // ==========================================
    // SALARY INFORMATION
    // ==========================================
    $stmt = $conn->prepare("
        SELECT 
            monthly_salary,
            pay_frequency,
            next_pay_date,
            created_at
        FROM salaries 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $salaryInfo = $stmt->get_result()->fetch_assoc();
    
    // ==========================================
    // BUDGET ALLOCATION
    // ==========================================
    $stmt = $conn->prepare("
        SELECT 
            needs_percentage,
            wants_percentage,
            savings_percentage,
            monthly_salary,
            needs_amount,
            wants_amount,
            savings_amount
        FROM personal_budget_allocation 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $budgetAllocation = $stmt->get_result()->fetch_assoc();
    
    // ==========================================
    // MONTHLY FINANCIAL OVERVIEW
    // ==========================================
    
    // Total income this month (from personal_income table)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_income
        FROM personal_income 
        WHERE user_id = ? 
        AND DATE_FORMAT(income_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $actualMonthlyIncome = floatval($stmt->get_result()->fetch_assoc()['total_income']);
    
    // Add projected income from income sources (personal_income_sources table)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(monthly_amount), 0) as projected_income
        FROM personal_income_sources 
        WHERE user_id = ? 
        AND is_active = 1
        AND include_in_budget = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $projectedIncome = floatval($stmt->get_result()->fetch_assoc()['projected_income']);
    
    // Combine actual and projected income
    $monthlyIncome = $actualMonthlyIncome + $projectedIncome;
    
    // Total expenses this month
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM personal_expenses 
        WHERE user_id = ? 
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $monthlyExpenses = floatval($stmt->get_result()->fetch_assoc()['total_expenses']);
    
    // Last month expenses for comparison
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM personal_expenses 
        WHERE user_id = ? 
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $lastMonth);
    $stmt->execute();
    $lastMonthExpenses = floatval($stmt->get_result()->fetch_assoc()['total_expenses']);
    
    // Calculate expense change percentage
    $expenseChange = $lastMonthExpenses > 0 ? 
        round((($monthlyExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100, 1) : 0;
    
    // Calculate available balance (total income - total expenses)
    $availableBalance = $monthlyIncome - $monthlyExpenses;
    
    // Calculate savings rate
    $savingsRate = $monthlyIncome > 0 ? 
        round((($monthlyIncome - $monthlyExpenses) / $monthlyIncome) * 100, 1) : 0;
    
    // ==========================================
    // CATEGORY-WISE SPENDING
    // ==========================================
    $stmt = $conn->prepare("
        SELECT 
            bc.name,
            bc.category_type,
            bc.icon,
            bc.budget_limit,
            COALESCE(SUM(pe.amount), 0) as spent_amount,
            COUNT(pe.id) as transaction_count
        FROM budget_categories bc
        LEFT JOIN personal_expenses pe ON bc.id = pe.category_id 
            AND pe.user_id = ? 
            AND DATE_FORMAT(pe.expense_date, '%Y-%m') = ?
        WHERE bc.user_id = ? AND bc.is_active = 1
        GROUP BY bc.id, bc.name, bc.category_type, bc.icon, bc.budget_limit
        ORDER BY spent_amount DESC
    ");
    $stmt->bind_param("isi", $userId, $currentMonth, $userId);
    $stmt->execute();
    $categorySpending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // ==========================================
    // BUDGET ALLOCATION PROGRESS
    // ==========================================
    $allocationProgress = [];
    if ($budgetAllocation) {
        // Calculate spent amounts by allocation type
        $stmt = $conn->prepare("
            SELECT 
                bc.category_type,
                COALESCE(SUM(pe.amount), 0) as spent_amount
            FROM budget_categories bc
            LEFT JOIN personal_expenses pe ON bc.id = pe.category_id 
                AND pe.user_id = ? 
                AND DATE_FORMAT(pe.expense_date, '%Y-%m') = ?
            WHERE bc.user_id = ? AND bc.is_active = 1
            GROUP BY bc.category_type
        ");
        $stmt->bind_param("isi", $userId, $currentMonth, $userId);
        $stmt->execute();
        $spendingByType = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $spendingMap = [];
        foreach ($spendingByType as $spending) {
            $spendingMap[$spending['category_type']] = floatval($spending['spent_amount']);
        }
        
        $allocationProgress = [
            'needs' => [
                'budgeted' => floatval($budgetAllocation['needs_amount']),
                'spent' => $spendingMap['needs'] ?? 0,
                'percentage' => intval($budgetAllocation['needs_percentage']),
                'remaining' => floatval($budgetAllocation['needs_amount']) - ($spendingMap['needs'] ?? 0)
            ],
            'wants' => [
                'budgeted' => floatval($budgetAllocation['wants_amount']),
                'spent' => $spendingMap['wants'] ?? 0,
                'percentage' => intval($budgetAllocation['wants_percentage']),
                'remaining' => floatval($budgetAllocation['wants_amount']) - ($spendingMap['wants'] ?? 0)
            ],
            'savings' => [
                'budgeted' => floatval($budgetAllocation['savings_amount']),
                'spent' => $spendingMap['savings'] ?? 0,
                'percentage' => intval($budgetAllocation['savings_percentage']),
                'remaining' => floatval($budgetAllocation['savings_amount']) - ($spendingMap['savings'] ?? 0)
            ]
        ];
    }
    
    // ==========================================
    // RECENT TRANSACTIONS
    // ==========================================
    $stmt = $conn->prepare("
        (SELECT 
            'expense' as type,
            pe.amount,
            pe.description,
            pe.expense_date as transaction_date,
            bc.name as category_name,
            bc.icon as category_icon,
            bc.category_type,
            pe.created_at
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ?)
        UNION ALL
        (SELECT 
            'income' as type,
            pi.amount,
            pi.description,
            pi.income_date as transaction_date,
            pi.source as category_name,
            '💰' as category_icon,
            'income' as category_type,
            pi.created_at
        FROM personal_income pi
        WHERE pi.user_id = ?)
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $recentTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // ==========================================
    // FINANCIAL GOALS
    // ==========================================
    $stmt = $conn->prepare("
        SELECT 
            goal_name,
            target_amount,
            current_amount,
            target_date,
            goal_type,
            priority,
            is_completed,
            CASE 
                WHEN target_amount > 0 THEN ROUND((current_amount / target_amount) * 100, 1)
                ELSE 0
            END as progress_percentage
        FROM personal_goals 
        WHERE user_id = ? 
        ORDER BY is_completed ASC, priority DESC, target_date ASC
        LIMIT 5
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $financialGoals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // ==========================================
    // CHART DATA (Last 6 months)
    // ==========================================
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(month_data.month_date, '%b %Y') as period,
            COALESCE(income.total_income, 0) as income,
            COALESCE(expenses.total_expenses, 0) as expenses
        FROM (
            SELECT DATE_SUB(CURDATE(), INTERVAL n MONTH) as month_date
            FROM (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) months
        ) month_data
        LEFT JOIN (
            SELECT 
                DATE_FORMAT(income_date, '%Y-%m') as month_key,
                SUM(amount) as total_income
            FROM personal_income 
            WHERE user_id = ? 
            AND income_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(income_date, '%Y-%m')
        ) income ON DATE_FORMAT(month_data.month_date, '%Y-%m') = income.month_key
        LEFT JOIN (
            SELECT 
                DATE_FORMAT(expense_date, '%Y-%m') as month_key,
                SUM(amount) as total_expenses
            FROM personal_expenses 
            WHERE user_id = ? 
            AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ) expenses ON DATE_FORMAT(month_data.month_date, '%Y-%m') = expenses.month_key
        ORDER BY month_data.month_date ASC
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $chartData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // ==========================================
    // RESPONSE DATA
    // ==========================================
    $responseData = [
        'success' => true,
        'user' => $userInfo,
        'salary' => $salaryInfo,
        'budget_allocation' => $budgetAllocation,
        'allocation_progress' => $allocationProgress,
        'financial_overview' => [
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'available_balance' => $availableBalance,
            'savings_rate' => $savingsRate,
            'expense_change_percentage' => $expenseChange
        ],
        'category_spending' => $categorySpending,
        'recent_transactions' => $recentTransactions,
        'financial_goals' => $financialGoals,
        'chart_data' => $chartData,
        'summary_stats' => [
            'total_categories' => count($categorySpending),
            'active_goals' => count(array_filter($financialGoals, fn($goal) => !$goal['is_completed'])),
            'transactions_this_month' => count(array_filter($recentTransactions, fn($t) => 
                date('Y-m', strtotime($t['transaction_date'])) === $currentMonth
            ))
        ]
    ];
    
    echo json_encode($responseData);
    
} catch (Exception $e) {
    error_log("Personal Dashboard API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard data',
        'error' => $e->getMessage()
    ]);
}
?>
