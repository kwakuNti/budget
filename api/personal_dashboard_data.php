<?php
/**
 * Personal Dashboard Data API
 * Provides all data needed for the personal dashboard
 */

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

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

try {
    // User Information
    $userInfo = [
        'id' => $userId,
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
    ];
    
    // Salary Information (SCHEDULED - for reference only)
    $stmt = $conn->prepare("
        SELECT 
            monthly_salary,
            pay_frequency,
            next_pay_date,
            created_at,
            updated_at
        FROM salaries 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $salaryInfo = $stmt->get_result()->fetch_assoc();
    
    // Check if salary is due/overdue (auto-confirmation logic)
    $salaryConfirmed = false;
    $confirmedSalaryAmount = 0;
    if ($salaryInfo) {
        $today = date('Y-m-d');
        $nextPayDate = $salaryInfo['next_pay_date'];
        $salaryConfirmed = $nextPayDate <= $today;
        if ($salaryConfirmed) {
            $confirmedSalaryAmount = floatval($salaryInfo['monthly_salary']);
        }
    }
    
    // Budget Allocation
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
    
    // CONFIRMED RECEIVED INCOME - This is the key change!
    $currentMonth = date('Y-m');
    $stmt = $conn->prepare("
        SELECT 
            source,
            amount,
            income_date,
            income_type,
            description
        FROM personal_income 
        WHERE user_id = ? 
        AND DATE_FORMAT(income_date, '%Y-%m') = ?
        ORDER BY income_date DESC
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $confirmedIncomeResult = $stmt->get_result();
    
    $confirmedIncomeList = [];
    $totalConfirmedIncome = 0;
    while ($row = $confirmedIncomeResult->fetch_assoc()) {
        $confirmedIncomeList[] = $row;
        $totalConfirmedIncome += floatval($row['amount']);
    }
    
    // Income Sources - SCHEDULED (for reference only, not included in calculations)
    $stmt = $conn->prepare("
        SELECT 
            id,
            source_name,
            income_type,
            monthly_amount,
            payment_frequency,
            payment_method,
            description,
            include_in_budget,
            created_at
        FROM personal_income_sources 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $incomeSourcesResult = $stmt->get_result();
    
    $incomeSources = [];
    while ($row = $incomeSourcesResult->fetch_assoc()) {
        $incomeSources[] = $row;
    }
    
    // CORRECTED: Calculate total monthly income from CONFIRMED sources only
    $monthlyIncome = $totalConfirmedIncome + $confirmedSalaryAmount;
    
    // Get monthly expenses (FIXED - removed is_active)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM personal_expenses 
        WHERE user_id = ? 
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $expenseResult = $stmt->get_result()->fetch_assoc();
    $monthlyExpenses = floatval($expenseResult['total_expenses']);
    
    // Available balance
    $availableBalance = $monthlyIncome - $monthlyExpenses;
    
    // Recent Transactions (FIXED - include all required fields for frontend)
    $stmt = $conn->prepare("
        SELECT 
            pe.id,
            pe.amount,
            pe.description,
            pe.expense_date,
            pe.payment_method,
            pe.created_at,
            COALESCE(bc.name, 'Uncategorized') as category_name,
            'expense' as type
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ?
        ORDER BY pe.expense_date DESC, pe.created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $expenseTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent income transactions
    $stmt = $conn->prepare("
        SELECT 
            pi.id,
            pi.amount,
            pi.source as description,
            pi.income_date as expense_date,
            'income' as payment_method,
            pi.created_at,
            CONCAT(pi.income_type, ' Income') as category_name,
            'income' as type
        FROM personal_income pi
        WHERE pi.user_id = ?
        ORDER BY pi.income_date DESC, pi.created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $incomeTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combine and sort all transactions
    $allTransactions = array_merge($expenseTransactions, $incomeTransactions);
    usort($allTransactions, function($a, $b) {
        $dateA = $a['expense_date'] . ' ' . $a['created_at'];
        $dateB = $b['expense_date'] . ' ' . $b['created_at'];
        return strtotime($dateB) - strtotime($dateA);
    });
    
    $recentTransactions = array_slice($allTransactions, 0, 10);
    
    // Get Personal Savings Goals
    $stmt = $conn->prepare("
        SELECT 
            id,
            goal_name,
            target_amount,
            current_amount,
            target_date,
            goal_type,
            priority,
            is_completed,
            created_at
        FROM personal_goals 
        WHERE user_id = ? 
        AND is_completed = FALSE
        ORDER BY priority DESC, target_date ASC
        LIMIT 10
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goalsResult = $stmt->get_result();
    
    $savingsGoals = [];
    while ($row = $goalsResult->fetch_assoc()) {
        $progressPercentage = $row['target_amount'] > 0 ? 
            min(100, ($row['current_amount'] / $row['target_amount']) * 100) : 0;
            
        $savingsGoals[] = [
            'id' => intval($row['id']),
            'goal_name' => $row['goal_name'],
            'target_amount' => floatval($row['target_amount']),
            'current_amount' => floatval($row['current_amount']),
            'target_date' => $row['target_date'],
            'goal_type' => $row['goal_type'],
            'priority' => $row['priority'],
            'progress_percentage' => round($progressPercentage, 1),
            'is_on_track' => $progressPercentage >= 50 // Simple heuristic
        ];
    }
    
    // Response Construction with CORRECTED CONFIRMED INCOME LOGIC
    $responseData = [
        'success' => true,
        'user' => $userInfo,
        'salary' => $salaryInfo,
        'salary_confirmed' => $salaryConfirmed,
        'confirmed_salary_amount' => $confirmedSalaryAmount,
        'budget_allocation' => $budgetAllocation,
        'income_sources' => $incomeSources,
        'confirmed_income' => $confirmedIncomeList,
        'financial_overview' => [
            'monthly_income' => $monthlyIncome, // Now only confirmed income
            'monthly_expenses' => $monthlyExpenses,
            'available_balance' => $availableBalance,
            'confirmed_salary' => $confirmedSalaryAmount,
            'confirmed_additional_income' => $totalConfirmedIncome,
            'total_confirmed_income' => $monthlyIncome,
            'salary_due_date' => $salaryInfo['next_pay_date'] ?? null,
            'salary_status' => $salaryConfirmed ? 'confirmed' : 'pending',
            'income_sources_count' => count($incomeSources),
            'confirmed_income_count' => count($confirmedIncomeList)
        ],
        'recent_transactions' => $recentTransactions,
        'savings_goals' => $savingsGoals
    ];
    
    echo json_encode($responseData);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
    ]);
}
?>
