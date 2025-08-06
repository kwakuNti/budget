<?php
require_once __DIR__ . '/../config/connection.php';

function getExpenseStats($conn, $family_id) {
    $month = date('m');
    $year = date('Y');

    // Total all-time expenses
    $total_query = "SELECT SUM(amount) AS total_expenses FROM family_expenses WHERE family_id = ?";
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $total_expenses = $stmt->get_result()->fetch_assoc()['total_expenses'] ?? 0;

    // This month's expenses
    $month_query = "SELECT SUM(amount) AS this_month_expenses FROM family_expenses WHERE family_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $stmt = $conn->prepare($month_query);
    $stmt->bind_param("iii", $family_id, $month, $year);
    $stmt->execute();
    $this_month_expenses = $stmt->get_result()->fetch_assoc()['this_month_expenses'] ?? 0;

    // Average monthly expenses (last 6 months)
    $avg_query = "SELECT AVG(monthly_sum) AS average_monthly FROM (
        SELECT SUM(amount) AS monthly_sum 
        FROM family_expenses 
        WHERE family_id = ? 
        AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(expense_date), MONTH(expense_date)
    ) AS monthly_totals";
    $stmt = $conn->prepare($avg_query);
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $average_monthly = $stmt->get_result()->fetch_assoc()['average_monthly'] ?? 0;

    // Top category this month
    $top_category_query = "SELECT expense_type, SUM(amount) AS total 
        FROM family_expenses 
        WHERE family_id = ? 
        AND MONTH(expense_date) = ? AND YEAR(expense_date) = ? 
        GROUP BY expense_type 
        ORDER BY total DESC LIMIT 1";
    $stmt = $conn->prepare($top_category_query);
    $stmt->bind_param("iii", $family_id, $month, $year);
    $stmt->execute();
    $top_category = $stmt->get_result()->fetch_assoc();

    // Last month's total for percentage change
    $last_month = date('m', strtotime('first day of last month'));
    $last_year = date('Y', strtotime('first day of last month'));
    $last_month_query = "SELECT SUM(amount) AS last_month_expenses FROM family_expenses WHERE family_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $stmt = $conn->prepare($last_month_query);
    $stmt->bind_param("iii", $family_id, $last_month, $last_year);
    $stmt->execute();
    $last_month_expenses = $stmt->get_result()->fetch_assoc()['last_month_expenses'] ?? 0;

    // Calculate percentage changes
    $total_change = $last_month_expenses > 0 ? (($this_month_expenses - $last_month_expenses) / $last_month_expenses) * 100 : 0;
    $monthly_change = $last_month_expenses > 0 ? (($this_month_expenses - $last_month_expenses) / $last_month_expenses) * 100 : 0;
    $average_change = $average_monthly > 0 ? (($this_month_expenses - $average_monthly) / $average_monthly) * 100 : 0;

    return [
        'total_expenses' => floatval($total_expenses),
        'this_month_expenses' => floatval($this_month_expenses),
        'average_monthly' => floatval($average_monthly),
        'top_category' => $top_category ? ucfirst($top_category['expense_type']) : 'None',
        'top_category_amount' => $top_category ? floatval($top_category['total']) : 0,
        'top_category_percent' => $this_month_expenses > 0 && $top_category ? round(($top_category['total'] / $this_month_expenses) * 100) : 0,
        'total_change' => round($total_change, 1),
        'monthly_change' => round($monthly_change, 1),
        'average_change' => round($average_change, 1)
    ];
}

// NEW FUNCTION: Get expense trends data for charts
function getExpenseTrendsData($conn, $family_id, $period = '6m') {
    switch($period) {
        case '6m':
            $query = "SELECT 
                DATE_FORMAT(MIN(expense_date), '%b') as month,
                SUM(amount) as total
                FROM family_expenses 
                WHERE family_id = ? 
                AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY YEAR(expense_date), MONTH(expense_date)
                ORDER BY MIN(expense_date) ASC";
            break;
            
        case '1y':
            $query = "SELECT 
                DATE_FORMAT(MIN(expense_date), '%b') as month,
                SUM(amount) as total
                FROM family_expenses 
                WHERE family_id = ? 
                AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY YEAR(expense_date), MONTH(expense_date)
                ORDER BY MIN(expense_date) ASC";
            break;
            
        case 'all':
            $query = "SELECT 
                CONCAT(YEAR(MIN(expense_date)), ' Q', QUARTER(MIN(expense_date))) as month,
                SUM(amount) as total
                FROM family_expenses 
                WHERE family_id = ?
                GROUP BY YEAR(expense_date), QUARTER(expense_date)
                ORDER BY MIN(expense_date) ASC";
            break;
            
        default:
            return ['labels' => [], 'data' => []];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['month'];
        $data[] = floatval($row['total']);
    }
    
    return ['labels' => $labels, 'data' => $data];
}
// NEW FUNCTION: Get category breakdown data
function getCategoryBreakdownData($conn, $family_id, $period = 'current') {
    switch($period) {
        case 'current':
            $query = "SELECT 
                expense_type,
                SUM(amount) as total
                FROM family_expenses 
                WHERE family_id = ? 
                AND MONTH(expense_date) = MONTH(CURDATE())
                AND YEAR(expense_date) = YEAR(CURDATE())
                GROUP BY expense_type
                ORDER BY total DESC";
            break;
            
        case 'last':
            $query = "SELECT 
                expense_type,
                SUM(amount) as total
                FROM family_expenses 
                WHERE family_id = ? 
                AND MONTH(expense_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                AND YEAR(expense_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                GROUP BY expense_type
                ORDER BY total DESC";
            break;
            
        case 'quarter':
            $query = "SELECT 
                expense_type,
                SUM(amount) as total
                FROM family_expenses 
                WHERE family_id = ? 
                AND QUARTER(expense_date) = QUARTER(CURDATE())
                AND YEAR(expense_date) = YEAR(CURDATE())
                GROUP BY expense_type
                ORDER BY total DESC";
            break;
            
        default:
            return ['labels' => [], 'data' => []];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $data = [];
    
    // Category display names
    $categoryNames = [
        'utilities' => 'Utilities',
        'dstv' => 'DSTV',
        'wifi' => 'WiFi',
        'dining' => 'Dining',
        'maintenance' => 'Maintenance',
        'other' => 'Other'
    ];
    
    while ($row = $result->fetch_assoc()) {
        $categoryName = $categoryNames[$row['expense_type']] ?? ucfirst($row['expense_type']);
        $labels[] = $categoryName;
        $data[] = floatval($row['total']);
    }
    
    return ['labels' => $labels, 'data' => $data];
}

// NEW FUNCTION: Get quick add suggestions based on recent expenses
function getQuickAddSuggestions($conn, $family_id, $limit = 6) {
    // Get most common expenses with their average amounts
    $query = "SELECT 
        expense_type,
        description,
        ROUND(AVG(amount), 0) as avg_amount,
        COUNT(*) as frequency,
        MAX(expense_date) as last_used
        FROM family_expenses 
        WHERE family_id = ? 
        AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY expense_type, description
        ORDER BY frequency DESC, last_used DESC
        LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $family_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suggestions = [];
    
    // Category icons mapping
    $categoryIcons = [
        'utilities' => '⚡',
        'dstv' => '📺',
        'wifi' => '📶',
        'dining' => '🍽️',
        'maintenance' => '🔧',
        'other' => '📦'
    ];
    
    // Default suggestions if no data
    $defaultSuggestions = [
        ['type' => 'dstv', 'name' => 'DSTV', 'amount' => 120, 'icon' => '📺'],
        ['type' => 'wifi', 'name' => 'WiFi', 'amount' => 80, 'icon' => '📶'],
        ['type' => 'utilities', 'name' => 'Electricity', 'amount' => 150, 'icon' => '⚡'],
        ['type' => 'utilities', 'name' => 'Water', 'amount' => 45, 'icon' => '💧'],
        ['type' => 'other', 'name' => 'Groceries', 'amount' => 0, 'icon' => '🛒'],
        ['type' => 'other', 'name' => 'Transport', 'amount' => 0, 'icon' => '🚗']
    ];
    
    if ($result->num_rows == 0) {
        return $defaultSuggestions;
    }
    
    while ($row = $result->fetch_assoc()) {
        $icon = $categoryIcons[$row['expense_type']] ?? '📦';
        
        // Special case for utilities - try to determine if it's water or electricity
        if ($row['expense_type'] == 'utilities') {
            if (stripos($row['description'], 'water') !== false) {
                $icon = '💧';
            } elseif (stripos($row['description'], 'electric') !== false || stripos($row['description'], 'light') !== false) {
                $icon = '⚡';
            }
        }
        
        $suggestions[] = [
            'type' => $row['expense_type'],
            'name' => $row['description'],
            'amount' => intval($row['avg_amount']),
            'icon' => $icon,
            'frequency' => $row['frequency']
        ];
    }
    
    // Fill remaining slots with defaults if needed
    $existingTypes = array_column($suggestions, 'type');
    foreach ($defaultSuggestions as $default) {
        if (count($suggestions) >= $limit) break;
        
        if (!in_array($default['type'], $existingTypes)) {
            $suggestions[] = $default;
        }
    }
    
    return array_slice($suggestions, 0, $limit);
}

function getAllExpenses($conn, $family_id, $limit = 10) {
    $query = "SELECT 
        e.id,
        e.expense_type as category,
        e.description,
        e.amount,
        e.expense_date,
        CASE 
            WHEN e.payer_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
            WHEN e.payer_type = 'member' THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
            ELSE 'Unknown'
        END as added_by
    FROM family_expenses e
    LEFT JOIN family_members fm ON e.paid_by = fm.id AND e.payer_type = 'user'
    LEFT JOIN users u ON fm.user_id = u.id
    LEFT JOIN family_members_only fmo ON e.member_only_id = fmo.id AND e.payer_type = 'member'
    WHERE e.family_id = ?
    ORDER BY e.expense_date DESC, e.created_at DESC
    LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $family_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    return $expenses;
}

function addExpense($conn, $family_id, $expense_type, $amount, $description, $expense_date, $paid_by, $payer_type = 'user', $payment_method = 'momo') {
    $query = "INSERT INTO family_expenses (family_id, expense_type, amount, description, expense_date, paid_by, payer_type, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isdsssss", $family_id, $expense_type, $amount, $description, $expense_date, $paid_by, $payer_type, $payment_method);
    
    return $stmt->execute();
}

function deleteExpense($conn, $expense_id, $family_id) {
    $query = "DELETE FROM family_expenses WHERE id = ? AND family_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $expense_id, $family_id);
    
    return $stmt->execute();
}

function getExpenseById($conn, $expense_id, $family_id) {
    $query = "SELECT * FROM family_expenses WHERE id = ? AND family_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $expense_id, $family_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

function updateExpense($conn, $expense_id, $family_id, $expense_type, $amount, $description, $expense_date) {
    $query = "UPDATE family_expenses SET expense_type = ?, amount = ?, description = ?, expense_date = ? WHERE id = ? AND family_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdsiii", $expense_type, $amount, $description, $expense_date, $expense_id, $family_id);
    
    return $stmt->execute();
}
?>