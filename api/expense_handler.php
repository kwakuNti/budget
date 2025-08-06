<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in and has family access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_expense') {
        $expense_type = trim($_POST['expense_type']);
        $amount = floatval($_POST['amount']);
        $description = trim($_POST['description']);
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $payment_method = $_POST['payment_method'] ?? 'momo';
        $family_id = $_SESSION['family_id'];
        $user_id = $_SESSION['user_id'];
        
        // Validate input
        if (empty($expense_type) || $amount <= 0 || empty($description)) {
            throw new Exception('All fields are required');
        }
        
        // Validate expense type
        $allowedTypes = ['dstv', 'wifi', 'utilities', 'dining', 'maintenance', 'other'];
        if (!in_array($expense_type, $allowedTypes)) {
            throw new Exception('Invalid expense type');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check current family pool balance (contributions - expenses)
            $stmt = $conn->prepare("
                SELECT total_pool 
                FROM family_groups 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $currentPool = $result['total_pool'] ?? 0;
            
            if ($currentPool < $amount) {
                throw new Exception('Insufficient family funds. Available: ₵' . number_format($currentPool, 2));
            }
            
            // Get the family member record for the logged-in user
            $stmt = $conn->prepare("
                SELECT id FROM family_members 
                WHERE user_id = ? AND family_id = ? AND is_active = TRUE
            ");
            $stmt->bind_param("ii", $user_id, $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $familyMember = $result->fetch_assoc();
            
            $paid_by = $familyMember['id'] ?? null;
            
            // Insert expense
            $stmt = $conn->prepare("
                INSERT INTO family_expenses (
                    family_id, 
                    expense_type, 
                    amount, 
                    description, 
                    expense_date,
                    paid_by,
                    payer_type,
                    payment_method,
                    is_approved,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'user', ?, TRUE, NOW())
            ");
            
            $stmt->bind_param("isdsiss", $family_id, $expense_type, $amount, $description, $expense_date, $paid_by, $payment_method);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add expense: ' . $conn->error);
            }
            
            // The trigger will automatically update the family pool
            // Let's also log this activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (
                    user_id, 
                    family_id, 
                    action_type, 
                    description, 
                    created_at
                ) VALUES (?, ?, 'expense_added', ?, NOW())
            ");
            $activityDesc = "Added expense: ₵" . number_format($amount, 2) . " for " . ucfirst($expense_type) . " - " . $description;
            $stmt->bind_param("iis", $user_id, $family_id, $activityDesc);
            $stmt->execute();
            
            $conn->commit();
            
            // Get updated pool balance
            $stmt = $conn->prepare("SELECT total_pool FROM family_groups WHERE id = ?");
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $newBalance = $stmt->get_result()->fetch_assoc()['total_pool'];
            
            echo json_encode([
                'success' => true,
                'message' => "₵" . number_format($amount, 2) . " expense added for " . ucfirst($expense_type),
                'new_pool_balance' => $newBalance
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } elseif ($action === 'update_expense') {
        $expense_id = intval($_POST['expense_id'] ?? 0);
        $expense_type = trim($_POST['expense_type']);
        $amount = floatval($_POST['amount']);
        $description = trim($_POST['description']);
        $expense_date = $_POST['expense_date'];
        $family_id = $_SESSION['family_id'];
        $user_id = $_SESSION['user_id'];
        
        if ($expense_id <= 0 || empty($expense_type) || $amount <= 0 || empty($description) || empty($expense_date)) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get original expense amount to adjust pool
            $stmt = $conn->prepare("
                SELECT amount FROM family_expenses 
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("ii", $expense_id, $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $originalExpense = $result->fetch_assoc();
            
            if (!$originalExpense) {
                throw new Exception('Expense not found');
            }
            
            $originalAmount = $originalExpense['amount'];
            $difference = $amount - $originalAmount;
            
            // Check if family has sufficient funds for the increase
            if ($difference > 0) {
                $stmt = $conn->prepare("SELECT total_pool FROM family_groups WHERE id = ?");
                $stmt->bind_param("i", $family_id);
                $stmt->execute();
                $currentPool = $stmt->get_result()->fetch_assoc()['total_pool'];
                
                if ($currentPool < $difference) {
                    throw new Exception('Insufficient family funds for this update');
                }
            }
            
            // Update expense
            $stmt = $conn->prepare("
                UPDATE family_expenses 
                SET expense_type = ?, amount = ?, description = ?, expense_date = ?
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("sdssii", $expense_type, $amount, $description, $expense_date, $expense_id, $family_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update expense');
            }
            
            // Manually adjust family pool for the difference
            $stmt = $conn->prepare("
                UPDATE family_groups 
                SET total_pool = total_pool - ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param("di", $difference, $family_id);
            $stmt->execute();
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (
                    user_id, 
                    family_id, 
                    action_type, 
                    description, 
                    created_at
                ) VALUES (?, ?, 'expense_updated', ?, NOW())
            ");
            $activityDesc = "Updated expense: ₵" . number_format($amount, 2) . " for " . ucfirst($expense_type);
            $stmt->bind_param("iis", $user_id, $family_id, $activityDesc);
            $stmt->execute();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Expense updated successfully'
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } elseif ($action === 'delete_expense') {
        $expense_id = intval($_POST['expense_id'] ?? 0);
        $family_id = $_SESSION['family_id'];
        $user_id = $_SESSION['user_id'];
        
        if ($expense_id <= 0) {
            throw new Exception('Invalid expense ID');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get expense details before deletion
            $stmt = $conn->prepare("
                SELECT amount, expense_type, description 
                FROM family_expenses 
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("ii", $expense_id, $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $expense = $result->fetch_assoc();
            
            if (!$expense) {
                throw new Exception('Expense not found');
            }
            
            // Delete expense
            $stmt = $conn->prepare("DELETE FROM family_expenses WHERE id = ? AND family_id = ?");
            $stmt->bind_param("ii", $expense_id, $family_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete expense');
            }
            
            // Add money back to family pool
            $stmt = $conn->prepare("
                UPDATE family_groups 
                SET total_pool = total_pool + ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param("di", $expense['amount'], $family_id);
            $stmt->execute();
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (
                    user_id, 
                    family_id, 
                    action_type, 
                    description, 
                    created_at
                ) VALUES (?, ?, 'expense_deleted', ?, NOW())
            ");
            $activityDesc = "Deleted expense: ₵" . number_format($expense['amount'], 2) . " for " . ucfirst($expense['expense_type']);
            $stmt->bind_param("iis", $user_id, $family_id, $activityDesc);
            $stmt->execute();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } elseif ($action === 'get_expense') {
        $expense_id = intval($_GET['expense_id'] ?? 0);
        $family_id = $_SESSION['family_id'];
        
        if ($expense_id <= 0) {
            throw new Exception('Invalid expense ID');
        }
        
        $stmt = $conn->prepare("
            SELECT 
                fe.*,
                CASE 
                    WHEN fe.payer_type = 'user' AND fm.id IS NOT NULL THEN CONCAT(u.first_name, ' ', u.last_name)
                    WHEN fe.payer_type = 'member' AND fmo.id IS NOT NULL THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                    ELSE 'Unknown'
                END as payer_name
            FROM family_expenses fe
            LEFT JOIN family_members fm ON fe.paid_by = fm.id AND fe.payer_type = 'user'
            LEFT JOIN users u ON fm.user_id = u.id
            LEFT JOIN family_members_only fmo ON fe.member_only_id = fmo.id AND fe.payer_type = 'member'
            WHERE fe.id = ? AND fe.family_id = ?
        ");
        $stmt->bind_param("ii", $expense_id, $family_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $expense = $result->fetch_assoc();
        
        if ($expense) {
            echo json_encode([
                'success' => true,
                'expense' => $expense
            ]);
        } else {
            throw new Exception('Expense not found');
        }
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Expense Handler Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>