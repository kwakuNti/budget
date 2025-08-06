// Global variables for goal tracking
let goalDashboardData = {}; // Renamed to avoid conflict
let currentMemberData = {};

// Initialize enhanced dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadMonthlyGoalDashboard();
    initializeEventListeners();
    
    // Auto-refresh every 5 minutes
    setInterval(loadMonthlyGoalDashboard, 300000);
});

// Load dashboard data with monthly goal tracking
async function loadMonthlyGoalDashboard() {
    try {
        showLoadingState();
        
        const response = await fetch('../api/goal_tracker.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_dashboard_data'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Raw response:', text); // Debug log
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success) {
            goalDashboardData = data.data;
            updateEnhancedDashboard();
            hideLoadingState();
        } else {
            throw new Error(data.message || 'Failed to load dashboard data');
        }
    } catch (error) {
        console.error('Error loading monthly goal dashboard:', error);
        showNotification('Error loading dashboard data: ' + error.message, 'error');
        hideLoadingState();
    }
}

// Update the enhanced dashboard UI
function updateEnhancedDashboard() {
    updateDebtAlertBanner();
    updateCycleStatusBanner();
    updateMemberGoalCards();
    updateCycleProgress();
}

// Update debt alert banner - Fixed positioning and data
// Enhanced debt banner positioning and utility functions

// Update debt alert banner with proper positioning
function updateDebtAlertBanner() {
    const debtBanner = document.getElementById('debtAlertBanner');
    const alertMessage = document.getElementById('debtAlertMessage');
    const alertDetails = document.getElementById('debtAlertDetails');
    
    const membersWithDebt = goalDashboardData.membersWithDebt || [];
    
    // Filter out admin/system members, logged-in user, and those without actual debt
    const actualMembersWithDebt = membersWithDebt.filter(member => {
        const hasDebt = parseFloat(member.accumulated_debt || 0) > 0;
        const isBehind = parseInt(member.months_behind || 0) > 0;
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        
        return (hasDebt || isBehind) && isNotAdmin && isNotSystemUser && isNotLoggedInUser;
    });
    
    if (actualMembersWithDebt.length > 0) {
        const totalDebt = actualMembersWithDebt.reduce((sum, member) => sum + parseFloat(member.accumulated_debt || 0), 0);
        const maxMonthsBehind = Math.max(...actualMembersWithDebt.map(m => parseInt(m.months_behind || 0)));
        
        // Update banner content
        if (alertMessage) {
            alertMessage.textContent = `${actualMembersWithDebt.length} family member${actualMembersWithDebt.length > 1 ? 's have' : ' has'} outstanding contributions`;
        }
        
        if (alertDetails) {
            alertDetails.textContent = `Total outstanding: ₵${totalDebt.toFixed(2)} • ${maxMonthsBehind} month${maxMonthsBehind > 1 ? 's' : ''} behind`;
        }
        
        // Show banner and add body class for spacing
        if (debtBanner) {
            debtBanner.style.display = 'block';
            document.body.classList.add('debt-banner-active');
        }
    } else {
        // Hide banner and remove body class
        if (debtBanner) {
            debtBanner.style.display = 'none';
            document.body.classList.remove('debt-banner-active');
        }
    }
}

// Enhanced show cycle summary with proper modal
function showCycleSummary() {
    const cycle = goalDashboardData.currentCycle;
    if (!cycle) {
        showNotification('No cycle data available', 'error');
        return;
    }
    
    const members = goalDashboardData.memberPerformance || [];
    const completedMembers = members.filter(m => m.is_completed && m.role !== 'admin').length;
    const totalMembers = members.filter(m => m.role !== 'admin' && !m.full_name.toLowerCase().includes('nkansah family')).length;
    const pendingAmount = cycle.totalTarget - cycle.totalCollected;
    
    const modalContent = `
        <div class="cycle-summary-modal-content">
            <div class="cycle-summary-header">
                <h2>📈 ${cycle.title} Summary</h2>
                <p>Complete overview of this month's contribution cycle</p>
            </div>
            
            <div class="cycle-summary-stats">
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">💰</div>
                    <div class="cycle-stat-value">₵${cycle.totalCollected.toFixed(2)}</div>
                    <div class="cycle-stat-label">Total Collected</div>
                    <div class="cycle-stat-target">of ₵${cycle.totalTarget.toFixed(2)} target</div>
                </div>
                
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">📊</div>
                    <div class="cycle-stat-value">${Math.round(cycle.completionPercentage)}%</div>
                    <div class="cycle-stat-label">Progress</div>
                    <div class="cycle-stat-target">${completedMembers}/${totalMembers} completed</div>
                </div>
                
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">⏰</div>
                    <div class="cycle-stat-value">${cycle.daysRemaining}</div>
                    <div class="cycle-stat-label">Days Left</div>
                    <div class="cycle-stat-target">of ${cycle.totalDays} days</div>
                </div>
                
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">💸</div>
                    <div class="cycle-stat-value">₵${pendingAmount.toFixed(2)}</div>
                    <div class="cycle-stat-label">Remaining</div>
                    <div class="cycle-stat-target">to reach target</div>
                </div>
            </div>
            
            <div class="cycle-member-breakdown">
                <h4>Member Status</h4>
                <div class="member-status-grid">
                    ${members.filter(m => m.role !== 'admin' && !m.full_name.toLowerCase().includes('nkansah family')).map(member => `
                        <div class="member-status-item ${member.is_completed ? 'completed' : 'pending'}">
                            <div class="member-status-avatar">${(member.first_name || 'M').charAt(0).toUpperCase()}</div>
                            <div class="member-status-info">
                                <div class="member-status-name">${member.full_name}</div>
                                <div class="member-status-progress">₵${parseFloat(member.contributed_amount || 0).toFixed(2)} / ₵${parseFloat(member.target_amount || 0).toFixed(2)}</div>
                            </div>
                            <div class="member-status-badge">
                                ${member.is_completed ? '✅' : '⏳'}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <div class="cycle-summary-actions">
                <button class="btn btn-secondary" onclick="closeModal('cycleSummaryModal')">Close</button>
                ${cycle.daysRemaining <= 3 && !cycle.isClosed ? `
                    <button class="btn btn-primary" onclick="closeModal('cycleSummaryModal'); showCloseCycleModal();">
                        🔒 Close Cycle
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    const modalStyles = `
        <style>
            .cycle-summary-modal .modal-content {
                max-width: 800px;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .cycle-summary-header {
                text-align: center;
                padding: 24px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                margin: -20px -20px 20px -20px;
                border-radius: 12px 12px 0 0;
            }
            
            .cycle-summary-header h2 {
                margin: 0 0 8px 0;
                font-size: 20px;
                font-weight: 600;
            }
            
            .cycle-summary-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 14px;
            }
            
            .cycle-summary-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            
            .cycle-stat-card {
                text-align: center;
                padding: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .cycle-stat-icon {
                font-size: 24px;
                margin-bottom: 8px;
            }
            
            .cycle-stat-value {
                font-size: 20px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 4px;
            }
            
            .cycle-stat-label {
                font-size: 14px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 4px;
            }
            
            .cycle-stat-target {
                font-size: 12px;
                color: #6b7280;
            }
            
            .cycle-member-breakdown {
                margin-bottom: 24px;
            }
            
            .cycle-member-breakdown h4 {
                margin: 0 0 16px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1f2937;
            }
            
            .member-status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 12px;
            }
            
            .member-status-item {
                display: flex;
                align-items: center;
                padding: 12px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .member-status-item.completed {
                border-color: #10b981;
                background: #f0fdf4;
            }
            
            .member-status-item.pending {
                border-color: #f59e0b;
                background: #fffbeb;
            }
            
            .member-status-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #3b82f6;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 14px;
                margin-right: 12px;
            }
            
            .member-status-info {
                flex: 1;
            }
            
            .member-status-name {
                font-size: 14px;
                font-weight: 500;
                color: #1f2937;
                margin-bottom: 2px;
            }
            
            .member-status-progress {
                font-size: 12px;
                color: #6b7280;
            }
            
            .member-status-badge {
                font-size: 16px;
            }
            
            .cycle-summary-actions {
                display: flex;
                justify-content: center;
                gap: 12px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
            }
            
            @media (max-width: 768px) {
                .cycle-summary-stats {
                    grid-template-columns: repeat(2, 1fr);
                }
                
                .member-status-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    `;
    
    showCustomModal('cycleSummaryModal', 'Cycle Summary', modalContent + modalStyles);
}

// Enhanced custom modal function
function showCustomModal(modalId, title, content) {
    // Remove existing modal if any
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        document.body.removeChild(existingModal);
    }
    
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal cycle-summary-modal';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('${modalId}')">&times;</span>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Show modal with animation
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Trigger animation
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Close on outside click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal(modalId);
        }
    });
    
    // Close on escape key
    const escapeHandler = function(e) {
        if (e.key === 'Escape') {
            closeModal(modalId);
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
}

// Enhanced close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Animate out
        modal.classList.remove('show');
        
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Remove custom modals completely
            if (modalId.includes('Summary') || modalId.includes('Custom')) {
                if (document.body.contains(modal)) {
                    document.body.removeChild(modal);
                }
            }
        }, 300);
    }
}

// Utility function to toggle debt banner visibility
function toggleDebtBanner(show) {
    const debtBanner = document.getElementById('debtAlertBanner');
    
    if (show) {
        if (debtBanner) {
            debtBanner.style.display = 'block';
            document.body.classList.add('debt-banner-active');
        }
    } else {
        if (debtBanner) {
            debtBanner.style.display = 'none';
            document.body.classList.remove('debt-banner-active');
        }
    }
}

// Function to refresh dashboard data after actions
async function refreshDashboardData() {
    try {
        await loadMonthlyGoalDashboard();
        showNotification('Dashboard data refreshed', 'success');
    } catch (error) {
        console.error('Error refreshing dashboard:', error);
        showNotification('Error refreshing dashboard data', 'error');
    }
}

// Enhanced notification system
function showNotification(message, type = 'info', duration = 3000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    });
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    
    const colors = {
        'success': '#10b981',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#3b82f6'
    };
    
    notification.style.cssText = `
        position: fixed;
        top: ${document.body.classList.contains('debt-banner-active') ? '80px' : '20px'};
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 3000;
        font-weight: 500;
        max-width: 350px;
        word-wrap: break-word;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    `;
    
    notification.innerHTML = `
        <span>${icons[type]}</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Slide in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Slide out and remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Function to handle responsive layout adjustments
function handleResponsiveLayout() {
    const isMobile = window.innerWidth <= 768;
    const debtBanner = document.getElementById('debtAlertBanner');
    
    if (debtBanner && debtBanner.style.display === 'block') {
        if (isMobile) {
            document.body.style.setProperty('--debt-banner-height', '80px');
        } else {
            document.body.style.setProperty('--debt-banner-height', '60px');
        }
    }
}

// Add resize listener for responsive adjustments
window.addEventListener('resize', handleResponsiveLayout);

// Function to validate member data before actions
function validateMemberAction(memberId, memberOnlyId, memberType, actionType) {
    if (!memberId && !memberOnlyId) {
        showNotification('Invalid member information', 'error');
        return false;
    }
    
    if (!memberType || !['user', 'member'].includes(memberType)) {
        showNotification('Invalid member type', 'error');
        return false;
    }
    
    if (!actionType) {
        showNotification('Invalid action type', 'error');
        return false;
    }
    
    return true;
}

// Enhanced error handling for API calls
async function handleApiCall(apiCall, successMessage, errorPrefix = 'Operation failed') {
    try {
        const result = await apiCall();
        if (result && result.success) {
            showNotification(successMessage, 'success');
            await refreshDashboardData();
            return result;
        } else {
            throw new Error(result?.message || 'Unknown error occurred');
        }
    } catch (error) {
        console.error(`${errorPrefix}:`, error);
        showNotification(`${errorPrefix}: ${error.message}`, 'error');
        throw error;
    }
}

// Function to format currency consistently
function formatCurrency(amount, currency = 'GHS') {
    const formatted = parseFloat(amount || 0).toFixed(2);
    return `₵${formatted}`;
}

// Function to calculate progress color
function getProgressColor(percentage, isCompleted = false) {
    if (isCompleted) return '#10b981'; // Green
    if (percentage >= 75) return '#10b981'; // Green
    if (percentage >= 50) return '#f59e0b'; // Yellow
    if (percentage >= 25) return '#f97316'; // Orange
    return '#ef4444'; // Red
}

// Initialize all enhanced features when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Set up responsive layout
    handleResponsiveLayout();
    
    // Initialize tooltips if needed
    initializeTooltips();
    
    // Set up keyboard shortcuts
    setupKeyboardShortcuts();
});

// Initialize tooltips for better UX
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const text = event.target.getAttribute('data-tooltip');
    if (!text) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 4000;
        pointer-events: none;
        white-space: nowrap;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    event.target._tooltip = tooltip;
}

function hideTooltip(event) {
    if (event.target._tooltip) {
        document.body.removeChild(event.target._tooltip);
        event.target._tooltip = null;
    }
}

// Setup keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + R to refresh dashboard
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            refreshDashboardData();
        }
        
        // Escape to close all modals
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="display: block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

// Update cycle status banner
// Enhanced dashboard functions with proper member filtering and modal summaries

// Update cycle status banner with proper member filtering
function updateCycleStatusBanner() {
    const cycle = goalDashboardData.currentCycle;
    if (!cycle) return;
    
    const cycleTitle = document.getElementById('cycleTitle');
    const cycleProgress = document.getElementById('cycleProgress');
    const cycleMembers = document.getElementById('cycleMembers');
    
    // Filter members properly - exclude admin, system users, and logged-in user
    const members = goalDashboardData.memberPerformance || [];
    const filteredMembers = members.filter(member => {
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        const hasGoal = parseFloat(member.target_amount || 0) > 0;
        
        return isNotAdmin && isNotSystemUser && isNotLoggedInUser && hasGoal;
    });
    
    const completedMembers = filteredMembers.filter(m => m.is_completed).length;
    const totalMembers = filteredMembers.length;
    
    if (cycleTitle) cycleTitle.textContent = cycle.title;
    if (cycleProgress) cycleProgress.textContent = `${cycle.daysRemaining} of ${cycle.totalDays} days remaining`;
    if (cycleMembers) cycleMembers.textContent = `${completedMembers} of ${totalMembers} members completed`;
}

// Enhanced cycle summary modal with proper member filtering and detailed information
function showCycleSummary() {
    const cycle = goalDashboardData.currentCycle;
    if (!cycle) {
        showNotification('No cycle data available', 'error');
        return;
    }
    
    // Filter members properly
    const allMembers = goalDashboardData.memberPerformance || [];
    const members = allMembers.filter(member => {
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        const hasGoal = parseFloat(member.target_amount || 0) > 0;
        
        return isNotAdmin && isNotSystemUser && isNotLoggedInUser && hasGoal;
    });
    
    const completedMembers = members.filter(m => m.is_completed).length;
    const totalMembers = members.length;
    const pendingMembers = totalMembers - completedMembers;
    
    // Calculate totals from filtered members
    const totalTarget = members.reduce((sum, m) => sum + parseFloat(m.target_amount || 0), 0);
    const totalCollected = members.reduce((sum, m) => sum + parseFloat(m.contributed_amount || 0), 0);
    const pendingAmount = totalTarget - totalCollected;
    const completionPercentage = totalTarget > 0 ? Math.round((totalCollected / totalTarget) * 100) : 0;
    
    const modalContent = `
        <div class="cycle-summary-modal-content">
            <div class="cycle-summary-header">
                <h2>📈 ${cycle.title} Summary</h2>
                <p>Complete overview of this month's contribution cycle</p>
            </div>
            
            <div class="cycle-summary-stats">
                <div class="cycle-stat-card collected">
                    <div class="cycle-stat-icon">💰</div>
                    <div class="cycle-stat-value">₵${totalCollected.toFixed(2)}</div>
                    <div class="cycle-stat-label">Total Collected</div>
                    <div class="cycle-stat-target">of ₵${totalTarget.toFixed(2)} target</div>
                </div>
                
                <div class="cycle-stat-card progress">
                    <div class="cycle-stat-icon">📊</div>
                    <div class="cycle-stat-value">${completionPercentage}%</div>
                    <div class="cycle-stat-label">Progress</div>
                    <div class="cycle-stat-target">${completedMembers}/${totalMembers} completed</div>
                </div>
                
                <div class="cycle-stat-card time">
                    <div class="cycle-stat-icon">⏰</div>
                    <div class="cycle-stat-value">${cycle.daysRemaining}</div>
                    <div class="cycle-stat-label">Days Left</div>
                    <div class="cycle-stat-target">of ${cycle.totalDays} days</div>
                </div>
                
                <div class="cycle-stat-card pending">
                    <div class="cycle-stat-icon">💸</div>
                    <div class="cycle-stat-value">₵${pendingAmount.toFixed(2)}</div>
                    <div class="cycle-stat-label">Remaining</div>
                    <div class="cycle-stat-target">to reach target</div>
                </div>
            </div>
            
            <div class="cycle-performance-breakdown">
                <div class="performance-overview">
                    <div class="performance-stat">
                        <span class="stat-number">${completedMembers}</span>
                        <span class="stat-label">Completed</span>
                    </div>
                    <div class="performance-stat">
                        <span class="stat-number">${pendingMembers}</span>
                        <span class="stat-label">Pending</span>
                    </div>
                    <div class="performance-stat">
                        <span class="stat-number">${Math.round(totalCollected / totalMembers)}</span>
                        <span class="stat-label">Avg. Contribution</span>
                    </div>
                </div>
            </div>
            
            <div class="cycle-member-breakdown">
                <h4>Member Status (${totalMembers} Active Members)</h4>
                <div class="member-status-grid">
                    ${members.map(member => `
                        <div class="member-status-item ${member.is_completed ? 'completed' : 'pending'}">
                            <div class="member-status-avatar ${member.is_completed ? 'completed' : 'pending'}">${(member.first_name || 'M').charAt(0).toUpperCase()}</div>
                            <div class="member-status-info">
                                <div class="member-status-name">${member.full_name}</div>
                                <div class="member-status-role">${getRoleDisplayName(member.role)}</div>
                                <div class="member-status-progress">₵${parseFloat(member.contributed_amount || 0).toFixed(2)} / ₵${parseFloat(member.target_amount || 0).toFixed(2)}</div>
                                ${parseFloat(member.accumulated_debt || 0) > 0 ? `<div class="member-debt-indicator">₵${parseFloat(member.accumulated_debt).toFixed(2)} debt</div>` : ''}
                            </div>
                            <div class="member-status-badge">
                                ${member.is_completed ? '✅' : '⏳'}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <div class="cycle-summary-actions">
                <button class="btn btn-secondary" onclick="closeModal('cycleSummaryModal')">Close</button>
                ${cycle.daysRemaining <= 3 && !cycle.isClosed ? `
                    <button class="btn btn-warning" onclick="closeModal('cycleSummaryModal'); showCloseCycleModal();">
                        🔒 Close Cycle
                    </button>
                ` : ''}
                ${pendingMembers > 0 ? `
                    <button class="btn btn-primary" onclick="closeModal('cycleSummaryModal'); showDebtSummary();">
                        📊 View Pending
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    const modalStyles = `
        <style>
            .cycle-summary-modal .modal-content {
                max-width: 900px;
                max-height: 85vh;
                overflow-y: auto;
            }
            
            .cycle-summary-header {
                text-align: center;
                padding: 24px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                margin: -20px -20px 20px -20px;
                border-radius: 12px 12px 0 0;
            }
            
            .cycle-summary-header h2 {
                margin: 0 0 8px 0;
                font-size: 22px;
                font-weight: 600;
            }
            
            .cycle-summary-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 14px;
            }
            
            .cycle-summary-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            
            .cycle-stat-card {
                text-align: center;
                padding: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                transition: transform 0.2s ease;
            }
            
            .cycle-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            
            .cycle-stat-card.collected {
                border-color: #10b981;
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            }
            
            .cycle-stat-card.progress {
                border-color: #3b82f6;
                background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            }
            
            .cycle-stat-card.time {
                border-color: #f59e0b;
                background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            }
            
            .cycle-stat-card.pending {
                border-color: #ef4444;
                background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
            }
            
            .cycle-stat-icon {
                font-size: 28px;
                margin-bottom: 12px;
            }
            
            .cycle-stat-value {
                font-size: 24px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 6px;
            }
            
            .cycle-stat-label {
                font-size: 14px;
                font-weight: 600;
                color: #374151;
                margin-bottom: 4px;
            }
            
            .cycle-stat-target {
                font-size: 12px;
                color: #6b7280;
            }
            
            .cycle-performance-breakdown {
                margin-bottom: 24px;
                padding: 20px;
                background: #f9fafb;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
            }
            
            .performance-overview {
                display: flex;
                justify-content: space-around;
                align-items: center;
            }
            
            .performance-stat {
                text-align: center;
            }
            
            .performance-stat .stat-number {
                display: block;
                font-size: 28px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 4px;
            }
            
            .performance-stat .stat-label {
                font-size: 12px;
                color: #6b7280;
                font-weight: 500;
                text-transform: uppercase;
            }
            
            .cycle-member-breakdown {
                margin-bottom: 24px;
            }
            
            .cycle-member-breakdown h4 {
                margin: 0 0 16px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1f2937;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .member-status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 12px;
                max-height: 300px;
                overflow-y: auto;
                padding: 8px;
            }
            
            .member-status-item {
                display: flex;
                align-items: center;
                padding: 14px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                transition: all 0.2s ease;
            }
            
            .member-status-item:hover {
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            }
            
            .member-status-item.completed {
                border-color: #10b981;
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            }
            
            .member-status-item.pending {
                border-color: #f59e0b;
                background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            }
            
            .member-status-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 16px;
                margin-right: 12px;
                flex-shrink: 0;
            }
            
            .member-status-avatar.completed {
                background: #10b981;
            }
            
            .member-status-avatar.pending {
                background: #f59e0b;
            }
            
            .member-status-info {
                flex: 1;
                min-width: 0;
            }
            
            .member-status-name {
                font-size: 14px;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .member-status-role {
                font-size: 11px;
                color: #6b7280;
                background: rgba(0,0,0,0.05);
                padding: 2px 6px;
                border-radius: 4px;
                font-weight: 500;
                display: inline-block;
                margin-bottom: 4px;
            }
            
            .member-status-progress {
                font-size: 12px;
                color: #4b5563;
                font-weight: 500;
            }
            
            .member-debt-indicator {
                font-size: 10px;
                color: #dc2626;
                background: #fef2f2;
                padding: 2px 6px;
                border-radius: 4px;
                font-weight: 600;
                display: inline-block;
                margin-top: 2px;
            }
            
            .member-status-badge {
                font-size: 18px;
                margin-left: 8px;
            }
            
            .cycle-summary-actions {
                display: flex;
                justify-content: center;
                gap: 12px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            
            .btn-secondary {
                background: #6b7280;
                color: white;
            }
            
            .btn-secondary:hover {
                background: #4b5563;
            }
            
            .btn-primary {
                background: #3b82f6;
                color: white;
            }
            
            .btn-primary:hover {
                background: #2563eb;
            }
            
            .btn-warning {
                background: #f59e0b;
                color: white;
            }
            
            .btn-warning:hover {
                background: #d97706;
            }
            
            @media (max-width: 768px) {
                .cycle-summary-stats {
                    grid-template-columns: repeat(2, 1fr);
                }
                
                .member-status-grid {
                    grid-template-columns: 1fr;
                }
                
                .performance-overview {
                    flex-direction: column;
                    gap: 16px;
                }
                
                .cycle-summary-actions {
                    flex-direction: column;
                }
            }
        </style>
    `;
    
    showCustomModal('cycleSummaryModal', 'Cycle Summary', modalContent + modalStyles);
}

// Enhanced debt alert banner update with proper filtering
function updateDebtAlertBanner() {
    const debtBanner = document.getElementById('debtAlertBanner');
    const alertMessage = document.getElementById('debtAlertMessage');
    const alertDetails = document.getElementById('debtAlertDetails');
    
    const membersWithDebt = goalDashboardData.membersWithDebt || [];
    
    // Filter out admin/system members, logged-in user, and those without actual debt
    const actualMembersWithDebt = membersWithDebt.filter(member => {
        const hasDebt = parseFloat(member.accumulated_debt || 0) > 0;
        const isBehind = parseInt(member.months_behind || 0) > 0;
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        
        return (hasDebt || isBehind) && isNotAdmin && isNotSystemUser && isNotLoggedInUser;
    });
    
    if (actualMembersWithDebt.length > 0) {
        const totalDebt = actualMembersWithDebt.reduce((sum, member) => sum + parseFloat(member.accumulated_debt || 0), 0);
        const maxMonthsBehind = Math.max(...actualMembersWithDebt.map(m => parseInt(m.months_behind || 0)));
        
        // Update banner content
        if (alertMessage) {
            alertMessage.textContent = `${actualMembersWithDebt.length} family member${actualMembersWithDebt.length > 1 ? 's have' : ' has'} outstanding contributions`;
        }
        
        if (alertDetails) {
            alertDetails.textContent = `Total outstanding: ₵${totalDebt.toFixed(2)} • ${maxMonthsBehind} month${maxMonthsBehind > 1 ? 's' : ''} behind`;
        }
        
        // Show banner and add body class for spacing
        if (debtBanner) {
            debtBanner.style.display = 'block';
            document.body.classList.add('debt-banner-active');
        }
    } else {
        // Hide banner and remove body class
        if (debtBanner) {
            debtBanner.style.display = 'none';
            document.body.classList.remove('debt-banner-active');
        }
    }
}

// Enhanced member goal cards update with proper filtering
function updateMemberGoalCards() {
    const grid = document.getElementById('membersGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    let members = goalDashboardData.memberPerformance || [];
    
    // Filter out admin members, system users, and logged-in user
    members = members.filter(member => {
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        const hasGoal = parseFloat(member.target_amount || 0) > 0;
        
        return isNotAdmin && isNotSystemUser && isNotLoggedInUser && hasGoal;
    });
    
    if (members.length === 0) {
        grid.innerHTML = `
            <div class="no-members-message">
                <div class="no-members-icon">👥</div>
                <h3>No Active Family Members</h3>
                <p>No family members with contribution goals found for this cycle.</p>
            </div>
        `;
        return;
    }
    
    members.forEach(member => {
        const card = createEnhancedMemberCard(member);
        grid.appendChild(card);
    });
}

// Enhanced notification with better positioning considering debt banner
function showNotification(message, type = 'info', duration = 3000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    });
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    
    const colors = {
        'success': '#10b981',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#3b82f6'
    };
    
    // Calculate top position based on debt banner visibility
    const debtBannerActive = document.body.classList.contains('debt-banner-active');
    const topPosition = debtBannerActive ? '80px' : '20px';
    
    notification.style.cssText = `
        position: fixed;
        top: ${topPosition};
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 3000;
        font-weight: 500;
        max-width: 350px;
        word-wrap: break-word;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    `;
    
    notification.innerHTML = `
        <span>${icons[type]}</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Slide in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Slide out and remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Add CSS for no members message
const additionalStyles = `
<style>
.no-members-message {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px dashed #d1d5db;
}

.no-members-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.no-members-message h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    color: #374151;
}

.no-members-message p {
    margin: 0;
    font-size: 14px;
}
</style>
`;

// Inject additional styles
if (!document.getElementById('additionalDashboardStyles')) {
    const styleElement = document.createElement('style');
    styleElement.id = 'additionalDashboardStyles';
    styleElement.innerHTML = additionalStyles;
    document.head.appendChild(styleElement);
}

// Update cycle progress circle
function updateCycleProgress() {
    const cycle = goalDashboardData.currentCycle;
    if (!cycle) return;
    
    const progressCircle = document.getElementById('progressCircle');
    const progressText = document.getElementById('progressText');
    
    if (progressCircle && progressText) {
        const circumference = 2 * Math.PI * 35; // radius = 35
        const offset = circumference - (cycle.completionPercentage / 100) * circumference;
        
        progressCircle.style.strokeDashoffset = offset;
        progressText.textContent = `${Math.round(cycle.completionPercentage)}%`;
    }
}

// Update member goal cards - Fixed to exclude admin members and logged-in user
function updateMemberGoalCards() {
    const grid = document.getElementById('membersGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    let members = goalDashboardData.memberPerformance || [];
    
    // Filter out admin members, system users, and logged-in user
    members = members.filter(member => {
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        const hasGoal = parseFloat(member.target_amount || 0) > 0;
        
        return isNotAdmin && isNotSystemUser && isNotLoggedInUser && hasGoal;
    });
    
    if (members.length === 0) {
        grid.innerHTML = '<div style="text-align: center; padding: 40px; color: #64748b;">No family members found</div>';
        return;
    }
    
    members.forEach(member => {
        const card = createEnhancedMemberCard(member);
        grid.appendChild(card);
    });
}

// Create enhanced member card with debt tracking - Improved design
function createEnhancedMemberCard(member) {
    const card = document.createElement('div');
    
    // Determine member status
    let status = 'partial';
    if (member.is_completed) {
        status = 'completed';
    } else if (parseFloat(member.accumulated_debt || 0) > 0 || parseInt(member.months_behind || 0) > 0) {
        status = 'behind';
    }
    
    card.className = `member-goal-card ${status}`;
    
    const statusText = {
        'completed': 'Goal Met',
        'partial': 'In Progress',
        'behind': 'Behind'
    };
    
    const progressPercentage = Math.min(parseFloat(member.progress_percentage || 0), 100);
    const remainingAmount = parseFloat(member.target_amount || 0) - parseFloat(member.contributed_amount || 0);
    const accumulatedDebt = parseFloat(member.accumulated_debt || 0);
    const monthsBehind = parseInt(member.months_behind || 0);
    
    // Get role display name
    const roleDisplayName = getRoleDisplayName(member.role);
    
    card.innerHTML = `
        <div class="member-status-badge ${status}">
            ${statusText[status]}
        </div>
        
        <div class="member-header">
            <div class="member-avatar">${(member.first_name || 'M').charAt(0).toUpperCase()}</div>
            <div class="member-info">
                <h3>${member.full_name || 'Unknown Member'}</h3>
                <div class="member-role">${roleDisplayName}</div>
            </div>
        </div>
        
        ${accumulatedDebt > 0 ? `
        <div class="debt-info">
            <div class="debt-amount">₵${accumulatedDebt.toFixed(2)} debt</div>
            <div class="debt-details">
                ${monthsBehind} month${monthsBehind > 1 ? 's' : ''} behind
                ${member.last_contribution_date ? `• Last payment: ${formatDate(member.last_contribution_date)}` : '• No payments yet'}
            </div>
        </div>
        ` : ''}
        
        <div class="goal-progress-section">
            <div class="goal-amounts">
                <span class="current-amount">₵${parseFloat(member.contributed_amount || 0).toFixed(2)}</span>
                <span class="target-amount"> / ₵${parseFloat(member.target_amount || 0).toFixed(2)}</span>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill ${status === 'completed' ? '' : status === 'partial' ? 'warning' : 'danger'}" 
                     style="width: ${progressPercentage}%"></div>
            </div>
            
            <div class="progress-percentage">
                ${Math.round(progressPercentage)}% complete
                ${!member.is_completed && remainingAmount > 0 ? ` • ₵${remainingAmount.toFixed(2)} remaining` : ''}
            </div>
        </div>
        
        <div class="member-actions">
            ${!member.is_completed ? `
                <button class="member-action-btn btn-contribute" onclick="quickContribute('${member.member_id || ''}', '${member.member_only_id || ''}', '${member.full_name}', '${member.member_type}')">
                    💰 Contribute
                </button>
                <button class="member-action-btn btn-remind" onclick="sendMemberReminder('${member.member_id || ''}', '${member.member_only_id || ''}', '${member.member_type}')">
                    📱 Remind
                </button>
            ` : ''}
            ${accumulatedDebt > 0 ? `
                <button class="member-action-btn btn-clear-debt" onclick="clearMemberDebt('${member.member_id || ''}', '${member.member_only_id || ''}', '${member.member_type}', '${member.full_name}')">
                    ✅ Clear Debt
                </button>
            ` : ''}
        </div>
    `;
    
    return card;
}

// Helper function to get role display name
function getRoleDisplayName(role) {
    const roleMap = {
        'parent': 'Parent',
        'child': 'Child',
        'spouse': 'Spouse',
        'sibling': 'Sibling',
        'member': 'Member',
        'head': 'Family Head',
        'admin': 'Admin',
        'other': 'Family Member'
    };
    
    return roleMap[role] || 'Family Member';
}

// Show debt summary - Enhanced modal with better styling
function showDebtSummary() {
    const membersWithDebt = goalDashboardData.membersWithDebt || [];
    
    // Filter out admin members and logged-in user
    const actualMembersWithDebt = membersWithDebt.filter(member => {
        const hasDebt = parseFloat(member.accumulated_debt || 0) > 0;
        const isBehind = parseInt(member.months_behind || 0) > 0;
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        const isNotLoggedInUser = !member.full_name.toLowerCase().includes('nkansah family');
        
        return (hasDebt || isBehind) && isNotAdmin && isNotSystemUser && isNotLoggedInUser;
    });
    
    if (actualMembersWithDebt.length === 0) {
        showNotification('No outstanding debts found', 'info');
        return;
    }
    
    let totalDebt = 0;
    let modalContent = `
        <div class="debt-summary-modal-content">
            <div class="debt-summary-header">
                <h2>💰 Outstanding Contributions Summary</h2>
                <p>Review family members who have pending payments</p>
            </div>
            <div class="debt-summary-list">
    `;
    
    actualMembersWithDebt.forEach(member => {
        const debt = parseFloat(member.accumulated_debt || 0);
        const months = parseInt(member.months_behind || 0);
        const roleDisplay = getRoleDisplayName(member.role);
        totalDebt += debt;
        
        modalContent += `
            <div class="debt-summary-item">
                <div class="debt-member-info">
                    <div class="debt-member-avatar">${(member.full_name || 'M').charAt(0).toUpperCase()}</div>
                    <div class="debt-member-details">
                        <h4>${member.full_name}</h4>
                        <span class="debt-member-role">${roleDisplay}</span>
                        ${member.last_payment_date ? `<div class="last-payment">Last payment: ${formatDate(member.last_payment_date)}</div>` : '<div class="last-payment">No payments yet</div>'}
                    </div>
                </div>
                <div class="debt-amount-info">
                    <div class="debt-amount">₵${debt.toFixed(2)}</div>
                    <div class="debt-months">${months} month${months > 1 ? 's' : ''} behind</div>
                </div>
                <div class="debt-actions">
                    <button class="btn-small btn-contribute" onclick="quickContribute('${member.member_id || ''}', '${member.member_only_id || ''}', '${member.full_name}', '${member.member_type || 'user'}'); closeModal('debtSummaryModal');">
                        💰 Pay
                    </button>
                    <button class="btn-small btn-remind" onclick="sendMemberReminder('${member.member_id || ''}', '${member.member_only_id || ''}', '${member.member_type || 'user'}'); closeModal('debtSummaryModal');">
                        📱 Remind
                    </button>
                </div>
            </div>
        `;
    });
    
    modalContent += `
            </div>
            <div class="debt-summary-footer">
                <div class="debt-total">
                    <strong>Total Outstanding: ₵${totalDebt.toFixed(2)}</strong>
                </div>
                <div class="debt-summary-actions">
                    <button class="btn btn-secondary" onclick="closeModal('debtSummaryModal')">Close</button>
                    <button class="btn btn-primary" onclick="sendBulkReminders(); closeModal('debtSummaryModal');">
                        📱 Send All Reminders
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add enhanced modal styles
    const modalStyles = `
        <style>
            .debt-summary-modal .modal-content {
                max-width: 800px;
                width: 90vw;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .debt-summary-modal-content {
                padding: 0;
            }
            
            .debt-summary-header {
                padding: 24px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                color: white;
                margin: -20px -20px 20px -20px;
            }
            
            .debt-summary-header h2 {
                margin: 0 0 8px 0;
                font-size: 20px;
                font-weight: 600;
            }
            
            .debt-summary-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 14px;
            }
            
            .debt-summary-list {
                max-height: 400px;
                overflow-y: auto;
                margin-bottom: 20px;
            }
            
            .debt-summary-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                margin-bottom: 12px;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .debt-member-info {
                display: flex;
                align-items: center;
                flex: 1;
            }
            
            .debt-member-avatar {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: #ef4444;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 18px;
                margin-right: 16px;
            }
            
            .debt-member-details h4 {
                margin: 0 0 4px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1f2937;
            }
            
            .debt-member-role {
                font-size: 12px;
                color: #6b7280;
                background: #f3f4f6;
                padding: 2px 8px;
                border-radius: 4px;
                font-weight: 500;
            }
            
            .last-payment {
                font-size: 11px;
                color: #9ca3af;
                margin-top: 4px;
            }
            
            .debt-amount-info {
                text-align: center;
                margin: 0 16px;
            }
            
            .debt-amount {
                font-size: 18px;
                font-weight: 700;
                color: #ef4444;
                margin-bottom: 4px;
            }
            
            .debt-months {
                font-size: 12px;
                color: #7f1d1d;
                background: #fef2f2;
                padding: 2px 8px;
                border-radius: 4px;
            }
            
            .debt-actions {
                display: flex;
                gap: 8px;
            }
            
            .btn-small {
                padding: 6px 12px;
                font-size: 12px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
                font-weight: 500;
            }
            
            .btn-small.btn-contribute {
                background: #10b981;
                color: white;
            }
            
            .btn-small.btn-contribute:hover {
                background: #059669;
            }
            
            .btn-small.btn-remind {
                background: #f59e0b;
                color: white;
            }
            
            .btn-small.btn-remind:hover {
                background: #d97706;
            }
            
            .debt-summary-footer {
                border-top: 1px solid #e5e7eb;
                padding: 20px 0 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .debt-total {
                font-size: 18px;
                color: #1f2937;
            }
            
            .debt-summary-actions {
                display: flex;
                gap: 12px;
            }
            
            @media (max-width: 768px) {
                .debt-summary-item {
                    flex-direction: column;
                    gap: 12px;
                    text-align: center;
                }
                
                .debt-member-info {
                    flex-direction: column;
                    text-align: center;
                }
                
                .debt-member-avatar {
                    margin-right: 0;
                    margin-bottom: 8px;
                }
                
                .debt-summary-footer {
                    flex-direction: column;
                    gap: 16px;
                    text-align: center;
                }
            }
        </style>
    `;
    
    // Create and show modal
    showCustomModal('debtSummaryModal', 'Debt Summary', modalContent + modalStyles);
}

// Show cycle summary with enhanced modal
function showCycleSummary() {
    const cycle = goalDashboardData.currentCycle;
    if (!cycle) {
        showNotification('No cycle data available', 'error');
        return;
    }
    
    const members = goalDashboardData.memberPerformance || [];
    const completedMembers = members.filter(m => m.is_completed).length;
    const totalMembers = members.length;
    
    const modalContent = `
        <div class="cycle-summary-modal-content">
            <div class="cycle-summary-header">
                <h2>📈 ${cycle.title} Summary</h2>
                <p>Complete overview of this month's contribution cycle</p>
            </div>
            
            <div class="cycle-summary-stats">
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">💰</div>
                    <div class="cycle-stat-value">₵${cycle.totalCollected.toFixed(2)}</div>
                    <div class="cycle-stat-label">Total Collected</div>
                    <div class="cycle-stat-target">of ₵${cycle.totalTarget.toFixed(2)} target</div>
                </div>
                
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">📊</div>
                    <div class="cycle-stat-value">${Math.round(cycle.completionPercentage)}%</div>
                    <div class="cycle-stat-label">Progress</div>
                    <div class="cycle-stat-target">${completedMembers}/${totalMembers} completed</div>
                </div>
                
                <div class="cycle-stat-card">
                    <div class="cycle-stat-icon">⏰</div>
                    <div class="cycle-stat-value">${cycle.daysRemaining}</div>
                    <div class="cycle-stat-label">Days Left</div>
                    <div class="cycle-stat-target">of ${cycle.totalDays} days</div>
                </div>
            </div>
            
            <div class="cycle-summary-actions">
                <button class="btn btn-secondary" onclick="closeModal('cycleSummaryModal')">Close</button>
                ${cycle.daysRemaining <= 3 && !cycle.isClosed ? `
                    <button class="btn btn-primary" onclick="closeModal('cycleSummaryModal'); showCloseCycleModal();">
                        🔒 Close Cycle
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    const modalStyles = `
        <style>
            .cycle-summary-modal .modal-content {
                max-width: 600px;
            }
            
            .cycle-summary-header {
                text-align: center;
                padding: 24px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                margin: -20px -20px 20px -20px;
            }
            
            .cycle-summary-header h2 {
                margin: 0 0 8px 0;
                font-size: 20px;
                font-weight: 600;
            }
            
            .cycle-summary-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 14px;
            }
            
            .cycle-summary-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            
            .cycle-stat-card {
                text-align: center;
                padding: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .cycle-stat-icon {
                font-size: 24px;
                margin-bottom: 8px;
            }
            
            .cycle-stat-value {
                font-size: 24px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 4px;
            }
            
            .cycle-stat-label {
                font-size: 14px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 4px;
            }
            
            .cycle-stat-target {
                font-size: 12px;
                color: #6b7280;
            }
            
            .cycle-summary-actions {
                display: flex;
                justify-content: center;
                gap: 12px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
            }
        </style>
    `;
    
    showCustomModal('cycleSummaryModal', 'Cycle Summary', modalContent + modalStyles);
}

// Custom modal function with enhanced styling
function showCustomModal(modalId, title, content) {
    // Remove existing modal if any
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        document.body.removeChild(existingModal);
    }
    
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal debt-summary-modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('${modalId}')">&times;</span>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Close on outside click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal(modalId);
        }
    });
    
    // Animate in
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
}

// Initialize event listeners
function initializeEventListeners() {
    // Form submissions
    const quickContributeForm = document.getElementById('quickContributeForm');
    if (quickContributeForm) {
        quickContributeForm.addEventListener('submit', handleQuickContribution);
    }
    
    // Modal close events
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
        
        if (e.target.classList.contains('close')) {
            const modal = e.target.closest('.modal');
            if (modal) closeModal(modal.id);
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModal(modal.id);
                }
            });
        }
    });
}

// Handle quick contribution form submission
async function handleQuickContribution(e) {
    e.preventDefault();
    
    const memberId = document.getElementById('contributeMemberId').value;
    const memberOnlyId = document.getElementById('contributeMemberOnlyId').value;
    const memberType = document.getElementById('contributeMemberType').value;
    const amount = parseFloat(document.getElementById('contributeAmount').value);
    const note = document.getElementById('contributeNote').value;
    
    if (!amount || amount <= 0) {
        showNotification('Please enter a valid amount', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        
        // Add member info based on type
        if (memberType === 'user' && memberId) {
            formData.append('member_id', memberId);
        } else if (memberType === 'member' && memberOnlyId) {
            formData.append('member_only_id', memberOnlyId);
        } else {
            throw new Error('Invalid member information');
        }
        
        formData.append('amount', amount);
        formData.append('notes', note);
        formData.append('action', 'add_contribution');
        
        const response = await fetch('../api/contribution_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        console.log('Contribution response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid response from server');
        }
        
        if (data.success) {
            showNotification(`Contribution of ₵${amount.toFixed(2)} recorded successfully!`, 'success');
            closeModal('quickContributeModal');
            loadMonthlyGoalDashboard(); // Refresh data
        } else {
            throw new Error(data.message || 'Failed to record contribution');
        }
    } catch (error) {
        console.error('Error recording contribution:', error);
        showNotification('Error recording contribution: ' + error.message, 'error');
    }
}

// Quick contribute function
function quickContribute(memberId, memberOnlyId, memberName, memberType) {
    document.getElementById('contributeMemberId').value = memberId || '';
    document.getElementById('contributeMemberOnlyId').value = memberOnlyId || '';
    document.getElementById('contributeMemberName').value = memberName;
    document.getElementById('contributeMemberType').value = memberType;
    document.getElementById('contributeAmount').value = '';
    document.getElementById('contributeNote').value = '';
    showModal('quickContributeModal');
}

// Send reminder to specific member
async function sendMemberReminder(memberId, memberOnlyId, memberType) {
    try {
        const formData = new FormData();
        if (memberType === 'user') {
            formData.append('member_id', memberId);
        } else {
            formData.append('member_only_id', memberOnlyId);
        }
        formData.append('member_type', memberType);
        formData.append('reminder_type', 'gentle');
        formData.append('action', 'send_reminder');
        
        const response = await fetch('../api/goal_tracker.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            throw new Error(data.message || 'Failed to send reminder');
        }
    } catch (error) {
        console.error('Error sending reminder:', error);
        showNotification('Error sending reminder: ' + error.message, 'error');
    }
}

// Clear member debt
async function clearMemberDebt(memberId, memberOnlyId, memberType, memberName) {
    if (!confirm(`Are you sure you want to clear ${memberName}'s debt? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        if (memberType === 'user') {
            formData.append('member_id', memberId);
        } else {
            formData.append('member_only_id', memberOnlyId);
        }
        formData.append('member_type', memberType);
        formData.append('action', 'clear_member_debt');
        
        const response = await fetch('../api/goal_tracker.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            loadMonthlyGoalDashboard(); // Refresh data
        } else {
            throw new Error(data.message || 'Failed to clear debt');
        }
    } catch (error) {
        console.error('Error clearing debt:', error);
        showNotification('Error clearing debt: ' + error.message, 'error');
    }
}

// Close monthly cycle
async function closeMonthlyCycle() {
    if (!confirm('Are you sure you want to close this monthly cycle? This will calculate debt for incomplete members and start a new cycle.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'close_cycle');
        
        const response = await fetch('../api/goal_tracker.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('closeCycleModal');
            loadMonthlyGoalDashboard(); // Refresh data
        } else {
            throw new Error(data.message || 'Failed to close cycle');
        }
    } catch (error) {
        console.error('Error closing cycle:', error);
        showNotification('Error closing cycle: ' + error.message, 'error');
    }
}
function openCyclePage() {
    window.location.href = 'cycle.php';
}

// Send bulk reminders
async function sendBulkReminders() {
    const membersWithDebt = goalDashboardData.membersWithDebt || [];
    
    // Filter out admin members
    const actualMembersWithDebt = membersWithDebt.filter(member => {
        const hasDebt = parseFloat(member.accumulated_debt || 0) > 0;
        const isBehind = parseInt(member.months_behind || 0) > 0;
        const isNotAdmin = member.role !== 'admin';
        const isNotSystemUser = !member.full_name.toLowerCase().includes('admin');
        
        return (hasDebt || isBehind) && isNotAdmin && isNotSystemUser;
    });
    
    if (actualMembersWithDebt.length === 0) {
        showNotification('No members with outstanding contributions found', 'info');
        return;
    }
    
    if (!confirm(`Send payment reminders to ${actualMembersWithDebt.length} members with outstanding contributions?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('bulk_send', 'true');
        formData.append('reminder_type', 'gentle');
        formData.append('action', 'send_reminder');
        
        const response = await fetch('../api/goal_tracker.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            throw new Error(data.message || 'Failed to send reminders');
        }
    } catch (error) {
        console.error('Error sending bulk reminders:', error);
        showNotification('Error sending reminders: ' + error.message, 'error');
    }
}

// Show cycle summary
function showCycleSummary() {
    const cycle = goalDashboardData.currentCycle;
    if (!cycle) {
        showNotification('No cycle data available', 'error');
        return;
    }
    
    const summary = `Cycle Summary:\n\n` +
                   `Progress: ${Math.round(cycle.completionPercentage)}% complete\n` +
                   `Collected: ₵${cycle.totalCollected.toFixed(2)} / ₵${cycle.totalTarget.toFixed(2)}\n` +
                   `Members: ${cycle.membersCompleted} / ${cycle.totalMembers} completed\n` +
                   `Time: ${cycle.daysRemaining} days remaining`;
    
    alert(summary);
}

// Show close cycle modal
function showCloseCycleModal() {
    showModal('closeCycleModal');
}

// Utility functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
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
        
        // Remove custom modals
        if (modalId.includes('Summary') || modalId.includes('Custom')) {
            document.body.removeChild(modal);
        }
    }
}

function showLoadingState() {
    // Add loading spinner or state
    const loadingIndicator = document.createElement('div');
    loadingIndicator.id = 'loadingIndicator';
    loadingIndicator.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    background: rgba(255,255,255,0.9); padding: 20px; border-radius: 8px; 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999;">
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; 
                           border-top: 4px solid #3b82f6; border-radius: 50%; 
                           animation: spin 1s linear infinite; margin: 0 auto 12px;"></div>
                <div>Loading dashboard...</div>
            </div>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    document.body.appendChild(loadingIndicator);
}

function hideLoadingState() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        document.body.removeChild(loadingIndicator);
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 3000;
        font-weight: 500;
        max-width: 300px;
        word-wrap: break-word;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}