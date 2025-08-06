<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ajax/cycle_management.php - Fixed version
session_start();

// Include database connection
$config_path = '../config/connection.php';
if (!file_exists($config_path)) {
    $config_path = dirname(__DIR__) . '/config/connection.php';
}

if (!file_exists($config_path)) {
    // Try alternative paths
    $possible_paths = [
        '../config/database.php',
        '../includes/db_connection.php',
        '../db/connection.php',
        'config/connection.php'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $config_path = $path;
            break;
        }
    }
}

if (!file_exists($config_path)) {
    error_log("Database connection file not found");
    http_response_code(500);
    echo json_encode(['error' => 'Database connection file not found']);
    exit;
}

require_once $config_path;

header('Content-Type: application/json');

// Validate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get family_id - try multiple sources
$family_id = $_SESSION['family_id'] ?? null;

if (!$family_id) {
    // Try to get family_id from database
    $stmt = $conn->prepare("SELECT family_id FROM family_members WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $family_id = $result['family_id'] ?? null;
    
    if ($family_id) {
        $_SESSION['family_id'] = $family_id;
    }
}

if (!$family_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No family association found']);
    exit;
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }
    $action = $input['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

try {
    switch ($action) {
        case 'get_cycle_status':
            getCycleStatus($conn, $family_id);
            break;
            
        case 'close_cycle':
            closeCycle($conn, $user_id, $family_id, $input ?? []);
            break;
            
        case 'get_debt_info':
            getDebtInfo($conn, $family_id);
            break;
            
        case 'clear_debt':
            clearDebt($conn, $input['debt_id'] ?? 0);
            break;
            
        case 'get_cycle_history':
            getCycleHistory($conn, $family_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    error_log("Cycle management error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function getCycleStatus($conn, $family_id) {
    try {
        // Ensure current cycle exists
        ensureCurrentCycleExists($conn, $family_id);
        
        // Get current cycle with additional calculated fields
        $cycle_stmt = $conn->prepare("
            SELECT 
                mc.*,
                DATEDIFF(mc.end_date, CURDATE()) as days_remaining,
                CASE 
                    WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
                    ELSE 0 
                END as completion_percentage,
                fg.total_pool as family_pool_balance,
                (SELECT COUNT(*) FROM monthly_cycles WHERE family_id = mc.family_id AND is_closed = TRUE) as cycles_completed
            FROM monthly_cycles mc
            JOIN family_groups fg ON mc.family_id = fg.id
            WHERE mc.family_id = ? AND mc.is_current = TRUE AND mc.is_closed = FALSE
            ORDER BY mc.created_at DESC LIMIT 1
        ");
        $cycle_stmt->bind_param("i", $family_id);
        $cycle_stmt->execute();
        $cycle = $cycle_stmt->get_result()->fetch_assoc();
        
        if (!$cycle) {
            // Try to create a cycle if none exists
            ensureCurrentCycleExists($conn, $family_id);
            $cycle_stmt->execute();
            $cycle = $cycle_stmt->get_result()->fetch_assoc();
        }
        
        if (!$cycle) {
            echo json_encode(['success' => false, 'message' => 'Unable to create or find active cycle']);
            return;
        }
        
        // Get member performance
        $performance_stmt = $conn->prepare("
            SELECT 
                mmp.*,
                CASE 
                    WHEN mmp.member_type = 'user' THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                    WHEN mmp.member_type = 'member' THEN CONCAT(COALESCE(fmo.first_name, ''), ' ', COALESCE(fmo.last_name, ''))
                    ELSE 'Unknown Member'
                END as member_name,
                CASE 
                    WHEN mmp.member_type = 'user' THEN COALESCE(fm.role, 'member')
                    WHEN mmp.member_type = 'member' THEN COALESCE(fmo.role, 'other')
                    ELSE 'unknown'
                END as role,
                CASE 
                    WHEN mmp.member_type = 'user' THEN COALESCE(fm.accumulated_debt, 0)
                    WHEN mmp.member_type = 'member' THEN COALESCE(fmo.accumulated_debt, 0)
                    ELSE 0
                END as accumulated_debt,
                CASE 
                    WHEN mmp.target_amount > 0 THEN ROUND((mmp.contributed_amount / mmp.target_amount) * 100, 2)
                    ELSE 0 
                END as progress_percentage
            FROM member_monthly_performance mmp
            LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
            LEFT JOIN users u ON fm.user_id = u.id
            LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member'
            WHERE mmp.cycle_id = ?
            ORDER BY progress_percentage DESC, member_name
        ");
        $performance_stmt->bind_param("i", $cycle['id']);
        $performance_stmt->execute();
        $performance = $performance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get debt summary
        $debt_stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_debt_records,
                COUNT(CASE WHEN is_cleared = FALSE THEN 1 END) as active_debts,
                COALESCE(SUM(CASE WHEN is_cleared = FALSE THEN deficit_amount ELSE 0 END), 0) as total_outstanding,
                COUNT(DISTINCT CASE 
                    WHEN is_cleared = FALSE AND member_type = 'user' THEN member_id
                    WHEN is_cleared = FALSE AND member_type = 'member' THEN member_only_id
                END) as members_with_debt
            FROM member_debt_history 
            WHERE family_id = ?
        ");
        $debt_stmt->bind_param("i", $family_id);
        $debt_stmt->execute();
        $debt_summary = $debt_stmt->get_result()->fetch_assoc();
        
        // Ensure numeric values
        $cycle['total_collected'] = floatval($cycle['total_collected'] ?? 0);
        $cycle['total_target'] = floatval($cycle['total_target'] ?? 0);
        $cycle['members_completed'] = intval($cycle['members_completed'] ?? 0);
        $cycle['members_pending'] = intval($cycle['members_pending'] ?? 0);
        $cycle['days_remaining'] = max(0, intval($cycle['days_remaining'] ?? 0));
        $cycle['completion_percentage'] = floatval($cycle['completion_percentage'] ?? 0);
        $cycle['family_pool_balance'] = floatval($cycle['family_pool_balance'] ?? 0);
        $cycle['cycles_completed'] = intval($cycle['cycles_completed'] ?? 0);
        $cycle['total_members'] = count($performance);
        
        echo json_encode([
            'success' => true,
            'cycle' => $cycle,
            'performance' => $performance,
            'debt_summary' => $debt_summary
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getCycleStatus: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading cycle status: ' . $e->getMessage()]);
    }
}

function closeCycle($conn, $user_id, $family_id, $input) {
    try {
        // Check permissions
        $perm_stmt = $conn->prepare("
            SELECT role FROM family_members 
            WHERE user_id = ? AND family_id = ? AND role IN ('admin', 'head')
        ");
        $perm_stmt->bind_param("ii", $user_id, $family_id);
        $perm_stmt->execute();
        
        if (!$perm_stmt->get_result()->fetch_assoc()) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to close cycles']);
            return;
        }
        
        // Get current cycle
        $cycle_stmt = $conn->prepare("
            SELECT id FROM monthly_cycles 
            WHERE family_id = ? AND is_current = TRUE AND is_closed = FALSE
        ");
        $cycle_stmt->bind_param("i", $family_id);
        $cycle_stmt->execute();
        $cycle = $cycle_stmt->get_result()->fetch_assoc();
        
        if (!$cycle) {
            echo json_encode(['success' => false, 'message' => 'No active cycle found']);
            return;
        }
        
        $conn->autocommit(FALSE);
        
        try {
            // Check if stored procedure exists
            $proc_check = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'CloseMonthlyCycle'");
            
            if ($proc_check->num_rows > 0) {
                // Call stored procedure to close cycle
                $close_stmt = $conn->prepare("CALL CloseMonthlyCycle(?, ?)");
                $close_stmt->bind_param("ii", $cycle['id'], $user_id);
                $close_stmt->execute();
            } else {
                // Manual cycle closing if procedure doesn't exist
                manualCloseCycle($conn, $cycle['id'], $user_id, $family_id);
            }
            
            // Log the action
            $log_stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, family_id, action_type, description, created_at)
                VALUES (?, ?, 'cycle_closed', ?, NOW())
            ");
            $description = "Cycle closed manually by user";
            $log_stmt->bind_param("iis", $user_id, $family_id, $description);
            $log_stmt->execute();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cycle closed successfully'
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        } finally {
            $conn->autocommit(TRUE);
        }
        
    } catch (Exception $e) {
        error_log("Error in closeCycle: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error closing cycle: ' . $e->getMessage()]);
    }
}

function manualCloseCycle($conn, $cycle_id, $user_id, $family_id) {
    // Get cycle info
    $cycle_stmt = $conn->prepare("SELECT cycle_month FROM monthly_cycles WHERE id = ?");
    $cycle_stmt->bind_param("i", $cycle_id);
    $cycle_stmt->execute();
    $cycle_month = $cycle_stmt->get_result()->fetch_assoc()['cycle_month'];
    
    // Get incomplete members
    $incomplete_stmt = $conn->prepare("
        SELECT * FROM member_monthly_performance 
        WHERE cycle_id = ? AND is_completed = FALSE AND target_amount > contributed_amount
    ");
    $incomplete_stmt->bind_param("i", $cycle_id);
    $incomplete_stmt->execute();
    $incomplete_members = $incomplete_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Process each incomplete member
    foreach ($incomplete_members as $member) {
        $deficit = $member['target_amount'] - $member['contributed_amount'];
        
        // Add to debt history
        $debt_stmt = $conn->prepare("
            INSERT INTO member_debt_history (
                family_id, member_id, member_only_id, member_type, 
                cycle_month, deficit_amount, target_amount, contributed_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $debt_stmt->bind_param(
            "iiissddd", 
            $family_id, 
            $member['member_id'], 
            $member['member_only_id'], 
            $member['member_type'],
            $cycle_month, 
            $deficit, 
            $member['target_amount'], 
            $member['contributed_amount']
        );
        $debt_stmt->execute();
        
        // Update member debt tracking
        if ($member['member_type'] === 'user' && $member['member_id']) {
            $update_stmt = $conn->prepare("
                UPDATE family_members 
                SET accumulated_debt = accumulated_debt + ?, 
                    months_behind = months_behind + 1,
                    current_month_contributed = 0,
                    goal_met_this_month = FALSE
                WHERE id = ?
            ");
            $update_stmt->bind_param("di", $deficit, $member['member_id']);
            $update_stmt->execute();
        } elseif ($member['member_type'] === 'member' && $member['member_only_id']) {
            $update_stmt = $conn->prepare("
                UPDATE family_members_only 
                SET accumulated_debt = accumulated_debt + ?, 
                    months_behind = months_behind + 1,
                    current_month_contributed = 0,
                    goal_met_this_month = FALSE
                WHERE id = ?
            ");
            $update_stmt->bind_param("di", $deficit, $member['member_only_id']);
            $update_stmt->execute();
        }
    }
    
    // Mark cycle as closed
    $close_stmt = $conn->prepare("
        UPDATE monthly_cycles 
        SET is_closed = TRUE, closed_by = ?, closed_at = NOW(), is_current = FALSE
        WHERE id = ?
    ");
    $close_stmt->bind_param("ii", $user_id, $cycle_id);
    $close_stmt->execute();
}

function getDebtInfo($conn, $family_id) {
    try {
        $debt_stmt = $conn->prepare("
            SELECT 
                mdh.*,
                CASE 
                    WHEN mdh.member_type = 'user' THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                    WHEN mdh.member_type = 'member' THEN CONCAT(COALESCE(fmo.first_name, ''), ' ', COALESCE(fmo.last_name, ''))
                    ELSE 'Unknown Member'
                END as member_name,
                DATEDIFF(CURDATE(), STR_TO_DATE(CONCAT(mdh.cycle_month, '-01'), '%Y-%m-%d')) as days_overdue
            FROM member_debt_history mdh
            LEFT JOIN family_members fm ON mdh.member_id = fm.id AND mdh.member_type = 'user'
            LEFT JOIN users u ON fm.user_id = u.id
            LEFT JOIN family_members_only fmo ON mdh.member_only_id = fmo.id AND mdh.member_type = 'member'
            WHERE mdh.family_id = ? AND mdh.is_cleared = FALSE
            ORDER BY mdh.cycle_month DESC, member_name
        ");
        $debt_stmt->bind_param("i", $family_id);
        $debt_stmt->execute();
        $debts = $debt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'debts' => $debts
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getDebtInfo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading debt info: ' . $e->getMessage()]);
    }
}

function clearDebt($conn, $debt_id) {
    try {
        if (!$debt_id) {
            echo json_encode(['success' => false, 'message' => 'Debt ID required']);
            return;
        }
        
        $clear_stmt = $conn->prepare("
            UPDATE member_debt_history 
            SET is_cleared = TRUE, cleared_date = CURDATE()
            WHERE id = ?
        ");
        $clear_stmt->bind_param("i", $debt_id);
        
        if ($clear_stmt->execute() && $clear_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Debt cleared successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Debt not found or already cleared']);
        }
        
    } catch (Exception $e) {
        error_log("Error in clearDebt: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error clearing debt: ' . $e->getMessage()]);
    }
}

function getCycleHistory($conn, $family_id) {
    try {
        $history_stmt = $conn->prepare("
            SELECT 
                mc.*,
                CASE 
                    WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
                    ELSE 0 
                END as completion_percentage
            FROM monthly_cycles mc
            WHERE mc.family_id = ?
            ORDER BY mc.cycle_year DESC, mc.cycle_month_num DESC
            LIMIT 12
        ");
        $history_stmt->bind_param("i", $family_id);
        $history_stmt->execute();
        $history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getCycleHistory: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading cycle history: ' . $e->getMessage()]);
    }
}

function ensureCurrentCycleExists($conn, $family_id) {
    try {
        $current_month = date('Y-m');
        
        $check_stmt = $conn->prepare("
            SELECT id FROM monthly_cycles 
            WHERE family_id = ? AND cycle_month = ? AND is_current = TRUE
        ");
        $check_stmt->bind_param("is", $family_id, $current_month);
        $check_stmt->execute();
        
        if (!$check_stmt->get_result()->fetch_assoc()) {
            // Check if stored procedure exists
            $proc_check = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'CreateNewMonthlyCycle'");
            
            if ($proc_check->num_rows > 0) {
                // Use stored procedure
                $create_stmt = $conn->prepare("CALL CreateNewMonthlyCycle(?, ?, ?)");
                $year = date('Y');
                $month = date('n');
                $create_stmt->bind_param("iii", $family_id, $year, $month);
                $create_stmt->execute();
            } else {
                // Manual cycle creation
                manualCreateCycle($conn, $family_id);
            }
        }
    } catch (Exception $e) {
        error_log("Error ensuring current cycle exists: " . $e->getMessage());
        // Don't throw exception here to avoid breaking the entire request
    }
}

function manualCreateCycle($conn, $family_id) {
    $year = date('Y');
    $month = date('n');
    $cycle_month = date('Y-m');
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    
    // Calculate total target
    $target_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(fm.monthly_contribution_goal), 0) + COALESCE(SUM(fmo.monthly_contribution_goal), 0) as total_target,
            COUNT(fm.id) + COUNT(fmo.id) as member_count
        FROM family_groups fg
        LEFT JOIN family_members fm ON fg.id = fm.family_id AND fm.is_active = TRUE
        LEFT JOIN family_members_only fmo ON fg.id = fmo.family_id AND fmo.is_active = TRUE
        WHERE fg.id = ?
    ");
    $target_stmt->bind_param("i", $family_id);
    $target_stmt->execute();
    $target_result = $target_stmt->get_result()->fetch_assoc();
    
    $total_target = $target_result['total_target'] ?? 0;
    $member_count = $target_result['member_count'] ?? 0;
    
    // Mark previous cycles as not current
    $update_stmt = $conn->prepare("UPDATE monthly_cycles SET is_current = FALSE WHERE family_id = ?");
    $update_stmt->bind_param("i", $family_id);
    $update_stmt->execute();
    
    // Create new cycle
    $create_stmt = $conn->prepare("
        INSERT INTO monthly_cycles (
            family_id, cycle_month, cycle_year, cycle_month_num, 
            start_date, end_date, total_target, members_pending
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $create_stmt->bind_param("isiissdi", $family_id, $cycle_month, $year, $month, $start_date, $end_date, $total_target, $member_count);
    $create_stmt->execute();
    
    $cycle_id = $conn->insert_id;
    
    // Create performance records for family members
    $fm_stmt = $conn->prepare("
        INSERT INTO member_monthly_performance (cycle_id, family_id, member_id, member_type, target_amount)
        SELECT ?, ?, fm.id, 'user', fm.monthly_contribution_goal
        FROM family_members fm
        WHERE fm.family_id = ? AND fm.is_active = TRUE AND fm.monthly_contribution_goal > 0
    ");
    $fm_stmt->bind_param("iii", $cycle_id, $family_id, $family_id);
    $fm_stmt->execute();
    
    // Create performance records for family members only
    $fmo_stmt = $conn->prepare("
        INSERT INTO member_monthly_performance (cycle_id, family_id, member_only_id, member_type, target_amount)
        SELECT ?, ?, fmo.id, 'member', fmo.monthly_contribution_goal
        FROM family_members_only fmo
        WHERE fmo.family_id = ? AND fmo.is_active = TRUE AND fmo.monthly_contribution_goal > 0
    ");
    $fmo_stmt->bind_param("iii", $cycle_id, $family_id, $family_id);
    $fmo_stmt->execute();
}
?>