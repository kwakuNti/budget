<?php
// Test what's actually being returned by the API
echo "Testing complete_step.php directly...\n\n";

// Capture all output including errors
ob_start();

// Mock the session and input
session_start();
$_SESSION['user_id'] = 2;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create a mock input stream
$inputData = json_encode(['step_name' => 'setup_income']);
file_put_contents('php://temp/maxmemory:1024', $inputData);

// Redirect stdin to our temp data
$tempFile = tmpfile();
fwrite($tempFile, $inputData);
rewind($tempFile);

// Capture the API output
try {
    // Include the API file
    include 'public/walkthrough/complete_step.php';
} catch (Exception $e) {
    echo "PHP Error: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "Raw output:\n";
echo "Length: " . strlen($output) . " characters\n";
echo "First 200 characters:\n";
echo substr($output, 0, 200) . "\n\n";

echo "Looking for HTML tags:\n";
if (strpos($output, '<') !== false) {
    echo "HTML detected at position: " . strpos($output, '<') . "\n";
    echo "Content around HTML: " . substr($output, max(0, strpos($output, '<') - 20), 50) . "\n";
}

echo "\nTrying to find JSON:\n";
$jsonStart = strpos($output, '{');
if ($jsonStart !== false) {
    echo "JSON starts at position: $jsonStart\n";
    $jsonPart = substr($output, $jsonStart);
    echo "JSON part: " . $jsonPart . "\n";
    
    $decoded = json_decode($jsonPart, true);
    if ($decoded) {
        echo "JSON decoded successfully!\n";
        print_r($decoded);
    } else {
        echo "JSON decode failed\n";
    }
}

fclose($tempFile);
?>
