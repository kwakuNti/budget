<?php
/**
 * Debt Management API
 * Handles debt tracking, display, and payment processing
 */

session_start();
require_once '../config/connection.php';
require_once '../includes/debt_functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$familyId = $_SESSION['family_id'];
$userId = $_SESSION['user_id'];

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_debts':
            // Get outstanding debts for the family
            $debts = getOutstandingDebts($conn, $familyId);
            $summary = getFamilyDebtSummary($conn, $familyId);
            
            echo json_encode([
                'success' => true,
                'debts' => $debts,
                'summary' => $summary
            ]);
            break;
            
        case 'get_member_debt':
            $memberId = $_GET['member_id'] ?? 0;
            if (!$memberId) {
                throw new Exception('Member ID required');
            }
            
            $memberDebt = getMemberTotalDebt($conn, $memberId);
            $memberDebts = getOutstandingDebts($conn, $familyId);
            $memberDebts = array_filter($memberDebts, function($debt) use ($memberId) {
                return $debt['member_id'] == $memberId;
            });
            
            echo json_encode([
                'success' => true,
                'total_debt' => $memberDebt['total_debt'],
                'debt_count' => $memberDebt['debt_count'],
                'debt_details' => array_values($memberDebts)
            ]);
            break;
            
        case 'process_payment':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $memberId = $input['member_id'] ?? 0;
            $amount = floatval($input['amount'] ?? 0);
            $paymentType = $input['payment_type'] ?? 'contribution'; // 'debt_only', 'auto_deduct', 'contribution'
            
            if (!$memberId || $amount <= 0) {
                throw new Exception('Invalid member ID or amount');
            }
            
            $result = processContributionWithDebtOptions($conn, $memberId, $amount, $paymentType, $familyId);
            
            if ($result['success']) {
                // If this was a contribution, record it in family_contributions
                if ($result['contribution_made'] > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO family_contributions (
                            family_id, member_only_id, amount, contribution_date, description
                        ) VALUES (?, ?, ?, CURDATE(), ?)
                    ");
                    $description = "Monthly contribution";
                    if ($result['debt_paid'] > 0) {
                        $description .= " (₵{$result['debt_paid']} applied to debt)";
                    }
                    $stmt->bind_param("iids", $familyId, $memberId, $result['contribution_made'], $description);
                    $stmt->execute();
                    
                    // Update member's current month contribution
                    $stmt = $conn->prepare("
                        UPDATE family_members_only 
                        SET current_month_contributed = current_month_contributed + ?,
                            total_contributed = total_contributed + ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ddi", $result['contribution_made'], $result['contribution_made'], $memberId);
                    $stmt->execute();
                }
                
                // Log the activity
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs (family_id, action_type, description, created_at)
                    VALUES (?, 'debt_payment', ?, NOW())
                ");
                $stmt->bind_param("is", $familyId, $result['message']);
                $stmt->execute();
            }
            
            echo json_encode($result);
            break;
            
        case 'clear_debt':
            $debtId = $_POST['debt_id'] ?? 0;
            if (!$debtId) {
                throw new Exception('Debt ID required');
            }
            
            // Mark debt as cleared (manual override)
            $stmt = $conn->prepare("
                UPDATE member_debt_history 
                SET is_cleared = 1, cleared_date = CURDATE(), notes = CONCAT(notes, ' - Manually cleared')
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("ii", $debtId, $familyId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Debt cleared successfully']);
            } else {
                throw new Exception('Debt not found or already cleared');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
