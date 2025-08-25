/**
 * Global Privacy System for Budget Manager - Enhanced Version with CSS-Based Hiding
 * Uses CSS transforms and overlays instead of content replacement to maintain data integrity
 */

class PrivacyManager {
    constructor() {
        this.isInitialized = false;
        this.privacyStatus = null;
        this.figureElements = [];
        this.sessionCheckInterval = null;
        this.figureRefreshInterval = null;
        this.storageKey = 'privacy_manager_state';
        this.init();
        this.addPrivacyStyles();
    }

    addPrivacyStyles() {
        // Add CSS styles for privacy effects
        const styleId = 'privacy-manager-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* Privacy hiding effects */
            .privacy-hidden {
                position: relative !important;
                user-select: none !important;
                pointer-events: none !important;
                transition: all 0.3s ease !important;
            }

            .privacy-hidden::before {
                content: '';
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: linear-gradient(45deg, 
                    rgba(0,0,0,0.8) 25%, 
                    transparent 25%, 
                    transparent 75%, 
                    rgba(0,0,0,0.8) 75%, 
                    rgba(0,0,0,0.8)),
                    linear-gradient(45deg, 
                    rgba(0,0,0,0.8) 25%, 
                    transparent 25%, 
                    transparent 75%, 
                    rgba(0,0,0,0.8) 75%, 
                    rgba(0,0,0,0.8)) !important;
                background-size: 4px 4px !important;
                background-position: 0 0, 2px 2px !important;
                border-radius: 4px !important;
                z-index: 999 !important;
                backdrop-filter: blur(8px) !important;
            }

            .privacy-hidden-blur {
                filter: blur(8px) contrast(0.3) !important;
                opacity: 0.4 !important;
                user-select: none !important;
                pointer-events: none !important;
                transition: all 0.3s ease !important;
            }

            .privacy-hidden-dots::after {
                content: '••••' !important;
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                font-size: 1.2em !important;
                color: #666 !important;
                font-weight: bold !important;
                z-index: 1000 !important;
                background: rgba(255,255,255,0.9) !important;
                padding: 2px 8px !important;
                border-radius: 4px !important;
                letter-spacing: 2px !important;
            }

            .privacy-hidden-overlay {
                position: relative !important;
                overflow: hidden !important;
            }

            .privacy-hidden-overlay::after {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: repeating-linear-gradient(
                    45deg,
                    rgba(0,0,0,0.1) 0px,
                    rgba(0,0,0,0.1) 2px,
                    transparent 2px,
                    transparent 4px
                ) !important;
                z-index: 998 !important;
                pointer-events: none !important;
            }

            /* Privacy toggle styles */
            .privacy-toggle {
                z-index: 9999;
            }

            .privacy-eye-btn {
                background: rgba(0,0,0,0.7);
                border: none;
                color: white;
                padding: 12px;
                border-radius: 50%;
                cursor: pointer;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }

            .privacy-eye-btn:hover {
                background: rgba(0,0,0,0.9);
                transform: scale(1.1);
            }

            .privacy-eye-btn i {
                font-size: 18px;
            }

            /* Modal styles */
            .privacy-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .privacy-modal.show {
                opacity: 1;
            }

            .privacy-modal-content {
                background: white;
                border-radius: 12px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 50px rgba(0,0,0,0.3);
                transform: scale(0.9);
                transition: transform 0.3s ease;
            }

            .privacy-modal.show .privacy-modal-content {
                transform: scale(1);
            }

            .privacy-modal-header {
                padding: 20px 24px 10px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .privacy-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background 0.2s ease;
            }

            .privacy-modal-close:hover {
                background: rgba(0,0,0,0.1);
            }

            .privacy-modal-body {
                padding: 20px 24px 24px;
            }

            .pin-setup-content, .pin-verify-content, .pin-reset-content {
                text-align: center;
            }

            .pin-icon {
                font-size: 48px;
                margin-bottom: 16px;
            }

            .pin-input-group {
                margin: 16px 0;
                text-align: left;
            }

            .pin-input-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #333;
            }

            .pin-input-group input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #ddd;
                border-radius: 8px;
                font-size: 18px;
                text-align: center;
                letter-spacing: 8px;
                transition: border-color 0.3s ease;
            }

            .pin-input-group input:focus {
                outline: none;
                border-color: #007bff;
            }

            .modal-actions {
                display: flex;
                gap: 12px;
                justify-content: center;
                margin-top: 24px;
            }

            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .btn-primary {
                background: #007bff;
                color: white;
            }

            .btn-primary:hover {
                background: #0056b3;
            }

            .btn-secondary {
                background: #6c757d;
                color: white;
            }

            .btn-secondary:hover {
                background: #545b62;
            }

            .btn-link {
                background: none;
                border: none;
                color: #007bff;
                text-decoration: underline;
                cursor: pointer;
                padding: 8px 0;
                margin-top: 16px;
            }

            .pin-error {
                color: #dc3545;
                font-size: 14px;
                margin-top: 8px;
                text-align: center;
            }

            .pin-forgot {
                text-align: center;
            }

            /* Snackbar styles */
            .privacy-snackbar {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: #333;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 10001;
                opacity: 0;
                transition: all 0.3s ease;
            }

            .privacy-snackbar.show {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }

            .privacy-snackbar.success {
                background: #28a745;
            }

            .privacy-snackbar.error {
                background: #dc3545;
            }

            .privacy-snackbar.warning {
                background: #ffc107;
                color: #333;
            }
        `;
        document.head.appendChild(style);
    }

    async init() {
        if (this.isInitialized) return;
        
        try {
            // Load state from both server and local storage for consistency
            await this.loadPrivacyStatus();
            this.setupPrivacyToggle();
            this.scanForFigures();
            this.applyPrivacyState();
            this.startSessionMonitoring();
            this.setupStorageListener();
            this.isInitialized = true;
            
            // Enhanced figure scanning with multiple attempts
            this.startFigureScanning();
            
        } catch (error) {
            console.error('Privacy system initialization failed:', error);
        }
    }

    startFigureScanning() {
        // Initial scan after DOM is ready
        setTimeout(() => {
            this.scanForFigures();
            this.applyPrivacyState();
        }, 500);

        // Additional scans for dynamic content
        setTimeout(() => {
            this.scanForFigures();
            this.applyPrivacyState();
        }, 1500);

        setTimeout(() => {
            this.scanForFigures();
            this.applyPrivacyState();
        }, 3000);

        // Continuous scanning for dynamic content
        this.figureRefreshInterval = setInterval(() => {
            const currentCount = this.figureElements.length;
            this.scanForFigures();
            
            // Only apply if we found new elements
            if (this.figureElements.length !== currentCount) {
                this.applyPrivacyState();
            }
        }, 2000);

        // Scan when page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                setTimeout(() => {
                    this.scanForFigures();
                    this.applyPrivacyState();
                }, 200);
            }
        });

        // Scan on window focus
        window.addEventListener('focus', () => {
            setTimeout(() => {
                this.scanForFigures();
                this.applyPrivacyState();
            }, 200);
        });
    }

    setupStorageListener() {
        // Listen for storage changes from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === this.storageKey) {
                this.handleStorageChange(e.newValue);
            }
        });

        // Custom event for same-page updates
        window.addEventListener('privacyStateChanged', (e) => {
            this.handlePrivacyStateChange(e.detail);
        });
    }

    handleStorageChange(newValue) {
        if (newValue) {
            try {
                const newState = JSON.parse(newValue);
                if (newState.timestamp > (this.privacyStatus?.timestamp || 0)) {
                    this.privacyStatus = newState;
                    this.applyPrivacyState();
                    this.updatePrivacyToggleUI();
                }
            } catch (error) {
                console.error('Error parsing storage state:', error);
            }
        }
    }

    handlePrivacyStateChange(newState) {
        if (newState && newState.timestamp > (this.privacyStatus?.timestamp || 0)) {
            this.privacyStatus = newState;
            this.applyPrivacyState();
            this.updatePrivacyToggleUI();
        }
    }

    saveStateToStorage() {
        try {
            this.privacyStatus.timestamp = Date.now();
            localStorage.setItem(this.storageKey, JSON.stringify(this.privacyStatus));
            
            // Dispatch custom event for same-page instances
            window.dispatchEvent(new CustomEvent('privacyStateChanged', {
                detail: this.privacyStatus
            }));
        } catch (error) {
            console.error('Error saving privacy state:', error);
        }
    }

    startSessionMonitoring() {
        // Check privacy session status every minute
        this.sessionCheckInterval = setInterval(() => {
            this.checkPrivacySession();
        }, 60 * 1000);
        
        // Check on page focus
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkPrivacySession();
            }
        });

        // Check on window focus
        window.addEventListener('focus', () => {
            this.checkPrivacySession();
        });
    }

    async checkPrivacySession() {
        if (!this.privacyStatus?.privacy_enabled) return;
        
        try {
            const response = await fetch('../api/privacy_handler.php?action=get_privacy_session', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                const wasVisible = this.privacyStatus.figures_visible;
                this.privacyStatus.figures_visible = data.figures_visible || false;
                this.privacyStatus.has_session = data.has_session || false;
                this.privacyStatus.session_expires = data.session_expires || null;

                // Save state and update if visibility changed
                this.saveStateToStorage();
                
                if (wasVisible !== this.privacyStatus.figures_visible) {
                    this.applyPrivacyState();
                    this.updatePrivacyToggleUI();
                    
                    if (!this.privacyStatus.figures_visible && wasVisible) {
                        this.showSnackbar('Privacy session expired. Figures are now hidden.', 'warning');
                    }
                }
            }
        } catch (error) {
            console.error('Error checking privacy session:', error);
        }
    }

    async loadPrivacyStatus() {
        try {
            // First try to load from localStorage for instant UI update
            const savedState = localStorage.getItem(this.storageKey);
            if (savedState) {
                try {
                    this.privacyStatus = JSON.parse(savedState);
                } catch (e) {
                    console.warn('Invalid saved privacy state');
                }
            }

            // Then fetch fresh data from server
            const response = await fetch('../api/privacy_handler.php?action=check_privacy_status', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                this.privacyStatus = {
                    ...data,
                    timestamp: Date.now()
                };
                this.saveStateToStorage();
                return data;
            } else {
                throw new Error(data.message || 'Failed to load privacy status');
            }
        } catch (error) {
            console.error('Error loading privacy status:', error);
            // Use saved state or defaults
            if (!this.privacyStatus) {
                this.privacyStatus = {
                    privacy_enabled: false,
                    has_pin: false,
                    figures_visible: true,
                    is_locked: false,
                    has_session: false,
                    timestamp: Date.now()
                };
            }
            return this.privacyStatus;
        }
    }

    setupPrivacyToggle() {
        // Remove existing toggle if any
        const existingToggle = document.getElementById('privacyToggle');
        if (existingToggle) {
            existingToggle.remove();
        }

        let targetContainer = this.findBestToggleContainer();

        if (targetContainer) {
            const privacyToggle = document.createElement('div');
            privacyToggle.id = 'privacyToggle';
            privacyToggle.className = 'privacy-toggle';
            
            const shouldHide = this.privacyStatus?.privacy_enabled && !this.privacyStatus?.figures_visible;
            
            privacyToggle.innerHTML = `
                <button id="privacyEyeBtn" class="privacy-eye-btn" title="${shouldHide ? 'Show figures (requires PIN)' : 'Hide figures'}">
                    <i class="fas ${shouldHide ? 'fa-eye-slash' : 'fa-eye'}"></i>
                </button>
            `;
            
            this.positionToggle(privacyToggle, targetContainer);
            targetContainer.appendChild(privacyToggle);
            
            // Add click event
            document.getElementById('privacyEyeBtn').addEventListener('click', () => {
                this.handlePrivacyToggle();
            });
        }
    }

    findBestToggleContainer() {
        const selectors = [
            '.payday-countdown-hero',
            '.main-header',
            '.navbar',
            '.header',
            '.main-content',
            '.container-fluid',
            '.container',
            'main',
            'body'
        ];

        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (element) {
                return element;
            }
        }
        return document.body;
    }

    positionToggle(toggle, container) {
        if (container.classList.contains('payday-countdown-hero')) {
            toggle.style.position = 'absolute';
            toggle.style.top = '20px';
            toggle.style.right = '20px';
            toggle.style.zIndex = '10';
            container.style.position = 'relative';
        } else if (container.tagName.toLowerCase() === 'body') {
            toggle.style.position = 'fixed';
            toggle.style.top = '20px';
            toggle.style.right = '20px';
            toggle.style.zIndex = '9999';
        } else {
            toggle.style.position = 'fixed';
            toggle.style.top = '20px';
            toggle.style.right = '20px';
            toggle.style.zIndex = '1000';
        }
    }

    updatePrivacyToggleUI() {
        const eyeBtn = document.getElementById('privacyEyeBtn');
        if (eyeBtn) {
            const shouldHide = this.privacyStatus?.privacy_enabled && !this.privacyStatus?.figures_visible;
            const icon = eyeBtn.querySelector('i');
            if (icon) {
                icon.className = `fas ${shouldHide ? 'fa-eye-slash' : 'fa-eye'}`;
                eyeBtn.title = shouldHide ? 'Show figures (requires PIN)' : 'Hide figures';
            }
        }
    }

    scanForFigures() {
        // Clear existing figures array but keep track of current elements
        const existingElements = new Set(this.figureElements.map(fig => fig.element));
        this.figureElements = [];
        
        // Enhanced selectors for financial figures
        const selectors = [
            // ID-based selectors
            '[id*="Balance"], [id*="balance"]',
            '[id*="Income"], [id*="income"]',
            '[id*="Expense"], [id*="expense"]',
            '[id*="Saving"], [id*="saving"]',
            '[id*="Amount"], [id*="amount"]',
            '[id*="Salary"], [id*="salary"]',
            '[id*="Total"], [id*="total"]',
            '[id*="Budget"], [id*="budget"]',
            
            // Class-based selectors
            '[class*="amount"], [class*="balance"], [class*="currency"]',
            '[class*="financial"], [class*="money"], [class*="salary"]',
            '[class*="income"], [class*="expense"], [class*="saving"]',
            '[class*="budget"], [class*="total"]',
            
            // Specific component selectors
            '.transaction-amount', '.goal-progress-percentage',
            '.budget-amount', '.expense-amount', '.income-amount',
            '.savings-amount', '.salary-amount', '.allocation-amount',
            '.remaining-amount', '.spent-amount', '.progress-amount',
            '.card-amount', '.summary-amount', '.dashboard-amount',
            
            // Table and list selectors
            'td[class*="amount"], th[class*="amount"]',
            'li[class*="amount"], div[class*="amount"]',
            'span[class*="amount"], p[class*="amount"]'
        ];
        
        // Scan with selectors first
        selectors.forEach(selector => {
            try {
                document.querySelectorAll(selector).forEach(element => {
                    if (this.containsMonetaryContent(element)) {
                        this.addFigureElement(element, existingElements);
                    }
                });
            } catch (e) {
                console.warn(`Error with selector ${selector}:`, e);
            }
        });

        // Enhanced text content scanning
        this.scanTextContent(existingElements);
    }

    scanTextContent(existingElements) {
        // Get all text-containing elements
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_ELEMENT,
            {
                acceptNode: function(node) {
                    // Skip script, style, and other non-content elements
                    const skipTags = ['SCRIPT', 'STYLE', 'META', 'LINK', 'TITLE', 'HEAD'];
                    if (skipTags.includes(node.tagName)) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    
                    // Skip if it's the privacy toggle
                    if (node.id === 'privacyToggle' || node.closest('#privacyToggle')) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    // Only consider leaf elements or elements with minimal children
                    if (node.children.length === 0 || 
                        (node.children.length <= 2 && node.textContent.trim().length < 100)) {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    
                    return NodeFilter.FILTER_SKIP;
                }
            }
        );

        let node;
        while (node = walker.nextNode()) {
            if (this.containsMonetaryContent(node)) {
                this.addFigureElement(node, existingElements);
            }
        }
    }

    addFigureElement(element, existingElements) {
        // Avoid duplicates
        const alreadyExists = this.figureElements.some(fig => fig.element === element);
        if (alreadyExists) return;

        const figureData = {
            element: element,
            isRestored: existingElements.has(element)
        };

        this.figureElements.push(figureData);

        // If this element was previously hidden and should still be hidden, hide it immediately
        if (this.privacyStatus?.privacy_enabled && !this.privacyStatus?.figures_visible && !figureData.isRestored) {
            this.hideFigure(element);
        }
    }

    containsMonetaryContent(element) {
        if (!element || !element.textContent) return false;
        
        const text = element.textContent.trim();
        
        // Skip very long content and certain element types
        if (text.length > 200) return false;
        if (text.length < 1) return false;
        
        const tagName = element.tagName?.toLowerCase() || '';
        if (['button', 'a', 'input', 'select', 'textarea', 'script', 'style', 'meta', 'link'].includes(tagName)) {
            return false;
        }
        
        // Skip navigation and menu items
        if (element.closest('nav') || element.closest('.nav') || element.closest('.menu')) {
            return false;
        }
        
        // Enhanced monetary patterns
        const monetaryPatterns = [
            /₵\s*[\d,]+\.?\d*/i,           // Ghanaian Cedi
            /\$\s*[\d,]+\.?\d*/i,          // Dollar
            /£\s*[\d,]+\.?\d*/i,           // Pound
            /€\s*[\d,]+\.?\d*/i,           // Euro
            /[\d,]+\.?\d*\s*%/i,           // Percentages
            /\b\d+\.\d{2}\b/,              // Decimal currency amounts
            /\b\d{1,3}(,\d{3})+\.?\d*\b/, // Formatted numbers with commas
            /\b\d{4,}\b/,                  // Large numbers (likely amounts)
            /^\s*[\d,]+\.?\d*\s*$/,        // Pure numbers (in elements likely to contain amounts)
        ];
        
        // Check for monetary patterns
        const hasMonetaryPattern = monetaryPatterns.some(pattern => pattern.test(text));
        
        // Additional context checks for pure numbers
        if (!hasMonetaryPattern && /^\s*[\d,]+\.?\d*\s*$/.test(text)) {
            const contextualClues = [
                'amount', 'balance', 'total', 'salary', 'income', 
                'expense', 'saving', 'budget', 'cost', 'price',
                'payment', 'fee', 'charge', 'sum', 'value'
            ];
            
            const elementText = (element.className + ' ' + element.id + ' ' + (element.closest('[class*="amount"], [class*="balance"], [class*="total"]')?.className || '')).toLowerCase();
            
            if (contextualClues.some(clue => elementText.includes(clue))) {
                return true;
            }
        }
        
        return hasMonetaryPattern;
    }

    applyPrivacyState() {
        const shouldHide = this.privacyStatus?.privacy_enabled && !this.privacyStatus?.figures_visible;
        
        this.figureElements.forEach(figureData => {
            if (shouldHide) {
                this.hideFigure(figureData.element);
            } else {
                this.showFigure(figureData.element);
            }
        });

        this.updatePrivacyToggleUI();
    }

    hideFigure(element) {
        if (element.classList.contains('privacy-hidden')) return;
        
        // Choose hiding method based on element content and size
        const text = element.textContent || '';
        const rect = element.getBoundingClientRect();
        
        // Remove any existing privacy classes first
        element.classList.remove('privacy-hidden-blur', 'privacy-hidden-dots', 'privacy-hidden-overlay');
        
        // Apply appropriate hiding method
        if (rect.width > 100 || rect.height > 30) {
            // Larger elements: use overlay pattern
            element.classList.add('privacy-hidden', 'privacy-hidden-overlay');
        } else if (text.length > 10) {
            // Medium text: use blur effect
            element.classList.add('privacy-hidden-blur');
        } else {
            // Small amounts: use dots overlay
            element.classList.add('privacy-hidden', 'privacy-hidden-dots');
        }
    }

    showFigure(element) {
        // Remove all privacy hiding classes
        element.classList.remove(
            'privacy-hidden', 
            'privacy-hidden-blur', 
            'privacy-hidden-dots', 
            'privacy-hidden-overlay'
        );
    }

    async handlePrivacyToggle() {
        try {
            if (!this.privacyStatus?.privacy_enabled) {
                this.showPinSetupModal();
            } else if (!this.privacyStatus?.has_pin) {
                this.showPinSetupModal();
            } else if (this.privacyStatus?.figures_visible) {
                // Hide figures immediately
                await this.setFiguresVisibility(false);
                this.showSnackbar('Figures hidden for privacy', 'success');
            } else {
                this.showPinVerificationModal();
            }
        } catch (error) {
            console.error('Error handling privacy toggle:', error);
            this.showSnackbar('Privacy toggle failed. Please try again.', 'error');
        }
    }

    async setFiguresVisibility(visible) {
        this.privacyStatus.figures_visible = visible;
        this.saveStateToStorage();
        this.applyPrivacyState();
        
        // Notify server of the change
        try {
            const formData = new FormData();
            formData.append('action', 'set_figures_visibility');
            formData.append('visible', visible);

            await fetch('../api/privacy_handler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
        } catch (error) {
            console.error('Error updating server state:', error);
        }
    }

    async togglePrivacy(enable) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_privacy');
            formData.append('enable', enable);

            const response = await fetch('../api/privacy_handler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (data.success) {
                this.privacyStatus.privacy_enabled = data.privacy_enabled;
                this.privacyStatus.figures_visible = !enable;
                this.saveStateToStorage();
                this.applyPrivacyState();
                this.showSnackbar(data.message, 'success');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error toggling privacy:', error);
            this.showSnackbar('Failed to toggle privacy', 'error');
        }
    }

    showPinSetupModal() {
        if (document.getElementById('pinSetupModal')) return;

        const modal = this.createModal('pinSetupModal', 'Set Privacy PIN', `
            <div class="pin-setup-content">
                <div class="pin-icon">🔒</div>
                <h3>Set Your Privacy PIN</h3>
                <p>Create a 6-digit PIN to protect your financial data</p>
                
                <form id="pinSetupForm">
                    <div class="pin-input-group">
                        <label>Enter 6-digit PIN:</label>
                        <input type="password" id="setupPin" maxlength="6" pattern="[0-9]{6}" placeholder="••••••" required autocomplete="new-password">
                    </div>
                    <div class="pin-input-group">
                        <label>Confirm PIN:</label>
                        <input type="password" id="confirmPin" maxlength="6" pattern="[0-9]{6}" placeholder="••••••" required autocomplete="new-password">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="privacyManager.closeModal('pinSetupModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Set PIN</button>
                    </div>
                </form>
            </div>
        `);

        document.getElementById('pinSetupForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePinSetup();
        });

        this.showModal('pinSetupModal');
    }

    showPinVerificationModal() {
        if (document.getElementById('pinVerifyModal')) return;

        const modal = this.createModal('pinVerifyModal', 'Enter Privacy PIN', `
            <div class="pin-verify-content">
                <div class="pin-icon">🔓</div>
                <h3>Enter Your PIN</h3>
                <p>Enter your 6-digit PIN to view financial figures</p>
                
                <form id="pinVerifyForm">
                    <div class="pin-input-group">
                        <input type="password" id="verifyPin" maxlength="6" pattern="[0-9]{6}" placeholder="••••••" required autocomplete="current-password">
                    </div>
                    <div class="pin-error" id="pinError" style="display: none;"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="privacyManager.closeModal('pinVerifyModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Verify PIN</button>
                    </div>
                    <div class="pin-forgot">
                        <button type="button" class="btn-link" onclick="privacyManager.showPinResetModal()">Forgot PIN?</button>
                    </div>
                </form>
            </div>
        `);

        document.getElementById('pinVerifyForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePinVerification();
        });

        this.showModal('pinVerifyModal');
    }

    showPinResetModal() {
        this.closeModal('pinVerifyModal');
        
        if (document.getElementById('pinResetModal')) return;

        const modal = this.createModal('pinResetModal', 'Reset PIN', `
            <div class="pin-reset-content">
                <div class="pin-icon">📧</div>
                <h3>Reset Your PIN</h3>
                <p>A reset link will be sent to your registered email address</p>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="privacyManager.closeModal('pinResetModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="privacyManager.requestPinReset()">Send Reset Link</button>
                </div>
            </div>
        `);

        this.showModal('pinResetModal');
    }

    async handlePinSetup() {
        const setupPin = document.getElementById('setupPin').value;
        const confirmPin = document.getElementById('confirmPin').value;

        if (setupPin.length !== 6 || !/^\d{6}$/.test(setupPin)) {
            this.showSnackbar('PIN must be exactly 6 digits', 'error');
            return;
        }

        if (setupPin !== confirmPin) {
            this.showSnackbar('PINs do not match', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'setup_pin');
            formData.append('pin', setupPin);

            const response = await fetch('../api/privacy_handler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (data.success) {
                this.closeModal('pinSetupModal');
                this.showSnackbar('PIN setup successfully!', 'success');
                
                // Update local state
                this.privacyStatus.has_pin = true;
                this.privacyStatus.privacy_enabled = true;
                this.privacyStatus.figures_visible = false;
                this.saveStateToStorage();
                
                // Apply privacy state immediately
                this.applyPrivacyState();
                
            } else {
                this.showSnackbar(data.message, 'error');
            }
        } catch (error) {
            console.error('Error setting up PIN:', error);
            this.showSnackbar('Failed to setup PIN', 'error');
        }
    }

    async handlePinVerification() {
        const pin = document.getElementById('verifyPin').value;

        if (pin.length !== 6 || !/^\d{6}$/.test(pin)) {
            this.showSnackbar('PIN must be exactly 6 digits', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'verify_pin');
            formData.append('pin', pin);

            const response = await fetch('../api/privacy_handler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (data.success) {
                this.closeModal('pinVerifyModal');
                this.showSnackbar('PIN verified! Figures are now visible.', 'success');
                
                // Update status immediately
                this.privacyStatus.figures_visible = true;
                this.privacyStatus.has_session = true;
                this.privacyStatus.session_expires = data.session_expires;
                this.saveStateToStorage();
                
                // Apply state immediately - no need to rescan since we're just removing CSS classes
                this.applyPrivacyState();
                
            } else {
                const errorEl = document.getElementById('pinError');
                if (errorEl) {
                    errorEl.textContent = data.message;
                    errorEl.style.display = 'block';
                }
                document.getElementById('verifyPin').value = '';
            }
        } catch (error) {
            console.error('Error verifying PIN:', error);
            this.showSnackbar('Failed to verify PIN', 'error');
        }
    }

    async requestPinReset() {
        try {
            const formData = new FormData();
            formData.append('action', 'request_pin_reset');

            const response = await fetch('../api/privacy_handler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            this.closeModal('pinResetModal');
            this.showSnackbar(data.message, data.success ? 'success' : 'error');
            
        } catch (error) {
            console.error('Error requesting PIN reset:', error);
            this.showSnackbar('Failed to request PIN reset', 'error');
        }
    }

    createModal(id, title, content) {
        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'privacy-modal';
        modal.innerHTML = `
            <div class="privacy-modal-overlay" onclick="privacyManager.closeModal('${id}')"></div>
            <div class="privacy-modal-content">
                <div class="privacy-modal-header">
                    <h2>${title}</h2>
                    <button class="privacy-modal-close" onclick="privacyManager.closeModal('${id}')">&times;</button>
                </div>
                <div class="privacy-modal-body">
                    ${content}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        return modal;
    }

    showModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            
            // Focus first input
            const firstInput = modal.querySelector('input[type="password"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                modal.remove();
            }, 300);
        }
    }

    showSnackbar(message, type = 'info') {
        // Remove existing snackbar
        const existing = document.querySelector('.privacy-snackbar');
        if (existing) existing.remove();

        const snackbar = document.createElement('div');
        snackbar.className = `privacy-snackbar ${type}`;
        snackbar.innerHTML = `
            <span class="snackbar-icon">${this.getSnackbarIcon(type)}</span>
            <span class="snackbar-message">${message}</span>
        `;
        
        document.body.appendChild(snackbar);
        
        setTimeout(() => snackbar.classList.add('show'), 100);
        setTimeout(() => {
            snackbar.classList.remove('show');
            setTimeout(() => snackbar.remove(), 300);
        }, 4000);
    }

    getSnackbarIcon(type) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-times-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons.info;
    }

    // Enhanced refresh method with CSS-based hiding
    refreshFigures(force = false) {
        if (force) {
            // Clear all existing figures to force redetection
            this.figureElements = [];
        }
        
        this.scanForFigures();
        this.applyPrivacyState();
        
        // Additional scan after a delay for dynamic content
        setTimeout(() => {
            this.scanForFigures();
            this.applyPrivacyState();
        }, 300);
    }

    // Method to manually add figure elements with immediate state application
    addFigureElementManual(element) {
        if (this.containsMonetaryContent(element)) {
            const figureData = {
                element: element
            };
            
            this.figureElements.push(figureData);
            
            // Apply current privacy state to new element immediately
            if (this.privacyStatus?.privacy_enabled && !this.privacyStatus?.figures_visible) {
                this.hideFigure(element);
            }
        }
    }

    // Method to force state synchronization across tabs/windows
    forceStateSync() {
        this.loadPrivacyStatus().then(() => {
            this.scanForFigures();
            this.applyPrivacyState();
        });
    }

    // Enhanced cleanup method
    destroy() {
        if (this.sessionCheckInterval) {
            clearInterval(this.sessionCheckInterval);
        }
        
        if (this.figureRefreshInterval) {
            clearInterval(this.figureRefreshInterval);
        }
        
        // Remove event listeners
        window.removeEventListener('storage', this.handleStorageChange);
        window.removeEventListener('privacyStateChanged', this.handlePrivacyStateChange);
        
        const toggle = document.getElementById('privacyToggle');
        if (toggle) {
            toggle.remove();
        }
        
        // Remove privacy styles
        const styles = document.getElementById('privacy-manager-styles');
        if (styles) {
            styles.remove();
        }
        
        // Restore all hidden figures by removing CSS classes
        this.figureElements.forEach(figureData => {
            this.showFigure(figureData.element);
        });
        
        // Clear storage
        try {
            localStorage.removeItem(this.storageKey);
        } catch (e) {
            console.warn('Could not clear privacy storage:', e);
        }
    }

    // Method to check if privacy is active
    isPrivacyActive() {
        return this.privacyStatus?.privacy_enabled && !this.privacyStatus?.figures_visible;
    }

    // Method to get current privacy status
    getPrivacyStatus() {
        return { ...this.privacyStatus };
    }

    // Method for external components to request figure refresh
    static requestRefresh() {
        if (window.privacyManager) {
            window.privacyManager.refreshFigures(true);
        }
    }
}

// Enhanced initialization with better error handling and retry logic
let privacyManager;

function initializePrivacyManager() {
    try {
        if (privacyManager) {
            privacyManager.destroy();
        }
        privacyManager = new PrivacyManager();
        window.privacyManager = privacyManager;
    } catch (error) {
        console.error('Failed to initialize privacy manager:', error);
        // Retry after a delay
        setTimeout(initializePrivacyManager, 1000);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePrivacyManager);
} else {
    initializePrivacyManager();
}

// Re-initialize on page changes for SPAs
window.addEventListener('popstate', function() {
    setTimeout(initializePrivacyManager, 100);
});

// Re-initialize on hash changes
window.addEventListener('hashchange', function() {
    setTimeout(() => {
        if (privacyManager) {
            privacyManager.refreshFigures(true);
        }
    }, 200);
});

// Enhanced page visibility handling
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && privacyManager) {
        setTimeout(() => {
            privacyManager.forceStateSync();
        }, 100);
    }
});

// Global utility methods
window.PrivacyManager = {
    refresh: () => PrivacyManager.requestRefresh(),
    isActive: () => window.privacyManager?.isPrivacyActive() || false,
    getStatus: () => window.privacyManager?.getPrivacyStatus() || null
};