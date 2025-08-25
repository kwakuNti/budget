<?php
// Update walkthrough target element for salary step
session_start();

// Include database configuration
require_once 'config/db_config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update the target element for the configure_salary step
    $sql = "UPDATE walkthrough_steps 
            SET target_element = '#salaryActionBtn' 
            WHERE walkthrough_type = 'initial_setup' 
            AND step_name = 'configure_salary'";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Successfully updated walkthrough target element for salary step\n";
        echo "Target element updated to: #salaryActionBtn\n";
        
        // Also check if the record exists
        $checkSql = "SELECT * FROM walkthrough_steps 
                     WHERE walkthrough_type = 'initial_setup' 
                     AND step_name = 'configure_salary'";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "✅ Verified: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ No record found for configure_salary step\n";
        }
    } else {
        echo "❌ Failed to update walkthrough target element\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
