<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_category':
        try {
            $name = trim($_POST['name'] ?? '');
            $categoryType = $_POST['category_type'] ?? '';
            $icon = $_POST['icon'] ?? 'fa-circle';
            $color = $_POST['color'] ?? '#007bff';
            $budgetLimit = floatval($_POST['budget_limit'] ?? 0);

            // Validate input
            if (empty($name) || empty($categoryType)) {
                echo json_encode(['success' => false, 'message' => 'Name and category type are required']);
                exit;
            }

            if (!in_array($categoryType, ['needs', 'wants', 'savings'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid category type']);
                exit;
            }

            // Check if category name already exists for this user
            $stmt = $conn->prepare("SELECT id FROM budget_categories WHERE user_id = ? AND name = ? AND is_active = TRUE");
            $stmt->bind_param("is", $userId, $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists']);
                exit;
            }

            // Insert new category
            $stmt = $conn->prepare("
                INSERT INTO budget_categories (user_id, name, category_type, icon, color, budget_limit, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->bind_param("issssd", $userId, $name, $categoryType, $icon, $color, $budgetLimit);
            
            if ($stmt->execute()) {
                $categoryId = $conn->insert_id;
                echo json_encode([
                    'success' => true, 
                    'message' => 'Category added successfully',
                    'category_id' => $categoryId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add category']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding category: ' . $e->getMessage()]);
        }
        break;

    case 'edit_category':
        try {
            $categoryId = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $categoryType = $_POST['category_type'] ?? '';
            $icon = $_POST['icon'] ?? '';
            $color = $_POST['color'] ?? '';
            $budgetLimit = floatval($_POST['budget_limit'] ?? 0);

            // Validate input
            if ($categoryId <= 0 || empty($name) || empty($categoryType)) {
                echo json_encode(['success' => false, 'message' => 'Invalid category data']);
                exit;
            }

            // Check if category belongs to user
            $stmt = $conn->prepare("SELECT id FROM budget_categories WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $categoryId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                exit;
            }

            // Check if new name already exists for another category
            $stmt = $conn->prepare("SELECT id FROM budget_categories WHERE user_id = ? AND name = ? AND id != ? AND is_active = TRUE");
            $stmt->bind_param("isi", $userId, $name, $categoryId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists']);
                exit;
            }

            // Update category
            $stmt = $conn->prepare("
                UPDATE budget_categories 
                SET name = ?, category_type = ?, icon = ?, color = ?, budget_limit = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ssssdii", $name, $categoryType, $icon, $color, $budgetLimit, $categoryId, $userId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update category']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating category: ' . $e->getMessage()]);
        }
        break;

    case 'delete_category':
        try {
            $categoryId = intval($_POST['category_id'] ?? 0);

            if ($categoryId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
                exit;
            }

            // Check if category belongs to user
            $stmt = $conn->prepare("SELECT id FROM budget_categories WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $categoryId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                exit;
            }

            // Check if category has expenses
            $stmt = $conn->prepare("SELECT COUNT(*) as expense_count FROM personal_expenses WHERE category_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $categoryId, $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['expense_count'] > 0) {
                // Soft delete if there are expenses
                $stmt = $conn->prepare("UPDATE budget_categories SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $categoryId, $userId);
                $message = 'Category archived (has associated expenses)';
            } else {
                // Hard delete if no expenses
                $stmt = $conn->prepare("DELETE FROM budget_categories WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $categoryId, $userId);
                $message = 'Category deleted successfully';
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting category: ' . $e->getMessage()]);
        }
        break;

    case 'get_category_icons':
        // Return available icons for categories
        $icons = [
            'general' => ['fa-circle', 'fa-tags', 'fa-folder', 'fa-star'],
            'food' => ['fa-utensils', 'fa-coffee', 'fa-pizza-slice', 'fa-hamburger'],
            'transport' => ['fa-car', 'fa-bus', 'fa-subway', 'fa-bicycle'],
            'shopping' => ['fa-shopping-bag', 'fa-shopping-cart', 'fa-store', 'fa-gift'],
            'bills' => ['fa-file-invoice-dollar', 'fa-bolt', 'fa-wifi', 'fa-mobile-alt'],
            'entertainment' => ['fa-film', 'fa-gamepad', 'fa-music', 'fa-tv'],
            'health' => ['fa-heartbeat', 'fa-pills', 'fa-hospital', 'fa-dumbbell'],
            'savings' => ['fa-piggy-bank', 'fa-university', 'fa-chart-line', 'fa-coins']
        ];
        
        echo json_encode(['success' => true, 'icons' => $icons]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
