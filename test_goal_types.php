<?php
// Test script to verify goal types API
require_once 'config/connection.php';

echo "<h2>Testing Goal Types API</h2>\n";

// Test the API endpoint
$url = 'http://localhost/budget/api/goal_types.php';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "<h3>API Response:</h3>\n";
echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>\n";

// Test the validation function
echo "<h3>Testing Validation Function:</h3>\n";
require_once 'actions/savings_handler.php';

$validTypes = getValidGoalTypes($conn);
echo "<p><strong>Valid Goal Types from Database:</strong></p>\n";
echo "<ul>\n";
foreach ($validTypes as $type) {
    echo "<li>$type</li>\n";
}
echo "</ul>\n";

// Test some validation
$testTypes = ['emergency_fund', 'vacation', 'invalid_type', 'retirement'];
echo "<p><strong>Validation Tests:</strong></p>\n";
echo "<table border='1'>\n";
echo "<tr><th>Input</th><th>Valid?</th><th>Icon</th><th>Color</th></tr>\n";
foreach ($testTypes as $testType) {
    $isValid = in_array($testType, $validTypes) ? 'Yes' : 'No';
    $icon = getGoalTypeIcon($testType);
    $color = getGoalTypeColor($testType);
    echo "<tr><td>$testType</td><td>$isValid</td><td>$icon</td><td style='background-color: $color; color: white;'>$color</td></tr>\n";
}
echo "</table>\n";
?>
