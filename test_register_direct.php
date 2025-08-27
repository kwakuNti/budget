<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing registration endpoint...\n";

// Set basic POST data
$_POST = [
    'username' => 'TestUser' . rand(1000, 9999),
    'email' => 'test' . rand(1000, 9999) . '@example.com',
    'password' => 'TestPass123',
    'confirmPassword' => 'TestPass123',
    'firstName' => 'Test',
    'lastName' => 'User',
    'phoneNumber' => '+233123456789',
    'dateOfBirth' => '1990-01-01',
    'accountType' => 'personal',
    'agreeTerms' => '1'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "POST data set, including register.php...\n";

// Capture output
ob_start();
try {
    include '/Applications/MAMP/htdocs/budget/actions/register.php';
    $output = ob_get_clean();
    echo "Output: " . $output . "\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
