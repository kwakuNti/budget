<?php
// Test the complete_step API with proper session
session_start();

// Set user session
$_SESSION['user_id'] = 2;

echo "Testing complete_step API with session...\n\n";

// Test the API endpoint with curl
$url = 'http://localhost:8888/budget/public/walkthrough/complete_step.php';
$data = json_encode(['step_name' => 'setup_income']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

// Try to decode the JSON
$decoded = json_decode($response, true);
if ($decoded) {
    echo "\nDecoded successfully:\n";
    print_r($decoded);
} else {
    echo "\nJSON decode failed. Raw response:\n";
    echo $response;
}
?>
