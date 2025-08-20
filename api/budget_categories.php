<?php
session_start();
require_once '../config/connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get budget allocation data first
            $stmt = $conn->prepare("
                SELECT 
                    needs_percentage, wants_percentage, savings_percentage,
                    monthly_salary,
                    needs_amount, wants_amount, savings_amount
                FROM personal_budget_allocation 
                WHERE user_id = ? AND is_active = TRUE 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $allocationResult = $stmt->get_result();
            $allocation = $allocationResult->fetch_assoc();
            
            // Get actual salary and additional income for proper calculation
            if ($allocation) {
                // Get base salary
                $stmt = $conn->prepare("
                    SELECT monthly_salary 
                    FROM salaries 
                    WHERE user_id = ? AND is_active = 1 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $salaryResult = $stmt->get_result()->fetch_assoc();
                $baseSalary = $salaryResult ? floatval($salaryResult['monthly_salary']) : 0;
                
                // Get additional income
                $stmt = $conn->prepare("
                    SELECT COALESCE(SUM(monthly_amount), 0) as total_additional_income 
                    FROM personal_income_sources 
                    WHERE user_id = ? AND is_active = 1 AND include_in_budget = 1
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $additionalResult = $stmt->get_result()->fetch_assoc();
                $additionalIncome = floatval($additionalResult['total_additional_income'] ?? 0);
                
                $totalMonthlyIncome = $baseSalary + $additionalIncome;
                
                // Update allocation data with correct breakdown
                $allocation['total_monthly_income'] = $totalMonthlyIncome;
                $allocation['base_salary'] = $baseSalary;
                $allocation['additional_income'] = $additionalIncome;
                $allocation['monthly_income'] = $totalMonthlyIncome; // For backward compatibility
                
                // Recalculate amounts based on total income if the stored monthly_salary is different
                if (abs(floatval($allocation['monthly_salary']) - $totalMonthlyIncome) > 0.01) {
                    $allocation['needs_amount'] = round($totalMonthlyIncome * intval($allocation['needs_percentage']) / 100, 2);
                    $allocation['wants_amount'] = round($totalMonthlyIncome * intval($allocation['wants_percentage']) / 100, 2);
                    $allocation['savings_amount'] = round($totalMonthlyIncome * intval($allocation['savings_percentage']) / 100, 2);
                }
            }
            
            // Get all budget categories for the user with spending data
            $stmt = $conn->prepare("
                SELECT 
                    bc.id,
                    bc.name,
                    bc.category_type,
                    bc.icon,
                    bc.color,
                    bc.budget_limit,
                    bc.is_active,
                    bc.created_at,
                    COALESCE(SUM(CASE WHEN 
                        MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) AND 
                        YEAR(pe.expense_date) = YEAR(CURRENT_DATE()) 
                        THEN pe.amount ELSE 0 END), 0) as current_month_spent,
                    COALESCE(COUNT(CASE WHEN 
                        MONTH(pe.expense_date) = MONTH(CURRENT_DATE()) AND 
                        YEAR(pe.expense_date) = YEAR(CURRENT_DATE()) 
                        THEN pe.id ELSE NULL END), 0) as expense_count
                FROM budget_categories bc
                LEFT JOIN personal_expenses pe ON bc.id = pe.category_id
                WHERE bc.user_id = ? AND bc.is_active = TRUE
                GROUP BY bc.id, bc.name, bc.category_type, bc.icon, bc.color, bc.budget_limit, bc.is_active, bc.created_at
                ORDER BY bc.category_type, bc.name
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = [];
            $categoryTotals = [
                'needs' => ['budgeted' => 0, 'spent' => 0, 'allocated' => $allocation ? floatval($allocation['needs_amount']) : 0],
                'wants' => ['budgeted' => 0, 'spent' => 0, 'allocated' => $allocation ? floatval($allocation['wants_amount']) : 0],
                'savings' => ['budgeted' => 0, 'spent' => 0, 'allocated' => $allocation ? floatval($allocation['savings_amount']) : 0]
            ];
            
            while ($row = $result->fetch_assoc()) {
                $budgetLimit = floatval($row['budget_limit']);
                $spent = floatval($row['current_month_spent']);
                $remaining = $budgetLimit - $spent;
                $percentageUsed = $budgetLimit > 0 ? ($spent / $budgetLimit) * 100 : 0;
                
                // Add to category totals
                $type = $row['category_type'];
                $categoryTotals[$type]['budgeted'] += $budgetLimit;
                $categoryTotals[$type]['spent'] += $spent;
                
                // Determine status
                $status = 'good';
                if ($spent > $budgetLimit) {
                    $status = 'over_budget';
                } elseif ($percentageUsed >= 90) {
                    $status = 'near_limit';
                } elseif ($percentageUsed >= 70) {
                    $status = 'on_track';
                }
                
                $categories[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'],
                    'category_type' => $row['category_type'],
                    'icon' => $row['icon'],
                    'color' => $row['color'],
                    'budget_limit' => $budgetLimit,
                    'current_month_spent' => $spent,
                    'remaining' => $remaining,
                    'percentage_used' => round($percentageUsed, 1),
                    'expense_count' => intval($row['expense_count']),
                    'status' => $status,
                    'is_active' => boolval($row['is_active']),
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'categories' => $categories,
                'allocation' => $allocation,
                'category_totals' => $categoryTotals
            ]);
            break;
            
        case 'POST':
            // Add new budget category
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($input['name']) || !isset($input['category_type']) || !isset($input['budget_limit'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: name, category_type, budget_limit'
                ]);
                exit;
            }
            
            $name = trim($input['name']);
            $category_type = $input['category_type'];
            $icon = $input['icon'] ?? '📝';
            $color = $input['color'] ?? '#3498db';
            $budget_limit = floatval($input['budget_limit']);
            
            // Validate category type
            if (!in_array($category_type, ['needs', 'wants', 'savings'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid category type'
                ]);
                exit;
            }
            
            // Check if category name already exists for this user
            $checkStmt = $conn->prepare("
                SELECT id FROM budget_categories 
                WHERE user_id = ? AND name = ? AND is_active = TRUE
            ");
            $checkStmt->bind_param("is", $userId, $name);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category name already exists'
                ]);
                exit;
            }
            
            // Insert new category
            $stmt = $conn->prepare("
                INSERT INTO budget_categories (user_id, name, category_type, icon, color, budget_limit)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssd", $userId, $name, $category_type, $icon, $color, $budget_limit);
            
            if ($stmt->execute()) {
                $categoryId = $conn->insert_id;
                
                // Get the newly created category
                $getStmt = $conn->prepare("
                    SELECT id, name, category_type, icon, color, budget_limit, created_at
                    FROM budget_categories 
                    WHERE id = ?
                ");
                $getStmt->bind_param("i", $categoryId);
                $getStmt->execute();
                $result = $getStmt->get_result();
                $newCategory = $result->fetch_assoc();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Category added successfully',
                    'category' => [
                        'id' => intval($newCategory['id']),
                        'name' => $newCategory['name'],
                        'category_type' => $newCategory['category_type'],
                        'icon' => $newCategory['icon'],
                        'color' => $newCategory['color'],
                        'budget_limit' => floatval($newCategory['budget_limit']),
                        'created_at' => $newCategory['created_at']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add category'
                ]);
            }
            break;
            
        case 'PUT':
            // Update existing budget category
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category ID is required'
                ]);
                exit;
            }
            
            $categoryId = intval($input['id']);
            $updates = [];
            $params = [];
            $types = '';
            
            // Build dynamic UPDATE query
            if (isset($input['name'])) {
                $updates[] = 'name = ?';
                $params[] = trim($input['name']);
                $types .= 's';
            }
            if (isset($input['category_type'])) {
                $updates[] = 'category_type = ?';
                $params[] = $input['category_type'];
                $types .= 's';
            }
            if (isset($input['icon'])) {
                $updates[] = 'icon = ?';
                $params[] = $input['icon'];
                $types .= 's';
            }
            if (isset($input['color'])) {
                $updates[] = 'color = ?';
                $params[] = $input['color'];
                $types .= 's';
            }
            if (isset($input['budget_limit'])) {
                $updates[] = 'budget_limit = ?';
                $params[] = floatval($input['budget_limit']);
                $types .= 'd';
            }
            
            if (empty($updates)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No fields to update'
                ]);
                exit;
            }
            
            $updates[] = 'updated_at = CURRENT_TIMESTAMP';
            $params[] = $userId;
            $params[] = $categoryId;
            $types .= 'ii';
            
            $sql = "UPDATE budget_categories SET " . implode(', ', $updates) . " WHERE user_id = ? AND id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update category'
                ]);
            }
            break;
            
        case 'DELETE':
            // Delete (soft delete) budget category
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category ID is required'
                ]);
                exit;
            }
            
            $categoryId = intval($input['id']);
            
            // Soft delete by setting is_active to FALSE
            $stmt = $conn->prepare("
                UPDATE budget_categories 
                SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = ? AND id = ?
            ");
            $stmt->bind_param("ii", $userId, $categoryId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete category'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
