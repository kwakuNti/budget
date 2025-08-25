<?php
// Simple session debug for walkthrough
require_once '../../config/connection.php';

header('Content-Type: application/json');

// Debug all the session information
$debug_info = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? null,
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_id_value' => $_SESSION['user_id'] ?? null,
    'cookies' => $_COOKIE ?? null,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'referer' => $_SERVER['HTTP_REFERER'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
];

echo json_encode([
    'success' => true,
    'debug' => $debug_info,
    'message' => 'Session debug info from public/walkthrough directory'
]);
?>
