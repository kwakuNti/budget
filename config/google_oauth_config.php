<?php
/**
 * Google OAuth Configuration using Google Client Library
 * Configure your Google OAuth settings here
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Load Google API Client
require_once __DIR__ . '/../vendor/autoload.php';

// Check if running in production or development
$isProduction = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$domain = $isProduction ? 'https://budgetly.online' : 'http://localhost';

/**
 * Get configured Google Client
 */
function getGoogleClient() {
    global $domain;
    
    $client = new Google_Client();
    $client->setClientId(env('GOOGLE_CLIENT_ID', ''));
    $client->setClientSecret(env('GOOGLE_CLIENT_SECRET', ''));
    $client->setRedirectUri($domain . '/budget/oauth/google/callback.php');
    $client->addScope('email');
    $client->addScope('profile');
    
    return $client;
}

/**
 * Check if Google OAuth is properly configured
 */
function isGoogleOAuthConfigured() {
    $clientId = env('GOOGLE_CLIENT_ID', '');
    $clientSecret = env('GOOGLE_CLIENT_SECRET', '');
    
    if (empty($clientId) || empty($clientSecret)) {
        error_log("Google OAuth Error: Client ID or Client Secret not configured. Check your .env file.");
        return false;
    }
    
    if ($clientId === 'your_google_client_id_here' || 
        $clientSecret === 'your_google_client_secret_here') {
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
