<?php
/**
 * Initiate Google OAuth Login
 * Redirects user to Google OAuth authorization page
 */

session_start();
require_once '../../config/connection.php';
require_once '../../config/google_oauth_config.php';

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

// Generate state token for CSRF protection
$state = generateOAuthState();
$redirectUrl = $_GET['redirect'] ?? null;

// Store state in database
if (!storeOAuthState($state, 'google', $redirectUrl)) {
    error_log("Failed to store OAuth state token");
    header('Location: ../../login?error=oauth_error');
    exit;
}

// Generate Google OAuth URL
$authUrl = getGoogleAuthUrl($state);

// Redirect to Google
header('Location: ' . $authUrl);
exit;
?>
