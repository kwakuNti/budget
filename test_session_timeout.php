<?php
/**
 * Session Timeout Test Script
 * Tests if sessions properly expire after the configured time
 */

session_start();
require_once 'config/connection.php';

header('Content-Type: application/json');

// Function to check session timeout
function checkSessionTimeout() {
    $current_time = time();
    
    // Check if session variables are set
    if (!isset($_SESSION['user_id'])) {
        return [
            'status' => 'no_session',
            'message' => 'No active session found',
            'logged_in' => false
        ];
    }
    
    // Check if last_activity is set
    if (!isset($_SESSION['last_activity'])) {
        return [
            'status' => 'no_activity_time',
            'message' => 'No last activity time recorded',
            'logged_in' => true,
            'session_data' => $_SESSION
        ];
    }
    
    // Check if session_expiry is set
    if (!isset($_SESSION['session_expiry'])) {
        return [
            'status' => 'no_expiry_time',
            'message' => 'No session expiry time configured',
            'logged_in' => true,
            'session_data' => $_SESSION
        ];
    }
    
    $last_activity = $_SESSION['last_activity'];
    $session_expiry = $_SESSION['session_expiry']; // This should be in seconds
    $time_since_last_activity = $current_time - $last_activity;
    
    // Check if session has expired
    if ($time_since_last_activity > $session_expiry) {
        // Session has expired - destroy it
        session_unset();
        session_destroy();
        
        return [
            'status' => 'expired',
            'message' => 'Session has expired due to inactivity',
            'logged_in' => false,
            'time_since_last_activity' => $time_since_last_activity,
            'session_expiry_seconds' => $session_expiry,
            'expired_by_seconds' => $time_since_last_activity - $session_expiry
        ];
    }
    
    // Session is still valid - update last activity
    $_SESSION['last_activity'] = $current_time;
    
    $time_remaining = $session_expiry - $time_since_last_activity;
    
    return [
        'status' => 'active',
        'message' => 'Session is active',
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'unknown',
        'time_since_last_activity' => $time_since_last_activity,
        'session_expiry_seconds' => $session_expiry,
        'time_remaining_seconds' => $time_remaining,
        'time_remaining_minutes' => round($time_remaining / 60, 2),
        'last_activity' => date('Y-m-d H:i:s', $last_activity),
        'current_time' => date('Y-m-d H:i:s', $current_time)
    ];
}

// Function to simulate session timeout (for testing)
function simulateTimeout() {
    if (isset($_SESSION['last_activity'])) {
        // Set last activity to more than 30 minutes ago
        $_SESSION['last_activity'] = time() - (31 * 60); // 31 minutes ago
        return [
            'status' => 'simulated',
            'message' => 'Session timeout simulated by setting last_activity to 31 minutes ago'
        ];
    }
    return [
        'status' => 'error',
        'message' => 'No session to simulate timeout for'
    ];
}

// Handle different test actions
$action = $_GET['action'] ?? 'check';

switch($action) {
    case 'check':
        $result = checkSessionTimeout();
        break;
        
    case 'simulate':
        $result = simulateTimeout();
        break;
        
    case 'info':
        $result = [
            'status' => 'info',
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION ?? null,
            'current_time' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ];
        break;
        
    default:
        $result = [
            'status' => 'error',
            'message' => 'Invalid action. Use: check, simulate, or info'
        ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
