<?php
// Enhanced login.php - Updated to match registration system
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Debug mode - set to false in production
$debug_mode = true;

// Debug logging function
function debugLog($message, $data = null) {
    global $debug_mode;
    if ($debug_mode) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] LOGIN DEBUG: $message";
        if ($data !== null) {
            $log_message .= " | Data: " . json_encode($data);
        }
        error_log($log_message);
    }
}

// Function to redirect with message
function redirectWithMessage($location, $status, $message) {
    $encodedMessage = urlencode($message);
    header("Location: {$location}?status={$status}&message={$encodedMessage}");
    exit();
}

debugLog("Login script started");
debugLog("Request method: " . $_SERVER['REQUEST_METHOD']);

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    debugLog("Invalid request method");
    redirectWithMessage("../templates/login", "error", "Invalid request method.");
}

// Include database connection
$connection_file = '../config/connection.php';
if (!file_exists($connection_file)) {
    debugLog("Connection file not found: $connection_file");
    redirectWithMessage("../templates/login", "error", "Database configuration error.");
}

include $connection_file;

// Check database connection
if (!isset($conn) || !$conn) {
    debugLog("Database connection failed");
    redirectWithMessage("../templates/login", "error", "Database connection error.");
}

if ($conn->connect_error) {
    debugLog("Database connection error: " . $conn->connect_error);
    redirectWithMessage("../templates/login", "error", "Database connection failed.");
}

debugLog("Database connection successful");

try {
    // Get and sanitize form data
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $rememberMe = isset($_POST["rememberMe"]);

    debugLog("Login attempt", [
        'username_length' => strlen($username),
        'password_length' => strlen($password),
        'remember_me' => $rememberMe,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    // Basic validation
    if (empty($username) || empty($password)) {
        debugLog("Empty credentials provided");
        redirectWithMessage("../templates/login", "error", "Username and password are required!");
    }

    // Check if input is email or username
    $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
    debugLog("Input type detected", ['is_email' => $isEmail]);

    // Prepare query based on input type
    if ($isEmail) {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, username, email, password_hash, user_type, is_active 
            FROM users 
            WHERE email = ? AND is_active = 1
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, username, email, password_hash, user_type, is_active 
            FROM users 
            WHERE username = ? AND is_active = 1
        ");
    }

    if (!$stmt) {
        debugLog("Statement preparation failed: " . $conn->error);
        redirectWithMessage("../templates/login", "error", "Database error occurred.");
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        debugLog("User not found: $username");
        $stmt->close();
        $conn->close();
        redirectWithMessage("../templates/login", "error", "Invalid username or password!");
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    debugLog("User found", [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'user_type' => $user['user_type']
    ]);

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        debugLog("Password verification failed for user: " . $user['username']);
        
        // Log failed login attempt
        $logStmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent, created_at) 
            VALUES (?, 'login_failed', 'Failed login attempt - invalid password', ?, ?, NOW())
        ");
        if ($logStmt) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $logStmt->bind_param("iss", $user['id'], $ipAddress, $userAgent);
            $logStmt->execute();
            $logStmt->close();
        }
        
        $conn->close();
        redirectWithMessage("../templates/login", "error", "Invalid username or password!");
    }

    debugLog("Password verified successfully");

    // Begin transaction for login process
    $conn->begin_transaction();

    try {
        // Set basic session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['last_activity'] = time();
        $_SESSION['session_expiry'] = 30 * 60; // 30 minutes
        $_SESSION['login_time'] = time();

        debugLog("Basic session variables set");

        // Handle "Remember Me" functionality
        if ($rememberMe) {
            $rememberToken = bin2hex(random_bytes(32));
            $hashedToken = password_hash($rememberToken, PASSWORD_DEFAULT);
            $tokenExpiry = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days

            // Set cookie
            setcookie(
                "remember_token", 
                $rememberToken, 
                time() + (7 * 24 * 60 * 60), // 7 days
                "/", 
                "", 
                false, // Set to true if using HTTPS
                true   // HTTP only
            );

            // Update database
            $updateTokenStmt = $conn->prepare("
                UPDATE users 
                SET remember_token = ?, remember_token_expiry = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            if (!$updateTokenStmt) {
                throw new Exception("Failed to prepare remember token update: " . $conn->error);
            }

            $updateTokenStmt->bind_param("ssi", $hashedToken, $tokenExpiry, $user['id']);
            
            if (!$updateTokenStmt->execute()) {
                throw new Exception("Failed to update remember token: " . $updateTokenStmt->error);
            }
            
            $updateTokenStmt->close();
            debugLog("Remember token set");
        }

        // Update last login timestamp
        $updateLoginStmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        if (!$updateLoginStmt) {
            throw new Exception("Failed to prepare login update: " . $conn->error);
        }

        $updateLoginStmt->bind_param("i", $user['id']);
        if (!$updateLoginStmt->execute()) {
            throw new Exception("Failed to update last login: " . $updateLoginStmt->error);
        }
        $updateLoginStmt->close();

        // Get family information if user is family type or belongs to a family
        if ($user['user_type'] === 'family') {
            // For family accounts, get their own family group
            $familyStmt = $conn->prepare("
                SELECT fg.id as family_id, fg.family_name, fg.family_code, fm.role 
                FROM family_groups fg 
                JOIN family_members fm ON fg.id = fm.family_id 
                WHERE fg.created_by = ? AND fm.user_id = ? AND fm.is_active = 1
                LIMIT 1
            ");
            
            if ($familyStmt) {
                $familyStmt->bind_param("ii", $user['id'], $user['id']);
                $familyStmt->execute();
                $familyResult = $familyStmt->get_result();
                
                if ($familyResult->num_rows > 0) {
                    $family = $familyResult->fetch_assoc();
                    $_SESSION['family_id'] = $family['family_id'];
                    $_SESSION['family_role'] = $family['role'];
                    $_SESSION['family_name'] = $family['family_name'];
                    $_SESSION['family_code'] = $family['family_code'];
                    
                    debugLog("Family session set", [
                        'family_id' => $family['family_id'],
                        'family_role' => $family['role']
                    ]);
                }
                $familyStmt->close();
            }
        } else {
            // For personal accounts, check if they're members of any family
            $familyMemberStmt = $conn->prepare("
                SELECT fm.family_id, fm.role, fg.family_name, fg.family_code
                FROM family_members fm 
                JOIN family_groups fg ON fm.family_id = fg.id 
                WHERE fm.user_id = ? AND fm.is_active = 1 
                LIMIT 1
            ");
            
            if ($familyMemberStmt) {
                $familyMemberStmt->bind_param("i", $user['id']);
                $familyMemberStmt->execute();
                $memberResult = $familyMemberStmt->get_result();
                
                if ($memberResult->num_rows > 0) {
                    $family = $memberResult->fetch_assoc();
                    $_SESSION['family_id'] = $family['family_id'];
                    $_SESSION['family_role'] = $family['role'];
                    $_SESSION['family_name'] = $family['family_name'];
                    $_SESSION['family_code'] = $family['family_code'];
                    
                    debugLog("Family membership found for personal user", [
                        'family_id' => $family['family_id'],
                        'family_role' => $family['role']
                    ]);
                }
                $familyMemberStmt->close();
            }
        }

        // Log successful login activity
        $logStmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, family_id, action_type, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, 'login', 'User logged in successfully', ?, ?, NOW())
        ");
        
        if ($logStmt) {
            $familyIdForLog = isset($_SESSION['family_id']) ? $_SESSION['family_id'] : null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            if ($familyIdForLog) {
                $logStmt->bind_param("iiss", $user['id'], $familyIdForLog, $ipAddress, $userAgent);
            } else {
                $logStmt->bind_param("isis", $user['id'], null, $ipAddress, $userAgent);
            }
            
            $logStmt->execute();
            $logStmt->close();
        }

        // Commit transaction
        $conn->commit();
        debugLog("Login transaction committed successfully");

        // Close database connection
        $conn->close();

        // Determine redirect URL based on user type and configuration
        $welcomeMessage = "Welcome back, " . $user['first_name'] . ' ' . $user['last_name'] . "!";
        
        // Updated routing logic based on user type (not hardcoded email)
        if ($user['user_type'] === 'family') {
            debugLog("Redirecting family user to family dashboard");
            redirectWithMessage("../templates/dashboard", "success", $welcomeMessage);
        } else {
            debugLog("Redirecting personal user to personal dashboard");
            redirectWithMessage("../templates/personal-dashboard", "success", $welcomeMessage);
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        debugLog("Login transaction failed: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    debugLog("Login error: " . $e->getMessage());
    
    if (isset($conn)) {
        $conn->close();
    }
    
    redirectWithMessage("../templates/login", "error", "An error occurred during login. Please try again.");
}

debugLog("Login script completed unexpectedly");
?>