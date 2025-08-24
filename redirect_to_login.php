<?php
// Universal redirect to login - works anywhere
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            text-align: center; 
            padding: 50px; 
            background: #f5f5f5; 
        }
        .redirect-message {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 0 auto;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="redirect-message">
        <h2>🔒 Access Restricted</h2>
        <div class="spinner"></div>
        <p>Redirecting to login page...</p>
    </div>
    
    <script>
        // Smart redirect that works from any location
        function redirectToLogin() {
            var currentPath = window.location.pathname;
            var basePath = '';
            
            // Find the budget folder in the current path
            if (currentPath.includes('/budget/')) {
                var budgetIndex = currentPath.indexOf('/budget/');
                basePath = currentPath.substring(0, budgetIndex + 8); // Include '/budget/'
            } else {
                // Assume we're in the budget root
                basePath = currentPath.replace(/\/[^\/]*$/, '/');
            }
            
            // Construct the login URL
            var loginUrl = basePath + 'templates/login.php';
            
            // Clean up any double slashes
            loginUrl = loginUrl.replace(/\/+/g, '/');
            
            // Redirect
            window.location.href = loginUrl;
        }
        
        // Redirect after 1 second
        setTimeout(redirectToLogin, 1000);
    </script>
</body>
</html>
