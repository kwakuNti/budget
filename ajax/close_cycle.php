<?php
// ajax/close_cycle.php
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
$cycle_id = isset($input['cycle_id']) ? (int)$input['cycle_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($cycle_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid cycle ID']);
    exit;
}

try {
    $conn->autocommit(FALSE);

    // Verify cycle belongs to family and user has admin rights
    $verify_stmt = $conn->prepare("
        SELECT mc.id, mc.is_closed, fm.role 
        FROM monthly_cycles mc
        JOIN family_members fm ON mc.family_id = fm.family_id
        JOIN users u ON fm.user_id = u.id
        WHERE mc.id = ? AND mc.family_id = ? AND u.id = ?
        AND fm.role IN ('admin', 'head')
    ");
    $verify_stmt->bind_param("iii", $cycle_id, $family_id, $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        $conn->rollback();
        http_response_code(403);
        echo json_encode(['error' => 'Access denied or cycle not found']);
        exit;
    }

    if ($result['is_closed']) {
        $conn->rollback();
        http_response_code(400);
        echo json_encode(['error' => 'Cycle is already closed']);
        exit;
    }

    // Call the stored procedure to close the cycle
    $close_stmt = $conn->prepare("CALL CloseMonthlyCycle(?, ?)");
    $close_stmt->bind_param("ii", $cycle_id, $user_id);
    $close_stmt->execute();

    // Log the activity
    $log_stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at) 
        VALUES (?, ?, 'cycle_closed', ?, NOW())
    ");
    $description = "Monthly cycle {$cycle_id} was closed";
    $log_stmt->bind_param("iis", $user_id, $family_id, $description);
    $log_stmt->execute();

    $conn->commit();
    $conn->autocommit(TRUE);

    echo json_encode([
        'success' => true,
        'message' => 'Cycle closed successfully',
        'cycle_id' => $cycle_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->autocommit(TRUE);
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>