<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing walkthrough API isolated...\n";

session_start();
$_SESSION['user_id'] = 2; // Set test user

require_once '../config/connection.php';

$user_id = 2;
$walkthrough_type = 'initial_setup';
$step_name = 'setup_income';
$current_step = 'setup_income';
$steps_completed = ['setup_income'];
$is_completed = false;

try {
    // Test the problematic update query
    $stmt = $conn->prepare("
        UPDATE user_walkthrough_progress 
        SET 
            current_step = ?,
            steps_completed = ?,
            is_completed = ?,
            completed_at = ?,
            last_shown_at = CURRENT_TIMESTAMP
        WHERE user_id = ? AND walkthrough_type = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare update query: ' . $conn->error);
    }
    
    $completed_at = $is_completed ? date('Y-m-d H:i:s') : null;
    $is_completed_int = $is_completed ? 1 : 0;
    $steps_completed_json = json_encode($steps_completed);
    
    echo "Variables to bind:\n";
    echo "current_step: " . var_export($current_step, true) . "\n";
    echo "steps_completed_json: " . var_export($steps_completed_json, true) . "\n";
    echo "is_completed_int: " . var_export($is_completed_int, true) . "\n";
    echo "completed_at: " . var_export($completed_at, true) . "\n";
    echo "user_id: " . var_export($user_id, true) . "\n";
    echo "walkthrough_type: " . var_export($walkthrough_type, true) . "\n";
    
    $result = $stmt->bind_param("ssisss", 
        $current_step,
        $steps_completed_json,
        $is_completed_int,
        $completed_at,
        $user_id,
        $walkthrough_type
    );
    
    if (!$result) {
        throw new Exception('Failed to bind parameters: ' . $stmt->error);
    }
    
    echo "✅ Bind successful!\n";
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute: ' . $stmt->error);
    }
    
    echo "✅ Execute successful!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
