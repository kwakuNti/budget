<?php
// Test the complete_step API directly
require_once 'config/connection.php';

echo "Testing complete_step.php API...\n\n";

// Check current state
echo "1. Checking current user state:\n";
$result = $conn->query("SELECT user_id, current_step, steps_completed FROM user_walkthrough_progress WHERE user_id = 2");
$current = $result->fetch_assoc();
echo "   Current step: " . $current['current_step'] . "\n";
echo "   Steps completed: " . $current['steps_completed'] . "\n\n";

// Test what happens when we try to complete setup_income
echo "2. Testing step completion logic:\n";

// Get the step that should come after setup_income
$stmt = $conn->prepare("
    SELECT step_name, page_url 
    FROM walkthrough_steps 
    WHERE walkthrough_type = 'initial_setup' 
    AND step_order > (
        SELECT step_order 
        FROM walkthrough_steps 
        WHERE step_name = ? AND walkthrough_type = 'initial_setup'
    )
    AND is_active = 1
    ORDER BY step_order ASC 
    LIMIT 1
");
$step_name = 'setup_income';
$stmt->bind_param("s", $step_name);
$stmt->execute();
$result = $stmt->get_result();
$next_step = $result->fetch_assoc();

if ($next_step) {
    echo "   Next step should be: " . $next_step['step_name'] . "\n";
    echo "   Page URL: " . $next_step['page_url'] . "\n";
} else {
    echo "   ERROR: No next step found!\n";
}

// Test JSON operations
echo "\n3. Testing JSON operations:\n";
$steps_completed = json_decode($current['steps_completed'], true) ?: [];
echo "   Parsed steps_completed: " . print_r($steps_completed, true);

if (!in_array('setup_income', $steps_completed)) {
    $steps_completed[] = 'setup_income';
}
echo "   After adding setup_income: " . print_r($steps_completed, true);
echo "   JSON encoded: " . json_encode($steps_completed) . "\n";

echo "\nTest completed.\n";
?>
