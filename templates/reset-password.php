<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Nkansah Budget Manager</title>
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
                <h2>Reset Password</h2>
                <p>Enter your new password below</p>
            </div>

            <form id="resetPasswordForm" class="reset-form" action="../actions/reset-password-process.php" method="POST">
                <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
                
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="newPassword" 
                               name="newPassword" 
                               placeholder="Enter your new password"
                               required
                               minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword', 'newPasswordToggleIcon')">
                            <span id="newPasswordToggleIcon">👁️</span>
                        </button>
                    </div>
                    <small class="password-hint">Password must be at least 6 characters long</small>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="confirmPassword" 
                               name="confirmPassword" 
                               placeholder="Confirm your new password"
                               required
                               minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', 'confirmPasswordToggleIcon')">
                            <span id="confirmPasswordToggleIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <span class="btn-text">Reset Password</span>
                    <span class="btn-loader" id="resetLoader"></span>
                </button>
            </form>

            <div class="form-footer">
                <p>Remember your password? 
                    <a href="login" class="signin-link">Sign In</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Resetting your password...</p>
        </div>
    </div>

    <!-- Notification Snackbar -->
    <div id="notification" class="notification">
        <span id="notificationMessage"></span>
        <button class="notification-close" onclick="closeNotification()">&times;</button>
    </div>

    <script src="../public/js/auth.js"></script>
    
    <!-- Reset Password Specific JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeResetPassword();
            
            // Handle URL parameters for notifications
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

            // Check if token exists
            const token = urlParams.get('token');
            if (!token) {
                showNotification('Invalid or missing reset token. Please request a new password reset.', 'error');
                setTimeout(() => {
                    window.location.href = 'login';
                }, 3000);
            }
        });

        function initializeResetPassword() {
            const resetForm = document.getElementById('resetPasswordForm');
            if (resetForm) {
                resetForm.addEventListener('submit', handleResetSubmit);
            }

            // Add password confirmation validation
            const confirmPassword = document.getElementById('confirmPassword');
            if (confirmPassword) {
                confirmPassword.addEventListener('input', validatePasswordMatch);
                confirmPassword.addEventListener('blur', validatePasswordMatch);
            }

            const newPassword = document.getElementById('newPassword');
            if (newPassword) {
                newPassword.addEventListener('input', function() {
                    validatePasswordStrength(this);
                    validatePasswordMatch(); // Re-check match when new password changes
                });
            }
        }

        function handleResetSubmit(e) {
            e.preventDefault();

            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Validate passwords
            if (newPassword.length < 6) {
                showNotification('Password must be at least 6 characters long', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showNotification('Passwords do not match', 'error');
                return;
            }

            // Show loading state
            const submitBtn = e.target.querySelector('.submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            btnText.textContent = 'Resetting...';
            
            document.getElementById('loadingOverlay').classList.add('show');
            
            // Submit the form
            e.target.submit();
        }

        function validatePasswordMatch() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const confirmField = document.getElementById('confirmPassword');
            const wrapper = confirmField.closest('.input-wrapper');

            // Remove existing error
            const existingError = wrapper.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }

            if (confirmPassword && newPassword !== confirmPassword) {
                showFieldError(wrapper, 'Passwords do not match');
                confirmField.classList.add('error');
                return false;
            } else {
                confirmField.classList.remove('error');
                return true;
            }
        }

        function validatePasswordStrength(field) {
            const wrapper = field.closest('.input-wrapper');
            const existingError = wrapper.querySelector('.field-error');
            
            if (existingError) {
                existingError.remove();
            }

            if (field.value.length > 0 && field.value.length < 6) {
                showFieldError(wrapper, 'Password must be at least 6 characters long');
                field.classList.add('error');
                return false;
            } else {
                field.classList.remove('error');
                return true;
            }
        }

        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        function showFieldError(wrapper, message) {
            const errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            errorElement.textContent = message;
            wrapper.appendChild(errorElement);
        }
    </script>

    <style>
        .password-hint {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .reset-form .form-group {
            margin-bottom: 1.5rem;
        }

        .input-wrapper input.error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .field-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
    </style>
</body>
</html>