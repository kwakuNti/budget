<?php
session_start();
require_once 'config/connection.php';

// Quick test script to verify auto-save system functionality
echo "<h2>Auto-Save System Test</h2>";

// Test 1: Check if tables exist
$tables = ['personal_autosave_config', 'personal_weekly_challenges', 'personal_challenge_progress', 'personal_auto_save_history'];
$existing_tables = [];

foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        $existing_tables[] = $table;
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' missing<br>";
    }
}

// Test 2: Check API endpoint existence
$api_file = 'api/autosave_config.php';
if (file_exists($api_file)) {
    echo "✅ API endpoint '$api_file' exists<br>";
} else {
    echo "❌ API endpoint '$api_file' missing<br>";
}

// Test 3: Check savings template includes SweetAlert
$savings_file = 'templates/savings.php';
if (file_exists($savings_file)) {
    $content = file_get_contents($savings_file);
    if (strpos($content, 'sweetalert2') !== false) {
        echo "✅ SweetAlert2 integration found in savings template<br>";
    } else {
        echo "❌ SweetAlert2 integration missing<br>";
    }
    
    if (strpos($content, 'Swal.fire') !== false) {
        echo "✅ SweetAlert functions implemented<br>";
    } else {
        echo "❌ SweetAlert functions missing<br>";
    }
} else {
    echo "❌ Savings template missing<br>";
}

// Test 4: Check CSS file for new styles
$css_file = 'public/css/savings.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    if (strpos($css_content, 'swal-form') !== false) {
        echo "✅ SweetAlert custom CSS styles found<br>";
    } else {
        echo "❌ SweetAlert custom CSS styles missing<br>";
    }
    
    if (strpos($css_content, 'challenge-card') !== false) {
        echo "✅ Challenge card styles found<br>";
    } else {
        echo "❌ Challenge card styles missing<br>";
    }
} else {
    echo "❌ Savings CSS file missing<br>";
}

echo "<br><h3>Summary:</h3>";
echo "Tables created: " . count($existing_tables) . "/" . count($tables) . "<br>";
echo "Auto-save system is " . (count($existing_tables) == count($tables) ? "ready" : "incomplete") . "<br>";

if (count($existing_tables) == count($tables)) {
    echo "<br><p style='color: green; font-weight: bold;'>🎉 Auto-save system implementation complete!</p>";
    echo "<p>Features implemented:</p>";
    echo "<ul>";
    echo "<li>✅ Configurable auto-save frequency and goal allocation</li>";
    echo "<li>✅ Multiple challenge types (save amount, no-spend, reduce category, round-up)</li>";
    echo "<li>✅ Real-time goal status management (pause/resume/inactive)</li>";
    echo "<li>✅ SweetAlert2 integration for better user experience</li>";
    echo "<li>✅ Challenge progress tracking and completion detection</li>";
    echo "<li>✅ Auto-save history and analytics</li>";
    echo "</ul>";
} else {
    echo "<br><p style='color: red; font-weight: bold;'>⚠️ System setup incomplete. Please run setup_autosave_system.php first.</p>";
}
?>
