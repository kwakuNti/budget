<?php
// Test database structure to verify table columns
require_once 'config/connection.php';

echo "Testing database structure...\n\n";

// Check if personal_budget_allocation table exists and its structure
$query = "DESCRIBE personal_budget_allocation";
$result = $conn->query($query);

if ($result) {
    echo "personal_budget_allocation table structure:\n";
    echo "Column Name | Type | Null | Key | Default | Extra\n";
    echo "--------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-15s | %-20s | %-4s | %-3s | %-10s | %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
    echo "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Check if budget_categories table exists and its structure
$query = "DESCRIBE budget_categories";
$result = $conn->query($query);

if ($result) {
    echo "budget_categories table structure:\n";
    echo "Column Name | Type | Null | Key | Default | Extra\n";
    echo "--------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-15s | %-20s | %-4s | %-3s | %-10s | %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
    echo "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test a simple query to see what data exists
$query = "SELECT COUNT(*) as count FROM personal_budget_allocation";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total records in personal_budget_allocation: " . $row['count'] . "\n\n";
} else {
    echo "Error querying personal_budget_allocation: " . $conn->error . "\n";
}

// Check existing data in personal_budget_allocation
$query = "SELECT * FROM personal_budget_allocation LIMIT 1";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "Sample record from personal_budget_allocation:\n";
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "No records found in personal_budget_allocation table.\n";
}
?>
