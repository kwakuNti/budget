<?php
// ajax/get_cycle_transactions.php
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
    // Get cycle details first
    $cycle_stmt = $conn->prepare("
        SELECT cycle_month, cycle_year, start_date, end_date 
        FROM monthly_cycles 
        WHERE id = ? AND family_id = ?
    ");
    $cycle_stmt->bind_param("ii", $cycle_id, $family_id);
    $cycle_stmt->execute();
    $cycle = $cycle_stmt->get_result()->fetch_assoc();
    
    if (!$cycle) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Get all contributions for this cycle period
    $transactions_stmt = $conn->prepare("
        SELECT 
            fc.*,
            CASE 
                WHEN fc.contributor_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN fc.contributor_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown'
            END as member_name,
            CASE 
                WHEN fc.contributor_type = 'user' THEN u.phone_number
                WHEN fc.contributor_type = 'member' THEN fmo.phone_number
                ELSE NULL
            END as phone_number
        FROM family_contributions fc
        LEFT JOIN family_members fm ON fc.member_id = fm.id AND fc.contributor_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id AND fc.contributor_type = 'member'
        WHERE fc.family_id = ? 
        AND fc.contribution_date >= ? 
        AND fc.contribution_date <= ?
        ORDER BY fc.contribution_date DESC, fc.created_at DESC
    ");
    $transactions_stmt->bind_param("iss", $family_id, $cycle['start_date'], $cycle['end_date']);
    $transactions_stmt->execute();
    $transactions = $transactions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'cycle' => $cycle,
        'cycle_id' => $cycle_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>