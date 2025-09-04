<?php
// config/connection.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username   = "root";
$password   = "Killshot@23"; // if using MAMP default, keep it empty
$database   = "budget";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    // For API calls, return JSON error instead of HTML
    if (isset($_POST['action']) || strpos($_SERVER['REQUEST_URI'], 'handler.php') !== false || strpos($_SERVER['REQUEST_URI'], 'walkthrough') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}
?>
