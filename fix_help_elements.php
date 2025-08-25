<?php
// Fix help guide target elements to use elements that actually exist

require_once 'config/config.php';

try {
    // Update dashboard help guide to target an element that exists
    $sql = "UPDATE walkthrough_steps SET 
                target_element = '.dashboard-title, .welcome-section, .card, .main-content' 
            WHERE walkthrough_type = 'help_guide' 
            AND step_name = 'dashboard_overview'";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "✅ Updated dashboard help guide target element\n";
    } else {
        echo "❌ Failed to update dashboard help: " . $conn->error . "\n";
    }
    
    // Update other help guides with better selectors
    $updates = [
        'budget_overview' => '.budget-categories, .budget-section, .main-content',
        'salary_overview' => '.salary-info, .salary-section, .main-content',
        'expenses_overview' => '.expense-form, .expense-section, .main-content'
    ];
    
    foreach ($updates as $step_name => $target_element) {
        $sql = "UPDATE walkthrough_steps SET 
                    target_element = ? 
                WHERE walkthrough_type = 'help_guide' 
                AND step_name = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $target_element, $step_name);
        
        if ($stmt->execute()) {
            echo "✅ Updated $step_name help guide\n";
        } else {
            echo "❌ Failed to update $step_name help: " . $stmt->error . "\n";
        }
    }
    
    echo "\n🎉 Help guide elements updated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
