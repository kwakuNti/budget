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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $feedback_id = $input['feedback_id'] ?? null;
    $status = $input['status'] ?? null;
    $admin_response = $input['admin_response'] ?? null;
    
    if (!$feedback_id || !$status) {
        throw new Exception('Feedback ID and status are required');
    }
    
    // Validate status
    $valid_statuses = ['new', 'in_progress', 'resolved', 'closed'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status provided');
    }
    
    // Update feedback status
    $query = "UPDATE user_feedback SET 
        status = ?, 
        admin_user_id = ?, 
        admin_response = ?,
        resolved_at = CASE WHEN ? = 'resolved' THEN NOW() ELSE resolved_at END,
        updated_at = NOW()
    WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $admin_user_id = $_SESSION['user_id'];
    $stmt->bind_param('sissi', $status, $admin_user_id, $admin_response, $status, $feedback_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update feedback: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Feedback not found or no changes made');
    }
    
    // Log the status update
    error_log("Feedback status updated - ID: $feedback_id, Status: $status, Admin: $admin_user_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Feedback status updated successfully'
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
