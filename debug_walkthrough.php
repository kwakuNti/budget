<?php
session_start();
require_once 'config/connection.php';

// Debug information
echo "<h2>Walkthrough Debug Information</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ No user session found</p>";
    echo "<p>Please <a href='actions/login.php'>login</a> first</p>";
    exit;
}

echo "<p style='color: green;'>✅ User logged in: {$_SESSION['user_id']}</p>";

// Check if walkthrough tables exist
try {
    $result = $conn->query("SHOW TABLES LIKE 'user_walkthrough_progress'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ user_walkthrough_progress table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ user_walkthrough_progress table missing</p>";
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'walkthrough_steps'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ walkthrough_steps table exists</p>";
        
        // Check if steps are populated
        $result = $conn->query("SELECT COUNT(*) as count FROM walkthrough_steps WHERE walkthrough_type = 'initial_setup'");
        $row = $result->fetch_assoc();
        echo "<p>📊 Initial setup steps in database: {$row['count']}</p>";
        
        // Show the steps
        $result = $conn->query("SELECT step_name, step_order, target_element, title FROM walkthrough_steps WHERE walkthrough_type = 'initial_setup' ORDER BY step_order");
        echo "<h3>Available Steps:</h3><ul>";
        while ($step = $result->fetch_assoc()) {
            echo "<li>Step {$step['step_order']}: {$step['step_name']} - {$step['title']} (target: {$step['target_element']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ walkthrough_steps table missing</p>";
    }
    
    // Check user's current walkthrough progress
    $stmt = $conn->prepare("SELECT * FROM user_walkthrough_progress WHERE user_id = ? AND walkthrough_type = 'initial_setup'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    
    if ($progress) {
        echo "<h3>User's Walkthrough Progress:</h3>";
        echo "<ul>";
        echo "<li>Current Step: " . ($progress['current_step'] ?? 'None') . "</li>";
        echo "<li>Completed: " . ($progress['is_completed'] ? 'Yes' : 'No') . "</li>";
        echo "<li>Steps Completed: " . ($progress['steps_completed'] ?? '[]') . "</li>";
        echo "</ul>";
        
        if ($progress['is_completed']) {
            echo "<p style='color: blue;'>ℹ️ User has already completed the walkthrough</p>";
            echo "<button onclick='resetWalkthrough()' style='padding: 10px; background: #f59e0b; color: white; border: none; border-radius: 5px; cursor: pointer;'>Reset Walkthrough</button>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No walkthrough progress found - this is normal for first-time setup</p>";
    }
    
    // Test API endpoints
    echo "<h3>API Tests:</h3>";
    echo "<button onclick='testWalkthroughAPI()' style='padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Test Walkthrough Status API</button>";
    echo "<button onclick='startWalkthroughTest()' style='padding: 10px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Start Walkthrough Test</button>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<script>
async function testWalkthroughAPI() {
    try {
        const response = await fetch('./api/walkthrough_status.php');
        const data = await response.json();
        alert('API Response: ' + JSON.stringify(data, null, 2));
    } catch (error) {
        alert('API Error: ' + error.message);
    }
}

async function startWalkthroughTest() {
    try {
        // Test if walkthrough JavaScript is loaded
        if (typeof BudgetWalkthrough !== 'undefined') {
            alert('✅ BudgetWalkthrough class is available! Testing walkthrough...');
            const walkthrough = new BudgetWalkthrough();
        } else {
            alert('❌ BudgetWalkthrough class not found. Make sure walkthrough.js is loaded.');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function resetWalkthrough() {
    try {
        const response = await fetch('./api/skip_walkthrough.php', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            // Now delete the record to reset
            const resetResponse = await fetch('./api/reset_walkthrough.php', {
                method: 'POST'
            });
            
            alert('Walkthrough reset! Refresh the page to see the effect.');
            location.reload();
        } else {
            alert('Error resetting walkthrough: ' + data.error);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
</style>
