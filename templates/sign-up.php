<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family Fund - Sign Up</title>
    <link rel="stylesheet" href="../public/css/auth.css">
    <link rel="stylesheet" href="../public/css/signup.css">
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
    <div class="auth-container signup-container">
        <div class="auth-form">
            <form id="signupForm" class="signup-form" action="../actions/register.php" method="POST">
                <!-- Step 1: Account Type Selection -->
                <div class="form-step active" id="step1">
                    <div class="step-header">
                        <h3>Choose Account Type</h3>
                        <p class="step-counter">Step 1 of 3</p>
                    </div>

                    <div class="account-type-selection">
                        <div class="account-type-card" data-type="family">
                            <div class="card-icon">👨‍👩‍👧‍👦</div>
                            <h4>Family Account</h4>
                        </div>
                        
                        <div class="account-type-card" data-type="personal">
                            <div class="card-icon">👤</div>
                            <h4>Personal Account</h4>
                        </div>
                    </div>
                    
                    <input type="hidden" id="accountType" name="accountType" required>
                    <div class="account-type-error" id="accountTypeError" style="display: none; color: #e74c3c; text-align: center; margin-top: 15px;">
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
                                <span class="input-icon">👤</span>
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
                                <span class="input-icon">👤</span>
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
                            <span class="input-icon">📧</span>
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
                                <span class="input-icon">📱</span>
                                <input type="tel" 
                                       id="phoneNumber" 
                                       name="phoneNumber" 
                                       class="phone-input"
                                       placeholder="+233 XX XXX XXXX"
                                       maxlength="16"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth</label>
                            <div class="input-wrapper">
                                <span class="input-icon">📅</span>
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
                                <span class="input-icon">💰</span>
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
                                    <span class="input-icon">🏦</span>
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
                                    <span class="input-icon">📱</span>
                                    <input type="tel" 
                                           id="momoNumber" 
                                           name="momoNumber" 
                                           class="phone-input"
                                           placeholder="+233 XX XXX XXXX"
                                           maxlength="16">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal account specific fields -->
                    <div id="personalFields" style="display: none;">
                        <div class="form-group">
                            <label for="monthlySalary">Monthly Income (₵)</label>
                            <div class="input-wrapper">
                                <span class="input-icon">💼</span>
                                <input type="number" 
                                       id="monthlySalary" 
                                       name="monthlySalary" 
                                       placeholder="0.00"
                                       min="0"
                                       step="0.01">
                            </div>
                            <small class="field-help">Optional: Enter your monthly income to help with budget planning</small>
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
                            <span class="input-icon">👤</span>
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
                            <span class="input-icon">🔒</span>
                            <input type="password" 
                                   id="signupPassword" 
                                   name="password" 
                                   placeholder="Create a strong password"
                                   minlength="6"
                                   required>
                            <button type="button" class="password-toggle" onclick="toggleSignupPassword()">
                                <span id="signupPasswordToggleIcon">👁️</span>
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
                            <span class="input-icon">🔒</span>
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
                            <span class="checkmark"></span>
                            <span class="checkbox-label">
                                I agree to the <a href="#" class="terms-link">Terms of Service</a> and 
                                <a href="#" class="terms-link">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="progress-step active" data-step="1">1</div>
                    <div class="progress-line" id="progressLine1"></div>
                    <div class="progress-step" data-step="2">2</div>
                    <div class="progress-line" id="progressLine2"></div>
                    <div class="progress-step" data-step="3">3</div>
                </div>

                <!-- Form Navigation -->
                <div class="form-navigation">
                    <button type="button" class="nav-btn prev-btn" onclick="previousStep()" style="display: none;">
                        ← Previous
                    </button>
                    <button type="button" class="nav-btn next-btn" onclick="nextStep()">
                        Next →
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

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Creating your account...</p>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification" style="display: none;">
        <span id="notificationMessage"></span>
        <button class="notification-close" onclick="closeNotification()">&times;</button>
    </div>



    <script src="../public/js/signup.js"></script>
</body>
</html>