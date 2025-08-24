<?php
// Test API exactly as browser would call it
session_start();
$_SESSION['user_id'] = 2;

// Test comprehensive_insights action
$_GET['action'] = 'comprehensive_insights';

// Just include the API directly without any buffering
include 'api/enhanced_insights_data.php';
?>
