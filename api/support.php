<?php
// api/dashboard.php
session_start();
require_once '../config/connection.php';
require_once '../includes/goal_tracking.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];

try {
    // Auto-create monthly cycle if needed
    autoCreateMonthlyCycleIfNeeded($conn, $family_id);
    
    // Get dashboard data
    $dashboardData = getDashboardData($conn, $family_id);
    
    echo json_encode([
        'success' => true,
        'data' => $dashboardData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard: ' . $e->getMessage()
    ]);
}

function getDashboardData($conn, $family_id) {
    // Get family information
    $familyInfo = getFamilyInfo($conn, $family_id);
    
    // Get current cycle information
    $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
    
    // Get performance summary
    $performanceSummary = getMonthlyPerformanceSummary($conn, $family_id);
    
    // Get member performance details
    $memberPerformance = getMemberPerformanceDetails($conn, $family_id);
    
    // Get recent contributions
    $recentContributions = getRecentContributions($conn, $family_id, 10);
    
    // Get recent expenses
    $recentExpenses = getRecentExpenses($conn, $family_id, 10);
    
    // Get members with debt
    $membersWithDebt = getMembersWithDebt($conn, $family_id);
    
    // Get cycle statistics
    $cycleStats = getCycleStatistics($conn, $family_id, 6);
    
    // Calculate additional metrics
    $metrics = calculateDashboardMetrics($memberPerformance, $currentCycle, $familyInfo);
    
    return [
        'familyInfo' => $familyInfo,
        'currentCycle' => $currentCycle,
        'performanceSummary' => $performanceSummary,
        'memberPerformance' => $memberPerformance,
        'recentContributions' => $recentContributions,
        'recentExpenses' => $recentExpenses,
        'membersWithDebt' => $membersWithDebt,
        'cycleStats' => $cycleStats,
        'metrics' => $metrics
    ];
}

function getFamilyInfo($conn, $family_id) {
    $stmt = $conn->prepare("
        SELECT 
            fg.family_name,
            fg.family_code,
            fg.total_pool,
            fg.monthly_target,
            fg.currency,
            fg.created_at,
            CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
            (SELECT COUNT(*) FROM family_members fm WHERE fm.family_id = fg.id AND fm.is_active = TRUE) +
            (SELECT COUNT(*) FROM family_members_only fmo WHERE fmo.family_id = fg.id AND fmo.is_active = TRUE) as total_members
        FROM family_groups fg
        JOIN users u ON fg.created_by = u.id
        WHERE fg.id = ?
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getRecentContributions($conn, $family_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            fc.amount,
            fc.contribution_date,
            fc.notes,
            fc.payment_method,
            fc.created_at,
            CASE 
                WHEN fc.contributor_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN fc.contributor_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown'
            END as contributor_name,
            fc.contributor_type
        FROM family_contributions fc
        LEFT JOIN family_members fm ON fc.member_id = fm.id AND fc.contributor_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id AND fc.contributor_type = 'member'
        WHERE fc.family_id = ?
        ORDER BY fc.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $family_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getRecentExpenses($conn, $family_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            fe.expense_type,
            fe.amount,
            fe.description,
            fe.expense_date,
            fe.payment_method,
            fe.created_at,
            CASE 
                WHEN fe.payer_type = 'user' AND fm.id IS NOT NULL THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN fe.payer_type = 'member' AND fmo.id IS NOT NULL THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'System'
            END as paid_by_name
        FROM family_expenses fe
        LEFT JOIN family_members fm ON fe.paid_by = fm.id AND fe.payer_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fe.member_only_id = fmo.id AND fe.payer_type = 'member'
        WHERE fe.family_id = ?
        ORDER BY fe.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $family_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function calculateDashboardMetrics($memberPerformance, $currentCycle, $familyInfo) {
    $totalMembers = count($memberPerformance);
    $completedMembers = 0;
    $totalTarget = 0;
    $totalContributed = 0;
    $totalDebt = 0;
    $membersWithDebt = 0;
    
    foreach ($memberPerformance as $member) {
        if ($member['is_completed']) {
            $completedMembers++;
        }
        $totalTarget += floatval($member['target_amount'] ?? 0);
        $totalContributed += floatval($member['contributed_amount'] ?? 0);
        
        $debt = floatval($member['accumulated_debt'] ?? 0);
        if ($debt > 0) {
            $totalDebt += $debt;
            $membersWithDebt++;
        }
    }
    
    $completionRate = $totalMembers > 0 ? round(($completedMembers / $totalMembers) * 100, 2) : 0;
    $contributionRate = $totalTarget > 0 ? round(($totalContributed / $totalTarget) * 100, 2) : 0;
    
    // Calculate days in cycle
    $today = new DateTime();
    $startDate = new DateTime($currentCycle['start_date'] ?? 'now');
    $endDate = new DateTime($currentCycle['end_date'] ?? 'now');
    
    $totalDays = $startDate->diff($endDate)->days + 1;
    $daysElapsed = $startDate->diff($today)->days + 1;
    $daysRemaining = max(0, $today->diff($endDate)->days);
    
    if ($today > $endDate) {
        $daysRemaining = 0;
        $daysElapsed = $totalDays;
    }
    
    $timeProgress = $totalDays > 0 ? round(($daysElapsed / $totalDays) * 100, 2) : 0;
    
    return [
        'totalMembers' => $totalMembers,
        'completedMembers' => $completedMembers,
        'pendingMembers' => $totalMembers - $completedMembers,
        'completionRate' => $completionRate,
        'totalTarget' => $totalTarget,
        'totalContributed' => $totalContributed,
        'contributionRate' => $contributionRate,
        'totalDebt' => $totalDebt,
        'membersWithDebt' => $membersWithDebt,
        'averageContribution' => $totalMembers > 0 ? round($totalContributed / $totalMembers, 2) : 0,
        'poolBalance' => floatval($familyInfo['total_pool'] ?? 0),
        'daysInCycle' => $totalDays,
        'daysElapsed' => $daysElapsed,
        'daysRemaining' => $daysRemaining,
        'timeProgress' => $timeProgress,
        'isOverdue' => $daysRemaining <= 0 && !($currentCycle['is_closed'] ?? false),
        'cycleStatus' => ($currentCycle['is_closed'] ?? false) ? 'closed' : 
                        ($daysRemaining <= 0 ? 'overdue' : 'active')
    ];
}
?>