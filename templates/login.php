<?php
// Check if user was redirected due to session timeout
$showTimeoutMessage = isset($_GET['timeout']) && $_GET['timeout'] == '1';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetly - Sign In</title>
    <?php include '../includes/favicon.php'; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/loading.css">
    <link rel="stylesheet" href="../public/css/personal.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1a1a1a;
            min-height: 100vh;
            line-height: 1.5;
        }

        /* Main Container */
        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Left Side - Visual/Branding */
        .login-visual {
            flex: 1;
            background: #2563eb; /* Fallback color if image doesn't load */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .visual-content {
            text-align: center;
            z-index: 10;
            max-width: 500px;
            position: relative;
            background: rgba(0, 0, 0, 0.5);
            padding: 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-name {
            font-size: 36px;
            font-weight: 800;
            color: white;
        }

        .visual-title {
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .visual-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
        }

        /* Background image that fills the entire left side */
        .dashboard-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: none;
            border-radius: 0;
            border: none;
            backdrop-filter: none;
            display: block;
            overflow: hidden;
            z-index: 1;
        }

        .dashboard-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Right Side - Form */
        .login-form-section {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            min-width: 480px;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .form-subtitle {
            font-size: 16px;
            color: #6b7280;
        }

        /* Form Styles */
        .login-form {
            margin-bottom: 32px;
        }

        /* Timeout Message Styles */
        .timeout-message {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            line-height: 1.4;
        }

        .timeout-message .icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .timeout-message .message {
            flex-grow: 1;
        }

        .timeout-message.show {
            animation: slideInFromTop 0.5s ease-out;
        }

        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 24px;
            font-size: 16px;
            background: #fafafa;
            color: #1a1a1a;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            font-size: 14px;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
        }

        .checkbox-label {
            font-size: 14px;
            color: #6b7280;
            cursor: pointer;
        }

        .forgot-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .submit-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .btn-loader {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        .submit-btn.loading .btn-text {
            display: none;
        }

        .submit-btn.loading .btn-loader {
            display: block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Social Login */
        .social-login {
            margin-bottom: 32px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: #9ca3af;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            padding: 0 16px;
        }

        .social-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .social-btn {
            width: 100%;
            padding: 12px 16px;
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
        }

        .social-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
            text-decoration: none;
        }

        .social-icon {
            width: 20px;
            height: 20px;
        }

        /* Form Footer */
        .form-footer {
            text-align: center;
        }

        .signup-text {
            font-size: 15px;
            color: #6b7280;
        }

        .signup-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .signup-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* Demo Section */
        .demo-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #f3f4f6;
        }

        .demo-title {
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-buttons {
            display: flex;
            gap: 8px;
        }

        .demo-btn {
            flex: 1;
            padding: 8px 12px;
            background: #f8fafc;
            color: #2563eb;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .demo-btn:hover {
            background: #2563eb;
            color: white;
        }

        /* Snackbar Notification Styles */
        .snackbar {
            position: fixed;
            bottom: -100px;
            left: 50%;
            transform: translateX(-50%);
            background: #323232;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            transition: all 0.3s ease;
            max-width: 400px;
            min-width: 300px;
        }

        .snackbar.show {
            bottom: 30px;
        }

        .snackbar.success {
            background: #059669;
        }

        .snackbar.error {
            background: #dc2626;
        }

        .snackbar.warning {
            background: #d97706;
        }

        .snackbar.info {
            background: #2563eb;
        }

        .snackbar-icon {
            font-size: 18px;
            font-weight: bold;
        }

        .snackbar-message {
            flex-grow: 1;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
            animation: modalSlideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 30px;
            border-bottom: 1px solid #f3f4f6;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .close {
            color: #6b7280;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .close:hover {
            background: #f3f4f6;
            color: #1a1a1a;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn-secondary {
            padding: 12px 20px;
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .btn-primary {
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Error States */
        .form-input.error {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .form-input.error:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .field-error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-visual {
                min-height: 40vh;
                padding: 30px 20px;
            }

            .visual-title {
                font-size: 28px;
            }

            .visual-subtitle {
                font-size: 16px;
            }

            .dashboard-preview {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                border-radius: 0;
            }

            .dashboard-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 20px;
            }

            .login-form-section {
                min-width: auto;
                padding: 30px 20px;
            }
        }

        @media (max-width: 768px) {
            .login-visual {
                min-height: 35vh;
                padding: 20px;
            }

            .visual-content {
                background: rgba(0, 0, 0, 0.6);
                padding: 20px;
                border-radius: 15px;
            }

            .brand-name {
                font-size: 28px;
            }

            .visual-title {
                font-size: 24px;
            }

            .visual-subtitle {
                font-size: 14px;
            }

            .dashboard-preview {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                font-size: 14px;
                border-radius: 0;
            }

            .dashboard-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 0;
            }

            .form-title {
                font-size: 24px;
            }

            .demo-buttons {
                flex-direction: column;
            }

            .snackbar {
                left: 10px;
                right: 10px;
                transform: none;
                min-width: auto;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }

        @media (max-width: 480px) {
            .login-visual {
                min-height: 30vh;
                padding: 20px 15px;
            }

            .visual-content {
                background: rgba(0, 0, 0, 0.7);
                padding: 15px;
                border-radius: 12px;
            }

            .brand-logo {
                gap: 12px;
            }

            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }

            .brand-name {
                font-size: 24px;
            }

            .login-form-section {
                padding: 20px 15px;
            }

            .form-input {
                font-size: 16px;
                /* Prevents zoom on iOS */
            }
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->

    <div class="login-wrapper">
        <!-- Left Side - Visual/Branding -->
        <div class="login-visual">
            <!-- Background image -->
            <div class="dashboard-preview">
                <img src="../public/overview.jpg" alt="Budgetly Dashboard Preview">
            </div>
            
            <!-- Content overlay -->
            <div class="visual-content">
                <div class="brand-logo">
                    <div class="logo-icon">💰</div>
                    <div class="brand-name">Budgetly</div>
                </div>
                
                <h1 class="visual-title">Manage Your Finances with Ease</h1>
                <p class="visual-subtitle">Track expenses, set budgets, and achieve your financial goals with our comprehensive budgeting platform.</p>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="login-form-section">
            <div class="form-container">
                <div class="form-header">
                    <h2 class="form-title">Sign in to Budgetly</h2>
                    <p class="form-subtitle">Welcome back! Please enter your details.</p>
                </div>

                <?php if ($showTimeoutMessage): ?>
                <div class="timeout-message show">
                    <span class="icon">⏰</span>
                    <div class="message">
                        <strong>Session Expired</strong><br>
                        Your session has timed out due to inactivity. Please sign in again to continue.
                    </div>
                </div>
                <?php endif; ?>

                <form id="loginForm" class="login-form" action="../actions/login.php" method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Email / Username</label>
                        <div class="input-wrapper">
                            <input type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                placeholder="Enter your email or username"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <input type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Enter your password"
                                required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <span id="passwordToggleIcon">Show</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="checkbox-group">
                            <input type="checkbox" id="rememberMe" name="rememberMe" class="checkbox">
                            <label for="rememberMe" class="checkbox-label">Remember for 30 days</label>
                        </div>

                        <a href="#" class="forgot-link" onclick="showForgotPassword()">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span class="btn-text">Sign in</span>
                        <span class="btn-loader" id="loginLoader"></span>
                    </button>
                </form>

                <div class="social-login">
                    <div class="divider">
                        <span>Or login with</span>
                    </div>
                    <div class="social-buttons">
                        <a href="../oauth/google/login.php" class="social-btn" id="googleLoginBtn">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                            </svg>
                            Continue with Google
                        </a>
                    </div>
                </div>

                <div class="form-footer">
                    <p class="signup-text">Don't have an account?
                        <a href="sign-up" class="signup-link">Sign up now</a>
                    </p>

                </div>
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
                <p style="margin-bottom: 20px; color: #6b7280;">Enter your email address or username and set a new password.</p>
                <form id="forgotPasswordForm" action="../actions/reset-password.php" method="POST">
                    <div class="form-group">
                        <label for="resetIdentifier" class="form-label">Email Address or Username</label>
                        <div class="input-wrapper">
                            <input type="text"
                                id="resetIdentifier"
                                name="identifier"
                                class="form-input"
                                placeholder="Enter your email or username"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPassword" class="form-label">New Password</label>
                        <div class="input-wrapper">
                            <input type="password"
                                id="newPassword"
                                name="newPassword"
                                class="form-input"
                                placeholder="Enter new password"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <div class="input-wrapper">
                            <input type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                class="form-input"
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

    <!-- Snackbar Notification -->
    <div id="snackbar" class="snackbar">
        <span class="snackbar-icon"></span>
        <span class="snackbar-message"></span>
    </div>

    <script src="../public/js/loading.js"></script>
    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'Show';
            }
        }

        // Modal functionality
        function showForgotPassword() {
            document.getElementById('forgotPasswordModal').style.display = 'block';
        }

        function closeForgotPassword() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Snackbar functionality (updated to match personal dashboard)
        function showSnackbar(message, type = 'info') {
            // Remove existing snackbar if any
            const existingSnackbar = document.querySelector('.snackbar');
            if (existingSnackbar) {
                existingSnackbar.remove();
            }

            // Create new snackbar
            const snackbar = document.createElement('div');
            snackbar.className = `snackbar ${type}`;
            
            const icons = {
                success: '<i class="fas fa-check-circle"></i>',
                error: '<i class="fas fa-times-circle"></i>',
                warning: '<i class="fas fa-exclamation-triangle"></i>',
                info: '<i class="fas fa-info-circle"></i>'
            };
            
            snackbar.innerHTML = `
                <span class="snackbar-icon">${icons[type] || icons.info}</span>
                <span class="snackbar-message">${message}</span>
            `;
            
            document.body.appendChild(snackbar);
            
            // Show snackbar
            setTimeout(() => snackbar.classList.add('show'), 100);
            
            // Hide snackbar after 4 seconds
            setTimeout(() => {
                snackbar.classList.remove('show');
                setTimeout(() => snackbar.remove(), 300);
            }, 4000);
        }

        // Demo credentials
        const demoCredentials = {
            family: {
                username: 'family@budgetly.com',
                password: 'family123'
            },
            personal: {
                username: 'user@budgetly.com',
                password: 'user123'
            }
        };

        function fillDemoCredentials(type) {
            const credentials = demoCredentials[type];
            if (credentials) {
                document.getElementById('username').value = credentials.username;
                document.getElementById('password').value = credentials.password;

                const dashboardType = type === 'family' ? 'Family Dashboard' : 'Personal Budget';
                showSnackbar(`Demo credentials filled for ${dashboardType}`, 'success');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            console.log('Login form submitted');
            
            const submitBtn = document.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.classList.add('loading');
            }

            // Show unified loading screen
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
            } else {
                // Fallback to existing loading
                showLoaderFor(3000);
            }
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit to server using fetch to handle response properly
            fetch('../actions/login.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Hide loading screen
                if (window.budgetlyLoader) {
                    window.budgetlyLoader.hide();
                } else {
                    hideLoader();
                }
                
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
                
                // Check if the response is a redirect (status 302 or 200 with Location header)
                if (response.redirected || response.status === 302) {
                    // Follow the redirect
                    window.location.href = response.url;
                    return;
                }
                
                // If it's a JSON response, handle it
                return response.text().then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                showSnackbar('Login successful!', 'success');
                            }
                        } else {
                            showSnackbar(data.message || 'Login failed. Please try again.', 'error');
                        }
                    } catch (e) {
                        // If it's not JSON, it might be HTML (redirect page)
                        // Check if it contains a redirect meta tag or javascript redirect
                        if (text.includes('location.href') || text.includes('window.location')) {
                            // Extract the redirect URL or just reload
                            window.location.reload();
                        } else {
                            showSnackbar('Login failed. Please try again.', 'error');
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Login error:', error);
                
                // Hide loading screen
                if (window.budgetlyLoader) {
                    window.budgetlyLoader.hide();
                } else {
                    hideLoader();
                }
                
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
                
                showSnackbar('Network error. Please check your connection and try again.', 'error');
            });
        });

        // Handle URL parameters for notifications
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Login: DOMContentLoaded fired');
            console.log('Login: LoadingScreen available?', typeof window.LoadingScreen);
            
            // Initialize loading screen
            if (window.LoadingScreen) {
                console.log('Login: Creating LoadingScreen');
                window.budgetlyLoader = new LoadingScreen();
                console.log('Login: LoadingScreen created', window.budgetlyLoader);
                
                // Customize the loading message for login
                const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
                if (loadingMessage) {
                    loadingMessage.innerHTML = 'Signing you in<span class="loading-dots-text">...</span>';
                    console.log('Login: Loading message customized');
                } else {
                    console.error('Login: Could not find loading message element');
                }
                
                // Ensure it's hidden initially
                window.budgetlyLoader.hide();
            } else {
                console.error('Login: LoadingScreen class not available');
            }

            // Handle Google OAuth login button
            const googleLoginBtn = document.getElementById('googleLoginBtn');
            if (googleLoginBtn) {
                googleLoginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show loading state
                    if (window.budgetlyLoader) {
                        const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
                        if (loadingMessage) {
                            loadingMessage.innerHTML = 'Connecting to Google<span class="loading-dots-text">...</span>';
                        }
                        window.budgetlyLoader.show();
                    }
                    
                    // Add slight delay for better UX, then redirect
                    setTimeout(() => {
                        window.location.href = '../oauth/google/login.php';
                    }, 500);
                });
            }

            // Handle OAuth error messages from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const status = urlParams.get('status');
            const message = urlParams.get('message');

            // Handle OAuth-specific errors
            if (error) {
                let errorMessage = '';
                switch (error) {
                    case 'oauth_disabled':
                        errorMessage = 'Google login is currently disabled. Please use email/password login.';
                        break;
                    case 'oauth_not_configured':
                        errorMessage = 'Google login is not properly configured. Please contact support.';
                        break;
                    case 'oauth_invalid_state':
                        errorMessage = 'Security error during Google login. Please try again.';
                        break;
                    case 'oauth_callback_failed':
                        errorMessage = 'Google login failed. Please try again or use email/password login.';
                        break;
                    case 'oauth_access_denied':
                        errorMessage = 'Google login was cancelled. You can try again or use email/password login.';
                        break;
                    default:
                        errorMessage = 'Login error occurred. Please try again.';
                }
                showSnackbar(errorMessage, 'error');
                
                // Clean URL
                const url = new URL(window.location);
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url);
            }

            if (status && message) {
                showSnackbar(decodeURIComponent(message), status);

                // Clean URL without reloading
                const url = new URL(window.location);
                url.searchParams.delete('status');
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url);
            }
        });

        // Test function for loading screen (can be called from browser console)
        window.testLoginLoadingScreen = function(duration = 3000) {
            if (window.budgetlyLoader) {
                console.log('Testing login loading screen for', duration, 'ms');
                window.budgetlyLoader.show();
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                    console.log('Login loading screen test complete');
                }, duration);
            } else {
                console.log('Loading screen not available');
            }
        };

        // Test function for snackbar (can be called from browser console)
        window.testLoginSnackbar = function(message = 'Test notification', type = 'success') {
            showSnackbar(message, type);
            console.log(`Showing ${type} snackbar: "${message}"`);
        };

        // Emergency function to hide loading screen (can be called from browser console)
        window.hideLoadingScreen = function() {
            if (window.budgetlyLoader) {
                window.budgetlyLoader.hide();
                console.log('Loading screen forcefully hidden');
            } else {
                const loadingScreen = document.getElementById('loadingScreen');
                if (loadingScreen) {
                    loadingScreen.style.display = 'none';
                    console.log('Loading screen element hidden');
                }
            }
        };

        // Keyboard shortcut to hide loading screen (Escape key)
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                window.hideLoadingScreen();
            }
        });

        // Global error handler to ensure loading screen is hidden
        window.addEventListener('error', function(event) {
            console.error('Global error caught:', event.error);
            if (window.budgetlyLoader) {
                window.budgetlyLoader.hide();
            }
        });

        // Promise rejection handler
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            if (window.budgetlyLoader) {
                window.budgetlyLoader.hide();
            }
        });
    </script>
</body>

</html>