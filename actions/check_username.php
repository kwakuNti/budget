<?php
// Username availability check
session_start();
include '../config/connection.php';

header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($available, $message = '') {
    echo json_encode([
        'available' => $available,
        'message' => $message
    ]);
    exit();
}

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(false, "Invalid request method");
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username'])) {
    sendResponse(false, "Username not provided");
}

$username = trim($input['username']);

// Basic validation
if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
    sendResponse(false, "Invalid username length");
}

if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
    sendResponse(false, "Invalid username format");
}

// Check reserved usernames
$reservedUsernames = ['admin', 'administrator', 'root', 'system', 'nkansah', 'family', 'user', 'test'];
if (in_array(strtolower($username), $reservedUsernames)) {
    sendResponse(false, "Username is reserved");
}

// Check database if connection exists
if (isset($conn) && $conn) {
    try {
        $checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if ($checkUsername) {
            $checkUsername->bind_param("s", $username);
            $checkUsername->execute();
            $result = $checkUsername->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(false, "Username already taken");
            } else {
                sendResponse(true, "Username is available");
            }
            
            $checkUsername->close();
        } else {
            sendResponse(false, "Database error");
        }
    } catch (Exception $e) {
        error_log("Username check error: " . $e->getMessage());
        sendResponse(false, "Database error");
    }
} else {
    // If no database connection, just check reserved names
    sendResponse(true, "Username appears available");
}

$conn->close();
?>