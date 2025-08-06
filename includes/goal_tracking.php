<?php
// goal_tracking.php

require_once '../config/connection.php';
/**
 * Get cycle statistics for dashboard
 */
function getCycleStatistics($conn, $familyId, $months = 6) {
    $stmt = $conn->prepare("
        SELECT 
            mc.cycle_month,
            mc.total_target,
            mc.total_collected,
            mc.members_completed,
            mc.members_pending,
            mc.is_closed,
            ROUND((mc.total_collected / mc.total_target) * 100, 2) as completion_rate
        FROM monthly_cycles mc
        WHERE mc.family_id = ?
        ORDER BY mc.cycle_month DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $familyId, $months);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get member's personal debt summary
 */
function getMemberDebtSummary($conn, $memberId, $memberOnlyId = null, $memberType = 'user') {
    if ($memberType === 'user') {
        $stmt = $conn->prepare("
            SELECT 
                fm.accumulated_debt,
                fm.months_behind,
                fm.current_month_contributed,
                fm.monthly_contribution_goal,
                fm.goal_met_this_month,
                fm.last_payment_date,
                (SELECT COUNT(*) FROM member_debt_history 
                 WHERE member_id = ? AND is_cleared = FALSE) as unpaid_months
            FROM family_members fm
            WHERE fm.id = ?
        ");
        $stmt->bind_param("ii", $memberId, $memberId);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                fmo.accumulated_debt,
                fmo.months_behind,
                fmo.current_month_contributed,
                fmo.monthly_contribution_goal,
                fmo.goal_met_this_month,
                fmo.last_payment_date,
                (SELECT COUNT(*) FROM member_debt_history 
                 WHERE member_only_id = ? AND is_cleared = FALSE) as unpaid_months
            FROM family_members_only fmo
            WHERE fmo.id = ?
        ");
        $stmt->bind_param("ii", $memberOnlyId, $memberOnlyId);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get top contributors for current cycle
 */
function getTopContributors($conn, $familyId, $limit = 5) {
    $cycle = getCurrentMonthlyCycle($conn, $familyId);
    if (!$cycle) return [];
    
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN mp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                ELSE CONCAT(fmo.first_name, ' ', fmo.last_name)
            END as full_name,
            CASE 
                WHEN mp.member_type = 'user' THEN u.first_name
                ELSE fmo.first_name
            END as first_name,
            mp.contributed_amount,
            mp.target_amount,
            mp.is_completed,
            mp.contribution_count,
            ROUND((mp.contributed_amount / mp.target_amount) * 100, 2) as completion_percentage
        FROM member_monthly_performance mp
        LEFT JOIN family_members fm ON mp.member_id = fm.id
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mp.member_only_id = fmo.id
        WHERE mp.cycle_id = ?
        ORDER BY mp.contributed_amount DESC, mp.completion_percentage DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $cycle['id'], $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Send payment reminder (placeholder for SMS/notification system)
 */


/**
 * Calculate payment suggestions based on remaining days in cycle
 */
function calculatePaymentSuggestions($conn, $familyId) {
    $cycle = getCurrentMonthlyCycle($conn, $familyId);
    if (!$cycle) return [];
    
    $today = new DateTime();
    $endDate = new DateTime($cycle['end_date']);
    $daysRemaining = $today->diff($endDate)->days;
    
    $stmt = $conn->prepare("
        SELECT 
            mp.*,
            CASE 
                WHEN mp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                ELSE CONCAT(fmo.first_name, ' ', fmo.last_name)
            END as full_name,
            (mp.target_amount - mp.contributed_amount) as remaining_amount
        FROM member_monthly_performance mp
        LEFT JOIN family_members fm ON mp.member_id = fm.id
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mp.member_only_id = fmo.id
        WHERE mp.cycle_id = ? AND mp.is_completed = FALSE
        ORDER BY (mp.target_amount - mp.contributed_amount) DESC
    ");
    $stmt->bind_param("i", $cycle['id']);
    $stmt->execute();
    $pendingMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $suggestions = [];
    foreach ($pendingMembers as $member) {
        $remainingAmount = $member['remaining_amount'];
        $dailySuggestion = $daysRemaining > 0 ? round($remainingAmount / $daysRemaining, 2) : $remainingAmount;
        $weeklySuggestion = $daysRemaining > 7 ? round($remainingAmount / ceil($daysRemaining / 7), 2) : $remainingAmount;
        
        $suggestions[] = [
            'member_name' => $member['full_name'],
            'remaining_amount' => $remainingAmount,
            'days_remaining' => $daysRemaining,
            'daily_suggestion' => $dailySuggestion,
            'weekly_suggestion' => $weeklySuggestion,
            'current_contributed' => $member['contributed_amount'],
            'target_amount' => $member['target_amount']
        ];
    }
    
    return $suggestions;
}

/**
 * Get current monthly cycle for a family
 */
function getCurrentMonthlyCycle($conn, $familyId) {
    $stmt = $conn->prepare("
        SELECT * FROM monthly_cycles 
        WHERE family_id = ? AND is_current = TRUE AND is_closed = FALSE
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get members with debt (those who haven't met their goals)
 */

/**
 * Get monthly performance summary
 */
function getMonthlyPerformanceSummary($conn, $familyId) {
    $cycle = getCurrentMonthlyCycle($conn, $familyId);
    if (!$cycle) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_members,
            SUM(CASE WHEN is_completed = TRUE THEN 1 ELSE 0 END) as completed_members,
            SUM(CASE WHEN is_completed = FALSE THEN 1 ELSE 0 END) as pending_members,
            SUM(target_amount) as total_target,
            SUM(contributed_amount) as total_contributed,
            ROUND((SUM(contributed_amount) / SUM(target_amount)) * 100, 2) as completion_percentage
        FROM member_monthly_performance
        WHERE cycle_id = ?
    ");
    $stmt->bind_param("i", $cycle['id']);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
    $summary['cycle_info'] = $cycle;
    return $summary;
}

/**
 * Get member performance details for current cycle
 */
function getMemberPerformanceDetails($conn, $familyId) {
    $cycle = getCurrentMonthlyCycle($conn, $familyId);
    if (!$cycle) {
        return [];
    }
    
    $stmt = $conn->prepare("
        SELECT 
            mp.*,
            CASE 
                WHEN mp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                ELSE CONCAT(fmo.first_name, ' ', fmo.last_name)
            END as full_name,
            CASE 
                WHEN mp.member_type = 'user' THEN u.first_name
                ELSE fmo.first_name
            END as first_name,
            CASE 
                WHEN mp.member_type = 'user' THEN fm.role
                ELSE fmo.role
            END as role,
            CASE 
                WHEN mp.member_type = 'user' THEN fm.accumulated_debt
                ELSE fmo.accumulated_debt
            END as accumulated_debt,
            CASE 
                WHEN mp.member_type = 'user' THEN fm.months_behind
                ELSE fmo.months_behind
            END as months_behind,
            ROUND((mp.contributed_amount / mp.target_amount) * 100, 2) as progress_percentage
        FROM member_monthly_performance mp
        LEFT JOIN family_members fm ON mp.member_id = fm.id
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mp.member_only_id = fmo.id
        WHERE mp.cycle_id = ?
        ORDER BY mp.is_completed ASC, mp.contributed_amount DESC
    ");
    $stmt->bind_param("i", $cycle['id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create new monthly cycle
 */
function createNewMonthlyCycle($conn, $familyId, $year = null, $month = null) {
    if (!$year) $year = date('Y');
    if (!$month) $month = date('n');
    
    try {
        $stmt = $conn->prepare("CALL CreateNewMonthlyCycle(?, ?, ?)");
        $stmt->bind_param("iii", $familyId, $year, $month);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error creating monthly cycle: " . $e->getMessage());
        return false;
    }
}

/**
 * Close current monthly cycle
 */
function closeMonthlyCycle($conn, $cycleId, $closedBy) {
    try {
        $stmt = $conn->prepare("CALL CloseMonthlyCycle(?, ?)");
        $stmt->bind_param("ii", $cycleId, $closedBy);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error closing monthly cycle: " . $e->getMessage());
        return false;
    }
}

/**
 * Get debt history for a family
 */
function getFamilyDebtHistory($conn, $familyId, $limit = 12) {
    $stmt = $conn->prepare("
        SELECT 
            mdh.*,
            CASE 
                WHEN mdh.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                ELSE CONCAT(fmo.first_name, ' ', fmo.last_name)
            END as full_name
        FROM member_debt_history mdh
        LEFT JOIN family_members fm ON mdh.member_id = fm.id
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mdh.member_only_id = fmo.id
        WHERE mdh.family_id = ?
        ORDER BY mdh.cycle_month DESC, mdh.deficit_amount DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $familyId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark member debt as cleared
 */


/**
 * Check if it's time to create a new cycle (beginning of month)
 */
function shouldCreateNewCycle($conn, $familyId) {
    $currentCycle = getCurrentMonthlyCycle($conn, $familyId);
    
    if (!$currentCycle) {
        return true; // No cycle exists
    }
    
    $today = date('Y-m-d');
    $cycleEndDate = $currentCycle['end_date'];
    
    // If we're past the end date and cycle isn't closed
    return $today > $cycleEndDate && !$currentCycle['is_closed'];
}

/**
 * Auto-create monthly cycle if needed
 */
function autoCreateMonthlyCycleIfNeeded($conn, $familyId) {
    if (shouldCreateNewCycle($conn, $familyId)) {
        $currentCycle = getCurrentMonthlyCycle($conn, $familyId);
        
        // Close previous cycle if it exists and isn't closed
        if ($currentCycle && !$currentCycle['is_closed']) {
            closeMonthlyCycle($conn, $currentCycle['id'], 1); // System closes it
        }
        
        // Create new cycle for current month
        return createNewMonthlyCycle($conn, $familyId);
    }
    return true;
}

