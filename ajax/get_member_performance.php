<?php
// ajax/get_member_performance.php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$family_id = $_SESSION['family_id'];
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($month < 1 || $month > 12) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid month']);
    exit;
}

try {
    $cycle_month = sprintf('%04d-%02d', $year, $month);
    
    // Get member performance for the specific month
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
            -- Count contributions for this member in this month
            (SELECT COUNT(*) 
             FROM family_contributions fc 
             WHERE fc.family_id = mmp.family_id 
             AND MONTH(fc.contribution_date) = ? 
             AND YEAR(fc.contribution_date) = ?
             AND ((fc.contributor_type = 'user' AND fc.member_id = mmp.member_id) 
                  OR (fc.contributor_type = 'member' AND fc.member_only_id = mmp.member_only_id))
            ) as contribution_count
        FROM member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member'
        WHERE mmp.family_id = ? 
        AND mc.cycle_month = ?
        ORDER BY member_name
    ");
    $stmt->bind_param("iiis", $month, $year, $family_id, $cycle_month);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'members' => $members,
        'month' => $month,
        'year' => $year,
        'cycle_month' => $cycle_month
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>