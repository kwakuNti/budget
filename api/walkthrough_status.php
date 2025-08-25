<?php
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Check user's walkthrough status
    $stmt = $conn->prepare("
        SELECT 
            current_step,
            steps_completed,
            is_completed,
            can_skip
        FROM user_walkthrough_progress 
        WHERE user_id = ? AND walkthrough_type = 'initial_setup'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    
    if (!$progress) {
        // First time user - create initial walkthrough record
        $stmt = $conn->prepare("
            INSERT INTO user_walkthrough_progress 
            (user_id, walkthrough_type, current_step, steps_completed) 
            VALUES (?, 'initial_setup', 'setup_income', '[]')
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $progress = [
            'current_step' => 'setup_income',
            'steps_completed' => [],
            'is_completed' => false,
            'can_skip' => false
        ];
    } else {
        // Parse JSON steps_completed
        $progress['steps_completed'] = json_decode($progress['steps_completed'], true) ?: [];
        $progress['is_completed'] = (bool)$progress['is_completed'];
        $progress['can_skip'] = (bool)$progress['can_skip'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $progress
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
