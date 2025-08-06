<?php
session_start();
include '../../includes/dashboard.php';
include '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in and has family access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    $period = $_GET['period'] ?? '6m';
    
    // Validate period
    $allowedPeriods = ['6m', '1y', 'all'];
    if (!in_array($period, $allowedPeriods)) {
        $period = '6m';
    }
    
    $dashboardData = new DashboardData($conn, $_SESSION['family_id']);
    $chartData = $dashboardData->getContributionTrends($period);
    
    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);
    
} catch (Exception $e) {
    error_log("Chart API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading chart data'
    ]);
}
?>