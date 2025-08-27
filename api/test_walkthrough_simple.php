<?php
session_start();

// Simulate user session for testing
$_SESSION['user_id'] = 2; // Test user ID

require_once '../config/connection.php';

header('Content-Type: application/json');

echo json_encode([
    'test' => 'walkthrough_api_test',
    'session_user_id' => $_SESSION['user_id'] ?? null,
    'database_connected' => isset($conn) && !$conn->connect_error
]);

// Test the complete step functionality
try {
    $step_name = 'setup_income';
    $walkthrough_type = 'initial_setup';
    $user_id = $_SESSION['user_id'];
    
    // Simple test - just check if we can query the tables
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_walkthrough_progress WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    echo "\n" . json_encode([
        'progress_records_for_user' => $count,
        'test_step' => $step_name,
        'test_type' => $walkthrough_type
    ]);
    
} catch (Exception $e) {
    echo "\n" . json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
