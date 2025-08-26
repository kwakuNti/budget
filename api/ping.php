<?php
/**
 * Simple ping endpoint to keep session alive
 */

session_start();
header('Content-Type: application/json');

// Include session timeout middleware
require_once '../includes/session_timeout_middleware.php';

// Check session
$session_check = checkSessionTimeout();

if ($session_check['valid']) {
    echo json_encode([
        'success' => true,
        'message' => 'Session active',
        'time_remaining' => $session_check['time_remaining'],
        'user_id' => $session_check['user_id']
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'session_expired' => true,
        'message' => $session_check['message']
    ]);
}
?>
