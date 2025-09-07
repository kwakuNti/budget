<?php
session_start();

// Check session timeout
require_once '../includes/session_timeout_middleware.php';
$session_check = checkSessionTimeout();
if (!$session_check['valid']) {
    header('Location: ../login?timeout=1');
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Check if user needs to complete salary setup first
require_once '../includes/walkthrough_guard.php';

// Get user information from session
$user_first_name = $_SESSION['first_name'] ?? 'User';
$user_full_name = $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning - Nkansah Budget Manager</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/mobile-nav.css">
    <link rel="stylesheet" href="../public/css/budget.css">
    <link rel="stylesheet" href="../public/css/loading.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Universal Snackbar -->
    <script src="../public/js/snackbar.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="logo-text">
                    <h1><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Personal Finance</p>
                </div>
            </div>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            
            <nav class="header-nav" id="headerNav">
                <a href="personal-dashboard" class="nav-item">Dashboard</a>
                <a href="salary" class="nav-item">Salary Setup</a>
                <a href="budgets" class="nav-item active">Budget</a>
                <a href="personal-expense" class="nav-item">Expenses</a>
                <a href="savings" class="nav-item">Savings</a>
                <!-- <a href="insights.php" class="nav-item">Insights</a> -->
                <a href="report" class="nav-item">Reports</a>
            </nav>

            
            <div class="user-menu">
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar"><?php 
                    echo strtoupper(substr($user_first_name, 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)); 
                ?></div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="savings">Savings</a>
                    <a href="personal-expense"> Expense </a>
                    <a href="budgets">Budget</a>
                    <!-- <hr> -->
                    <a href="salary">Salary</a>
                    <a href="../actions/signout.php">Logout</a>
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
                    <h2><i class="fas fa-calculator"></i> Budget Planning</h2>
                    <p>Plan, track, and optimize your monthly budget across all categories</p>
                </div>
                <div class="quick-actions">
                    <button class="quick-btn add-category-btn" onclick="showAddCategoryModal()">
                        <span class="btn-icon">➕</span>
                        Add Category
                    </button>
                    <button class="quick-btn" onclick="showBudgetTemplateModal()">
                        <span class="btn-icon"><i class="fas fa-clipboard-list"></i></span>
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
                        <span class="card-icon"><i class="fas fa-dollar-sign"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="totalIncome">₵0.00</div>
                        <div class="change">Available for budgeting</div>
                    </div>
                </div>

                <div class="card income-card">
                    <div class="card-header">
                        <h3>Planned Budget</h3>
                        <span class="card-icon"><i class="fas fa-chart-bar"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="plannedBudget">₵0.00</div>
                        <div class="change" id="budgetSurplus">₵0 surplus</div>
                    </div>
                </div>

                <div class="card expense-card">
                    <div class="card-header">
                        <h3>Actual Spending</h3>
                        <span class="card-icon"><i class="fas fa-money-bill-wave"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="actualSpending">₵0.00</div>
                        <div class="change" id="spendingVariance">₵0 under budget</div>
                    </div>
                </div>

                <div class="card savings-card">
                    <div class="card-header">
                        <h3>Budget Performance</h3>
                        <span class="card-icon"><i class="fas fa-bullseye"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="budgetPerformance">0%</div>
                        <div class="change" id="performanceLabel">Starting tracking</div>
                    </div>
                </div>
            </section>

            <!-- Budget Period Selector -->
            <section class="budget-controls">
                <div class="period-selector">
                    <label>Budget Period:</label>
                    <select id="budgetPeriodSelector">
                        <option value="">Loading periods...</option>
                    </select>
                    <span id="currentPeriodDisplay" class="current-period">
                        <?php echo date('F Y'); ?>
                    </span>
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

                <!-- Loading State -->
                <div id="budgetLoading" class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading budget categories...</p>
                </div>

                <!-- Dynamic Categories Container -->
                <div id="budgetCategoriesContainer" style="display: none;">
                    <!-- Categories will be dynamically loaded here -->
                </div>

                <!-- Empty State -->
                <div id="emptyBudgetState" class="empty-state" style="display: none;">
                    <div class="empty-icon">�</div>
                    <h3>No Budget Categories Yet</h3>
                    <p>Get started by adding your first budget category</p>
                    <button class="btn-primary" onclick="showAddCategoryModal()">Add Your First Category</button>
                </div>
            </section>

            <!-- Budget Summary -->
            <section class="budget-summary">
                <div class="summary-cards">
                    <div class="summary-card">
                        <h4>Total Planned</h4>
                        <div class="summary-amount" id="summaryPlanned">₵0.00</div>
                        <div class="summary-detail" id="summaryPlannedPercent">0% of income</div>
                    </div>
                    <div class="summary-card">
                        <h4>Total Actual</h4>
                        <div class="summary-amount" id="summaryActual">₵0.00</div>
                        <div class="summary-detail" id="summaryActualPercent">0% of income</div>
                    </div>
                    <div class="summary-card">
                        <h4>Remaining Budget</h4>
                        <div class="summary-amount" id="summaryRemaining">₵0.00</div>
                        <div class="summary-detail" id="summaryRemainingPercent">0% unspent</div>
                    </div>
                    <div class="summary-card">
                        <h4>Available Balance</h4>
                        <div class="summary-amount" id="summaryAvailable">₵0.00</div>
                        <div class="summary-detail">Unallocated funds</div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="budget-modal">
        <div class="budget-modal-content add-category-modal">
            <div class="budget-modal-header clean-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="modal-title-section">
                        <h3>Create New Budget Category</h3>
                        <p>Organize your spending with custom categories</p>
                    </div>
                </div>
                <span class="budget-modal-close modern-close" onclick="closeModal('addCategoryModal')">&times;</span>
            </div>
            <form class="budget-modal-form well-organized-form" id="addCategoryForm">
                <div class="form-section basic-info">
                    <h4><i class="fas fa-info-circle"></i> Basic Information</h4>
                    <div class="form-grid two-column spaced">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Category Name</label>
                            <input type="text" name="name" placeholder="e.g., Healthcare, Groceries" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-list"></i> Category Type</label>
                            <select name="category_type" required>
                                <option value="">Select type</option>
                                <option value="needs">🏠 Needs (Essential)</option>
                                <option value="wants">🎉 Wants (Lifestyle)</option>
                                <option value="savings">💰 Savings & Investments</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section budget-setup">
                    <h4><i class="fas fa-calculator"></i> Budget Setup</h4>
                    <div class="form-grid two-column spaced">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Budget Period</label>
                            <select name="budget_period" required>
                                <option value="monthly" selected>📅 Monthly</option>
                                <option value="weekly">📆 Weekly</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calculator"></i> Budget Input Type</label>
                            <div class="budget-type-selector enhanced-radio">
                                <label class="radio-option">
                                    <input type="radio" name="budget_input_type" value="amount" checked onchange="toggleBudgetInputType()">
                                    <span class="radio-label">💵 Fixed Amount</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="budget_input_type" value="percentage" onchange="toggleBudgetInputType()">
                                    <span class="radio-label">📊 Percentage</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width budget-input-wrapper">
                        <label><i class="fas fa-money-bill-wave"></i> Budget Limit</label>
                        <div class="budget-input-container enhanced-input">
                            <div id="amountInput" class="budget-input-section active">
                                <input type="number" name="budget_limit" step="0.01" placeholder="0.00" min="0" required>
                                <span class="input-suffix">₵</span>
                            </div>
                            <div id="percentageInput" class="budget-input-section" style="display: none;">
                                <input type="number" name="budget_percentage" step="0.1" placeholder="0.0" min="0" max="100">
                                <span class="input-suffix">%</span>
                                <div class="percentage-info">
                                    <small id="percentageCalculation">of section allocation</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section customization">
                    <h4><i class="fas fa-palette"></i> Customization</h4>
                    <div class="form-grid two-column spaced">
                        <div class="form-group">
                            <label><i class="fas fa-icons"></i> Choose Icon</label>
                            <div class="icon-selector enhanced-selector">
                                <div class="icon-option selected" data-icon="hospital"><i class="fas fa-hospital"></i></div>
                                <div class="icon-option" data-icon="pills"><i class="fas fa-pills"></i></div>
                                <div class="icon-option" data-icon="car"><i class="fas fa-car"></i></div>
                                <div class="icon-option" data-icon="book"><i class="fas fa-book"></i></div>
                                <div class="icon-option" data-icon="bullseye"><i class="fas fa-bullseye"></i></div>
                                <!-- <div class="icon-option" data-icon="credit-card"><i class="fas fa-credit-card"></i></div> -->
                                <!-- <div class="icon-option" data-icon="home"><i class="fas fa-home"></i></div> -->
                                <!-- <div class="icon-option" data-icon="utensils"><i class="fas fa-utensils"></i></div> -->
                            </div>
                            <input type="hidden" name="icon" value="hospital">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-palette"></i> Choose Color</label>
                            <div class="color-selector enhanced-selector">
                                <div class="color-option selected" data-color="#007bff" style="background-color: #007bff;"></div>
                                <div class="color-option" data-color="#28a745" style="background-color: #28a745;"></div>
                                <div class="color-option" data-color="#dc3545" style="background-color: #dc3545;"></div>
                                <div class="color-option" data-color="#ffc107" style="background-color: #ffc107;"></div>
                                <div class="color-option" data-color="#6f42c1" style="background-color: #6f42c1;"></div>
                                <div class="color-option" data-color="#fd7e14" style="background-color: #fd7e14;"></div>
                            </div>
                            <input type="hidden" name="color" value="#007bff">
                        </div>
                    </div>
                </div>

                <div class="form-notes organized-notes">
                    <div class="form-note helpful-note">
                        <i class="fas fa-lightbulb"></i>
                        <div>
                            <strong>Tip:</strong> For savings goals, use the <a href="savings">Savings</a> page to create and track specific goals. Savings categories here are for budget allocation only.
                        </div>
                    </div>
                    <div class="form-note info-note">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Note:</strong> All calculations are stored monthly, but your original choice will be shown for clarity.
                        </div>
                    </div>
                </div>

                <div class="budget-modal-actions enhanced-actions">
                    <button type="button" class="btn-secondary enhanced-btn" onclick="closeModal('addCategoryModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary enhanced-btn">
                        <i class="fas fa-plus-circle"></i> Create Category
                    </button>
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
                            <div class="icon-option selected" data-icon="fas fa-dumbbell"><i class="fas fa-dumbbell"></i></div>
                            <div class="icon-option" data-icon="fas fa-mobile-alt"><i class="fas fa-mobile-alt"></i></div>
                            <div class="icon-option" data-icon="fas fa-music"><i class="fas fa-music"></i></div>
                            <div class="icon-option" data-icon="fas fa-book-open"><i class="fas fa-book-open"></i></div>
                            <div class="icon-option" data-icon="fas fa-palette"><i class="fas fa-palette"></i></div>
                            <div class="icon-option" data-icon="fas fa-futbol"><i class="fas fa-futbol"></i></div>
                        </div>
                        <input type="hidden" name="itemIcon" value="fas fa-dumbbell">
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
    <div id="budgetTemplateModal" class="budget-modal">
        <div class="budget-modal-content large">
            <div class="budget-modal-header">
                <h3>Budget Templates</h3>
                <span class="budget-modal-close" onclick="closeModal('budgetTemplateModal')">&times;</span>
            </div>
            <div class="budget-modal-form">
                <div class="template-section" id="templateSelection">
                    <h4>Popular Budget Templates</h4>
                    <p>Choose a proven budgeting strategy or create your own custom allocation</p>
                    
                    <div class="template-grid" id="templateGrid">
                        <div class="template-card" onclick="selectTemplate(50, 30, 20, '50/30/20 Rule')">
                            <h5>50/30/20 Rule</h5>
                            <p class="template-desc">Most popular balanced approach</p>
                            <div class="template-preview">
                                <div class="preview-circle" style="--needs-deg: 180deg; --wants-end-deg: 288deg;"></div>
                                <div class="preview-legend">
                                    <div class="legend-item">
                                        <div class="legend-color needs"></div>
                                        <span class="legend-text">50% Needs</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color wants"></div>
                                        <span class="legend-text">30% Wants</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color savings"></div>
                                        <span class="legend-text">20% Savings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-card" onclick="selectTemplate(80, 10, 10, 'Minimalist Budget')">
                            <h5>80/10/10 Minimalist</h5>
                            <p class="template-desc">Focus on essentials only</p>
                            <div class="template-preview">
                                <div class="preview-circle" style="--needs-deg: 288deg; --wants-end-deg: 324deg;"></div>
                                <div class="preview-legend">
                                    <div class="legend-item">
                                        <div class="legend-color needs"></div>
                                        <span class="legend-text">80% Needs</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color wants"></div>
                                        <span class="legend-text">10% Wants</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color savings"></div>
                                        <span class="legend-text">10% Savings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-card" onclick="selectTemplate(60, 20, 20, 'Conservative Budget')">
                            <h5>60/20/20 Conservative</h5>
                            <p class="template-desc">Higher focus on necessities</p>
                            <div class="template-preview">
                                <div class="preview-circle" style="--needs-deg: 216deg; --wants-end-deg: 288deg;"></div>
                                <div class="preview-legend">
                                    <div class="legend-item">
                                        <div class="legend-color needs"></div>
                                        <span class="legend-text">60% Needs</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color wants"></div>
                                        <span class="legend-text">20% Wants</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color savings"></div>
                                        <span class="legend-text">20% Savings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-card" onclick="selectTemplate(40, 30, 30, 'Aggressive Saver')">
                            <h5>40/30/30 Aggressive</h5>
                            <p class="template-desc">Maximum savings focus</p>
                            <div class="template-preview">
                                <div class="preview-circle" style="--needs-deg: 144deg; --wants-end-deg: 252deg;"></div>
                                <div class="preview-legend">
                                    <div class="legend-item">
                                        <div class="legend-color needs"></div>
                                        <span class="legend-text">40% Needs</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color wants"></div>
                                        <span class="legend-text">30% Wants</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color savings"></div>
                                        <span class="legend-text">30% Savings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-card" onclick="selectTemplate(70, 20, 10, 'Debt Payoff Focus')">
                            <h5>70/20/10 Debt Focus</h5>
                            <p class="template-desc">Prioritize debt elimination</p>
                            <div class="template-preview">
                                <div class="preview-circle" style="--needs-deg: 252deg; --wants-end-deg: 324deg;"></div>
                                <div class="preview-legend">
                                    <div class="legend-item">
                                        <div class="legend-color needs"></div>
                                        <span class="legend-text">70% Needs</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color wants"></div>
                                        <span class="legend-text">20% Wants</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color savings"></div>
                                        <span class="legend-text">10% Savings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-card custom-template" onclick="showCustomTemplate()">
                            <h5><i class="fas fa-bullseye"></i> Custom Template</h5>
                            <p class="template-desc">Create your own allocation</p>
                            <div class="custom-icon">
                                <span>✏️</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Custom Template Section -->
                <div id="customTemplateSection" class="custom-section" style="display: none;">
                    <h4>Create Custom Budget Template</h4>
                    <p>Adjust the percentages to match your financial goals. Total must equal 100%.</p>
                    
                    <div class="custom-controls">
                        <div class="percentage-control">
                            <label>Needs (Essential expenses)</label>
                            <div class="percentage-input">
                                <input type="range" id="needsSlider" min="0" max="100" value="50" oninput="updateCustomTemplate()">
                                <input type="number" id="needsInput" min="0" max="100" value="50" oninput="updateCustomTemplate()">
                                <span>%</span>
                            </div>
                        </div>
                        
                        <div class="percentage-control">
                            <label>Wants (Lifestyle & entertainment)</label>
                            <div class="percentage-input">
                                <input type="range" id="wantsSlider" min="0" max="100" value="30" oninput="updateCustomTemplate()">
                                <input type="number" id="wantsInput" min="0" max="100" value="30" oninput="updateCustomTemplate()">
                                <span>%</span>
                            </div>
                        </div>
                        
                        <div class="percentage-control">
                            <label>Savings & Investments</label>
                            <div class="percentage-input">
                                <input type="range" id="savingsSlider" min="0" max="100" value="20" oninput="updateCustomTemplate()">
                                <input type="number" id="savingsInput" min="0" max="100" value="20" oninput="updateCustomTemplate()">
                                <span>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="custom-preview">
                        <div class="total-check">
                            <span id="totalPercentage">100%</span>
                            <span id="totalStatus" class="status-valid">✓ Valid</span>
                        </div>
                        <div class="custom-template-preview">
                            <div id="customNeedsBar" class="preview-bar needs-bar" style="width: 50%">
                                <span id="customNeedsLabel">50% Needs</span>
                            </div>
                            <div id="customWantsBar" class="preview-bar wants-bar" style="width: 30%">
                                <span id="customWantsLabel">30% Wants</span>
                            </div>
                            <div id="customSavingsBar" class="preview-bar savings-bar" style="width: 20%">
                                <span id="customSavingsLabel">20% Savings</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Template Preview -->
                <div id="templatePreviewSection" class="preview-section" style="display: none;">
                    <h4>Template Preview</h4>
                    <div id="selectedTemplateName" class="template-name"></div>
                    <div id="templateCalculations" class="template-calculations"></div>
                </div>
                
                <!-- Template Preview Section -->
                <div id="templatePreview" class="template-preview-section"></div>
                
                <div class="budget-modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('budgetTemplateModal')">Cancel</button>
                    <button type="button" class="btn-secondary" id="backToTemplates" onclick="backToTemplates()" style="display: none;">Back to Templates</button>
                    <button type="button" class="btn-primary" id="saveCustomTemplate" onclick="saveCustomTemplate()" style="display: none;">Save & Apply Template</button>
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

    <!-- Export Budget Modal -->
    <div id="exportBudgetModal" class="budget-modal">
        <div class="budget-modal-content">
            <div class="budget-modal-header">
                <h3>Export Budget Data</h3>
                <span class="budget-modal-close" onclick="closeModal('exportBudgetModal')">&times;</span>
            </div>
            <form class="budget-modal-form" id="exportBudgetForm">
                <div class="form-section">
                    <div class="form-group">
                        <label>Export Period</label>
                        <select name="export_period" required>
                            <option value="">Select period</option>
                            <option value="current">Current Month</option>
                            <option value="january-2025">January 2025</option>
                            <option value="february-2025">February 2025</option>
                            <option value="march-2025">March 2025</option>
                            <option value="april-2025">April 2025</option>
                            <option value="may-2025">May 2025</option>
                            <option value="june-2025">June 2025</option>
                            <option value="july-2025">July 2025</option>
                            <option value="august-2025">August 2025</option>
                            <option value="september-2025">September 2025</option>
                            <option value="october-2025">October 2025</option>
                            <option value="november-2025">November 2025</option>
                            <option value="december-2025">December 2025</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>
                    
                    <div id="customDateRange" class="form-group" style="display: none;">
                        <label>Custom Date Range</label>
                        <div class="date-range-inputs">
                            <div class="date-input">
                                <label>From:</label>
                                <input type="date" name="export_from_date">
                            </div>
                            <div class="date-input">
                                <label>To:</label>
                                <input type="date" name="export_to_date">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Export Format</label>
                        <select name="export_format" required>
                            <option value="csv">CSV (Excel Compatible)</option>
                            <option value="pdf">PDF Report</option>
                            <option value="json">JSON Data</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Include Data</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="include_categories" checked>
                                <span>Budget Categories</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="include_expenses" checked>
                                <span>Actual Expenses</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="include_variance" checked>
                                <span>Variance Analysis</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="include_allocation">
                                <span>Budget Allocation (80/10/10)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Additional Options</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="include_summary" checked>
                                <span>Include Summary Statistics</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="include_charts">
                                <span>Include Charts (PDF only)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="budget-modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('exportBudgetModal')">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <span class="btn-icon">📤</span>
                        Export Budget
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="budgetLoadingIndicator">
        <div class="loading-spinner"></div>
        <div>Loading budget data...</div>
    </div>

    <!-- Snackbar -->
    <div id="snackbar"></div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Expense</h3>
                <span class="close" onclick="closeModal('addExpenseModal')">&times;</span>
            </div>
            <form class="modal-form" id="addExpenseForm">
                <div class="form-group">
                    <label>Amount (₵)</label>
                    <input type="number" name="amount" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Budget Category</label>
                    <select name="category_id" required>
                        <option value="">Loading categories...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="What was this for?" required>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="expense_date" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="Additional notes">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addExpenseModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle budget input type between amount and percentage
        function toggleBudgetInputType() {
            const amountRadio = document.querySelector('input[name="budget_input_type"][value="amount"]');
            const percentageRadio = document.querySelector('input[name="budget_input_type"][value="percentage"]');
            const amountInput = document.getElementById('amountInput');
            const percentageInput = document.getElementById('percentageInput');
            
            if (percentageRadio && percentageRadio.checked) {
                if (amountInput) {
                    amountInput.style.display = 'none';
                    amountInput.classList.remove('active');
                }
                if (percentageInput) {
                    percentageInput.style.display = 'block';
                    percentageInput.classList.add('active');
                }
                // Remove required from amount input and add to percentage
                const amountInputField = amountInput?.querySelector('input');
                const percentageInputField = percentageInput?.querySelector('input');
                if (amountInputField) amountInputField.required = false;
                if (percentageInputField) percentageInputField.required = true;
            } else {
                if (amountInput) {
                    amountInput.style.display = 'block';
                    amountInput.classList.add('active');
                }
                if (percentageInput) {
                    percentageInput.style.display = 'none';
                    percentageInput.classList.remove('active');
                }
                // Add required to amount input and remove from percentage
                const amountInputField = amountInput?.querySelector('input');
                const percentageInputField = percentageInput?.querySelector('input');
                if (amountInputField) amountInputField.required = true;
                if (percentageInputField) percentageInputField.required = false;
            }
        }

        // Coming soon message for auto-save features
        function showComingSoonMessage(event) {
            event.preventDefault();
            event.target.checked = false;
            showSnackbar('Auto-Save features are coming soon! Stay tuned for automated savings.', 'info');
            return false;
        }

        // Enhanced Budget Page JavaScript
        // Wait for loading.js to be available
        function initializeBudget() {
            
            // Initialize loading screen with budget-specific message
            if (window.LoadingScreen) {
                window.budgetlyLoader = new LoadingScreen();
                
                // Customize the loading message for budget
                const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
                if (loadingMessage) {
                    loadingMessage.innerHTML = 'Loading your budget<span class="loading-dots-text">...</span>';
                } else {
                    console.error('Budget: Could not find loading message element');
                }
            } else {
                console.error('Budget: LoadingScreen class not available');
            }

            // Show initial loading for data fetch
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
            } else {
                console.error('Budget: budgetlyLoader not available');
            }

            // Initialize budget page
            loadBudgetData();
            
            // Hide loading screen after initialization
            if (window.budgetlyLoader) {
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                }, 500);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            
            // Enhanced loading screen availability check
            function checkLoadingScreen(attempts = 0) {
                const maxAttempts = 10;
                
                if (window.LoadingScreen) {
                    initializeBudget();
                } else if (attempts < maxAttempts) {
                    setTimeout(() => checkLoadingScreen(attempts + 1), 50);
                } else {
                    console.error('Budget: LoadingScreen still not available after', maxAttempts, 'attempts');
                    // Initialize without loading screen
                    loadBudgetData();
                }
            }
            
            checkLoadingScreen();
        });

        function loadBudgetData() {
            // Show loading screen for data refresh (but only if not initial load)
            if (window.budgetlyLoader && document.body.classList.contains('loaded')) {
                window.budgetlyLoader.show();
            }

            // Add your existing budget data loading logic here
            
            // Load budget template data if available
            const functionsToLoad = [
                'loadBudgetTemplate',
                'loadExpenseCategories'
            ];
            
            functionsToLoad.forEach(funcName => {
                if (typeof window[funcName] === 'function') {
                    window[funcName]();
                } else {
                    console.warn(`Budget: Function ${funcName} not found, skipping`);
                }
            });
            
            // Mark body as loaded after first successful load
            document.body.classList.add('loaded');
            
            // Hide loading screen after data processing
            if (window.budgetlyLoader) {
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                }, 500);
            }
        }

        // Load saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('personalTheme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
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

        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Add Expense Modal Functions
        function showAddExpenseModal() {
            loadExpenseCategories();
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('#addExpenseModal input[name="expense_date"]').value = today;
            showModal('addExpenseModal');
        }

        // View Expenses function - redirect to expense page
        function viewExpenses() {
            window.location.href = 'personal-expense';
        }

        // Load categories for expense form
        function loadExpenseCategories() {
            fetch('../api/budget_categories.php')
                .then(response => response.json())
                .then(data => {
                    const categorySelect = document.querySelector('#addExpenseModal select[name="category_id"]');
                    categorySelect.innerHTML = '<option value="">Select a category</option>';
                    
                    if (data.success && data.categories) {
                        data.categories.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = `${category.category_name} (₵${parseFloat(category.budget_limit || 0).toFixed(2)} budget)`;
                            categorySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                    showSnackbar('Failed to load categories', 'error');
                });
        }

        // Handle Add Expense Form Submission
        document.addEventListener('DOMContentLoaded', function() {
            const addExpenseForm = document.getElementById('addExpenseForm');
            if (addExpenseForm) {
                addExpenseForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;
                    
                    // Show loading state
                    submitButton.textContent = 'Adding...';
                    submitButton.disabled = true;
                    
                    fetch('../actions/personal_expense_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSnackbar('Expense added successfully!', 'success');
                            closeModal('addExpenseModal');
                            this.reset();
                            
                            // Refresh budget data with animation
                            refreshBudgetData();
                        } else {
                            showSnackbar(result.message || 'Failed to add expense', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showSnackbar('Failed to add expense. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    });
                });
            }
        });

        // Refresh budget data with animation
        function refreshBudgetData() {
            // This function will be enhanced by the existing budget.js
            // For now, we'll trigger a reload of the budget data
            if (typeof loadBudgetData === 'function') {
                loadBudgetData();
            } else {
                // Fallback: reload the page
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }

        // Enhance budget numbers with animation when data loads
        function animateBudgetNumbers(data) {
            if (data && data.overview) {
                const overview = data.overview;
                
                // Animate the overview cards
                if (document.getElementById('totalIncome')) {
                    animateNumber(document.getElementById('totalIncome'), 0, parseFloat(overview.total_income || 0), 1500, '₵');
                }
                if (document.getElementById('plannedBudget')) {
                    animateNumber(document.getElementById('plannedBudget'), 0, parseFloat(overview.total_planned || 0), 1500, '₵');
                }
                if (document.getElementById('actualSpending')) {
                    animateNumber(document.getElementById('actualSpending'), 0, parseFloat(overview.total_spent || 0), 1500, '₵');
                }
                if (document.getElementById('budgetPerformance')) {
                    animateNumber(document.getElementById('budgetPerformance'), 0, parseFloat(overview.budget_performance || 0), 1500, '', '%');
                }
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    </script>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="budget-modal">
        <div class="budget-modal-content">
            <div class="budget-modal-header">
                <h3>Confirm Delete</h3>
                <span class="budget-modal-close" onclick="closeModal('deleteConfirmModal')">&times;</span>
            </div>
            <div class="budget-modal-body">
                <div class="confirm-content">
                    <div class="confirm-icon">⚠️</div>
                    <h4>Delete Category?</h4>
                    <p>Are you sure you want to delete this category? This action cannot be undone and will remove all associated data.</p>
                </div>
            </div>
            <div class="budget-modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button type="button" class="btn-danger" id="confirmDeleteBtn">Delete Category</button>
            </div>
        </div>
    </div>

    <script src="../public/js/loading.js"></script>
    <script src="../public/js/budget.js"></script>
    <script src="../public/js/mobile-nav.js"></script>
    
    <!-- Walkthrough System -->
    <script src="../public/js/walkthrough.js?v=<?php echo time(); ?>"></script>
        <script src="../public/js/privacy.js"></script>

</body>
</html>