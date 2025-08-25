<?php
// Test the API call exactly as the JavaScript does
session_start();
$_SESSION['user_id'] = 2; // Set up session

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
curl_setopt($ch, CURLOPT_HEADER, true);  // Include headers in output

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== RAW CURL RESPONSE ===\n";
echo "HTTP Code: " . $httpCode . "\n";
echo "Raw Response:\n";
echo $response . "\n";

echo "\n=== ANALYSIS ===\n";

// Split headers and body
$parts = explode("\r\n\r\n", $response, 2);
if (count($parts) == 2) {
    $headers = $parts[0];
    $body = $parts[1];
    
    echo "Headers:\n" . $headers . "\n\n";
    echo "Body (first 500 chars):\n" . substr($body, 0, 500) . "\n\n";
    echo "Body length: " . strlen($body) . " characters\n";
    
    // Check for HTML
    if (strpos($body, '<') !== false) {
        echo "HTML detected at position: " . strpos($body, '<') . "\n";
        echo "Characters around HTML: " . substr($body, max(0, strpos($body, '<') - 10), 30) . "\n";
    }
    
    // Try to find JSON
    $jsonStart = strpos($body, '{');
    if ($jsonStart !== false) {
        echo "JSON starts at position: $jsonStart\n";
        $json = substr($body, $jsonStart);
        echo "JSON: " . $json . "\n";
        
        $decoded = json_decode($json, true);
        if ($decoded) {
            echo "JSON decoded successfully!\n";
            print_r($decoded);
        } else {
            echo "JSON decode failed: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "No JSON found in response\n";
    }
}
?>
