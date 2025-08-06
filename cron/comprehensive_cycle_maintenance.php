<?php
/**
 * Comprehensive Cycle Maintenance System
 * 
 * This script handles:
 * 1. Auto-closing overdue cycles
 * 2. Creating new cycles at the start of each month
 * 3. Cleaning up old cycle data
 * 
 * Can be run via:
 * - Cron job (recommended): Run daily at 2 AM
 * - Manual execution via web browser
 * - Called from application startup
 */

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/cycle_functions.php';

// Configuration
$GRACE_DAYS_AFTER_MONTH_END = 3; // Days to wait before auto-closing cycles
$CLEANUP_MONTHS_TO_KEEP = 12; // Keep cycle data for 12 months

/**
 * Main cycle maintenance function
 */
function runComprehensiveCycleMaintenance($conn) {
    global $GRACE_DAYS_AFTER_MONTH_END, $CLEANUP_MONTHS_TO_KEEP;
    
    $results = [
        'auto_closed_cycles' => 0,
        'new_cycles_created' => 0,
        'errors' => []
    ];
    
    try {
        echo "Starting cycle maintenance at " . date('Y-m-d H:i:s') . "\n";
        
        // 1. Auto-close overdue cycles
        $results['auto_closed_cycles'] = autoCloseOverdueCycles($conn, $GRACE_DAYS_AFTER_MONTH_END);
        
        // 2. Create new cycles for the current month if needed
        $results['new_cycles_created'] = createNewMonthCycles($conn);
        
        // 3. Clean up old cycles (optional, uncomment if needed)
        // cleanupOldCycleData($conn, $CLEANUP_MONTHS_TO_KEEP);
        
        echo "Cycle maintenance completed successfully\n";
        echo "- Auto-closed cycles: {$results['auto_closed_cycles']}\n";
        echo "- New cycles created: {$results['new_cycles_created']}\n";
        
    } catch (Exception $e) {
        $results['errors'][] = $e->getMessage();
        echo "Error during cycle maintenance: " . $e->getMessage() . "\n";
    }
    
    return $results;
}

/**
 * Auto-close cycles that are past their grace period
 */
function autoCloseOverdueCycles($conn, $graceDays = 3) {
    $cutoffDate = date('Y-m-d', strtotime("-{$graceDays} days"));
    $closedCount = 0;
    
    // Find cycles that need to be closed
    $stmt = $conn->prepare("
        SELECT id, family_id, cycle_month, end_date 
        FROM monthly_cycles 
        WHERE is_current = TRUE 
        AND is_closed = FALSE 
        AND end_date <= ?
    ");
    $stmt->bind_param("s", $cutoffDate);
    $stmt->execute();
    $cycles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($cycles as $cycle) {
        try {
            // Auto-close with system user (ID 1)
            if (closeMonthlyCycle($conn, $cycle['id'], 1)) {
                $closedCount++;
                echo "Auto-closed cycle {$cycle['id']} for family {$cycle['family_id']} (month: {$cycle['cycle_month']})\n";
                
                // Log the auto-close
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs (family_id, action_type, description, created_at)
                    VALUES (?, 'cycle_auto_closed', ?, NOW())
                ");
                $description = "Cycle automatically closed after grace period: " . $cycle['end_date'];
                $stmt->bind_param("is", $cycle['family_id'], $description);
                $stmt->execute();
            }
        } catch (Exception $e) {
            echo "Failed to auto-close cycle {$cycle['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    return $closedCount;
}

/**
 * Create new cycles for families that need them for the current month
 */
function createNewMonthCycles($conn) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    $currentMonthStr = date('Y-m');
    $currentDay = date('j');
    $createdCount = 0;
    
    // Only create new cycles in the first week of the month
    if ($currentDay > 7) {
        echo "Not creating new cycles - too late in the month (day {$currentDay})\n";
        return 0;
    }
    
    // Find families that need a new cycle for the current month
    $stmt = $conn->prepare("
        SELECT fg.id as family_id, fg.family_name,
               MAX(mc.cycle_month) as latest_cycle_month,
               MAX(mc.is_closed) as latest_cycle_closed
        FROM family_groups fg
        LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id
        WHERE fg.id NOT IN (
            SELECT DISTINCT family_id 
            FROM monthly_cycles 
            WHERE cycle_month = ?
        )
        GROUP BY fg.id, fg.family_name
        HAVING latest_cycle_month IS NULL OR latest_cycle_closed = 1
    ");
    $stmt->bind_param("s", $currentMonthStr);
    $stmt->execute();
    $families = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($families as $family) {
        try {
            if (startNewCycle($conn, $family['family_id'], $currentYear, $currentMonth)) {
                $createdCount++;
                echo "Created new cycle for family {$family['family_id']} ({$family['family_name']}) for {$currentMonthStr}\n";
            }
        } catch (Exception $e) {
            echo "Failed to create cycle for family {$family['family_id']}: " . $e->getMessage() . "\n";
        }
    }
    
    return $createdCount;
}

/**
 * Clean up old cycle data (optional)
 */
function cleanupOldCycleData($conn, $monthsToKeep = 12) {
    $cutoffDate = date('Y-m-d', strtotime("-{$monthsToKeep} months"));
    
    // This would delete old cycles and their related data
    // Uncomment and customize as needed
    /*
    $stmt = $conn->prepare("
        DELETE FROM member_monthly_performance 
        WHERE cycle_id IN (
            SELECT id FROM monthly_cycles 
            WHERE created_at < ? AND is_closed = TRUE
        )
    ");
    $stmt->bind_param("s", $cutoffDate);
    $stmt->execute();
    
    $stmt = $conn->prepare("
        DELETE FROM monthly_cycles 
        WHERE created_at < ? AND is_closed = TRUE
    ");
    $stmt->bind_param("s", $cutoffDate);
    $stmt->execute();
    */
    
    echo "Cycle cleanup would remove data older than {$cutoffDate}\n";
}

// Run the maintenance if this file is executed directly
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'maintenance')) {
    $results = runComprehensiveCycleMaintenance($conn);
    
    // If running via web, output JSON for AJAX calls
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => empty($results['errors']),
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} else {
    echo "Cycle maintenance script loaded. Call runComprehensiveCycleMaintenance() to execute.\n";
}
?>
