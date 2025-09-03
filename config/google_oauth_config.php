<?php
/**
 * Google OAuth Configuration
 * Configure your Google OAuth settings here
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Check if running in production or development
$isProduction = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$domain = $isProduction ? 'https://budgetly.online' : 'http://localhost';

// Google OAuth Configuration - Using environment variables for security
define('GOOGLE_OAUTH_CONFIG', [
    'client_id' => env('GOOGLE_CLIENT_ID', ''), // Set in .env file
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''), // Set in .env file
    'redirect_uri' => $domain . '/budget/oauth/google/callback.php',
    'scopes' => [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile'
    ],
    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'user_info_url' => 'https://www.googleapis.com/oauth2/v2/userinfo'
]);

/**
 * Check if Google OAuth is properly configured
 */
function isGoogleOAuthConfigured() {
    $config = GOOGLE_OAUTH_CONFIG;
    
    if (empty($config['client_id']) || empty($config['client_secret'])) {
        error_log("Google OAuth Error: Client ID or Client Secret not configured. Check your .env file.");
        return false;
    }
    
    if ($config['client_id'] === 'your_google_client_id_here' || 
        $config['client_secret'] === 'your_google_client_secret_here') {
        error_log("Google OAuth Error: Using template values. Update your .env file with real credentials.");
        return false;
    }
    
    return true;
}

/**
 * Check if Google OAuth is enabled
 */
function isGoogleOAuthEnabled() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'google_oauth_enabled'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (bool) $row['setting_value'];
    }
    
    return true; // Default to enabled if setting doesn't exist
}
?>
