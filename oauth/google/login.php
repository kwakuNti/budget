<?php
/**
 * Initiate Google OAuth Login using Google Client Library
 * Redirects user to Google OAuth authorization page
 */

session_start();
require_once '../../config/connection.php';
require_once '../../config/google_oauth_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if OAuth is properly configured
if (!isGoogleOAuthConfigured()) {
    error_log("Google OAuth Error: Configuration missing or invalid");
    header('Location: ../../login?error=oauth_not_configured');
    exit;
}

// Check if OAuth is enabled
if (!isGoogleOAuthEnabled()) {
    header('Location: ../../login?error=oauth_disabled');
    exit;
}

// Get Google Client and create auth URL
$googleClient = getGoogleClient();
$authUrl = $googleClient->createAuthUrl();

// Redirect to Google
header('Location: ' . $authUrl);
exit;
?>
