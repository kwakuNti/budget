// Enhanced Personal Dashboard JavaScript

// Theme Management
let currentTheme = localStorage.getItem('personalTheme') || 'default';

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Clear any old localStorage demo data
    localStorage.removeItem('personalTransactions');
    
    initializeDashboard();
    applyTheme(currentTheme);
    updateActiveThemeOption(currentTheme);
    loadPersonalDashboardData(); // Load data from API
});

// API Integration Functions
async function loadPersonalDashboardData() {
    try {
        const response = await fetch('../api/personal_dashboard_data.php');
        if (!response.ok) {
            throw new Error('Failed to fetch dashboard data');
        }
        
        const data = await response.json();
        
        if (data.success) {
            populateDashboardWithData(data);
        } else {
            console.error('API Error:', data.message);
            showNotification('Failed to load dashboard data: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showNotification('Failed to load dashboard data', 'error');
    }
}

function populateDashboardWithData(data) {
    
    // Update user info
    if (data.user) {
        const userAvatar = document.querySelector('.user-avatar');
        const welcomeMessage = document.getElementById('welcomeMessage');
        const logoUserName = document.getElementById('logoUserName');
        
        if (userAvatar && data.user.initials) {
            userAvatar.textContent = data.user.initials;
        }
        if (welcomeMessage && data.user.first_name) {
            welcomeMessage.textContent = `Welcome back, ${data.user.first_name}!`;
        }
        if (logoUserName) {
            logoUserName.textContent = data.user.first_name || 'Personal';
        }
    } else {
    }
    
    // Update salary information
    const totalMonthlyIncome = parseFloat(data.financial_overview?.monthly_income || 0);
    const baseSalary = parseFloat(data.salary?.monthly_salary || 0);
    const additionalIncome = totalMonthlyIncome - baseSalary;
    
    if (data.salary && data.salary.monthly_salary) {
        const salaryDueInfo = document.getElementById('salaryDueInfo');
        const monthlySalary = document.getElementById('monthlySalary');
        const salaryAllocationTitle = document.getElementById('salaryAllocationTitle');
        
        if (salaryDueInfo && data.salary.next_pay_date) {
            const nextPayDate = new Date(data.salary.next_pay_date);
            const formattedDate = nextPayDate.toLocaleDateString('en-GB', { 
                day: 'numeric', 
                month: 'short', 
                year: 'numeric' 
            });
            salaryDueInfo.innerHTML = `Your next salary (₵${baseSalary.toLocaleString()}) is due on <strong>${formattedDate}</strong>`;
        }
        if (monthlySalary) {
            if (additionalIncome > 0) {
                monthlySalary.textContent = `Total Monthly Income: ₵${totalMonthlyIncome.toLocaleString()} (₵${baseSalary.toLocaleString()} salary + ₵${additionalIncome.toLocaleString()} additional)`;
            } else {
                monthlySalary.textContent = `Monthly Income: ₵${totalMonthlyIncome.toLocaleString()}`;
            }
        }
        if (salaryAllocationTitle) {
            salaryAllocationTitle.textContent = `Budget Allocation & Preview (₵${totalMonthlyIncome.toLocaleString()} total income)`;
        }
    } else {
        const salaryDueInfo = document.getElementById('salaryDueInfo');
        const monthlySalary = document.getElementById('monthlySalary');
        const salaryAllocationTitle = document.getElementById('salaryAllocationTitle');
        
        if (salaryDueInfo) {
            salaryDueInfo.innerHTML = '📝 <strong>Please set up your salary information to get started</strong>';
        }
        if (monthlySalary) {
            if (totalMonthlyIncome > 0) {
                monthlySalary.textContent = `Total Monthly Income: ₵${totalMonthlyIncome.toLocaleString()} (Set up salary for better tracking)`;
            } else {
                monthlySalary.textContent = 'Monthly Income: Not set';
            }
        }
        if (salaryAllocationTitle) {
            salaryAllocationTitle.textContent = 'Budget Allocation & Preview (Set up income first)';
        }
    }
    
    // Update financial overview cards
    updateFinancialOverviewCards(data);
    
    // Update budget allocation
    updateBudgetAllocation(data.budget_allocation || []);
    
    // Update recent transactions
    updateRecentTransactions(data.recent_transactions || []);
    
    // Update savings goals
    updateSavingsGoals(data.financial_goals || []);
    
    // Update insights
    updateInsights(data);
}

function updateFinancialOverviewCards(data) {
    const currentBalance = document.getElementById('currentBalance');
    const balanceChange = document.getElementById('balanceChange');
    const monthlyIncome = document.getElementById('monthlyIncome');
    const nextSalaryDate = document.getElementById('nextSalaryDate');
    const monthlyExpenses = document.getElementById('monthlyExpenses');
    const budgetRemaining = document.getElementById('budgetRemaining');
    const autoSavings = document.getElementById('autoSavings');
    const savingsPercentage = document.getElementById('savingsPercentage');
    
    // Current Balance
    if (currentBalance && data.financial_overview) {
        const balance = parseFloat(data.financial_overview.available_balance) || 0;
        currentBalance.textContent = `₵${balance.toLocaleString()}`;
        
        if (balanceChange) {
            const change = parseFloat(data.financial_overview.expense_change_percentage) || 0;
            const changeClass = change >= 0 ? 'positive' : 'negative';
            const changeSign = change >= 0 ? '+' : '';
            balanceChange.className = `change ${changeClass}`;
            balanceChange.textContent = `${changeSign}${Math.abs(change).toFixed(1)}% this month`;
        }
    } else {
        if (currentBalance) currentBalance.textContent = '₵0.00';
        if (balanceChange) balanceChange.textContent = 'No data yet';
    }
    
    // Monthly Income
    if (monthlyIncome && data.financial_overview) {
        const income = parseFloat(data.financial_overview.monthly_income) || 0;
        monthlyIncome.textContent = `₵${income.toLocaleString()}`;
        
        if (nextSalaryDate && data.salary && data.salary.next_pay_date) {
            const nextDate = new Date(data.salary.next_pay_date);
            const formattedDate = nextDate.toLocaleDateString('en-GB', { 
                day: 'numeric', 
                month: 'short' 
            });
            nextSalaryDate.textContent = `Next salary: ${formattedDate}`;
        } else if (nextSalaryDate) {
            nextSalaryDate.textContent = 'Set up salary first';
        }
    } else {
        if (monthlyIncome) monthlyIncome.textContent = '₵0.00';
        if (nextSalaryDate) nextSalaryDate.textContent = 'Set up salary first';
    }
    
    // Monthly Expenses
    if (monthlyExpenses && data.financial_overview) {
        const expenses = parseFloat(data.financial_overview.monthly_expenses) || 0;
        monthlyExpenses.textContent = `₵${expenses.toLocaleString()}`;
        
        if (budgetRemaining) {
            const income = parseFloat(data.financial_overview.monthly_income) || 0;
            const remaining = income - expenses;
            if (income > 0) {
                budgetRemaining.textContent = `₵${remaining.toLocaleString()} left in budget`;
            } else {
                budgetRemaining.textContent = 'Set up income first';
            }
        }
    } else {
        if (monthlyExpenses) monthlyExpenses.textContent = '₵0.00';
        if (budgetRemaining) budgetRemaining.textContent = 'No budget set';
    }
    
    // Auto Savings (calculate from total monthly income and savings rate)
    if (autoSavings && data.financial_overview && totalMonthlyIncome > 0) {
        const savingsRate = parseFloat(data.financial_overview.savings_rate) || 0;
        const savings = (totalMonthlyIncome * savingsRate) / 100;
        autoSavings.textContent = `₵${savings.toLocaleString()}`;
        
        if (savingsPercentage) {
            savingsPercentage.textContent = `${savingsRate.toFixed(0)}% of total income saved`;
        }
    } else {
        if (autoSavings) autoSavings.textContent = '₵0.00';
        if (savingsPercentage) {
            if (totalMonthlyIncome <= 0) {
                savingsPercentage.textContent = 'Set up income to enable auto-savings';
            } else {
                savingsPercentage.textContent = '0% of income saved';
            }
        }
    }
}

function updateBudgetAllocation(allocations) {
    // Update both budget allocation preview and spending progress
    updateBudgetAllocationPreview(allocations);
    updateSpendingProgress(allocations);
}

function updateBudgetAllocationPreview(allocations) {
    const budgetAllocationPreview = document.getElementById('budgetAllocationPreview');
    const previewBasedOnSalary = document.getElementById('previewBasedOnSalary');
    const previewTotalAllocated = document.getElementById('previewTotalAllocated');
    
    if (!budgetAllocationPreview) return;
    
    // Calculate total monthly income from window variables
    const salary = parseFloat(window.userSalary) || 0;
    const additionalIncome = parseFloat(window.userAdditionalIncome) || 0;
    const totalMonthlyIncome = salary + additionalIncome;
    
    if (allocations.length === 0) {
        // Hide the preview section when no data
        budgetAllocationPreview.style.display = 'none';
        return;
    }
    
    budgetAllocationPreview.style.display = 'block';
    
    // Update total display to show it's based on total monthly income
    if (previewBasedOnSalary) {
        previewBasedOnSalary.textContent = `₵${totalMonthlyIncome.toLocaleString()}`;
    }
    if (previewTotalAllocated) {
        const totalPercentage = allocations.reduce((sum, allocation) => sum + (allocation.percentage || 0), 0);
        previewTotalAllocated.textContent = `${totalPercentage}%`;
    }
    
    // Update each category
    allocations.forEach(allocation => {
        const categoryType = allocation.category_type;
        const allocated = parseFloat(allocation.allocated_amount) || 0;
        const percentage = allocation.percentage || 0;
        
        // Update percentage display
        const percentElement = document.getElementById(`preview${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}Percent`);
        if (percentElement) {
            percentElement.textContent = `${percentage}%`;
        }
        
        // Update amount display
        const amountElement = document.getElementById(`preview${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}Amount`);
        if (amountElement) {
            amountElement.textContent = `₵${allocated.toLocaleString()}`;
        }
    });
}

function updateSpendingProgress(allocations) {
    const allocationGrid = document.getElementById('allocationGrid');
    const spendingProgressSection = document.getElementById('spendingProgressSection');
    
    if (!allocationGrid) return;
    
    allocationGrid.innerHTML = '';
    
    if (allocations.length === 0) {
        // Hide spending progress section when no data
        if (spendingProgressSection) {
            spendingProgressSection.style.display = 'none';
        }
        allocationGrid.innerHTML = `
            <div class="no-data-message">
                <div class="no-data-icon">📊</div>
                <h4>No Budget Allocation Set Up</h4>
                <p>Set up your monthly salary first, then create your budget allocation to track spending across categories.</p>
                <button class="btn-primary" onclick="showSalarySetupModal()">Set Up Salary & Budget</button>
            </div>
        `;
        return;
    }
    
    // Show spending progress section when data exists
    if (spendingProgressSection) {
        spendingProgressSection.style.display = 'block';
    }
    
    allocations.forEach(allocation => {
        const spent = parseFloat(allocation.spent) || 0;
        const allocated = parseFloat(allocation.allocated_amount) || 0;
        const remaining = allocated - spent;
        const percentage = allocated > 0 ? (spent / allocated) * 100 : 0;
        
        const statusClass = percentage > 90 ? 'overspent' : percentage > 75 ? 'warning' : 'good';
        const statusIcon = percentage > 90 ? '⚠️' : percentage > 75 ? '⚠️' : '✅';
        const statusText = percentage > 90 ? 'Over budget!' : percentage > 75 ? 'Getting close to limit' : 'On track';
        
        const allocationItem = document.createElement('div');
        allocationItem.className = `allocation-item ${allocation.category_type}`;
        allocationItem.innerHTML = `
            <div class="allocation-header">
                <span class="allocation-icon">${getCategoryIcon(allocation.category_type)}</span>
                <div class="allocation-info">
                    <h4>${allocation.category_type.charAt(0).toUpperCase() + allocation.category_type.slice(1)} (${allocation.percentage}%)</h4>
                    <p>₵${allocated.toLocaleString()} allocated</p>
                </div>
            </div>
            <div class="allocation-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${Math.min(percentage, 100)}%"></div>
                </div>
                <span class="progress-text">₵${spent.toLocaleString()} spent • ₵${remaining.toLocaleString()} left</span>
            </div>
            <div class="allocation-status ${statusClass}">${statusIcon} ${statusText}</div>
        `;
        
        allocationGrid.appendChild(allocationItem);
    });
}

function getCategoryIcon(categoryType) {
    const icons = {
        'needs': '🏠',
        'wants': '🎮',
        'savings': '💰'
    };
    return icons[categoryType] || '📊';
}

function updateRecentTransactions(transactions) {
    const transactionsList = document.getElementById('recentTransactions');
    if (!transactionsList) return;
    
    transactionsList.innerHTML = '';
    
    if (transactions.length === 0) {
        transactionsList.innerHTML = `
            <div class="no-data-message">
                <div class="no-data-icon">💳</div>
                <h4>No Transactions Yet</h4>
                <p>Start tracking your income and expenses to see your transaction history here.</p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem;">
                    <button class="btn-primary" onclick="showAddIncomeModal()">Add Income</button>
                    <button class="btn-primary" onclick="showAddExpenseModal()">Add Expense</button>
                </div>
            </div>
        `;
        return;
    }
    
    transactions.forEach(transaction => {
        const amount = parseFloat(transaction.amount);
        const isIncome = transaction.type === 'income';
        const typeClass = isIncome ? 'income' : transaction.type;
        const amountPrefix = isIncome ? '+' : '-';
        const icon = getTransactionIcon(transaction.type, transaction.category_name);
        
        const transactionItem = document.createElement('div');
        transactionItem.className = 'transaction-item';
        transactionItem.innerHTML = `
            <div class="transaction-icon ${typeClass}">${icon}</div>
            <div class="transaction-details">
                <span class="transaction-name">${transaction.description || transaction.category_name}</span>
                <span class="transaction-category">${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)} • ${transaction.category_name}</span>
            </div>
            <div class="transaction-amount ${typeClass}">${amountPrefix}₵${amount.toLocaleString()}</div>
        `;
        
        transactionsList.appendChild(transactionItem);
    });
}

function getTransactionIcon(type, category) {
    if (type === 'income') return '💵';
    if (type === 'savings') return '💰';
    
    // Expense icons based on category
    const categoryIcons = {
        'Food': '🛒',
        'Transport': '⛽',
        'Housing': '🏠',
        'Entertainment': '🎮',
        'Shopping': '🛍️',
        'Healthcare': '🏥',
        'Education': '📚',
        'Utilities': '💡'
    };
    
    return categoryIcons[category] || '💸';
}

function updateSavingsGoals(goals) {
    const savingsGoals = document.getElementById('savingsGoals');
    if (!savingsGoals) return;
    
    savingsGoals.innerHTML = '';
    
    if (goals.length === 0) {
        savingsGoals.innerHTML = `
            <div class="no-data-message">
                <div class="no-data-icon">🎯</div>
                <h4>No Savings Goals Yet</h4>
                <p>Set up savings goals to track your progress towards financial targets like emergency funds, vacations, or major purchases.</p>
                <button class="btn-primary" onclick="showAddGoalModal()">Add Savings Goal</button>
            </div>
        `;
        return;
    }
    
    goals.forEach(goal => {
        const currentAmount = parseFloat(goal.current_amount) || 0;
        const targetAmount = parseFloat(goal.target_amount) || 0;
        const monthlyTarget = parseFloat(goal.monthly_target) || 0;
        const progress = targetAmount > 0 ? (currentAmount / targetAmount) * 100 : 0;
        
        const goalItem = document.createElement('div');
        goalItem.className = 'goal-item';
        goalItem.innerHTML = `
            <div class="goal-header">
                <span class="goal-name">${goal.goal_name}</span>
                <span class="goal-interval">Monthly: ₵${monthlyTarget.toLocaleString()}</span>
            </div>
            <div class="goal-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${Math.min(progress, 100)}%"></div>
                </div>
                <span class="goal-text">₵${currentAmount.toLocaleString()} / ₵${targetAmount.toLocaleString()}</span>
            </div>
        `;
        
        savingsGoals.appendChild(goalItem);
    });
}

function updateInsights(data) {
    const insightsGrid = document.getElementById('insightsGrid');
    if (!insightsGrid) return;
    
    insightsGrid.innerHTML = '';
    
    // Generate insights based on data
    const insights = generateInsights(data);
    
    insights.forEach(insight => {
        const insightCard = document.createElement('div');
        insightCard.className = `insight-card ${insight.type}`;
        insightCard.innerHTML = `
            <div class="insight-icon">${insight.icon}</div>
            <div class="insight-content">
                <h4>${insight.title}</h4>
                <p>${insight.description}</p>
                <button class="insight-action" onclick="${insight.action || 'void(0)'}">${insight.actionText || 'Learn More'}</button>
            </div>
        `;
        
        insightsGrid.appendChild(insightCard);
    });
}

function generateInsights(data) {
    const insights = [];
    const totalMonthlyIncome = parseFloat(data.financial_overview?.monthly_income || 0);
    
    // Check if user needs to set up income first
    if (totalMonthlyIncome <= 0) {
        insights.push({
            type: 'info',
            icon: '🚀',
            title: 'Get Started',
            description: 'Set up your monthly income and budget allocation to start tracking your personal finances effectively.',
            actionText: 'Set Up Income',
            action: 'showSalarySetupModal()'
        });
        return insights;
    }
    
    // Budget allocation insights
    if (data.budget_allocation && data.budget_allocation.length > 0) {
        data.budget_allocation.forEach(allocation => {
            const spent = parseFloat(allocation.spent) || 0;
            const allocated = parseFloat(allocation.allocated_amount) || 0;
            const percentage = allocated > 0 ? (spent / allocated) * 100 : 0;
            
            if (percentage > 90) {
                insights.push({
                    type: 'warning',
                    icon: '⚠️',
                    title: 'Budget Alert',
                    description: `You've spent ${percentage.toFixed(0)}% of your '${allocation.category_type}' budget. Consider tracking expenses more closely.`,
                    actionText: 'View Categories',
                    action: 'showCategoryDetails()'
                });
            }
        });
    }
    
    // Savings opportunity insight
    if (data.financial_overview && totalMonthlyIncome > 0) {
        const savingsRate = parseFloat(data.financial_overview.savings_rate) || 0;
        
        if (savingsRate < 20) {
            insights.push({
                type: 'suggestion',
                icon: '💡',
                title: 'Savings Opportunity',
                description: `You're currently saving ${savingsRate.toFixed(0)}% of your total income. Financial experts recommend saving at least 20% for better financial health.`,
                actionText: 'Adjust Budget',
                action: 'showBudgetModal()'
            });
        } else if (savingsRate >= 20) {
            insights.push({
                type: 'success',
                icon: '🎯',
                title: 'Great Savings Rate!',
                description: `You're saving ${savingsRate.toFixed(0)}% of your total income, which exceeds the recommended 20%. Keep up the excellent work!`,
                actionText: 'View Goals'
            });
        }
    }
    
    // Default insight if no specific insights and income is set
    if (insights.length === 0 && totalMonthlyIncome > 0) {
        insights.push({
            type: 'info',
            icon: '💡',
            title: 'Good Financial Health',
            description: 'Your budget allocation looks healthy. Keep tracking your expenses to maintain good financial habits.',
            actionText: 'View Reports'
        });
    }
    
    return insights;
}

// Dashboard state - now populated from API
let dashboardState = {
    salary: 0,
    payFrequency: 'monthly',
    nextPayDate: null,
    budgetAllocation: {
        needs: 0,
        wants: 0,
        savings: 0
    },
    currentBalance: 0,
    monthlySpending: {
        needs: 0,
        wants: 0,
        savings: 0
    }
};

// Theme Functions
function toggleThemeSelector() {
    const dropdown = document.getElementById('themeDropdown');
    dropdown.classList.toggle('show');
    
    // Close user dropdown if open
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown && userDropdown.classList.contains('show')) {
        userDropdown.classList.remove('show');
    }
}

function changeTheme(themeName) {
    currentTheme = themeName;
    applyTheme(themeName);
    updateActiveThemeOption(themeName);
    
    // Save theme preference
    localStorage.setItem('personalTheme', themeName);
    
    // Close theme dropdown
    const dropdown = document.getElementById('themeDropdown');
    dropdown.classList.remove('show');
    
    // Show notification
    showNotification(`Theme changed to ${getThemeDisplayName(themeName)}`, 'success');
}

function applyTheme(themeName) {
    document.documentElement.setAttribute('data-theme', themeName);
    
    // Add smooth transition class
    document.body.classList.add('theme-transition');
    setTimeout(() => {
        document.body.classList.remove('theme-transition');
    }, 300);
}

function updateActiveThemeOption(themeName) {
    // Remove active class from all theme options
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('active');
    });
    
    // Add active class to selected theme
    const activeOption = document.querySelector(`[data-theme="${themeName}"]`);
    if (activeOption) {
        activeOption.classList.add('active');
    }
}

function getThemeDisplayName(themeName) {
    const themeNames = {
        'default': 'Ocean Blue',
        'forest': 'Forest Green',
        'sunset': 'Sunset Orange',
        'purple': 'Royal Purple',
        'rose': 'Rose Pink',
        'dark': 'Dark Mode'
    };
    return themeNames[themeName] || 'Ocean Blue';
}

function initializeDashboard() {
    // Load saved data
    loadSavedData();
    
    // Setup event listeners
    setupModalListeners();
    setupFormSubmissions();
    setupAllocationSliders();
    
    // Load dashboard data
    loadDashboardData();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const userMenu = document.querySelector('.user-menu');
        const themeSelector = document.querySelector('.theme-selector');
        const userDropdown = document.getElementById('userDropdown');
        const themeDropdown = document.getElementById('themeDropdown');
        
        // Close user dropdown if clicking outside
        if (userDropdown && !userMenu.contains(e.target)) {
            userDropdown.classList.remove('show');
        }
        
        // Close theme dropdown if clicking outside
        if (themeDropdown && !themeSelector.contains(e.target)) {
            themeDropdown.classList.remove('show');
        }
    });
}

// Load saved data from localStorage
function loadSavedData() {
    const savedState = localStorage.getItem('personalDashboardState');
    if (savedState) {
        dashboardState = { ...dashboardState, ...JSON.parse(savedState) };
    }
}

// Save data to localStorage
function saveData() {
    localStorage.setItem('personalDashboardState', JSON.stringify(dashboardState));
}

// User Menu Functions
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Modal Functions
function showSalarySetupModal() {
    populateSalaryModal();
    showModal('salarySetupModal');
}

function showAddIncomeModal() {
    showModal('addIncomeModal');
}

function showAddExpenseModal() {
    populateExpenseCategories();
    showModal('addExpenseModal');
}

function showBudgetModal() {
    showSalarySetupModal(); // Same modal for now
}

function showTransferModal() {
    showNotification('Transfer feature coming soon!', 'info');
}

function showAddGoalModal() {
    showNotification('Savings goal setup coming soon! For now, use the salary setup to configure your savings allocation.', 'info');
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    const firstInput = modal.querySelector('input');
    if (firstInput) {
        firstInput.focus();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }, 300);
}

function setupModalListeners() {
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            const modalId = e.target.id;
            closeModal(modalId);
        }
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

// Setup allocation sliders
function setupAllocationSliders() {
    const sliders = document.querySelectorAll('.allocation-slider');
    sliders.forEach(slider => {
        slider.addEventListener('input', updateAllocationDisplay);
    });
}

function updateAllocationDisplay() {
    const needsSlider = document.querySelector('[data-category="needs"]');
    const wantsSlider = document.querySelector('[data-category="wants"]');
    const savingsSlider = document.querySelector('[data-category="savings"]');
    
    if (!needsSlider || !wantsSlider || !savingsSlider) return;
    
    const needs = parseInt(needsSlider.value);
    const wants = parseInt(wantsSlider.value);
    const savings = parseInt(savingsSlider.value);
    
    // Update display values
    updateSliderDisplay('needs', needs);
    updateSliderDisplay('wants', wants);
    updateSliderDisplay('savings', savings);
    
    // Update total
    const total = needs + wants + savings;
    const totalElement = document.getElementById('totalAllocation');
    if (totalElement) {
        totalElement.textContent = `${total}%`;
        totalElement.style.color = total === 100 ? '#059669' : '#dc2626';
    }
}

function updateSliderDisplay(category, percentage) {
    const percentElement = document.querySelector(`[data-category="${category}"]`).parentNode.querySelector('.allocation-percent');
    const amountElement = document.querySelector(`[data-category="${category}"]`).parentNode.querySelector('.allocation-amount');
    
    if (percentElement) percentElement.textContent = `${percentage}%`;
    if (amountElement) {
        const amount = (dashboardState.salary * percentage / 100).toLocaleString('en-GH', { minimumFractionDigits: 2 });
        amountElement.textContent = `₵${amount}`;
    }
}

// Form Submission Functions
function setupFormSubmissions() {
    // Salary Setup Form
    const salaryModal = document.getElementById('salarySetupModal');
    const salaryForm = salaryModal?.querySelector('form');
    if (salaryForm) {
        salaryForm.addEventListener('submit', handleSalarySetup);
    }

    // Add Income Form
    const addIncomeModal = document.getElementById('addIncomeModal');
    const incomeForm = addIncomeModal?.querySelector('form');
    if (incomeForm) {
        incomeForm.addEventListener('submit', handleAddIncome);
    }

    // Add Expense Form
    const addExpenseModal = document.getElementById('addExpenseModal');
    const expenseForm = addExpenseModal?.querySelector('form');
    if (expenseForm) {
        expenseForm.addEventListener('submit', handleAddExpense);
    }
}

function handleSalarySetup(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const salary = parseFloat(e.target.querySelector('input[type="number"]').value);
    const frequency = e.target.querySelector('select').value;
    const nextPayDate = e.target.querySelector('input[type="date"]').value;
    
    // Get allocation percentages
    const needsSlider = document.querySelector('[data-category="needs"]');
    const wantsSlider = document.querySelector('[data-category="wants"]');
    const savingsSlider = document.querySelector('[data-category="savings"]');
    
    const needs = parseInt(needsSlider?.value || 50);
    const wants = parseInt(wantsSlider?.value || 30);
    const savings = parseInt(savingsSlider?.value || 20);
    
    // Validate total is 100%
    if (needs + wants + savings !== 100) {
        showNotification('Budget allocation must total 100%', 'error');
        return;
    }
    
    // Disable submit button and show loading
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Setting up...';
    
    // Prepare data for API
    const apiData = new FormData();
    apiData.append('action', 'setup_salary_budget');
    apiData.append('monthlySalary', salary);
    apiData.append('payFrequency', frequency);
    apiData.append('nextPayDate', nextPayDate);
    apiData.append('needsPercent', needs);
    apiData.append('wantsPercent', wants);
    apiData.append('savingsPercent', savings);
    
    // Make API call
    fetch('../actions/personal_dashboard_actions.php', {
        method: 'POST',
        body: apiData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('salarySetupModal');
            showNotification(data.message, 'success');
            // Reload dashboard data to reflect changes
            setTimeout(() => {
                loadPersonalDashboardData();
            }, 500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to save salary information', 'error');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function handleAddIncome(e) {
    e.preventDefault();
    
    const amount = parseFloat(e.target.querySelector('input[type="number"]').value);
    const source = e.target.querySelector('select').value;
    const description = e.target.querySelector('input[type="text"]').value;
    
    if (!amount || !source || !description) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    if (amount <= 0) {
        showNotification('Amount must be greater than 0', 'error');
        return;
    }
    
    // Disable submit button and show loading
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    // Prepare data for API
    const apiData = new FormData();
    apiData.append('action', 'add_income');
    apiData.append('amount', amount);
    apiData.append('source', source);
    apiData.append('description', description);
    apiData.append('incomeDate', new Date().toISOString().split('T')[0]);
    
    // Make API call
    fetch('../actions/personal_dashboard_actions.php', {
        method: 'POST',
        body: apiData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('addIncomeModal');
            showNotification(data.message, 'success');
            // Reload dashboard data to reflect changes
            setTimeout(() => {
                loadPersonalDashboardData();
            }, 500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to add income', 'error');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function handleAddExpense(e) {
    e.preventDefault();
    
    const amount = parseFloat(e.target.querySelector('input[type="number"]').value);
    const category = e.target.querySelector('select').value;
    const description = e.target.querySelector('input[type="text"]').value;
    
    if (!amount || !category || !description) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    if (amount <= 0) {
        showNotification('Amount must be greater than 0', 'error');
        return;
    }
    
    // Disable submit button and show loading
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    // Prepare data for API
    const apiData = new FormData();
    apiData.append('action', 'add_expense');
    apiData.append('amount', amount);
    apiData.append('category', category);
    apiData.append('description', description);
    apiData.append('expenseDate', new Date().toISOString().split('T')[0]);
    
    // Make API call
    fetch('../actions/personal_dashboard_actions.php', {
        method: 'POST',
        body: apiData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('addExpenseModal');
            showNotification(data.message, 'success');
            // Reload dashboard data to reflect changes
            setTimeout(() => {
                loadPersonalDashboardData();
            }, 500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to add expense', 'error');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Budget calculation functions
function calculateBudgetLimit(category) {
    if (!dashboardState || !dashboardState.budgetAllocation || !dashboardState.salary) {
        return 0;
    }
    const percentage = dashboardState.budgetAllocation[category];
    return (dashboardState.salary * percentage / 100);
}

function updateBudgetSpending(category, amount) {
    if (!dashboardState.monthlySpending) {
        dashboardState.monthlySpending = {};
    }
    dashboardState.monthlySpending[category] = (dashboardState.monthlySpending[category] || 0) + amount;
    saveData();
}

// Data Management Functions - now handled by API
// Note: Transaction management is now handled entirely through the API

function loadDashboardData() {
    updateSalaryInfo();
    updateOverviewCards();
    updateBudgetAllocation();
    // Recent transactions now loaded via API in loadPersonalDashboardData()
    updateFinancialInsights();
}

function updateSalaryInfo() {
    // Update welcome section
    const salaryBadge = document.querySelector('.salary-badge');
    if (salaryBadge) {
        salaryBadge.textContent = `${dashboardState.payFrequency.charAt(0).toUpperCase() + dashboardState.payFrequency.slice(1)} Salary: ₵${dashboardState.salary.toLocaleString('en-GH')}`;
    }
    
    // Update next pay date
    const nextPayText = document.querySelector('.welcome-content p');
    if (nextPayText) {
        const payDate = new Date(dashboardState.nextPayDate).toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
        nextPayText.innerHTML = `Your next salary (₵${dashboardState.salary.toLocaleString('en-GH')}) is due on <strong>${payDate}</strong>`;
    }
}

function updateOverviewCards() {
    // Update balance
    const balanceAmount = document.querySelector('.balance-card .amount');
    if (balanceAmount) {
        balanceAmount.textContent = `₵${dashboardState.currentBalance.toLocaleString('en-GH', { minimumFractionDigits: 2 })}`;
    }
    
    // Update income
    const incomeAmount = document.querySelector('.income-card .amount');
    if (incomeAmount) {
        incomeAmount.textContent = `₵${dashboardState.salary.toLocaleString('en-GH', { minimumFractionDigits: 2 })}`;
    }
    
    // Update expenses
    const totalExpenses = (dashboardState.monthlySpending.needs || 0) + (dashboardState.monthlySpending.wants || 0);
    const expenseAmount = document.querySelector('.expense-card .amount');
    if (expenseAmount) {
        expenseAmount.textContent = `₵${totalExpenses.toLocaleString('en-GH', { minimumFractionDigits: 2 })}`;
    }
    
    // Update savings
    const savingsAmount = document.querySelector('.savings-card .amount');
    const expectedSavings = calculateBudgetLimit('savings');
    if (savingsAmount) {
        savingsAmount.textContent = `₵${expectedSavings.toLocaleString('en-GH', { minimumFractionDigits: 2 })}`;
    }
}

function updateBudgetAllocation() {
    const allocationItems = document.querySelectorAll('.allocation-item');
    
    allocationItems.forEach(item => {
        const category = item.classList.contains('needs') ? 'needs' : 
                        item.classList.contains('wants') ? 'wants' : 'savings';
        
        const percentage = dashboardState.budgetAllocation[category];
        const allocated = calculateBudgetLimit(category);
        const spent = dashboardState.monthlySpending[category] || 0;
        const remaining = allocated - spent;
        const progressPercentage = Math.min((spent / allocated) * 100, 100);
        
        // Update allocation info
        const allocationInfo = item.querySelector('.allocation-info p');
        if (allocationInfo) {
            allocationInfo.textContent = `₵${allocated.toLocaleString('en-GH')} allocated`;
        }
        
        // Update progress bar
        const progressFill = item.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = `${progressPercentage}%`;
        }
        
        // Update progress text
        const progressText = item.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = `₵${spent.toLocaleString('en-GH')} spent • ₵${Math.max(0, remaining).toLocaleString('en-GH')} left`;
        }
        
        // Update status
        const status = item.querySelector('.allocation-status');
        if (status) {
            if (spent > allocated) {
                status.textContent = '⚠️ Over budget';
                status.className = 'allocation-status overspent';
            } else if (spent > allocated * 0.8) {
                status.textContent = '⚠️ Getting close to limit';
                status.className = 'allocation-status warning';
            } else {
                status.textContent = '✅ On track';
                status.className = 'allocation-status good';
            }
        }
    });
}

// Note: Recent transactions are now loaded via API in updateRecentTransactions()

// Note: Transaction elements are now created in updateRecentTransactions() using API data

function getTransactionIcon(type, category) {
    const icons = {
        income: {
            salary: '💵',
            freelance: '💻',
            'side-work': '🔧',
            gift: '🎁',
            investment: '📈',
            other: '💰'
        },
        expense: {
            'needs-food': '🛒',
            'needs-transport': '⛽',
            'needs-utilities': '💡',
            'needs-rent': '🏠',
            'needs-healthcare': '🏥',
            'wants-entertainment': '🎬',
            'wants-shopping': '🛍️',
            'wants-dining': '🍽️',
            'wants-hobbies': '🎮',
            needs: '🏠',
            wants: '🎮'
        },
        savings: '💰'
    };
    
    if (type === 'savings') return icons.savings;
    return icons[type]?.[category] || (type === 'income' ? '💵' : '💸');
}

function updateFinancialInsights() {
    // This would typically calculate insights based on spending patterns
    // For now, we'll update based on current spending vs budget
    
    const needsSpent = dashboardState.monthlySpending.needs || 0;
    const needsLimit = calculateBudgetLimit('needs');
    const needsPercentage = (needsSpent / needsLimit) * 100;
    
    const wantsSpent = dashboardState.monthlySpending.wants || 0;
    const wantsLimit = calculateBudgetLimit('wants');
    
    // Update insights based on spending patterns
    const insightCards = document.querySelectorAll('.insight-card');
    
    // Update food spending insight if needs spending is high
    if (needsPercentage > 80) {
        const foodInsight = insightCards[0];
        if (foodInsight) {
            const content = foodInsight.querySelector('.insight-content p');
            if (content) {
                content.textContent = `You've spent ₵${needsSpent.toFixed(0)} on needs this month (${needsPercentage.toFixed(0)}% of budget). Consider reviewing your essential expenses.`;
            }
        }
    }
}

// Modal population functions
function populateSalaryModal() {
    const modal = document.getElementById('salarySetupModal');
    if (!modal) return;
    
    // Populate current values
    const salaryInput = modal.querySelector('input[type="number"]');
    const frequencySelect = modal.querySelector('select');
    const dateInput = modal.querySelector('input[type="date"]');
    
    if (salaryInput) salaryInput.value = dashboardState.salary;
    if (frequencySelect) frequencySelect.value = dashboardState.payFrequency;
    if (dateInput) dateInput.value = dashboardState.nextPayDate;
    
    // Set slider values
    const needsSlider = modal.querySelector('[data-category="needs"]');
    const wantsSlider = modal.querySelector('[data-category="wants"]');
    const savingsSlider = modal.querySelector('[data-category="savings"]');
    
    if (needsSlider) needsSlider.value = dashboardState.budgetAllocation.needs;
    if (wantsSlider) wantsSlider.value = dashboardState.budgetAllocation.wants;
    if (savingsSlider) savingsSlider.value = dashboardState.budgetAllocation.savings;
    
    // Update display
    updateAllocationDisplay();
}

function populateExpenseCategories() {
    const modal = document.getElementById('addExpenseModal');
    if (!modal) return;
    
    const select = modal.querySelector('select');
    if (!select) return;
    
    // Calculate remaining budget for each category
    const needsLimit = calculateBudgetLimit('needs');
    const needsSpent = dashboardState.monthlySpending.needs || 0;
    const needsRemaining = needsLimit - needsSpent;
    
    const wantsLimit = calculateBudgetLimit('wants');
    const wantsSpent = dashboardState.monthlySpending.wants || 0;
    const wantsRemaining = wantsLimit - wantsSpent;
    
    // Update optgroup labels with remaining amounts
    const needsOptgroup = select.querySelector('optgroup[label*="Needs"]');
    const wantsOptgroup = select.querySelector('optgroup[label*="Wants"]');
    
    if (needsOptgroup) {
        needsOptgroup.label = `Needs (${dashboardState.budgetAllocation.needs}% - ₵${needsRemaining.toFixed(0)} left)`;
    }
    
    if (wantsOptgroup) {
        wantsOptgroup.label = `Wants (${dashboardState.budgetAllocation.wants}% - ₵${wantsRemaining.toFixed(0)} left)`;
    }
}

// Notification System
function showNotification(message, type = 'info') {
    let notification = document.getElementById('notification');
    if (!notification) {
        notification = createNotificationElement();
        document.body.appendChild(notification);
    }
    
    const messageElement = notification.querySelector('.notification-message');
    messageElement.textContent = message;
    
    notification.className = `notification ${type} show`;
    
    setTimeout(() => {
        hideNotification();
    }, 4000);
}

function createNotificationElement() {
    const notification = document.createElement('div');
    notification.id = 'notification';
    notification.className = 'notification';
    notification.innerHTML = `
        <span class="notification-message"></span>
        <button class="notification-close" onclick="hideNotification()">&times;</button>
    `;
    
    return notification;
}

function hideNotification() {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.classList.remove('show');
    }
}

// Utility Functions
function formatCurrency(amount) {
    return `₵${amount.toLocaleString('en-GH', { minimumFractionDigits: 2 })}`;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

// Financial advice functions
function generateFinancialAdvice() {
    const advice = [];
    
    const totalIncome = dashboardState.salary;
    const totalExpenses = (dashboardState.monthlySpending.needs || 0) + (dashboardState.monthlySpending.wants || 0);
    const savingsRate = ((dashboardState.monthlySpending.savings || 0) / totalIncome) * 100;
    
    // Check savings rate
    if (savingsRate < 20) {
        advice.push({
            type: 'warning',
            title: 'Low Savings Rate',
            message: `Your current savings rate is ${savingsRate.toFixed(1)}%. Consider increasing to at least 20% for better financial health.`,
            action: 'Adjust Budget'
        });
    }
    
    // Check needs spending
    const needsPercentage = ((dashboardState.monthlySpending.needs || 0) / totalIncome) * 100;
    if (needsPercentage > 50) {
        advice.push({
            type: 'warning',
            title: 'High Essential Spending',
            message: `You're spending ${needsPercentage.toFixed(1)}% on needs. Look for ways to reduce essential expenses.`,
            action: 'View Tips'
        });
    }
    
    // Check wants spending
    const wantsPercentage = ((dashboardState.monthlySpending.wants || 0) / totalIncome) * 100;
    if (wantsPercentage > 30) {
        advice.push({
            type: 'tip',
            title: 'High Discretionary Spending',
            message: `Consider reducing wants spending from ${wantsPercentage.toFixed(1)}% to 30% or less.`,
            action: 'Review Expenses'
        });
    }
    
    return advice;
}

// Demo data initialization removed - using real API data only

// Budget analysis functions
function analyzeBudget() {
    const analysis = {
        totalBudgeted: dashboardState.salary,
        totalSpent: Object.values(dashboardState.monthlySpending).reduce((sum, amount) => sum + amount, 0),
        categoryAnalysis: {}
    };
    
    Object.keys(dashboardState.budgetAllocation).forEach(category => {
        const budgeted = calculateBudgetLimit(category);
        const spent = dashboardState.monthlySpending[category] || 0;
        const remaining = budgeted - spent;
        const utilizationRate = (spent / budgeted) * 100;
        
        analysis.categoryAnalysis[category] = {
            budgeted,
            spent,
            remaining,
            utilizationRate,
            status: utilizationRate > 100 ? 'over' : utilizationRate > 80 ? 'warning' : 'good'
        };
    });
    
    return analysis;
}

// Notification System
function showNotification(message, type = 'info', duration = 3000) {
    // Remove existing notification if any
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto hide after duration
    setTimeout(() => {
        closeNotification(notification);
    }, duration);
}

function closeNotification(element) {
    const notification = element.closest ? element.closest('.notification') : element;
    if (notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

// Add smooth transition class for theme changes
const style = document.createElement('style');
style.textContent = `
    .theme-transition * {
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease !important;
    }
`;
document.head.appendChild(style);

// Navigation functions
function navigateToSalarySetup() {
    // Pass current dashboard state to salary setup page
    const totalIncomeText = document.getElementById('monthlySalary')?.textContent || '';
    const totalIncomeMatch = totalIncomeText.match(/₵([\d,]+(?:\.\d{2})?)/);
    const totalIncomeAmount = totalIncomeMatch ? totalIncomeMatch[1].replace(',', '') : '';
    
    const params = new URLSearchParams();
    if (totalIncomeAmount) {
        params.append('amount', totalIncomeAmount);
        params.append('type', 'total_income');
    }
    params.append('from', 'dashboard');
    
    window.location.href = 'salary.php?' + params.toString();
}

function showTransferModal() {
    showNotification('Transfer feature coming soon!', 'info');
}

// Show snackbar notification
function showSnackbar(message, type = 'info') {
    // Remove any existing snackbar
    const existingSnackbar = document.querySelector('.snackbar');
    if (existingSnackbar) {
        existingSnackbar.remove();
    }
    
    // Create snackbar element
    const snackbar = document.createElement('div');
    snackbar.className = `snackbar snackbar-${type}`;
    snackbar.innerHTML = `
        <div class="snackbar-content">
            <span class="snackbar-icon">${getSnackbarIcon(type)}</span>
            <span class="snackbar-message">${message}</span>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(snackbar);
    
    // Trigger animation
    setTimeout(() => {
        snackbar.classList.add('show');
    }, 100);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        snackbar.classList.remove('show');
        setTimeout(() => {
            if (snackbar.parentNode) {
                snackbar.remove();
            }
        }, 300);
    }, 4000);
}

// Get appropriate icon for snackbar type
function getSnackbarIcon(type) {
    switch (type) {
        case 'success': return '✅';
        case 'error': return '❌';
        case 'warning': return '⚠️';
        case 'info': 
        default: return 'ℹ️';
    }
}

// Show salary paid modal
function showSalaryPaidModal() {
    showModal('salaryPaidModal');
}

// Confirm salary from dashboard
function confirmSalaryFromDashboard() {
    const confirmBtn = document.querySelector('#salaryPaidModal .btn-primary');
    const originalText = confirmBtn.textContent;
    
    confirmBtn.textContent = 'Processing...';
    confirmBtn.disabled = true;
    
    fetch('../actions/salary_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=confirm_salary_received'
    })
    .then(response => {
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showSnackbar(result.message, 'success');
            closeModal('salaryPaidModal');
            
            // Reload dashboard data
            setTimeout(() => {
                loadDashboardData();
            }, 1000);
        } else {
            showSnackbar(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showSnackbar('Failed to confirm salary', 'error');
    })
    .finally(() => {
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
    });
}

// Export functions for potential future use
window.dashboardFunctions = {
    showSalarySetupModal,
    showAddIncomeModal,
    showAddExpenseModal,
    showBudgetModal,
    closeModal,
    toggleUserMenu,
    toggleThemeSelector,
    changeTheme,
    showNotification,
    analyzeBudget,
    generateFinancialAdvice,
    navigateToSalarySetup,
    showSalaryPaidModal,
    confirmSalaryFromDashboard
};