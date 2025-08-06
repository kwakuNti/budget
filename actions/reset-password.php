<?php
session_start();
include '../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST["identifier"]);
    $newPassword = $_POST["newPassword"];
    $confirmPassword = $_POST["confirmPassword"];

    // Validation
    if (empty($identifier) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: ../templates/login?status=error&message=All fields are required.");
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        header("Location: ../templates/login?status=error&message=Passwords do not match.");
        exit();
    }

    if (strlen($newPassword) < 6) {
        header("Location: ../templates/login?status=error&message=Password must be at least 6 characters long.");
        exit();
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Detect if input is an email
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

    // First, check if user exists and get their ID
    if ($isEmail) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND is_active = 1");
    }
    
    $checkStmt->bind_param("s", $identifier);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $user = $checkResult->fetch_assoc();
        $userId = $user['id'];
        $checkStmt->close();
        
        // Update password - using password_hash column name from your login.php
        if ($isEmail) {
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ? AND is_active = 1");
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE username = ? AND is_active = 1");
        }
        
        $updateStmt->bind_param("ss", $hashedPassword, $identifier);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows > 0) {
            // Log the reset action
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent) VALUES (?, 'password_reset', 'Password was reset', ?, ?)");
            $log->bind_param("iss", $userId, $ipAddress, $userAgent);
            $log->execute();
            $log->close();
            
            $updateStmt->close();
            $conn->close();
            
            header("Location: ../templates/login?status=success&message=Password reset successfully. You can now log in with your new password.");
            exit();
        } else {
            $updateStmt->close();
            $conn->close();
            
            header("Location: ../templates/login?status=error&message=Failed to update password. Please try again.");
            exit();
        }
    } else {
        $checkStmt->close();
        $conn->close();
        
        // User not found - but don't reveal this for security
        header("Location: ../templates/login?status=success&message=If the account exists, the password has been reset.");
        exit();
    }
}
?>