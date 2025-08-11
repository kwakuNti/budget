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
            // Get all budget categories for the user
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    name,
                    category_type,
                    icon,
                    color,
                    budget_limit,
                    is_active,
                    created_at
                FROM budget_categories 
                WHERE user_id = ? AND is_active = TRUE
                ORDER BY category_type, name
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'],
                    'category_type' => $row['category_type'],
                    'icon' => $row['icon'],
                    'color' => $row['color'],
                    'budget_limit' => floatval($row['budget_limit']),
                    'is_active' => boolval($row['is_active']),
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'categories' => $categories
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
