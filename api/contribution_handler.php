<?php
// contribution_handler.php - Fixed version
session_start();
require_once '../config/connection.php';

// Error reporting configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start output buffering at the beginning
ob_start();

/**
 * Validate user session and permissions
 */
function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
        return [
            'success' => false,
            'message' => 'Unauthorized access. Please log in again.',
            'redirect' => 'login.php'
        ];
    }
    return ['success' => true];
}

/**
 * Validate request method
 */
function validateRequestMethod() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [
            'success' => false,
            'message' => 'Invalid request method. Only POST requests are allowed.'
        ];
    }
    return ['success' => true];
}

/**
 * Sanitize and validate input data
 */
function validateContributionInput($data) {
    $errors = [];
    
    // For this system, we primarily use member_id which refers to family_members_only table
    $member_id = isset($data['member_id']) ? intval($data['member_id']) : null;
    
    if (!$member_id || $member_id <= 0) {
        $errors[] = 'Valid member ID is required';
    }
    
    // Validate amount
    $amount = floatval($data['amount'] ?? 0);
    if ($amount <= 0) {
        $errors[] = 'Contribution amount must be greater than 0';
    }
    
    if ($amount > 999999.99) {
        $errors[] = 'Contribution amount is too large';
    }
    
    // Validate notes (optional but sanitize)
    $notes = isset($data['notes']) ? trim(strip_tags($data['notes'])) : '';
    if (strlen($notes) > 500) {
        $errors[] = 'Notes cannot exceed 500 characters';
    }
    
    // Validate payment method
    $payment_method = isset($data['payment_method']) ? strtolower(trim($data['payment_method'])) : 'momo';
    $allowed_methods = ['momo', 'cash', 'bank_transfer', 'mobile_money'];
    if (!in_array($payment_method, $allowed_methods)) {
        $payment_method = 'momo'; // Default fallback
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors,
        'data' => [
            'member_id' => $member_id,
            'amount' => $amount,
            'notes' => $notes,
            'payment_method' => $payment_method
        ]
    ];
}

/**
 * Verify member belongs to family and get member details
 */
function verifyAndGetMember($conn, $member_id, $family_id) {
    // Check family member from family_members_only table
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, monthly_contribution_goal,
               current_month_contributed, is_active
        FROM family_members_only 
        WHERE id = ? AND family_id = ? AND is_active = TRUE
    ");
    $stmt->bind_param("ii", $member_id, $family_id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    
    if (!$member) {
        return [
            'success' => false,
            'message' => 'Member not found or inactive'
        ];
    }
    
    $member['member_type'] = 'member';
    $member['full_name'] = $member['first_name'] . ' ' . $member['last_name'];
    
    return [
        'success' => true,
        'member' => $member
    ];
}

/**
 * Add contribution and update related records
 */
function addContribution($conn, $validated_data, $member, $family_id, $user_id) {
    $conn->begin_transaction();
    
    try {
        $member_id = $validated_data['member_id'];
        $amount = $validated_data['amount'];
        $notes = $validated_data['notes'];
        $payment_method = $validated_data['payment_method'];
        
        // Insert contribution record
        $stmt = $conn->prepare("
            INSERT INTO family_contributions (
                family_id, 
                member_only_id, 
                contributor_type, 
                amount, 
                contribution_date, 
                payment_method, 
                notes,
                created_at
            ) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, NOW())
        ");
        
        $contributor_type = 'member';
        $stmt->bind_param("iisdss", 
            $family_id, 
            $member_id, 
            $contributor_type, 
            $amount, 
            $payment_method, 
            $notes
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to record contribution: ' . $stmt->error);
        }
        
        $contribution_id = $conn->insert_id;
        
        // Update family member's totals
        $stmt = $conn->prepare("
            UPDATE family_members_only 
            SET 
                total_contributed = total_contributed + ?,
                current_month_contributed = current_month_contributed + ?,
                goal_met_this_month = CASE 
                    WHEN (current_month_contributed + ?) >= monthly_contribution_goal THEN TRUE 
                    ELSE FALSE 
                END,
                last_payment_date = CURDATE(),
                updated_at = NOW()
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("dddii", $amount, $amount, $amount, $member_id, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update member totals: ' . $stmt->error);
        }
        
        // Update family pool
        $stmt = $conn->prepare("
            UPDATE family_groups 
            SET 
                total_pool = total_pool + ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $amount, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update family pool: ' . $stmt->error);
        }
        
        // Log activity (optional - don't fail on this)
        try {
            $activity_description = "₵" . number_format($amount, 2) . " contribution added for " . $member['full_name'];
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at)
                VALUES (?, ?, 'contribution_added', ?, NOW())
            ");
            $stmt->bind_param("iis", $user_id, $family_id, $activity_description);
            $stmt->execute();
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            error_log("Activity log error: " . $e->getMessage());
        }
        
        $conn->commit();
        
        // Get updated member contribution status
        $new_monthly_total = ($member['current_month_contributed'] ?? 0) + $amount;
        $goal_met = $new_monthly_total >= ($member['monthly_contribution_goal'] ?? 0);
        
        return [
            'success' => true,
            'message' => "₵" . number_format($amount, 2) . " contribution successfully added for {$member['full_name']}",
            'data' => [
                'contribution_id' => $contribution_id,
                'member_name' => $member['full_name'],
                'amount' => $amount,
                'new_monthly_total' => $new_monthly_total,
                'monthly_goal' => $member['monthly_contribution_goal'] ?? 0,
                'goal_met' => $goal_met,
                'payment_method' => $payment_method
            ]
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Contribution Transaction Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle add contribution action
 */
function handleAddContribution($conn, $family_id, $user_id) {
    // Validate input
    $validation = validateContributionInput($_POST);
    if (!$validation['success']) {
        return [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation['errors']
        ];
    }
    
    $validated_data = $validation['data'];
    
    // Verify member exists and belongs to family
    $member_check = verifyAndGetMember(
        $conn, 
        $validated_data['member_id'], 
        $family_id
    );
    
    if (!$member_check['success']) {
        return $member_check;
    }
    
    // Add the contribution
    return addContribution($conn, $validated_data, $member_check['member'], $family_id, $user_id);
}

/**
 * Get list of active family members for dropdown
 */
function getMemberList($conn, $family_id) {
    // Get members from family_members_only table
    $stmt = $conn->prepare("
        SELECT id as member_id, 'member' as member_type,
               CONCAT(first_name, ' ', last_name) as full_name,
               first_name, role, monthly_contribution_goal
        FROM family_members_only
        WHERE family_id = ? AND is_active = TRUE
        ORDER BY first_name, last_name
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'success' => true,
        'members' => $members
    ];
}

// Main execution
try {
    // Clean any output that might have been generated
    ob_clean();
    
    // Validate session
    $session_check = validateSession();
    if (!$session_check['success']) {
        echo json_encode($session_check);
        exit;
    }
    
    // Validate request method
    $method_check = validateRequestMethod();
    if (!$method_check['success']) {
        echo json_encode($method_check);
        exit;
    }
    
    // Get session data
    $user_id = $_SESSION['user_id'];
    $family_id = $_SESSION['family_id'];
    $action = $_POST['action'] ?? '';
    
    if (empty($action)) {
        echo json_encode([
            'success' => false,
            'message' => 'No action specified'
        ]);
        exit;
    }
    
    // Handle the action
    $result = null;
    switch ($action) {
        case 'add_contribution':
            $result = handleAddContribution($conn, $family_id, $user_id);
            break;
            
        case 'get_member_list':
            $result = getMemberList($conn, $family_id);
            break;
            
        default:
            $result = [
                'success' => false,
                'message' => 'Invalid action specified'
            ];
    }
    
    // Output result
    echo json_encode($result);
    
} catch (Exception $e) {
    // Log the error
    error_log("Contribution Handler Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again.',
        'error_code' => 'INTERNAL_ERROR',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} finally {
    // End output buffering and exit
    ob_end_flush();
    exit;
}
?>