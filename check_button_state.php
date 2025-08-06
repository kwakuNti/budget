<?php
require_once 'config/connection.php';

echo "=== CURRENT CYCLE STATE FOR BUTTON VISIBILITY ===\n";

// Check current cycle state
$stmt = $conn->prepare("
    SELECT 
        id,
        cycle_month,
        cycle_year,
        cycle_month_num,
        is_current,
        is_closed,
        DATEDIFF(end_date, CURDATE()) as days_remaining
    FROM monthly_cycles 
    WHERE family_id = 1 
    ORDER BY cycle_year DESC, cycle_month_num DESC 
    LIMIT 1
");
$stmt->execute();
$currentCycle = $stmt->get_result()->fetch_assoc();

if ($currentCycle) {
    echo "Current Cycle Details:\n";
    echo "  Month: {$currentCycle['cycle_month']} {$currentCycle['cycle_year']}\n";
    echo "  Is Current: " . ($currentCycle['is_current'] ? 'Yes' : 'No') . "\n";
    echo "  Is Closed: " . ($currentCycle['is_closed'] ? 'Yes' : 'No') . "\n";
    echo "  Days Remaining: {$currentCycle['days_remaining']}\n";
    
    // Check if we're waiting for new month
    $currentMonth = date('n');
    $waitingForNewMonth = ($currentCycle['is_closed'] && $currentMonth > $currentCycle['cycle_month_num']);
    
    echo "  Waiting for New Month: " . ($waitingForNewMonth ? 'Yes' : 'No') . "\n";
    
    echo "\nButton Visibility Logic:\n";
    if ($waitingForNewMonth) {
        echo "  ✅ START NEW CYCLE button should be VISIBLE\n";
        echo "  ❌ Close cycle button should be HIDDEN\n";
    } elseif ($currentCycle['is_closed']) {
        echo "  ❌ Both buttons should be HIDDEN (closed cycle)\n";
    } else {
        echo "  ❌ START NEW CYCLE button should be HIDDEN\n";
        echo "  ✅ Close cycle button should be VISIBLE\n";
    }
} else {
    echo "No current cycle found - this might be the issue!\n";
}

// Check current date and what we need
echo "\nCurrent Date Info:\n";
echo "  Server Date: " . date('Y-m-d') . "\n";
echo "  Current Month: " . date('n') . "/" . date('Y') . " (August)\n";

// Check if we have an August cycle
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM monthly_cycles 
    WHERE family_id = 1 
    AND cycle_month_num = ?
    AND cycle_year = ?
");
$currentMonth = date('n');
$currentYear = date('Y');
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$augustCycleExists = $stmt->get_result()->fetch_assoc()['count'] > 0;

echo "August 2025 cycle exists: " . ($augustCycleExists ? 'Yes' : 'No') . "\n";

if (!$augustCycleExists) {
    echo "⚠️  NO AUGUST CYCLE EXISTS - We need to create one!\n";
    echo "   The Start New Cycle button should be visible to create August 2025 cycle\n";
}

echo "\n=== SOLUTION ===\n";
echo "If the Start New Cycle button is not showing, the issue is likely:\n";
echo "1. The dashboard API is not setting waiting_for_new_month correctly\n";
echo "2. Or there's a JavaScript error preventing the button from showing\n";
echo "3. You should see the button on the main dashboard at http://localhost/budget-app/\n";
?>
