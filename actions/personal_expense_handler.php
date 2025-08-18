<?php
// Set JSON content type header first
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to prevent HTML output

session_start();
require_once '../config/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_expense':
                addExpense($conn, $user_id);
                break;
            case 'get_categories':
                getCategories($conn, $user_id);
                break;
            case 'get_expenses':
                getExpenses($conn, $user_id);
                break;
            case 'get_expense_summary':
                getExpenseSummary($conn, $user_id);
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
}

function addExpense($conn, $user_id) {
    try {
        $amount = round(floatval($_POST['amount']), 2); // Round to 2 decimal places
        $category_id = intval($_POST['category_id']);
        $description = trim($_POST['description']);
        $expense_date = $_POST['expense_date'];
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate required fields
        if (empty($amount) || $amount <= 0) {
            throw new Exception('Invalid amount');
        }
        
        if (empty($category_id)) {
            throw new Exception('Category is required');
        }
        
        if (empty($description)) {
            throw new Exception('Description is required');
        }
        
        if (empty($expense_date)) {
            throw new Exception('Date is required');
        }
        
        // Verify category belongs to user
        $stmt = $conn->prepare("SELECT id, name, budget_limit FROM budget_categories WHERE id = ? AND user_id = ? AND is_active = 1");
        $stmt->bind_param("ii", $category_id, $user_id);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        
        if (!$category) {
            throw new Exception('Invalid category selected');
        }
        
        // Insert expense
        $insert_stmt = $conn->prepare("
            INSERT INTO personal_expenses (user_id, category_id, amount, description, expense_date, payment_method, receipt_url, tags) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $tags = json_encode(['notes' => $notes]);
        $receipt_url = null; // No receipt upload functionality yet
        $insert_stmt->bind_param("iidsssss", $user_id, $category_id, $amount, $description, $expense_date, $payment_method, $receipt_url, $tags);
        
        if ($insert_stmt->execute()) {
            $expense_id = $conn->insert_id;
            
            // Get the total spent in this category for the current month
            $current_month = date('Y-m');
            $total_stmt = $conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_spent 
                FROM personal_expenses 
                WHERE user_id = ? AND category_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
            ");
            $total_stmt->bind_param("iis", $user_id, $category_id, $current_month);
            $total_stmt->execute();
            $total_result = $total_stmt->get_result()->fetch_assoc();
            $total_spent = $total_result['total_spent'];
            
            $remaining_budget = $category['budget_limit'] - $total_spent;
            $budget_percentage = $category['budget_limit'] > 0 ? ($total_spent / $category['budget_limit']) * 100 : 0;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Expense added successfully',
                'expense_id' => $expense_id,
                'category_name' => $category['name'],
                'total_spent' => $total_spent,
                'remaining_budget' => $remaining_budget,
                'budget_percentage' => round($budget_percentage, 1)
            ]);
        } else {
            throw new Exception('Failed to save expense');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getCategories($conn, $user_id) {
    try {
        // First get the budget allocation totals
        $allocation_stmt = $conn->prepare("
            SELECT 
                needs_amount,
                wants_amount,
                savings_amount
            FROM personal_budget_allocation 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $allocation_stmt->bind_param("i", $user_id);
        $allocation_stmt->execute();
        $allocation_result = $allocation_stmt->get_result();
        $allocation = $allocation_result->fetch_assoc();
        
        // Set default values if no allocation found
        $section_totals = [
            'needs' => $allocation ? floatval($allocation['needs_amount']) : 0,
            'wants' => $allocation ? floatval($allocation['wants_amount']) : 0,
            'savings' => $allocation ? floatval($allocation['savings_amount']) : 0
        ];
        
        $stmt = $conn->prepare("
            SELECT 
                c.id, 
                c.name, 
                c.category_type, 
                c.icon, 
                c.color, 
                c.budget_limit,
                COALESCE(SUM(CASE 
                    WHEN e.expense_date >= DATE_FORMAT(NOW(), '%Y-%m-01') 
                    THEN e.amount 
                    ELSE 0 
                END), 0) as spent_this_month
            FROM budget_categories c
            LEFT JOIN personal_expenses e ON c.id = e.category_id AND e.user_id = c.user_id
            WHERE c.user_id = ? AND c.is_active = 1
            GROUP BY c.id, c.name, c.category_type, c.icon, c.color, c.budget_limit
            ORDER BY c.category_type, c.name
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [
            'needs' => [],
            'wants' => [],
            'savings' => []
        ];
        
        // Track section-level spending
        $section_spending = [
            'needs' => 0,
            'wants' => 0,
            'savings' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            $remaining = $row['budget_limit'] - $row['spent_this_month'];
            $percentage = $row['budget_limit'] > 0 ? ($row['spent_this_month'] / $row['budget_limit']) * 100 : 0;
            
            $row['remaining_budget'] = $remaining;
            $row['budget_percentage'] = round($percentage, 1);
            $row['status'] = $percentage >= 100 ? 'over' : ($percentage >= 80 ? 'warning' : 'good');
            
            // Add to section spending total
            $section_spending[$row['category_type']] += floatval($row['spent_this_month']);
            
            $categories[$row['category_type']][] = $row;
        }
        
        // Add section totals to the response
        $response = [
            'success' => true, 
            'categories' => $categories,
            'section_totals' => $section_totals,
            'section_spending' => $section_spending
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getExpenses($conn, $user_id) {
    try {
        $limit = intval($_POST['limit'] ?? 20);
        $offset = intval($_POST['offset'] ?? 0);
        $category_filter = $_POST['category_filter'] ?? '';
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        
        $where_conditions = ["e.user_id = ?"];
        $params = [$user_id];
        $param_types = "i";
        
        if (!empty($category_filter)) {
            $where_conditions[] = "e.category_id = ?";
            $params[] = $category_filter;
            $param_types .= "i";
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = "e.expense_date >= ?";
            $params[] = $date_from;
            $param_types .= "s";
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "e.expense_date <= ?";
            $params[] = $date_to;
            $param_types .= "s";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $stmt = $conn->prepare("
            SELECT 
                e.id,
                e.amount,
                e.description,
                e.expense_date,
                e.payment_method,
                e.tags,
                e.created_at,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                c.category_type
            FROM personal_expenses e
            LEFT JOIN budget_categories c ON e.category_id = c.id
            WHERE $where_clause
            ORDER BY e.expense_date DESC, e.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $param_types .= "ii";
        
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $expenses = [];
        while ($row = $result->fetch_assoc()) {
            $tags = json_decode($row['tags'], true);
            $row['notes'] = $tags['notes'] ?? '';
            unset($row['tags']);
            $expenses[] = $row;
        }
        
        // Get total count for pagination
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM personal_expenses e WHERE $where_clause");
        $count_params = array_slice($params, 0, -2); // Remove limit and offset
        $count_param_types = substr($param_types, 0, -2);
        $count_stmt->bind_param($count_param_types, ...$count_params);
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        
        echo json_encode([
            'success' => true, 
            'expenses' => $expenses,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getExpenseSummary($conn, $user_id) {
    try {
        $current_month = date('Y-m');
        $last_month = date('Y-m', strtotime('-1 month'));
        
        // Get budget allocation
        $allocation_stmt = $conn->prepare("
            SELECT 
                needs_percentage, 
                wants_percentage, 
                savings_percentage, 
                monthly_salary,
                needs_amount,
                wants_amount,
                savings_amount
            FROM personal_budget_allocation 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $allocation_stmt->bind_param("i", $user_id);
        $allocation_stmt->execute();
        $allocation = $allocation_stmt->get_result()->fetch_assoc();
        
        // Get total expenses for current month by category type
        $expense_stmt = $conn->prepare("
            SELECT 
                c.category_type,
                COALESCE(SUM(e.amount), 0) as total_spent,
                COUNT(e.id) as transaction_count
            FROM budget_categories c
            LEFT JOIN personal_expenses e ON c.id = e.category_id 
                AND e.user_id = c.user_id 
                AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
            WHERE c.user_id = ? AND c.is_active = 1
            GROUP BY c.category_type
        ");
        $expense_stmt->bind_param("si", $current_month, $user_id);
        $expense_stmt->execute();
        $expense_result = $expense_stmt->get_result();
        
        $expenses_by_type = [];
        $total_expenses = 0;
        $total_transactions = 0;
        
        while ($row = $expense_result->fetch_assoc()) {
            $expenses_by_type[$row['category_type']] = [
                'spent' => floatval($row['total_spent']),
                'transactions' => intval($row['transaction_count'])
            ];
            if ($row['category_type'] !== 'savings') {
                $total_expenses += floatval($row['total_spent']);
                $total_transactions += intval($row['transaction_count']);
            }
        }
        
        // Get last month total for comparison
        $last_month_stmt = $conn->prepare("
            SELECT COALESCE(SUM(e.amount), 0) as last_month_total
            FROM personal_expenses e
            JOIN budget_categories c ON e.category_id = c.id
            WHERE e.user_id = ? AND c.category_type IN ('needs', 'wants')
            AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
        ");
        $last_month_stmt->bind_param("is", $user_id, $last_month);
        $last_month_stmt->execute();
        $last_month_total = $last_month_stmt->get_result()->fetch_assoc()['last_month_total'];
        
        // Calculate daily average
        $days_in_month = date('t');
        $current_day = date('j');
        $daily_average = $current_day > 0 ? $total_expenses / $current_day : 0;
        
        // Calculate budget amounts if allocation exists
        $needs_budget = $allocation ? floatval($allocation['needs_amount']) : 0;
        $wants_budget = $allocation ? floatval($allocation['wants_amount']) : 0;
        
        // Get category breakdown
        $category_stmt = $conn->prepare("
            SELECT 
                c.id,
                c.name,
                c.icon,
                c.color,
                c.category_type,
                c.budget_limit,
                COALESCE(SUM(e.amount), 0) as spent_amount,
                COUNT(e.id) as transaction_count
            FROM budget_categories c
            LEFT JOIN personal_expenses e ON c.id = e.category_id 
                AND e.user_id = c.user_id 
                AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
            WHERE c.user_id = ? AND c.is_active = 1 AND c.category_type IN ('needs', 'wants')
            GROUP BY c.id, c.name, c.icon, c.color, c.category_type, c.budget_limit
            ORDER BY c.category_type, c.name
        ");
        $category_stmt->bind_param("si", $current_month, $user_id);
        $category_stmt->execute();
        $category_result = $category_stmt->get_result();
        
        $category_breakdown = [];
        while ($row = $category_result->fetch_assoc()) {
            $spent = floatval($row['spent_amount']);
            $budget = floatval($row['budget_limit']);
            $remaining = $budget - $spent;
            $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;
            
            $category_breakdown[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'icon' => $row['icon'],
                'color' => $row['color'],
                'category_type' => $row['category_type'],
                'spent' => $spent,
                'budget' => $budget,
                'remaining' => $remaining,
                'percentage' => round($percentage, 1),
                'transactions' => intval($row['transaction_count']),
                'status' => $percentage >= 100 ? 'over' : ($percentage >= 80 ? 'warning' : 'good')
            ];
        }
        
        // Calculate change from last month
        $month_change = $total_expenses - $last_month_total;
        $month_change_percentage = $last_month_total > 0 ? ($month_change / $last_month_total) * 100 : 0;
        
        $summary = [
            'total_expenses' => $total_expenses,
            'total_transactions' => $total_transactions,
            'needs' => [
                'spent' => $expenses_by_type['needs']['spent'] ?? 0,
                'budget' => $needs_budget,
                'remaining' => $needs_budget - ($expenses_by_type['needs']['spent'] ?? 0),
                'transactions' => $expenses_by_type['needs']['transactions'] ?? 0
            ],
            'wants' => [
                'spent' => $expenses_by_type['wants']['spent'] ?? 0,
                'budget' => $wants_budget, 
                'remaining' => $wants_budget - ($expenses_by_type['wants']['spent'] ?? 0),
                'transactions' => $expenses_by_type['wants']['transactions'] ?? 0
            ],
            'daily_average' => $daily_average,
            'month_change' => $month_change,
            'month_change_percentage' => round($month_change_percentage, 1),
            'category_breakdown' => $category_breakdown,
            'target_daily_average' => 100 // This could be calculated based on budget
        ];
        
        echo json_encode(['success' => true, 'summary' => $summary]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
