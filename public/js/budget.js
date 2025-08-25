// Budget Page JavaScript - Enhanced with Allocation Logic

// Global Variables
let budgetData = null;
let currentEditingCategory = null;
let budgetAllocation = null; // Store current budget allocation (80/10/10 etc.)
let currentBudgetPeriod = {
    month: 8, // August
    year: 2025
};

// Utility Functions
function formatCurrency(amount) {
    // Handle null, undefined, NaN, or invalid values
    if (amount === null || amount === undefined || isNaN(amount) || amount === '') {
        return '₵0.00';
    }
    
    // Convert to number and ensure it's valid
    let numAmount = parseFloat(amount);
    if (isNaN(numAmount)) {
        return '₵0.00';
    }
    
    // Round to 2 decimal places to avoid floating point precision issues
    numAmount = Math.round(numAmount * 100) / 100;
    
    return `₵${numAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

function formatPercent(value) {
    // Handle null, undefined, NaN, or invalid values
    if (value === null || value === undefined || isNaN(value) || value === '') {
        return '0.0%';
    }
    
    // Convert to number and ensure it's valid
    const numValue = parseFloat(value);
    if (isNaN(numValue)) {
        return '0.0%';
    }
    
    return `${numValue.toFixed(1)}%`;
}

function getIconHTML(iconName) {
    // Convert icon name to FontAwesome HTML
    if (!iconName) return '<i class="fas fa-tag"></i>';
    
    // If it's already HTML (contains '<i'), return as is
    if (iconName.includes('<i')) return iconName;
    
    // If it's just the icon name, convert to FontAwesome
    return `<i class="fas fa-${iconName}"></i>`;
}

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
        success: '✓',
        error: '✗',
        warning: '⚠',
        info: 'ℹ'
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

// Budget input type toggle
function toggleBudgetInputType() {
    const amountRadio = document.querySelector('input[name="budget_input_type"][value="amount"]');
    const percentageRadio = document.querySelector('input[name="budget_input_type"][value="percentage"]');
    const amountInput = document.getElementById('amountInput');
    const percentageInput = document.getElementById('percentageInput');
    const percentageInfo = document.querySelector('.percentage-info');
    
    if (!amountRadio || !percentageRadio || !amountInput || !percentageInput) {
        console.warn('Budget input type elements not found');
        return;
    }
    
    if (amountRadio.checked) {
        amountInput.style.display = 'flex';
        percentageInput.style.display = 'none';
        if (percentageInfo) percentageInfo.classList.remove('show');
        // Make amount input required, remove from percentage
        const amountInputField = amountInput.querySelector('input');
        const percentageInputField = percentageInput.querySelector('input');
        if (amountInputField) amountInputField.required = true;
        if (percentageInputField) percentageInputField.required = false;
    } else if (percentageRadio.checked) {
        amountInput.style.display = 'none';
        percentageInput.style.display = 'flex';
        if (percentageInfo) percentageInfo.classList.add('show');
        // Make percentage input required, remove from amount
        const amountInputField = amountInput.querySelector('input');
        const percentageInputField = percentageInput.querySelector('input');
        if (amountInputField) amountInputField.required = false;
        if (percentageInputField) percentageInputField.required = true;
        
        // Update percentage calculation display
        updatePercentageCalculation();
    }
}

// Update percentage calculation display
function updatePercentageCalculation() {
    const categoryTypeSelect = document.querySelector('select[name="category_type"]');
    const percentageCalculation = document.getElementById('percentageCalculation');
    const percentageInput = document.querySelector('input[name="budget_percentage"]');
    
    if (!categoryTypeSelect.value || !budgetAllocation) {
        percentageCalculation.textContent = 'Select category type first';
        return;
    }
    
    const categoryType = categoryTypeSelect.value;
    let allocationAmount = 0;
    let sectionName = '';
    
    switch(categoryType) {
        case 'needs':
            allocationAmount = parseFloat(budgetAllocation.needs_amount) || 0;
            sectionName = 'Needs';
            break;
        case 'wants':
            allocationAmount = parseFloat(budgetAllocation.wants_amount) || 0;
            sectionName = 'Wants';
            break;
        case 'savings':
            allocationAmount = parseFloat(budgetAllocation.savings_amount) || 0;
            sectionName = 'Savings';
            break;
    }
    
    const percentage = parseFloat(percentageInput.value) || 0;
    const calculatedAmount = (allocationAmount * percentage) / 100;
    
    if (allocationAmount > 0) {
        percentageCalculation.innerHTML = `${percentage}% of ${sectionName} allocation (${formatCurrency(calculatedAmount)} of ${formatCurrency(allocationAmount)})`;
    } else {
        percentageCalculation.textContent = `of ${sectionName} allocation (no allocation set)`;
    }
}

// Enhanced add category form submission
function enhancedSubmitAddCategory(formData) {
    const budgetInputType = document.querySelector('input[name="budget_input_type"]:checked').value;
    const categoryType = formData.get('category_type');
    const budgetPeriod = formData.get('budget_period') || 'monthly';
    
    let finalBudgetLimit = 0;
    
    if (budgetInputType === 'amount') {
        finalBudgetLimit = parseFloat(formData.get('budget_limit')) || 0;
    } else {
        // Calculate from percentage
        const percentage = parseFloat(formData.get('budget_percentage')) || 0;
        let allocationAmount = 0;
        
        if (budgetAllocation) {
            switch(categoryType) {
                case 'needs':
                    allocationAmount = parseFloat(budgetAllocation.needs_amount) || 0;
                    break;
                case 'wants':
                    allocationAmount = parseFloat(budgetAllocation.wants_amount) || 0;
                    break;
                case 'savings':
                    allocationAmount = parseFloat(budgetAllocation.savings_amount) || 0;
                    break;
            }
        }
        
        finalBudgetLimit = (allocationAmount * percentage) / 100;
        
        // Ensure we don't get NaN
        if (isNaN(finalBudgetLimit)) {
            finalBudgetLimit = 0;
        }
    }
    
    // Properly handle budget limit precision to avoid floating point errors
    // Use parseFloat and toFixed to ensure proper decimal precision
    const budgetLimitValue = parseFloat(finalBudgetLimit.toFixed(2));
    
    // Update the form data with calculated amount and budget period
    formData.set('budget_limit', budgetLimitValue.toString());
    formData.set('budget_period', budgetPeriod);
    
    return budgetLimitValue;
}

// Budget Period Management
function changeBudgetPeriod(month, year) {
    currentBudgetPeriod = { month, year };
    
    // Update period display
    const monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' });
    const periodDisplay = document.getElementById('currentPeriodDisplay');
    if (periodDisplay) {
        periodDisplay.textContent = `${monthName} ${year}`;
    }
    
    // Reload budget data for the new period
    loadBudgetDataForPeriod(month, year);
    showSnackbar(`Switched to ${monthName} ${year}`, 'success');
}

function formatPeriodName(period) {
    if (typeof period === 'object') {
        const monthName = new Date(period.year, period.month - 1).toLocaleString('default', { month: 'long' });
        return `${monthName} ${period.year}`;
    }
    
    const parts = period.split('-');
    if (parts.length === 2) {
        const month = parts[0].charAt(0).toUpperCase() + parts[0].slice(1);
        const year = parts[1];
        return `${month} ${year}`;
    }
    return period;
}

function copyFromPreviousMonth() {
    const currentMonth = currentBudgetPeriod.month;
    const currentYear = currentBudgetPeriod.year;
    
    let previousMonth = currentMonth - 1;
    let previousYear = currentYear;
    
    if (previousMonth === 0) {
        previousMonth = 12;
        previousYear--;
    }
    
    const monthName = getMonthName(previousMonth);
    
    if (confirm(`Copy budget categories from ${monthName} ${previousYear}?`)) {
        copyBudgetFromPeriod(previousMonth, previousYear, currentMonth, currentYear);
    }
}

function copyBudgetFromPeriod(fromMonth, fromYear, toMonth, toYear) {
    const formData = new FormData();
    formData.append('from_month', fromMonth);
    formData.append('from_year', fromYear);
    formData.append('to_month', toMonth);
    formData.append('to_year', toYear);
    
    fetch('../api/budget_period.php?action=copy_budget', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar(`Successfully copied budget from ${getMonthName(fromMonth)} ${fromYear}`, 'success');
            loadBudgetDataForPeriod(toMonth, toYear);
        } else {
            showSnackbar('Error copying budget: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error copying budget:', error);
        showSnackbar('Error copying budget. Please try again.', 'error');
    });
}

function loadBudgetDataForPeriod(month, year) {
    // Show loading indicator
    const loadingElement = document.getElementById('budgetLoadingIndicator');
    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
    
    fetch(`../api/budget_period.php?action=get_period_data&month=${month}&year=${year}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateBudgetDisplay(data.data);
        } else {
            showSnackbar('Error loading period data: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error loading period data:', error);
        showSnackbar('Error loading period data. Please try again.', 'error');
    })
    .finally(() => {
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    });
}

function updateBudgetDisplay(data) {
    // Update categories display
    if (data.categories) {
        updateCategoriesTable(data.categories);
    }
    
    // Update allocation display
    if (data.allocation) {
        updateAllocationDisplay(data.allocation);
    }
    
    // Update period indicator
    if (data.period) {
        const periodDisplay = document.getElementById('currentPeriodDisplay');
        if (periodDisplay) {
            periodDisplay.textContent = `${data.period.month_name} ${data.period.year}`;
        }
    }
    
    // Update summary statistics
    updateBudgetSummary(data);
}

function updateCategoriesTable(categories) {
    const tableBody = document.querySelector('#budgetCategoriesTable tbody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    categories.forEach(category => {
        const row = document.createElement('tr');
        const budgetAmount = category.budget_amount || 0;
        const spentAmount = category.spent_amount || 0;
        const variance = budgetAmount - spentAmount;
        const varianceClass = variance >= 0 ? 'positive' : 'negative';
        
        row.innerHTML = `
            <td>${category.category_name}</td>
            <td><span class="badge badge-${category.category_type}">${category.category_type}</span></td>
            <td>${formatCurrency(budgetAmount)}</td>
            <td>${formatCurrency(spentAmount)}</td>
            <td class="variance ${varianceClass}">${formatCurrency(Math.abs(variance))}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editBudgetCategory(${category.category_id})">Edit</button>
                <button class="btn btn-sm btn-secondary" onclick="viewCategoryExpenses(${category.category_id})">View</button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

function updateAllocationDisplay(allocation) {
    const elements = {
        'monthly_salary': allocation.monthly_salary || 0,
        'needs_amount': allocation.needs_amount || 0,
        'wants_amount': allocation.wants_amount || 0,
        'savings_amount': allocation.savings_amount || 0
    };
    
    Object.keys(elements).forEach(key => {
        const element = document.getElementById(key + 'Display');
        if (element) {
            element.textContent = formatCurrency(elements[key]);
        }
    });
}

function updateBudgetSummary(data) {
    // Use the summary data from the new API structure
    const summary = data?.summary || {};
    const totalIncome = data?.total_monthly_income || 0;
    const totalBudgeted = summary.total_planned || 0;
    const totalSpent = summary.total_actual || 0;
    const budgetPerformance = summary.budget_performance || 0;
    
    // Update overview cards with animation if animateNumber function exists
    if (typeof animateNumber === 'function') {
        const totalIncomeEl = document.getElementById('totalIncome');
        const plannedBudgetEl = document.getElementById('plannedBudget');
        const actualSpendingEl = document.getElementById('actualSpending');
        const budgetPerformanceEl = document.getElementById('budgetPerformance');
        
        if (totalIncomeEl) animateNumber(totalIncomeEl, 0, totalIncome, 1500, '₵');
        if (plannedBudgetEl) animateNumber(plannedBudgetEl, 0, totalBudgeted, 1500, '₵');
        if (actualSpendingEl) animateNumber(actualSpendingEl, 0, totalSpent, 1500, '₵');
        if (budgetPerformanceEl) animateNumber(budgetPerformanceEl, 0, budgetPerformance, 1500, '', '%');
    } else {
        // Fallback to direct update
        const totalIncomeEl = document.getElementById('totalIncome');
        const plannedBudgetEl = document.getElementById('plannedBudget');
        const actualSpendingEl = document.getElementById('actualSpending');
        const budgetPerformanceEl = document.getElementById('budgetPerformance');
        
        if (totalIncomeEl) totalIncomeEl.textContent = formatCurrency(totalIncome);
        if (plannedBudgetEl) plannedBudgetEl.textContent = formatCurrency(totalBudgeted);
        if (actualSpendingEl) actualSpendingEl.textContent = formatCurrency(totalSpent);
        if (budgetPerformanceEl) budgetPerformanceEl.textContent = formatPercent(budgetPerformance);
    }
    
    // Update other summary displays
    const summaryElements = {
        'totalBudgeted': totalBudgeted,
        'totalSpent': totalSpent,
        'totalVariance': summary.total_variance || (totalBudgeted - totalSpent)
    };
    
    Object.keys(summaryElements).forEach(key => {
        const element = document.getElementById(key + 'Display');
        if (element) {
            element.textContent = formatCurrency(summaryElements[key]);
        }
    });
    
    // Update budget status text
    updateBudgetStatusText(totalIncome, totalBudgeted, totalSpent);
    
    // Also update the main budget summary if budgetData is available
    if (budgetData) {
        updateMainBudgetSummary();
    }
}

// Add function to update status text
function updateBudgetStatusText(income, budgeted, spent) {
    const budgetSurplusEl = document.getElementById('budgetSurplus');
    const spendingVarianceEl = document.getElementById('spendingVariance');
    const performanceLabelEl = document.getElementById('performanceLabel');
    
    if (budgetSurplusEl) {
        const surplus = income - budgeted;
        const surplusFormatted = Math.floor(surplus) === surplus ? surplus.toLocaleString('en-US') : surplus.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const absSurplusFormatted = Math.floor(Math.abs(surplus)) === Math.abs(surplus) ? Math.abs(surplus).toLocaleString('en-US') : Math.abs(surplus).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        budgetSurplusEl.textContent = surplus >= 0 ? `₵${surplusFormatted} surplus` : `₵${absSurplusFormatted} over budget`;
        budgetSurplusEl.className = surplus >= 0 ? 'change positive' : 'change negative';
    }
    
    if (spendingVarianceEl) {
        const variance = budgeted - spent;
        const varianceFormatted = Math.floor(variance) === variance ? variance.toLocaleString('en-US') : variance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const absVarianceFormatted = Math.floor(Math.abs(variance)) === Math.abs(variance) ? Math.abs(variance).toLocaleString('en-US') : Math.abs(variance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        spendingVarianceEl.textContent = variance >= 0 ? `₵${varianceFormatted} under budget` : `₵${absVarianceFormatted} over budget`;
        spendingVarianceEl.className = variance >= 0 ? 'change positive' : 'change negative';
    }
    
    if (performanceLabelEl) {
        const performanceRatio = budgeted > 0 ? (spent / budgeted) : 0;
        if (performanceRatio <= 0.8) {
            performanceLabelEl.textContent = 'Excellent control';
        } else if (performanceRatio <= 0.95) {
            performanceLabelEl.textContent = 'Good spending';
        } else if (performanceRatio <= 1.0) {
            performanceLabelEl.textContent = 'On track';
        } else {
            performanceLabelEl.textContent = 'Over budget';
        }
    }
}

function updateMainBudgetSummary() {
    if (!budgetData) return;

    const summaryPlanned = document.getElementById('summaryPlanned');
    const summaryActual = document.getElementById('summaryActual');
    const summaryRemaining = document.getElementById('summaryRemaining');
    const summaryAvailable = document.getElementById('summaryAvailable');
    const summaryPlannedPercent = document.getElementById('summaryPlannedPercent');
    const summaryActualPercent = document.getElementById('summaryActualPercent');
    const summaryRemainingPercent = document.getElementById('summaryRemainingPercent');

    const summary = budgetData.summary || {};
    const income = budgetData.total_monthly_income || 0;

    if (summaryPlanned) {
        summaryPlanned.textContent = formatCurrency(summary.total_planned || 0);
    }
    if (summaryActual) {
        summaryActual.textContent = formatCurrency(summary.total_actual || 0);
    }
    if (summaryRemaining) {
        const remainingBudget = summary.remaining_budget || 0;
        summaryRemaining.textContent = formatCurrency(remainingBudget);
        summaryRemaining.className = remainingBudget >= 0 ? 'summary-amount positive' : 'summary-amount negative';
    }
    if (summaryAvailable) {
        summaryAvailable.textContent = formatCurrency(summary.available_balance || 0);
    }

    if (summaryPlannedPercent) {
        const plannedPercent = income > 0 && summary.total_planned ? 
            ((summary.total_planned / income) * 100).toFixed(1) : 0;
        const validPlannedPercent = isNaN(plannedPercent) ? 0 : plannedPercent;
        summaryPlannedPercent.textContent = `${validPlannedPercent}% of income`;
    }
    if (summaryActualPercent) {
        const actualPercent = income > 0 && summary.total_actual ? 
            ((summary.total_actual / income) * 100).toFixed(1) : 0;
        const validActualPercent = isNaN(actualPercent) ? 0 : actualPercent;
        summaryActualPercent.textContent = `${validActualPercent}% of income`;
    }
    if (summaryRemainingPercent) {
        const remainingPercent = income > 0 && summary.remaining_budget ? 
            ((summary.remaining_budget / income) * 100).toFixed(1) : 0;
        const validRemainingPercent = isNaN(remainingPercent) ? 0 : remainingPercent;
        summaryRemainingPercent.textContent = `${validRemainingPercent}% unspent`;
    }
}

function getMonthName(month) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[month - 1];
}

function loadAvailablePeriods() {
    // Generate periods for the current year and some previous months
    const currentDate = new Date();
    const periods = [];
    
    // Generate last 12 months from current date
    for (let i = 0; i < 12; i++) {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
        periods.push({
            month: date.getMonth() + 1,
            year: date.getFullYear(),
            month_name: date.toLocaleString('default', { month: 'long' }),
            period_key: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
            display_name: `${date.toLocaleString('default', { month: 'long' })} ${date.getFullYear()}`
        });
    }
    
    // Also fetch from API to get periods with actual data
    fetch('../api/budget_period.php?action=get_available_periods')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data && data.data.length > 0) {
            // Merge API periods with generated periods
            const apiPeriods = data.data;
            const mergedPeriods = [];
            const seenKeys = new Set();
            
            // Add generated periods first (to ensure current period is included)
            periods.forEach(period => {
                if (!seenKeys.has(period.period_key)) {
                    mergedPeriods.push(period);
                    seenKeys.add(period.period_key);
                }
            });
            
            // Add API periods that aren't already included
            apiPeriods.forEach(period => {
                // Generate period_key if it doesn't exist
                if (!period.period_key) {
                    period.period_key = period.year + '-' + String(period.month).padStart(2, '0');
                }
                if (!seenKeys.has(period.period_key)) {
                    mergedPeriods.push(period);
                }
            });
            
            populatePeriodSelector(mergedPeriods);
        } else {
            // Use generated periods if API fails
            populatePeriodSelector(periods);
        }
    })
    .catch(error => {
        console.error('Error loading available periods:', error);
        // Fallback to generated periods
        populatePeriodSelector(periods);
    });
}

function populatePeriodSelector(periods) {
    const selector = document.getElementById('budgetPeriodSelector');
    if (!selector) return;
    
    selector.innerHTML = '';
    
    // Sort periods by date (newest first)
    periods.sort((a, b) => {
        const aKey = `${a.year}-${String(a.month).padStart(2, '0')}`;
        const bKey = `${b.year}-${String(b.month).padStart(2, '0')}`;
        return bKey.localeCompare(aKey);
    });
    
    periods.forEach(period => {
        const option = document.createElement('option');
        option.value = `${period.month}-${period.year}`;
        option.textContent = period.display_name;
        
        // Mark current period as selected (August 2025)
        if (period.month === currentBudgetPeriod.month && period.year === currentBudgetPeriod.year) {
            option.selected = true;
        }
        
        selector.appendChild(option);
    });
    
    // Update the current period display
    const currentPeriodDisplay = document.getElementById('currentPeriodDisplay');
    if (currentPeriodDisplay) {
        const currentPeriod = periods.find(p => p.month === currentBudgetPeriod.month && p.year === currentBudgetPeriod.year);
        if (currentPeriod) {
            currentPeriodDisplay.textContent = currentPeriod.display_name;
        }
    }
}

function saveBudget() {
    showSnackbar('Budget saved successfully!', 'success');
}

// Export Budget Functions
function exportBudget() {
    showModal('exportBudgetModal');
}

function showExportModal() {
    const modal = document.getElementById('exportBudgetModal');
    if (modal) {
        // Set current period as default
        const periodSelect = modal.querySelector('select[name="export_period"]');
        if (periodSelect) {
            periodSelect.value = currentBudgetPeriod;
        }
        
        showModal('exportBudgetModal');
    }
}

async function processExport(exportData) {
    try {
        const response = await fetch('../api/export_budget.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(exportData)
        });
        
        if (exportData.export_format === 'pdf' || exportData.export_format === 'csv') {
            // Handle file download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `budget-${exportData.export_period}.${exportData.export_format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            // Handle JSON response
            const result = await response.json();
            if (result.success) {
                // Download JSON data
                const dataStr = JSON.stringify(result.data, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = window.URL.createObjectURL(dataBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `budget-${exportData.export_period}.json`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }
        }
        
        showSnackbar('Budget exported successfully!', 'success');
        closeModal('exportBudgetModal');
        
    } catch (error) {
        console.error('Export error:', error);
        showSnackbar('Error exporting budget', 'error');
    }
}

// API Functions
async function loadBudgetData() {
    try {
        // Load budget data from the unified API
        const response = await fetch('../api/budget_data.php');
        const data = await response.json();
        
        if (data.success) {
            // Store data globally
            budgetData = data;
            budgetAllocation = data.budget_allocation;
            
            // Reset numbers to 0 before animating
            resetBudgetDisplay();
            
            // Start animations after a short delay
            setTimeout(() => {
                updateBudgetOverview();
                renderBudgetCategories();
                updateMainBudgetSummary();
            }, 200);
        } else {
            throw new Error(data.message || 'Failed to load budget data');
        }
    } catch (error) {
        console.error('Error loading budget data:', error);
        showSnackbar('Error loading budget data: ' + error.message, 'error');
        showEmptyState();
    }
}

// Function to reset budget display to 0 for animation
function resetBudgetDisplay() {
    const elements = [
        'totalIncome',
        'plannedBudget', 
        'actualSpending',
        'budgetPerformance'
    ];
    
    elements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = '0.00';
        }
    });
}

// Helper function to calculate budget summary with real spending and allocation
function calculateBudgetSummary(categories, financialOverview, allocation) {
    const totalPlanned = categories.reduce((sum, cat) => sum + parseFloat(cat.budget_limit || 0), 0);
    const totalSpent = categories.reduce((sum, cat) => sum + parseFloat(cat.actual_spent || 0), 0);
    const totalIncome = allocation?.monthly_salary || financialOverview.monthly_income || 0;
    const remainingBudget = totalPlanned - totalSpent;
    const availableBalance = totalIncome - totalPlanned;
    const incomeUtilization = totalIncome > 0 ? Math.round((totalPlanned / totalIncome) * 100) : 0;
    const budgetPerformance = totalPlanned > 0 ? Math.round(((totalPlanned - totalSpent) / totalPlanned) * 100) : 100;
    const spendingUtilization = totalPlanned > 0 ? Math.round((totalSpent / totalPlanned) * 100) : 0;
    
    return {
        total_planned: totalPlanned,
        total_spent: totalSpent,
        remaining_budget: remainingBudget,
        available_balance: availableBalance,
        income_utilization: incomeUtilization,
        budget_performance: budgetPerformance,
        spending_utilization: spendingUtilization,
        total_variance: remainingBudget,
        total_income: totalIncome
    };
}

// Helper function to calculate category type totals with real spending
function calculateCategoryTypeTotals(categories) {
    const types = ['needs', 'wants', 'savings'];
    const totals = {};
    
    types.forEach(type => {
        const typeCategories = categories.filter(cat => cat.category_type === type);
        const planned = typeCategories.reduce((sum, cat) => sum + parseFloat(cat.budget_limit || 0), 0);
        const spent = typeCategories.reduce((sum, cat) => sum + parseFloat(cat.actual_spent || 0), 0);
        
        // Get allocation data from the budget allocation if available
        const allocatedAmount = totals.allocated || 0;
        const templatePercentage = totals.template_percentage || null;
        
        // Calculate variances
        const allocationVsBudgetVariance = allocatedAmount - planned; // How much over/under allocated they budgeted
        const budgetVsSpentVariance = planned - spent; // How much over/under budget they spent
        const allocationVsSpentVariance = allocatedAmount - spent; // How much of allocation is left
        
        // Calculate utilization percentages
        const budgetUtilization = planned > 0 ? (spent / planned) * 100 : 0;
        const allocationUtilization = allocatedAmount > 0 ? (spent / allocatedAmount) * 100 : 0;
        
        // Determine status
        let status = 'good';
        if (spent > allocatedAmount) {
            status = 'over_allocation';
        } else if (spent > planned) {
            status = 'over_budget';
        } else if (planned > allocatedAmount) {
            status = 'over_allocated';
        } else if (budgetUtilization >= 90) {
            status = 'near_limit';
        } else if (budgetUtilization >= 70) {
            status = 'on_track';
        }
        
        totals[type] = {
            planned: planned,
            spent: spent,
            allocated_amount: allocatedAmount,
            template_percentage: templatePercentage,
            allocation_vs_budget_variance: allocationVsBudgetVariance,
            budget_vs_spent_variance: budgetVsSpentVariance,
            allocation_vs_spent_variance: allocationVsSpentVariance,
            budget_utilization: budgetUtilization,
            allocation_utilization: allocationUtilization,
            status: status,
            categories_count: typeCategories.length
        };
    });
    
    return totals;
}

// Get template percentage for category type
function getTemplatePercentageForType(type) {
    // Check if there's a saved template allocation
    const savedTemplate = localStorage.getItem('appliedBudgetTemplate');
    if (savedTemplate) {
        try {
            const template = JSON.parse(savedTemplate);
            return template[type] || null;
        } catch (e) {
            return null;
        }
    }
    return null;
}

// Template management functions
function selectTemplate(needsPercent, wantsPercent, savingsPercent, templateName, templateId = null) {
    // Get monthly income from budget data instead of DOM element
    const monthlyIncome = budgetData?.total_monthly_income || 0;
    
    if (monthlyIncome <= 0) {
        showSnackbar('Please set your monthly income in your profile first', 'warning');
        return;
    }
    
    const needsAmount = parseFloat(((monthlyIncome * needsPercent) / 100).toFixed(2));
    const wantsAmount = parseFloat(((monthlyIncome * wantsPercent) / 100).toFixed(2));
    const savingsAmount = parseFloat(((monthlyIncome * savingsPercent) / 100).toFixed(2));
    
    // Update the template preview
    const templatePreview = document.getElementById('templatePreview');
    if (templatePreview) {
        templatePreview.innerHTML = `
            <div class="template-preview-content">
                <h4>Selected: ${templateName}</h4>
                <p>Based on monthly income of ₵${monthlyIncome.toLocaleString()}</p>
                <div class="template-breakdown">
                    <div class="breakdown-item">
                        <span class="category-label needs">Needs (${needsPercent}%)</span>
                        <span class="amount">₵${needsAmount.toLocaleString()}</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="category-label wants">Wants (${wantsPercent}%)</span>
                        <span class="amount">₵${wantsAmount.toLocaleString()}</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="category-label savings">Savings (${savingsPercent}%)</span>
                        <span class="amount">₵${savingsAmount.toLocaleString()}</span>
                    </div>
                </div>
                <div class="template-actions">
                    <button onclick="applyTemplate(${needsPercent}, ${wantsPercent}, ${savingsPercent}, '${templateName}', ${templateId})" class="btn-primary apply-btn">Apply This Template</button>
                    <button onclick="resetTemplateModal()" class="btn-secondary">Choose Different Template</button>
                </div>
            </div>
        `;
        templatePreview.style.display = 'block';
    }
    
    // Hide template selection and show preview
    const templateSelection = document.getElementById('templateSelection');
    if (templateSelection) {
        templateSelection.style.display = 'none';
    }
    
    // Visual feedback - highlight selected card
    document.querySelectorAll('.template-card').forEach(card => card.classList.remove('selected'));
    if (event && event.target) {
        event.target.closest('.template-card')?.classList.add('selected');
    }
}

function applyTemplate(needsPercent, wantsPercent, savingsPercent, templateName, templateId = null) {
    // Save applied template to localStorage
    const appliedTemplate = {
        name: templateName,
        needs: needsPercent,
        wants: wantsPercent,
        savings: savingsPercent,
        templateId: templateId,
        appliedDate: new Date().toISOString()
    };
    
    localStorage.setItem('appliedBudgetTemplate', JSON.stringify(appliedTemplate));
    
    // Save allocation to backend
    fetch('../actions/salary_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=update_budget_allocation&needsPercent=${encodeURIComponent(needsPercent)}&wantsPercent=${encodeURIComponent(wantsPercent)}&savingsPercent=${encodeURIComponent(savingsPercent)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close template modal
            closeModal('budgetTemplateModal');
            
            // Refresh budget display to show template percentages
            if (typeof loadBudgetData === 'function') {
                loadBudgetData();
            }
            
            // Show success message
            showSnackbar(`Template "${templateName}" applied and saved!`, 'success');
        } else {
            showSnackbar(data.message || 'Failed to save budget allocation', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving budget allocation:', error);
        showSnackbar('Error saving budget allocation', 'error');
    });
}

function showTemplateModal() {
    showModal('budgetTemplateModal');
    loadCustomTemplates();
}

function showBudgetTemplateModal() {
    // This function is called from the HTML button
    showTemplateModal();
}

function resetTemplateModal() {
    // Reset to template selection view
    const customSection = document.getElementById('customTemplateSection');
    const templateSelection = document.getElementById('templateSelection');
    const templatePreview = document.getElementById('templatePreview');
    
    if (customSection) customSection.style.display = 'none';
    if (templateSelection) templateSelection.style.display = 'block';
    if (templatePreview) {
        templatePreview.style.display = 'none';
        templatePreview.innerHTML = '';
    }
    
    // Reset button visibility
    const backButton = document.getElementById('backToTemplates');
    const saveButton = document.getElementById('saveCustomTemplate');
    
    if (backButton) backButton.style.display = 'none';
    if (saveButton) saveButton.style.display = 'none';
    
    // Remove selected class from all template cards
    document.querySelectorAll('.template-card').forEach(card => card.classList.remove('selected'));
}

function clearTemplate() {
    // Clear the currently applied budget template
    const confirmClear = confirm('Are you sure you want to clear the current budget template? This will reset all category allocations.');
    
    if (confirmClear) {
        // Make API call to clear the template
        fetch('../api/budget_allocation.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload budget data to show cleared state
                loadBudgetData();
                showSnackbar('Budget template cleared successfully!', 'success');
                closeModal('budgetTemplateModal');
            } else {
                showSnackbar('Error clearing template: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error clearing template:', error);
            showSnackbar('Error clearing budget template', 'error');
        });
    }
}

function showCustomTemplate() {
    // Reset sliders to default values
    const needsSlider = document.getElementById('needsSlider');
    const wantsSlider = document.getElementById('wantsSlider');
    const savingsSlider = document.getElementById('savingsSlider');
    
    if (needsSlider) needsSlider.value = 50;
    if (wantsSlider) wantsSlider.value = 30;
    if (savingsSlider) savingsSlider.value = 20;
    
    // Update display
    updateCustomTemplate();
    
    // Show custom template section and hide template selection
    const customSection = document.getElementById('customTemplateSection');
    const templateSelection = document.getElementById('templateSelection');
    
    if (customSection) customSection.style.display = 'block';
    if (templateSelection) templateSelection.style.display = 'none';
    
    // Update button visibility
    const backButton = document.getElementById('backToTemplates');
    const saveButton = document.getElementById('saveCustomTemplate');
    const applyButton = document.getElementById('applyTemplateBtn');
    
    if (backButton) backButton.style.display = 'inline-block';
    if (saveButton) saveButton.style.display = 'inline-block';
    if (applyButton) applyButton.style.display = 'none';
}

function backToTemplates() {
    const customSection = document.getElementById('customTemplateSection');
    const templateSelection = document.getElementById('templateSelection');
    
    if (customSection) customSection.style.display = 'none';
    if (templateSelection) templateSelection.style.display = 'block';
    
    // Update button visibility
    const backButton = document.getElementById('backToTemplates');
    const saveButton = document.getElementById('saveCustomTemplate');
    const applyButton = document.getElementById('applyTemplateBtn');
    
    if (backButton) backButton.style.display = 'none';
    if (saveButton) saveButton.style.display = 'none';
    if (applyButton) applyButton.style.display = 'none';
}

function updateCustomTemplate() {
    const needsSlider = document.getElementById('needsSlider');
    const wantsSlider = document.getElementById('wantsSlider');
    const savingsSlider = document.getElementById('savingsSlider');
    
    const needsInput = document.getElementById('needsInput');
    const wantsInput = document.getElementById('wantsInput');
    const savingsInput = document.getElementById('savingsInput');
    
    if (!needsSlider || !wantsSlider || !savingsSlider) {
        console.warn('Custom template sliders not found');
        return;
    }
    
    const needsPercent = parseInt(needsSlider.value);
    const wantsPercent = parseInt(wantsSlider.value);
    const savingsPercent = parseInt(savingsSlider.value);
    
    // Sync number inputs with sliders
    if (needsInput) needsInput.value = needsPercent;
    if (wantsInput) wantsInput.value = wantsPercent;
    if (savingsInput) savingsInput.value = savingsPercent;
    
    // Update preview bars
    const needsBar = document.getElementById('customNeedsBar');
    const wantsBar = document.getElementById('customWantsBar');
    const savingsBar = document.getElementById('customSavingsBar');
    
    if (needsBar) needsBar.style.width = needsPercent + '%';
    if (wantsBar) wantsBar.style.width = wantsPercent + '%';
    if (savingsBar) savingsBar.style.width = savingsPercent + '%';
    
    // Update preview labels
    const needsLabel = document.getElementById('customNeedsLabel');
    const wantsLabel = document.getElementById('customWantsLabel');
    const savingsLabel = document.getElementById('customSavingsLabel');
    
    if (needsLabel) needsLabel.textContent = `${needsPercent}% Needs`;
    if (wantsLabel) wantsLabel.textContent = `${wantsPercent}% Wants`;
    if (savingsLabel) savingsLabel.textContent = `${savingsPercent}% Savings`;
    
    // Check if total equals 100%
    const total = needsPercent + wantsPercent + savingsPercent;
    const totalDisplay = document.getElementById('totalPercentage');
    const saveButton = document.getElementById('saveCustomTemplate');
    const totalStatus = document.getElementById('totalStatus');
    
    if (totalDisplay) {
        totalDisplay.textContent = total + '%';
        
        if (total === 100) {
            totalDisplay.style.color = '#27ae60';
            if (totalStatus) {
                totalStatus.textContent = '✓ Valid';
                totalStatus.className = 'status-valid';
            }
        } else {
            totalDisplay.style.color = '#e74c3c';
            if (totalStatus) {
                totalStatus.textContent = '✗ Invalid';
                totalStatus.className = 'status-invalid';
            }
        }
    }
    
    if (saveButton) {
        if (total === 100) {
            saveButton.disabled = false;
            saveButton.textContent = 'Save & Apply Template';
            saveButton.style.opacity = '1';
        } else {
            saveButton.disabled = true;
            saveButton.textContent = `Total: ${total}% (Need 100%)`;
            saveButton.style.opacity = '0.6';
        }
    }
}

function saveCustomTemplate() {
    const needsPercent = parseInt(document.getElementById('needsSlider').value);
    const wantsPercent = parseInt(document.getElementById('wantsSlider').value);
    const savingsPercent = parseInt(document.getElementById('savingsSlider').value);
    
    // Validate template totals to 100%
    if (needsPercent + wantsPercent + savingsPercent !== 100) {
        showSnackbar('Template percentages must total exactly 100%', 'warning');
        return;
    }
    
    // Check if user has monthly income set
    const monthlyIncome = budgetData?.total_monthly_income || 0;
    if (monthlyIncome <= 0) {
        showSnackbar('Please set your monthly income in your profile first', 'warning');
        return;
    }
    
    // Get existing custom templates
    const customTemplates = JSON.parse(localStorage.getItem('customBudgetTemplates') || '[]');
    
    // Generate next available name
    const nextIndex = customTemplates.length + 1;
    const templateName = `Custom ${nextIndex}`;
    
    // Create new template
    const newTemplate = {
        id: Date.now(), // Unique ID
        name: templateName,
        needs: needsPercent,
        wants: wantsPercent,
        savings: savingsPercent,
        created: new Date().toISOString()
    };
    
    // Save to localStorage
    customTemplates.push(newTemplate);
    localStorage.setItem('customBudgetTemplates', JSON.stringify(customTemplates));
    
    // Apply the template
    applyTemplate(needsPercent, wantsPercent, savingsPercent, templateName, newTemplate.id);
    
    // Refresh template grid
    loadCustomTemplates();
    
    // Show success message
    showSnackbar(`Custom template "${templateName}" created and applied successfully!`, 'success');
}

function loadCustomTemplates() {
    const customTemplates = JSON.parse(localStorage.getItem('customBudgetTemplates') || '[]');
    const templateGrid = document.getElementById('templateGrid');
    
    if (!templateGrid) return;
    
    // Remove existing custom template cards (except the "Create New" card)
    const existingCustomCards = templateGrid.querySelectorAll('.custom-template-card');
    existingCustomCards.forEach(card => card.remove());
    
    // Add custom templates before the "Create New" card
    const createNewCard = templateGrid.querySelector('.custom-template');
    
    customTemplates.forEach(template => {
        const templateCard = document.createElement('div');
        templateCard.className = 'template-card custom-template-card';
        templateCard.onclick = () => selectTemplate(template.needs, template.wants, template.savings, template.name, template.id);
        
        templateCard.innerHTML = `
            <button class="delete-btn" onclick="deleteCustomTemplate(${template.id}, event)" title="Delete template">×</button>
            <h5>${template.name}</h5>
            <p class="template-desc">Custom allocation template</p>
            <div class="template-preview">
                <div class="preview-bar needs-bar" style="width: ${template.needs}%">
                    <span>${template.needs}% Needs</span>
                </div>
                <div class="preview-bar wants-bar" style="width: ${template.wants}%">
                    <span>${template.wants}% Wants</span>
                </div>
                <div class="preview-bar savings-bar" style="width: ${template.savings}%">
                    <span>${template.savings}% Savings</span>
                </div>
            </div>
        `;
        
        if (createNewCard) {
            templateGrid.insertBefore(templateCard, createNewCard);
        }
    });
}

function deleteCustomTemplate(templateId, event) {
    event.stopPropagation(); // Prevent card selection
    
    const confirmDelete = confirm('Are you sure you want to delete this custom template?');
    
    if (confirmDelete) {
        const customTemplates = JSON.parse(localStorage.getItem('customBudgetTemplates') || '[]');
        const templateToDelete = customTemplates.find(t => t.id === templateId);
        const updatedTemplates = customTemplates.filter(template => template.id !== templateId);
        localStorage.setItem('customBudgetTemplates', JSON.stringify(updatedTemplates));
        
        // Refresh template grid
        loadCustomTemplates();
        
        // Show success message
        showSnackbar(`Custom template "${templateToDelete?.name || 'Template'}" deleted successfully!`, 'success');
    }
}

// Handle input changes from number inputs
function syncSliderWithInput(inputId, sliderId) {
    const input = document.getElementById(inputId);
    const slider = document.getElementById(sliderId);
    
    if (input && slider) {
        let value = parseInt(input.value) || 0;
        value = Math.max(0, Math.min(100, value)); // Clamp between 0-100
        input.value = value;
        slider.value = value;
        updateCustomTemplate();
    }
}

// Initialize custom templates on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof loadCustomTemplates === 'function') {
        loadCustomTemplates();
    }
    
    // Initialize period management
    loadAvailablePeriods();
    loadBudgetDataForPeriod(currentBudgetPeriod.month, currentBudgetPeriod.year);
    
    // Add event listeners for budget input functionality
    const categoryTypeSelect = document.querySelector('select[name="category_type"]');
    const percentageInput = document.querySelector('input[name="budget_percentage"]');
    
    if (categoryTypeSelect) {
        categoryTypeSelect.addEventListener('change', updatePercentageCalculation);
    }
    
    if (percentageInput) {
        percentageInput.addEventListener('input', updatePercentageCalculation);
    }
    
    // Export form event listeners
    const exportForm = document.getElementById('exportBudgetForm');
    if (exportForm) {
        exportForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const exportData = {
                export_period: formData.get('export_period'),
                export_format: formData.get('export_format'),
                export_from_date: formData.get('export_from_date'),
                export_to_date: formData.get('export_to_date'),
                include_categories: formData.has('include_categories'),
                include_expenses: formData.has('include_expenses'),
                include_variance: formData.has('include_variance'),
                include_allocation: formData.has('include_allocation'),
                include_summary: formData.has('include_summary'),
                include_charts: formData.has('include_charts')
            };
            
            await processExport(exportData);
        });
    }
    
    // Period select change for custom date range
    const exportPeriodSelect = document.querySelector('select[name="export_period"]');
    if (exportPeriodSelect) {
        exportPeriodSelect.addEventListener('change', function() {
            const customDateRange = document.getElementById('customDateRange');
            if (customDateRange) {
                if (this.value === 'custom') {
                    customDateRange.style.display = 'block';
                } else {
                    customDateRange.style.display = 'none';
                }
            }
        });
    }
    
    // Period selector change handler
    const periodSelector = document.getElementById('budgetPeriodSelector');
    if (periodSelector) {
        periodSelector.addEventListener('change', function() {
            const periodValue = this.value;
            if (periodValue) {
                const [month, year] = periodValue.split('-');
                changeBudgetPeriod(parseInt(month), parseInt(year));
            }
        });
    }
});

async function addCategory(categoryData) {
    try {
        const response = await fetch('../api/budget_categories.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(categoryData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar('Category added successfully!', 'success');
            await loadBudgetData(); // Reload data
            return true;
        } else {
            throw new Error(result.message || 'Failed to add category');
        }
    } catch (error) {
        console.error('Error adding category:', error);
        showSnackbar('Error adding category: ' + error.message, 'error');
        return false;
    }
}

async function editCategory(categoryId, categoryData) {
    try {
        const response = await fetch('../api/budget_categories.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: categoryId,
                ...categoryData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar('Category updated successfully!', 'success');
            await loadBudgetData(); // Reload data
            return true;
        } else {
            throw new Error(result.message || 'Failed to update category');
        }
    } catch (error) {
        console.error('Error updating category:', error);
        showSnackbar('Error updating category: ' + error.message, 'error');
        return false;
    }
}

async function deleteCategory(categoryId) {
    if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        return false;
    }
    
    try {
        const response = await fetch('../api/budget_categories.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: categoryId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar(result.message || 'Category deleted successfully!', 'success');
            await loadBudgetData(); // Reload data
            return true;
        } else {
            throw new Error(result.message || 'Failed to delete category');
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        showSnackbar('Error deleting category: ' + error.message, 'error');
        return false;
    }
}

// UI Update Functions
function updateBudgetOverview() {
    if (!budgetData) return;

    const summary = budgetData.summary || {};
    const totalIncome = budgetData.total_monthly_income || 0;
    const totalPlanned = summary.total_planned || 0;
    const totalActual = summary.total_actual || 0;
    
    // Calculate budget performance with better logic
    let budgetPerformance = 0;
    if (totalPlanned > 0) {
        if (totalActual === 0) {
            budgetPerformance = 100; // Perfect control
        } else if (totalActual <= totalPlanned) {
            budgetPerformance = ((totalPlanned - totalActual) / totalPlanned) * 100;
        } else {
            // Over budget - performance decreases based on how much over
            const overspent = totalActual - totalPlanned;
            const overspentPercentage = (overspent / totalPlanned) * 100;
            budgetPerformance = Math.max(0, 100 - overspentPercentage);
        }
    }

    // Update overview cards with animation if animateNumber function exists
    if (typeof animateNumber === 'function') {
        const totalIncomeEl = document.getElementById('totalIncome');
        const plannedBudgetEl = document.getElementById('plannedBudget');
        const actualSpendingEl = document.getElementById('actualSpending');
        const budgetPerformanceEl = document.getElementById('budgetPerformance');
        
        if (totalIncomeEl) animateNumber(totalIncomeEl, 0, totalIncome, 1500, '₵');
        if (plannedBudgetEl) animateNumber(plannedBudgetEl, 0, totalPlanned, 1500, '₵');
        if (actualSpendingEl) animateNumber(actualSpendingEl, 0, totalActual, 1500, '₵');
        if (budgetPerformanceEl) animateNumber(budgetPerformanceEl, 0, budgetPerformance, 1500, '', '%');
    } else {
        // Fallback to direct update
        const totalIncomeEl = document.getElementById('totalIncome');
        const plannedBudgetEl = document.getElementById('plannedBudget');
        const actualSpendingEl = document.getElementById('actualSpending');
        const budgetPerformanceEl = document.getElementById('budgetPerformance');
        
        if (totalIncomeEl) totalIncomeEl.textContent = formatCurrency(totalIncome);
        if (plannedBudgetEl) plannedBudgetEl.textContent = formatCurrency(totalPlanned);
        if (actualSpendingEl) actualSpendingEl.textContent = formatCurrency(totalActual);
        if (budgetPerformanceEl) budgetPerformanceEl.textContent = formatPercent(budgetPerformance);
    }

    // Update variance displays with better status logic
    const budgetSurplusEl = document.getElementById('budgetSurplus');
    const spendingVarianceEl = document.getElementById('spendingVariance');
    const performanceLabelEl = document.getElementById('performanceLabel');
    
    const surplus = summary.available_balance || 0;
    if (budgetSurplusEl) {
        budgetSurplusEl.textContent = surplus >= 0 ? 
            `₵${surplus.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} surplus` : 
            `₵${Math.abs(surplus).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} deficit`;
        budgetSurplusEl.className = surplus >= 0 ? 'change positive' : 'change negative';
    }

    const variance = summary.total_variance || 0;
    if (spendingVarianceEl) {
        if (totalActual === 0 && totalPlanned > 0) {
            spendingVarianceEl.textContent = `₵${totalPlanned.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} unused`;
            spendingVarianceEl.className = 'change positive';
        } else if (variance > 0) {
            spendingVarianceEl.textContent = `₵${variance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} remaining`;
            spendingVarianceEl.className = 'change positive';
        } else if (variance === 0) {
            spendingVarianceEl.textContent = 'Budget limit reached';
            spendingVarianceEl.className = 'change warning';
        } else {
            spendingVarianceEl.textContent = `₵${Math.abs(variance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} over budget`;
            spendingVarianceEl.className = 'change negative';
        }
    }

    if (performanceLabelEl) {
        if (totalPlanned === 0) {
            performanceLabelEl.textContent = 'No budget set';
        } else if (totalActual === 0) {
            performanceLabelEl.textContent = 'Perfect control';
        } else {
            const spentPercentage = (totalActual / totalPlanned) * 100;
            if (spentPercentage <= 50) {
                performanceLabelEl.textContent = 'Excellent control';
            } else if (spentPercentage <= 80) {
                performanceLabelEl.textContent = 'Good spending';
            } else if (spentPercentage <= 95) {
                performanceLabelEl.textContent = 'Approaching limit';
            } else if (spentPercentage < 100) {
                performanceLabelEl.textContent = 'Near limit';
            } else if (spentPercentage === 100) {
                performanceLabelEl.textContent = 'Limit reached';
            } else {
                performanceLabelEl.textContent = 'Over budget';
            }
        }
    }

    // Show applied template information
    updateAppliedTemplateDisplay();
}

function updateAppliedTemplateDisplay() {
    return true;
}

function clearAllocation() {
    // Create a confirmation modal instead of browser alert
    const confirmClear = confirm('Are you sure you want to remove the active budget allocation?');
    
    if (confirmClear) {
        // Make API call to delete allocation
        fetch('../api/budget_allocation.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadBudgetData(); // Refresh to show updated state
                showSnackbar('Budget allocation removed successfully!', 'success');
            } else {
                showSnackbar('Error removing allocation: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error clearing allocation:', error);
            showSnackbar('Error removing allocation', 'error');
        });
    }
}

function renderBudgetCategories() {
    
    if (!budgetData || !budgetData.categories || budgetData.categories.length === 0) {
        showEmptyState();
        return;
    }

    hideLoadingState();
    hideEmptyState();

    const container = document.getElementById('budgetCategoriesContainer');
    if (!container) return;

    container.style.display = 'block';
    container.innerHTML = '';

    // Group categories by type
    const categoryTypes = {
        needs: { title: 'Needs (Essential)', icon: '🏠', description: 'Housing, food, utilities, transportation' },
        wants: { title: 'Wants (Lifestyle)', icon: '🎮', description: 'Entertainment, dining out, hobbies' },
        savings: { title: 'Savings & Investments', icon: '💰', description: 'Emergency fund, retirement, goals' }
    };

    Object.keys(categoryTypes).forEach(type => {
        // Get categories for this type - use categoriesByType for organized data
        let typeCategories;
        if (budgetData.categories_by_type && budgetData.categories_by_type[type]) {
            typeCategories = budgetData.categories_by_type[type];
        } else {
            // Fallback to filtering from flat categories array (for backward compatibility)
            typeCategories = budgetData.categories ? budgetData.categories.filter(cat => cat.category_type === type) : [];
        }
        
        // Ensure typeCategories is always an array
        if (!Array.isArray(typeCategories)) {
            console.warn(`renderBudgetCategories: typeCategories for ${type} is not an array:`, typeCategories);
            typeCategories = [];
        }
        
        const typeInfo = categoryTypes[type];
        
        // Handle savings type specially - get data from savings_data instead of categories
        let typeTotals;
        if (type === 'savings') {
            // Use savings_data from API for savings information
            const savingsData = budgetData.savings_data || {};
            typeTotals = {
                planned: savingsData.planned_savings || 0,
                actual: savingsData.actual_savings || 0,
                count: 0, // No expense categories for savings
                variance: savingsData.savings_variance || 0,
                progress_percentage: savingsData.savings_percentage || 0
            };
        } else {
            // Get totals from the API data (category_type_totals from budget_data.php)
            typeTotals = budgetData.category_type_totals?.[type] || {
                planned: 0,
                actual: 0,
                count: 0,
                variance: 0,
                progress_percentage: 0
            };
            
            // If we don't have API totals, calculate them from categories
            if (!budgetData.category_type_totals) {
                typeTotals = {
                    planned: typeCategories.reduce((sum, cat) => sum + parseFloat(cat.budget_limit || 0), 0),
                    actual: typeCategories.reduce((sum, cat) => sum + parseFloat(cat.actual_spent || 0), 0),
                    count: typeCategories.length,
                    variance: 0,
                    progress_percentage: 0
                };
                typeTotals.variance = typeTotals.planned - typeTotals.actual;
                typeTotals.progress_percentage = typeTotals.planned > 0 ? 
                    Math.round((typeTotals.actual / typeTotals.planned) * 100) : 0;
            }
        }
        
        // For backward compatibility, map to old field names that the rest of the function expects
        typeTotals.budgeted = typeTotals.planned;
        typeTotals.spent = typeTotals.actual;
        typeTotals.allocated = 0; // Will be set below if allocation exists
        
        // Add allocation percentage info if available
        if (budgetAllocation) {
            const percentageField = type === 'needs' ? 'needs_percentage' : 
                                  type === 'wants' ? 'wants_percentage' : 'savings_percentage';
            typeTotals.template_percentage = budgetAllocation[percentageField];
            
            // Use the allocated amount from allocation if available
            const amountField = type === 'needs' ? 'needs_amount' : 
                              type === 'wants' ? 'wants_amount' : 'savings_amount';
            if (budgetAllocation[amountField]) {
                typeTotals.allocated = parseFloat(budgetAllocation[amountField]);
            }
        }
        
        // Calculate utilization and status
        typeTotals.budget_utilization = typeTotals.budgeted > 0 ? (typeTotals.spent / typeTotals.budgeted) * 100 : 0;
        typeTotals.allocation_utilization = typeTotals.allocated > 0 ? (typeTotals.spent / typeTotals.allocated) * 100 : 0;
        typeTotals.budget_vs_spent_variance = typeTotals.budgeted - typeTotals.spent;
        typeTotals.allocation_vs_spent_variance = typeTotals.allocated - typeTotals.spent;
        
        // Determine status
        if (typeTotals.spent > typeTotals.allocated && typeTotals.allocated > 0) {
            typeTotals.status = 'over_allocation';
        } else if (typeTotals.spent > typeTotals.budgeted) {
            typeTotals.status = 'over_budget';
        } else if (typeTotals.budget_utilization >= 90) {
            typeTotals.status = 'near_limit';
        } else if (typeTotals.budget_utilization >= 70) {
            typeTotals.status = 'on_track';
        } else {
            typeTotals.status = 'good';
        }

        const categorySection = createCategorySection(type, typeInfo, typeTotals, typeCategories);
        container.appendChild(categorySection);
    });

    // Expand needs section by default
    const needsSection = container.querySelector('.needs-section');
    if (needsSection) {
        const content = needsSection.querySelector('.category-content');
        const header = needsSection.querySelector('.category-header');
        const icon = needsSection.querySelector('.expand-icon');
        
        if (content) content.classList.add('expanded');
        if (header) header.classList.add('expanded');
        if (icon) icon.textContent = '▲';
    }
}

function createCategorySection(type, typeInfo, totals, categories) {
    const section = document.createElement('div');
    section.className = `category-section ${type}-section`;
    
    // Get amounts
    const allocatedAmount = totals.allocated || 0;
    const plannedAmount = totals.budgeted || 0;
    const spentAmount = totals.spent || 0;
    
    // Determine what to show in progress text
    let progressText = '';
    let allocationText = '';
    
    if (budgetAllocation && totals.template_percentage) {
        progressText = `${formatPercent(totals.budget_utilization)} of budget used`;
        allocationText = `${totals.template_percentage}% of salary (${formatCurrency(allocatedAmount)})`;
    } else {
        progressText = `${formatPercent(totals.budget_utilization)} used`;
        allocationText = 'No allocation set';
    }
    
    // Status styling
    const statusClass = getStatusClass(totals.status);
    const statusText = getStatusText(totals.status);
    
    section.innerHTML = `
        <div class="category-header" onclick="toggleCategory('${type}')">
            <div class="category-info">
                <span class="category-icon">${typeInfo.icon}</span>
                <div class="category-details">
                    <h4>${typeInfo.title} <span class="status-badge ${statusClass}">${statusText}</span></h4>
                    <p>${typeInfo.description}</p>
                    <small class="allocation-info">${allocationText}</small>
                </div>
            </div>
            <div class="category-summary">
                <div class="category-amounts">
                    <div class="amount-display">${formatCurrency(spentAmount)} / ${formatCurrency(allocatedAmount > 0 ? allocatedAmount : plannedAmount)}</div>
                </div>
                <div class="category-progress">
                    <div class="progress-bar">
                        <div class="progress-fill ${type}-progress ${totals.budget_utilization > 100 ? 'over-budget' : ''}" 
                             style="width: ${Math.min(totals.budget_utilization, 100)}%"></div>
                    </div>
                    <span class="progress-text">${progressText}</span>
                </div>
                <div class="variance-summary">
                    <div class="variance-item ${totals.budget_vs_spent_variance < 0 ? 'negative' : 'positive'}">
                        ${totals.budget_vs_spent_variance >= 0 ? 'Under budget: ' : 'Over budget: '}
                        ${formatCurrency(Math.abs(totals.budget_vs_spent_variance))}
                    </div>
                    ${allocatedAmount > 0 ? `
                    <div class="variance-item ${totals.allocation_vs_spent_variance < 0 ? 'negative' : 'positive'}">
                        ${totals.allocation_vs_spent_variance >= 0 ? 'Allocation left: ' : 'Over allocation: '}
                        ${formatCurrency(Math.abs(totals.allocation_vs_spent_variance))}
                    </div>
                    ` : ''}
                </div>
                <span class="expand-icon">▼</span>
            </div>
        </div>
        <div class="category-content">
            ${type === 'savings' ? (categories.length > 0 ? createSavingsCategoryTable(categories) : createSavingsMessage(totals)) : createCategoryTable(categories)}
            ${type !== 'savings' ? `<button class="add-item-btn" onclick="showAddCategoryModal('${type}')">+ Add Category</button>` : 
              (categories.length > 0 ? '<div class="savings-actions" style="margin-top: 1rem;"><a href="savings.php" class="btn-primary">Manage All Goals</a></div>' : '<div class="savings-actions"><a href="savings.php" class="btn-primary">Create Your First Goal</a></div>')}
        </div>
    `;
    
    return section;
}

// Helper function to create savings message for budget page when no goals exist
function createSavingsMessage(totals) {
    // Get savings data from the passed totals
    const plannedSavings = totals?.planned || 0;
    const actualSavings = totals?.actual || 0;
    
    return `
        <div class="savings-message">
            <div class="message-icon">💰</div>
            <h4>Start Saving with Goals</h4>
            <p>Create specific savings goals to track your progress and allocate your savings budget effectively.</p>
            <div class="savings-info">
                <div class="info-item">
                    <span class="label">Savings Budget Available:</span>
                    <span class="value">${formatCurrency(plannedSavings)}</span>
                </div>
                <div class="info-item">
                    <span class="label">Current Month Savings:</span>
                    <span class="value">${formatCurrency(actualSavings)}</span>
                </div>
            </div>
            <div class="savings-actions">
                <a href="savings.php" class="btn-primary">Create Your First Goal</a>
                <button onclick="showSavingsInfo()" class="btn-secondary">Learn More</button>
            </div>
        </div>
    `;
}

// Function to show savings information modal
function showSavingsInfo() {
    showSnackbar('💡 Savings goals help you track specific financial objectives like emergency funds, vacation savings, or major purchases. Use the Savings page to create and manage your goals.', 'info');
}

// Helper function to calculate category status with better logic
function getCategoryStatus(spent, limit) {
    if (!limit || limit === 0) {
        return {
            status: 'no_limit',
            text: 'No limit set',
            class: 'neutral'
        };
    }
    
    const percentage = (spent / limit) * 100;
    
    if (spent === 0) {
        return {
            status: 'unused',
            text: 'Not used',
            class: 'good'
        };
    } else if (percentage < 50) {
        return {
            status: 'low',
            text: 'Low usage',
            class: 'good'
        };
    } else if (percentage < 80) {
        return {
            status: 'moderate',
            text: 'Moderate usage',
            class: 'good'
        };
    } else if (percentage < 95) {
        return {
            status: 'high',
            text: 'High usage',
            class: 'warning'
        };
    } else if (percentage < 99.99) {  // Changed from < 100 to < 99.99
        return {
            status: 'near_limit',
            text: 'Near limit',
            class: 'warning'
        };
    } else if (percentage >= 99.99 && percentage <= 100.01) { // When spending equals or very close to limit
        return {
            status: 'limit_reached',
            text: 'Limit reached',
            class: 'danger'
        };
    } else {
        return {
            status: 'over_budget',
            text: 'Over budget',
            class: 'danger'
        };
    }
}

// Helper functions for status display
function getStatusClass(status) {
    const statusClasses = {
        'over_allocation': 'status-danger',
        'over_budget': 'status-danger',
        'limit_reached': 'status-danger',
        'over_allocated': 'status-warning',
        'near_limit': 'status-warning',
        'high': 'status-warning',
        'on_track': 'status-success',
        'good': 'status-success',
        'moderate': 'status-success',
        'low': 'status-success',
        'unused': 'status-success',
        'no_limit': 'status-neutral',
        'neutral': 'status-neutral'
    };
    return statusClasses[status] || 'status-neutral';
}

function getStatusText(status) {
    const statusTexts = {
        'over_allocation': 'Over Allocation',
        'over_budget': 'Over Budget',
        'over_allocated': 'Over Allocated',
        'near_limit': 'Near Limit',
        'on_track': 'On Track',
        'good': 'Good',
        'under_budget': 'Under Budget'
    };
    return statusTexts[status] || 'Unknown';
}

function createCategoryTable(categories) {
    // Ensure categories is an array
    if (!categories || !Array.isArray(categories)) {
        console.warn('createCategoryTable: categories is not an array:', categories);
        categories = []; // Set to empty array
    }
    
    if (categories.length === 0) {
        return `
            <div class="empty-category">
                <p>No categories added yet. Click "Add Category" to get started.</p>
            </div>
        `;
    }

    let tableHTML = `
        <div class="budget-table">
            <div class="table-header">
                <div class="col-item">Category</div>
                <div class="col-planned">Budget Limit</div>
                <div class="col-actual">Spent</div>
                <div class="col-variance">Variance</div>
                <div class="col-status">Status</div>
                <div class="col-actions">Actions</div>
            </div>
    `;

    categories.forEach(category => {
        const budgetLimit = parseFloat(category.budget_limit || 0);
        const spentAmount = parseFloat(category.actual_spent || 0);
        const variance = budgetLimit - spentAmount;
        const percentageUsed = budgetLimit > 0 ? (spentAmount / budgetLimit) * 100 : 0;
        const expenseCount = category.transaction_count || 0;
        const budgetPeriod = category.budget_period || 'monthly';
        const displayBudgetLimit = category.display_budget_limit || budgetLimit;
        const originalBudgetLimit = category.original_budget_limit || budgetLimit;
        
        // Use the new status calculation function
        const statusInfo = getCategoryStatus(spentAmount, budgetLimit);
        const statusClass = getStatusClass(statusInfo.status);
        const statusText = statusInfo.text;
        const varianceClass = variance >= 0 ? 'positive' : 'negative';
        
        // Create period indicator
        const periodIndicator = budgetPeriod === 'weekly' ? 
            `<small class="period-indicator weekly">Weekly: ${formatCurrency(originalBudgetLimit)}</small>` :
            `<small class="period-indicator monthly">Monthly</small>`;
        
        tableHTML += `
            <div class="budget-item ${statusInfo.status === 'over_budget' ? 'over-budget' : ''}" data-category-id="${category.id}">
                <div class="col-item">
                    <span class="item-icon" style="color: ${category.color}">${getIconHTML(category.icon)}</span>
                    <div class="item-info">
                        <h5>${category.name}</h5>
                        <p>${expenseCount} transaction${expenseCount !== 1 ? 's' : ''} this month</p>
                        ${periodIndicator}
                    </div>
                </div>
                <div class="col-planned">
                    <span class="editable-amount" onclick="makeEditable(this, ${category.id}, 'budget_limit')">${formatCurrency(budgetLimit)}</span>
                    <small class="monthly-note">Monthly limit</small>
                </div>
                <div class="col-actual">
                    <span class="spent-amount ${statusInfo.status === 'over_budget' ? 'over-budget' : ''}">${formatCurrency(spentAmount)}</span>
                    <div class="usage-bar">
                        <div class="usage-fill ${statusInfo.status === 'over_budget' ? 'over-budget' : ''}" 
                             style="width: ${Math.min(percentageUsed, 100)}%"></div>
                    </div>
                    <small>${formatPercent(percentageUsed)} used</small>
                </div>
                <div class="col-variance ${varianceClass}">
                    <span class="variance-amount">${variance >= 0 ? '+' : ''}${formatCurrency(variance)}</span>
                    <small>${variance >= 0 ? 'Under budget' : 'Over budget'}</small>
                </div>
                <div class="col-status">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="col-actions">
                    <button class="action-btn" onclick="editBudgetCategory(${category.id})" title="Edit Budget">✏️</button>
                    <button class="action-btn primary" onclick="addExpenseToCategory(${category.id}, '${category.name}')" title="Add Expense">💰</button>
                    <button class="action-btn" onclick="viewCategoryExpenses(${category.id}, '${category.name}')" title="View Expenses">📊</button>
                    <button class="action-btn danger" onclick="deleteCategory(${category.id})" title="Delete Category">🗑️</button>
                </div>
            </div>
        `;
    });

    tableHTML += '</div>';
    return tableHTML;
}

// Specialized function to create savings category table with appropriate terminology
function createSavingsCategoryTable(categories) {
    if (categories.length === 0) {
        return `
            <div class="empty-category">
                <p>No savings goals created yet. Create your first goal to start tracking your savings progress.</p>
            </div>
        `;
    }

    let tableHTML = `
        <div class="budget-table savings-table">
            <div class="table-header">
                <div class="col-item">Savings Goal</div>
                <div class="col-target">Target Amount</div>
                <div class="col-actual">Contributed</div>
                <div class="col-progress">Progress</div>
                <div class="col-status">Status</div>
                <div class="col-actions">Actions</div>
            </div>
    `;

    categories.forEach(category => {
        const monthlyTarget = parseFloat(category.budget_limit || 0);
        const contributed = parseFloat(category.actual_spent || 0); // 'actual_spent' is actually contributed for savings
        const variance = contributed - monthlyTarget;
        const percentageAchieved = monthlyTarget > 0 ? (contributed / monthlyTarget) * 100 : 0;
        const contributionCount = category.transaction_count || 0;
        const goalId = category.goal_id;
        const targetAmount = parseFloat(category.target_amount || 0);
        const currentAmount = parseFloat(category.current_amount || 0);
        const overallProgress = targetAmount > 0 ? (currentAmount / targetAmount) * 100 : 0;
        
        // For savings, we want different status logic based on overall progress
        let statusInfo;
        if (currentAmount === 0) {
            statusInfo = { status: 'not_started', text: 'Not Started', class: 'neutral' };
        } else if (overallProgress >= 100) {
            statusInfo = { status: 'goal_achieved', text: 'Goal Achieved', class: 'success' };
        } else if (overallProgress >= 75) {
            statusInfo = { status: 'on_track', text: 'On Track', class: 'good' };
        } else if (overallProgress >= 25) {
            statusInfo = { status: 'moderate', text: 'In Progress', class: 'warning' };
        } else {
            statusInfo = { status: 'low', text: 'Just Started', class: 'info' };
        }
        
        const varianceClass = variance >= 0 ? 'positive' : 'negative';
        
        // Create goal name without (Savings) suffix for display
        const displayName = category.name.replace(' (Savings)', '');
        
        tableHTML += `
            <div class="budget-item savings-item" data-category-id="${category.id}" data-goal-id="${goalId}">
                <div class="col-item">
                    <span class="item-icon" style="color: ${category.color}">${getIconHTML(category.icon)}</span>
                    <div class="item-info">
                        <h5>${displayName}</h5>
                        <p>${contributionCount} contribution${contributionCount !== 1 ? 's' : ''} this month</p>
                    </div>
                </div>
                <div class="col-target">
                    <span class="target-amount">${formatCurrency(targetAmount)}</span>
                    <small>Goal target</small>
                </div>
                <div class="col-actual">
                    <span class="contributed-amount">${formatCurrency(currentAmount)}</span>
                    <small>Total saved</small>
                </div>
                <div class="col-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${Math.min(overallProgress, 100)}%"></div>
                    </div>
                    <span class="progress-text">${overallProgress.toFixed(1)}%</span>
                </div>
                <div class="col-status">
                    <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                </div>
                <div class="col-actions">
                    <button class="action-btn" onclick="editSavingsGoal(${goalId})" title="Edit Goal">✏️</button>
                    <button class="action-btn primary" onclick="addToSavingsGoal(${goalId}, '${displayName}')" title="Add Money">💰</button>
                    <button class="action-btn" onclick="viewGoalDetails(${goalId}, '${displayName}')" title="View Details">📊</button>
                    <button class="action-btn secondary" onclick="goToSavingsPage()" title="Manage in Savings">🎯</button>
                </div>
            </div>
        `;
    });

    tableHTML += '</div>';
    return tableHTML;
}


function getStatusClass(status) {
    // If status is a string, return the appropriate class
    if (typeof status === 'string') {
        switch(status) {
            case 'over_allocation': return 'over_allocation';
            case 'over_budget': return 'over-budget';
            case 'over_allocated': return 'over_allocated';
            case 'near_limit': return 'near_limit';
            case 'on_track': return 'on-track';
            case 'good': return 'good';
            case 'not_started': return 'not-started';
            default: return 'on-track';
        }
    }
    
    // Legacy support for numerical comparison (if needed)
    const planned = arguments[0];
    const actual = arguments[1];
    if (typeof planned === 'number' && typeof actual === 'number') {
        const variance = actual - planned;
        const percentageVariance = planned > 0 ? Math.abs(variance / planned) * 100 : 0;
        
        if (actual === 0) return 'not-started';
        if (variance === 0) return 'on-track';
        if (variance < 0) return 'under-budget';
        if (percentageVariance <= 10) return 'near-target';
        if (actual > planned * 1.5) return 'exceeded';
        return 'over-budget';
    }
    
    return 'on-track';
}

function getStatusText(status) {
    // If status is a string, return the appropriate text
    if (typeof status === 'string') {
        switch(status) {
            case 'over_allocation': return 'Over Allocation';
            case 'over_budget': return 'Over Budget';
            case 'over_allocated': return 'Over Allocated';
            case 'near_limit': return 'Near Limit';
            case 'on_track': return 'On Track';
            case 'good': return 'Good';
            case 'not_started': return 'Not Started';
            default: return 'On Track';
        }
    }
    
    // Legacy support for numerical comparison (if needed)
    const planned = arguments[0];
    const actual = arguments[1];
    if (typeof planned === 'number' && typeof actual === 'number') {
        const variance = actual - planned;
        const percentageVariance = planned > 0 ? Math.abs(variance / planned) * 100 : 0;
        
        if (actual === 0) return 'Not Started';
        if (variance === 0) return 'On Track';
        if (variance < 0) return 'Under Budget';
        if (percentageVariance <= 10) return 'Near Target';
        if (actual > planned * 1.5) return 'Exceeded';
        return 'Over Budget';
    }
    
    return 'On Track';
}

// State Management Functions
function showLoadingState() {
    const loading = document.getElementById('budgetLoading');
    if (loading) loading.style.display = 'block';
}

function hideLoadingState() {
    const loading = document.getElementById('budgetLoading');
    if (loading) loading.style.display = 'none';
}

function showEmptyState() {
    hideLoadingState();
    const container = document.getElementById('budgetCategoriesContainer');
    const emptyState = document.getElementById('emptyBudgetState');
    
    if (container) container.style.display = 'none';
    if (emptyState) emptyState.style.display = 'block';
}

function hideEmptyState() {
    const emptyState = document.getElementById('emptyBudgetState');
    if (emptyState) emptyState.style.display = 'none';
}

// Modal Functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function showAddCategoryModal(categoryType = '') {
    const modal = document.getElementById('addCategoryModal');
    if (modal) {
        const typeSelect = modal.querySelector('select[name="category_type"]');
        if (typeSelect && categoryType) {
            typeSelect.value = categoryType;
        }
        
        // Reset budget input type to amount
        const amountRadio = modal.querySelector('#budgetAmount');
        const amountInput = modal.querySelector('#amountInput');
        const percentageInput = modal.querySelector('#percentageInput');
        const percentageInfo = modal.querySelector('.percentage-info');
        
        if (amountRadio) {
            amountRadio.checked = true;
            if (amountInput) amountInput.style.display = 'flex';
            if (percentageInput) percentageInput.style.display = 'none';
            if (percentageInfo) percentageInfo.classList.remove('show');
        }
        
        // Clear form values
        const form = modal.querySelector('#addCategoryForm');
        if (form) {
            form.reset();
            // Re-set category type if provided
            if (typeSelect && categoryType) {
                typeSelect.value = categoryType;
            }
        }
        
        showModal('addCategoryModal');
    }
}

// Category Management Functions
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

function editBudgetCategory(categoryId) {
    const category = budgetData.categories.find(cat => cat.id === categoryId);
    if (!category) return;

    currentEditingCategory = category;
    
    // Populate edit modal with category data
    const modal = document.getElementById('addCategoryModal');
    if (!modal) return;

    const nameInput = modal.querySelector('input[name="name"]');
    const typeSelect = modal.querySelector('select[name="category_type"]');
    const budgetInput = modal.querySelector('input[name="budget_limit"]');
    const iconInput = modal.querySelector('input[name="icon"]');
    const colorInput = modal.querySelector('input[name="color"]');

    if (nameInput) nameInput.value = category.name;
    if (typeSelect) typeSelect.value = category.category_type;
    if (budgetInput) budgetInput.value = category.budget_limit;
    if (iconInput) iconInput.value = category.icon;
    if (colorInput) colorInput.value = category.color;

    // Update modal title
    const modalTitle = modal.querySelector('.modal-header h3');
    if (modalTitle) modalTitle.textContent = 'Edit Category';

    // Update button text
    const submitBtn = modal.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.textContent = 'Update Category';

    showModal('addCategoryModal');
}

function addExpenseToCategory(categoryId, categoryName) {
    // Find the category data to get the budget limit
    const category = budgetData?.categories?.find(cat => cat.id === categoryId);
    
    if (!category) {
        showSnackbar(`Category not found: ${categoryName}`, 'error');
        return;
    }
    
    const budgetLimit = category.budget_limit || 0;
    const spent = category.actual_spent || 0;
    const remaining = budgetLimit - spent;
    
    if (budgetLimit <= 0) {
        showSnackbar(`No budget limit set for "${categoryName}". Please set a budget limit first.`, 'warning');
        return;
    }
    
    if (remaining <= 0) {
        showSnackbar(`Budget already fully spent for "${categoryName}". Remaining: ${formatCurrency(remaining)}`, 'warning');
        return;
    }
    
    // Automatically add expense for the remaining budget amount
    const expenseAmount = remaining;
    const today = new Date().toISOString().split('T')[0];
    const expenseData = {
        category_id: categoryId,
        amount: expenseAmount,
        description: `Budget allocation for ${categoryName}`,
        expense_date: today
    };
    
    // Send to server
    fetch('../actions/personal_expense_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'add_expense',
            category_id: categoryId,
            amount: expenseAmount,
            description: expenseData.description,
            expense_date: expenseData.expense_date
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSnackbar(`Expense of ${formatCurrency(expenseAmount)} added to ${categoryName}`, 'success');
            loadBudgetData(); // Refresh the budget data
        } else {
            showSnackbar(result.message || 'Failed to add expense', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding expense:', error);
        showSnackbar('Error adding expense', 'error');
    });
}

function viewCategoryExpenses(categoryId, categoryName) {
    // Navigate to the personal expense page with the category pre-selected
    // You can modify this to open a modal instead if preferred
    const url = `../templates/personal-expense.php?category=${categoryId}&name=${encodeURIComponent(categoryName)}`;
    window.location.href = url;
}

function makeEditable(element, categoryId, field) {
    const currentValue = element.textContent.replace('₵', '').replace(',', '');
    const input = document.createElement('input');
    input.type = 'number';
    input.step = '0.01';
    input.value = currentValue;
    input.className = 'editable-input';
    
    input.addEventListener('blur', async function() {
        const newValue = parseFloat(this.value);
        if (!isNaN(newValue) && newValue >= 0) {
            try {
                const response = await fetch('../api/budget_categories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_category',
                        category_id: categoryId,
                        [field]: newValue
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    element.textContent = formatCurrency(newValue);
                    showSnackbar('Budget limit updated successfully', 'success');
                    await loadBudgetData(); // Refresh data
                } else {
                    element.textContent = formatCurrency(currentValue);
                    showSnackbar('Failed to update budget limit', 'error');
                }
            } catch (error) {
                element.textContent = formatCurrency(currentValue);
                showSnackbar('Error updating budget limit', 'error');
            }
        } else {
            element.textContent = formatCurrency(currentValue);
        }
        element.style.display = 'inline';
        this.remove();
    });
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.blur();
        }
    });
    
    element.style.display = 'none';
    element.parentNode.insertBefore(input, element.nextSibling);
    input.focus();
    input.select();
}

// Form Setup and Handlers
function setupFormHandlers() {
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Saving...';
            submitButton.disabled = true;
            
            try {
                const formData = new FormData(this);
                
                // Use enhanced calculation for budget limit
                const calculatedBudgetLimit = enhancedSubmitAddCategory(formData);
                
                const categoryData = {
                    name: formData.get('name'),
                    category_type: formData.get('category_type'),
                    budget_limit: calculatedBudgetLimit,
                    budget_period: formData.get('budget_period') || 'monthly',
                    icon: formData.get('icon'),
                    color: formData.get('color')
                };

                let success = false;
                if (currentEditingCategory) {
                    // Edit existing category
                    success = await editCategory(currentEditingCategory.id, categoryData);
                } else {
                    // Add new category
                    success = await addCategory(categoryData);
                }

                if (success) {
                    this.reset();
                    resetModalForm();
                    closeModal('addCategoryModal');
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showSnackbar('Error saving category', 'error');
            } finally {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                currentEditingCategory = null;
            }
        });
    }
}

function resetModalForm() {
    const modal = document.getElementById('addCategoryModal');
    if (!modal) return;

    // Reset title
    const modalTitle = modal.querySelector('.modal-header h3');
    if (modalTitle) modalTitle.textContent = 'Add New Category';

    // Reset button text
    const submitBtn = modal.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.textContent = 'Add Category';

    // Reset icon selection
    const iconOptions = modal.querySelectorAll('.icon-option');
    iconOptions.forEach(option => option.classList.remove('selected'));
    if (iconOptions[0]) {
        iconOptions[0].classList.add('selected');
        const iconInput = modal.querySelector('input[name="icon"]');
        if (iconInput) iconInput.value = iconOptions[0].dataset.icon;
    }

    // Reset color selection
    const colorOptions = modal.querySelectorAll('.color-option');
    colorOptions.forEach(option => option.classList.remove('selected'));
    if (colorOptions[0]) {
        colorOptions[0].classList.add('selected');
        const colorInput = modal.querySelector('input[name="color"]');
        if (colorInput) colorInput.value = colorOptions[0].dataset.color;
    }

    currentEditingCategory = null;
}

function setupIconAndColorSelectors() {
    // Icon selection
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('icon-option')) {
            const container = e.target.closest('.icon-selector');
            if (container) {
                const options = container.querySelectorAll('.icon-option');
                options.forEach(option => option.classList.remove('selected'));
                e.target.classList.add('selected');
                
                const hiddenInput = container.parentNode.querySelector('input[name="icon"]');
                if (hiddenInput) {
                    hiddenInput.value = e.target.dataset.icon;
                }
            }
        }
        
        // Color selection
        if (e.target.classList.contains('color-option')) {
            const container = e.target.closest('.color-selector');
            if (container) {
                const options = container.querySelectorAll('.color-option');
                options.forEach(option => option.classList.remove('selected'));
                e.target.classList.add('selected');
                
                const hiddenInput = container.parentNode.querySelector('input[name="color"]');
                if (hiddenInput) {
                    hiddenInput.value = e.target.dataset.color;
                }
            }
        }
    });
}

// Other UI Functions
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
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
        document.querySelectorAll('.budget-table').forEach(table => {
            table.style.display = 'none';
        });
        showSnackbar('Switched to summary view', 'info');
    } else {
        document.querySelectorAll('.budget-table').forEach(table => {
            table.style.display = 'block';
        });
        showSnackbar('Switched to detailed view', 'info');
    }
}

// Initialize Page
async function initializePage() {
    showLoadingState();
    
    // Setup event handlers
    setupFormHandlers();
    setupIconAndColorSelectors();
    
    // Close modals when clicking outside
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
    
    // Reload data when page becomes visible (user returns to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            loadBudgetData();
        }
    });
    
    // Also reload when window gains focus
    window.addEventListener('focus', function() {
        loadBudgetData();
    });
    
    // Load budget data
    await loadBudgetData();
    
}

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage);
} else {
    initializePage();
}

// Export functions for global access
window.budgetFunctions = {
    toggleCategory,
    switchView,
    showAddCategoryModal,
    editBudgetCategory,
    addExpenseToCategory,
    viewCategoryExpenses,
    makeEditable,
    deleteCategory,
    toggleUserMenu,
    closeModal,
    showModal,
    showTemplateModal,
    showBudgetTemplateModal,
    resetTemplateModal,
    selectTemplate,
    applyTemplate,
    clearTemplate,
    showCustomTemplate,
    backToTemplates,
    updateCustomTemplate,
    saveCustomTemplate,
    loadCustomTemplates,
    deleteCustomTemplate,
    syncSliderWithInput
};

// Make functions globally accessible for HTML onclick events
window.showBudgetTemplateModal = showBudgetTemplateModal;
window.resetTemplateModal = resetTemplateModal;
window.selectTemplate = selectTemplate;
window.applyTemplate = applyTemplate;
window.clearTemplate = clearTemplate;
window.showCustomTemplate = showCustomTemplate;
window.backToTemplates = backToTemplates;
window.updateCustomTemplate = updateCustomTemplate;
window.saveCustomTemplate = saveCustomTemplate;
window.deleteCustomTemplate = deleteCustomTemplate;
window.syncSliderWithInput = syncSliderWithInput;
window.makeEditable = makeEditable;
window.addExpenseToCategory = addExpenseToCategory;
window.viewCategoryExpenses = viewCategoryExpenses;
window.editBudgetCategory = editBudgetCategory;
window.deleteCategory = deleteCategory;
window.addExpenseToCategory = addExpenseToCategory;
window.viewCategoryExpenses = viewCategoryExpenses;

// Additional functions needed for HTML integration
window.showAddCategoryModal = (categoryType = '') => {
    const modal = document.getElementById('addCategoryModal');
    if (modal) {
        const typeSelect = modal.querySelector('select[name="category_type"]');
        if (typeSelect && categoryType) {
            typeSelect.value = categoryType;
        }
        showModal('addCategoryModal');
    }
};

window.closeModal = closeModal;
window.toggleUserMenu = toggleUserMenu;
window.switchView = switchView;
window.toggleBudgetInputType = toggleBudgetInputType;

// Savings-specific functions for budget integration
window.editSavingsGoal = function(goalId) {
    window.open(`savings.php?edit=${goalId}`, '_blank');
};

window.addToSavingsGoal = function(goalId, goalName) {
    if (confirm(`Add money to your "${goalName}" savings goal?`)) {
        window.open(`savings.php?contribute=${goalId}`, '_blank');
    }
};

window.viewGoalDetails = function(goalId, goalName) {
    window.open(`savings.php?goal=${goalId}`, '_blank');
};

window.goToSavingsPage = function() {
    window.open('savings.php', '_blank');
};