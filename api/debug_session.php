<?php
require_once '../config/connection.php';

header('Content-Type: application/json');

// Debug session information
$debug_info = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? null,
    'user_id_set' => isset($_SESSION['user_id']),
    'user_id_value' => $_SESSION['user_id'] ?? null,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'http_referer' => $_SERVER['HTTP_REFERER'] ?? null,
    'cookies' => $_COOKIE ?? null
];

echo json_encode([
    'success' => true,
    'debug' => $debug_info
]);
?>
