<?php
// Debug version - let's catch ALL errors and see what's happening
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Test 1: Session
    session_start();
    
    // Test 2: Database connection
    $connection_path = '../../config/connection.php';
    if (!file_exists($connection_path)) {
        throw new Exception("Connection file not found at: $connection_path");
    }
    
    require_once $connection_path;
    
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection variable \$conn is not set");
    }
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Test 3: User authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated - session user_id not set");
    }
    
    // Test 4: Request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // If it's a GET request, show debug info instead of error
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'This endpoint requires POST method',
                'debug_info' => [
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'session_user_id' => $_SESSION['user_id'] ?? 'Not set',
                    'connection_file_exists' => file_exists($connection_path),
                    'database_connected' => isset($conn) && !$conn->connect_error,
                    'instructions' => 'Use the walkthrough JavaScript to test this endpoint properly'
                ]
            ]);
            exit;
        }
        throw new Exception("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    }
    
    // Test 5: Input data
    $raw_input = file_get_contents('php://input');
    if (empty($raw_input)) {
        throw new Exception("No input data received");
    }
    
    $input = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg() . ". Raw input: " . $raw_input);
    }
    
    $step_name = $input['step_name'] ?? '';
    if (empty($step_name)) {
        throw new Exception("Step name is empty. Input received: " . print_r($input, true));
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Test 6: Check if tables exist
    $tables_check = [];
    
    $result = $conn->query("SHOW TABLES LIKE 'user_walkthrough_progress'");
    if (!$result || $result->num_rows === 0) {
        $tables_check[] = "user_walkthrough_progress table missing";
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'walkthrough_steps'");
    if (!$result || $result->num_rows === 0) {
        $tables_check[] = "walkthrough_steps table missing";
    }
    
    if (!empty($tables_check)) {
        throw new Exception("Database tables missing: " . implode(", ", $tables_check));
    }
    
    // Test 7: Check current progress exists
    $stmt = $conn->prepare("SELECT current_step, steps_completed, is_completed FROM user_walkthrough_progress WHERE user_id = ? AND walkthrough_type = 'initial_setup'");
    if (!$stmt) {
        throw new Exception("Failed to prepare progress query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute progress query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    $stmt->close();
    
    if (!$progress) {
        throw new Exception("No walkthrough progress found for user $user_id");
    }
    
    // Test 8: Check if step exists
    $stmt = $conn->prepare("SELECT step_order FROM walkthrough_steps WHERE step_name = ? AND walkthrough_type = 'initial_setup' AND is_active = 1");
    if (!$stmt) {
        throw new Exception("Failed to prepare step query: " . $conn->error);
    }
    
    $stmt->bind_param("s", $step_name);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute step query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $current_step_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$current_step_data) {
        throw new Exception("Step '$step_name' not found in database");
    }
    
    // If we get here, everything is working! Let's do the actual update
    $steps_completed = json_decode($progress['steps_completed'], true) ?: [];
    
    if (!in_array($step_name, $steps_completed)) {
        $steps_completed[] = $step_name;
    }
    
    $current_order = $current_step_data['step_order'];
    
    // Get next step
    $stmt = $conn->prepare("SELECT step_name, page_url FROM walkthrough_steps WHERE walkthrough_type = 'initial_setup' AND step_order > ? AND is_active = 1 ORDER BY step_order ASC LIMIT 1");
    $stmt->bind_param("i", $current_order);
    $stmt->execute();
    $result = $stmt->get_result();
    $next_step = $result->fetch_assoc();
    $stmt->close();
    
    $next_step_name = $next_step ? $next_step['step_name'] : null;
    $redirect_url = $next_step ? '../' . $next_step['page_url'] : null;
    $is_completed = !$next_step;
    
    // Update progress
    $stmt = $conn->prepare("UPDATE user_walkthrough_progress SET current_step = ?, steps_completed = ?, is_completed = ?, completed_at = ? WHERE user_id = ? AND walkthrough_type = 'initial_setup'");
    $completed_at = $is_completed ? date('Y-m-d H:i:s') : null;
    $steps_json = json_encode($steps_completed);
    
    $stmt->bind_param("ssisi", $next_step_name, $steps_json, $is_completed ? 1 : 0, $completed_at, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update progress: " . $stmt->error);
    }
    $stmt->close();
    
    // Clean any output buffer content before sending JSON
    ob_clean();
    
    // Success response
    echo json_encode([
        'success' => true,
        'next_step' => $next_step_name,
        'redirect_url' => $redirect_url,
        'is_completed' => $is_completed,
        'steps_completed' => $steps_completed,
        'debug_info' => [
            'user_id' => $user_id,
            'step_name' => $step_name,
            'current_order' => $current_order,
            'progress_before' => $progress
        ]
    ]);
    
} catch (Exception $e) {
    // Clean output buffer
    ob_clean();
    
    // Log the full error
    error_log("Walkthrough Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return detailed error info
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => $_SESSION['user_id'] ?? 'Not set',
            'step_name' => $step_name ?? 'Not set',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'raw_input' => $raw_input ?? 'No input',
            'php_version' => phpversion()
        ]
    ]);
} finally {
    // End output buffering
    ob_end_flush();
}
?>