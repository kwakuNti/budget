<?php
require_once 'config/connection.php';

echo "=== CURRENT CYCLE STATE FOR UI ===\n";
echo "Current date: " . date('Y-m-d H:i:s') . "\n";
echo "Current month: " . date('n') . "/2025 (August)\n\n";

$familyId = 1;

// Check August 2025 cycle
$stmt = $conn->prepare("
    SELECT 
        id,
        cycle_month,
        cycle_year, 
        is_current,
        is_closed,
        total_collected,
        total_target,
        DATEDIFF(end_date, CURDATE()) as days_remaining
    FROM monthly_cycles 
    WHERE family_id = ? 
    AND cycle_year = 2025 
    AND cycle_month_num = 8
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $familyId);
$stmt->execute();
$augustCycle = $stmt->get_result()->fetch_assoc();

if ($augustCycle) {
    echo "📅 AUGUST 2025 CYCLE:\n";
    echo "   ID: {$augustCycle['id']}\n";
    echo "   Is Current: " . ($augustCycle['is_current'] ? 'Yes' : 'No') . "\n";
    echo "   Is Closed: " . ($augustCycle['is_closed'] ? 'Yes' : 'No') . "\n";
    echo "   Days Remaining: {$augustCycle['days_remaining']}\n";
    echo "   Total Collected: ₵{$augustCycle['total_collected']}\n";
    echo "   Target: ₵{$augustCycle['total_target']}\n";
}

// Check September 2025 cycle
$stmt = $conn->prepare("
    SELECT 
        id,
        cycle_month,
        cycle_year, 
        is_current,
        is_closed,
        total_collected,
        total_target,
        DATEDIFF(end_date, CURDATE()) as days_remaining
    FROM monthly_cycles 
    WHERE family_id = ? 
    AND cycle_year = 2025 
    AND cycle_month_num = 9
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $familyId);
$stmt->execute();
$septemberCycle = $stmt->get_result()->fetch_assoc();

if ($septemberCycle) {
    echo "\n📅 SEPTEMBER 2025 CYCLE:\n";
    echo "   ID: {$septemberCycle['id']}\n"; 
    echo "   Is Current: " . ($septemberCycle['is_current'] ? 'Yes' : 'No') . "\n";
    echo "   Is Closed: " . ($septemberCycle['is_closed'] ? 'Yes' : 'No') . "\n";
    echo "   Days Remaining: {$septemberCycle['days_remaining']}\n";
    echo "   Total Collected: ₵{$septemberCycle['total_collected']}\n";
    echo "   Target: ₵{$septemberCycle['total_target']}\n";
    
    // Check September member performance
    echo "\n👥 SEPTEMBER MEMBER PROGRESS:\n";
    $stmt = $conn->prepare("
        SELECT 
            m.display_name,
            m.current_month_contributed,
            m.monthly_contribution_goal,
            m.total_contributed
        FROM family_members m
        WHERE m.family_id = ? AND m.is_active = 1
        ORDER BY m.display_name
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($members as $member) {
        $percentage = $member['monthly_contribution_goal'] > 0 ? 
            round(($member['current_month_contributed'] / $member['monthly_contribution_goal']) * 100) : 0;
        echo "   {$member['display_name']}: ₵{$member['current_month_contributed']} / ₵{$member['monthly_contribution_goal']} ({$percentage}%) | Lifetime: ₵{$member['total_contributed']}\n";
    }
    
    // Check September contributions
    echo "\n💰 SEPTEMBER CONTRIBUTIONS:\n";
    $stmt = $conn->prepare("
        SELECT 
            fc.contribution_date,
            fc.amount,
            m.display_name
        FROM family_contributions fc
        JOIN family_members m ON fc.member_id = m.id
        WHERE fc.family_id = ? 
        AND MONTH(fc.contribution_date) = 9 
        AND YEAR(fc.contribution_date) = 2025
        ORDER BY fc.contribution_date DESC
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $septContribs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($septContribs)) {
        echo "   No September contributions yet\n";
    } else {
        foreach ($septContribs as $contrib) {
            echo "   {$contrib['contribution_date']}: {$contrib['display_name']} contributed ₵{$contrib['amount']}\n";
        }
    }
}

echo "\n=== WHAT THE UI SHOULD SHOW ===\n";

if ($septemberCycle && $septemberCycle['is_current']) {
    echo "🎯 FOR SEPTEMBER 2025 (NEW CYCLE):\n";
    echo "✅ Cycle Banner: 'September 2025' with days remaining\n";
    echo "✅ Monthly Progress: Each member shows ₵0 or current monthly progress\n";
    echo "✅ Monthly Stats: Show September-only contributions/expenses\n";
    echo "✅ Family Pool: ₵611 (preserved from all previous months)\n";
    echo "✅ Member Cards: Show monthly progress vs ₵300 goals\n";
    echo "✅ Contribute Buttons: Active and working\n";
} else if ($augustCycle && !$augustCycle['is_closed']) {
    echo "🎯 FOR AUGUST 2025 (CURRENT CYCLE):\n";
    echo "✅ Cycle Banner: 'August 2025' with days remaining\n";
    echo "✅ Monthly Progress: Shows August contributions\n";
    echo "✅ Family Pool: ₵611 (total accumulated)\n";
    echo "✅ Member Cards: Show August progress\n";
} else {
    echo "⚠️  WAITING STATE:\n";
    echo "✅ Cycle Banner: Shows 'Waiting for new cycle' or similar\n";
    echo "✅ Contribute Buttons: Disabled until new cycle starts\n";
}

echo "\n=== KEY RESET BEHAVIOR ===\n";
echo "When a NEW cycle starts (like September), these reset to ₵0:\n";
echo "• Member monthly contributions (₵0/₵300)\n";  
echo "• Monthly contribution totals\n";
echo "• Monthly expense totals\n";
echo "• Monthly transaction counts\n";
echo "\nThese are PRESERVED:\n";
echo "• Family pool total (₵611)\n";
echo "• Member lifetime totals\n";
echo "• Historical monthly data\n";

echo "\n=== CHECK COMPLETE ===\n";
?>
