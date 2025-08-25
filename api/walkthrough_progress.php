<?php
require_once '../config/connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check walkthrough completion status
        $action = $_GET['action'] ?? '';
        
        if ($action === 'check_status') {
            $stmt = $conn->prepare("SELECT walkthrough_completed FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            echo json_encode([
                'completed' => $user ? (bool)$user['walkthrough_completed'] : false
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST requests
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'complete_walkthrough') {
            // Mark walkthrough as completed
            $stmt = $conn->prepare("UPDATE users SET walkthrough_completed = 1, walkthrough_completed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Walkthrough marked as completed'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update walkthrough status']);
            }
            
        } elseif ($action === 'reset_walkthrough') {
            // Reset walkthrough status (for testing/admin purposes)
            $stmt = $conn->prepare("UPDATE users SET walkthrough_completed = 0, walkthrough_completed_at = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Walkthrough status reset'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to reset walkthrough status']);
            }
            
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Walkthrough API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>
