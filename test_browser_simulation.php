<?php
// Test the actual form submission path that the browser would use

// Simulate browser request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/budget/actions/register.php';

// Set form data exactly as the browser would send it
$_POST = [
    'firstName' => 'John',
    'lastName' => 'Doe', 
    'email' => 'john' . rand(1000, 9999) . '@test.com',
    'username' => 'johndoe' . rand(1000, 9999),
    'phoneNumber' => '+233 20 123 4567',
    'dateOfBirth' => '1990-01-15',
    'password' => 'SecurePass123!',
    'confirmPassword' => 'SecurePass123!',
    'accountType' => 'personal',
    'monthlyContribution' => '',
    'agreeTerms' => '1'
];

// Capture the clean response
ob_start();

// Include the register script
include '/Applications/MAMP/htdocs/budget/actions/register.php';

$response = ob_get_clean();

// Output the response
echo "=== REGISTRATION TEST RESPONSE ===\n";
echo $response;
echo "\n=== END RESPONSE ===\n";

// Try to parse as JSON
echo "\n=== JSON VALIDATION ===\n";
$json = json_decode($response, true);
if ($json) {
    echo "✅ Valid JSON response\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $json['message'] . "\n";
    if (isset($json['data']['user_id'])) {
        echo "User ID: " . $json['data']['user_id'] . "\n";
    }
} else {
    echo "❌ Invalid JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
?>
