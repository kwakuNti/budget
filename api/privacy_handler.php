<?php
/**
 * Enhanced Privacy System API Handler
 * Handles PIN setup, validation, and privacy toggle functionality with improved session management
 */

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check_privacy_status':
        checkPrivacyStatus($conn, $user_id);
        break;
    case 'setup_pin':
        setupPin($conn, $user_id);
        break;
    case 'verify_pin':
        verifyPin($conn, $user_id);
        break;
    case 'toggle_privacy':
        togglePrivacy($conn, $user_id);
        break;
    case 'set_figures_visibility':
        setFiguresVisibility($conn, $user_id);
        break;
    case 'request_pin_reset':
        requestPinReset($conn, $user_id);
        break;
    case 'reset_pin':
        resetPin($conn, $user_id);
        break;
    case 'get_privacy_session':
        getPrivacySession($conn, $user_id);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function checkPrivacyStatus($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT privacy_enabled, privacy_pin IS NOT NULL as has_pin, failed_attempts, locked_until FROM user_privacy_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            // Create default settings
            $stmt = $conn->prepare("INSERT INTO user_privacy_settings (user_id, privacy_enabled) VALUES (?, FALSE)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = ['privacy_enabled' => false, 'has_pin' => false, 'failed_attempts' => 0, 'locked_until' => null];
        }
        
        // Check if account is locked
        $is_locked = false;
        if ($result['locked_until']) {
            $locked_until = new DateTime($result['locked_until']);
            $now = new DateTime();
            $is_locked = $now < $locked_until;
            
            // If lock has expired, clear it
            if (!$is_locked) {
                $stmt = $conn->prepare("UPDATE user_privacy_settings SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }
        }
        
        // Check for active privacy session and clean up expired ones
        $has_active_session = false;
        $session_expires = null;
        if ($result['privacy_enabled']) {
            // Clean up expired sessions first
            $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ? AND expires_at <= NOW()");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Check for valid session
            $stmt = $conn->prepare("SELECT expires_at FROM privacy_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $session_result = $stmt->get_result()->fetch_assoc();
            
            if ($session_result) {
                $has_active_session = true;
                $session_expires = $session_result['expires_at'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'privacy_enabled' => (bool)$result['privacy_enabled'],
            'has_pin' => (bool)$result['has_pin'],
            'is_locked' => $is_locked,
            'failed_attempts' => (int)$result['failed_attempts'],
            'has_session' => $has_active_session,
            'session_expires' => $session_expires,
            'figures_visible' => !$result['privacy_enabled'] || $has_active_session,
            'timestamp' => time() * 1000 // JavaScript timestamp
        ]);
        
    } catch (Exception $e) {
        error_log("Privacy status check error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to check privacy status']);
    }
}

function setupPin($conn, $user_id) {
    try {
        $pin = $_POST['pin'] ?? '';
        
        if (strlen($pin) !== 6 || !ctype_digit($pin)) {
            echo json_encode(['success' => false, 'message' => 'PIN must be exactly 6 digits']);
            return;
        }
        
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update PIN and enable privacy
        $stmt = $conn->prepare("UPDATE user_privacy_settings SET privacy_pin = ?, privacy_enabled = TRUE, pin_set_date = NOW(), failed_attempts = 0, locked_until = NULL WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_pin, $user_id);
        $stmt->execute();
        
        // Clear any existing sessions
        $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'PIN setup successfully',
            'privacy_enabled' => true,
            'figures_visible' => false
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("PIN setup error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to setup PIN']);
    }
}

function verifyPin($conn, $user_id) {
    try {
        $pin = $_POST['pin'] ?? '';
        
        if (strlen($pin) !== 6 || !ctype_digit($pin)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PIN format']);
            return;
        }
        
        // Check if account is locked
        $stmt = $conn->prepare("SELECT privacy_pin, failed_attempts, locked_until FROM user_privacy_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result || !$result['privacy_pin']) {
            echo json_encode(['success' => false, 'message' => 'No PIN set']);
            return;
        }
        
        // Check if locked
        if ($result['locked_until']) {
            $locked_until = new DateTime($result['locked_until']);
            $now = new DateTime();
            if ($now < $locked_until) {
                $minutes_left = ceil(($locked_until->getTimestamp() - $now->getTimestamp()) / 60);
                echo json_encode(['success' => false, 'message' => "Account locked. Try again in {$minutes_left} minutes"]);
                return;
            }
        }
        
        // Verify PIN
        if (password_verify($pin, $result['privacy_pin'])) {
            // Start transaction
            $conn->begin_transaction();
            
            // Reset failed attempts
            $stmt = $conn->prepare("UPDATE user_privacy_settings SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Clear old sessions
            $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Create new privacy session (valid for 30 minutes)
            $session_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO privacy_sessions (user_id, session_token, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $session_token, $expires_at, $ip_address, $user_agent);
            $stmt->execute();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'PIN verified successfully', 
                'session_token' => $session_token,
                'session_expires' => $expires_at,
                'figures_visible' => true,
                'timestamp' => time() * 1000
            ]);
            
        } else {
            // Increment failed attempts
            $failed_attempts = $result['failed_attempts'] + 1;
            $locked_until = null;
            
            // Lock account after 5 failed attempts for 15 minutes
            if ($failed_attempts >= 5) {
                $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }
            
            $stmt = $conn->prepare("UPDATE user_privacy_settings SET failed_attempts = ?, locked_until = ? WHERE user_id = ?");
            $stmt->bind_param("isi", $failed_attempts, $locked_until, $user_id);
            $stmt->execute();
            
            if ($locked_until) {
                echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes']);
            } else {
                $remaining = 5 - $failed_attempts;
                echo json_encode(['success' => false, 'message' => "Incorrect PIN. {$remaining} attempts remaining"]);
            }
        }
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        error_log("PIN verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to verify PIN']);
    }
}

function togglePrivacy($conn, $user_id) {
    try {
        $enable = filter_var($_POST['enable'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        $stmt = $conn->prepare("UPDATE user_privacy_settings SET privacy_enabled = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $enable, $user_id);
        $stmt->execute();
        
        // If disabling privacy, clear all sessions
        if (!$enable) {
            $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $enable ? 'Privacy enabled' : 'Privacy disabled', 
            'privacy_enabled' => $enable,
            'figures_visible' => !$enable,
            'timestamp' => time() * 1000
        ]);
        
    } catch (Exception $e) {
        error_log("Toggle privacy error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to toggle privacy']);
    }
}

function setFiguresVisibility($conn, $user_id) {
    try {
        $visible = filter_var($_POST['visible'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (!$visible) {
            // Hide figures - clear all sessions
            $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true, 
            'figures_visible' => $visible,
            'timestamp' => time() * 1000
        ]);
        
    } catch (Exception $e) {
        error_log("Set figures visibility error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to set figures visibility']);
    }
}

function requestPinReset($conn, $user_id) {
    try {
        // Get user email
        $stmt = $conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $conn->prepare("UPDATE user_privacy_settings SET pin_reset_token = ?, pin_reset_expires = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $reset_token, $expires_at, $user_id);
        $stmt->execute();
        
        // Send email (implement your email service here)
        $email_sent = sendPinResetEmail($user['email'], $user['first_name'], $reset_token);
        
        if ($email_sent) {
            echo json_encode(['success' => true, 'message' => 'PIN reset link sent to your email']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send reset email. Please try again']);
        }
        
    } catch (Exception $e) {
        error_log("PIN reset request error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to process reset request']);
    }
}

function resetPin($conn, $user_id) {
    try {
        $token = $_POST['token'] ?? '';
        $new_pin = $_POST['new_pin'] ?? '';
        
        if (strlen($new_pin) !== 6 || !ctype_digit($new_pin)) {
            echo json_encode(['success' => false, 'message' => 'PIN must be exactly 6 digits']);
            return;
        }
        
        // Verify token
        $stmt = $conn->prepare("SELECT pin_reset_expires FROM user_privacy_settings WHERE user_id = ? AND pin_reset_token = ?");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
            return;
        }
        
        $expires_at = new DateTime($result['pin_reset_expires']);
        $now = new DateTime();
        
        if ($now > $expires_at) {
            echo json_encode(['success' => false, 'message' => 'Reset token has expired']);
            return;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update PIN
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user_privacy_settings SET privacy_pin = ?, pin_reset_token = NULL, pin_reset_expires = NULL, failed_attempts = 0, locked_until = NULL, last_pin_reset = NOW() WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_pin, $user_id);
        $stmt->execute();
        
        // Clear all privacy sessions
        $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'PIN reset successfully']);
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        error_log("PIN reset error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to reset PIN']);
    }
}

function getPrivacySession($conn, $user_id) {
    try {
        // Clean up expired sessions first
        $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE user_id = ? AND expires_at <= NOW()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Get active session
        $stmt = $conn->prepare("SELECT session_token, expires_at FROM privacy_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'has_session' => true, 
                'expires_at' => $result['expires_at'],
                'figures_visible' => true,
                'timestamp' => time() * 1000
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'has_session' => false,
                'figures_visible' => false,
                'timestamp' => time() * 1000
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Get privacy session error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to check session']);
    }
}

function sendPinResetEmail($email, $name, $token) {
    // Enhanced email implementation
    $subject = "PIN Reset Request - Budget Manager";
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/budget/templates/pin-reset.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>PIN Reset Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9fafb; }
            .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            .footer { padding: 20px; text-align: center; color: #6b7280; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>PIN Reset Request</h2>
            </div>
            <div class='content'>
                <p>Hello {$name},</p>
                <p>You requested to reset your privacy PIN for the Budget Manager application.</p>
                <p>Click the button below to reset your PIN:</p>
                <p><a href='{$reset_link}' class='button'>Reset PIN</a></p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you didn't request this reset, please ignore this email and your PIN will remain unchanged.</p>
            </div>
            <div class='footer'>
                <p>Best regards,<br>Budget Manager Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Budget Manager <noreply@budgetmanager.com>" . "\r\n";
    $headers .= "Reply-To: noreply@budgetmanager.com" . "\r\n";
    
    // Use mail() function or your preferred email service
    return mail($email, $subject, $message, $headers);
}

// Utility function to clean up old sessions (can be called via cron)
function cleanupExpiredSessions($conn) {
    try {
        $stmt = $conn->prepare("DELETE FROM privacy_sessions WHERE expires_at <= NOW()");
        $stmt->execute();
        $affected = $stmt->affected_rows;
        error_log("Cleaned up {$affected} expired privacy sessions");
    } catch (Exception $e) {
        error_log("Session cleanup error: " . $e->getMessage());
    }
}

// Auto cleanup on every 10th request (simple maintenance)
if (rand(1, 10) === 1) {
    cleanupExpiredSessions($conn);
}
?>