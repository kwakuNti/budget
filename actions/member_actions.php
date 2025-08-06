<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['family_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$family_id = $_SESSION['family_id'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_member':
                addMember($conn, $family_id, $user_id);
                break;
                
            case 'edit_member':
                editMember($conn, $family_id, $user_id);
                break;
                
            case 'delete_member':
                deleteMember($conn, $family_id, $user_id);
                break;
                
            case 'toggle_member_status':
                toggleMemberStatus($conn, $family_id, $user_id);
                break;
                
            case 'bulk_update_goals':
                bulkUpdateGoals($conn, $family_id, $user_id);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function addMember($conn, $family_id, $user_id) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $goal = floatval($_POST['monthly_goal'] ?? 0);
    $momoNetwork = $_POST['momo_network'] ?? null;
    $momoNumber = trim($_POST['momo_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($phone) || empty($role)) {
        throw new Exception('Please fill in all required fields');
    }
    
    if ($goal < 0) {
        throw new Exception('Monthly goal cannot be negative');
    }
    
    // Validate role
    $allowedRoles = ['parent', 'child', 'spouse', 'sibling', 'other'];
    if (!in_array(strtolower($role), $allowedRoles)) {
        throw new Exception('Invalid role selected');
    }
    
    // Convert role to lowercase to match database ENUM
    $role = strtolower($role);
    
    // Check for duplicate phone numbers in the same family
    $stmt = $conn->prepare("
        SELECT id FROM family_members_only 
        WHERE family_id = ? AND phone_number = ? AND is_active = TRUE
    ");
    $stmt->bind_param("is", $family_id, $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('A member with this phone number already exists in your family');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Direct SQL insert
        $stmt = $conn->prepare("
            INSERT INTO family_members_only (
                family_id,
                first_name,
                last_name,
                phone_number,
                role,
                monthly_contribution_goal,
                momo_network,
                momo_number,
                total_contributed,
                accumulated_debt,
                is_active,
                added_by,
                added_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, 0.00, TRUE, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param("issssdssi", 
            $family_id,
            $firstName,
            $lastName,
            $phone,
            $role,
            $goal,
            $momoNetwork,
            $momoNumber,
            $user_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add family member');
        }
        
        $memberId = $conn->insert_id;
        
        // Create performance record for current cycle if exists
        $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
        if ($currentCycle && $goal > 0) {
            $stmt = $conn->prepare("
                INSERT INTO member_monthly_performance (
                    cycle_id,
                    family_id,
                    member_only_id,
                    member_type,
                    target_amount
                ) VALUES (?, ?, ?, 'member', ?)
            ");
            $stmt->bind_param("iiid", $currentCycle['id'], $family_id, $memberId, $goal);
            $stmt->execute();
            
            // Update cycle totals
            updateCycleTotals($conn, $currentCycle['id'], $family_id);
        }
        
        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, 
                family_id, 
                action_type, 
                description
            ) VALUES (?, ?, 'member_added', ?)
        ");
        $description = "Added new family member: $firstName $lastName";
        $stmt->bind_param("iis", $user_id, $family_id, $description);
        $stmt->execute();
        
        $conn->commit();
        
        // Return success response with redirect flag for page reload
        header('Location: ../templates/members.php?success=' . urlencode("$firstName $lastName has been added successfully!"));
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}


function editMember($conn, $family_id, $user_id) {
    $memberId = intval($_POST['member_id'] ?? 0);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $goal = floatval($_POST['monthly_goal'] ?? 0);
    $momoNetwork = $_POST['momo_network'] ?? null;
    $momoNumber = trim($_POST['momo_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($memberId <= 0) {
        throw new Exception('Invalid member ID');
    }
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($phone) || empty($role)) {
        throw new Exception('Please fill in all required fields');
    }
    
    if ($goal < 0) {
        throw new Exception('Monthly goal cannot be negative');
    }
    
    // Validate role
    $allowedRoles = ['parent', 'child', 'spouse', 'sibling', 'other'];
    if (!in_array(strtolower($role), $allowedRoles)) {
        throw new Exception('Invalid role selected');
    }
    
    // Convert role to lowercase to match database ENUM
    $role = strtolower($role);
    
    // Check if member exists and belongs to this family
    $stmt = $conn->prepare("
        SELECT monthly_contribution_goal 
        FROM family_members_only 
        WHERE id = ? AND family_id = ?
    ");
    $stmt->bind_param("ii", $memberId, $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    
    if (!$member) {
        throw new Exception('Member not found or access denied');
    }
    
    $oldGoal = $member['monthly_contribution_goal'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update member
        $stmt = $conn->prepare("
            UPDATE family_members_only 
            SET first_name = ?, 
                last_name = ?, 
                phone_number = ?, 
                role = ?, 
                monthly_contribution_goal = ?,
                momo_network = ?,
                momo_number = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("ssssdssii", 
            $firstName, 
            $lastName, 
            $phone, 
            $role, 
            $goal,
            $momoNetwork,
            $momoNumber,
            $memberId, 
            $family_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update member');
        }
        
        // Update current cycle performance if goal changed
        if ($oldGoal != $goal) {
            $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
            if ($currentCycle) {
                $stmt = $conn->prepare("
                    UPDATE member_monthly_performance 
                    SET target_amount = ?
                    WHERE cycle_id = ? AND member_only_id = ? AND member_type = 'member'
                ");
                $stmt->bind_param("dii", $goal, $currentCycle['id'], $memberId);
                $stmt->execute();
                
                // Update cycle totals
                updateCycleTotals($conn, $currentCycle['id'], $family_id);
            }
        }
        
        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, 
                family_id, 
                action_type, 
                description
            ) VALUES (?, ?, 'member_updated', ?)
        ");
        $description = "Updated family member: $firstName $lastName";
        $stmt->bind_param("iis", $user_id, $family_id, $description);
        $stmt->execute();
        
        $conn->commit();
        
        // Redirect with success message
        header('Location: ../templates/members.php?success=' . urlencode("$firstName $lastName has been updated successfully!"));
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function deleteMember($conn, $family_id, $user_id) {
    $memberId = intval($_POST['member_id'] ?? 0);
    
    if ($memberId <= 0) {
        throw new Exception('Invalid member ID');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get member details before deletion
        $stmt = $conn->prepare("
            SELECT CONCAT(first_name, ' ', last_name) as full_name,
                   total_contributed,
                   accumulated_debt
            FROM family_members_only 
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("ii", $memberId, $family_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        
        if (!$member) {
            throw new Exception('Member not found or access denied');
        }
        
        // Check if member has contributions
        if ($member['total_contributed'] > 0) {
            throw new Exception('Cannot delete member with existing contributions. Please contact admin.');
        }
        
        // Delete related records first (to avoid foreign key constraints)
        // Delete from member_monthly_performance
        $stmt = $conn->prepare("DELETE FROM member_monthly_performance WHERE member_only_id = ? AND family_id = ?");
        $stmt->bind_param("ii", $memberId, $family_id);
        $stmt->execute();
        
        // Delete from contributions if any (though we checked total_contributed above)
        $stmt = $conn->prepare("DELETE FROM family_contributions WHERE member_only_id = ? AND family_id = ?");
        $stmt->bind_param("ii", $memberId, $family_id);
        $stmt->execute();
        
        // Delete member
        $stmt = $conn->prepare("DELETE FROM family_members_only WHERE id = ? AND family_id = ?");
        $stmt->bind_param("ii", $memberId, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete member');
        }
        
        // Update current cycle totals
        $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
        if ($currentCycle) {
            updateCycleTotals($conn, $currentCycle['id'], $family_id);
        }
        
        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, 
                family_id, 
                action_type, 
                description
            ) VALUES (?, ?, 'member_deleted', ?)
        ");
        $description = "Deleted family member: " . $member['full_name'];
        $stmt->bind_param("iis", $user_id, $family_id, $description);
        $stmt->execute();
        
        $conn->commit();
        
        // Redirect with success message
        header('Location: ../templates/members.php?success=' . urlencode($member['full_name'] . " has been removed from your family."));
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function toggleMemberStatus($conn, $family_id, $user_id) {
    $memberId = intval($_POST['member_id'] ?? 0);
    $newStatus = $_POST['status'] === 'true' ? 1 : 0;
    
    if ($memberId <= 0) {
        throw new Exception('Invalid member ID');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get member details
        $stmt = $conn->prepare("
            SELECT CONCAT(first_name, ' ', last_name) as full_name, 
                   is_active,
                   monthly_contribution_goal
            FROM family_members_only 
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("ii", $memberId, $family_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        
        if (!$member) {
            throw new Exception('Member not found');
        }
        
        // Update status
        $stmt = $conn->prepare("
            UPDATE family_members_only 
            SET is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("iii", $newStatus, $memberId, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update member status');
        }
        
        // Handle current cycle performance record
        $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
        if ($currentCycle) {
            if ($newStatus == 1 && $member['monthly_contribution_goal'] > 0) {
                // Add performance record for newly activated member
                $stmt = $conn->prepare("
                    INSERT IGNORE INTO member_monthly_performance (
                        cycle_id,
                        family_id,
                        member_only_id,
                        member_type,
                        target_amount
                    ) VALUES (?, ?, ?, 'member', ?)
                ");
                $stmt->bind_param("iiid", $currentCycle['id'], $family_id, $memberId, $member['monthly_contribution_goal']);
                $stmt->execute();
            } else {
                // Remove performance record for deactivated member
                $stmt = $conn->prepare("
                    DELETE FROM member_monthly_performance 
                    WHERE cycle_id = ? AND member_only_id = ? AND member_type = 'member'
                ");
                $stmt->bind_param("ii", $currentCycle['id'], $memberId);
                $stmt->execute();
            }
            
            // Update cycle totals
            updateCycleTotals($conn, $currentCycle['id'], $family_id);
        }
        
        // Log activity
        $status = $newStatus ? 'activated' : 'deactivated';
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, 
                family_id, 
                action_type, 
                description
            ) VALUES (?, ?, 'member_status_changed', ?)
        ");
        $description = "Member " . $member['full_name'] . " $status";
        $stmt->bind_param("iis", $user_id, $family_id, $description);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $member['full_name'] . " has been $status successfully!"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function bulkUpdateGoals($conn, $family_id, $user_id) {
    $updates = json_decode($_POST['updates'] ?? '[]', true);
    
    if (empty($updates)) {
        throw new Exception('No updates provided');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $updatedCount = 0;
        $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
        
        foreach ($updates as $update) {
            $memberId = intval($update['member_id'] ?? 0);
            $newGoal = floatval($update['goal'] ?? 0);
            
            if ($memberId <= 0 || $newGoal < 0) {
                continue;
            }
            
            // Update member goal
            $stmt = $conn->prepare("
                UPDATE family_members_only 
                SET monthly_contribution_goal = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("dii", $newGoal, $memberId, $family_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $updatedCount++;
                
                // Update current cycle performance if exists
                if ($currentCycle) {
                    $stmt = $conn->prepare("
                        UPDATE member_monthly_performance 
                        SET target_amount = ?
                        WHERE cycle_id = ? AND member_only_id = ? AND member_type = 'member'
                    ");
                    $stmt->bind_param("dii", $newGoal, $currentCycle['id'], $memberId);
                    $stmt->execute();
                }
            }
        }
        
        // Update cycle totals if any changes were made
        if ($updatedCount > 0 && $currentCycle) {
            updateCycleTotals($conn, $currentCycle['id'], $family_id);
        }
        
        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, 
                family_id, 
                action_type, 
                description
            ) VALUES (?, ?, 'bulk_goals_updated', ?)
        ");
        $description = "Bulk updated goals for $updatedCount members";
        $stmt->bind_param("iis", $user_id, $family_id, $description);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated goals for $updatedCount members!"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Helper function to get current monthly cycle
function getCurrentMonthlyCycle($conn, $family_id) {
    $stmt = $conn->prepare("
        SELECT * FROM monthly_cycles 
        WHERE family_id = ? AND is_current = TRUE AND is_closed = FALSE
        LIMIT 1
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Helper function to update cycle totals
function updateCycleTotals($conn, $cycle_id, $family_id) {
    // Check if monthly_cycles table exists
    $result = $conn->query("SHOW TABLES LIKE 'monthly_cycles'");
    if ($result->num_rows == 0) {
        return; // Table doesn't exist, skip this operation
    }
    
    // Update total target
    $stmt = $conn->prepare("
        UPDATE monthly_cycles 
        SET total_target = (
            SELECT COALESCE(SUM(target_amount), 0)
            FROM member_monthly_performance 
            WHERE cycle_id = ?
        ),
        members_pending = (
            SELECT COUNT(*)
            FROM member_monthly_performance 
            WHERE cycle_id = ? AND is_completed = FALSE
        ),
        members_completed = (
            SELECT COUNT(*)
            FROM member_monthly_performance 
            WHERE cycle_id = ? AND is_completed = TRUE
        )
        WHERE id = ?
    ");
    $stmt->bind_param("iiii", $cycle_id, $cycle_id, $cycle_id, $cycle_id);
    $stmt->execute();
}
?>