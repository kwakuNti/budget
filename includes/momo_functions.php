<?php
// Fixed MoMo Functions - Add these to your momo_functions.php file

/**
 * Get family MoMo account (primary function)
 */
function getFamilyMoMoAccount($conn, $family_id) {
    try {
        error_log("getFamilyMoMoAccount called with family_id: $family_id");
        
        $stmt = $conn->prepare("
            SELECT ma.*, fg.family_name 
            FROM momo_accounts ma 
            LEFT JOIN family_groups fg ON ma.family_id = fg.id 
            WHERE ma.family_id = ? AND ma.is_active = 1 
            ORDER BY ma.is_primary DESC, ma.id DESC 
            LIMIT 1
        ");
        
        if (!$stmt) {
            error_log("getFamilyMoMoAccount - Prepare failed: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $family_id);
        
        if (!$stmt->execute()) {
            error_log("getFamilyMoMoAccount - Execute failed: " . $stmt->error);
            return null;
        }
        
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();
        
        error_log("getFamilyMoMoAccount - Result: " . json_encode($account));
        
        $stmt->close();
        
        return $account;
    } catch (Exception $e) {
        error_log("getFamilyMoMoAccount error: " . $e->getMessage());
        error_log("getFamilyMoMoAccount stack trace: " . $e->getTraceAsString());
        return null;
    }
}

/**
 * Get family MoMo account (simple version - fallback)
 */
function getFamilyMoMoAccountSimple($conn, $family_id) {
    try {
        error_log("getFamilyMoMoAccountSimple called with family_id: $family_id");
        
        $stmt = $conn->prepare("SELECT * FROM momo_accounts WHERE family_id = ? AND is_active = 1 LIMIT 1");
        
        if (!$stmt) {
            error_log("getFamilyMoMoAccountSimple - Prepare failed: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $family_id);
        
        if (!$stmt->execute()) {
            error_log("getFamilyMoMoAccountSimple - Execute failed: " . $stmt->error);
            return null;
        }
        
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();
        
        error_log("getFamilyMoMoAccountSimple - Result: " . json_encode($account));
        
        $stmt->close();
        
        return $account;
    } catch (Exception $e) {
        error_log("getFamilyMoMoAccountSimple error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get MoMo statistics
 */
function getMoMoStats($conn, $family_id) {
    try {
        $stats = [
            'total_requests' => 0,
            'total_received' => 0,
            'pending_requests' => 0,
            'recent_requests' => 0,
            'recent_received' => 0
        ];
        
        // Get total requests
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM payment_requests WHERE family_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['total_requests'] = intval($row['total'] ?? 0);
            $stmt->close();
        }
        
        // Get total received
        $stmt = $conn->prepare("SELECT SUM(amount) as total FROM payment_requests WHERE family_id = ? AND status = 'completed'");
        if ($stmt) {
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['total_received'] = floatval($row['total'] ?? 0);
            $stmt->close();
        }
        
        // Get pending requests
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM payment_requests WHERE family_id = ? AND status = 'pending'");
        if ($stmt) {
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['pending_requests'] = intval($row['total'] ?? 0);
            $stmt->close();
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("getMoMoStats error: " . $e->getMessage());
        return [
            'total_requests' => 0,
            'total_received' => 0,
            'pending_requests' => 0,
            'recent_requests' => 0,
            'recent_received' => 0
        ];
    }
}

/**
 * Get recent payment requests
 */
function getRecentPaymentRequests($conn, $family_id, $limit = 10) {
    try {
        $stmt = $conn->prepare("
            SELECT pr.*, 
                   COALESCE(fu.full_name, au.full_name) as recipient_name,
                   COALESCE(fu.phone_number, au.phone_number) as recipient_phone
            FROM payment_requests pr
            LEFT JOIN family_users fu ON pr.recipient_id = fu.id AND pr.recipient_type = 'family_user'
            LEFT JOIN app_users au ON pr.recipient_id = au.id AND pr.recipient_type = 'app_user'
            WHERE pr.family_id = ?
            ORDER BY pr.requested_at DESC
            LIMIT ?
        ");
        
        if (!$stmt) {
            error_log("getRecentPaymentRequests - Prepare failed: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("ii", $family_id, $limit);
        
        if (!$stmt->execute()) {
            error_log("getRecentPaymentRequests - Execute failed: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        $requests = [];
        
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        $stmt->close();
        
        return $requests;
    } catch (Exception $e) {
        error_log("getRecentPaymentRequests error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get family members for payment requests
 */
function getFamilyMembersForPayment($conn, $family_id) {
    try {
        $members = [];
        
        // Get family users
        $stmt = $conn->prepare("
            SELECT 'family_user' as member_type, id as member_id, full_name, display_name, phone_number
            FROM family_users 
            WHERE family_id = ? AND is_active = 1
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt->close();
        }
        
        // Get app users (if they're part of the family)
        $stmt = $conn->prepare("
            SELECT 'app_user' as member_type, au.id as member_id, au.full_name, NULL as display_name, au.phone_number
            FROM app_users au
            INNER JOIN family_groups fg ON au.id = fg.created_by
            WHERE fg.id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $family_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt->close();
        }
        
        return $members;
    } catch (Exception $e) {
        error_log("getFamilyMembersForPayment error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can perform MoMo operations
 */
function canPerformMoMoOperations($conn, $user_id, $family_id) {
    try {
        // For now, allow all operations for family members
        // You can add more specific permission checks here later
        return true;
    } catch (Exception $e) {
        error_log("canPerformMoMoOperations error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get network information
 */
function getNetworkInfo($network) {
    $networks = [
        'mtn' => [
            'name' => 'MTN Mobile Money',
            'short' => 'MTN',
            'code' => 'mtn',
            'color' => '#ffcc00'
        ],
        'vodafone' => [
            'name' => 'Vodafone Cash',
            'short' => 'Voda',
            'code' => 'vodafone',
            'color' => '#e60000'
        ],
        'airteltigo' => [
            'name' => 'AirtelTigo Money',
            'short' => 'AT',
            'code' => 'airteltigo',
            'color' => '#ff6600'
        ]
    ];
    
    return $networks[$network] ?? $networks['mtn'];
}

/**
 * Check if MoMo functions are working
 */
function testMoMoFunctions($conn, $family_id) {
    error_log("=== TESTING MOMO FUNCTIONS ===");
    
    $account = getFamilyMoMoAccount($conn, $family_id);
    error_log("Test - getFamilyMoMoAccount result: " . json_encode($account));
    
    if (!$account) {
        $account = getFamilyMoMoAccountSimple($conn, $family_id);
        error_log("Test - getFamilyMoMoAccountSimple result: " . json_encode($account));
    }
    
    $stats = getMoMoStats($conn, $family_id);
    error_log("Test - getMoMoStats result: " . json_encode($stats));
    
    $requests = getRecentPaymentRequests($conn, $family_id, 5);
    error_log("Test - getRecentPaymentRequests count: " . count($requests));
    
    $members = getFamilyMembersForPayment($conn, $family_id);
    error_log("Test - getFamilyMembersForPayment count: " . count($members));
    
    error_log("=== END TESTING MOMO FUNCTIONS ===");
    
    return $account;
}
?>