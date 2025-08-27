<?php
// Simple test script
session_start();

// Set up test session
$_SESSION['user_id'] = 2; // Test user ID

// Test the API directly
require_once '../config/connection.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing walkthrough API...\n";

// Simulate POST data
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create a test input
$test_input = json_encode([
    'step_name' => 'setup_income',
    'type' => 'initial_setup'
]);

// Override php://input for testing
file_put_contents('php://temp', $test_input);

// Include the API file
ob_start();
include 'complete_walkthrough_step.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output;
?>
