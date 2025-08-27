<?php
session_start();
include '../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST["resetEmail"]); // Can be email or username

    if (empty($identifier)) {
        header("Location: /login?status=error&message=Email or username is required!");
        exit();
    }

    // Check if input is email or username
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    // Prepare query based on input type
    if ($isEmail) {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, username FROM users WHERE email = ? AND is_active = 1");
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, username FROM users WHERE username = ? AND is_active = 1");
    }
    
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($userId, $firstName, $lastName, $userEmail, $username);
        $stmt->fetch();
        
        // Generate a simple reset token for demo purposes
        $resetToken = bin2hex(random_bytes(16)); // Shorter token for simplicity
        $resetExpiry = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours from now
        
        // Store reset token in database
        // You'll need to add reset_token and reset_token_expiry columns to users table
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("ssi", $resetToken, $resetExpiry, $userId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Log the password reset request
        $logActivity = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent) VALUES (?, 'password_reset_request', 'Password reset requested', ?, ?)");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logActivity->bind_param("iss", $userId, $ipAddress, $userAgent);
        $logActivity->execute();
        $logActivity->close();
        
        // For demo purposes, redirect to reset password page with token
        // In production, you would send this via email
        $stmt->close();
        $conn->close();
        header("Location: /reset-password?token=" . $resetToken . "&status=success&message=Reset link generated. Use the link to reset your password.");
        exit();
        
    } else {
        $stmt->close();
        $conn->close();
        // Still show success message for security (don't reveal if user exists)
        header("Location: /login?status=success&message=If an account with that email/username exists, a password reset link has been generated.");
        exit();
    }
}
?>