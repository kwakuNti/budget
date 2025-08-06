<?php
require_once 'config/connection.php';

echo "Current server date: " . date('Y-m-d H:i:s') . "\n";
echo "Current month/year: " . date('n/Y') . "\n";

// Check what cycles exist
$stmt = $conn->prepare("SELECT cycle_month, cycle_year, status FROM monthly_cycles WHERE family_id = 1 ORDER BY cycle_year DESC, cycle_month DESC LIMIT 3");
$stmt->execute();
$cycles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "Existing cycles:\n";
foreach($cycles as $cycle) {
    echo "  {$cycle['cycle_month']}/{$cycle['cycle_year']} - Status: {$cycle['status']}\n";
}

// Check if there's an active August cycle
$stmt = $conn->prepare("SELECT * FROM monthly_cycles WHERE family_id = 1 AND cycle_month = 8 AND cycle_year = 2025");
$stmt->execute();
$augustCycle = $stmt->get_result()->fetch_assoc();

if ($augustCycle) {
    echo "\nAugust 2025 cycle status: {$augustCycle['status']}\n";
    echo "Days remaining: {$augustCycle['days_remaining']}\n";
} else {
    echo "\nNo August 2025 cycle found\n";
}
?>
