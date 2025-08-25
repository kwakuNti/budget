<?php
require_once 'config/connection.php';

try {
    // Read the SQL file
    $sql = file_get_contents('db/updatedb.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read updatedb.sql file');
    }
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)), 
        function($stmt) { 
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt); 
        }
    );
    
    echo "Executing " . count($statements) . " SQL statements...\n";
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            try {
                $result = $conn->query($statement . ';');
                if ($result === false) {
                    echo "\nError executing statement: " . substr($statement, 0, 100) . "...\n";
                    echo "Error: " . $conn->error . "\n";
                } else {
                    $executed++;
                    echo ".";
                }
            } catch (Exception $e) {
                echo "\nError executing statement: " . substr($statement, 0, 100) . "...\n";
                echo "Error: " . $e->getMessage() . "\n";
                // Continue with other statements
            }
        }
    }
    
    echo "\nExecuted $executed statements successfully.\n";
    echo "Database update completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
