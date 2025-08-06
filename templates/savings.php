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
                    <h1>Nkansah</h1>
                    <p>Personal Finance</p>
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
                <div class="user-avatar" onclick="toggleUserMenu()">JD</div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php">Profile Settings</a>
                    <a href="income-sources.php">Income Sources</a>
                    <a href="categories.php">Categories</a>
                    <hr>
                    <a href="family-dashboard.php">Switch to Family</a>
                    <a href="logout.php">Logout</a>
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
                        <div class="amount">₵12,450.75</div>
                        <div class="change positive">+₵850.00 this month</div>
                        <div class="savings-breakdown">
                            <span class="breakdown-item">Goals: ₵10,700.00</span>
                            <span class="breakdown-item">Emergency: ₵1,750.75</span>
                        </div>
                    </div>
                </div>

                <div class="overview-card monthly-target">
                    <div class="card-header">
                        <h3>Monthly Target</h3>
                        <span class="card-icon">📊</span>
                    </div>
                    <div class="card-content">
                        <div class="amount">₵700.00</div>
                        <div class="change">20% of salary</div>
                        <div class="target-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 85%"></div>
                            </div>
                            <span class="progress-text">₵595 saved • ₵105 to go</span>
                        </div>
                    </div>
                </div>

                <div class="overview-card savings-rate">
                    <div class="card-header">
                        <h3>Savings Rate</h3>
                        <span class="card-icon">📈</span>
                    </div>
                    <div class="card-content">
                        <div class="amount">17%</div>
                        <div class="change positive">+2% from last month</div>
                        <div class="rate-comparison">
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
                    <!-- Emergency Fund Goal -->
                    <div class="goal-card emergency" data-status="active">
                        <div class="goal-header">
                            <div class="goal-info">
                                <h4>🚨 Emergency Fund</h4>
                                <p class="goal-description">6 months of expenses</p>
                            </div>
                            <div class="goal-menu">
                                <button class="menu-btn" onclick="toggleGoalMenu(this)">⋯</button>
                                <div class="goal-dropdown">
                                    <a href="#" onclick="editGoal('emergency')">Edit Goal</a>
                                    <a href="#" onclick="pauseGoal('emergency')">Pause Goal</a>
                                    <a href="#" onclick="addToGoal('emergency')">Add Money</a>
                                    <hr>
                                    <a href="#" onclick="deleteGoal('emergency')" class="danger">Delete Goal</a>
                                </div>
                            </div>
                        </div>

                        <div class="goal-progress">
                            <div class="progress-circle" data-percentage="57">
                                <div class="circle-inner">
                                    <span class="percentage">57%</span>
                                </div>
                            </div>
                            <div class="progress-details">
                                <div class="current-amount">₵8,500.00</div>
                                <div class="target-amount">of ₵15,000.00</div>
                                <div class="remaining">₵6,500.00 to go</div>
                            </div>
                        </div>

                        <div class="goal-timeline">
                            <div class="timeline-item">
                                <span class="timeline-label">Started:</span>
                                <span class="timeline-value">Jan 2024</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Target:</span>
                                <span class="timeline-value">Dec 2025</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Monthly:</span>
                                <span class="timeline-value">₵500.00</span>
                            </div>
                        </div>

                        <div class="goal-actions">
                            <button class="goal-btn primary" onclick="addToGoal('emergency')">Add ₵500</button>
                            <button class="goal-btn secondary" onclick="viewGoalDetails('emergency')">Details</button>
                        </div>
                    </div>

                    <!-- Vacation Fund Goal -->
                    <div class="goal-card vacation" data-status="active">
                        <div class="goal-header">
                            <div class="goal-info">
                                <h4>🏖️ Vacation Fund</h4>
                                <p class="goal-description">Dream trip to Dubai</p>
                            </div>
                            <div class="goal-menu">
                                <button class="menu-btn" onclick="toggleGoalMenu(this)">⋯</button>
                                <div class="goal-dropdown">
                                    <a href="#" onclick="editGoal('vacation')">Edit Goal</a>
                                    <a href="#" onclick="pauseGoal('vacation')">Pause Goal</a>
                                    <a href="#" onclick="addToGoal('vacation')">Add Money</a>
                                    <hr>
                                    <a href="#" onclick="deleteGoal('vacation')" class="danger">Delete Goal</a>
                                </div>
                            </div>
                        </div>

                        <div class="goal-progress">
                            <div class="progress-circle" data-percentage="44">
                                <div class="circle-inner">
                                    <span class="percentage">44%</span>
                                </div>
                            </div>
                            <div class="progress-details">
                                <div class="current-amount">₵2,200.00</div>
                                <div class="target-amount">of ₵5,000.00</div>
                                <div class="remaining">₵2,800.00 to go</div>
                            </div>
                        </div>

                        <div class="goal-timeline">
                            <div class="timeline-item">
                                <span class="timeline-label">Started:</span>
                                <span class="timeline-value">Nov 2024</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Target:</span>
                                <span class="timeline-value">Aug 2025</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Bi-weekly:</span>
                                <span class="timeline-value">₵150.00</span>
                            </div>
                        </div>

                        <div class="goal-actions">
                            <button class="goal-btn primary" onclick="addToGoal('vacation')">Add ₵150</button>
                            <button class="goal-btn secondary" onclick="viewGoalDetails('vacation')">Details</button>
                        </div>
                    </div>

                    <!-- Car Fund Goal -->
                    <div class="goal-card car" data-status="active">
                        <div class="goal-header">
                            <div class="goal-info">
                                <h4>🚗 Car Fund</h4>
                                <p class="goal-description">Down payment for new car</p>
                            </div>
                            <div class="goal-menu">
                                <button class="menu-btn" onclick="toggleGoalMenu(this)">⋯</button>
                                <div class="goal-dropdown">
                                    <a href="#" onclick="editGoal('car')">Edit Goal</a>
                                    <a href="#" onclick="pauseGoal('car')">Pause Goal</a>
                                    <a href="#" onclick="addToGoal('car')">Add Money</a>
                                    <hr>
                                    <a href="#" onclick="deleteGoal('car')" class="danger">Delete Goal</a>
                                </div>
                            </div>
                        </div>

                        <div class="goal-progress">
                            <div class="progress-circle" data-percentage="8">
                                <div class="circle-inner">
                                    <span class="percentage">8%</span>
                                </div>
                            </div>
                            <div class="progress-details">
                                <div class="current-amount">₵800.00</div>
                                <div class="target-amount">of ₵10,000.00</div>
                                <div class="remaining">₵9,200.00 to go</div>
                            </div>
                        </div>

                        <div class="goal-timeline">
                            <div class="timeline-item">
                                <span class="timeline-label">Started:</span>
                                <span class="timeline-value">Dec 2024</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Target:</span>
                                <span class="timeline-value">Dec 2026</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Monthly:</span>
                                <span class="timeline-value">₵400.00</span>
                            </div>
                        </div>

                        <div class="goal-actions">
                            <button class="goal-btn primary" onclick="addToGoal('car')">Add ₵400</button>
                            <button class="goal-btn secondary" onclick="viewGoalDetails('car')">Details</button>
                        </div>
                    </div>

                    <!-- Completed Goal Example -->
                    <div class="goal-card laptop completed" data-status="completed">
                        <div class="goal-header">
                            <div class="goal-info">
                                <h4>💻 MacBook Pro</h4>
                                <p class="goal-description">Completed Dec 2024</p>
                            </div>
                            <div class="completed-badge">✅ Completed</div>
                        </div>

                        <div class="goal-progress">
                            <div class="progress-circle completed" data-percentage="100">
                                <div class="circle-inner">
                                    <span class="percentage">100%</span>
                                </div>
                            </div>
                            <div class="progress-details">
                                <div class="current-amount">₵4,500.00</div>
                                <div class="target-amount">of ₵4,500.00</div>
                                <div class="completed-text">Goal Achieved! 🎉</div>
                            </div>
                        </div>

                        <div class="goal-timeline">
                            <div class="timeline-item">
                                <span class="timeline-label">Started:</span>
                                <span class="timeline-value">May 2024</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Completed:</span>
                                <span class="timeline-value">Dec 2024</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Duration:</span>
                                <span class="timeline-value">7 months</span>
                            </div>
                        </div>

                        <div class="goal-actions">
                            <button class="goal-btn secondary" onclick="viewGoalDetails('laptop')">View Details</button>
                            <button class="goal-btn secondary" onclick="archiveGoal('laptop')">Archive</button>
                        </div>
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

                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon deposit">💵</div>
                        <div class="activity-details">
                            <div class="activity-title">Emergency Fund Deposit</div>
                            <div class="activity-meta">Auto-save from salary • Today</div>
                        </div>
                        <div class="activity-amount positive">+₵500.00</div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon deposit">🔄</div>
                        <div class="activity-details">
                            <div class="activity-title">Vacation Fund Contribution</div>
                            <div class="activity-meta">Manual deposit • Jan 10</div>
                        </div>
                        <div class="activity-amount positive">+₵150.00</div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon roundup">🔄</div>
                        <div class="activity-details">
                            <div class="activity-title">Round-up Savings</div>
                            <div class="activity-meta">Weekly collection • Jan 8</div>
                        </div>
                        <div class="activity-amount positive">+₵8.50</div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon goal">🎯</div>
                        <div class="activity-details">
                            <div class="activity-title">Car Fund Goal Created</div>
                            <div class="activity-meta">Target: ₵10,000 • Jan 5</div>
                        </div>
                        <div class="activity-amount neutral">New Goal</div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon achievement">🏆</div>
                        <div class="activity-details">
                            <div class="activity-title">MacBook Pro Goal Completed</div>
                            <div class="activity-meta">₵4,500 saved in 7 months • Dec 28</div>
                        </div>
                        <div class="activity-amount achievement">Completed</div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modals -->
    <!-- New Goal Modal -->
    <div id="newGoalModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Create New Savings Goal</h3>
                <span class="close" onclick="closeModal('newGoalModal')">&times;</span>
            </div>
            <form class="modal-form" onsubmit="createNewGoal(event)">
                <div class="form-section">
                    <h4>Goal Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Goal Name</label>
                            <input type="text" id="goalName" placeholder="e.g., Dream Vacation" required>
                        </div>
                        <div class="form-group">
                            <label>Goal Icon</label>
                            <select id="goalIcon" required>
                                <option value="">Choose icon</option>
                                <option value="🏠">🏠 House</option>
                                <option value="🚗">🚗 Car</option>
                                <option value="🏖️">🏖️ Vacation</option>
                                <option value="💻">💻 Electronics</option>
                                <option value="🎓">🎓 Education</option>
                                <option value="💍">💍 Wedding</option>
                                <option value="👶">👶 Baby Fund</option>
                                <option value="🏥">🏥 Medical</option>
                                <option value="🎯">🎯 General</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="goalDescription" rows="3" placeholder="What is this goal for?"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Financial Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Target Amount (₵)</label>
                            <input type="number" id="targetAmount" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Target Date</label>
                            <input type="date" id="targetDate" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Save Frequency</label>
                            <select id="saveFrequency" required>
                                <option value="monthly">Monthly</option>
                                <option value="bi-weekly">Bi-weekly</option>
                                <option value="weekly">Weekly</option>
                                <option value="manual">Manual Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount per Save (₵)</label>
                            <input type="number" id="saveAmount" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Initial Deposit (₵)</label>
                        <input type="number" id="initialDeposit" step="0.01" placeholder="0.00">
                    </div>
                </div>

                <div class="form-section">
                    <h4>Auto-Save Settings</h4>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="autoSaveEnabled">
                            <span class="checkmark"></span>
                            Enable automatic savings for this goal
                        </label>
                    </div>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="roundUpEnabled">
                            <span class="checkmark"></span>
                            Include round-up savings
                        </label>
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
    <div id="depositModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Deposit</h3>
                <span class="close" onclick="closeModal('depositModal')">&times;</span>
            </div>
            <form class="modal-form" onsubmit="addDeposit(event)">
                <div class="form-group">
                    <label>Select Goal</label>
                    <select id="depositGoal" required>
                        <option value="">Choose a goal</option>
                        <option value="emergency">🚨 Emergency Fund</option>
                        <option value="vacation">🏖️ Vacation Fund</option>
                        <option value="car">🚗 Car Fund</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₵)</label>
                    <input type="number" id="depositAmount" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Source</label>
                    <select id="depositSource" required>
                        <option value="">Select source</option>
                        <option value="salary">From Salary</option>
                        <option value="bonus">Bonus/Extra Income</option>
                        <option value="manual">Manual Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Note (Optional)</label>
                    <input type="text" id="depositNote" placeholder="Add a note...">
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

    <script src="../public/js/personal.js"></script>
    <script src="../public/js/savings.js"></script>
</body>
</html>