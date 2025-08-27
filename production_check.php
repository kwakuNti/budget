<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Production Email Verification System Check</h1>";
echo "<p><em>Testing all components for production readiness...</em></p>";

$checks = [
    'email_config' => false,
    'verify_endpoint' => false,
    'verify_page' => false,
    'resend_endpoint' => false,
    'register_action' => false,
    'login_action' => false,
    'database_schema' => false
];

// 1. Check Email Configuration
echo "<h2>1. Email Configuration Check</h2>";
try {
    if (file_exists(__DIR__ . '/config/email_config.php')) {
        require_once __DIR__ . '/config/email_config.php';
        if (class_exists('EmailService')) {
            $emailService = new EmailService();
            echo "<p style='color: green;'>✅ EmailService class loaded successfully</p>";
            $checks['email_config'] = true;
        } else {
            echo "<p style='color: red;'>❌ EmailService class not found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Email configuration file missing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email config error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. Check API Endpoints
echo "<h2>2. API Endpoints Check</h2>";

$endpoints = [
    'verify_email' => '/api/verify_email.php',
    'resend_verification' => '/api/resend_verification.php'
];

foreach ($endpoints as $name => $path) {
    $fullPath = __DIR__ . $path;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ $name endpoint exists: $path</p>";
        $checks[str_replace('_', '_', $name) . '_endpoint'] = true;
    } else {
        echo "<p style='color: red;'>❌ $name endpoint missing: $path</p>";
    }
}

// 3. Check Templates/Pages
echo "<h2>3. Template Pages Check</h2>";

$templates = [
    'verify-email' => '/templates/verify-email.php'
];

foreach ($templates as $name => $path) {
    $fullPath = __DIR__ . $path;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ $name page exists: $path</p>";
        $checks['verify_page'] = true;
    } else {
        echo "<p style='color: red;'>❌ $name page missing: $path</p>";
    }
}

// 4. Check Action Files
echo "<h2>4. Action Files Check</h2>";

$actions = [
    'register' => '/actions/register.php',
    'login' => '/actions/login.php'
];

foreach ($actions as $name => $path) {
    $fullPath = __DIR__ . $path;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ $name action exists: $path</p>";
        // Quick content check for email verification integration
        $content = file_get_contents($fullPath);
        if (strpos($content, 'email_verified') !== false || strpos($content, 'verification_token') !== false) {
            echo "<p style='color: green;'>  ↳ Email verification integration detected</p>";
            $checks[$name . '_action'] = true;
        } else {
            echo "<p style='color: orange;'>  ↳ Email verification integration may be missing</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $name action missing: $path</p>";
    }
}

// 5. Database Schema Check
echo "<h2>5. Database Schema Check</h2>";
try {
    $conn = new mysqli('localhost', 'root', 'root', 'budget');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $result = $conn->query("DESCRIBE users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['email_verified', 'verification_token', 'token_expires_at'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        $checks['database_schema'] = true;
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 6. Dependencies Check
echo "<h2>6. Dependencies Check</h2>";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ Composer vendor directory exists</p>";
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color: green;'>✅ PHPMailer available</p>";
    } else {
        echo "<p style='color: red;'>❌ PHPMailer not found</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Composer vendor directory missing</p>";
}

// 7. Configuration Values Check
echo "<h2>7. Configuration Values Check</h2>";

if ($checks['email_config']) {
    try {
        $emailService = new EmailService();
        $reflection = new ReflectionClass($emailService);
        echo "<p style='color: green;'>✅ EmailService instantiable</p>";
        
        // Test base URL generation
        $method = $reflection->getMethod('getBaseUrl');
        $method->setAccessible(true);
        $baseUrl = $method->invoke($emailService);
        echo "<p style='color: green;'>✅ Base URL: " . htmlspecialchars($baseUrl) . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ EmailService test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Summary
echo "<h2>Production Readiness Summary</h2>";

$totalChecks = count($checks);
$passedChecks = array_sum($checks);
$percentage = round(($passedChecks / $totalChecks) * 100);

echo "<div style='padding: 15px; margin: 10px 0; border: 2px solid " . ($percentage >= 80 ? 'green' : ($percentage >= 60 ? 'orange' : 'red')) . ";'>";
echo "<h3>Overall Status: $passedChecks/$totalChecks ($percentage%)</h3>";

if ($percentage >= 80) {
    echo "<p style='color: green; font-weight: bold;'>🎉 PRODUCTION READY!</p>";
    echo "<p>Your email verification system is ready for production deployment.</p>";
} elseif ($percentage >= 60) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ MOSTLY READY</p>";
    echo "<p>Most components are working, but some issues need attention.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ NOT READY</p>";
    echo "<p>Several critical issues need to be resolved before production.</p>";
}

echo "</div>";

// Detailed status
echo "<h3>Detailed Component Status:</h3>";
echo "<ul>";
foreach ($checks as $component => $status) {
    $icon = $status ? "✅" : "❌";
    $color = $status ? "green" : "red";
    echo "<li style='color: $color;'>$icon " . ucwords(str_replace('_', ' ', $component)) . "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h2>Next Steps for Production:</h2>";
echo "<ol>";
echo "<li><strong>Test Registration Flow:</strong> Register a new user and verify email delivery</li>";
echo "<li><strong>Test Verification Process:</strong> Click verification links and confirm database updates</li>";
echo "<li><strong>Test Login Protection:</strong> Ensure unverified users cannot log in</li>";
echo "<li><strong>Domain Configuration:</strong> Update DNS records for budgetly.online if needed</li>";
echo "<li><strong>SSL Certificate:</strong> Ensure HTTPS is properly configured</li>";
echo "<li><strong>Error Handling:</strong> Test edge cases (expired tokens, invalid tokens, etc.)</li>";
echo "</ol>";

echo "<p><em>Production check completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
