<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'personal';

echo "Testing personal dashboard API...\n";

ob_start();
include 'api/personal_dashboard_data.php';
$output = ob_get_clean();

echo "Response length: " . strlen($output) . " characters\n";
if (strlen($output) > 0) {
    echo "Response: $output\n";
    $json = json_decode($output, true);
    if ($json === null) {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON parsed successfully!\n";
        if (isset($json['success'])) {
            echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        }
    }
} else {
    echo "Empty response!\n";
}
?>
