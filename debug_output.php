<?php
session_start();
$_SESSION['user_id'] = 2;
$_GET['action'] = 'financial_health';

ob_start();
include 'api/enhanced_insights_data.php';
$output = ob_get_clean();

// Save to file for examination
file_put_contents('api_output.txt', $output);

echo "Output saved to api_output.txt\n";
echo "Length: " . strlen($output) . " bytes\n";
echo "Last 10 chars: " . substr($output, -10) . "\n";

// Check for invisible characters
for ($i = 0; $i < strlen($output); $i++) {
    $char = $output[$i];
    $ord = ord($char);
    if ($ord < 32 || $ord > 126) {
        echo "Non-printable character at position $i: ASCII $ord\n";
        if ($i < 20 || $i > strlen($output) - 20) {
            echo "Near position $i: " . substr($output, max(0, $i-5), 10) . "\n";
        }
    }
}
?>
