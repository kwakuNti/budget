<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include only the email configuration first
require_once __DIR__ . '/config/email_config.php';

echo "<h1>Email Verification Test</h1>";

try {
    // Create email service instance
    $emailService = new EmailService();
    
    // Test email address
    $testEmail = 'cliffco24@gmail.com';
    $testUsername = 'TestUser';
    
    // Generate a test verification token
    $verificationToken = bin2hex(random_bytes(32));
    
    echo "<h2>Test Configuration:</h2>";
    echo "<p><strong>Test Email:</strong> $testEmail</p>";
    echo "<p><strong>Username:</strong> $testUsername</p>";
    echo "<p><strong>Verification Token:</strong> " . substr($verificationToken, 0, 16) . "...</p>";
    
    echo "<h2>Sending Verification Email...</h2>";
    
    // Test sending verification email
    $result = $emailService->sendVerificationEmail($testEmail, $testUsername, $verificationToken);
    
    if ($result) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Verification email sent successfully to $testEmail";
        echo "</div>";
        
        echo "<h3>What to check:</h3>";
        echo "<ul>";
        echo "<li>Check your inbox at $testEmail</li>";
        echo "<li>Look for an email from 'noreply@budgetly.online'</li>";
        echo "<li>The verification link should point to https://budgetly.online/verify?token=...</li>";
        echo "<li>Check spam folder if not in inbox</li>";
        echo "</ul>";
        
        echo "<h3>Verification URL Preview:</h3>";
        echo "<p><code>https://budgetly.online/verify?token=$verificationToken</code></p>";
        
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "<strong>❌ FAILED!</strong> Could not send verification email";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>❌ ERROR:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<h3>Error Details:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h2>PHPMailer Status Check</h2>";

// Check if PHPMailer is installed
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p style='color: green;'>✅ PHPMailer is installed and accessible</p>";
} else {
    echo "<p style='color: red;'>❌ PHPMailer not found - check composer installation</p>";
}

// Check email config file
if (file_exists(__DIR__ . '/config/email_config.php')) {
    echo "<p style='color: green;'>✅ Email configuration file exists</p>";
} else {
    echo "<p style='color: red;'>❌ Email configuration file not found</p>";
}

echo "<hr>";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
