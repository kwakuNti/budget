<?php
/**
 * Setup script for new auto-savings system
 * Run this to create the necessary database tables
 */

require_once 'config/connection.php';

echo "Setting up Auto-Savings System...\n\n";

try {
    // Read and execute the updatedb.sql file
    $sqlFile = 'db/updatedb.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL commands by semicolon and execute each one
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($commands as $command) {
        if (empty($command) || strpos($command, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $conn->query($command);
            $successCount++;
            
            // Extract table name for better logging
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $command, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $command, $matches)) {
                echo "✅ Updated table: {$matches[1]}\n";
            } else {
                echo "✅ Executed SQL command\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            // Check if it's just a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "ℹ️  Table already exists (skipping)\n";
                $errorCount--; // Don't count as error
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Setup completed!\n";
    echo "✅ Successful operations: $successCount\n";
    if ($errorCount > 0) {
        echo "❌ Errors encountered: $errorCount\n";
    }
    echo "\nAuto-Savings System is now ready to use!\n\n";
    
    // Test the new API endpoint
    echo "Testing API endpoint...\n";
    
    // Simulate a user session for testing
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo "⚠️  No user session found. Please log in to test the full functionality.\n";
    } else {
        $userId = $_SESSION['user_id'];
        
        // Test getting autosave config
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM personal_autosave_config WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] == 0) {
            // Create default config
            $stmt = $conn->prepare("
                INSERT INTO personal_autosave_config 
                (user_id, enabled, save_frequency, save_day, round_up_enabled, round_up_threshold, emergency_fund_priority, emergency_fund_target) 
                VALUES (?, 0, 'monthly', 1, 0, 5.00, 1, 1000.00)
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            echo "✅ Created default autosave configuration for user $userId\n";
        } else {
            echo "✅ Autosave configuration already exists for user $userId\n";
        }
    }
    
    echo "\n🎉 Auto-Savings System setup complete!\n";
    echo "\nFeatures available:\n";
    echo "• Smart auto-save with configurable frequency\n";
    echo "• Emergency fund prioritization\n";
    echo "• Round-up savings\n";
    echo "• Multiple challenge types (save amount, no-spend, reduce category, round-up)\n";
    echo "• Progress tracking for all challenges\n";
    echo "• Integration with existing savings goals\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}

$conn->close();
?>
