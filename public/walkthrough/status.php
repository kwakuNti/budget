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

$user_id = $_SESSION['user_id'];

try {
    // Handle POST requests for help guide
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $walkthrough_type = $input['walkthrough_type'] ?? 'initial_setup';
        $page_url = $input['page_url'] ?? '';
        
        if ($walkthrough_type === 'help_guide' && $page_url) {
            // Get first help step for this page
            $stmt = $conn->prepare("
                SELECT 
                    step_name,
                    step_order,
                    page_url,
                    target_element,
                    title,
                    content,
                    action_required,
                    can_skip
                FROM walkthrough_steps 
                WHERE walkthrough_type = 'help_guide' 
                AND page_url LIKE ?
                AND is_active = 1
                ORDER BY step_order ASC
                LIMIT 1
            ");
            $page_pattern = '%' . basename($page_url) . '%';
            $stmt->bind_param("s", $page_pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $step = $result->fetch_assoc();
            
            if ($step) {
                echo json_encode([
                    'success' => true,
                    'step' => $step
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'No help available for this page'
                ]);
            }
            exit;
        }
    }
    
    // Default GET request - check initial setup status
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
