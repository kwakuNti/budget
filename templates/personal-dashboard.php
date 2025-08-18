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
    <title>Personal Dashboard - Nkansah Budget Manager</title>
    <link rel="stylesheet" href="../public/css/personal.css">
    <style>
        /* Enhanced Payday Countdown Styles */
        .payday-countdown-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark), var(--secondary-color));
            color: white;
            padding: 3rem 2rem;
            border-radius: 24px;
            text-align: center;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px var(--shadow-color), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .payday-countdown-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="grad" cx="50%" cy="50%" r="50%"><stop offset="0%" style="stop-color:rgba(255,255,255,0.1);stop-opacity:1" /><stop offset="100%" style="stop-color:rgba(255,255,255,0);stop-opacity:0" /></radialGradient></defs><circle cx="200" cy="200" r="150" fill="url(%23grad)"/><circle cx="800" cy="300" r="100" fill="url(%23grad)"/><circle cx="600" cy="700" r="120" fill="url(%23grad)"/></svg>') center/cover;
            pointer-events: none;
            opacity: 0.3;
        }

        .payday-countdown-hero h2 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .payday-countdown-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .countdown-main-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3rem;
            margin: 2rem 0;
            position: relative;
            z-index: 2;
        }

        .countdown-number-large {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #ffffff, #f0f9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .countdown-details {
            text-align: left;
        }

        .countdown-label-large {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .pay-date-display {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .progress-ring-large {
            position: relative;
        }

        .progress-ring-large svg {
            width: 140px;
            height: 140px;
            transform: rotate(-90deg);
        }

        .progress-ring-large .progress-percentage-large {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .salary-info-hero {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 16px;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .salary-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .salary-amount-hero {
            font-size: 2rem;
            font-weight: 800;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .setup-salary-btn-hero {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .setup-salary-btn-hero:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments for payday hero */
        @media (max-width: 768px) {
            .payday-countdown-hero {
                padding: 2rem 1rem;
                margin-bottom: 2rem;
            }

            .payday-countdown-hero h2 {
                font-size: 2rem;
            }

            .countdown-main-display {
                flex-direction: column;
                gap: 1.5rem;
            }

            .countdown-number-large {
                font-size: 4rem;
            }

            .countdown-details {
                text-align: center;
            }

            .salary-display {
                flex-direction: column;
                gap: 1rem;
            }

            .salary-amount-hero {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .payday-countdown-hero {
                padding: 1.5rem 1rem;
            }

            .payday-countdown-hero h2 {
                font-size: 1.8rem;
            }

            .payday-countdown-hero p {
                font-size: 1rem;
            }

            .countdown-number-large {
                font-size: 3rem;
            }

            .countdown-label-large {
                font-size: 1.2rem;
            }

            .progress-ring-large svg {
                width: 100px;
                height: 100px;
            }

            .progress-percentage-large {
                font-size: 1rem;
            }
        }

        /* Additional transaction styles */
        .transaction-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            opacity: 0.7;
        }

        .no-transactions {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
        }

        .no-transactions-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .no-transactions p {
            margin: 0.5rem 0;
            font-weight: 500;
        }

        .no-transactions small {
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Savings Goals Styles */
        .no-goals {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
        }

        .no-goals-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .no-goals p {
            margin: 0.5rem 0;
            font-weight: 500;
        }

        .no-goals small {
            font-size: 0.875rem;
            opacity: 0.7;
            display: block;
            margin-bottom: 1rem;
        }

        .create-goal-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .create-goal-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .goal-item {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .goal-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        .goal-item.high-priority {
            border-left: 4px solid #ef4444;
        }

        .goal-item.medium-priority {
            border-left: 4px solid #f59e0b;
        }

        .goal-item.low-priority {
            border-left: 4px solid #10b981;
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .goal-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .goal-interval {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: var(--background-secondary);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .goal-percentage {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-left: 0.5rem;
        }

        .goal-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .goal-action-btn {
            flex: 1;
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--primary-color);
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .goal-action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .goal-action-btn.secondary {
            background: transparent;
            color: var(--primary-color);
        }

        .goal-action-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>
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
            <!-- Hero Payday Countdown Section -->
            <section class="payday-countdown-hero">
                <h2>🎯 Next Payday Countdown</h2>
                <p>Stay motivated and track your financial progress</p>
                
                <div class="countdown-main-display">
                    <div class="countdown-number-large" id="daysUntilPayLarge">12</div>
                    <div class="countdown-details">
                        <div class="countdown-label-large">Days Until Payday</div>
                        <div class="pay-date-display" id="payDateTextLarge">January 28, 2025</div>
                    </div>
                    <div class="progress-ring-large">
                        <svg class="progress-ring-svg" width="140" height="140">
                            <circle class="progress-ring-circle-bg" cx="70" cy="70" r="60" stroke="rgba(255,255,255,0.2)" stroke-width="8" fill="transparent"/>
                            <circle class="progress-ring-circle" cx="70" cy="70" r="60" stroke="url(#gradient-large)" stroke-width="8" fill="transparent" stroke-dasharray="377" stroke-dashoffset="151" stroke-linecap="round"/>
                            <defs>
                                <linearGradient id="gradient-large" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#f0f9ff;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="progress-percentage-large" id="payProgressPercentageLarge">60%</div>
                    </div>
                </div>
                
                <div class="salary-info-hero">
                    <div class="salary-display">
                        <div class="salary-amount-hero" id="monthlySalaryHero">Monthly Salary: ₵0.00</div>
                        <button class="setup-salary-btn-hero" onclick="showSalarySetupModal()">⚙️ Setup Salary</button>
                        <button class="setup-salary-btn-hero" onclick="showSalaryPaidModal()">✅ I've Been Paid</button>
                    </div>
                </div>
            </section>

            <!-- Welcome Section with Quick Actions -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2 id="welcomeMessage">Welcome back, <?php echo htmlspecialchars($user_first_name); ?>!</h2>
                    <p id="salaryDueInfo">Ready to manage your finances today?</p>
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
                        <span class="btn-icon">📊</span>
                        View Budget
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

            <!-- Financial Insights & Advice -->
            <section class="insights-section">
                <div class="section-header">
                    <h3>💡 Financial Insights</h3>
                    <a href="insights.php" class="view-all">View All</a>
                </div>
                <div class="insights-grid" id="insightsGrid">
                    <!-- Dynamic insights will be populated here -->
                    <div class="insight-card success">
                        <div class="insight-icon">🎉</div>
                        <div class="insight-content">
                            <h4>Great Saving Habit!</h4>
                            <p>You're consistently saving 20% of your income. Keep up the excellent work!</p>
                            <button class="insight-action" onclick="navigateToSavings()">View Savings</button>
                        </div>
                    </div>
                    
                    <div class="insight-card tip">
                        <div class="insight-icon">💡</div>
                        <div class="insight-content">
                            <h4>Budget Optimization Tip</h4>
                            <p>Consider reviewing your "Wants" category - you have some room for reallocation.</p>
                            <button class="insight-action" onclick="navigateToBudget()">Optimize Budget</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dashboard Grid -->
            <section class="dashboard-grid">
                <!-- Savings Goals -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Savings Goals</h3>
                        <a href="savings.php" class="view-all">Manage</a>
                    </div>
                    <div class="savings-goals" id="savingsGoals">
                        <div class="goal-item">
                            <div class="goal-header">
                                <span class="goal-name">⏳ Loading goals...</span>
                                <span class="goal-interval">Please wait</span>
                            </div>
                            <div class="goal-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="goal-text">Loading savings goals...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Recent Transactions</h3>
                        <a href="personal-expense.php" class="view-all">View All</a>
                    </div>
                    <div class="transactions-list" id="recentTransactions">
                        <div class="transaction-item">
                            <div class="transaction-icon">⏳</div>
                            <div class="transaction-details">
                                <div class="transaction-name">Loading transactions...</div>
                                <div class="transaction-category">Please wait</div>
                            </div>
                            <div class="transaction-amount">₵0.00</div>
                        </div>
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
                            <input type="number" step="0.01" value="0" required>
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
                                <span class="allocation-amount">₵1,800</span>
                            </div>
                        </div>
                        <div class="allocation-row">
                            <label>Wants (Non-essential)</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="30" class="allocation-slider" data-category="wants">
                                <span class="allocation-percent">30%</span>
                                <span class="allocation-amount">₵1,080</span>
                            </div>
                        </div>
                        <div class="allocation-row">
                            <label>Savings & Investments</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="20" class="allocation-slider" data-category="savings">
                                <span class="allocation-percent">20%</span>
                                <span class="allocation-amount">₵720</span>
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
                        <optgroup label="Needs (50% - ₵1,800)">
                            <option value="needs-food">Food & Groceries</option>
                            <option value="needs-transport">Transportation</option>
                            <option value="needs-utilities">Utilities</option>
                            <option value="needs-rent">Rent/Housing</option>
                            <option value="needs-healthcare">Healthcare</option>
                        </optgroup>
                        <optgroup label="Wants (30% - ₵1,080)">
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

    <script>
        // Enhanced Dashboard JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dashboard
            loadDashboardData();
            updatePaydayCountdown();
            
            // Update data every 30 seconds
            setInterval(loadDashboardData, 30000);
            
            // Update countdown every hour
            setInterval(updatePaydayCountdown, 3600000);
        });

        // Animated number counting function
        function animateNumber(element, start, end, duration = 2000, prefix = '', suffix = '') {
            if (!element) return;
            
            const startTime = performance.now();
            const difference = end - start;
            
            function step(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = start + (difference * easeOutQuart);
                
                if (suffix === '%') {
                    element.textContent = prefix + Math.round(current) + suffix;
                } else {
                    element.textContent = prefix + current.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + suffix;
                }
                
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            }
            
            requestAnimationFrame(step);
        }

        function loadDashboardData() {
            fetch('../api/personal_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateDashboardUI(data);
                    } else {
                        console.error('Failed to load dashboard data:', data.message);
                        showSnackbar('Failed to load dashboard data', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                    showSnackbar('Error loading dashboard data', 'error');
                });
        }

        function updateDashboardUI(data) {
            const overview = data.financial_overview;
            const salary = data.salary;
            
            // Update welcome message with real name
            const welcomeElement = document.getElementById('welcomeMessage');
            if (welcomeElement && data.user) {
                welcomeElement.textContent = `Welcome back, ${data.user.first_name}!`;
            }
            
            // Update financial overview cards with animation
            const currentBalance = overview.available_balance || 0;
            const monthlyIncome = overview.monthly_income || 0;
            const monthlyExpenses = overview.monthly_expenses || 0;
            const autoSavings = calculateAutoSavings(data);
            
            animateNumber(document.getElementById('currentBalance'), 0, currentBalance, 2000, '₵');
            animateNumber(document.getElementById('monthlyIncome'), 0, monthlyIncome, 2000, '₵');
            animateNumber(document.getElementById('monthlyExpenses'), 0, monthlyExpenses, 2000, '₵');
            animateNumber(document.getElementById('autoSavings'), 0, autoSavings, 2000, '₵');
            
            // Update status text
            updateStatusText(data);
            
            // Update salary hero section
            updateSalaryHero(data);
            
            // Update recent transactions
            updateRecentTransactions(data.recent_transactions || []);
            
            // Update savings goals
            updateSavingsGoals(data.savings_goals || []);
            
            // Update payday countdown with real data
            updatePaydayCountdownData(data);
        }

        function calculateAutoSavings(data) {
            const allocation = data.budget_allocation;
            const overview = data.financial_overview;
            
            if (allocation && overview.monthly_income > 0) {
                const savingsPercentage = allocation.savings_percentage || 20;
                return (overview.monthly_income * savingsPercentage) / 100;
            }
            return 0;
        }

        function updateStatusText(data) {
            const overview = data.financial_overview;
            
            // Balance change
            const balanceChangeEl = document.getElementById('balanceChange');
            if (balanceChangeEl) {
                if (overview.available_balance > 0) {
                    balanceChangeEl.textContent = 'Available to spend';
                    balanceChangeEl.className = 'change positive';
                } else if (overview.available_balance < 0) {
                    balanceChangeEl.textContent = 'Overspent this month';
                    balanceChangeEl.className = 'change negative';
                } else {
                    balanceChangeEl.textContent = 'Balanced budget';
                    balanceChangeEl.className = 'change';
                }
            }
            
            // Next salary info
            const nextSalaryEl = document.getElementById('nextSalaryDate');
            if (nextSalaryEl && data.salary) {
                if (data.salary_confirmed) {
                    nextSalaryEl.textContent = 'Salary confirmed for this month';
                } else {
                    const payDate = new Date(data.salary.next_pay_date);
                    nextSalaryEl.textContent = `Next: ${payDate.toLocaleDateString()}`;
                }
            }
            
            // Budget remaining
            const budgetRemainingEl = document.getElementById('budgetRemaining');
            if (budgetRemainingEl) {
                const remaining = overview.monthly_income - overview.monthly_expenses;
                if (remaining > 0) {
                    budgetRemainingEl.textContent = `₵${remaining.toFixed(2)} remaining`;
                } else {
                    budgetRemainingEl.textContent = `₵${Math.abs(remaining).toFixed(2)} overspent`;
                }
            }
            
            // Savings percentage
            const savingsPercentageEl = document.getElementById('savingsPercentage');
            if (savingsPercentageEl && data.budget_allocation) {
                savingsPercentageEl.textContent = `${data.budget_allocation.savings_percentage || 20}% of income`;
            }
        }

        function updateSalaryHero(data) {
            const salaryAmountEl = document.getElementById('monthlySalaryHero');
            if (salaryAmountEl && data.salary) {
                const amount = data.salary.monthly_salary || 0;
                salaryAmountEl.textContent = `Monthly Salary: ₵${amount.toLocaleString()}`;
            }
        }

        function updateRecentTransactions(transactions) {
            const transactionsContainer = document.getElementById('recentTransactions');
            if (!transactionsContainer) return;
            
            if (!transactions || transactions.length === 0) {
                transactionsContainer.innerHTML = `
                    <div class="no-transactions">
                        <div class="no-transactions-icon">📝</div>
                        <p>No recent transactions</p>
                        <small>Your recent income and expenses will appear here</small>
                    </div>
                `;
                return;
            }
            
            const transactionsHTML = transactions.map(transaction => {
                const isIncome = transaction.type === 'income';
                const isExpense = transaction.type === 'expense';
                const amount = parseFloat(transaction.amount);
                
                // Format the transaction date
                const transactionDate = new Date(transaction.expense_date);
                const timeAgo = getTimeAgo(transactionDate);
                
                // Determine icon and styling based on transaction type
                let icon, amountClass, amountPrefix;
                if (isIncome) {
                    icon = '💰';
                    amountClass = 'income';
                    amountPrefix = '+';
                } else {
                    icon = '💸';
                    amountClass = 'expense';
                    amountPrefix = '-';
                }
                
                return `
                    <div class="transaction-item">
                        <div class="transaction-icon ${amountClass}">${icon}</div>
                        <div class="transaction-details">
                            <div class="transaction-name">${escapeHtml(transaction.description || 'Transaction')}</div>
                            <div class="transaction-category">${escapeHtml(transaction.category_name || 'Uncategorized')}</div>
                            <div class="transaction-time">${timeAgo}</div>
                        </div>
                        <div class="transaction-amount ${amountClass}">${amountPrefix}₵${amount.toFixed(2)}</div>
                    </div>
                `;
            }).join('');
            
            transactionsContainer.innerHTML = transactionsHTML;
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffTime = now - date;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
            const diffMinutes = Math.floor(diffTime / (1000 * 60));
            
            if (diffDays > 0) {
                return diffDays === 1 ? '1 day ago' : `${diffDays} days ago`;
            } else if (diffHours > 0) {
                return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
            } else if (diffMinutes > 0) {
                return diffMinutes === 1 ? '1 minute ago' : `${diffMinutes} minutes ago`;
            } else {
                return 'Just now';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function updateSavingsGoals(goals) {
            const savingsContainer = document.getElementById('savingsGoals');
            if (!savingsContainer) return;
            
            if (!goals || goals.length === 0) {
                savingsContainer.innerHTML = `
                    <div class="no-goals">
                        <div class="no-goals-icon">🎯</div>
                        <p>No savings goals set</p>
                        <small>Create your first savings goal to track your progress</small>
                        <button class="create-goal-btn" onclick="showCreateGoalModal()">Create Goal</button>
                    </div>
                `;
                return;
            }
            
            const goalsHTML = goals.map(goal => {
                const progressPercentage = goal.progress_percentage || 0;
                const isOnTrack = goal.is_on_track;
                const timeToTarget = getTimeToTarget(goal.target_date);
                
                // Format amounts
                const currentAmount = goal.current_amount || 0;
                const targetAmount = goal.target_amount || 0;
                
                // Determine priority styling
                const priorityClass = goal.priority === 'high' ? 'high-priority' : 
                                    goal.priority === 'medium' ? 'medium-priority' : 'low-priority';
                
                // Get goal type emoji
                const goalEmoji = getGoalEmoji(goal.goal_type);
                
                return `
                    <div class="goal-item ${priorityClass}">
                        <div class="goal-header">
                            <span class="goal-name">${goalEmoji} ${escapeHtml(goal.goal_name)}</span>
                            <span class="goal-interval">${timeToTarget}</span>
                        </div>
                        <div class="goal-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                            </div>
                            <div class="goal-text">
                                ₵${currentAmount.toLocaleString()} of ₵${targetAmount.toLocaleString()} saved
                                <span class="goal-percentage">(${Math.round(progressPercentage)}%)</span>
                            </div>
                        </div>
                        <div class="goal-actions">
                            <button class="goal-action-btn" onclick="addToGoal(${goal.id})">Add Money</button>
                            <button class="goal-action-btn secondary" onclick="editGoal(${goal.id})">Edit</button>
                        </div>
                    </div>
                `;
            }).join('');
            
            savingsContainer.innerHTML = goalsHTML;
        }

        function getTimeToTarget(targetDate) {
            if (!targetDate) return 'No deadline';
            
            const target = new Date(targetDate);
            const now = new Date();
            const diffTime = target.getTime() - now.getTime();
            const diffMonths = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffMonths > 12) {
                const years = Math.ceil(diffMonths / 12);
                return `${years} year${years > 1 ? 's' : ''} left`;
            } else if (diffMonths > 1) {
                return `${diffMonths} month${diffMonths > 1 ? 's' : ''} left`;
            } else if (diffDays > 0) {
                return `${diffDays} day${diffDays > 1 ? 's' : ''} left`;
            } else {
                return 'Due now!';
            }
        }

        function getGoalEmoji(goalType) {
            const emojiMap = {
                'emergency_fund': '🚨',
                'vacation': '✈️',
                'car': '🚗',
                'house': '🏠',
                'education': '🎓',
                'other': '💰'
            };
            return emojiMap[goalType] || '💰';
        }

        function addToGoal(goalId) {
            // Placeholder function - can be implemented later
            showSnackbar('Add money to goal feature coming soon!', 'info');
        }

        function editGoal(goalId) {
            // Placeholder function - can be implemented later
            showSnackbar('Edit goal feature coming soon!', 'info');
        }

        function showCreateGoalModal() {
            // Navigate to savings page to create a new goal
            window.location.href = 'savings.php';
        }

        // Navigation functions for insight actions
        function navigateToSavings() {
            window.location.href = 'savings.php';
        }

        function navigateToBudget() {
            window.location.href = 'budget.php';
        }

        function updatePaydayCountdownData(data) {
            if (!data.salary || !data.salary.next_pay_date) {
                // No salary data, use default countdown
                updatePaydayCountdown();
                return;
            }
            
            const nextPayDate = new Date(data.salary.next_pay_date);
            const today = new Date();
            const timeDiff = nextPayDate.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            // Update countdown displays
            const daysElement = document.getElementById('daysUntilPayLarge');
            const payDateElement = document.getElementById('payDateTextLarge');
            
            if (daysElement) {
                if (daysDiff <= 0) {
                    daysElement.textContent = '0';
                    if (payDateElement) {
                        payDateElement.textContent = 'Salary is due today!';
                    }
                } else {
                    animateNumber(daysElement, 0, daysDiff, 1000, '', '');
                    if (payDateElement) {
                        payDateElement.textContent = nextPayDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                }
            }
            
            // Calculate and animate progress
            updatePaydayProgress(data);
        }

        function updatePaydayProgress(data) {
            if (!data.salary) return;
            
            const payFrequency = data.salary.pay_frequency || 'monthly';
            let totalDays = 30; // default for monthly
            
            switch (payFrequency) {
                case 'weekly': totalDays = 7; break;
                case 'bi-weekly': totalDays = 14; break;
                case 'monthly': totalDays = 30; break;
                default: totalDays = 30;
            }
            
            const nextPayDate = new Date(data.salary.next_pay_date);
            const today = new Date();
            const daysDiff = Math.ceil((nextPayDate.getTime() - today.getTime()) / (1000 * 3600 * 24));
            
            const progressPercentage = Math.max(0, Math.min(100, ((totalDays - daysDiff) / totalDays) * 100));
            
            // Update progress ring
            const circle = document.querySelector('.progress-ring-circle');
            const radius = 60;
            const circumference = 2 * Math.PI * radius;
            const strokeDashoffset = circumference - (progressPercentage / 100) * circumference;
            
            if (circle) {
                circle.style.strokeDashoffset = strokeDashoffset;
            }
            
            const progressEl = document.getElementById('payProgressPercentageLarge');
            if (progressEl) {
                animateNumber(progressEl, 0, Math.round(progressPercentage), 1500, '', '%');
            }
        }

        function updatePaydayCountdown() {
            // Fallback function when no salary data is available
            const daysElement = document.getElementById('daysUntilPayLarge');
            const payDateElement = document.getElementById('payDateTextLarge');
            
            if (daysElement) {
                daysElement.textContent = '--';
            }
            if (payDateElement) {
                payDateElement.textContent = 'Set up your salary to see countdown';
            }
            
            const progressEl = document.getElementById('payProgressPercentageLarge');
            if (progressEl) {
                progressEl.textContent = '0%';
            }
        }

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

        // Modal functionality
        function showSalarySetupModal() {
            showModal('salarySetupModal');
        }

        function showAddIncomeModal() {
            showModal('addIncomeModal');
        }

        function showAddExpenseModal() {
            showModal('addExpenseModal');
        }

        function showSalaryPaidModal() {
            showModal('salaryPaidModal');
        }

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        function confirmSalaryFromDashboard() {
            console.log('Dashboard: Confirming salary received...');
            
            // Update button state
            const confirmBtn = document.querySelector('#salaryPaidModal .btn-primary');
            if (confirmBtn) {
                const originalText = confirmBtn.textContent;
                confirmBtn.textContent = 'Processing...';
                confirmBtn.disabled = true;
                
                // Make API call to confirm salary
                fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=confirm_salary_received'
                })
                .then(response => response.json())
                .then(result => {
                    console.log('Salary confirmation result:', result);
                    
                    if (result.success) {
                        showSnackbar(result.message, 'success');
                        closeModal('salaryPaidModal');
                        
                        // Refresh dashboard data to show updated income
                        setTimeout(() => {
                            loadDashboardData();
                            updatePaydayCountdown();
                        }, 1000);
                    } else {
                        showSnackbar(result.message || 'Failed to confirm salary', 'error');
                        
                        // Reset button state
                        confirmBtn.textContent = originalText;
                        confirmBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error confirming salary:', error);
                    showSnackbar('Failed to confirm salary - please try again', 'error');
                    
                    // Reset button state
                    confirmBtn.textContent = originalText;
                    confirmBtn.disabled = false;
                });
            } else {
                // Fallback if button not found
                fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=confirm_salary_received'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSnackbar(result.message, 'success');
                        closeModal('salaryPaidModal');
                        setTimeout(() => loadDashboardData(), 1000);
                    } else {
                        showSnackbar(result.message || 'Failed to confirm salary', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showSnackbar('Failed to confirm salary', 'error');
                });
            }
        }

        function navigateToSalarySetup() {
            window.location.href = 'budget.php';
        }

        // Snackbar notification function
        function showSnackbar(message, type = 'info') {
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

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.theme-selector')) {
                document.getElementById('themeDropdown').classList.remove('show');
            }
            if (!event.target.closest('.user-menu')) {
                document.getElementById('userDropdown').classList.remove('show');
            }
        });

        // Load saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('personalTheme') || 'default';
            changeTheme(savedTheme);
        });

        // Form handling for modals
        document.querySelectorAll('.modal-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const modalId = this.closest('.modal').id;
                let message = '';
                
                switch(modalId) {
                    case 'salarySetupModal':
                        message = 'Salary and budget setup saved successfully!';
                        break;
                    case 'addIncomeModal':
                        message = 'Income added successfully!';
                        break;
                    case 'addExpenseModal':
                        message = 'Expense recorded successfully!';
                        break;
                }
                
                showSnackbar(message, 'success');
                closeModal(modalId);
                
                // Reset form
                this.reset();
                
                // Refresh dashboard data
                setTimeout(loadDashboardData, 500);
            });
        });

        // Allocation slider functionality for salary setup modal
        document.addEventListener('DOMContentLoaded', function() {
            const sliders = document.querySelectorAll('.allocation-slider');
            const salaryInput = document.querySelector('input[type="number"][step="0.01"]');
            
            function updateAllocation() {
                const salary = parseFloat(salaryInput?.value || 3600);
                let total = 0;
                
                sliders.forEach(slider => {
                    const percentage = parseInt(slider.value);
                    const amount = (salary * percentage) / 100;
                    
                    const percentSpan = slider.parentElement.querySelector('.allocation-percent');
                    const amountSpan = slider.parentElement.querySelector('.allocation-amount');
                    
                    if (percentSpan) percentSpan.textContent = percentage + '%';
                    if (amountSpan) amountSpan.textContent = '₵' + amount.toLocaleString();
                    
                    total += percentage;
                });
                
                const totalSpan = document.getElementById('totalAllocation');
                if (totalSpan) {
                    totalSpan.textContent = total + '%';
                    totalSpan.style.color = total === 100 ? '#059669' : '#ef4444';
                }
            }
            
            sliders.forEach(slider => {
                slider.addEventListener('input', updateAllocation);
            });
            
            if (salaryInput) {
                salaryInput.addEventListener('input', updateAllocation);
            }
            
            // Initial update
            updateAllocation();
        });
    </script>
</body>
</html>