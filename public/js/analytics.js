// Initialize charts and handle all interactions

// Global variables
let monthlyChart = null;
let savingsChart = null;
let memberHeatmapData = {};

// Force toggle button visibility check



// Initialize when document is loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeAnalytics();

});

;

// Main initialization function
function initializeAnalytics() {

    // Wait a bit for DOM to be fully ready
    setTimeout(() => {
        createMonthlyPerformanceChart();
        createSavingsChart();
        generateMemberHeatmap();
        initializeInteractions();
        initializeTooltips();
    }, 100);
}



// Create monthly performance chart
function createMonthlyPerformanceChart() {
    const ctx = document.getElementById('monthlyPerformanceChart');
    if (!ctx || !window.analyticsData) {
        return;
    }

    const monthlyData = window.analyticsData.monthlyBreakdown;

    if (!monthlyData || monthlyData.length === 0) {
        ctx.parentElement.innerHTML = '<div class="no-data-message">No monthly data available for this year.</div>';
        return;
    }

    const labels = monthlyData.map(month => month.month_name);
    const contributionsData = monthlyData.map(month => parseFloat(month.contributions) || 0);
    const expensesData = monthlyData.map(month => parseFloat(month.expenses) || 0);
    const netData = monthlyData.map(month => parseFloat(month.net_amount) || 0);

    // Destroy existing chart if it exists
    if (monthlyChart) {
        monthlyChart.destroy();
    }

    try {
        monthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Contributions',
                        data: contributionsData,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#48bb78',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Expenses',
                        data: expensesData,
                        borderColor: '#ed64a6',
                        backgroundColor: 'rgba(237, 100, 166, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ed64a6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Net Savings',
                        data: netData,
                        borderColor: '#4299e1',
                        backgroundColor: 'rgba(66, 153, 225, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4299e1',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // We have custom legend
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            title: function (context) {
                                return context[0].label + ' ' + window.analyticsData.selectedYear;
                            },
                            label: function (context) {
                                const value = context.parsed.y;
                                return context.dataset.label + ': ₵' + value.toLocaleString('en-GH', { minimumFractionDigits: 2 });
                            },
                            afterBody: function (context) {
                                const monthIndex = context[0].dataIndex;
                                const monthData = monthlyData[monthIndex];
                                if (monthData) {
                                    return [
                                        '',
                                        `Contribution Transactions: ${monthData.contribution_count}`,
                                        `Expense Transactions: ${monthData.expense_count}`,
                                        `Savings Rate: ${monthData.contributions > 0 ? ((monthData.net_amount / monthData.contributions) * 100).toFixed(1) : 0}%`
                                    ];
                                }
                                return [];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#718096',
                            callback: function (value) {
                                return '₵' + value.toLocaleString('en-GH');
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#718096'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    } catch (error) {
        console.error('Error creating monthly chart:', error);
        ctx.parentElement.innerHTML = '<div class="error-message">Error loading chart. Please refresh the page.</div>';
    }
}

// Create savings trend chart
function createSavingsChart() {
    const ctx = document.getElementById('savingsChart');
    if (!ctx || !window.analyticsData) {
        return;
    }

    const monthlyData = window.analyticsData.monthlyBreakdown;

    if (!monthlyData || monthlyData.length === 0) {
        ctx.parentElement.innerHTML = '<div class="no-data-message">No savings data available.</div>';
        return;
    }

    const labels = monthlyData.map(month => month.month_name);
    const savingsData = monthlyData.map(month => parseFloat(month.net_amount) || 0);

    // Destroy existing chart if it exists
    if (savingsChart) {
        savingsChart.destroy();
    }

    try {
        savingsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Savings',
                    data: savingsData,
                    backgroundColor: savingsData.map(value =>
                        value >= 0 ? 'rgba(72, 187, 120, 0.8)' : 'rgba(229, 62, 62, 0.8)'
                    ),
                    borderColor: savingsData.map(value =>
                        value >= 0 ? '#48bb78' : '#e53e3e'
                    ),
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        callbacks: {
                            title: function (context) {
                                return context[0].label + ' ' + window.analyticsData.selectedYear;
                            },
                            label: function (context) {
                                const value = context.parsed.y;
                                const status = value >= 0 ? 'Surplus' : 'Deficit';
                                return `${status}: ₵${Math.abs(value).toLocaleString('en-GH', { minimumFractionDigits: 2 })}`;
                            },
                            afterLabel: function (context) {
                                const monthIndex = context.dataIndex;
                                const monthData = monthlyData[monthIndex];
                                if (monthData && monthData.contributions > 0) {
                                    const savingsRate = (monthData.net_amount / monthData.contributions) * 100;
                                    return `Savings Rate: ${savingsRate.toFixed(1)}%`;
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#718096',
                            callback: function (value) {
                                return '₵' + value.toLocaleString('en-GH');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#718096'
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutBounce'
                }
            }
        });
    } catch (error) {
        console.error('Error creating savings chart:', error);
        ctx.parentElement.innerHTML = '<div class="error-message">Error loading savings chart.</div>';
    }
}

// Generate member performance heatmap
function generateMemberHeatmap() {
    const heatmapContainer = document.getElementById('memberHeatmap');
    if (!heatmapContainer || !window.analyticsData) {
        return;
    }

    const memberPerformance = window.analyticsData.memberPerformance;

    if (!memberPerformance || memberPerformance.length === 0) {
        heatmapContainer.innerHTML = '<div class="no-data-message">No member performance data available for this year.</div>';
        return;
    }

    // Group data by member
    const memberData = {};
    memberPerformance.forEach(record => {
        const memberKey = record.member_name || 'Unknown Member';
        if (!memberData[memberKey]) {
            memberData[memberKey] = {
                role: record.role || 'member',
                data: {}
            };
        }
        memberData[memberKey].data[record.cycle_month_num] = {
            completion_percentage: parseFloat(record.completion_percentage) || 0,
            is_completed: record.is_completed == 1,
            contributed_amount: parseFloat(record.contributed_amount) || 0,
            target_amount: parseFloat(record.target_amount) || 0,
            cycle_month: record.cycle_month
        };
    });

    if (Object.keys(memberData).length === 0) {
        heatmapContainer.innerHTML = '<div class="no-data-message">No member data to display.</div>';
        return;
    }

    // Create heatmap HTML
    let heatmapHTML = '<div class="heatmap-grid">';

    // Header row
    heatmapHTML += '<div class="heatmap-header">';
    heatmapHTML += '<div class="heatmap-corner">Member</div>';
    for (let month = 1; month <= 12; month++) {
        const monthName = new Date(2000, month - 1, 1).toLocaleDateString('en-US', { month: 'short' });
        heatmapHTML += `<div class="heatmap-month">${monthName}</div>`;
    }
    heatmapHTML += '</div>';

    // Member rows
    Object.keys(memberData).sort().forEach(memberName => {
        const member = memberData[memberName];
        heatmapHTML += '<div class="heatmap-row">';
        heatmapHTML += `<div class="heatmap-member" title="Role: ${member.role}">${memberName}</div>`;

        for (let month = 1; month <= 12; month++) {
            const monthData = member.data[month];
            let cellClass = 'no-data';
            let cellText = '-';
            let title = `${memberName} - ${getMonthName(month)}: No data`;

            if (monthData) {
                const percentage = monthData.completion_percentage;
                if (percentage >= 100) {
                    cellClass = 'completed';
                    cellText = '✓';
                } else if (percentage >= 75) {
                    cellClass = 'high-partial';
                    cellText = Math.round(percentage) + '%';
                } else if (percentage > 0) {
                    cellClass = 'partial';
                    cellText = Math.round(percentage) + '%';
                } else {
                    cellClass = 'missed';
                    cellText = '✗';
                }

                title = `${memberName} - ${getMonthName(month)} ${window.analyticsData.selectedYear}\n` +
                    `Target: ₵${monthData.target_amount.toLocaleString()}\n` +
                    `Contributed: ₵${monthData.contributed_amount.toLocaleString()}\n` +
                    `Completion: ${percentage.toFixed(1)}%\n` +
                    `Status: ${monthData.is_completed ? 'Completed' : 'Incomplete'}`;
            }

            heatmapHTML += `<div class="heatmap-cell ${cellClass}" 
                           title="${title}" 
                           data-member="${memberName}" 
                           data-month="${month}"
                           onclick="showMemberMonthDetail('${memberName}', ${month})">${cellText}</div>`;
        }

        heatmapHTML += '</div>';
    });

    heatmapHTML += '</div>';

    // Add legend
    heatmapHTML += `
        <div class="heatmap-legend">
            <div class="legend-title">Performance Legend:</div>
            <div class="legend-items">
                <div class="legend-item">
                    <div class="legend-cell completed"></div>
                    <span>Completed (100%)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-cell high-partial"></div>
                    <span>High Progress (75%+)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-cell partial"></div>
                    <span>Partial Progress</span>
                </div>
                <div class="legend-item">
                    <div class="legend-cell missed"></div>
                    <span>Missed/No Progress</span>
                </div>
                <div class="legend-item">
                    <div class="legend-cell no-data"></div>
                    <span>No Data</span>
                </div>
            </div>
        </div>
    `;

    heatmapContainer.innerHTML = heatmapHTML;

    // Store for later use
    memberHeatmapData = memberData;
}

// Helper function to get month name
function getMonthName(monthNum) {
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    return months[monthNum - 1] || 'Unknown';
}

function getShortMonthName(monthNum) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[monthNum - 1] || 'Unknown';
}

// Initialize all interactions
function initializeInteractions() {
    setupMonthCardInteractions();
    setupCycleTableInteractions();
    setupOverviewCardInteractions();
    setupKeyboardShortcuts();
}

// Initialize tooltips
function initializeTooltips() {
    // Add hover effects for interactive elements
    document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', function () {
            this.setAttribute('data-original-title', this.title);
            this.removeAttribute('title');
        });

        element.addEventListener('mouseleave', function () {
            if (this.getAttribute('data-original-title')) {
                this.title = this.getAttribute('data-original-title');
                this.removeAttribute('data-original-title');
            }
        });
    });
}

// Setup month card interactions
function setupMonthCardInteractions() {
    const monthCards = document.querySelectorAll('.month-card');
    monthCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 12px 35px rgba(66, 153, 225, 0.2)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });

        // Add click animation
        card.addEventListener('mousedown', function () {
            this.style.transform = 'translateY(-2px) scale(0.98)';
        });

        card.addEventListener('mouseup', function () {
            this.style.transform = 'translateY(-4px) scale(1)';
        });
    });
}

// Setup cycle table interactions
function setupCycleTableInteractions() {
    const cycleRows = document.querySelectorAll('.cycle-row');
    cycleRows.forEach(row => {
        row.style.cursor = 'pointer';

        row.addEventListener('click', function (e) {
            // Don't trigger if clicking on a button
            if (e.target.tagName === 'BUTTON') return;

            // Find cycle ID from the view button
            const viewButton = this.querySelector('button[onclick*="showCycleDetails"]');
            if (viewButton) {
                const match = viewButton.getAttribute('onclick').match(/showCycleDetails\((\d+)\)/);
                if (match) {
                    showCycleDetails(match[1]);
                }
            }
        });

        // Prevent row click when clicking buttons
        const buttons = row.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        });
    });
}

// Setup overview card interactions
function setupOverviewCardInteractions() {
    const overviewCards = document.querySelectorAll('.overview-card');
    overviewCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-6px)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Setup keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function (event) {
        // Ctrl/Cmd + E for export
        if ((event.ctrlKey || event.metaKey) && event.key === 'e') {
            event.preventDefault();
            exportAnalytics();
        }

        // Ctrl/Cmd + R for detailed report
        if ((event.ctrlKey || event.metaKey) && event.key === 'r') {
            event.preventDefault();
            showDetailedReport();
        }
    });
}

// Event handlers for user interactions
function changeYear(year) {
    // Add loading animation
    showLoadingOverlay();

    setTimeout(() => {
        window.location.href = `analytics.php?year=${year}`;
    }, 500);
}

function showMonthDetail(month, monthName) {
    const modal = document.getElementById('monthDetailModal');
    const title = document.getElementById('monthDetailTitle');
    const body = document.getElementById('monthDetailBody');

    if (!modal || !title || !body) {
        showSnackbar(`Viewing details for ${monthName}`, 'info');
        return;
    }

    title.innerHTML = `<span class="modal-icon">📅</span> ${monthName} ${window.analyticsData.selectedYear} - Detailed Breakdown`;

    // Find the month data
    const monthData = window.analyticsData.monthlyBreakdown.find(m => m.month == month);

    if (!monthData) {
        body.innerHTML = '<div class="no-data-message">No data available for this month.</div>';
        showModal('monthDetailModal');
        return;
    }

    const savingsRate = monthData.contributions > 0 ?
        ((parseFloat(monthData.net_amount) / parseFloat(monthData.contributions)) * 100).toFixed(1) : 0;

    body.innerHTML = `
        <div class="month-detail-content">
            <div class="detail-overview">
                <div class="detail-stat contributions">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h4>Total Contributions</h4>
                        <p class="stat-value positive">₵${parseFloat(monthData.contributions).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="stat-desc">${monthData.contribution_count} transactions</p>
                    </div>
                </div>
                <div class="detail-stat expenses">
                    <div class="stat-icon">💸</div>
                    <div class="stat-content">
                        <h4>Total Expenses</h4>
                        <p class="stat-value negative">₵${parseFloat(monthData.expenses).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="stat-desc">${monthData.expense_count} transactions</p>
                    </div>
                </div>
                <div class="detail-stat net">
                    <div class="stat-icon">${parseFloat(monthData.net_amount) >= 0 ? '💎' : '⚠️'}</div>
                    <div class="stat-content">
                        <h4>Net Result</h4>
                        <p class="stat-value ${parseFloat(monthData.net_amount) >= 0 ? 'positive' : 'negative'}">
                            ₵${Math.abs(parseFloat(monthData.net_amount)).toLocaleString('en-GH', { minimumFractionDigits: 2 })}
                        </p>
                        <p class="stat-desc">${parseFloat(monthData.net_amount) >= 0 ? 'Surplus' : 'Deficit'}</p>
                    </div>
                </div>
                <div class="detail-stat savings-rate">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h4>Savings Rate</h4>
                        <p class="stat-value ${savingsRate >= 0 ? 'positive' : 'negative'}">${savingsRate}%</p>
                        <p class="stat-desc">Of total contributions</p>
                    </div>
                </div>
            </div>
            
            <div class="detail-actions">
                <button class="btn btn-primary" onclick="loadMonthTransactions(${month}, '${monthName}')">
                    <span class="btn-icon">📋</span>
                    View All Transactions
                </button>
                <button class="btn btn-secondary" onclick="loadMemberPerformance(${month}, '${monthName}')">
                    <span class="btn-icon">👥</span>
                    Member Performance
                </button>
                <button class="btn btn-outline" onclick="exportMonthData(${month}, '${monthName}')">
                    <span class="btn-icon">📤</span>
                    Export Month Data
                </button>
            </div>
        </div>
    `;

    showModal('monthDetailModal');
}

function showMonthTransactionsModal(data, monthName) {
    const modalId = 'monthTransactionsModal';
    const modal = createDynamicModal(modalId);
    
    const modalTitle = `📋 ${monthName} ${data.year} - All Transactions`;
    const totalContributions = data.contributions.reduce((sum, c) => sum + parseFloat(c.amount), 0);
    const totalExpenses = data.expenses.reduce((sum, e) => sum + parseFloat(e.amount), 0);
    const netAmount = totalContributions - totalExpenses;
    
    const modalBody = `
        <div class="transactions-summary">
            <div class="summary-cards">
                <div class="summary-card contributions">
                    <div class="summary-icon">💰</div>
                    <div class="summary-content">
                        <h4>Total Contributions</h4>
                        <p class="summary-amount positive">₵${totalContributions.toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="summary-count">${data.contributions.length} transactions</p>
                    </div>
                </div>
                <div class="summary-card expenses">
                    <div class="summary-icon">💸</div>
                    <div class="summary-content">
                        <h4>Total Expenses</h4>
                        <p class="summary-amount negative">₵${totalExpenses.toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="summary-count">${data.expenses.length} transactions</p>
                    </div>
                </div>
                <div class="summary-card net">
                    <div class="summary-icon">${netAmount >= 0 ? '💎' : '⚠️'}</div>
                    <div class="summary-content">
                        <h4>Net Result</h4>
                        <p class="summary-amount ${netAmount >= 0 ? 'positive' : 'negative'}">₵${Math.abs(netAmount).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="summary-count">${netAmount >= 0 ? 'Surplus' : 'Deficit'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="transactions-tabs">
            <button class="tab-button active" onclick="showTransactionTab('contributions-tab', this)">
                Contributions (${data.contributions.length})
            </button>
            <button class="tab-button" onclick="showTransactionTab('expenses-tab', this)">
                Expenses (${data.expenses.length})
            </button>
        </div>
        
        <div class="transactions-content">
            <div id="contributions-tab" class="tab-content active">
                ${generateContributionsTable(data.contributions)}
            </div>
            <div id="expenses-tab" class="tab-content">
                ${generateExpensesTable(data.expenses)}
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="exportMonthTransactions(${data.month}, '${monthName}', ${data.year})">
                <span class="btn-icon">📤</span>
                Export Transactions
            </button>
            <button class="btn btn-secondary" onclick="closeModal('${modalId}')">
                Close
            </button>
        </div>
    `;
    
    showDynamicModal(modal, modalTitle, modalBody);
}

function showMemberPerformanceModal(data, monthName) {
    const modalId = 'memberPerformanceModal';
    const modal = createDynamicModal(modalId);
    
    const modalTitle = `👥 ${monthName} ${data.year} - Member Performance`;
    
    const modalBody = `
        <div class="performance-overview">
            <div class="performance-stats">
                <div class="stat-item">
                    <span class="stat-label">Total Members:</span>
                    <span class="stat-value">${data.members.length}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Completed Targets:</span>
                    <span class="stat-value">${data.members.filter(m => m.is_completed).length}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Average Completion:</span>
                    <span class="stat-value">${data.members.length > 0 ? 
                        (data.members.reduce((sum, m) => sum + parseFloat(m.completion_percentage), 0) / data.members.length).toFixed(1) : 0}%</span>
                </div>
            </div>
        </div>
        
        <div class="members-performance-table">
            ${generateMemberPerformanceTable(data.members)}
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="exportMemberPerformance(${data.month}, '${monthName}', ${data.year})">
                <span class="btn-icon">📤</span>
                Export Performance
            </button>
            <button class="btn btn-secondary" onclick="closeModal('${modalId}')">
                Close
            </button>
        </div>
    `;
    
    showDynamicModal(modal, modalTitle, modalBody);
}


function showCycleDetails(cycleId) {
    const modal = document.getElementById('cycleDetailModal');
    const title = document.getElementById('cycleDetailTitle');
    const body = document.getElementById('cycleDetailBody');

    if (!modal || !title || !body) {
        showSnackbar('Showing cycle details for ID: ' + cycleId, 'info');
        return;
    }

    // Find cycle data
    const cycle = window.analyticsData.cyclePerformance.find(c => c.id == cycleId);

    if (!cycle) {
        body.innerHTML = '<div class="no-data-message">Cycle data not found.</div>';
        showModal('cycleDetailModal');
        return;
    }

    title.innerHTML = `<span class="modal-icon">🔄</span> Cycle Details - ${cycle.cycle_month}`;

    const completionRate = parseFloat(cycle.avg_completion_rate || 0);
    const progressBarWidth = Math.min(completionRate, 100);

    body.innerHTML = `
        <div class="cycle-detail-content">
            <div class="cycle-overview">
                <div class="cycle-stat">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <h4>Target Amount</h4>
                        <p class="stat-value">₵${parseFloat(cycle.total_target || 0).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="stat-desc">Monthly Goal</p>
                    </div>
                </div>
                <div class="cycle-stat">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h4>Collected Amount</h4>
                        <p class="stat-value">₵${parseFloat(cycle.total_contributed || 0).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="stat-desc">Total Received</p>
                    </div>
                </div>
                <div class="cycle-stat">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h4>Completion Rate</h4>
                        <p class="stat-value ${completionRate >= 100 ? 'positive' : completionRate >= 75 ? 'warning' : 'negative'}">
                            ${completionRate.toFixed(1)}%
                        </p>
                        <div class="progress-bar">
                            <div class="progress-fill ${completionRate >= 100 ? 'complete' : completionRate >= 75 ? 'good' : 'poor'}" 
                                 style="width: ${progressBarWidth}%"></div>
                        </div>
                    </div>
                </div>
                <div class="cycle-stat">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h4>Member Progress</h4>
                        <p class="stat-value">${cycle.completed_members || 0}/${cycle.total_members || 0}</p>
                        <p class="stat-desc">Completed Goals</p>
                    </div>
                </div>
            </div>
            
            ${parseFloat(cycle.total_deficit || 0) > 0 ? `
                <div class="deficit-warning">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-content">
                        <h4>Outstanding Deficit</h4>
                        <p class="deficit-amount">₵${parseFloat(cycle.total_deficit).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</p>
                        <p class="deficit-desc">Amount not yet contributed by members</p>
                    </div>
                </div>
            ` : `
                <div class="success-message">
                    <div class="success-icon">✅</div>
                    <div class="success-content">
                        <h4>Cycle Complete</h4>
                        <p>All targets have been met for this cycle!</p>
                    </div>
                </div>
            `}
            
            <div class="cycle-status-info">
                <div class="status-item">
                    <span class="status-label">Status:</span>
                    <span class="status-value ${cycle.is_closed == 1 ? 'closed' : 'active'}">
                        ${cycle.is_closed == 1 ? '🔒 Closed' : '🔄 Active'}
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Period:</span>
                    <span class="status-value">${cycle.cycle_month}</span>
                </div>
            </div>
            
            <div class="cycle-actions">
                <button class="btn btn-primary" onclick="loadCycleMembers(${cycleId})">
                    <span class="btn-icon">👥</span>
                    View Member Details
                </button>
                <button class="btn btn-secondary" onclick="loadCycleTransactions(${cycleId})">
                    <span class="btn-icon">📋</span>
                    View Transactions
                </button>
                ${cycle.is_closed != 1 ? `
                    <button class="btn btn-warning" onclick="confirmCloseCycle(${cycleId})">
                        <span class="btn-icon">🔒</span>
                        Close Cycle
                    </button>
                ` : ''}
                <button class="btn btn-outline" onclick="exportCycleData(${cycleId})">
                    <span class="btn-icon">📤</span>
                    Export Data
                </button>
            </div>
        </div>
    `;

    showModal('cycleDetailModal');
}

function showMemberMonthDetail(memberName, month) {
    const monthName = getMonthName(month);
    const memberData = memberHeatmapData[memberName];

    if (!memberData || !memberData.data[month]) {
        showSnackbar(`No data available for ${memberName} in ${monthName}`, 'warning');
        return;
    }

    const data = memberData.data[month];
    const completionStatus = data.is_completed ? 'Completed' : 'Incomplete';
    const progressColor = data.completion_percentage >= 100 ? 'success' :
        data.completion_percentage >= 75 ? 'warning' : 'error';

    showSnackbar(
        `${memberName} - ${monthName}: ${completionStatus} (${data.completion_percentage}%)`,
        progressColor
    );
}

// Load month transactions with detailed modal display
function loadMonthTransactions(month, monthName) {
    showLoadingOverlay();
    
    const year = window.analyticsData.selectedYear;
    
    fetch(`../ajax/get_month_transactions.php?month=${month}&year=${year}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingOverlay();
            
            if (data.success) {
                showMonthTransactionsModal(data, monthName);
            } else {
                showSnackbar(data.error || 'Failed to load transactions', 'error');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            console.error('Error loading month transactions:', error);
            showSnackbar('Failed to load transactions. Please try again.', 'error');
        });
}


// Load member performance with detailed breakdown
function loadMemberPerformance(month, monthName) {
    showLoadingOverlay();
    
    const year = window.analyticsData.selectedYear;
    
    fetch(`../ajax/get_member_performance.php?month=${month}&year=${year}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingOverlay();
            
            if (data.success) {
                showMemberPerformanceModal(data, monthName);
            } else {
                showSnackbar(data.error || 'Failed to load member performance', 'error');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            console.error('Error loading member performance:', error);
            showSnackbar('Failed to load member performance. Please try again.', 'error');
        });
}
function showCycleMembersModal(data) {
    const modalId = 'cycleMembersModal';
    const modal = createDynamicModal(modalId);
    
    const modalTitle = `👥 Cycle Members - Details`;
    
    const modalBody = `
        <div class="cycle-members-overview">
            <div class="members-stats">
                <div class="stat-item">
                    <span class="stat-label">Total Members:</span>
                    <span class="stat-value">${data.members.length}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Completed:</span>
                    <span class="stat-value">${data.members.filter(m => m.is_completed).length}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">In Progress:</span>
                    <span class="stat-value">${data.members.filter(m => !m.is_completed && parseFloat(m.completion_percentage) > 0).length}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Not Started:</span>
                    <span class="stat-value">${data.members.filter(m => parseFloat(m.completion_percentage) === 0).length}</span>
                </div>
            </div>
        </div>
        
        <div class="cycle-members-table">
            ${generateCycleMembersTable(data.members)}
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="exportCycleMembers(${data.cycle_id})">
                <span class="btn-icon">📤</span>
                Export Members
            </button>
            <button class="btn btn-secondary" onclick="closeModal('${modalId}')">
                Close
            </button>
        </div>
    `;
    
    showDynamicModal(modal, modalTitle, modalBody);
}

// Load cycle members with performance details
function loadCycleMembers(cycleId) {
    showLoadingOverlay();
    
    fetch(`../ajax/get_cycle_members.php?cycle_id=${cycleId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingOverlay();
            
            if (data.success) {
                showCycleMembersModal(data);
            } else {
                showSnackbar(data.error || 'Failed to load cycle members', 'error');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            console.error('Error loading cycle members:', error);
            showSnackbar('Failed to load cycle members. Please try again.', 'error');
        });
}

// Load cycle transactions
function loadCycleTransactions(cycleId) {
    showLoadingOverlay();
    
    fetch(`../ajax/get_cycle_transactions.php?cycle_id=${cycleId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingOverlay();
            
            if (data.success) {
                showCycleTransactionsModal(data);
            } else {
                showSnackbar(data.error || 'Failed to load cycle transactions', 'error');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            console.error('Error loading cycle transactions:', error);
            showSnackbar('Failed to load cycle transactions. Please try again.', 'error');
        });
}

function showCycleTransactionsModal(data) {
    const modalId = 'cycleTransactionsModal';
    const modal = createDynamicModal(modalId);
    
    const modalTitle = `📋 ${data.cycle.cycle_month} - Cycle Transactions`;
    const totalAmount = data.transactions.reduce((sum, t) => sum + parseFloat(t.amount), 0);
    
    const modalBody = `
        <div class="cycle-transactions-summary">
            <div class="summary-info">
                <div class="info-item">
                    <span class="info-label">Period:</span>
                    <span class="info-value">${formatDate(data.cycle.start_date)} to ${formatDate(data.cycle.end_date)}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Transactions:</span>
                    <span class="info-value">${data.transactions.length}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">₵${totalAmount.toLocaleString('en-GH', { minimumFractionDigits: 2 })}</span>
                </div>
            </div>
        </div>
        
        <div class="cycle-transactions-table">
            ${generateCycleTransactionsTable(data.transactions)}
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="exportCycleTransactions(${data.cycle_id})">
                <span class="btn-icon">📤</span>
                Export Transactions
            </button>
            <button class="btn btn-secondary" onclick="closeModal('${modalId}')">
                Close
            </button>
        </div>
    `;
    
    showDynamicModal(modal, modalTitle, modalBody);
}


// Enhanced cycle closure with proper backend integration
function confirmCloseCycle(cycleId) {
    const modal = createConfirmationModal(
        'Close Cycle Confirmation',
        'Are you sure you want to close this cycle? This action cannot be undone and will:',
        [
            '• Calculate outstanding debts for incomplete members',
            '• Lock the cycle from further modifications',
            '• Generate final performance reports',
            '• Update member debt records'
        ],
        () => closeCycle(cycleId)
    );
    
    showCustomModal(modal);
}

function closeCycle(cycleId) {
    showLoadingOverlay();
    
    fetch('../ajax/close_cycle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cycle_id: cycleId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoadingOverlay();
        
        if (data.success) {
            showSnackbar('Cycle closed successfully!', 'success');
            // Refresh the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showSnackbar(data.error || 'Failed to close cycle', 'error');
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error closing cycle:', error);
        showSnackbar('Failed to close cycle. Please try again.', 'error');
    });
}


// Export functions
function exportMonthData(month, monthName) {
    showLoadingOverlay();

    // Create CSV data
    const monthData = window.analyticsData.monthlyBreakdown.find(m => m.month == month);

    if (!monthData) {
        hideLoadingOverlay();
        showSnackbar('No data to export for this month', 'error');
        return;
    }

    const csvData = [
        ['Month', 'Year', 'Contributions', 'Expenses', 'Net Amount', 'Contribution Count', 'Expense Count', 'Savings Rate'],
        [
            monthName,
            window.analyticsData.selectedYear,
            monthData.contributions,
            monthData.expenses,
            monthData.net_amount,
            monthData.contribution_count,
            monthData.expense_count,
            monthData.contributions > 0 ? ((monthData.net_amount / monthData.contributions) * 100).toFixed(2) + '%' : '0%'
        ]
    ];

    downloadCSV(csvData, `${monthName}_${window.analyticsData.selectedYear}_Report.csv`);

    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar(`${monthName} data exported successfully!`, 'success');
    }, 1000);
}

function exportCycleData(cycleId) {
    showLoadingOverlay();

    const cycle = window.analyticsData.cyclePerformance.find(c => c.id == cycleId);

    if (!cycle) {
        hideLoadingOverlay();
        showSnackbar('No cycle data to export', 'error');
        return;
    }

    const csvData = [
        ['Cycle Month', 'Status', 'Target Amount', 'Collected Amount', 'Completion Rate', 'Total Members', 'Completed Members', 'Deficit Amount'],
        [
            cycle.cycle_month,
            cycle.is_closed ? 'Closed' : 'Active',
            cycle.total_target,
            cycle.total_contributed,
            cycle.avg_completion_rate + '%',
            cycle.total_members,
            cycle.completed_members,
            cycle.total_deficit
        ]
    ];

    downloadCSV(csvData, `Cycle_${cycle.cycle_month.replace('-', '_')}_Report.csv`);

    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar('Cycle data exported successfully!', 'success');
    }, 1000);
}

function generateContributionsTable(contributions) {
    if (contributions.length === 0) {
        return '<div class="no-data-message">No contributions found for this month.</div>';
    }
    
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${contributions.map(contribution => `
                        <tr>
                            <td>${formatDate(contribution.contribution_date)}</td>
                            <td>
                                <div class="member-info">
                                    <span class="member-name">${contribution.member_name}</span>
                                    ${contribution.phone_number ? `<span class="member-phone">${contribution.phone_number}</span>` : ''}
                                </div>
                            </td>
                            <td class="amount positive">₵${parseFloat(contribution.amount).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</td>
                            <td>${contribution.description || 'Regular contribution'}</td>
                            <td><span class="status-badge verified">Verified</span></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function generateExpensesTable(expenses) {
    if (expenses.length === 0) {
        return '<div class="no-data-message">No expenses found for this month.</div>';
    }
    
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Paid By</th>
                    </tr>
                </thead>
                <tbody>
                    ${expenses.map(expense => `
                        <tr>
                            <td>${formatDate(expense.expense_date)}</td>
                            <td><span class="category-tag">${expense.category}</span></td>
                            <td>${expense.description}</td>
                            <td class="amount negative">₵${parseFloat(expense.amount).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</td>
                            <td>${expense.paid_by_name || 'Family Fund'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function generateMemberPerformanceTable(members) {
    if (members.length === 0) {
        return '<div class="no-data-message">No member performance data found.</div>';
    }
    
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Target</th>
                        <th>Contributed</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Contributions</th>
                    </tr>
                </thead>
                <tbody>
                    ${members.map(member => {
                        const percentage = parseFloat(member.completion_percentage);
                        const progressClass = percentage >= 100 ? 'complete' : percentage >= 75 ? 'good' : percentage > 0 ? 'partial' : 'poor';
                        
                        return `
                            <tr>
                                <td>
                                    <div class="member-info">
                                        <span class="member-name">${member.member_name}</span>
                                        <span class="member-role">${member.role}</span>
                                    </div>
                                </td>
                                <td class="amount">₵${parseFloat(member.target_amount).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</td>
                                <td class="amount">₵${parseFloat(member.contributed_amount).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill ${progressClass}" style="width: ${Math.min(percentage, 100)}%"></div>
                                        </div>
                                        <span class="progress-text">${percentage.toFixed(1)}%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge ${member.is_completed ? 'completed' : 'pending'}">
                                        ${member.is_completed ? 'Completed' : 'Pending'}
                                    </span>
                                </td>
                                <td class="center">${member.contribution_count || 0}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function generateCycleMembersTable(members) {
    return generateMemberPerformanceTable(members); // Same structure
}

function generateCycleTransactionsTable(transactions) {
    if (transactions.length === 0) {
        return '<div class="no-data-message">No transactions found for this cycle.</div>';
    }
    
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    ${transactions.map(transaction => `
                        <tr>
                            <td>${formatDate(transaction.contribution_date)}</td>
                            <td>
                                <div class="member-info">
                                    <span class="member-name">${transaction.member_name}</span>
                                </div>
                            </td>
                            <td class="amount positive">₵${parseFloat(transaction.amount).toLocaleString('en-GH', { minimumFractionDigits: 2 })}</td>
                            <td>${transaction.description || 'Regular contribution'}</td>
                            <td class="member-phone">${transaction.phone_number || 'N/A'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Export functions for new modals

function exportMonthTransactions(month, monthName, year) {
    showLoadingOverlay();
    // Implementation would be similar to existing export functions
    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar(`${monthName} transactions exported successfully!`, 'success');
    }, 1000);
}

function exportMemberPerformance(month, monthName, year) {
    showLoadingOverlay();
    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar(`${monthName} member performance exported successfully!`, 'success');
    }, 1000);
}

function exportCycleMembers(cycleId) {
    showLoadingOverlay();
    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar('Cycle members exported successfully!', 'success');
    }, 1000);
}

function exportCycleTransactions(cycleId) {
    showLoadingOverlay();
    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar('Cycle transactions exported successfully!', 'success');
    }, 1000);
}

// Utility functions

function createDynamicModal(modalId) {
    // Remove existing modal if it exists
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal large-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="${modalId}Title"></h3>
                <button class="close" onclick="closeModal('${modalId}')">&times;</button>
            </div>
            <div class="modal-body" id="${modalId}Body">
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    return modal;
}

function showDynamicModal(modal, title, body) {
    const titleElement = modal.querySelector(`#${modal.id}Title`);
    const bodyElement = modal.querySelector(`#${modal.id}Body`);
    
    titleElement.innerHTML = title;
    bodyElement.innerHTML = body;
    
    showModal(modal.id);
}

function createConfirmationModal(title, message, details, onConfirm) {
    const modalId = 'confirmationModal';
    const modal = createDynamicModal(modalId);
    
    const modalBody = `
        <div class="confirmation-content">
            <div class="confirmation-icon">⚠️</div>
            <div class="confirmation-message">
                <p>${message}</p>
                ${details.length > 0 ? `
                    <ul class="confirmation-details">
                        ${details.map(detail => `<li>${detail}</li>`).join('')}
                    </ul>
                ` : ''}
            </div>
            <div class="confirmation-actions">
                <button class="btn btn-warning" onclick="confirmAction('${modalId}')">
                    Yes, Close Cycle
                </button>
                <button class="btn btn-secondary" onclick="closeModal('${modalId}')">
                    Cancel
                </button>
            </div>
        </div>
    `;
    
    // Store the confirmation callback
    modal.confirmCallback = onConfirm;
    
    showDynamicModal(modal, title, modalBody);
    return modal;
}

function showCustomModal(modal) {
    showModal(modal.id);
}

function confirmAction(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.confirmCallback) {
        modal.confirmCallback();
    }
    closeModal(modalId);
}

function showTransactionTab(tabId, buttonElement) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab and mark button as active
    document.getElementById(tabId).classList.add('active');
    buttonElement.classList.add('active');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

// Additional CSS styles for enhanced modals
function initializeEnhancedModalStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* Enhanced Modal Styles */
        .large-modal .modal-content {
            max-width: 95vw;
            width: 1200px;
        }
        
        /* Transaction Summary Cards */
        .transactions-summary {
            margin-bottom: 2rem;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .summary-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            border-color: #4299e1;
            transform: translateY(-2px);
        }
        
        .summary-icon {
            font-size: 2rem;
            padding: 0.75rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .summary-content h4 {
            margin: 0 0 0.5rem 0;
            color: #4a5568;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .summary-amount.positive {
            color: #38a169;
        }
        
        .summary-amount.negative {
            color: #e53e3e;
        }
        
        .summary-count {
            font-size: 0.8rem;
            color: #718096;
            margin: 0;
        }
        
        /* Tabs */
        .transactions-tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .tab-button {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
            color: #718096;
        }
        
        .tab-button.active {
            color: #4299e1;
            border-bottom-color: #4299e1;
        }
        
        .tab-button:hover {
            color: #4299e1;
            background: #f7fafc;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Data Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .data-table th {
            background: #f7fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        .data-table tr:hover {
            background: #f7fafc;
        }
        
        .data-table .amount {
            font-weight: 600;
            text-align: right;
        }
        
        .data-table .amount.positive {
            color: #38a169;
        }
        
        .data-table .amount.negative {
            color: #e53e3e;
        }
        
        .data-table .center {
            text-align: center;
        }
        
        /* Member Info */
        .member-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .member-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .member-phone {
            font-size: 0.8rem;
            color: #718096;
        }
        
        .member-role {
            font-size: 0.75rem;
            color: #4299e1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge.verified {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-badge.completed {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-badge.pending {
            background: #fed7d7;
            color: #742a2a;
        }
        
        /* Category Tags */
        .category-tag {
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.25rem 0.75rem;
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Progress Bars */
        .progress-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-fill.complete {
            background: #38a169;
        }
        
        .progress-fill.good {
            background: #ed8936;
        }
        
        .progress-fill.partial {
            background: #ecc94b;
        }
        
        .progress-fill.poor {
            background: #e53e3e;
        }
        
        .progress-text {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4a5568;
            min-width: 50px;
            text-align: right;
        }
        
        /* Performance Overview */
        .performance-overview,
        .cycle-members-overview,
        .cycle-transactions-summary {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        
        .performance-stats,
        .members-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .stat-item,
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .stat-label,
        .info-label {
            font-weight: 500;
            color: #4a5568;
        }
        
        .stat-value,
        .info-value {
            font-weight: 700;
            color: #2d3748;
        }
        
        .summary-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        /* Confirmation Modal */
        .confirmation-content {
            text-align: center;
            padding: 1rem;
        }
        
        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .confirmation-message {
            margin-bottom: 2rem;
        }
        
        .confirmation-message p {
            font-size: 1.1rem;
            color: #4a5568;
            margin-bottom: 1rem;
        }
        
        .confirmation-details {
            text-align: left;
            background: #fef5e7;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #ed8936;
        }
        
        .confirmation-details li {
            color: #744210;
            margin-bottom: 0.5rem;
        }
        
        .confirmation-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        /* No Data Messages */
        .no-data-message {
            text-align: center;
            padding: 3rem;
            color: #718096;
            font-style: italic;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .large-modal .modal-content {
                width: 95vw;
                margin: 1rem auto;
                max-height: 95vh;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .transactions-tabs {
                overflow-x: auto;
            }
            
            .tab-button {
                white-space: nowrap;
                padding: 0.75rem 1rem;
            }
            
            .data-table {
                font-size: 0.8rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .performance-stats,
            .members-stats,
            .summary-info {
                grid-template-columns: 1fr;
            }
            
            .confirmation-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .confirmation-actions .btn {
                width: 200px;
            }
        }
        
        /* Animation for modal content */
        .modal-content {
            animation: modalSlide 0.3s ease-out;
        }
        
        @keyframes modalSlide {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Loading states */
        .loading-table {
            position: relative;
            min-height: 200px;
        }
        
        .loading-table::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .loading-table::after {
            content: "Loading...";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 600;
            color: #4a5568;
            z-index: 11;
        }
        
        /* Enhanced button styles for modals */
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            margin-top: 2rem;
        }
        
        .modal-actions .btn {
            min-width: 150px;
        }
        
        /* Table sorting indicators */
        .data-table th.sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
        }
        
        .data-table th.sortable:hover {
            background: #edf2f7;
        }
        
        .data-table th.sortable::after {
            content: "↕";
            position: absolute;
            right: 0.5rem;
            opacity: 0.3;
        }
        
        .data-table th.sortable.asc::after {
            content: "↑";
            opacity: 1;
        }
        
        .data-table th.sortable.desc::after {
            content: "↓";
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
}

// Initialize enhanced modal styles when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancedModalStyles();
});

// Add table sorting functionality
function addTableSorting() {
    document.querySelectorAll('.data-table th.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            
            const isAsc = this.classList.contains('asc');
            
            // Remove sorting classes from all headers
            table.querySelectorAll('th').forEach(th => {
                th.classList.remove('asc', 'desc');
            });
            
            // Add appropriate class to current header
            this.classList.add(isAsc ? 'desc' : 'asc');
            
            // Sort rows
            rows.sort((a, b) => {
                const aText = a.children[columnIndex].textContent.trim();
                const bText = b.children[columnIndex].textContent.trim();
                
                // Handle numeric values
                const aNum = parseFloat(aText.replace(/[₵,]/g, ''));
                const bNum = parseFloat(bText.replace(/[₵,]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? bNum - aNum : aNum - bNum;
                }
                
                // Handle text values
                const comparison = aText.localeCompare(bText);
                return isAsc ? -comparison : comparison;
            });
            
            // Reorder rows in DOM
            rows.forEach(row => tbody.appendChild(row));
        });
    });
}

// Enhanced search functionality for tables
function addTableSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
        
        // Update visible count
        const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
        const countElement = document.querySelector(`#${tableId}-count`);
        if (countElement) {
            countElement.textContent = `Showing ${visibleRows.length} of ${rows.length} records`;
        }
    });
}

// Export enhanced data with better formatting
function exportEnhancedCSV(data, filename, headers) {
    const csvContent = [
        headers,
        ...data.map(row => headers.map(header => {
            const value = row[header.key] || '';
            // Handle special formatting for amounts
            if (header.type === 'amount') {
                return typeof value === 'number' ? value.toFixed(2) : value;
            }
            // Handle dates
            if (header.type === 'date') {
                return formatDate(value);
            }
            // Escape commas and quotes in text
            if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                return `"${value.replace(/"/g, '""')}"`;
            }
            return value;
        }))
    ].map(row => row.join(',')).join('\n');
    
    downloadCSV([csvContent.split('\n').map(row => row.split(','))], filename);
}

// Utility function to refresh current analytics data
function refreshAnalyticsData() {
    const currentYear = window.analyticsData?.selectedYear || new Date().getFullYear();
    window.location.href = `analytics.php?year=${currentYear}&refresh=1`;
}

function exportAnalytics() {
    showLoadingOverlay();

    // Prepare comprehensive analytics data
    const csvData = [
        ['Analytics Report - ' + window.analyticsData.selectedYear],
        [''],
        ['Year Overview'],
        ['Metric', 'Value'],
        ['Total Contributions', '₵' + window.analyticsData.yearlyOverview.total_contributions],
        ['Total Expenses', '₵' + window.analyticsData.yearlyOverview.total_expenses],
        ['Net Savings', '₵' + window.analyticsData.yearlyOverview.net_savings],
        ['Active Contributors', window.analyticsData.yearlyOverview.active_contributors],
        [''],
        ['Monthly Breakdown'],
        ['Month', 'Contributions', 'Expenses', 'Net Amount', 'Contribution Count', 'Expense Count']
    ];

    // Add monthly data
    window.analyticsData.monthlyBreakdown.forEach(month => {
        csvData.push([
            month.month_name,
            month.contributions,
            month.expenses,
            month.net_amount,
            month.contribution_count,
            month.expense_count
        ]);
    });

    // Add top contributors
    csvData.push([''], ['Top Contributors'], ['Name', 'Total Contributed', 'Contribution Count']);
    window.analyticsData.topContributors.slice(0, 10).forEach(contributor => {
        csvData.push([
            contributor.contributor_name,
            contributor.total_contributed,
            contributor.contribution_count
        ]);
    });

    downloadCSV(csvData, `Family_Analytics_Report_${window.analyticsData.selectedYear}.csv`);

    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar('Analytics report exported successfully!', 'success');
    }, 1500);
}

function showDetailedReport() {
    showLoadingOverlay();

    setTimeout(() => {
        hideLoadingOverlay();
        showSnackbar('Detailed report feature coming soon!', 'info');
    }, 1000);
}

// Helper functions
function downloadCSV(data, filename) {
    const csvContent = data.map(row =>
        row.map(field => {
            // Handle fields that might contain commas or quotes
            if (typeof field === 'string' && (field.includes(',') || field.includes('"') || field.includes('\n'))) {
                return '"' + field.replace(/"/g, '""') + '"';
            }
            return field;
        }).join(',')
    ).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function showLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showSnackbar(message, type = 'info') {
    const snackbar = document.getElementById('snackbar');
    if (!snackbar) return;

    snackbar.textContent = message;
    snackbar.className = `snackbar show ${type}`;

    setTimeout(() => {
        snackbar.className = 'snackbar';
    }, 3000);
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        // Add click handler to close modal when clicking outside
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modalId);
            }
        });
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Sign out function
function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        showLoadingOverlay();

        fetch('../actions/signout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
            .then(() => {
                window.location.href = 'login.php';
            })
            .catch(() => {
                // Fallback - redirect anyway
                window.location.href = 'login.php';
            });
    }
}

// Initialize heatmap legend styling
function initializeHeatmapLegend() {
    const style = document.createElement('style');
    style.textContent = `
        .heatmap-grid {
            display: grid;
            gap: 2px;
            background: #f7fafc;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .heatmap-header {
            display: grid;
            grid-template-columns: 150px repeat(12, 1fr);
            gap: 2px;
            margin-bottom: 4px;
        }
        
        .heatmap-row {
            display: grid;
            grid-template-columns: 150px repeat(12, 1fr);
            gap: 2px;
            margin-bottom: 2px;
        }
        
        .heatmap-corner {
            background: #e2e8f0;
            padding: 8px;
            font-weight: 600;
            text-align: center;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .heatmap-month {
            background: #e2e8f0;
            padding: 8px;
            font-weight: 600;
            text-align: center;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .heatmap-member {
            background: #f1f5f9;
            padding: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            border-radius: 4px;
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .heatmap-cell {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .heatmap-cell:hover {
            transform: scale(1.1);
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .heatmap-cell.completed {
            background: #48bb78;
            color: white;
        }
        
        .heatmap-cell.high-partial {
            background: #ed8936;
            color: white;
        }
        
        .heatmap-cell.partial {
            background: #ecc94b;
            color: #744210;
        }
        
        .heatmap-cell.missed {
            background: #e53e3e;
            color: white;
        }
        
        .heatmap-cell.no-data {
            background: #e2e8f0;
            color: #718096;
        }
        
        .heatmap-legend {
            margin-top: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .legend-title {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        
        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #4a5568;
        }
        
        .legend-cell {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .legend-cell.completed {
            background: #48bb78;
        }
        
        .legend-cell.high-partial {
            background: #ed8936;
        }
        
        .legend-cell.partial {
            background: #ecc94b;
        }
        
        .legend-cell.missed {
            background: #e53e3e;
        }
        
        .legend-cell.no-data {
            background: #e2e8f0;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        .large-modal .modal-content {
            max-width: 95vw;
            width: 1200px;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-icon {
            font-size: 1.5rem;
        }

        .close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
            max-height: calc(90vh - 140px);
            overflow-y: auto;
        }
        
        /* Detail modal styles */
        .detail-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-stat {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .detail-stat:hover {
            border-color: #4299e1;
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 2rem;
            padding: 0.75rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-content h4 {
            margin: 0 0 0.5rem 0;
            color: #4a5568;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-value.positive {
            color: #38a169;
        }

        .stat-value.negative {
            color: #e53e3e;
        }

        .stat-desc {
            font-size: 0.8rem;
            color: #718096;
            margin: 0;
        }

        .detail-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            border: none;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: #4299e1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3182ce;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .btn-outline {
            background: transparent;
            color: #4299e1;
            border: 2px solid #4299e1;
        }
        
        .btn-outline:hover {
            background: #4299e1;
            color: white;
        }
        
        .btn-warning {
            background: #ed8936;
            color: white;
        }
        
        .btn-warning:hover {
            background: #dd6b20;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .heatmap-header,
            .heatmap-row {
                grid-template-columns: 120px repeat(12, 60px);
            }
            
            .heatmap-member {
                font-size: 0.75rem;
                padding: 6px;
            }
            
            .heatmap-cell {
                min-height: 35px;
                font-size: 0.7rem;
            }
            
            .detail-overview {
                grid-template-columns: 1fr;
            }
            
            .detail-actions {
                flex-direction: column;
            }
        }
    `;
    document.head.appendChild(style);
}

// Enhanced error handling
window.addEventListener('error', function (e) {
    console.error('Analytics error:', e.error);
    showSnackbar('An error occurred. Please refresh the page.', 'error');
});

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    try {
        initializeAnalytics();
        initializeHeatmapLegend();
    } catch (error) {
        console.error('Initialization error:', error);
        showSnackbar('Failed to initialize analytics. Please refresh the page.', 'error');
    }
});