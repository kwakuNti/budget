<?php
// Update the walkthrough completion step to target the welcome content specifically
include_once '../includes/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update the final step to target the welcome content instead of quick actions
    $sql = "UPDATE walkthrough_steps 
            SET target_element = '.welcome-content', 
                title = 'Setup Complete!',
                content = 'Congratulations! You have successfully completed the initial setup. Your budget system is now ready to use. You can start tracking your income, expenses, and work towards your financial goals!'
            WHERE walkthrough_type = 'initial_setup' 
            AND step_name = 'setup_complete'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Walkthrough completion step updated successfully',
        'rows_affected' => $stmt->rowCount()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
