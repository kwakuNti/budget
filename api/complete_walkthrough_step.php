<?php
// Enable error reporting for debugging but don't display
error_reporting(E_ALL);
ini_set('display_errors', 0);
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
    $step_name = $input['step_name'] ?? '';

    if (empty($step_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Step name is required']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->autocommit(false);
    
    // Check if walkthrough_steps table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'walkthrough_steps'");
    if ($table_check->num_rows === 0) {
        throw new Exception('Walkthrough steps table not found. Please run database setup.');
    }
    
    // Check if user_walkthrough_progress table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'user_walkthrough_progress'");
    if ($table_check->num_rows === 0) {
        throw new Exception('User walkthrough progress table not found. Please run database setup.');
    }
    
    // Get current progress
    $stmt = $conn->prepare("
        SELECT steps_completed, current_step 
        FROM user_walkthrough_progress 
        WHERE user_id = ? AND walkthrough_type = 'initial_setup'
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare progress query: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    
    if (!$progress) {
        // Create initial progress record
        $stmt = $conn->prepare("
            INSERT INTO user_walkthrough_progress 
            (user_id, walkthrough_type, current_step, steps_completed, is_completed) 
            VALUES (?, 'initial_setup', 'setup_income', '[]', 0)
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $progress = [
            'steps_completed' => '[]',
            'current_step' => 'setup_income'
        ];
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
    
    if (!$stmt) {
        throw new Exception('Failed to prepare next step query: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $step_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $next_step = $result->fetch_assoc();
    
    $next_step_name = null;
    $redirect_url = null;
    $is_completed = false;
    
    if ($next_step) {
        $next_step_name = $next_step['step_name'];
        $redirect_url = $next_step['page_url']; // Use the page URL directly
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
    
    if (!$stmt) {
        throw new Exception('Failed to prepare update query: ' . $conn->error);
    }
    
    $completed_at = $is_completed ? date('Y-m-d H:i:s') : null;
    $stmt->bind_param("sssis", 
        $next_step_name,
        json_encode($steps_completed),
        $is_completed ? 1 : 0,
        $completed_at,
        $user_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update progress: ' . $stmt->error);
    }
    
    $conn->commit();
    $conn->autocommit(true);
    
    echo json_encode([
        'success' => true,
        'next_step' => $next_step_name,
        'redirect_url' => $redirect_url,
        'is_completed' => $is_completed,
        'steps_completed' => $steps_completed,
        'debug' => [
            'step_completed' => $step_name,
            'user_id' => $user_id,
            'progress_found' => !empty($progress)
        ]
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
            'file' => __FILE__,
            'line' => $e->getLine(),
            'step_name' => $step_name ?? 'not_set',
            'user_id' => $_SESSION['user_id'] ?? 'not_set'
        ]
    ]);
}
?>
