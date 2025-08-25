<?php
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
    // Delete the user's walkthrough progress to reset it
    $stmt = $conn->prepare("DELETE FROM user_walkthrough_progress WHERE user_id = ? AND walkthrough_type = 'initial_setup'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Walkthrough reset successfully. User will see walkthrough on next page load.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
