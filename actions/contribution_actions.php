<?php
// actions/contribution_actions.php - Updated with cycle integration

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
    // Ensure current cycle exists before any contribution
    ensureCurrentCycleExists($conn, $family_id);
    
    switch ($action) {
        case 'add_contribution':
            handleAddContribution($conn, $family_id, $user_id);
            break;
            
        case 'edit_contribution':
            handleEditContribution($conn, $family_id, $user_id);
            break;
            
        case 'delete_contribution':
            handleDeleteContribution($conn, $family_id, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Contribution action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function handleAddContribution($conn, $family_id, $user_id) {
    // Validate input
    $member_name = $_POST['member_name'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $contribution_date = $_POST['contribution_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'momo';
    
    if (empty($member_name) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please provide valid member name and amount']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Find member (check both user members and member-only)
        $member_info = findMemberByName($conn, $family_id, $member_name);
        
        if (!$member_info) {
            throw new Exception('Member not found');
        }
        
        // Insert contribution
        $stmt = $conn->prepare("
            INSERT INTO family_contributions (
                family_id, 
                member_id, 
                member_only_id, 
                contributor_type, 
                amount, 
                contribution_date, 
                payment_method, 
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("iiisdsos", 
            $family_id,
            $member_info['member_id'],
            $member_info['member_only_id'],
            $member_info['type'],
            $amount,
            $contribution_date,
            $payment_method,
            $notes
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add contribution');
        }
        
        // Update member performance in current cycle
        $member_id = $member_info['type'] === 'user' ? $member_info['member_id'] : $member_info['member_only_id'];
        $update_success = updateMemberPerformance($conn, $family_id, $member_id, $member_info['type'], $amount, $contribution_date);
        
        if (!$update_success) {
            throw new Exception('Failed to update member performance');
        }
        
        // Check if this contribution clears any existing debt
        clearDebtIfApplicable($conn, $family_id, $member_id, $member_info['type']);
        
        $conn->commit();
        
        // Log the activity
        logActivity($conn, $user_id, $family_id, 'contribution_added', 
            "Added contribution of ₵{$amount} for {$member_name}");
        
        // Get updated member performance for response
        $current_performance = getCurrentCyclePerformance($conn, $family_id);
        $member_performance = array_filter($current_performance, function($p) use ($member_id, $member_info) {
            return ($member_info['type'] === 'user' && $p['member_id'] == $member_id) ||
                   ($member_info['type'] === 'member' && $p['member_only_id'] == $member_id);
        });
        
        echo json_encode([
            'success' => true,
            'message' => 'Contribution added successfully!',
            'contribution_id' => $conn->insert_id,
            'member_performance' => reset($member_performance) ?: null,
            'amount' => $amount,
            'member_name' => $member_name
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleEditContribution($conn, $family_id, $user_id) {
    $contribution_id = intval($_POST['contribution_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $contribution_date = $_POST['contribution_date'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (!$contribution_id || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid contribution data']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get original contribution
        $stmt = $conn->prepare("
            SELECT * FROM family_contributions 
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("ii", $contribution_id, $family_id);
        $stmt->execute();
        $original = $stmt->get_result()->fetch_assoc();
        
        if (!$original) {
            throw new Exception('Contribution not found');
        }
        
        $amount_difference = $amount - $original['amount'];
        
        // Update contribution
        $stmt = $conn->prepare("
            UPDATE family_contributions 
            SET amount = ?, contribution_date = ?, notes = ?
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("dssii", $amount, $contribution_date, $notes, $contribution_id, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update contribution');
        }
        
        // Update member performance if amount changed
        if ($amount_difference != 0) {
            $member_id = $original['contributor_type'] === 'user' ? $original['member_id'] : $original['member_only_id'];
            updateMemberPerformance($conn, $family_id, $member_id, $original['contributor_type'], $amount_difference, $contribution_date);
        }
        
        // Update family pool
        $stmt = $conn->prepare("UPDATE family_groups SET total_pool = total_pool + ? WHERE id = ?");
        $stmt->bind_param("di", $amount_difference, $family_id);
        $stmt->execute();
        
        // Update member total
        if ($original['contributor_type'] === 'user') {
            $stmt = $conn->prepare("UPDATE family_members SET total_contributed = total_contributed + ? WHERE id = ?");
            $stmt->bind_param("di", $amount_difference, $original['member_id']);
        } else {
            $stmt = $conn->prepare("UPDATE family_members_only SET total_contributed = total_contributed + ? WHERE id = ?");
            $stmt->bind_param("di", $amount_difference, $original['member_only_id']);
        }
        $stmt->execute();
        
        $conn->commit();
        
        logActivity($conn, $user_id, $family_id, 'contribution_edited', 
            "Edited contribution #{$contribution_id} - amount changed by ₵{$amount_difference}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Contribution updated successfully!',
            'amount_difference' => $amount_difference
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleDeleteContribution($conn, $family_id, $user_id) {
    $contribution_id = intval($_POST['contribution_id'] ?? 0);
    
    if (!$contribution_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid contribution ID']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get contribution details
        $stmt = $conn->prepare("
            SELECT * FROM family_contributions 
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("ii", $contribution_id, $family_id);
        $stmt->execute();
        $contribution = $stmt->get_result()->fetch_assoc();
        
        if (!$contribution) {
            throw new Exception('Contribution not found');
        }
        
        // Delete contribution
        $stmt = $conn->prepare("DELETE FROM family_contributions WHERE id = ? AND family_id = ?");
        $stmt->bind_param("ii", $contribution_id, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete contribution');
        }
        
        // Reverse the contribution effects
        $member_id = $contribution['contributor_type'] === 'user' ? $contribution['member_id'] : $contribution['member_only_id'];
        updateMemberPerformance($conn, $family_id, $member_id, $contribution['contributor_type'], -$contribution['amount'], $contribution['contribution_date']);
        
        // Update family pool
        $stmt = $conn->prepare("UPDATE family_groups SET total_pool = total_pool - ? WHERE id = ?");
        $stmt->bind_param("di", $contribution['amount'], $family_id);
        $stmt->execute();
        
        // Update member total
        if ($contribution['contributor_type'] === 'user') {
            $stmt = $conn->prepare("UPDATE family_members SET total_contributed = total_contributed - ? WHERE id = ?");
            $stmt->bind_param("di", $contribution['amount'], $contribution['member_id']);
        } else {
            $stmt = $conn->prepare("UPDATE family_members_only SET total_contributed = total_contributed - ? WHERE id = ?");
            $stmt->bind_param("di", $contribution['amount'], $contribution['member_only_id']);
        }
        $stmt->execute();
        
        $conn->commit();
        
        logActivity($conn, $user_id, $family_id, 'contribution_deleted', 
            "Deleted contribution of ₵{$contribution['amount']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Contribution deleted successfully!',
            'deleted_amount' => $contribution['amount']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Helper functions

function findMemberByName($conn, $family_id, $member_name) {
    // First check registered users
    $stmt = $conn->prepare("
        SELECT fm.id as member_id, NULL as member_only_id, 'user' as type,
               CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        WHERE fm.family_id = ? AND CONCAT(u.first_name, ' ', u.last_name) = ?
    ");
    $stmt->bind_param("is", $family_id, $member_name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        return $result;
    }
    
    // Check non-registered members
    $stmt = $conn->prepare("
        SELECT NULL as member_id, id as member_only_id, 'member' as type,
               CONCAT(first_name, ' ', last_name) as full_name
        FROM family_members_only
        WHERE family_id = ? AND CONCAT(first_name, ' ', last_name) = ?
    ");
    $stmt->bind_param("is", $family_id, $member_name);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function clearDebtIfApplicable($conn, $family_id, $member_id, $member_type) {
    // Get current member performance
    $current_cycle = getCurrentCycle($conn, $family_id);
    if (!$current_cycle) return;
    
    $member_field = ($member_type === 'user') ? 'member_id' : 'member_only_id';
    
    $stmt = $conn->prepare("
        SELECT * FROM member_monthly_performance 
        WHERE cycle_id = ? AND {$member_field} = ? AND is_completed = TRUE
    ");
    $stmt->bind_param("ii", $current_cycle['id'], $member_id);
    $stmt->execute();
    $performance = $stmt->get_result()->fetch_assoc();
    
    // If member completed their goal this month, clear their oldest debt
    if ($performance) {
        $stmt = $conn->prepare("
            SELECT id FROM member_debt_history 
            WHERE family_id = ? AND {$member_field} = ? AND is_cleared = FALSE
            ORDER BY cycle_month ASC
            LIMIT 1
        ");
        $stmt->bind_param("ii", $family_id, $member_id);
        $stmt->execute();
        $debt = $stmt->get_result()->fetch_assoc();
        
        if ($debt) {
            clearMemberDebt($conn, $debt['id'], $current_cycle['id']);
            
            // Update member's accumulated debt
            $table = ($member_type === 'user') ? 'family_members' : 'family_members_only';
            $stmt = $conn->prepare("
                UPDATE {$table} 
                SET accumulated_debt = (
                    SELECT COALESCE(SUM(deficit_amount), 0) 
                    FROM member_debt_history 
                    WHERE {$member_field} = ? AND is_cleared = FALSE
                ),
                months_behind = (
                    SELECT COUNT(*) 
                    FROM member_debt_history 
                    WHERE {$member_field} = ? AND is_cleared = FALSE
                )
                WHERE id = ?
            ");
            $stmt->bind_param("iii", $member_id, $member_id, $member_id);
            $stmt->execute();
        }
    }
}

function logActivity($conn, $user_id, $family_id, $action_type, $description) {
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiss", $user_id, $family_id, $action_type, $description);
    $stmt->execute();
}
?>