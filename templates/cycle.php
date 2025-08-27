<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family - Cycle Management</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="css/cycle-management.css">
        <title>Cycle Management Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #334155;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .cycle-management-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .cycle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .cycle-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cycle-status {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .cycle-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .cycle-stat-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .cycle-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #10b981);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .progress-ring {
            width: 80px;
            height: 80px;
            margin: 0 auto 12px;
            position: relative;
        }

        .progress-ring svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 6;
            cx: 40;
            cy: 40;
            r: 30;
        }

        .progress-ring .progress {
            stroke: #10b981;
            stroke-dasharray: 188.5;
            stroke-dashoffset: 188.5;
            transition: stroke-dashoffset 1s ease;
            stroke-linecap: round;
        }

        .progress-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .member-performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .member-performance-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .member-performance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .member-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .member-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }

        .member-details h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .member-role {
            font-size: 12px;
            color: #64748b;
            text-transform: capitalize;
            font-weight: 500;
        }

        .member-progress {
            margin: 16px 0;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 4px;
            transition: width 0.8s ease;
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .member-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .debt-indicator {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        .completed-indicator {
            background: #dcfce7;
            color: #166534;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        .debt-section {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .debt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .debt-title {
            font-size: 18px;
            font-weight: 600;
            color: #991b1b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .debt-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .debt-stat {
            text-align: center;
            background: white;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #fecaca;
        }

        .debt-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #991b1b;
            margin-bottom: 4px;
        }

        .debt-stat-label {
            font-size: 11px;
            color: #7f1d1d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .debt-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .debt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid #fecaca;
        }

        .cycle-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #4b5563;
            transform: translateY(-1px);
        }

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
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }

        .modal-body {
            padding: 24px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .close {
            color: #64748b;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .close:hover {
            color: #1e293b;
            background: #e2e8f0;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .loading-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: #64748b;
        }

        .loading-container .loading-spinner {
            width: 24px;
            height: 24px;
            margin-right: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .empty-state h3 {
            color: #374151;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .cycle-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .cycle-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .member-performance-grid {
                grid-template-columns: 1fr;
            }
            
            .debt-summary {
                grid-template-columns: 1fr;
            }

            .cycle-actions {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>
    <style>
        /* Additional styles for the complete page */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .page-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }

        .page-header p {
            margin: 8px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .breadcrumb {
            background: #f8fafc;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .breadcrumb .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .breadcrumb-list {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }

        .breadcrumb-list li {
            color: #64748b;
        }

        .breadcrumb-list li:not(:last-child)::after {
            content: '→';
            margin-left: 8px;
            color: #94a3b8;
        }

        .breadcrumb-list a {
            color: #3b82f6;
            text-decoration: none;
        }

        .breadcrumb-list a:hover {
            text-decoration: underline;
        }

        .quick-stats {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .quick-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
        }

        .quick-stat {
            text-align: center;
        }

        .quick-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .quick-stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .page-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-bottom: 24px;
        }

        .notification-banner {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            display: none;
        }

        .notification-banner.show {
            display: block;
        }

        .notification-banner.success {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .notification-banner.warning {
            background: #fef3c7;
            border-color: #fde68a;
            color: #92400e;
        }

        .notification-banner.danger {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px 0;
                text-align: center;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .page-actions {
                flex-direction: column;
            }

            .quick-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb-list">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="family">Family</a></li>
                <li>Cycle Management</li>
            </ul>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Monthly Cycle Management</h1>
            <p>Track and manage monthly contribution cycles for your family</p>
        </div>
    </div>

    <div class="container">
        <!-- Notification Banner -->
        <div id="notificationBanner" class="notification-banner">
            <span id="notificationMessage"></span>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats" id="quickStats" style="display: none;">
            <div class="quick-stats-grid">
                <div class="quick-stat">
                    <div class="quick-stat-value" id="familyPoolBalance">₵0</div>
                    <div class="quick-stat-label">Family Pool</div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-value" id="monthlyTarget">₵0</div>
                    <div class="quick-stat-label">Monthly Target</div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-value" id="totalMembers">0</div>
                    <div class="quick-stat-label">Active Members</div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-value" id="cyclesCompleted">0</div>
                    <div class="quick-stat-label">Cycles Completed</div>
                </div>
            </div>
        </div>

        <!-- Page Actions -->
        <div class="page-actions">
            <button class="btn btn-secondary" onclick="exportCycleData()">
                📊 Export Data
            </button>
            <button class="btn btn-primary" onclick="refreshCycleData()">
                🔄 Refresh
            </button>
        </div>

        <!-- Main Cycle Management Section -->
        <div class="cycle-management-section">
            <div class="cycle-header">
                <div class="cycle-title">
                    📅 Current Monthly Cycle
                </div>
                <div class="cycle-status">
                    <span class="status-badge status-active" id="cycleStatusBadge">Loading...</span>
                    <span id="daysRemaining" class="status-badge status-warning">-- Days Left</span>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="loading-container">
                <div class="loading-spinner"></div>
                Loading cycle data...
            </div>

            <!-- Error State -->
            <div id="errorState" class="empty-state" style="display: none;">
                <h3>⚠️ Unable to Load Cycle Data</h3>
                <p>There was an error loading the cycle information. Please try refreshing the page.</p>
                <button class="btn btn-primary" onclick="loadCycleData()">Try Again</button>
            </div>

            <!-- Main Content (hidden initially) -->
            <div id="mainContent" style="display: none;">
                <!-- Cycle Statistics -->
                <div class="cycle-stats-grid">
                    <div class="cycle-stat-card">
                        <div class="progress-ring">
                            <svg viewBox="0 0 80 80">
                                <circle cx="40" cy="40" r="30"></circle>
                                <circle cx="40" cy="40" r="30" class="progress" id="completionProgress"></circle>
                            </svg>
                            <div class="progress-percentage" id="completionPercentage">0%</div>
                        </div>
                        <div class="stat-label">Completion Rate</div>
                    </div>

                    <div class="cycle-stat-card">
                        <div class="stat-value">₵<span id="totalCollected">0</span></div>
                        <div class="stat-label">Total Collected</div>
                    </div>

                    <div class="cycle-stat-card">
                        <div class="stat-value"><span id="completedMembers">0</span>/<span id="totalMembers">0</span></div>
                        <div class="stat-label">Members Completed</div>
                    </div>

                    <div class="cycle-stat-card">
                        <div class="stat-value">₵<span id="remainingAmount">0</span></div>
                        <div class="stat-label">Remaining Target</div>
                    </div>
                </div>

                <!-- Member Performance -->
                <div class="member-performance-grid" id="memberPerformanceGrid">
                    <!-- Will be populated by JavaScript -->
                </div>

                <!-- Debt Section (only shown if there are debts) -->
                <div class="debt-section" id="debtSection" style="display: none;">
                    <div class="debt-header">
                        <div class="debt-title">
                            ⚠️ Outstanding Debts
                        </div>
                    </div>
                    
                    <div class="debt-summary">
                        <div class="debt-stat">
                            <div class="debt-stat-value" id="totalOutstandingDebt">₵0</div>
                            <div class="debt-stat-label">Total Outstanding</div>
                        </div>
                        <div class="debt-stat">
                            <div class="debt-stat-value" id="membersWithDebt">0</div>
                            <div class="debt-stat-label">Members with Debt</div>
                        </div>
                        <div class="debt-stat">
                            <div class="debt-stat-value" id="activeDebts">0</div>
                            <div class="debt-stat-label">Active Debts</div>
                        </div>
                    </div>

                    <div class="debt-list" id="debtList">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Cycle Actions -->
                <div class="cycle-actions">
                    <button class="btn btn-secondary" onclick="showCycleHistory()">
                        📊 View History
                    </button>
                    <button class="btn btn-secondary" onclick="showCycleAnalytics()">
                        📈 Analytics
                    </button>
                    <button class="btn btn-primary" onclick="refreshCycleData()">
                        🔄 Refresh
                    </button>
                    <button class="btn btn-danger" onclick="showCloseCycleModal()" id="closeCycleBtn">
                        🔒 Close Cycle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Close Cycle Confirmation Modal -->
    <div id="closeCycleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Close Monthly Cycle</h3>
                <span class="close" onclick="closeModal('closeCycleModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>Warning:</strong> Closing this cycle will:
                    <ul style="margin: 8px 0 0 20px;">
                        <li>Mark all incomplete members as having debt</li>
                        <li>Reset monthly contributions to zero</li>
                        <li>Create a new cycle for next month</li>
                        <li>This action cannot be undone</li>
                    </ul>
                </div>
                
                <div id="closeCyclePreview">
                    <!-- Will show preview of what will happen -->
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button class="btn btn-secondary" onclick="closeModal('closeCycleModal')" style="margin-right: 12px;">
                        Cancel
                    </button>
                    <button class="btn btn-danger" onclick="confirmCloseCycle()" id="confirmCloseBtn">
                        <span id="closeCycleSpinner" class="loading-spinner" style="display: none;"></span>
                        Yes, Close Cycle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cycle History Modal -->
    <div id="cycleHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cycle History</h3>
                <span class="close" onclick="closeModal('cycleHistoryModal')">&times;</span>
            </div>
            <div class="modal-body" id="cycleHistoryContent">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Analytics Modal -->
    <div id="analyticsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cycle Analytics</h3>
                <span class="close" onclick="closeModal('analyticsModal')">&times;</span>
            </div>
            <div class="modal-body" id="analyticsContent">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>



    <!-- Include external scripts if using separate files -->
    <!-- <script src="js/cycle-management.js"></script> -->
    <script src="../public/js/cycle_integration.js"></script>
</body>
</html>