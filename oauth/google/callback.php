<?php
/**
 * Google OAuth Callback Handler using Google Client Library
 * Handles the response from Google OAuth and processes user authentication
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/connection.php';
require_once '../../config/google_oauth_config.php';

if (isset($_GET['code'])) {
    try {
        // Get Google Client and exchange code for token
        $googleClient = getGoogleClient();
        $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
        $googleClient->setAccessToken($token['access_token']);

        // Get user profile from Google
        $googleService = new Google_Service_Oauth2($googleClient);
        $googleUser = $googleService->userinfo->get();

        $googleId = $googleUser->id;
        $name = $googleUser->name;
        $email = $googleUser->email;
        $picture = $googleUser->picture ?? '';

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
                    $updateStmt->bind_param("si", $googleId, $userId);
                    $updateStmt->execute();
                }

            } else {
                // Create new user account
                $hashedPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT); // Random password

                $insertStmt = $conn->prepare("
                    INSERT INTO users (email, password, first_name, google_id, email_verified, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ");
                $insertStmt->bind_param("ssss", $email, $hashedPassword, $name, $googleId);
                $insertStmt->execute();

                $userId = $conn->insert_id;
            }

            // Commit transaction
            $conn->commit();

            // Set up user session
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $name;
            $_SESSION['logged_in'] = true;
            $_SESSION['login_method'] = 'google_oauth';
            $_SESSION['last_activity'] = time();

            // Redirect to dashboard
            header('Location: ../../dashboard');
            exit;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("OAuth Callback Error: " . $e->getMessage());
        header('Location: ../../login?error=oauth_callback_failed&message=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    $error = $_GET['error'] ?? 'access_denied';
    error_log("OAuth Error: " . $error);
    header('Location: ../../login?error=oauth_' . $error);
    exit;
}
?>
