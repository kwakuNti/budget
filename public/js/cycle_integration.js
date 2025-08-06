// cycle_integration.js - Complete version with all required functions

// Global variables
let currentCycleData = null;
let memberPerformanceData = [];
let debtData = [];

// Utility Functions
function formatNumber(number) {
    return new Intl.NumberFormat('en-GH').format(number);
}

function formatCycleMonth(cycleMonth) {
    const [year, month] = cycleMonth.split('-');
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return `${monthNames[parseInt(month) - 1]} ${year}`;
}

// UI Helper Functions
function showLoading(show) {
    const loadingState = document.getElementById('loadingState');
    const mainContent = document.getElementById('mainContent');
    const errorState = document.getElementById('errorState');
    
    if (show) {
        loadingState.style.display = 'block';
        mainContent.style.display = 'none';
        errorState.style.display = 'none';
    } else {
        loadingState.style.display = 'none';
        mainContent.style.display = 'block';
        errorState.style.display = 'none';
    }
}

function showAlert(message, type = 'info') {
    // Use the notification banner from the HTML
    showNotification(message, type);
}

function showNotification(message, type = 'info') {
    const banner = document.getElementById('notificationBanner');
    const messageEl = document.getElementById('notificationMessage');
    
    if (banner && messageEl) {
        banner.className = `notification-banner ${type} show`;
        messageEl.textContent = message;
        
        setTimeout(() => {
            banner.classList.remove('show');
        }, 5000);
    } else {
        // Fallback to alert if banner not found
        alert(message);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// API Functions
async function callAPI(action, data = {}) {
    const url = `../ajax/cycle_management.php`;
    
    const options = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action, ...data })
    };
    
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        return result;
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
}

// Main Data Loading Function
async function loadCycleData() {
    try {
        showLoading(true);
        
        const response = await callAPI('get_cycle_status');
        
        if (response.success) {
            currentCycleData = response.cycle;
            memberPerformanceData = response.performance;
            debtData = response.debt_summary;
            
            updateCycleDisplay(response);
            updateMemberPerformance(response.performance);
            updateDebtSection(response.debt_summary);
            updateQuickStats(response);
            
            showLoading(false);
        } else {
            throw new Error(response.message || 'Failed to load cycle data');
        }
    } catch (error) {
        console.error('Error loading cycle data:', error);
        showAlert('Error loading cycle data: ' + error.message, 'danger');
        showLoading(false);
        
        // Show error state
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
        document.getElementById('mainContent').style.display = 'none';
    }
}

// Display Update Functions
function updateQuickStats(data) {
    const quickStats = document.getElementById('quickStats');
    if (quickStats && data.cycle) {
        // Show quick stats
        quickStats.style.display = 'block';
        
        // Update values
        const familyPoolBalance = document.getElementById('familyPoolBalance');
        const monthlyTarget = document.getElementById('monthlyTarget');
        const totalMembers = document.getElementById('totalMembers');
        const cyclesCompleted = document.getElementById('cyclesCompleted');
        
        if (familyPoolBalance) familyPoolBalance.textContent = `₵${formatNumber(data.cycle.family_pool_balance || 0)}`;
        if (monthlyTarget) monthlyTarget.textContent = `₵${formatNumber(data.cycle.total_target || 0)}`;
        if (totalMembers) totalMembers.textContent = data.cycle.total_members || 0;
        if (cyclesCompleted) cyclesCompleted.textContent = data.cycle.cycles_completed || 0;
    }
}

function updateCycleDisplay(data) {
    if (!data.cycle) return;
    
    const cycle = data.cycle;
    
    // Update cycle status badge
    const statusBadge = document.getElementById('cycleStatusBadge');
    if (statusBadge) {
        statusBadge.textContent = cycle.status || 'Active';
        statusBadge.className = `status-badge ${cycle.status === 'active' ? 'status-active' : 'status-warning'}`;
    }
    
    // Update days remaining
    const daysRemaining = document.getElementById('daysRemaining');
    if (daysRemaining) {
        const days = cycle.days_remaining || 0;
        daysRemaining.textContent = `${days} Days Left`;
        daysRemaining.className = `status-badge ${days > 7 ? 'status-active' : days > 3 ? 'status-warning' : 'status-overdue'}`;
    }
    
    // Update completion percentage
    const completionPercentage = cycle.completion_percentage || 0;
    const completionEl = document.getElementById('completionPercentage');
    const progressEl = document.getElementById('completionProgress');
    
    if (completionEl) completionEl.textContent = `${Math.round(completionPercentage)}%`;
    if (progressEl) {
        const circumference = 2 * Math.PI * 30; // radius = 30
        const offset = circumference - (completionPercentage / 100) * circumference;
        progressEl.style.strokeDashoffset = offset;
    }
    
    // Update statistics
    const totalCollected = document.getElementById('totalCollected');
    const completedMembers = document.getElementById('completedMembers');
    const totalMembersEl = document.getElementById('totalMembers');
    const remainingAmount = document.getElementById('remainingAmount');
    
    if (totalCollected) totalCollected.textContent = formatNumber(cycle.total_collected || 0);
    if (completedMembers) completedMembers.textContent = cycle.members_completed || 0;
    if (totalMembersEl) totalMembersEl.textContent = cycle.total_members || 0;
    if (remainingAmount) remainingAmount.textContent = formatNumber(Math.max(0, (cycle.total_target || 0) - (cycle.total_collected || 0)));
}

function updateMemberPerformance(performance) {
    const grid = document.getElementById('memberPerformanceGrid');
    if (!grid || !performance) return;
    
    grid.innerHTML = '';
    
    if (performance.length === 0) {
        grid.innerHTML = '<div class="empty-state"><p>No member performance data available.</p></div>';
        return;
    }
    
    performance.forEach(member => {
        const card = createMemberPerformanceCard(member);
        grid.appendChild(card);
    });
}

function createMemberPerformanceCard(member) {
    const card = document.createElement('div');
    card.className = 'member-performance-card';
    card.style.cursor = 'pointer';
    
    const progressClass = member.progress_percentage >= 100 ? '' : 
        (member.progress_percentage >= 50 ? 'warning' : 'danger');
        
    const statusIndicator = member.is_completed ? 
        '<span class="completed-indicator">✅ Completed</span>' :
        (member.accumulated_debt > 0 ? 
            `<span class="debt-indicator">⚠️ Debt: ₵${formatNumber(member.accumulated_debt)}</span>` : 
            '');
    
    card.innerHTML = `
        <div class="member-header">
            <div class="member-info">
                <div class="member-avatar">${member.member_name.charAt(0).toUpperCase()}</div>
                <div class="member-details">
                    <h4>${member.member_name}</h4>
                    <div class="member-role">${member.role || member.member_type}</div>
                </div>
            </div>
            ${statusIndicator}
        </div>
        
        <div class="member-progress">
            <div class="progress-text">
                <span>Progress: ${Math.round(member.progress_percentage || 0)}%</span>
                <span>₵${formatNumber(member.contributed_amount || 0)} / ₵${formatNumber(member.target_amount || 0)}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill ${progressClass}" style="width: ${Math.min(member.progress_percentage || 0, 100)}%"></div>
            </div>
        </div>
        
        <div class="member-stats">
            <span>Contributions: ${member.contribution_count || 0}</span>
            <span>Remaining: ₵${formatNumber(Math.max(0, (member.target_amount || 0) - (member.contributed_amount || 0)))}</span>
        </div>
        
        <div class="member-actions" style="margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end;">
            <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;" 
                    onclick="event.stopPropagation(); showMemberDetails(${member.member_id || member.member_only_id}, '${member.member_type}', '${member.member_name}')">
                View Details
            </button>
        </div>
    `;
    
    // Add click handler for the card
    card.onclick = function() {
        showMemberDetails(member.member_id || member.member_only_id, member.member_type, member.member_name);
    };
    
    return card;
}

function updateDebtSection(debtSummary) {
    const debtSection = document.getElementById('debtSection');
    if (!debtSection || !debtSummary) return;
    
    if (debtSummary.total_outstanding <= 0) {
        debtSection.style.display = 'none';
        return;
    }
    
    debtSection.style.display = 'block';
    
    // Update debt statistics
    const totalOutstandingDebt = document.getElementById('totalOutstandingDebt');
    const membersWithDebt = document.getElementById('membersWithDebt');
    const activeDebts = document.getElementById('activeDebts');
    
    if (totalOutstandingDebt) totalOutstandingDebt.textContent = `₵${formatNumber(debtSummary.total_outstanding)}`;
    if (membersWithDebt) membersWithDebt.textContent = debtSummary.members_with_debt || 0;
    if (activeDebts) activeDebts.textContent = debtSummary.active_debts || 0;
    
    // Load debt list
    loadDebtList();
}

// Debt Management Functions
async function loadDebtList() {
    try {
        const response = await callAPI('get_debt_info');
        
        if (response.success) {
            const debtList = document.getElementById('debtList');
            if (!debtList) return;
            
            debtList.innerHTML = '';
            
            if (response.debts.length === 0) {
                debtList.innerHTML = '<div class="empty-state"><p>No active debts found.</p></div>';
                return;
            }
            
            response.debts.forEach(debt => {
                const debtItem = document.createElement('div');
                debtItem.className = 'debt-item';
                debtItem.innerHTML = `
                    <div>
                        <strong>${debt.member_name}</strong>
                        <br>
                        <small>${formatCycleMonth(debt.cycle_month)} - ₵${formatNumber(debt.deficit_amount)} (${debt.days_overdue} days overdue)</small>
                    </div>
                    <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="clearDebt(${debt.id})">
                        Clear
                    </button>
                `;
                debtList.appendChild(debtItem);
            });
        }
    } catch (error) {
        console.error('Error loading debt list:', error);
        const debtList = document.getElementById('debtList');
        if (debtList) {
            debtList.innerHTML = '<div class="empty-state"><p>Error loading debt information.</p></div>';
        }
    }
}

async function clearDebt(debtId) {
    if (!confirm('Are you sure you want to clear this debt?')) return;
    
    try {
        const response = await callAPI('clear_debt', { debt_id: debtId });
        
        if (response.success) {
            showAlert('Debt cleared successfully!', 'success');
            loadCycleData(); // Reload to update debt section
        } else {
            showAlert('Failed to clear debt: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error clearing debt:', error);
        showAlert('Error clearing debt. Please try again.', 'danger');
    }
}

// Cycle Management Functions
async function confirmCloseCycle() {
    const spinner = document.getElementById('closeCycleSpinner');
    const confirmBtn = document.getElementById('confirmCloseBtn');
    
    if (spinner) spinner.style.display = 'inline-block';
    if (confirmBtn) confirmBtn.disabled = true;
    
    try {
        const response = await callAPI('close_cycle', {
            cycle_id: currentCycleData.id
        });
        
        if (response.success) {
            showAlert(`Cycle closed successfully! ${response.debts_created || 0} debt records created.`, 'success');
            closeModal('closeCycleModal');
            loadCycleData(); // Reload to show new cycle
        } else {
            showAlert('Failed to close cycle: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error closing cycle:', error);
        showAlert('Error closing cycle. Please try again.', 'danger');
    } finally {
        if (spinner) spinner.style.display = 'none';
        if (confirmBtn) confirmBtn.disabled = false;
    }
}

function showCloseCycleModal() {
    const modal = document.getElementById('closeCycleModal');
    if (modal) {
        modal.style.display = 'block';
        
        // Show preview of what will happen
        const preview = document.getElementById('closeCyclePreview');
        if (preview && currentCycleData) {
            const incompleteMembersCount = currentCycleData.total_members - currentCycleData.members_completed;
            const remainingAmount = currentCycleData.total_target - currentCycleData.total_collected;
            
            preview.innerHTML = `
                <div style="background: #f8fafc; padding: 16px; border-radius: 6px; margin: 16px 0;">
                    <h4>Impact Preview:</h4>
                    <ul style="margin: 8px 0 0 20px; color: #64748b;">
                        <li><strong>${incompleteMembersCount}</strong> members will have debt added</li>
                        <li><strong>₵${formatNumber(remainingAmount)}</strong> remaining target will be carried as debt</li>
                        <li>New cycle will start for next month</li>
                    </ul>
                </div>
            `;
        }
    }
}

// History and Analytics
async function showCycleHistory() {
    const modal = document.getElementById('cycleHistoryModal');
    const content = document.getElementById('cycleHistoryContent');
    
    if (!modal || !content) return;
    
    modal.style.display = 'block';
    content.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div> Loading history...</div>';
    
    try {
        const response = await callAPI('get_cycle_history');
        
        if (response.success && response.history) {
            let historyHtml = '<div class="cycle-history"><h4>Recent Cycles</h4><div style="margin-top: 16px;">';
            
            if (response.history.length === 0) {
                historyHtml += '<div class="empty-state"><p>No cycle history available.</p></div>';
            } else {
                response.history.forEach(cycle => {
                    const status = cycle.is_closed ? 'Completed' : 'Active';
                    const statusClass = cycle.is_closed ? '' : 'style="color: #059669; font-weight: 600;"';
                    
                    historyHtml += `
                        <div style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px;">
                            <strong>${formatCycleMonth(cycle.cycle_month)}</strong> - 
                            <span ${statusClass}>${status}</span>
                            (${Math.round(cycle.completion_percentage || 0)}% target achieved)
                            <br>
                            <small>
                                ₵${formatNumber(cycle.total_collected || 0)} / ₵${formatNumber(cycle.total_target || 0)} collected • 
                                ${cycle.members_completed || 0}/${(cycle.members_completed || 0) + (cycle.members_pending || 0)} members completed
                            </small>
                        </div>
                    `;
                });
            }
            
            historyHtml += '</div></div>';
            content.innerHTML = historyHtml;
        } else {
            content.innerHTML = '<div class="empty-state"><p>Failed to load cycle history.</p></div>';
        }
    } catch (error) {
        console.error('Error loading cycle history:', error);
        content.innerHTML = '<div class="empty-state"><p>Error loading cycle history.</p></div>';
    }
}

async function showCycleAnalytics() {
    const modal = document.getElementById('analyticsModal');
    const content = document.getElementById('analyticsContent');
    
    if (!modal || !content) return;
    
    modal.style.display = 'block';
    content.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div> Loading analytics...</div>';
    
    try {
        const response = await fetch('../ajax/get_cycle_analytics.php?months=6');
        const result = await response.json();
        
        if (result.success) {
            // Display analytics data
            content.innerHTML = `
                <div class="analytics-summary">
                    <h4>Performance Summary (Last 6 Months)</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin: 16px 0;">
                        <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 6px;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e293b;">${result.analytics.summary.average_completion_rate || 0}%</div>
                            <div style="font-size: 12px; color: #64748b;">Avg. Completion</div>
                        </div>
                        <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 6px;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e293b;">₵${formatNumber(result.analytics.summary.total_collected || 0)}</div>
                            <div style="font-size: 12px; color: #64748b;">Total Collected</div>
                        </div>
                        <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 6px;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e293b;">${result.analytics.summary.cycles_analyzed || 0}</div>
                            <div style="font-size: 12px; color: #64748b;">Cycles Analyzed</div>
                        </div>
                    </div>
                    <p style="color: #64748b; font-size: 14px; margin-top: 16px;">
                        <em>Detailed analytics and charts coming soon!</em>
                    </p>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="empty-state"><p>Failed to load analytics data.</p></div>';
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        content.innerHTML = '<div class="empty-state"><p>Error loading analytics data.</p></div>';
    }
}

// Member Details Functions
async function getMemberContributions(memberId, memberType, cycleId = null) {
    try {
        let url = `../ajax/get_member_contributions.php?member_id=${memberId}&member_type=${memberType}`;
        if (cycleId) {
            url += `&cycle_id=${cycleId}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            return result;
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('Error getting member contributions:', error);
        throw error;
    }
}

async function showMemberDetails(memberId, memberType, memberName) {
    try {
        const contributions = await getMemberContributions(memberId, memberType, currentCycleData?.id);
        
        // Create and show modal with member details
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.display = 'block';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${memberName} - Details</h3>
                    <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                </div>
                <div class="modal-body">
                    <h4>Current Cycle Contributions</h4>
                    <p><strong>Total:</strong> ₵${formatNumber(contributions.summary.total_amount)} 
                       (${contributions.summary.total_count} transactions)</p>
                    
                    <div style="max-height: 300px; overflow-y: auto; margin-top: 16px;">
                        ${contributions.contributions.map(contrib => `
                            <div style="padding: 8px; border-bottom: 1px solid #e2e8f0;">
                                <strong>₵${formatNumber(contrib.amount)}</strong> - 
                                ${contrib.contribution_date} 
                                <small>(${contrib.payment_method})</small>
                                ${contrib.notes ? `<br><small>${contrib.notes}</small>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close modal when clicking outside
        modal.onclick = function(event) {
            if (event.target === modal) {
                modal.remove();
            }
        };
        
    } catch (error) {
        showAlert('Error loading member details: ' + error.message, 'danger');
    }
}

// Data Export
function exportCycleData() {
    showNotification('Export functionality coming soon!', 'info');
}

// Refresh and Auto-refresh
async function refreshCycleData() {
    try {
        await loadCycleData();
        showAlert('Cycle data refreshed successfully!', 'success');
    } catch (error) {
        showAlert('Failed to refresh data: ' + error.message, 'danger');
    }
}

function setupAutoRefresh() {
    let refreshInterval;
    let retryCount = 0;
    const maxRetries = 3;
    const refreshIntervalMs = 5 * 60 * 1000; // 5 minutes
    
    function startRefresh() {
        refreshInterval = setInterval(async () => {
            try {
                await loadCycleData();
                retryCount = 0; // Reset retry count on success
            } catch (error) {
                retryCount++;
                console.error(`Auto-refresh failed (attempt ${retryCount}):`, error);
                
                if (retryCount >= maxRetries) {
                    clearInterval(refreshInterval);
                    showAlert('Auto-refresh disabled due to repeated failures. Please refresh manually.', 'warning');
                }
            }
        }, refreshIntervalMs);
    }
    
    // Start auto-refresh
    startRefresh();
    
    // Restart auto-refresh on visibility change (when user comes back to tab)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && retryCount >= maxRetries) {
            retryCount = 0;
            startRefresh();
        }
    });
}

// Modal Management
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadCycleData().then(() => {
        console.log('Cycle management initialized successfully');
        setupAutoRefresh();
        showNotification('Cycle data loaded successfully!', 'success');
    }).catch(error => {
        console.error('Failed to initialize cycle management:', error);
        showAlert('Failed to initialize cycle management. Please refresh the page.', 'danger');
    });
});

// Export functions for global access
window.CycleManagement = {
    loadCycleData,
    refreshCycleData,
    showMemberDetails,
    getMemberContributions,
    callAPI,
    showCloseCycleModal,
    confirmCloseCycle,
    showCycleHistory,
    showCycleAnalytics,
    clearDebt,
    exportCycleData
};

// Make functions globally accessible for onclick handlers
window.loadCycleData = loadCycleData;
window.refreshCycleData = refreshCycleData;
window.showMemberDetails = showMemberDetails;
window.showCloseCycleModal = showCloseCycleModal;
window.confirmCloseCycle = confirmCloseCycle;
window.showCycleHistory = showCycleHistory;
window.showCycleAnalytics = showCycleAnalytics;
window.clearDebt = clearDebt;
window.exportCycleData = exportCycleData;
window.closeModal = closeModal;