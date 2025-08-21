<?php
/**
 * Auto-Save Configuration API
 * Handles comprehensive auto-savings settings and challenge management
 */

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access - please log in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_config':
            getAutoSaveConfig($conn, $userId);
            break;
            
        case 'update_config':
            updateAutoSaveConfig($conn, $userId);
            break;
            
        case 'get_challenges':
            getChallenges($conn, $userId);
            break;
            
        case 'create_challenge':
            createChallenge($conn, $userId);
            break;
            
        case 'update_challenge':
            updateChallenge($conn, $userId);
            break;
            
        case 'complete_challenge':
            completeChallenge($conn, $userId);
            break;
            
        case 'abandon_challenge':
            abandonChallenge($conn, $userId);
            break;
            
        case 'add_challenge_progress':
            addChallengeProgress($conn, $userId);
            break;
            
        case 'get_challenge_progress':
            getChallengeProgress($conn, $userId);
            break;
            
        case 'process_autosave':
            processAutoSave($conn, $userId);
            break;
            
        case 'get_autosave_history':
            getAutoSaveHistory($conn, $userId);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

function getAutoSaveConfig($conn, $userId) {
    try {
        // Get current autosave configuration
        $stmt = $conn->prepare("
            SELECT 
                enabled,
                save_frequency,
                save_day,
                round_up_enabled,
                round_up_threshold,
                emergency_fund_priority,
                emergency_fund_target
            FROM personal_autosave_config 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();
        
        if (!$config) {
            // Create default configuration
            $stmt = $conn->prepare("
                INSERT INTO personal_autosave_config 
                (user_id, enabled, save_frequency, save_day, round_up_enabled, round_up_threshold, emergency_fund_priority, emergency_fund_target) 
                VALUES (?, 0, 'monthly', 1, 0, 5.00, 1, 1000.00)
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            $config = [
                'enabled' => false,
                'save_frequency' => 'monthly',
                'save_day' => 1,
                'round_up_enabled' => false,
                'round_up_threshold' => 5.00,
                'emergency_fund_priority' => true,
                'emergency_fund_target' => 1000.00
            ];
        } else {
            $config = [
                'enabled' => (bool)$config['enabled'],
                'save_frequency' => $config['save_frequency'],
                'save_day' => (int)$config['save_day'],
                'round_up_enabled' => (bool)$config['round_up_enabled'],
                'round_up_threshold' => (float)$config['round_up_threshold'],
                'emergency_fund_priority' => (bool)$config['emergency_fund_priority'],
                'emergency_fund_target' => (float)$config['emergency_fund_target']
            ];
        }
        
        // Get active goals for auto-save allocation
        $stmt = $conn->prepare("
            SELECT 
                pg.id,
                pg.goal_name,
                pg.target_amount,
                pg.current_amount,
                pg.goal_type,
                COALESCE(pgs.auto_save_enabled, 0) as auto_save_enabled,
                COALESCE(pgs.save_method, 'manual') as save_method,
                COALESCE(pgs.save_percentage, 0) as save_percentage,
                COALESCE(pgs.save_amount, 0) as save_amount
            FROM personal_goals pg
            LEFT JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
            WHERE pg.user_id = ? 
            AND pg.is_completed = 0 
            AND COALESCE(pg.status, 'active') = 'active'
            ORDER BY 
                CASE pg.goal_type WHEN 'emergency_fund' THEN 1 ELSE 2 END,
                CASE pg.priority WHEN 'high' THEN 3 WHEN 'medium' THEN 2 WHEN 'low' THEN 1 ELSE 0 END DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $goals = [];
        while ($row = $result->fetch_assoc()) {
            $goals[] = [
                'id' => (int)$row['id'],
                'goal_name' => $row['goal_name'],
                'target_amount' => (float)$row['target_amount'],
                'current_amount' => (float)$row['current_amount'],
                'goal_type' => $row['goal_type'],
                'auto_save_enabled' => (bool)$row['auto_save_enabled'],
                'save_method' => $row['save_method'],
                'save_percentage' => (float)$row['save_percentage'],
                'save_amount' => (float)$row['save_amount'],
                'progress_percentage' => $row['target_amount'] > 0 ? round(($row['current_amount'] / $row['target_amount']) * 100, 1) : 0
            ];
        }
        
        echo json_encode([
            'success' => true,
            'config' => $config,
            'goals' => $goals
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get autosave config: ' . $e->getMessage());
    }
}

function updateAutoSaveConfig($conn, $userId) {
    try {
        $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;
        $saveFrequency = $_POST['save_frequency'] ?? 'monthly';
        $saveDay = (int)($_POST['save_day'] ?? 1);
        $roundUpEnabled = isset($_POST['round_up_enabled']) ? (bool)$_POST['round_up_enabled'] : false;
        $roundUpThreshold = (float)($_POST['round_up_threshold'] ?? 5.00);
        $emergencyFundPriority = isset($_POST['emergency_fund_priority']) ? (bool)$_POST['emergency_fund_priority'] : true;
        $emergencyFundTarget = (float)($_POST['emergency_fund_target'] ?? 1000.00);
        
        // Validate save_frequency
        $validFrequencies = ['weekly', 'biweekly', 'monthly'];
        if (!in_array($saveFrequency, $validFrequencies)) {
            $saveFrequency = 'monthly';
        }
        
        // Validate save_day based on frequency
        if ($saveFrequency === 'weekly' || $saveFrequency === 'biweekly') {
            // Day of week (1-7)
            if ($saveDay < 1 || $saveDay > 7) {
                $saveDay = 1; // Default to Monday
            }
        } else {
            // Day of month (1-31)
            if ($saveDay < 1 || $saveDay > 31) {
                $saveDay = 1; // Default to 1st of month
            }
        }
        
        // Update or insert configuration
        $stmt = $conn->prepare("
            INSERT INTO personal_autosave_config 
            (user_id, enabled, save_frequency, save_day, round_up_enabled, round_up_threshold, emergency_fund_priority, emergency_fund_target) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            enabled = VALUES(enabled),
            save_frequency = VALUES(save_frequency),
            save_day = VALUES(save_day),
            round_up_enabled = VALUES(round_up_enabled),
            round_up_threshold = VALUES(round_up_threshold),
            emergency_fund_priority = VALUES(emergency_fund_priority),
            emergency_fund_target = VALUES(emergency_fund_target),
            updated_at = CURRENT_TIMESTAMP
        ");
        $enabledInt = $enabled ? 1 : 0;
        $roundUpEnabledInt = $roundUpEnabled ? 1 : 0;
        $emergencyPriorityInt = $emergencyFundPriority ? 1 : 0;
        
        $stmt->bind_param("isisidid", $userId, $enabledInt, $saveFrequency, $saveDay, $roundUpEnabledInt, $roundUpThreshold, $emergencyPriorityInt, $emergencyFundTarget);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Auto-save configuration updated successfully'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to update autosave config: ' . $e->getMessage());
    }
}

function getChallenges($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id,
                challenge_type,
                title,
                description,
                target_amount,
                current_amount,
                target_category_id,
                start_date,
                end_date,
                status,
                reward_amount,
                created_at,
                updated_at
            FROM personal_weekly_challenges 
            WHERE user_id = ? 
            ORDER BY 
                CASE status 
                    WHEN 'active' THEN 1 
                    WHEN 'completed' THEN 2 
                    WHEN 'failed' THEN 3 
                    WHEN 'abandoned' THEN 4 
                    ELSE 5 
                END,
                start_date DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $challenges = [];
        while ($row = $result->fetch_assoc()) {
            $progressPercentage = 0;
            if ($row['target_amount'] > 0) {
                $progressPercentage = min(100, ($row['current_amount'] / $row['target_amount']) * 100);
            }
            
            $challenges[] = [
                'id' => (int)$row['id'],
                'challenge_type' => $row['challenge_type'],
                'title' => $row['title'],
                'description' => $row['description'],
                'target_amount' => (float)$row['target_amount'],
                'current_amount' => (float)$row['current_amount'],
                'target_category_id' => $row['target_category_id'] ? (int)$row['target_category_id'] : null,
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'status' => $row['status'],
                'reward_amount' => (float)$row['reward_amount'],
                'progress_percentage' => round($progressPercentage, 1),
                'days_remaining' => max(0, (strtotime($row['end_date']) - time()) / 86400),
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'challenges' => $challenges
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get challenges: ' . $e->getMessage());
    }
}

function createChallenge($conn, $userId) {
    try {
        $challengeType = $_POST['challenge_type'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $targetAmount = (float)($_POST['target_amount'] ?? 0);
        $targetCategoryId = !empty($_POST['target_category_id']) ? (int)$_POST['target_category_id'] : null;
        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $endDate = $_POST['end_date'] ?? '';
        $rewardAmount = (float)($_POST['reward_amount'] ?? 0);
        
        // Validate challenge type
        $validTypes = ['save_amount', 'no_spend', 'reduce_category', 'round_up'];
        if (!in_array($challengeType, $validTypes)) {
            throw new Exception('Invalid challenge type');
        }
        
        if (empty($title) || empty($endDate)) {
            throw new Exception('Title and end date are required');
        }
        
        // Validate dates
        if (strtotime($endDate) <= strtotime($startDate)) {
            throw new Exception('End date must be after start date');
        }
        
        $stmt = $conn->prepare("
            INSERT INTO personal_weekly_challenges 
            (user_id, challenge_type, title, description, target_amount, target_category_id, start_date, end_date, reward_amount, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param("isssdisd", $userId, $challengeType, $title, $description, $targetAmount, $targetCategoryId, $startDate, $endDate, $rewardAmount);
        $stmt->execute();
        
        $challengeId = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Challenge created successfully',
            'challenge_id' => $challengeId
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to create challenge: ' . $e->getMessage());
    }
}

function updateChallenge($conn, $userId) {
    try {
        $challengeId = (int)($_POST['challenge_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $targetAmount = (float)($_POST['target_amount'] ?? 0);
        $endDate = $_POST['end_date'] ?? '';
        $rewardAmount = (float)($_POST['reward_amount'] ?? 0);
        
        if (!$challengeId || empty($title)) {
            throw new Exception('Challenge ID and title are required');
        }
        
        // Verify challenge belongs to user
        $stmt = $conn->prepare("SELECT id FROM personal_weekly_challenges WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $challengeId, $userId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Challenge not found');
        }
        
        $stmt = $conn->prepare("
            UPDATE personal_weekly_challenges 
            SET title = ?, description = ?, target_amount = ?, end_date = ?, reward_amount = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssdsdii", $title, $description, $targetAmount, $endDate, $rewardAmount, $challengeId, $userId);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Challenge updated successfully'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to update challenge: ' . $e->getMessage());
    }
}

function addChallengeProgress($conn, $userId) {
    try {
        $challengeId = (int)($_POST['challenge_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $progressDate = $_POST['progress_date'] ?? date('Y-m-d');
        
        if (!$challengeId || $amount <= 0) {
            throw new Exception('Challenge ID and amount are required');
        }
        
        // Verify challenge belongs to user and is active
        $stmt = $conn->prepare("
            SELECT current_amount, target_amount, status 
            FROM personal_weekly_challenges 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $challengeId, $userId);
        $stmt->execute();
        $challenge = $stmt->get_result()->fetch_assoc();
        
        if (!$challenge) {
            throw new Exception('Challenge not found');
        }
        
        if ($challenge['status'] !== 'active') {
            throw new Exception('Cannot add progress to inactive challenge');
        }
        
        $conn->begin_transaction();
        
        try {
            // Add progress entry
            $stmt = $conn->prepare("
                INSERT INTO personal_challenge_progress 
                (challenge_id, user_id, progress_date, amount, description) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisds", $challengeId, $userId, $progressDate, $amount, $description);
            $stmt->execute();
            
            // Update challenge current amount
            $newAmount = $challenge['current_amount'] + $amount;
            $status = 'active';
            
            // Check if challenge is completed
            if ($newAmount >= $challenge['target_amount']) {
                $status = 'completed';
            }
            
            $stmt = $conn->prepare("
                UPDATE personal_weekly_challenges 
                SET current_amount = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->bind_param("dsi", $newAmount, $status, $challengeId);
            $stmt->execute();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => $status === 'completed' ? 'Challenge completed!' : 'Progress added successfully',
                'new_amount' => $newAmount,
                'status' => $status,
                'is_completed' => $status === 'completed'
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        throw new Exception('Failed to add challenge progress: ' . $e->getMessage());
    }
}

function processAutoSave($conn, $userId) {
    try {
        // Get autosave configuration
        $stmt = $conn->prepare("
            SELECT enabled, save_frequency, save_day, emergency_fund_priority, emergency_fund_target
            FROM personal_autosave_config 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();
        
        if (!$config || !$config['enabled']) {
            echo json_encode([
                'success' => false,
                'message' => 'Auto-save is not enabled'
            ]);
            return;
        }
        
        // Get user's budget allocation for available savings amount
        $stmt = $conn->prepare("
            SELECT savings_amount, monthly_salary
            FROM personal_budget_allocation 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $budgetData = $stmt->get_result()->fetch_assoc();
        
        if (!$budgetData) {
            throw new Exception('No budget allocation found. Please set up your budget first.');
        }
        
        $availableSavings = (float)$budgetData['savings_amount'];
        $monthlySalary = (float)$budgetData['monthly_salary'];
        
        // Get all active goals with auto-save enabled
        $stmt = $conn->prepare("
            SELECT 
                pg.id,
                pg.goal_name,
                pg.goal_type,
                pg.current_amount,
                pg.target_amount,
                pgs.save_method,
                pgs.save_percentage,
                pgs.save_amount
            FROM personal_goals pg
            JOIN personal_goal_settings pgs ON pg.id = pgs.goal_id
            WHERE pg.user_id = ? 
            AND pg.is_completed = 0 
            AND COALESCE(pg.status, 'active') = 'active'
            AND pgs.auto_save_enabled = 1
            ORDER BY 
                CASE 
                    WHEN pg.goal_type = 'emergency_fund' AND ? = 1 THEN 1 
                    ELSE 2 
                END,
                CASE pg.priority 
                    WHEN 'high' THEN 3 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 1 
                    ELSE 0 
                END DESC
        ");
        $emergencyPriority = $config['emergency_fund_priority'] ? 1 : 0;
        $stmt->bind_param("ii", $userId, $emergencyPriority);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $totalAllocated = 0;
        $processedGoals = [];
        
        $conn->begin_transaction();
        
        try {
            while ($row = $result->fetch_assoc() && $totalAllocated < $availableSavings) {
                $saveAmount = 0;
                
                // Calculate save amount based on method
                if ($row['save_method'] === 'percentage') {
                    $saveAmount = ($monthlySalary * $row['save_percentage']) / 100;
                } else if ($row['save_method'] === 'fixed') {
                    $saveAmount = (float)$row['save_amount'];
                }
                
                // Don't exceed available savings budget
                $remainingBudget = $availableSavings - $totalAllocated;
                $saveAmount = min($saveAmount, $remainingBudget);
                
                // Don't exceed goal target
                $remainingGoalAmount = $row['target_amount'] - $row['current_amount'];
                $saveAmount = min($saveAmount, $remainingGoalAmount);
                
                if ($saveAmount > 0) {
                    // Add contribution
                    $stmt = $conn->prepare("
                        INSERT INTO personal_goal_contributions 
                        (goal_id, user_id, amount, contribution_date, source, description) 
                        VALUES (?, ?, ?, CURDATE(), 'auto_save', 'Automatic savings contribution')
                    ");
                    $stmt->bind_param("iid", $row['id'], $userId, $saveAmount);
                    $stmt->execute();
                    
                    // Update goal current amount
                    $newAmount = $row['current_amount'] + $saveAmount;
                    $isCompleted = $newAmount >= $row['target_amount'] ? 1 : 0;
                    
                    $stmt = $conn->prepare("
                        UPDATE personal_goals 
                        SET current_amount = ?, is_completed = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("dii", $newAmount, $isCompleted, $row['id']);
                    $stmt->execute();
                    
                    $totalAllocated += $saveAmount;
                    $processedGoals[] = [
                        'goal_name' => $row['goal_name'],
                        'amount' => $saveAmount,
                        'new_amount' => $newAmount,
                        'is_completed' => (bool)$isCompleted
                    ];
                }
            }
            
            // Record auto-save history
            if ($totalAllocated > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO personal_auto_save_history 
                    (user_id, salary_date, salary_amount, total_auto_saved, remaining_after_saves, goals_processed) 
                    VALUES (?, CURDATE(), ?, ?, ?, ?)
                ");
                $remainingAfterSaves = $monthlySalary - $totalAllocated;
                $goalsJson = json_encode($processedGoals);
                $stmt->bind_param("iddds", $userId, $monthlySalary, $totalAllocated, $remainingAfterSaves, $goalsJson);
                $stmt->execute();
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Auto-save processed successfully',
                'total_saved' => $totalAllocated,
                'goals_processed' => count($processedGoals),
                'processed_goals' => $processedGoals
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        throw new Exception('Failed to process auto-save: ' . $e->getMessage());
    }
}

function completeChallenge($conn, $userId) {
    try {
        $challengeId = (int)($_POST['challenge_id'] ?? 0);
        
        if (!$challengeId) {
            throw new Exception('Challenge ID is required');
        }
        
        // Verify challenge belongs to user
        $stmt = $conn->prepare("
            SELECT id, reward_amount 
            FROM personal_weekly_challenges 
            WHERE id = ? AND user_id = ? AND status = 'active'
        ");
        $stmt->bind_param("ii", $challengeId, $userId);
        $stmt->execute();
        $challenge = $stmt->get_result()->fetch_assoc();
        
        if (!$challenge) {
            throw new Exception('Challenge not found or not active');
        }
        
        // Update challenge status
        $stmt = $conn->prepare("
            UPDATE personal_weekly_challenges 
            SET status = 'completed', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $challengeId);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Challenge completed successfully!',
            'reward_amount' => (float)$challenge['reward_amount']
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to complete challenge: ' . $e->getMessage());
    }
}

function abandonChallenge($conn, $userId) {
    try {
        $challengeId = (int)($_POST['challenge_id'] ?? 0);
        
        if (!$challengeId) {
            throw new Exception('Challenge ID is required');
        }
        
        // Update challenge status
        $stmt = $conn->prepare("
            UPDATE personal_weekly_challenges 
            SET status = 'abandoned', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $challengeId, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Challenge abandoned'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Challenge not found'
            ]);
        }
        
    } catch (Exception $e) {
        throw new Exception('Failed to abandon challenge: ' . $e->getMessage());
    }
}

function getAutoSaveHistory($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                salary_date,
                salary_amount,
                total_auto_saved,
                remaining_after_saves,
                goals_processed,
                created_at
            FROM personal_auto_save_history 
            WHERE user_id = ? 
            ORDER BY salary_date DESC 
            LIMIT 10
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $goalsProcessed = json_decode($row['goals_processed'], true) ?? [];
            
            $history[] = [
                'salary_date' => $row['salary_date'],
                'salary_amount' => (float)$row['salary_amount'],
                'total_auto_saved' => (float)$row['total_auto_saved'],
                'remaining_after_saves' => (float)$row['remaining_after_saves'],
                'goals_processed' => $goalsProcessed,
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get auto-save history: ' . $e->getMessage());
    }
}

function getChallengeProgress($conn, $userId) {
    try {
        $challengeId = (int)($_GET['challenge_id'] ?? 0);
        
        if (!$challengeId) {
            throw new Exception('Challenge ID is required');
        }
        
        $stmt = $conn->prepare("
            SELECT 
                progress_date,
                amount,
                description,
                created_at
            FROM personal_challenge_progress 
            WHERE challenge_id = ? AND user_id = ? 
            ORDER BY progress_date DESC
        ");
        $stmt->bind_param("ii", $challengeId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $progress = [];
        while ($row = $result->fetch_assoc()) {
            $progress[] = [
                'progress_date' => $row['progress_date'],
                'amount' => (float)$row['amount'],
                'description' => $row['description'],
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'progress' => $progress
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get challenge progress: ' . $e->getMessage());
    }
}

?>
