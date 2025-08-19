<?php
/**
 * Salary Confirmation API
 * Allows users to confirm salary receipt via "I've been paid" button
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests allowed'
    ]);
    exit;
}

try {
    // Get the current active salary
    $stmt = $conn->prepare("
        SELECT 
            id,
            monthly_salary,
            pay_frequency,
            next_pay_date
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
        exit;
    }
    
    // Check if salary was already confirmed for this month
    $currentMonth = date('Y-m');
    $stmt = $conn->prepare("
        SELECT id FROM personal_income 
        WHERE user_id = ? 
        AND source = 'Monthly Salary'
        AND income_type = 'salary'
        AND DATE_FORMAT(income_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $existingConfirmation = $stmt->get_result()->fetch_assoc();
    
    if ($existingConfirmation) {
        echo json_encode([
            'success' => false,
            'message' => 'Salary already confirmed for this month'
        ]);
        exit;
    }
    
    // Record the confirmed salary in personal_income table
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        INSERT INTO personal_income 
        (user_id, source, amount, income_date, income_type, description, is_recurring, recurring_frequency) 
        VALUES (?, 'Monthly Salary', ?, ?, 'salary', 'Salary confirmed via I\\'ve been paid button', 1, ?)
    ");
    $stmt->bind_param("isds", $userId, $salary['monthly_salary'], $today, $salary['pay_frequency']);
    
    if ($stmt->execute()) {
        // Update next pay date based on frequency
        $nextPayDate = calculateNextPayDate($salary['next_pay_date'], $salary['pay_frequency']);
        
        $updateStmt = $conn->prepare("
            UPDATE salaries 
            SET next_pay_date = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $updateStmt->bind_param("si", $nextPayDate, $salary['id']);
        $updateStmt->execute();
        
        // Process auto-save for goals if enabled
        $autoSaveResult = processAutoSave($conn, $userId, $salary['monthly_salary']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Salary confirmed successfully',
            'data' => [
                'confirmed_amount' => floatval($salary['monthly_salary']),
                'confirmation_date' => $today,
                'next_pay_date' => $nextPayDate,
                'auto_save' => $autoSaveResult
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to confirm salary'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error confirming salary: ' . $e->getMessage()
    ]);
}

/**
 * Process auto-save for goals when salary is confirmed
 */
function processAutoSave($conn, $userId, $salaryAmount) {
    try {
        // Get all active goals with auto-save enabled that should deduct from income
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
        
        while ($row = $result->fetch_assoc()) {
            $saveAmount = 0;
            
            if ($row['save_method'] === 'percentage') {
                $saveAmount = ($salaryAmount * $row['save_percentage']) / 100;
            } else if ($row['save_method'] === 'fixed') {
                $saveAmount = floatval($row['save_amount']);
            }
            
            if ($saveAmount > 0) {
                // Add contribution
                $stmt = $conn->prepare("
                    INSERT INTO personal_goal_contributions 
                    (goal_id, user_id, amount, contribution_date, source, description) 
                    VALUES (?, ?, ?, CURDATE(), 'auto_save', 'Auto-save from salary confirmation')
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
        
        // Record auto-save history
        if ($totalDeducted > 0) {
            $stmt = $conn->prepare("
                INSERT INTO personal_auto_save_history 
                (user_id, salary_date, salary_amount, total_auto_saved, remaining_after_saves, goals_processed) 
                VALUES (?, CURDATE(), ?, ?, ?, ?)
            ");
            $goalsProcessedJson = json_encode($processed);
            $remainingAfterSaves = $salaryAmount - $totalDeducted;
            $stmt->bind_param("iddds", $userId, $salaryAmount, $totalDeducted, $remainingAfterSaves, $goalsProcessedJson);
            $stmt->execute();
        }
        
        $conn->commit();
        
        return [
            'enabled' => true,
            'total_deducted' => $totalDeducted,
            'remaining_salary' => $salaryAmount - $totalDeducted,
            'processed_goals' => $processed
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'enabled' => false,
            'error' => $e->getMessage()
        ];
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
        case 'monthly':
            $date->add(new DateInterval('P1M'));
            break;
        case 'quarterly':
            $date->add(new DateInterval('P3M'));
            break;
        case 'yearly':
            $date->add(new DateInterval('P1Y'));
            break;
        default:
            $date->add(new DateInterval('P1M')); // Default to monthly
    }
    
    return $date->format('Y-m-d');
}
?>
