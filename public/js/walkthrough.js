/**
 * Budget App Walkthrough System
 * Provides guided tours for new users with non-skippable steps
 */

class BudgetWalkthrough {
    constructor() {
        this.currentStep = null;
        this.walkthroughData = null;
        this.overlay = null;
        this.tooltip = null;
        this.instruction = null;
        this.isActive = false;
        this.completedSteps = [];
        this.isTemporarilyHidden = false;
        this.init();
    }

    async init() {
        // Emergency disable switch - check for URL parameter to disable walkthrough
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('disable_walkthrough') === '1') {
            console.log('🚨 Walkthrough disabled via URL parameter');
            return;
        }
        
        // Check if user needs walkthrough
        await this.checkWalkthroughStatus();
        
        // Add styles
        this.addStyles();
        
        // Setup navigation protection immediately
        this.setupNavigationProtection();
        
        // Start walkthrough if needed
        if (this.shouldShowWalkthrough()) {
            // Add a small delay to ensure page is fully loaded
            setTimeout(() => {
                this.startWalkthrough();
            }, 500);
        } else {
            // If no initial walkthrough needed, add page help icon
            setTimeout(() => {
                this.initPageHelp();
            }, 1000);
        }
    }

    async checkSalaryCompletionStatus() {
        try {
            // Check if salary is actually set up by making a quick API call
            const response = await fetch('/budget/api/salary_data.php', {
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                
                // Check if user has any salary configured
                const hasSalary = data.total_income && 
                                 parseFloat(data.total_income) > 0;
                
                return hasSalary;
            }
        } catch (error) {
            console.log('❌ Error checking salary status:', error);
        }
        
        // Fallback - check the display element on the page
        const primarySalaryAmount = document.getElementById('primarySalaryAmount');
        if (primarySalaryAmount) {
            const salaryDisplayText = primarySalaryAmount.textContent.trim();
            const hasSalarySet = salaryDisplayText && 
                                !salaryDisplayText.includes('₵0.00') && 
                                !salaryDisplayText.includes('₵0') &&
                                salaryDisplayText !== '₵0.00' &&
                                salaryDisplayText !== '₵0';
            return hasSalarySet;
        }
        
        return false;
    }

    setupNavigationProtection() {
        
        // Don't protect navigation if walkthrough is completed
        if (!this.walkthroughData || this.walkthroughData.is_completed) {
            return;
        }
        
        const currentStep = this.walkthroughData.current_step;
        
        // Only protect navigation if user is on salary setup step
        if (currentStep === 'configure_salary') {
            // Check if salary is actually completed
            this.checkSalaryCompletionStatus().then(isCompleted => {
                this.salaryCompletionChecked = true;
                this.isSalaryCompleted = isCompleted;
                
                if (!isCompleted) {
                    console.log('🚫 Salary not completed, blocking navigation');
                    this.blockNavigationForSalarySetup();
                } else {
                    console.log('✅ Salary already completed, not blocking navigation');
                }
            });
        }
    }

    blockNavigationForSalarySetup() {
        
        // Get all navigation links
        const navLinks = document.querySelectorAll('.header-nav .nav-item, .nav-link');
        const currentPageUrl = window.location.pathname;
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            
            // Skip if it's the salary page or dashboard (allowed pages)
            if (href && (href.includes('salary.php') || href.includes('personal-dashboard.php'))) {
                return;
            }
            
            // Block all other navigation
            if (href && !href.includes('salary.php')) {
                // Store original href
                link.setAttribute('data-original-href', href);
                
                // Remove href to disable normal navigation
                link.removeAttribute('href');
                
                // Add disabled styling
                link.classList.add('nav-blocked');
                
                // Add click handler that shows warning
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.showSalaryRequiredMessage();
                    return false;
                });
                
                // Add visual indicator
                const indicator = document.createElement('span');
                indicator.className = 'nav-block-indicator';
                indicator.innerHTML = ' 🔒';
                indicator.title = 'Complete salary setup to access this page';
                link.appendChild(indicator);
            }
        });
        
        // Also block any other buttons/links that might navigate away
        this.blockOtherNavigationElements();
    }

    blockOtherNavigationElements() {
        // Block common navigation elements like buttons that redirect
        const navigationButtons = document.querySelectorAll('[onclick*="location"], [onclick*="href"], button[data-redirect]');
        
        navigationButtons.forEach(button => {
            const onclick = button.getAttribute('onclick');
            if (onclick && !onclick.includes('salary.php') && !onclick.includes('personal-dashboard.php')) {
                // Store original onclick
                button.setAttribute('data-original-onclick', onclick);
                
                // Remove onclick
                button.removeAttribute('onclick');
                
                // Add disabled styling
                button.classList.add('nav-blocked');
                
                // Add click handler that shows warning
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.showSalaryRequiredMessage();
                    return false;
                });
            }
        });
        
        // Intercept window.location changes
        this.interceptWindowLocationChanges();
    }

    interceptWindowLocationChanges() {
        // Instead of overriding location methods (which are read-only), 
        // we'll use a different approach with event delegation and interception
        
        // Store reference to this for use in event handlers
        const self = this;
        
        // Intercept all click events to check for navigation
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a, button[onclick], [data-href]');
            if (!target) return;
            
            let url = null;
            
            // Check for various navigation patterns
            if (target.tagName === 'A' && target.href) {
                url = target.href;
            } else if (target.onclick) {
                const onclickStr = target.onclick.toString();
                // Look for location assignments in onclick
                const locationMatch = onclickStr.match(/(?:window\.)?location(?:\.href)?\s*=\s*['"`]([^'"`]+)['"`]/);
                if (locationMatch) {
                    url = locationMatch[1];
                }
            } else if (target.dataset.href) {
                url = target.dataset.href;
            }
            
            if (url && self.shouldBlockNavigation(url)) {
                e.preventDefault();
                e.stopPropagation();
                self.showSalaryRequiredMessage();
                return false;
            }
        }, true); // Use capture phase to catch events early
        
        // Store original window.location.href for monitoring
        this.originalHref = window.location.href;
        
        // Periodically check for programmatic navigation attempts
        this.navigationCheckInterval = setInterval(() => {
            if (window.location.href !== self.originalHref) {
                const newUrl = window.location.href;
                if (self.shouldBlockNavigation(newUrl)) {
                    // If navigation was blocked, try to go back
                    history.back();
                    self.showSalaryRequiredMessage();
                }
                self.originalHref = newUrl;
            }
        }, 100);
    }

    shouldBlockNavigation(url) {
        // Don't block if walkthrough is completed or not active
        if (!this.walkthroughData || this.walkthroughData.is_completed) {
            return false;
        }
        
        // Don't block if not on salary setup step
        if (this.walkthroughData.current_step !== 'configure_salary') {
            return false;
        }
        
        // Check if salary completion status is cached and completed
        if (this.salaryCompletionChecked && this.isSalaryCompleted) {
            return false;
        }
        
        // Allow navigation to salary page and dashboard
        if (url.includes('salary.php') || url.includes('personal-dashboard.php')) {
            return false;
        }
        
        // Block all other navigation
        return true;
    }

    showSalaryRequiredMessage() {
        
        // Remove any existing message
        const existingMessage = document.querySelector('.salary-required-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new message
        const message = document.createElement('div');
        message.className = 'salary-required-message walkthrough-message walkthrough-message-warning';
        message.innerHTML = `
            <div class="message-content">
                <h5>⚠️ Salary Setup Required</h5>
                <p>You need to complete your salary setup before accessing other pages. This is essential for budget planning and goal tracking.</p>
                <div class="message-actions">
                    <button class="btn btn-primary btn-sm" onclick="window.location.href='/salary'">Complete Salary Setup</button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="this.closest('.salary-required-message').remove()">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(message);
        
        // Auto remove after 8 seconds
        setTimeout(() => {
            if (message.parentElement) {
                message.remove();
            }
        }, 8000);
    }

    restoreNavigation() {
        
        // Remove blocked styling and restore functionality
        const blockedElements = document.querySelectorAll('.nav-blocked');
        blockedElements.forEach(element => {
            element.classList.remove('nav-blocked');
            
            // Restore href for links
            const originalHref = element.getAttribute('data-original-href');
            if (originalHref) {
                element.setAttribute('href', originalHref);
                element.removeAttribute('data-original-href');
            }
            
            // Restore onclick for buttons
            const originalOnclick = element.getAttribute('data-original-onclick');
            if (originalOnclick) {
                element.setAttribute('onclick', originalOnclick);
                element.removeAttribute('data-original-onclick');
            }
            
            // Remove block indicators
            const indicator = element.querySelector('.nav-block-indicator');
            if (indicator) {
                indicator.remove();
            }
        });
        
        // Clean up navigation check interval
        if (this.navigationCheckInterval) {
            clearInterval(this.navigationCheckInterval);
            this.navigationCheckInterval = null;
        }
        
        // Restore window.location methods (this is complex, so we'll just reload the page)
        // The navigation will be restored on page reload when walkthrough status is checked
    }

    // Initialize page-specific help icon
    initPageHelp() {
        // Check if help icon already exists
        if (document.getElementById('page-help-icon')) return;
        
        // Create help icon
        const helpIcon = document.createElement('div');
        helpIcon.id = 'page-help-icon';
        helpIcon.innerHTML = `
            <div class="help-icon-container">
                <button class="help-icon-btn" title="Get help with this page">
                    <i class="fas fa-question-circle"></i>
                    <span>Help</span>
                </button>
            </div>
        `;
        
        // Add styles
        helpIcon.innerHTML += `
            <style>
                .help-icon-container {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 1000;
                }
                .help-icon-btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    border-radius: 25px;
                    padding: 12px 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
                    transition: all 0.3s ease;
                }
                .help-icon-btn:hover {
                    background: #0056b3;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
                }
                .help-icon-btn i {
                    font-size: 16px;
                }
                @media (max-width: 768px) {
                    .help-icon-container {
                        bottom: 15px;
                        right: 15px;
                    }
                    .help-icon-btn {
                        padding: 10px 14px;
                        font-size: 13px;
                    }
                }
            </style>
        `;
        
        // Add click handler
        helpIcon.querySelector('.help-icon-btn').addEventListener('click', () => {
            this.startPageHelp();
        });
        
        document.body.appendChild(helpIcon);
        
        console.log('✅ Page help icon added');
    }

    // Start page-specific help tour
    async startPageHelp() {
        
        try {
            const currentPageUrl = window.location.pathname;
            const response = await fetch('/budget/api/walkthrough_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ 
                    walkthrough_type: 'help_guide',
                    page_url: currentPageUrl 
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.step) {
                this.isActive = true;
                this.walkthroughType = 'help_guide';
                this.showStep(data.step);
            } else {
                console.log('ℹ️ No help available for this page');
            }
        } catch (error) {
            console.error('❌ Error starting page help:', error);
        }
    }

    async checkWalkthroughStatus() {
        try {
            // Use absolute path to ensure correct resolution regardless of current URL
            const response = await fetch('/budget/api/walkthrough_status.php', {
                credentials: 'same-origin' // Ensure cookies/session are sent
            });
            
            if (!response.ok) {
                console.log('Walkthrough API not available:', response.status);
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.walkthroughData = data.data;
                this.currentStep = data.data.current_step;
                this.completedSteps = data.data.steps_completed || [];
            } else {
                console.log('Walkthrough status error:', data.error);
                this.walkthroughData = null;
            }
        } catch (error) {
            console.log('Walkthrough status check failed:', error);
            this.walkthroughData = null;
        }
    }

    shouldShowWalkthrough() {
        // Show walkthrough if user has an active walkthrough that's not completed
        return this.walkthroughData && 
               !this.walkthroughData.is_completed && 
               this.walkthroughData.current_step;
    }

    getCurrentPageStep() {
        const currentPage = window.location.pathname;
        console.log('🌍 Current page:', currentPage);
        console.log('📍 Current step:', this.currentStep);
        
        // If user has setup_income step, start it on any page (dashboard)
        if (this.currentStep === 'setup_income') {
            if (currentPage.includes('personal-dashboard')) {
                return 'setup_income';
            } else {
                // Redirect to dashboard to start walkthrough
                console.log('🚀 Redirecting to dashboard to start walkthrough');
                window.location.href = '/personal-dashboard';
                return null;
            }
        }
        
        // Configure salary step - should be on salary page
        if (this.currentStep === 'configure_salary') {
            if (currentPage.includes('salary')) {
                return 'configure_salary';
            } else {
                // User is on wrong page, but this is expected - salary modal opens from dashboard
                // Just start the walkthrough where they are if it's dashboard
                if (currentPage.includes('personal-dashboard')) {
                    // Redirect to salary page for salary step
                    console.log('🚀 Redirecting to salary page for salary configuration');
                    window.location.href = '/salary';
                    return null;
                }
            }
        }
        
        // Setup budget step - should be on budget page  
        if (this.currentStep === 'setup_budget') {
            if (currentPage.includes('budget')) {
                return 'setup_budget';
            } else {
                // Redirect to budget page
                console.log('🚀 Redirecting to budget page for budget setup');
                window.location.href = '/budgets';
                return null;
            }
        }
        
        return null;
    }

    async startWalkthrough() {
        const stepName = this.getCurrentPageStep();
        console.log('Starting walkthrough for step:', stepName);
        if (!stepName) {
            console.log('No step found for current page');
            return;
        }

        try {
            const response = await fetch('/budget/api/get_walkthrough_step.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ step_name: stepName })
            });
            
            const data = await response.json();
            console.log('Walkthrough step data:', data);
            
            if (data.success) {
                this.showStep(data.step);
            } else {
                console.error('Failed to get step:', data.error);
            }
        } catch (error) {
            console.error('Failed to get walkthrough step:', error);
        }
    }

    showStep(step) {
        this.isActive = true;
        this.currentStep = step; // Store current step
        
        console.log('🎯 Showing step:', step);
        console.log('🎯 Target element selector:', step.target_element);
        
        // Create overlay
        this.createOverlay();
        
        // Try multiple fallback selectors for better element finding
        const targetSelectors = [
            step.target_element,
            // Common fallbacks for different page elements
            '.page-header', '.main-content', '.container', 'main', 'body'
        ];
        
        let targetElement = null;
        
        // Try each selector until we find an element
        for (const selector of targetSelectors) {
            try {
                targetElement = document.querySelector(selector);
                if (targetElement) {
                    console.log('✅ Found target with selector:', selector);
                    break;
                }
            } catch (e) {
                console.log('❌ Invalid selector:', selector);
                continue;
            }
        }
        
        if (!targetElement) {
            console.log('❌ No suitable target element found, using fallback');
            // Use a very generic fallback
            targetElement = document.body;
        }

        console.log('✅ Using target element:', targetElement);

        // For help guides with missing elements, show general help
        if (!targetElement || targetElement === document.body) {
            if (step.walkthrough_type === 'help_guide') {
                this.showGeneralHelpMessage(step);
                return;
            }
        }

        // Highlight target element
        this.highlightElement(targetElement);
        
        // Show floating instruction only for action-required steps
        if (step.action_required && step.walkthrough_type !== 'help_guide') {
            this.showFloatingInstruction(targetElement, step);
        }
        
        // Show tooltip
        this.showTooltip(targetElement, step);
        
        // Handle different step types
        if (step.step_name === 'configure_salary') {
            console.log('🔧 Setting up salary step monitoring');
            this.setupSalaryButtonMonitoring(targetElement, step);
        } else if (step.step_name === 'setup_budget') {
            console.log('💰 Setting up budget step monitoring');
            this.setupBudgetStepMonitoring(targetElement, step);
        } else if (step.action_required && step.walkthrough_type !== 'help_guide') {
            // For other action-required steps, but not help guides
            this.disableOtherElements(targetElement);
        }
    }

    setupSalaryButtonMonitoring(targetElement, step) {
        console.log('🔧 Setting up salary button monitoring without interference');
        
        // Don't add interfering click listeners to the salary button
        // Instead, monitor for the modal to appear after user clicks
        const checkForClick = () => {
            // Monitor for modal appearance which indicates user clicked the button
            const modal = document.getElementById('primarySalaryModal');
            if (modal && (modal.style.display === 'flex' || modal.classList.contains('show'))) {
                console.log('🎉 Salary modal opened - user clicked the button!');
                this.handleSalarySetupStep(step);
                return; // Stop monitoring
            }
            
            // Continue monitoring for click
            setTimeout(checkForClick, 100);
        };
        
        // Start monitoring
        setTimeout(checkForClick, 100);
    }

    setupBudgetStepMonitoring(targetElement, step) {
        console.log('💰 Setting up budget step monitoring');
        
        // For budget step, we allow the user to either:
        // 1. Click "Use Template" to open template modal
        // 2. Skip the step entirely
        
        // Monitor for template modal opening
        const checkForTemplateModal = () => {
            const modal = document.getElementById('budgetTemplateModal');
            if (modal && (modal.style.display === 'block' || modal.classList.contains('show'))) {
                console.log('📊 Budget template modal opened!');
                this.monitorTemplateSelection(step);
                return; // Stop monitoring
            }
            
            // Continue monitoring
            setTimeout(checkForTemplateModal, 100);
        };
        
        // Start monitoring
        setTimeout(checkForTemplateModal, 100);
    }

    monitorTemplateSelection(step) {
        console.log('👀 Monitoring template selection...');
        
        // Monitor for template selection or modal closure
        const modal = document.getElementById('budgetTemplateModal');
        if (!modal) return;
        
        // Listen for template selection (template cards have onclick events)
        const templateCards = modal.querySelectorAll('.template-card');
        templateCards.forEach(card => {
            card.addEventListener('click', () => {
                console.log('✅ Template selected!');
                setTimeout(() => {
                    this.completeBudgetStep(step);
                }, 1000); // Allow time for template to be applied
            }, { once: true });
        });
        
        // Also monitor for modal closure
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    if (modal.style.display === 'none' || !modal.style.display) {
                        console.log('📊 Template modal closed');
                        observer.disconnect();
                        // Complete the step anyway (they may have selected a template)
                        setTimeout(() => {
                            this.completeBudgetStep(step);
                        }, 500);
                    }
                }
            });
        });
        
        observer.observe(modal, { attributes: true });
    }

    completeBudgetStep(step) {
        console.log('🎉 Completing budget setup step');
        
        // Complete the walkthrough step
        this.completeStep(step.step_name);
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'walkthrough-overlay';
        document.body.appendChild(this.overlay);
        
        // Listen for modal events
        this.setupModalListeners();
    }
    
    setupModalListeners() {
        // Observer to detect when modals are shown/hidden
        const observer = new MutationObserver((mutations) => {
            // Skip if we're handling salary setup manually
            if (this.isHandlingSalarySetup) return;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (target.classList.contains('modal')) {
                        const isVisible = target.style.display === 'flex' || 
                                        target.classList.contains('show') ||
                                        getComputedStyle(target).display === 'flex';
                        if (this.overlay) {
                            if (isVisible) {
                                console.log('🎭 Modal detected, hiding walkthrough overlay');
                                this.overlay.classList.add('modal-open');
                            } else {
                                console.log('🎭 Modal closed, showing walkthrough overlay');
                                this.overlay.classList.remove('modal-open');
                            }
                        }
                    }
                }
            });
        });
        
        // Watch all modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });
        });
        
        // Also listen for direct modal show/hide events
        document.addEventListener('modalShow', () => {
            // Skip if we're handling salary setup manually
            if (this.isHandlingSalarySetup) return;
            
            if (this.overlay) {
                console.log('🎭 Modal show event, hiding walkthrough overlay');
                this.overlay.classList.add('modal-open');
            }
        });
        
        document.addEventListener('modalHide', () => {
            // Skip if we're handling salary setup manually
            if (this.isHandlingSalarySetup) return;
            
            if (this.overlay) {
                console.log('🎭 Modal hide event, showing walkthrough overlay');
                this.overlay.classList.remove('modal-open');
            }
        });
    }

    highlightElement(element) {
        // Store reference to current target element
        this.currentTargetElement = element;
        
        element.classList.add('walkthrough-highlight');
        
        // Ensure element is visible and scrolled into view
        element.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        
        // Make sure element is above overlay and clickable
        element.style.position = 'relative';
        element.style.zIndex = '10001';
        element.style.pointerEvents = 'auto';
    }

    showFloatingInstruction(targetElement, step) {
        const instruction = document.createElement('div');
        instruction.className = 'walkthrough-instruction';
        instruction.innerHTML = `
            <div class="instruction-arrow"></div>
            <div class="instruction-text">
                <strong>👆 Click here</strong><br>
                ${step.action_required ? step.title : 'to continue'}
            </div>
        `;
        
        document.body.appendChild(instruction);
        
        // Position next to target element
        this.positionInstruction(targetElement, instruction);
        
        // Store reference for cleanup
        this.instruction = instruction;
    }

    positionInstruction(targetElement, instruction) {
        const rect = targetElement.getBoundingClientRect();
        
        // Position to the right of the element
        let left = rect.right + 20;
        let top = rect.top + (rect.height / 2) - 25;
        
        // If too far right, position to the left
        if (left + 200 > window.innerWidth) {
            left = rect.left - 220;
            instruction.classList.add('instruction-left');
        }
        
        // Adjust vertical position if needed
        if (top < 10) top = 10;
        if (top + 50 > window.innerHeight) top = window.innerHeight - 60;
        
        instruction.style.top = top + window.scrollY + 'px';
        instruction.style.left = left + 'px';
    }

    showTooltip(targetElement, step) {
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'walkthrough-tooltip';
        
        const canSkip = step.can_skip || step.walkthrough_type === 'help_guide';
        const isHelpGuide = step.walkthrough_type === 'help_guide';
        
        // Different content for help guides vs initial setup
        const progressText = isHelpGuide ? 
            `Help Guide - ${step.page_url}` : 
            `Step ${step.step_order} of 5`;
            
        const actionContent = step.action_required && !isHelpGuide ? 
            '<p class="action-note"><i class="fas fa-info-circle"></i> Complete this action to continue</p>' : 
            isHelpGuide ?
                '<button class="btn btn-primary btn-sm tooltip-next">Next Tip</button>' :
                '<button class="btn btn-primary btn-sm tooltip-next">Next</button>';
        
        this.tooltip.innerHTML = `
            <div class="tooltip-header">
                <h4>${step.title}</h4>
                <div class="tooltip-progress">
                    ${progressText}
                </div>
            </div>
            <div class="tooltip-content">
                <p>${step.content}</p>
            </div>
            <div class="tooltip-actions">
                ${actionContent}
                ${canSkip ? '<button class="btn btn-outline-secondary btn-sm tooltip-skip">Close Help</button>' : ''}
            </div>
        `;

        document.body.appendChild(this.tooltip);
        
        // Force immediate layout calculation
        this.tooltip.offsetHeight;
        
        // Position tooltip
        this.positionTooltip(targetElement);
        
        // Double-check positioning after a brief delay to ensure proper rendering
        setTimeout(() => {
            this.ensureTooltipVisible();
        }, 10);
        
        // Add event listeners
        if (!step.action_required || isHelpGuide) {
            const nextBtn = this.tooltip.querySelector('.tooltip-next');
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if (isHelpGuide) {
                        // For help guides, just close the current tip
                        this.cleanup();
                    } else {
                        this.nextStep();
                    }
                });
            }
        }
        
        if (canSkip) {
            const skipBtn = this.tooltip.querySelector('.tooltip-skip');
            if (skipBtn) {
                skipBtn.addEventListener('click', () => {
                    if (isHelpGuide) {
                        this.cleanup();
                    } else {
                        this.skipWalkthrough();
                    }
                });
            }
        }

        // Listen for the required action only for non-help guide steps
        if (step.action_required && !isHelpGuide && step.step_name !== 'configure_salary' && step.step_name !== 'setup_budget') {
            this.listenForAction(targetElement, step);
        }
    }

    positionTooltip(targetElement) {
        const rect = targetElement.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const scrollY = window.scrollY;
        const scrollX = window.scrollX;
        
        // Reset any existing arrow classes
        this.tooltip.className = this.tooltip.className.replace(/arrow-[a-z]+/g, '');
        
        // For small screens, use simpler positioning
        if (viewportWidth <= 768) {
            // Mobile positioning - always center horizontally
            this.tooltip.style.maxWidth = `${viewportWidth - 20}px`;
            this.tooltip.style.width = `${viewportWidth - 20}px`;
            this.tooltip.style.left = '10px';
            
            // Position above or below based on available space
            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;
            
            if (spaceBelow > spaceAbove) {
                // Position below
                this.tooltip.style.top = (rect.bottom + 15 + scrollY) + 'px';
                this.tooltip.classList.add('arrow-top');
            } else {
                // Position above
                const tooltipHeight = this.tooltip.offsetHeight || 200; // estimate if not rendered
                this.tooltip.style.top = (rect.top - tooltipHeight - 15 + scrollY) + 'px';
                this.tooltip.classList.add('arrow-bottom');
            }
            
            console.log(`📱 Mobile tooltip positioned:`, {
                width: viewportWidth - 20,
                position: spaceBelow > spaceAbove ? 'below' : 'above'
            });
            return;
        }
        
        // Desktop positioning
        const tooltipRect = this.tooltip.getBoundingClientRect();
        let top, left;
        let position = 'bottom'; // Default preference: show below target
        
        // Calculate space available in each direction
        const spaceBelow = viewportHeight - rect.bottom;
        const spaceAbove = rect.top;
        const spaceLeft = rect.left;
        const spaceRight = viewportWidth - rect.right;
        
        // Determine best position based on available space
        // Prefer top/bottom positioning to avoid text cutoff
        if (spaceBelow >= tooltipRect.height + 30) {
            // Position below (preferred)
            position = 'bottom';
            top = rect.bottom + 15;
            left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            this.tooltip.classList.add('arrow-top');
        } else if (spaceAbove >= tooltipRect.height + 30) {
            // Position above
            position = 'top';
            top = rect.top - tooltipRect.height - 15;
            left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            this.tooltip.classList.add('arrow-bottom');
        } else if (spaceRight >= tooltipRect.width + 30) {
            // Position to the right (only if top/bottom won't work)
            position = 'right';
            top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
            left = rect.right + 15;
            this.tooltip.classList.add('arrow-left');
        } else if (spaceLeft >= tooltipRect.width + 30) {
            // Position to the left (last resort)
            position = 'left';
            top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
            left = rect.left - tooltipRect.width - 15;
            this.tooltip.classList.add('arrow-right');
        } else {
            // Force centered positioning if no good space (fallback)
            position = 'center';
            top = Math.max(20, (viewportHeight - tooltipRect.height) / 2);
            left = Math.max(20, (viewportWidth - tooltipRect.width) / 2);
            this.tooltip.classList.add('arrow-bottom');
        }
        
        // Adjust horizontal position to stay within viewport
        if (position === 'bottom' || position === 'top') {
            if (left < 20) {
                left = 20;
            } else if (left + tooltipRect.width > viewportWidth - 20) {
                left = viewportWidth - tooltipRect.width - 20;
            }
        }
        
        // Adjust vertical position to stay within viewport
        if (position === 'left' || position === 'right') {
            if (top < 20) {
                top = 20;
            } else if (top + tooltipRect.height > viewportHeight - 20) {
                top = viewportHeight - tooltipRect.height - 20;
            }
        }
        
        // Final positioning with scroll offset
        this.tooltip.style.top = (top + scrollY) + 'px';
        this.tooltip.style.left = (left + scrollX) + 'px';
        
        console.log(`📍 Positioned tooltip ${position} of target:`, {
            targetRect: rect,
            tooltipSize: { width: tooltipRect.width, height: tooltipRect.height },
            finalPosition: { top: top + scrollY, left: left + scrollX },
            position: position
        });
    }

    ensureTooltipVisible() {
        if (!this.tooltip) return;
        
        const rect = this.tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        let adjustedLeft = parseInt(this.tooltip.style.left);
        let adjustedTop = parseInt(this.tooltip.style.top);
        let needsAdjustment = false;
        
        // Check if tooltip is cut off horizontally
        if (rect.right > viewportWidth - 10) {
            adjustedLeft = viewportWidth - rect.width - 10;
            needsAdjustment = true;
        }
        if (rect.left < 10) {
            adjustedLeft = 10;
            needsAdjustment = true;
        }
        
        // Check if tooltip is cut off vertically
        if (rect.bottom > viewportHeight - 10) {
            adjustedTop = viewportHeight - rect.height - 10;
            needsAdjustment = true;
        }
        if (rect.top < 10) {
            adjustedTop = 10;
            needsAdjustment = true;
        }
        
        // Apply adjustments if needed
        if (needsAdjustment) {
            this.tooltip.style.left = adjustedLeft + 'px';
            this.tooltip.style.top = adjustedTop + 'px';
            
            console.log('🔧 Adjusted tooltip position to ensure visibility:', {
                original: { left: rect.left, top: rect.top, right: rect.right, bottom: rect.bottom },
                adjusted: { left: adjustedLeft, top: adjustedTop }
            });
        }
        
        // On mobile, ensure full width usage
        if (viewportWidth <= 768) {
            this.tooltip.style.width = (viewportWidth - 20) + 'px';
            this.tooltip.style.maxWidth = (viewportWidth - 20) + 'px';
            this.tooltip.style.left = '10px';
        }
    }

    listenForAction(targetElement, step) {
        console.log('Setting up action listener for:', step.step_name);
        console.log('Target element:', targetElement);
        console.log('Element classes:', targetElement.className);
        console.log('Element ID:', targetElement.id);
        
        const handleAction = (event) => {
            console.log('🔥 ACTION TRIGGERED! Event:', event.type);
            console.log('🔥 Event target:', event.target);
            
            // Handle different step types differently
            if (step.step_name === 'configure_salary') {
                console.log('🔧 Handling salary configuration step');
                
                // Don't prevent the modal from opening - let it open first
                // The button click should open the modal, then we monitor for form submission
                this.handleSalarySetupStep(step);
                
            } else {
                console.log('🔥 Preventing default action to complete step first');
                
                // For other steps, prevent default action and complete step first
                event.preventDefault();
                event.stopPropagation();
                
                console.log('🔥 Completing step:', step.step_name);
                
                // Complete the step first, then handle navigation
                this.completeStep(step.step_name);
            }
        };

        // Listen for click on target element with high priority
        targetElement.addEventListener('click', handleAction, { 
            once: true, 
            capture: true  // Use capture phase to get event first
        });
        console.log('✅ Click listener added to target element');
        
        // Test if element is clickable
        console.log('Element pointer-events:', window.getComputedStyle(targetElement).pointerEvents);
        console.log('Element z-index:', window.getComputedStyle(targetElement).zIndex);
    }

    handleSalarySetupStep(step) {
        console.log('🔧 Handling salary setup step');
        
        // Disable automatic modal listeners during salary setup
        this.isHandlingSalarySetup = true;
        
        // Temporarily hide the walkthrough to avoid z-index conflicts
        console.log('👻 Temporarily hiding walkthrough for salary setup');
        this.temporarilyHide();
        
        // Wait for the modal to appear, then monitor form submission
        const checkForModal = () => {
            const modal = document.getElementById('primarySalaryModal');
            if (modal && modal.classList.contains('show')) {
                console.log('✅ Salary modal detected, setting up form listener');
                this.monitorSalaryForm(step);
            } else {
                // Check again in a short while
                setTimeout(checkForModal, 100);
            }
        };
        
        // Start checking for modal
        setTimeout(checkForModal, 50);
    }

    monitorSalaryForm(step) {
        const form = document.getElementById('primarySalaryForm');
        
        if (form) {
            console.log('📝 Monitoring salary form submission');
            
            // Listen for successful form submission
            const handleFormSubmit = (event) => {
                console.log('💰 Salary form submitted');
                
                // Don't prevent submission - let it go through
                // Monitor for success indicators instead
                this.monitorSalarySuccess(step);
            };
            
            form.addEventListener('submit', handleFormSubmit, { once: true });
            
            // Also monitor for modal closure as a success indicator
            this.monitorModalClosure(step);
        } else {
            console.warn('⚠️ Salary form not found');
        }
    }

    monitorSalarySuccess(step) {
        console.log('👀 Monitoring for salary setup success...');
        
        // Watch for success indicators
        const checkForSuccess = () => {
            // Check if modal is closed (success indicator)
            const modal = document.getElementById('primarySalaryModal');
            if (!modal || !modal.classList.contains('show')) {
                console.log('✅ Modal closed - likely successful');
                this.completeSalaryStep(step);
                return;
            }
            
            // Check for success snackbar
            const snackbar = document.getElementById('snackbar');
            if (snackbar && snackbar.classList.contains('show') && snackbar.classList.contains('success')) {
                console.log('✅ Success snackbar detected');
                this.completeSalaryStep(step);
                return;
            }
            
            // Continue monitoring for a reasonable time
            setTimeout(checkForSuccess, 500);
        };
        
        // Start monitoring after a brief delay to allow submission to process
        setTimeout(checkForSuccess, 1000);
    }

    monitorModalClosure(step) {
        const modal = document.getElementById('primarySalaryModal');
        if (!modal) return;
        
        // Use MutationObserver to watch for class changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!modal.classList.contains('show')) {
                        console.log('🎯 Modal closed - checking if salary was completed');
                        observer.disconnect();
                        
                        // Check if salary was actually set up before completing
                        setTimeout(() => {
                            this.checkSalaryCompletionOnModalClose(step);
                        }, 500);
                    }
                }
            });
        });
        
        observer.observe(modal, { attributes: true });
    }

    checkSalaryCompletionOnModalClose(step) {
        console.log('🔍 Checking if salary was actually completed...');
        
        // Check the primary salary display element
        const primarySalaryAmount = document.getElementById('primarySalaryAmount');
        const salaryDisplayText = primarySalaryAmount ? primarySalaryAmount.textContent.trim() : '';
        
        // Check if salary amount is set (not ₵0.00 or empty)
        const hasSalarySet = salaryDisplayText && 
                            !salaryDisplayText.includes('₵0.00') && 
                            !salaryDisplayText.includes('₵0') &&
                            salaryDisplayText !== '₵0.00' &&
                            salaryDisplayText !== '₵0';
        
        console.log('💰 Salary display text:', salaryDisplayText);
        console.log('✅ Salary is set:', hasSalarySet);
        
        if (hasSalarySet) {
            console.log('✅ Salary was completed - proceeding with walkthrough');
            this.completeSalaryStep(step);
        } else {
            console.log('❌ Salary was not completed - showing reminder');
            this.handleIncompleteSalarySetup(step);
        }
    }

    handleIncompleteSalarySetup(step) {
        console.log('⚠️ User closed modal without completing salary setup');
        
        // Resume walkthrough to show the reminder
        this.resumeFromHiding();
        
        // Update tooltip to show reminder message
        if (this.tooltip) {
            const content = this.tooltip.querySelector('.tooltip-content');
            if (content) {
                content.innerHTML = `
                    <h3 style="color: #ff6b6b;">⚠️ Salary Setup Required</h3>
                    <p>You need to complete your salary setup to continue. This is essential for budget planning and goal tracking.</p>
                    <p style="font-weight: bold; color: #007bff;">Please click "Set Up Salary" and fill in your details.</p>
                `;
            }
        }
        
        // Set up monitoring again for the next attempt
        this.setupSalaryButtonMonitoring(this.currentTargetElement, step);
    }

    completeSalaryStep(step) {
        console.log('🎉 Completing salary setup step');
        
        // Re-enable automatic modal listeners
        this.isHandlingSalarySetup = false;
        
        // Update cached salary status
        this.isSalaryCompleted = true;
        
        // Restore navigation
        this.restoreNavigation();
        
        // Show walkthrough again
        this.resumeFromHiding();
        
        // Update the UI to show successful salary setup
        this.updateTooltipForSuccess();
        
        // Complete the walkthrough step
        setTimeout(() => {
            this.completeStep(step.step_name);
        }, 1000);
    }

    updateTooltipForSuccess() {
        if (this.tooltip) {
            this.tooltip.innerHTML = `
                <div class="tooltip-header">
                    <h4>✅ Salary Setup Complete!</h4>
                    <div class="tooltip-progress">
                        Step 2 of 5
                    </div>
                </div>
                <div class="tooltip-content">
                    <p>Great! Your salary has been configured. Now let's set up your budget to make the most of your income.</p>
                </div>
                <div class="tooltip-actions">
                    <p class="action-note"><i class="fas fa-check-circle"></i> Moving to budget setup...</p>
                </div>
            `;
        }
    }

    disableOtherElements(excludeElement) {
        // Only disable interactive elements, not all elements, and exclude the target element
        const interactiveSelectors = 'button, a, input, select, textarea, [onclick], [data-toggle], .btn, .card-link';
        const interactiveElements = document.querySelectorAll(interactiveSelectors);
        
        interactiveElements.forEach(el => {
            // Don't disable the target element or elements inside the tooltip
            if (el !== excludeElement && 
                !excludeElement.contains(el) && 
                !el.closest('.walkthrough-tooltip') &&
                !el.closest('.walkthrough-highlight')) {
                el.style.pointerEvents = 'none';
                el.classList.add('walkthrough-disabled');
            }
        });
    }

    async completeStep(stepName) {
        console.log('🚀 completeStep called with:', stepName);
        
        try {
            console.log('📡 Sending request to complete_step.php...');
            let response = await fetch('/budget/api/complete_walkthrough_step.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ step_name: stepName })
            });
            
            // If main endpoint fails, try test endpoint
            if (!response.ok) {
                console.log('⚠️ Main endpoint failed, trying test endpoint...');
                response = await fetch('/budget/api/test_complete_step.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ step_name: stepName })
                });
            }
            
            console.log('📡 Response status:', response.status);
            console.log('📡 Response ok:', response.ok);
            console.log('📡 Response headers:', Object.fromEntries(response.headers));
            
            // Get response text first to see what we're getting
            const responseText = await response.text();
            console.log('📡 Raw response text:', responseText);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ JSON Parse Error:', parseError);
                console.error('❌ Response was not valid JSON:', responseText);
                throw new Error('Invalid JSON response from server. Response: ' + responseText.substring(0, 200));
            }
            
            console.log('📡 Parsed response data:', data);
            
            if (data.success) {
                console.log('✅ Step completed successfully!');
                console.log('📝 Next step:', data.next_step);
                console.log('🔄 Redirect URL:', data.redirect_url);
                console.log('🏁 Is completed:', data.is_completed);
                
                this.completedSteps.push(stepName);
                this.currentStep = data.next_step;
                
                this.cleanup();
                
                // Handle navigation based on response
                if (data.is_completed) {
                    console.log('🎉 Walkthrough completed!');
                    // For initial setup, just cleanup without showing completion message or redirecting
                    console.log('✅ Initial setup complete - staying on current page');
                    return;
                } else if (data.next_step && data.redirect_url && stepName !== 'setup_budget') {
                    // Only redirect if it's not the budget step (we want to stay on budget page)
                    console.log('🔄 Redirecting to:', data.redirect_url);
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 500);
                } else {
                    console.log('📍 Staying on current page, no redirect needed');
                    // If we have a next step but we're staying on the same page, continue the walkthrough
                    if (data.next_step) {
                        console.log('🔄 Continuing walkthrough on same page...');
                        setTimeout(() => {
                            this.startWalkthrough();
                        }, 1000);
                    }
                }
            } else {
                console.error('❌ Step completion failed:', data.error);
                console.error('❌ Debug info:', data.debug);
                
                // Check if it's a "step not found" error - this may be normal for help guides
                if (data.error && data.error.includes('not found in database')) {
                    console.warn('⚠️ Step not found, but continuing gracefully:', stepName);
                    
                    // For help guides, missing steps are not critical - continue the tour
                    if (this.currentWalkthroughType === 'help_guide') {
                        console.log('📖 Help guide step missing, continuing tour...');
                        if (typeof showSnackbar === 'function') {
                            showSnackbar('Tour step completed', 'info');
                        }
                        this.cleanup();
                        return;
                    }
                    
                    // For initial setup, show a gentle warning but continue
                    if (typeof showSnackbar === 'function') {
                        showSnackbar('Step completed (database update needed)', 'warning');
                    }
                    this.cleanup();
                } else {
                    // Other errors are more serious
                    console.error('❌ Serious error:', data.error);
                    if (typeof showSnackbar === 'function') {
                        showSnackbar('Failed to complete step: ' + data.error, 'error');
                    } else {
                        this.showMessage('Error', 'Failed to complete step: ' + data.error, 'warning');
                    }
                }
            }
        } catch (error) {
            console.error('❌ Failed to complete step:', error);
            this.showMessage('Error', 'Network error completing step: ' + error.message, 'warning');
        }
    }

    async nextStep() {
        if (this.currentStep) {
            await this.completeStep(this.currentStep.step_name);
        }
    }

    async skipWalkthrough() {
        try {
            const response = await fetch('/budget/api/skip_walkthrough.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.cleanup();
                this.showSkipMessage();
            }
        } catch (error) {
            console.error('Failed to skip walkthrough:', error);
        }
    }

    showCompletionMessage() {
        // Show success message
        this.showMessage('🎉 Setup Complete!', 'You\'ve successfully completed the initial setup. Welcome to your budget dashboard!', 'success');
    }

    showSkipMessage() {
        if (typeof showSnackbar === 'function') {
            showSnackbar('Tour skipped. You can restart it anytime from your settings.', 'info');
        } else {
            this.showMessage('Tour Skipped', 'You can restart the tour anytime from your settings.', 'info');
        }
    }

    showWrongPageMessage(expectedStep, correctPage) {
        const stepMessages = {
            'setup_income': 'Please complete the "Set Up Income" step first by visiting your personal dashboard.',
            'configure_salary': 'Complete the salary configuration step first.',
            'setup_budget': 'Complete the budget setup step first.'
        };
        
        const message = stepMessages[expectedStep] || 'Please complete the previous step first.';
        
        this.showMessage('⚠️ Wrong Step', message, 'warning');
        
        // Optionally redirect after a delay
        if (correctPage) {
            setTimeout(() => {
                window.location.href = correctPage;
            }, 3000);
        }
    }

    showMessage(title, content, type = 'info') {
        const message = document.createElement('div');
        message.className = `walkthrough-message walkthrough-message-${type}`;
        message.innerHTML = `
            <div class="message-content">
                <h5>${title}</h5>
                <p>${content}</p>
                <button class="btn btn-sm btn-outline-secondary" onclick="this.parentElement.parentElement.remove()">Close</button>
            </div>
        `;
        
        document.body.appendChild(message);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (message.parentElement) {
                message.remove();
            }
        }, 5000);
    }

    cleanup() {
        this.isActive = false;
        
        // Remove overlay
        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }
        
        // Remove tooltip
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
        
        // Remove instruction
        if (this.instruction) {
            this.instruction.remove();
            this.instruction = null;
        }
        
        // Remove highlights and restore interactions
        document.querySelectorAll('.walkthrough-highlight').forEach(el => {
            el.classList.remove('walkthrough-highlight');
            el.style.position = '';
            el.style.zIndex = '';
            el.style.pointerEvents = '';
        });
        
        document.querySelectorAll('.walkthrough-disabled').forEach(el => {
            el.style.pointerEvents = '';
            el.classList.remove('walkthrough-disabled');
        });
    }

    temporarilyHide() {
        console.log('👻 Temporarily hiding walkthrough');
        this.isTemporarilyHidden = true;
        
        // Store references to elements before removing them
        this.hiddenElements = {};
        
        // Remove highlighting from target element
        if (this.currentTargetElement) {
            this.currentTargetElement.classList.remove('walkthrough-highlight');
            this.currentTargetElement.style.position = '';
            this.currentTargetElement.style.zIndex = '';
            this.currentTargetElement.style.pointerEvents = '';
        }
        
        // Completely remove overlay from DOM
        if (this.overlay) {
            this.hiddenElements.overlay = this.overlay;
            this.overlay.remove();
            this.overlay = null;
        }
        
        // Remove tooltip from DOM
        if (this.tooltip) {
            this.hiddenElements.tooltip = this.tooltip;
            this.tooltip.remove();
            this.tooltip = null;
        }
        
        // Remove instruction from DOM
        if (this.instruction) {
            this.hiddenElements.instruction = this.instruction;
            this.instruction.remove();
            this.instruction = null;
        }
        
        // Remove any disabled overlays on other elements
        document.querySelectorAll('.walkthrough-disabled').forEach(el => {
            el.style.pointerEvents = '';
            el.classList.remove('walkthrough-disabled');
        });
        
        console.log('✅ Walkthrough completely removed from DOM');
    }

    resumeFromHiding() {
        console.log('👀 Resuming walkthrough from hiding');
        this.isTemporarilyHidden = false;
        
        // Recreate overlay if it was removed
        if (!this.overlay && this.hiddenElements && this.hiddenElements.overlay) {
            this.overlay = this.hiddenElements.overlay;
            document.body.appendChild(this.overlay);
        } else if (!this.overlay) {
            this.createOverlay();
        }
        
        // Recreate tooltip if it was removed
        if (!this.tooltip && this.hiddenElements && this.hiddenElements.tooltip) {
            this.tooltip = this.hiddenElements.tooltip;
            document.body.appendChild(this.tooltip);
        }
        
        // Recreate instruction if it was removed
        if (!this.instruction && this.hiddenElements && this.hiddenElements.instruction) {
            this.instruction = this.hiddenElements.instruction;
            document.body.appendChild(this.instruction);
        }
        
        // Restore highlighting to target element
        if (this.currentTargetElement) {
            this.currentTargetElement.classList.add('walkthrough-highlight');
            this.currentTargetElement.style.position = 'relative';
            this.currentTargetElement.style.zIndex = '10001';
            this.currentTargetElement.style.pointerEvents = 'auto';
        }
        
        // Clear the hidden elements reference
        this.hiddenElements = null;
        
        console.log('✅ Walkthrough restored to DOM');
    }

    showGeneralHelpMessage(step) {
        console.log('📝 Showing general help message for step:', step.step_name);
        
        // Create overlay
        this.createOverlay();
        
        // Show a center tooltip with general help
        this.showCenterTooltip(step);
    }

    showCenterTooltip(step) {
        // Remove existing tooltip
        if (this.tooltip) {
            this.tooltip.remove();
        }

        this.tooltip = document.createElement('div');
        this.tooltip.className = 'walkthrough-tooltip center-tooltip';
        this.tooltip.innerHTML = `
            <div class="tooltip-content">
                <h3>${step.title}</h3>
                <p>${step.content}</p>
                <div class="tooltip-actions">
                    ${step.can_skip ? '<button class="tooltip-skip">Close Help</button>' : ''}
                </div>
            </div>
        `;

        // Position in center of screen
        this.tooltip.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10001;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            padding: 0;
            max-width: 400px;
            width: 90%;
        `;

        document.body.appendChild(this.tooltip);

        // Add close button functionality
        const closeBtn = this.tooltip.querySelector('.tooltip-skip');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.cleanup());
        }
    }

    addStyles() {
        if (document.getElementById('walkthrough-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'walkthrough-styles';
        styles.textContent = `
            .walkthrough-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 9998;
                pointer-events: none;
            }
            
            .walkthrough-overlay.modal-open {
                z-index: 1;
                background: rgba(0, 0, 0, 0.1);
                display: none;
            }
            
            .walkthrough-highlight {
                box-shadow: 
                    0 0 0 4px #007bff, 
                    0 0 0 8px rgba(0, 123, 255, 0.3),
                    0 0 0 9999px rgba(0, 0, 0, 0.7) !important;
                border-radius: 8px !important;
                transition: all 0.3s ease;
                position: relative !important;
                z-index: 10001 !important;
                background: white !important;
                color: #333 !important;
            }
            
            .walkthrough-highlight *,
            .walkthrough-highlight span,
            .walkthrough-highlight div,
            .walkthrough-highlight p,
            .walkthrough-highlight h1,
            .walkthrough-highlight h2,
            .walkthrough-highlight h3,
            .walkthrough-highlight h4,
            .walkthrough-highlight h5,
            .walkthrough-highlight h6,
            .walkthrough-highlight i,
            .walkthrough-highlight strong,
            .walkthrough-highlight em {
                color: #333 !important;
                text-shadow: none !important;
            }
            
            .walkthrough-highlight .btn,
            .walkthrough-highlight button {
                color: #333 !important;
                background-color: white !important;
                border-color: #333 !important;
            }
            
            .walkthrough-instruction {
                position: absolute;
                background: #007bff;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                z-index: 10002;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                width: 180px;
                animation: bounce 2s infinite;
            }
            
            .instruction-arrow {
                position: absolute;
                width: 0;
                height: 0;
                border: 6px solid transparent;
                border-right-color: #007bff;
                left: -12px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .walkthrough-instruction.instruction-left .instruction-arrow {
                border-right-color: transparent;
                border-left-color: #007bff;
                left: auto;
                right: -12px;
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-5px);
                }
                60% {
                    transform: translateY(-3px);
                }
            }
            
            .walkthrough-tooltip {
                position: absolute;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                max-width: 350px;
                z-index: 10003;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            
            .tooltip-header {
                padding: 16px 16px 8px;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .tooltip-header h4 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            }
            
            .tooltip-progress {
                font-size: 12px;
                color: #666;
                background: #f8f9fa;
                padding: 4px 8px;
                border-radius: 12px;
            }
            
            .tooltip-content {
                padding: 8px 16px 16px;
            }
            
            .tooltip-content p {
                margin: 0;
                font-size: 14px;
                line-height: 1.5;
                color: #555;
            }
            
            .tooltip-actions {
                padding: 0 16px 16px;
                display: flex;
                gap: 8px;
                align-items: center;
                flex-wrap: wrap;
            }
            
            .action-note {
                margin: 0;
                font-size: 12px;
                color: #007bff;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .tooltip-next, .tooltip-skip {
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .tooltip-next {
                background: #007bff;
                color: white;
            }
            
            .tooltip-next:hover {
                background: #0056b3;
            }
            
            .tooltip-skip {
                background: transparent;
                color: #6c757d;
                border: 1px solid #dee2e6;
            }
            
            .tooltip-skip:hover {
                background: #f8f9fa;
            }
            
            .walkthrough-message {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                z-index: 10004;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            }
            
            .walkthrough-message-success {
                border-left: 4px solid #28a745;
            }
            
            .walkthrough-message-info {
                border-left: 4px solid #17a2b8;
            }
            
            .walkthrough-message-warning {
                border-left: 4px solid #ffc107;
                background: #fff8e1;
            }
            
            .message-content {
                padding: 16px;
            }
            
            .message-content h5 {
                margin: 0 0 8px 0;
                font-size: 14px;
                font-weight: 600;
            }
            
            .message-content p {
                margin: 0 0 12px 0;
                font-size: 13px;
                line-height: 1.4;
                color: #666;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .walkthrough-disabled {
                opacity: 0.5;
                pointer-events: none;
            }
            
            .nav-blocked {
                opacity: 0.5;
                pointer-events: none;
                cursor: not-allowed !important;
                position: relative;
                color: #999 !important;
                text-decoration: none !important;
            }
            
            .nav-blocked:hover {
                color: #999 !important;
                background-color: transparent !important;
                transform: none !important;
            }
            
            .nav-block-indicator {
                position: absolute;
                top: -5px;
                right: -5px;
                font-size: 12px;
                z-index: 1001;
            }
            
            .salary-required-message {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border: 1px solid #ffc107;
                border-left: 4px solid #ffc107;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                z-index: 10004;
                max-width: 400px;
                width: 90%;
                animation: modalBounceIn 0.3s ease;
            }
            
            .message-actions {
                margin-top: 12px;
                display: flex;
                gap: 8px;
                align-items: center;
            }
            
            .message-actions .btn {
                padding: 6px 12px;
                font-size: 13px;
                border-radius: 4px;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .message-actions .btn-primary {
                background: #007bff;
                color: white;
            }
            
            .message-actions .btn-primary:hover {
                background: #0056b3;
            }
            
            .message-actions .btn-outline-secondary {
                background: transparent;
                color: #6c757d;
                border: 1px solid #dee2e6;
            }
            
            .message-actions .btn-outline-secondary:hover {
                background: #f8f9fa;
            }
            
            @keyframes modalBounceIn {
                0% {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.7);
                }
                50% {
                    transform: translate(-50%, -50%) scale(1.05);
                }
                100% {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
}

// Initialize walkthrough when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.budgetWalkthrough = new BudgetWalkthrough();
});