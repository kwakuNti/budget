<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

try {
    // Check if tables exist
    $tables_to_check = ['walkthrough_steps', 'user_walkthrough_progress'];
    $results = [];
    
    foreach ($tables_to_check as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $result->num_rows > 0;
        $results[$table] = $exists;
        
        if ($exists) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            $results[$table . '_count'] = $count;
        }
    }
    
    // Check user session
    $results['user_logged_in'] = isset($_SESSION['user_id']);
    $results['user_id'] = $_SESSION['user_id'] ?? null;
    
    // Check if user has walkthrough progress
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT * FROM user_walkthrough_progress WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $progress = $stmt->get_result()->fetch_assoc();
        $results['user_progress'] = $progress;
    }
    
    echo json_encode([
        'success' => true,
        'database_status' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
