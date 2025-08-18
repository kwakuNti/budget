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
    <title>Expenses</title>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/personal-expense.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-content {
            padding: 20px;
            min-height: 400px;
        }
        
        .chart-section {
            position: relative;
            height: 400px;
        }
        
        .chart-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .chart-btn {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .chart-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        
        .chart-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .view-icon {
            font-size: 16px;
        }
        
        .no-expenses {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        
        .no-expenses p {
            margin-bottom: 16px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">💰</div>
                <div class="logo-text">
                    <h1 id="logoUserName"><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Expense Dashboard</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard.php" class="nav-item">Dashboard</a>
                <a href="salary.php" class="nav-item ">Salary Setup</a>
                <a href="budget.php" class="nav-item">Budget</a>
                <a href="personal-expense.php" class="nav-item active">Expenses</a>
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
            <!-- Page Header -->
            <section class="page-header">
                <div class="page-title">
                    <h2>💸 Expense Tracker</h2>
                    <p>Monitor and manage your spending across all categories</p>
                </div>
                <button class="quick-btn" onclick="showAddExpenseModal()">
                    <span class="btn-icon">➕</span>
                    Add Expense
                </button>
            </section>

            <!-- Expense Summary Cards -->
            <section class="expense-summary">
                <div class="summary-card total">
                    <div class="summary-header">
                        <h3>Total Spent This Month</h3>
                        <span class="summary-icon">💳</span>
                    </div>
                    <div class="summary-amount" id="totalSpent">₵0.00</div>
                    <div class="summary-change" id="monthChange">Loading...</div>
                </div>

                <div class="summary-card needs">
                    <div class="summary-header">
                        <h3>Needs Expenses</h3>
                        <span class="summary-icon">🏠</span>
                    </div>
                    <div class="summary-amount" id="needsSpent">₵0.00</div>
                    <div class="summary-budget" id="needsBudget">Loading...</div>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill needs-fill" id="needsProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card wants">
                    <div class="summary-header">
                        <h3>Wants Expenses</h3>
                        <span class="summary-icon">🎮</span>
                    </div>
                    <div class="summary-amount" id="wantsSpent">₵0.00</div>
                    <div class="summary-budget" id="wantsBudget">Loading...</div>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill wants-fill" id="wantsProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card daily">
                    <div class="summary-header">
                        <h3>Daily Average</h3>
                        <span class="summary-icon">📊</span>
                    </div>
                    <div class="summary-amount" id="dailyAverage">₵0.00</div>
                    <div class="summary-change" id="dailyTarget">Loading...</div>
                </div>
            </section>

            <!-- Filters and Controls -->
            <section class="expense-controls">
                <div class="controls-left">
                    <div class="filter-group">
                        <label>Filter by Category:</label>
                        <select id="categoryFilter" onchange="filterExpenses()">
                            <option value="all">All Categories</option>
                            <optgroup label="Needs">
                                <option value="needs-food">Food & Groceries</option>
                                <option value="needs-transport">Transportation</option>
                                <option value="needs-utilities">Utilities</option>
                                <option value="needs-rent">Rent/Housing</option>
                                <option value="needs-healthcare">Healthcare</option>
                            </optgroup>
                            <optgroup label="Wants">
                                <option value="wants-entertainment">Entertainment</option>
                                <option value="wants-shopping">Shopping</option>
                                <option value="wants-dining">Dining Out</option>
                                <option value="wants-hobbies">Hobbies</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Time Period:</label>
                        <select id="timeFilter" onchange="filterExpenses()">
                            <option value="this-month">This Month</option>
                            <option value="last-month">Last Month</option>
                            <option value="this-week">This Week</option>
                            <option value="last-week">Last Week</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <input type="text" id="searchExpenses" placeholder="Search expenses..." onkeyup="searchExpenses()">
                        <span class="search-icon">🔍</span>
                    </div>
                </div>

                <div class="controls-right">
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="setView('list')" data-view="list">
                            <span class="view-icon">📋</span>
                            List
                        </button>
                        <button class="view-btn" onclick="setView('chart')" data-view="chart">
                            <span class="view-icon">📊</span>
                            Chart
                        </button>
                    </div>
                </div>
            </section>

            <!-- Expense Categories Breakdown -->
            <section class="category-breakdown">
                <div class="section-header">
                    <h3>Category Breakdown</h3>
                    <span class="breakdown-period">January 2025</span>
                </div>
                <div class="category-grid">
                    <div class="category-item food">
                        <div class="category-header">
                            <span class="category-icon">🛒</span>
                            <div class="category-info">
                                <h4>Food & Groceries</h4>
                                <p>8 transactions</p>
                            </div>
                            <div class="category-amount">₵450.00</div>
                        </div>
                        <div class="category-details">
                            <div class="detail-row">
                                <span>Budget: ₵500</span>
                                <span class="status good">₵50 left</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 90%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="category-item transport">
                        <div class="category-header">
                            <span class="category-icon">⛽</span>
                            <div class="category-info">
                                <h4>Transportation</h4>
                                <p>12 transactions</p>
                            </div>
                            <div class="category-amount">₵380.00</div>
                        </div>
                        <div class="category-details">
                            <div class="detail-row">
                                <span>Budget: ₵400</span>
                                <span class="status good">₵20 left</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 95%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="category-item utilities">
                        <div class="category-header">
                            <span class="category-icon">💡</span>
                            <div class="category-info">
                                <h4>Utilities</h4>
                                <p>3 transactions</p>
                            </div>
                            <div class="category-amount">₵220.00</div>
                        </div>
                        <div class="category-details">
                            <div class="detail-row">
                                <span>Budget: ₵300</span>
                                <span class="status good">₵80 left</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 73%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="category-item entertainment">
                        <div class="category-header">
                            <span class="category-icon">🎬</span>
                            <div class="category-info">
                                <h4>Entertainment</h4>
                                <p>5 transactions</p>
                            </div>
                            <div class="category-amount">₵320.00</div>
                        </div>
                        <div class="category-details">
                            <div class="detail-row">
                                <span>Budget: ₵400</span>
                                <span class="status good">₵80 left</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill wants-fill" style="width: 80%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="category-item shopping">
                        <div class="category-header">
                            <span class="category-icon">🛍️</span>
                            <div class="category-info">
                                <h4>Shopping</h4>
                                <p>6 transactions</p>
                            </div>
                            <div class="category-amount">₵260.00</div>
                        </div>
                        <div class="category-details">
                            <div class="detail-row">
                                <span>Budget: ₵300</span>
                                <span class="status good">₵40 left</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill wants-fill" style="width: 87%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="category-item healthcare">
                        <div class="category-header">
                            <span class="category-icon">🏥</span>
                            <div class="category-info">
                                <h4>Healthcare</h4>
                                <p>2 transactions</p>
                            </div>
                            <div class="category-amount">₵150.00</div>
                        </div>
                        <div class="category-details">
                            <div class="detail-row">
                                <span>Budget: ₵200</span>
                                <span class="status good">₵50 left</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Expense List/Chart Container -->
            <section class="expense-content">
                <!-- List View -->
                <div id="expenseList" class="expense-list-container">
                    <div class="section-header">
                        <h3>Recent Expenses</h3>
                        <div class="list-controls">
                            <button class="sort-btn" onclick="sortExpenses('date')">Sort by Date</button>
                            <button class="sort-btn" onclick="sortExpenses('amount')">Sort by Amount</button>
                        </div>
                    </div>
                    
                    <div class="expense-list">
                        <div class="expense-item" data-category="needs-food" data-date="2025-01-20">
                            <div class="expense-icon food">🛒</div>
                            <div class="expense-details">
                                <div class="expense-name">Grocery Shopping</div>
                                <div class="expense-meta">
                                    <span class="expense-category">Food & Groceries</span>
                                    <span class="expense-date">Jan 20, 2025</span>
                                </div>
                                <div class="expense-description">Weekly groceries at MaxMart</div>
                            </div>
                            <div class="expense-amount">₵245.50</div>
                            <div class="expense-actions">
                                <button class="action-btn edit" onclick="editExpense(1)">✏️</button>
                                <button class="action-btn delete" onclick="deleteExpense(1)">🗑️</button>
                            </div>
                        </div>

                        <div class="expense-item" data-category="needs-transport" data-date="2025-01-19">
                            <div class="expense-icon transport">⛽</div>
                            <div class="expense-details">
                                <div class="expense-name">Fuel</div>
                                <div class="expense-meta">
                                    <span class="expense-category">Transportation</span>
                                    <span class="expense-date">Jan 19, 2025</span>
                                </div>
                                <div class="expense-description">Full tank at Shell station</div>
                            </div>
                            <div class="expense-amount">₵180.00</div>
                            <div class="expense-actions">
                                <button class="action-btn edit" onclick="editExpense(2)">✏️</button>
                                <button class="action-btn delete" onclick="deleteExpense(2)">🗑️</button>
                            </div>
                        </div>

                        <div class="expense-item" data-category="wants-entertainment" data-date="2025-01-18">
                            <div class="expense-icon entertainment">🎬</div>
                            <div class="expense-details">
                                <div class="expense-name">Movie Night</div>
                                <div class="expense-meta">
                                    <span class="expense-category">Entertainment</span>
                                    <span class="expense-date">Jan 18, 2025</span>
                                </div>
                                <div class="expense-description">Cinema tickets and snacks</div>
                            </div>
                            <div class="expense-amount">₵85.00</div>
                            <div class="expense-actions">
                                <button class="action-btn edit" onclick="editExpense(3)">✏️</button>
                                <button class="action-btn delete" onclick="deleteExpense(3)">🗑️</button>
                            </div>
                        </div>

                        <div class="expense-item" data-category="needs-utilities" data-date="2025-01-17">
                            <div class="expense-icon utilities">💡</div>
                            <div class="expense-details">
                                <div class="expense-name">Electricity Bill</div>
                                <div class="expense-meta">
                                    <span class="expense-category">Utilities</span>
                                    <span class="expense-date">Jan 17, 2025</span>
                                </div>
                                <div class="expense-description">Monthly electricity payment</div>
                            </div>
                            <div class="expense-amount">₵120.00</div>
                            <div class="expense-actions">
                                <button class="action-btn edit" onclick="editExpense(4)">✏️</button>
                                <button class="action-btn delete" onclick="deleteExpense(4)">🗑️</button>
                            </div>
                        </div>

                        <div class="expense-item" data-category="wants-shopping" data-date="2025-01-16">
                            <div class="expense-icon shopping">🛍️</div>
                            <div class="expense-details">
                                <div class="expense-name">New Shoes</div>
                                <div class="expense-meta">
                                    <span class="expense-category">Shopping</span>
                                    <span class="expense-date">Jan 16, 2025</span>
                                </div>
                                <div class="expense-description">Running shoes from Nike store</div>
                            </div>
                            <div class="expense-amount">₵450.00</div>
                            <div class="expense-actions">
                                <button class="action-btn edit" onclick="editExpense(5)">✏️</button>
                                <button class="action-btn delete" onclick="deleteExpense(5)">🗑️</button>
                            </div>
                        </div>

                        <div class="expense-item" data-category="needs-transport" data-date="2025-01-15">
                            <div class="expense-icon transport">🚌</div>
                            <div class="expense-details">
                                <div class="expense-name">Public Transport</div>
                                <div class="expense-meta">
                                    <span class="expense-category">Transportation</span>
                                    <span class="expense-date">Jan 15, 2025</span>
                                </div>
                                <div class="expense-description">Weekly tro-tro and taxi fares</div>
                            </div>
                            <div class="expense-amount">₵45.00</div>
                            <div class="expense-actions">
                                <button class="action-btn edit" onclick="editExpense(6)">✏️</button>
                                <button class="action-btn delete" onclick="deleteExpense(6)">🗑️</button>
                            </div>
                        </div>
                    </div>

                    <div class="load-more">
                        <button class="btn-secondary" onclick="loadMoreExpenses()">Load More Expenses</button>
                    </div>
                </div>

                <!-- Chart View -->
                <div id="expenseChart" class="expense-chart-container" style="display: none;">
                    <div class="section-header">
                        <h3>Expense Analytics</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" onclick="showChart('spending')" data-chart="spending">
                                <span class="view-icon">📈</span>
                                Spending Trend
                            </button>
                            <button class="chart-btn" onclick="showChart('category')" data-chart="category">
                                <span class="view-icon">🥧</span>
                                By Category
                            </button>
                            <button class="chart-btn" onclick="showChart('budget')" data-chart="budget">
                                <span class="view-icon">🎯</span>
                                Budget vs Actual
                            </button>
                        </div>
                    </div>
                    
                    <div class="chart-content">
                        <!-- Spending Trend Chart -->
                        <div id="spendingChart" class="chart-section active">
                            <canvas id="spendingTrendCanvas" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Category Pie Chart -->
                        <div id="categoryChart" class="chart-section" style="display: none;">
                            <canvas id="categoryPieCanvas" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Budget vs Actual Chart -->
                        <div id="budgetChart" class="chart-section" style="display: none;">
                            <canvas id="budgetComparisonCanvas" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Add/Edit Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Expense</h3>
                <span class="close" onclick="closeModal('addExpenseModal')">&times;</span>
            </div>
            <form class="modal-form" onsubmit="saveExpense(event)">
                <div class="form-group">
                    <label>Amount (₵)</label>
                    <input type="number" step="0.01" placeholder="0.00" id="expenseAmount" required>
                </div>
                
                <div class="form-group">
                    <label>Budget Category</label>
                    <select id="expenseCategory" onchange="updateCategoryBudget()" required>
                        <option value="">Loading categories...</option>
                    </select>
                    <div id="categoryBudgetInfo" class="category-budget-info" style="display: none;">
                        <span id="budgetStatus"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <input type="text" placeholder="What was this expense for?" id="expenseDescription" required>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="expenseDate" required>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select id="paymentMethod">
                        <option value="cash">Cash</option>
                        <option value="card">Debit Card</option>
                        <option value="mobile">Mobile Money</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea placeholder="Additional notes..." id="expenseNotes" rows="3"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addExpenseModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set today's date as default
            document.getElementById('expenseDate').value = new Date().toISOString().split('T')[0];
            
            // Load saved theme
            const savedTheme = localStorage.getItem('personalTheme') || 'default';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Set active theme option
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            const activeOption = document.querySelector(`[data-theme="${savedTheme}"]`);
            if (activeOption) {
                activeOption.classList.add('active');
            }
            
            // Initialize page features
            loadCategories();
            loadExpenseSummary();
            loadCategoryBreakdown();
            loadRecentExpenses();
            initializeCharts();
        });

        // Toggle user menu
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                document.getElementById('userDropdown').classList.remove('show');
            }
            if (!e.target.closest('.theme-selector')) {
                document.getElementById('themeDropdown').classList.remove('show');
            }
        });

        // Theme Functions
        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            dropdown.classList.toggle('show');
        }

        function changeTheme(theme) {
            // Set the data-theme attribute directly - no conversion needed
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('personalTheme', theme);
            
            // Update active theme option
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`[data-theme="${theme}"]`).classList.add('active');
            
            // Close theme dropdown
            document.getElementById('themeDropdown').classList.remove('show');
        }

        // Modal functions
        function showAddExpenseModal() {
            const modal = document.getElementById('addExpenseModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Expense functions
        function filterExpenses() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const timeFilter = document.getElementById('timeFilter').value;
            const expenses = document.querySelectorAll('.expense-item');
            
            expenses.forEach(expense => {
                const category = expense.dataset.category;
                let showExpense = true;
                
                if (categoryFilter !== 'all' && category !== categoryFilter) {
                    showExpense = false;
                }
                
                expense.style.display = showExpense ? 'flex' : 'none';
            });
        }

        function searchExpenses() {
            const searchTerm = document.getElementById('searchExpenses').value.toLowerCase();
            const expenses = document.querySelectorAll('.expense-item');
            
            expenses.forEach(expense => {
                const name = expense.querySelector('.expense-name').textContent.toLowerCase();
                const description = expense.querySelector('.expense-description').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                    expense.style.display = 'flex';
                } else {
                    expense.style.display = 'none';
                }
            });
        }

        function setView(view) {
            const listView = document.getElementById('expenseList');
            const chartView = document.getElementById('expenseChart');
            const viewBtns = document.querySelectorAll('.view-btn');
            
            viewBtns.forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[data-view="${view}"]`).classList.add('active');
            
            if (view === 'list') {
                listView.style.display = 'block';
                chartView.style.display = 'none';
            } else {
                listView.style.display = 'none';
                chartView.style.display = 'block';
            }
        }

        function sortExpenses(sortBy) {
            const expenseList = document.querySelector('.expense-list');
            const expenses = Array.from(expenseList.querySelectorAll('.expense-item'));
            
            expenses.sort((a, b) => {
                if (sortBy === 'date') {
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                } else if (sortBy === 'amount') {
                    const amountA = parseFloat(a.querySelector('.expense-amount').textContent.replace('₵', '').replace(',', ''));
                    const amountB = parseFloat(b.querySelector('.expense-amount').textContent.replace('₵', '').replace(',', ''));
                    return amountB - amountA;
                }
            });
            
            expenses.forEach(expense => expenseList.appendChild(expense));
        }

        function showChart(chartType) {
            const chartBtns = document.querySelectorAll('.chart-btn');
            chartBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Chart switching logic would go here
            console.log(`Showing ${chartType} chart`);
        }

        // Load expense summary data
        function loadExpenseSummary() {
            fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_expense_summary'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Summary response:', data); // Debug log
                if (data.success && data.summary) {
                    updateSummaryCards(data.summary);
                } else {
                    console.error('Failed to load expense summary:', data.message);
                    showNotification('Failed to load expense summary', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading expense summary:', error);
                showNotification('Failed to load expense summary', 'error');
            });
        }

        // Update summary cards with real data
        function updateSummaryCards(summary) {
            // Update total spent
            document.getElementById('totalSpent').textContent = `₵${summary.total_expenses.toFixed(2)}`;
            
            // Update month change
            const monthChange = document.getElementById('monthChange');
            const changeAmount = Math.abs(summary.month_change);
            const changeText = summary.month_change >= 0 
                ? `₵${changeAmount.toFixed(2)} over last month`
                : `₵${changeAmount.toFixed(2)} under last month`;
            monthChange.textContent = changeText;
            monthChange.className = `summary-change ${summary.month_change >= 0 ? 'warning' : 'positive'}`;
            
            // Update needs
            document.getElementById('needsSpent').textContent = `₵${summary.needs.spent.toFixed(2)}`;
            const needsRemaining = Math.max(0, summary.needs.remaining);
            document.getElementById('needsBudget').textContent = 
                `₵${needsRemaining.toFixed(2)} left of ₵${summary.needs.budget.toFixed(2)}`;
            
            const needsPercentage = summary.needs.budget > 0 
                ? Math.min(100, (summary.needs.spent / summary.needs.budget) * 100) 
                : 0;
            document.getElementById('needsProgress').style.width = `${needsPercentage}%`;
            
            // Update wants
            document.getElementById('wantsSpent').textContent = `₵${summary.wants.spent.toFixed(2)}`;
            const wantsRemaining = Math.max(0, summary.wants.remaining);
            document.getElementById('wantsBudget').textContent = 
                `₵${wantsRemaining.toFixed(2)} left of ₵${summary.wants.budget.toFixed(2)}`;
            
            const wantsPercentage = summary.wants.budget > 0 
                ? Math.min(100, (summary.wants.spent / summary.wants.budget) * 100) 
                : 0;
            document.getElementById('wantsProgress').style.width = `${wantsPercentage}%`;
            
            // Update daily average
            document.getElementById('dailyAverage').textContent = `₵${summary.daily_average.toFixed(2)}`;
            const targetDiff = summary.target_daily_average - summary.daily_average;
            const dailyTargetText = targetDiff >= 0 
                ? `₵${targetDiff.toFixed(2)} under target`
                : `₵${Math.abs(targetDiff).toFixed(2)} over target`;
            const dailyTarget = document.getElementById('dailyTarget');
            dailyTarget.textContent = dailyTargetText;
            dailyTarget.className = `summary-change ${targetDiff >= 0 ? 'positive' : 'warning'}`;
        }

        function updateCategoryBudget() {
            const category = document.getElementById('expenseCategory').value;
            const budgetInfo = document.getElementById('categoryBudgetInfo');
            const budgetStatus = document.getElementById('budgetStatus');
            
            if (category && window.categoryBudgets) {
                const data = window.categoryBudgets[category];
                if (data) {
                    const spent = data.spent || 0;
                    const budget = data.budget || 0;
                    const left = budget - spent;
                    
                    budgetStatus.innerHTML = `Spent: ₵${spent.toFixed(2)} | Budget: ₵${budget.toFixed(2)} | Left: ₵${left.toFixed(2)}`;
                    budgetStatus.className = left < 50 ? 'warning' : 'good';
                    budgetInfo.style.display = 'block';
                }
            } else {
                budgetInfo.style.display = 'none';
            }
        }

        // Load categories from database
        function loadCategories() {
            fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_categories'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Categories response:', data); // Debug log
                if (data.success && data.categories) {
                    const categorySelect = document.getElementById('expenseCategory');
                    categorySelect.innerHTML = '<option value="">Select Category</option>';
                    
                    // Store category budget data globally for updateCategoryBudget function
                    window.categoryBudgets = {};
                    
                    // Only show 'needs' and 'wants' for expenses, not savings
                    const expenseTypes = ['needs', 'wants'];
                    
                    expenseTypes.forEach(categoryType => {
                        if (data.categories[categoryType] && data.categories[categoryType].length > 0) {
                            // Use section totals from budget allocation instead of individual category sums
                            const sectionBudget = data.section_totals ? data.section_totals[categoryType] : 0;
                            const sectionSpent = data.section_spending ? data.section_spending[categoryType] : 0;
                            const sectionRemaining = sectionBudget - sectionSpent;
                            
                            const typeLabel = categoryType === 'needs' ? 'Essential Needs' : 'Personal Wants';
                            const optgroup = document.createElement('optgroup');
                            optgroup.label = `${typeLabel} (₵${sectionRemaining.toFixed(2)} left of ₵${sectionBudget.toFixed(2)})`;
                            
                            const categories = data.categories[categoryType];
                            categories.forEach(category => {
                                const option = document.createElement('option');
                                option.value = category.id;
                                option.textContent = category.name;
                                option.dataset.categoryType = categoryType;
                                
                                // Store budget data for this category using category ID
                                window.categoryBudgets[category.id] = {
                                    spent: parseFloat(category.spent_this_month) || 0,
                                    budget: parseFloat(category.budget_limit) || 0
                                };
                                
                                // Add visual indicator for budget status
                                const spentAmount = parseFloat(category.spent_this_month) || 0;
                                const budgetLimit = parseFloat(category.budget_limit) || 0;
                                const budgetPercentage = budgetLimit > 0 ? (spentAmount / budgetLimit) * 100 : 0;
                                
                                if (budgetPercentage >= 100) {
                                    option.textContent += ' ⚠️ (Over Budget)';
                                    option.style.color = '#dc2626';
                                } else if (budgetPercentage >= 80) {
                                    option.textContent += ' ⚡ (Near Limit)';
                                    option.style.color = '#f59e0b';
                                }
                                
                                optgroup.appendChild(option);
                            });
                            
                            categorySelect.appendChild(optgroup);
                        }
                    });
                } else {
                    console.error('Failed to load categories:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading categories:', error);
            });
        }

        // Load category breakdown data
        function loadCategoryBreakdown() {
            fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_expense_summary'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.summary && data.summary.category_breakdown) {
                    updateCategoryBreakdown(data.summary.category_breakdown);
                }
            })
            .catch(error => {
                console.error('Error loading category breakdown:', error);
            });
        }

        // Update category breakdown section with real data
        function updateCategoryBreakdown(categories) {
            const categoryGrid = document.querySelector('.category-grid');
            if (!categoryGrid) return;

            // Update the section header with current month
            const breakdownPeriod = document.querySelector('.breakdown-period');
            if (breakdownPeriod) {
                const currentDate = new Date();
                const monthYear = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                breakdownPeriod.textContent = monthYear;
            }

            categoryGrid.innerHTML = ''; // Clear existing content

            categories.forEach(category => {
                const categoryElement = document.createElement('div');
                categoryElement.className = `category-item ${category.category_type}`;
                
                const percentage = category.budget > 0 ? (category.spent / category.budget) * 100 : 0;
                const statusClass = category.status || (percentage >= 100 ? 'over' : percentage >= 80 ? 'warning' : 'good');
                
                categoryElement.innerHTML = `
                    <div class="category-header">
                        <span class="category-icon">${category.icon || '📝'}</span>
                        <div class="category-info">
                            <h4>${category.name}</h4>
                            <p>${category.transactions} transaction${category.transactions !== 1 ? 's' : ''}</p>
                        </div>
                        <div class="category-amount">₵${category.spent.toFixed(2)}</div>
                    </div>
                    <div class="category-details">
                        <div class="detail-row">
                            <span>Budget: ₵${category.budget.toFixed(2)}</span>
                            <span class="status ${statusClass}">₵${category.remaining.toFixed(2)} left</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.min(100, percentage).toFixed(1)}%"></div>
                        </div>
                    </div>
                `;
                
                categoryGrid.appendChild(categoryElement);
            });
        }

        // Load recent expenses
        function loadRecentExpenses() {
            fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_expenses&limit=10&offset=0'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.expenses) {
                    updateRecentExpenses(data.expenses);
                }
            })
            .catch(error => {
                console.error('Error loading recent expenses:', error);
            });
        }

        // Update recent expenses list with real data
        function updateRecentExpenses(expenses) {
            const expenseList = document.querySelector('.expense-list');
            if (!expenseList) return;

            expenseList.innerHTML = ''; // Clear existing content

            if (expenses.length === 0) {
                expenseList.innerHTML = `
                    <div class="no-expenses">
                        <p>No expenses recorded yet.</p>
                        <button class="btn-primary" onclick="showAddExpenseModal()">Add Your First Expense</button>
                    </div>
                `;
                return;
            }

            expenses.forEach(expense => {
                const expenseElement = document.createElement('div');
                expenseElement.className = 'expense-item';
                expenseElement.dataset.category = `${expense.category_type}-${expense.category_name.toLowerCase().replace(/\s+/g, '-')}`;
                expenseElement.dataset.date = expense.expense_date;
                
                // Get appropriate icon based on category
                const iconMap = {
                    'food': '🛒', 'groceries': '🛒', 'transport': '⛽', 'utilities': '💡',
                    'entertainment': '🎬', 'shopping': '🛍️', 'healthcare': '🏥',
                    'rent': '🏠', 'fuel': '⛽', 'dining': '🍽️'
                };
                
                const categoryKey = expense.category_name.toLowerCase();
                const icon = iconMap[categoryKey] || expense.category_icon || '📝';
                
                expenseElement.innerHTML = `
                    <div class="expense-icon ${expense.category_type}">${icon}</div>
                    <div class="expense-details">
                        <div class="expense-name">${expense.description}</div>
                        <div class="expense-meta">
                            <span class="expense-category">${expense.category_name}</span>
                            <span class="expense-date">${formatDate(expense.expense_date)}</span>
                        </div>
                        <div class="expense-description">${expense.payment_method.charAt(0).toUpperCase() + expense.payment_method.slice(1)} payment</div>
                    </div>
                    <div class="expense-amount">₵${parseFloat(expense.amount).toFixed(2)}</div>
                    <div class="expense-actions">
                        <button class="action-btn edit" onclick="editExpense(${expense.id})">✏️</button>
                        <button class="action-btn delete" onclick="deleteExpense(${expense.id})">🗑️</button>
                    </div>
                `;
                
                expenseList.appendChild(expenseElement);
            });
        }

        // Helper function to format dates
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            return date.toLocaleDateString('en-US', options);
        }

        function saveExpense(event) {
            event.preventDefault();
            
            // Get form data
            const amount = document.getElementById('expenseAmount').value;
            const category_id = document.getElementById('expenseCategory').value;
            const description = document.getElementById('expenseDescription').value;
            const expense_date = document.getElementById('expenseDate').value;
            const payment_method = document.getElementById('paymentMethod').value;
            const notes = document.getElementById('expenseNotes').value;
            
            // Validate required fields
            if (!amount || !category_id || !description || !expense_date) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';
            submitBtn.disabled = true;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'add_expense');
            formData.append('amount', amount);
            formData.append('category_id', category_id);
            formData.append('description', description);
            formData.append('expense_date', expense_date);
            formData.append('payment_method', payment_method);
            formData.append('notes', notes);
            
            // Send to server
            fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Expense added successfully!', 'success');
                    closeModal('addExpenseModal');
                    event.target.reset();
                    
                    // Refresh all data including charts
                    loadExpenseSummary();
                    loadCategoryBreakdown();
                    loadRecentExpenses();
                    loadChartData();
                    
                    // Also refresh categories to update budget indicators
                    loadCategories();
                } else {
                    showNotification(data.message || 'Failed to add expense', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to add expense. Please try again.', 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        function editExpense(id) {
            // In real app, load expense data and show edit modal
            console.log('Editing expense:', id);
            showNotification('Edit functionality coming soon!', 'info');
        }

        function deleteExpense(id) {
            if (confirm('Are you sure you want to delete this expense?')) {
                // In real app, delete from database
                console.log('Deleting expense:', id);
                showNotification('Expense deleted successfully!', 'success');
            }
        }

        // Chart functionality
        let charts = {
            spending: null,
            category: null,
            budget: null
        };

        function initializeCharts() {
            // Initialize empty charts
            createSpendingTrendChart();
            createCategoryPieChart();
            createBudgetComparisonChart();
            
            // Load chart data
            loadChartData();
        }

        function loadChartData() {
            // Load data for charts
            Promise.all([
                fetch('../actions/personal_expense_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_expense_summary'
                }).then(r => r.json()),
                
                fetch('../actions/personal_expense_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_expenses&limit=100&offset=0'
                }).then(r => r.json())
            ]).then(([summaryData, expensesData]) => {
                if (summaryData.success && expensesData.success) {
                    updateCharts(summaryData.summary, expensesData.expenses);
                }
            }).catch(error => {
                console.error('Error loading chart data:', error);
            });
        }

        function createSpendingTrendChart() {
            const ctx = document.getElementById('spendingTrendCanvas').getContext('2d');
            charts.spending = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Daily Spending',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Spending Trend (Last 30 Days)'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₵' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }

        function createCategoryPieChart() {
            const ctx = document.getElementById('categoryPieCanvas').getContext('2d');
            charts.category = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6b7280'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Spending by Category'
                        },
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return context.label + ': ₵' + value.toFixed(2) + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        function createBudgetComparisonChart() {
            const ctx = document.getElementById('budgetComparisonCanvas').getContext('2d');
            charts.budget = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Budget',
                            data: [],
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        },
                        {
                            label: 'Actual Spending',
                            data: [],
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Budget vs Actual Spending'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₵' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateCharts(summary, expenses) {
            updateSpendingTrendChart(expenses);
            updateCategoryPieChart(summary.category_breakdown);
            updateBudgetComparisonChart(summary.category_breakdown);
        }

        function updateSpendingTrendChart(expenses) {
            // Group expenses by date for last 30 days
            const last30Days = [];
            const dailySpending = {};
            
            // Create last 30 days array
            for (let i = 29; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                const dateStr = date.toISOString().split('T')[0];
                last30Days.push(dateStr);
                dailySpending[dateStr] = 0;
            }
            
            // Aggregate spending by date
            expenses.forEach(expense => {
                if (dailySpending.hasOwnProperty(expense.expense_date)) {
                    dailySpending[expense.expense_date] += parseFloat(expense.amount);
                }
            });
            
            const labels = last30Days.map(date => {
                const d = new Date(date);
                return d.getDate() + '/' + (d.getMonth() + 1);
            });
            
            const data = last30Days.map(date => dailySpending[date]);
            
            charts.spending.data.labels = labels;
            charts.spending.data.datasets[0].data = data;
            charts.spending.update();
        }

        function updateCategoryPieChart(categoryBreakdown) {
            const labels = [];
            const data = [];
            
            categoryBreakdown.forEach(category => {
                if (category.spent > 0) {
                    labels.push(category.name);
                    data.push(category.spent);
                }
            });
            
            charts.category.data.labels = labels;
            charts.category.data.datasets[0].data = data;
            charts.category.update();
        }

        function updateBudgetComparisonChart(categoryBreakdown) {
            const labels = [];
            const budgetData = [];
            const actualData = [];
            
            categoryBreakdown.forEach(category => {
                labels.push(category.name);
                budgetData.push(category.budget);
                actualData.push(category.spent);
            });
            
            charts.budget.data.labels = labels;
            charts.budget.data.datasets[0].data = budgetData;
            charts.budget.data.datasets[1].data = actualData;
            charts.budget.update();
        }

        function showChart(chartType) {
            // Update button states
            document.querySelectorAll('.chart-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.chart === chartType) {
                    btn.classList.add('active');
                }
            });
            
            // Hide all chart sections
            document.querySelectorAll('.chart-section').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active');
            });
            
            // Show selected chart
            const chartSection = document.getElementById(chartType + 'Chart');
            if (chartSection) {
                chartSection.style.display = 'block';
                chartSection.classList.add('active');
                
                // Resize chart to ensure proper display
                setTimeout(() => {
                    if (charts[chartType]) {
                        charts[chartType].resize();
                    }
                }, 100);
            }
        }

        function loadMoreExpenses() {
            // In real app, load more expenses from server
            console.log('Loading more expenses...');
            showNotification('Loading more expenses...', 'info');
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>