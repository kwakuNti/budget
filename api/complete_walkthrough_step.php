<?php
require_once '../config/connection.php';

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

$input = json_decode(file_get_contents('php://input'), true);
$step_name = $input['step_name'] ?? '';

if (empty($step_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Step name is required']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $conn->autocommit(false);
    
    // Get current progress
    $stmt = $conn->prepare("
        SELECT steps_completed, current_step 
        FROM user_walkthrough_progress 
        WHERE user_id = ? AND walkthrough_type = 'initial_setup'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    
    if (!$progress) {
        throw new Exception('Progress record not found');
    }
    
    $steps_completed = json_decode($progress['steps_completed'], true) ?: [];
    
    // Add current step to completed if not already there
    if (!in_array($step_name, $steps_completed)) {
        $steps_completed[] = $step_name;
    }
    
    // Get next step
    $stmt = $conn->prepare("
        SELECT step_name, page_url 
        FROM walkthrough_steps 
        WHERE walkthrough_type = 'initial_setup' 
        AND step_order > (
            SELECT step_order 
            FROM walkthrough_steps 
            WHERE step_name = ? AND walkthrough_type = 'initial_setup'
        )
        AND is_active = 1
        ORDER BY step_order ASC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $step_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $next_step = $result->fetch_assoc();
    
    $next_step_name = null;
    $redirect_url = null;
    $is_completed = false;
    
    if ($next_step) {
        $next_step_name = $next_step['step_name'];
        
        // Determine redirect URL based on next step
        $current_page = $_SERVER['HTTP_REFERER'] ?? '';
        $next_page_url = $next_step['page_url'];
        
        if (!str_contains($current_page, basename($next_page_url))) {
            $redirect_url = '/budget/' . $next_page_url;
        }
    } else {
        // No more steps - walkthrough completed
        $is_completed = true;
        $next_step_name = null;
    }
    
    // Update progress
    $stmt = $conn->prepare("
        UPDATE user_walkthrough_progress 
        SET 
            current_step = ?,
            steps_completed = ?,
            is_completed = ?,
            completed_at = ?
        WHERE user_id = ? AND walkthrough_type = 'initial_setup'
    ");
    
    $completed_at = $is_completed ? date('Y-m-d H:i:s') : null;
    $stmt->bind_param("sssis", 
        $next_step_name,
        json_encode($steps_completed),
        $is_completed ? 1 : 0,
        $completed_at,
        $user_id
    );
    $stmt->execute();
    
    $conn->commit();
    $conn->autocommit(true);
    
    echo json_encode([
        'success' => true,
        'next_step' => $next_step_name,
        'redirect_url' => $redirect_url,
        'is_completed' => $is_completed,
        'steps_completed' => $steps_completed
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    $conn->autocommit(true);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
