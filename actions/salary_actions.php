<?php
/**
 * Salary Actions Handler
 * Handles all salary-related operations for personal accounts
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
        case 'save_primary_salary':
            savePrimarySalary($conn, $userId);
            break;
        
        case 'get_salary_data':
            getSalaryData($conn, $userId);
            break;
        
        case 'update_budget_allocation':
            updateBudgetAllocation($conn, $userId);
            break;
        
        case 'save_pay_schedule':
            savePaySchedule($conn, $userId);
            break;
        
        case 'add_income_source':
            addIncomeSource($conn, $userId);
            break;
        
        case 'delete_income_source':
            deleteIncomeSource($conn, $userId);
            break;
        
        case 'confirm_salary_received':
            confirmSalaryReceived($conn, $userId);
            break;
        
        case 'check_salary_due':
            checkSalaryDue($conn, $userId);
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
 * Save or update primary salary information
 */
function savePrimarySalary($conn, $userId) {
    $salaryAmount = floatval($_POST['salaryAmount'] ?? 0);
    $payFrequency = $_POST['payFrequency'] ?? 'monthly';
    $nextPayDate = $_POST['nextPayDate'] ?? null;
    $employer = $_POST['employer'] ?? '';
    $paymentMethod = $_POST['paymentMethod'] ?? 'bank';
    $paymentAccount = $_POST['paymentAccount'] ?? '';
    $autoBudget = isset($_POST['autoBudget']) ? 1 : 0;
    
    if ($salaryAmount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid salary amount'
        ]);
        return;
    }
    
    if (!$nextPayDate) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select your next pay date'
        ]);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // First, deactivate any existing salary records
        $stmt = $conn->prepare("UPDATE salaries SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Insert new salary record
        $stmt = $conn->prepare("
            INSERT INTO salaries (
                user_id, monthly_salary, pay_frequency, next_pay_date, 
                is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->bind_param("idss", $userId, $salaryAmount, $payFrequency, $nextPayDate);
        $stmt->execute();
        
        // If auto-budget is enabled, create/update budget allocation
        if ($autoBudget) {
            // Deactivate existing budget allocations
            $stmt = $conn->prepare("UPDATE personal_budget_allocation SET is_active = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Create new budget allocation with 50-30-20 rule
            $stmt = $conn->prepare("
                INSERT INTO personal_budget_allocation (
                    user_id, needs_percentage, wants_percentage, savings_percentage, 
                    monthly_salary, is_active, created_at, updated_at
                ) VALUES (?, 50, 30, 20, ?, 1, NOW(), NOW())
            ");
            $stmt->bind_param("id", $userId, $salaryAmount);
            $stmt->execute();
        }
        
        // Create default budget categories if they don't exist
        createDefaultCategories($conn, $userId);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Salary information saved successfully!',
            'data' => [
                'salary_amount' => $salaryAmount,
                'pay_frequency' => $payFrequency,
                'next_pay_date' => $nextPayDate,
                'auto_budget_enabled' => $autoBudget
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Get current salary data for the user
 */
function getSalaryData($conn, $userId) {
    // Get salary information
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
    $salaryData = $stmt->get_result()->fetch_assoc();
    
    // Get budget allocation
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
    $budgetData = $stmt->get_result()->fetch_assoc();
    
    // Get income sources
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
            is_active,
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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'salary' => $salaryData,
            'budget_allocation' => $budgetData,
            'income_sources' => $incomeSources
        ]
    ]);
}

/**
 * Update budget allocation percentages
 */
function updateBudgetAllocation($conn, $userId) {
    $needsPercent = intval($_POST['needsPercent'] ?? 50);
    $wantsPercent = intval($_POST['wantsPercent'] ?? 30);
    $savingsPercent = intval($_POST['savingsPercent'] ?? 20);
    
    // Validate percentages add up to 100
    if ($needsPercent + $wantsPercent + $savingsPercent !== 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Budget allocation must total 100%'
        ]);
        return;
    }
    
    // Get current salary amount
    $stmt = $conn->prepare("
        SELECT monthly_salary 
        FROM salaries 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Please set up your salary information first'
        ]);
        return;
    }
    
    $monthlySalary = $result['monthly_salary'];
    
    $conn->begin_transaction();
    
    try {
        // Deactivate existing budget allocation
        $stmt = $conn->prepare("UPDATE personal_budget_allocation SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Create new budget allocation
        $stmt = $conn->prepare("
            INSERT INTO personal_budget_allocation (
                user_id, needs_percentage, wants_percentage, savings_percentage, 
                monthly_salary, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->bind_param("iiiid", $userId, $needsPercent, $wantsPercent, $savingsPercent, $monthlySalary);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Budget allocation updated successfully!',
            'data' => [
                'needs_percent' => $needsPercent,
                'wants_percent' => $wantsPercent,
                'savings_percent' => $savingsPercent,
                'needs_amount' => $monthlySalary * $needsPercent / 100,
                'wants_amount' => $monthlySalary * $wantsPercent / 100,
                'savings_amount' => $monthlySalary * $savingsPercent / 100
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Save pay schedule and reminder settings
 */
function savePaySchedule($conn, $userId) {
    $payFrequency = $_POST['payFrequency'] ?? 'monthly';
    $payDay = $_POST['payDay'] ?? '';
    $nextPayDate = $_POST['nextPayDate'] ?? null;
    $expectedAmount = floatval($_POST['expectedAmount'] ?? 0);
    $enableReminders = isset($_POST['enableReminders']) ? 1 : 0;
    $reminderDays = intval($_POST['reminderDays'] ?? 3);
    $reminderTime = $_POST['reminderTime'] ?? '09:00';
    
    if (!$nextPayDate || $expectedAmount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide valid pay date and amount'
        ]);
        return;
    }
    
    // Update salary record with new schedule
    $stmt = $conn->prepare("
        UPDATE salaries 
        SET pay_frequency = ?, next_pay_date = ?, monthly_salary = ?, updated_at = NOW()
        WHERE user_id = ? AND is_active = 1
    ");
    $stmt->bind_param("ssdi", $payFrequency, $nextPayDate, $expectedAmount, $userId);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No active salary record found. Please set up your salary first.'
        ]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pay schedule updated successfully!',
        'data' => [
            'pay_frequency' => $payFrequency,
            'next_pay_date' => $nextPayDate,
            'expected_amount' => $expectedAmount,
            'reminders_enabled' => $enableReminders
        ]
    ]);
}

/**
 * Add additional income source
 */
function addIncomeSource($conn, $userId) {
    $sourceName = trim($_POST['sourceName'] ?? '');
    $incomeType = $_POST['incomeType'] ?? 'other';
    $monthlyAmount = floatval($_POST['monthlyAmount'] ?? 0);
    $paymentFrequency = $_POST['paymentFrequency'] ?? 'monthly';
    $paymentMethod = $_POST['paymentMethod'] ?? 'bank';
    $description = trim($_POST['description'] ?? '');
    $includeInBudget = isset($_POST['includeInBudget']) ? 1 : 0;
    
    if (empty($sourceName) || $monthlyAmount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a valid source name and amount'
        ]);
        return;
    }
    
    // Map income type to database ENUM values
    $incomeTypeMap = [
        'freelance' => 'freelance',
        'side-business' => 'other',
        'part-time' => 'other',
        'investment' => 'investment',
        'rental' => 'other',
        'salary' => 'salary',
        'bonus' => 'bonus',
        'other' => 'other'
    ];
    
    $mappedIncomeType = $incomeTypeMap[$incomeType] ?? 'other';
    
    // Validate payment frequency
    $validFrequencies = ['weekly', 'bi-weekly', 'monthly', 'variable', 'one-time'];
    if (!in_array($paymentFrequency, $validFrequencies)) {
        $paymentFrequency = 'monthly';
    }
    
    try {
        // Insert income source
        $stmt = $conn->prepare("
            INSERT INTO personal_income_sources (
                user_id, source_name, income_type, monthly_amount, 
                payment_frequency, payment_method, description, 
                include_in_budget, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param("issdsssi", $userId, $sourceName, $mappedIncomeType, $monthlyAmount, 
                         $paymentFrequency, $paymentMethod, $description, $includeInBudget);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Also add an income record for tracking
            $incomeDate = date('Y-m-d');
            $stmt2 = $conn->prepare("
                INSERT INTO personal_income (
                    user_id, source, amount, income_date, income_type, 
                    description, is_recurring, recurring_frequency
                ) VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ");
            
            // Map payment frequency to recurring frequency for personal_income table
            $recurringFreqMap = [
                'weekly' => 'weekly',
                'bi-weekly' => 'monthly', // Closest match
                'monthly' => 'monthly',
                'variable' => 'monthly',
                'one-time' => 'monthly'
            ];
            $recurringFreq = $recurringFreqMap[$paymentFrequency] ?? 'monthly';
            
            $stmt2->bind_param("isdssss", $userId, $sourceName, $monthlyAmount, $incomeDate, 
                              $mappedIncomeType, $description, $recurringFreq);
            $stmt2->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Income source added successfully!',
            'data' => [
                'source_name' => $sourceName,
                'amount' => $monthlyAmount,
                'frequency' => $paymentFrequency,
                'include_in_budget' => $includeInBudget
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add income source: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete income source
 */
function deleteIncomeSource($conn, $userId) {
    $sourceId = intval($_POST['source_id'] ?? 0);
    
    if ($sourceId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid source ID'
        ]);
        return;
    }
    
    try {
        // Verify ownership and delete
        $stmt = $conn->prepare("
            UPDATE personal_income_sources 
            SET is_active = 0, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $sourceId, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Income source deleted successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Income source not found or already deleted'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete income source: ' . $e->getMessage()
        ]);
    }
}

/**
 * Create default budget categories for new users
 */
function createDefaultCategories($conn, $userId) {
    $defaultCategories = [
        // Needs categories
        ['Food & Groceries', 'needs', '🍔', '#e74c3c'],
        ['Rent/Housing', 'needs', '🏠', '#e67e22'],
        ['Utilities', 'needs', '💡', '#f39c12'],
        ['Transportation', 'needs', '🚗', '#3498db'],
        ['Healthcare', 'needs', '🏥', '#9b59b6'],
        ['Insurance', 'needs', '🛡️', '#34495e'],
        
        // Wants categories
        ['Entertainment', 'wants', '🎮', '#1abc9c'],
        ['Dining Out', 'wants', '🍽️', '#e74c3c'],
        ['Shopping', 'wants', '🛍️', '#f1c40f'],
        ['Hobbies', 'wants', '🎨', '#9b59b6'],
        ['Subscription Services', 'wants', '📱', '#3498db'],
        
        // Savings categories
        ['Emergency Fund', 'savings', '🆘', '#27ae60'],
        ['Retirement', 'savings', '👴', '#2980b9'],
        ['Vacation Fund', 'savings', '✈️', '#e67e22'],
        ['Investment', 'savings', '📈', '#8e44ad']
    ];
    
    foreach ($defaultCategories as $category) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO budget_categories (
                user_id, name, category_type, icon, color, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param("issss", $userId, $category[0], $category[1], $category[2], $category[3]);
        $stmt->execute();
    }
}

/**
 * Confirm salary received and add to balance
 */
function confirmSalaryReceived($conn, $userId) {
    try {
        // Get active salary information
        $stmt = $conn->prepare("
            SELECT id, monthly_salary, pay_frequency, next_pay_date 
            FROM salaries 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $salary = $stmt->get_result()->fetch_assoc();
        
        if (!$salary) {
            echo json_encode([
                'success' => false,
                'message' => 'No active salary found'
            ]);
            return;
        }
        
        $conn->begin_transaction();
        
        // Add salary to personal income
        $stmt = $conn->prepare("
            INSERT INTO personal_income (
                user_id, source, amount, income_date, income_type, 
                description, is_recurring, recurring_frequency
            ) VALUES (?, 'Primary Salary', ?, CURDATE(), 'salary', 'Salary payment received', 1, 'monthly')
        ");
        $stmt->bind_param("id", $userId, $salary['monthly_salary']);
        $stmt->execute();
        
        // Calculate next pay date
        $nextPayDate = calculateNextPayDate($salary['next_pay_date'], $salary['pay_frequency']);
        
        // Update next pay date
        $stmt = $conn->prepare("
            UPDATE salaries 
            SET next_pay_date = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $nextPayDate, $salary['id']);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Salary confirmed! Amount added to your income.',
            'data' => [
                'amount' => $salary['monthly_salary'],
                'next_pay_date' => $nextPayDate
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to confirm salary: ' . $e->getMessage()
        ]);
    }
}

/**
 * Check if salary is due
 */
function checkSalaryDue($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT monthly_salary, pay_frequency, next_pay_date 
            FROM salaries 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $salary = $stmt->get_result()->fetch_assoc();
        
        if (!$salary || !$salary['next_pay_date']) {
            echo json_encode([
                'success' => true,
                'salary_due' => false,
                'message' => 'No salary schedule found'
            ]);
            return;
        }
        
        $today = new DateTime();
        $payDate = new DateTime($salary['next_pay_date']);
        $daysDiff = $today->diff($payDate)->days;
        $isPastDue = $today > $payDate;
        
        $isDue = $isPastDue || $daysDiff <= 1;
        
        echo json_encode([
            'success' => true,
            'salary_due' => $isDue,
            'is_past_due' => $isPastDue,
            'days_until' => $isPastDue ? -$daysDiff : $daysDiff,
            'pay_date' => $salary['next_pay_date'],
            'amount' => $salary['monthly_salary'],
            'message' => $isDue ? 'Salary is due!' : 'Salary not due yet'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check salary status: ' . $e->getMessage()
        ]);
    }
}

/**
 * Calculate next pay date based on frequency
 */
function calculateNextPayDate($currentDate, $frequency) {
    $date = new DateTime($currentDate);
    
    switch ($frequency) {
        case 'weekly':
            $date->add(new DateInterval('P7D'));
            break;
        case 'bi-weekly':
            $date->add(new DateInterval('P14D'));
            break;
        case 'semi-monthly':
            // Add 15 days for semi-monthly
            $date->add(new DateInterval('P15D'));
            break;
        case 'monthly':
        default:
            $date->add(new DateInterval('P1M'));
            break;
    }
    
    return $date->format('Y-m-d');
}

$conn->close();
?>
