/**
 * Session Timeout Monitor
 * Monitors session status and warns users before timeout
 */

class SessionTimeoutMonitor {
    constructor(options = {}) {
        this.options = {
            checkInterval: 60000, // Check every minute
            warningTime: 300, // Warn when 5 minutes left
            sessionUrl: '../includes/session_timeout_middleware.php',
            loginUrl: '../login',
            ...options
        };
        
        this.isWarningShown = false;
        this.checkInterval = null;
        this.warningDialog = null;
        
        this.init();
    }
    
    init() {
        // Start monitoring
        this.startMonitoring();
        
        // Monitor user activity
        this.trackUserActivity();
        
        // Create warning dialog
        this.createWarningDialog();
    }
    
    startMonitoring() {
        // Initial check
        this.checkSession();
        
        // Set up periodic checks
        this.checkInterval = setInterval(() => {
            this.checkSession();
        }, this.options.checkInterval);
        
    }
    
    stopMonitoring() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }
    
    async checkSession() {
        try {
            const response = await fetch(this.options.sessionUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.session_valid) {
                this.handleSessionExpired(data);
            } else {
                this.handleActiveSession(data);
            }
            
        } catch (error) {
            console.error('Session check failed:', error);
            // Continue monitoring even if check fails
        }
    }
    
    handleSessionExpired(data) {
        this.stopMonitoring();
        
        if (data.reason === 'timeout') {
            this.showExpiredMessage();
        } else {
            this.redirectToLogin('Session expired. Please log in again.');
        }
    }
    
    handleActiveSession(data) {
        const timeRemaining = data.time_remaining || 0;
        const timeRemainingMinutes = data.time_remaining_minutes || 0;
        
        // Show warning if close to timeout
        if (timeRemaining <= this.options.warningTime && !this.isWarningShown) {
            this.showTimeoutWarning(timeRemainingMinutes);
        }
        
        // Hide warning if time increased (user activity)
        if (timeRemaining > this.options.warningTime && this.isWarningShown) {
            this.hideTimeoutWarning();
        }
    }
    
    showTimeoutWarning(minutesLeft) {
        this.isWarningShown = true;
        
        const warningDialog = this.warningDialog;
        const minutesSpan = warningDialog.querySelector('.minutes-left');
        const extendBtn = warningDialog.querySelector('.extend-session-btn');
        const logoutBtn = warningDialog.querySelector('.logout-btn');
        
        minutesSpan.textContent = Math.ceil(minutesLeft);
        
        // Remove existing event listeners
        const newExtendBtn = extendBtn.cloneNode(true);
        const newLogoutBtn = logoutBtn.cloneNode(true);
        extendBtn.parentNode.replaceChild(newExtendBtn, extendBtn);
        logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);
        
        // Add new event listeners
        newExtendBtn.addEventListener('click', () => this.extendSession());
        newLogoutBtn.addEventListener('click', () => this.logout());
        
        warningDialog.style.display = 'flex';
        
    }
    
    hideTimeoutWarning() {
        this.isWarningShown = false;
        this.warningDialog.style.display = 'none';
    }
    
    showExpiredMessage() {
        // Replace warning with expired message
        const warningDialog = this.warningDialog;
        warningDialog.innerHTML = `
            <div class="session-dialog-content">
                <div class="session-dialog-header">
                    <h3>Session Expired</h3>
                </div>
                <div class="session-dialog-body">
                    <p>Your session has expired due to inactivity. You will be redirected to the login page.</p>
                </div>
                <div class="session-dialog-footer">
                    <button class="btn btn-primary login-btn">Go to Login</button>
                </div>
            </div>
        `;
        
        const loginBtn = warningDialog.querySelector('.login-btn');
        loginBtn.addEventListener('click', () => this.redirectToLogin());
        
        warningDialog.style.display = 'flex';
        
        // Auto redirect after 5 seconds
        setTimeout(() => {
            this.redirectToLogin('Session expired due to inactivity');
        }, 5000);
    }
    
    async extendSession() {
        try {
            // Make a request to any endpoint to refresh session
            const response = await fetch('../api/ping.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'ping' })
            });
            
            this.hideTimeoutWarning();
            this.showSnackbar('Session extended successfully', 'success');
            
        } catch (error) {
            console.error('Failed to extend session:', error);
            this.showSnackbar('Failed to extend session. Please refresh the page.', 'error');
        }
    }
    
    logout() {
        window.location.href = '../actions/signout.php';
    }
    
    redirectToLogin(message = '') {
        const url = message ? 
            `${this.options.loginUrl}?status=warning&message=${encodeURIComponent(message)}` : 
            this.options.loginUrl;
        window.location.href = url;
    }
    
    trackUserActivity() {
        // Track various user activities to reset session
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        let activityTimeout;
        
        const resetActivityTimer = () => {
            clearTimeout(activityTimeout);
            activityTimeout = setTimeout(() => {
                // User has been inactive for a while, check session more frequently
                if (this.checkInterval) {
                    clearInterval(this.checkInterval);
                    this.checkInterval = setInterval(() => {
                        this.checkSession();
                    }, 30000); // Check every 30 seconds when inactive
                }
            }, 300000); // 5 minutes of inactivity
        };
        
        events.forEach(event => {
            document.addEventListener(event, resetActivityTimer, true);
        });
    }
    
    createWarningDialog() {
        const dialog = document.createElement('div');
        dialog.className = 'session-timeout-dialog';
        dialog.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            font-family: Arial, sans-serif;
        `;
        
        dialog.innerHTML = `
            <div class="session-dialog-content" style="
                background: white;
                padding: 20px;
                border-radius: 8px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            ">
                <div class="session-dialog-header" style="margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #f39c12;">⚠️ Session Timeout Warning</h3>
                </div>
                <div class="session-dialog-body" style="margin-bottom: 20px;">
                    <p style="margin: 0 0 10px 0;">Your session will expire in <span class="minutes-left" style="font-weight: bold; color: #e74c3c;">5</span> minutes due to inactivity.</p>
                    <p style="margin: 0; color: #666;">Would you like to extend your session?</p>
                </div>
                <div class="session-dialog-footer" style="display: flex; gap: 10px; justify-content: center;">
                    <button class="extend-session-btn" style="
                        background: #27ae60;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    ">Extend Session</button>
                    <button class="logout-btn" style="
                        background: #e74c3c;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    ">Logout Now</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
        this.warningDialog = dialog;
    }
    
    showSnackbar(message, type = 'info') {
        // Use existing snackbar function if available
        if (typeof showSnackbar === 'function') {
            showSnackbar(message, type);
        } else {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
                color: white;
                padding: 10px 20px;
                border-radius: 4px;
                z-index: 10001;
                font-size: 14px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 3000);
        }
    }
}

// Initialize session monitor when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on protected pages (not login/signup)
    const isProtectedPage = !window.location.pathname.includes('/login') && 
                           !window.location.pathname.includes('/sign-up') &&
                           !window.location.pathname.includes('/register');
    
    if (isProtectedPage) {
        window.sessionMonitor = new SessionTimeoutMonitor();
    }
});

// Export for manual initialization
window.SessionTimeoutMonitor = SessionTimeoutMonitor;
