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
        
        echo json_encode([
            'success' => true,
            'message' => 'Salary confirmed successfully',
            'data' => [
                'confirmed_amount' => floatval($salary['monthly_salary']),
                'confirmation_date' => $today,
                'next_pay_date' => $nextPayDate
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
