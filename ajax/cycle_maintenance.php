<?php
// includes/cycle_maintenance.php - Maintenance functions
require_once '../config/connection.php';

function runCycleMaintenance($conn) {
    try {
        // Auto-close overdue cycles
        $grace_days = 3;
        $cutoff_date = date('Y-m-d', strtotime("-{$grace_days} days"));
        
        $overdue_stmt = $conn->prepare("
            SELECT id, family_id, end_date 
            FROM monthly_cycles 
            WHERE is_current = TRUE 
            AND is_closed = FALSE 
            AND end_date <= ?
        ");
        $overdue_stmt->bind_param("s", $cutoff_date);
        $overdue_stmt->execute();
        $overdue_cycles = $overdue_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($overdue_cycles as $cycle) {
            // Auto-close with system user (ID 1)
            $close_stmt = $conn->prepare("CALL CloseMonthlyCycle(?, ?)");
            $close_stmt->bind_param("ii", $cycle['id'], 1);
            $close_stmt->execute();
            
            // Log the auto-close
            $log_stmt = $conn->prepare("
                INSERT INTO activity_logs (family_id, action_type, description, created_at)
                VALUES (?, 'cycle_auto_closed', ?, NOW())
            ");
            $description = "Cycle automatically closed after grace period: " . $cycle['end_date'];
            $log_stmt->bind_param("is", $cycle['family_id'], $description);
            $log_stmt->execute();
        }
        
        // Ensure all families have current cycles
        $families_stmt = $conn->prepare("
            SELECT fg.id 
            FROM family_groups fg
            LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id AND mc.is_current = TRUE
            WHERE mc.id IS NULL
        ");
        $families_stmt->execute();
        $families_needing_cycles = $families_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($families_needing_cycles as $family) {
            $create_stmt = $conn->prepare("CALL CreateNewMonthlyCycle(?, ?, ?)");
            $year = date('Y');
            $month = date('n');
            $create_stmt->bind_param("iii", $family['id'], $year, $month);
            $create_stmt->execute();
        }
        
        return [
            'success' => true,
            'cycles_closed' => count($overdue_cycles),
            'cycles_created' => count($families_needing_cycles)
        ];
        
    } catch (Exception $e) {
        error_log("Cycle maintenance error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// This can be called from a cron job or manually
if (php_sapi_name() === 'cli' || (isset($_GET['run_maintenance']) && $_GET['run_maintenance'] === 'true')) {
    $result = runCycleMaintenance($conn);
    
    if (php_sapi_name() === 'cli') {
        echo "Cycle Maintenance Results:\n";
        echo "Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
        if ($result['success']) {
            echo "Cycles Closed: " . $result['cycles_closed'] . "\n";
            echo "Cycles Created: " . $result['cycles_created'] . "\n";
        } else {
            echo "Error: " . $result['error'] . "\n";
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>
