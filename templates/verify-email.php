<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Budget Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/loading.css">
    <link rel="stylesheet" href="../public/css/personal.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .verification-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .verification-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .verification-icon.success {
            color: #28a745;
        }
        
        .verification-icon.error {
            color: #dc3545;
        }
        
        .verification-icon.loading {
            color: #667eea;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .token-input-section {
            margin: 30px 0;
        }
        
        .token-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 10px 0;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #cce7ff;
            color: #004085;
            border: 1px solid #99d5ff;
        }
        
        .resend-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .verification-card {
                padding: 30px 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <div id="loadingIcon" class="verification-icon loading">
                <i class="fas fa-spinner"></i>
            </div>
            <div id="successIcon" class="verification-icon success" style="display: none;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div id="errorIcon" class="verification-icon error" style="display: none;">
                <i class="fas fa-times-circle"></i>
            </div>
            
            <h1 id="mainTitle">Verifying Your Email...</h1>
            <p id="mainMessage">Please wait while we verify your email address.</p>
            
            <div id="alertContainer"></div>
            
            <!-- Manual token entry section -->
            <div id="manualTokenSection" style="display: none;">
                <div class="token-input-section">
                    <label for="tokenInput">Enter your verification code:</label>
                    <input type="text" id="tokenInput" class="token-input" placeholder="Enter 8-digit code" maxlength="8">
                    <button id="verifyTokenBtn" class="btn btn-primary">
                        <i class="fas fa-check"></i> Verify Code
                    </button>
                </div>
            </div>
            
            <!-- Resend verification section -->
            <div id="resendSection" class="resend-section" style="display: none;">
                <p>Didn't receive the verification email?</p>
                <input type="email" id="resendEmail" placeholder="Enter your email address" 
                       style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px;">
                <button id="resendBtn" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i> Resend Verification Email
                </button>
            </div>
            
            <!-- Action buttons -->
            <div id="actionButtons" style="display: none;">
                <button id="loginBtn" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </button>
                <button id="homeBtn" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </button>
            </div>
        </div>
    </div>

    <script src="../public/js/loading.js"></script>
    <script>
        class EmailVerificationHandler {
            constructor() {
                this.urlParams = new URLSearchParams(window.location.search);
                this.token = this.urlParams.get('token');
                this.email = this.urlParams.get('email');
                
                this.init();
            }
            
            init() {
                // If token is in URL, verify automatically
                if (this.token) {
                    this.verifyEmailFromURL();
                } else {
                    // Show manual token entry
                    this.showManualTokenEntry();
                }
                
                this.bindEvents();
            }
            
            bindEvents() {
                document.getElementById('verifyTokenBtn').addEventListener('click', () => {
                    this.verifyManualToken();
                });
                
                document.getElementById('resendBtn').addEventListener('click', () => {
                    this.resendVerification();
                });
                
                document.getElementById('loginBtn').addEventListener('click', () => {
                    window.location.href = '/budget/login';
                });
                
                document.getElementById('homeBtn').addEventListener('click', () => {
                    window.location.href = '/budget/';
                });
                
                // Allow Enter key to submit token
                document.getElementById('tokenInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.verifyManualToken();
                    }
                });
            }
            
            async verifyEmailFromURL() {
                if (!this.token) {
                    this.showError('No verification token provided');
                    return;
                }
                
                try {
                    const response = await fetch('../api/verify_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            email: this.email,
                            token: this.token
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showSuccess(data.message, data.user_name);
                    } else {
                        this.showError(data.error);
                    }
                    
                } catch (error) {
                    console.error('Verification error:', error);
                    this.showError('Failed to verify email. Please try again.');
                }
            }
            
            async verifyManualToken() {
                const tokenInput = document.getElementById('tokenInput');
                const token = tokenInput.value.trim().toUpperCase();
                
                if (!token) {
                    this.showAlert('Please enter your verification code', 'error');
                    return;
                }
                
                if (token.length !== 8) {
                    this.showAlert('Verification code must be 8 characters long', 'error');
                    return;
                }
                
                this.setLoading(true);
                
                try {
                    const response = await fetch('../api/verify_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            email: this.email,
                            token: token
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showSuccess(data.message, data.user_name);
                    } else {
                        this.showAlert(data.error, 'error');
                    }
                    
                } catch (error) {
                    console.error('Verification error:', error);
                    this.showAlert('Failed to verify email. Please try again.', 'error');
                } finally {
                    this.setLoading(false);
                }
            }
            
            async resendVerification() {
                const emailInput = document.getElementById('resendEmail');
                const email = emailInput.value.trim();
                
                if (!email) {
                    this.showAlert('Please enter your email address', 'error');
                    return;
                }
                
                if (!this.isValidEmail(email)) {
                    this.showAlert('Please enter a valid email address', 'error');
                    return;
                }
                
                this.setLoading(true);
                
                try {
                    const response = await fetch('../api/resend_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ email: email })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert(data.message, 'success');
                        emailInput.value = '';
                    } else {
                        this.showAlert(data.error, 'error');
                    }
                    
                } catch (error) {
                    console.error('Resend error:', error);
                    this.showAlert('Failed to resend verification email. Please try again.', 'error');
                } finally {
                    this.setLoading(false);
                }
            }
            
            showManualTokenEntry() {
                document.getElementById('loadingIcon').style.display = 'none';
                document.getElementById('errorIcon').style.display = 'block';
                document.getElementById('mainTitle').textContent = 'Email Verification Required';
                document.getElementById('mainMessage').textContent = 'Enter your verification code or request a new one.';
                document.getElementById('manualTokenSection').style.display = 'block';
                document.getElementById('resendSection').style.display = 'block';
            }
            
            showSuccess(message, userName = null) {
                document.getElementById('loadingIcon').style.display = 'none';
                document.getElementById('errorIcon').style.display = 'none';
                document.getElementById('successIcon').style.display = 'block';
                
                const title = userName ? `Welcome, ${userName}!` : 'Email Verified Successfully!';
                document.getElementById('mainTitle').textContent = title;
                document.getElementById('mainMessage').textContent = message;
                
                document.getElementById('manualTokenSection').style.display = 'none';
                document.getElementById('resendSection').style.display = 'none';
                document.getElementById('actionButtons').style.display = 'block';
                
                this.showAlert('🎉 ' + message, 'success');
            }
            
            showError(message) {
                document.getElementById('loadingIcon').style.display = 'none';
                document.getElementById('errorIcon').style.display = 'block';
                document.getElementById('mainTitle').textContent = 'Verification Failed';
                document.getElementById('mainMessage').textContent = 'There was an issue verifying your email.';
                
                document.getElementById('manualTokenSection').style.display = 'block';
                document.getElementById('resendSection').style.display = 'block';
                
                this.showAlert(message, 'error');
            }
            
            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertClass = type === 'success' ? 'alert-success' : 
                                  type === 'error' ? 'alert-error' : 'alert-info';
                
                alertContainer.innerHTML = `
                    <div class="alert ${alertClass}">
                        ${message}
                    </div>
                `;
                
                // Auto-hide after 5 seconds for success messages
                if (type === 'success') {
                    setTimeout(() => {
                        alertContainer.innerHTML = '';
                    }, 5000);
                }
            }
            
            setLoading(loading) {
                const buttons = document.querySelectorAll('.btn');
                buttons.forEach(btn => {
                    btn.disabled = loading;
                });
            }
            
            isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new EmailVerificationHandler();
        });
    </script>
</body>
</html>
