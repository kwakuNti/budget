<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_period_data':
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            echo json_encode(getPeriodBudgetData($conn, $user_id, $month, $year));
            break;
            
        case 'copy_budget':
            $from_month = $_POST['from_month'] ?? '';
            $from_year = $_POST['from_year'] ?? '';
            $to_month = $_POST['to_month'] ?? '';
            $to_year = $_POST['to_year'] ?? '';
            echo json_encode(copyBudgetPeriod($conn, $user_id, $from_month, $from_year, $to_month, $to_year));
            break;
            
        case 'get_available_periods':
            echo json_encode(getAvailablePeriods($conn, $user_id));
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getPeriodBudgetData($conn, $user_id, $month, $year) {
    // For now, return empty data since this is period-specific functionality
    // that we haven't fully implemented yet
    return [
        'success' => true,
        'data' => [
            'categories' => [],
            'allocation' => null,
            'expenses' => [],
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ]
        ]
    ];
}

function copyBudgetPeriod($conn, $user_id, $from_month, $from_year, $to_month, $to_year) {
    // Not implemented yet - return success for now
    return [
        'success' => true,
        'message' => 'Budget copy functionality not yet implemented'
    ];
}

function getAvailablePeriods($conn, $user_id) {
    // Return current period for now
    $current_month = date('m');
    $current_year = date('Y');
    
    return [
        'success' => true,
        'data' => [
            [
                'month' => $current_month,
                'year' => $current_year,
                'month_name' => date('F'),
                'is_current' => true,
                'has_data' => true
            ]
        ]
    ];
}
?>
