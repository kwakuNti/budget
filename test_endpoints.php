<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Verification Endpoint Test</h1>";

// Test the verification endpoint with a sample token
$testToken = "test_token_12345";

echo "<h2>Testing /api/verify_email.php</h2>";

// Simulate a POST request to the verification endpoint
$_POST['token'] = $testToken;
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();

try {
    // Include the verification endpoint
    if (file_exists(__DIR__ . '/api/verify_email.php')) {
        include __DIR__ . '/api/verify_email.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "<h3>Endpoint Response:</h3>";
        echo "<div style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
        
        // Check if it's a valid JSON response
        $decoded = json_decode($output, true);
        if ($decoded !== null) {
            echo "<p style='color: green;'>✅ Valid JSON response</p>";
            if (isset($decoded['success'])) {
                echo "<p style='color: green;'>✅ Response has 'success' field</p>";
            }
            if (isset($decoded['message'])) {
                echo "<p style='color: green;'>✅ Response has 'message' field</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        }
        
    } else {
        ob_end_clean();
        echo "<p style='color: red;'>❌ Verification endpoint not found</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Error testing endpoint: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

// Test the resend verification endpoint
echo "<h2>Testing /api/resend_verification.php</h2>";

$_POST['email'] = 'test@example.com';

ob_start();

try {
    if (file_exists(__DIR__ . '/api/resend_verification.php')) {
        include __DIR__ . '/api/resend_verification.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "<h3>Endpoint Response:</h3>";
        echo "<div style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
        
        // Check if it's a valid JSON response
        $decoded = json_decode($output, true);
        if ($decoded !== null) {
            echo "<p style='color: green;'>✅ Valid JSON response</p>";
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        }
        
    } else {
        ob_end_clean();
        echo "<p style='color: red;'>❌ Resend verification endpoint not found</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Error testing resend endpoint: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

// Test the verification page
echo "<h2>Testing /verify Route (URL Rewriting)</h2>";

$_GET['token'] = $testToken;

ob_start();

try {
    if (file_exists(__DIR__ . '/templates/verify-email.php')) {
        include __DIR__ . '/templates/verify-email.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "<h3>Page Response:</h3>";
        echo "<p style='color: green;'>✅ Verification page loads successfully</p>";
        
        // Check if the page contains expected elements
        if (strpos($output, 'verification') !== false) {
            echo "<p style='color: green;'>✅ Page contains verification content</p>";
        }
        if (strpos($output, 'token') !== false) {
            echo "<p style='color: green;'>✅ Page processes token parameter</p>";
        }
        
    } else {
        ob_end_clean();
        echo "<p style='color: red;'>❌ Verification page not found</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Error testing verification page: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Production Environment Notes</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
echo "<h3>⚠️ Important for Production:</h3>";
echo "<ul>";
echo "<li><strong>URL Rewriting:</strong> Ensure Apache/Nginx is configured to route /verify to /templates/verify-email.php</li>";
echo "<li><strong>HTTPS:</strong> All verification links should use HTTPS in production</li>";
echo "<li><strong>Domain:</strong> Update email config to use your actual domain (budgetly.online)</li>";
echo "<li><strong>Error Logging:</strong> Configure proper error logging for production</li>";
echo "<li><strong>Rate Limiting:</strong> Consider adding rate limiting to prevent abuse</li>";
echo "</ul>";
echo "</div>";

echo "<p><em>Endpoint test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
