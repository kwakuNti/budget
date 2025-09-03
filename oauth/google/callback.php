<?php
/**
 * Google OAuth Callback Handler
 * Handles the response from Google OAuth and processes user authentication
 */

session_start();
require_once '../../config/connection.php';
require_once '../../config/google_oauth_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check for authorization code
if (!isset($_GET['code'])) {
    $error = $_GET['error'] ?? 'unknown_error';
    error_log("OAuth Error: " . $error);
    header('Location: ../../login?error=oauth_' . $error);
    exit;
}

// Verify state token (CSRF protection)
$state = $_GET['state'] ?? '';
$redirectUrl = verifyOAuthState($state, 'google');

if ($redirectUrl === false) {
    error_log("OAuth Error: Invalid state token");
    header('Location: ../../login?error=oauth_invalid_state');
    exit;
}

try {
    // Exchange authorization code for access token
    $tokenData = exchangeCodeForToken($_GET['code']);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception("Failed to exchange code for token");
    }
    
    // Get user information from Google
    $userInfo = getGoogleUserInfo($tokenData['access_token']);
    
    if (!$userInfo || !isset($userInfo['email'])) {
        throw new Exception("Failed to get user information from Google");
    }
    
    $googleUserId = $userInfo['id'];
    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? '';
    $picture = $userInfo['picture'] ?? '';
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Check if user exists by email
        $stmt = $conn->prepare("SELECT id, google_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();
        
        if ($existingUser) {
            // User exists - link Google account if not already linked
            $userId = $existingUser['id'];
            
            if (empty($existingUser['google_id'])) {
                // Link Google account to existing user
                $updateStmt = $conn->prepare("UPDATE users SET google_id = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("si", $googleUserId, $userId);
                $updateStmt->execute();
            }
            
            // Update or insert OAuth account record
            $oauthStmt = $conn->prepare("
                INSERT INTO user_oauth_accounts (user_id, provider_name, provider_user_id, provider_email, provider_data, last_login_at) 
                VALUES (?, 'google', ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                provider_email = VALUES(provider_email), 
                provider_data = VALUES(provider_data), 
                last_login_at = NOW()
            ");
            $providerData = json_encode($userInfo);
            $oauthStmt->bind_param("isss", $userId, $googleUserId, $email, $providerData);
            $oauthStmt->execute();
            
        } else {
            // Create new user account
            $hashedPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT); // Random password
            
            $insertStmt = $conn->prepare("
                INSERT INTO users (email, password, first_name, google_id, email_verified, created_at, updated_at) 
                VALUES (?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $insertStmt->bind_param("ssss", $email, $hashedPassword, $name, $googleUserId);
            $insertStmt->execute();
            
            $userId = $conn->insert_id;
            
            // Create OAuth account record
            $oauthStmt = $conn->prepare("
                INSERT INTO user_oauth_accounts (user_id, provider_name, provider_user_id, provider_email, provider_data, created_at, last_login_at) 
                VALUES (?, 'google', ?, ?, ?, NOW(), NOW())
            ");
            $providerData = json_encode($userInfo);
            $oauthStmt->bind_param("isss", $userId, $googleUserId, $email, $providerData);
            $oauthStmt->execute();
            
            // Set up default budget allocation (50/30/20 rule)
            $defaultStmt = $conn->prepare("
                INSERT INTO budget_allocation (user_id, needs_percentage, wants_percentage, savings_percentage, created_at, updated_at) 
                VALUES (?, 50.00, 30.00, 20.00, NOW(), NOW())
            ");
            $defaultStmt->bind_param("i", $userId);
            $defaultStmt->execute();
        }
        
        // Log successful OAuth attempt
        logOAuthAttempt('google', $googleUserId, $email, 1, null, $userId);
        
        // Commit transaction
        $conn->commit();
        
        // Set up user session
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $name;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_method'] = 'google_oauth';
        $_SESSION['last_activity'] = time();
        
        // Redirect to intended page or dashboard
        $finalRedirect = $redirectUrl ?: '../../dashboard';
        header('Location: ' . $finalRedirect);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log failed OAuth attempt
    $googleUserId = $userInfo['id'] ?? 'unknown';
    $email = $userInfo['email'] ?? 'unknown';
    logOAuthAttempt('google', $googleUserId, $email, 0, $e->getMessage());
    
    error_log("OAuth Callback Error: " . $e->getMessage());
    header('Location: ../../login?error=oauth_callback_failed');
    exit;
}
?>
