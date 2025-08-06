<?php
require_once '../config/connection.php';

function getFamilyMemberContributions($familyId) {
    global $conn;

    $query = "
        SELECT 
            fmo.id,
            fmo.first_name,
            fmo.last_name,
            CONCAT(fmo.first_name, ' ', fmo.last_name) AS full_name,
            fmo.role,
            COALESCE(fmo.monthly_contribution_goal, 0) AS monthly_contribution_goal,
            COALESCE(current_month.total_contributed, 0) AS total_contributed_this_month,
            COALESCE(current_month.contribution_count, 0) AS contribution_count,
            COALESCE(current_month.average_contribution, 0) AS average_contribution
        FROM family_members_only fmo
        LEFT JOIN (
            SELECT 
                fc.member_only_id,
                SUM(fc.amount) AS total_contributed,
                COUNT(fc.id) AS contribution_count,
                AVG(fc.amount) AS average_contribution
            FROM family_contributions fc
            WHERE MONTH(fc.contribution_date) = MONTH(CURRENT_DATE())
            AND YEAR(fc.contribution_date) = YEAR(CURRENT_DATE())
            GROUP BY fc.member_only_id
        ) current_month ON fmo.id = current_month.member_only_id
        WHERE fmo.family_id = ? AND fmo.is_active = 1
        ORDER BY fmo.first_name ASC
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("SQL Prepare Error in getFamilyMemberContributions: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $familyId);
    
    if (!$stmt->execute()) {
        error_log("SQL Execute Error in getFamilyMemberContributions: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    $members = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calculate progress percentage
        $row['progress'] = $row['monthly_contribution_goal'] > 0
            ? round(($row['total_contributed_this_month'] / $row['monthly_contribution_goal']) * 100, 1)
            : 0;
            
        $members[] = $row;
    }

    $stmt->close();
    return $members;
}
?>