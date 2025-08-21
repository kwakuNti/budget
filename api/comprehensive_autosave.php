<?php
/**
 * Comprehensive Auto-Save Configuration API
 * Handles all auto-save configuration, goal management, and execution
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

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_autosave_config':
            getAutoSaveConfig($conn, $userId);
            break;
            
        case 'update_autosave_config':
            updateAutoSaveConfig($conn, $userId);
            break;
            
        case 'get_goal_progress':
            getGoalProgress($conn, $userId);
            break;
            
        case 'update_goal_status':
            updateGoalStatus($conn, $userId);
            break;
            
        case 'get_autosave_history':
            getAutoSaveHistory($conn, $userId);
            break;
            
        case 'process_autosave':
            processAutoSave($conn, $userId);
            break;
            
        case 'get_allocation_rules':
            getAllocationRules($conn, $userId);
            break;
            
        case 'update_allocation_rules':
            updateAllocationRules($conn, $userId);
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
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Get auto-save configuration for user
 */
function getAutoSaveConfig($conn, $userId) {
    // Get global auto-save configuration
    $stmt = $conn->prepare("
        SELECT * FROM personal_goal_autosave 
        WHERE user_id = ? AND goal_id IS NULL
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $globalConfig = $stmt->get_result()->fetch_assoc();
    
    // Get per-goal auto-save configurations
    $stmt = $conn->prepare("
        SELECT pga.*, pg.title as goal_title, pg.target_amount, pg.current_amount, pg.status
        FROM personal_goal_autosave pga
        JOIN personal_goals pg ON pga.goal_id = pg.id
        WHERE pga.user_id = ? AND pga.goal_id IS NOT NULL
        ORDER BY pg.priority ASC, pg.created_at ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goalConfigs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get user's active goals
    $stmt = $conn->prepare("
        SELECT id, title, target_amount, current_amount, status, priority,
               CASE 
                   WHEN target_amount > 0 THEN (current_amount / target_amount) * 100
                   ELSE 0 
               END as progress_percentage
        FROM personal_goals 
        WHERE user_id = ? AND status IN ('active', 'paused')
        ORDER BY priority ASC, created_at ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'global_config' => $globalConfig,
            'goal_configs' => $goalConfigs,
            'goals' => $goals
        ]
    ]);
}

/**
 * Update auto-save configuration
 */
function updateAutoSaveConfig($conn, $userId) {
    $goalId = $_POST['goal_id'] ?? null; // NULL for global, specific ID for per-goal
    $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;
    $triggerSalary = isset($_POST['trigger_salary']) ? (bool)$_POST['trigger_salary'] : true;
    $triggerAdditional = isset($_POST['trigger_additional_income']) ? (bool)$_POST['trigger_additional_income'] : false;
    $triggerSchedule = isset($_POST['trigger_schedule']) ? (bool)$_POST['trigger_schedule'] : false;
    $scheduleFrequency = $_POST['schedule_frequency'] ?? 'monthly';
    $scheduleDay = intval($_POST['schedule_day'] ?? 1);
    $saveType = $_POST['save_type'] ?? 'percentage';
    $savePercentage = floatval($_POST['save_percentage'] ?? 10.0);
    $saveAmount = floatval($_POST['save_amount'] ?? 0.0);
    $allocationMethod = $_POST['allocation_method'] ?? 'priority_based';
    $maxPerGoal = floatval($_POST['max_per_goal'] ?? 0.0);
    $minIncomeThreshold = floatval($_POST['min_income_threshold'] ?? 0.0);
    $preserveEmergencyAmount = floatval($_POST['preserve_emergency_amount'] ?? 500.0);
    
    $stmt = $conn->prepare("
        INSERT INTO personal_goal_autosave (
            user_id, goal_id, enabled, trigger_salary, trigger_additional_income, 
            trigger_schedule, schedule_frequency, schedule_day, save_type, 
            save_percentage, save_amount, allocation_method, max_per_goal,
            min_income_threshold, preserve_emergency_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            enabled = VALUES(enabled),
            trigger_salary = VALUES(trigger_salary),
            trigger_additional_income = VALUES(trigger_additional_income),
            trigger_schedule = VALUES(trigger_schedule),
            schedule_frequency = VALUES(schedule_frequency),
            schedule_day = VALUES(schedule_day),
            save_type = VALUES(save_type),
            save_percentage = VALUES(save_percentage),
            save_amount = VALUES(save_amount),
            allocation_method = VALUES(allocation_method),
            max_per_goal = VALUES(max_per_goal),
            min_income_threshold = VALUES(min_income_threshold),
            preserve_emergency_amount = VALUES(preserve_emergency_amount),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->bind_param("iiiiiisiidddidd", 
        $userId, $goalId, $enabled, $triggerSalary, $triggerAdditional,
        $triggerSchedule, $scheduleFrequency, $scheduleDay, $saveType,
        $savePercentage, $saveAmount, $allocationMethod, $maxPerGoal,
        $minIncomeThreshold, $preserveEmergencyAmount
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $goalId ? 'Goal auto-save configuration updated' : 'Global auto-save configuration updated'
        ]);
    } else {
        throw new Exception('Failed to update auto-save configuration');
    }
}

/**
 * Get goal progress for all user goals
 */
function getGoalProgress($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            pg.id,
            pg.title,
            pg.target_amount,
            pg.current_amount,
            pg.status,
            pg.priority,
            CASE 
                WHEN pg.target_amount > 0 THEN (pg.current_amount / pg.target_amount) * 100
                ELSE 0 
            END as progress_percentage,
            COALESCE(pga.enabled, FALSE) as autosave_enabled
        FROM personal_goals pg
        LEFT JOIN personal_goal_autosave pga ON pg.id = pga.goal_id AND pga.user_id = pg.user_id
        WHERE pg.user_id = ?
        ORDER BY pg.priority ASC, pg.created_at ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'goals' => $goals
    ]);
}

/**
 * Update goal status (pause/resume/inactive)
 */
function updateGoalStatus($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if (!$goalId || !in_array($status, ['active', 'paused', 'inactive', 'completed'])) {
        throw new Exception('Invalid goal ID or status');
    }
    
    $stmt = $conn->prepare("
        UPDATE personal_goals 
        SET status = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("sii", $status, $goalId, $userId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Goal status updated to $status"
        ]);
    } else {
        throw new Exception('Goal not found or no changes made');
    }
}

/**
 * Get auto-save execution history
 */
function getAutoSaveHistory($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            execution_date,
            trigger_type,
            trigger_amount,
            total_saved,
            goals_affected,
            breakdown,
            remaining_balance,
            emergency_preserved,
            notes
        FROM personal_autosave_history
        WHERE user_id = ?
        ORDER BY execution_date DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Decode JSON breakdown
    foreach ($history as &$record) {
        if ($record['breakdown']) {
            $record['breakdown'] = json_decode($record['breakdown'], true);
        }
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
}

/**
 * Process auto-save based on configuration
 */
function processAutoSave($conn, $userId) {
    $triggerType = $_POST['trigger_type'] ?? 'manual';
    $triggerAmount = floatval($_POST['trigger_amount'] ?? 0);
    
    // Get global auto-save configuration
    $stmt = $conn->prepare("
        SELECT * FROM personal_goal_autosave 
        WHERE user_id = ? AND goal_id IS NULL AND enabled = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $globalConfig = $stmt->get_result()->fetch_assoc();
    
    if (!$globalConfig) {
        echo json_encode([
            'success' => false,
            'message' => 'Auto-save is not enabled or configured'
        ]);
        return;
    }
    
    // Check if this trigger type is enabled
    $triggerEnabled = false;
    switch ($triggerType) {
        case 'salary':
            $triggerEnabled = $globalConfig['trigger_salary'];
            break;
        case 'additional_income':
            $triggerEnabled = $globalConfig['trigger_additional_income'];
            break;
        case 'scheduled':
            $triggerEnabled = $globalConfig['trigger_schedule'];
            break;
        case 'manual':
            $triggerEnabled = true;
            break;
    }
    
    if (!$triggerEnabled) {
        echo json_encode([
            'success' => false,
            'message' => "Auto-save is not enabled for $triggerType"
        ]);
        return;
    }
    
    // Check minimum income threshold
    if ($triggerAmount < $globalConfig['min_income_threshold']) {
        echo json_encode([
            'success' => false,
            'message' => "Income amount is below minimum threshold of ₵{$globalConfig['min_income_threshold']}"
        ]);
        return;
    }
    
    // Calculate save amount
    $saveAmount = 0;
    if ($globalConfig['save_type'] === 'percentage') {
        $saveAmount = ($triggerAmount * $globalConfig['save_percentage']) / 100;
    } else {
        $saveAmount = $globalConfig['save_amount'];
    }
    
    // Get active goals
    $stmt = $conn->prepare("
        SELECT id, title, target_amount, current_amount, priority
        FROM personal_goals 
        WHERE user_id = ? AND status = 'active'
        ORDER BY priority ASC, created_at ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($goals)) {
        echo json_encode([
            'success' => false,
            'message' => 'No active goals found for auto-save'
        ]);
        return;
    }
    
    // Allocate savings based on method
    $allocations = allocateSavings($saveAmount, $goals, $globalConfig);
    
    // Execute the savings
    $totalSaved = 0;
    $breakdown = [];
    
    $conn->begin_transaction();
    
    try {
        foreach ($allocations as $allocation) {
            if ($allocation['amount'] > 0) {
                // Add contribution to goal
                $stmt = $conn->prepare("
                    INSERT INTO personal_goal_contributions 
                    (goal_id, user_id, amount, contribution_date, source, description)
                    VALUES (?, ?, ?, CURDATE(), 'auto_save', ?)
                ");
                $description = "Auto-save from $triggerType (₵$triggerAmount)";
                $stmt->bind_param("iids", $allocation['goal_id'], $userId, $allocation['amount'], $description);
                $stmt->execute();
                
                // Update goal current amount
                $stmt = $conn->prepare("
                    UPDATE personal_goals 
                    SET current_amount = current_amount + ?
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->bind_param("dii", $allocation['amount'], $allocation['goal_id'], $userId);
                $stmt->execute();
                
                $totalSaved += $allocation['amount'];
                $breakdown[] = $allocation;
            }
        }
        
        // Record auto-save history
        $stmt = $conn->prepare("
            INSERT INTO personal_autosave_history 
            (user_id, execution_date, trigger_type, trigger_amount, total_saved, 
             goals_affected, breakdown, remaining_balance, emergency_preserved)
            VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)
        ");
        $goalsAffected = count($breakdown);
        $breakdownJson = json_encode($breakdown);
        $remainingBalance = $triggerAmount - $totalSaved;
        $emergencyPreserved = $globalConfig['preserve_emergency_amount'];
        
        $stmt->bind_param("isdiisdd", 
            $userId, $triggerType, $triggerAmount, $totalSaved,
            $goalsAffected, $breakdownJson, $remainingBalance, $emergencyPreserved
        );
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Auto-save completed successfully",
            'data' => [
                'total_saved' => $totalSaved,
                'goals_affected' => $goalsAffected,
                'breakdown' => $breakdown,
                'remaining_balance' => $remainingBalance
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Allocate savings among goals based on allocation method
 */
function allocateSavings($totalAmount, $goals, $config) {
    $allocations = [];
    $method = $config['allocation_method'];
    $maxPerGoal = $config['max_per_goal'];
    
    switch ($method) {
        case 'equal_split':
            $amountPerGoal = $totalAmount / count($goals);
            if ($maxPerGoal > 0) {
                $amountPerGoal = min($amountPerGoal, $maxPerGoal);
            }
            
            foreach ($goals as $goal) {
                $allocations[] = [
                    'goal_id' => $goal['id'],
                    'goal_title' => $goal['title'],
                    'amount' => $amountPerGoal
                ];
            }
            break;
            
        case 'priority_based':
            $remainingAmount = $totalAmount;
            
            foreach ($goals as $goal) {
                if ($remainingAmount <= 0) break;
                
                $amountForGoal = min($remainingAmount, $maxPerGoal > 0 ? $maxPerGoal : $remainingAmount);
                
                // Check if goal is already complete
                if ($goal['current_amount'] >= $goal['target_amount'] && $goal['target_amount'] > 0) {
                    continue;
                }
                
                $allocations[] = [
                    'goal_id' => $goal['id'],
                    'goal_title' => $goal['title'],
                    'amount' => $amountForGoal
                ];
                
                $remainingAmount -= $amountForGoal;
            }
            break;
            
        case 'percentage_based':
            // This would use allocation rules from personal_goal_allocation_rules
            // For now, fall back to equal split
            $amountPerGoal = $totalAmount / count($goals);
            if ($maxPerGoal > 0) {
                $amountPerGoal = min($amountPerGoal, $maxPerGoal);
            }
            
            foreach ($goals as $goal) {
                $allocations[] = [
                    'goal_id' => $goal['id'],
                    'goal_title' => $goal['title'],
                    'amount' => $amountPerGoal
                ];
            }
            break;
    }
    
    return $allocations;
}

/**
 * Get allocation rules for user
 */
function getAllocationRules($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            pgar.*,
            pg.title as goal_title,
            pg.target_amount,
            pg.current_amount
        FROM personal_goal_allocation_rules pgar
        JOIN personal_goals pg ON pgar.goal_id = pg.id
        WHERE pgar.user_id = ? AND pgar.active = 1
        ORDER BY pgar.priority_order ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $rules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'rules' => $rules
    ]);
}

/**
 * Update allocation rules
 */
function updateAllocationRules($conn, $userId) {
    $goalId = intval($_POST['goal_id'] ?? 0);
    $priorityOrder = intval($_POST['priority_order'] ?? 1);
    $allocationPercentage = floatval($_POST['allocation_percentage'] ?? 0);
    $minAllocation = floatval($_POST['min_allocation'] ?? 0);
    $maxAllocation = floatval($_POST['max_allocation'] ?? 0);
    $onlyWhenTargetBelow = floatval($_POST['only_when_target_below'] ?? 0);
    $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;
    
    if (!$goalId) {
        throw new Exception('Goal ID is required');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO personal_goal_allocation_rules 
        (user_id, goal_id, priority_order, allocation_percentage, min_allocation, 
         max_allocation, only_when_target_below, active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            priority_order = VALUES(priority_order),
            allocation_percentage = VALUES(allocation_percentage),
            min_allocation = VALUES(min_allocation),
            max_allocation = VALUES(max_allocation),
            only_when_target_below = VALUES(only_when_target_below),
            active = VALUES(active),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->bind_param("iiiddddi", 
        $userId, $goalId, $priorityOrder, $allocationPercentage, 
        $minAllocation, $maxAllocation, $onlyWhenTargetBelow, $active
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Allocation rules updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update allocation rules');
    }
}
?>
