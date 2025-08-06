// Budget Page JavaScript - Fixed Version

// Global Variables
let budgetData = {
    income: 4150,
    categories: {
        needs: {
            planned: 2200,
            actual: 2075,
            items: {
                rent: { planned: 1200, actual: 1200, icon: '🏠' },
                groceries: { planned: 450, actual: 425, icon: '🍽️' },
                utilities: { planned: 200, actual: 180, icon: '⚡' },
                transport: { planned: 350, actual: 270, icon: '🚗' }
            }
        },
        wants: {
            planned: 1050,
            actual: 950,
            items: {
                dining: { planned: 300, actual: 280, icon: '🍽️' },
                entertainment: { planned: 250, actual: 270, icon: '🎬' },
                shopping: { planned: 500, actual: 400, icon: '🛍️' }
            }
        },
        savings: {
            planned: 800,
            actual: 850,
            items: {
                emergency: { planned: 400, actual: 450, icon: '🆘' },
                vacation: { planned: 200, actual: 200, icon: '🏖️' },
                investments: { planned: 200, actual: 200, icon: '📈' }
            }
        }
    }
};

let currentCategory = '';

// Snackbar function
function showSnackbar(message, type = '') {
    const snackbar = document.getElementById('snackbar');
    if (!snackbar) return;
    
    snackbar.textContent = message;
    snackbar.className = 'show';
    
    if (type) {
        snackbar.classList.add(type);
    }
    
    setTimeout(() => {
        snackbar.className = snackbar.className.replace('show', '');
        if (type) {
            snackbar.classList.remove(type);
        }
    }, 3000);
}

// Core Functions
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

// Category Management
function toggleCategory(categoryName) {
    const section = document.querySelector(`.${categoryName}-section`);
    if (!section) return;
    
    const content = section.querySelector('.category-content');
    const header = section.querySelector('.category-header');
    const icon = header.querySelector('.expand-icon');
    
    if (content && header && icon) {
        if (content.classList.contains('expanded')) {
            content.classList.remove('expanded');
            header.classList.remove('expanded');
            icon.textContent = '▼';
        } else {
            content.classList.add('expanded');
            header.classList.add('expanded');
            icon.textContent = '▲';
        }
    }
}

function switchView(viewType) {
    const buttons = document.querySelectorAll('.toggle-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    const activeBtn = document.querySelector(`[data-view="${viewType}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
    
    if (viewType === 'summary') {
        // Hide detailed budget items, show only category summaries
        document.querySelectorAll('.category-content').forEach(content => {
            content.style.display = 'none';
        });
        showSnackbar('Switched to summary view', 'info');
    } else {
        // Show detailed view
        document.querySelectorAll('.category-content').forEach(content => {
            content.style.display = 'block';
        });
        showSnackbar('Switched to detailed view', 'info');
    }
}

// Budget Item Management
function updateBudgetItem(input, itemId, type) {
    if (!input || !itemId || !type) return;
    
    const value = parseFloat(input.value) || 0;
    
    // Find the category this item belongs to
    let categoryName = '';
    for (const [catName, category] of Object.entries(budgetData.categories)) {
        if (category.items[itemId]) {
            categoryName = catName;
            break;
        }
    }
    
    if (categoryName && type === 'planned') {
        budgetData.categories[categoryName].items[itemId].planned = value;
        updateCategoryTotals(categoryName);
        updateBudgetSummary();
        showSnackbar(`Updated ${itemId} planned amount to ₵${value.toFixed(2)}`, 'success');
    }
}

function updateCategoryTotals(categoryName) {
    const category = budgetData.categories[categoryName];
    if (!category) return;
    
    let plannedTotal = 0;
    let actualTotal = 0;
    
    for (const item of Object.values(category.items)) {
        plannedTotal += item.planned;
        actualTotal += item.actual;
    }
    
    category.planned = plannedTotal;
    category.actual = actualTotal;
    
    // Update UI
    const section = document.querySelector(`.${categoryName}-section`);
    if (!section) return;
    
    const amountElement = section.querySelector('.category-amount');
    const progressFill = section.querySelector('.progress-fill');
    const progressText = section.querySelector('.progress-text');
    
    if (amountElement) {
        amountElement.textContent = `₵${actualTotal.toFixed(2)} / ₵${plannedTotal.toFixed(2)}`;
    }
    
    if (progressFill && progressText) {
        const percentage = plannedTotal > 0 ? Math.round((actualTotal / plannedTotal) * 100) : 0;
        progressFill.style.width = `${Math.min(percentage, 100)}%`;
        progressText.textContent = `${percentage}%`;
    }
}

function updateBudgetSummary() {
    let totalPlanned = 0;
    let totalActual = 0;
    
    for (const category of Object.values(budgetData.categories)) {
        totalPlanned += category.planned;
        totalActual += category.actual;
    }
    
    const remaining = totalPlanned - totalActual;
    const available = budgetData.income - totalPlanned;
    
    // Update summary cards
    const summaryCards = document.querySelectorAll('.summary-card');
    if (summaryCards.length >= 4) {
        const plannedElement = summaryCards[0].querySelector('.summary-amount');
        const plannedDetail = summaryCards[0].querySelector('.summary-detail');
        if (plannedElement && plannedDetail) {
            plannedElement.textContent = `₵${totalPlanned.toFixed(2)}`;
            plannedDetail.textContent = `${((totalPlanned/budgetData.income)*100).toFixed(1)}% of income`;
        }
        
        const actualElement = summaryCards[1].querySelector('.summary-amount');
        const actualDetail = summaryCards[1].querySelector('.summary-detail');
        if (actualElement && actualDetail) {
            actualElement.textContent = `₵${totalActual.toFixed(2)}`;
            actualDetail.textContent = `${((totalActual/budgetData.income)*100).toFixed(1)}% of income`;
        }
        
        const remainingElement = summaryCards[2].querySelector('.summary-amount');
        const remainingDetail = summaryCards[2].querySelector('.summary-detail');
        if (remainingElement && remainingDetail) {
            remainingElement.textContent = `₵${remaining.toFixed(2)}`;
            remainingElement.className = `summary-amount ${remaining >= 0 ? 'positive' : 'negative'}`;
            remainingDetail.textContent = `${((remaining/budgetData.income)*100).toFixed(1)}% unspent`;
        }
        
        const availableElement = summaryCards[3].querySelector('.summary-amount');
        const availableDetail = summaryCards[3].querySelector('.summary-detail');
        if (availableElement && availableDetail) {
            availableElement.textContent = `₵${available.toFixed(2)}`;
            availableDetail.textContent = available >= 0 ? 'Unallocated funds' : 'Over allocated';
        }
    }
}

// Modal Functions
function showAddCategoryModal() {
    showModal('addCategoryModal');
}

function showAddItemModal(categoryName) {
    currentCategory = categoryName;
    showModal('addItemModal');
}

function showBudgetTemplateModal() {
    showModal('budgetTemplateModal');
}

function editBudgetItem(itemId) {
    showSnackbar(`Edit functionality for ${itemId} coming soon`, 'info');
}

function addExpense(itemId) {
    currentCategory = itemId;
    const modal = document.getElementById('addExpenseModal');
    if (modal) {
        const categoryInput = modal.querySelector('input[name="expenseCategory"]');
        if (categoryInput) {
            categoryInput.value = itemId.charAt(0).toUpperCase() + itemId.slice(1);
        }
        
        // Set today's date
        const dateInput = modal.querySelector('input[name="expenseDate"]');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
    }
    
    showModal('addExpenseModal');
}

// Budget Period Management
function changeBudgetPeriod() {
    const periodSelect = document.getElementById('budgetPeriod');
    if (periodSelect) {
        const period = periodSelect.value;
        showSnackbar(`Switched to ${period} budget`, 'info');
        // Here you would typically load the budget data for the selected period
    }
}

function copyFromPreviousMonth() {
    showSnackbar('Copied budget from previous month', 'success');
    // Implementation would copy actual values from previous month to planned values
    // For demo purposes, we'll just update a few values
    setTimeout(() => {
        updateBudgetSummary();
    }, 500);
}

function saveBudget() {
    const saveButton = event.target;
    if (!saveButton) return;
    
    const originalText = saveButton.textContent;
    
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;
    
    setTimeout(() => {
        showSnackbar('Budget saved successfully!', 'success');
        saveButton.textContent = originalText;
        saveButton.disabled = false;
    }, 1000);
}

function exportBudget() {
    showSnackbar('Budget export functionality coming soon', 'info');
    // Implementation would generate CSV/PDF export
}

// Template Functions
function applyTemplate(templateType) {
    const income = budgetData.income;
    let needsPercent, wantsPercent, savingsPercent;
    
    switch(templateType) {
        case '50-30-20':
            needsPercent = 50;
            wantsPercent = 30;
            savingsPercent = 20;
            break;
        case '60-20-20':
            needsPercent = 60;
            wantsPercent = 20;
            savingsPercent = 20;
            break;
        case '40-40-20':
            needsPercent = 40;
            wantsPercent = 40;
            savingsPercent = 20;
            break;
        case 'aggressive-savings':
            needsPercent = 45;
            wantsPercent = 25;
            savingsPercent = 30;
            break;
        default:
            return;
    }
    
    // Calculate new amounts
    const needsAmount = (income * needsPercent) / 100;
    const wantsAmount = (income * wantsPercent) / 100;
    const savingsAmount = (income * savingsPercent) / 100;
    
    // Update budget data
    budgetData.categories.needs.planned = needsAmount;
    budgetData.categories.wants.planned = wantsAmount;
    budgetData.categories.savings.planned = savingsAmount;
    
    // Distribute amounts across items proportionally
    distributeAmountAcrossItems('needs', needsAmount);
    distributeAmountAcrossItems('wants', wantsAmount);
    distributeAmountAcrossItems('savings', savingsAmount);
    
    // Update UI
    updateAllCategoryDisplays();
    
    closeModal('budgetTemplateModal');
    showSnackbar(`Applied ${getTemplateName(templateType)} template successfully!`, 'success');
}

function getTemplateName(templateType) {
    const names = {
        '50-30-20': '50/30/20 Rule',
        '60-20-20': 'Conservative',
        '40-40-20': 'Balanced',
        'aggressive-savings': 'Aggressive Savings'
    };
    return names[templateType] || templateType;
}

function distributeAmountAcrossItems(categoryName, totalAmount) {
    const items = budgetData.categories[categoryName]?.items;
    if (!items) return;
    
    const itemCount = Object.keys(items).length;
    const amountPerItem = totalAmount / itemCount;
    
    for (const itemId of Object.keys(items)) {
        items[itemId].planned = amountPerItem;
        
        // Update input field in UI
        const input = document.querySelector(`input[onchange*="${itemId}"]`);
        if (input) {
            input.value = amountPerItem.toFixed(2);
        }
    }
}

function updateAllCategoryDisplays() {
    ['needs', 'wants', 'savings'].forEach(categoryName => {
        updateCategoryTotals(categoryName);
    });
    updateBudgetSummary();
}

// Icon Selection
function selectIcon(iconElement) {
    if (!iconElement) return;
    
    const container = iconElement.closest('.icon-selector');
    if (!container) return;
    
    const icons = container.querySelectorAll('.icon-option');
    const hiddenInput = container.nextElementSibling;
    
    icons.forEach(icon => icon.classList.remove('selected'));
    iconElement.classList.add('selected');
    
    if (hiddenInput && hiddenInput.type === 'hidden') {
        hiddenInput.value = iconElement.dataset.icon;
    }
}

// Form Handlers
function handleAddCategory(formData) {
    const categoryData = {
        name: formData.get('categoryName'),
        type: formData.get('categoryType'),
        icon: formData.get('categoryIcon'),
        description: formData.get('description')
    };
    
    console.log('New category:', categoryData);
    showSnackbar(`Category "${categoryData.name}" added successfully!`, 'success');
    
    // Here you would add the category to the budget data and update the UI
    // For now, we'll just close the modal
    closeModal('addCategoryModal');
}

function handleAddItem(formData) {
    const itemData = {
        name: formData.get('itemName'),
        planned: parseFloat(formData.get('plannedAmount')),
        icon: formData.get('itemIcon'),
        description: formData.get('description'),
        category: currentCategory
    };
    
    console.log('New budget item:', itemData);
    showSnackbar(`Budget item "${itemData.name}" added to ${currentCategory}!`, 'success');
    
    // Here you would add the item to the appropriate category
    // For now, we'll just close the modal
    closeModal('addItemModal');
}

function handleAddExpense(formData) {
    const expenseData = {
        category: formData.get('expenseCategory'),
        amount: parseFloat(formData.get('expenseAmount')),
        date: formData.get('expenseDate'),
        description: formData.get('expenseDescription')
    };
    
    console.log('New expense:', expenseData);
    showSnackbar(`Expense of ₵${expenseData.amount.toFixed(2)} added!`, 'success');
    
    // Here you would update the actual spending for the category
    // For now, we'll just close the modal
    closeModal('addExpenseModal');
}

// Event Listeners Setup
function setupEventListeners() {
    // Modal form handlers
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            const originalText = submitButton.textContent;
            
            submitButton.textContent = 'Adding...';
            submitButton.disabled = true;
            
            setTimeout(() => {
                handleAddCategory(formData);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                this.reset();
                
                // Reset icon selection
                const firstIcon = this.querySelector('.icon-option');
                if (firstIcon) {
                    this.querySelectorAll('.icon-option').forEach(icon => icon.classList.remove('selected'));
                    firstIcon.classList.add('selected');
                    const hiddenInput = this.querySelector('input[name="categoryIcon"]');
                    if (hiddenInput) {
                        hiddenInput.value = firstIcon.dataset.icon;
                    }
                }
            }, 1000);
        });
    }
    
    const addItemForm = document.getElementById('addItemForm');
    if (addItemForm) {
        addItemForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            const originalText = submitButton.textContent;
            
            submitButton.textContent = 'Adding...';
            submitButton.disabled = true;
            
            setTimeout(() => {
                handleAddItem(formData);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                this.reset();
                
                // Reset icon selection
                const firstIcon = this.querySelector('.icon-option');
                if (firstIcon) {
                    this.querySelectorAll('.icon-option').forEach(icon => icon.classList.remove('selected'));
                    firstIcon.classList.add('selected');
                    const hiddenInput = this.querySelector('input[name="itemIcon"]');
                    if (hiddenInput) {
                        hiddenInput.value = firstIcon.dataset.icon;
                    }
                }
            }, 1000);
        });
    }
    
    const addExpenseForm = document.getElementById('addExpenseForm');
    if (addExpenseForm) {
        addExpenseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            const originalText = submitButton.textContent;
            
            submitButton.textContent = 'Adding...';
            submitButton.disabled = true;
            
            setTimeout(() => {
                handleAddExpense(formData);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                this.reset();
            }, 1000);
        });
    }
    
    // Icon selection handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('icon-option')) {
            selectIcon(e.target);
        }
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Close user dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.querySelector('.user-menu');
        const dropdown = document.getElementById('userDropdown');
        
        if (userMenu && dropdown && !userMenu.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
}

// Initialize page
function initializePage() {
    // Initialize budget summary
    updateBudgetSummary();
    
    // Initialize category states (expand needs by default)
    const needsCategory = document.querySelector('.needs-section .category-content');
    if (needsCategory) {
        needsCategory.classList.add('expanded');
        const needsHeader = document.querySelector('.needs-section .category-header');
        if (needsHeader) {
            needsHeader.classList.add('expanded');
            const needsIcon = needsHeader.querySelector('.expand-icon');
            if (needsIcon) {
                needsIcon.textContent = '▲';
            }
        }
    }
    
    // Setup event listeners
    setupEventListeners();
    
    console.log('Budget page initialized successfully');
}

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage);
} else {
    initializePage();
}

// Utility Functions
function formatCurrency(amount) {
    return `₵${amount.toFixed(2)}`;
}

function calculateVariance(planned, actual) {
    return actual - planned;
}

function getVarianceClass(variance) {
    if (variance > 0) return 'warning'; // Over budget
    if (variance < 0) return 'success'; // Under budget
    return 'success'; // On target
}

function getStatusText(planned, actual) {
    const variance = calculateVariance(planned, actual);
    const percentageVariance = Math.abs(variance / planned) * 100;
    
    if (variance === 0) return 'On Track';
    if (variance < 0) return 'Under Budget';
    if (percentageVariance <= 10) return 'Near Target';
    return 'Over Budget';
}

function getStatusClass(planned, actual) {
    const variance = calculateVariance(planned, actual);
    const percentageVariance = Math.abs(variance / planned) * 100;
    
    if (variance === 0) return 'on-track';
    if (variance < 0) return 'under-budget';
    if (variance > 0 && percentageVariance > 10) return 'over-budget';
    if (variance > 0) return 'exceeded';
    return 'on-track';
}

// Export functions for global access (if needed)
window.budgetFunctions = {
    toggleCategory,
    switchView,
    updateBudgetItem,
    showAddCategoryModal,
    showAddItemModal,
    showBudgetTemplateModal,
    editBudgetItem,
    addExpense,
    changeBudgetPeriod,
    copyFromPreviousMonth,
    saveBudget,
    exportBudget,
    applyTemplate,
    toggleUserMenu,
    closeModal,
    showModal
};