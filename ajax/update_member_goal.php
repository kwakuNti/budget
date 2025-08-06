<?php
// ajax/update_member_goal.php - Update member's monthly contribution goal
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$family_id = $_SESSION['family_id'];
$user_id = $_SESSION['user_id'];
$member_id = isset($input['member_id']) ? (int)$input['member_id'] : 0;
$member_type = isset($input['member_type']) ? $input['member_type'] : 'user';
$new_goal = isset($input['new_goal']) ? (float)$input['new_goal'] : 0;

if ($member_id <= 0 || $new_goal < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    // Check permissions - only admin/head can update goals
    $perm_stmt = $conn->prepare("
        SELECT role FROM family_members 
        WHERE user_id = ? AND family_id = ? AND role IN ('admin', 'head')
    ");
    $perm_stmt->bind_param("ii", $user_id, $family_id);
    $perm_stmt->execute();
    
    if (!$perm_stmt->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to update member goals']);
        exit;
    }
    
    $conn->autocommit(FALSE);
    
    // Update member goal
    if ($member_type === 'user') {
        $update_stmt = $conn->prepare("
            UPDATE family_members 
            SET monthly_contribution_goal = ? 
            WHERE id = ? AND family_id = ?
        ");
        $update_stmt->bind_param("dii", $new_goal, $member_id, $family_id);
    } else {
        $update_stmt = $conn->prepare("
            UPDATE family_members_only 
            SET monthly_contribution_goal = ? 
            WHERE id = ? AND family_id = ?
        ");
        $update_stmt->bind_param("dii", $new_goal, $member_id, $family_id);
    }
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update member goal');
    }
    
    // Update current cycle performance target if exists
    $cycle_update_stmt = $conn->prepare("
        UPDATE member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        SET mmp.target_amount = ?
        WHERE mc.family_id = ? 
        AND mc.is_current = TRUE 
        AND mc.is_closed = FALSE
        AND ((? = 'user' AND mmp.member_id = ?) OR (? = 'member' AND mmp.member_only_id = ?))
    ");
    $cycle_update_stmt->bind_param("dissii", $new_goal, $family_id, $member_type, $member_id, $member_type, $member_id);
    $cycle_update_stmt->execute();
    
    // Update cycle totals
    $total_update_stmt = $conn->prepare("
        UPDATE monthly_cycles mc
        SET mc.total_target = (
            SELECT COALESCE(SUM(mmp.target_amount), 0)
            FROM member_monthly_performance mmp
            WHERE mmp.cycle_id = mc.id
        )
        WHERE mc.family_id = ? AND mc.is_current = TRUE AND mc.is_closed = FALSE
    ");
    $total_update_stmt->bind_param("i", $family_id);
    $total_update_stmt->execute();
    
    // Log the action
    $log_stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at)
        VALUES (?, ?, 'member_goal_updated', ?, NOW())
    ");
    $description = "Updated member goal to ₵" . number_format($new_goal, 2);
    $log_stmt->bind_param("iis", $user_id, $family_id, $description);
    $log_stmt->execute();
    
    $conn->commit();
    $conn->autocommit(TRUE);
    
    echo json_encode([
        'success' => true,
        'message' => 'Member goal updated successfully',
        'new_goal' => $new_goal
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    $conn->autocommit(TRUE);
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
