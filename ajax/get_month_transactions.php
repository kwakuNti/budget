<?php
// ajax/get_month_transactions.php
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
    // Get contributions for the month
    $contributions_stmt = $conn->prepare("
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
        AND MONTH(fc.contribution_date) = ? 
        AND YEAR(fc.contribution_date) = ?
        ORDER BY fc.contribution_date DESC, fc.created_at DESC
    ");
    $contributions_stmt->bind_param("iii", $family_id, $month, $year);
    $contributions_stmt->execute();
    $contributions = $contributions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get expenses for the month
    $expenses_stmt = $conn->prepare("
        SELECT 
            fe.*,
            CASE 
                WHEN fe.payer_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN fe.payer_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Family Fund'
            END as paid_by_name
        FROM family_expenses fe
        LEFT JOIN family_members fm ON fe.paid_by = fm.id AND fe.payer_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fe.member_only_id = fmo.id AND fe.payer_type = 'member'
        WHERE fe.family_id = ? 
        AND MONTH(fe.expense_date) = ? 
        AND YEAR(fe.expense_date) = ?
        ORDER BY fe.expense_date DESC, fe.created_at DESC
    ");
    $expenses_stmt->bind_param("iii", $family_id, $month, $year);
    $expenses_stmt->execute();
    $expenses = $expenses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'contributions' => $contributions,
        'expenses' => $expenses,
        'month' => $month,
        'year' => $year
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

