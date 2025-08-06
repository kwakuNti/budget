<?php
/**
 * Debt Management Functions
 * Handles debt tracking, calculation, and payment processing
 */

/**
 * Calculate and record debts when a cycle closes
 */
function calculateAndRecordDebts($conn, $cycleId, $familyId) {
    try {
        // Get cycle information
        $stmt = $conn->prepare("
            SELECT cycle_month, cycle_year, total_target, end_date 
            FROM monthly_cycles 
            WHERE id = ? AND family_id = ?
        ");
        $stmt->bind_param("ii", $cycleId, $familyId);
        $stmt->execute();
        $cycle = $stmt->get_result()->fetch_assoc();
        
        if (!$cycle) {
            throw new Exception("Cycle not found");
        }
        
        // Get all family members and their contributions for this cycle
        $stmt = $conn->prepare("
            SELECT 
                fmo.id as member_only_id,
                CONCAT(fmo.first_name, ' ', fmo.last_name) as display_name,
                fmo.monthly_contribution_goal,
                COALESCE(contrib.total_contributed, 0) as actual_contributed
            FROM family_members_only fmo
            LEFT JOIN (
                SELECT 
                    member_only_id,
                    SUM(amount) as total_contributed
                FROM family_contributions 
                WHERE family_id = ? 
                AND DATE_FORMAT(contribution_date, '%Y-%m') = ?
                GROUP BY member_only_id
            ) contrib ON fmo.id = contrib.member_only_id
            WHERE fmo.family_id = ? AND fmo.is_active = 1
        ");
        
        $cycleMonth = $cycle['cycle_year'] . '-' . str_pad($cycle['cycle_month'], 2, '0', STR_PAD_LEFT);
        $stmt->bind_param("isi", $familyId, $cycleMonth, $familyId);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $debtsRecorded = 0;
        
        foreach ($members as $member) {
            $targetAmount = floatval($member['monthly_contribution_goal']);
            $contributedAmount = floatval($member['actual_contributed']);
            $deficit = $targetAmount - $contributedAmount;
            
            if ($deficit > 0) {
                // Record the debt
                $stmt = $conn->prepare("
                    INSERT INTO member_debt_history (
                        family_id, 
                        member_only_id, 
                        cycle_month,
                        target_amount,
                        contributed_amount,
                        deficit_amount,
                        notes,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $notes = "Debt from {$cycle['cycle_month']}/{$cycle['cycle_year']} cycle";
                $stmt->bind_param("iisddds", 
                    $familyId, 
                    $member['member_only_id'], 
                    $cycleMonth,
                    $targetAmount,
                    $contributedAmount,
                    $deficit,
                    $notes
                );
                $stmt->execute();
                
                // Update member's accumulated debt
                $stmt = $conn->prepare("
                    UPDATE family_members_only 
                    SET accumulated_debt = accumulated_debt + ?
                    WHERE id = ?
                ");
                $stmt->bind_param("di", $deficit, $member['member_only_id']);
                $stmt->execute();
                
                $debtsRecorded++;
            }
        }
        
        return [
            'success' => true,
            'debts_recorded' => $debtsRecorded,
            'message' => "Recorded {$debtsRecorded} member debts for cycle {$cycle['cycle_month']}/{$cycle['cycle_year']}"
        ];
        
    } catch (Exception $e) {
        error_log("Error calculating debts: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get outstanding debts for a family
 */
function getOutstandingDebts($conn, $familyId) {
    $stmt = $conn->prepare("
        SELECT 
            mdh.id,
            mdh.member_only_id as member_id,
            CONCAT(fmo.first_name, ' ', fmo.last_name) as display_name,
            mdh.cycle_month,
            mdh.deficit_amount,
            mdh.target_amount,
            mdh.contributed_amount,
            mdh.created_at
        FROM member_debt_history mdh
        JOIN family_members_only fmo ON mdh.member_only_id = fmo.id
        WHERE mdh.family_id = ? 
        AND mdh.is_cleared = 0
        ORDER BY mdh.created_at ASC
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get total debt for a specific member
 */
function getMemberTotalDebt($conn, $memberId) {
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(deficit_amount), 0) as total_debt,
            COUNT(*) as debt_count
        FROM member_debt_history 
        WHERE member_only_id = ? AND is_cleared = 0
    ");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Process contribution with debt handling options
 */
function processContributionWithDebtOptions($conn, $memberId, $amount, $paymentType = 'contribution', $familyId = null) {
    try {
        $conn->begin_transaction();
        
        // Get member's current debt
        $debtInfo = getMemberTotalDebt($conn, $memberId);
        $totalDebt = floatval($debtInfo['total_debt']);
        
        $result = [
            'success' => true,
            'amount_processed' => $amount,
            'debt_paid' => 0,
            'contribution_made' => 0,
            'remaining_debt' => $totalDebt,
            'message' => ''
        ];
        
        if ($paymentType === 'debt_only' && $totalDebt > 0) {
            // Pay debt only
            $debtPayment = min($amount, $totalDebt);
            $result['debt_paid'] = $debtPayment;
            $result['remaining_debt'] = $totalDebt - $debtPayment;
            
            // Apply payment to oldest debts first
            $stmt = $conn->prepare("
                SELECT id, deficit_amount 
                FROM member_debt_history 
                WHERE member_only_id = ? AND is_cleared = 0 
                ORDER BY created_at ASC
            ");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $debts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $remainingPayment = $debtPayment;
            foreach ($debts as $debt) {
                if ($remainingPayment <= 0) break;
                
                $debtAmount = floatval($debt['deficit_amount']);
                $paymentForThisDebt = min($remainingPayment, $debtAmount);
                
                if ($paymentForThisDebt >= $debtAmount) {
                    // Fully clear this debt
                    $stmt = $conn->prepare("
                        UPDATE member_debt_history 
                        SET is_cleared = 1, cleared_date = CURDATE()
                        WHERE id = ?
                    ");
                    $stmt->bind_param("i", $debt['id']);
                    $stmt->execute();
                } else {
                    // Partially pay this debt
                    $stmt = $conn->prepare("
                        UPDATE member_debt_history 
                        SET deficit_amount = deficit_amount - ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("di", $paymentForThisDebt, $debt['id']);
                    $stmt->execute();
                }
                
                $remainingPayment -= $paymentForThisDebt;
            }
            
            // Update member's accumulated debt
            $stmt = $conn->prepare("
                UPDATE family_members_only 
                SET accumulated_debt = accumulated_debt - ?
                WHERE id = ?
            ");
            $stmt->bind_param("di", $debtPayment, $memberId);
            $stmt->execute();
            
            $result['message'] = "₵{$debtPayment} applied to debt payment";
            
        } elseif ($paymentType === 'auto_deduct' && $totalDebt > 0) {
            // Auto-deduct debt from contribution
            $debtPayment = min($amount * 0.5, $totalDebt); // Max 50% for debt
            $contributionAmount = $amount - $debtPayment;
            
            if ($debtPayment > 0) {
                // Apply same debt payment logic as above
                $stmt = $conn->prepare("
                    SELECT id, deficit_amount 
                    FROM member_debt_history 
                    WHERE member_only_id = ? AND is_cleared = 0 
                    ORDER BY created_at ASC
                ");
                $stmt->bind_param("i", $memberId);
                $stmt->execute();
                $debts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                $remainingPayment = $debtPayment;
                foreach ($debts as $debt) {
                    if ($remainingPayment <= 0) break;
                    
                    $debtAmount = floatval($debt['deficit_amount']);
                    $paymentForThisDebt = min($remainingPayment, $debtAmount);
                    
                    if ($paymentForThisDebt >= $debtAmount) {
                        $stmt = $conn->prepare("
                            UPDATE member_debt_history 
                            SET is_cleared = 1, cleared_date = CURDATE()
                            WHERE id = ?
                        ");
                        $stmt->bind_param("i", $debt['id']);
                        $stmt->execute();
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE member_debt_history 
                            SET deficit_amount = deficit_amount - ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("di", $paymentForThisDebt, $debt['id']);
                        $stmt->execute();
                    }
                    
                    $remainingPayment -= $paymentForThisDebt;
                }
                
                // Update member's accumulated debt
                $stmt = $conn->prepare("
                    UPDATE family_members_only 
                    SET accumulated_debt = accumulated_debt - ?
                    WHERE id = ?
                ");
                $stmt->bind_param("di", $debtPayment, $memberId);
                $stmt->execute();
                
                $result['debt_paid'] = $debtPayment;
            }
            
            if ($contributionAmount > 0) {
                $result['contribution_made'] = $contributionAmount;
            }
            
            $result['remaining_debt'] = $totalDebt - $debtPayment;
            $result['message'] = "₵{$debtPayment} paid debt, ₵{$contributionAmount} as contribution";
            
        } else {
            // Regular contribution only
            $result['contribution_made'] = $amount;
            $result['message'] = "₵{$amount} recorded as regular contribution";
        }
        
        $conn->commit();
        return $result;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error processing contribution with debt: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get debt summary for dashboard display
 */
function getFamilyDebtSummary($conn, $familyId) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT mdh.member_only_id) as members_with_debt,
            COALESCE(SUM(mdh.deficit_amount), 0) as total_family_debt,
            COUNT(mdh.id) as total_debt_records
        FROM member_debt_history mdh
        WHERE mdh.family_id = ? AND mdh.is_cleared = 0
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Clear all debt for a member (manual override)
 */
function clearMemberDebt($conn, $memberId) {
    $stmt = $conn->prepare("
        UPDATE member_debt_history 
        SET is_cleared = 1, cleared_date = CURDATE(), notes = CONCAT(notes, ' - Cleared manually')
        WHERE member_only_id = ? AND is_cleared = 0
    ");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    
    // Reset accumulated debt
    $stmt = $conn->prepare("
        UPDATE family_members_only 
        SET accumulated_debt = 0
        WHERE id = ?
    ");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    
    return $stmt->affected_rows;
}

?>
