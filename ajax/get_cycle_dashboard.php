<?php
// ajax/get_cycle_dashboard.php - Dashboard specific data
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$family_id = $_SESSION['family_id'];

try {
    // Get dashboard summary using the view
    $dashboard_stmt = $conn->prepare("
        SELECT * FROM v_family_dashboard_summary WHERE family_id = ?
    ");
    $dashboard_stmt->bind_param("i", $family_id);
    $dashboard_stmt->execute();
    $dashboard = $dashboard_stmt->get_result()->fetch_assoc();
    
    // Get recent transactions (last 10)
    $transactions_stmt = $conn->prepare("
        SELECT 
            fc.amount,
            fc.contribution_date,
            fc.payment_method,
            CASE 
                WHEN fc.contributor_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN fc.contributor_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown'
            END as member_name
        FROM family_contributions fc
        LEFT JOIN family_members fm ON fc.member_id = fm.id AND fc.contributor_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id AND fc.contributor_type = 'member'
        WHERE fc.family_id = ?
        ORDER BY fc.created_at DESC
        LIMIT 10
    ");
    $transactions_stmt->bind_param("i", $family_id);
    $transactions_stmt->execute();
    $recent_transactions = $transactions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'dashboard' => $dashboard,
        'recent_transactions' => $recent_transactions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>