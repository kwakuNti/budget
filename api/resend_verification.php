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
    
    if (!$email) {
        throw new Exception('Valid email address is required');
    }
    
    // Check if user exists and is not already verified
    $stmt = $conn->prepare("
        SELECT id, first_name, email_verified 
        FROM users 
        WHERE email = ? AND is_active = 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        throw new Exception('No account found with this email address');
    }
    
    if ($user['email_verified'] == 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Email is already verified. You can log in now.',
            'already_verified' => true
        ]);
        exit;
    }
    
    // Generate new verification token
    $verification_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Update user with new token
    $stmt = $conn->prepare("
        UPDATE users 
        SET verification_token = ?, 
            token_expires_at = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $verification_token, $token_expiry, $user['id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to generate new verification token');
    }
    $stmt->close();
    
    // Send verification email
    $emailResult = sendVerificationEmail($email, $user['first_name'], $verification_token);
    
    if ($emailResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Verification email sent successfully! Please check your inbox.'
        ]);
    } else {
        throw new Exception($emailResult['error'] ?? 'Failed to send verification email');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
