<?php
require_once '../config/config.php';

try {
    // Remove the setup_complete step that's causing the redirect
    $stmt = $pdo->prepare("DELETE FROM walkthrough_steps WHERE step_name = 'setup_complete' AND walkthrough_type = 'initial_setup'");
    $stmt->execute();
    
    echo "✅ Removed setup_complete step - walkthrough will now end after budget step\n";
    
    // Also clean up any user progress that might be stuck on this step
    $stmt = $pdo->prepare("UPDATE user_walkthrough_progress SET current_step = 'setup_budget' WHERE current_step = 'setup_complete' AND walkthrough_type = 'initial_setup'");
    $stmt->execute();
    
    echo "✅ Fixed any users stuck on setup_complete step\n";
    echo "🎉 Walkthrough completion fix applied!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
