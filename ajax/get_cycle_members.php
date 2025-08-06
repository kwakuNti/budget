<?php
// ajax/get_cycle_members.php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$family_id = $_SESSION['family_id'];
$cycle_id = isset($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : 0;

if ($cycle_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid cycle ID']);
    exit;
}

try {
    // Verify cycle belongs to family
    $verify_stmt = $conn->prepare("SELECT id FROM monthly_cycles WHERE id = ? AND family_id = ?");
    $verify_stmt->bind_param("ii", $cycle_id, $family_id);
    $verify_stmt->execute();
    if (!$verify_stmt->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Get cycle member details
    $stmt = $conn->prepare("
        SELECT 
            mmp.*,
            CASE 
                WHEN mmp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN mmp.member_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown'
            END as member_name,
            CASE 
                WHEN mmp.member_type = 'user' THEN u.phone_number
                WHEN mmp.member_type = 'member' THEN fmo.phone_number
                ELSE NULL
            END as phone_number,
            CASE 
                WHEN mmp.member_type = 'user' THEN fm.role
                WHEN mmp.member_type = 'member' THEN fmo.role
                ELSE 'member'
            END as role,
            mc.cycle_month,
            mc.cycle_year
        FROM member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member'
        WHERE mmp.cycle_id = ?
        ORDER BY member_name
    ");
    $stmt->bind_param("i", $cycle_id);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'members' => $members,
        'cycle_id' => $cycle_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
