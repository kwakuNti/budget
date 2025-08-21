<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get current budget allocation
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
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $budgetAllocation = $stmt->get_result()->fetch_assoc();

    // Get budget categories with spending data (EXCLUDE savings categories)
    $stmt = $conn->prepare("
        SELECT 
            bc.id,
            bc.name,
            bc.category_type,
            bc.icon,
            bc.color,
            bc.budget_limit,
            COALESCE(SUM(pe.amount), 0) as actual_spent,
            COUNT(pe.id) as transaction_count
        FROM budget_categories bc
        LEFT JOIN personal_expenses pe ON bc.id = pe.category_id 
            AND pe.user_id = ? 
            AND MONTH(pe.expense_date) = MONTH(CURRENT_DATE())
            AND YEAR(pe.expense_date) = YEAR(CURRENT_DATE())
        WHERE bc.user_id = ? AND bc.is_active = TRUE
            AND bc.category_type != 'savings'
        GROUP BY bc.id, bc.name, bc.category_type, bc.icon, bc.color, bc.budget_limit
        ORDER BY bc.category_type, bc.name
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $categoriesResult = $stmt->get_result();
    
    $categories = [];
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'category_type' => $row['category_type'],
            'icon' => $row['icon'],
            'color' => $row['color'],
            'budget_limit' => floatval($row['budget_limit']),
            'actual_spent' => floatval($row['actual_spent']),
            'transaction_count' => intval($row['transaction_count']),
            'variance' => floatval($row['budget_limit']) - floatval($row['actual_spent']),
            'progress_percentage' => $row['budget_limit'] > 0 ? 
                min(100, (floatval($row['actual_spent']) / floatval($row['budget_limit'])) * 100) : 0
        ];
    }

    // Get total monthly income (check both salary and budget allocation)
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(pba.monthly_salary, s.monthly_salary, 0) as allocation_income,
            COALESCE(s.monthly_salary, 0) as salary_income,
            COALESCE(SUM(pis.monthly_amount), 0) as additional_income
        FROM users u
        LEFT JOIN personal_budget_allocation pba ON u.id = pba.user_id AND pba.is_active = TRUE
        LEFT JOIN salaries s ON u.id = s.user_id AND s.is_active = TRUE
        LEFT JOIN personal_income_sources pis ON u.id = pis.user_id AND pis.is_active = TRUE AND pis.include_in_budget = TRUE
        WHERE u.id = ?
        GROUP BY u.id, pba.monthly_salary, s.monthly_salary
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $incomeResult = $stmt->get_result()->fetch_assoc();
    
    // Calculate total monthly income
    $salaryIncome = floatval($incomeResult['salary_income'] ?? 0);
    $additionalIncome = floatval($incomeResult['additional_income'] ?? 0);
    $totalMonthlyIncome = $salaryIncome + $additionalIncome;
    
    // If we have allocation data but it's different from calculated income, use the higher value
    $allocationIncome = floatval($incomeResult['allocation_income'] ?? 0);
    if ($allocationIncome > $totalMonthlyIncome) {
        $totalMonthlyIncome = $allocationIncome;
    }

    // Calculate summary statistics (expenses only, savings handled separately)
    $totalPlanned = array_sum(array_column($categories, 'budget_limit'));
    $totalActual = array_sum(array_column($categories, 'actual_spent'));
    $totalVariance = $totalPlanned - $totalActual;
    $budgetPerformance = $totalPlanned > 0 ? round(($totalActual / $totalPlanned) * 100, 1) : 0;

    // Get savings categories (goals) with their contributions
    $stmt = $conn->prepare("
        SELECT 
            bc.id,
            bc.name,
            bc.category_type,
            bc.icon,
            bc.color,
            bc.budget_limit,
            pg.id as goal_id,
            pg.current_amount,
            pg.target_amount,
            pg.auto_save_enabled,
            pg.save_amount,
            COALESCE(SUM(pgc.amount), 0) as actual_contributed,
            COUNT(pgc.id) as contribution_count
        FROM budget_categories bc
        LEFT JOIN personal_goals pg ON bc.id = pg.budget_category_id AND pg.user_id = ?
        LEFT JOIN personal_goal_contributions pgc ON pg.id = pgc.goal_id 
            AND MONTH(pgc.contribution_date) = MONTH(CURRENT_DATE())
            AND YEAR(pgc.contribution_date) = YEAR(CURRENT_DATE())
        WHERE bc.user_id = ? AND bc.is_active = TRUE
            AND bc.category_type = 'savings'
        GROUP BY bc.id, bc.name, bc.category_type, bc.icon, bc.color, bc.budget_limit, 
                 pg.id, pg.current_amount, pg.target_amount, pg.auto_save_enabled, pg.save_amount
        ORDER BY bc.name
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $savingsResult = $stmt->get_result();
    
    $savingsCategories = [];
    $totalActualSavings = 0;
    $totalPlannedSavings = 0;
    
    while ($row = $savingsResult->fetch_assoc()) {
        $actualContributed = floatval($row['actual_contributed']);
        $autoSaveAmount = floatval($row['save_amount'] ?? 0);
        $targetAmount = floatval($row['target_amount'] ?? 0);
        
        // Use auto-save amount as monthly planned if available, otherwise don't set a monthly target
        $monthlyPlanned = $autoSaveAmount > 0 ? $autoSaveAmount : 0;
        
        $savingsCategories[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'category_type' => $row['category_type'],
            'icon' => $row['icon'],
            'color' => $row['color'],
            'budget_limit' => $monthlyPlanned,
            'actual_spent' => $actualContributed, // Using 'spent' for consistency, but it's actually saved
            'transaction_count' => intval($row['contribution_count']),
            'variance' => $monthlyPlanned - $actualContributed,
            'progress_percentage' => $monthlyPlanned > 0 ? 
                min(100, ($actualContributed / $monthlyPlanned) * 100) : 0,
            'goal_id' => $row['goal_id'],
            'current_amount' => floatval($row['current_amount'] ?? 0),
            'target_amount' => $targetAmount,
            'auto_save_enabled' => boolval($row['auto_save_enabled'] ?? false),
            'auto_save_amount' => $autoSaveAmount
        ];
        
        $totalActualSavings += $actualContributed;
        $totalPlannedSavings += $monthlyPlanned;
    }

    // Get total actual savings from goal contributions (not expenses)
    $actualSavings = $totalActualSavings;

    // Get planned savings from budget allocation (fallback if no specific goals)
    $plannedSavings = $budgetAllocation ? floatval($budgetAllocation['savings_amount']) : $totalPlannedSavings;

    // Group categories by type (including savings goals as budget categories)
    $categoriesByType = [
        'needs' => array_values(array_filter($categories, fn($cat) => $cat['category_type'] === 'needs')),
        'wants' => array_values(array_filter($categories, fn($cat) => $cat['category_type'] === 'wants')),
        'savings' => array_values($savingsCategories) // Ensure it's an indexed array
    ];

    // Calculate category type totals
    $categoryTypeTotals = [];
    foreach ($categoriesByType as $type => $cats) {
        if ($type === 'savings') {
            // Handle savings using actual goal data
            $categoryTypeTotals[$type] = [
                'planned' => $totalPlannedSavings > 0 ? $totalPlannedSavings : $plannedSavings,
                'actual' => $actualSavings,
                'count' => count($savingsCategories)
            ];
        } else {
            $categoryTypeTotals[$type] = [
                'planned' => array_sum(array_column($cats, 'budget_limit')),
                'actual' => array_sum(array_column($cats, 'actual_spent')),
                'count' => count($cats)
            ];
        }
        $categoryTypeTotals[$type]['variance'] = $categoryTypeTotals[$type]['planned'] - $categoryTypeTotals[$type]['actual'];
        $categoryTypeTotals[$type]['progress_percentage'] = $categoryTypeTotals[$type]['planned'] > 0 ? 
            round(($categoryTypeTotals[$type]['actual'] / $categoryTypeTotals[$type]['planned']) * 100, 1) : 0;
    }

    echo json_encode([
        'success' => true,
        'budget_allocation' => $budgetAllocation,
        'total_monthly_income' => $totalMonthlyIncome,
        'categories' => $categories,
        'categories_by_type' => $categoriesByType,
        'category_type_totals' => $categoryTypeTotals,
        'savings_data' => [
            'planned_savings' => $plannedSavings,
            'actual_savings' => $actualSavings,
            'savings_variance' => $plannedSavings - $actualSavings,
            'savings_percentage' => $plannedSavings > 0 ? round(($actualSavings / $plannedSavings) * 100, 1) : 0
        ],
        'summary' => [
            'total_planned' => $totalPlanned,
            'total_actual' => $totalActual,
            'total_variance' => $totalVariance,
            'budget_performance' => $budgetPerformance,
            'remaining_budget' => $totalPlanned - $totalActual,
            'available_balance' => $totalMonthlyIncome - $totalActual - $actualSavings, // Corrected calculation
            'income_utilization' => $totalMonthlyIncome > 0 ? round(($totalPlanned / $totalMonthlyIncome) * 100, 1) : 0
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving budget data: ' . $e->getMessage()
    ]);
}
?>
