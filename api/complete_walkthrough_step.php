<?php
// Temporarily enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

// Set JSON header early
header('Content-Type: application/json');

try {
    // Check database connection
    require_once '../config/connection.php';
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

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
    
    // Accept both 'step' and 'step_name' for flexibility
    $step_name = $input['step'] ?? $input['step_name'] ?? '';
    $walkthrough_type = $input['type'] ?? 'initial_setup';

    if (empty($step_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Step name is required']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->autocommit(false);
    
    // Get current progress or create if doesn't exist
    $stmt = $conn->prepare("
        SELECT id, steps_completed, current_step, is_completed
        FROM user_walkthrough_progress 
        WHERE user_id = ? AND walkthrough_type = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare progress query: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $user_id, $walkthrough_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    $stmt->close();
    
    if (!$progress) {
        // Create initial progress record
        $stmt = $conn->prepare("
            INSERT INTO user_walkthrough_progress 
            (user_id, walkthrough_type, current_step, steps_completed, is_completed) 
            VALUES (?, ?, ?, ?, 0)
        ");
        $initial_steps = json_encode([$step_name]);
        $stmt->bind_param("isss", $user_id, $walkthrough_type, $step_name, $initial_steps);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create progress record: ' . $stmt->error);
        }
        $stmt->close();
        
        $steps_completed = [$step_name];
        $current_step = $step_name;
    } else {
        // Update existing progress
        $steps_completed = json_decode($progress['steps_completed'], true) ?: [];
        
        // Add current step to completed if not already there
        if (!in_array($step_name, $steps_completed)) {
            $steps_completed[] = $step_name;
        }
        
        $current_step = $step_name;
    }
    
    // Get next step from walkthrough_steps table
    $stmt = $conn->prepare("
        SELECT step_name, page_url 
        FROM walkthrough_steps 
        WHERE walkthrough_type = ? 
        AND step_order > (
            SELECT step_order 
            FROM walkthrough_steps 
            WHERE step_name = ? AND walkthrough_type = ?
        )
        AND is_active = 1
        ORDER BY step_order ASC 
        LIMIT 1
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare next step query: ' . $conn->error);
    }
    
    $stmt->bind_param("sss", $walkthrough_type, $step_name, $walkthrough_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $next_step = $result->fetch_assoc();
    $stmt->close();
    
    $next_step_name = null;
    $redirect_url = null;
    $is_completed = false;
    
    if ($next_step) {
        $next_step_name = $next_step['step_name'];
        $redirect_url = $next_step['page_url'];
        $current_step = $next_step_name; // Update current step to next step
    } else {
        // No more steps - walkthrough completed
        $is_completed = true;
    }
    
    // Update progress record
    $stmt = $conn->prepare("
        UPDATE user_walkthrough_progress 
        SET 
            current_step = ?,
            steps_completed = ?,
            is_completed = ?,
            completed_at = ?,
            last_shown_at = CURRENT_TIMESTAMP
        WHERE user_id = ? AND walkthrough_type = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare update query: ' . $conn->error);
    }
    
    $completed_at = $is_completed ? date('Y-m-d H:i:s') : null;
    $is_completed_int = $is_completed ? 1 : 0;
    $steps_completed_json = json_encode($steps_completed);
    
    $stmt->bind_param("ssisss", 
        $current_step,
        $steps_completed_json,
        $is_completed_int,
        $completed_at,
        $user_id,
        $walkthrough_type
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update progress: ' . $stmt->error);
    }
    $stmt->close();
    
    $conn->commit();
    $conn->autocommit(true);
    
    echo json_encode([
        'success' => true,
        'completed_step' => $step_name,
        'next_step' => $next_step_name,
        'redirect_url' => $redirect_url,
        'is_completed' => $is_completed,
        'steps_completed' => $steps_completed,
        'walkthrough_type' => $walkthrough_type
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->autocommit(true);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine(),
            'step_name' => $step_name ?? 'not_set',
            'user_id' => $_SESSION['user_id'] ?? 'not_set'
        ]
    ]);
}
?>
