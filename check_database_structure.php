<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'budget';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Database Table Structure Check</h1>";
    
    // Check users table structure
    echo "<h2>Users Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if email verification columns exist
    echo "<h3>Email Verification Columns Check:</h3>";
    $emailVerifiedExists = false;
    $verificationTokenExists = false;
    $tokenExpiresExists = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'email_verified') {
            $emailVerifiedExists = true;
            echo "<p style='color: green;'>✅ email_verified column exists</p>";
        }
        if ($column['Field'] === 'verification_token') {
            $verificationTokenExists = true;
            echo "<p style='color: green;'>✅ verification_token column exists</p>";
        }
        if ($column['Field'] === 'token_expires_at') {
            $tokenExpiresExists = true;
            echo "<p style='color: green;'>✅ token_expires_at column exists</p>";
        }
    }
    
    if (!$emailVerifiedExists) {
        echo "<p style='color: red;'>❌ email_verified column missing</p>";
    }
    if (!$verificationTokenExists) {
        echo "<p style='color: red;'>❌ verification_token column missing</p>";
    }
    if (!$tokenExpiresExists) {
        echo "<p style='color: red;'>❌ token_expires_at column missing</p>";
    }
    
    // Try to add missing columns
    echo "<h2>Adding Missing Columns:</h2>";
    
    if (!$emailVerifiedExists) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
            echo "<p style='color: green;'>✅ Added email_verified column</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to add email_verified: " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$verificationTokenExists) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255)");
            echo "<p style='color: green;'>✅ Added verification_token column</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to add verification_token: " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$tokenExpiresExists) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN token_expires_at DATETIME");
            echo "<p style='color: green;'>✅ Added token_expires_at column</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to add token_expires_at: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
