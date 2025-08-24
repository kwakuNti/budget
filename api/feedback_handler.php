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
    // Get form data
    $user_id = $_SESSION['user_id'];
    $feedback_type = $_POST['feedback_type'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $page_url = $_POST['page_url'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $rating = !empty($_POST['rating']) ? (int)$_POST['rating'] : null;
    $browser_info = $_POST['browser_info'] ?? null;
    
    // Validate required fields
    if (empty($feedback_type) || empty($subject) || empty($message)) {
        throw new Exception('Please fill in all required fields.');
    }
    
    // Validate feedback type
    $valid_types = ['bug_report', 'feature_request', 'general', 'complaint', 'compliment'];
    if (!in_array($feedback_type, $valid_types)) {
        throw new Exception('Invalid feedback type.');
    }
    
    // Validate priority
    $valid_priorities = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($priority, $valid_priorities)) {
        $priority = 'medium';
    }
    
    // Validate rating if provided
    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        $rating = null;
    }
    
    // Clean URL if provided
    if (!empty($page_url) && !filter_var($page_url, FILTER_VALIDATE_URL)) {
        $page_url = null;
    }
    
    // Insert feedback into database
    $query = "INSERT INTO user_feedback (
        user_id, 
        feedback_type, 
        subject, 
        message, 
        page_url, 
        browser_info, 
        priority, 
        rating,
        status,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param(
        'issssssi',
        $user_id,
        $feedback_type,
        $subject,
        $message,
        $page_url,
        $browser_info,
        $priority,
        $rating
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save feedback: ' . $stmt->error);
    }
    
    $feedback_id = $conn->insert_id;
    
    // Log the feedback submission
    error_log("New feedback submitted - ID: $feedback_id, User: $user_id, Type: $feedback_type, Priority: $priority");
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Feedback submitted successfully',
        'feedback_id' => $feedback_id
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
