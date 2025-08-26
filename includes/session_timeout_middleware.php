<?php
/**
 * Session Timeout Middleware
 * Include this at the top of protected pages to check session timeout
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if session has timed out due to inactivity
 * @return array Result of session check
 */
function checkSessionTimeout() {
    $current_time = time();
    
    // If user is not logged in, redirect to login
    if (!isset($_SESSION['user_id'])) {
        return [
            'valid' => false,
            'reason' => 'not_logged_in',
            'message' => 'Please log in to access this page'
        ];
    }
    
    // Check if last_activity is set (should be set during login)
    if (!isset($_SESSION['last_activity'])) {
        // Set it now if missing
        $_SESSION['last_activity'] = $current_time;
    }
    
    // Check if session_expiry is set (should be 30 minutes = 1800 seconds)
    if (!isset($_SESSION['session_expiry'])) {
        // Set default 30 minutes if missing
        $_SESSION['session_expiry'] = 30 * 60;
    }
    
    $last_activity = $_SESSION['last_activity'];
    $session_expiry = $_SESSION['session_expiry'];
    $time_since_last_activity = $current_time - $last_activity;
    
    // Check if session has expired
    if ($time_since_last_activity > $session_expiry) {
        // Session has expired - destroy it
        $expired_username = $_SESSION['username'] ?? 'unknown';
        
        session_unset();
        session_destroy();
        
        return [
            'valid' => false,
            'reason' => 'timeout',
            'message' => 'Your session has expired due to inactivity. Please log in again.',
            'expired_user' => $expired_username,
            'time_inactive' => $time_since_last_activity,
            'timeout_minutes' => round($session_expiry / 60)
        ];
    }
    
    // Session is still valid - update last activity
    $_SESSION['last_activity'] = $current_time;
    
    return [
        'valid' => true,
        'reason' => 'active',
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'unknown',
        'time_remaining' => $session_expiry - $time_since_last_activity
    ];
}

/**
 * Enforce session timeout - redirects if session is invalid
 * Call this function at the top of protected pages
 * @param string $redirect_url URL to redirect to if session is invalid
 */
function enforceSessionTimeout($redirect_url = 'templates/login.php') {
    $session_check = checkSessionTimeout();
    
    if (!$session_check['valid']) {
        $message = urlencode($session_check['message']);
        $status = ($session_check['reason'] === 'timeout') ? 'warning' : 'error';
        
        // For AJAX requests, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'session_expired' => true,
                'message' => $session_check['message'],
                'redirect' => $redirect_url
            ]);
            exit;
        }
        
        // For regular requests, redirect
        header("Location: {$redirect_url}?status={$status}&message={$message}");
        exit;
    }
    
    return $session_check;
}

/**
 * Get session status for AJAX calls
 * Returns JSON with session information
 */
function getSessionStatus() {
    $session_check = checkSessionTimeout();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $session_check['valid'],
        'session_valid' => $session_check['valid'],
        'reason' => $session_check['reason'],
        'message' => $session_check['message'] ?? '',
        'time_remaining' => $session_check['time_remaining'] ?? 0,
        'time_remaining_minutes' => isset($session_check['time_remaining']) ? round($session_check['time_remaining'] / 60, 1) : 0
    ]);
    exit;
}

// If this file is called directly via AJAX, return session status
if (basename($_SERVER['PHP_SELF']) === 'session_timeout_middleware.php') {
    getSessionStatus();
}
?>
