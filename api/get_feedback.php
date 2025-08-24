<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $feedback_id = $_GET['id'] ?? null;
    
    if (!$feedback_id) {
        throw new Exception('Feedback ID is required');
    }
    
    // Get feedback details with user information
    $query = "SELECT 
        uf.*,
        u.first_name,
        u.last_name,
        u.email
    FROM user_feedback uf
    JOIN users u ON uf.user_id = u.id
    WHERE uf.id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $feedback_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch feedback: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $feedback = $result->fetch_assoc();
    
    if (!$feedback) {
        throw new Exception('Feedback not found');
    }
    
    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
