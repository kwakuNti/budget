<?php
/**
 * Walkthrough Guard - Server-side protection for pages that require salary setup
 * Include this file at the top of pages that should be protected
 */

function checkSalaryRequirement() {
    // Only check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    // Create database connection
    $servername = "localhost";
    $username   = "root";
    $password   = "root";
    $database   = "budget";
    
    $conn = new mysqli($servername, $username, $password, $database);
    
    if ($conn->connect_error) {
        error_log("Walkthrough guard database connection failed: " . $conn->connect_error);
        return; // Fail silently to not break the page
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if user has completed the initial walkthrough
        $stmt = $conn->prepare("
            SELECT current_step, is_completed 
            FROM user_walkthrough_progress 
            WHERE user_id = ? AND walkthrough_type = 'initial_setup'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $progress = $result->fetch_assoc();
        
        // If no walkthrough progress, user is new - redirect to dashboard
        if (!$progress) {
            redirectToSalarySetup();
            return;
        }
        
        // If walkthrough is completed, no need to check
        if ($progress['is_completed']) {
            return;
        }
        
        // If current step is salary configuration, check if they actually have salary
        if ($progress['current_step'] === 'configure_salary') {
            // Check if user has salary configured
            $salary_stmt = $conn->prepare("
                SELECT COALESCE(salary, 0) + COALESCE(additional_income, 0) as total_income
                FROM member_salary 
                WHERE member_id = ?
            ");
            $salary_stmt->bind_param("i", $user_id);
            $salary_stmt->execute();
            $salary_result = $salary_stmt->get_result();
            $salary_data = $salary_result->fetch_assoc();
            
            $total_income = $salary_data ? floatval($salary_data['total_income']) : 0;
            
            // If no salary configured, redirect to salary page
            if ($total_income <= 0) {
                $current_page = basename($_SERVER['PHP_SELF']);
                
                // Only redirect if not already on salary page or dashboard
                if ($current_page !== 'salary.php' && $current_page !== 'personal-dashboard.php') {
                    redirectToSalarySetup();
                }
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't break the page
        error_log("Walkthrough guard error: " . $e->getMessage());
    } finally {
        // Close database connection
        if (isset($conn)) {
            $conn->close();
        }
    }
}

function redirectToSalarySetup() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['REQUEST_URI']);
    
    // Remove /templates from path if present
    $base_path = str_replace('/templates', '', $base_path);
    
    $redirect_url = $protocol . '://' . $host . $base_path . '/templates/salary.php';
    
    // Use JavaScript redirect with message to avoid header issues
    echo '<script type="text/javascript">
        alert("Please complete your salary setup before accessing other pages.");
        window.location.href = "' . $redirect_url . '";
    </script>';
    exit();
}

// Auto-run the check when this file is included
checkSalaryRequirement();
?>
