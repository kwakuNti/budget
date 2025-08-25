<?php
require_once 'config/connection.php';

echo "Testing walkthrough JSON handling...\n";

try {
    // Just check the current state and test the PHP JSON handling
    $result = $conn->query("SELECT user_id, current_step, steps_completed FROM user_walkthrough_progress WHERE user_id = 2");
    $row = $result->fetch_assoc();
    
    echo "\nUser 2 current state:\n";
    echo "Current step: " . $row['current_step'] . "\n";
    echo "Steps completed (raw): " . var_export($row['steps_completed'], true) . "\n";
    
    // Test how PHP handles the NULL JSON
    $parsed = json_decode($row['steps_completed'], true);
    echo "json_decode result: " . var_export($parsed, true) . "\n";
    
    // Test the fallback that should be used
    $steps_completed = json_decode($row['steps_completed'], true) ?: [];
    echo "With fallback (?:[]): " . var_export($steps_completed, true) . "\n";
    
    // Test adding a step
    if (!in_array('setup_income', $steps_completed)) {
        $steps_completed[] = 'setup_income';
    }
    echo "After adding setup_income: " . var_export($steps_completed, true) . "\n";
    echo "JSON encoded: " . json_encode($steps_completed) . "\n";
    
    echo "\nThe PHP code should handle NULL values correctly with the ?: [] fallback.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
