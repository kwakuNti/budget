<?php
// Suppress any PHP warnings/notices that might corrupt JSON output
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Mark walkthrough as completed and skipped
    $stmt = $conn->prepare("
        UPDATE user_walkthrough_progress 
        SET 
            is_completed = 1,
            completed_at = NOW(),
            current_step = NULL
        WHERE user_id = ? AND walkthrough_type = 'initial_setup'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // If no record exists, create one that's already completed
    if ($stmt->affected_rows === 0) {
        $stmt = $conn->prepare("
            INSERT INTO user_walkthrough_progress 
            (user_id, walkthrough_type, current_step, is_completed, completed_at) 
            VALUES (?, 'initial_setup', NULL, 1, NOW())
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Walkthrough skipped successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
