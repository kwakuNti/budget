<?php
/**
 * Savings Handler - Fixed Version
 * Manages personal savings goals, auto-save settings, and goal contributions
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

// Verify user has personal account
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
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
    // Handle both GET and POST requests
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_goals':
            getPersonalGoals($conn, $userId);
            break;
            
        case 'create_goal':
            createGoal($conn, $userId);
            break;
            
        case 'update_goal':
            updateGoal($conn, $userId);
            break;
            
        case 'delete_goal':
            deleteGoal($conn, $userId);
            break;
            
        case 'pause_goal':
            pauseGoal($conn, $userId);
            break;
            
        case 'resume_goal':
            resumeGoal($conn, $userId);
            break;
            
        case 'set_goal_inactive':
            setGoalInactive($conn, $userId);
            break;
            
        case 'add_contribution':
            addContribution($conn, $userId);
            break;
            
        case 'update_auto_save':
            updateAutoSave($conn, $userId);
            break;
            
        case 'get_monthly_target':
            getMonthlyTarget($conn, $userId);
            break;
            
        case 'process_salary_auto_save':
            processSalaryAutoSave($conn, $userId);
            break;
            
        case 'get_recent_activity':
            getRecentActivity($conn, $userId);
            break;
            
        case 'get_savings_overview':
            getSavingsOverview($conn, $userId);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

function getPersonalGoals($conn, $userId) {
    try {
        // First check if status column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM personal_goals LIKE 'status'");
        $stmt->execute();
        $statusExists = $stmt->get_result()->num_rows > 0;
        
        $statusField = $statusExists ? "COALESCE(pg.status, 'active') as status," : "'active' as status,";
        $statusOrderBy = $statusExists ? 
            "CASE COALESCE(pg.status, 'active')
                WHEN 'active' THEN 1
                WHEN 'paused' THEN 2
                WHEN 'inactive' THEN 3
                ELSE 4
            END ASC," : "";
        
        $stmt = $conn->prepare("
            SELECT 
                pg.id,
                pg.goal_name,
                pg.target_amount,
                pg.current_amount,
                pg.target_date,
                pg.goal_type,
                pg.priority,
                pg.is_completed,
                {$statusField}
                pg.created_at,
                pg.updated_at,
                COALESCE(pgs.auto_save_enabled, 0) as auto_save_enabled,
                COALESCE(pgs.save_method, 'manual') as save_method,
                COALESCE(pgs.save_percentage, 0) as save_percentage,
                COALESCE(pgs.save_amount, 0) as save_amount,
                COALESCE(pgs.deduct_from_income, 0) as deduct_from_income,
                CASE 
                    WHEN pg.target_amount > 0 THEN 
                        ROUND((pg.current_amount / pg.target_amount) * 100, 1)
                    ELSE 0 
                END as progress_percentage
            FROM personal_goals pg
            LEFT JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
            WHERE pg.user_id = ? 
            ORDER BY 
                pg.is_completed ASC,
                {$statusOrderBy}
                CASE pg.priority 
                    WHEN 'high' THEN 3 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 1 
                    ELSE 0 
                END DESC,
                pg.created_at DESC
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $goals = [];
        while ($row = $result->fetch_assoc()) {
            $goals[] = [
                'id' => intval($row['id']),
                'goal_name' => $row['goal_name'],
                'target_amount' => floatval($row['target_amount']),
                'current_amount' => floatval($row['current_amount']),
                'target_date' => $row['target_date'],
                'goal_type' => $row['goal_type'],
                'priority' => $row['priority'],
                'is_completed' => (bool)$row['is_completed'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'progress_percentage' => floatval($row['progress_percentage']),
                'auto_save_enabled' => (bool)$row['auto_save_enabled'],
                'save_method' => $row['save_method'],
                'save_percentage' => floatval($row['save_percentage']),
                'save_amount' => floatval($row['save_amount']),
                'deduct_from_income' => (bool)$row['deduct_from_income']
            ];
        }
        
        // Get budget allocation data
        $budgetAllocation = getBudgetAllocation($conn, $userId);
        
        echo json_encode([
            'success' => true,
            'goals' => $goals,
            'budget_allocation' => $budgetAllocation,
            'auto_save_settings' => getAutoSaveSettings($conn, $userId)
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to fetch goals: ' . $e->getMessage());
    }
}

function getBudgetAllocation($conn, $userId) {
    try {
        // Get user's current budget allocation
        $stmt = $conn->prepare("
            SELECT 
                monthly_salary,
                savings_percentage,
                savings_amount,
                needs_percentage,
                wants_percentage,
                needs_amount,
                wants_amount
            FROM personal_budget_allocation 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $budgetResult = $stmt->get_result()->fetch_assoc();
        
        if (!$budgetResult) {
            return [
                'allocated_savings' => 0,
                'monthly_salary' => 0,
                'savings_percentage' => 20,
                'total_goal_amounts' => 0,
                'remaining_allocation' => 0,
                'allocation_percentage_used' => 0
            ];
        }
        
        // Get total of all active goal auto-save amounts
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE 
                    WHEN pgs.save_method = 'percentage' THEN 
                        (? * pgs.save_percentage / 100)
                    ELSE pgs.save_amount 
                END) as total_goal_amounts
            FROM personal_goals pg
            JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
            WHERE pg.user_id = ? 
            AND pg.is_completed = 0 
            AND pg.status = 'active'
            AND pgs.auto_save_enabled = 1
        ");
        $monthlySalary = floatval($budgetResult['monthly_salary']);
        $stmt->bind_param("di", $monthlySalary, $userId);
        $stmt->execute();
        $goalResult = $stmt->get_result()->fetch_assoc();
        
        $allocatedSavings = floatval($budgetResult['savings_amount']);
        $totalGoalAmounts = floatval($goalResult['total_goal_amounts'] ?? 0);
        $remainingAllocation = max(0, $allocatedSavings - $totalGoalAmounts);
        $allocationPercentageUsed = $allocatedSavings > 0 ? ($totalGoalAmounts / $allocatedSavings) * 100 : 0;
        
        return [
            'allocated_savings' => $allocatedSavings,
            'monthly_salary' => $monthlySalary,
            'savings_percentage' => floatval($budgetResult['savings_percentage']),
            'total_goal_amounts' => $totalGoalAmounts,
            'remaining_allocation' => $remainingAllocation,
            'allocation_percentage_used' => round($allocationPercentageUsed, 1),
            'needs_amount' => floatval($budgetResult['needs_amount']),
            'wants_amount' => floatval($budgetResult['wants_amount'])
        ];
        
    } catch (Exception $e) {
        throw new Exception('Failed to get budget allocation: ' . $e->getMessage());
    }
}

function getMonthlyTargetData($conn, $userId) {
    // Get budget allocation data
    $budgetAllocation = getBudgetAllocation($conn, $userId);
    
    // Get all active goals with auto-save enabled
    $stmt = $conn->prepare("
        SELECT 
            pg.id,
            pg.goal_name,
            pgs.save_method,
            pgs.save_percentage,
            pgs.save_amount,
            pgs.deduct_from_income
        FROM personal_goals pg
        JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
        WHERE pg.user_id = ? 
        AND pg.is_completed = 0 
        AND pg.status = 'active'
        AND pgs.auto_save_enabled = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totalMonthlyTarget = 0;
    $goalBreakdown = [];
    $monthlySalary = $budgetAllocation['monthly_salary'];
    
    while ($row = $result->fetch_assoc()) {
        $goalTarget = 0;
        $goalPercentage = 0;
        
        if ($row['save_method'] === 'percentage' && $monthlySalary > 0) {
            $goalPercentage = floatval($row['save_percentage']);
            $goalTarget = ($monthlySalary * $goalPercentage) / 100;
        } else if ($row['save_method'] === 'fixed') {
            $goalTarget = floatval($row['save_amount']);
            if ($monthlySalary > 0) {
                $goalPercentage = ($goalTarget / $monthlySalary) * 100;
            }
        }
        
        $totalMonthlyTarget += $goalTarget;
        $goalBreakdown[] = [
            'goal_name' => $row['goal_name'],
            'amount' => $goalTarget,
            'percentage' => round($goalPercentage, 1),
            'method' => $row['save_method'],
            'deduct_from_income' => (bool)$row['deduct_from_income']
        ];
    }
    
    // Calculate percentage based on budget allocation, not total salary
    $allocationPercentage = $budgetAllocation['allocated_savings'] > 0 ? 
        ($totalMonthlyTarget / $budgetAllocation['allocated_savings']) * 100 : 0;
    
    return [
        'monthly_target' => $totalMonthlyTarget,
        'allocated_savings' => $budgetAllocation['allocated_savings'],
        'allocation_percentage' => round($allocationPercentage, 1),
        'salary_percentage' => $budgetAllocation['savings_percentage'],
        'monthly_salary' => $monthlySalary,
        'remaining_allocation' => $budgetAllocation['remaining_allocation'],
        'goal_breakdown' => $goalBreakdown
    ];
}

function getAutoSaveSettings($conn, $userId) {
    // Get general auto-save preferences
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_goals,
            SUM(CASE WHEN pgs.auto_save_enabled = 1 THEN 1 ELSE 0 END) as auto_save_goals
        FROM personal_goals pg
        LEFT JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
        WHERE pg.user_id = ? AND pg.is_completed = 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return [
        'total_goals' => intval($result['total_goals']),
        'auto_save_goals' => intval($result['auto_save_goals']),
        'round_up_enabled' => false, // This would be a separate setting
        'salary_auto_save_enabled' => $result['auto_save_goals'] > 0
    ];
}

function createGoal($conn, $userId) {
    // Add comprehensive debugging
    error_log("=== CREATE GOAL DEBUG START ===");
    error_log("Raw POST data: " . json_encode($_POST));
    
    $goalName = trim($_POST['goal_name'] ?? '');
    $targetAmountRaw = $_POST['target_amount'] ?? 0;
    $targetAmount = sprintf('%.2f', $targetAmountRaw);
    $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $goalType = $_POST['goal_type'] ?? 'other';
    $priority = $_POST['priority'] ?? 'medium';
    $initialDepositRaw = $_POST['initial_deposit'] ?? 0;
    $initialDeposit = sprintf('%.2f', $initialDepositRaw);
    $autoSaveEnabled = isset($_POST['auto_save_enabled']) ? (bool)$_POST['auto_save_enabled'] : false;
    $saveMethod = $_POST['save_method'] ?? 'manual';
    $savePercentageRaw = $_POST['save_percentage'] ?? 0;
    $savePercentage = sprintf('%.2f', $savePercentageRaw);
    $saveAmountRaw = $_POST['save_amount'] ?? 0;
    $saveAmount = sprintf('%.2f', $saveAmountRaw);
    $deductFromIncome = isset($_POST['deduct_from_income']) ? (bool)$_POST['deduct_from_income'] : false;
    
    // Enhanced debugging for goal_type
    error_log("Goal type received: '" . $goalType . "' (length: " . strlen($goalType) . ")");
    error_log("Goal type bytes: " . bin2hex($goalType));
    
    // Get valid goal types from database schema
    $validGoalTypes = getValidGoalTypesFromSchema($conn);
    error_log("Valid goal types from database: " . json_encode($validGoalTypes));
    
    // Validate and sanitize goal_type
    if (!in_array($goalType, $validGoalTypes)) {
        error_log("INVALID goal_type received: '" . $goalType . "'");
        error_log("Valid goal_types: " . implode(', ', $validGoalTypes));
        
        // Check for exact matches with debugging
        foreach ($validGoalTypes as $valid) {
            if (trim(strtolower($goalType)) === trim(strtolower($valid))) {
                error_log("Found case-insensitive match: '$goalType' -> '$valid'");
                $goalType = $valid;
                break;
            }
        }
        
        // If still not valid, default to 'other'
        if (!in_array($goalType, $validGoalTypes)) {
            error_log("Defaulting to 'other' for invalid goal_type: '$goalType'");
            $goalType = 'other';
        }
    }
    
    error_log("Final goal_type to be inserted: '" . $goalType . "'");
    error_log("=== CREATE GOAL DEBUG END ===");
    
    if (empty($goalName) || floatval($targetAmount) <= 0) {
        throw new Exception('Goal name and target amount are required');
    }
    
    // Debug logging
    error_log("Creating goal with parameters:");
    error_log("- goal_name: " . $goalName);
    error_log("- target_amount: " . $targetAmount);
    error_log("- goal_type: " . $goalType);
    error_log("- priority: " . $priority);
    
    // Get budget allocation data
    $budgetAllocation = getBudgetAllocation($conn, $userId);
    $monthlySalary = $budgetAllocation['monthly_salary'];
    
    // If using percentage method, calculate the fixed amount
    if ($autoSaveEnabled && $saveMethod === 'percentage' && floatval($savePercentage) > 0) {
        if ($monthlySalary > 0) {
            $calculatedAmount = ($monthlySalary * floatval($savePercentage)) / 100;
            $saveAmount = sprintf('%.2f', $calculatedAmount);
        }
    }
    
    // Check if the new goal exceeds budget allocation
    if ($autoSaveEnabled && floatval($saveAmount) > 0) {
        $currentGoalTotal = $budgetAllocation['total_goal_amounts'];
        $newTotal = $currentGoalTotal + floatval($saveAmount);
        
        if ($newTotal > $budgetAllocation['allocated_savings']) {
            $remaining = $budgetAllocation['remaining_allocation'];
            throw new Exception("Goal auto-save amount (₵{$saveAmount}) exceeds remaining budget allocation (₵{$remaining}). Please reduce the amount or adjust your budget allocation.");
        }
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if status column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM personal_goals LIKE 'status'");
        $stmt->execute();
        $statusExists = $stmt->get_result()->num_rows > 0;
        
        // Always use or create a single shared 'General Savings' category for all goals
        $sharedCategoryName = 'General Savings';
        $categoryIcon = 'piggy-bank';
        $categoryColor = '#10b981';
        $budgetLimit = floatval($targetAmount);
        
        // Check if the shared category exists
        $stmt = $conn->prepare("
            SELECT id FROM budget_categories 
            WHERE user_id = ? AND name = ? AND category_type = 'savings'
        ");
        $stmt->bind_param("is", $userId, $sharedCategoryName);
        $stmt->execute();
        $existingCategory = $stmt->get_result()->fetch_assoc();
        
        if (!$existingCategory) {
            $stmt = $conn->prepare("
                INSERT INTO budget_categories 
                (user_id, name, category_type, icon, color, budget_limit, is_active) 
                VALUES (?, ?, 'savings', ?, ?, ?, 1)
            ");
            $stmt->bind_param("isssd", $userId, $sharedCategoryName, $categoryIcon, $categoryColor, $budgetLimit);
            $stmt->execute();
            $categoryId = $conn->insert_id;
        } else {
            // Use existing shared category
            $categoryId = $existingCategory['id'];
        }
        
        if ($statusExists) {
            // Create the goal with status and new columns
            $stmt = $conn->prepare("
                INSERT INTO personal_goals 
                (user_id, goal_name, target_amount, current_amount, target_date, budget_category_id, goal_type, priority, status, auto_save_enabled, save_method, save_percentage, save_amount, deduct_from_income, is_completed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, 0)
            ");
            $autoSaveInt = $autoSaveEnabled ? 1 : 0;
            $deductInt = $deductFromIncome ? 1 : 0;
            
            // Debug the exact values being bound
            error_log("Binding parameters - userId: $userId, goalName: '$goalName', targetAmount: $targetAmount, initialDeposit: $initialDeposit, targetDate: '$targetDate', categoryId: $categoryId, goalType: '$goalType', priority: '$priority', autoSaveInt: $autoSaveInt, saveMethod: '$saveMethod', savePercentage: $savePercentage, saveAmount: $saveAmount, deductInt: $deductInt");
            
            $stmt->bind_param("isdsisissdsdi", $userId, $goalName, $targetAmount, $initialDeposit, $targetDate, $categoryId, $goalType, $priority, $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt);
        } else {
            // Create the goal without status but with new columns
            $stmt = $conn->prepare("
                INSERT INTO personal_goals 
                (user_id, goal_name, target_amount, current_amount, target_date, budget_category_id, goal_type, priority, auto_save_enabled, save_method, save_percentage, save_amount, deduct_from_income, is_completed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $autoSaveInt = $autoSaveEnabled ? 1 : 0;
            $deductInt = $deductFromIncome ? 1 : 0;
            
            // Debug the exact values being bound
            error_log("Binding parameters (no status) - userId: $userId, goalName: '$goalName', targetAmount: $targetAmount, initialDeposit: $initialDeposit, targetDate: '$targetDate', categoryId: $categoryId, goalType: '$goalType', priority: '$priority', autoSaveInt: $autoSaveInt, saveMethod: '$saveMethod', savePercentage: $savePercentage, saveAmount: $saveAmount, deductInt: $deductInt");
            
            $stmt->bind_param("isdsisissdsdi", $userId, $goalName, $targetAmount, $initialDeposit, $targetDate, $categoryId, $goalType, $priority, $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt);
        }
        
        // Add error handling for the execute with comprehensive debugging
        error_log("About to execute SQL insert...");
        
        // Get current database schema for goal_type column
        $schemaQuery = "SHOW COLUMNS FROM personal_goals LIKE 'goal_type'";
        $schemaStmt = $conn->prepare($schemaQuery);
        $schemaStmt->execute();
        $schemaResult = $schemaStmt->get_result()->fetch_assoc();
        error_log("Current database schema for goal_type: " . json_encode($schemaResult));
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $errno = $stmt->errno;
            $sqlstate = $stmt->sqlstate;
            
            error_log("=== SQL EXECUTION FAILED ===");
            error_log("MySQL Error Number: " . $errno);
            error_log("MySQL Error: " . $error);
            error_log("SQL State: " . $sqlstate);
            error_log("Goal Type Value: '" . $goalType . "'");
            error_log("Goal Type Length: " . strlen($goalType));
            error_log("Goal Type Hex: " . bin2hex($goalType));
            error_log("Goal Type UTF-8 Check: " . (mb_check_encoding($goalType, 'UTF-8') ? 'Valid' : 'Invalid'));
            
            // Try to get more specific information about the truncation
            if (strpos($error, 'Data truncated') !== false) {
                error_log("DATA TRUNCATION DETECTED");
                error_log("Checking each field for potential issues:");
                error_log("- goal_name length: " . strlen($goalName));
                error_log("- goal_type length: " . strlen($goalType));
                error_log("- priority length: " . strlen($priority));
                error_log("- save_method length: " . strlen($saveMethod));
                
                // Check if goal_type is actually in the enum
                if ($schemaResult && isset($schemaResult['Type'])) {
                    preg_match_all("/'([^']+)'/", $schemaResult['Type'], $matches);
                    $enumValues = $matches[1];
                    error_log("ENUM values from schema: " . json_encode($enumValues));
                    error_log("Goal type '" . $goalType . "' in ENUM? " . (in_array($goalType, $enumValues) ? 'YES' : 'NO'));
                    
                    // Check for similar values
                    foreach ($enumValues as $enumValue) {
                        if (levenshtein(strtolower($goalType), strtolower($enumValue)) <= 2) {
                            error_log("Similar enum value found: '$enumValue' (distance: " . levenshtein(strtolower($goalType), strtolower($enumValue)) . ")");
                        }
                    }
                }
            }
            
            error_log("=== END SQL ERROR DEBUG ===");
            throw new Exception("Database error: " . $error . " (Error #" . $errno . ")");
        }
        $goalId = $conn->insert_id;
        
        // Add initial deposit if provided
        if ($initialDeposit > 0) {
            $stmt = $conn->prepare("
                INSERT INTO personal_goal_contributions 
                (goal_id, user_id, amount, contribution_date, source, description) 
                VALUES (?, ?, ?, CURDATE(), 'manual', 'Initial deposit')
            ");
            $stmt->bind_param("iid", $goalId, $userId, $initialDeposit);
            $stmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Goal created successfully',
            'goal_id' => $goalId,
            'calculated_amount' => floatval($saveAmount),
            'budget_check' => [
                'allocated_savings' => $budgetAllocation['allocated_savings'],
                'new_total_goals' => $budgetAllocation['total_goal_amounts'] + floatval($saveAmount),
                'remaining_allocation' => $budgetAllocation['remaining_allocation'] - floatval($saveAmount)
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to create goal: ' . $e->getMessage());
    }
}

function editGoal($conn, $userId) {
    $goalId = $_POST['goal_id'] ?? 0;
    $goalName = trim($_POST['goal_name'] ?? '');
    $targetAmountRaw = $_POST['target_amount'] ?? 0;
    $targetAmount = sprintf('%.2f', $targetAmountRaw);
    $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $goalType = $_POST['goal_type'] ?? 'other';
    $priority = $_POST['priority'] ?? 'medium';
    $autoSaveEnabled = isset($_POST['auto_save_enabled']) ? (bool)$_POST['auto_save_enabled'] : false;
    $saveMethod = $_POST['save_method'] ?? 'manual';
    $savePercentageRaw = $_POST['save_percentage'] ?? 0;
    $savePercentage = sprintf('%.2f', $savePercentageRaw);
    $saveAmountRaw = $_POST['save_amount'] ?? 0;
    $saveAmount = sprintf('%.2f', $saveAmountRaw);
    $deductFromIncome = isset($_POST['deduct_from_income']) ? (bool)$_POST['deduct_from_income'] : false;
    
    if (!$goalId || empty($goalName) || floatval($targetAmount) <= 0) {
        throw new Exception('Goal ID, name and target amount are required');
    }
    
    // Verify goal belongs to user
    $stmt = $conn->prepare("SELECT id FROM personal_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Goal not found or access denied');
    }
    
    // Get budget allocation data
    $budgetAllocation = getBudgetAllocation($conn, $userId);
    $monthlySalary = $budgetAllocation['monthly_salary'];
    
    // If using percentage method, calculate the fixed amount
    if ($autoSaveEnabled && $saveMethod === 'percentage' && floatval($savePercentage) > 0) {
        if ($monthlySalary > 0) {
            $calculatedAmount = ($monthlySalary * floatval($savePercentage)) / 100;
            $saveAmount = sprintf('%.2f', $calculatedAmount);
        }
    }
    
    // Check if the edited goal exceeds budget allocation
    if ($autoSaveEnabled && floatval($saveAmount) > 0) {
        // Get current auto-save amount for this goal
        $stmt = $conn->prepare("SELECT save_amount FROM personal_goal_settings WHERE goal_id = ?");
        $stmt->bind_param("i", $goalId);
        $stmt->execute();
        $currentSettings = $stmt->get_result()->fetch_assoc();
        $currentAutoSaveAmount = $currentSettings ? floatval($currentSettings['save_amount']) : 0;
        
        // Calculate the difference
        $difference = floatval($saveAmount) - $currentAutoSaveAmount;
        
        if ($difference > 0) {
            // Check if the increase exceeds remaining allocation
            if ($difference > $budgetAllocation['remaining_allocation']) {
                $remaining = $budgetAllocation['remaining_allocation'];
                throw new Exception("Goal auto-save increase (₵{$difference}) exceeds remaining budget allocation (₵{$remaining}). Please reduce the amount or adjust your budget allocation.");
            }
        }
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if status column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM personal_goals LIKE 'status'");
        $stmt->execute();
        $statusExists = $stmt->get_result()->num_rows > 0;
        
        if ($statusExists) {
            // Update goal with status preservation
            $stmt = $conn->prepare("
                UPDATE personal_goals 
                SET goal_name = ?, target_amount = ?, target_date = ?, goal_type = ?, priority = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("sdsssii", $goalName, $targetAmount, $targetDate, $goalType, $priority, $goalId, $userId);
        } else {
            // Update goal without status
            $stmt = $conn->prepare("
                UPDATE personal_goals 
                SET goal_name = ?, target_amount = ?, target_date = ?, goal_type = ?, priority = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("sdsssii", $goalName, $targetAmount, $targetDate, $goalType, $priority, $goalId, $userId);
        }
        
        $stmt->execute();
        
        // Update or create goal settings
        $stmt = $conn->prepare("SELECT id FROM personal_goal_settings WHERE goal_id = ?");
        $stmt->bind_param("i", $goalId);
        $stmt->execute();
        $settingsExist = $stmt->get_result()->fetch_assoc();
        
        if ($settingsExist) {
            // Update existing settings
            $stmt = $conn->prepare("
                UPDATE personal_goal_settings 
                SET auto_save_enabled = ?, save_method = ?, save_percentage = ?, save_amount = ?, deduct_from_income = ?
                WHERE goal_id = ?
            ");
            $autoSaveInt = $autoSaveEnabled ? 1 : 0;
            $deductInt = $deductFromIncome ? 1 : 0;
            $stmt->bind_param("isddii", $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt, $goalId);
        } else if ($autoSaveEnabled || $saveMethod !== 'manual') {
            // Create new settings
            $stmt = $conn->prepare("
                INSERT INTO personal_goal_settings 
                (goal_id, auto_save_enabled, save_method, save_percentage, save_amount, deduct_from_income) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $autoSaveInt = $autoSaveEnabled ? 1 : 0;
            $deductInt = $deductFromIncome ? 1 : 0;
            $stmt->bind_param("iisddi", $goalId, $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt);
        }
        
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Goal updated successfully',
            'calculated_amount' => floatval($saveAmount),
            'budget_check' => [
                'allocated_savings' => $budgetAllocation['allocated_savings'],
                'total_goal_amounts' => $budgetAllocation['total_goal_amounts'] + (floatval($saveAmount) - ($currentSettings ? floatval($currentSettings['save_amount']) : 0)),
                'remaining_allocation' => $budgetAllocation['remaining_allocation'] - (floatval($saveAmount) - ($currentSettings ? floatval($currentSettings['save_amount']) : 0))
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to update goal: ' . $e->getMessage());
    }
}

function addContribution($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    $amountRaw = $_POST['amount'] ?? 0;
    $amount = sprintf('%.2f', $amountRaw);
    $source = 'manual'; // Default to manual since we removed the source field
    $description = trim($_POST['description'] ?? '');
    
    if (!$goalId || floatval($amount) <= 0) {
        throw new Exception('Goal ID and amount are required');
    }
    
    // Verify goal belongs to user and get current details
    $stmt = $conn->prepare("SELECT id, goal_name, current_amount, target_amount FROM personal_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();
    $goal = $stmt->get_result()->fetch_assoc();
    
    if (!$goal) {
        throw new Exception('Goal not found');
    }
    
    // Check if contribution would exceed the goal target
    $newAmount = $goal['current_amount'] + floatval($amount);
    $targetAmount = floatval($goal['target_amount']);
    
    if ($newAmount > $targetAmount) {
        $remaining = $targetAmount - $goal['current_amount'];
        if ($remaining <= 0) {
            throw new Exception('Goal has already been completed. No additional contributions needed.');
        } else {
            throw new Exception(sprintf('Contribution amount would exceed goal target. Maximum you can save is ₵%.2f to complete this goal.', $remaining));
        }
    }
    
    $conn->begin_transaction();
    
    try {
        // Add contribution
        $stmt = $conn->prepare("
            INSERT INTO personal_goal_contributions 
            (goal_id, user_id, amount, contribution_date, source, description) 
            VALUES (?, ?, ?, CURDATE(), ?, ?)
        ");
        $stmt->bind_param("iidss", $goalId, $userId, $amount, $source, $description);
        $stmt->execute();
        
        // Update goal current amount
        $isCompleted = $newAmount >= $goal['target_amount'] ? 1 : 0;
        
        $stmt = $conn->prepare("
            UPDATE personal_goals 
            SET current_amount = ?, is_completed = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->bind_param("dii", $newAmount, $isCompleted, $goalId);
        $stmt->execute();
        
        // NOTE: Removed incorrect expense tracking for savings contributions
        // Savings should NOT be treated as expenses in the budget system
        // They are already properly tracked in personal_goal_contributions table
        // Budget calculations should be: Available = Income - Expenses - Savings
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Contribution added successfully',
            'new_amount' => $newAmount,
            'is_completed' => (bool)$isCompleted,
            'goal_name' => $goal['goal_name']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to add contribution: ' . $e->getMessage());
    }
}

function updateAutoSave($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    $autoSaveEnabled = isset($_POST['auto_save_enabled']) ? (bool)$_POST['auto_save_enabled'] : false;
    $saveMethod = $_POST['save_method'] ?? 'manual';
    $savePercentageRaw = $_POST['save_percentage'] ?? 0;
    $savePercentage = sprintf('%.2f', $savePercentageRaw);
    $saveAmountRaw = $_POST['save_amount'] ?? 0;
    $saveAmount = sprintf('%.2f', $saveAmountRaw);
    $deductFromIncome = isset($_POST['deduct_from_income']) ? (bool)$_POST['deduct_from_income'] : false;
    
    if (!$goalId) {
        throw new Exception('Goal ID is required');
    }
    
    // Verify goal belongs to user
    $stmt = $conn->prepare("SELECT id FROM personal_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Goal not found');
    }
    
    // Update or insert goal settings
    $stmt = $conn->prepare("
        INSERT INTO personal_goal_settings 
        (goal_id, auto_save_enabled, save_method, save_percentage, save_amount, deduct_from_income) 
        VALUES (?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        auto_save_enabled = VALUES(auto_save_enabled),
        save_method = VALUES(save_method),
        save_percentage = VALUES(save_percentage),
        save_amount = VALUES(save_amount),
        deduct_from_income = VALUES(deduct_from_income),
        updated_at = CURRENT_TIMESTAMP
    ");
    $autoSaveInt = $autoSaveEnabled ? 1 : 0;
    $deductInt = $deductFromIncome ? 1 : 0;
    $stmt->bind_param("issddi", $goalId, $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto-save settings updated successfully'
    ]);
}

function deleteGoal($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    
    if (!$goalId) {
        throw new Exception('Goal ID is required');
    }
    
    // Verify goal belongs to user
    $stmt = $conn->prepare("SELECT goal_name FROM personal_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();
    $goal = $stmt->get_result()->fetch_assoc();
    
    if (!$goal) {
        throw new Exception('Goal not found');
    }
    
    $conn->begin_transaction();
    
    try {
        // Delete goal settings
        $stmt = $conn->prepare("DELETE FROM personal_goal_settings WHERE goal_id = ?");
        $stmt->bind_param("i", $goalId);
        $stmt->execute();
        
        // Delete contributions
        $stmt = $conn->prepare("DELETE FROM personal_goal_contributions WHERE goal_id = ?");
        $stmt->bind_param("i", $goalId);
        $stmt->execute();
        
        // Delete goal
        $stmt = $conn->prepare("DELETE FROM personal_goals WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $goalId, $userId);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Goal "' . $goal['goal_name'] . '" deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to delete goal: ' . $e->getMessage());
    }
}

function getMonthlyTarget($conn, $userId) {
    $data = getMonthlyTargetData($conn, $userId);
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

function pauseGoal($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    
    if (!$goalId) {
        throw new Exception('Goal ID is required');
    }
    
    // Check if status column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM personal_goals LIKE 'status'");
    $stmt->execute();
    $statusExists = $stmt->get_result()->num_rows > 0;
    
    if ($statusExists) {
        // Update goal status to paused and disable auto-save
        $stmt = $conn->prepare("
            UPDATE personal_goals 
            SET status = 'paused', auto_save_enabled = 0, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $goalId, $userId);
        $stmt->execute();
    } else {
        // Just disable auto-save if status column doesn't exist
        $stmt = $conn->prepare("
            UPDATE personal_goals 
            SET auto_save_enabled = 0, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $goalId, $userId);
        $stmt->execute();
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Goal paused successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Goal not found or already paused'
        ]);
    }
}

function resumeGoal($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    
    if (!$goalId) {
        throw new Exception('Goal ID is required');
    }
    
    // Update goal status to active
    $stmt = $conn->prepare("
        UPDATE personal_goals 
        SET status = 'active', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Goal resumed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Goal not found or already active'
        ]);
    }
}

function setGoalInactive($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    
    if (!$goalId) {
        throw new Exception('Goal ID is required');
    }
    
    // Update goal status to inactive
    $stmt = $conn->prepare("
        UPDATE personal_goals 
        SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Goal set to inactive successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Goal not found'
        ]);
    }
}

function updateGoal($conn, $userId) {
    $goalId = $_POST['goal_id'] ?? 0;
    $goalName = trim($_POST['goal_name'] ?? '');
    $targetAmountRaw = $_POST['target_amount'] ?? 0;
    $targetAmount = sprintf('%.2f', $targetAmountRaw);
    $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $goalType = $_POST['goal_type'] ?? 'other';
    $priority = $_POST['priority'] ?? 'medium';
    $autoSaveEnabled = isset($_POST['auto_save_enabled']) ? (bool)$_POST['auto_save_enabled'] : false;
    $saveMethod = $_POST['save_method'] ?? 'manual';
    $savePercentageRaw = $_POST['save_percentage'] ?? 0;
    $savePercentage = sprintf('%.2f', $savePercentageRaw);
    $saveAmountRaw = $_POST['save_amount'] ?? 0;
    $saveAmount = sprintf('%.2f', $saveAmountRaw);
    $deductFromIncome = isset($_POST['deduct_from_income']) ? (bool)$_POST['deduct_from_income'] : false;
    
    if (!$goalId || empty($goalName) || floatval($targetAmount) <= 0) {
        throw new Exception('Goal ID, name and target amount are required');
    }
    
    // Verify goal belongs to user
    $stmt = $conn->prepare("SELECT id FROM personal_goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Goal not found or access denied');
    }
    
    // Get budget allocation data
    $budgetAllocation = getBudgetAllocation($conn, $userId);
    $monthlySalary = $budgetAllocation['monthly_salary'];
    
    // If using percentage method, calculate the fixed amount
    if ($autoSaveEnabled && $saveMethod === 'percentage' && floatval($savePercentage) > 0) {
        if ($monthlySalary > 0) {
            $calculatedAmount = ($monthlySalary * floatval($savePercentage)) / 100;
            $saveAmount = sprintf('%.2f', $calculatedAmount);
        }
    }
    
    // Check if the edited goal exceeds budget allocation
    if ($autoSaveEnabled && floatval($saveAmount) > 0) {
        // Get current auto-save amount for this goal
        $stmt = $conn->prepare("SELECT save_amount FROM personal_goal_settings WHERE goal_id = ?");
        $stmt->bind_param("i", $goalId);
        $stmt->execute();
        $currentSettings = $stmt->get_result()->fetch_assoc();
        $currentAutoSaveAmount = $currentSettings ? floatval($currentSettings['save_amount']) : 0;
        
        // Calculate the difference
        $difference = floatval($saveAmount) - $currentAutoSaveAmount;
        
        if ($difference > 0) {
            // Check if the increase exceeds remaining allocation
            if ($difference > $budgetAllocation['remaining_allocation']) {
                $remaining = $budgetAllocation['remaining_allocation'];
                throw new Exception("Goal auto-save increase (₵{$difference}) exceeds remaining budget allocation (₵{$remaining}). Please reduce the amount or adjust your budget allocation.");
            }
        }
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if status column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM personal_goals LIKE 'status'");
        $stmt->execute();
        $statusExists = $stmt->get_result()->num_rows > 0;
        
        if ($statusExists) {
            // Update goal with status preservation
            $stmt = $conn->prepare("
                UPDATE personal_goals 
                SET goal_name = ?, target_amount = ?, target_date = ?, goal_type = ?, priority = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("sdsssii", $goalName, $targetAmount, $targetDate, $goalType, $priority, $goalId, $userId);
        } else {
            // Update goal without status
            $stmt = $conn->prepare("
                UPDATE personal_goals 
                SET goal_name = ?, target_amount = ?, target_date = ?, goal_type = ?, priority = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("sdsssii", $goalName, $targetAmount, $targetDate, $goalType, $priority, $goalId, $userId);
        }
        
        $stmt->execute();
        
        // Update or create goal settings
        $stmt = $conn->prepare("SELECT id FROM personal_goal_settings WHERE goal_id = ?");
        $stmt->bind_param("i", $goalId);
        $stmt->execute();
        $settingsExist = $stmt->get_result()->fetch_assoc();
        
        if ($settingsExist) {
            // Update existing settings
            $stmt = $conn->prepare("
                UPDATE personal_goal_settings 
                SET auto_save_enabled = ?, save_method = ?, save_percentage = ?, save_amount = ?, deduct_from_income = ?
                WHERE goal_id = ?
            ");
            $autoSaveInt = $autoSaveEnabled ? 1 : 0;
            $deductInt = $deductFromIncome ? 1 : 0;
            $stmt->bind_param("isddii", $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt, $goalId);
        } else if ($autoSaveEnabled || $saveMethod !== 'manual') {
            // Create new settings
            $stmt = $conn->prepare("
                INSERT INTO personal_goal_settings 
                (goal_id, auto_save_enabled, save_method, save_percentage, save_amount, deduct_from_income) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $autoSaveInt = $autoSaveEnabled ? 1 : 0;
            $deductInt = $deductFromIncome ? 1 : 0;
            $stmt->bind_param("iisddi", $goalId, $autoSaveInt, $saveMethod, $savePercentage, $saveAmount, $deductInt);
        }
        
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Goal updated successfully',
            'calculated_amount' => floatval($saveAmount),
            'budget_check' => [
                'allocated_savings' => $budgetAllocation['allocated_savings'],
                'total_goal_amounts' => $budgetAllocation['total_goal_amounts'] + (floatval($saveAmount) - ($currentSettings ? floatval($currentSettings['save_amount']) : 0)),
                'remaining_allocation' => $budgetAllocation['remaining_allocation'] - (floatval($saveAmount) - ($currentSettings ? floatval($currentSettings['save_amount']) : 0))
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to update goal: ' . $e->getMessage());
    }
}

function processSalaryAutoSave($conn, $userId) {
    // This function would be called when salary is confirmed
    // It processes auto-save for all active goals
    
    $monthlySalaryRaw = $_POST['salary_amount'] ?? 0;
    $monthlySalary = sprintf('%.2f', $monthlySalaryRaw);
    if (floatval($monthlySalary) <= 0) {
        throw new Exception('Salary amount is required');
    }
    
    // Get all active goals with auto-save enabled
    $stmt = $conn->prepare("
        SELECT 
            pg.id,
            pg.goal_name,
            pgs.save_method,
            pgs.save_percentage,
            pgs.save_amount,
            pgs.deduct_from_income
        FROM personal_goals pg
        JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
        WHERE pg.user_id = ? 
        AND pg.is_completed = 0 
        AND pgs.auto_save_enabled = 1
        AND pgs.deduct_from_income = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totalDeducted = 0;
    $processed = [];
    
    $conn->begin_transaction();
    
    try {
        while ($row = $result->fetch_assoc()) {
            $saveAmount = 0;
            
            if ($row['save_method'] === 'percentage') {
                $saveAmount = ($monthlySalary * $row['save_percentage']) / 100;
            } else if ($row['save_method'] === 'fixed') {
                $saveAmount = floatval($row['save_amount']);
            }
            
            if ($saveAmount > 0) {
                // Add contribution
                $stmt = $conn->prepare("
                    INSERT INTO personal_goal_contributions 
                    (goal_id, user_id, amount, contribution_date, source, description) 
                    VALUES (?, ?, ?, CURDATE(), 'auto_save', 'Auto-save from salary')
                ");
                $stmt->bind_param("iid", $row['id'], $userId, $saveAmount);
                $stmt->execute();
                
                // Update goal current amount
                $stmt = $conn->prepare("
                    UPDATE personal_goals 
                    SET current_amount = current_amount + ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->bind_param("di", $saveAmount, $row['id']);
                $stmt->execute();
                
                $totalDeducted += $saveAmount;
                $processed[] = [
                    'goal_name' => $row['goal_name'],
                    'amount' => $saveAmount
                ];
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Auto-save processed successfully',
            'total_deducted' => $totalDeducted,
            'remaining_salary' => $monthlySalary - $totalDeducted,
            'processed_goals' => $processed
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to process auto-save: ' . $e->getMessage());
    }
}

function getRecentActivity($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                'goal' as activity_type,
                CONCAT('Created goal: ', goal_name) as title,
                CONCAT('Target: $', FORMAT(target_amount, 2)) as description,
                0 as amount,
                created_at
            FROM personal_goals 
            WHERE user_id = ?
            
            UNION ALL
            
            SELECT 
                'contribution' as activity_type,
                CONCAT('Saved money for: ', pg.goal_name) as title,
                CONCAT('Source: ', IFNULL(pgc.source, 'manual')) as description,
                pgc.amount,
                pgc.contribution_date as created_at
            FROM personal_goal_contributions pgc
            JOIN personal_goals pg ON pgc.goal_id = pg.id
            WHERE pgc.user_id = ?
            
            ORDER BY created_at DESC
            LIMIT 20
        ");
        
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'activity_type' => $row['activity_type'],
                'title' => $row['title'],
                'description' => $row['description'],
                'amount' => floatval($row['amount']),
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $activities
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get recent activity: ' . $e->getMessage());
    }
}

function getSavingsOverview($conn, $userId) {
    try {
        // Clean any output buffer
        ob_start();
        ob_clean();
        
        // Check if tables exist
        $stmt = $conn->prepare("SHOW TABLES LIKE 'personal_goals'");
        $stmt->execute();
        $goalsTableExists = $stmt->get_result()->num_rows > 0;
        
        $stmt = $conn->prepare("SHOW TABLES LIKE 'personal_budget_allocation'");
        $stmt->execute();
        $budgetTableExists = $stmt->get_result()->num_rows > 0;
        
        if (!$goalsTableExists) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_savings' => 0,
                    'emergency_savings' => 0,
                    'goal_savings' => 0,
                    'monthly_contributions' => 0,
                    'monthly_change' => 0,
                    'change_direction' => 'neutral',
                    'savings_rate' => 0,
                    'savings_rate_change' => 0,
                    'rate_change_direction' => 'neutral',
                    'monthly_salary' => 0,
                    'total_goals' => 0,
                    'target_savings_percentage' => 20,
                    'target_savings_amount' => 0
                ]
            ]);
            return;
        }
        
        // Get total savings from all goals
        $stmt = $conn->prepare("
            SELECT 
                SUM(current_amount) as total_savings,
                COUNT(*) as total_goals,
                SUM(CASE WHEN goal_type = 'emergency_fund' THEN current_amount ELSE 0 END) as emergency_savings,
                SUM(CASE WHEN goal_type != 'emergency_fund' THEN current_amount ELSE 0 END) as goal_savings
            FROM personal_goals 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $totals = $stmt->get_result()->fetch_assoc();
        
        // Get current month contributions
        $stmt = $conn->prepare("
            SELECT 
                SUM(pgc.amount) as monthly_contributions
            FROM personal_goal_contributions pgc
            JOIN personal_goals pg ON pgc.goal_id = pg.id
            WHERE pgc.user_id = ? 
            AND DATE_FORMAT(pgc.contribution_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $monthlyData = $stmt->get_result()->fetch_assoc();
        
        // Get previous month contributions for comparison
        $stmt = $conn->prepare("
            SELECT 
                SUM(pgc.amount) as prev_monthly_contributions
            FROM personal_goal_contributions pgc
            JOIN personal_goals pg ON pgc.goal_id = pg.id
            WHERE pgc.user_id = ? 
            AND DATE_FORMAT(pgc.contribution_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $prevMonthData = $stmt->get_result()->fetch_assoc();
        
        // Get user's budget allocation for target savings percentage
        $targetSavingsPercentage = 20; // Default
        $targetSavingsAmount = 0;
        $monthlySalary = 0;
        
        if ($budgetTableExists) {
            $stmt = $conn->prepare("
                SELECT 
                    monthly_salary,
                    savings_percentage,
                    savings_amount
                FROM personal_budget_allocation 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $budgetData = $stmt->get_result()->fetch_assoc();
            
            if ($budgetData) {
                $monthlySalary = floatval($budgetData['monthly_salary']);
                $targetSavingsPercentage = floatval($budgetData['savings_percentage']);
                $targetSavingsAmount = floatval($budgetData['savings_amount']);
            }
        }
        
        // Fallback to salary table if no budget allocation
        if ($monthlySalary === 0) {
            $stmt = $conn->prepare("
                SELECT monthly_salary 
                FROM salaries 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $salaryData = $stmt->get_result()->fetch_assoc();
            $monthlySalary = floatval($salaryData['monthly_salary'] ?? 0);
            
            // Calculate target savings amount if we have salary but no budget allocation
            if ($monthlySalary > 0 && $targetSavingsAmount === 0) {
                $targetSavingsAmount = ($monthlySalary * $targetSavingsPercentage) / 100;
            }
        }
        
        $totalSavings = floatval($totals['total_savings'] ?? 0);
        $emergencySavings = floatval($totals['emergency_savings'] ?? 0);
        $goalSavings = floatval($totals['goal_savings'] ?? 0);
        $monthlyContributions = floatval($monthlyData['monthly_contributions'] ?? 0);
        $prevMonthlyContributions = floatval($prevMonthData['prev_monthly_contributions'] ?? 0);
        
        // Calculate savings rate
        $savingsRate = 0;
        if ($monthlySalary > 0 && $monthlyContributions > 0) {
            $savingsRate = ($monthlyContributions / $monthlySalary) * 100;
        }
        
        // Calculate month-over-month change
        $monthlyChange = $monthlyContributions - $prevMonthlyContributions;
        $changeDirection = $monthlyChange >= 0 ? 'positive' : 'negative';
        
        // Calculate previous month savings rate for comparison
        $prevSavingsRate = 0;
        if ($monthlySalary > 0 && $prevMonthlyContributions > 0) {
            $prevSavingsRate = ($prevMonthlyContributions / $monthlySalary) * 100;
        }
        $rateChange = $savingsRate - $prevSavingsRate;
        $rateChangeDirection = $rateChange >= 0 ? 'positive' : 'negative';
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_savings' => $totalSavings,
                'emergency_savings' => $emergencySavings,
                'goal_savings' => $goalSavings,
                'monthly_contributions' => $monthlyContributions,
                'monthly_change' => $monthlyChange,
                'change_direction' => $changeDirection,
                'savings_rate' => round($savingsRate, 1),
                'savings_rate_change' => round($rateChange, 1),
                'rate_change_direction' => $rateChangeDirection,
                'monthly_salary' => $monthlySalary,
                'total_goals' => intval($totals['total_goals'] ?? 0),
                'target_savings_percentage' => round($targetSavingsPercentage, 1),
                'target_savings_amount' => $targetSavingsAmount
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get savings overview: ' . $e->getMessage());
    }
}

/**
 * Get icon for goal type
 */
function getGoalTypeIcon($goalType) {
    $icons = [
        'emergency_fund' => '🚨',
        'vacation' => '🏖️',
        'car' => '🚗',
        'house' => '🏠',
        'education' => '🎓',
        'retirement' => '🏖️',
        'investment' => '📈',
        'debt_payoff' => '💳',
        'business' => '💼',
        'technology' => '💻',
        'health' => '🏥',
        'entertainment' => '🎬',
        'shopping' => '🛍️',
        'travel' => '✈️',
        'wedding' => '�',
        'other' => '🎯'
    ];
    return $icons[$goalType] ?? '🎯';
}

/**
 * Get color for goal type
 */
function getGoalTypeColor($goalType) {
    $colors = [
        'emergency_fund' => '#e74c3c',  // Red
        'vacation' => '#3498db',        // Blue
        'car' => '#2ecc71',            // Green
        'house' => '#f39c12',          // Orange
        'education' => '#9b59b6',      // Purple
        'retirement' => '#1abc9c',     // Turquoise
        'investment' => '#27ae60',     // Dark green
        'debt_payoff' => '#e67e22',    // Dark orange
        'business' => '#8e44ad',       // Dark purple
        'technology' => '#2980b9',     // Dark blue
        'health' => '#c0392b',         // Dark red
        'entertainment' => '#f1c40f',  // Yellow
        'shopping' => '#e91e63',       // Pink
        'travel' => '#00bcd4',         // Cyan
        'wedding' => '#ff69b4',        // Hot pink
        'other' => '#34495e'           // Dark gray
    ];
    return $colors[$goalType] ?? '#34495e';
}

/**
 * Get valid goal types directly from database schema
 */
function getValidGoalTypesFromSchema($conn) {
    try {
        $query = "SHOW COLUMNS FROM personal_goals LIKE 'goal_type'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['Type']) {
            preg_match_all("/'([^']+)'/", $result['Type'], $matches);
            return $matches[1];
        }
    } catch (Exception $e) {
        error_log("Error getting valid goal types: " . $e->getMessage());
    }
    
    // Fallback
    return ['emergency_fund', 'vacation', 'car', 'house', 'education', 'other'];
}

/**
 * Check if a column exists in a table
 */
function checkColumnExists($conn, $table, $column) {
    try {
        $query = "SHOW COLUMNS FROM `$table` LIKE '$column'";
        $result = $conn->query($query);
        return $result->num_rows > 0;
    } catch (Exception $e) {
        error_log("Error checking column existence: " . $e->getMessage());
        return false;
    }
}

?>