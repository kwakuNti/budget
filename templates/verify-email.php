<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Budgetly</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .verification-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            min-height: 85vh;
            max-height: 90vh;
        }
        
        .verification-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 40px 50px;
            text-align: center;
            color: white;
            flex: 0 0 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .brand-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }
        
        .brand-logo i {
            font-size: 2.2rem;
            color: white;
        }
        
        .header-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 280px;
        }
        
        .verification-body {
            padding: 40px 50px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        
        .status-section {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .status-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        
        .status-icon.loading {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            animation: pulse 2s infinite;
        }
        
        .status-icon.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .status-icon.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .status-title {
            font-size: 1.6rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .status-message {
            color: #6b7280;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .verification-form {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .verification-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 20px;
            text-align: center;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 600;
            transition: all 0.3s ease;
            background: white;
        }
        
        .verification-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .verification-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
            letter-spacing: 2px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            min-width: 140px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover:not(:disabled) {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            color: white;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .alert-icon {
            font-size: 1.1rem;
            margin-top: 1px;
        }
        
        .divider {
            border-top: 1px solid #e5e7eb;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: 'or';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 15px;
            color: #9ca3af;
            font-size: 0.9rem;
        }
        
        .resend-section {
            text-align: center;
        }
        
        .resend-section p {
            color: #6b7280;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .email-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .email-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }
        
        @media (max-width: 768px) {
            .verification-container {
                flex-direction: column;
                margin: 10px;
                min-height: auto;
                max-height: none;
            }
            
            .verification-header {
                flex: none;
                padding: 40px 30px 30px;
            }
            
            .verification-body {
                padding: 30px;
            }
            
            .verification-form {
                padding: 25px;
                margin: 25px 0 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 12px;
            }
            
            .btn {
                width: 100%;
            }
            
            .brand-logo {
                width: 70px;
                height: 70px;
                margin-bottom: 20px;
            }
            
            .header-title {
                font-size: 1.8rem;
            }
            
            .status-icon {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .verification-container {
                margin: 5px;
            }
            
            .verification-header,
            .verification-body {
                padding: 25px 20px;
            }
            
            .verification-form {
                padding: 20px;
            }
            
            .header-title {
                font-size: 1.6rem;
            }
            
            .status-title {
                font-size: 1.4rem;
            }
            
            .verification-input {
                font-size: 18px;
                letter-spacing: 2px;
                padding: 14px;
            }
        }
        
        @media (min-width: 769px) and (max-height: 700px) {
            .verification-container {
                min-height: 95vh;
                max-height: 95vh;
            }
            
            .verification-header {
                padding: 30px 40px;
            }
            
            .verification-body {
                padding: 30px 40px;
            }
            
            .status-section {
                margin-bottom: 25px;
            }
            
            .verification-form {
                margin: 20px 0 15px;
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <!-- Beautiful Blue Header -->
        <div class="verification-header">
            <div class="brand-logo">
                <i class="fas fa-wallet"></i>
            </div>
            <h1 class="header-title">Email Verification</h1>
            <p class="header-subtitle">We're almost ready! Please verify your email address to continue.</p>
        </div>
        
        <!-- Main Content -->
        <div class="verification-body">
            <!-- Status Section -->
            <div class="status-section">
                <div id="statusIcon" class="status-icon loading">
                    <i class="fas fa-spinner"></i>
                </div>
                <h2 id="statusTitle" class="status-title">Verifying Your Email...</h2>
                <p id="statusMessage" class="status-message">Please wait while we verify your email address.</p>
            </div>
            
            <!-- Alert Container -->
            <div id="alertContainer"></div>
            
            <!-- Manual Verification Form -->
            <div id="manualVerificationForm" class="verification-form" style="display: none;">
                <div class="form-group">
                    <label for="tokenInput" class="form-label">
                        <i class="fas fa-key"></i> Enter your verification code
                    </label>
                    <input 
                        type="text" 
                        id="tokenInput" 
                        class="verification-input" 
                        placeholder="XXXXXXXX" 
                        maxlength="8"
                        autocomplete="off"
                    >
                </div>
                <button id="verifyTokenBtn" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i> Verify Code
                </button>
            </div>
            
            <!-- Resend Section -->
            <div id="resendSection" style="display: none;">
                <div class="divider"></div>
                <div class="resend-section">
                    <p>Didn't receive the verification email?</p>
                    <input 
                        type="email" 
                        id="resendEmail" 
                        class="email-input" 
                        placeholder="Enter your email address"
                    >
                    <button id="resendBtn" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-envelope"></i> Resend Verification Email
                    </button>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div id="actionButtons" class="action-buttons" style="display: none;">
                <button id="loginBtn" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </button>
                
            </div>
        </div>
    </div>

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
                    window.location.href = '/login';
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
                
                // Auto-format token input
                document.getElementById('tokenInput').addEventListener('input', (e) => {
                    e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
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
                        body: JSON.stringify({
                            token: this.token,
                            email: this.email
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showSuccess('Email verified successfully!', 'Your account is now active. You can log in to start managing your budget.');
                    } else {
                        this.showError(data.message || 'Verification failed');
                        this.showManualTokenEntry();
                    }
                } catch (error) {
                    console.error('Verification error:', error);
                    this.showError('Network error occurred. Please try again.');
                    this.showManualTokenEntry();
                }
            }
            
            async verifyManualToken() {
                const token = document.getElementById('tokenInput').value.trim();
                const email = document.getElementById('resendEmail').value.trim();
                
                if (!token) {
                    this.showAlert('Please enter your verification code', 'error');
                    return;
                }
                
                if (token.length !== 8) {
                    this.showAlert('Verification code must be 8 characters', 'error');
                    return;
                }
                
                this.setButtonLoading('verifyTokenBtn', true);
                
                try {
                    const response = await fetch('../api/verify_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            token: token,
                            email: email || this.email
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showSuccess('Email verified successfully!', 'Your account is now active. You can log in to start managing your budget.');
                    } else {
                        this.showAlert(data.message || 'Verification failed. Please check your code and try again.', 'error');
                    }
                } catch (error) {
                    console.error('Verification error:', error);
                    this.showAlert('Network error occurred. Please try again.', 'error');
                } finally {
                    this.setButtonLoading('verifyTokenBtn', false);
                }
            }
            
            async resendVerification() {
                const email = document.getElementById('resendEmail').value.trim();
                
                if (!email) {
                    this.showAlert('Please enter your email address', 'error');
                    return;
                }
                
                if (!this.isValidEmail(email)) {
                    this.showAlert('Please enter a valid email address', 'error');
                    return;
                }
                
                this.setButtonLoading('resendBtn', true);
                
                try {
                    const response = await fetch('../api/resend_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ email: email })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('Verification email sent! Please check your inbox.', 'success');
                    } else {
                        this.showAlert(data.message || 'Failed to resend email. Please try again.', 'error');
                    }
                } catch (error) {
                    console.error('Resend error:', error);
                    this.showAlert('Network error occurred. Please try again.', 'error');
                } finally {
                    this.setButtonLoading('resendBtn', false);
                }
            }
            
            showManualTokenEntry() {
                this.updateStatus('Manual Verification Required', 'Please enter the 8-character verification code from your email.', 'loading');
                document.getElementById('manualVerificationForm').style.display = 'block';
                document.getElementById('resendSection').style.display = 'block';
                
                // Pre-fill email if available
                if (this.email) {
                    document.getElementById('resendEmail').value = this.email;
                }
                
                // Focus on token input
                setTimeout(() => {
                    document.getElementById('tokenInput').focus();
                }, 100);
            }
            
            showSuccess(title, message) {
                this.updateStatus(title, message, 'success');
                document.getElementById('manualVerificationForm').style.display = 'none';
                document.getElementById('resendSection').style.display = 'none';
                document.getElementById('actionButtons').style.display = 'flex';
            }
            
            showError(message) {
                this.updateStatus('Verification Failed', message, 'error');
            }
            
            updateStatus(title, message, type) {
                const statusIcon = document.getElementById('statusIcon');
                const statusTitle = document.getElementById('statusTitle');
                const statusMessage = document.getElementById('statusMessage');
                
                // Update icon
                statusIcon.className = `status-icon ${type}`;
                let iconClass = 'fas fa-spinner';
                if (type === 'success') iconClass = 'fas fa-check';
                else if (type === 'error') iconClass = 'fas fa-times';
                
                statusIcon.innerHTML = `<i class="${iconClass}"></i>`;
                
                // Update text
                statusTitle.textContent = title;
                statusMessage.textContent = message;
            }
            
            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const iconClass = type === 'success' ? 'fa-check-circle' : 
                                 type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
                
                alertContainer.innerHTML = `
                    <div class="alert alert-${type}">
                        <i class="fas ${iconClass} alert-icon"></i>
                        <span>${message}</span>
                    </div>
                `;
            }
            
            setButtonLoading(buttonId, loading) {
                const button = document.getElementById(buttonId);
                button.disabled = loading;
                
                if (loading) {
                    button.classList.add('btn-loading');
                } else {
                    button.classList.remove('btn-loading');
                }
            }
            
            isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
        }
        
        // Initialize the verification handler when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new EmailVerificationHandler();
        });
    </script>
</body>
</html>
