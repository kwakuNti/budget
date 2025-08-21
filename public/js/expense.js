// Animation function for counting numbers
function animateNumber(element, start, end, duration = 2000, prefix = '', suffix = '') {
    if (!element) return;
    
    const startTime = performance.now();
    const range = end - start;
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const current = start + (range * easeOutQuart);
        
        // Format number with appropriate decimal places
        const formattedNumber = current.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        element.textContent = prefix + formattedNumber + suffix;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        } else {
            // Ensure final value is exact
            const finalFormatted = end.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            element.textContent = prefix + finalFormatted + suffix;
        }
    }
    
    requestAnimationFrame(updateNumber);
}

// Function to load fresh expense data and animate
// Function to load fresh expense data and animate
async function loadExpenseData() {
    try {
        // For now, just animate the existing data from PHP
        setTimeout(() => {
            animateExpenseOverview();
        }, 500);
        
        // Add visibility change listener for refresh when returning to page
        if (typeof document.visibilityState !== 'undefined') {
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Page became visible again, refresh data
                    setTimeout(() => {
                        animateExpenseOverview();
                    }, 300);
                }
            });
        }
    } catch (error) {
        console.error('Error loading expense data:', error);
    }
}

// Function to animate the expense overview cards
function animateExpenseOverview() {
    const stats = window.expenseStats || {};
    
    // Animate Total Expenses
    const totalExpensesEl = document.getElementById('totalExpenses');
    if (totalExpensesEl) {
        animateNumber(totalExpensesEl, 0, parseFloat(stats.total_expenses) || 0, 2500);
    }
    
    // Animate Current Month Expenses
    const currentMonthEl = document.getElementById('currentMonthExpenses');
    if (currentMonthEl) {
        animateNumber(currentMonthEl, 0, parseFloat(stats.this_month_expenses) || 0, 2500);
    }
    
    // Animate Average Monthly Expenses
    const avgMonthlyEl = document.getElementById('avgMonthlyExpenses');
    if (avgMonthlyEl) {
        animateNumber(avgMonthlyEl, 0, parseFloat(stats.average_monthly) || 0, 2500);
    }
    
    // Animate top category (just text, no number animation needed)
    const largestCategoryEl = document.getElementById('largestCategory');
    if (largestCategoryEl && stats.top_category) {
        largestCategoryEl.textContent = stats.top_category;
    }
}

// Function to update chart data after expense changes
function updateChartsData() {
    // Placeholder for chart updates
    // You could fetch fresh chart data here and update the charts
}

// Function to update charts with fresh data from API
function updateChartsWithFreshData(data) {
    try {
        // Update expense trends chart
        if (expenseTrendsChart && data.expense_trends_data) {
            expenseTrendsChart.data = data.expense_trends_data;
            expenseTrendsChart.update();
        }
        
        // Update category chart
        if (categoryChart && data.category_data) {
            categoryChart.data = data.category_data;
            categoryChart.update();
        }
        
    } catch (error) {
        console.error('Error updating charts:', error);
    }
}

// Sidebar functionality
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 1024 && 
        sidebar.classList.contains('open') && 
        !sidebar.contains(e.target) && 
        !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// Chart configurations and data
const chartColors = {
    primary: '#1e293b',
    secondary: '#64748b',
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#3b82f6',
    purple: '#8b5cf6'
};

// Get real data from PHP (passed via window variables)
let expensesData = window.recentExpenses || [];
let familyId = window.familyId || 1;

// Category mappings
const categoryInfo = {
    utilities: { icon: '⚡', name: 'Utilities', color: '#f59e0b' },
    dstv: { icon: '📺', name: 'DSTV', color: '#8b5cf6' },
    wifi: { icon: '📶', name: 'WiFi', color: '#3b82f6' },
    dining: { icon: '🍽️', name: 'Dining', color: '#10b981' },
    maintenance: { icon: '🔧', name: 'Maintenance', color: '#ef4444' },
    other: { icon: '📦', name: 'Other', color: '#64748b' }
};

// Initialize charts
let expenseTrendsChart, categoryChart;

// Expense Trends Chart - Initialize with dynamic data
const expenseTrendsCtx = document.getElementById('expenseTrendsChart').getContext('2d');
expenseTrendsChart = new Chart(expenseTrendsCtx, {
    type: 'line',
    data: {
        labels: window.initialExpenseTrends.labels || [],
        datasets: [{
            label: 'Monthly Expenses',
            data: window.initialExpenseTrends.data || [],
            borderColor: chartColors.danger,
            backgroundColor: chartColors.danger + '20',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: chartColors.danger,
            pointRadius: 5,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 14 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                cornerRadius: 8,
                displayColors: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    callback: function(value) {
                        return '₵' + value;
                    }
                }
            },
            x: {
                grid: { display: false }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Generate colors for categories dynamically
function getCategoryColors(labels) {
    const colorPalette = [
        chartColors.warning,
        chartColors.purple,
        chartColors.info,
        chartColors.success,
        chartColors.danger,
        chartColors.secondary
    ];
    
    return labels.map((label, index) => {
        // Try to match with predefined category colors first
        const categoryKey = Object.keys(categoryInfo).find(key => 
            categoryInfo[key].name.toLowerCase() === label.toLowerCase()
        );
        
        if (categoryKey) {
            return categoryInfo[categoryKey].color;
        }
        
        // Fallback to palette colors
        return colorPalette[index % colorPalette.length];
    });
}

// Category Breakdown Chart - Initialize with dynamic data
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryColors = getCategoryColors(window.initialCategoryData.labels || []);

categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: window.initialCategoryData.labels || [],
        datasets: [{
            data: window.initialCategoryData.data || [],
            backgroundColor: categoryColors,
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: { size: 12 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${context.label}: ₵${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Chart control interactions
document.querySelectorAll('.chart-control').forEach(control => {
    control.addEventListener('click', function() {
        document.querySelectorAll('.chart-control').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        
        const period = this.dataset.period;
        updateExpenseTrendsChart(period);
    });
});

// Dynamic chart update function
function updateExpenseTrendsChart(period) {
    // Show loading state
    const chartContainer = expenseTrendsChart.canvas.parentElement;
    chartContainer.style.opacity = '0.6';
    
    fetch(`../actions/chart_data.php?action=expense_trends&period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                expenseTrendsChart.data.labels = data.labels;
                expenseTrendsChart.data.datasets[0].data = data.data;
                expenseTrendsChart.update('smooth');
            } else {
                showSnackbar('Error loading chart data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showSnackbar('Error loading chart data', 'error');
        })
        .finally(() => {
            chartContainer.style.opacity = '1';
        });
}

// Category period selector
document.getElementById('categoryPeriod').addEventListener('change', function() {
    const period = this.value;
    updateCategoryChart(period);
});

function updateCategoryChart(period) {
    // Show loading state
    const chartContainer = categoryChart.canvas.parentElement;
    chartContainer.style.opacity = '0.6';
    
    fetch(`../actions/chart_data.php?action=category_breakdown&period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newColors = getCategoryColors(data.labels);
                categoryChart.data.labels = data.labels;
                categoryChart.data.datasets[0].data = data.data;
                categoryChart.data.datasets[0].backgroundColor = newColors;
                categoryChart.update('smooth');
            } else {
                showSnackbar('Error loading chart data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showSnackbar('Error loading chart data', 'error');
        })
        .finally(() => {
            chartContainer.style.opacity = '1';
        });
}

// Snackbar functionality
function showSnackbar(message, type = 'default') {
    const snackbar = document.getElementById('snackbar');
    snackbar.textContent = message;
    snackbar.className = 'show';
    
    if (type !== 'default') {
        snackbar.classList.add(type);
    }
    
    setTimeout(() => {
        snackbar.className = snackbar.className.replace('show', '');
        snackbar.classList.remove('success', 'error', 'warning');
    }, 3000);
}

// Modal functionality
function showAddExpenseModal() {
    document.getElementById('addExpenseModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Set today's date as default
    document.getElementById('expenseDate').valueAsDate = new Date();
}

function closeAddExpenseModal() {
    document.getElementById('addExpenseModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('addExpenseForm').reset();
    
    // Reset form state if editing
    const form = document.getElementById('addExpenseForm');
    if (form.dataset.editingId) {
        delete form.dataset.editingId;
        document.querySelector('#addExpenseModal .modal-header h3').textContent = 'Add New Expense';
        document.querySelector('#addExpenseForm button[type="submit"]').textContent = 'Add Expense';
    }
}

// Quick Add Modal
let currentQuickExpense = null;

function showQuickAddModal(category, amount) {
    currentQuickExpense = { category, amount };
    const modal = document.getElementById('quickAddModal');
    const categoryInfo = getCategoryInfo(category);
    
    document.getElementById('quickCategoryIcon').textContent = categoryInfo.icon;
    document.getElementById('quickCategoryName').textContent = categoryInfo.name;
    document.getElementById('quickAmount').value = amount || '';
    document.getElementById('quickDescription').value = `Monthly ${categoryInfo.name.toLowerCase()}`;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Focus on amount input if no preset amount
    if (!amount) {
        setTimeout(() => document.getElementById('quickAmount').focus(), 100);
    }
}

function closeQuickAddModal() {
    document.getElementById('quickAddModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentQuickExpense = null;
}

function getCategoryInfo(category) {
    const categoryMap = {
        'dstv': 'dstv',
        'wifi': 'wifi',
        'utilities': 'utilities',
        'dining': 'dining',
        'maintenance': 'maintenance',
        'other': 'other'
    };
    
    const mappedCategory = categoryMap[category.toLowerCase()] || 'other';
    return categoryInfo[mappedCategory];
}

// Quick expense functions
function quickAddExpense(category, amount) {
    if (amount > 0) {
        // Show modal even for preset amounts to allow confirmation
        showQuickAddModal(category, amount);
    } else {
        // Show modal for custom amounts
        showQuickAddModal(category, 0);
    }
}

function confirmQuickAdd() {
    const amount = parseFloat(document.getElementById('quickAmount').value);
    const description = document.getElementById('quickDescription').value;
    
    if (!amount || amount <= 0) {
        showSnackbar('Please enter a valid amount', 'error');
        return;
    }
    
    if (!description) {
        showSnackbar('Please enter a description', 'error');
        return;
    }
    
    // Submit the expense via AJAX
    submitExpense(currentQuickExpense.category, amount, description, new Date().toISOString().split('T')[0], 'momo', '');
    
    closeQuickAddModal();
}

// Form submissions
document.getElementById('addExpenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const category = document.getElementById('expenseCategory').value;
    const amount = parseFloat(document.getElementById('expenseAmount').value);
    const description = document.getElementById('expenseDescription').value;
    const date = document.getElementById('expenseDate').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    const notes = document.getElementById('expenseNotes').value;
    
    if (!category || !amount || amount <= 0 || !date || !paymentMethod) {
        showSnackbar('Please fill in all required fields correctly', 'error');
        return;
    }
    
    const editingId = this.dataset.editingId;
    
    if (editingId) {
        // Update existing expense
        updateExpense(editingId, category, amount, description, date);
    } else {
        // Add new expense
        submitExpense(category, amount, description, date, paymentMethod, notes);
    }
    
    closeAddExpenseModal();
});

// Submit expense to server
function submitExpense(category, amount, description, date, paymentMethod, notes) {
    const formData = new FormData();
    formData.append('action', 'add_expense');
    formData.append('family_id', familyId);
    formData.append('expense_type', category);
    formData.append('amount', amount);
    formData.append('description', description);
    formData.append('expense_date', date);
    formData.append('payment_method', paymentMethod);
    formData.append('notes', notes);
    
    fetch('../actions/expense_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const formattedAmount = Math.floor(amount) === amount ? 
                amount.toLocaleString('en-US') : 
                amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            showSnackbar(`₵${formattedAmount} expense added successfully!`, 'success');
            // Refresh data and animations instead of full page reload
            setTimeout(() => {
                loadExpenseData();
            }, 500);
        } else {
            showSnackbar(data.message || 'Error adding expense', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSnackbar('Error adding expense. Please try again.', 'error');
    });
}

// Update expense
function updateExpense(expenseId, category, amount, description, date) {
    const formData = new FormData();
    formData.append('action', 'update_expense');
    formData.append('expense_id', expenseId);
    formData.append('family_id', familyId);
    formData.append('expense_type', category);
    formData.append('amount', amount);
    formData.append('description', description);
    formData.append('expense_date', date);
    
    fetch('../actions/expense_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar('Expense updated successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showSnackbar(data.message || 'Error updating expense', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSnackbar('Error updating expense. Please try again.', 'error');
    });
}

// Expense management functions
function editExpense(expenseId) {
    // Fetch expense details
    fetch(`../actions/expense_handler.php?action=get_expense&expense_id=${expenseId}&family_id=${familyId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.expense) {
            const expense = data.expense;
            
            // Pre-fill the form
            document.getElementById('expenseCategory').value = expense.expense_type;
            document.getElementById('expenseAmount').value = expense.amount;
            document.getElementById('expenseDescription').value = expense.description;
            document.getElementById('expenseDate').value = expense.expense_date;
            document.getElementById('paymentMethod').value = expense.payment_method || 'momo';
            document.getElementById('expenseNotes').value = expense.notes || '';
            
            // Change modal title and button text
            document.querySelector('#addExpenseModal .modal-header h3').textContent = 'Edit Expense';
            document.querySelector('#addExpenseForm button[type="submit"]').textContent = 'Update Expense';
            
            // Store the expense ID for updating
            document.getElementById('addExpenseForm').dataset.editingId = expenseId;
            
            showAddExpenseModal();
        } else {
            showSnackbar('Error loading expense details', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSnackbar('Error loading expense details', 'error');
    });
}

function deleteExpense(expenseId) {
    if (!confirm('Are you sure you want to delete this expense?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_expense');
    formData.append('expense_id', expenseId);
    formData.append('family_id', familyId);
    
    fetch('../actions/expense_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar('Expense deleted successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showSnackbar(data.message || 'Error deleting expense', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSnackbar('Error deleting expense. Please try again.', 'error');
    });
}

// Export functionality
function exportExpensesData() {
    window.open(`../actions/expense_handler.php?action=export&family_id=${familyId}`, '_blank');
    showSnackbar('Exporting expenses data...', 'success');
}

// Filter and view functions
function showExpenseFilters() {
    showSnackbar('Filter functionality coming soon', 'warning');
}

function showAllExpenses() {
    showSnackbar('Detailed expenses view coming soon', 'warning');
}

// Sign out functionality
function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        showSnackbar('Signing out...', 'warning');
        setTimeout(() => {
            window.location.href = '../actions/signout';
        }, 1500);
    }
}

// Update quick expense suggestions dynamically
function updateQuickSuggestions() {
    fetch('../actions/chart_data.php?action=quick_suggestions')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const grid = document.getElementById('quickExpenseGrid');
                grid.innerHTML = '';
                
                data.suggestions.forEach(suggestion => {
                    const card = document.createElement('div');
                    card.className = 'quick-expense-card';
                    card.onclick = () => quickAddExpense(suggestion.type, suggestion.amount);
                    
                    card.innerHTML = `
                        <div class="quick-expense-icon">${suggestion.icon}</div>
                        <div class="quick-expense-name">${suggestion.name}</div>
                        <div class="quick-expense-amount">
                            ${suggestion.amount > 0 ? '₵' + suggestion.amount : 'Custom'}
                        </div>
                    `;
                    
                    grid.appendChild(card);
                });
            }
        })
        .catch(error => {
            console.error('Error updating suggestions:', error);
        });
}

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    const addModal = document.getElementById('addExpenseModal');
    const quickModal = document.getElementById('quickAddModal');
    
    if (e.target === addModal) {
        closeAddExpenseModal();
    }
    
    if (e.target === quickModal) {
        closeQuickAddModal();
    }
});

// Form validation helpers
function validateAmount(input) {
    const value = parseFloat(input.value);
    if (isNaN(value) || value <= 0) {
        input.style.borderColor = '#ef4444';
        return false;
    } else {
        input.style.borderColor = '#10b981';
        return true;
    }
}

// Add real-time validation to amount inputs
document.getElementById('expenseAmount').addEventListener('input', function() {
    validateAmount(this);
});

document.getElementById('quickAmount').addEventListener('input', function() {
    validateAmount(this);
});

// Format currency inputs
function formatCurrency(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    if (value.includes('.')) {
        const parts = value.split('.');
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }
    input.value = value;
}

document.getElementById('expenseAmount').addEventListener('input', function() {
    formatCurrency(this);
});

document.getElementById('quickAmount').addEventListener('input', function() {
    formatCurrency(this);
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        closeAddExpenseModal();
        closeQuickAddModal();
    }
    
    // Ctrl/Cmd + N to add new expense
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        showAddExpenseModal();
    }
    
    // Ctrl/Cmd + E to export data
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportExpensesData();
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        sidebar.classList.remove('open');
    }
});

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Show welcome message
    setTimeout(() => {
        if (expensesData.length === 0) {
            showSnackbar('No expenses found. Add your first expense!', 'warning');
        } else {
            showSnackbar('Expenses loaded successfully!', 'success');
        }
    }, 1000);
    
    // Add animation to quick expense cards
    setTimeout(() => {
        document.querySelectorAll('.quick-expense-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 500);
    
    // Update quick suggestions every 5 minutes to keep them fresh
    setInterval(updateQuickSuggestions, 300000);
});