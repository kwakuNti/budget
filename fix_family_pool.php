<?php
/**
 * Family Pool Fix Script
 * Corrects the total_pool value in the database
 */

require_once 'config/connection.php';

echo "=== FAMILY POOL FIX SCRIPT ===\n";
echo "This will correct the total_pool values for all families\n\n";

// Get all families
$stmt = $conn->prepare("SELECT id, family_name, total_pool FROM family_groups");
$stmt->execute();
$families = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($families as $family) {
    echo "Processing family: {$family['family_name']} (ID: {$family['id']})\n";
    
    // Calculate correct total contributions
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(fc.amount), 0) as total_contributions
        FROM family_contributions fc
        WHERE fc.family_id = ?
    ");
    $stmt->bind_param("i", $family['id']);
    $stmt->execute();
    $totalContributions = floatval($stmt->get_result()->fetch_assoc()['total_contributions']);
    
    // Calculate correct total expenses
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(fe.amount), 0) as total_expenses
        FROM family_expenses fe
        WHERE fe.family_id = ?
    ");
    $stmt->bind_param("i", $family['id']);
    $stmt->execute();
    $totalExpenses = floatval($stmt->get_result()->fetch_assoc()['total_expenses']);
    
    $correctPool = $totalContributions - $totalExpenses;
    
    echo "  Current stored: ₵{$family['total_pool']}\n";
    echo "  Should be: ₵{$correctPool}\n";
    
    if ($family['total_pool'] != $correctPool) {
        echo "  ⚠️  FIXING: Updating from ₵{$family['total_pool']} to ₵{$correctPool}\n";
        
        // Update the database
        $stmt = $conn->prepare("UPDATE family_groups SET total_pool = ? WHERE id = ?");
        $stmt->bind_param("di", $correctPool, $family['id']);
        
        if ($stmt->execute()) {
            echo "  ✅ SUCCESS: Database updated\n";
            
            // Log this action
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (family_id, action_type, description, created_at)
                VALUES (?, 'pool_correction', ?, NOW())
            ");
            $description = "Pool corrected from ₵{$family['total_pool']} to ₵{$correctPool} (sync fix)";
            $stmt->bind_param("is", $family['id'], $description);
            $stmt->execute();
            
        } else {
            echo "  ❌ ERROR: Failed to update database\n";
        }
    } else {
        echo "  ✅ OK: Pool is already correct\n";
    }
    echo "\n";
}

echo "=== FIX COMPLETE ===\n";
echo "The dashboard should now show correct pool values\n";
?>
