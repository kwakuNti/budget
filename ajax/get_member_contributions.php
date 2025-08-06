<?php
// ajax/get_member_contributions.php - Get contributions for a specific member
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$family_id = $_SESSION['family_id'];
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$member_type = isset($_GET['member_type']) ? $_GET['member_type'] : 'user';
$cycle_id = isset($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : 0;

if ($member_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid member ID']);
    exit;
}

try {
    // Get cycle info if specified
    $cycle_condition = "";
    $params = [$family_id];
    $param_types = "i";
    
    if ($cycle_id > 0) {
        $cycle_stmt = $conn->prepare("
            SELECT start_date, end_date FROM monthly_cycles 
            WHERE id = ? AND family_id = ?
        ");
        $cycle_stmt->bind_param("ii", $cycle_id, $family_id);
        $cycle_stmt->execute();
        $cycle = $cycle_stmt->get_result()->fetch_assoc();
        
        if ($cycle) {
            $cycle_condition = " AND fc.contribution_date >= ? AND fc.contribution_date <= ?";
            $params[] = $cycle['start_date'];
            $params[] = $cycle['end_date'];
            $param_types .= "ss";
        }
    }
    
    // Build query based on member type
    if ($member_type === 'user') {
        $member_condition = " AND fc.member_id = ? AND fc.contributor_type = 'user'";
        $params[] = $member_id;
        $param_types .= "i";
    } else {
        $member_condition = " AND fc.member_only_id = ? AND fc.contributor_type = 'member'";
        $params[] = $member_id;
        $param_types .= "i";
    }
    
    $contributions_stmt = $conn->prepare("
        SELECT 
            fc.*,
            CASE 
                WHEN fc.contributor_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN fc.contributor_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown'
            END as member_name
        FROM family_contributions fc
        LEFT JOIN family_members fm ON fc.member_id = fm.id AND fc.contributor_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id AND fc.contributor_type = 'member'
        WHERE fc.family_id = ?" . $cycle_condition . $member_condition . "
        ORDER BY fc.contribution_date DESC, fc.created_at DESC
    ");
    
    $contributions_stmt->bind_param($param_types, ...$params);
    $contributions_stmt->execute();
    $contributions = $contributions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate totals
    $total_amount = array_sum(array_column($contributions, 'amount'));
    $total_count = count($contributions);
    
    echo json_encode([
        'success' => true,
        'contributions' => $contributions,
        'summary' => [
            'total_amount' => $total_amount,
            'total_count' => $total_count,
            'member_id' => $member_id,
            'member_type' => $member_type,
            'cycle_id' => $cycle_id
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>