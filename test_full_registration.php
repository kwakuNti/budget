<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'budget';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Full Registration & Email Verification Test</h1>";

try {
    // Test user data
    $testEmail = 'cliffco24@gmail.com';
    $testUsername = 'TestUser' . rand(1000, 9999);
    $testPassword = 'TestPassword123!';
    
    echo "<h2>Test Registration Data:</h2>";
    echo "<p><strong>Email:</strong> $testEmail</p>";
    echo "<p><strong>Username:</strong> $testUsername</p>";
    echo "<p><strong>Password:</strong> [hidden]</p>";
    
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id, email_verified FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $testEmail);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
        echo "⚠️ User with email $testEmail already exists. ";
        echo "Email verified status: " . ($existingUser['email_verified'] ? 'YES' : 'NO');
        echo "</div>";
        
        // Delete existing test user to start fresh
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE email = ?");
        $deleteStmt->bind_param("s", $testEmail);
        if ($deleteStmt->execute()) {
            echo "<p style='color: blue;'>🗑️ Deleted existing test user to start fresh</p>";
        }
    }
    
    echo "<h2>Step 1: Creating User Account...</h2>";
    
    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    
    // Insert test user with email verification fields
    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, email_verified, verification_token, token_expires_at) 
            VALUES (?, ?, ?, ?, ?, 'personal', 0, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $firstName = 'Test';
    $lastName = 'User';
    $stmt->bind_param("sssssss", $testUsername, $testEmail, $hashedPassword, $firstName, $lastName, $verificationToken, $tokenExpires);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo "<p style='color: green;'>✅ User account created successfully (ID: $userId)</p>";
        
        echo "<h2>Step 2: Sending Verification Email...</h2>";
        
        // Send verification email
        require_once __DIR__ . '/config/email_config.php';
        $emailService = new EmailService();
        
        if ($emailService->sendVerificationEmail($testEmail, $testUsername, $verificationToken)) {
            echo "<p style='color: green;'>✅ Verification email sent successfully!</p>";
            
            echo "<h2>Step 3: Testing Verification Process...</h2>";
            echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0;'>";
            echo "<h3>🔗 Verification Link:</h3>";
            echo "<p><a href='verify?token=$verificationToken' target='_blank'>";
            echo "https://budgetly.online/verify?token=$verificationToken</a></p>";
            echo "<p><em>Click this link to test email verification</em></p>";
            echo "</div>";
            
            echo "<h2>Step 4: Current Database Status...</h2>";
            
            // Check current user status
            $statusStmt = $conn->prepare("SELECT username, email, email_verified, verification_token, token_expires_at, created_at FROM users WHERE id = ?");
            $statusStmt->bind_param("i", $userId);
            $statusStmt->execute();
            $userStatus = $statusStmt->get_result()->fetch_assoc();
            
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Username</td><td>" . htmlspecialchars($userStatus['username']) . "</td></tr>";
            echo "<tr><td>Email</td><td>" . htmlspecialchars($userStatus['email']) . "</td></tr>";
            echo "<tr><td>Email Verified</td><td>" . ($userStatus['email_verified'] ? '✅ YES' : '❌ NO') . "</td></tr>";
            echo "<tr><td>Token</td><td>" . substr($userStatus['verification_token'], 0, 20) . "...</td></tr>";
            echo "<tr><td>Token Expires</td><td>" . $userStatus['token_expires_at'] . "</td></tr>";
            echo "<tr><td>Created</td><td>" . $userStatus['created_at'] . "</td></tr>";
            echo "</table>";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to send verification email</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create user account: " . $stmt->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>❌ ERROR:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check your email at $testEmail for the verification message</li>";
echo "<li>Click the verification link in the email</li>";
echo "<li>Verify that the email_verified status changes to 1 in the database</li>";
echo "<li>Test login with the verified account</li>";
echo "</ol>";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
