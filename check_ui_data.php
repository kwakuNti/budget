<?php
/**
 * Check what data the UI should be showing for current cycle
 */
session_start();
require_once 'config/connection.php';
require_once 'includes/cycle_functions.php';

// Simulate logged in user (family_id = 1)
$familyId = 1;
$currentMonth = date('Y-m');

echo "=== UI DATA CHECK FOR SEPTEMBER 2025 ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Current Month: $currentMonth\n\n";

// 1. Check current cycle information
echo "1. CURRENT CYCLE INFORMATION:\n";
$stmt = $conn->prepare("
    SELECT 
        cycle_id,
        cycle_month,
        cycle_year,
        status,
        start_date,
        end_date,
        days_remaining,
        total_target
    FROM monthly_cycles 
    WHERE family_id = ? 
    AND cycle_month = MONTH(CURDATE()) 
    AND cycle_year = YEAR(CURDATE())
    ORDER BY cycle_id DESC 
    LIMIT 1
");
$stmt->bind_param("i", $familyId);
$stmt->execute();
$currentCycle = $stmt->get_result()->fetch_assoc();

if ($currentCycle) {
    echo "✅ September 2025 Cycle Found:\n";
    echo "   - Cycle ID: {$currentCycle['cycle_id']}\n";
    echo "   - Status: {$currentCycle['status']}\n";
    echo "   - Days Remaining: {$currentCycle['days_remaining']}\n";
    echo "   - Target: ₵{$currentCycle['total_target']}\n";
} else {
    echo "❌ No September 2025 cycle found\n";
}

echo "\n2. MONTHLY PROGRESS (Should show ₵0 for new month):\n";

// Check monthly contributions for September 2025
$stmt = $conn->prepare("
    SELECT 
        m.member_id,
        m.first_name,
        m.last_name,
        COALESCE(mmp.monthly_contribution, 0) as monthly_contribution,
        COALESCE(mmp.goal_amount, 300) as monthly_goal,
        COALESCE(mmp.total_contribution, 0) as lifetime_total
    FROM members m
    LEFT JOIN member_monthly_performance mmp ON (
        m.member_id = mmp.member_id 
        AND mmp.cycle_month = MONTH(CURDATE())
        AND mmp.cycle_year = YEAR(CURDATE())
    )
    WHERE m.family_id = ?
    ORDER BY m.first_name
");
$stmt->bind_param("i", $familyId);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($members as $member) {
    echo "   {$member['first_name']} {$member['last_name']}:\n";
    echo "     - Monthly Progress: ₵{$member['monthly_contribution']} / ₵{$member['monthly_goal']} (should be ₵0 for new month)\n";
    echo "     - Lifetime Total: ₵{$member['lifetime_total']}\n";
}

echo "\n3. FAMILY POOL (Should be preserved):\n";
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(fc.amount), 0) as total_contributions,
        COALESCE((SELECT SUM(fe.amount) FROM family_expenses fe WHERE fe.family_id = ?), 0) as total_expenses
    FROM family_contributions fc
    WHERE fc.family_id = ?
");
$stmt->bind_param("ii", $familyId, $familyId);
$stmt->execute();
$poolData = $stmt->get_result()->fetch_assoc();

$familyPool = $poolData['total_contributions'] - $poolData['total_expenses'];
echo "   - Total Contributions: ₵{$poolData['total_contributions']}\n";
echo "   - Total Expenses: ₵{$poolData['total_expenses']}\n";
echo "   - Family Pool: ₵{$familyPool} (should be preserved from previous months)\n";

echo "\n4. MONTHLY STATISTICS (Should be ₵0 for new month):\n";

// Check September 2025 monthly stats
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(fc.amount), 0) as monthly_contributions,
        COUNT(fc.contribution_id) as contribution_count,
        COALESCE((SELECT SUM(fe.amount) FROM family_expenses fe 
                  WHERE fe.family_id = ? 
                  AND MONTH(fe.expense_date) = MONTH(CURDATE())
                  AND YEAR(fe.expense_date) = YEAR(CURDATE())), 0) as monthly_expenses,
        COALESCE((SELECT COUNT(*) FROM family_expenses fe 
                  WHERE fe.family_id = ? 
                  AND MONTH(fe.expense_date) = MONTH(CURDATE())
                  AND YEAR(fe.expense_date) = YEAR(CURDATE())), 0) as expense_count
    FROM family_contributions fc
    WHERE fc.family_id = ?
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
");
$stmt->bind_param("iii", $familyId, $familyId, $familyId);
$stmt->execute();
$monthlyStats = $stmt->get_result()->fetch_assoc();

echo "   - Monthly Contributions: ₵{$monthlyStats['monthly_contributions']} (should be ₵0 or ₵100 if test contribution exists)\n";
echo "   - Number of Contributions: {$monthlyStats['contribution_count']}\n";
echo "   - Monthly Expenses: ₵{$monthlyStats['monthly_expenses']} (should be ₵0 for new month)\n";
echo "   - Number of Expenses: {$monthlyStats['expense_count']}\n";

echo "\n5. UI BEHAVIOR SUMMARY:\n";
echo "✅ What should RESET to ₵0 for new month:\n";
echo "   - Individual member monthly progress\n";
echo "   - Monthly contribution totals\n";
echo "   - Monthly expense totals\n";
echo "   - Monthly transaction counts\n";
echo "\n✅ What should be PRESERVED:\n";
echo "   - Family pool total\n";
echo "   - Member lifetime contribution totals\n";
echo "   - Historical data from previous months\n";

echo "\n=== UI DATA CHECK COMPLETE ===\n";
?>
