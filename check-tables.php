<?php
/**
 * Check database structure
 */

$conn = new mysqli('localhost', 'root', 'root', 'budget');

echo "=== CHECKING DATABASE STRUCTURE ===" . PHP_EOL . PHP_EOL;

// Check personal_income_sources table structure
$result = $conn->query("DESCRIBE personal_income_sources");
echo "personal_income_sources table structure:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']}" . PHP_EOL;
}

echo PHP_EOL;

// Check salaries table structure
$result = $conn->query("DESCRIBE salaries");
echo "salaries table structure:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']}" . PHP_EOL;
}

echo PHP_EOL;

// Check personal_expenses table structure
$result = $conn->query("DESCRIBE personal_expenses");
echo "personal_expenses table structure:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']}" . PHP_EOL;
}

$conn->close();
?>
