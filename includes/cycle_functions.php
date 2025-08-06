<?php
// cycle_functions.php

/**
 * Get current active cycle for family
 */

function getCurrentCycle($conn, $family_id) {
    $stmt = $conn->prepare("
        SELECT * FROM monthly_cycles 
        WHERE family_id = ? AND is_current = TRUE AND is_closed = FALSE
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Create new monthly cycle (auto or manual)
 */
function createMonthlyCycle($conn, $family_id, $year = null, $month = null) {
    if (!$year) $year = date('Y');
    if (!$month) $month = date('n');
    
    try {
        $stmt = $conn->prepare("CALL CreateNewMonthlyCycle(?, ?, ?)");
        $stmt->bind_param("iii", $family_id, $year, $month);
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        error_log("Error creating cycle: " . $e->getMessage());
        return false;
    }
}

/**
 * Close current cycle and handle debts
 * Improved version that properly manages cycle transitions
 */
function closeMonthlyCycle($conn, $cycle_id, $closed_by) {
    try {
        // Get cycle information before closing
        $cycle = getCycleById($conn, $cycle_id);
        if (!$cycle) {
            throw new Exception("Cycle not found");
        }
        
        // Include debt functions for debt calculation
        require_once __DIR__ . '/debt_functions.php';
        
        // Calculate and record any debts from this cycle
        $debtResult = calculateAndRecordDebts($conn, $cycle_id, $cycle['family_id']);
        if (!$debtResult['success']) {
            error_log("Warning: Failed to record debts during cycle closure: " . $debtResult['error']);
        }
        
        // Call the stored procedure to close the cycle
        $stmt = $conn->prepare("CALL CloseMonthlyCycle(?, ?)");
        $stmt->bind_param("ii", $cycle_id, $closed_by);
        $stmt->execute();
        
        // Log the cycle closure with debt information
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (family_id, action_type, description, created_at)
            VALUES (?, 'cycle_closed', ?, NOW())
        ");
        $description = "Monthly cycle closed for " . date('F Y', strtotime($cycle['start_date']));
        if ($debtResult['success'] && $debtResult['debts_recorded'] > 0) {
            $description .= " - {$debtResult['debts_recorded']} member(s) have outstanding debts";
        }
        $stmt->bind_param("is", $cycle['family_id'], $description);
        $stmt->execute();
        
        // Important: Do NOT create a new cycle immediately
        // New cycles should only be created at the start of the new month
        // This allows for proper month-end processing and prevents premature resets
        
        error_log("Cycle {$cycle_id} closed for family {$cycle['family_id']}. Debts processed: " . ($debtResult['success'] ? $debtResult['debts_recorded'] : 'failed'));
        
        return [
            'success' => true,
            'debts_recorded' => $debtResult['success'] ? $debtResult['debts_recorded'] : 0,
            'message' => $description
        ];
    } catch (Exception $e) {
        error_log("Error closing cycle: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get cycle by ID
 */
function getCycleById($conn, $cycle_id) {
    $stmt = $conn->prepare("SELECT * FROM monthly_cycles WHERE id = ?");
    $stmt->bind_param("i", $cycle_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Check if cycle should be auto-closed (3 days after month end)
 */
function checkAutoCloseCycles($conn) {
    $grace_days = 3;
    $cutoff_date = date('Y-m-d', strtotime("-{$grace_days} days"));
    
    $stmt = $conn->prepare("
        SELECT id, family_id, end_date 
        FROM monthly_cycles 
        WHERE is_current = TRUE 
        AND is_closed = FALSE 
        AND end_date <= ?
    ");
    $stmt->bind_param("s", $cutoff_date);
    $stmt->execute();
    $cycles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($cycles as $cycle) {
        // Auto-close with system user (ID 1)
        closeMonthlyCycle($conn, $cycle['id'], 1);
        
        // Log the auto-close
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (family_id, action_type, description, created_at)
            VALUES (?, 'cycle_auto_closed', ?, NOW())
        ");
        $description = "Cycle automatically closed after grace period: " . $cycle['end_date'];
        $stmt->bind_param("is", $cycle['family_id'], $description);
        $stmt->execute();
    }
}

/**
 * Create new cycles for the current month if none exist
 * This function ensures proper cycle creation and prevents premature cycle creation
 */
function ensureCurrentMonthCycles($conn) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    $currentMonthStr = date('Y-m');
    
    // Find families that need a cycle for the current month
    $stmt = $conn->prepare("
        SELECT DISTINCT fg.id as family_id,
               COALESCE(mc_latest.is_closed, 0) as latest_cycle_closed,
               COALESCE(mc_latest.cycle_month, '') as latest_cycle_month
        FROM family_groups fg
        LEFT JOIN (
            SELECT family_id, cycle_month, is_closed,
                   ROW_NUMBER() OVER (PARTITION BY family_id ORDER BY cycle_year DESC, cycle_month_num DESC) as rn
            FROM monthly_cycles
        ) mc_latest ON fg.id = mc_latest.family_id AND mc_latest.rn = 1
        WHERE NOT EXISTS (
            SELECT 1 FROM monthly_cycles mc 
            WHERE mc.family_id = fg.id 
            AND mc.cycle_month = ?
        )
    ");
    $stmt->bind_param("s", $currentMonthStr);
    $stmt->execute();
    $families = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($families as $family) {
        // Only create a new cycle if:
        // 1. No cycle exists for current month, AND
        // 2. Either no previous cycle exists OR the latest cycle is closed
        if (empty($family['latest_cycle_month']) || $family['latest_cycle_closed']) {
            createMonthlyCycle($conn, $family['family_id'], $currentYear, $currentMonth);
            error_log("Auto-created cycle for family {$family['family_id']} for month {$currentMonthStr}");
        }
    }
}

/**
 * Start a new cycle for a family (usually at the beginning of a new month)
 * This function can be called manually or automatically
 */
function startNewCycle($conn, $family_id, $year = null, $month = null) {
    if (!$year) $year = date('Y');
    if (!$month) $month = date('n');
    
    $monthStr = date('Y-m', strtotime("$year-$month-01"));
    
    // Check if cycle already exists for this month
    $stmt = $conn->prepare("
        SELECT id FROM monthly_cycles 
        WHERE family_id = ? AND cycle_month = ?
    ");
    $stmt->bind_param("is", $family_id, $monthStr);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        error_log("Cycle already exists for family $family_id for $monthStr");
        return false; // Cycle already exists
    }
    
    // Create the new cycle
    $success = createMonthlyCycle($conn, $family_id, $year, $month);
    
    if ($success) {
        // Log the new cycle creation
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (family_id, action_type, description, created_at)
            VALUES (?, 'cycle_started', ?, NOW())
        ");
        $description = "New monthly cycle started for " . date('F Y', strtotime("$year-$month-01"));
        $stmt->bind_param("is", $family_id, $description);
        $stmt->execute();
        
        error_log("New cycle started for family $family_id for $monthStr");
    }
    
    return $success;
}

/**
 * Check and create new cycles for the beginning of the month
 * This should be called daily via cron job or when the app loads
 */
function checkAndStartNewMonthCycles($conn) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    $currentMonthStr = date('Y-m');
    $currentDay = date('j');
    
    // Only run this check in the first few days of the month
    if ($currentDay > 5) {
        return; // Not the beginning of the month
    }
    
    // Find families that need a new cycle for the current month
    $stmt = $conn->prepare("
        SELECT fg.id as family_id, fg.family_name,
               MAX(mc.cycle_month) as latest_cycle_month,
               MAX(mc.is_closed) as latest_cycle_closed
        FROM family_groups fg
        LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id
        WHERE fg.id NOT IN (
            SELECT DISTINCT family_id 
            FROM monthly_cycles 
            WHERE cycle_month = ?
        )
        GROUP BY fg.id, fg.family_name
        HAVING latest_cycle_month IS NULL OR latest_cycle_closed = 1
    ");
    $stmt->bind_param("s", $currentMonthStr);
    $stmt->execute();
    $families = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($families as $family) {
        startNewCycle($conn, $family['family_id'], $currentYear, $currentMonth);
    }
}
function getMemberDebtInfo($conn, $family_id, $member_id = null, $member_type = 'user') {
    $where_clause = "WHERE mdh.family_id = ? AND mdh.is_cleared = FALSE";
    $params = [$family_id];
    $param_types = "i";
    
    if ($member_id) {
        if ($member_type === 'user') {
            $where_clause .= " AND mdh.member_id = ?";
        } else {
            $where_clause .= " AND mdh.member_only_id = ?";
        }
        $params[] = $member_id;
        $param_types .= "i";
    }
    
    $stmt = $conn->prepare("
        SELECT 
            mdh.*,
            CASE 
                WHEN mdh.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN mdh.member_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown Member'
            END as member_name,
            DATEDIFF(CURDATE(), STR_TO_DATE(CONCAT(mdh.cycle_month, '-01'), '%Y-%m-%d')) as days_overdue
        FROM member_debt_history mdh
        LEFT JOIN family_members fm ON mdh.member_id = fm.id AND mdh.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mdh.member_only_id = fmo.id AND mdh.member_type = 'member'
        {$where_clause}
        ORDER BY mdh.cycle_month DESC, member_name
    ");
    
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get current cycle performance
 */
function getCurrentCyclePerformance($conn, $family_id) {
    $stmt = $conn->prepare("
        SELECT 
            mmp.*,
            CASE 
                WHEN mmp.member_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                WHEN mmp.member_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown Member'
            END as member_name,
            CASE 
                WHEN mmp.member_type = 'user' THEN fm.role
                WHEN mmp.member_type = 'member' THEN fmo.role
                ELSE 'unknown'
            END as role,
            CASE 
                WHEN mmp.member_type = 'user' THEN fm.accumulated_debt
                WHEN mmp.member_type = 'member' THEN fmo.accumulated_debt
                ELSE 0
            END as accumulated_debt,
            CASE 
                WHEN mmp.target_amount > 0 THEN ROUND((mmp.contributed_amount / mmp.target_amount) * 100, 2)
                ELSE 0 
            END as progress_percentage,
            mc.cycle_month,
            mc.end_date,
            DATEDIFF(mc.end_date, CURDATE()) as days_remaining
        FROM member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member'
        WHERE mc.family_id = ? AND mc.is_current = TRUE AND mc.is_closed = FALSE
        ORDER BY progress_percentage DESC, member_name
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update member performance when contribution is made
 */
function updateMemberPerformance($conn, $family_id, $member_id, $member_type, $contribution_amount, $contribution_date) {
    // Get current cycle
    $current_cycle = getCurrentCycle($conn, $family_id);
    if (!$current_cycle) {
        // Create current cycle if none exists
        createMonthlyCycle($conn, $family_id);
        $current_cycle = getCurrentCycle($conn, $family_id);
    }
    
    if (!$current_cycle) return false;
    
    // Update performance record
    $member_field = ($member_type === 'user') ? 'member_id' : 'member_only_id';
    
    $stmt = $conn->prepare("
        UPDATE member_monthly_performance 
        SET 
            contributed_amount = contributed_amount + ?,
            contribution_count = contribution_count + 1,
            last_contribution_date = ?,
            first_contribution_date = COALESCE(first_contribution_date, ?),
            is_completed = (contributed_amount + ? >= target_amount),
            completed_date = CASE 
                WHEN (contributed_amount + ? >= target_amount) AND completed_date IS NULL 
                THEN ? 
                ELSE completed_date 
            END
        WHERE cycle_id = ? AND {$member_field} = ?
    ");
    
    $stmt->bind_param("dssdsdii", 
        $contribution_amount, 
        $contribution_date, 
        $contribution_date,
        $contribution_amount,
        $contribution_amount,
        $contribution_date,
        $current_cycle['id'], 
        $member_id
    );
    
    $result = $stmt->execute();
    
    // Update cycle totals
    if ($result) {
        updateCycleTotals($conn, $current_cycle['id']);
        
        // Update member's current month contribution
        $table = ($member_type === 'user') ? 'family_members' : 'family_members_only';
        $stmt = $conn->prepare("
            UPDATE {$table} 
            SET 
                current_month_contributed = current_month_contributed + ?,
                goal_met_this_month = (current_month_contributed + ? >= monthly_contribution_goal),
                last_payment_date = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ddsi", $contribution_amount, $contribution_amount, $contribution_date, $member_id);
        $stmt->execute();
    }
    
    return $result;
}

/**
 * Update cycle totals
 */
function updateCycleTotals($conn, $cycle_id) {
    $stmt = $conn->prepare("
        UPDATE monthly_cycles 
        SET 
            total_collected = (
                SELECT COALESCE(SUM(contributed_amount), 0) 
                FROM member_monthly_performance 
                WHERE cycle_id = ?
            ),
            members_completed = (
                SELECT COUNT(*) 
                FROM member_monthly_performance 
                WHERE cycle_id = ? AND is_completed = TRUE
            ),
            members_pending = (
                SELECT COUNT(*) 
                FROM member_monthly_performance 
                WHERE cycle_id = ? AND is_completed = FALSE
            )
        WHERE id = ?
    ");
    $stmt->bind_param("iiii", $cycle_id, $cycle_id, $cycle_id, $cycle_id);
    return $stmt->execute();
}

/**
 * Check if member can close cycle (admin/head only)
 */
function canCloseCycle($conn, $user_id, $family_id) {
    $stmt = $conn->prepare("
        SELECT role FROM family_members 
        WHERE user_id = ? AND family_id = ? AND role IN ('admin', 'head')
    ");
    $stmt->bind_param("ii", $user_id, $family_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Get cycle history
 */
function getCycleHistory($conn, $family_id, $limit = 12) {
    $stmt = $conn->prepare("
        SELECT 
            mc.*,
            CASE 
                WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
                ELSE 0 
            END as completion_percentage,
            (SELECT COUNT(*) FROM member_debt_history WHERE cycle_month = mc.cycle_month AND family_id = mc.family_id) as members_with_debt
        FROM monthly_cycles mc
        WHERE mc.family_id = ?
        ORDER BY mc.cycle_year DESC, mc.cycle_month_num DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $family_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Clear member debt (when they pay back)
 */
function clearMemberDebt($conn, $debt_id, $cleared_by_cycle_id = null) {
    $stmt = $conn->prepare("
        UPDATE member_debt_history 
        SET 
            is_cleared = TRUE,
            cleared_date = CURDATE(),
            cleared_by_cycle_id = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $cleared_by_cycle_id, $debt_id);
    return $stmt->execute();
}

/**
 * Get family debt summary
 */
function getFamilyDebtSummary($conn, $family_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_debt_records,
            COUNT(CASE WHEN is_cleared = FALSE THEN 1 END) as active_debts,
            COALESCE(SUM(CASE WHEN is_cleared = FALSE THEN deficit_amount ELSE 0 END), 0) as total_outstanding_debt,
            COALESCE(SUM(deficit_amount), 0) as total_debt_ever,
            COUNT(DISTINCT CASE 
                WHEN is_cleared = FALSE AND member_type = 'user' THEN member_id
                WHEN is_cleared = FALSE AND member_type = 'member' THEN member_only_id
            END) as members_with_active_debt
        FROM member_debt_history 
        WHERE family_id = ?
    ");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Ensure cycle exists for current month
 */
function ensureCurrentCycleExists($conn, $family_id) {
    $current_cycle = getCurrentCycle($conn, $family_id);
    
    if (!$current_cycle) {
        createMonthlyCycle($conn, $family_id);
        return getCurrentCycle($conn, $family_id);
    }
    
    // Check if current cycle is for this month
    $current_month = date('Y-m');
    if ($current_cycle['cycle_month'] !== $current_month) {
        // Close old cycle and create new one
        if (!$current_cycle['is_closed']) {
            closeMonthlyCycle($conn, $current_cycle['id'], 1); // System close
        }
        createMonthlyCycle($conn, $family_id);
        return getCurrentCycle($conn, $family_id);
    }
    
    return $current_cycle;
}

// Auto-run cycle checks (call this from a cron job or at page load)
function runCycleMaintenanceTasks($conn) {
    checkAutoCloseCycles($conn);
    
    // Auto-create cycles for families that don't have current ones
    $stmt = $conn->prepare("
        SELECT fg.id 
        FROM family_groups fg
        LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id AND mc.is_current = TRUE
        WHERE mc.id IS NULL
    ");
    $stmt->execute();
    $families = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($families as $family) {
        createMonthlyCycle($conn, $family['id']);
    }
}
?>