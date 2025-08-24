<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing ai_predictions action...\n";

session_start();
$_SESSION['user_id'] = 2;
$_GET['action'] = 'ai_predictions';

echo "Before including API...\n";

try {
    include 'api/enhanced_insights_data.php';
    echo "\nAfter including API...\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
