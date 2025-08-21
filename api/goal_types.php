<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

require_once '../config/connection.php';

// Clean any output buffer before sending headers
if (ob_get_length()) {
    $debug_output = ob_get_contents();
    ob_clean();
    error_log("Unexpected output in goal_types.php: " . $debug_output);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Log that we're starting the process
    error_log("Goal types API: Starting to fetch goal types");
    
    // Check if connection exists
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }
    
    // Get the ENUM values for goal_type from the database schema
    $query = "SHOW COLUMNS FROM personal_goals LIKE 'goal_type'";
    error_log("Goal types API: Executing query: " . $query);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    error_log("Goal types API: Query result: " . json_encode($row));
    
    if ($row && $row['Type']) {
        // Parse ENUM values from the Type column
        // Format: enum('value1','value2','value3')
        preg_match_all("/'([^']+)'/", $row['Type'], $matches);
        $goalTypes = $matches[1];
        
        error_log("Goal types API: Parsed goal types: " . json_encode($goalTypes));
        
        // Define icons for each goal type
        $goalTypeIcons = [
            'emergency_fund' => '🚨',
            'vacation' => '🏖️',
            'car' => '🚗',
            'house' => '🏠',
            'education' => '🎓',
            'retirement' => '🏖️',
            'investment' => '📈',
            'debt_payoff' => '💳',
            'business' => '💼',
            'technology' => '💻',
            'health' => '🏥',
            'entertainment' => '🎬',
            'shopping' => '🛍️',
            'travel' => '✈️',
            'wedding' => '💒',
            'other' => '🎯'
        ];
        
        // Format the response with icons and display names
        $formattedGoalTypes = [];
        foreach ($goalTypes as $type) {
            $icon = isset($goalTypeIcons[$type]) ? $goalTypeIcons[$type] : '🎯';
            $displayName = ucwords(str_replace('_', ' ', $type));
            
            $formattedGoalTypes[] = [
                'value' => $type,
                'display' => "$icon $displayName",
                'icon' => $icon,
                'name' => $displayName
            ];
        }
        
        $response = [
            'success' => true,
            'goal_types' => $formattedGoalTypes
        ];
        
        error_log("Goal types API: Success response: " . json_encode($response));
        echo json_encode($response);
    } else {
        $error_response = [
            'success' => false,
            'message' => 'Could not retrieve goal types from database',
            'debug' => [
                'result' => $row,
                'query' => $query
            ]
        ];
        error_log("Goal types API: No result error: " . json_encode($error_response));
        echo json_encode($error_response);
    }
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log("Goal types API: Exception: " . json_encode($error_response));
    echo json_encode($error_response);
}
?>
