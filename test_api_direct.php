<?php
// Direct test of the API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Goal Types API Directly</h2>\n";

// Test database connection first
try {
    require_once 'config/connection.php';
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test the query
    $query = "SHOW COLUMNS FROM personal_goals LIKE 'goal_type'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>✅ Query successful</p>\n";
        echo "<p><strong>Result:</strong></p>\n";
        echo "<pre>" . print_r($result, true) . "</pre>\n";
        
        // Test the regex parsing
        if ($result['Type']) {
            preg_match_all("/'([^']+)'/", $result['Type'], $matches);
            echo "<p><strong>Parsed goal types:</strong></p>\n";
            echo "<pre>" . print_r($matches[1], true) . "</pre>\n";
        }
    } else {
        echo "<p>❌ No result from query</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test the API endpoint
echo "<hr>\n";
echo "<h3>API Endpoint Test</h3>\n";
$apiUrl = "http://localhost/budget/api/goal_types.php";
$context = stream_context_create([
    'http' => [
        'timeout' => 10
    ]
]);

$response = file_get_contents($apiUrl, false, $context);
if ($response !== false) {
    echo "<p>✅ API endpoint accessible</p>\n";
    echo "<p><strong>Response:</strong></p>\n";
    echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
} else {
    echo "<p>❌ Could not access API endpoint</p>\n";
}
?>
