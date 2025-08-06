<?php
session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';
require_once '../includes/cycle_functions.php';

// Check if user is logged in and has family access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if user has permission to start cycles (admin/head only)
if (!canCloseCycle($conn, $_SESSION['user_id'], $_SESSION['family_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'You do not have permission to start new cycles'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || $input['action'] !== 'start_new_cycle') {
        throw new Exception('Invalid request');
    }
    
    $familyId = $_SESSION['family_id'];
    $currentMonth = date('n');
    $currentYear = date('Y');
    $currentMonthStr = date('Y-m');
    
    // Check if a cycle already exists for current month
    $stmt = $conn->prepare("
        SELECT id FROM monthly_cycles 
        WHERE family_id = ? AND cycle_month = ?
    ");
    $stmt->bind_param("is", $familyId, $currentMonthStr);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('A cycle already exists for the current month');
    }
    
    // Start the new cycle
    $success = startNewCycle($conn, $familyId, $currentYear, $currentMonth);
    
    if (!$success) {
        throw new Exception('Failed to create new cycle');
    }
    
    // Log the action
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at)
        VALUES (?, ?, 'cycle_manual_start', ?, NOW())
    ");
    $description = "Manually started new cycle for " . date('F Y');
    $stmt->bind_param("iis", $_SESSION['user_id'], $familyId, $description);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'New cycle started successfully for ' . date('F Y')
    ]);
    
} catch (Exception $e) {
    error_log("Error starting new cycle: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
