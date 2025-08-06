// Fixed signup JavaScript with comprehensive debugging
let currentStep = 1;
const totalSteps = 3;
let usernameCheckTimeout;
let selectedAccountType = null;
let debugMode = true;

// Debug function
function debugLog(message, data = null) {
    if (debugMode) {
        console.log(`[SIGNUP DEBUG] ${message}`, data || '');
        const debugInfo = document.getElementById('debugInfo');
        if (debugInfo) {
            const timestamp = new Date().toLocaleTimeString();
            debugInfo.innerHTML += `<br>[${timestamp}] ${message}`;
            if (data) {
                debugInfo.innerHTML += `<br>&nbsp;&nbsp;Data: ${JSON.stringify(data)}`;
            }
        }
    }
}

function toggleDebug() {
    const debugPanel = document.getElementById('debugPanel');
    debugPanel.style.display = debugPanel.style.display === 'none' ? 'block' : 'none';
}

// Initialize when DOM is loaded 
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM loaded, initializing signup form');
    initializeSignupForm();
});

function initializeSignupForm() {
    debugLog('Starting form initialization');
    
    try {
        setupAccountTypeSelection();
        setupInputValidation();
        setupPhoneFormatting();
        setupUsernameCheck();
        setupPasswordStrength();
        setupPasswordMatching();
        updateNavigationButtons();
        updateProgressIndicator();
        setupKeyboardNavigation();
        loadFormData();
        
        debugLog('Form initialization completed successfully');
    } catch (error) {
        debugLog('Error during form initialization', error.message);
        console.error('Form initialization error:', error);
    }
}

// Account type selection
function setupAccountTypeSelection() {
    debugLog('Setting up account type selection');
    
    const accountTypeCards = document.querySelectorAll('.account-type-card');
    const accountTypeInput = document.getElementById('accountType');
    
    if (!accountTypeCards.length) {
        debugLog('ERROR: No account type cards found');
        return;
    }
    
    if (!accountTypeInput) {
        debugLog('ERROR: Account type input not found');
        return;
    }
    
    accountTypeCards.forEach((card, index) => {
        debugLog(`Setting up card ${index + 1}: ${card.dataset.type}`);
        
        card.addEventListener('click', function() {
            debugLog(`Account type card clicked: ${this.dataset.type}`);
            
            // Remove selected class from all cards
            accountTypeCards.forEach(c => c.classList.remove('selected'));
            
            // Add selected class to clicked card
            this.classList.add('selected');
            
            // Set the account type value
            selectedAccountType = this.dataset.type;
            accountTypeInput.value = selectedAccountType;
            
            debugLog(`Account type set to: ${selectedAccountType}`);
            
            // Hide error message
            const errorElement = document.getElementById('accountTypeError');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            
            // Show/hide relevant fields on step 2
            updateFieldVisibility();
        });
    });
    
    debugLog(`Account type selection setup complete. Found ${accountTypeCards.length} cards`);
}

function updateFieldVisibility() {
    debugLog(`Updating field visibility for account type: ${selectedAccountType}`);
    
    const familyFields = document.getElementById('familyFields');
    const personalFields = document.getElementById('personalFields');
    
    if (!familyFields || !personalFields) {
        debugLog('ERROR: Family or personal fields containers not found');
        return;
    }
    
    if (selectedAccountType === 'family') {
        familyFields.style.display = 'block';
        personalFields.style.display = 'none';
        
        debugLog('Showing family fields, hiding personal fields');
        
        // Set required status for family fields
        const momoNetwork = document.getElementById('momoNetwork');
        const momoNumber = document.getElementById('momoNumber');
        
        if (momoNetwork) momoNetwork.required = true;
        if (momoNumber) momoNumber.required = true;
        
        // Remove required from personal fields
        const monthlySalary = document.getElementById('monthlySalary');
        if (monthlySalary) monthlySalary.required = false;
        
    } else if (selectedAccountType === 'personal') {
        familyFields.style.display = 'none';
        personalFields.style.display = 'block';
        
        debugLog('Showing personal fields, hiding family fields');
        
        // Remove required from family fields
        const momoNetwork = document.getElementById('momoNetwork');
        const momoNumber = document.getElementById('momoNumber');
        
        if (momoNetwork) momoNetwork.required = false;
        if (momoNumber) momoNumber.required = false;
        
        // Personal salary is optional
        const monthlySalary = document.getElementById('monthlySalary');
        if (monthlySalary) monthlySalary.required = false;
    }
}

// Form submission
function handleSignupSubmit(e) {
    debugLog('Form submission started');
    e.preventDefault();
    
    if (!validateAllSteps()) {
        debugLog('Form validation failed');
        showNotification('Please complete all required fields correctly.', 'error');
        return;
    }
    
    debugLog('Form validation passed, proceeding with submission');
    
    const submitBtn = document.querySelector('.submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Show loading state
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    btnText.textContent = 'Creating Account...';
    loadingOverlay.classList.add('show');
    
    // Prepare form data
    const form = document.getElementById('signupForm');
    const formData = new FormData(form);
    
    // Log form data for debugging
    const formDataObj = {};
    for (let [key, value] of formData.entries()) {
        if (key !== 'password' && key !== 'confirmPassword') {
            formDataObj[key] = value;
        }
    }
    debugLog('Form data being submitted', formDataObj);
    
    // Remove fields that shouldn't be sent based on account type
    if (selectedAccountType === 'personal') {
        formData.delete('monthlyContribution');
        formData.delete('momoNetwork');
        formData.delete('momoNumber');
        debugLog('Removed family-specific fields for personal account');
    } else if (selectedAccountType === 'family') {
        formData.delete('monthlySalary');
        debugLog('Removed personal-specific fields for family account');
    }
    
    // Submit to server
    debugLog(`Submitting to: ${form.action}`);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        debugLog(`Server response status: ${response.status}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        debugLog(`Response content type: ${contentType}`);
        
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                debugLog('Non-JSON response received', text.substring(0, 500));
                throw new Error('Server returned non-JSON response.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        debugLog('Server response data', data);
        
        // Reset loading state
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        btnText.textContent = 'Create Account';
        loadingOverlay.classList.remove('show');
        
        if (data.success) {
            debugLog('Registration successful');
            showNotification(data.message || 'Account created successfully!', 'success');
            sessionStorage.removeItem('signupFormData');
            
            setTimeout(() => {
                if (data.redirect) {
                    debugLog(`Redirecting to: ${data.redirect}`);
                    window.location.href = data.redirect;
                } else {
                    const defaultRedirect = selectedAccountType === 'family' 
                        ? '../family/dashboard' 
                        : '../personal/dashboard';
                    debugLog(`Redirecting to default: ${defaultRedirect}`);
                    window.location.href = defaultRedirect;
                }
            }, 2000);
        } else {
            debugLog('Registration failed', data.message);
            showNotification(data.message || 'Registration failed. Please try again.', 'error');
            
            if (data.data && data.data.errors) {
                debugLog('Field errors received', data.data.errors);
                Object.keys(data.data.errors).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        showFieldError(field, data.data.errors[fieldName]);
                    }
                });
            }
        }
    })
    .catch(error => {
        debugLog('Registration error', error.message);
        console.error('Registration error:', error);
        
        // Reset loading state
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        btnText.textContent = 'Create Account';
        loadingOverlay.classList.remove('show');
        
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Step navigation
function nextStep() {
    debugLog(`Attempting to move from step ${currentStep} to ${currentStep + 1}`);
    
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            // Mark current step as completed
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('completed');
            const progressLine = document.getElementById(`progressLine${currentStep}`);
            if (progressLine) {
                progressLine.classList.add('completed');
            }
            
            // Hide current step
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
            
            // Show next step
            currentStep++;
            document.getElementById(`step${currentStep}`).classList.add('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            
            debugLog(`Successfully moved to step ${currentStep}`);
            
            updateNavigationButtons();
            updateProgressIndicator();
            focusFirstInput();
            saveFormData();
        }
    } else {
        debugLog(`Step ${currentStep} validation failed`);
    }
}

function previousStep() {
    debugLog(`Moving back from step ${currentStep} to ${currentStep - 1}`);
    
    if (currentStep > 1) {
        // Hide current step
        document.getElementById(`step${currentStep}`).classList.remove('active');
        document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
        
        // Show previous step
        currentStep--;
        document.getElementById(`step${currentStep}`).classList.add('active');
        document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
        
        updateNavigationButtons();
        updateProgressIndicator();
        focusFirstInput();
    }
}

function updateNavigationButtons() {
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const submitBtn = document.querySelector('.submit-btn');
    
    if (!prevBtn || !nextBtn || !submitBtn) {
        debugLog('ERROR: Navigation buttons not found');
        return;
    }
    
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
    
    debugLog(`Navigation buttons updated for step ${currentStep}`);
}

function updateProgressIndicator() {
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
        } else if (index + 1 < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else {
            step.classList.remove('active', 'completed');
        }
    });
    
    debugLog(`Progress indicator updated for step ${currentStep}`);
}

function focusFirstInput() {
    const currentStepElement = document.getElementById(`step${currentStep}`);
    if (currentStepElement) {
        const firstInput = currentStepElement.querySelector('input:not([type="checkbox"]):not([type="hidden"]), select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

// Validation functions
function validateCurrentStep() {
    debugLog(`Validating step ${currentStep}`);
    
    if (currentStep === 1) {
        // Validate account type selection
        if (!selectedAccountType) {
            debugLog('Account type not selected');
            document.getElementById('accountTypeError').style.display = 'block';
            return false;
        }
        debugLog('Step 1 validation passed');
        return true;
    }
    
    const currentStepElement = document.getElementById(`step${currentStep}`);
    if (!currentStepElement) {
        debugLog(`ERROR: Step ${currentStep} element not found`);
        return false;
    }
    
    // Get only visible required inputs for validation
    const inputs = currentStepElement.querySelectorAll('input, select');
    let isValid = true;
    let validatedCount = 0;
    
    inputs.forEach(input => {
        // Skip hidden inputs
        if (input.type === 'hidden') return;
        
        // Check if field is in a hidden container
        const parentDiv = input.closest('#familyFields, #personalFields');
        if (parentDiv && window.getComputedStyle(parentDiv).display === 'none') {
            return; // Skip validation for hidden fields
        }
        
        // Only validate required fields that are visible
        if (input.required) {
            validatedCount++;
            if (!validateField(input)) {
                debugLog(`Field validation failed: ${input.name || input.id}`);
                isValid = false;
            }
        }
    });
    
    debugLog(`Step ${currentStep} validation result: ${isValid} (validated ${validatedCount} fields)`);
    return isValid;
}

function validateAllSteps() {
    debugLog('Validating all steps');
    let isValid = true;
    
    // Check account type
    if (!selectedAccountType) {
        debugLog('Account type validation failed');
        isValid = false;
    }
    
    // Check all required fields across all steps
    for (let step = 1; step <= totalSteps; step++) {
        const stepElement = document.getElementById(`step${step}`);
        if (stepElement) {
            const inputs = stepElement.querySelectorAll('input, select');
            inputs.forEach(input => {
                // Skip hidden inputs
                if (input.type === 'hidden') return;
                
                // Skip fields that are in hidden containers based on account type
                const parentDiv = input.closest('#familyFields, #personalFields');
                if (parentDiv && window.getComputedStyle(parentDiv).display === 'none') {
                    return; // Skip validation for hidden fields
                }
                
                // Only validate required fields
                if (input.required && !validateField(input)) {
                    debugLog(`All steps validation failed at field: ${input.name || input.id}`);
                    isValid = false;
                }
            });
        }
    }
    
    // Check terms agreement
    const termsField = document.getElementById('agreeTerms');
    if (termsField && !termsField.checked) {
        showNotification('You must agree to the Terms of Service and Privacy Policy', 'error');
        debugLog('Terms agreement validation failed');
        isValid = false;
    }
    
    debugLog(`All steps validation result: ${isValid}`);
    return isValid;
}

function validateField(field) {
    if (!field) return false;
    
    const formGroup = field.closest('.form-group');
    if (!formGroup) return false;
    
    let isValid = true;
    let errorMessage = '';
    
    // Skip validation for non-required fields that are empty
    if (!field.hasAttribute('required') && !field.value.trim()) {
        return true;
    }
    
    // Required field validation
    if (field.hasAttribute('required') && !field.value.trim()) {
        errorMessage = 'This field is required';
        isValid = false;
    }
    
    // Specific field validations
    if (isValid && field.value.trim()) {
        switch (field.id) {
            case 'email':
                if (!isValidEmail(field.value)) {
                    errorMessage = 'Please enter a valid email address';
                    isValid = false;
                }
                break;
            
            case 'phoneNumber':
            case 'momoNumber':
                if (!field.value.match(/^\+233\s\d{2}\s\d{3}\s\d{4}$/)) {
                    errorMessage = 'Please enter a valid phone number (+233 XX XXX XXXX)';
                    isValid = false;
                }
                break;
            
            case 'dateOfBirth':
                const birthDate = new Date(field.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                if (age < 13) {
                    errorMessage = 'You must be at least 13 years old';
                    isValid = false;
                }
                break;
            
            case 'username':
                if (field.value.length < 3) {
                    errorMessage = 'Username must be at least 3 characters';
                    isValid = false;
                } else if (!field.value.match(/^[a-zA-Z0-9_-]{3,20}$/)) {
                    errorMessage = 'Username can only contain letters, numbers, underscores, and hyphens';
                    isValid = false;
                }
                break;
            
            case 'signupPassword':
                if (field.value.length < 6) {
                    errorMessage = 'Password must be at least 6 characters';
                    isValid = false;
                }
                break;
            
            case 'confirmPassword':
                const passwordField = document.getElementById('signupPassword');
                if (passwordField && field.value !== passwordField.value) {
                    errorMessage = 'Passwords do not match';
                    isValid = false;
                }
                break;
                
            case 'monthlyContribution':
                if (selectedAccountType === 'family' && parseFloat(field.value) < 0) {
                    errorMessage = 'Monthly contribution cannot be negative';
                    isValid = false;
                }
                break;
                
            case 'monthlySalary':
                if (selectedAccountType === 'personal' && parseFloat(field.value) < 0) {
                    errorMessage = 'Monthly salary cannot be negative';
                    isValid = false;
                }
                break;
        }
    }
    
    // Update field state
    if (isValid) {
        clearFieldError(field);
    } else {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    const existingError = formGroup.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.style.color = '#e74c3c';
    errorElement.style.fontSize = '12px';
    errorElement.style.marginTop = '5px';
    
    const inputWrapper = formGroup.querySelector('.input-wrapper');
    const usernameStatus = formGroup.querySelector('.username-status');
    const passwordStrength = formGroup.querySelector('.password-strength');
    const passwordMatch = formGroup.querySelector('.password-match');
    
    if (passwordMatch) {
        passwordMatch.parentNode.insertBefore(errorElement, passwordMatch.nextSibling);
    } else if (passwordStrength) {
        passwordStrength.parentNode.insertBefore(errorElement, passwordStrength.nextSibling);
    } else if (usernameStatus) {
        usernameStatus.parentNode.insertBefore(errorElement, usernameStatus.nextSibling);
    } else if (inputWrapper) {
        inputWrapper.parentNode.insertBefore(errorElement, inputWrapper.nextSibling);
    } else {
        formGroup.appendChild(errorElement);
    }
    
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

// Phone formatting
function setupPhoneFormatting() {
    debugLog('Setting up phone formatting');
    
    const phoneInputs = document.querySelectorAll('.phone-input');
    
    phoneInputs.forEach((input, index) => {
        debugLog(`Setting up phone input ${index + 1}: ${input.id}`);
        
        if (!input.value.startsWith('+233')) {
            input.value = '+233 ';
        }
        
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d+\s]/g, '');
            
            if (!value.startsWith('+233')) {
                value = '+233 ';
            }
            
            if (value.length > 5) {
                const digits = value.slice(4).replace(/\s/g, '');
                if (digits.length <= 2) {
                    value = '+233 ' + digits;
                } else if (digits.length <= 5) {
                    value = '+233 ' + digits.slice(0, 2) + ' ' + digits.slice(2);
                } else {
                    value = '+233 ' + digits.slice(0, 2) + ' ' + digits.slice(2, 5) + ' ' + digits.slice(5, 9);
                }
            }
            
            e.target.value = value;
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && e.target.selectionStart <= 5) {
                e.preventDefault();
                e.target.value = '+233 ';
                e.target.setSelectionRange(5, 5);
            }
        });
        
        input.addEventListener('focus', function(e) {
            if (e.target.value === '+233 ') {
                e.target.setSelectionRange(5, 5);
            }
        });
    });
    
    debugLog(`Phone formatting setup complete for ${phoneInputs.length} inputs`);
}

// Username check
function setupUsernameCheck() {
    debugLog('Setting up username availability check');
    
    const usernameField = document.getElementById('username');
    const usernameStatus = document.getElementById('usernameStatus');
    
    if (!usernameField || !usernameStatus) {
        debugLog('ERROR: Username field or status element not found');
        return;
    }
    
    usernameField.addEventListener('input', function() {
        clearTimeout(usernameCheckTimeout);
        const username = this.value.trim();
        
        usernameStatus.innerHTML = '';
        
        if (username.length >= 3 && username.match(/^[a-zA-Z0-9_-]{3,20}$/)) {
            usernameStatus.innerHTML = '<span class="checking">⏳ Checking availability...</span>';
            
            usernameCheckTimeout = setTimeout(() => {
                checkUsernameAvailability(username);
            }, 800);
        } else if (username.length > 0) {
            usernameStatus.innerHTML = '<span class="unavailable">❌ Username must be 3-20 characters (letters, numbers, _, -)</span>';
        }
    });
    
    debugLog('Username check setup complete');
}

function checkUsernameAvailability(username) {
    debugLog(`Checking username availability: ${username}`);
    
    const usernameStatus = document.getElementById('usernameStatus');
    
    // Try to check with server first
    fetch('../../actions/check_username.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ username: username })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        debugLog('Username check response', data);
        
        if (data.available) {
            usernameStatus.innerHTML = '<span class="available">✅ Username is available!</span>';
        } else {
            usernameStatus.innerHTML = '<span class="unavailable">❌ Username is already taken</span>';
        }
    })
    .catch(error => {
        debugLog('Username check failed, using fallback', error.message);
        
        // Fallback to client-side check
        const reservedUsernames = ['admin', 'test', 'user123', 'family', 'root', 'administrator', 'nkansah', 'system'];
        const isAvailable = !reservedUsernames.includes(username.toLowerCase());
        
        if (isAvailable) {
            usernameStatus.innerHTML = '<span class="available">✅ Username appears available!</span>';
        } else {
            usernameStatus.innerHTML = '<span class="unavailable">❌ Username is reserved</span>';
        }
    });
}

// Password strength
function setupPasswordStrength() {
    debugLog('Setting up password strength indicator');
    
    const passwordField = document.getElementById('signupPassword');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    if (!passwordField || !strengthFill || !strengthText) {
        debugLog('ERROR: Password strength elements not found');
        return;
    }
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        strengthFill.style.width = strength.percentage + '%';
        strengthFill.className = 'strength-fill ' + strength.class;
        strengthText.textContent = strength.text;
        
        debugLog(`Password strength: ${strength.text} (${strength.percentage}%)`);
    });
    
    debugLog('Password strength setup complete');
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score += 25;
    else if (password.length >= 6) score += 15;
    else if (password.length >= 4) score += 10;
    
    if (/[a-z]/.test(password)) score += 15;
    if (/[A-Z]/.test(password)) score += 15;
    if (/[0-9]/.test(password)) score += 15;
    if (/[^A-Za-z0-9]/.test(password)) score += 20;
    
    if (password.length >= 12) score += 10;
    
    let strength;
    if (score < 30) {
        strength = { percentage: Math.max(score, 10), class: 'weak', text: 'Weak password' };
    } else if (score < 60) {
        strength = { percentage: score, class: 'medium', text: 'Medium strength' };
    } else if (score < 90) {
        strength = { percentage: score, class: 'strong', text: 'Strong password' };
    } else {
        strength = { percentage: 100, class: 'very-strong', text: 'Very strong!' };
    }
    
    return strength;
}

// Password matching
function setupPasswordMatching() {
    debugLog('Setting up password matching');
    
    const passwordField = document.getElementById('signupPassword');
    const confirmField = document.getElementById('confirmPassword');
    const matchIndicator = document.getElementById('passwordMatch');
    
    if (!passwordField || !confirmField || !matchIndicator) {
        debugLog('ERROR: Password matching elements not found');
        return;
    }
    
    function checkPasswordMatch() {
        if (confirmField.value.length > 0) {
            if (passwordField.value === confirmField.value) {
                matchIndicator.innerHTML = '<span class="match-success">✅ Passwords match</span>';
                clearFieldError(confirmField);
            } else {
                matchIndicator.innerHTML = '<span class="match-error">❌ Passwords do not match</span>';
            }
        } else {
            matchIndicator.innerHTML = '';
        }
    }
    
    passwordField.addEventListener('input', checkPasswordMatch);
    confirmField.addEventListener('input', checkPasswordMatch);
    
    debugLog('Password matching setup complete');
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
    
    debugLog(`Password visibility toggled: ${passwordField.type}`);
}

// Input validation setup
function setupInputValidation() {
    debugLog('Setting up input validation');
    
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', handleSignupSubmit);
        debugLog('Form submit handler attached');
    } else {
        debugLog('ERROR: Signup form not found');
    }
    
    const allInputs = document.querySelectorAll('input, select');
    
    allInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
            saveFormData();
        });
    });
    
    debugLog(`Input validation setup complete for ${allInputs.length} inputs`);
}

// Notifications
function showNotification(message, type = 'info') {
    debugLog(`Showing notification: ${type} - ${message}`);
    
    const notification = document.getElementById('notification');
    const messageElement = document.getElementById('notificationMessage');
    
    if (!notification || !messageElement) {
        debugLog('ERROR: Notification elements not found, using alert');
        alert(message);
        return;
    }
    
    if (notification.hideTimeout) {
        clearTimeout(notification.hideTimeout);
    }
    
    messageElement.textContent = message;
    notification.className = `notification show ${type}`;
    notification.style.display = 'flex';
    
    notification.hideTimeout = setTimeout(() => {
        closeNotification();
    }, 5000);
}

function closeNotification() {
    const notification = document.getElementById('notification');
    
    if (!notification) return;
    
    if (notification.hideTimeout) {
        clearTimeout(notification.hideTimeout);
        delete notification.hideTimeout;
    }
    
    notification.classList.remove('show');
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 300);
}

// Form data persistence
function saveFormData() {
    const formData = {};
    const inputs = document.querySelectorAll('#signupForm input, #signupForm select');
    
    inputs.forEach(input => {
        if (input.type !== 'password' && input.id !== 'confirmPassword') {
            if (input.type === 'checkbox') {
                formData[input.id] = input.checked;
            } else {
                formData[input.id] = input.value;
            }
        }
    });
    
    try {
        sessionStorage.setItem('signupFormData', JSON.stringify(formData));
        debugLog('Form data saved to sessionStorage');
    } catch (e) {
        debugLog('Could not save form data to sessionStorage', e.message);
    }
}

function loadFormData() {
    debugLog('Loading saved form data');
    
    try {
        const savedData = sessionStorage.getItem('signupFormData');
        if (savedData) {
            const formData = JSON.parse(savedData);
            let restoredFields = 0;
            
            Object.keys(formData).forEach(key => {
                const input = document.getElementById(key);
                if (input && formData[key]) {
                    if (input.type === 'checkbox') {
                        input.checked = formData[key];
                    } else {
                        input.value = formData[key];
                    }
                    
                    restoredFields++;
                    
                    // Handle account type selection
                    if (key === 'accountType') {
                        selectedAccountType = formData[key];
                        const card = document.querySelector(`[data-type="${selectedAccountType}"]`);
                        if (card) {
                            card.classList.add('selected');
                        }
                        updateFieldVisibility();
                    }
                }
            });
            
            debugLog(`Restored ${restoredFields} form fields from saved data`);
        } else {
            debugLog('No saved form data found');
        }
    } catch (e) {
        debugLog('Could not load saved form data', e.message);
    }
}

// Keyboard navigation
function setupKeyboardNavigation() {
    debugLog('Setting up keyboard navigation');
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey) {
            const activeElement = document.activeElement;
            
            if (activeElement && activeElement.tagName === 'INPUT' && activeElement.type !== 'submit') {
                e.preventDefault();
                
                if (currentStep < totalSteps) {
                    nextStep();
                } else {
                    const submitBtn = document.querySelector('.submit-btn');
                    if (submitBtn && submitBtn.style.display !== 'none') {
                        document.getElementById('signupForm').dispatchEvent(new Event('submit'));
                    }
                }
            }
        }
    });
    
    debugLog('Keyboard navigation setup complete');
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Cleanup
window.addEventListener('beforeunload', function(e) {
    const submitBtn = document.querySelector('.submit-btn');
    if (submitBtn && submitBtn.disabled) {
        try {
            sessionStorage.removeItem('signupFormData');
            debugLog('Form data cleared on page unload');
        } catch (e) {
            debugLog('Could not clear form data', e.message);
        }
    }
});

// Global functions for HTML onclick handlers
window.nextStep = nextStep;
window.previousStep = previousStep;
window.toggleSignupPassword = toggleSignupPassword;
window.closeNotification = closeNotification;
window.toggleDebug = toggleDebug;