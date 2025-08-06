<?php
// cron/cycle_maintenance.php - Cron job for automatic cycle maintenance
#!/usr/bin/env php

// This file should be called by cron job daily
// Add to crontab: 0 2 * * * /usr/bin/php /path/to/your/project/cron/cycle_maintenance.php

require_once dirname(__DIR__) . '../config/connection.php';
require_once dirname(__DIR__) . '../ajax/cycle_maintenance.php';

// Set up error reporting for cron
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/cycle_maintenance.log');

echo "[" . date('Y-m-d H:i:s') . "] Starting cycle maintenance...\n";

try {
    $result = runCycleMaintenance($conn);
    
    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Maintenance completed successfully\n";
        echo "  - Cycles closed: " . $result['cycles_closed'] . "\n";
        echo "  - Cycles created: " . $result['cycles_created'] . "\n";
        
        // Log to database
        $log_stmt = $conn->prepare("
            INSERT INTO activity_logs (action_type, description, created_at)
            VALUES ('system_maintenance', ?, NOW())
        ");
        $description = "Daily cycle maintenance: {$result['cycles_closed']} closed, {$result['cycles_created']} created";
        $log_stmt->bind_param("s", $description);
        $log_stmt->execute();
        
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Maintenance failed: " . $result['error'] . "\n";
        
        // Log error to database
        $error_stmt = $conn->prepare("
            INSERT INTO activity_logs (action_type, description, created_at)
            VALUES ('system_error', ?, NOW())
        ");
        $error_description = "Cycle maintenance failed: " . $result['error'];
        $error_stmt->bind_param("s", $error_description);
        $error_stmt->execute();
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    
    // Log fatal error
    $fatal_stmt = $conn->prepare("
        INSERT INTO activity_logs (action_type, description, created_at)
        VALUES ('system_fatal_error', ?, NOW())
    ");
    $fatal_description = "Cycle maintenance fatal error: " . $e->getMessage();
    $fatal_stmt->bind_param("s", $fatal_description);
    $fatal_stmt->execute();
}

echo "[" . date('Y-m-d H:i:s') . "] Cycle maintenance completed.\n";
?>