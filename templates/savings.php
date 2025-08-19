<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information from session
$user_first_name = $_SESSION['first_name'] ?? 'User';
$user_full_name = $_SESSION['full_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings - Nkansah Budget Manager</title>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/savings.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">💰</div>
                <div class="logo-text">
                    <h1 id="logoUserName"><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Finance Dashboard</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard.php" class="nav-item">Dashboard</a>
                <a href="salary.php" class="nav-item">Salary Setup</a>
                <a href="budget.php" class="nav-item">Budget</a>
                <a href="personal-expense.php" class="nav-item">Expenses</a>
                <a href="savings.php" class="nav-item active">Savings</a>
                <a href="insights.php" class="nav-item">Insights</a>
                <a href="reports.php" class="nav-item">Reports</a>
            </nav>

            <div class="theme-selector">
                <button class="theme-toggle-btn" onclick="toggleThemeSelector()" title="Change Theme">
                    <span class="theme-icon">🎨</span>
                </button>
                <div class="theme-dropdown" id="themeDropdown">
                    <div class="theme-dropdown-header">
                        <h4>Choose Theme</h4>
                    </div>
                    <div class="themes-grid">
                        <div class="theme-option active" data-theme="default" onclick="changeTheme('default')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #3b82f6, #2563eb)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #10b981, #059669)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #f59e0b, #d97706)"></div>
                            </div>
                            <span class="theme-name">Ocean Blue</span>
                        </div>
                        <div class="theme-option" data-theme="forest" onclick="changeTheme('forest')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #059669, #047857)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #10b981, #065f46)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #34d399, #059669)"></div>
                            </div>
                            <span class="theme-name">Forest Green</span>
                        </div>
                        <div class="theme-option" data-theme="sunset" onclick="changeTheme('sunset')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #f59e0b, #d97706)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #f97316, #ea580c)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fbbf24, #f59e0b)"></div>
                            </div>
                            <span class="theme-name">Sunset Orange</span>
                        </div>
                        <div class="theme-option" data-theme="purple" onclick="changeTheme('purple')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #a855f7, #9333ea)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #c084fc, #a855f7)"></div>
                            </div>
                            <span class="theme-name">Royal Purple</span>
                        </div>
                        <div class="theme-option" data-theme="rose" onclick="changeTheme('rose')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #f43f5e, #e11d48)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fb7185, #f43f5e)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fda4af, #fb7185)"></div>
                            </div>
                            <span class="theme-name">Rose Pink</span>
                        </div>
                        <div class="theme-option" data-theme="dark" onclick="changeTheme('dark')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #374151, #1f2937)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #6b7280, #4b5563)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #9ca3af, #6b7280)"></div>
                            </div>
                            <span class="theme-name">Dark Mode</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-menu">
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar"><?php 
                    echo strtoupper(substr($user_first_name, 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)); 
                ?></div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php">Profile Settings</a>
                    <a href="income-sources.php">Income Sources</a>
                    <a href="categories.php">Categories</a>
                    <hr>
                    <a href="family-dashboard.php">Switch to Family</a>
                    <a href="../actions/signout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <section class="page-header">
                <div class="page-title">
                    <h2>💰 Savings Management</h2>
                    <p>Track your goals, manage auto-savings, and build your financial future</p>
                </div>
                <div class="page-actions">
                    <button class="quick-btn" onclick="showNewGoalModal()">
                        <span class="btn-icon">🎯</span>
                        New Goal
                    </button>
                    <button class="quick-btn" onclick="showDepositModal()">
                        <span class="btn-icon">💵</span>
                        Add Deposit
                    </button>
                    <button class="quick-btn secondary" onclick="showAutoSaveModal()">
                        <span class="btn-icon">⚙️</span>
                        Auto-Save
                    </button>
                </div>
            </section>

            <!-- Savings Overview -->
            <section class="savings-overview">
                <div class="overview-card total-savings">
                    <div class="card-header">
                        <h3>Total Savings</h3>
                        <span class="card-icon">🏦</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="totalSavingsAmount">₵0.00</div>
                        <div class="change" id="totalSavingsChange">Loading...</div>
                        <div class="savings-breakdown" id="savingsBreakdown">
                            <span class="breakdown-item">Goals: ₵0.00</span>
                            <span class="breakdown-item">Emergency: ₵0.00</span>
                        </div>
                    </div>
                </div>

                <div class="overview-card monthly-target">
                    <div class="card-header">
                        <h3>Monthly Target</h3>
                        <span class="card-icon">📊</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="monthlyTargetAmount">₵700.00</div>
                        <div class="change" id="monthlyTargetPercentage">20% of salary</div>
                        <div class="target-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" id="monthlyProgressFill" style="width: 85%"></div>
                            </div>
                            <span class="progress-text" id="monthlyProgressText">₵595 saved • ₵105 to go</span>
                        </div>
                        <div class="target-breakdown" id="targetBreakdown">
                            <small>From active goals auto-save settings</small>
                        </div>
                    </div>
                </div>

                <div class="overview-card savings-rate">
                    <div class="card-header">
                        <h3>Savings Rate</h3>
                        <span class="card-icon">📈</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="savingsRateAmount">0%</div>
                        <div class="change" id="savingsRateChange">Loading...</div>
                        <div class="rate-comparison" id="rateComparison">
                            <span class="comparison-item">Target: 20%</span>
                            <span class="comparison-item">Average: 15%</span>
                        </div>
                    </div>
                </div>


            </section>

            <!-- Savings Goals Grid -->
            <section class="savings-goals-section">
                <div class="section-header">
                    <h3>🎯 Savings Goals</h3>
                    <div class="goal-filters">
                        <button class="filter-btn active" data-filter="all">All Goals</button>
                        <button class="filter-btn" data-filter="active">Active</button>
                        <button class="filter-btn" data-filter="completed">Completed</button>
                        <button class="filter-btn" data-filter="paused">Paused</button>
                    </div>
                </div>

                <div class="goals-grid" id="goalsGrid">
                    <!-- Goals will be loaded dynamically from database -->
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>Loading your savings goals...</p>
                    </div>
                </div>
            </section>

            <!-- Auto-Save Settings -->
            <section class="auto-save-section">
                <div class="section-header">
                    <h3>⚙️ Auto-Save Settings</h3>
                    <button class="view-all" onclick="showAutoSaveModal()">Configure</button>
                </div>

                <div class="auto-save-grid">
                    <div class="auto-save-card">
                        <div class="auto-save-header">
                            <div class="auto-save-info">
                                <h4>Salary Auto-Save</h4>
                                <p>Automatically save from each salary</p>
                            </div>
                            <div class="auto-save-toggle">
                                <input type="checkbox" id="salaryAutoSave" checked>
                                <label for="salaryAutoSave" class="toggle-switch"></label>
                            </div>
                        </div>
                        <div class="auto-save-details">
                            <div class="detail-item">
                                <span class="detail-label">Amount:</span>
                                <span class="detail-value">₵700.00 (20%)</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Next Save:</span>
                                <span class="detail-value">Jan 28, 2025</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Saved:</span>
                                <span class="detail-value">₵8,400.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="auto-save-card">
                        <div class="auto-save-header">
                            <div class="auto-save-info">
                                <h4>Round-Up Savings</h4>
                                <p>Round up purchases to nearest ₵5</p>
                            </div>
                            <div class="auto-save-toggle">
                                <input type="checkbox" id="roundUpSave" checked>
                                <label for="roundUpSave" class="toggle-switch"></label>
                            </div>
                        </div>
                        <div class="auto-save-details">
                            <div class="detail-item">
                                <span class="detail-label">This Month:</span>
                                <span class="detail-value">₵23.50</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Average/Month:</span>
                                <span class="detail-value">₵18.75</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Saved:</span>
                                <span class="detail-value">₵225.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="auto-save-card">
                        <div class="auto-save-header">
                            <div class="auto-save-info">
                                <h4>Weekly Challenge</h4>
                                <p>Save ₵20 every week</p>
                            </div>
                            <div class="auto-save-toggle">
                                <input type="checkbox" id="weeklySave">
                                <label for="weeklySave" class="toggle-switch"></label>
                            </div>
                        </div>
                        <div class="auto-save-details">
                            <div class="detail-item">
                                <span class="detail-label">Weekly Amount:</span>
                                <span class="detail-value">₵20.00</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Next Save:</span>
                                <span class="detail-value">Every Monday</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value inactive">Inactive</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="recent-activity-section">
                <div class="section-header">
                    <h3>📊 Recent Activity</h3>
                    <a href="#" class="view-all">View All Transactions</a>
                </div>

                <div class="activity-list" id="activityList">
                    <!-- Activities will be loaded dynamically from database -->
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>Loading recent activity...</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modals -->
<!-- Fixed New Goal Modal -->
<div id="newGoalModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Create New Savings Goal</h3>
            <span class="close" onclick="closeModal('newGoalModal')">&times;</span>
        </div>
        <form class="modal-form" id="newGoalForm">
            <div class="form-section">
                <h4>Goal Details</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Goal Name</label>
                        <input type="text" name="goal_name" id="goalName" placeholder="e.g., Dream Vacation" required>
                    </div>
                    <div class="form-group">
                        <label>Goal Type</label>
                        <select name="goal_type" id="goalType" required>
                            <option value="">Choose type</option>
                            <option value="emergency_fund">🚨 Emergency Fund</option>
                            <option value="vacation">🏖️ Vacation</option>
                            <option value="car">🚗 Car</option>
                            <option value="house">🏠 House</option>
                            <option value="education">🎓 Education</option>
                            <option value="other">🎯 Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>Financial Details</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Target Amount (₵)</label>
                        <input type="number" name="target_amount" id="targetAmount" step="1" placeholder="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" id="priority" required>
                            <option value="high">High Priority</option>
                            <option value="medium" selected>Medium Priority</option>
                            <option value="low">Low Priority</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Initial Deposit (₵)</label>
                        <input type="number" name="initial_deposit" id="initialDeposit" step="0.01" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>Auto-Save Settings</h4>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="auto_save_enabled" id="autoSaveEnabled" value="1">
                        <span class="checkmark"></span>
                        Enable automatic savings for this goal
                    </label>
                </div>
                
                <div id="autoSaveOptions" style="display: none;">
                    <div class="form-group">
                        <label>Save Method</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="save_method" value="percentage" id="saveMethodPercentage" checked>
                                <span class="radio-mark"></span>
                                Percentage of Income
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="save_method" value="fixed" id="saveMethodFixed">
                                <span class="radio-mark"></span>
                                Fixed Amount
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" id="percentageGroup">
                            <label>Percentage of Income (%)</label>
                            <div class="input-with-slider">
                                <input type="range" name="save_percentage" id="savePercentage" min="1" max="50" value="10" class="config-slider">
                                <span class="slider-value">10%</span>
                            </div>
                        </div>
                        <div class="form-group" id="fixedAmountGroup" style="display: none;">
                            <label>Fixed Amount (₵)</label>
                            <input type="number" name="save_amount" id="fixedSaveAmount" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="save-preview">
                        <div class="preview-info">
                            <span class="preview-label">Estimated monthly save:</span>
                            <span class="preview-amount" id="savePreviewAmount">₵350.00</span>
                        </div>
                        <small class="preview-note">Based on current income settings</small>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="deduct_from_income" id="deductFromIncome" value="1">
                            <span class="checkmark"></span>
                            Deduct from available income automatically
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('newGoalModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Goal</button>
            </div>
        </form>
    </div>
</div>

    <!-- Add Deposit Modal -->
<!-- Fixed Add Deposit Modal -->
<div id="depositModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Deposit</h3>
            <span class="close" onclick="closeModal('depositModal')">&times;</span>
        </div>
        <form class="modal-form" id="depositForm" onsubmit="addDeposit(event)">
            <div class="form-group">
                <label>Select Goal</label>
                <select name="goal_id" id="depositGoal" required>
                    <option value="">Choose a goal</option>
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
            <div class="form-group">
                <label>Amount (₵)</label>
                <input type="number" name="amount" id="depositAmount" step="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Source</label>
                <select name="source" id="depositSource" required>
                    <option value="">Select source</option>
                    <option value="manual">Manual Transfer</option>
                    <option value="salary">From Salary</option>
                    <option value="bonus">Bonus/Extra Income</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('depositModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Deposit</button>
            </div>
        </form>
    </div>
</div>

    <!-- Auto-Save Configuration Modal -->
    <div id="autoSaveModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Auto-Save Configuration</h3>
                <span class="close" onclick="closeModal('autoSaveModal')">&times;</span>
            </div>
            <form class="modal-form" onsubmit="updateAutoSave(event)">
                <div class="form-section">
                    <h4>Salary Auto-Save</h4>
                    <div class="auto-save-config">
                        <div class="config-header">
                            <div class="config-info">
                                <h5>Save from each salary payment</h5>
                                <p>Automatically transfer a portion of your salary to savings</p>
                            </div>
                            <div class="config-toggle">
                                <input type="checkbox" id="configSalaryAutoSave" checked>
                                <label for="configSalaryAutoSave" class="toggle-switch"></label>
                            </div>
                        </div>
                        <div class="config-details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Percentage of Salary</label>
                                    <div class="input-with-slider">
                                        <input type="range" id="salaryPercentage" min="0" max="50" value="20" class="config-slider">
                                        <span class="slider-value">20%</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Amount (₵3,500 salary)</label>
                                    <input type="number" id="salaryAmount" value="700" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Distribute to Goals</label>
                                <div class="goal-distribution">
                                    <div class="distribution-item">
                                        <span>🚨 Emergency Fund</span>
                                        <input type="number" value="500" min="0" step="50">
                                    </div>
                                    <div class="distribution-item">
                                        <span>🏖️ Vacation Fund</span>
                                        <input type="number" value="150" min="0" step="50">
                                    </div>
                                    <div class="distribution-item">
                                        <span>🚗 Car Fund</span>
                                        <input type="number" value="50" min="0" step="50">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Round-Up Savings</h4>
                    <div class="auto-save-config">
                        <div class="config-header">
                            <div class="config-info">
                                <h5>Round up purchases</h5>
                                <p>Round up expenses to the nearest amount and save the difference</p>
                            </div>
                            <div class="config-toggle">
                                <input type="checkbox" id="configRoundUpSave" checked>
                                <label for="configRoundUpSave" class="toggle-switch"></label>
                            </div>
                        </div>
                        <div class="config-details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Round up to nearest</label>
                                    <select id="roundUpAmount">
                                        <option value="1">₵1.00</option>
                                        <option value="5" selected>₵5.00</option>
                                        <option value="10">₵10.00</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Maximum per transaction</label>
                                    <select id="roundUpMax">
                                        <option value="5">₵5.00</option>
                                        <option value="10" selected>₵10.00</option>
                                        <option value="20">₵20.00</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Send round-ups to</label>
                                <select id="roundUpGoal">
                                    <option value="emergency" selected>🚨 Emergency Fund</option>
                                    <option value="vacation">🏖️ Vacation Fund</option>
                                    <option value="car">🚗 Car Fund</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Challenge Savings</h4>
                    <div class="auto-save-config">
                        <div class="config-header">
                            <div class="config-info">
                                <h5>Weekly savings challenge</h5>
                                <p>Save a fixed amount every week to build consistency</p>
                            </div>
                            <div class="config-toggle">
                                <input type="checkbox" id="configWeeklySave">
                                <label for="configWeeklySave" class="toggle-switch"></label>
                            </div>
                        </div>
                        <div class="config-details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Weekly Amount (₵)</label>
                                    <input type="number" id="weeklyAmount" value="20" step="5" min="5">
                                </div>
                                <div class="form-group">
                                    <label>Save on</label>
                                    <select id="weeklyDay">
                                        <option value="monday" selected>Monday</option>
                                        <option value="tuesday">Tuesday</option>
                                        <option value="wednesday">Wednesday</option>
                                        <option value="thursday">Thursday</option>
                                        <option value="friday">Friday</option>
                                        <option value="saturday">Saturday</option>
                                        <option value="sunday">Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Destination Goal</label>
                                <select id="weeklyGoal">
                                    <option value="emergency">🚨 Emergency Fund</option>
                                    <option value="vacation" selected>🏖️ Vacation Fund</option>
                                    <option value="car">🚗 Car Fund</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('autoSaveModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Goal Details Modal -->
    <div id="goalDetailsModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3 id="goalDetailsTitle">Goal Details</h3>
                <span class="close" onclick="closeModal('goalDetailsModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="goal-details-content">
                    <div class="goal-summary">
                        <div class="summary-left">
                            <div class="goal-icon-large" id="goalIconLarge">🚨</div>
                            <div class="goal-basic-info">
                                <h4 id="goalTitleLarge">Emergency Fund</h4>
                                <p id="goalDescriptionLarge">6 months of expenses for financial security</p>
                            </div>
                        </div>
                        <div class="summary-right">
                            <div class="goal-progress-large">
                                <div class="progress-circle-large" data-percentage="57">
                                    <div class="circle-inner-large">
                                        <span class="percentage-large">57%</span>
                                        <span class="progress-label">Complete</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="goal-stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Current Amount</div>
                            <div class="stat-value" id="currentAmountStat">₵8,500.00</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Target Amount</div>
                            <div class="stat-value" id="targetAmountStat">₵15,000.00</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Remaining</div>
                            <div class="stat-value" id="remainingStat">₵6,500.00</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Monthly Target</div>
                            <div class="stat-value" id="monthlyTargetStat">₵500.00</div>
                        </div>
                    </div>

                    <div class="goal-history">
                        <h5>Recent Contributions</h5>
                        <div class="history-list">
                            <div class="history-item">
                                <div class="history-date">Jan 15, 2025</div>
                                <div class="history-description">Auto-save from salary</div>
                                <div class="history-amount">+₵500.00</div>
                            </div>
                            <div class="history-item">
                                <div class="history-date">Jan 8, 2025</div>
                                <div class="history-description">Round-up collection</div>
                                <div class="history-amount">+₵12.50</div>
                            </div>
                            <div class="history-item">
                                <div class="history-date">Dec 28, 2024</div>
                                <div class="history-description">Manual deposit</div>
                                <div class="history-amount">+₵200.00</div>
                            </div>
                            <div class="history-item">
                                <div class="history-date">Dec 15, 2024</div>
                                <div class="history-description">Auto-save from salary</div>
                                <div class="history-amount">+₵500.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('goalDetailsModal')">Close</button>
                <button type="button" class="btn-primary" onclick="addToGoalFromDetails()">Add Money</button>
            </div>
        </div>
    </div>
    <script>
        // Global functions for modal and goal actions - defined immediately
window.showNewGoalModal = function() {
    const modal = document.getElementById('newGoalModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
        setTimeout(() => modal.style.opacity = '1', 10);
    } else {
        console.error('New goal modal not found');
    }
};

window.showDepositModal = function() {
    const modal = document.getElementById('depositModal');
    if (modal) {
        // Populate goals dropdown
        if (window.savingsManager) {
            window.savingsManager.populateGoalsDropdown();
        }
        modal.style.display = 'flex';
        modal.classList.add('show');
        setTimeout(() => modal.style.opacity = '1', 10);
    } else {
        console.error('Deposit modal not found');
    }
};

window.showAutoSaveModal = function() {
    const modal = document.getElementById('autoSaveModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
        setTimeout(() => modal.style.opacity = '1', 10);
    } else {
        console.error('Auto-save modal not found');
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }, 300);
    }
};

window.editGoal = function(goalId) {
    console.log('Edit goal:', goalId);
    // Find the goal data
    const goal = window.savingsManager.currentGoals.find(g => g.id === goalId);
    if (goal) {
        // Populate edit form with goal data
        document.getElementById('goalName').value = goal.goal_name;
        document.getElementById('targetAmount').value = goal.target_amount;
        document.getElementById('goalType').value = goal.goal_type;
        document.getElementById('priority').value = goal.priority;
        
        // Show the modal in edit mode
        const modal = document.getElementById('newGoalModal');
        const title = modal.querySelector('h3');
        const submitBtn = modal.querySelector('button[type="submit"]');
        
        title.textContent = 'Edit Savings Goal';
        submitBtn.textContent = 'Update Goal';
        
        // Add goal ID as hidden field
        const form = document.getElementById('newGoalForm');
        let hiddenField = form.querySelector('input[name="goal_id"]');
        if (!hiddenField) {
            hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'goal_id';
            form.appendChild(hiddenField);
        }
        hiddenField.value = goalId;
        
        showNewGoalModal();
    }
};

window.pauseGoal = function(goalId) {
    if (confirm('Are you sure you want to pause this goal? Auto-save will be disabled.')) {
        fetch('/budget-app/actions/savings_handler.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=pause_goal&goal_id=${goalId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.savingsManager) {
                    window.savingsManager.loadSavingsData();
                    window.savingsManager.loadSavingsOverview();
                }
                window.savingsManager?.showSnackbar('Goal paused successfully', 'success');
            } else {
                alert(data.message || 'Failed to pause goal');
            }
        })
        .catch(err => console.error('Pause goal error:', err));
    }
};

window.resumeGoal = function(goalId) {
    fetch('/budget-app/actions/savings_handler.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=resume_goal&goal_id=${goalId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (window.savingsManager) {
                window.savingsManager.loadSavingsData();
                window.savingsManager.loadSavingsOverview();
            }
            window.savingsManager?.showSnackbar('Goal resumed successfully', 'success');
        } else {
            alert(data.message || 'Failed to resume goal');
        }
    })
    .catch(err => console.error('Resume goal error:', err));
};

window.setGoalInactive = function(goalId) {
    if (confirm('Are you sure you want to set this goal to inactive? Auto-save will be disabled.')) {
        fetch('/budget-app/actions/savings_handler.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=set_goal_inactive&goal_id=${goalId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.savingsManager) {
                    window.savingsManager.loadSavingsData();
                    window.savingsManager.loadSavingsOverview();
                }
                window.savingsManager?.showSnackbar('Goal set to inactive', 'success');
            } else {
                alert(data.message || 'Failed to set goal inactive');
            }
        })
        .catch(err => console.error('Set goal inactive error:', err));
    }
};

window.pauseGoal = function(goalId) {
    console.log('Pause goal:', goalId);
    // Implementation will be added
};

window.resumeGoal = function(goalId) {
    console.log('Resume goal:', goalId);
    // Implementation will be added
};

window.addToGoal = function(goalId) {
    console.log('Add to goal:', goalId);
    showDepositModal();
};

window.viewGoalDetails = function(goalId) {
    console.log('View goal details:', goalId);
    const modal = document.getElementById('goalDetailsModal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

window.deleteGoal = function(goalId) {
    if (confirm('Are you sure you want to delete this goal?')) {
        console.log('Delete goal:', goalId);
        fetch('/budget-app/actions/savings_handler.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_goal&goal_id=${goalId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.savingsManager) {
                    window.savingsManager.loadSavingsData();
                    window.savingsManager.loadSavingsOverview();
                }
            } else {
                alert(data.message || 'Failed to delete goal');
            }
        })
        .catch(err => console.error('Delete goal error:', err));
    }
};

window.archiveGoal = function(goalId) {
    console.log('Archive goal:', goalId);
    // Implementation will be added
};

window.toggleGoalMenu = function(btn) {
    // Close all other dropdowns first
    document.querySelectorAll('.goal-dropdown').forEach(dropdown => {
        if (dropdown !== btn.nextElementSibling) {
            dropdown.classList.remove('show');
        }
    });
    
    const dropdown = btn.nextElementSibling;
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
};

// Savings Manager Class
class SavingsManager {
    constructor() {
        this.currentGoals = [];
        this.autoSaveSettings = {};
        this.monthlyTargetFromGoals = 0;
        this.init();
    }

    init() {
        this.loadSavingsData();
        this.loadSavingsOverview();
        this.loadRecentActivity();
        this.setupEventListeners();
        this.updateMonthlyTargetDisplay();
    }

    async loadSavingsOverview() {
        try {
            const response = await fetch('/budget-app/actions/savings_handler.php?action=get_savings_overview', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            console.log('Savings overview loaded:', data);
            
            if (data.success) {
                this.updateSavingsOverviewDisplay(data.data);
            } else {
                console.error('Failed to load savings overview:', data.message);
                this.updateSavingsOverviewDisplay(null);
            }
        } catch (error) {
            console.error('Error loading savings overview:', error);
            this.updateSavingsOverviewDisplay(null);
        }
    }

    updateSavingsOverviewDisplay(data) {
        if (!data) {
            // Show default/error state
            const totalSavingsAmount = document.getElementById('totalSavingsAmount');
            const totalSavingsChange = document.getElementById('totalSavingsChange');
            const savingsBreakdown = document.getElementById('savingsBreakdown');
            const savingsRateAmount = document.getElementById('savingsRateAmount');
            const savingsRateChange = document.getElementById('savingsRateChange');
            
            if (totalSavingsAmount) totalSavingsAmount.textContent = '₵0.00';
            if (totalSavingsChange) totalSavingsChange.textContent = 'No data available';
            if (savingsBreakdown) savingsBreakdown.innerHTML = '<span class="breakdown-item">No savings yet</span>';
            if (savingsRateAmount) savingsRateAmount.textContent = '0%';
            if (savingsRateChange) savingsRateChange.textContent = 'No data available';
            return;
        }

        // Update Total Savings
        const totalSavingsAmount = document.getElementById('totalSavingsAmount');
        if (totalSavingsAmount) {
            totalSavingsAmount.textContent = `₵${data.total_savings.toFixed(2)}`;
        }

        const totalSavingsChange = document.getElementById('totalSavingsChange');
        if (totalSavingsChange) {
            const changeText = data.monthly_change >= 0 ? 
                `+₵${data.monthly_change.toFixed(2)} this month` : 
                `₵${data.monthly_change.toFixed(2)} this month`;
            totalSavingsChange.textContent = changeText;
            totalSavingsChange.className = `change ${data.change_direction}`;
        }

        const savingsBreakdown = document.getElementById('savingsBreakdown');
        if (savingsBreakdown) {
            savingsBreakdown.innerHTML = `
                <span class="breakdown-item">Goals: ₵${data.goal_savings.toFixed(2)}</span>
                <span class="breakdown-item">Emergency: ₵${data.emergency_savings.toFixed(2)}</span>
            `;
        }

        // Update Savings Rate
        const savingsRateAmount = document.getElementById('savingsRateAmount');
        if (savingsRateAmount) {
            savingsRateAmount.textContent = `${data.savings_rate}%`;
        }

        const savingsRateChange = document.getElementById('savingsRateChange');
        if (savingsRateChange) {
            const rateChangeText = data.savings_rate_change >= 0 ? 
                `+${data.savings_rate_change}% from last month` : 
                `${data.savings_rate_change}% from last month`;
            savingsRateChange.textContent = rateChangeText;
            savingsRateChange.className = `change ${data.rate_change_direction}`;
        }

        // Update comparison targets (could be made dynamic in the future)
        const rateComparison = document.getElementById('rateComparison');
        if (rateComparison) {
            rateComparison.innerHTML = `
                <span class="comparison-item">Target: 20%</span>
                <span class="comparison-item">Current: ${data.savings_rate}%</span>
            `;
        }
    }

    async loadSavingsOverview() {
        try {
            const response = await fetch('/budget-app/actions/savings_handler.php?action=get_savings_overview', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            console.log('Savings overview loaded:', data);
            
            if (data.success) {
                this.updateSavingsOverviewDisplay(data.data);
            } else {
                console.error('Failed to load savings overview:', data.message);
                this.updateSavingsOverviewDisplay(null);
            }
        } catch (error) {
            console.error('Error loading savings overview:', error);
            this.updateSavingsOverviewDisplay(null);
        }
    }

    updateSavingsOverviewDisplay(data) {
        if (!data) {
            // Show default/error state
            const totalSavingsAmount = document.getElementById('totalSavingsAmount');
            const totalSavingsChange = document.getElementById('totalSavingsChange');
            const savingsBreakdown = document.getElementById('savingsBreakdown');
            const savingsRateAmount = document.getElementById('savingsRateAmount');
            const savingsRateChange = document.getElementById('savingsRateChange');
            
            if (totalSavingsAmount) totalSavingsAmount.textContent = '₵0.00';
            if (totalSavingsChange) totalSavingsChange.textContent = 'No data available';
            if (savingsBreakdown) savingsBreakdown.innerHTML = '<span class="breakdown-item">No savings yet</span>';
            if (savingsRateAmount) savingsRateAmount.textContent = '0%';
            if (savingsRateChange) savingsRateChange.textContent = 'No data available';
            return;
        }

        // Update Total Savings
        const totalSavingsAmount = document.getElementById('totalSavingsAmount');
        if (totalSavingsAmount) {
            totalSavingsAmount.textContent = `₵${data.total_savings.toFixed(2)}`;
        }

        const totalSavingsChange = document.getElementById('totalSavingsChange');
        if (totalSavingsChange) {
            const changeText = data.monthly_change >= 0 ? 
                `+₵${data.monthly_change.toFixed(2)} this month` : 
                `₵${data.monthly_change.toFixed(2)} this month`;
            totalSavingsChange.textContent = changeText;
            totalSavingsChange.className = `change ${data.change_direction}`;
        }

        const savingsBreakdown = document.getElementById('savingsBreakdown');
        if (savingsBreakdown) {
            savingsBreakdown.innerHTML = `
                <span class="breakdown-item">Goals: ₵${data.goal_savings.toFixed(2)}</span>
                <span class="breakdown-item">Emergency: ₵${data.emergency_savings.toFixed(2)}</span>
            `;
        }

        // Update Savings Rate
        const savingsRateAmount = document.getElementById('savingsRateAmount');
        if (savingsRateAmount) {
            savingsRateAmount.textContent = `${data.savings_rate}%`;
        }

        const savingsRateChange = document.getElementById('savingsRateChange');
        if (savingsRateChange) {
            const rateChangeText = data.savings_rate_change >= 0 ? 
                `+${data.savings_rate_change}% from last month` : 
                `${data.savings_rate_change}% from last month`;
            savingsRateChange.textContent = rateChangeText;
            savingsRateChange.className = `change ${data.rate_change_direction}`;
        }

        // Update comparison targets (could be made dynamic in the future)
        const rateComparison = document.getElementById('rateComparison');
        if (rateComparison) {
            rateComparison.innerHTML = `
                <span class="comparison-item">Target: 20%</span>
                <span class="comparison-item">Current: ${data.savings_rate}%</span>
            `;
        }
    }

    async loadSavingsData() {
        try {
            const response = await fetch('/budget-app/actions/savings_handler.php?action=get_goals', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            console.log('Savings data loaded:', data);
            
            if (data.success) {
                this.currentGoals = data.goals || [];
                this.autoSaveSettings = data.auto_save_settings || {};
                this.budgetAllocation = data.budget_allocation || {};
                this.updateGoalsDisplay();
                this.updateMonthlyTargetDisplay();
                this.updateBudgetAllocationDisplay();
                // this.updateAutoSaveStatus(); // Commented out for now
            } else {
                console.error('Failed to load savings data:', data.message);
            }
        } catch (error) {
            console.error('Error loading savings data:', error);
        }
    }

    setupEventListeners() {
        // Setup form submission handlers
        const newGoalForm = document.getElementById('newGoalForm');
        if (newGoalForm) {
            newGoalForm.addEventListener('submit', (e) => this.handleNewGoal(e));
        }

        const depositForm = document.getElementById('depositForm');
        if (depositForm) {
            depositForm.addEventListener('submit', (e) => this.handleDeposit(e));
        }

        const autoSaveForm = document.getElementById('autoSaveForm');
        if (autoSaveForm) {
            autoSaveForm.addEventListener('submit', (e) => this.handleAutoSaveSettings(e));
        }

        // Setup auto save method toggle
        const autoSaveMethod = document.getElementById('auto_save_method');
        if (autoSaveMethod) {
            autoSaveMethod.addEventListener('change', () => this.toggleAutoSaveMethod());
        }

        // Setup goal filtering
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.filterGoals(e.target.dataset.filter));
        });

        // Setup auto-save method toggle in goal creation
        const saveMethodRadios = document.querySelectorAll('input[name="save_method"]');
        saveMethodRadios.forEach(radio => {
            radio.addEventListener('change', () => this.toggleSaveMethod());
        });

        // Setup auto-save enabled checkbox
        const autoSaveEnabledCheckbox = document.getElementById('autoSaveEnabled');
        if (autoSaveEnabledCheckbox) {
            autoSaveEnabledCheckbox.addEventListener('change', () => this.toggleAutoSaveOptions());
        }

        // Setup percentage slider
        const savePercentageSlider = document.getElementById('savePercentage');
        if (savePercentageSlider) {
            savePercentageSlider.addEventListener('input', () => this.updateSavePreview());
        }

        // Setup fixed amount input
        const fixedSaveAmountInput = document.getElementById('fixedSaveAmount');
        if (fixedSaveAmountInput) {
            fixedSaveAmountInput.addEventListener('input', () => this.updateSavePreview());
        }

        // Close modals when clicking outside
        this.setupModalHandlers();
    }

    setupModalHandlers() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    }

    filterGoals(filter) {
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

        // Filter goals
        const goalCards = document.querySelectorAll('.goal-card');
        goalCards.forEach(card => {
            const goalStatus = card.dataset.status || 'active';
            const isCompleted = card.classList.contains('completed');
            
            let shouldShow = false;
            
            switch(filter) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'active':
                    shouldShow = goalStatus === 'active' && !isCompleted;
                    break;
                case 'completed':
                    shouldShow = isCompleted;
                    break;
                case 'paused':
                    shouldShow = goalStatus === 'paused' && !isCompleted;
                    break;
                case 'inactive':
                    shouldShow = goalStatus === 'inactive' && !isCompleted;
                    break;
            }
            
            if (shouldShow) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    toggleSaveMethod() {
        const percentageRadio = document.getElementById('saveMethodPercentage');
        const fixedRadio = document.getElementById('saveMethodFixed');
        const percentageGroup = document.getElementById('percentageGroup');
        const fixedAmountGroup = document.getElementById('fixedAmountGroup');
        
        if (percentageRadio && percentageRadio.checked) {
            percentageGroup.style.display = 'block';
            fixedAmountGroup.style.display = 'none';
            this.updateSavePreview();
        } else if (fixedRadio && fixedRadio.checked) {
            percentageGroup.style.display = 'none';
            fixedAmountGroup.style.display = 'block';
            this.updateSavePreview();
        }
    }

    toggleAutoSaveOptions() {
        const autoSaveEnabled = document.getElementById('autoSaveEnabled');
        const autoSaveOptions = document.getElementById('autoSaveOptions');
        
        if (autoSaveEnabled && autoSaveOptions) {
            if (autoSaveEnabled.checked) {
                autoSaveOptions.style.display = 'block';
                this.updateSavePreview();
            } else {
                autoSaveOptions.style.display = 'none';
            }
        }
    }

    updateSavePreview() {
        const percentageRadio = document.getElementById('saveMethodPercentage');
        const savePercentage = document.getElementById('savePercentage');
        const fixedSaveAmount = document.getElementById('fixedSaveAmount');
        const previewAmount = document.getElementById('savePreviewAmount');
        
        if (!previewAmount) return;
        
        // Get user's salary from the monthly target data
        const monthlySalary = this.autoSaveSettings?.monthly_target?.monthly_salary || 3500; // Default for preview
        
        let estimatedAmount = 0;
        
        if (percentageRadio && percentageRadio.checked && savePercentage) {
            const percentage = parseFloat(savePercentage.value) || 0;
            estimatedAmount = (monthlySalary * percentage) / 100;
            
            // Update slider value display
            const sliderValue = document.querySelector('.slider-value');
            if (sliderValue) {
                sliderValue.textContent = percentage + '%';
            }
        } else if (fixedSaveAmount) {
            estimatedAmount = parseFloat(fixedSaveAmount.value) || 0;
        }
        
        previewAmount.textContent = `₵${estimatedAmount.toFixed(2)}`;
    }

    updateGoalsDisplay() {
        const goalsList = document.getElementById('goalsGrid');
        if (!goalsList) {
            console.log('Goals grid element not found');
            return;
        }

        if (this.currentGoals.length === 0) {
            goalsList.innerHTML = '<p class="no-goals">No savings goals set. Click "New Goal" to get started!</p>';
            return;
        }

        goalsList.innerHTML = this.currentGoals.map(goal => {
            const percentage = Math.min((goal.current_amount / goal.target_amount) * 100, 100);
            const remaining = Math.max(goal.target_amount - goal.current_amount, 0);
            const goalIcon = this.getGoalIcon(goal.goal_type);
            const isCompleted = goal.current_amount >= goal.target_amount;
            const status = goal.status || 'active';
            
            // Status display
            let statusBadge = '';
            let statusClass = status;
            switch(status) {
                case 'active':
                    statusBadge = '<span class="status-badge active">🟢 Active</span>';
                    break;
                case 'paused':
                    statusBadge = '<span class="status-badge paused">⏸️ Paused</span>';
                    statusClass = 'paused';
                    break;
                case 'inactive':
                    statusBadge = '<span class="status-badge inactive">⭕ Inactive</span>';
                    statusClass = 'inactive';
                    break;
            }
            
            // Auto-save status
            const autoSaveStatus = goal.auto_save_enabled ? 
                (goal.save_method === 'percentage' ? 
                    `${goal.save_percentage}% (₵${parseFloat(goal.save_amount || 0).toFixed(2)})` :
                    `₵${parseFloat(goal.save_amount || 0).toFixed(2)}/month`) : 
                'Manual Only';
            const autoSaveClass = goal.auto_save_enabled ? 'active' : 'inactive';
            
            // Action buttons based on status
            let actionButtons = '';
            if (isCompleted) {
                actionButtons = `
                    <button class="goal-btn completed" disabled>Goal Achieved! 🎉</button>
                    <button class="goal-btn secondary" onclick="viewGoalDetails(${goal.id})">View Details</button>
                `;
            } else {
                const addAmount = Math.min(100, remaining);
                actionButtons = `
                    <button class="goal-btn primary" onclick="addToGoal(${goal.id})">Add </button>
                    <button class="goal-btn secondary" onclick="viewGoalDetails(${goal.id})">Details</button>
                `;
            }
            
            // Menu options based on status
            let menuOptions = '';
            if (status === 'active') {
                menuOptions = `
                    <a href="#" onclick="editGoal(${goal.id})">Edit Goal</a>
                    <a href="#" onclick="pauseGoal(${goal.id})">Pause Goal</a>
                    <a href="#" onclick="setGoalInactive(${goal.id})">Set Inactive</a>
                    <a href="#" onclick="addToGoal(${goal.id})">Add Money</a>
                    <hr>
                    <a href="#" onclick="deleteGoal(${goal.id})" class="danger">Delete Goal</a>
                `;
            } else if (status === 'paused') {
                menuOptions = `
                    <a href="#" onclick="editGoal(${goal.id})">Edit Goal</a>
                    <a href="#" onclick="resumeGoal(${goal.id})">Resume Goal</a>
                    <a href="#" onclick="setGoalInactive(${goal.id})">Set Inactive</a>
                    <a href="#" onclick="addToGoal(${goal.id})">Add Money</a>
                    <hr>
                    <a href="#" onclick="deleteGoal(${goal.id})" class="danger">Delete Goal</a>
                `;
            } else {
                menuOptions = `
                    <a href="#" onclick="editGoal(${goal.id})">Edit Goal</a>
                    <a href="#" onclick="resumeGoal(${goal.id})">Activate Goal</a>
                    <a href="#" onclick="addToGoal(${goal.id})">Add Money</a>
                    <hr>
                    <a href="#" onclick="deleteGoal(${goal.id})" class="danger">Delete Goal</a>
                `;
            }
            
            return `
                <div class="goal-card ${goal.goal_type} ${isCompleted ? 'completed' : statusClass}" data-status="${status}" data-goal-id="${goal.id}">
                    <div class="goal-header">
                        <div class="goal-info">
                            <h4>${goalIcon} ${goal.goal_name}</h4>
                            <div class="goal-meta">
                                ${statusBadge}
                                <span class="priority-badge ${goal.priority}">${goal.priority.toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="goal-menu">
                            <button class="menu-btn" onclick="toggleGoalMenu(this)">⋯</button>
                            <div class="goal-dropdown">
                                ${menuOptions}
                            </div>
                        </div>
                    </div>

                    <div class="goal-progress">
                        <div class="progress-circle" data-percentage="${percentage.toFixed(0)}">
                            <div class="circle-inner">
                                <span class="percentage">${percentage.toFixed(0)}%</span>
                            </div>
                        </div>
                        <div class="progress-details">
                            <div class="current-amount">₵${parseFloat(goal.current_amount).toFixed(2)}</div>
                            <div class="target-amount">of ₵${parseFloat(goal.target_amount).toFixed(2)}</div>
                            <div class="remaining">${isCompleted ? 'Goal Achieved! 🎉' : `₵${remaining.toFixed(2)} to go`}</div>
                        </div>
                    </div>

                    <div class="goal-timeline">
                        <div class="timeline-item">
                            <span class="timeline-label">Started:</span>
                            <span class="timeline-value">${new Date(goal.created_at).toLocaleDateString('en-US', {month: 'short', year: 'numeric'})}</span>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-label">Target:</span>
                            <span class="timeline-value">${goal.target_date ? new Date(goal.target_date).toLocaleDateString('en-US', {month: 'short', year: 'numeric'}) : 'No deadline'}</span>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-label">Auto-Save:</span>
                            <span class="timeline-value auto-save-status ${autoSaveClass}">${autoSaveStatus}</span>
                        </div>
                    </div>

                    <div class="goal-actions">
                        ${actionButtons}
                    </div>
                </div>
            `;
        }).join('');
    }

    getGoalIcon(goalType) {
        const icons = {
            'emergency_fund': '🚨',
            'vacation': '🏖️',
            'car': '🚗',
            'house': '🏠',
            'education': '🎓',
            'other': '🎯'
        };
        return icons[goalType] || '🎯';
    }

    async loadRecentActivity() {
        try {
            const response = await fetch('/budget-app/actions/savings_handler.php?action=get_recent_activity', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            console.log('Recent activity loaded:', data);
            
            if (data.success) {
                this.updateRecentActivityDisplay(data.data || []);
            } else {
                console.error('Failed to load recent activity:', data.message);
                this.updateRecentActivityDisplay([]);
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
            this.updateRecentActivityDisplay([]);
        }
    }

    updateRecentActivityDisplay(activities) {
        const activityList = document.getElementById('activityList');
        if (!activityList) {
            console.log('Activity list element not found');
            return;
        }

        if (activities.length === 0) {
            activityList.innerHTML = '<p class="no-activity">No recent activity. Start saving to see your progress!</p>';
            return;
        }

        activityList.innerHTML = activities.map(activity => {
            const icon = this.getActivityIcon(activity.activity_type);
            const activityClass = this.getActivityClass(activity.activity_type);
            const amountDisplay = this.getActivityAmountDisplay(activity);
            
            return `
                <div class="activity-item">
                    <div class="activity-icon ${activityClass}">${icon}</div>
                    <div class="activity-details">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-meta">${activity.description} • ${this.formatDate(activity.created_at)}</div>
                    </div>
                    <div class="activity-amount ${amountDisplay.class}">${amountDisplay.text}</div>
                </div>
            `;
        }).join('');
    }

    getActivityIcon(activityType) {
        const icons = {
            'goal': '🎯',
            'contribution': '💵',
            'auto_save': '🔄',
            'achievement': '🏆'
        };
        return icons[activityType] || '💵';
    }

    getActivityClass(activityType) {
        const classes = {
            'goal': 'goal',
            'contribution': 'deposit',
            'auto_save': 'roundup',
            'achievement': 'achievement'
        };
        return classes[activityType] || 'deposit';
    }

    getActivityAmountDisplay(activity) {
        if (activity.activity_type === 'goal') {
            return { text: 'New Goal', class: 'neutral' };
        }
        if (activity.activity_type === 'achievement') {
            return { text: 'Completed', class: 'achievement' };
        }
        if (activity.amount > 0) {
            return { text: `+₵${parseFloat(activity.amount).toFixed(2)}`, class: 'positive' };
        }
        return { text: '₵0.00', class: 'neutral' };
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) return 'Today';
        if (diffDays === 2) return 'Yesterday';
        if (diffDays <= 7) return `${diffDays} days ago`;
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    populateGoalsDropdown() {
        const goalSelect = document.getElementById('depositGoal');
        if (!goalSelect) return;

        // Clear existing options (except the first one)
        goalSelect.innerHTML = '<option value="">Choose a goal</option>';

        // Add goals from currentGoals
        this.currentGoals.forEach(goal => {
            if (goal.current_amount < goal.target_amount) { // Only show incomplete goals
                const option = document.createElement('option');
                option.value = goal.id;
                option.textContent = `${this.getGoalIcon(goal.goal_type)} ${goal.goal_name} (₵${parseFloat(goal.current_amount).toFixed(2)} / ₵${parseFloat(goal.target_amount).toFixed(2)})`;
                goalSelect.appendChild(option);
            }
        });
    }

    updateMonthlyTargetDisplay() {
        // Calculate total monthly target from all active goals
        const activeGoals = this.currentGoals.filter(goal => goal.status === 'active');
        let totalMonthlyTarget = 0;
        
        const targetBreakdown = activeGoals.map(goal => {
            const remainingAmount = goal.target_amount - goal.current_amount;
            const remainingMonths = this.calculateRemainingMonths(goal.target_date);
            const monthlyTarget = remainingMonths > 0 ? remainingAmount / remainingMonths : remainingAmount;
            totalMonthlyTarget += monthlyTarget;
            
            return {
                name: goal.goal_name,
                monthlyTarget: monthlyTarget,
                remainingAmount: remainingAmount
            };
        });

        this.monthlyTargetFromGoals = totalMonthlyTarget;

        // Update the monthly target amount display
        const targetAmountElement = document.getElementById('monthlyTargetAmount');
        if (targetAmountElement) {
            targetAmountElement.textContent = `₵${totalMonthlyTarget.toFixed(2)}`;
        }

        // Update the percentage display to show combined percentage
        const targetPercentageElement = document.getElementById('monthlyTargetPercentage');
        if (targetPercentageElement && this.autoSaveSettings && this.autoSaveSettings.monthly_target) {
            const salaryPercentage = this.autoSaveSettings.monthly_target.salary_percentage || 0;
            targetPercentageElement.textContent = `${salaryPercentage}% of salary`;
        }

        // Update the breakdown display
        const targetBreakdownElement = document.getElementById('targetBreakdown');
        if (targetBreakdownElement && targetBreakdown.length > 0) {
            targetBreakdownElement.innerHTML = `
                <small>Active Goals Breakdown:</small>
                ${targetBreakdown.map(item => `
                    <div class="breakdown-item">
                        <span>${item.name}: ₵${item.monthlyTarget.toFixed(2)} (${(item.monthlyTarget/totalMonthlyTarget*100).toFixed(1)}%)</span>
                    </div>
                `).join('')}
            `;
        } else if (targetBreakdownElement) {
            targetBreakdownElement.innerHTML = '<small>No active auto-save goals</small>';
        }
    }

    calculateRemainingMonths(targetDate) {
        const today = new Date();
        const target = new Date(targetDate);
        const diffTime = target - today;
        const diffMonths = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30));
        return Math.max(diffMonths, 1);
    }

    updateAutoSaveStatus() {
        const statusDiv = document.getElementById('autoSaveStatus');
        if (!statusDiv) return;

        if (this.autoSaveSettings.enabled) {
            const method = this.autoSaveSettings.method === 'percentage' ? 
                `${this.autoSaveSettings.percentage}% of income` : 
                `GH₵ ${this.autoSaveSettings.fixed_amount}`;
            
            statusDiv.innerHTML = `
                <div class="auto-save-active">
                    <span class="status-indicator active"></span>
                    Auto-save is ON (${method})
                </div>
            `;
        } else {
            statusDiv.innerHTML = `
                <div class="auto-save-inactive">
                    <span class="status-indicator inactive"></span>
                    Auto-save is OFF
                </div>
            `;
        }
    }

    updateBudgetAllocationDisplay() {
        if (!this.budgetAllocation) {
            return;
        }

        // Update Budget Allocation Amount
        const allocationAmountElement = document.getElementById('budgetAllocationAmount');
        if (allocationAmountElement) {
            allocationAmountElement.textContent = `₵${this.budgetAllocation.allocated_savings.toFixed(2)}`;
        }

        // Update Budget Allocation Percentage
        const allocationPercentageElement = document.getElementById('budgetAllocationPercentage');
        if (allocationPercentageElement) {
            allocationPercentageElement.textContent = `${this.budgetAllocation.savings_percentage}% allocated`;
        }

        // Update progress bar
        const allocationProgressFill = document.getElementById('allocationProgressFill');
        const allocationProgressText = document.getElementById('allocationProgressText');
        
        if (allocationProgressFill && allocationProgressText) {
            const usedAmount = this.budgetAllocation.total_goal_amounts;
            const totalAllocated = this.budgetAllocation.allocated_savings;
            const remainingAmount = this.budgetAllocation.remaining_allocation;
            const usagePercentage = totalAllocated > 0 ? (usedAmount / totalAllocated) * 100 : 0;
            
            allocationProgressFill.style.width = `${Math.min(usagePercentage, 100)}%`;
            allocationProgressText.textContent = `₵${usedAmount.toFixed(2)} used • ₵${remainingAmount.toFixed(2)} remaining`;
            
            // Change color based on usage
            if (usagePercentage > 90) {
                allocationProgressFill.style.backgroundColor = '#ef4444'; // Red
            } else if (usagePercentage > 75) {
                allocationProgressFill.style.backgroundColor = '#f59e0b'; // Orange
            } else {
                allocationProgressFill.style.backgroundColor = '#10b981'; // Green
            }
        }

        // Update breakdown
        const allocationBreakdownElement = document.getElementById('allocationBreakdown');
        if (allocationBreakdownElement) {
            const percentageUsed = this.budgetAllocation.allocation_percentage_used;
            allocationBreakdownElement.innerHTML = `
                <small>Auto-save goals using ${percentageUsed.toFixed(1)}% of allocation</small>
            `;
        }

        // Update the monthly target to use budget allocation instead of salary percentage
        this.updateMonthlyTargetFromBudget();
    }

    updateMonthlyTargetFromBudget() {
        if (!this.budgetAllocation) return;

        const targetAmountElement = document.getElementById('monthlyTargetAmount');
        const targetPercentageElement = document.getElementById('monthlyTargetPercentage');
        
        if (targetAmountElement) {
            targetAmountElement.textContent = `₵${this.budgetAllocation.allocated_savings.toFixed(2)}`;
        }

        if (targetPercentageElement) {
            targetPercentageElement.textContent = `${this.budgetAllocation.savings_percentage}% of salary (Budget Allocation)`;
        }

        // Update progress based on current month's progress toward allocated amount
        const monthlyProgressFill = document.getElementById('monthlyProgressFill');
        const monthlyProgressText = document.getElementById('monthlyProgressText');
        
        if (monthlyProgressFill && monthlyProgressText) {
            const totalGoalContributions = this.budgetAllocation.total_goal_amounts;
            const allocatedAmount = this.budgetAllocation.allocated_savings;
            const progressPercentage = allocatedAmount > 0 ? (totalGoalContributions / allocatedAmount) * 100 : 0;
            const remaining = allocatedAmount - totalGoalContributions;
            
            monthlyProgressFill.style.width = `${Math.min(progressPercentage, 100)}%`;
            monthlyProgressText.textContent = `₵${totalGoalContributions.toFixed(2)} committed • ₵${remaining.toFixed(2)} available`;
        }

        const targetBreakdownElement = document.getElementById('targetBreakdown');
        if (targetBreakdownElement) {
            targetBreakdownElement.innerHTML = `
                <small>From budget allocation (${this.budgetAllocation.savings_percentage}% of monthly salary)</small>
            `;
        }
    }

    async handleNewGoal(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const goalId = formData.get('goal_id');
        
        if (goalId) {
            formData.append('action', 'update_goal');
        } else {
            formData.append('action', 'create_goal');
        }

        try {
            const response = await fetch('/budget-app/actions/savings_handler.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeModal('newGoalModal');
                e.target.reset();
                
                // Reset form to create mode
                const modal = document.getElementById('newGoalModal');
                const title = modal.querySelector('h3');
                const submitBtn = modal.querySelector('button[type="submit"]');
                title.textContent = 'Create New Savings Goal';
                submitBtn.textContent = 'Create Goal';
                
                // Remove goal_id hidden field
                const hiddenField = e.target.querySelector('input[name="goal_id"]');
                if (hiddenField) {
                    hiddenField.remove();
                }
                
                this.loadSavingsData();
                this.loadSavingsOverview();
                this.loadRecentActivity();
                
                const message = goalId ? 'Goal updated successfully!' : 'Goal created successfully!';
                
                // Show budget allocation feedback if available
                if (data.budget_check) {
                    const remaining = data.budget_check.remaining_allocation;
                    const allocatedSavings = data.budget_check.allocated_savings;
                    const usagePercentage = ((allocatedSavings - remaining) / allocatedSavings * 100).toFixed(1);
                    
                    this.showSnackbar(
                        `${message} Budget allocation: ${usagePercentage}% used, ₵${remaining.toFixed(2)} remaining.`, 
                        'success'
                    );
                } else {
                    this.showSnackbar(message, 'success');
                }
            } else {
                this.showSnackbar(data.message || 'Error saving goal', 'error');
            }
        } catch (error) {
            console.error('Error saving goal:', error);
            this.showSnackbar('Error saving goal', 'error');
        }
    }

    async handleDeposit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('action', 'add_contribution');

        try {
            const response = await fetch('/budget-app/actions/savings_handler.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeModal('depositModal');
                e.target.reset();
                this.loadSavingsData();
                this.loadSavingsOverview();
                this.loadRecentActivity();
                this.showSnackbar('Contribution added successfully!', 'success');
            } else {
                this.showSnackbar(data.message || 'Error adding contribution', 'error');
            }
        } catch (error) {
            console.error('Error adding contribution:', error);
            this.showSnackbar('Error adding contribution', 'error');
        }
    }

    async handleAutoSaveSettings(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('action', 'update_auto_save');

        try {
            const response = await fetch('/budget-app/actions/savings_handler.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeModal('autoSaveModal');
                this.loadSavingsData();
                this.showSnackbar('Auto-save settings updated!', 'success');
            } else {
                this.showSnackbar(data.message || 'Error updating auto-save settings', 'error');
            }
        } catch (error) {
            console.error('Error updating auto-save settings:', error);
            this.showSnackbar('Error updating auto-save settings', 'error');
        }
    }

    toggleAutoSaveMethod() {
        const method = document.getElementById('auto_save_method').value;
        const percentageField = document.getElementById('percentageField');
        const fixedAmountField = document.getElementById('fixedAmountField');

        if (method === 'percentage') {
            percentageField.style.display = 'block';
            fixedAmountField.style.display = 'none';
            document.getElementById('auto_save_percentage').required = true;
            document.getElementById('auto_save_fixed_amount').required = false;
        } else {
            percentageField.style.display = 'none';
            fixedAmountField.style.display = 'block';
            document.getElementById('auto_save_percentage').required = false;
            document.getElementById('auto_save_fixed_amount').required = true;
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }, 300);
        }
    }

    showSnackbar(message, type = 'info') {
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing SavingsManager');
    window.savingsManager = new SavingsManager();
    
    // Load saved theme
    const savedTheme = localStorage.getItem('personalTheme') || 'default';
    changeTheme(savedTheme);
});

console.log('Savings inline JS loaded - Global functions available');

// Theme functionality
function toggleThemeSelector() {
    const dropdown = document.getElementById('themeDropdown');
    dropdown.classList.toggle('show');
}

function changeTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    // Update active theme option
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('active');
    });
    document.querySelector(`[data-theme="${theme}"]`).classList.add('active');
    
    // Close dropdown
    document.getElementById('themeDropdown').classList.remove('show');
    
    // Save theme preference
    localStorage.setItem('personalTheme', theme);
}

// User menu functionality
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    // Close theme dropdown
    const themeDropdown = document.getElementById('themeDropdown');
    const themeToggle = document.querySelector('.theme-toggle-btn');
    if (themeDropdown && themeToggle && !themeToggle.contains(event.target) && !themeDropdown.contains(event.target)) {
        themeDropdown.classList.remove('show');
    }
    
    // Close user dropdown
    const userDropdown = document.getElementById('userDropdown');
    const userAvatar = document.getElementById('userAvatar');
    if (userDropdown && userAvatar && !userAvatar.contains(event.target) && !userDropdown.contains(event.target)) {
        userDropdown.classList.remove('show');
    }
    
    // Close goal dropdowns
    const goalDropdowns = document.querySelectorAll('.goal-dropdown');
    goalDropdowns.forEach(dropdown => {
        const menuBtn = dropdown.previousElementSibling;
        if (menuBtn && !menuBtn.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
});
    </script>
</body>
</html>