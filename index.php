<?php
// Budget App - Main Entry Point
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if we have a session and user is logged in
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['family_id'])) {
    // User is logged in, redirect to dashboard
    header("Location: templates/personal-dashboard");
} else {
    // User not logged in, redirect to login
    header("Location: templates/login");
}
exit();
?>