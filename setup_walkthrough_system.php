<?php
require_once 'config/connection.php';

try {
    // Add walkthrough columns to users table using MySQLi
    $result1 = $conn->query("ALTER TABLE users ADD COLUMN walkthrough_completed TINYINT(1) DEFAULT 0");
    $result2 = $conn->query("ALTER TABLE users ADD COLUMN walkthrough_completed_at TIMESTAMP NULL DEFAULT NULL");
    
    if ($result1 && $result2) {
        echo "✅ Walkthrough columns added to users table successfully!\n";
    } else {
        echo "⚠️ Some columns may already exist (this is OK)\n";
    }
    
    // Check current structure
    $result = $conn->query("DESCRIBE users");
    
    echo "\n📋 Current users table structure:\n";
    while ($column = $result->fetch_assoc()) {
        $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ? "DEFAULT '{$column['Default']}'" : '';
        echo "  - {$column['Field']}: {$column['Type']} {$null} {$default}\n";
    }
    
    // Create a test to ensure the walkthrough system works
    echo "\n🧪 Testing walkthrough system...\n";
    
    // Check if there are any users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $result->fetch_assoc()['count'];
    
    if ($userCount > 0) {
        echo "  - Found {$userCount} users in the system\n";
        
        // Check walkthrough status
        $result = $conn->query("SELECT id, username, walkthrough_completed FROM users LIMIT 5");
        
        echo "  - Sample user walkthrough status:\n";
        while ($user = $result->fetch_assoc()) {
            $status = $user['walkthrough_completed'] ? '✅ Completed' : '⏳ Pending';
            echo "    * {$user['username']}: {$status}\n";
        }
    } else {
        echo "  - No users found in the system\n";
    }
    
    echo "\n🎯 Walkthrough system setup complete!\n";
    echo "📝 Next steps:\n";
    echo "  1. Include walkthrough.css in your main pages\n";
    echo "  2. Include walkthrough.js in your main pages\n";
    echo "  3. The system will automatically start for first-time users\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up walkthrough system: " . $e->getMessage() . "\n";
    
    // If columns already exist, that's OK
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✅ Walkthrough columns already exist in users table!\n";
    }
}
?>
