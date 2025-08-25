<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family Fund - Login</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/auth.css">
</head>
<body>
    <!-- Header Brand -->
    <div class="header-brand">
        <div class="brand-logo">
            <div class="logo-icon">💰</div>
            <div class="brand-text">
                <h1>Nkansah</h1>
                <p>Budget Manager</p>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="auth-container">
        <div class="auth-form">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your family dashboard</p>
            </div>

            <form id="loginForm" class="login-form" action="../actions/login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username or email"
                               value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <span id="passwordToggleIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">Remember me</span>
                    </label>
                    
                    <a href="#" class="forgot-password" onclick="showForgotPassword()">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="submit-btn">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-loader" id="loginLoader"></span>
                </button>
            </form>

            <div class="form-footer">
                <p>Don't have an account? 
                    <a href="sign-up" class="signup-link">Create Account</a>
                </p>
            </div>

            
        </div>
    </div>

    <!-- Forgot Password Modal -->
   <div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset Password</h3>
            <span class="close" onclick="closeForgotPassword()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Enter your email address or username and set a new password.</p>
            <form id="forgotPasswordForm" action="../actions/reset-password.php" method="POST">
                <div class="form-group">
                    <label for="resetIdentifier">Email Address or Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="text" 
                               id="resetIdentifier" 
                               name="identifier" 
                               placeholder="Enter your email or username"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="newPassword" 
                               name="newPassword" 
                               placeholder="Enter new password"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="confirmPassword" 
                               name="confirmPassword" 
                               placeholder="Confirm new password"
                               required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeForgotPassword()">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Signing you in...</p>
        </div>
    </div>

    <!-- Notification Snackbar -->
    <div id="notification" class="notification">
        <span id="notificationMessage"></span>
        <button class="notification-close" onclick="closeNotification()">&times;</button>
    </div>

    <script src="../public/js/auth.js"></script>
    
    <!-- Handle URL parameters for notifications -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            
            if (status && message) {
                showNotification(decodeURIComponent(message), status);
                
                // Clean URL without reloading
                const url = new URL(window.location);
                url.searchParams.delete('status');
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url);
            }
        });

        // Updated demo credentials
        const demoCredentials = {
            family: {
                username: 'family@nkansah.com',
                password: 'family123'
            },
            personal: {
                username: 'user@example.com',
                password: 'user123'
            }
        };

        // Updated demo credentials function
        function fillDemoCredentials(type) {
            const credentials = demoCredentials[type];
            if (credentials) {
                document.getElementById('username').value = credentials.username;
                document.getElementById('password').value = credentials.password;
                
                // Clear any validation errors
                document.getElementById('username').classList.remove('error');
                document.getElementById('password').classList.remove('error');
                
                const dashboardType = type === 'family' ? 'Family Dashboard' : 'Personal Dashboard';
                showNotification(`Demo credentials filled for ${dashboardType}`, 'success');
            }
        }
    </script>
</body>
</html>