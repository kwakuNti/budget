<?php
/**
 * Personal Dashboard Actions Handler
 * Handles actions from the personal dashboard modals
 */

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access - please log in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'setup_salary_budget':
            setupSalaryBudget($conn, $userId);
            break;
        
        case 'add_income':
            addIncome($conn, $userId);
            break;
        
        case 'add_expense':
            addExpense($conn, $userId);
            break;
        
        case 'get_dashboard_summary':
            getDashboardSummary($conn, $userId);
            break;
        
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

/**
 * Setup salary and budget allocation from dashboard modal
 */
function setupSalaryBudget($conn, $userId) {
    $monthlySalary = floatval($_POST['monthlySalary'] ?? 0);
    $payFrequency = $_POST['payFrequency'] ?? 'monthly';
    $nextPayDate = $_POST['nextPayDate'] ?? null;
    $needsPercent = intval($_POST['needsPercent'] ?? 50);
    $wantsPercent = intval($_POST['wantsPercent'] ?? 30);
    $savingsPercent = intval($_POST['savingsPercent'] ?? 20);
    
    // Validation
    if ($monthlySalary <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid salary amount'
        ]);
        return;
    }
    
    if ($needsPercent + $wantsPercent + $savingsPercent !== 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Budget allocation must total 100%'
        ]);
        return;
    }
    
    if (!$nextPayDate) {
        // Set default next pay date based on frequency
        switch ($payFrequency) {
            case 'weekly':
                $nextPayDate = date('Y-m-d', strtotime('next friday'));
                break;
            case 'bi-weekly':
                $nextPayDate = date('Y-m-d', strtotime('+2 weeks'));
                break;
            case 'monthly':
            default:
                $nextPayDate = date('Y-m-t'); // Last day of current month
                break;
        }
    }
    
    $conn->begin_transaction();
    
    try {
        // 1. Update/Insert salary information
        $stmt = $conn->prepare("UPDATE salaries SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $stmt = $conn->prepare("
            INSERT INTO salaries (
                user_id, monthly_salary, pay_frequency, next_pay_date, 
                is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->bind_param("idss", $userId, $monthlySalary, $payFrequency, $nextPayDate);
        $stmt->execute();
        
        // 2. Update/Insert budget allocation
        $stmt = $conn->prepare("UPDATE personal_budget_allocation SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $stmt = $conn->prepare("
            INSERT INTO personal_budget_allocation (
                user_id, needs_percentage, wants_percentage, savings_percentage, 
                monthly_salary, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->bind_param("iiiid", $userId, $needsPercent, $wantsPercent, $savingsPercent, $monthlySalary);
        $stmt->execute();
        
        // 3. Create default budget categories if they don't exist
        createDefaultBudgetCategories($conn, $userId);
        
        // 4. Add salary as income for current month
        $currentDate = date('Y-m-d');
        $stmt = $conn->prepare("
            INSERT INTO personal_income (
                user_id, source, amount, income_date, income_type, 
                description, is_recurring, recurring_frequency, created_at
            ) VALUES (?, 'Monthly Salary', ?, ?, 'salary', 'Primary monthly salary', 1, ?, NOW())
        ");
        $stmt->bind_param("idss", $userId, $monthlySalary, $currentDate, $payFrequency);
        $stmt->execute();
        
        $conn->commit();
        
        // Calculate amounts for response
        $needsAmount = $monthlySalary * $needsPercent / 100;
        $wantsAmount = $monthlySalary * $wantsPercent / 100;
        $savingsAmount = $monthlySalary * $savingsPercent / 100;
        
        echo json_encode([
            'success' => true,
            'message' => 'Salary and budget setup completed successfully!',
            'data' => [
                'monthly_salary' => $monthlySalary,
                'pay_frequency' => $payFrequency,
                'next_pay_date' => $nextPayDate,
                'budget_allocation' => [
                    'needs' => ['percent' => $needsPercent, 'amount' => $needsAmount],
                    'wants' => ['percent' => $wantsPercent, 'amount' => $wantsAmount],
                    'savings' => ['percent' => $savingsPercent, 'amount' => $savingsAmount]
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Add income from dashboard modal
 */
function addIncome($conn, $userId) {
    $amount = floatval($_POST['amount'] ?? 0);
    $source = trim($_POST['source'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $incomeDate = $_POST['incomeDate'] ?? date('Y-m-d');
    
    if ($amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid amount'
        ]);
        return;
    }
    
    if (empty($source)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select an income source'
        ]);
        return;
    }
    
    // Map source to income type
    $incomeTypeMap = [
        'salary' => 'salary',
        'freelance' => 'freelance',
        'side-work' => 'other',
        'gift' => 'other',
        'investment' => 'investment',
        'other' => 'other'
    ];
    
    $incomeType = $incomeTypeMap[$source] ?? 'other';
    
    $stmt = $conn->prepare("
        INSERT INTO personal_income (
            user_id, source, amount, income_date, income_type, 
            description, is_recurring, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param("isdsss", $userId, $source, $amount, $incomeDate, $incomeType, $description);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Income added successfully!',
        'data' => [
            'amount' => $amount,
            'source' => $source,
            'date' => $incomeDate
        ]
    ]);
}

/**
 * Add expense from dashboard modal
 */
function addExpense($conn, $userId) {
    $amount = floatval($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $expenseDate = $_POST['expenseDate'] ?? date('Y-m-d');
    
    if ($amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid amount'
        ]);
        return;
    }
    
    if (empty($category)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select a category'
        ]);
        return;
    }
    
    // Get or create category
    $categoryId = getOrCreateCategory($conn, $userId, $category);
    
    $stmt = $conn->prepare("
        INSERT INTO personal_expenses (
            user_id, category_id, amount, description, expense_date, 
            payment_method, created_at
        ) VALUES (?, ?, ?, ?, ?, 'card', NOW())
    ");
    $stmt->bind_param("iidss", $userId, $categoryId, $amount, $description, $expenseDate);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Expense added successfully!',
        'data' => [
            'amount' => $amount,
            'category' => $category,
            'date' => $expenseDate
        ]
    ]);
}

/**
 * Get dashboard summary data
 */
function getDashboardSummary($conn, $userId) {
    $currentMonth = date('Y-m');
    
    // Get monthly income
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_income
        FROM personal_income 
        WHERE user_id = ? AND DATE_FORMAT(income_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $monthlyIncome = floatval($stmt->get_result()->fetch_assoc()['total_income']);
    
    // Get monthly expenses
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM personal_expenses 
        WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $monthlyExpenses = floatval($stmt->get_result()->fetch_assoc()['total_expenses']);
    
    // Get salary info
    $stmt = $conn->prepare("
        SELECT monthly_salary, pay_frequency, next_pay_date
        FROM salaries 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $salaryInfo = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'current_balance' => $monthlyIncome - $monthlyExpenses,
            'salary_info' => $salaryInfo
        ]
    ]);
}

/**
 * Get or create budget category
 */
function getOrCreateCategory($conn, $userId, $categoryKey) {
    // Map category keys to names and types
    $categoryMap = [
        // Needs
        'needs-food' => ['Food & Groceries', 'needs', '🍔'],
        'needs-utilities' => ['Utilities', 'needs', '💡'],
        
        // Wants
        'wants-entertainment' => ['Entertainment', 'wants', '🎮'],
        'wants-shopping' => ['Shopping', 'wants', '🛍️'],
        'wants-dining' => ['Dining Out', 'wants', '🍽️'],
        'wants-hobbies' => ['Hobbies', 'wants', '🎨']
    ];
    
    if (!isset($categoryMap[$categoryKey])) {
        // Default category
        $categoryInfo = ['Other', 'needs', '📝'];
    } else {
        $categoryInfo = $categoryMap[$categoryKey];
    }
    
    // Check if category exists
    $stmt = $conn->prepare("
        SELECT id FROM budget_categories 
        WHERE user_id = ? AND name = ? AND is_active = 1
    ");
    $stmt->bind_param("is", $userId, $categoryInfo[0]);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        return $result['id'];
    }
    
    // Create new category
    $stmt = $conn->prepare("
        INSERT INTO budget_categories (
            user_id, name, category_type, icon, color, is_active, created_at
        ) VALUES (?, ?, ?, ?, '#3498db', 1, NOW())
    ");
    $stmt->bind_param("isss", $userId, $categoryInfo[0], $categoryInfo[1], $categoryInfo[2]);
    $stmt->execute();
    
    return $conn->insert_id;
}

/**
 * Create default budget categories
 */
function createDefaultBudgetCategories($conn, $userId) {
    $categories = [
        // Needs
        ['Utilities', 'needs', '💡', '#f39c12'],
        
        // Wants
        ['Entertainment', 'wants', '🎮', '#1abc9c'],
        
        // Savings
        ['Investment', 'savings', '📈', '#8e44ad']
    ];
    
    foreach ($categories as $cat) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO budget_categories (
                user_id, name, category_type, icon, color, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param("issss", $userId, $cat[0], $cat[1], $cat[2], $cat[3]);
        $stmt->execute();
    }
}

$conn->close();
?>
