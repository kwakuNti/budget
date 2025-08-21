<?php
// Suppress any HTML output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors as HTML
ini_set('log_errors', 1);

// Set JSON header immediately
header('Content-Type: application/json');

// Clean any output buffer
if (ob_get_length()) {
    ob_clean();
}

try {
    require_once 'config/connection.php';
    
    // Test database connection
    if (!isset($conn)) {
        throw new Exception('Database connection variable not available');
    }
    
    // Test a simple query
    $conn->query("SELECT 1");
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

/**
 * Get valid goal types directly from database schema
 */
function getValidGoalTypesFromSchema($conn) {
    try {
        $query = "SHOW COLUMNS FROM personal_goals LIKE 'goal_type'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['Type']) {
            preg_match_all("/'([^']+)'/", $result['Type'], $matches);
            return $matches[1];
        }
    } catch (Exception $e) {
        error_log("Error getting valid goal types: " . $e->getMessage());
    }
    
    // Fallback
    return ['emergency_fund', 'vacation', 'car', 'house', 'education', 'other'];
}

/**
 * Check if a column exists in a table
 */
function checkColumnExists($conn, $table, $column) {
    try {
        $query = "SHOW COLUMNS FROM `$table` LIKE '$column'";
        $result = $conn->query($query);
        return $result->num_rows > 0;
    } catch (Exception $e) {
        error_log("Error checking column existence: " . $e->getMessage());
        return false;
    }
}

/**
 * CLEAN GOAL CREATION FUNCTION
 */
function createGoalClean($conn, $userId) {
    try {
        // Validate database connection
        if (!$conn) {
            throw new Exception('Database connection is null');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Log incoming data for debugging
        error_log("=== NEW GOAL CREATION STARTED ===");
        error_log("User ID: " . $userId);
        error_log("POST Data: " . json_encode($_POST));
        
        // Extract and validate input data
        $goalName = trim($_POST['goal_name'] ?? '');
        $targetAmount = floatval($_POST['target_amount'] ?? 0);
        $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
        $goalType = trim($_POST['goal_type'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $initialDeposit = floatval($_POST['initial_deposit'] ?? 0);
        
        // Validate required fields
        if (empty($goalName)) {
            throw new Exception('Goal name is required');
        }
        if ($targetAmount <= 0) {
            throw new Exception('Target amount must be greater than 0');
        }
        if (empty($goalType)) {
            throw new Exception('Goal type is required');
        }
        
        // Validate goal_type against database schema
        $validGoalTypes = getValidGoalTypesFromSchema($conn);
        error_log("Valid goal types from schema: " . json_encode($validGoalTypes));
        error_log("Submitted goal type: '$goalType'");
        
        if (!in_array($goalType, $validGoalTypes)) {
            error_log("Invalid goal type submitted. Defaulting to 'other'");
            $goalType = 'other';
        }
        
        // Use basic goal creation for now
        $sql = "INSERT INTO personal_goals (user_id, goal_name, target_amount, current_amount, goal_type, priority, is_completed) 
                VALUES (?, ?, ?, ?, ?, ?, 0)";
        
        error_log("Using basic SQL: " . $sql);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("issdss", $userId, $goalName, $targetAmount, $initialDeposit, $goalType, $priority);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        
        $goalId = $conn->insert_id;
        error_log("Goal created with ID: " . $goalId);
        
        // Commit transaction
        $conn->commit();
        
        error_log("=== GOAL CREATION COMPLETED SUCCESSFULLY ===");
        
        return [
            'success' => true,
            'message' => 'Goal created successfully!',
            'goal_id' => $goalId
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn && method_exists($conn, 'inTransaction') && $conn->inTransaction()) {
            $conn->rollback();
        }
        
        error_log("Goal creation failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to create goal: ' . $e->getMessage()
        ];
    }
}

// Test the function if called directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_create_goal') {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $result = createGoalClean($conn, $_SESSION['user_id']);
    echo json_encode($result);
    exit;
}

// Default response if not a POST request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>
