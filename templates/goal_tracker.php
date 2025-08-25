<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Dashboard - Monthly Goal Tracking</title>
    <?php include '../includes/favicon.php'; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        /* Debt Alert Banner - Persistent at top */
        .debt-alert-banner {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
            animation: slideDown 0.3s ease-out;
        }

        .debt-alert-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .debt-alert-text {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .debt-alert-icon {
            font-size: 20px;
            animation: pulse 2s infinite;
        }

        .debt-alert-message {
            font-weight: 600;
            font-size: 14px;
        }

        .debt-alert-details {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 2px;
        }

        .debt-alert-actions {
            display: flex;
            gap: 8px;
        }

        .debt-alert-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .debt-alert-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Monthly Cycle Status */
        .cycle-status-banner {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .cycle-status-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 24px;
            align-items: center;
        }

        .cycle-info h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .cycle-details {
            font-size: 14px;
            opacity: 0.9;
        }

        .cycle-progress {
            text-align: center;
        }

        .cycle-progress-circle {
            width: 80px;
            height: 80px;
            margin: 0 auto 8px;
            position: relative;
        }

        .cycle-progress-text {
            font-size: 12px;
            font-weight: 600;
        }

        .cycle-actions {
            display: flex;
            gap: 8px;
        }

        .cycle-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cycle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .cycle-btn.danger {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }

        /* Main Dashboard */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Member Goal Cards with Enhanced Status */
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .member-goal-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 2px solid transparent;
            position: relative;
            transition: all 0.3s ease;
        }

        .member-goal-card.completed {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
        }

        .member-goal-card.behind {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%);
        }

        .member-goal-card.partial {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }

        .member-status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .member-status-badge.completed {
            background: #10b981;
            color: white;
        }

        .member-status-badge.behind {
            background: #ef4444;
            color: white;
        }

        .member-status-badge.partial {
            background: #f59e0b;
            color: white;
        }

        .member-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .member-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 16px;
        }

        .member-info h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .member-role {
            color: #64748b;
            font-size: 14px;
        }

        .goal-progress-section {
            margin-bottom: 20px;
        }

        .goal-amounts {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .current-amount {
            font-weight: 600;
            color: #1e293b;
        }

        .target-amount {
            color: #64748b;
        }

        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.6s ease;
            position: relative;
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }

        .progress-percentage {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        /* Debt Information */
        .debt-info {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .debt-amount {
            font-weight: 600;
            color: #dc2626;
            font-size: 16px;
        }

        .debt-details {
            font-size: 12px;
            color: #7f1d1d;
            margin-top: 4px;
        }

        /* Member Actions */
        .member-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .member-action-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .btn-contribute {
            background: #3b82f6;
            color: white;
        }

        .btn-contribute:hover {
            background: #2563eb;
        }

        .btn-remind {
            background: #f59e0b;
            color: white;
        }

        .btn-remind:hover {
            background: #d97706;
        }

        .btn-clear-debt {
            background: #10b981;
            color: white;
        }

        .btn-clear-debt:hover {
            background: #059669;
        }

        /* Animations */
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 24px;
            max-width: 400px;
            margin: 5% auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-header h3 {
            font-size: 18px;
            color: #1e293b;
        }

        .close {
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
            transition: color 0.2s;
        }

        .close:hover {
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cycle-status-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 16px;
            }

            .debt-alert-content {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .members-grid {
                grid-template-columns: 1fr;
            }

            .member-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Debt Alert Banner (Shows only when there are members with debt) -->
    <div class="debt-alert-banner" id="debtAlertBanner" style="display: none;">
        <div class="debt-alert-content">
            <div class="debt-alert-text">
                <span class="debt-alert-icon">⚠️</span>
                <div>
                    <div class="debt-alert-message" id="debtAlertMessage">
                        3 family members have outstanding contributions
                    </div>
                    <div class="debt-alert-details" id="debtAlertDetails">
                        Total outstanding: ₵450.00 • 2 months behind
                    </div>
                </div>
            </div>
            <div class="debt-alert-actions">
                <button class="debt-alert-btn" onclick="showDebtSummary()">
                    📊 View Details
                </button>
                <button class="debt-alert-btn" onclick="sendBulkReminders()">
                    📱 Send Reminders
                </button>
            </div>
        </div>
    </div>

    <!-- Monthly Cycle Status Banner -->
    <div class="cycle-status-banner">
        <div class="cycle-status-content">
            <div class="cycle-info">
                <h3 id="cycleTitle">January 2025 Contribution Cycle</h3>
                <div class="cycle-details">
                    <span id="cycleProgress">12 of 15 days remaining</span> • 
                    <span id="cycleMembers">5 of 8 members completed</span>
                </div>
            </div>
            <div class="cycle-progress">
                <div class="cycle-progress-circle">
                    <!-- Progress circle SVG will go here -->
                    <svg width="80" height="80" viewBox="0 0 80 80">
                        <circle cx="40" cy="40" r="35" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>
                        <circle cx="40" cy="40" r="35" fill="none" stroke="white" stroke-width="3" 
                                stroke-dasharray="220" stroke-dashoffset="66" stroke-linecap="round"
                                transform="rotate(-90 40 40)" id="progressCircle"/>
                        <text x="40" y="45" text-anchor="middle" fill="white" font-size="16" font-weight="bold" id="progressText">70%</text>
                    </svg>
                </div>
                <div class="cycle-progress-text">Cycle Progress</div>
            </div>
            <div class="cycle-actions">
                <button class="cycle-btn" onclick="showCycleSummary()">
                    📈 Summary
                </button>
                <button class="cycle-btn danger" onclick="showCloseCycleModal()">
                    🔒 Close Cycle
                </button>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Members Goal Tracking Grid -->
        <div class="members-grid" id="membersGrid">
            <!-- Member cards will be dynamically generated -->
        </div>
    </div>

    <!-- Close Cycle Confirmation Modal -->
    <div id="closeCycleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Close Monthly Cycle</h3>
                <span class="close" onclick="closeModal('closeCycleModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 20px; color: #64748b;">
                    Closing this cycle will:
                </p>
                <ul style="margin-bottom: 20px; padding-left: 20px; color: #64748b;">
                    <li>Calculate debt for members who didn't meet their goals</li>
                    <li>Reset monthly progress for all members</li>
                    <li>Create a new cycle for next month</li>
                    <li>Send notifications to members with outstanding balances</li>
                </ul>
                <div style="background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <strong style="color: #dc2626;">Warning:</strong>
                    <span style="color: #7f1d1d; font-size: 14px;">
                        This action cannot be undone. Make sure all contributions for this month have been recorded.
                    </span>
                </div>
                <button class="btn-primary" onclick="closeMonthlyCycle()">
                    🔒 Close Cycle & Start New Month
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Contribute Modal -->
    <div id="quickContributeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Contribution</h3>
                <span class="close" onclick="closeModal('quickContributeModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="quickContributeForm">
                    <div class="form-group">
                        <label>Member</label>
                        <input type="text" id="contributeMemberName" readonly>
                        <input type="hidden" id="contributeMemberId">
                        <input type="hidden" id="contributeMemberType">
                    </div>
                    <div class="form-group">
                        <label>Amount (₵)</label>
                        <input type="number" id="contributeAmount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" id="contributeNote" placeholder="e.g., Partial payment for January">
                    </div>
                    <button type="submit" class="btn-primary">
                        💰 Add Contribution
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mock data - in real implementation, this would come from PHP
        const mockData = {
            currentCycle: {
                id: 1,
                title: "January 2025 Contribution Cycle",
                daysRemaining: 12,
                totalDays: 31,
                membersCompleted: 5,
                totalMembers: 8,
                totalCollected: 3500,
                totalTarget: 5000,
                completionPercentage: 70
            },
            membersWithDebt: [
                {
                    id: 1,
                    name: "John Nkansah",
                    firstName: "John",
                    memberType: "user",
                    accumulatedDebt: 200.00,
                    monthsBehind: 2,
                    monthlyGoal: 150.00,
                    currentContributed: 50.00,
                    goalMet: false,
                    lastPayment: "2024-12-15"
                },
                {
                    id: 2, 
                    name: "Mary Nkansah",
                    firstName: "Mary",
                    memberType: "member",
                    accumulatedDebt: 150.00,
                    monthsBehind: 1,
                    monthlyGoal: 200.00,
                    currentContributed: 100.00,
                    goalMet: false,
                    lastPayment: "2025-01-10"
                }
            ],
            allMembers: [
                {
                    id: 1,
                    name: "John Nkansah",
                    firstName: "John",
                    memberType: "user",
                    monthlyGoal: 150.00,
                    currentContributed: 50.00,
                    goalMet: false,
                    accumulatedDebt: 200.00,
                    monthsBehind: 2,
                    progressPercentage: 33,
                    status: "behind"
                },
                {
                    id: 2,
                    name: "Mary Nkansah", 
                    firstName: "Mary",
                    memberType: "member",
                    monthlyGoal: 200.00,
                    currentContributed: 100.00,
                    goalMet: false,
                    accumulatedDebt: 150.00,
                    monthsBehind: 1,
                    progressPercentage: 50,
                    status: "partial"
                },
                {
                    id: 3,
                    name: "Sarah Nkansah",
                    firstName: "Sarah", 
                    memberType: "user",
                    monthlyGoal: 180.00,
                    currentContributed: 180.00,
                    goalMet: true,
                    accumulatedDebt: 0,
                    monthsBehind: 0,
                    progressPercentage: 100,
                    status: "completed"
                },
                {
                    id: 4,
                    name: "Peter Nkansah",
                    firstName: "Peter",
                    memberType: "user", 
                    monthlyGoal: 120.00,
                    currentContributed: 120.00,
                    goalMet: true,
                    accumulatedDebt: 0,
                    monthsBehind: 0,
                    progressPercentage: 100,
                    status: "completed"
                }
            ]
        };

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            updateDebtAlert();
            updateCycleStatus();
            renderMemberCards();
        });

        function updateDebtAlert() {
            const debtBanner = document.getElementById('debtAlertBanner');
            const alertMessage = document.getElementById('debtAlertMessage');
            const alertDetails = document.getElementById('debtAlertDetails');
            
            const membersWithDebt = mockData.membersWithDebt;
            
            if (membersWithDebt.length > 0) {
                const totalDebt = membersWithDebt.reduce((sum, member) => sum + member.accumulatedDebt, 0);
                const maxMonthsBehind = Math.max(...membersWithDebt.map(m => m.monthsBehind));
                
                alertMessage.textContent = `${membersWithDebt.length} family member${membersWithDebt.length > 1 ? 's have' : ' has'} outstanding contributions`;
                alertDetails.textContent = `Total outstanding: ₵${totalDebt.toFixed(2)} • ${maxMonthsBehind} month${maxMonthsBehind > 1 ? 's' : ''} behind`;
                
                debtBanner.style.display = 'block';
            } else {
                debtBanner.style.display = 'none';
            }
        }

        function updateCycleStatus() {
            const cycle = mockData.currentCycle;
            
            document.getElementById('cycleTitle').textContent = cycle.title;
            document.getElementById('cycleProgress').textContent = `${cycle.daysRemaining} of ${cycle.totalDays} days remaining`;
            document.getElementById('cycleMembers').textContent = `${cycle.membersCompleted} of ${cycle.totalMembers} members completed`;
            
            // Update progress circle
            const progressCircle = document.getElementById('progressCircle');
            const progressText = document.getElementById('progressText');
            const circumference = 2 * Math.PI * 35; // radius = 35
            const offset = circumference - (cycle.completionPercentage / 100) * circumference;
            
            progressCircle.style.strokeDashoffset = offset;
            progressText.textContent = `${cycle.completionPercentage}%`;
        }

        function renderMemberCards() {
            const grid = document.getElementById('membersGrid');
            grid.innerHTML = '';
            
            mockData.allMembers.forEach(member => {
                const card = createMemberCard(member);
                grid.appendChild(card);
            });
        }

        function createMemberCard(member) {
            const card = document.createElement('div');
            card.className = `member-goal-card ${member.status}`;
            
            const statusText = {
                'completed': 'Goal Met',
                'partial': 'In Progress', 
                'behind': 'Behind'
            };
            
            const remainingAmount = member.monthlyGoal - member.currentContributed;
            
            card.innerHTML = `
                <div class="member-status-badge ${member.status}">
                    ${statusText[member.status]}
                </div>
                
                <div class="member-header">
                    <div class="member-avatar">${member.firstName.charAt(0)}</div>
                    <div class="member-info">
                        <h3>${member.name}</h3>
                        <div class="member-role">Family Member</div>
                    </div>
                </div>
                
                ${member.accumulatedDebt > 0 ? `
                <div class="debt-info">
                    <div class="debt-amount">₵${member.accumulatedDebt.toFixed(2)} debt</div>
                    <div class="debt-details">
                        ${member.monthsBehind} month${member.monthsBehind > 1 ? 's' : ''} behind • 
                        Last payment: ${member.lastPayment || 'Never'}
                    </div>
                </div>
                ` : ''}
                
                <div class="goal-progress-section">
                    <div class="goal-amounts">
                        <span class="current-amount">₵${member.currentContributed.toFixed(2)}</span>
                        <span class="target-amount"> / ₵${member.monthlyGoal.toFixed(2)}</span>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill ${member.status === 'completed' ? '' : member.status === 'partial' ? 'warning' : 'danger'}" 
                             style="width: ${Math.min(member.progressPercentage, 100)}%"></div>
                    </div>
                    
                    <div class="progress-percentage">
                        ${member.progressPercentage}% complete
                        ${!member.goalMet ? ` • ₵${remainingAmount.toFixed(2)} remaining` : ''}
                    </div>
                </div>
                
                <div class="member-actions">
                    ${!member.goalMet ? `
                        <button class="member-action-btn btn-contribute" onclick="quickContribute(${member.id}, '${member.name}', '${member.memberType}')">
                            💰 Contribute
                        </button>
                        <button class="member-action-btn btn-remind" onclick="sendReminder(${member.id}, '${member.memberType}')">
                            📱 Remind
                        </button>
                    ` : ''}
                    ${member.accumulatedDebt > 0 ? `
                        <button class="member-action-btn btn-clear-debt" onclick="clearDebt(${member.id}, '${member.memberType}')">
                            ✅ Clear Debt
                        </button>
                    ` : ''}
                </div>
            `;
            
            return card;
        }

        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showCloseCycleModal() {
            showModal('closeCycleModal');
        }

        function quickContribute(memberId, memberName, memberType) {
            document.getElementById('contributeMemberId').value = memberId;
            document.getElementById('contributeMemberName').value = memberName;
            document.getElementById('contributeMemberType').value = memberType;
            document.getElementById('contributeAmount').value = '';
            document.getElementById('contributeNote').value = '';
            showModal('quickContributeModal');
        }

        // Action functions
        function sendReminder(memberId, memberType) {
            // In real implementation, this would make an AJAX call
            showNotification(`Reminder sent to member!`, 'success');
        }

        function clearDebt(memberId, memberType) {
            if (confirm('Are you sure you want to clear this member\'s debt? This action cannot be undone.')) {
                // In real implementation, this would make an AJAX call
                showNotification(`Debt cleared successfully!`, 'success');
                
                // Update the member data and re-render
                const member = mockData.allMembers.find(m => m.id === memberId);
                if (member) {
                    member.accumulatedDebt = 0;
                    member.monthsBehind = 0;
                    if (member.status === 'behind' && member.progressPercentage > 0) {
                        member.status = 'partial';
                    }
                }
                
                // Update debt alert and re-render cards
                mockData.membersWithDebt = mockData.membersWithDebt.filter(m => m.id !== memberId);
                updateDebtAlert();
                renderMemberCards();
            }
        }

        function closeMonthlyCycle() {
            if (confirm('Are you sure you want to close this monthly cycle? This will calculate debt for incomplete members and start a new cycle.')) {
                // In real implementation, this would make an AJAX call
                showNotification('Monthly cycle closed successfully! New cycle started.', 'success');
                closeModal('closeCycleModal');
                
                // Simulate cycle closure - reset progress and update debt
                mockData.allMembers.forEach(member => {
                    if (!member.goalMet) {
                        const deficit = member.monthlyGoal - member.currentContributed;
                        member.accumulatedDebt += deficit;
                        member.monthsBehind += 1;
                    }
                    member.currentContributed = 0;
                    member.goalMet = false;
                    member.progressPercentage = 0;
                    member.status = member.accumulatedDebt > 0 ? 'behind' : 'partial';
                });
                
                // Update cycle info
                mockData.currentCycle.title = "February 2025 Contribution Cycle";
                mockData.currentCycle.daysRemaining = 28;
                mockData.currentCycle.totalDays = 28;
                mockData.currentCycle.membersCompleted = 0;
                mockData.currentCycle.completionPercentage = 0;
                
                // Re-render everything
                updateDebtAlert();
                updateCycleStatus();
                renderMemberCards();
            }
        }

        function showDebtSummary() {
            // In real implementation, this would show a detailed debt summary modal
            alert('Debt Summary:\n\n' + 
                  mockData.membersWithDebt.map(m => 
                      `${m.name}: ₵${m.accumulatedDebt.toFixed(2)} (${m.monthsBehind} months behind)`
                  ).join('\n'));
        }

        function sendBulkReminders() {
            if (confirm(`Send payment reminders to ${mockData.membersWithDebt.length} members with outstanding contributions?`)) {
                showNotification(`Reminders sent to ${mockData.membersWithDebt.length} members!`, 'success');
            }
        }

        function showCycleSummary() {
            const cycle = mockData.currentCycle;
            alert(`Cycle Summary:\n\n` +
                  `Progress: ${cycle.completionPercentage}% complete\n` +
                  `Collected: ₵${cycle.totalCollected.toFixed(2)} / ₵${cycle.totalTarget.toFixed(2)}\n` +
                  `Members: ${cycle.membersCompleted} / ${cycle.totalMembers} completed\n` +
                  `Time: ${cycle.daysRemaining} days remaining`);
        }

        // Form submission
        document.getElementById('quickContributeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const memberId = parseInt(document.getElementById('contributeMemberId').value);
            const amount = parseFloat(document.getElementById('contributeAmount').value);
            const note = document.getElementById('contributeNote').value;
            
            if (amount <= 0) {
                showNotification('Please enter a valid amount', 'error');
                return;
            }
            
            // In real implementation, this would make an AJAX call
            showNotification(`Contribution of ₵${amount.toFixed(2)} recorded successfully!`, 'success');
            
            // Update member data
            const member = mockData.allMembers.find(m => m.id === memberId);
            if (member) {
                member.currentContributed += amount;
                member.progressPercentage = Math.min((member.currentContributed / member.monthlyGoal) * 100, 100);
                
                if (member.currentContributed >= member.monthlyGoal) {
                    member.goalMet = true;
                    member.status = 'completed';
                    mockData.currentCycle.membersCompleted++;
                } else if (member.currentContributed > 0) {
                    member.status = member.accumulatedDebt > 0 ? 'partial' : 'partial';
                }
                
                // Update cycle progress
                const totalContributed = mockData.allMembers.reduce((sum, m) => sum + m.currentContributed, 0);
                mockData.currentCycle.totalCollected = totalContributed;
                mockData.currentCycle.completionPercentage = Math.round((totalContributed / mockData.currentCycle.totalTarget) * 100);
            }
            
            closeModal('quickContributeModal');
            updateCycleStatus();
            renderMemberCards();
        });

        // Utility function to show notifications
        function showNotification(message, type = 'info') {
            // Create notification element
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
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close any open modal
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        closeModal(modal.id);
                    }
                });
            }
        });
    </script>
</body>
</html>