<?php
// Update walkthrough for budget step
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update the budget step to target the Use Template button and make it skippable
    $query = "UPDATE walkthrough_steps 
              SET target_element = 'button[onclick=\"showBudgetTemplateModal()\"]',
                  title = 'Set Up Your Budget',
                  content = 'Perfect! Now let\\'s set up your budget. You can click \"Use Template\" to choose from our popular templates, or you can skip this step to create a custom budget later.',
                  action_required = FALSE,
                  can_skip = TRUE
              WHERE walkthrough_type = 'initial_setup' 
                AND step_name = 'setup_budget'";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute()) {
        echo "✅ Budget step updated successfully!\n";
        
        // Check if the completion step exists
        $checkQuery = "SELECT COUNT(*) FROM walkthrough_steps 
                      WHERE walkthrough_type = 'initial_setup' 
                        AND step_name = 'setup_complete'";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        $exists = $checkStmt->fetchColumn();
        
        if (!$exists) {
            // Add the completion step
            $insertQuery = "INSERT INTO walkthrough_steps 
                           (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip) 
                           VALUES 
                           ('initial_setup', 'setup_complete', 4, 'templates/budget.php', '.welcome-section', 'Setup Complete!', 'Congratulations! You\\'ve completed the initial setup. You\\'re now ready to manage your budget, track expenses, and work towards your financial goals!', FALSE, TRUE)";
            
            $insertStmt = $db->prepare($insertQuery);
            if ($insertStmt->execute()) {
                echo "✅ Completion step added successfully!\n";
            } else {
                echo "❌ Failed to add completion step\n";
            }
        } else {
            echo "ℹ️ Completion step already exists\n";
        }
        
        // Show current walkthrough steps
        $listQuery = "SELECT step_name, step_order, title, can_skip FROM walkthrough_steps 
                     WHERE walkthrough_type = 'initial_setup' 
                     ORDER BY step_order";
        $listStmt = $db->prepare($listQuery);
        $listStmt->execute();
        $steps = $listStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Current walkthrough steps:\n";
        foreach ($steps as $step) {
            $skipText = $step['can_skip'] ? '(Skippable)' : '(Required)';
            echo "  {$step['step_order']}. {$step['step_name']}: {$step['title']} {$skipText}\n";
        }
        
    } else {
        echo "❌ Failed to update budget step\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
