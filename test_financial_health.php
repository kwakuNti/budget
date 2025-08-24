<?php
session_start();
$_SESSION['user_id'] = 2;
$_GET['action'] = 'financial_health';

try {
    include 'api/enhanced_insights_data.php';
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
