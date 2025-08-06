<?php
/**
 * Quick fix to show Start New Cycle button
 * This will make the button visible for testing
 */
require_once 'config/connection.php';

$familyId = 1; // Your family ID

echo "=== MAKING START NEW CYCLE BUTTON VISIBLE ===\n";

// Option 1: Check if we need to close the September cycle to make way for a new one
$stmt = $conn->prepare("
    SELECT id, cycle_month, is_current, is_closed 
    FROM monthly_cycles 
    WHERE family_id = ? 
    AND is_current = 1 
    ORDER BY cycle_year DESC, cycle_month_num DESC 
    LIMIT 1
");
$stmt->bind_param("i", $familyId);
$stmt->execute();
$activeCycle = $stmt->get_result()->fetch_assoc();

if ($activeCycle) {
    echo "Found active cycle: {$activeCycle['cycle_month']}\n";
    echo "Status: " . ($activeCycle['is_closed'] ? 'Closed' : 'Active') . "\n";
    
    if (!$activeCycle['is_closed']) {
        echo "\n⚠️  To see the 'Start New Cycle' button, you have two options:\n";
        echo "1. Close the current September cycle first\n";
        echo "2. Or modify the dashboard logic to allow manual cycle creation\n";
        
        // Option to close the current cycle
        $choice = readline("\nDo you want to close the current September cycle? (y/n): ");
        
        if (strtolower($choice) === 'y') {
            echo "Closing current cycle...\n";
            
            $stmt = $conn->prepare("
                UPDATE monthly_cycles 
                SET is_closed = 1, is_current = 0, closed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $activeCycle['id']);
            
            if ($stmt->execute()) {
                echo "✅ September cycle closed successfully!\n";
                echo "Now the 'Start New Cycle' button should be visible on the dashboard.\n";
                echo "\nGo to http://localhost/budget-app/ to see the button.\n";
            } else {
                echo "❌ Failed to close cycle.\n";
            }
        } else {
            echo "Cycle not closed. The button will remain hidden.\n";
        }
    }
} else {
    echo "No active cycle found - the Start New Cycle button should already be visible!\n";
}

echo "\n=== ALTERNATIVE: ALWAYS SHOW BUTTON ===\n";
echo "If you want the button to always be available, I can modify the dashboard logic.\n";
echo "This would allow you to start new cycles manually anytime.\n";

?>
