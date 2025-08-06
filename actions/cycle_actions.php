<?php
// actions/cycle_actions.php - Handle cycle management AJAX requests

session_start();
require_once '../config/connection.php';
require_once '../includes/cycle_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'close_cycle':
            handleCloseCycle($conn, $user_id, $family_id);
            break;
            
        case 'get_cycle_status':
            handleGetCycleStatus($conn, $family_id);
            break;
            
        case 'get_member_performance':
            handleGetMemberPerformance($conn, $family_id);
            break;
            
        case 'get_debt_info':
            handleGetDebtInfo($conn, $family_id);
            break;
            
        case 'clear_debt':
            handleClearDebt($conn, $_POST['debt_id'] ?? 0);
            break;
            
        case 'force_create_cycle':
            handleForceCreateCycle($conn, $family_id, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Cycle action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function handleCloseCycle($conn, $user_id, $family_id) {
    // Check permissions
    if (!canCloseCycle($conn, $user_id, $family_id)) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to close cycles']);
        return;
    }
    
    // Get current cycle
    $current_cycle = getCurrentCycle($conn, $family_id);
    if (!$current_cycle) {
        echo json_encode(['success' => false, 'message' => 'No active cycle found']);
        return;
    }
    
    // Close the cycle
    $success = closeMonthlyCycle($conn, $current_cycle['id'], $user_id);
    
    if ($success) {
        // Get summary of what happened
        $debt_info = getMemberDebtInfo($conn, $family_id);
        $new_cycle = getCurrentCycle($conn, $family_id);
        
        // Log the action
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at)
            VALUES (?, ?, 'cycle_closed', ?, NOW())
        ");
        $description = "Monthly cycle {$current_cycle['cycle_month']} closed manually";
        $stmt->bind_param("iis", $user_id, $family_id, $description);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cycle closed successfully',
            'closed_cycle' => $current_cycle,
            'new_cycle' => $new_cycle,
            'debts_created' => count($debt_info),
            'debt_info' => $debt_info
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to close cycle']);
    }
}

function handleGetCycleStatus($conn, $family_id) {
    // Ensure current cycle exists
    $current_cycle = ensureCurrentCycleExists($conn, $family_id);
    
    if (!$current_cycle) {
        echo json_encode(['success' => false, 'message' => 'Could not create or find current cycle']);
        return;
    }
    
    $performance = getCurrentCyclePerformance($conn, $family_id);
    $debt_summary = getFamilyDebtSummary($conn, $family_id);
    
    // Calculate additional stats
    $days_remaining = max(0, (strtotime($current_cycle['end_date']) - time()) / (24 * 60 * 60));
    $completion_rate = $current_cycle['total_target'] > 0 
        ? round(($current_cycle['total_collected'] / $current_cycle['total_target']) * 100, 2) 
        : 0;
    
    echo json_encode([
        'success' => true,
        'cycle' => $current_cycle,
        'performance' => $performance,
        'debt_summary' => $debt_summary,
        'stats' => [
            'days_remaining' => floor($days_remaining),
            'completion_rate' => $completion_rate,
            'total_members' => count($performance),
            'completed_members' => count(array_filter($performance, function($p) { return $p['is_completed']; })),
            'behind_members' => count(array_filter($performance, function($p) { return $p['accumulated_debt'] > 0; }))
        ]
    ]);
}

function handleGetMemberPerformance($conn, $family_id) {
    $performance = getCurrentCyclePerformance($conn, $family_id);
    
    // Add additional calculations
    foreach ($performance as &$member) {
        $member['remaining_amount'] = max(0, $member['target_amount'] - $member['contributed_amount']);
        $member['status'] = $member['is_completed'] ? 'completed' : 
            ($member['contributed_amount'] > 0 ? 'partial' : 'not_started');
        $member['debt_status'] = $member['accumulated_debt'] > 0 ? 'has_debt' : 'clear';
    }
    
    echo json_encode([
        'success' => true,
        'performance' => $performance
    ]);
}

function handleGetDebtInfo($conn, $family_id) {
    $member_id = $_POST['member_id'] ?? null;
    $member_type = $_POST['member_type'] ?? 'user';
    
    $debts = getMemberDebtInfo($conn, $family_id, $member_id, $member_type);
    $summary = getFamilyDebtSummary($conn, $family_id);
    
    echo json_encode([
        'success' => true,
        'debts' => $debts,
        'summary' => $summary
    ]);
}

function handleClearDebt($conn, $debt_id) {
    if (!$debt_id) {
        echo json_encode(['success' => false, 'message' => 'Debt ID required']);
        return;
    }
    
    $success = clearMemberDebt($conn, $debt_id);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Debt cleared successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear debt']);
    }
}

function handleForceCreateCycle($conn, $family_id, $user_id) {
    // Check permissions
    if (!canCloseCycle($conn, $user_id, $family_id)) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to create cycles']);
        return;
    }
    
    $year = $_POST['year'] ?? date('Y');
    $month = $_POST['month'] ?? date('n');
    
    $success = createMonthlyCycle($conn, $family_id, $year, $month);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Cycle created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create cycle']);
    }
}
?>