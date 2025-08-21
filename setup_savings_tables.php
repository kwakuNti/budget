<?php
require_once __DIR__ . '/config/connection.php';

try {
    // Read and execute the consolidated updatedb.sql file
    $sqlFile = __DIR__ . '/db/updatedb.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("updatedb.sql file not found at: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Could not read updatedb.sql file");
    }
    
    // Remove USE statement if it exists (we're already connected to the right database)
    $sql = preg_replace('/USE\s+[^;]+;/', '', $sql);
    
    // Execute the SQL
    if ($conn->multi_query($sql)) {
        do {
            // Store the result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "✅ Personal budget database update completed successfully!\n";
        echo "✅ All tables, enhancements, and features have been configured!\n";
        
    } else {
        throw new Exception("Error executing updatedb.sql: " . $conn->error);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
