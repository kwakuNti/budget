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

try {
    // Get step details
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
        WHERE step_name = ? AND walkthrough_type = 'initial_setup' AND is_active = 1
    ");
    $stmt->bind_param("s", $step_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $step = $result->fetch_assoc();
    
    if (!$step) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Step not found']);
        exit;
    }
    
    // Convert boolean fields
    $step['action_required'] = (bool)$step['action_required'];
    $step['can_skip'] = (bool)$step['can_skip'];
    
    echo json_encode([
        'success' => true,
        'step' => $step
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
