<?php
session_start();
header('Content-Type: application/json');

try {
    require_once '../../config/connection.php';
    
    $checks = [];
    
    // Check if tables exist
    $tables = ['user_walkthrough_progress', 'walkthrough_steps'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $checks[$table] = [
            'exists' => $result && $result->num_rows > 0,
            'count' => 0
        ];
        
        if ($checks[$table]['exists']) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $checks[$table]['count'] = $count_result->fetch_assoc()['count'];
        }
    }
    
    // Check for walkthrough steps specifically
    if ($checks['walkthrough_steps']['exists']) {
        $result = $conn->query("SELECT * FROM walkthrough_steps WHERE walkthrough_type = 'initial_setup' ORDER BY step_order");
        $steps = [];
        while ($row = $result->fetch_assoc()) {
            $steps[] = [
                'step_name' => $row['step_name'],
                'step_order' => $row['step_order'],
                'title' => $row['title'],
                'is_active' => $row['is_active']
            ];
        }
        $checks['walkthrough_steps']['initial_setup_steps'] = $steps;
    }
    
    // Check user progress if logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $result = $conn->query("SELECT * FROM user_walkthrough_progress WHERE user_id = $user_id");
        $checks['user_progress'] = [];
        while ($row = $result->fetch_assoc()) {
            $checks['user_progress'][] = $row;
        }
    }
    
    $all_good = $checks['user_walkthrough_progress']['exists'] && 
                $checks['walkthrough_steps']['exists'] && 
                $checks['walkthrough_steps']['count'] > 0;
    
    echo json_encode([
        'success' => $all_good,
        'checks' => $checks,
        'user_id' => $_SESSION['user_id'] ?? 'Not logged in',
        'recommendations' => $all_good ? [] : [
            !$checks['user_walkthrough_progress']['exists'] ? 'Create user_walkthrough_progress table' : null,
            !$checks['walkthrough_steps']['exists'] ? 'Create walkthrough_steps table' : null,
            $checks['walkthrough_steps']['count'] == 0 ? 'Insert walkthrough steps data' : null
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>