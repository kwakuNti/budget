<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning - Nkansah Budget Manager</title>
    <link rel="stylesheet" href="../public/css/salary.css">
    <link rel="stylesheet" href="../public/css/budget.css">
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
                <a href="budget.php" class="nav-item active">Budget</a>
                <a href="expenses.php" class="nav-item">Expenses</a>
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
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2>💼 Budget Planning</h2>
                    <p>Plan, track, and optimize your monthly budget across all categories</p>
                </div>
                <div class="quick-actions">
                    <button class="quick-btn" onclick="showAddCategoryModal()">
                        <span class="btn-icon">➕</span>
                        Add Category
                    </button>
                    <button class="quick-btn" onclick="showBudgetTemplateModal()">
                        <span class="btn-icon">📋</span>
                        Use Template
                    </button>
                    <button class="quick-btn" onclick="exportBudget()">
                        <span class="btn-icon">📤</span>
                        Export Budget
                    </button>
                </div>
            </section>

            <!-- Budget Overview Cards -->
            <section class="overview-cards">
                <div class="card balance-card">
                    <div class="card-header">
                        <h3>Total Monthly Income</h3>
                        <span class="card-icon">💰</span>
                    </div>
                    <div class="card-content">
                        <div class="amount">₵4,150.00</div>
                        <div class="change">Available for budgeting</div>
                    </div>
                </div>

                <div class="card income-card">
                    <div class="card-header">
                        <h3>Planned Budget</h3>
                        <span class="card-icon">📊</span>
                    </div>
                    <div class="card-content">
                        <div class="amount">₵4,050.00</div>
                        <div class="change positive">₵100 surplus</div>
                    </div>
                </div>

                <div class="card expense-card">
                    <div class="card-header">
                        <h3>Actual Spending</h3>
                        <span class="card-icon">💸</span>
                    </div>
                    <div class="card-content">
                        <div class="amount">₵3,875.00</div>
                        <div class="change positive">₵175 under budget</div>
                    </div>
                </div>

                <div class="card savings-card">
                    <div class="card-header">
                        <h3>Budget Performance</h3>
                        <span class="card-icon">🎯</span>
                    </div>
                    <div class="card-content">
                        <div class="amount">96%</div>
                        <div class="change">Excellent tracking</div>
                    </div>
                </div>
            </section>

            <!-- Budget Period Selector -->
            <section class="budget-controls">
                <div class="period-selector">
                    <label>Budget Period:</label>
                    <select id="budgetPeriod" onchange="changeBudgetPeriod()">
                        <option value="january-2025" selected>January 2025</option>
                        <option value="february-2025">February 2025</option>
                        <option value="march-2025">March 2025</option>
                    </select>
                </div>
                <div class="budget-actions">
                    <button class="btn-secondary" onclick="copyFromPreviousMonth()">Copy from Previous</button>
                    <button class="btn-primary" onclick="saveBudget()">Save Budget</button>
                </div>
            </section>

            <!-- Main Budget Categories -->
            <section class="budget-categories">
                <div class="section-header">
                    <h3>Budget Categories</h3>
                    <div class="view-toggle">
                        <button class="toggle-btn active" data-view="detailed" onclick="switchView('detailed')">Detailed View</button>
                        <button class="toggle-btn" data-view="summary" onclick="switchView('summary')">Summary View</button>
                    </div>
                </div>

                <!-- Needs Category -->
                <div class="category-section needs-section">
                    <div class="category-header" onclick="toggleCategory('needs')">
                        <div class="category-info">
                            <span class="category-icon">🏠</span>
                            <div class="category-details">
                                <h4>Needs (Essential)</h4>
                                <p>Housing, food, utilities, transportation</p>
                            </div>
                        </div>
                        <div class="category-summary">
                            <div class="category-amount">₵2,075.00 / ₵2,200.00</div>
                            <div class="category-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill needs-progress" style="width: 94%"></div>
                                </div>
                                <span class="progress-text">94%</span>
                            </div>
                            <span class="expand-icon">▼</span>
                        </div>
                    </div>
                    
                    <div class="category-content expanded">
                        <div class="budget-table">
                            <div class="table-header">
                                <div class="col-item">Item</div>
                                <div class="col-planned">Planned</div>
                                <div class="col-actual">Actual</div>
                                <div class="col-variance">Variance</div>
                                <div class="col-status">Status</div>
                                <div class="col-actions">Actions</div>
                            </div>
                            
                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🏠</span>
                                    <div class="item-info">
                                        <h5>Rent/Mortgage</h5>
                                        <p>Monthly housing cost</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="1200" step="0.01" onchange="updateBudgetItem(this, 'rent', 'planned')">
                                </div>
                                <div class="col-actual">₵1,200.00</div>
                                <div class="col-variance success">₵0.00</div>
                                <div class="col-status">
                                    <span class="status-badge on-track">On Track</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('rent')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('rent')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🍽️</span>
                                    <div class="item-info">
                                        <h5>Groceries</h5>
                                        <p>Food and household items</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="450" step="0.01" onchange="updateBudgetItem(this, 'groceries', 'planned')">
                                </div>
                                <div class="col-actual">₵425.00</div>
                                <div class="col-variance success">₵25.00</div>
                                <div class="col-status">
                                    <span class="status-badge under-budget">Under Budget</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('groceries')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('groceries')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">⚡</span>
                                    <div class="item-info">
                                        <h5>Utilities</h5>
                                        <p>Electricity, water, internet</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="200" step="0.01" onchange="updateBudgetItem(this, 'utilities', 'planned')">
                                </div>
                                <div class="col-actual">₵180.00</div>
                                <div class="col-variance success">₵20.00</div>
                                <div class="col-status">
                                    <span class="status-badge under-budget">Under Budget</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('utilities')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('utilities')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🚗</span>
                                    <div class="item-info">
                                        <h5>Transportation</h5>
                                        <p>Fuel, maintenance, public transport</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="350" step="0.01" onchange="updateBudgetItem(this, 'transport', 'planned')">
                                </div>
                                <div class="col-actual">₵270.00</div>
                                <div class="col-variance success">₵80.00</div>
                                <div class="col-status">
                                    <span class="status-badge under-budget">Under Budget</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('transport')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('transport')">💰</button>
                                </div>
                            </div>
                        </div>
                        <button class="add-item-btn" onclick="showAddItemModal('needs')">+ Add Item</button>
                    </div>
                </div>

                <!-- Wants Category -->
                <div class="category-section wants-section">
                    <div class="category-header" onclick="toggleCategory('wants')">
                        <div class="category-info">
                            <span class="category-icon">🎮</span>
                            <div class="category-details">
                                <h4>Wants (Lifestyle)</h4>
                                <p>Entertainment, dining out, hobbies</p>
                            </div>
                        </div>
                        <div class="category-summary">
                            <div class="category-amount">₵950.00 / ₵1,050.00</div>
                            <div class="category-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill wants-progress" style="width: 90%"></div>
                                </div>
                                <span class="progress-text">90%</span>
                            </div>
                            <span class="expand-icon">▼</span>
                        </div>
                    </div>
                    
                    <div class="category-content">
                        <div class="budget-table">
                            <div class="table-header">
                                <div class="col-item">Item</div>
                                <div class="col-planned">Planned</div>
                                <div class="col-actual">Actual</div>
                                <div class="col-variance">Variance</div>
                                <div class="col-status">Status</div>
                                <div class="col-actions">Actions</div>
                            </div>
                            
                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🍽️</span>
                                    <div class="item-info">
                                        <h5>Dining Out</h5>
                                        <p>Restaurants, takeout</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="300" step="0.01" onchange="updateBudgetItem(this, 'dining', 'planned')">
                                </div>
                                <div class="col-actual">₵280.00</div>
                                <div class="col-variance success">₵20.00</div>
                                <div class="col-status">
                                    <span class="status-badge under-budget">Under Budget</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('dining')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('dining')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🎬</span>
                                    <div class="item-info">
                                        <h5>Entertainment</h5>
                                        <p>Movies, games, subscriptions</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="250" step="0.01" onchange="updateBudgetItem(this, 'entertainment', 'planned')">
                                </div>
                                <div class="col-actual">₵270.00</div>
                                <div class="col-variance warning">-₵20.00</div>
                                <div class="col-status">
                                    <span class="status-badge over-budget">Over Budget</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('entertainment')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('entertainment')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🛍️</span>
                                    <div class="item-info">
                                        <h5>Shopping</h5>
                                        <p>Clothes, personal items</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="500" step="0.01" onchange="updateBudgetItem(this, 'shopping', 'planned')">
                                </div>
                                <div class="col-actual">₵400.00</div>
                                <div class="col-variance success">₵100.00</div>
                                <div class="col-status">
                                    <span class="status-badge under-budget">Under Budget</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('shopping')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('shopping')">💰</button>
                                </div>
                            </div>
                        </div>
                        <button class="add-item-btn" onclick="showAddItemModal('wants')">+ Add Item</button>
                    </div>
                </div>

                <!-- Savings & Investments -->
                <div class="category-section savings-section">
                    <div class="category-header" onclick="toggleCategory('savings')">
                        <div class="category-info">
                            <span class="category-icon">💰</span>
                            <div class="category-details">
                                <h4>Savings & Investments</h4>
                                <p>Emergency fund, retirement, goals</p>
                            </div>
                        </div>
                        <div class="category-summary">
                            <div class="category-amount">₵850.00 / ₵800.00</div>
                            <div class="category-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill savings-progress" style="width: 106%"></div>
                                </div>
                                <span class="progress-text">106%</span>
                            </div>
                            <span class="expand-icon">▼</span>
                        </div>
                    </div>
                    
                    <div class="category-content">
                        <div class="budget-table">
                            <div class="table-header">
                                <div class="col-item">Item</div>
                                <div class="col-planned">Planned</div>
                                <div class="col-actual">Actual</div>
                                <div class="col-variance">Variance</div>
                                <div class="col-status">Status</div>
                                <div class="col-actions">Actions</div>
                            </div>
                            
                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🆘</span>
                                    <div class="item-info">
                                        <h5>Emergency Fund</h5>
                                        <p>3-6 months expenses</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="400" step="0.01" onchange="updateBudgetItem(this, 'emergency', 'planned')">
                                </div>
                                <div class="col-actual">₵450.00</div>
                                <div class="col-variance success">-₵50.00</div>
                                <div class="col-status">
                                    <span class="status-badge exceeded">Exceeded Target</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('emergency')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('emergency')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">🏖️</span>
                                    <div class="item-info">
                                        <h5>Vacation Fund</h5>
                                        <p>Holiday savings</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="200" step="0.01" onchange="updateBudgetItem(this, 'vacation', 'planned')">
                                </div>
                                <div class="col-actual">₵200.00</div>
                                <div class="col-variance success">₵0.00</div>
                                <div class="col-status">
                                    <span class="status-badge on-track">On Track</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('vacation')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('vacation')">💰</button>
                                </div>
                            </div>

                            <div class="budget-item">
                                <div class="col-item">
                                    <span class="item-icon">📈</span>
                                    <div class="item-info">
                                        <h5>Investments</h5>
                                        <p>Stocks, bonds, mutual funds</p>
                                    </div>
                                </div>
                                <div class="col-planned">
                                    <input type="number" value="200" step="0.01" onchange="updateBudgetItem(this, 'investments', 'planned')">
                                </div>
                                <div class="col-actual">₵200.00</div>
                                <div class="col-variance success">₵0.00</div>
                                <div class="col-status">
                                    <span class="status-badge on-track">On Track</span>
                                </div>
                                <div class="col-actions">
                                    <button class="action-btn" onclick="editBudgetItem('investments')">✏️</button>
                                    <button class="action-btn" onclick="addExpense('investments')">💰</button>
                                </div>
                            </div>
                        </div>
                        <button class="add-item-btn" onclick="showAddItemModal('savings')">+ Add Item</button>
                    </div>
                </div>
            </section>

            <!-- Budget Summary -->
            <section class="budget-summary">
                <div class="summary-cards">
                    <div class="summary-card">
                        <h4>Total Planned</h4>
                        <div class="summary-amount">₵4,050.00</div>
                        <div class="summary-detail">97.6% of income</div>
                    </div>
                    <div class="summary-card">
                        <h4>Total Actual</h4>
                        <div class="summary-amount">₵3,875.00</div>
                        <div class="summary-detail">93.4% of income</div>
                    </div>
                    <div class="summary-card">
                        <h4>Remaining Budget</h4>
                        <div class="summary-amount positive">₵275.00</div>
                        <div class="summary-detail">6.6% unspent</div>
                    </div>
                    <div class="summary-card">
                        <h4>Available Balance</h4>
                        <div class="summary-amount">₵100.00</div>
                        <div class="summary-detail">Unallocated funds</div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
            </div>
            <form class="modal-form" id="addCategoryForm">
                <div class="form-section">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="categoryName" placeholder="e.g., Healthcare" required>
                    </div>
                    <div class="form-group">
                        <label>Category Type</label>
                        <select name="categoryType" required>
                            <option value="">Select type</option>
                            <option value="needs">Needs (Essential)</option>
                            <option value="wants">Wants (Lifestyle)</option>
                            <option value="savings">Savings & Investments</option>
                            <option value="debt">Debt Payments</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Icon</label>
                        <div class="icon-selector">
                            <div class="icon-option selected" data-icon="🏥">🏥</div>
                            <div class="icon-option" data-icon="💊">💊</div>
                            <div class="icon-option" data-icon="🚗">🚗</div>
                            <div class="icon-option" data-icon="📚">📚</div>
                            <div class="icon-option" data-icon="🎯">🎯</div>
                            <div class="icon-option" data-icon="💳">💳</div>
                        </div>
                        <input type="hidden" name="categoryIcon" value="🏥">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Brief description of this category"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addCategoryModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Budget Item Modal -->
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Budget Item</h3>
                <span class="close" onclick="closeModal('addItemModal')">&times;</span>
            </div>
            <form class="modal-form" id="addItemForm">
                <div class="form-section">
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="itemName" placeholder="e.g., Gym Membership" required>
                    </div>
                    <div class="form-group">
                        <label>Planned Amount (₵)</label>
                        <input type="number" name="plannedAmount" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Icon</label>
                        <div class="icon-selector">
                            <div class="icon-option selected" data-icon="💪">💪</div>
                            <div class="icon-option" data-icon="📱">📱</div>
                            <div class="icon-option" data-icon="🎵">🎵</div>
                            <div class="icon-option" data-icon="📖">📖</div>
                            <div class="icon-option" data-icon="🎨">🎨</div>
                            <div class="icon-option" data-icon="⚽">⚽</div>
                        </div>
                        <input type="hidden" name="itemIcon" value="💪">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Brief description"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addItemModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Budget Template Modal -->
    <div id="budgetTemplateModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Budget Templates</h3>
                <span class="close" onclick="closeModal('budgetTemplateModal')">&times;</span>
            </div>
            <div class="modal-form">
                <div class="template-grid">
                    <div class="template-card" onclick="applyTemplate('50-30-20')">
                        <h4>50/30/20 Rule</h4>
                        <p>50% Needs, 30% Wants, 20% Savings</p>
                        <div class="template-preview">
                            <div class="preview-bar needs-bar" style="width: 50%"></div>
                            <div class="preview-bar wants-bar" style="width: 30%"></div>
                            <div class="preview-bar savings-bar" style="width: 20%"></div>
                        </div>
                    </div>
                    <div class="template-card" onclick="applyTemplate('60-20-20')">
                        <h4>Conservative</h4>
                        <p>60% Needs, 20% Wants, 20% Savings</p>
                        <div class="template-preview">
                            <div class="preview-bar needs-bar" style="width: 60%"></div>
                            <div class="preview-bar wants-bar" style="width: 20%"></div>
                            <div class="preview-bar savings-bar" style="width: 20%"></div>
                        </div>
                    </div>
                    <div class="template-card" onclick="applyTemplate('40-40-20')">
                        <h4>Balanced</h4>
                        <p>40% Needs, 40% Wants, 20% Savings</p>
                        <div class="template-preview">
                            <div class="preview-bar needs-bar" style="width: 40%"></div>
                            <div class="preview-bar wants-bar" style="width: 40%"></div>
                            <div class="preview-bar savings-bar" style="width: 20%"></div>
                        </div>
                    </div>
                    <div class="template-card" onclick="applyTemplate('aggressive-savings')">
                        <h4>Aggressive Savings</h4>
                        <p>45% Needs, 25% Wants, 30% Savings</p>
                        <div class="template-preview">
                            <div class="preview-bar needs-bar" style="width: 45%"></div>
                            <div class="preview-bar wants-bar" style="width: 25%"></div>
                            <div class="preview-bar savings-bar" style="width: 30%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Expense</h3>
                <span class="close" onclick="closeModal('addExpenseModal')">&times;</span>
            </div>
            <form class="modal-form" id="addExpenseForm">
                <div class="form-section">
                    <div class="form-group">
                        <label>Expense Category</label>
                        <input type="text" name="expenseCategory" readonly>
                    </div>
                    <div class="form-group">
                        <label>Amount (₵)</label>
                        <input type="number" name="expenseAmount" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expenseDate" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="expenseDescription" placeholder="What was this expense for?"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addExpenseModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Snackbar -->
    <div id="snackbar"></div>

    <script src="../public/js/budget.js"></script>
</body>
</html>