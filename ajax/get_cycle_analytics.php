<?php
// ajax/get_cycle_analytics.php - Get detailed analytics for cycles
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$family_id = $_SESSION['family_id'];
$months = isset($_GET['months']) ? (int)$_GET['months'] : 6;

try {
    // Get cycle performance over time
    $performance_stmt = $conn->prepare("
        SELECT 
            mc.cycle_month,
            mc.cycle_year,
            mc.cycle_month_num,
            mc.total_target,
            mc.total_collected,
            mc.members_completed,
            mc.members_pending,
            mc.is_closed,
            CASE 
                WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
                ELSE 0 
            END as completion_percentage,
            (SELECT COUNT(*) FROM member_debt_history mdh 
             WHERE mdh.family_id = mc.family_id 
             AND mdh.cycle_month = mc.cycle_month) as debts_created
        FROM monthly_cycles mc
        WHERE mc.family_id = ?
        ORDER BY mc.cycle_year DESC, mc.cycle_month_num DESC
        LIMIT ?
    ");
    $performance_stmt->bind_param("ii", $family_id, $months);
    $performance_stmt->execute();
    $cycle_performance = $performance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get member performance trends
    $member_trends_stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN mmp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN mmp.member_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown'
            END as member_name,
            mc.cycle_month,
            mmp.target_amount,
            mmp.contributed_amount,
            mmp.is_completed,
            CASE 
                WHEN mmp.target_amount > 0 THEN ROUND((mmp.contributed_amount / mmp.target_amount) * 100, 2)
                ELSE 0 
            END as completion_percentage
        FROM member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member'
        WHERE mc.family_id = ?
        AND mc.cycle_year >= YEAR(DATE_SUB(CURDATE(), INTERVAL ? MONTH))
        ORDER BY member_name, mc.cycle_year DESC, mc.cycle_month_num DESC
    ");
    $member_trends_stmt->bind_param("ii", $family_id, $months);
    $member_trends_stmt->execute();
    $member_trends = $member_trends_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get debt trends
    $debt_trends_stmt = $conn->prepare("
        SELECT 
            cycle_month,
            COUNT(*) as total_debts,
            SUM(deficit_amount) as total_deficit,
            COUNT(CASE WHEN is_cleared = TRUE THEN 1 END) as cleared_debts,
            COUNT(CASE WHEN is_cleared = FALSE THEN 1 END) as active_debts
        FROM member_debt_history
        WHERE family_id = ?
        AND STR_TO_DATE(CONCAT(cycle_month, '-01'), '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        GROUP BY cycle_month
        ORDER BY cycle_month DESC
    ");
    $debt_trends_stmt->bind_param("ii", $family_id, $months);
    $debt_trends_stmt->execute();
    $debt_trends = $debt_trends_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate summary statistics
    $avg_completion = count($cycle_performance) > 0 
        ? array_sum(array_column($cycle_performance, 'completion_percentage')) / count($cycle_performance)
        : 0;
    
    $total_collected = array_sum(array_column($cycle_performance, 'total_collected'));
    $total_target = array_sum(array_column($cycle_performance, 'total_target'));
    
    echo json_encode([
        'success' => true,
        'analytics' => [
            'cycle_performance' => $cycle_performance,
            'member_trends' => $member_trends,
            'debt_trends' => $debt_trends,
            'summary' => [
                'average_completion_rate' => round($avg_completion, 2),
                'total_collected' => $total_collected,
                'total_target' => $total_target,
                'cycles_analyzed' => count($cycle_performance),
                'overall_completion' => $total_target > 0 ? round(($total_collected / $total_target) * 100, 2) : 0
            ]
        ],
        'period' => [
            'months' => $months,
            'from' => date('Y-m', strtotime("-{$months} months")),
            'to' => date('Y-m')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>