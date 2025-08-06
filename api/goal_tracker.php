<?php
// api/goal_tracker.php

// Prevent any output before JSON response
ob_start();

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any output buffer to prevent HTML errors
ob_clean();

try {
    require_once '../config/connection.php';
    require_once '../includes/goal_tracking.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load required files: ' . $e->getMessage()
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_dashboard_data':
            getDashboardData($conn, $family_id);
            break;
            
        case 'close_cycle':
            closeCycle($conn, $family_id, $user_id);
            break;
            
        case 'create_cycle':
            createCycle($conn, $family_id, $user_id);
            break;
            
        case 'clear_member_debt':
            clearMemberDebt($conn, $family_id);
            break;
            
        case 'send_reminder':
            sendPaymentReminder($conn, $family_id);
            break;
            
        case 'get_debt_summary':
            getDebtSummary($conn, $family_id);
            break;
            
        case 'update_goal':
            updateMemberGoal($conn, $family_id);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

function getDashboardData($conn, $family_id) {
    try {
        // Auto-create cycle if needed
        autoCreateMonthlyCycleIfNeeded($conn, $family_id);
        
        // Get current cycle information
        $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
        
        // Get member performance with actual contribution data
        $memberPerformance = getMemberPerformanceWithContributions($conn, $family_id);
        $membersWithDebt = getMembersWithDebt($conn, $family_id);
        $debtHistory = getFamilyDebtHistory($conn, $family_id, 6);
        
        // Filter members to exclude admin, system users, and logged-in user
        $filteredMembers = array_filter($memberPerformance, function($member) {
            $isNotAdmin = $member['role'] !== 'admin';
            $isNotSystemUser = !stripos($member['full_name'], 'admin');
            $isNotLoggedInUser = !stripos($member['full_name'], 'nkansah family');
            $hasGoal = floatval($member['target_amount'] ?? 0) > 0;
            
            return $isNotAdmin && $isNotSystemUser && $isNotLoggedInUser && $hasGoal;
        });
        
        // Calculate metrics from filtered members only
        $totalTarget = 0;
        $totalContributed = 0;
        $completedMembers = 0;
        
        foreach ($filteredMembers as $member) {
            $totalTarget += floatval($member['target_amount'] ?? 0);
            $totalContributed += floatval($member['contributed_amount'] ?? 0);
            if ($member['is_completed']) {
                $completedMembers++;
            }
        }
        
        $completionPercentage = $totalTarget > 0 ? round(($totalContributed / $totalTarget) * 100, 2) : 0;
        
        // Calculate days remaining in cycle
        $today = new DateTime();
        $endDate = new DateTime($currentCycle['end_date'] ?? 'now');
        $daysRemaining = max(0, $today->diff($endDate)->days);
        if ($today > $endDate) $daysRemaining = 0;
        
        $totalDays = date('t', strtotime($currentCycle['start_date'] ?? 'now'));
        
        $response = [
            'success' => true,
            'data' => [
                'currentCycle' => [
                    'id' => $currentCycle['id'] ?? null,
                    'title' => date('F Y', strtotime($currentCycle['start_date'] ?? 'now')) . ' Contribution Cycle',
                    'daysRemaining' => $daysRemaining,
                    'totalDays' => $totalDays,
                    'membersCompleted' => $completedMembers,
                    'totalMembers' => count($filteredMembers), // Use filtered count
                    'totalCollected' => $totalContributed,
                    'totalTarget' => $totalTarget,
                    'completionPercentage' => $completionPercentage,
                    'isClosed' => $currentCycle['is_closed'] ?? false
                ],
                'memberPerformance' => array_values($filteredMembers), // Return filtered members
                'membersWithDebt' => $membersWithDebt, // This is already filtered in its function
                'debtHistory' => $debtHistory
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        throw new Exception('Error getting dashboard data: ' . $e->getMessage());
    }
}

function getMembersWithDebt($conn, $family_id) {
    $membersWithDebt = [];
    
    // Get registered members with debt - with explicit filtering
    $stmt = $conn->prepare("
        SELECT 
            fm.id,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.first_name,
            fm.role,
            fm.accumulated_debt,
            fm.months_behind,
            fm.monthly_contribution_goal,
            fm.current_month_contributed,
            fm.goal_met_this_month,
            fm.last_payment_date,
            'user' as member_type
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        WHERE fm.family_id = ? 
        AND fm.is_active = TRUE 
        AND fm.role != 'admin'
        AND LOWER(CONCAT(u.first_name, ' ', u.last_name)) NOT LIKE '%admin%'
        AND LOWER(CONCAT(u.first_name, ' ', u.last_name)) NOT LIKE '%nkansah family%'
        AND (fm.accumulated_debt > 0 OR fm.goal_met_this_month = FALSE)
        ORDER BY fm.months_behind DESC, fm.accumulated_debt DESC
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $registeredMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get non-registered members with debt - with explicit filtering
    $stmt = $conn->prepare("
        SELECT 
            fmo.id,
            CONCAT(fmo.first_name, ' ', fmo.last_name) as full_name,
            fmo.first_name,
            fmo.role,
            fmo.accumulated_debt,
            fmo.months_behind,
            fmo.monthly_contribution_goal,
            fmo.current_month_contributed,
            fmo.goal_met_this_month,
            fmo.last_payment_date,
            'member' as member_type
        FROM family_members_only fmo
        WHERE fmo.family_id = ? 
        AND fmo.is_active = TRUE 
        AND fmo.role != 'admin'
        AND LOWER(CONCAT(fmo.first_name, ' ', fmo.last_name)) NOT LIKE '%admin%'
        AND LOWER(CONCAT(fmo.first_name, ' ', fmo.last_name)) NOT LIKE '%nkansah family%'
        AND (fmo.accumulated_debt > 0 OR fmo.goal_met_this_month = FALSE)
        ORDER BY fmo.months_behind DESC, fmo.accumulated_debt DESC
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $nonRegisteredMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return array_merge($registeredMembers, $nonRegisteredMembers);
}

function validateMemberFiltering($member) {
    // Server-side validation to ensure proper filtering
    $isNotAdmin = strtolower($member['role'] ?? '') !== 'admin';
    $isNotSystemUser = stripos($member['full_name'] ?? '', 'admin') === false;
    $isNotLoggedInUser = stripos($member['full_name'] ?? '', 'nkansah family') === false;
    $hasGoal = floatval($member['target_amount'] ?? 0) > 0;
    
    return $isNotAdmin && $isNotSystemUser && $isNotLoggedInUser && $hasGoal;
}
function getMemberPerformanceWithContributions($conn, $family_id) {
    $cycle = getCurrentMonthlyCycle($conn, $family_id);
    if (!$cycle) {
        return [];
    }
    
    // Get current month for contribution calculations
    $currentMonth = date('Y-m');
    $startOfMonth = $currentMonth . '-01';
    $endOfMonth = date('Y-m-t');
    
    $members = [];
    
    // Get registered family members with their actual contributions - with explicit filtering
    $stmt = $conn->prepare("
        SELECT 
            fm.id as member_id,
            NULL as member_only_id,
            'user' as member_type,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.first_name,
            fm.role,
            fm.monthly_contribution_goal as target_amount,
            fm.accumulated_debt,
            fm.months_behind,
            fm.last_payment_date,
            -- Calculate actual contributions for current month
            COALESCE(SUM(fc.amount), 0) as contributed_amount,
            COUNT(fc.id) as contribution_count,
            MAX(fc.contribution_date) as last_contribution_date,
            -- Check if goal is met
            CASE 
                WHEN COALESCE(SUM(fc.amount), 0) >= fm.monthly_contribution_goal THEN TRUE 
                ELSE FALSE 
            END as is_completed
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_contributions fc ON fm.id = fc.member_id 
            AND fc.contribution_date >= ? 
            AND fc.contribution_date <= ?
            AND fc.family_id = ?
        WHERE fm.family_id = ? 
        AND fm.is_active = TRUE
        AND fm.role != 'admin'
        AND LOWER(CONCAT(u.first_name, ' ', u.last_name)) NOT LIKE '%admin%'
        AND LOWER(CONCAT(u.first_name, ' ', u.last_name)) NOT LIKE '%nkansah family%'
        AND fm.monthly_contribution_goal > 0
        GROUP BY fm.id, u.first_name, u.last_name, fm.role, fm.monthly_contribution_goal, fm.accumulated_debt, fm.months_behind, fm.last_payment_date
    ");
    $stmt->bind_param("ssii", $startOfMonth, $endOfMonth, $family_id, $family_id);
    $stmt->execute();
    $registeredMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get non-registered family members with their actual contributions - with explicit filtering
    $stmt = $conn->prepare("
        SELECT 
            NULL as member_id,
            fmo.id as member_only_id,
            'member' as member_type,
            CONCAT(fmo.first_name, ' ', fmo.last_name) as full_name,
            fmo.first_name,
            fmo.role,
            fmo.monthly_contribution_goal as target_amount,
            fmo.accumulated_debt,
            fmo.months_behind,
            fmo.last_payment_date,
            -- Calculate actual contributions for current month
            COALESCE(SUM(fc.amount), 0) as contributed_amount,
            COUNT(fc.id) as contribution_count,
            MAX(fc.contribution_date) as last_contribution_date,
            -- Check if goal is met
            CASE 
                WHEN COALESCE(SUM(fc.amount), 0) >= fmo.monthly_contribution_goal THEN TRUE 
                ELSE FALSE 
            END as is_completed
        FROM family_members_only fmo
        LEFT JOIN family_contributions fc ON fmo.id = fc.member_only_id 
            AND fc.contribution_date >= ? 
            AND fc.contribution_date <= ?
            AND fc.family_id = ?
        WHERE fmo.family_id = ? 
        AND fmo.is_active = TRUE
        AND fmo.role != 'admin'
        AND LOWER(CONCAT(fmo.first_name, ' ', fmo.last_name)) NOT LIKE '%admin%'
        AND LOWER(CONCAT(fmo.first_name, ' ', fmo.last_name)) NOT LIKE '%nkansah family%'
        AND fmo.monthly_contribution_goal > 0
        GROUP BY fmo.id, fmo.first_name, fmo.last_name, fmo.role, fmo.monthly_contribution_goal, fmo.accumulated_debt, fmo.months_behind, fmo.last_payment_date
    ");
    $stmt->bind_param("ssii", $startOfMonth, $endOfMonth, $family_id, $family_id);
    $stmt->execute();
    $nonRegisteredMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combine both arrays and add calculated fields
    $allMembers = array_merge($registeredMembers, $nonRegisteredMembers);
    
    foreach ($allMembers as &$member) {
        $member['contributed_amount'] = floatval($member['contributed_amount']);
        $member['target_amount'] = floatval($member['target_amount']);
        $member['contribution_count'] = intval($member['contribution_count']);
        $member['accumulated_debt'] = floatval($member['accumulated_debt']);
        $member['months_behind'] = intval($member['months_behind']);
        
        // Calculate progress percentage
        if ($member['target_amount'] > 0) {
            $member['progress_percentage'] = min(100, round(($member['contributed_amount'] / $member['target_amount']) * 100, 2));
        } else {
            $member['progress_percentage'] = 0;
        }
        
        // Update member performance in the performance table if cycle exists
        if ($cycle) {
            updateMemberPerformanceRecord($conn, $cycle['id'], $member);
        }
    }
    
    return $allMembers;
}
function debugMemberFiltering($conn, $family_id) {
    error_log("=== MEMBER FILTERING DEBUG ===");
    
    // Get all members for debugging
    $stmt = $conn->prepare("
        SELECT 
            fm.id,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            fm.role,
            fm.monthly_contribution_goal,
            'user' as member_type
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        WHERE fm.family_id = ? AND fm.is_active = TRUE
        UNION ALL
        SELECT 
            fmo.id,
            CONCAT(fmo.first_name, ' ', fmo.last_name) as full_name,
            fmo.role,
            fmo.monthly_contribution_goal,
            'member' as member_type
        FROM family_members_only fmo
        WHERE fmo.family_id = ? AND fmo.is_active = TRUE
    ");
    $stmt->bind_param("ii", $family_id, $family_id);
    $stmt->execute();
    $allMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    error_log("Total members found: " . count($allMembers));
    
    foreach ($allMembers as $member) {
        $shouldInclude = validateMemberFiltering($member);
        error_log("Member: {$member['full_name']} | Role: {$member['role']} | Goal: {$member['monthly_contribution_goal']} | Include: " . ($shouldInclude ? 'YES' : 'NO'));
    }
    
    error_log("=== END DEBUG ===");
}

function updateMemberPerformanceRecord($conn, $cycle_id, $member) {
    // Check if performance record exists
    if ($member['member_type'] === 'user') {
        $stmt = $conn->prepare("
            SELECT id FROM member_monthly_performance 
            WHERE cycle_id = ? AND member_id = ? AND member_type = 'user'
        ");
        $stmt->bind_param("ii", $cycle_id, $member['member_id']);
    } else {
        $stmt = $conn->prepare("
            SELECT id FROM member_monthly_performance 
            WHERE cycle_id = ? AND member_only_id = ? AND member_type = 'member'
        ");
        $stmt->bind_param("ii", $cycle_id, $member['member_only_id']);
    }
    
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if ($exists) {
        // Update existing record
        if ($member['member_type'] === 'user') {
            $stmt = $conn->prepare("
                UPDATE member_monthly_performance 
                SET 
                    contributed_amount = ?,
                    is_completed = ?,
                    contribution_count = ?,
                    last_contribution_date = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE cycle_id = ? AND member_id = ? AND member_type = 'user'
            ");
            $stmt->bind_param("diisii", 
                $member['contributed_amount'],
                $member['is_completed'],
                $member['contribution_count'],
                $member['last_contribution_date'],
                $cycle_id,
                $member['member_id']
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE member_monthly_performance 
                SET 
                    contributed_amount = ?,
                    is_completed = ?,
                    contribution_count = ?,
                    last_contribution_date = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE cycle_id = ? AND member_only_id = ? AND member_type = 'member'
            ");
            $stmt->bind_param("diisii", 
                $member['contributed_amount'],
                $member['is_completed'],
                $member['contribution_count'],
                $member['last_contribution_date'],
                $cycle_id,
                $member['member_only_id']
            );
        }
        $stmt->execute();
    } else {
        // Create new record
        if ($member['member_type'] === 'user') {
            $stmt = $conn->prepare("
                INSERT INTO member_monthly_performance (
                    cycle_id, family_id, member_id, member_type, target_amount,
                    contributed_amount, is_completed, contribution_count, last_contribution_date
                ) VALUES (?, ?, ?, 'user', ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiididis", 
                $cycle_id,
                $_SESSION['family_id'],
                $member['member_id'],
                $member['target_amount'],
                $member['contributed_amount'],
                $member['is_completed'],
                $member['contribution_count'],
                $member['last_contribution_date']
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO member_monthly_performance (
                    cycle_id, family_id, member_only_id, member_type, target_amount,
                    contributed_amount, is_completed, contribution_count, last_contribution_date
                ) VALUES (?, ?, ?, 'member', ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiididis", 
                $cycle_id,
                $_SESSION['family_id'],
                $member['member_only_id'],
                $member['target_amount'],
                $member['contributed_amount'],
                $member['is_completed'],
                $member['contribution_count'],
                $member['last_contribution_date']
            );
        }
        $stmt->execute();
    }
}

function closeCycle($conn, $family_id, $user_id) {
    try {
        $currentCycle = getCurrentMonthlyCycle($conn, $family_id);
        
        if (!$currentCycle) {
            throw new Exception('No active cycle found');
        }
        
        if ($currentCycle['is_closed']) {
            throw new Exception('Cycle is already closed');
        }
        
        $success = closeMonthlyCycle($conn, $currentCycle['id'], $user_id);
        
        if ($success) {
            // Create next month's cycle
            $nextMonth = date('n', strtotime('+1 month', strtotime($currentCycle['start_date'])));
            $nextYear = date('Y', strtotime('+1 month', strtotime($currentCycle['start_date'])));
            
            createNewMonthlyCycle($conn, $family_id, $nextYear, $nextMonth);
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, family_id, action_type, description) 
                VALUES (?, ?, 'cycle_closed', 'Monthly cycle closed and new cycle created')
            ");
            $stmt->bind_param("ii", $user_id, $family_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Monthly cycle closed successfully. New cycle created for next month.'
            ]);
        } else {
            throw new Exception('Failed to close monthly cycle');
        }
    } catch (Exception $e) {
        throw new Exception('Error closing cycle: ' . $e->getMessage());
    }
}

function createCycle($conn, $family_id, $user_id) {
    try {
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('n');
        
        $success = createNewMonthlyCycle($conn, $family_id, $year, $month);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'New monthly cycle created successfully'
            ]);
        } else {
            throw new Exception('Failed to create monthly cycle');
        }
    } catch (Exception $e) {
        throw new Exception('Error creating cycle: ' . $e->getMessage());
    }
}

function clearMemberDebt($conn, $family_id) {
    try {
        $member_id = $_POST['member_id'] ?? null;
        $member_only_id = $_POST['member_only_id'] ?? null;
        $member_type = $_POST['member_type'] ?? 'user';
        
        if (!$member_id && !$member_only_id) {
            throw new Exception('Member ID is required');
        }
        
        // Clear debt based on member type
        if ($member_type === 'user' && $member_id) {
            $stmt = $conn->prepare("
                UPDATE family_members 
                SET accumulated_debt = 0, months_behind = 0, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("ii", $member_id, $family_id);
        } else if ($member_only_id) {
            $stmt = $conn->prepare("
                UPDATE family_members_only 
                SET accumulated_debt = 0, months_behind = 0, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("ii", $member_only_id, $family_id);
        }
        
        $success = $stmt->execute();
        
        if ($success) {
            // Mark debt history as cleared
            $stmt = $conn->prepare("
                UPDATE member_debt_history 
                SET is_cleared = TRUE, cleared_date = CURDATE()
                WHERE family_id = ? 
                AND ((member_type = 'user' AND member_id = ?) OR (member_type = 'member' AND member_only_id = ?))
                AND is_cleared = FALSE
            ");
            $stmt->bind_param("iii", $family_id, $member_id, $member_only_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Member debt cleared successfully'
            ]);
        } else {
            throw new Exception('Failed to clear member debt');
        }
    } catch (Exception $e) {
        throw new Exception('Error clearing member debt: ' . $e->getMessage());
    }
}

function sendPaymentReminder($conn, $family_id) {
    try {
        $member_id = $_POST['member_id'] ?? null;
        $member_only_id = $_POST['member_only_id'] ?? null;
        $member_type = $_POST['member_type'] ?? 'user';
        $reminder_type = $_POST['reminder_type'] ?? 'gentle';
        $bulk_send = $_POST['bulk_send'] ?? false;
        
        if ($bulk_send) {
            $membersWithDebt = getMembersWithDebt($conn, $family_id);
            $sentCount = count($membersWithDebt);
            
            echo json_encode([
                'success' => true,
                'message' => "Payment reminders would be sent to $sentCount members (SMS integration needed)"
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Payment reminder would be sent (SMS integration needed)'
            ]);
        }
    } catch (Exception $e) {
        throw new Exception('Error sending payment reminder: ' . $e->getMessage());
    }
}

function getDebtSummary($conn, $family_id) {
    try {
        $membersWithDebt = getMembersWithDebt($conn, $family_id);
        $debtHistory = getFamilyDebtHistory($conn, $family_id, 12);
        
        $totalDebt = array_sum(array_column($membersWithDebt, 'accumulated_debt'));
        $totalMembersWithDebt = count($membersWithDebt);
        $maxMonthsBehind = $membersWithDebt ? max(array_column($membersWithDebt, 'months_behind')) : 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_debt' => $totalDebt,
                    'total_members_with_debt' => $totalMembersWithDebt,
                    'max_months_behind' => $maxMonthsBehind
                ],
                'members_with_debt' => $membersWithDebt,
                'debt_history' => $debtHistory
            ]
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting debt summary: ' . $e->getMessage());
    }
}

function updateMemberGoal($conn, $family_id) {
    try {
        $member_id = $_POST['member_id'] ?? null;
        $member_only_id = $_POST['member_only_id'] ?? null;
        $member_type = $_POST['member_type'] ?? 'user';
        $new_goal = floatval($_POST['new_goal'] ?? 0);
        
        if (!$member_id && !$member_only_id) {
            throw new Exception('Member ID is required');
        }
        
        if ($new_goal < 0) {
            throw new Exception('Goal amount must be positive');
        }
        
        if ($member_type === 'user' && $member_id) {
            $stmt = $conn->prepare("
                UPDATE family_members 
                SET monthly_contribution_goal = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("dii", $new_goal, $member_id, $family_id);
        } else if ($member_only_id) {
            $stmt = $conn->prepare("
                UPDATE family_members_only 
                SET monthly_contribution_goal = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND family_id = ?
            ");
            $stmt->bind_param("dii", $new_goal, $member_only_id, $family_id);
        } else {
            throw new Exception('Invalid member type or ID');
        }
        
        $success = $stmt->execute();
        
        if ($success && $stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Member goal updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update member goal or no changes made');
        }
    } catch (Exception $e) {
        throw new Exception('Error updating member goal: ' . $e->getMessage());
    }
}

?>