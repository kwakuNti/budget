<?php
/**
 * Simple check of current September cycle state
 */
require_once 'config/connection.php';

$familyId = 1;
echo "=== SEPTEMBER 2025 CYCLE STATE ===\n";
echo "Current date: " . date('Y-m-d') . "\n\n";

// Check if we have a September cycle
$stmt = $conn->prepare("
    SELECT * FROM monthly_cycles 
    WHERE family_id = ? 
    AND cycle_month = 9 
    AND cycle_year = 2025
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $familyId);
$stmt->execute();
$septemberCycle = $stmt->get_result()->fetch_assoc();

if ($septemberCycle) {
    echo "✅ September 2025 cycle exists:\n";
    echo "   Status: {$septemberCycle['status']}\n";
    echo "   Days remaining: {$septemberCycle['days_remaining']}\n";
    
    // Check monthly performance for September
    echo "\n📊 September Monthly Performance:\n";
    $stmt = $conn->prepare("
        SELECT 
            m.first_name,
            m.last_name,
            COALESCE(mmp.monthly_contribution, 0) as monthly_contrib,
            COALESCE(mmp.total_contribution, 0) as lifetime_total
        FROM members m
        LEFT JOIN member_monthly_performance mmp ON (
            m.member_id = mmp.member_id 
            AND mmp.cycle_month = 9
            AND mmp.cycle_year = 2025
        )
        WHERE m.family_id = ?
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($members as $member) {
        echo "   {$member['first_name']}: Monthly ₵{$member['monthly_contrib']} | Lifetime ₵{$member['lifetime_total']}\n";
    }
    
    // Check September contributions
    echo "\n💰 September 2025 Contributions:\n";
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total
        FROM family_contributions 
        WHERE family_id = ? 
        AND MONTH(contribution_date) = 9 
        AND YEAR(contribution_date) = 2025
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $septContribs = $stmt->get_result()->fetch_assoc();
    
    echo "   Total September contributions: ₵{$septContribs['total']}\n";
    echo "   Number of contributions: {$septContribs['count']}\n";
    
} else {
    echo "❌ No September 2025 cycle found\n";
    
    echo "\nLet's check what cycles exist:\n";
    $stmt = $conn->prepare("SELECT * FROM monthly_cycles WHERE family_id = ? ORDER BY cycle_year DESC, cycle_month DESC LIMIT 5");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $cycles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($cycles as $cycle) {
        echo "   {$cycle['cycle_month']}/{$cycle['cycle_year']} - Status: {$cycle['status']}\n";
    }
}

echo "\n=== EXPECTED UI BEHAVIOR ===\n";
echo "For a NEW September cycle, the UI should show:\n";
echo "✅ Monthly contributions: ₵0 (unless test contribution exists)\n";
echo "✅ Monthly expenses: ₵0\n";
echo "✅ Member monthly progress: ₵0/₵300 for each member\n";
echo "✅ Family pool: ₵611 (preserved from previous months)\n";
echo "✅ Member lifetime totals: Preserved (Clifford ₵700, Perry ₵622)\n";

echo "\n=== CHECK COMPLETE ===\n";
?>
