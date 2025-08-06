<?php
require_once __DIR__ . '/../config/connection.php';

function getAllFamilyMembers($familyId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT fmo.id, fmo.first_name, fmo.last_name, fmo.phone_number,
               fmo.role, fmo.monthly_contribution_goal, fmo.total_contributed,
               fmo.is_active, fmo.added_at
        FROM family_members_only fmo
        WHERE fmo.family_id = ?
    ");
    $stmt->bind_param('i', $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getRecentContributions($memberOnlyId, $limit = 2) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT contribution_date, amount
        FROM family_contributions
        WHERE member_only_id = ?
        ORDER BY contribution_date DESC
        LIMIT ?
    ");
    $stmt->bind_param('ii', $memberOnlyId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getMemberContributionCount($memberOnlyId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS count
        FROM family_contributions
        WHERE member_only_id = ?
    ");
    $stmt->bind_param('i', $memberOnlyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] ?? 0;
}

function getCurrentMonthTotal($memberOnlyId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUM(amount) AS total
        FROM family_contributions
        WHERE member_only_id = ?
          AND MONTH(contribution_date) = MONTH(CURRENT_DATE())
          AND YEAR(contribution_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->bind_param('i', $memberOnlyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function getTopContributor($familyId) {
    global $conn;
    
    // First, check if anyone has made contributions
    $checkStmt = $conn->prepare("
        SELECT MAX(total_contributed) as max_contribution
        FROM family_members_only
        WHERE family_id = ? AND is_active = 1
    ");
    $checkStmt->bind_param('i', $familyId);
    $checkStmt->execute();
    $maxResult = $checkStmt->get_result()->fetch_assoc();
    
    // If no one has contributed yet, return appropriate message
    if ($maxResult['max_contribution'] == 0.00 || $maxResult['max_contribution'] === null) {
        return ['name' => 'No contributions', 'total_contributed' => 0.00];
    }
    
    // Get the top contributor
    $stmt = $conn->prepare("
        SELECT CONCAT(first_name, ' ', last_name) AS name, total_contributed
        FROM family_members_only
        WHERE family_id = ? AND is_active = 1
        ORDER BY total_contributed DESC, first_name ASC
        LIMIT 1
    ");
    $stmt->bind_param('i', $familyId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result ?: ['name' => 'N/A', 'total_contributed' => 0.00];
}


function getTotalActiveMembers($familyId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM family_members_only
        WHERE family_id = ? AND is_active = 1
    ");
    $stmt->bind_param('i', $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function getAverageMonthlyContribution($familyId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT AVG(current_month) AS avg_monthly
        FROM (
            SELECT SUM(amount) AS current_month
            FROM family_contributions
            WHERE family_id = ? AND MONTH(contribution_date) = MONTH(CURRENT_DATE())
            GROUP BY member_only_id
        ) AS monthly_totals
    ");
    $stmt->bind_param('i', $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['avg_monthly'] ?? 0;
}

function getAverageMonthlyContributionLastMonth($familyId) {
    global $conn;
    $lastMonth = date('Y-m', strtotime('first day of last month'));

    $stmt = $conn->prepare("
        SELECT AVG(monthly_total) AS avg_last_month
        FROM (
            SELECT SUM(amount) AS monthly_total
            FROM family_contributions
            WHERE family_id = ?
            AND DATE_FORMAT(contribution_date, '%Y-%m') = ?
            GROUP BY member_id
        ) AS member_totals
    ");
    $stmt->bind_param("is", $familyId, $lastMonth);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['avg_last_month'] ?? 0;
}
