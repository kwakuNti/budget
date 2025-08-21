<?php
session_start();
$_SESSION['user_id'] = 2;

require_once 'config/connection.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

try {
    // Get budget allocation
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
        
        // Use auto-save amount as monthly planned if available, otherwise use target/12
        $monthlyPlanned = $autoSaveAmount > 0 ? $autoSaveAmount : 
            ($targetAmount > 0 ? $targetAmount / 12 : floatval($row['budget_limit']));
        
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

    echo json_encode([
        'success' => true,
        'data' => [
            'budget_allocation' => $budgetAllocation,
            'savings_categories' => $savingsCategories,
            'savings_summary' => [
                'total_planned' => $totalPlannedSavings,
                'total_actual' => $totalActualSavings,
                'count' => count($savingsCategories)
            ]
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
