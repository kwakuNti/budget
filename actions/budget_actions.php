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
            $budgetPeriod = $_POST['budget_period'] ?? 'monthly';

            // Store original budget limit as entered by user
            $originalBudgetLimit = $budgetLimit;
            
            // Convert weekly budget to monthly for storage
            if ($budgetPeriod === 'weekly') {
                $budgetLimit = $budgetLimit * 4.33; // Convert weekly to monthly (52 weeks / 12 months = 4.33)
            }

            // Validate input
            if (empty($name) || empty($categoryType)) {
                echo json_encode(['success' => false, 'message' => 'Name and category type are required']);
                exit;
            }

            if (!in_array($categoryType, ['needs', 'wants', 'savings'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid category type']);
                exit;
            }

            if (!in_array($budgetPeriod, ['weekly', 'monthly'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid budget period']);
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

            // Insert new category with budget period
            $stmt = $conn->prepare("
                INSERT INTO budget_categories (user_id, name, category_type, icon, color, budget_limit, budget_period, original_budget_limit, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->bind_param("issssdsd", $userId, $name, $categoryType, $icon, $color, $budgetLimit, $budgetPeriod, $originalBudgetLimit);
            
            if ($stmt->execute()) {
                $categoryId = $conn->insert_id;
                
                // If this is a savings category, create a corresponding savings goal
                if ($categoryType === 'savings') {
                    createSavingsGoalFromCategory($conn, $userId, $name, $budgetLimit, $color);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Category added successfully',
                    'category_id' => $categoryId,
                    'budget_period' => $budgetPeriod,
                    'original_limit' => $originalBudgetLimit,
                    'monthly_limit' => $budgetLimit
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
            $budgetPeriod = $_POST['budget_period'] ?? 'monthly';

            // Store original budget limit as entered by user
            $originalBudgetLimit = $budgetLimit;
            
            // Convert weekly budget to monthly for storage
            if ($budgetPeriod === 'weekly') {
                $budgetLimit = $budgetLimit * 4.33; // Convert weekly to monthly
            }

            // Validate input
            if ($categoryId <= 0 || empty($name) || empty($categoryType)) {
                echo json_encode(['success' => false, 'message' => 'Invalid category data']);
                exit;
            }

            if (!in_array($budgetPeriod, ['weekly', 'monthly'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid budget period']);
                exit;
            }

            // Get the original category details to check if it was a savings category
            $stmt = $conn->prepare("SELECT name, category_type FROM budget_categories WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $categoryId, $userId);
            $stmt->execute();
            $originalCategory = $stmt->get_result()->fetch_assoc();
            
            if (!$originalCategory) {
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

            // Update category with budget period
            $stmt = $conn->prepare("
                UPDATE budget_categories 
                SET name = ?, category_type = ?, icon = ?, color = ?, budget_limit = ?, budget_period = ?, original_budget_limit = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ssssdsdii", $name, $categoryType, $icon, $color, $budgetLimit, $budgetPeriod, $originalBudgetLimit, $categoryId, $userId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // If this was or is now a savings category, update corresponding savings goal
                if ($originalCategory['category_type'] === 'savings' || $categoryType === 'savings') {
                    updateLinkedSavingsGoal($conn, $userId, $originalCategory['name'], $name, $budgetLimit, $categoryType);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Category updated successfully',
                    'budget_period' => $budgetPeriod,
                    'original_limit' => $originalBudgetLimit,
                    'monthly_limit' => $budgetLimit
                ]);
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

/**
 * Update linked savings goal when budget category is modified
 */
function updateLinkedSavingsGoal($conn, $userId, $oldName, $newName, $newBudgetLimit, $newCategoryType) {
    try {
        // If category is no longer savings type, deactivate the goal
        if ($newCategoryType !== 'savings') {
            $stmt = $conn->prepare("
                UPDATE personal_goals 
                SET status = 'paused' 
                WHERE user_id = ? AND goal_name = ?
            ");
            $stmt->bind_param("is", $userId, $oldName);
            $stmt->execute();
            return;
        }
        
        // Find existing goal with old name
        $stmt = $conn->prepare("
            SELECT id FROM personal_goals 
            WHERE user_id = ? AND goal_name = ?
        ");
        $stmt->bind_param("is", $userId, $oldName);
        $stmt->execute();
        $goal = $stmt->get_result()->fetch_assoc();
        
        if ($goal) {
            // Update existing goal
            $stmt = $conn->prepare("
                UPDATE personal_goals 
                SET goal_name = ?, target_amount = ? 
                WHERE id = ?
            ");
            $newTargetAmount = $newBudgetLimit * 12; // 12 months worth
            $stmt->bind_param("sdi", $newName, $newTargetAmount, $goal['id']);
            $stmt->execute();
            
            // Update goal settings
            $stmt = $conn->prepare("
                UPDATE personal_goal_settings 
                SET save_amount = ? 
                WHERE goal_id = ?
            ");
            $stmt->bind_param("di", $newBudgetLimit, $goal['id']);
            $stmt->execute();
        } else {
            // Create new goal if it doesn't exist
            createSavingsGoalFromCategory($conn, $userId, $newName, $newBudgetLimit, '#34495e');
        }
        
    } catch (Exception $e) {
        // Silently fail - don't break budget category update
        error_log("Failed to update linked savings goal: " . $e->getMessage());
    }
}

/**
 * Create a savings goal when a savings category is created
 */
function createSavingsGoalFromCategory($conn, $userId, $categoryName, $monthlyBudget, $color) {
    try {
        // Check if goal with same name already exists
        $stmt = $conn->prepare("
            SELECT id FROM personal_goals 
            WHERE user_id = ? AND goal_name = ?
        ");
        $stmt->bind_param("is", $userId, $categoryName);
        $stmt->execute();
        $existingGoal = $stmt->get_result()->fetch_assoc();
        
        if ($existingGoal) {
            // Goal already exists, just return
            return;
        }
        
        // Set target amount equal to the budget limit as requested
        $targetAmount = $monthlyBudget;
        
        // Determine goal type based on category name
        $goalType = determineGoalType($categoryName);
        
        // Check if status column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM personal_goals LIKE 'status'");
        $stmt->execute();
        $statusExists = $stmt->get_result()->num_rows > 0;
        
        if ($statusExists) {
            // Create the goal with status
            $stmt = $conn->prepare("
                INSERT INTO personal_goals 
                (user_id, goal_name, target_amount, current_amount, goal_type, priority, status, is_completed) 
                VALUES (?, ?, ?, 0, ?, 'medium', 'active', 0)
            ");
            $stmt->bind_param("isds", $userId, $categoryName, $targetAmount, $goalType);
        } else {
            // Create the goal without status
            $stmt = $conn->prepare("
                INSERT INTO personal_goals 
                (user_id, goal_name, target_amount, current_amount, goal_type, priority, is_completed) 
                VALUES (?, ?, ?, 0, ?, 'medium', 0)
            ");
            $stmt->bind_param("isds", $userId, $categoryName, $targetAmount, $goalType);
        }
        
        $stmt->execute();
        $goalId = $conn->insert_id;
        
        // Create goal settings - only enable auto-save if there's a budget amount
        if ($monthlyBudget > 0) {
            $stmt = $conn->prepare("
                INSERT INTO personal_goal_settings 
                (goal_id, auto_save_enabled, save_method, save_amount, deduct_from_income) 
                VALUES (?, 1, 'fixed', ?, 1)
            ");
            $stmt->bind_param("id", $goalId, $monthlyBudget);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("
                INSERT INTO personal_goal_settings 
                (goal_id, auto_save_enabled, save_method, save_amount, deduct_from_income) 
                VALUES (?, 0, 'manual', 0, 0)
            ");
            $stmt->bind_param("i", $goalId);
            $stmt->execute();
        }
        
    } catch (Exception $e) {
        // Silently fail - don't break budget category creation
        error_log("Failed to create savings goal from category: " . $e->getMessage());
    }
}

/**
 * Determine goal type based on category name
 */
function determineGoalType($categoryName) {
    $name = strtolower($categoryName);
    
    if (strpos($name, 'emergency') !== false || strpos($name, 'fund') !== false) {
        return 'emergency_fund';
    } elseif (strpos($name, 'vacation') !== false || strpos($name, 'travel') !== false || strpos($name, 'holiday') !== false) {
        return 'vacation';
    } elseif (strpos($name, 'car') !== false || strpos($name, 'vehicle') !== false) {
        return 'car';
    } elseif (strpos($name, 'house') !== false || strpos($name, 'home') !== false || strpos($name, 'mortgage') !== false) {
        return 'house';
    } elseif (strpos($name, 'education') !== false || strpos($name, 'school') !== false || strpos($name, 'college') !== false) {
        return 'education';
    } else {
        return 'other';
    }
}

?>
