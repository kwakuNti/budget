<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test the enhanced insights API
$_SESSION['user_id'] = 1; // Mock user ID

// Mock the action parameter
$_GET['action'] = 'comprehensive_insights';

// Capture output
ob_start();
include 'api/enhanced_insights_data.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
echo "\n\nLength: " . strlen($output);
echo "\nFirst 100 chars: " . substr($output, 0, 100);
?>
