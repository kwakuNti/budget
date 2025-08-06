<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Nkansah Budget Manager</title>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/personal-expense.css">
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
                <a href="expenses.php" class="nav-item active">Expenses</a>
                <a href="savings.php" class="nav-item">Savings</a>
                <a href="insights.php" class="nav-item">Insights</a>
                <a href="reports.php" class="nav-item">Reports</a>
            </nav>

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
                    <div class="summary-amount">₵2,180.00</div>
                    <div class="summary-change warning">₵245 over last month</div>
                </div>

                <div class="summary-card needs">
                    <div class="summary-header">
                        <h3>Needs Expenses</h3>
                        <span class="summary-icon">🏠</span>
                    </div>
                    <div class="summary-amount">₵1,200.00</div>
                    <div class="summary-budget">₵550 left of ₵1,750</div>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill needs-fill" style="width: 68%"></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card wants">
                    <div class="summary-header">
                        <h3>Wants Expenses</h3>
                        <span class="summary-icon">🎮</span>
                    </div>
                    <div class="summary-amount">₵580.00</div>
                    <div class="summary-budget">₵470 left of ₵1,050</div>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill wants-fill" style="width: 55%"></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card daily">
                    <div class="summary-header">
                        <h3>Daily Average</h3>
                        <span class="summary-icon">📊</span>
                    </div>
                    <div class="summary-amount">₵72.67</div>
                    <div class="summary-change positive">₵15 less than target</div>
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
                            <button class="chart-btn active" onclick="showChart('spending')">Spending Trend</button>
                            <button class="chart-btn" onclick="showChart('category')">By Category</button>
                            <button class="chart-btn" onclick="showChart('budget')">Budget vs Actual</button>
                        </div>
                    </div>
                    
                    <div class="chart-placeholder">
                        <div class="chart-icon">📊</div>
                        <h4>Spending Analytics</h4>
                        <p>Interactive charts showing your expense patterns, category breakdowns, and budget comparisons would be displayed here.</p>
                        <div class="chart-features">
                            <div class="feature-item">📈 Daily spending trends</div>
                            <div class="feature-item">🥧 Category distribution</div>
                            <div class="feature-item">🎯 Budget vs actual comparison</div>
                            <div class="feature-item">📅 Monthly comparisons</div>
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
                        <option value="">Select category</option>
                        <optgroup label="Needs (₵1,750 budget)">
                            <option value="needs-food">Food & Groceries</option>
                            <option value="needs-transport">Transportation</option>
                            <option value="needs-utilities">Utilities</option>
                            <option value="needs-rent">Rent/Housing</option>
                            <option value="needs-healthcare">Healthcare</option>
                        </optgroup>
                        <optgroup label="Wants (₵1,050 budget)">
                            <option value="wants-entertainment">Entertainment</option>
                            <option value="wants-shopping">Shopping</option>
                            <option value="wants-dining">Dining Out</option>
                            <option value="wants-hobbies">Hobbies</option>
                        </optgroup>
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
        });

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

        function updateCategoryBudget() {
            const category = document.getElementById('expenseCategory').value;
            const budgetInfo = document.getElementById('categoryBudgetInfo');
            const budgetStatus = document.getElementById('budgetStatus');
            
            if (category) {
                // Mock budget data - in real app, fetch from server
                const budgets = {
                    'needs-food': { spent: 450, budget: 500, left: 50 },
                    'needs-transport': { spent: 380, budget: 400, left: 20 },
                    'needs-utilities': { spent: 220, budget: 300, left: 80 },
                    'wants-entertainment': { spent: 320, budget: 400, left: 80 },
                    'wants-shopping': { spent: 260, budget: 300, left: 40 }
                };
                
                const data = budgets[category];
                if (data) {
                    budgetStatus.innerHTML = `Spent: ₵${data.spent} | Budget: ₵${data.budget} | Left: ₵${data.left}`;
                    budgetStatus.className = data.left < 50 ? 'warning' : 'good';
                    budgetInfo.style.display = 'block';
                }
            } else {
                budgetInfo.style.display = 'none';
            }
        }

        function saveExpense(event) {
            event.preventDefault();
            
            // Get form data
            const amount = document.getElementById('expenseAmount').value;
            const category = document.getElementById('expenseCategory').value;
            const description = document.getElementById('expenseDescription').value;
            const date = document.getElementById('expenseDate').value;
            const paymentMethod = document.getElementById('paymentMethod').value;
            const notes = document.getElementById('expenseNotes').value;
            
            // Validate required fields
            if (!amount || !category || !description || !date) {
                alert('Please fill in all required fields');
                return;
            }
            
            // In a real app, save to database here
            console.log('Saving expense:', {
                amount, category, description, date, paymentMethod, notes
            });
            
            // Show success message
            showNotification('Expense added successfully!', 'success');
            
            // Close modal and reset form
            closeModal('addExpenseModal');
            event.target.reset();
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