// Complete Authentication JavaScript for Nkansah Budget Manager
// Supports both login and multi-step signup

// Global variables
let currentStep = 1;
const totalSteps = 3;

// Demo credentials
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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAuth();
    checkRememberedUser();
    handleUrlNotifications();
    
    // Initialize signup if on signup page
    if (document.getElementById('signupForm')) {
        initializeSignupForm();
    }
});

// =============================================
// CORE INITIALIZATION
// =============================================

function initializeAuth() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }

    // Forgot password form
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', handleForgotPasswordSubmit);
    }

    setupModalListeners();
    setupInputValidation();
}

function handleUrlNotifications() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');
    
    if (status && message) {
        // Close any open modals first
        const modal = document.getElementById('forgotPasswordModal');
        if (modal && modal.style.display === 'block') {
            closeForgotPassword();
        }
        
        showNotification(decodeURIComponent(message), status);
        
        // Clean URL without reloading
        const url = new URL(window.location);
        url.searchParams.delete('status');
        url.searchParams.delete('message');
        window.history.replaceState({}, document.title, url);
    }
}

// =============================================
// LOGIN FUNCTIONALITY
// =============================================

function handleLoginSubmit(e) {
    const submitBtn = e.target.querySelector('.submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    btnText.textContent = 'Signing In...';
    loadingOverlay.classList.add('show');
}

function checkRememberedUser() {
    const rememberedUser = localStorage.getItem('rememberedUser');
    if (rememberedUser) {
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.value = rememberedUser;
            const rememberCheckbox = document.getElementById('rememberMe');
            if (rememberCheckbox) {
                rememberCheckbox.checked = true;
            }
        }
    }
}

function fillDemoCredentials(type) {
    const credentials = demoCredentials[type];
    if (credentials) {
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        
        if (usernameField && passwordField) {
            usernameField.value = credentials.username;
            passwordField.value = credentials.password;
            
            // Clear any errors
            clearFieldError(usernameField);
            clearFieldError(passwordField);
            
            const dashboardType = type === 'family' ? 'Family Dashboard' : 'Personal Dashboard';
            showNotification(`Demo credentials filled for ${dashboardType}`, 'success');
        }
    }
}

function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordField && toggleIcon) {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.textContent = '🙈';
        } else {
            passwordField.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    }
}

// =============================================
// FORGOT PASSWORD FUNCTIONALITY
// =============================================

function handleForgotPasswordSubmit(e) {
    e.preventDefault();

    const identifier = document.getElementById('resetIdentifier').value.trim();
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Clear previous errors
    document.querySelectorAll('#forgotPasswordModal .field-error').forEach(error => error.remove());

    if (!identifier || !newPassword || !confirmPassword) {
        showNotification('Please fill in all required fields.', 'error');
        return;
    }

    if (!isValidEmail(identifier) && !isValidUsername(identifier)) {
        const identifierField = document.getElementById('resetIdentifier');
        showFieldError(identifierField, 'Please enter a valid email or username.');
        return;
    }

    if (newPassword.length < 6) {
        const newPasswordField = document.getElementById('newPassword');
        showFieldError(newPasswordField, 'New password must be at least 6 characters.');
        return;
    }

    if (newPassword !== confirmPassword) {
        const confirmPasswordField = document.getElementById('confirmPassword');
        showFieldError(confirmPasswordField, 'Passwords do not match.');
        return;
    }

    const submitBtn = e.target.querySelector('.btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Resetting Password...';
    submitBtn.disabled = true;

    e.target.classList.add('loading');
    e.target.submit();
}

function showForgotPassword() {
    const modal = document.getElementById('forgotPasswordModal');
    if (!modal) return;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    setTimeout(() => {
        const identifierField = document.getElementById('resetIdentifier');
        if (identifierField) {
            identifierField.focus();
        }
    }, 100);
}

function closeForgotPassword() {
    const modal = document.getElementById('forgotPasswordModal');
    if (!modal) return;
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    const form = document.getElementById('forgotPasswordForm');
    if (form) {
        form.reset();
        form.classList.remove('loading');
        
        // Clear all errors
        form.querySelectorAll('.field-error').forEach(error => error.remove());
        form.querySelectorAll('input').forEach(input => {
            input.classList.remove('error');
            const formGroup = input.closest('.form-group');
            if (formGroup) {
                formGroup.classList.remove('has-error');
            }
        });
        
        const submitBtn = form.querySelector('.btn-primary');
        if (submitBtn) {
            submitBtn.textContent = 'Reset Password';
            submitBtn.disabled = false;
        }
    }
}

// =============================================
// MULTI-STEP SIGNUP FUNCTIONALITY
// =============================================

function initializeSignupForm() {
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', handleSignupSubmit);
    }

    setupSignupValidation();
    setupUsernameCheck();
    setupPasswordStrength();
    setupPasswordMatching();
    updateNavigationButtons();
}

function handleSignupSubmit(e) {
    e.preventDefault();
    
    if (!validateAllSteps()) {
        showNotification('Please complete all required fields correctly.', 'error');
        return;
    }
    
    const submitBtn = document.querySelector('.submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    btnText.textContent = 'Creating Account...';
    loadingOverlay.classList.add('show');
    
    e.target.submit();
}

// Step navigation
function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
            
            currentStep++;
            document.getElementById(`step${currentStep}`).classList.add('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            
            updateNavigationButtons();
            focusFirstInput();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        document.getElementById(`step${currentStep}`).classList.remove('active');
        document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
        
        currentStep--;
        document.getElementById(`step${currentStep}`).classList.add('active');
        document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
        
        updateNavigationButtons();
        focusFirstInput();
    }
}

function updateNavigationButtons() {
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const submitBtn = document.querySelector('.submit-btn');
    
    if (!prevBtn || !nextBtn || !submitBtn) return;
    
    if (currentStep === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'inline-block';
    }
    
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }
}

function focusFirstInput() {
    const currentStepElement = document.getElementById(`step${currentStep}`);
    if (currentStepElement) {
        const firstInput = currentStepElement.querySelector('input, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

// =============================================
// VALIDATION FUNCTIONS (UNIFIED)
// =============================================

function validateField(field) {
    if (!field) return false;
    
    const formGroup = field.closest('.form-group');
    if (!formGroup) return false;
    
    let isValid = true;
    
    // Remove existing error message
    const existingError = formGroup.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Required field validation
    if (field.hasAttribute('required') && !field.value.trim()) {
        showFieldError(field, 'This field is required');
        isValid = false;
    }
    
    // Specific field validations
    if (field.id === 'username' && field.value.trim().length > 0 && field.value.trim().length < 3) {
        showFieldError(field, 'Username must be at least 3 characters');
        isValid = false;
    }
    
    if ((field.id === 'password' || field.id === 'signupPassword') && field.value.length > 0 && field.value.length < 6) {
        showFieldError(field, 'Password must be at least 6 characters');
        isValid = false;
    }
    
    if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
        showFieldError(field, 'Please enter a valid email address');
        isValid = false;
    }
    
    // Phone number validation
    if ((field.id === 'phoneNumber' || field.id === 'momoNumber') && field.value && !field.value.match(/^\+233\d{9}$/)) {
        const fieldName = field.id === 'phoneNumber' ? 'phone number' : 'MoMo number';
        showFieldError(field, `Please enter a valid ${fieldName} (+233XXXXXXXXX)`);
        isValid = false;
    }
    
    // Age validation for date of birth
    if (field.id === 'dateOfBirth' && field.value) {
        const birthDate = new Date(field.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        if (age < 13) {
            showFieldError(field, 'You must be at least 13 years old');
            isValid = false;
        }
    }
    
    // Monthly contribution validation
    if (field.id === 'monthlyContribution' && field.value && parseFloat(field.value) < 0) {
        showFieldError(field, 'Monthly contribution cannot be negative');
        isValid = false;
    }
    
    // Username format validation for signup
if (field.id === 'username' && field.value) {
    const value = field.value.trim();
    const isEmail = isValidEmail(value);
    const isUsername = isValidUsername(value);

    if (!isEmail && !isUsername) {
        showFieldError(field, 'Enter a valid username (3-20 chars) or email');
        isValid = false;
    }
}

    
    // Password confirmation
    if (field.id === 'confirmPassword') {
        const passwordField = document.getElementById('signupPassword') || document.getElementById('newPassword');
        if (passwordField && field.value && field.value !== passwordField.value) {
            showFieldError(field, 'Passwords do not match');
            isValid = false;
        }
    }
    
    // Update field state
    if (isValid) {
        field.classList.remove('error');
        formGroup.classList.remove('has-error');
    } else {
        field.classList.add('error');
        formGroup.classList.add('has-error');
    }
    
    return isValid;
}

function showFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    // Remove any existing error
    const existingError = formGroup.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Create error element
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    // Add error below the input wrapper
    formGroup.appendChild(errorElement);
    
    // Add error state to field and form group
    field.classList.add('error');
    formGroup.classList.add('has-error');
}

function clearFieldError(field) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    const existingError = formGroup.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    field.classList.remove('error');
    formGroup.classList.remove('has-error');
}

function validateCurrentStep() {
    const currentStepElement = document.getElementById(`step${currentStep}`);
    if (!currentStepElement) return false;
    
    const inputs = currentStepElement.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateAllSteps() {
    let isValid = true;
    
    for (let step = 1; step <= totalSteps; step++) {
        const stepElement = document.getElementById(`step${step}`);
        if (stepElement) {
            const inputs = stepElement.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
        }
    }
    
    // Check terms agreement for step 3
    const termsField = document.getElementById('agreeTerms');
    if (termsField && !termsField.checked) {
        showNotification('You must agree to the Terms of Service and Privacy Policy', 'error');
        isValid = false;
    }
    
    return isValid;
}

// =============================================
// INPUT VALIDATION SETUP
// =============================================

function setupInputValidation() {
    const allInputs = document.querySelectorAll('input, select');
    
    allInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
}

function setupSignupValidation() {
    // This is now handled by setupInputValidation()
    setupInputValidation();
}

// =============================================
// SIGNUP ENHANCED FEATURES
// =============================================

function setupUsernameCheck() {
    const usernameField = document.getElementById('username');
    const usernameStatus = document.getElementById('usernameStatus');
    
    if (!usernameField || !usernameStatus) return;
    
    let checkTimeout;
    
    usernameField.addEventListener('input', function() {
        clearTimeout(checkTimeout);
        const username = this.value.trim();
        
        if (username.length >= 3) {
            checkTimeout = setTimeout(() => {
                usernameStatus.innerHTML = '<span class="checking">Checking availability...</span>';
                // You can implement AJAX username checking here
            }, 500);
        } else {
            usernameStatus.innerHTML = '';
        }
    });
}

function setupPasswordStrength() {
    const passwordField = document.getElementById('signupPassword');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    if (!passwordField || !strengthFill || !strengthText) return;
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        strengthFill.style.width = strength.percentage + '%';
        strengthFill.className = 'strength-fill ' + strength.class;
        strengthText.textContent = strength.text;
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score += 25;
    else if (password.length >= 6) score += 10;
    
    if (/[a-z]/.test(password)) score += 15;
    if (/[A-Z]/.test(password)) score += 15;
    if (/[0-9]/.test(password)) score += 15;
    if (/[^A-Za-z0-9]/.test(password)) score += 25;
    
    if (password.length >= 12) score += 5;
    
    let strength;
    if (score < 30) {
        strength = { percentage: score, class: 'weak', text: 'Weak password' };
    } else if (score < 60) {
        strength = { percentage: score, class: 'medium', text: 'Medium strength' };
    } else if (score < 90) {
        strength = { percentage: score, class: 'strong', text: 'Strong password' };
    } else {
        strength = { percentage: 100, class: 'very-strong', text: 'Very strong!' };
    }
    
    return strength;
}

function setupPasswordMatching() {
    const passwordField = document.getElementById('signupPassword');
    const confirmField = document.getElementById('confirmPassword');
    const matchIndicator = document.getElementById('passwordMatch');
    
    if (!passwordField || !confirmField || !matchIndicator) return;
    
    function checkPasswordMatch() {
        if (confirmField.value.length > 0) {
            if (passwordField.value === confirmField.value) {
                matchIndicator.innerHTML = '<span class="match-success">✓ Passwords match</span>';
                clearFieldError(confirmField);
            } else {
                matchIndicator.innerHTML = '<span class="match-error">✗ Passwords do not match</span>';
                showFieldError(confirmField, 'Passwords do not match');
            }
        } else {
            matchIndicator.innerHTML = '';
            clearFieldError(confirmField);
        }
    }
    
    passwordField.addEventListener('input', checkPasswordMatch);
    confirmField.addEventListener('input', checkPasswordMatch);
}

function toggleSignupPassword() {
    const passwordField = document.getElementById('signupPassword');
    const toggleIcon = document.getElementById('signupPasswordToggleIcon');
    
    if (!passwordField || !toggleIcon) return;
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordField.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// =============================================
// MODAL & EVENT LISTENERS
// =============================================

function setupModalListeners() {
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('forgotPasswordModal');
        if (e.target === modal) {
            closeForgotPassword();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('forgotPasswordModal');
            if (modal && modal.style.display === 'block') {
                closeForgotPassword();
            }
        }
    });
}

// =============================================
// NOTIFICATION SYSTEM
// =============================================

function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const messageElement = document.getElementById('notificationMessage');
    
    if (!notification || !messageElement) {
        console.error('Notification elements not found');
        return;
    }
    
    if (notification.hideTimeout) {
        clearTimeout(notification.hideTimeout);
    }
    
    messageElement.textContent = message;
    notification.className = `notification show ${type}`;
    
    notification.style.display = 'flex';
    notification.style.right = '20px';
    
    notification.hideTimeout = setTimeout(() => {
        closeNotification();
    }, 6000);
}

function closeNotification() {
    const notification = document.getElementById('notification');
    
    if (!notification) return;
    
    if (notification.hideTimeout) {
        clearTimeout(notification.hideTimeout);
        delete notification.hideTimeout;
    }
    
    notification.style.right = '-100%';
    
    setTimeout(() => {
        notification.classList.remove('show');
        notification.style.display = 'none';
        notification.style.right = '20px';
    }, 300);
}

// =============================================
// UTILITY FUNCTIONS
// =============================================

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidUsername(username) {
    const usernameRegex = /^[a-zA-Z0-9_-]{3,20}$/;
    return usernameRegex.test(username);
}

function rememberUser(username) {
    const rememberCheckbox = document.getElementById('rememberMe');
    if (rememberCheckbox && rememberCheckbox.checked) {
        localStorage.setItem('rememberedUser', username);
    } else {
        localStorage.removeItem('rememberedUser');
    }
}