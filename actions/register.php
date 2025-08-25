<?php
// Enhanced register.php with better error handling and debugging
session_start();

// Enable comprehensive error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log all errors to a file
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Set content type for JSON response
header('Content-Type: application/json');

// Debug mode - set to false in production
$debug_mode = true;

// Debug logging function
function debugLog($message, $data = null) {
    global $debug_mode;
    if ($debug_mode) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] REGISTER DEBUG: $message";
        if ($data !== null) {
            $log_message .= " | Data: " . json_encode($data);
        }
        error_log($log_message);
    }
}

// Function to send JSON response
function sendResponse($success, $message, $data = [], $redirect = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    
    debugLog("Sending response", $response);
    echo json_encode($response);
    exit();
}

debugLog("Registration script started");
debugLog("Request method: " . $_SERVER['REQUEST_METHOD']);
debugLog("POST data keys: " . implode(', ', array_keys($_POST)));

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    debugLog("Invalid request method");
    sendResponse(false, "Invalid request method.");
}

// Include database connection
$connection_file = '../config/connection.php';
if (!file_exists($connection_file)) {
    debugLog("Connection file not found: $connection_file");
    sendResponse(false, "Database configuration file not found.");
}

include $connection_file;

// Check if connection exists
if (!isset($conn) || !$conn) {
    debugLog("Database connection failed");
    sendResponse(false, "Database connection error. Please try again later.");
}

// Test database connection
if ($conn->connect_error) {
    debugLog("Database connection error: " . $conn->connect_error);
    sendResponse(false, "Database connection failed: " . $conn->connect_error);
}

debugLog("Database connection successful");

try {
    // Get and sanitize all form data
    $accountType = trim($_POST["accountType"] ?? '');
    $firstName = trim($_POST["firstName"] ?? '');
    $lastName = trim($_POST["lastName"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $phoneNumber = trim($_POST["phoneNumber"] ?? '');
    $dateOfBirth = $_POST["dateOfBirth"] ?? '';
    
    // Family-specific fields (only validate if family account)
    $monthlyContribution = 0;
    $momoNetwork = '';
    $momoNumber = '';
    
    // Personal-specific fields (only validate if personal account)
    $monthlySalary = 0;
    
    // Security fields
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirmPassword = $_POST["confirmPassword"] ?? '';
    $agreeTerms = isset($_POST["agreeTerms"]);

    // Only get account-type specific fields based on the selected type
    if ($accountType === 'family') {
        $monthlyContribution = floatval($_POST["monthlyContribution"] ?? 0);
        $momoNetwork = $_POST["momoNetwork"] ?? '';
        $momoNumber = trim($_POST["momoNumber"] ?? '');
    } else if ($accountType === 'personal') {
        $monthlySalary = floatval($_POST["monthlySalary"] ?? 0);
    }

    debugLog("Form data received", [
        'accountType' => $accountType,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phoneNumber' => $phoneNumber,
        'username' => $username,
        'monthlyContribution' => $monthlyContribution,
        'monthlySalary' => $monthlySalary,
        'agreeTerms' => $agreeTerms
    ]);

    // Validation array to collect all errors
    $errors = [];
    $fieldErrors = [];

    // Validate account type
    $allowedAccountTypes = ['family', 'personal'];
    if (empty($accountType) || !in_array($accountType, $allowedAccountTypes)) {
        $errors[] = "Please select a valid account type.";
        $fieldErrors['accountType'] = "Please select a valid account type.";
    }

    // Step 2 validation - Personal Information (common to both types)
    if (empty($firstName) || strlen($firstName) < 2) {
        $errors[] = "First name must be at least 2 characters long.";
        $fieldErrors['firstName'] = "First name must be at least 2 characters long.";
    }

    if (empty($lastName) || strlen($lastName) < 2) {
        $errors[] = "Last name must be at least 2 characters long.";
        $fieldErrors['lastName'] = "Last name must be at least 2 characters long.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
        $fieldErrors['email'] = "Please enter a valid email address.";
    }

    // Clean phone number for validation (remove spaces and formatting)
    $cleanPhoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
    if (empty($phoneNumber) || !preg_match('/^\+233\d{9}$/', $cleanPhoneNumber)) {
        $errors[] = "Please enter a valid phone number in format +233XXXXXXXXX.";
        $fieldErrors['phoneNumber'] = "Please enter a valid phone number.";
    }

    if (empty($dateOfBirth)) {
        $errors[] = "Date of birth is required.";
        $fieldErrors['dateOfBirth'] = "Date of birth is required.";
    } else {
        try {
            $birthDate = new DateTime($dateOfBirth);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age < 13) {
                $errors[] = "You must be at least 13 years old to register.";
                $fieldErrors['dateOfBirth'] = "You must be at least 13 years old.";
            }
            if ($age > 120) {
                $errors[] = "Please enter a valid date of birth.";
                $fieldErrors['dateOfBirth'] = "Please enter a valid date of birth.";
            }
        } catch (Exception $e) {
            $errors[] = "Invalid date of birth format.";
            $fieldErrors['dateOfBirth'] = "Invalid date format.";
        }
    }

    // Account type specific validation
    if ($accountType === 'family') {
        // Family account validations
        if ($monthlyContribution < 0) {
            $errors[] = "Monthly contribution cannot be negative.";
            $fieldErrors['monthlyContribution'] = "Monthly contribution cannot be negative.";
        }

        if ($monthlyContribution > 50000) {
            $errors[] = "Monthly contribution seems too high. Please verify the amount.";
            $fieldErrors['monthlyContribution'] = "Monthly contribution seems too high.";
        }

        // MoMo network and number are required for family accounts
        $allowedNetworks = ['mtn', 'vodafone', 'airteltigo'];
        if (empty($momoNetwork) || !in_array($momoNetwork, $allowedNetworks)) {
            $errors[] = "Please select a valid MoMo network.";
            $fieldErrors['momoNetwork'] = "Please select a valid MoMo network.";
        }

        $cleanMomoNumber = preg_replace('/[^\d+]/', '', $momoNumber);
        if (empty($momoNumber) || !preg_match('/^\+233\d{9}$/', $cleanMomoNumber)) {
            $errors[] = "Please enter a valid MoMo number in format +233XXXXXXXXX.";
            $fieldErrors['momoNumber'] = "Please enter a valid MoMo number.";
        }
    } else if ($accountType === 'personal') {
        // Personal account validations (monthly salary is optional)
        if ($monthlySalary < 0) {
            $errors[] = "Monthly salary cannot be negative.";
            $fieldErrors['monthlySalary'] = "Monthly salary cannot be negative.";
        }

        if ($monthlySalary > 500000) {
            $errors[] = "Monthly salary seems too high. Please verify the amount.";
            $fieldErrors['monthlySalary'] = "Monthly salary seems too high.";
        }
    }

    // Security validation (common to both types)
    if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Username must be between 3 and 20 characters long.";
        $fieldErrors['username'] = "Username must be between 3 and 20 characters long.";
    }

    if (!empty($username) && !preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, underscore, and hyphen.";
        $fieldErrors['username'] = "Username can only contain letters, numbers, underscore, and hyphen.";
    }

    // Reserved usernames check
    $reservedUsernames = ['admin', 'administrator', 'root', 'system', 'nkansah', 'family', 'user', 'test'];
    if (!empty($username) && in_array(strtolower($username), $reservedUsernames)) {
        $errors[] = "This username is reserved. Please choose another.";
        $fieldErrors['username'] = "This username is reserved. Please choose another.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
        $fieldErrors['signupPassword'] = "Password must be at least 6 characters long.";
    }

    if (strlen($password) > 255) {
        $errors[] = "Password is too long.";
        $fieldErrors['signupPassword'] = "Password is too long.";
    }

    // Password strength check
    if (strlen($password) >= 6) {
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/\d/', $password);
        
        if (!$hasLower || !$hasNumber) {
            $errors[] = "Password must contain at least one lowercase letter and one number.";
            $fieldErrors['signupPassword'] = "Password must contain at least one lowercase letter and one number.";
        }
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
        $fieldErrors['confirmPassword'] = "Passwords do not match.";
    }

    if (!$agreeTerms) {
        $errors[] = "You must agree to the Terms of Service and Privacy Policy.";
        $fieldErrors['agreeTerms'] = "You must agree to the Terms of Service and Privacy Policy.";
    }

    debugLog("Validation completed", [
        'errors_count' => count($errors),
        'field_errors_count' => count($fieldErrors)
    ]);

    // If validation errors exist, return them
    if (!empty($errors)) {
        debugLog("Validation failed", $errors);
        sendResponse(false, "Please correct the errors and try again.", [
            'errors' => $fieldErrors,
            'validation_errors' => $errors
        ]);
    }

    // Check for existing email
    debugLog("Checking for existing email");
    $emailCheckStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$emailCheckStmt) {
        debugLog("Email check preparation failed: " . $conn->error);
        sendResponse(false, "Database error occurred. Please try again.");
    }

    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();

    if ($emailCheckResult->num_rows > 0) {
        debugLog("Email already exists: $email");
        $fieldErrors['email'] = "This email address is already registered.";
        sendResponse(false, "This email address is already registered.", [
            'errors' => $fieldErrors
        ]);
    }
    $emailCheckStmt->close();

    // Check for existing username
    debugLog("Checking for existing username");
    $usernameCheckStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$usernameCheckStmt) {
        debugLog("Username check preparation failed: " . $conn->error);
        sendResponse(false, "Database error occurred. Please try again.");
    }

    $usernameCheckStmt->bind_param("s", $username);
    $usernameCheckStmt->execute();
    $usernameCheckResult = $usernameCheckStmt->get_result();

    if ($usernameCheckResult->num_rows > 0) {
        debugLog("Username already exists: $username");
        $fieldErrors['username'] = "This username is already taken.";
        sendResponse(false, "This username is already taken.", [
            'errors' => $fieldErrors
        ]);
    }
    $usernameCheckStmt->close();

    // Begin transaction for data integrity
    debugLog("Starting database transaction");
    $conn->begin_transaction();

    try {
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (!$passwordHash) {
            throw new Exception("Failed to hash password");
        }

        debugLog("Password hashed successfully");

        // Insert user into users table
        $insertUserStmt = $conn->prepare("
            INSERT INTO users (
                username, email, password_hash, first_name, last_name, 
                phone_number, date_of_birth, user_type, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        if (!$insertUserStmt) {
            throw new Exception("Failed to prepare user insert statement: " . $conn->error);
        }

        $insertUserStmt->bind_param(
            "ssssssss",
            $username,
            $email,
            $passwordHash,
            $firstName,
            $lastName,
            $cleanPhoneNumber,
            $dateOfBirth,
            $accountType
        );

        if (!$insertUserStmt->execute()) {
            throw new Exception("Failed to insert user: " . $insertUserStmt->error);
        }

        $userId = $conn->insert_id;
        $insertUserStmt->close();

        debugLog("User created successfully", ['user_id' => $userId]);

        // Account type specific setup
        if ($accountType === 'family') {
            // Create family group
            $familyName = $firstName . " " . $lastName . " Family";
            $familyCode = strtoupper(substr($lastName, 0, 6)) . date('Y');
            
            // Ensure unique family code
            $codeCounter = 1;
            $originalFamilyCode = $familyCode;
            
            do {
                $familyCodeCheckStmt = $conn->prepare("SELECT id FROM family_groups WHERE family_code = ?");
                $familyCodeCheckStmt->bind_param("s", $familyCode);
                $familyCodeCheckStmt->execute();
                $codeExists = $familyCodeCheckStmt->get_result()->num_rows > 0;
                $familyCodeCheckStmt->close();
                
                if ($codeExists) {
                    $familyCode = $originalFamilyCode . $codeCounter;
                    $codeCounter++;
                }
            } while ($codeExists && $codeCounter < 100);

            debugLog("Creating family group", [
                'family_name' => $familyName,
                'family_code' => $familyCode
            ]);

            $insertFamilyStmt = $conn->prepare("
                INSERT INTO family_groups (
                    family_name, family_code, monthly_target, currency, created_by, created_at
                ) VALUES (?, ?, ?, 'GHS', ?, NOW())
            ");

            if (!$insertFamilyStmt) {
                throw new Exception("Failed to prepare family insert statement: " . $conn->error);
            }

            $insertFamilyStmt->bind_param("ssdi", $familyName, $familyCode, $monthlyContribution, $userId);

            if (!$insertFamilyStmt->execute()) {
                throw new Exception("Failed to create family group: " . $insertFamilyStmt->error);
            }

            $familyId = $conn->insert_id;
            $insertFamilyStmt->close();

            debugLog("Family group created", ['family_id' => $familyId]);

            // Add user as family admin
            $insertMemberStmt = $conn->prepare("
                INSERT INTO family_members (
                    user_id, family_id, role, display_name, monthly_contribution_goal, is_active, joined_at
                ) VALUES (?, ?, 'admin', ?, ?, 1, NOW())
            ");

            if (!$insertMemberStmt) {
                throw new Exception("Failed to prepare family member insert statement: " . $conn->error);
            }

            $displayName = $firstName . " " . $lastName;
            $insertMemberStmt->bind_param("iisssd", $userId, $familyId, $displayName, $monthlyContribution);

            if (!$insertMemberStmt->execute()) {
                throw new Exception("Failed to add user as family member: " . $insertMemberStmt->error);
            }
            $insertMemberStmt->close();

            debugLog("User added as family admin");

            // Create MoMo account if provided
            if (!empty($momoNetwork) && !empty($cleanMomoNumber)) {
                $insertMomoStmt = $conn->prepare("
                    INSERT INTO momo_accounts (
                        user_id, family_id, phone_number, network, account_name, is_primary, is_active, created_at
                    ) VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
                ");

                if (!$insertMomoStmt) {
                    throw new Exception("Failed to prepare MoMo account insert: " . $conn->error);
                }

                $momoAccountName = $familyName . " Fund";
                $insertMomoStmt->bind_param("iisss", $userId, $familyId, $cleanMomoNumber, $momoNetwork, $momoAccountName);

                if (!$insertMomoStmt->execute()) {
                    throw new Exception("Failed to create MoMo account: " . $insertMomoStmt->error);
                }
                $insertMomoStmt->close();

                debugLog("MoMo account created");
            }

        } else if ($accountType === 'personal') {
            // Create salary record if provided
            if ($monthlySalary > 0) {
                $nextPayDate = date('Y-m-d', strtotime('+1 month'));
                
                $insertSalaryStmt = $conn->prepare("
                    INSERT INTO salaries (
                        user_id, monthly_salary, pay_frequency, next_pay_date, is_active, created_at
                    ) VALUES (?, ?, 'monthly', ?, 1, NOW())
                ");

                if (!$insertSalaryStmt) {
                    throw new Exception("Failed to prepare salary insert: " . $conn->error);
                }

                $insertSalaryStmt->bind_param("ids", $userId, $monthlySalary, $nextPayDate);

                if (!$insertSalaryStmt->execute()) {
                    throw new Exception("Failed to create salary record: " . $insertSalaryStmt->error);
                }
                $insertSalaryStmt->close();

                debugLog("Salary record created");
            }

            // Note: Default budget categories removed as requested
            // Users will create their own categories as needed
            debugLog("Personal account setup completed - no default categories created");
        }

        // Log the registration activity
        $activityStmt = $conn->prepare("
            INSERT INTO activity_logs (
                user_id, action_type, description, metadata, ip_address, user_agent, created_at
            ) VALUES (?, 'user_registration', ?, ?, ?, ?, NOW())
        ");

        if ($activityStmt) {
            $description = "New " . $accountType . " account registered";
            $metadata = json_encode([
                'account_type' => $accountType,
                'registration_method' => 'web_form',
                'user_agent_short' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100)
            ]);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $activityStmt->bind_param("issss", $userId, $description, $metadata, $ipAddress, $userAgent);
            $activityStmt->execute();
            $activityStmt->close();

            debugLog("Activity logged");
        }

        // Commit the transaction
        $conn->commit();
        
        debugLog("Transaction committed successfully");

        // Set session variables for auto-login
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = $accountType;
        $_SESSION['login_time'] = time();

        debugLog("Session variables set");

        // Determine redirect URL
        $redirectUrl = ($accountType === 'family') ? '../templates/dashboard' : '../templates/login';

        // Send success response
        sendResponse(true, "Account created successfully! Redirecting to login...", [
            'user_id' => $userId,
            'username' => $username,
            'account_type' => $accountType,
            'family_code' => ($accountType === 'family') ? $familyCode : null
        ], $redirectUrl);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        debugLog("Transaction rolled back due to error: " . $e->getMessage());
        
        sendResponse(false, "Registration failed: " . $e->getMessage());
    }

} catch (Exception $e) {
    debugLog("Registration error: " . $e->getMessage());
    sendResponse(false, "An unexpected error occurred. Please try again.");
}

// Close database connection
if (isset($conn)) {
    $conn->close();
    debugLog("Database connection closed");
}
?>