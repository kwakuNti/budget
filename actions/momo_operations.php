<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/operations.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in and has access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];

// Get the operation type
$operation = $_POST['operation'] ?? $_GET['operation'] ?? '';

// Add request logging for debugging
error_log("MoMo Operation: $operation for user $user_id, family $family_id");

switch ($operation) {
    case 'send_payment_request':
        handleSendPaymentRequest($conn, $user_id, $family_id);
        break;
        
    case 'refresh_balance':
        handleRefreshBalance($conn, $user_id, $family_id);
        break;
        
    case 'switch_network':
        handleSwitchNetwork($conn, $user_id, $family_id);
        break;
        
    case 'change_number':
        handleChangeNumber($conn, $user_id, $family_id);
        break;
        
    case 'get_recent_requests':
        handleGetRecentRequests($conn, $family_id);
        break;
        
    case 'update_request_status':
        handleUpdateRequestStatus($conn, $user_id, $family_id);
        break;
        
    case 'get_momo_stats':
        handleGetMoMoStats($conn, $family_id);
        break;
        
    case 'setup_momo':
        handleSetupMoMo($conn, $user_id, $family_id);
        break;
        
    case 'get_members':
        handleGetMembers($conn, $family_id);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid operation']);
        break;
}

function handleSendPaymentRequest($conn, $user_id, $family_id) {
    try {
        // Validate input
        $selected_members = $_POST['members'] ?? [];
        $amount = floatval($_POST['amount'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');
        $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == 'true';
        
        // Enhanced validation
        if (empty($selected_members)) {
            throw new Exception('Please select at least one member');
        }
        
        if ($amount <= 0) {
            throw new Exception('Please enter a valid amount');
        }
        
        if ($amount > 10000) {
            throw new Exception('Amount cannot exceed ₵10,000');
        }
        
        if (empty($purpose)) {
            throw new Exception('Please enter a purpose for the request');
        }
        
        if (strlen($purpose) < 3) {
            throw new Exception('Purpose must be at least 3 characters long');
        }
        
        if (strlen($purpose) > 255) {
            throw new Exception('Purpose cannot exceed 255 characters');
        }
        
        // Verify MoMo account exists
        $momoAccount = getFamilyMoMoAccount($conn, $family_id);
        if (!$momoAccount) {
            throw new Exception('No MoMo account found. Please setup your MoMo account first.');
        }
        
        // Get member details and validate selections
        $members = getFamilyMembersForPayment($conn, $family_id);
        $recipients = [];
        
        foreach ($selected_members as $member_identifier) {
            $found = false;
            foreach ($members as $member) {
                $identifier = $member['member_type'] . '_' . $member['member_id'];
                if ($identifier == $member_identifier) {
                    $recipients[] = $member;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('Invalid member selection: ' . $member_identifier);
            }
        }
        
        if (empty($recipients)) {
            throw new Exception('No valid recipients found');
        }
        
        // Check for duplicate requests (prevent spam)
        $recent_check = checkRecentDuplicateRequests($conn, $user_id, $family_id, $amount, $purpose);
        if ($recent_check) {
            throw new Exception('A similar request was sent recently. Please wait before sending another.');
        }
        
        // Create payment requests
        $request_ids = createPaymentRequest($conn, $user_id, $recipients, $family_id, $amount, $purpose, $send_sms);
        
        if (empty($request_ids)) {
            throw new Exception('Failed to create payment requests');
        }
        
        // Log activity
        logMoMoActivity($conn, $family_id, $user_id, 'payment_request_sent', 
            "Sent payment request for ₵{$amount} to " . count($recipients) . " member(s): {$purpose}", 
            [
                'amount' => $amount, 
                'purpose' => $purpose, 
                'recipients' => count($recipients),
                'request_ids' => $request_ids,
                'send_sms' => $send_sms
            ]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment request sent successfully to ' . count($recipients) . ' member(s)',
            'request_ids' => $request_ids,
            'recipients_count' => count($recipients)
        ]);
        
    } catch (Exception $e) {
        error_log("Send payment request error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleRefreshBalance($conn, $user_id, $family_id) {
    try {
        $result = refreshMoMoBalance($conn, $family_id);
        
        if ($result['success']) {
            // Get updated stats and account info
            $stats = getMoMoStats($conn, $family_id);
            $account = getFamilyMoMoAccount($conn, $family_id);
            
            $result['stats'] = $stats;
            $result['account'] = $account;
            
            // Log activity
            logMoMoActivity($conn, $family_id, $user_id, 'balance_refreshed', 
                "Balance refreshed - New balance: ₵{$result['balance']}", 
                ['new_balance' => $result['balance']]
            );
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Refresh balance error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleSwitchNetwork($conn, $user_id, $family_id) {
    try {
        $new_network = trim($_POST['network'] ?? '');
        $new_number = trim($_POST['phone_number'] ?? '');
        
        if (empty($new_network)) {
            throw new Exception('Please select a network');
        }
        
        if (empty($new_number)) {
            throw new Exception('Please enter a phone number');
        }
        
        // Validate phone number format
        $clean_number = cleanPhoneNumber($new_number);
        if (!isValidGhanaianNumber($clean_number)) {
            throw new Exception('Please enter a valid Ghanaian phone number (+233XXXXXXXXX)');
        }
        
        // Check if network is available
        $networkInfo = getNetworkInfo($new_network);
        if (!$networkInfo['available']) {
            throw new Exception($networkInfo['name'] . ' is not available yet. Coming soon!');
        }
        
        // Check current account
        $currentAccount = getFamilyMoMoAccount($conn, $family_id);
        if ($currentAccount) {
            if ($currentAccount['network'] === $new_network && $currentAccount['phone_number'] === $clean_number) {
                throw new Exception('You are already using this network and phone number');
            }
        }
        
        $result = switchMoMoNetwork($conn, $family_id, $new_network, $clean_number);
        
        if ($result['success']) {
            // Log activity
            logMoMoActivity($conn, $family_id, $user_id, 'network_switched', 
                "Switched to {$new_network} network with number {$clean_number}", 
                ['old_network' => $currentAccount['network'] ?? null, 'new_network' => $new_network, 'new_number' => $clean_number]
            );
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Switch network error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleChangeNumber($conn, $user_id, $family_id) {
    try {
        $new_number = trim($_POST['phone_number'] ?? '');
        $pin = trim($_POST['pin'] ?? '');
        
        if (empty($new_number)) {
            throw new Exception('Please enter a phone number');
        }
        
        if (empty($pin)) {
            throw new Exception('Please enter your MoMo PIN');
        }
        
        // Validate phone number format
        $clean_number = cleanPhoneNumber($new_number);
        if (!isValidGhanaianNumber($clean_number)) {
            throw new Exception('Please enter a valid Ghanaian phone number (+233XXXXXXXXX)');
        }
        
        if (strlen($pin) < 4) {
            throw new Exception('PIN must be at least 4 characters');
        }
        
        // Check current account
        $currentAccount = getFamilyMoMoAccount($conn, $family_id);
        if (!$currentAccount) {
            throw new Exception('No MoMo account found');
        }
        
        if ($currentAccount['phone_number'] === $clean_number) {
            throw new Exception('This is already your current phone number');
        }
        
        $result = changeMoMoNumber($conn, $family_id, $clean_number, $pin);
        
        if ($result['success']) {
            // Log activity
            logMoMoActivity($conn, $family_id, $user_id, 'number_changed', 
                "Changed phone number from {$currentAccount['phone_number']} to {$clean_number}", 
                ['old_number' => $currentAccount['phone_number'], 'new_number' => $clean_number]
            );
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Change number error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetRecentRequests($conn, $family_id) {
    try {
        $limit = intval($_GET['limit'] ?? 10);
        $offset = intval($_GET['offset'] ?? 0);
        
        // Validate limits
        if ($limit > 50) $limit = 50; // Prevent excessive requests
        if ($limit < 1) $limit = 10;
        if ($offset < 0) $offset = 0;
        
        $requests = getRecentPaymentRequests($conn, $family_id, $limit, $offset);
        
        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
    } catch (Exception $e) {
        error_log("Get recent requests error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleUpdateRequestStatus($conn, $user_id, $family_id) {
    try {
        $request_id = intval($_POST['request_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $transaction_id = trim($_POST['transaction_id'] ?? '') ?: null;
        
        if ($request_id <= 0) {
            throw new Exception('Invalid request ID');
        }
        
        if (!in_array($status, ['pending', 'completed', 'failed', 'cancelled'])) {
            throw new Exception('Invalid status');
        }
        
        // Verify request belongs to this family
        $stmt = $conn->prepare("SELECT id, amount, purpose, status as current_status FROM payment_requests WHERE id = ? AND family_id = ?");
        $stmt->bind_param("ii", $request_id, $family_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if (!$request) {
            throw new Exception('Request not found');
        }
        
        // Prevent invalid status transitions
        if ($request['current_status'] === 'completed' && $status !== 'completed') {
            throw new Exception('Cannot change status of completed request');
        }
        
        $success = updatePaymentRequestStatus($conn, $request_id, $status, $transaction_id);
        
        if ($success) {
            // Log activity
            logMoMoActivity($conn, $family_id, $user_id, 'request_status_updated', 
                "Updated payment request #{$request_id} status from {$request['current_status']} to {$status}", 
                ['request_id' => $request_id, 'old_status' => $request['current_status'], 'new_status' => $status, 'transaction_id' => $transaction_id]
            );
            
            echo json_encode(['success' => true, 'message' => 'Request status updated successfully']);
        } else {
            throw new Exception('Failed to update request status');
        }
        
    } catch (Exception $e) {
        error_log("Update request status error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetMoMoStats($conn, $family_id) {
    try {
        $stats = getMoMoStats($conn, $family_id);
        $account = getFamilyMoMoAccount($conn, $family_id);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'account' => $account
        ]);
        
    } catch (Exception $e) {
        error_log("Get MoMo stats error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleSetupMoMo($conn, $user_id, $family_id) {
    try {
        $phone_number = trim($_POST['phone_number'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');
        $network = trim($_POST['network'] ?? 'mtn');
        
        if (empty($phone_number)) {
            throw new Exception('Please enter a phone number');
        }
        
        if (empty($account_name)) {
            throw new Exception('Please enter an account name');
        }
        
        // Validate phone number format
        $clean_number = cleanPhoneNumber($phone_number);
        if (!isValidGhanaianNumber($clean_number)) {
            throw new Exception('Please enter a valid Ghanaian phone number (+233XXXXXXXXX)');
        }
        
        // Validate account name
        if (strlen($account_name) < 3) {
            throw new Exception('Account name must be at least 3 characters long');
        }
        
        if (strlen($account_name) > 50) {
            throw new Exception('Account name cannot exceed 50 characters');
        }
        
        // Check if account already exists
        $existing = getFamilyMoMoAccount($conn, $family_id);
        if ($existing) {
            throw new Exception('MoMo account already exists for this family');
        }
        
        // Check if network is available
        $networkInfo = getNetworkInfo($network);
        if (!$networkInfo['available']) {
            throw new Exception($networkInfo['name'] . ' is not available yet. Only MTN is currently supported.');
        }
        
        // Check if phone number is already in use by another family
        if (isPhoneNumberInUse($conn, $clean_number)) {
            throw new Exception('This phone number is already registered with another family');
        }
        
        // Use the setupMoMoAccount function
        $result = setupMoMoAccount($conn, $family_id, $user_id, $clean_number, $account_name, $network);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Setup MoMo error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetMembers($conn, $family_id) {
    try {
        $members = getFamilyMembersForPayment($conn, $family_id);
        
        echo json_encode([
            'success' => true,
            'members' => $members
        ]);
        
    } catch (Exception $e) {
        error_log("Get members error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Helper function to check for recent duplicate requests
function checkRecentDuplicateRequests($conn, $user_id, $family_id, $amount, $purpose) {
    $stmt = $conn->prepare("
        SELECT id FROM payment_requests 
        WHERE sender_id = ? AND family_id = ? AND amount = ? AND purpose = ? 
        AND requested_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        LIMIT 1
    ");
    $stmt->bind_param("iids", $user_id, $family_id, $amount, $purpose);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() !== null;
}
?>