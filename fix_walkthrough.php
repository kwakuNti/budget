<?php
require_once 'config/connection.php';

// Remove the setup_complete step - we don't need it
$sql = "DELETE FROM walkthrough_steps WHERE step_name = 'setup_complete' AND walkthrough_type = 'initial_setup'";
$result = $conn->query($sql);

if ($result) {
    echo "✅ Removed setup_complete step successfully\n";
} else {
    echo "❌ Error removing setup_complete step: " . $conn->error . "\n";
}

// Update the budget step to be the final step
$sql = "UPDATE walkthrough_steps SET step_order = 3 WHERE step_name = 'setup_budget' AND walkthrough_type = 'initial_setup'";
$result = $conn->query($sql);

if ($result) {
    echo "✅ Updated budget step to be final step\n";
} else {
    echo "❌ Error updating budget step: " . $conn->error . "\n";
}

// Check current walkthrough steps
$sql = "SELECT step_name, step_order, page_url FROM walkthrough_steps WHERE walkthrough_type = 'initial_setup' ORDER BY step_order";
$result = $conn->query($sql);

echo "\n📋 Current walkthrough steps:\n";
while ($row = $result->fetch_assoc()) {
    echo "  {$row['step_order']}. {$row['step_name']} - {$row['page_url']}\n";
}

$conn->close();
echo "\n🎉 Walkthrough fix complete!\n";
?>
