// Budget Page JavaScript - Backend Integrated Version

// Global Variables
let budgetData = null;
let currentEditingCategory = null;

// Utility Functions
function formatCurrency(amount) {
    return `₵${parseFloat(amount).toFixed(2)}`;
}

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

// API Functions
async function loadBudgetData() {
    try {
        // Load personal dashboard data for financial overview
        const dashboardResponse = await fetch('../api/personal_dashboard_data.php');
        const dashboardData = await dashboardResponse.json();
        
        // Load budget categories
        const categoriesResponse = await fetch('../api/budget_categories.php');
        const categoriesData = await categoriesResponse.json();
        
        if (dashboardData.success && categoriesData.success) {
            // Combine data into expected format
            budgetData = {
                total_monthly_income: dashboardData.financial_overview?.monthly_income || 0,
                categories: categoriesData.categories || [],
                summary: calculateBudgetSummary(categoriesData.categories || [], dashboardData.financial_overview || {}),
                category_type_totals: calculateCategoryTypeTotals(categoriesData.categories || [])
            };
            
            updateBudgetOverview();
            renderBudgetCategories();
            updateBudgetSummary();
        } else {
            throw new Error(categoriesData.message || dashboardData.message || 'Failed to load budget data');
        }
    } catch (error) {
        console.error('Error loading budget data:', error);
        showSnackbar('Error loading budget data: ' + error.message, 'error');
        showEmptyState();
    }
}

// Helper function to calculate budget summary
function calculateBudgetSummary(categories, financialOverview) {
    const totalPlanned = categories.reduce((sum, cat) => sum + parseFloat(cat.budget_limit || 0), 0);
    const totalActual = financialOverview.monthly_expenses || 0;
    const totalIncome = financialOverview.monthly_income || 0;
    const remainingBudget = totalPlanned - totalActual;
    const availableBalance = totalIncome - totalPlanned;
    const incomeUtilization = totalIncome > 0 ? Math.round((totalPlanned / totalIncome) * 100) : 0;
    const budgetPerformance = totalPlanned > 0 ? Math.max(0, Math.round(((totalPlanned - totalActual) / totalPlanned) * 100)) : 100;
    const totalVariance = totalPlanned - totalActual;
    
    return {
        total_planned: totalPlanned,
        total_actual: totalActual,
        remaining_budget: remainingBudget,
        available_balance: availableBalance,
        income_utilization: incomeUtilization,
        budget_performance: budgetPerformance,
        total_variance: totalVariance
    };
}

// Helper function to calculate category type totals
function calculateCategoryTypeTotals(categories) {
    const types = ['needs', 'wants', 'savings'];
    const totals = {};
    
    types.forEach(type => {
        const typeCategories = categories.filter(cat => cat.category_type === type);
        const planned = typeCategories.reduce((sum, cat) => sum + parseFloat(cat.budget_limit || 0), 0);
        const actual = 0; // TODO: Calculate from actual expenses
        
        // Get template percentage for this type if it exists
        const templatePercentage = getTemplatePercentageForType(type);
        const progressPercentage = templatePercentage || (planned > 0 ? Math.round((actual / planned) * 100) : 0);
        
        totals[type] = {
            planned: planned,
            actual: actual,
            progress_percentage: progressPercentage,
            template_percentage: templatePercentage
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

// Get applied template information
function getAppliedTemplateInfo() {
    const savedTemplate = localStorage.getItem('appliedBudgetTemplate');
    if (savedTemplate) {
        try {
            return JSON.parse(savedTemplate);
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
    
    const needsAmount = Math.round((monthlyIncome * needsPercent) / 100);
    const wantsAmount = Math.round((monthlyIncome * wantsPercent) / 100);
    const savingsAmount = Math.round((monthlyIncome * savingsPercent) / 100);
    
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
    
    // Close template modal
    closeModal('budgetTemplateModal');
    
    // Refresh budget display to show template percentages
    if (typeof loadBudgetData === 'function') {
        loadBudgetData();
    }
    
    // Show success message
    showSnackbar(`Template "${templateName}" applied successfully!`, 'success');
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

    const totalIncomeEl = document.getElementById('totalIncome');
    const plannedBudgetEl = document.getElementById('plannedBudget');
    const actualSpendingEl = document.getElementById('actualSpending');
    const budgetPerformanceEl = document.getElementById('budgetPerformance');
    const budgetSurplusEl = document.getElementById('budgetSurplus');
    const spendingVarianceEl = document.getElementById('spendingVariance');
    const performanceLabelEl = document.getElementById('performanceLabel');

    if (totalIncomeEl) {
        totalIncomeEl.textContent = formatCurrency(budgetData.total_monthly_income);
    }

    if (plannedBudgetEl) {
        plannedBudgetEl.textContent = formatCurrency(budgetData.summary.total_planned);
    }

    if (actualSpendingEl) {
        actualSpendingEl.textContent = formatCurrency(budgetData.summary.total_actual);
    }

    if (budgetPerformanceEl) {
        budgetPerformanceEl.textContent = budgetData.summary.budget_performance + '%';
    }

    // Update variance displays
    const surplus = budgetData.summary.available_balance;
    if (budgetSurplusEl) {
        budgetSurplusEl.textContent = surplus >= 0 ? `₵${surplus.toFixed(2)} surplus` : `₵${Math.abs(surplus).toFixed(2)} deficit`;
        budgetSurplusEl.className = surplus >= 0 ? 'change positive' : 'change negative';
    }

    const variance = budgetData.summary.total_variance;
    if (spendingVarianceEl) {
        spendingVarianceEl.textContent = variance >= 0 ? `₵${variance.toFixed(2)} under budget` : `₵${Math.abs(variance).toFixed(2)} over budget`;
        spendingVarianceEl.className = variance >= 0 ? 'change positive' : 'change negative';
    }

    if (performanceLabelEl) {
        const performance = budgetData.summary.budget_performance;
        if (performance >= 95) {
            performanceLabelEl.textContent = 'Excellent tracking';
        } else if (performance >= 80) {
            performanceLabelEl.textContent = 'Good tracking';
        } else if (performance >= 60) {
            performanceLabelEl.textContent = 'Fair tracking';
        } else {
            performanceLabelEl.textContent = 'Needs improvement';
        }
    }

    // Show applied template information
    updateAppliedTemplateDisplay();
}

function updateAppliedTemplateDisplay() {
    const appliedTemplate = getAppliedTemplateInfo();
    let templateInfoEl = document.getElementById('appliedTemplateInfo');
    
    // Create template info element if it doesn't exist
    if (!templateInfoEl) {
        templateInfoEl = document.createElement('div');
        templateInfoEl.id = 'appliedTemplateInfo';
        templateInfoEl.className = 'applied-template-info';
        
        // Insert after budget overview or at the top of the page
        const overviewSection = document.querySelector('.budget-overview') || document.querySelector('.main-content');
        if (overviewSection) {
            overviewSection.appendChild(templateInfoEl);
        }
    }
    
    if (appliedTemplate) {
        templateInfoEl.innerHTML = `
            <div class="template-banner">
                <div class="template-icon">📊</div>
                <div class="template-details">
                    <h4>Active Budget Template: ${appliedTemplate.name}</h4>
                    <p>Applied on ${new Date(appliedTemplate.appliedDate).toLocaleDateString()}</p>
                    <div class="template-allocations">
                        <span class="allocation needs">Needs: ${appliedTemplate.needs}%</span>
                        <span class="allocation wants">Wants: ${appliedTemplate.wants}%</span>
                        <span class="allocation savings">Savings: ${appliedTemplate.savings}%</span>
                    </div>
                </div>
                <button onclick="clearTemplate()" class="clear-template-btn" title="Remove template">×</button>
            </div>
        `;
        templateInfoEl.style.display = 'block';
    } else {
        templateInfoEl.style.display = 'none';
    }
}

function clearTemplate() {
    // Create a confirmation modal instead of browser alert
    const confirmClear = confirm('Are you sure you want to remove the active budget template?');
    
    if (confirmClear) {
        localStorage.removeItem('appliedBudgetTemplate');
        loadBudgetData(); // Refresh to show regular percentages
        showSnackbar('Budget template removed successfully!', 'success');
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
        const typeCategories = budgetData.categories.filter(cat => cat.category_type === type);
        const typeInfo = categoryTypes[type];
        const typeTotals = budgetData.category_type_totals[type] || { planned: 0, actual: 0, progress_percentage: 0 };

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
    
    // Get applied template info
    const appliedTemplate = getAppliedTemplateInfo();
    const templateText = totals.template_percentage ? 
        `${totals.template_percentage}% allocated (${appliedTemplate.name || 'Template'})` : 
        `${totals.progress_percentage}%`;
    
    section.innerHTML = `
        <div class="category-header" onclick="toggleCategory('${type}')">
            <div class="category-info">
                <span class="category-icon">${typeInfo.icon}</span>
                <div class="category-details">
                    <h4>${typeInfo.title}</h4>
                    <p>${typeInfo.description}</p>
                </div>
            </div>
            <div class="category-summary">
                <div class="category-amount">${formatCurrency(totals.actual)} / ${formatCurrency(totals.planned)}</div>
                <div class="category-progress">
                    <div class="progress-bar">
                        <div class="progress-fill ${type}-progress" style="width: ${Math.min(totals.progress_percentage, 100)}%"></div>
                    </div>
                    <span class="progress-text">${templateText}</span>
                </div>
                <span class="expand-icon">▼</span>
            </div>
        </div>
        <div class="category-content">
            ${createCategoryTable(categories)}
            <button class="add-item-btn" onclick="showAddCategoryModal('${type}')">+ Add Category</button>
        </div>
    `;
    
    return section;
}

function createCategoryTable(categories) {
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
        const actualSpent = 0; // TODO: Calculate from actual expenses
        const variance = parseFloat(category.budget_limit) - actualSpent;
        const statusClass = getStatusClass(category.budget_limit, actualSpent);
        const statusText = getStatusText(category.budget_limit, actualSpent);
        const varianceClass = variance >= 0 ? 'success' : 'warning';
        
        tableHTML += `
            <div class="budget-item" data-category-id="${category.id}">
                <div class="col-item">
                    <span class="item-icon" style="color: ${category.color}">${category.icon}</span>
                    <div class="item-info">
                        <h5>${category.name}</h5>
                        <p>0 transactions this month</p>
                    </div>
                </div>
                <div class="col-planned">
                    <span class="editable-amount" onclick="makeEditable(this, ${category.id}, 'budget_limit')">${formatCurrency(category.budget_limit)}</span>
                </div>
                <div class="col-actual">${formatCurrency(actualSpent)}</div>
                <div class="col-variance ${varianceClass}">${formatCurrency(variance)}</div>
                <div class="col-status">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="col-actions">
                    <button class="action-btn" onclick="editBudgetCategory(${category.id})" title="Edit Category">✏️</button>
                    <button class="action-btn" onclick="addExpenseToCategory(${category.id}, '${category.name}')" title="Add Expense">💰</button>
                    <button class="action-btn danger" onclick="deleteCategory(${category.id})" title="Delete Category">🗑️</button>
                </div>
            </div>
        `;
    });

    tableHTML += '</div>';
    return tableHTML;
}


function getStatusClass(planned, actual) {
    const variance = actual - planned;
    const percentageVariance = planned > 0 ? Math.abs(variance / planned) * 100 : 0;
    
    if (actual === 0) return 'not-started';
    if (variance === 0) return 'on-track';
    if (variance < 0) return 'under-budget';
    if (percentageVariance <= 10) return 'near-target';
    if (actual > planned * 1.5) return 'exceeded';
    return 'over-budget';
}

function getStatusText(planned, actual) {
    const variance = actual - planned;
    const percentageVariance = planned > 0 ? Math.abs(variance / planned) * 100 : 0;
    
    if (actual === 0) return 'Not Started';
    if (variance === 0) return 'On Track';
    if (variance < 0) return 'Under Budget';
    if (percentageVariance <= 10) return 'Near Target';
    if (actual > planned * 1.5) return 'Exceeded';
    return 'Over Budget';
}

function updateBudgetSummary() {
    if (!budgetData) return;

    const summaryPlanned = document.getElementById('summaryPlanned');
    const summaryActual = document.getElementById('summaryActual');
    const summaryRemaining = document.getElementById('summaryRemaining');
    const summaryAvailable = document.getElementById('summaryAvailable');
    const summaryPlannedPercent = document.getElementById('summaryPlannedPercent');
    const summaryActualPercent = document.getElementById('summaryActualPercent');
    const summaryRemainingPercent = document.getElementById('summaryRemainingPercent');

    const summary = budgetData.summary;
    const income = budgetData.total_monthly_income;

    if (summaryPlanned) {
        summaryPlanned.textContent = formatCurrency(summary.total_planned);
    }
    if (summaryActual) {
        summaryActual.textContent = formatCurrency(summary.total_actual);
    }
    if (summaryRemaining) {
        summaryRemaining.textContent = formatCurrency(summary.remaining_budget);
        summaryRemaining.className = summary.remaining_budget >= 0 ? 'summary-amount positive' : 'summary-amount negative';
    }
    if (summaryAvailable) {
        summaryAvailable.textContent = formatCurrency(summary.available_balance);
    }

    if (summaryPlannedPercent) {
        summaryPlannedPercent.textContent = `${summary.income_utilization}% of income`;
    }
    if (summaryActualPercent) {
        const actualPercent = income > 0 ? ((summary.total_actual / income) * 100).toFixed(1) : 0;
        summaryActualPercent.textContent = `${actualPercent}% of income`;
    }
    if (summaryRemainingPercent) {
        const remainingPercent = income > 0 ? ((summary.remaining_budget / income) * 100).toFixed(1) : 0;
        summaryRemainingPercent.textContent = `${remainingPercent}% unspent`;
    }
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
    showSnackbar(`Add expense functionality for "${categoryName}" coming soon`, 'info');
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
                const categoryData = {
                    name: formData.get('name'),
                    category_type: formData.get('category_type'),
                    budget_limit: formData.get('budget_limit'),
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
    
    // Load budget data
    await loadBudgetData();
    
    console.log('Budget page initialized successfully');
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