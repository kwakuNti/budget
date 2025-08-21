<?php
require_once 'config/connection.php';

// Test script to verify savings goals are properly integrated with budget allocation

echo "=== BUDGET-SAVINGS INTEGRATION TEST ===\n\n";

// Test user ID (you can change this to an actual user ID)
$testUserId = 2;

echo "Testing for User ID: $testUserId\n\n";

// 1. Check if user has budget allocation
$stmt = $conn->prepare("
    SELECT 
        needs_percentage,
        wants_percentage, 
        savings_percentage,
        monthly_salary,
        needs_amount,
        wants_amount,
        savings_amount
    FROM personal_budget_allocation 
    WHERE user_id = ? AND is_active = TRUE
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $testUserId);
$stmt->execute();
$budgetAllocation = $stmt->get_result()->fetch_assoc();

if ($budgetAllocation) {
    echo "✓ Budget Allocation Found:\n";
    echo "  - Monthly Salary: ₵" . number_format($budgetAllocation['monthly_salary'], 2) . "\n";
    echo "  - Needs ({$budgetAllocation['needs_percentage']}%): ₵" . number_format($budgetAllocation['needs_amount'], 2) . "\n";
    echo "  - Wants ({$budgetAllocation['wants_percentage']}%): ₵" . number_format($budgetAllocation['wants_amount'], 2) . "\n";
    echo "  - Savings ({$budgetAllocation['savings_percentage']}%): ₵" . number_format($budgetAllocation['savings_amount'], 2) . "\n\n";
} else {
    echo "✗ No budget allocation found for user\n\n";
}

// 2. Check savings goals
$stmt = $conn->prepare("
    SELECT 
        pg.id,
        pg.goal_name,
        pg.target_amount,
        pg.current_amount,
        pg.goal_type,
        pg.auto_save_enabled,
        pg.save_amount,
        pg.budget_category_id,
        bc.name as category_name,
        bc.category_type,
        bc.budget_limit
    FROM personal_goals pg
    LEFT JOIN budget_categories bc ON pg.budget_category_id = bc.id
    WHERE pg.user_id = ?
    ORDER BY pg.created_at DESC
");
$stmt->bind_param("i", $testUserId);
$stmt->execute();
$goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($goals) {
    echo "✓ Savings Goals Found (" . count($goals) . " goals):\n";
    foreach ($goals as $goal) {
        echo "  Goal: {$goal['goal_name']}\n";
        echo "    - Target: ₵" . number_format($goal['target_amount'], 2) . "\n";
        echo "    - Current: ₵" . number_format($goal['current_amount'], 2) . "\n";
        echo "    - Auto-save: " . ($goal['auto_save_enabled'] ? "Yes (₵" . number_format($goal['save_amount'], 2) . "/month)" : "No") . "\n";
        echo "    - Budget Category: " . ($goal['budget_category_id'] ? "ID {$goal['budget_category_id']} ({$goal['category_name']})" : "Not linked") . "\n";
        echo "    - Category Type: " . ($goal['category_type'] ?: 'N/A') . "\n";
        echo "    - Budget Limit: ₵" . number_format($goal['budget_limit'] ?: 0, 2) . "\n\n";
    }
} else {
    echo "✗ No savings goals found for user\n\n";
}

// 3. Check savings budget categories
$stmt = $conn->prepare("
    SELECT 
        bc.id,
        bc.name,
        bc.category_type,
        bc.budget_limit,
        bc.is_active,
        pg.id as goal_id,
        pg.goal_name
    FROM budget_categories bc
    LEFT JOIN personal_goals pg ON bc.id = pg.budget_category_id
    WHERE bc.user_id = ? AND bc.category_type = 'savings'
    ORDER BY bc.created_at DESC
");
$stmt->bind_param("i", $testUserId);
$stmt->execute();
$savingsCategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($savingsCategories) {
    echo "✓ Savings Budget Categories Found (" . count($savingsCategories) . " categories):\n";
    foreach ($savingsCategories as $cat) {
        echo "  Category: {$cat['name']}\n";
        echo "    - Budget Limit: ₵" . number_format($cat['budget_limit'], 2) . "\n";
        echo "    - Active: " . ($cat['is_active'] ? "Yes" : "No") . "\n";
        echo "    - Linked Goal: " . ($cat['goal_id'] ? "{$cat['goal_name']} (ID: {$cat['goal_id']})" : "None") . "\n\n";
    }
} else {
    echo "✗ No savings budget categories found for user\n\n";
}

// 4. Check current month contributions (actual savings)
$stmt = $conn->prepare("
    SELECT 
        pgc.id,
        pg.goal_name,
        pgc.amount,
        pgc.contribution_date,
        pgc.source,
        pgc.description
    FROM personal_goal_contributions pgc
    JOIN personal_goals pg ON pgc.goal_id = pg.id
    WHERE pg.user_id = ? 
    AND MONTH(pgc.contribution_date) = MONTH(CURRENT_DATE())
    AND YEAR(pgc.contribution_date) = YEAR(CURRENT_DATE())
    ORDER BY pgc.contribution_date DESC
");
$stmt->bind_param("i", $testUserId);
$stmt->execute();
$contributions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($contributions) {
    $totalContributions = array_sum(array_column($contributions, 'amount'));
    echo "✓ This Month's Goal Contributions Found (" . count($contributions) . " contributions):\n";
    echo "  Total Contributed: ₵" . number_format($totalContributions, 2) . "\n";
    foreach ($contributions as $contrib) {
        echo "    - {$contrib['goal_name']}: ₵" . number_format($contrib['amount'], 2) . 
             " on {$contrib['contribution_date']} ({$contrib['source']})\n";
    }
    echo "\n";
} else {
    echo "✗ No goal contributions found for current month\n\n";
}

// 5. Summary and validation
echo "=== INTEGRATION SUMMARY ===\n";

if ($budgetAllocation && $goals) {
    $totalAutoSaveAmount = array_sum(array_filter(array_column($goals, 'save_amount')));
    $savingsAllocation = $budgetAllocation['savings_amount'];
    
    echo "✓ Budget allocation for savings: ₵" . number_format($savingsAllocation, 2) . "\n";
    echo "✓ Total auto-save goals: ₵" . number_format($totalAutoSaveAmount, 2) . "\n";
    
    if ($totalAutoSaveAmount <= $savingsAllocation) {
        $remaining = $savingsAllocation - $totalAutoSaveAmount;
        echo "✓ Goals fit within allocation (₵" . number_format($remaining, 2) . " remaining)\n";
    } else {
        $excess = $totalAutoSaveAmount - $savingsAllocation;
        echo "⚠ Goals exceed allocation by ₵" . number_format($excess, 2) . "\n";
    }
    
    echo "\nIntegration Status: " . (count($savingsCategories) > 0 ? "✓ WORKING" : "✗ NEEDS ATTENTION") . "\n";
    echo "Savings goals are " . (count($savingsCategories) > 0 ? "properly integrated" : "not integrated") . " with budget allocation categories.\n";
    
} else {
    echo "✗ Missing budget allocation or savings goals\n";
    echo "Integration Status: ✗ INCOMPLETE\n";
}

echo "\n=== END TEST ===\n";
?>
