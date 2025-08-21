// Global variables
let dashboardData = {};
let contributionChart = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    checkForMessage();
    initializeSidebar();
});

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('../api/dashboard_data.php');
        
        // First check if the response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get the response text first so we can inspect it
        const responseText = await response.text();
        
        try {
            // Try to parse it as JSON
            const data = JSON.parse(responseText);
            
            if (data.success) {
                dashboardData = data.data;
                updateDashboardUI();
            } else {
                showSnackbar('Error loading dashboard data: ' + (data.message || 'Unknown error'), 'error');
                console.error('API Error:', data);
                if (data.error) {
                    console.error('Detailed error:', data.error);
                }
            }
        } catch (jsonError) {
            console.error('Failed to parse JSON:', responseText);
            showSnackbar('Invalid response from server', 'error');
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showSnackbar('Error loading dashboard data', 'error');
    }
}

// Update UI with loaded data
function updateDashboardUI() {
    updateStatistics();
    updateQuickStats();
    updateMembers();
    updateRecentActivity();
    updateCycleInformation();
    updateDebtInfo();
    initializeChart();
    populateMemberOptions();
}

// Update statistics cards
function updateStatistics() {
    const stats = dashboardData.stats;

    // Total Pool
    const totalPoolEl = document.getElementById('totalPool');
    if (totalPoolEl && stats.totalPool) {
        const current = parseFloat(totalPoolEl.textContent.replace(/[,₵]/g, '')) || 0;
        animateValue('totalPool', current, stats.totalPool.amount);
        updateChangeIndicator('poolChange', stats.totalPool.change || 0);
    }

    // Monthly Contributions
    const monthlyContribEl = document.getElementById('monthlyContrib');
    if (monthlyContribEl && stats.monthlyContributions) {
        monthlyContribEl.textContent = formatCurrency(stats.monthlyContributions.amount);
        updateChangeIndicator('contribChange', stats.monthlyContributions.change || 0);
    }

    // Monthly Expenses
    const monthlyExpensesEl = document.getElementById('monthlyExpenses');
    if (monthlyExpensesEl && stats.monthlyExpenses) {
        monthlyExpensesEl.textContent = formatCurrency(stats.monthlyExpenses.amount);
        updateChangeIndicator('expenseChange', stats.monthlyExpenses.change || 0);
    }

    // Savings Rate
    const savingsRateEl = document.getElementById('savingsRate');
    const netSavingsEl = document.getElementById('netSavings');
    if (savingsRateEl && netSavingsEl) {
        savingsRateEl.textContent = stats.savingsRate || 0;
        netSavingsEl.textContent = formatCurrency(stats.netSavings || 0);
    }
}

// Update quick stats
function updateQuickStats() {
    const stats = dashboardData.stats;
    
    const elements = {
        'activeMembers': stats.activeMembers || 0,
        'contributionCount': stats.contributionCount || 0,
        'expenseCount': stats.expenseCount || 0,
        'netSavingsDisplay': formatCurrency(stats.netSavings || 0)
    };

    Object.keys(elements).forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = elements[id];
        }
    });
}

// Update cycle information
function updateCycleInformation() {
    const cycle = dashboardData.currentCycle;
    
    if (!cycle) {
        console.warn('No current cycle data found');
        return;
    }

    // Update cycle title
    const cycleTitle = document.getElementById('cycleTitle');
    if (cycleTitle) {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const monthName = monthNames[cycle.cycle_month_num - 1];
        
        let statusText = '';
        if (cycle.waiting_for_new_month) {
            statusText = ' (Waiting for New Month)';
        } else if (cycle.is_closed) {
            statusText = ' (CLOSED)';
        } else {
            statusText = ' Contribution Cycle';
        }
        
        cycleTitle.textContent = `${monthName} ${cycle.cycle_year}${statusText}`;
    }

    // Update cycle progress text
    const cycleProgress = document.getElementById('cycleProgress');
    if (cycleProgress) {
        if (cycle.waiting_for_new_month) {
            const currentMonth = new Date().toLocaleDateString('default', { month: 'long' });
            cycleProgress.textContent = `Waiting for ${currentMonth} cycle to begin`;
        } else if (cycle.is_closed) {
            const closedDate = new Date(cycle.closed_at);
            const closedDateStr = closedDate.toLocaleDateString();
            cycleProgress.textContent = `Closed on ${closedDateStr}`;
        } else {
            const daysRemaining = cycle.days_remaining || 0;
            if (daysRemaining > 0) {
                cycleProgress.textContent = `${daysRemaining} days remaining`;
            } else if (daysRemaining === 0) {
                cycleProgress.textContent = 'Last day of cycle';
            } else {
                cycleProgress.textContent = 'Cycle overdue';
            }
        }
    }

    // Update cycle members info
    const cycleMembers = document.getElementById('cycleMembers');
    if (cycleMembers) {
        const completed = cycle.members_completed || 0;
        const pending = cycle.members_pending || 0;
        const total = completed + pending;
        
        if (cycle.waiting_for_new_month) {
            cycleMembers.textContent = `Ready for new month: ${total} members`;
        } else if (cycle.is_closed) {
            cycleMembers.textContent = `Final: ${completed}/${total} members completed`;
        } else {
            cycleMembers.textContent = `${completed}/${total} members completed`;
        }
    }

    // Update progress circle
    const progressCircle = document.getElementById('progressCircle');
    const progressText = document.getElementById('progressText');
    if (progressCircle && progressText) {
        const progress = Math.max(0, Math.min(100, cycle.progress_percentage || 0));
        const circumference = 2 * Math.PI * 35; // radius is 35
        const offset = circumference - (progress / 100) * circumference;
        
        progressCircle.style.strokeDashoffset = offset;
        
        if (cycle.waiting_for_new_month) {
            progressText.textContent = 'WAITING';
            progressCircle.style.stroke = '#f59e0b'; // Amber color for waiting
        } else if (cycle.is_closed) {
            progressText.textContent = 'CLOSED';
            progressCircle.style.stroke = '#ef4444'; // Red color for closed
        } else {
            progressText.textContent = `${Math.round(progress)}%`;
            progressCircle.style.stroke = 'white'; // Default color
        }
    }
    
    // Update button visibility based on cycle state
    const closeCycleBtn = document.getElementById('closeCycleBtn');
    const startCycleBtn = document.getElementById('startCycleBtn');
    
    if (closeCycleBtn && startCycleBtn) {
        // Check if current cycle is for current month
        const currentMonth = new Date().getMonth() + 1; // JavaScript months are 0-based
        const currentYear = new Date().getFullYear();
        const cycleMonth = cycle.cycle_month_num;
        const cycleYear = cycle.cycle_year;
        
        const isCurrentMonthCycle = (cycleMonth === currentMonth && cycleYear === currentYear);
        
        if (cycle.waiting_for_new_month || (!isCurrentMonthCycle && cycle.is_closed)) {
            // Show start cycle button if waiting for new month OR if the current cycle is not for this month
            closeCycleBtn.style.display = 'none';
            startCycleBtn.style.display = 'inline-block';
        } else if (cycle.is_closed) {
            // For closed cycles of current month, show both buttons (option to restart)
            closeCycleBtn.style.display = 'none';
            startCycleBtn.style.display = 'inline-block';
        } else {
            // Show close cycle button for active cycles, hide start cycle button
            closeCycleBtn.style.display = 'inline-block';
            startCycleBtn.style.display = 'none';
        }
    }
}

// Update members overview
function updateMembers() {
    const membersContainer = document.getElementById('membersOverview');
    if (!membersContainer) return;
    
    membersContainer.innerHTML = '';

    if (!dashboardData.members || dashboardData.members.length === 0) {
        membersContainer.innerHTML = '<div class="no-members">No family members found</div>';
        return;
    }

    dashboardData.members.forEach(member => {
        const memberCard = createMemberCard(member);
        membersContainer.appendChild(memberCard);
    });
}

// Create member card element
function createMemberCard(member) {
    const card = document.createElement('div');
    card.className = 'member-card';

    const avatar = member.first_name ? member.first_name.charAt(0).toUpperCase() : 'M';
    const progressWidth = Math.min(100, member.progress_percentage || 0);
    const hasDebt = member.has_debt || false;
    const totalDebt = member.total_debt || 0;
    
    // Check if current cycle is closed or waiting for new month
    const cycle = dashboardData.currentCycle;
    const isCycleClosed = cycle && (cycle.is_closed || cycle.waiting_for_new_month);
    
    const contributeButton = isCycleClosed 
        ? `<button class="btn-contribute" disabled style="opacity: 0.5; cursor: not-allowed;">
             🔒 ${cycle.waiting_for_new_month ? 'Waiting for New Month' : 'Cycle Closed'}
           </button>`
        : `<button class="btn-contribute ${hasDebt ? 'has-debt' : ''}" onclick="showPaymentModal(${member.family_member_id || member.id}, '${member.name}')">
             💰 ${hasDebt ? 'Pay/Contribute' : 'Contribute'}
           </button>`;

    // Add debt indicator if member has debt
    const debtIndicator = hasDebt ? `
        <div class="debt-indicator">
            <span class="debt-icon">⚠️</span>
            <span class="debt-text">Owes ₵${totalDebt.toLocaleString()}</span>
        </div>
    ` : '';

    card.innerHTML = `
        <div class="member-header">
            <div class="member-avatar ${hasDebt ? 'has-debt' : ''}">${avatar}</div>
            <div class="member-info">
                <h3>${member.name || 'Unknown Member'}</h3>
                <div class="member-role">${member.role || 'Member'}</div>
                ${debtIndicator}
            </div>
        </div>
        <div class="member-stats">
            <div class="member-stat">
                <div class="member-stat-value">${formatCurrency(member.total_contributed || 0)}</div>
                <div class="member-stat-label">Total Contributed</div>
            </div>
            <div class="member-stat">
                <div class="member-stat-value">${member.contribution_count || 0}</div>
                <div class="member-stat-label">Contributions</div>
            </div>
        </div>
        <div class="member-progress">
            <div class="member-progress-label">
                <span>Monthly Goal</span>
                <span>₵${formatCurrency(member.monthly_contribution || 0)} / ₵${formatCurrency(member.monthly_goal || 0)}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill ${hasDebt ? 'has-debt' : ''}" style="width: ${progressWidth}%"></div>
            </div>
        </div>
        <div class="member-actions">
            ${contributeButton}
        </div>
    `;

    return card;
}

// Update recent activity
function updateRecentActivity() {
    const activityList = document.getElementById('activityList');
    if (!activityList) return;
    
    activityList.innerHTML = '';

    if (!dashboardData.activities || dashboardData.activities.length === 0) {
        activityList.innerHTML = '<div class="no-activity">No recent activity</div>';
        return;
    }

    dashboardData.activities.forEach(activity => {
        const activityItem = createActivityItem(activity);
        activityList.appendChild(activityItem);
    });
}

// Create activity item element
function createActivityItem(activity) {
    const item = document.createElement('div');
    item.className = 'activity-item';

    const isPositive = (activity.amount || 0) > 0;
    const iconClass = activity.type === 'contribution' ? 'contribution' : 'expense';
    const amountClass = isPositive ? 'positive' : 'negative';
    const amountPrefix = isPositive ? '+' : '';
    const icon = activity.type === 'contribution' ? '💰' : '💸';

    item.innerHTML = `
        <div class="activity-icon ${iconClass}">${icon}</div>
        <div class="activity-content">
            <div class="activity-title">${activity.title || 'Unknown Activity'}</div>
            <div class="activity-description">${activity.description || 'No description'}</div>
        </div>
        <div class="activity-amount ${amountClass}">${amountPrefix}${formatCurrency(Math.abs(activity.amount || 0))}</div>
    `;

    return item;
}

// Initialize chart
function initializeChart() {
    const chartCanvas = document.getElementById('contributionChart');
    if (!chartCanvas) return;

    const ctx = chartCanvas.getContext('2d');
    const chartData = dashboardData.chartData || { labels: [], contributions: [], expenses: [] };

    // Destroy existing chart if it exists
    if (contributionChart) {
        contributionChart.destroy();
    }

    contributionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels || [],
            datasets: [{
                label: 'Total Contributions',
                data: chartData.contributions || [],
                borderColor: '#1e293b',
                backgroundColor: '#1e293b20',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1e293b',
                pointRadius: 5,
                pointHoverRadius: 8
            }, {
                label: 'Expenses',
                data: chartData.expenses || [],
                borderColor: '#ef4444',
                backgroundColor: '#ef444420',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ef4444',
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
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₵' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        callback: function(value) {
                            return  formatCurrency(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// Update chart with new period
async function updateChart(period) {
    try {
        // Update active button
        document.querySelectorAll('.chart-control').forEach(btn => {
            btn.classList.remove('active');
        });
        const activeBtn = document.querySelector(`[data-period="${period}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        // For now, we'll use the existing chart data
        // In a full implementation, you'd fetch new data based on the period
        showSnackbar('Chart updated for ' + period, 'success');
        
    } catch (error) {
        console.error('Error updating chart:', error);
        showSnackbar('Error updating chart', 'error');
    }
}

// Populate member options in forms
function populateMemberOptions() {
    const select = document.getElementById('contributionMember');
    if (!select) {
        console.warn('contributionMember select element not found');
        return;
    }
    
    select.innerHTML = '<option value="">Select Member</option>';

    if (dashboardData.members && dashboardData.members.length > 0) {
        dashboardData.members.forEach(member => {
            const option = document.createElement('option');
            // Use the appropriate ID based on member type
            option.value = member.member_id || member.id;
            option.textContent = member.name || member.full_name || 'Unknown Member';
            
            // Add data attributes for additional info
            option.setAttribute('data-member-type', member.member_type || 'user');
            option.setAttribute('data-member-only-id', member.member_only_id || '');
            
            select.appendChild(option);
        });
    } else {
        console.warn('No members found in dashboardData');
    }
}
// Enhanced form validation
function validateContributionForm() {
    const member = document.getElementById('contributionMember').value;
    const amount = document.getElementById('contributionAmount').value;
    
    let isValid = true;
    let errors = [];

    if (!member) {
        errors.push('Please select a member');
        isValid = false;
    }

    if (!amount || parseFloat(amount) <= 0) {
        errors.push('Please enter a valid amount greater than 0');
        isValid = false;
    }

    if (parseFloat(amount) > 999999.99) {
        errors.push('Amount is too large');
        isValid = false;
    }

    return {
        isValid: isValid,
        errors: errors
    };
}


async function submitQuickContribution(event) {
    event.preventDefault();

    const memberOnlyId = document.getElementById('contributeMemberOnlyId').value;
    const amount = document.getElementById('contributeAmount').value;
    const notes = document.getElementById('contributeNote').value;

    if (!amount || parseFloat(amount) <= 0) {
        showSnackbar('Please enter a valid amount', 'error');
        return;
    }

    if (!memberOnlyId) {
        showSnackbar('Please select a valid member', 'error');
        return;
    }

    try {
        const submitBtn = event.target.querySelector('button[type="submit"]');
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'add_contribution');
        formData.append('member_id', memberOnlyId); // Use member_id just like the working version
        formData.append('amount', amount);
        formData.append('notes', notes);
        formData.append('payment_method', 'momo');



        const response = await fetch('../api/contribution_handler.php', {
            method: 'POST',
            body: formData
        });

        const responseText = await response.text();

        const data = JSON.parse(responseText);

        if (data.success) {
            showSnackbar(data.message, 'success');
            closeModal('quickContributeModal');
            document.getElementById('quickContributeForm').reset();
            await loadDashboardData();
        } else {
            showSnackbar(data.message || 'Error adding contribution', 'error');
            if (data.errors && Array.isArray(data.errors)) {
                console.error('Validation errors:', data.errors);
                data.errors.forEach(error => {
                    showSnackbar(error, 'error');
                });
            }
        }

        submitBtn.textContent = 'Record Contribution';
        submitBtn.disabled = false;

    } catch (error) {
        console.error('Error in quick contribution:', error);
        showSnackbar('Error adding contribution: ' + error.message, 'error');
        
        const submitBtn = event.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Record Contribution';
            submitBtn.disabled = false;
        }
    }
}

// Helper function to show member contribution modal
function showQuickContributeModal(memberId, memberOnlyId, memberType, memberName) {
    // Check if current cycle is closed or waiting for new month
    const cycle = dashboardData.currentCycle;
    if (cycle && (cycle.is_closed || cycle.waiting_for_new_month)) {
        const message = cycle.waiting_for_new_month 
            ? 'Cannot add contributions - waiting for new month to begin'
            : 'Cannot add contributions - the current cycle is closed';
        showSnackbar(message, 'warning');
        return;
    }
    
    // Simplified - just use the memberOnlyId as the member_id (same as working version)
    document.getElementById('contributeMemberOnlyId').value = memberOnlyId || '';
    document.getElementById('contributeMemberName').value = memberName || '';
    
    // Clear amount and note
    document.getElementById('contributeAmount').value = '';
    document.getElementById('contributeNote').value = '';
    
    document.getElementById('quickContributeModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Add event listener for quick contribute form
document.addEventListener('DOMContentLoaded', function() {
    const quickContributeForm = document.getElementById('quickContributeForm');
    if (quickContributeForm) {
        quickContributeForm.addEventListener('submit', submitQuickContribution);
    }
});

// Enhanced error handling for AJAX requests
function handleAjaxError(error, operation) {
    console.error(`Error in ${operation}:`, error);
    
    let message = 'An error occurred';
    
    if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
        message = 'Network error. Please check your connection.';
    } else if (error.name === 'SyntaxError' && error.message.includes('JSON')) {
        message = 'Server returned invalid response.';
    } else if (error.message) {
        message = error.message;
    }
    
    showSnackbar(`${operation} failed: ${message}`, 'error');
}
// Submit contribution form
// Fixed submitContribution function and related code
async function submitContribution(event) {
    event.preventDefault();

    // Get form data
    const memberId = document.getElementById('contributionMember').value;
    const amount = document.getElementById('contributionAmount').value;
    const notes = document.getElementById('contributionNote').value;

    // Basic validation
    if (!memberId) {
        showSnackbar('Please select a member', 'error');
        return;
    }

    if (!amount || parseFloat(amount) <= 0) {
        showSnackbar('Please enter a valid amount', 'error');
        return;
    }

    try {
        // Show loading state
        const submitBtn = event.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;

        // Create FormData
        const formData = new FormData();
        formData.append('action', 'add_contribution');
        formData.append('member_id', memberId);
        formData.append('amount', amount);
        formData.append('notes', notes);
        formData.append('payment_method', 'momo'); // Default payment method



        const response = await fetch('../api/contribution_handler.php', {
            method: 'POST',
            body: formData
        });



        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get response text first
        const responseText = await response.text();

        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', responseText);
            throw new Error('Invalid JSON response from server');
        }


        if (data.success) {
            showSnackbar(data.message, 'success');
            closeQuickAddModal();
            
            // Reset form
            document.getElementById('contributionForm').reset();
            
            // Refresh dashboard data
            await loadDashboardData();
        } else {
            showSnackbar(data.message || 'Error adding contribution', 'error');
            if (data.errors && Array.isArray(data.errors)) {
                console.error('Validation errors:', data.errors);
                data.errors.forEach(error => {
                    showSnackbar(error, 'error');
                });
            }
        }

        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;

    } catch (error) {
        console.error('Error submitting contribution:', error);
        showSnackbar('Error adding contribution: ' + error.message, 'error');
        
        // Reset button state
        const submitBtn = event.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Add Contribution';
            submitBtn.disabled = false;
        }
    }
}


// Submit expense form
async function submitExpense(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('expense_type', document.getElementById('expenseType').value);
    formData.append('amount', document.getElementById('expenseAmount').value);
    formData.append('description', document.getElementById('expenseDescription').value);
    formData.append('action', 'add_expense');

    try {
        const response = await fetch('../api/expense_handler.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showSnackbar(data.message, 'success');
            closeQuickAddModal();
            loadDashboardData(); // Refresh data
        } else {
            showSnackbar(data.message || 'Error adding expense', 'error');
        }
    } catch (error) {
        console.error('Error submitting expense:', error);
        showSnackbar('Error adding expense', 'error');
    }
}

// Utility functions
function formatCurrency(amount) {
    // Handle null, undefined, NaN, or invalid values
    if (amount === null || amount === undefined || isNaN(amount) || amount === '') {
        return '0';
    }
    
    // Convert to number and ensure it's valid
    let numAmount = parseFloat(amount);
    if (isNaN(numAmount)) {
        return '0';
    }
    
    // Round to 2 decimal places to avoid floating point precision issues
    numAmount = Math.round(numAmount * 100) / 100;
    
    if (Math.floor(numAmount) === numAmount) {
        return numAmount.toLocaleString('en-US');
    } else {
        return numAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function updateChangeIndicator(elementId, change) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const isPositive = (change || 0) >= 0;
    const icon = isPositive ? '↗' : '↘';
    const className = isPositive ? 'positive' : 'negative';

    element.className = `stat-change ${className}`;
    element.innerHTML = `<span class="change-icon">${icon}</span> ${isPositive ? '+' : ''}${change || 0}% from last month`;
}

// Modal functions
function showQuickAddModal() {
    // Check if current cycle is closed or waiting for new month
    const cycle = dashboardData.currentCycle;
    if (cycle && (cycle.is_closed || cycle.waiting_for_new_month)) {
        const message = cycle.waiting_for_new_month 
            ? 'Cannot add contributions - waiting for new month to begin'
            : 'Cannot add contributions - the current cycle is closed';
        showSnackbar(message, 'warning');
        return;
    }
    
    const modal = document.getElementById('quickAddModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function closeQuickAddModal() {
    const modal = document.getElementById('quickAddModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset forms
        const contribForm = document.getElementById('contributionForm');
        const expenseForm = document.getElementById('expenseForm');
        if (contribForm) contribForm.reset();
        if (expenseForm) expenseForm.reset();
    }
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    const tabContent = document.getElementById(tabName + 'Tab');
    if (tabContent) {
        tabContent.classList.add('active');
    }
}

// Sidebar functions
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

// Snackbar functions
function showSnackbar(message, type = 'default') {
    const snackbar = document.getElementById('snackbar');
    if (snackbar) {
        snackbar.textContent = message;
        snackbar.className = `show ${type}`;

        setTimeout(() => {
            snackbar.className = snackbar.className.replace('show', '');
        }, 3000);
    }
}

function checkForMessage() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('status') && params.has('message')) {
        const message = params.get('message');
        const status = params.get('status');
        showSnackbar(message, status);
    }
}

// Export data
function exportData() {
    if (!dashboardData || Object.keys(dashboardData).length === 0) {
        showSnackbar('No data available to export', 'error');
        return;
    }

    const exportData = {
        stats: dashboardData.stats || {},
        members: dashboardData.members || [],
        activities: dashboardData.activities || [],
        exportDate: new Date().toISOString()
    };

    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], {
        type: 'application/json'
    });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `family-budget-data-${new Date().toISOString().split('T')[0]}.json`;
    link.click();

    showSnackbar('Data exported successfully', 'success');
}

// Cycle management functions
function showCycleSummary() {
    // This would typically open a modal or navigate to cycle details
    showSnackbar('Cycle summary feature coming soon', 'info');
}

function showCloseCycleModal() {
    const cycle = dashboardData.currentCycle;
    
    if (!cycle) {
        showSnackbar('No active cycle found', 'error');
        return;
    }
    
    if (cycle.is_closed || cycle.waiting_for_new_month) {
        showSnackbar('This cycle is already closed', 'warning');
        return;
    }
    
    const cycleTitle = `${new Date(0, cycle.cycle_month_num - 1).toLocaleString('default', { month: 'long' })} ${cycle.cycle_year}`;
    
    if (confirm(`Are you sure you want to close the ${cycleTitle} contribution cycle?\n\nThis will:\n- Record final contributions and debts\n- Reset monthly progress for all members\n- Archive this month's data\n- Wait until next month to allow new contributions\n\nThis action cannot be undone.`)) {
        closeCycle(cycle.id);
    }
}

// Function to start a new cycle (for new month)
function showStartNewCycleModal() {
    const cycle = dashboardData.currentCycle;
    
    const currentMonth = new Date().toLocaleDateString('default', { month: 'long' });
    const currentYear = new Date().getFullYear();
    
    // Allow starting new cycle in multiple scenarios:
    // 1. When explicitly waiting for new month
    // 2. When current cycle is closed
    // 3. When there's no current cycle for this month
    const canStartNewCycle = !cycle || 
                           cycle.waiting_for_new_month || 
                           cycle.is_closed ||
                           (cycle.cycle_month_num !== new Date().getMonth() + 1);
    
    if (!canStartNewCycle) {
        showSnackbar('Cannot start new cycle while current cycle is active', 'warning');
        return;
    }
    
    const confirmMessage = cycle && cycle.is_closed 
        ? `Restart contribution cycle for ${currentMonth} ${currentYear}?\n\nThis will:\n- Create a fresh cycle for this month\n- Reset all member monthly contributions to ₵0\n- Set new monthly targets\n- Allow members to start contributing again\n\nNote: This will preserve your family pool and lifetime totals.\n\nProceed?`
        : `Start a new contribution cycle for ${currentMonth} ${currentYear}?\n\nThis will:\n- Create a fresh cycle for the new month\n- Reset all member contributions to ₵0\n- Set new monthly targets\n- Allow members to start contributing again\n\nProceed?`;
    
    if (confirm(confirmMessage)) {
        startNewCycle();
    }
}

// Function to actually start a new cycle
async function startNewCycle() {
    try {
        showSnackbar('Starting new cycle...', 'info');
        
        const response = await fetch('../ajax/start_new_cycle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'start_new_cycle'
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showSnackbar(data.message || 'New cycle started successfully', 'success');
            // Refresh dashboard to show updated cycle status
            await loadDashboardData();
        } else {
            showSnackbar(data.error || 'Error starting new cycle', 'error');
        }
    } catch (error) {
        console.error('Error starting new cycle:', error);
        showSnackbar('Network error while starting new cycle', 'error');
    }
}

// Function to actually close the cycle
async function closeCycle(cycleId) {
    try {
        showSnackbar('Closing cycle...', 'info');
        
        const response = await fetch('../ajax/close_cycle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cycle_id: cycleId
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showSnackbar(data.message || 'Cycle closed successfully', 'success');
            // Refresh dashboard to show updated cycle status
            await loadDashboardData();
        } else {
            showSnackbar(data.error || 'Error closing cycle', 'error');
        }
    } catch (error) {
        console.error('Error closing cycle:', error);
        showSnackbar('Network error while closing cycle', 'error');
    }
}

// Sign out
function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        showSnackbar('Signing out...', 'warning');
        setTimeout(() => {
            window.location.href = '../actions/signout';
        }, 1500);
    }
}

// =======================
// DEBT MANAGEMENT FUNCTIONS
// =======================

// Show debt information in dashboard
function updateDebtInfo() {
    const debtSummary = dashboardData.debtSummary;
    const debtContainer = document.getElementById('debtSummary');
    
    if (!debtContainer || !debtSummary) return;
    
    const totalDebt = parseFloat(debtSummary.total_family_debt || 0);
    const membersWithDebt = parseInt(debtSummary.members_with_debt || 0);
    
    if (totalDebt > 0) {
        debtContainer.innerHTML = `
            <div class="debt-alert">
                <div class="debt-icon">⚠️</div>
                <div class="debt-content">
                    <h4>Outstanding Debts</h4>
                    <p><strong>₵${totalDebt.toLocaleString()}</strong> owed by ${membersWithDebt} member(s)</p>
                    <button class="btn btn-sm btn-warning" onclick="showDebtModal()">
                        View Details
                    </button>
                </div>
            </div>
        `;
        debtContainer.style.display = 'block';
    } else {
        debtContainer.style.display = 'none';
    }
}

// Show detailed debt modal
async function showDebtModal() {
    try {
        const response = await fetch('../api/debt_manager.php?action=get_debts');
        const data = await response.json();
        
        if (data.success) {
            showDebtDetailsModal(data.debts, data.summary);
        } else {
            showSnackbar('Error loading debt information', 'error');
        }
    } catch (error) {
        console.error('Error loading debts:', error);
        showSnackbar('Network error loading debt information', 'error');
    }
}

// Display debt details modal
function showDebtDetailsModal(debts, summary) {
    const modal = document.createElement('div');
    modal.className = 'modal debt-modal';
    modal.id = 'debtModal';
    
    const debtsHtml = debts.map(debt => `
        <div class="debt-item">
            <div class="debt-member">
                <strong>${debt.display_name}</strong>
                <span class="debt-cycle">${debt.cycle_month}</span>
            </div>
            <div class="debt-amount">₵${parseFloat(debt.deficit_amount).toLocaleString()}</div>
            <div class="debt-actions">
                <button class="btn btn-sm btn-primary" onclick="showPaymentModal(${debt.member_id}, '${debt.display_name}')">
                    Make Payment
                </button>
                <button class="btn btn-sm btn-outline" onclick="clearDebt(${debt.id})">
                    Clear
                </button>
            </div>
        </div>
    `).join('');
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Outstanding Debts</h3>
                <button class="close" onclick="closeModal('debtModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="debt-summary">
                    <div class="summary-stat">
                        <span class="stat-label">Total Debt:</span>
                        <span class="stat-value debt">₵${parseFloat(summary.total_family_debt).toLocaleString()}</span>
                    </div>
                    <div class="summary-stat">
                        <span class="stat-label">Members with Debt:</span>
                        <span class="stat-value">${summary.members_with_debt}</span>
                    </div>
                </div>
                <div class="debt-list">
                    ${debts.length > 0 ? debtsHtml : '<p class="no-debt">No outstanding debts!</p>'}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    showModal('debtModal');
}

// Show payment modal with debt options
function showPaymentModal(memberId, memberName) {
    // First get member's debt info
    fetch(`../api/debt_manager.php?action=get_member_debt&member_id=${memberId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPaymentOptionsModal(memberId, memberName, data);
            } else {
                showSnackbar('Error loading member debt information', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showSnackbar('Network error', 'error');
        });
}

// Display payment options modal
function showPaymentOptionsModal(memberId, memberName, debtInfo) {
    const totalDebt = parseFloat(debtInfo.total_debt);
    const debtCount = parseInt(debtInfo.debt_count);
    
    const modal = document.createElement('div');
    modal.className = 'modal payment-modal';
    modal.id = 'paymentModal';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Payment for ${memberName}</h3>
                <button class="close" onclick="closeModal('paymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                ${totalDebt > 0 ? `
                    <div class="debt-info">
                        <div class="debt-alert">
                            <span class="debt-icon">⚠️</span>
                            <span>Outstanding debt: <strong>₵${totalDebt.toLocaleString()}</strong> (${debtCount} cycle(s))</span>
                        </div>
                    </div>
                ` : ''}
                
                <form id="paymentForm" onsubmit="processPayment(event, ${memberId})">
                    <div class="form-group">
                        <label for="paymentAmount">Amount (₵)</label>
                        <input type="number" id="paymentAmount" name="amount" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Type:</label>
                        <div class="payment-options">
                            <label class="radio-option">
                                <input type="radio" name="paymentType" value="contribution" checked>
                                <span>Regular Contribution Only</span>
                                <small>Full amount goes to monthly contribution</small>
                            </label>
                            
                            ${totalDebt > 0 ? `
                                <label class="radio-option">
                                    <input type="radio" name="paymentType" value="debt_only">
                                    <span>Pay Debt Only</span>
                                    <small>Apply full amount to outstanding debt</small>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="paymentType" value="auto_deduct">
                                    <span>Auto-Split (Recommended)</span>
                                    <small>Automatically split between debt and contribution</small>
                                </label>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="payment-preview" id="paymentPreview" style="display: none;">
                        <!-- Payment breakdown will be shown here -->
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Process Payment</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    showModal('paymentModal');
    
    // Add event listeners for payment preview
    const amountInput = modal.querySelector('#paymentAmount');
    const paymentTypeInputs = modal.querySelectorAll('input[name="paymentType"]');
    
    function updatePaymentPreview() {
        const amount = parseFloat(amountInput.value) || 0;
        const paymentType = modal.querySelector('input[name="paymentType"]:checked').value;
        const preview = modal.querySelector('#paymentPreview');
        
        if (amount > 0 && totalDebt > 0) {
            let debtPayment = 0;
            let contribution = 0;
            
            if (paymentType === 'debt_only') {
                debtPayment = Math.min(amount, totalDebt);
                contribution = 0;
            } else if (paymentType === 'auto_deduct') {
                debtPayment = Math.min(amount * 0.5, totalDebt);
                contribution = amount - debtPayment;
            } else {
                debtPayment = 0;
                contribution = amount;
            }
            
            preview.innerHTML = `
                <div class="payment-breakdown">
                    <h4>Payment Breakdown:</h4>
                    <div class="breakdown-item">
                        <span>Debt Payment:</span>
                        <span class="amount ${debtPayment > 0 ? 'debt' : ''}">₵${formatCurrency(debtPayment)}</span>
                    </div>
                    <div class="breakdown-item">
                        <span>Monthly Contribution:</span>
                        <span class="amount ${contribution > 0 ? 'positive' : ''}">₵${formatCurrency(contribution)}</span>
                    </div>
                    <div class="breakdown-item total">
                        <span>Total:</span>
                        <span class="amount">₵${formatCurrency(amount)}</span>
                    </div>
                    ${debtPayment > 0 ? `
                        <div class="remaining-debt">
                            Remaining debt after payment: ₵${formatCurrency(totalDebt - debtPayment)}
                        </div>
                    ` : ''}
                </div>
            `;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
    
    amountInput.addEventListener('input', updatePaymentPreview);
    paymentTypeInputs.forEach(input => {
        input.addEventListener('change', updatePaymentPreview);
    });
}

// Process payment with debt handling
async function processPayment(event, memberId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const amount = parseFloat(formData.get('amount'));
    const paymentType = formData.get('paymentType');
    
    if (amount <= 0) {
        showSnackbar('Please enter a valid amount', 'error');
        return;
    }
    
    try {
        showSnackbar('Processing payment...', 'info');
        
        const response = await fetch('../api/debt_manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'process_payment',
                member_id: memberId,
                amount: amount,
                payment_type: paymentType
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            let message = `Payment processed successfully!`;
            if (data.debt_paid > 0) {
                message += ` ₵${data.debt_paid} applied to debt.`;
            }
            if (data.contribution_made > 0) {
                message += ` ₵${data.contribution_made} recorded as contribution.`;
            }
            
            showSnackbar(message, 'success');
            closeModal('paymentModal');
            closeModal('debtModal');
            
            // Refresh dashboard
            await loadDashboardData();
        } else {
            showSnackbar(data.error || 'Error processing payment', 'error');
        }
    } catch (error) {
        console.error('Error processing payment:', error);
        showSnackbar('Network error while processing payment', 'error');
    }
}

// Clear debt (manual override)
async function clearDebt(debtId) {
    if (!confirm('Are you sure you want to clear this debt? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('../api/debt_manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=clear_debt&debt_id=${debtId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSnackbar('Debt cleared successfully', 'success');
            closeModal('debtModal');
            await loadDashboardData();
        } else {
            showSnackbar(data.error || 'Error clearing debt', 'error');
        }
    } catch (error) {
        console.error('Error clearing debt:', error);
        showSnackbar('Network error while clearing debt', 'error');
    }
}

function animateValue(id, start, end, duration = 500) {
    const el = document.getElementById(id);
    if (!el) return;
    
    let startTime = null;

    const step = timestamp => {
        if (!startTime) startTime = timestamp;
        const progress = Math.min((timestamp - startTime) / duration, 1);
        const current = start + (end - start) * progress;
        el.textContent = formatCurrency(current);
        if (progress < 1) requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    const modal = document.getElementById('quickAddModal');
    if (e.target === modal) {
        closeQuickAddModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuickAddModal();
    }

    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        showQuickAddModal();
    }
});

// Auto-refresh data every 5 minutes
setInterval(loadDashboardData, 300000);

// Error handling for missing elements
function safeElementUpdate(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    } else {
        console.warn(`Element with id '${id}' not found`);
    }
}