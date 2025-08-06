<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Dashboard - Nkansah Budget Manager</title>
    <link rel="stylesheet" href="../public/css/personal.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">💰</div>
                <div class="logo-text">
                    <h1 id="logoUserName">Personal</h1>
                    <p>Finance Dashboard</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard.php" class="nav-item active">Dashboard</a>
                <a href="salary.php" class="nav-item">Salary Setup</a>
                <a href="budget.php" class="nav-item">Budget</a>
                <a href="personal-expense.php" class="nav-item">Expenses</a>
                <a href="savings.php" class="nav-item">Savings</a>
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
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar">--</div>
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
            <!-- Welcome Section with Salary Info -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2 id="welcomeMessage">Welcome back!</h2>
                    <p id="salaryDueInfo">Loading salary information...</p>
                    <div class="salary-info">
                        <span class="salary-badge" id="monthlySalary">Monthly Salary: ₵0</span>
                        <button class="setup-salary-btn" onclick="showSalarySetupModal()">⚙️ Setup</button>
                    </div>
                </div>
                <div class="quick-actions">
                    <button class="quick-btn" onclick="showAddIncomeModal()">
                        <span class="btn-icon">💵</span>
                        Add Income
                    </button>
                    <button class="quick-btn" onclick="showAddExpenseModal()">
                        <span class="btn-icon">💸</span>
                        Add Expense
                    </button>
                    <button class="quick-btn" onclick="navigateToSalarySetup()">
                        <span class="btn-icon">⚙️</span>
                        Salary Setup
                    </button>
                    <button class="quick-btn" onclick="showSalaryPaidModal()">
                        <span class="btn-icon">💵</span>
                        I've Been Paid
                    </button>
                </div>
            </section>

            <!-- Financial Overview Cards -->
            <section class="overview-cards">
                <div class="card balance-card">
                    <div class="card-header">
                        <h3>Current Balance</h3>
                        <span class="card-icon">💳</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="currentBalance">₵0.00</div>
                        <div class="change" id="balanceChange">Loading...</div>
                    </div>
                </div>

                <div class="card income-card">
                    <div class="card-header">
                        <h3>This Month Income</h3>
                        <span class="card-icon">📈</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="monthlyIncome">₵0.00</div>
                        <div class="change" id="nextSalaryDate">Loading...</div>
                    </div>
                </div>

                <div class="card expense-card">
                    <div class="card-header">
                        <h3>This Month Expenses</h3>
                        <span class="card-icon">📊</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="monthlyExpenses">₵0.00</div>
                        <div class="change" id="budgetRemaining">Loading...</div>
                    </div>
                </div>

                <div class="card savings-card">
                    <div class="card-header">
                        <h3>Auto Savings</h3>
                        <span class="card-icon">🎯</span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="autoSavings">₵0.00</div>
                        <div class="change" id="savingsPercentage">Loading...</div>
                    </div>
                </div>
            </section>

            <!-- Salary-Based Budget Breakdown -->
            <section class="salary-breakdown">
                <div class="section-header">
                    <h3 id="salaryAllocationTitle">Budget Allocation & Preview</h3>
                </div>
                
                <!-- Budget Allocation Preview (Same as Salary Page) -->
                <div class="budget-allocation-preview" id="budgetAllocationPreview">
                    <div class="allocation-grid" id="previewAllocationGrid">
                        <div class="allocation-item needs">
                            <div class="allocation-header">
                                <span class="allocation-icon">🏠</span>
                                <div class="allocation-info">
                                    <h4>Needs</h4>
                                    <div class="allocation-display">
                                        <span class="allocation-percent" id="previewNeedsPercent">50%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="allocation-amount" id="previewNeedsAmount">₵0.00</div>
                            <div class="allocation-categories">
                                <span class="category-tag">Food</span>
                                <span class="category-tag">Rent</span>
                                <span class="category-tag">Utilities</span>
                                <span class="category-tag">Transport</span>
                            </div>
                        </div>

                        <div class="allocation-item wants">
                            <div class="allocation-header">
                                <span class="allocation-icon">🎮</span>
                                <div class="allocation-info">
                                    <h4>Wants</h4>
                                    <div class="allocation-display">
                                        <span class="allocation-percent" id="previewWantsPercent">30%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="allocation-amount" id="previewWantsAmount">₵0.00</div>
                            <div class="allocation-categories">
                                <span class="category-tag">Entertainment</span>
                                <span class="category-tag">Dining</span>
                                <span class="category-tag">Shopping</span>
                            </div>
                        </div>

                        <div class="allocation-item savings">
                            <div class="allocation-header">
                                <span class="allocation-icon">💰</span>
                                <div class="allocation-info">
                                    <h4>Savings</h4>
                                    <div class="allocation-display">
                                        <span class="allocation-percent" id="previewSavingsPercent">20%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="allocation-amount" id="previewSavingsAmount">₵0.00</div>
                            <div class="allocation-categories">
                                <span class="category-tag">Emergency Fund</span>
                                <span class="category-tag">Goals</span>
                            </div>
                        </div>
                    </div>
                    <div class="allocation-summary">
                        <div class="summary-item">
                            <span>Total Allocated:</span>
                            <strong id="previewTotalAllocated">100%</strong>
                        </div>
                        <div class="summary-item">
                            <span>Based on Salary:</span>
                            <strong id="previewBasedOnSalary">₵0.00</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Current Spending Progress -->
                <div class="spending-progress-section" id="spendingProgressSection">
                    <div class="section-header">
                        <h4>📊 Current Month Progress</h4>
                    </div>
                    <div class="allocation-grid" id="allocationGrid">
                        <!-- Dynamic allocation items will be populated here -->
                    </div>
                </div>
            </section>

            <!-- Financial Insights & Advice -->
            <!-- Financial Insights & Advice -->
            <section class="insights-section">
                <div class="section-header">
                    <h3>💡 Financial Insights</h3>
                    <a href="insights.php" class="view-all">View All</a>
                </div>
                <div class="insights-grid" id="insightsGrid">
                    <!-- Dynamic insights will be populated here -->
                </div>
            </section>            <!-- Dashboard Grid -->
            <section class="dashboard-grid">
                <!-- Savings Goals -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Savings Goals</h3>
                        <a href="savings.php" class="view-all">Manage</a>
                    </div>
                    <div class="savings-goals" id="savingsGoals">
                        <!-- Dynamic savings goals will be populated here -->
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Recent Transactions</h3>
                        <a href="personal-expense.php" class="view-all">View All</a>
                    </div>
                    <div class="transactions-list" id="recentTransactions">
                        <!-- Dynamic transaction items will be populated here -->
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modals -->
    <!-- Salary Setup Modal -->
    <div id="salarySetupModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Salary & Budget Setup</h3>
                <span class="close" onclick="closeModal('salarySetupModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-section">
                    <h4>Salary Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Monthly Salary (₵)</label>
                            <input type="number" step="0.01" value="3500" required>
                        </div>
                        <div class="form-group">
                            <label>Pay Frequency</label>
                            <select required>
                                <option value="monthly">Monthly</option>
                                <option value="bi-weekly">Bi-weekly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Next Pay Date</label>
                        <input type="date" value="2025-01-28" required>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Budget Allocation</h4>
                    <div class="allocation-setup">
                        <div class="allocation-row">
                            <label>Needs (Essential expenses)</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="50" class="allocation-slider" data-category="needs">
                                <span class="allocation-percent">50%</span>
                                <span class="allocation-amount">₵1,750</span>
                            </div>
                        </div>
                        <div class="allocation-row">
                            <label>Wants (Non-essential)</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="30" class="allocation-slider" data-category="wants">
                                <span class="allocation-percent">30%</span>
                                <span class="allocation-amount">₵1,050</span>
                            </div>
                        </div>
                        <div class="allocation-row">
                            <label>Savings & Investments</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="20" class="allocation-slider" data-category="savings">
                                <span class="allocation-percent">20%</span>
                                <span class="allocation-amount">₵700</span>
                            </div>
                        </div>
                    </div>
                    <div class="allocation-total">
                        <strong>Total: <span id="totalAllocation">100%</span></strong>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('salarySetupModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Setup</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Income Modal -->
    <div id="addIncomeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Income</h3>
                <span class="close" onclick="closeModal('addIncomeModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-group">
                    <label>Amount (₵)</label>
                    <input type="number" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Source</label>
                    <select required>
                        <option value="">Select source</option>
                        <option value="salary">Salary</option>
                        <option value="freelance">Freelance Project</option>
                        <option value="side-work">Side Work</option>
                        <option value="gift">Gift</option>
                        <option value="investment">Investment Return</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" placeholder="Brief description" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addIncomeModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Income</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Expense</h3>
                <span class="close" onclick="closeModal('addExpenseModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-group">
                    <label>Amount (₵)</label>
                    <input type="number" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Budget Category</label>
                    <select required>
                        <option value="">Select category</option>
                        <optgroup label="Needs (50% - ₵1,750)">
                            <option value="needs-food">Food & Groceries</option>
                            <option value="needs-transport">Transportation</option>
                            <option value="needs-utilities">Utilities</option>
                            <option value="needs-rent">Rent/Housing</option>
                            <option value="needs-healthcare">Healthcare</option>
                        </optgroup>
                        <optgroup label="Wants (30% - ₵1,050)">
                            <option value="wants-entertainment">Entertainment</option>
                            <option value="wants-shopping">Shopping</option>
                            <option value="wants-dining">Dining Out</option>
                            <option value="wants-hobbies">Hobbies</option>
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" placeholder="What was this for?" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addExpenseModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Salary Paid Confirmation Modal -->
    <div id="salaryPaidModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Salary Received</h3>
                <span class="close" onclick="closeModal('salaryPaidModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="salary-confirmation-info">
                    <div class="confirmation-icon">💰</div>
                    <h4>Did you receive your salary?</h4>
                    <p id="salaryConfirmationDetails">Confirming will add your salary amount to your current balance and update your next pay date.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('salaryPaidModal')">Cancel</button>
                    <button type="button" class="btn-primary" onclick="confirmSalaryFromDashboard()">✓ Yes, I received it</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../public/js/personal.js"></script>
</body>
</html>