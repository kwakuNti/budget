<?php
session_start();
require_once '../config/connection.php';
require_once '../config/email_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('No input data received');
    }
    
    $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $token = trim($input['token'] ?? '');
    
    if (!$token) {
        throw new Exception('Verification token is required');
    }
    
    // Check if the token is valid and not expired
    // If email is provided, use both email and token for verification
    // If only token is provided, use token only (more common for email verification links)
    // Handle both full tokens (64 chars) and short codes (8 chars)
    
    if ($email) {
        if (strlen($token) === 8) {
            // Short code verification with email
            $stmt = $conn->prepare("
                SELECT id, first_name, email, email_verified, token_expires_at 
                FROM users 
                WHERE email = ? AND LEFT(verification_token, 8) = ? AND is_active = 1
            ");
            $stmt->bind_param("ss", $email, strtolower($token));
        } else {
            // Full token verification with email
            $stmt = $conn->prepare("
                SELECT id, first_name, email, email_verified, token_expires_at 
                FROM users 
                WHERE email = ? AND verification_token = ? AND is_active = 1
            ");
            $stmt->bind_param("ss", $email, $token);
        }
    } else {
        if (strlen($token) === 8) {
            // Short code verification without email (less secure, but usable)
            $stmt = $conn->prepare("
                SELECT id, first_name, email, email_verified, token_expires_at 
                FROM users 
                WHERE LEFT(verification_token, 8) = ? AND is_active = 1
            ");
            $stmt->bind_param("s", strtolower($token));
        } else {
            // Full token verification without email
            $stmt = $conn->prepare("
                SELECT id, first_name, email, email_verified, token_expires_at 
                FROM users 
                WHERE verification_token = ? AND is_active = 1
            ");
            $stmt->bind_param("s", $token);
        }
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        throw new Exception('Invalid verification token or email address');
    }
    
    if ($user['email_verified'] == 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Email is already verified. You can now log in.',
            'already_verified' => true
        ]);
        exit;
    }
    
    // Check if token has expired
    if ($user['token_expires_at'] && strtotime($user['token_expires_at']) < time()) {
        throw new Exception('Verification token has expired. Please request a new verification email.');
    }
    
    // Update user as verified
    $stmt = $conn->prepare("
        UPDATE users 
        SET email_verified = 1, 
            verification_token = NULL, 
            token_expires_at = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user['id']);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log successful verification
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action_type, description, ip_address, created_at) 
            VALUES (?, 'email_verified', 'Email address successfully verified', ?, NOW())
        ");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param("is", $user['id'], $ip_address);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! You can now log in.',
            'user_name' => $user['first_name']
        ]);
    } else {
        throw new Exception('Failed to verify email. Please try again.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
