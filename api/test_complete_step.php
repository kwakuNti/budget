<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Simple test version
try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $step_name = $input['step_name'] ?? '';
    
    if (empty($step_name)) {
        echo json_encode(['success' => false, 'error' => 'No step name provided']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Step received successfully',
        'step_name' => $step_name,
        'user_id' => $_SESSION['user_id'],
        'is_completed' => false,
        'next_step' => null,
        'redirect_url' => null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
