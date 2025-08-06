<?php
function getTotalPoolAmount($familyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM contributions WHERE family_id = ?");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function getCurrentMonthTotalForFamily($familyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM contributions WHERE family_id = ? AND MONTH(contribution_date) = MONTH(CURDATE()) AND YEAR(contribution_date) = YEAR(CURDATE())");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function getCurrentMonthExpensesForFamily($familyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE family_id = ? AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function getContributionTrend($familyId) {
    global $conn;
    $query = "SELECT DATE_FORMAT(contribution_date, '%b') as month, SUM(amount) as total
              FROM contributions
              WHERE family_id = ? AND contribution_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY MONTH(contribution_date), YEAR(contribution_date)
              ORDER BY contribution_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getExpenseTrend($familyId) {
    global $conn;
    $query = "SELECT DATE_FORMAT(expense_date, '%b') as month, SUM(amount) as total
              FROM expenses
              WHERE family_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY MONTH(expense_date), YEAR(expense_date)
              ORDER BY expense_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFamilyContributions($familyId) {
    global $conn;

    $query = "
        SELECT fc.*, 
               CONCAT(fmo.first_name, ' ', fmo.last_name) AS member_name,
               fmo.role AS member_role
        FROM family_contributions fc
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id
        WHERE fc.family_id = ?
        ORDER BY fc.contribution_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    return $stmt->get_result();
}

function get_all_contributions($conn) {
    $sql = "SELECT c.id, c.member_id, c.amount, c.date, c.note, 
                   m.first_name, m.last_name, m.role 
            FROM contributions c
            JOIN family_members m ON c.member_id = m.id
            ORDER BY c.date DESC";

    $result = $conn->query($sql);
    $contributions = [];

    while ($row = $result->fetch_assoc()) {
        $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
        $contributions[] = $row;
    }

    return $contributions;
}


// Fixed version of your contribution functions


// Add these functions to your contribution_functions.php (don't duplicate existing ones)

// Replace your existing getFilteredContributions function with this:


function getFilteredContributions($conn, $familyId, $memberName = '', $dateFrom = '', $dateTo = '', $amountRange = '') {
    $query = "
        SELECT 
            fc.id,
            fc.amount,
            fc.contribution_date,
            fc.notes,
            fmo.first_name,
            fmo.last_name,
            CONCAT(fmo.first_name, ' ', fmo.last_name) AS full_name
        FROM family_contributions fc
        JOIN family_members_only fmo ON fc.member_only_id = fmo.id
        WHERE fmo.family_id = ?
    ";

    $params = [$familyId];
    $types = "i";

    // Filter by member name
    if (!empty($memberName)) {
        $query .= " AND CONCAT(fmo.first_name, ' ', fmo.last_name) = ?";
        $params[] = $memberName;
        $types .= "s";
    }

    // Filter by date range
    if (!empty($dateFrom)) {
        $query .= " AND fc.contribution_date >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    if (!empty($dateTo)) {
        $query .= " AND fc.contribution_date <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    // Filter by amount range
    if (!empty($amountRange)) {
        switch ($amountRange) {
            case '0-50':
                $query .= " AND fc.amount BETWEEN 0 AND 50";
                break;
            case '50-100':
                $query .= " AND fc.amount BETWEEN 50.01 AND 100";
                break;
            case '100-200':
                $query .= " AND fc.amount BETWEEN 100.01 AND 200";
                break;
            case '200+':
                $query .= " AND fc.amount > 200";
                break;
        }
    }

    $query .= " ORDER BY fc.contribution_date DESC LIMIT 100";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("SQL Prepare Error in getFilteredContributions: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("SQL Execute Error in getFilteredContributions: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    $contributions = [];
    
    while ($row = $result->fetch_assoc()) {
        $contributions[] = $row;
    }

    $stmt->close();
    return $contributions;
}





// Fixed Monthly Stats function
function getMonthlyStats($conn, $familyId) {
    $currentMonth = date('Y-m');
    $startDate = $currentMonth . '-01';
    $endDate = date('Y-m-t');

    // Get monthly stats with proper JOIN
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(fc.amount), 0) AS total,
            COUNT(fc.id) AS count 
        FROM family_contributions fc
        JOIN family_members_only fmo ON fc.member_only_id = fmo.id
        WHERE fmo.family_id = ? 
        AND fc.contribution_date BETWEEN ? AND ?
    ");
    
    $stmt->bind_param("iss", $familyId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();

    // Get active member count
    $stmt = $conn->prepare("SELECT COUNT(*) as member_count FROM family_members_only WHERE family_id = ? AND is_active = 1");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $memberData = $result->fetch_assoc();
    $memberCount = $memberData['member_count'];
    $stmt->close();

    // Get monthly target
    $stmt = $conn->prepare("SELECT COALESCE(monthly_target, 0) as monthly_target FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $targetData = $result->fetch_assoc();
    $monthlyTarget = $targetData['monthly_target'] ?? 0;
    $stmt->close();

    $total = $stats['total'] ?? 0;
    $count = $stats['count'] ?? 0;
    $average = ($memberCount > 0) ? round($total / $memberCount, 2) : 0;
    $achievement = ($monthlyTarget > 0) ? round(($total / $monthlyTarget) * 100, 0) : 0;

    return [
        'total' => $total,
        'average' => $average,
        'count' => $count,
        'achievement' => $achievement
    ];
}
?>