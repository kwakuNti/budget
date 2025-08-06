<?php
/**
 * Check database for confirmation/received status fields
 */

$conn = new mysqli('localhost', 'root', 'root', 'budget');

echo "=== CHECKING FOR CONFIRMATION/RECEIVED STATUS FIELDS ===" . PHP_EOL . PHP_EOL;

echo "SALARIES TABLE:" . PHP_EOL;
$result = $conn->query("DESCRIBE salaries");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']}" . PHP_EOL;
}

echo PHP_EOL . "PERSONAL_INCOME_SOURCES TABLE:" . PHP_EOL;
$result = $conn->query("DESCRIBE personal_income_sources");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']}" . PHP_EOL;
}

echo PHP_EOL . "CHECKING FOR EXISTING CONFIRMATION DATA:" . PHP_EOL;

// Check if there are any salary confirmation related tables
$result = $conn->query("SHOW TABLES LIKE '%salary%'");
echo "Salary-related tables:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- " . current($row) . PHP_EOL;
}

echo PHP_EOL;

// Check if there are any income confirmation related tables
$result = $conn->query("SHOW TABLES LIKE '%income%'");
echo "Income-related tables:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- " . current($row) . PHP_EOL;
}

echo PHP_EOL;

// Check if there are any confirmation/payment tables
$result = $conn->query("SHOW TABLES LIKE '%confirm%'");
echo "Confirmation-related tables:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- " . current($row) . PHP_EOL;
}

$result = $conn->query("SHOW TABLES LIKE '%payment%'");
echo "Payment-related tables:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- " . current($row) . PHP_EOL;
}

$conn->close();
?>
