<?php
/**
 * MoMo Functions Library for Nkansah Family Budget Manager
 * Handles all Mobile Money operations and integrations
 */

// =============================================
// CONFIGURATION & CONSTANTS
// =============================================

// MoMo Network Configuration
const MOMO_NETWORKS = [
    'mtn' => [
        'name' => 'MTN Mobile Money',
        'code' => 'MTN_MM',
        'available' => true,
        'api_url' => 'https://sandbox.momodeveloper.mtn.com',
        'prefixes' => ['024', '025', '053', '054', '055', '059']
    ],
    'vodafone' => [
        'name' => 'Vodafone Cash',
        'code' => 'VODAFONE',
        'available' => false, // Coming soon
        'api_url' => '',
        'prefixes' => ['020', '050']
    ],
    'airteltigo' => [
        'name' => 'AirtelTigo Money',
        'code' => 'AIRTELTIGO',
        'available' => false, // Coming soon
        'api_url' => '',
        'prefixes' => ['027', '028', '057', '026', '056']
    ]
];

// =============================================
// CORE MOMO FUNCTIONS
// =============================================

/**
 * Get network information
 */
function getNetworkInfo($network) {
    return MOMO_NETWORKS[$network] ?? [
        'name' => 'Unknown Network',
        'available' => false
    ];
}

/**
 * Clean and format phone number to international format
 */
function cleanPhoneNumber($phone) {
    if (empty($phone)) {
        return false;
    }

    // Remove all non-digit characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    // Handle different formats
    if (strlen($cleaned) === 9) {
        return '+233' . $cleaned;
    } elseif (strlen($cleaned) === 10 && $cleaned[0] === '0') {
        return '+233' . substr($cleaned, 1);
    } elseif (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '233') {
        return '+' . $cleaned;
    } elseif (strlen($cleaned) === 13 && substr($cleaned, 0, 4) === '+233') {
        return $cleaned; // Already in correct format
    }
    
    return false;
}

/**
 * Validate Ghanaian phone number
 */
function isValidGhanaianNumber($phone) {
    $cleanNumber = cleanPhoneNumber($phone);
    if (!$cleanNumber) {
        return false;
    }

    // Must be exactly +233XXXXXXXXX
    if (strlen($cleanNumber) !== 13 || substr($cleanNumber, 0, 4) !== '+233') {
        return false;
    }

    // Remaining 9 digits must be numeric
    $numberPart = substr($cleanNumber, 4);
    return preg_match('/^[0-9]{9}$/', $numberPart);
}


/**
 * Detect network from phone number
 */
function detectNetworkFromNumber($phone) {
    $cleanPhone = cleanPhoneNumber($phone);
    if (!$cleanPhone) return null;
    
    $prefix = substr($cleanPhone, 4, 3);
    
    foreach (MOMO_NETWORKS as $networkKey => $network) {
        if (in_array($prefix, $network['prefixes'])) {
            return $networkKey;
        }
    }
    
    return null;
}

/**
 * Check if phone number is already in use by another family
 */
function isPhoneNumberInUse($conn, $phone_number) {
    $stmt = $conn->prepare("SELECT id FROM momo_accounts WHERE phone_number = ? AND is_active = 1");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// =============================================
// MOMO ACCOUNT MANAGEMENT
// =============================================

/**
 * Setup MoMo account for family
 */
function setupMoMoAccount($conn, $family_id, $user_id, $phone_number, $account_name, $network = 'mtn') {
    try {
        $conn->begin_transaction();
        
        // Auto-detect network if not provided
        if (!$network) {
            $network = detectNetworkFromNumber($phone_number);
            if (!$network) {
                throw new Exception('Could not detect network from phone number');
            }
        }
        
        // Verify network is available
        $networkInfo = getNetworkInfo($network);
        if (!$networkInfo['available']) {
            throw new Exception($networkInfo['name'] . ' is not available yet');
        }
        
        // Check if account already exists
        $existing = getFamilyMoMoAccount($conn, $family_id);
        if ($existing) {
            throw new Exception('MoMo account already exists for this family');
        }
        
        // Insert MoMo account
        $stmt = $conn->prepare("
            INSERT INTO momo_accounts (
                family_id, phone_number, network, account_name, 
                balance, is_primary, is_active
            ) VALUES (?, ?, ?, ?, 0.00, 1, 1)
        ");
        $stmt->bind_param("isss", $family_id, $phone_number, $network, $account_name);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create MoMo account');
        }
        
        // Log the activity
        logMoMoActivity($conn, $family_id, $user_id, 'account_setup', 
            "MoMo account setup completed for {$account_name} ({$phone_number})", 
            ['network' => $network, 'phone_number' => $phone_number, 'account_name' => $account_name]
        );
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'MoMo account setup successfully',
            'account_id' => $conn->insert_id,
            'network' => $networkInfo['name']
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get family MoMo account
 */
function getFamilyMoMoAccount($conn, $family_id) {
    $stmt = $conn->prepare("
        SELECT ma.*, COALESCE(ma.updated_at, ma.created_at) as last_updated
        FROM momo_accounts ma 
        WHERE ma.family_id = ? AND ma.is_active = 1 
        ORDER BY ma.is_primary DESC, ma.created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if ($account) {
        $account['network_info'] = getNetworkInfo($account['network']);
        $account['formatted_balance'] = '₵' . number_format($account['balance'], 2);
    }
    
    return $account;
}

/**
 * Switch MoMo network
 */
function switchMoMoNetwork($conn, $family_id, $new_network, $new_number) {
    try {
        $conn->begin_transaction();
        
        // Verify network is available
        $networkInfo = getNetworkInfo($new_network);
        if (!$networkInfo['available']) {
            throw new Exception($networkInfo['name'] . ' is not available yet');
        }
        
        // Check if number is already in use
        if (isPhoneNumberInUse($conn, $new_number)) {
            throw new Exception('This phone number is already registered with another family');
        }
        
        // Update existing account
        $stmt = $conn->prepare("
            UPDATE momo_accounts 
            SET network = ?, phone_number = ?, balance = 0.00, updated_at = CURRENT_TIMESTAMP
            WHERE family_id = ? AND is_active = 1
        ");
        $stmt->bind_param("ssi", $new_network, $new_number, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to switch network');
        }
        
        if ($stmt->affected_rows == 0) {
            throw new Exception('No active MoMo account found');
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Successfully switched to ' . $networkInfo['name'],
            'network' => $networkInfo['name'],
            'phone_number' => $new_number
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Change MoMo phone number
 */
function changeMoMoNumber($conn, $family_id, $new_number, $pin) {
    try {
        $conn->begin_transaction();
        
        // Get current account
        $account = getFamilyMoMoAccount($conn, $family_id);
        if (!$account) {
            throw new Exception('No MoMo account found');
        }
        
        // Check if number is already in use
        if (isPhoneNumberInUse($conn, $new_number)) {
            throw new Exception('This phone number is already registered with another family');
        }
        
        // Verify PIN (in real implementation, you'd verify against MoMo API)
        if (strlen($pin) < 4) {
            throw new Exception('Invalid PIN format');
        }
        
        // Detect network from new number
        $detected_network = detectNetworkFromNumber($new_number);
        if (!$detected_network) {
            throw new Exception('Could not detect network from phone number');
        }
        
        // Update phone number and network if different
        $stmt = $conn->prepare("
            UPDATE momo_accounts 
            SET phone_number = ?, network = ?, updated_at = CURRENT_TIMESTAMP
            WHERE family_id = ? AND is_active = 1
        ");
        $stmt->bind_param("ssi", $new_number, $detected_network, $family_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update phone number');
        }
        
        $conn->commit();
        
        $networkInfo = getNetworkInfo($detected_network);
        
        return [
            'success' => true,
            'message' => 'Phone number updated successfully',
            'phone_number' => $new_number,
            'network' => $networkInfo['name']
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Refresh MoMo balance (simulate API call for now)
 */
function refreshMoMoBalance($conn, $family_id) {
    try {
        $account = getFamilyMoMoAccount($conn, $family_id);
        if (!$account) {
            throw new Exception('No MoMo account found');
        }
        
        // In real implementation, call MoMo API to get actual balance
        // For now, simulate by calculating from contributions minus expenses
        $balance = calculateMoMoBalance($conn, $family_id);
        
        // Update balance in database
        $stmt = $conn->prepare("
            UPDATE momo_accounts 
            SET balance = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE family_id = ? AND is_active = 1
        ");
        $stmt->bind_param("di", $balance, $family_id);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Balance refreshed successfully',
            'balance' => $balance,
            'formatted_balance' => '₵' . number_format($balance, 2),
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Calculate MoMo balance from transactions
 */
function calculateMoMoBalance($conn, $family_id) {
    // Get total contributions
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_contributions
        FROM family_contributions 
        WHERE family_id = ?
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $contributions = $stmt->get_result()->fetch_assoc()['total_contributions'];
    
    // Get total expenses
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM family_expenses 
        WHERE family_id = ?
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_assoc()['total_expenses'];
    
    return max(0, $contributions - $expenses);
}

// =============================================
// PAYMENT REQUEST FUNCTIONS
// =============================================

/**
 * Create payment request
 */
function createPaymentRequest($conn, $sender_id, $recipients, $family_id, $amount, $purpose, $send_sms = false) {
    try {
        $conn->begin_transaction();
        $request_ids = [];
        
        foreach ($recipients as $recipient) {
            // Determine recipient ID based on member type
            $recipient_id = null;
            if ($recipient['member_type'] == 'user') {
                $recipient_id = $recipient['user_id'];
            } else {
                // For family_members_only, we'll use a special approach
                // Create or get a dummy user record, or handle differently
                // For now, we'll store in a modified way
                $recipient_id = $sender_id; // Temporary solution
            }
            
            $stmt = $conn->prepare("
                INSERT INTO payment_requests (
                    sender_id, recipient_id, family_id, amount, purpose, 
                    status, send_sms, requested_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->bind_param("iiidsi", $sender_id, $recipient_id, $family_id, $amount, $purpose, $send_sms);
            
            if ($stmt->execute()) {
                $request_ids[] = $conn->insert_id;
                
                // Send SMS if requested (implement SMS gateway integration)
                if ($send_sms && !empty($recipient['phone_number'])) {
                    sendPaymentRequestSMS($recipient['phone_number'], $amount, $purpose);
                }
            }
        }
        
        $conn->commit();
        return $request_ids;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Create payment request error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent payment requests
 */
function getRecentPaymentRequests($conn, $family_id, $limit = 10, $offset = 0) {
    $stmt = $conn->prepare("
        SELECT 
            pr.*,
            u1.first_name as sender_first_name,
            u1.last_name as sender_last_name,
            u2.first_name as recipient_first_name,
            u2.last_name as recipient_last_name,
            DATE_FORMAT(pr.requested_at, '%M %d, %Y at %h:%i %p') as formatted_date,
            CASE 
                WHEN pr.status = 'pending' THEN 'warning'
                WHEN pr.status = 'completed' THEN 'success'
                WHEN pr.status = 'failed' THEN 'danger'
                ELSE 'secondary'
            END as status_class
        FROM payment_requests pr
        LEFT JOIN users u1 ON pr.sender_id = u1.id
        LEFT JOIN users u2 ON pr.recipient_id = u2.id
        WHERE pr.family_id = ?
        ORDER BY pr.requested_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $family_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $row['formatted_amount'] = '₵' . number_format($row['amount'], 2);
        $requests[] = $row;
    }
    
    return $requests;
}

/**
 * Update payment request status
 */
function updatePaymentRequestStatus($conn, $request_id, $status, $transaction_id = null) {
    try {
        $completed_at = ($status === 'completed') ? ', completed_at = NOW()' : '';
        $sql = "UPDATE payment_requests SET status = ?, momo_transaction_id = ? $completed_at WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $transaction_id, $request_id);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Update request status error: " . $e->getMessage());
        return false;
    }
}

// =============================================
// FAMILY MEMBER FUNCTIONS
// =============================================

/**
 * Get family members for payment requests
 */
function getFamilyMembersForPayment($conn, $family_id) {
    $members = [];
    
    // Get registered family members
    $stmt = $conn->prepare("
        SELECT 
            fm.id as member_id,
            u.id as user_id,
            u.first_name,
            u.last_name,
            u.phone_number,
            fm.role,
            fm.display_name,
            'user' as member_type,
            CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        WHERE fm.family_id = ? AND fm.is_active = 1 AND u.is_active = 1
        ORDER BY fm.role, u.first_name
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    
    // Get non-registered family members
    $stmt = $conn->prepare("
        SELECT 
            id as member_id,
            NULL as user_id,
            first_name,
            last_name,
            phone_number,
            role,
            'member' as member_type,
            CONCAT(first_name, ' ', last_name) as full_name,
            momo_network,
            momo_number
        FROM family_members_only
        WHERE family_id = ? AND is_active = 1
        ORDER BY role, first_name
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    
    return $members;
}

// =============================================
// STATISTICS & REPORTING
// =============================================

/**
 * Get MoMo statistics
 */
function getMoMoStats($conn, $family_id) {
    // Total contributions this month
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_contributions
        FROM family_contributions 
        WHERE family_id = ? AND MONTH(contribution_date) = MONTH(CURRENT_DATE())
        AND YEAR(contribution_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $monthly_contributions = $stmt->get_result()->fetch_assoc()['monthly_contributions'];
    
    // Total expenses this month
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_expenses
        FROM family_expenses 
        WHERE family_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE())
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $monthly_expenses = $stmt->get_result()->fetch_assoc()['monthly_expenses'];
    
    // Pending payment requests
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending_requests, COALESCE(SUM(amount), 0) as pending_amount
        FROM payment_requests 
        WHERE family_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    
    // Recent transaction count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as recent_transactions
        FROM payment_requests 
        WHERE family_id = ? AND requested_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $recent_transactions = $stmt->get_result()->fetch_assoc()['recent_transactions'];
    
    return [
        'monthly_contributions' => $monthly_contributions,
        'monthly_expenses' => $monthly_expenses,
        'net_monthly' => $monthly_contributions - $monthly_expenses,
        'pending_requests' => $pending['pending_requests'],
        'pending_amount' => $pending['pending_amount'],
        'recent_transactions' => $recent_transactions,
        'formatted' => [
            'monthly_contributions' => '₵' . number_format($monthly_contributions, 2),
            'monthly_expenses' => '₵' . number_format($monthly_expenses, 2),
            'net_monthly' => '₵' . number_format($monthly_contributions - $monthly_expenses, 2),
            'pending_amount' => '₵' . number_format($pending['pending_amount'], 2)
        ]
    ];
}

// =============================================
// UTILITY FUNCTIONS
// =============================================

/**
 * Log MoMo activity
 */
function logMoMoActivity($conn, $family_id, $user_id, $action_type, $description, $metadata = []) {
    try {
        $metadata_json = json_encode($metadata);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, family_id, action_type, description, 
                metadata, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisssss", $user_id, $family_id, $action_type, $description, 
                         $metadata_json, $ip_address, $user_agent);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send SMS notification for payment request
 */
function sendPaymentRequestSMS($phone_number, $amount, $purpose) {
    // Implement SMS gateway integration here
    // This is a placeholder for actual SMS sending
    try {
        $message = "Payment request: ₵" . number_format($amount, 2) . " for " . $purpose . 
                  ". Please make payment via MoMo. Thank you!";
        
        // Log the SMS attempt
        error_log("SMS to {$phone_number}: {$message}");
        
        // Return true for now (implement actual SMS gateway)
        return true;
        
    } catch (Exception $e) {
        error_log("SMS sending error: " . $e->getMessage());
        return false;
    }
}

/**
 * Format currency amount
 */
function formatCurrency($amount, $currency = 'GHS') {
    $symbol = ($currency === 'GHS') ? '₵' : $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Generate transaction reference
 */
function generateTransactionReference($prefix = 'NKFAM') {
    return $prefix . date('Ymd') . rand(100000, 999999);
}

/**
 * Validate MoMo PIN format
 */
function validateMoMoPin($pin) {
    return strlen(trim($pin)) >= 4 && strlen(trim($pin)) <= 6 && is_numeric($pin);
}

/**
 * Get available MoMo networks
 */
function getAvailableMoMoNetworks() {
    $available = [];
    foreach (MOMO_NETWORKS as $key => $network) {
        if ($network['available']) {
            $available[$key] = $network;
        }
    }
    return $available;
}

/**
 * Check if MoMo service is available
 */
function isMoMoServiceAvailable($network = null) {
    if ($network) {
        $networkInfo = getNetworkInfo($network);
        return $networkInfo['available'];
    }
    
    // Check if any network is available
    foreach (MOMO_NETWORKS as $net) {
        if ($net['available']) {
            return true;
        }
    }
    
    return false;
}

?>