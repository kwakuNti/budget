<?php
// dashboard.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    header("Location: login.php");
    exit;
}

// Get user and family information
$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];

try {
    // Get user info
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Get family info
    $stmt = $conn->prepare("SELECT family_name FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $family_id);
    $stmt->execute();
    $family = $stmt->get_result()->fetch_assoc();

    if (!$user || !$family) {
        throw new Exception("User or family not found");
    }
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    header('Location: login.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($family['family_name']); ?> - Dashboard</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        /* Additional styles for better presentation */
        .no-members,
        .no-activity {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .member-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .member-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .member-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 12px;
        }

        .member-info h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .member-role {
            font-size: 14px;
            color: #6b7280;
        }

        .member-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .member-stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .member-stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .member-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #10b981;
            transition: width 0.3s ease;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 18px;
        }

        .activity-icon.contribution {
            background: #dcfce7;
        }

        .activity-icon.expense {
            background: #fee2e2;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .activity-description {
            font-size: 14px;
            color: #6b7280;
        }

        .activity-amount {
            font-weight: 600;
        }

        .activity-amount.positive {
            color: #10b981;
        }

        .activity-amount.negative {
            color: #ef4444;
        }

        .stat-change.positive {
            color: #10b981;
        }

        .stat-change.negative {
            color: #ef4444;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .members-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .members-overview {
                grid-template-columns: 1fr;
            }

            .member-stats {
                grid-template-columns: 1fr;
            }
        }

        .debt-alert-banner {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    position: relative;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.debt-banner-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.debt-icon {
    font-size: 20px;
    animation: shake 2s infinite;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

.debt-info {
    flex: 1;
}

.debt-message {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 2px;
}

.debt-details {
    font-size: 12px;
    opacity: 0.9;
}

.debt-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.debt-action-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.debt-action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.debt-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.debt-close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}


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
            background-color: #ef4444;
        }
        
        .cycle-btn.success {
            background-color: #10b981;
        }
        
        .cycle-btn.success:hover {
            background-color: #059669;
        }

        .member-goal-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 2px solid transparent;
            position: relative;
            transition: all 0.3s ease;
            margin-bottom: 20px;
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

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

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

            .member-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>


    <!-- Monthly Cycle Status Banner -->
<div class="cycle-status-banner" id="cycleStatusBanner" onclick="showCycleSummary()" style="cursor: pointer;">
        <div class="cycle-status-content">
            <div class="cycle-info">
                <h3 id="cycleTitle">Loading cycle information...</h3>
                <div class="cycle-details">
                    <span id="cycleProgress">Loading...</span> •
                    <span id="cycleMembers">Loading...</span>
                </div>
            </div>
            <div class="cycle-progress">
                <div class="cycle-progress-circle">
                    <svg width="80" height="80" viewBox="0 0 80 80">
                        <circle cx="40" cy="40" r="35" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="3" />
                        <circle cx="40" cy="40" r="35" fill="none" stroke="white" stroke-width="3"
                            stroke-dasharray="220" stroke-dashoffset="220" stroke-linecap="round"
                            transform="rotate(-90 40 40)" id="progressCircle" />
                        <text x="40" y="45" text-anchor="middle" fill="white" font-size="16" font-weight="bold" id="progressText">0%</text>
                    </svg>
                </div>
                <div class="cycle-progress-text">Cycle Progress</div>
            </div>
            <div class="cycle-actions">
                <button class="cycle-btn" onclick="showCycleSummary()">
                    📈 Summary
                </button>
                <button class="cycle-btn danger" onclick="showCloseCycleModal()" id="closeCycleBtn">
                    🔒 Close Cycle
                </button>
                <button class="cycle-btn success" onclick="showStartNewCycleModal()" id="startCycleBtn" style="display: none;">
                    🚀 Start New Cycle
                </button>
            </div>
        </div>
    </div>
    <!-- Snackbar for notifications -->
    <div id="snackbar"></div>

    <!-- Sidebar Toggle Button -->
    <button id="sidebarToggle">☰</button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1><?php echo htmlspecialchars(explode(' ', $family['family_name'])[0]); ?></h1>
            <p>Family Fund</p>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="members.php" class="nav-link">
                    <span class="nav-icon">👥</span>
                    <span>Members</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="contribution.php" class="nav-link">
                    <span class="nav-icon">💰</span>
                    <span>Contributions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="expense.php" class="nav-link">
                    <span class="nav-icon">💸</span>
                    <span>Expenses</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="momo.php" class="nav-link">
                    <span class="nav-icon">🏦</span>
                    <span>MoMo Account</span>
                </a>
            </li>
                        <li class="nav-item">
                <a href="analytics" class="nav-link ">
                    <span class="nav-icon">📊</span>
                    <span>Analytics</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <button class="sign-out-btn" onclick="signOut()">
                <span class="nav-icon">🚪</span>
                <span>Sign Out</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Family Dashboard</h2>
                <p class="dashboard-subtitle">Overview of family contributions and expenses</p>
            </div>
            <div class="dashboard-actions">
                <button class="btn btn-secondary" onclick="exportData()">
                    📤 Export Data
                </button>
                <button class="btn btn-primary" onclick="showQuickAddModal()">
                    ➕ Quick Add
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card total-pool">
                <div class="stat-icon pool">💰</div>
                <div class="stat-value"><span id="totalPool">0.00</span></div>
                <div class="stat-label">Total Family Pool</div>
                <div class="stat-change" id="poolChange">
                    <span class="change-icon">↗</span> +0% from last month
                </div>
            </div>

            <div class="stat-card monthly-contrib">
                <div class="stat-icon contrib">📈</div>
                <div class="stat-value"><span id="monthlyContrib">0.00</span></div>
                <div class="stat-label">This Month's Contributions</div>
                <div class="stat-change" id="contribChange">
                    <span class="change-icon">↗</span> +0% from last month
                </div>
            </div>

            <div class="stat-card monthly-expenses">
                <div class="stat-icon expenses">💸</div>
                <div class="stat-value"><span id="monthlyExpenses">0.00</span></div>
                <div class="stat-label">This Month's Expenses</div>
                <div class="stat-change" id="expenseChange">
                    <span class="change-icon">↗</span> +0% from last month
                </div>
            </div>

            <div class="stat-card savings-rate">
                <div class="stat-icon savings">💎</div>
                <div class="stat-value"><span id="savingsRate">0</span>%</div>
                <div class="stat-label">Savings Rate</div>
                <div class="stat-change" id="savingsChange">
                    <span class="change-icon">↗</span> Net: <span id="netSavings">0.00</span>
                </div>
            </div>
        </div>
        <!-- Members Goal Tracking Grid -->
        <div class="members-overview" id="membersGrid">
            <!-- Member cards will be dynamically generated -->
            <div style="text-align: center; padding: 40px; color: #64748b;">
                Loading member information...
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="analytics-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Contribution Trends</h3>
                    <div class="chart-controls">
                        <button class="chart-control active" data-period="6m" onclick="updateChart('6m')">6M</button>
                        <button class="chart-control" data-period="1y" onclick="updateChart('1y')">1Y</button>
                        <button class="chart-control" data-period="all" onclick="updateChart('all')">All</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="contributionChart"></canvas>
                </div>
            </div>

            <div class="chart-card quick-stats">
                <div class="chart-header">
                    <h3 class="chart-title">Monthly Summary</h3>
                </div>
                <div class="quick-stats-container">
                    <div class="quick-stat-item">
                        <div class="quick-stat-number" id="activeMembers">0</div>
                        <div class="quick-stat-label">Active Members</div>
                        <div class="quick-stat-icon">👥</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number" id="contributionCount">0</div>
                        <div class="quick-stat-label">Contributions</div>
                        <div class="quick-stat-icon">💰</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number" id="expenseCount">0</div>
                        <div class="quick-stat-label">Expenses</div>
                        <div class="quick-stat-icon">💸</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number" id="netSavingsDisplay">₵0</div>
                        <div class="quick-stat-label">Net Savings</div>
                        <div class="quick-stat-icon">💎</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Overview -->
        <div class="members-overview" id="membersOverview">
            <!-- Members will be loaded dynamically -->
        </div>

        <!-- Recent Activity -->
        <div class="chart-card recent-activity">
            <div class="chart-header">
                <h3 class="chart-title">Recent Activity</h3>
            </div>
            <div class="activity-list" id="activityList">
                <!-- Activity items will be loaded dynamically -->
            </div>
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
<button class="btn btn-primary" onclick="openCyclePage()">
    🔁 View & Manage Cycles
</button>

            </div>
        </div>
    </div>

<!-- Enhanced modal structure for cycle summary -->
<div id="cycleSummaryModal" class="modal cycle-summary-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal('cycleSummaryModal')">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Content will be dynamically inserted here -->
        </div>
    </div>
</div>

<!-- Enhanced modal structure for debt summary -->
<div id="debtSummaryModal" class="modal debt-summary-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal('debtSummaryModal')">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Content will be dynamically inserted here -->
        </div>
    </div>
</div>


    <div id="quickContributeModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Contribution</h3>
                <span class="close" onclick="closeModal('quickContributeModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="quickContributeForm">
                    <input type="hidden" id="contributeMemberId" name="member_id">
                    <input type="hidden" id="contributeMemberOnlyId" name="member_only_id">
                    <input type="hidden" id="contributeMemberType" name="member_type">

                    <div class="form-group">
                        <label>Member</label>
                        <input type="text" id="contributeMemberName" readonly style="background-color: #f5f5f5;">
                    </div>

                    <div class="form-group">
                        <label>Amount (₵)</label>
                        <input type="number" id="contributeAmount" step="0.01" min="0" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" id="contributeNote" placeholder="e.g., Monthly contribution">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('quickContributeModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Contribution</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Quick Add Modal -->
    <div id="quickAddModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Add</h3>
                <span class="close" onclick="closeQuickAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="quick-add-tabs">
                    <button class="tab-btn active" onclick="switchTab('contribution')">Contribution</button>
                    <button class="tab-btn" onclick="switchTab('expense')">Expense</button>
                </div>

                <div id="contributionTab" class="tab-content active">
                    <form id="contributionForm" onsubmit="submitContribution(event)">
                        <div class="form-group">
                            <label>Member</label>
                            <select id="contributionMember" required>
                                <option value="">Select Member</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount (₵)</label>
                            <input type="number" id="contributionAmount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Note (Optional)</label>
                            <input type="text" id="contributionNote" placeholder="e.g., Monthly contribution">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Contribution</button>
                    </form>
                </div>

                <div id="expenseTab" class="tab-content">
                    <form id="expenseForm" onsubmit="submitExpense(event)">
                        <div class="form-group">
                            <label>Expense Type</label>
                            <select id="expenseType" required>
                                <option value="">Select Type</option>
                                <option value="dstv">DSTV</option>
                                <option value="wifi">WiFi</option>
                                <option value="utilities">Utilities</option>
                                <option value="dining">Dining</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount (₵)</label>
                            <input type="number" id="expenseAmount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" id="expenseDescription" placeholder="e.g., Monthly DSTV subscription" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Expense</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../public/js/dashboard.js"></script>
    <script src="../public/js/goal_tracker.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

</body>

</html>