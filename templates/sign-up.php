<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Budgetly</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .signup-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            min-height: 700px;
        }

        /* Left Side - Branding */
        .brand-section {
            flex: 1;
            background: #2563eb;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }

        .brand-logo i {
            font-size: 2.5rem;
            color: white;
        }

        .brand-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
            max-width: 300px;
        }

        .features {
            list-style: none;
            text-align: left;
        }

        .features li {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            font-size: 1rem;
            opacity: 0.9;
        }

        .features li i {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 0.8rem;
        }

        /* Right Side - Form */
        .form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 700px;
            overflow-y: auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .progress-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }

        .progress-step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: #9ca3af;
            transition: all 0.3s ease;
        }

        .progress-step.active {
            background: #2563eb;
            color: white;
        }

        .progress-step.completed {
            background: #10b981;
            color: white;
        }

        .progress-line {
            width: 40px;
            height: 2px;
            background: #e5e7eb;
            margin: 0 8px;
            transition: all 0.3s ease;
        }

        .progress-line.active {
            background: #2563eb;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .step-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .step-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .step-counter {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* Account Type Cards */
        .account-type-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .account-type-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .account-type-card:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .account-type-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: all 0.3s ease;
        }

        .account-type-card.selected .card-icon {
            background: #2563eb;
            color: white;
        }

        .card-icon i {
            font-size: 1.5rem;
            color: #6b7280;
        }

        .account-type-card.selected .card-icon i {
            color: white;
        }

        .account-type-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }

        /* Form Elements */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 1;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            color: #1f2937;
        }

        /* Enhanced styling for phone inputs */
        .phone-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }

        .phone-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group input:focus + .input-icon,
        .form-group select:focus + .input-icon {
            color: #2563eb;
        }

        .field-help {
            display: block;
            margin-top: 4px;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 4px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #2563eb;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-fill.weak {
            background: #ef4444;
        }

        .strength-fill.medium {
            background: #f59e0b;
        }

        .strength-fill.strong {
            background: #10b981;
        }

        .strength-text {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .username-status,
        .password-match {
            margin-top: 4px;
            font-size: 0.85rem;
        }

        .status-success {
            color: #10b981;
        }

        .status-error {
            color: #ef4444;
        }

        /* Checkbox */
        .checkbox-wrapper {
            display: flex;
            align-items: flex-start;
            cursor: pointer;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            margin-top: 2px;
            accent-color: #2563eb;
        }

        .terms-link {
            color: #2563eb;
            text-decoration: none;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        /* Navigation Buttons */
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .nav-btn,
        .submit-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
        }

        .prev-btn {
            background: #f9fafb;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .prev-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .next-btn,
        .submit-btn {
            background: #2563eb;
            color: white;
        }

        .next-btn:hover,
        .submit-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-loader {
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .login-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        /* Error Messages */
        .account-type-error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
            display: none;
            border: 1px solid #fecaca;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification.error {
            background: #ef4444;
        }

        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .signup-container {
                flex-direction: column;
                max-width: 100%;
                margin: 10px;
                border-radius: 12px;
            }

            .brand-section {
                padding: 40px 30px;
                text-align: center;
            }

            .brand-title {
                font-size: 2rem;
            }

            .form-section {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .account-type-selection {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .signup-container {
                margin: 0;
                border-radius: 8px;
            }

            .brand-section {
                padding: 30px 20px;
            }

            .form-section {
                padding: 20px 15px;
            }

            .form-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->

    <div class="signup-container">
        <!-- Left Side - Branding -->
        <div class="brand-section">
            <div class="brand-logo">
                <i class="fas fa-wallet"></i>
            </div>
            <h1 class="brand-title">Budgetly</h1>
            <p class="brand-subtitle">Take control of your finances with our comprehensive budgeting platform</p>
            <ul class="features">
                <li><i class="fas fa-check"></i>Track expenses effortlessly</li>
                <li><i class="fas fa-check"></i>Set and achieve financial goals</li>
                <li><i class="fas fa-check"></i>Family budget management</li>
                <li><i class="fas fa-check"></i>Real-time financial insights</li>
            </ul>
        </div>

        <!-- Right Side - Form -->
        <div class="form-section">
            <div class="form-header">
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Join thousands who are already managing their finances better</p>
            </div>

            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-step active" data-step="1">1</div>
                <div class="progress-line" id="progressLine1"></div>
                <div class="progress-step" data-step="2">2</div>
                <div class="progress-line" id="progressLine2"></div>
                <div class="progress-step" data-step="3">3</div>
            </div>

            <form id="signupForm" class="signup-form" action="../actions/register.php" method="POST">
                <!-- Step 1: Account Type Selection -->
                <div class="form-step active" id="step1">
                    <div class="step-header">
                        <h3>Choose Account Type</h3>
                        <p class="step-counter">Step 1 of 3</p>
                    </div>

                    <div class="account-type-selection">
                        <div class="account-type-card" data-type="family">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4>Family Account</h4>
                        </div>
                        
                        <div class="account-type-card" data-type="personal">
                            <div class="card-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4>Personal Account</h4>
                        </div>
                    </div>
                    
                    <input type="hidden" id="accountType" name="accountType" required>
                    <div class="account-type-error" id="accountTypeError">
                        Please select an account type to continue
                    </div>
                </div>

                <!-- Step 2: Personal Information -->
                <div class="form-step" id="step2">
                    <div class="step-header">
                        <h3>Personal Information</h3>
                        <p class="step-counter">Step 2 of 3</p>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" 
                                       id="firstName" 
                                       name="firstName" 
                                       placeholder="First name"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" 
                                       id="lastName" 
                                       name="lastName" 
                                       placeholder="Last name"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Enter your email"
                                   required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phoneNumber">Phone Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" 
                                       id="phoneNumber" 
                                       name="phoneNumber" 
                                       class="phone-input"
                                       placeholder="+233 XX XXX XXXX"
                                       maxlength="16"
                                       required>
                            </div>
                            <small class="field-help">Ghana phone number format</small>
                        </div>

                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth</label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar input-icon"></i>
                                <input type="date" 
                                       id="dateOfBirth" 
                                       name="dateOfBirth" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Family-specific fields (shown only for family accounts) -->
                    <div id="familyFields" style="display: none;">
                        <div class="form-group">
                            <label for="monthlyContribution">Monthly Contribution Goal (₵)</label>
                            <div class="input-wrapper">
                                <i class="fas fa-coins input-icon"></i>
                                <input type="number" 
                                       id="monthlyContribution" 
                                       name="monthlyContribution" 
                                       placeholder="0.00"
                                       min="0"
                                       step="0.01">
                            </div>
                            <small class="field-help">Optional: Set your monthly contribution goal to the family fund</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="momoNetwork">MoMo Network *</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-building input-icon"></i>
                                    <select id="momoNetwork" name="momoNetwork">
                                        <option value="">Select network</option>
                                        <option value="mtn">MTN Mobile Money</option>
                                        <option value="vodafone">Vodafone Cash</option>
                                        <option value="airteltigo">AirtelTigo Money</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="momoNumber">MoMo Number *</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-mobile-alt input-icon"></i>
                                    <input type="tel" 
                                           id="momoNumber" 
                                           name="momoNumber" 
                                           class="phone-input"
                                           placeholder="+233 XX XXX XXXX"
                                           maxlength="16">
                                </div>
                                <small class="field-help">Ghana mobile money number</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Account Security -->
                <div class="form-step" id="step3">
                    <div class="step-header">
                        <h3>Account Security</h3>
                        <p class="step-counter">Step 3 of 3</p>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-at input-icon"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Choose a username"
                                   minlength="3"
                                   maxlength="20"
                                   required>
                        </div>
                        <div class="username-status" id="usernameStatus"></div>
                    </div>

                    <div class="form-group">
                        <label for="signupPassword">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="signupPassword" 
                                   name="password" 
                                   placeholder="Create a strong password"
                                   minlength="6"
                                   required>
                            <button type="button" class="password-toggle" onclick="toggleSignupPassword()">
                                <i class="fas fa-eye" id="signupPasswordToggleIcon"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Enter a password</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="confirmPassword" 
                                   name="confirmPassword" 
                                   placeholder="Confirm your password"
                                   required>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                            <span class="checkbox-label">
                                I agree to the <a href="#" class="terms-link">Terms of Service</a> and 
                                <a href="#" class="terms-link">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Form Navigation -->
                <div class="form-navigation">
                    <button type="button" class="nav-btn prev-btn" onclick="previousStep()" style="display: none;">
                        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Previous
                    </button>
                    <button type="button" class="nav-btn next-btn" onclick="nextStep()">
                        Next<i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                    <button type="submit" class="submit-btn" style="display: none;">
                        <span class="btn-text">Create Account</span>
                        <span class="btn-loader" id="signupLoader"></span>
                    </button>
                </div>
            </form>

            <div class="form-footer">
                <p>Already have an account? 
                    <a href="login" class="login-link">Sign In</a>
                </p>
            </div>
        </div>
    </div>


    <!-- Notification -->
    <div id="notification" class="notification" style="display: none;">
        <span id="notificationMessage"></span>
        <button class="notification-close" onclick="closeNotification()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <script src="../public/js/loading.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        // Account type selection
        document.querySelectorAll('.account-type-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selection from all cards
                document.querySelectorAll('.account-type-card').forEach(c => c.classList.remove('selected'));
                
                // Add selection to clicked card
                this.classList.add('selected');
                
                // Set hidden input value
                document.getElementById('accountType').value = this.dataset.type;
                
                // Hide error message
                document.getElementById('accountTypeError').style.display = 'none';
                
                // Show/hide family fields
                const familyFields = document.getElementById('familyFields');
                if (this.dataset.type === 'family') {
                    familyFields.style.display = 'block';
                } else {
                    familyFields.style.display = 'none';
                }
            });
        });

        function nextStep() {
            if (currentStep === 1) {
                // Validate account type selection
                const accountType = document.getElementById('accountType').value;
                if (!accountType) {
                    document.getElementById('accountTypeError').style.display = 'block';
                    return;
                }
            }
            
            if (currentStep === 2) {
                // Validate required fields for step 2
                const requiredFields = ['firstName', 'lastName', 'email', 'phoneNumber', 'dateOfBirth'];
                let isValid = true;
                
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        field.style.borderColor = '#ef4444';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#d1d5db';
                    }
                });
                
                // Validate phone number format and length
                const phoneNumber = document.getElementById('phoneNumber');
                const phoneDigits = phoneNumber.value.replace(/\D/g, '');
                if (phoneDigits.length !== 12 || !phoneDigits.startsWith('233')) { // 233 + 9 digits = 12 total
                    phoneNumber.style.borderColor = '#ef4444';
                    showSnackbar('Please enter a valid Ghana phone number with 9 digits', 'error');
                    isValid = false;
                } else {
                    phoneNumber.style.borderColor = '#10b981';
                }
                
                // Validate family-specific fields if family account
                const accountType = document.getElementById('accountType').value;
                if (accountType === 'family') {
                    const momoNetwork = document.getElementById('momoNetwork');
                    const momoNumber = document.getElementById('momoNumber');
                    
                    if (!momoNetwork.value) {
                        momoNetwork.style.borderColor = '#ef4444';
                        isValid = false;
                    } else {
                        momoNetwork.style.borderColor = '#d1d5db';
                    }
                    
                    if (!momoNumber.value.trim()) {
                        momoNumber.style.borderColor = '#ef4444';
                        isValid = false;
                    } else {
                        // Validate MoMo number format and length
                        const momoDigits = momoNumber.value.replace(/\D/g, '');
                        if (momoDigits.length !== 12 || !momoDigits.startsWith('233')) {
                            momoNumber.style.borderColor = '#ef4444';
                            showSnackbar('Please enter a valid Ghana mobile money number with 9 digits', 'error');
                            isValid = false;
                        } else {
                            momoNumber.style.borderColor = '#10b981';
                        }
                    }
                }
                
                if (!isValid) return;
            }
            
            if (currentStep === 3) {
                // Validate step 3 fields
                const requiredFields = ['username', 'signupPassword', 'confirmPassword'];
                let isValid = true;
                
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        field.style.borderColor = '#ef4444';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#d1d5db';
                    }
                });
                
                // Check password match
                const password = document.getElementById('signupPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                if (password !== confirmPassword) {
                    document.getElementById('confirmPassword').style.borderColor = '#ef4444';
                    isValid = false;
                }
                
                // Check terms agreement
                const agreeTerms = document.getElementById('agreeTerms');
                if (!agreeTerms.checked) {
                    isValid = false;
                }
                
                if (!isValid) return;
            }

            if (currentStep < totalSteps) {
                // Hide current step
                document.getElementById(`step${currentStep}`).classList.remove('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('completed');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
                
                // Update progress line
                if (currentStep < totalSteps) {
                    document.getElementById(`progressLine${currentStep}`).classList.add('active');
                }
                
                // Move to next step
                currentStep++;
                
                // Show next step
                document.getElementById(`step${currentStep}`).classList.add('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
                
                // Update navigation buttons
                updateNavigationButtons();
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                // Hide current step
                document.getElementById(`step${currentStep}`).classList.remove('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
                
                // Move to previous step
                currentStep--;
                
                // Show previous step
                document.getElementById(`step${currentStep}`).classList.add('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('completed');
                
                // Update progress line
                if (currentStep < totalSteps) {
                    document.getElementById(`progressLine${currentStep}`).classList.remove('active');
                }
                
                // Update navigation buttons
                updateNavigationButtons();
            }
        }

        function updateNavigationButtons() {
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');
            const submitBtn = document.querySelector('.submit-btn');

            // Show/hide previous button
            if (currentStep === 1) {
                prevBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
            }

            // Show/hide next/submit buttons
            if (currentStep === totalSteps) {
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'flex';
            } else {
                nextBtn.style.display = 'flex';
                submitBtn.style.display = 'none';
            }
        }

        // Password visibility toggle
        function toggleSignupPassword() {
            const passwordInput = document.getElementById('signupPassword');
            const toggleIcon = document.getElementById('signupPasswordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        document.getElementById('signupPassword').addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let strengthLabel = '';
            
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/)) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            
            if (strength === 0) {
                strengthLabel = 'Enter a password';
                strengthFill.className = 'strength-fill';
            } else if (strength <= 50) {
                strengthLabel = 'Weak password';
                strengthFill.className = 'strength-fill weak';
            } else if (strength <= 75) {
                strengthLabel = 'Medium password';
                strengthFill.className = 'strength-fill medium';
            } else {
                strengthLabel = 'Strong password';
                strengthFill.className = 'strength-fill strong';
            }
            
            strengthFill.style.width = strength + '%';
            strengthText.textContent = strengthLabel;
        });

        // Password match checker
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = this.value;
            const matchElement = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchElement.textContent = '';
                matchElement.className = 'password-match';
            } else if (password === confirmPassword) {
                matchElement.textContent = 'Passwords match';
                matchElement.className = 'password-match status-success';
                this.style.borderColor = '#10b981';
            } else {
                matchElement.textContent = 'Passwords do not match';
                matchElement.className = 'password-match status-error';
                this.style.borderColor = '#ef4444';
            }
        });

        // Username availability checker (placeholder)
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const statusElement = document.getElementById('usernameStatus');
            
            if (username.length < 3) {
                statusElement.textContent = 'Username must be at least 3 characters';
                statusElement.className = 'username-status status-error';
            } else {
                statusElement.textContent = 'Username looks good';
                statusElement.className = 'username-status status-success';
            }
        });

        // Enhanced phone number formatting for Ghana numbers
        document.querySelectorAll('.phone-input').forEach(input => {
            // Set initial value
            if (!input.value) {
                input.value = '+233 ';
            }
            
            input.addEventListener('focus', function() {
                // Ensure +233 is always present when focused
                if (!this.value.startsWith('+233')) {
                    this.value = '+233 ';
                }
                // Position cursor after +233 space
                setTimeout(() => {
                    this.setSelectionRange(5, 5);
                }, 0);
            });
            
            input.addEventListener('input', function(e) {
                let value = this.value;
                
                // Always ensure it starts with +233
                if (!value.startsWith('+233')) {
                    value = '+233 ' + value.replace(/^\+?233\s?/, '');
                }
                
                // Remove all non-digits after +233
                let phoneDigits = value.substring(5).replace(/\D/g, '');
                
                // Limit to 9 digits maximum
                if (phoneDigits.length > 9) {
                    phoneDigits = phoneDigits.substring(0, 9);
                }
                
                // Format the number: +233 XX XXX XXXX
                let formattedNumber = '+233 ';
                if (phoneDigits.length > 0) {
                    formattedNumber += phoneDigits.substring(0, 2);
                    if (phoneDigits.length > 2) {
                        formattedNumber += ' ' + phoneDigits.substring(2, 5);
                        if (phoneDigits.length > 5) {
                            formattedNumber += ' ' + phoneDigits.substring(5, 9);
                        }
                    }
                }
                
                this.value = formattedNumber;
                
                // Prevent cursor from moving to beginning
                const cursorPos = this.value.length;
                setTimeout(() => {
                    this.setSelectionRange(cursorPos, cursorPos);
                }, 0);
            });
            
            input.addEventListener('keydown', function(e) {
                const cursorPos = this.selectionStart;
                
                // Prevent deleting +233 prefix
                if ((e.key === 'Backspace' || e.key === 'Delete') && cursorPos <= 5) {
                    e.preventDefault();
                    return false;
                }
                
                // Prevent typing before +233
                if (cursorPos < 5 && e.key.length === 1) {
                    e.preventDefault();
                    return false;
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                
                let pastedText = (e.clipboardData || window.clipboardData).getData('text');
                let phoneDigits = pastedText.replace(/\D/g, '');
                
                // Handle different paste formats
                if (phoneDigits.startsWith('233')) {
                    phoneDigits = phoneDigits.substring(3);
                } else if (phoneDigits.startsWith('0')) {
                    phoneDigits = phoneDigits.substring(1);
                }
                
                // Limit to 9 digits
                phoneDigits = phoneDigits.substring(0, 9);
                
                // Format the number
                let formattedNumber = '+233 ';
                if (phoneDigits.length > 0) {
                    formattedNumber += phoneDigits.substring(0, 2);
                    if (phoneDigits.length > 2) {
                        formattedNumber += ' ' + phoneDigits.substring(2, 5);
                        if (phoneDigits.length > 5) {
                            formattedNumber += ' ' + phoneDigits.substring(5, 9);
                        }
                    }
                }
                
                this.value = formattedNumber;
            });
        });

        // Form submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Sign-up form submitted');
            
            // Show unified loading screen
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
            }
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit to server
            fetch('../actions/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                try {
                    // Hide unified loading screen
                    if (window.budgetlyLoader) {
                        window.budgetlyLoader.hide();
                    }
                    
                    if (data.success) {
                        // Show success notification
                        showSnackbar('Account created successfully! Redirecting to login...', 'success');
                        
                        // Redirect based on server response or default to login
                        setTimeout(() => {
                            window.location.href = data.redirect || 'login';
                        }, 2000);
                    } else {
                        // Show error message
                        showSnackbar(data.message || 'Registration failed. Please try again.', 'error');
                        
                        // Handle field-specific errors if provided
                        if (data.data && data.data.errors) {
                            Object.keys(data.data.errors).forEach(fieldName => {
                                const field = document.getElementById(fieldName);
                                if (field) {
                                    field.style.borderColor = '#ef4444';
                                    // You could also display field-specific error messages here
                                }
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error processing response:', error);
                    // Ensure loading screen is hidden even on error
                    if (window.budgetlyLoader) {
                        window.budgetlyLoader.hide();
                    }
                    showSnackbar('An error occurred. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                
                // Hide loading screen
                if (window.budgetlyLoader) {
                    window.budgetlyLoader.hide();
                }
                
                showSnackbar('Network error. Please check your connection and try again.', 'error');
            });
        });

        // Snackbar notification function (from personal dashboard)
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

        // Legacy function for compatibility
        function showNotification(message, type = 'success') {
            showSnackbar(message, type);
        }

        function closeNotification() {
            const existingSnackbar = document.querySelector('.snackbar');
            if (existingSnackbar) {
                existingSnackbar.remove();
            }
        }

        // Initialize the form
        updateNavigationButtons();

        // Initialize phone number fields with +233 prefix
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial +233 prefix for all phone inputs
            document.querySelectorAll('.phone-input').forEach(input => {
                if (!input.value) {
                    input.value = '+233 ';
                }
            });
        });

        // Initialize loading screen when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sign-up: DOMContentLoaded fired');
            console.log('Sign-up: LoadingScreen available?', typeof window.LoadingScreen);
            
            if (window.LoadingScreen) {
                console.log('Sign-up: Creating LoadingScreen');
                window.budgetlyLoader = new LoadingScreen();
                console.log('Sign-up: LoadingScreen created', window.budgetlyLoader);
                
                // Customize the loading message for sign-up
                const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
                if (loadingMessage) {
                    loadingMessage.innerHTML = 'Creating your account<span class="loading-dots-text">...</span>';
                    console.log('Sign-up: Loading message customized');
                } else {
                    console.error('Sign-up: Could not find loading message element');
                }
                
                // Ensure it's hidden initially
                window.budgetlyLoader.hide();
            } else {
                console.error('Sign-up: LoadingScreen class not available');
            }
        });

        // Test function for loading screen (can be called from browser console)
        window.testSignupLoadingScreen = function(duration = 3000) {
            if (window.budgetlyLoader) {
                console.log('Testing signup loading screen for', duration, 'ms');
                window.budgetlyLoader.show();
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                    console.log('Signup loading screen test complete');
                }, duration);
            } else {
                console.log('Loading screen not available');
            }
        };

        // Test function for snackbar (can be called from browser console)
        window.testSignupSnackbar = function(message = 'Test notification', type = 'success') {
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