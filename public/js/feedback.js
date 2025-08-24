// ===================================
// FEEDBACK PAGE JAVASCRIPT
// ===================================

class FeedbackManager {
    constructor() {
        this.form = document.getElementById('feedbackForm');
        this.stars = document.querySelectorAll('#starRating i');
        this.ratingInput = document.getElementById('rating');
        this.pageUrlInput = document.getElementById('pageUrl');
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupStarRating();
        this.prefillPageUrl();
        this.collectBrowserInfo();
    }
    
    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Auto-resize textarea
        const textarea = document.getElementById('message');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        
        // Form validation
        const requiredFields = this.form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
        });
    }
    
    setupStarRating() {
        this.stars.forEach((star, index) => {
            star.addEventListener('click', () => this.setRating(index + 1));
            star.addEventListener('mouseenter', () => this.highlightStars(index + 1));
        });
        
        const starContainer = document.getElementById('starRating');
        starContainer.addEventListener('mouseleave', () => this.resetStarHighlight());
    }
    
    setRating(rating) {
        this.ratingInput.value = rating;
        this.updateStarDisplay(rating);
        
        const ratingText = document.querySelector('.rating-text');
        const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        ratingText.textContent = ratingLabels[rating];
    }
    
    highlightStars(rating) {
        this.stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas');
                star.style.color = '#fbbf24';
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
                star.style.color = '#d1d5db';
            }
        });
    }
    
    updateStarDisplay(rating) {
        this.stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas', 'active');
            } else {
                star.classList.remove('fas', 'active');
                star.classList.add('far');
            }
        });
    }
    
    resetStarHighlight() {
        const currentRating = parseInt(this.ratingInput.value) || 0;
        this.updateStarDisplay(currentRating);
    }
    
    prefillPageUrl() {
        // Get the referring page URL
        const referrer = document.referrer;
        if (referrer && !this.pageUrlInput.value) {
            this.pageUrlInput.value = referrer;
        }
    }
    
    collectBrowserInfo() {
        this.browserInfo = {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            screenResolution: `${screen.width}x${screen.height}`,
            timestamp: new Date().toISOString()
        };
    }
    
    validateField(field) {
        const value = field.value.trim();
        const isValid = field.checkValidity();
        
        // Remove existing validation classes
        field.classList.remove('valid', 'invalid');
        
        if (value && isValid) {
            field.classList.add('valid');
        } else if (value && !isValid) {
            field.classList.add('invalid');
        }
        
        return isValid;
    }
    
    validateForm() {
        const requiredFields = this.form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    async handleSubmit(event) {
        event.preventDefault();
        
        if (!this.validateForm()) {
            this.showAlert('Please fill in all required fields correctly.', 'error');
            return;
        }
        
        this.setFormLoading(true);
        
        try {
            const formData = new FormData(this.form);
            formData.append('browser_info', JSON.stringify(this.browserInfo));
            
            const response = await fetch('../api/feedback_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Thank you for your feedback! We\'ll review it and get back to you if needed.', 'success');
                this.resetForm();
            } else {
                throw new Error(result.message || 'Failed to submit feedback');
            }
            
        } catch (error) {
            console.error('Error submitting feedback:', error);
            this.showAlert('Sorry, there was an error submitting your feedback. Please try again.', 'error');
        } finally {
            this.setFormLoading(false);
        }
    }
    
    setFormLoading(loading) {
        const submitButton = this.form.querySelector('button[type="submit"]');
        const formContainer = document.querySelector('.form-container');
        
        if (loading) {
            formContainer.classList.add('form-loading');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        } else {
            formContainer.classList.remove('form-loading');
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Feedback';
        }
    }
    
    showAlert(message, type) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
        `;
        
        // Insert at the top of the form container
        const formContainer = document.querySelector('.form-container');
        formContainer.insertBefore(alert, formContainer.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    resetForm() {
        this.form.reset();
        this.ratingInput.value = '';
        this.updateStarDisplay(0);
        
        const ratingText = document.querySelector('.rating-text');
        ratingText.textContent = 'Click to rate';
        
        // Remove validation classes
        const fields = this.form.querySelectorAll('.valid, .invalid');
        fields.forEach(field => {
            field.classList.remove('valid', 'invalid');
        });
        
        // Reset textarea height
        const textarea = document.getElementById('message');
        textarea.style.height = 'auto';
    }
}

// Global reset function
function resetForm() {
    if (window.feedbackManager) {
        window.feedbackManager.resetForm();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.feedbackManager = new FeedbackManager();
    
    // Add CSS for validation states
    const style = document.createElement('style');
    style.textContent = `
        .form-group input.valid,
        .form-group select.valid,
        .form-group textarea.valid {
            border-color: var(--success-color);
        }
        
        .form-group input.invalid,
        .form-group select.invalid,
        .form-group textarea.invalid {
            border-color: var(--error-color);
        }
        
        .form-group input.valid:focus,
        .form-group select.valid:focus,
        .form-group textarea.valid:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .form-group input.invalid:focus,
        .form-group select.invalid:focus,
        .form-group textarea.invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
    `;
    document.head.appendChild(style);
});
