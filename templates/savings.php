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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings - Nkansah Budget Manager</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/mobile-nav.css">
    <link rel="stylesheet" href="../public/css/savings.css">
    <link rel="stylesheet" href="../public/css/loading.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h1 id="logoUserName"><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Finance Dashboard</p>
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
                <a href="budget" class="nav-item">Budget</a>
                <a href="personal-expense" class="nav-item">Expenses</a>
                <a href="savings" class="nav-item active">Savings</a>
                <!-- <a href="insights" class="nav-item">Insights</a> -->
                <a href="report" class="nav-item">Reports</a>
            </nav>

            <div class="theme-selector">
                <button class="theme-toggle-btn" onclick="toggleThemeSelector()" title="Change Theme">
                    <span class="theme-icon"><i class="fas fa-palette"></i></span>
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
                    <!-- <a href="profile.php">Profile Settings</a> -->
                    <!-- <a href="income-sources.php">Income Sources</a> -->
                    <!-- <a href="categories.php">Categories</a> -->
                    <!-- <hr> -->
                    <!-- <a href="family-dashboard.php">Switch to Family</a> -->
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
                    <h2><i class="fas fa-piggy-bank"></i> Savings Management</h2>
                    <p>Track your goals, manage auto-savings, and build your financial future</p>
                </div>
                <div class="page-actions">
                    <button class="quick-btn" onclick="showNewGoalModal()">
                        <span class="btn-icon"><i class="fas fa-bullseye"></i></span>
                        New Goal
                    </button>
                    <button class="quick-btn" onclick="showDepositModal()">
                        <span class="btn-icon"><i class="fas fa-dollar-sign"></i></span>
                        Add Deposit
                    </button>
                    <button class="quick-btn secondary" onclick="showAutoSaveModal()">
                        <span class="btn-icon"><i class="fas fa-cog"></i></span>
                        Auto-Save
                    </button>
                </div>
            </section>

            <!-- Savings Overview -->
            <section class="savings-overview">
                <div class="overview-card total-savings">
                    <div class="card-header">
                        <h3>Total Savings</h3>
                        <span class="card-icon"><i class="fas fa-piggy-bank"></i></span>
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
                        <span class="card-icon"><i class="fas fa-chart-bar"></i></span>
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
                        <span class="card-icon"><i class="fas fa-chart-line"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="savingsRateAmount">0%</div>
                        <div class="change" id="savingsRateChange">Loading...</div>
                        <div class="rate-comparison" id="rateComparison">
                            <span class="comparison-item">Target: Loading...</span>
                            <span class="comparison-item">Current: 0%</span>
                        </div>
                    </div>
                </div>


            </section>

            <!-- Savings Goals Grid -->
            <section class="savings-goals-section">
                <div class="section-header">
                    <h3><i class="fas fa-bullseye"></i> Savings Goals</h3>
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
                    <h3><i class="fas fa-cog"></i> Smart Auto-Save System</h3>
                    <div class="section-actions">
                        <button class="btn btn-secondary btn-sm" onclick="processAutoSave()" title="Process auto-save now"><i class="fas fa-bolt"></i> Process Now</button>
                        <button class="view-all" onclick="showAutoSaveModal()">Configure Settings</button>
                    </div>
                </div>

                <div class="auto-save-overview" id="autoSaveOverview">
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>Loading auto-save configuration...</p>
                    </div>
                </div>
            </section>

            <!-- Savings Challenges -->
            <section class="challenges-section">
                <div class="section-header">
                    <h3><i class="fas fa-trophy"></i> Savings Challenges</h3>
                    <button class="view-all" onclick="showCreateChallengeModal()">Create Challenge</button>
                </div>

                <div class="challenges-grid" id="challengesGrid">
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>Loading challenges...</p>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="recent-activity-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Recent Activity</h3>
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
    <div class="modal-content wide-modal">
        <div class="modal-header gradient-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="modal-title-section">
                    <h3>Create New Savings Goal</h3>
                    <p>Set targets and watch your savings grow</p>
                </div>
            </div>
            <span class="close modern-close" onclick="closeModal('newGoalModal')">&times;</span>
        </div>
        <form class="modal-form compact-form" id="newGoalForm">
            <div class="form-grid two-column">
                <div class="form-group">
                    <label><i class="fas fa-flag"></i> Goal Name</label>
                    <input type="text" name="goal_name" id="goalName" placeholder="e.g., Dream Vacation, New Car" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-list"></i> Goal Type</label>
                    <select name="goal_type" id="goalType" required>
                        <option value="">Choose type</option>
                        <!-- Options will be populated dynamically from database -->
                    </select>
                </div>
            </div>

            <div class="form-grid two-column">
                <div class="form-group">
                    <label><i class="fas fa-target"></i> Target Amount (₵)</label>
                    <input type="number" name="target_amount" id="targetAmount" step="1" placeholder="0" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-star"></i> Priority</label>
                    <select name="priority" id="priority" required>
                        <option value="high">🔴 High Priority</option>
                        <option value="medium" selected>🟡 Medium Priority</option>
                        <option value="low">🟢 Low Priority</option>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label><i class="fas fa-piggy-bank"></i> Initial Deposit (₵)</label>
                <input type="number" name="initial_deposit" id="initialDeposit" step="0.01" placeholder="0.00">
            </div>

            <div class="auto-save-section">
                <div class="section-header">
                    <h4><i class="fas fa-robot"></i> Auto-Save Settings</h4>
                    <label class="toggle-switch">
                        <input type="checkbox" name="auto_save_enabled" id="autoSaveEnabled" value="1" onclick="showComingSoonMessage(event)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div id="autoSaveOptions" class="auto-save-options" style="display: none;">
                    <div class="form-grid two-column">
                        <div class="form-group">
                            <label><i class="fas fa-cog"></i> Save Method</label>
                            <div class="radio-group modern-radio">
                                <label class="radio-option">
                                    <input type="radio" name="save_method" value="percentage" checked>
                                    <span class="radio-label">📊 Percentage of Income</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="save_method" value="fixed">
                                    <span class="radio-label">💰 Fixed Amount</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div id="percentageGroup">
                                <label><i class="fas fa-percentage"></i> Percentage of Income (%)</label>
                                <div class="input-with-slider modern-slider">
                                    <input type="range" name="save_percentage" id="savePercentage" min="1" max="50" value="10" class="config-slider">
                                    <span class="slider-value">10%</span>
                                </div>
                            </div>
                            <div id="fixedAmountGroup" style="display: none;">
                                <label><i class="fas fa-money-bill"></i> Fixed Amount (₵)</label>
                                <input type="number" name="save_amount" id="fixedSaveAmount" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="save-preview modern-preview">
                        <div class="preview-content">
                            <span class="preview-label">Estimated monthly save:</span>
                            <span class="preview-amount" id="savePreviewAmount">₵350.00</span>
                        </div>
                        <small class="preview-note">Based on current income settings</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="deduct_from_income" id="deductFromIncome" value="1">
                            <span class="checkmark"></span>
                            Deduct from available income automatically
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-actions modern-actions">
                <button type="button" class="btn-secondary modern-btn" onclick="closeModal('newGoalModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-primary modern-btn">
                    <i class="fas fa-bullseye"></i> Create Goal
                </button>
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
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('depositModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Deposit</button>
            </div>
        </form>
    </div>
</div>

    <!-- Auto-Save Configuration Modal -->
    <div id="autoSaveModal" class="modal">
        <div class="modal-content wide-modal">
            <div class="modal-header gradient-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="modal-title-section">
                        <h3>Smart Auto-Save Configuration</h3>
                        <p>Automate your savings and reach goals faster</p>
                    </div>
                </div>
                <span class="close modern-close" onclick="closeModal('autoSaveModal')">&times;</span>
            </div>
            <form class="modal-form compact-form" onsubmit="saveAutoSaveConfig(event)">
                <div class="auto-save-section">
                    <div class="section-header">
                        <h4><i class="fas fa-bullseye"></i> Automatic Savings</h4>
                        <label class="toggle-switch">
                            <input type="checkbox" id="autoSaveEnabled">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="auto-save-options" id="autoSaveDetails">
                        <div class="form-grid two-column">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Save Frequency</label>
                                <select id="saveFrequency">
                                    <option value="weekly">📅 Weekly</option>
                                    <option value="biweekly">🗓️ Bi-weekly</option>
                                    <option value="monthly" selected>📆 Monthly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Save Day</label>
                                <select id="saveDay">
                                    <option value="1">1st of month</option>
                                    <option value="15">15th of month</option>
                                    <option value="30">30th of month</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-grid two-column">
                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="emergencyFundPriority" checked>
                                    <span class="checkmark"></span>
                                    Prioritize Emergency Fund
                                </label>
                                <small class="field-note">Fill emergency fund first before other goals</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-shield-alt"></i> Emergency Fund Target (₵)</label>
                                <input type="number" id="emergencyFundTarget" value="1000" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="auto-save-section">
                    <div class="section-header">
                        <h4><i class="fas fa-coins"></i> Round-Up Savings</h4>
                        <label class="toggle-switch">
                            <input type="checkbox" id="roundUpEnabled">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="auto-save-options">
                        <div class="form-group">
                            <label><i class="fas fa-arrow-up"></i> Round up to nearest</label>
                            <select id="roundUpThreshold">
                                <option value="1">₵1.00</option>
                                <option value="5" selected>₵5.00</option>
                                <option value="10">₵10.00</option>
                            </select>
                        </div>
                        <div class="round-up-preview modern-preview">
                            <div class="preview-content">
                                <span class="preview-label">Example:</span>
                                <span class="preview-amount">₵27.30 → ₵30.00 (saves ₵2.70)</span>
                            </div>
                            <small class="preview-note">Based on selected round-up amount</small>
                        </div>
                    </div>
                </div>

                <div class="auto-save-section">
                    <div class="section-header">
                        <h4><i class="fas fa-sort"></i> Goal Allocation Priority</h4>
                    </div>
                    <div class="goal-allocation-list modern-list" id="goalAllocationList">
                        <div class="loading-placeholder">
                            <div class="loading-icon"><i class="fas fa-spinner fa-spin"></i></div>
                            <p>Loading goals...</p>
                        </div>
                    </div>
                </div>

                <div class="modal-actions modern-actions">
                    <button type="button" class="btn-secondary modern-btn" onclick="closeModal('autoSaveModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary modern-btn">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Challenge Modal -->
    <div id="createChallengeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Savings Challenge</h3>
                <span class="close" onclick="closeModal('createChallengeModal')">&times;</span>
            </div>
            <form class="modal-form" onsubmit="createChallenge(event)">
                <div class="form-section">
                    <div class="form-group">
                        <label>Challenge Type</label>
                        <select id="challengeType" onchange="updateChallengeForm()">
                            <option value="save_amount">Save Specific Amount</option>
                            <option value="no_spend">No-Spend Challenge</option>
                            <option value="reduce_category">Reduce Category Spending</option>
                            <option value="round_up">Round-Up Challenge</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Challenge Title</label>
                        <input type="text" id="challengeTitle" placeholder="e.g., Save ₵500 in January" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="challengeDescription" placeholder="Describe your challenge goals and motivation"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Target Amount</label>
                            <input type="number" id="challengeTargetAmount" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Reward Amount</label>
                            <input type="number" id="challengeRewardAmount" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" id="challengeStartDate" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" id="challengeEndDate" required>
                        </div>
                    </div>
                    
                    <div class="form-group" id="categoryGroup" style="display: none;">
                        <label>Target Category</label>
                        <select id="challengeCategory">
                            <option value="">Select category...</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createChallengeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Challenge</button>
                </div>
            </form>
        </div>
    </div>

                <!-- <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('autoSaveModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div> -->
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
                            <div class="goal-icon-large" id="goalIconLarge"><i class="fas fa-bullseye"></i></div>
                            <div class="goal-basic-info">
                                <h4 id="goalTitleLarge">Goal Name</h4>
                                <p id="goalDescriptionLarge">Goal description will be loaded dynamically</p>
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

    <!-- Comprehensive Auto-Save Configuration Modal -->
    <div id="comprehensiveAutoSaveModal" class="modal">
        <div class="modal-content extra-large">
            <div class="modal-header">
                <h3>🤖 Comprehensive Auto-Save Configuration</h3>
                <span class="close" onclick="closeModal('comprehensiveAutoSaveModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="autosave-config-tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="showTab('global-settings')">Global Settings</button>
                        <button class="tab-btn" onclick="showTab('goal-specific')">Goal-Specific</button>
                        <button class="tab-btn" onclick="showTab('conditions')">Advanced Conditions</button>
                        <button class="tab-btn" onclick="showTab('history')">Execution History</button>
                    </div>

                    <!-- Global Settings Tab -->
                    <div id="global-settings" class="tab-content active">
                        <h4>🌐 Global Auto-Save Settings</h4>
                        <div class="autosave-toggle">
                            <label class="switch">
                                <input type="checkbox" id="globalAutoSaveEnabled">
                                <span class="slider round"></span>
                            </label>
                            <span>Enable Global Auto-Save</span>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Default Save Amount</label>
                                <div class="input-group">
                                    <span class="input-prefix">₵</span>
                                    <input type="number" id="defaultSaveAmount" placeholder="100.00" step="0.01">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-chart-bar"></i> Default Save Percentage</label>
                                <div class="input-group">
                                    <input type="number" id="defaultSavePercentage" placeholder="10" min="1" max="100">
                                    <span class="input-suffix">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="trigger-section">
                            <h5><i class="fas fa-bullseye"></i> Auto-Save Triggers</h5>
                            <div class="trigger-grid">
                                <label class="trigger-option">
                                    <input type="checkbox" id="triggerSalary">
                                    <span class="checkmark"></span>
                                    <div class="trigger-info">
                                        <strong><i class="fas fa-briefcase"></i> Salary Added</strong>
                                        <small>Save when salary is added to account</small>
                                    </div>
                                </label>
                                
                                <label class="trigger-option">
                                    <input type="checkbox" id="triggerAdditional">
                                    <span class="checkmark"></span>
                                    <div class="trigger-info">
                                        <strong><i class="fas fa-money-bill-wave"></i> Additional Income</strong>
                                        <small>Save when any additional income is received</small>
                                    </div>
                                </label>
                                
                                <label class="trigger-option">
                                    <input type="checkbox" id="triggerScheduled">
                                    <span class="checkmark"></span>
                                    <div class="trigger-info">
                                        <strong>⏰ Scheduled</strong>
                                        <small>Save on specific dates/times</small>
                                    </div>
                                </label>
                                
                                <label class="trigger-option">
                                    <input type="checkbox" id="triggerExpense">
                                    <span class="checkmark"></span>
                                    <div class="trigger-info">
                                        <strong>🛒 After Expense</strong>
                                        <small>Round-up savings after expenses</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="allocation-section">
                            <h5><i class="fas fa-chart-line"></i> Goal Allocation Method</h5>
                            <div class="allocation-options">
                                <label class="radio-option">
                                    <input type="radio" name="allocationMethod" value="equal" checked>
                                    <span class="radio-checkmark"></span>
                                    <div class="option-info">
                                        <strong>⚖️ Equal Split</strong>
                                        <small>Divide amount equally among all active goals</small>
                                    </div>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="allocationMethod" value="priority">
                                    <span class="radio-checkmark"></span>
                                    <div class="option-info">
                                        <strong><i class="fas fa-bullseye"></i> Priority-Based</strong>
                                        <small>Allocate based on goal priority levels</small>
                                    </div>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="allocationMethod" value="percentage">
                                    <span class="radio-checkmark"></span>
                                    <div class="option-info">
                                        <strong><i class="fas fa-chart-bar"></i> Percentage-Based</strong>
                                        <small>Custom percentage allocation per goal</small>
                                    </div>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="allocationMethod" value="single">
                                    <span class="radio-checkmark"></span>
                                    <div class="option-info">
                                        <strong><i class="fas fa-bullseye"></i> Single Goal</strong>
                                        <small>Save to one specific goal only</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Goal-Specific Settings Tab -->
                    <div id="goal-specific" class="tab-content">
                        <h4><i class="fas fa-bullseye"></i> Goal-Specific Auto-Save Rules</h4>
                        <div class="goal-rules-list" id="goalRulesList">
                            <!-- Goal rules will be loaded dynamically -->
                        </div>
                        <button type="button" class="btn-secondary" onclick="addGoalRule()">+ Add Goal Rule</button>
                    </div>

                    <!-- Advanced Conditions Tab -->
                    <div id="conditions" class="tab-content">
                        <h4>🔧 Advanced Conditions & Limits</h4>
                        
                        <div class="conditions-grid">
                            <div class="condition-group">
                                <h5><i class="fas fa-dollar-sign"></i> Amount Limits</h5>
                                <div class="form-group">
                                    <label>Minimum Save Amount</label>
                                    <div class="input-group">
                                        <span class="input-prefix">₵</span>
                                        <input type="number" id="minSaveAmount" placeholder="5.00" step="0.01">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Maximum Save Amount</label>
                                    <div class="input-group">
                                        <span class="input-prefix">₵</span>
                                        <input type="number" id="maxSaveAmount" placeholder="1000.00" step="0.01">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="condition-group">
                                <h5>⏰ Time Conditions</h5>
                                <div class="form-group">
                                    <label>Schedule Type</label>
                                    <select id="scheduleType">
                                        <option value="">No Schedule</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="biweekly">Bi-Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="custom">Custom Dates</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Preferred Time</label>
                                    <input type="time" id="preferredTime" value="09:00">
                                </div>
                            </div>
                        </div>

                        <div class="condition-group">
                            <h5>🚦 Execution Conditions</h5>
                            <div class="condition-checks">
                                <label class="check-option">
                                    <input type="checkbox" id="checkAccountBalance">
                                    <span class="checkmark"></span>
                                    <span>Only save if account balance is sufficient</span>
                                </label>
                                <label class="check-option">
                                    <input type="checkbox" id="checkMonthlyBudget">
                                    <span class="checkmark"></span>
                                    <span>Respect monthly budget limits</span>
                                </label>
                                <label class="check-option">
                                    <input type="checkbox" id="pauseOnOverspend">
                                    <span class="checkmark"></span>
                                    <span>Pause auto-save if overspending detected</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Execution History Tab -->
                    <div id="history" class="tab-content">
                        <h4><i class="fas fa-chart-bar"></i> Auto-Save Execution History</h4>
                        <div class="history-stats">
                            <div class="stat-card">
                                <div class="stat-value" id="totalAutoSaved">₵0.00</div>
                                <div class="stat-label">Total Auto-Saved</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="totalExecutions">0</div>
                                <div class="stat-label">Total Executions</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="avgSaveAmount">₵0.00</div>
                                <div class="stat-label">Average Amount</div>
                            </div>
                        </div>
                        
                        <div class="history-list" id="autoSaveHistory">
                            <!-- History will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('comprehensiveAutoSaveModal')">Cancel</button>
                <button type="button" class="btn-primary" onclick="saveComprehensiveAutoSaveConfig()">💾 Save Configuration</button>
            </div>
        </div>
    </div>

    <script>
        // Show coming soon message for auto-save features
        function showComingSoonMessage(event) {
            event.preventDefault();
            event.target.checked = false; // Keep the toggle off
            
            // Show snackbar notification
            showSnackbar('Auto-save features are coming soon! Stay tuned for automated savings.', 'info');
        }

        // Animation function for counting numbers
        function animateNumber(element, start, end, duration, prefix = '', suffix = '') {
            const startTime = performance.now();
            const difference = end - start;
            
            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                
                const current = start + (difference * easeOutCubic);
                const formattedNumber = current.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                element.textContent = `${prefix}${formattedNumber}${suffix}`;
                
                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }
            
            requestAnimationFrame(updateNumber);
        }

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
    showSnackbar('Auto-save configuration coming soon!', 'info');
    // Temporarily disabled until feature is fully implemented
    // const modal = document.getElementById('comprehensiveAutoSaveModal');
    // if (modal) {
    //     modal.style.display = 'flex';
    //     modal.classList.add('show');
    //     setTimeout(() => modal.style.opacity = '1', 10);
    //     loadComprehensiveAutoSaveConfig();
    // } else {
    //     console.error('Comprehensive auto-save modal not found');
    // }
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
    Swal.fire({
        title: 'Pause Goal?',
        text: 'Are you sure you want to pause this goal? Auto-save will be disabled.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, pause it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/budget/actions/savings_handler.php', {
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
                    showSnackbar('Goal paused successfully', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Failed to pause goal', 'error');
                }
            })
            .catch(err => {
                console.error('Pause goal error:', err);
                Swal.fire('Error', 'Failed to pause goal', 'error');
            });
        }
    });
};

window.resumeGoal = function(goalId) {
    fetch('/budget/actions/savings_handler.php', {
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
            showSnackbar('Goal resumed successfully', 'success');
        } else {
            Swal.fire('Error', data.message || 'Failed to resume goal', 'error');
        }
    })
    .catch(err => {
        console.error('Resume goal error:', err);
        Swal.fire('Error', 'Failed to resume goal', 'error');
    });
};

window.setGoalInactive = function(goalId) {
    Swal.fire({
        title: 'Set Goal Inactive?',
        text: 'Are you sure you want to set this goal to inactive? Auto-save will be disabled.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, set inactive',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/budget/actions/savings_handler.php', {
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
                    showSnackbar('Goal set to inactive', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Failed to set goal inactive', 'error');
                }
            })
            .catch(err => {
                console.error('Set goal inactive error:', err);
                Swal.fire('Error', 'Failed to set goal inactive', 'error');
            });
        }
    });
};

// Helper function to update goal status in the UI without page refresh
function updateGoalStatus(goalId, newStatus) {
    const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
    if (!goalCard) return;
    
    // Update the data attribute
    goalCard.dataset.status = newStatus;
    
    // Remove existing status classes
    goalCard.classList.remove('active-status', 'paused-status', 'inactive-status');
    
    // Add new status class
    goalCard.classList.add(`${newStatus}-status`);
    
    // Update status badge
    const statusBadge = goalCard.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.className = `status-badge ${newStatus}`;
        switch(newStatus) {
            case 'active':
                statusBadge.innerHTML = '🟢 Active';
                break;
            case 'paused':
                statusBadge.innerHTML = '⏸️ Paused';
                break;
            case 'inactive':
                statusBadge.innerHTML = '⭕ Inactive';
                break;
        }
    }
    
    // Update action menu
    updateGoalActionMenu(goalCard, newStatus);
    
    // Update in the goals data if savingsManager exists
    if (window.savingsManager && window.savingsManager.currentGoals) {
        const goalIndex = window.savingsManager.currentGoals.findIndex(g => g.id == goalId);
        if (goalIndex !== -1) {
            window.savingsManager.currentGoals[goalIndex].status = newStatus;
        }
    }
    
    // Show visual feedback with animation
    goalCard.style.transform = 'scale(0.95)';
    goalCard.style.transition = 'all 0.2s ease';
    setTimeout(() => {
        goalCard.style.transform = 'scale(1)';
        // Add a subtle glow effect
        goalCard.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.3)';
        setTimeout(() => {
            goalCard.style.boxShadow = '';
        }, 1000);
    }, 150);
    
    // Update auto-save overview since goal status affects auto-save
    if (typeof loadAutoSaveOverview === 'function') {
        setTimeout(() => loadAutoSaveOverview(), 500);
    }
}

function updateGoalActionMenu(goalCard, status) {
    const actionsDropdown = goalCard.querySelector('.goal-actions-dropdown');
    if (!actionsDropdown) return;
    
    const goalId = goalCard.dataset.goalId;
    
    // Create menu options based on status
    let menuOptions = '';
    if (status === 'active') {
        menuOptions = `
            <a href="#" onclick="editGoal(${goalId})">Edit Goal</a>
            <a href="#" onclick="pauseGoal(${goalId})">Pause Goal</a>
            <a href="#" onclick="setGoalInactive(${goalId})">Set Inactive</a>
            <a href="#" onclick="addToGoal(${goalId})">Add Money</a>
            <hr>
            <a href="#" onclick="deleteGoal(${goalId})" class="danger">Delete Goal</a>
        `;
    } else if (status === 'paused') {
        menuOptions = `
            <a href="#" onclick="editGoal(${goalId})">Edit Goal</a>
            <a href="#" onclick="resumeGoal(${goalId})">Resume Goal</a>
            <a href="#" onclick="setGoalInactive(${goalId})">Set Inactive</a>
            <a href="#" onclick="addToGoal(${goalId})">Add Money</a>
            <hr>
            <a href="#" onclick="deleteGoal(${goalId})" class="danger">Delete Goal</a>
        `;
    } else { // inactive
        menuOptions = `
            <a href="#" onclick="editGoal(${goalId})">Edit Goal</a>
            <a href="#" onclick="resumeGoal(${goalId})">Activate Goal</a>
            <a href="#" onclick="addToGoal(${goalId})">Add Money</a>
            <hr>
            <a href="#" onclick="deleteGoal(${goalId})" class="danger">Delete Goal</a>
        `;
    }
    
    actionsDropdown.innerHTML = menuOptions;
}

function updateGoalActionButtons(goalCard, status) {
    const actionsDropdown = goalCard.querySelector('.goal-actions-dropdown');
    if (!actionsDropdown) return;
    
    // Find existing action buttons
    const pauseButton = actionsDropdown.querySelector('[onclick*="pauseGoal"]');
    const resumeButton = actionsDropdown.querySelector('[onclick*="resumeGoal"]');
    const inactiveButton = actionsDropdown.querySelector('[onclick*="setGoalInactive"]');
    
    // Hide all status action buttons first
    if (pauseButton) pauseButton.style.display = 'none';
    if (resumeButton) resumeButton.style.display = 'none';
    if (inactiveButton) inactiveButton.style.display = 'none';
    
    // Show appropriate buttons based on status
    switch (status) {
        case 'active':
            if (pauseButton) pauseButton.style.display = 'block';
            if (inactiveButton) inactiveButton.style.display = 'block';
            break;
        case 'paused':
        case 'inactive':
            if (resumeButton) resumeButton.style.display = 'block';
            break;
    }
}

window.addToGoal = function(goalId) {
    showDepositModal();
};

window.viewGoalDetails = function(goalId) {
    const modal = document.getElementById('goalDetailsModal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

// Auto-Save System Functions

window.showCreateChallengeModal = function() {
    showSnackbar('Savings challenges coming soon! Stay tuned for this exciting feature.', 'info');
    // Temporarily disabled until feature is fully implemented
    // const modal = document.getElementById('createChallengeModal');
    // if (modal) {
    //     // Set default dates
    //     const today = new Date();
    //     const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
    //     
    //     document.getElementById('challengeStartDate').value = today.toISOString().split('T')[0];
    //     document.getElementById('challengeEndDate').value = nextWeek.toISOString().split('T')[0];
    //     modal.style.display = 'flex';
    //     modal.classList.add('show');
    //     setTimeout(() => modal.style.opacity = '1', 10);
    // }
};

function loadAutoSaveConfig() {
    fetch('/budget/api/comprehensive_autosave.php?action=get_autosave_config')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAutoSaveConfiguration(data.data);
            } else {
                console.error('Failed to load auto-save config:', data.message);
                document.getElementById('autoSaveOverview').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Failed to load auto-save configuration</p>
                        <button class="btn btn-primary btn-sm" onclick="loadAutoSaveConfig()">Retry</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading auto-save config:', error);
            document.getElementById('autoSaveOverview').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading auto-save configuration</p>
                    <button class="btn btn-primary btn-sm" onclick="loadAutoSaveConfig()">Retry</button>
                </div>
            `;
        });
}

function displayAutoSaveConfiguration(data) {
    const { global_config, goal_configs, goals } = data;
    const overview = document.getElementById('autoSaveOverview');
    
    const isEnabled = global_config && global_config.enabled;
    
    let html = `
        <div class="auto-save-status ${isEnabled ? 'enabled' : 'disabled'}">
            <div class="status-indicator">
                <i class="fas fa-circle ${isEnabled ? 'text-success' : 'text-muted'}"></i>
                <span class="status-text">${isEnabled ? 'Active' : 'Disabled'}</span>
            </div>
            <div class="status-details">
                ${isEnabled ? 
                    `<p>Auto-save is configured to save ${global_config.save_type === 'percentage' ? global_config.save_percentage + '%' : '₵' + global_config.save_amount} 
                     ${global_config.trigger_salary ? 'when salary is received' : ''}
                     ${global_config.trigger_additional_income ? ', additional income' : ''}
                     ${global_config.trigger_schedule ? ', on schedule (' + global_config.schedule_frequency + ')' : ''}</p>` : 
                    '<p>Configure auto-save to automatically allocate money to your savings goals.</p>'
                }
            </div>
        </div>
    `;
    
    if (isEnabled && goals.length > 0) {
        html += `
            <div class="goals-allocation">
                <h4>Goal Management (${global_config.allocation_method ? global_config.allocation_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Priority Based'})</h4>
                <div class="goals-list">
        `;
        
        goals.forEach(goal => {
            const progressPercentage = goal.progress_percentage || 0;
            const hasAutoSave = goal_configs.find(gc => gc.goal_id == goal.id);
            
            html += `
                <div class="goal-allocation-item">
                    <div class="goal-info">
                        <span class="goal-name">${goal.title}</span>
                        <span class="goal-status status-${goal.status}">${goal.status}</span>
                        ${hasAutoSave ? '<i class="fas fa-robot auto-save-icon" title="Auto-save enabled for this goal"></i>' : ''}
                    </div>
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.min(progressPercentage, 100)}%"></div>
                        </div>
                        <span class="progress-text">₵${parseFloat(goal.current_amount || 0).toFixed(2)} / ₵${parseFloat(goal.target_amount).toFixed(2)}</span>
                    </div>
                    <div class="goal-actions">
                        <button class="btn-icon ${goal.status === 'paused' ? 'btn-success' : 'btn-warning'}" 
                                onclick="${goal.status === 'paused' ? 'resumeGoal' : 'pauseGoal'}(${goal.id})"
                                title="${goal.status === 'paused' ? 'Resume' : 'Pause'} goal">
                            <i class="fas fa-${goal.status === 'paused' ? 'play' : 'pause'}"></i>
                        </button>
                        <button class="btn-icon btn-secondary" 
                                onclick="setGoalInactive(${goal.id})"
                                title="Set goal inactive">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    overview.innerHTML = html;
}

function updateSaveDayOptions(frequency, currentDay) {
    const saveDaySelect = document.getElementById('saveDay');
    saveDaySelect.innerHTML = '';
    
    if (frequency === 'weekly' || frequency === 'biweekly') {
        // Days of week
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        days.forEach((day, index) => {
            const option = document.createElement('option');
            option.value = index + 1;
            option.textContent = day;
            if (index + 1 === currentDay) option.selected = true;
            saveDaySelect.appendChild(option);
        });
    } else {
        // Days of month
        for (let i = 1; i <= 31; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i + getOrdinalSuffix(i) + ' of month';
            if (i === currentDay) option.selected = true;
            saveDaySelect.appendChild(option);
        }
    }
}

function getOrdinalSuffix(day) {
    if (day >= 11 && day <= 13) return 'th';
    switch (day % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
    }
}

function populateGoalAllocationList(goals) {
    const container = document.getElementById('goalAllocationList');
    
    if (goals.length === 0) {
        container.innerHTML = '<p>No active goals found. Create some goals first to configure auto-save allocation.</p>';
        return;
    }
    
    let html = '';
    goals.forEach(goal => {
        const progressWidth = goal.progress_percentage;
        const isAutoSaveEnabled = goal.auto_save_enabled;
        
        html += `
            <div class="goal-allocation-item">
                <div class="goal-info">
                    <h5>${goal.goal_name}</h5>
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressWidth}%"></div>
                        </div>
                        <span class="progress-text">${progressWidth}% (₵${goal.current_amount.toLocaleString()} / ₵${goal.target_amount.toLocaleString()})</span>
                    </div>
                </div>
                <div class="goal-allocation-controls">
                    <label class="toggle-label">
                        <input type="checkbox" ${isAutoSaveEnabled ? 'checked' : ''} onchange="toggleGoalAutoSave(${goal.id}, this.checked)">
                        Auto-save enabled
                    </label>
                    ${isAutoSaveEnabled ? `
                        <div class="allocation-method">
                            <select onchange="updateGoalSaveMethod(${goal.id}, this.value)">
                                <option value="percentage" ${goal.save_method === 'percentage' ? 'selected' : ''}>Percentage</option>
                                <option value="fixed" ${goal.save_method === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
                            </select>
                            ${goal.save_method === 'percentage' ? 
                                `<input type="number" value="${goal.save_percentage}" min="0" max="100" step="0.1" onchange="updateGoalSaveAmount(${goal.id}, this.value, 'percentage')">%` :
                                `<input type="number" value="${goal.save_amount}" min="0" step="0.01" onchange="updateGoalSaveAmount(${goal.id}, this.value, 'fixed')">₵`
                            }
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function toggleAutoSaveDetails() {
    const enabled = document.getElementById('autoSaveEnabled').checked;
    const details = document.getElementById('autoSaveDetails');
    details.style.display = enabled ? 'block' : 'none';
}

function saveAutoSaveConfig(event) {
    event.preventDefault();
    
    // Show loading screen during save
    if (window.budgetlyLoader) {
        window.budgetlyLoader.show();
    }
    
    const formData = new FormData();
    formData.append('action', 'update_config');
    formData.append('enabled', document.getElementById('autoSaveEnabled').checked);
    formData.append('save_frequency', document.getElementById('saveFrequency').value);
    formData.append('save_day', document.getElementById('saveDay').value);
    formData.append('round_up_enabled', document.getElementById('roundUpEnabled').checked);
    formData.append('round_up_threshold', document.getElementById('roundUpThreshold').value);
    formData.append('emergency_fund_priority', document.getElementById('emergencyFundPriority').checked);
    formData.append('emergency_fund_target', document.getElementById('emergencyFundTarget').value);
    
    fetch('/budget/api/autosave_config.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading screen
        if (window.budgetlyLoader) {
            window.budgetlyLoader.hide();
        }
        
        if (data.success) {
            showSnackbar('Auto-save configuration updated successfully', 'success');
            closeModal('autoSaveModal');
            loadAutoSaveOverview();
        } else {
            showSnackbar(data.message || 'Failed to update configuration', 'error');
        }
    })
    .catch(error => {
        // Hide loading screen on error
        if (window.budgetlyLoader) {
            window.budgetlyLoader.hide();
        }
        console.error('Error saving config:', error);
        showSnackbar('Error saving configuration', 'error');
    });
}

function createChallenge(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'create_challenge');
    formData.append('challenge_type', document.getElementById('challengeType').value);
    formData.append('title', document.getElementById('challengeTitle').value);
    formData.append('description', document.getElementById('challengeDescription').value);
    formData.append('target_amount', document.getElementById('challengeTargetAmount').value);
    formData.append('start_date', document.getElementById('challengeStartDate').value);
    formData.append('end_date', document.getElementById('challengeEndDate').value);
    formData.append('reward_amount', document.getElementById('challengeRewardAmount').value);
    
    const categoryField = document.getElementById('challengeCategory');
    if (categoryField.style.display !== 'none' && categoryField.value) {
        formData.append('target_category_id', categoryField.value);
    }
    
    fetch('/budget/api/autosave_config.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar('Challenge created successfully!', 'success');
            closeModal('createChallengeModal');
            loadChallenges();
        } else {
            showSnackbar(data.message || 'Failed to create challenge', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating challenge:', error);
        showSnackbar('Error creating challenge', 'error');
    });
}

function updateChallengeForm() {
    const challengeType = document.getElementById('challengeType').value;
    const categoryGroup = document.getElementById('categoryGroup');
    const targetAmountField = document.getElementById('challengeTargetAmount');
    
    // Show/hide category selection based on challenge type
    if (challengeType === 'reduce_category') {
        categoryGroup.style.display = 'block';
        targetAmountField.placeholder = 'Reduction target (e.g., 50.00)';
    } else {
        categoryGroup.style.display = 'none';
        targetAmountField.placeholder = challengeType === 'save_amount' ? 'Amount to save' : '0.00';
    }
}

function loadAutoSaveOverview() {
    fetch('/budget/api/autosave_config.php?action=get_config')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAutoSaveOverview(data.config, data.goals);
            }
        })
        .catch(error => console.error('Error loading overview:', error));
}

function renderAutoSaveOverview(config, goals) {
    const container = document.getElementById('autoSaveOverview');
    
    if (!config.enabled) {
        container.innerHTML = `
            <div class="auto-save-disabled">
                <div class="disabled-icon">⏸️</div>
                <h4>Auto-Save is Disabled</h4>
                <p>Enable auto-save to automatically distribute your savings to goals</p>
                <button class="btn btn-primary" onclick="showAutoSaveModal()">Enable Auto-Save</button>
            </div>
        `;
        return;
    }
    
    const activeGoals = goals.filter(g => g.auto_save_enabled);
    const totalAutoSaveAmount = activeGoals.reduce((sum, g) => sum + (g.save_method === 'percentage' ? g.save_percentage : g.save_amount), 0);
    
    let html = `
        <div class="auto-save-status">
            <div class="status-card enabled">
                <div class="status-icon">✅</div>
                <div class="status-info">
                    <h4>Auto-Save Active</h4>
                    <p>Frequency: ${config.save_frequency.charAt(0).toUpperCase() + config.save_frequency.slice(1)}</p>
                    <p>${activeGoals.length} goal${activeGoals.length !== 1 ? 's' : ''} configured</p>
                </div>
                <div class="status-actions">
                    <button class="btn btn-sm btn-primary" onclick="showAutoSaveModal()">Configure</button>
                </div>
            </div>
        </div>
        
        <div class="auto-save-goals">
            <h5>Configured Goals</h5>
            <div class="goals-list">
    `;
    
    if (activeGoals.length === 0) {
        html += '<p>No goals configured for auto-save. <a href="#" onclick="showAutoSaveModal()">Configure now</a></p>';
    } else {
        activeGoals.forEach(goal => {
            const amount = goal.save_method === 'percentage' ? 
                `${goal.save_percentage}% of savings` : 
                `₵${goal.save_amount.toLocaleString()}`;
            
            html += `
                <div class="goal-item">
                    <span class="goal-name">${goal.goal_name}</span>
                    <span class="goal-amount">${amount}</span>
                </div>
            `;
        });
    }
    
    html += '</div></div>';
    container.innerHTML = html;
}

function loadChallenges() {
    fetch('/budget/api/autosave_config.php?action=get_challenges')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderChallenges(data.challenges);
            }
        })
        .catch(error => console.error('Error loading challenges:', error));
}

function renderChallenges(challenges) {
    const container = document.getElementById('challengesGrid');
    
    if (challenges.length === 0) {
        container.innerHTML = `
            <div class="no-challenges">
                <div class="empty-icon">🎯</div>
                <h4>No Active Challenges</h4>
                <p>Create savings challenges to make saving more fun and engaging</p>
                <button class="btn btn-primary" onclick="showCreateChallengeModal()">Create First Challenge</button>
            </div>
        `;
        return;
    }
    
    let html = '';
    challenges.forEach(challenge => {
        const statusClass = challenge.status === 'completed' ? 'completed' : 
                           challenge.status === 'failed' ? 'failed' : 'active';
        const progressWidth = challenge.progress_percentage;
        const daysLeft = Math.ceil(challenge.days_remaining);
        
        html += `
            <div class="challenge-card ${statusClass}">
                <div class="challenge-header">
                    <h4>${challenge.title}</h4>
                    <span class="challenge-status">${challenge.status}</span>
                </div>
                <div class="challenge-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progressWidth}%"></div>
                    </div>
                    <div class="progress-text">
                        ₵${challenge.current_amount.toLocaleString()} / ₵${challenge.target_amount.toLocaleString()} (${progressWidth}%)
                    </div>
                </div>
                <div class="challenge-details">
                    <p>${challenge.description}</p>
                    <div class="challenge-meta">
                        <span>Type: ${challenge.challenge_type.replace('_', ' ')}</span>
                        <span>${daysLeft} days left</span>
                    </div>
                </div>
                <div class="challenge-actions">
                    ${challenge.status === 'active' ? `
                        <button class="btn btn-sm btn-primary" onclick="addChallengeProgress(${challenge.id})">Add Progress</button>
                        <button class="btn btn-sm btn-secondary" onclick="abandonChallenge(${challenge.id})">Abandon</button>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Enhanced Savings Page JavaScript
// Wait for loading.js to be available
function initializeSavings() {
    console.log('Savings: Initializing savings page');
    console.log('Savings: LoadingScreen available?', typeof window.LoadingScreen);
    
    // Initialize loading screen with savings-specific message
    if (window.LoadingScreen) {
        console.log('Savings: Creating LoadingScreen');
        window.budgetlyLoader = new LoadingScreen();
        console.log('Savings: LoadingScreen created', window.budgetlyLoader);
        
        // Customize the loading message for savings
        const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
        if (loadingMessage) {
            loadingMessage.innerHTML = 'Loading your savings<span class="loading-dots-text">...</span>';
            console.log('Savings: Loading message customized');
        } else {
            console.error('Savings: Could not find loading message element');
        }
    } else {
        console.error('Savings: LoadingScreen class not available');
    }

    // Show initial loading for data fetch
    if (window.budgetlyLoader) {
        console.log('Savings: Showing loading screen');
        window.budgetlyLoader.show();
    } else {
        console.error('Savings: budgetlyLoader not available');
    }

    // Initialize savings page
    loadSavingsData();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Savings: DOMContentLoaded fired');
    
    // Enhanced loading screen availability check
    function checkLoadingScreen(attempts = 0) {
        const maxAttempts = 10;
        
        if (window.LoadingScreen) {
            console.log('Savings: LoadingScreen found after', attempts, 'attempts');
            initializeSavings();
        } else if (attempts < maxAttempts) {
            console.log('Savings: LoadingScreen not ready, attempt', attempts + 1, 'of', maxAttempts);
            setTimeout(() => checkLoadingScreen(attempts + 1), 50);
        } else {
            console.error('Savings: LoadingScreen still not available after', maxAttempts, 'attempts');
            // Initialize without loading screen
            loadSavingsData();
        }
    }
    
    checkLoadingScreen();
});

function loadSavingsData() {
    // Show loading screen for data refresh (but only if not initial load)
    if (window.budgetlyLoader && document.body.classList.contains('loaded')) {
        window.budgetlyLoader.show();
    }

    // Initialize savings manager
    if (typeof SavingsManager !== 'undefined') {
        window.savingsManager = new SavingsManager();
    }
    
    // Load goal types dynamically
    if (typeof loadGoalTypes === 'function') {
        loadGoalTypes();
    }
    
    // Load saved theme
    if (typeof changeTheme === 'function') {
        const savedTheme = localStorage.getItem('personalTheme') || 'default';
        changeTheme(savedTheme);
    }

    // Load initial data - only call functions that exist
    const functionsToLoad = [
        'loadAutoSaveOverview',
        'loadChallenges',
        'loadAutoSaveConfig'
    ];
    
    console.log('Savings: Loading page functions...');
    functionsToLoad.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`Savings: Loading ${funcName}...`);
            window[funcName]();
        } else {
            console.warn(`Savings: Function ${funcName} not found, skipping`);
        }
    });
    
    // Mark body as loaded after first successful load
    document.body.classList.add('loaded');
    
    // Hide loading screen after data processing
    if (window.budgetlyLoader) {
        setTimeout(() => {
            window.budgetlyLoader.hide();
        }, 2000);
    }
}

// Event listeners for auto-save functionality (legacy - removed redundant loading code)
document.addEventListener('DOMContentLoaded', function() {    
    // Save frequency change handler
    const saveFrequencySelect = document.getElementById('saveFrequency');
    if (saveFrequencySelect) {
        saveFrequencySelect.addEventListener('change', function() {
            updateSaveDayOptions(this.value, 1);
        });
    }
    
    // Auto-save enabled toggle
    const autoSaveEnabledToggle = document.getElementById('autoSaveEnabled');
    if (autoSaveEnabledToggle) {
        autoSaveEnabledToggle.addEventListener('change', toggleAutoSaveDetails);
    }
});

// Additional helper functions for the auto-save system
function toggleGoalAutoSave(goalId, enabled) {
    const formData = new FormData();
    formData.append('action', 'update_auto_save');
    formData.append('goal_id', goalId);
    formData.append('auto_save_enabled', enabled);
    
    fetch('/budget/actions/savings_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar('Goal auto-save updated', 'success');
            loadAutoSaveConfig(); // Reload the config to update UI
        } else {
            showSnackbar(data.message || 'Failed to update auto-save', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating auto-save:', error);
        showSnackbar('Error updating auto-save', 'error');
    });
}

function updateGoalSaveMethod(goalId, method) {
    // This will be called when save method dropdown changes
    const formData = new FormData();
    formData.append('action', 'update_auto_save');
    formData.append('goal_id', goalId);
    formData.append('save_method', method);
    
    fetch('/budget/actions/savings_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadAutoSaveConfig(); // Reload to update the form
        } else {
            showSnackbar(data.message || 'Failed to update save method', 'error');
        }
    })
    .catch(error => console.error('Error updating save method:', error));
}

function updateGoalSaveAmount(goalId, amount, type) {
    const formData = new FormData();
    formData.append('action', 'update_auto_save');
    formData.append('goal_id', goalId);
    
    if (type === 'percentage') {
        formData.append('save_percentage', amount);
    } else {
        formData.append('save_amount', amount);
    }
    
    fetch('/budget/actions/savings_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar('Save amount updated', 'success');
        } else {
            showSnackbar(data.message || 'Failed to update amount', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating save amount:', error);
        showSnackbar('Error updating save amount', 'error');
    });
}

function addChallengeProgress(challengeId) {
    Swal.fire({
        title: 'Add Progress',
        html: `
            <div class="swal-form">
                <input type="number" id="swal-amount" class="swal2-input" placeholder="Enter amount (₵)" min="0" step="0.01">
                <input type="text" id="swal-description" class="swal2-input" placeholder="Description (optional)">
            </div>
        `,
        confirmButtonText: 'Add Progress',
        showCancelButton: true,
        preConfirm: () => {
            const amount = document.getElementById('swal-amount').value;
            const description = document.getElementById('swal-description').value || '';
            
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                Swal.showValidationMessage('Please enter a valid amount');
                return false;
            }
            
            return { amount: amount, description: description };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { amount, description } = result.value;
            
            const formData = new FormData();
            formData.append('action', 'add_challenge_progress');
            formData.append('challenge_id', challengeId);
            formData.append('amount', amount);
            formData.append('description', description);
            
            fetch('/budget/api/autosave_config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSnackbar(data.message, data.is_completed ? 'success' : 'info');
                    loadChallenges(); // Reload challenges
                } else {
                    showSnackbar(data.message || 'Failed to add progress', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding progress:', error);
                showSnackbar('Error adding progress', 'error');
            });
        }
    });
}

function abandonChallenge(challengeId) {
    Swal.fire({
        title: 'Abandon Challenge?',
        text: 'Are you sure you want to abandon this challenge? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, abandon it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'abandon_challenge');
            formData.append('challenge_id', challengeId);
            
            fetch('/budget/api/autosave_config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSnackbar('Challenge abandoned', 'info');
                    loadChallenges();
                } else {
                    showSnackbar(data.message || 'Failed to abandon challenge', 'error');
                }
            })
            .catch(error => {
                console.error('Error abandoning challenge:', error);
                showSnackbar('Error abandoning challenge', 'error');
            });
        }
    });
}

function processAutoSave() {
    // Show loading screen during auto-save processing
    if (window.budgetlyLoader) {
        window.budgetlyLoader.show();
    }
    
    fetch('/budget/api/autosave_config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=process_autosave'
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading screen
        if (window.budgetlyLoader) {
            window.budgetlyLoader.hide();
        }
        
        if (data.success) {
            showSnackbar(`Auto-save processed! Saved ₵${data.total_saved.toLocaleString()} to ${data.goals_processed} goals`, 'success');
            // Reload data to show updated amounts
            if (window.savingsManager) {
                window.savingsManager.loadData();
            }
        } else {
            showSnackbar(data.message || 'Failed to process auto-save', 'error');
        }
    })
    .catch(error => {
        // Hide loading screen on error
        if (window.budgetlyLoader) {
            window.budgetlyLoader.hide();
        }
        console.error('Error processing auto-save:', error);
        showSnackbar('Error processing auto-save', 'error');
    });
}

window.deleteGoal = function(goalId) {
    Swal.fire({
        title: 'Delete Goal?',
        text: 'Are you sure you want to delete this goal? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/budget/actions/savings_handler.php', {
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
                    showSnackbar('Goal deleted successfully', 'success');
                } else {
                    showSnackbar(data.message || 'Failed to delete goal', 'error');
                }
            })
            .catch(err => console.error('Delete goal error:', err));
        }
    });
};

window.archiveGoal = function(goalId) {
    // Implementation will be added
};

window.toggleGoalMenu = function(btn) {
    const dropdown = btn.nextElementSibling;
    const isVisible = dropdown.classList.contains('show');
    
    // Close all other dropdowns first
    document.querySelectorAll('.goal-actions-dropdown.show').forEach(d => {
        if (d !== dropdown) {
            d.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    if (!isVisible) {
        dropdown.classList.add('show');
        
        // Close dropdown when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 10);
    } else {
        dropdown.classList.remove('show');
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
            const response = await fetch('/budget/actions/savings_handler.php?action=get_savings_overview', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.savingsOverviewData = data.data;
                this.updateSavingsOverviewDisplay(data.data);
                // Update budget allocation with accurate committed amounts
                this.updateBudgetAllocationDisplay();
            } else {
                console.error('Failed to load savings overview:', data.message);
                this.savingsOverviewData = null;
                this.updateSavingsOverviewDisplay(null);
            }
        } catch (error) {
            console.error('Error loading savings overview:', error);
            this.savingsOverviewData = null;
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
            animateNumber(totalSavingsAmount, 0, data.total_savings, 2500, '₵');
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
            animateNumber(savingsRateAmount, 0, data.savings_rate, 2200, '', '%');
        }

        const savingsRateChange = document.getElementById('savingsRateChange');
        if (savingsRateChange) {
            const rateChangeText = data.savings_rate_change >= 0 ? 
                `+${data.savings_rate_change}% from last month` : 
                `${data.savings_rate_change}% from last month`;
            savingsRateChange.textContent = rateChangeText;
            savingsRateChange.className = `change ${data.rate_change_direction}`;
        }

        // Update comparison targets with dynamic data
        const rateComparison = document.getElementById('rateComparison');
        if (rateComparison) {
            const targetPercentage = data.target_savings_percentage || 20;
            rateComparison.innerHTML = `
                <span class="comparison-item">Target: ${targetPercentage}%</span>
                <span class="comparison-item">Current: ${data.savings_rate}%</span>
            `;
        }
    }

    async loadSavingsData() {
        try {
            const response = await fetch('/budget/actions/savings_handler.php?action=get_goals', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentGoals = data.goals || [];
                this.autoSaveSettings = data.auto_save_settings || {};
                this.budgetAllocation = data.budget_allocation || {};
                this.updateGoalsDisplay();
                this.updateMonthlyTargetDisplay();
                this.updateBudgetAllocationDisplay();
                // Populate goal dropdowns with loaded goals
                populateGoalDropdowns();
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
                                <div class="status-line">${statusBadge}</div>
                                <div class="priority-line">
                                    <span class="priority-badge ${goal.priority}">${goal.priority.toUpperCase()}</span>
                                </div>
                            </div>
                        </div>
                        <div class="goal-menu">
                            <button class="menu-btn" onclick="toggleGoalMenu(this)">⋯</button>
                            <div class="goal-actions-dropdown">
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
                            <div class="current-amount">${formatCurrency(goal.current_amount)}</div>
                            <div class="target-amount">of ${formatCurrency(goal.target_amount)}</div>
                            <div class="remaining">${isCompleted ? 'Goal Achieved! 🎉' : `₵${remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} to go`}</div>
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
            'retirement': '🏖️',
            'investment': '📈',
            'debt_payoff': '💳',
            'business': '💼',
            'technology': '💻',
            'health': '🏥',
            'entertainment': '🎬',
            'shopping': '🛍️',
            'travel': '✈️',
            'wedding': '💒',
            'other': '🎯'
        };
        return icons[goalType] || '🎯';
    }

    async loadRecentActivity() {
        try {
            const response = await fetch('/budget/actions/savings_handler.php?action=get_recent_activity', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
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
            return { text: `+${formatCurrency(activity.amount)}`, class: 'positive' };
        }
        return { text: formatCurrency(0), class: 'neutral' };
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
                const remaining = goal.target_amount - goal.current_amount;
                const option = document.createElement('option');
                option.value = goal.id;
                option.textContent = `${this.getGoalIcon(goal.goal_type)} ${goal.goal_name} (${formatCurrency(goal.current_amount)} / ${formatCurrency(goal.target_amount)})`;
                option.dataset.remaining = remaining.toFixed(2);
                option.dataset.currentAmount = goal.current_amount;
                option.dataset.targetAmount = goal.target_amount;
                goalSelect.appendChild(option);
            }
        });

        // Add event listener to update amount field constraints when goal is selected
        goalSelect.addEventListener('change', this.updateDepositConstraints.bind(this));
    }

    updateDepositConstraints() {
        const goalSelect = document.getElementById('depositGoal');
        const amountInput = document.getElementById('depositAmount');
        const selectedOption = goalSelect.selectedOptions[0];
        
        if (selectedOption && selectedOption.value) {
            const remaining = parseFloat(selectedOption.dataset.remaining);
            const currentAmount = parseFloat(selectedOption.dataset.currentAmount);
            const targetAmount = parseFloat(selectedOption.dataset.targetAmount);
            
            // Set maximum amount to remaining
            amountInput.max = remaining.toFixed(2);
            amountInput.placeholder = `Maximum: ₵${remaining.toFixed(2)}`;
            
            // Add helper text
            let helperText = amountInput.parentNode.querySelector('.deposit-helper');
            if (!helperText) {
                helperText = document.createElement('small');
                helperText.className = 'deposit-helper';
                amountInput.parentNode.appendChild(helperText);
            }
            helperText.textContent = `Remaining to complete goal: ₵${remaining.toFixed(2)}`;
            helperText.style.color = '#666';
            helperText.style.fontSize = '12px';
            helperText.style.marginTop = '4px';
            helperText.style.display = 'block';
            
        } else {
            // Reset constraints
            amountInput.removeAttribute('max');
            amountInput.placeholder = '0.00';
            
            // Remove helper text
            const helperText = amountInput.parentNode.querySelector('.deposit-helper');
            if (helperText) {
                helperText.remove();
            }
        }
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
            animateNumber(targetAmountElement, 0, totalMonthlyTarget, 2000, '₵');
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
            const allocatedSavings = this.budgetAllocation.allocated_savings || 0;
            animateNumber(allocationAmountElement, 0, allocatedSavings, 1800, '₵');
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
            // Use actual monthly contributions from savings overview if available
            const usedAmount = this.savingsOverviewData ? this.savingsOverviewData.monthly_contributions : 0;
            const totalAllocated = this.budgetAllocation.allocated_savings;
            const remainingAmount = Math.max(0, totalAllocated - usedAmount);
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
            const actualCommitted = this.savingsOverviewData ? this.savingsOverviewData.monthly_contributions : 0;
            const totalAllocated = this.budgetAllocation.allocated_savings;
            const percentageUsed = totalAllocated > 0 ? (actualCommitted / totalAllocated) * 100 : 0;
            allocationBreakdownElement.innerHTML = `
                <small>Current contributions using ${percentageUsed.toFixed(1)}% of allocation</small>
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
            targetAmountElement.textContent = formatCurrency(this.budgetAllocation.allocated_savings);
        }

        if (targetPercentageElement) {
            targetPercentageElement.textContent = `${this.budgetAllocation.savings_percentage}% of salary (Budget Allocation)`;
        }

        // Update progress based on current month's progress toward allocated amount
        const monthlyProgressFill = document.getElementById('monthlyProgressFill');
        const monthlyProgressText = document.getElementById('monthlyProgressText');
        
        if (monthlyProgressFill && monthlyProgressText) {
            // Use actual monthly contributions from savings overview if available
            const actualCommitted = this.savingsOverviewData ? this.savingsOverviewData.monthly_contributions : 0;
            const allocatedAmount = this.budgetAllocation.allocated_savings;
            const progressPercentage = allocatedAmount > 0 ? (actualCommitted / allocatedAmount) * 100 : 0;
            const remaining = Math.max(0, allocatedAmount - actualCommitted);
            
            monthlyProgressFill.style.width = `${Math.min(progressPercentage, 100)}%`;
            monthlyProgressText.textContent = `${formatCurrency(actualCommitted)} committed • ${formatCurrency(remaining)} available`;
            
            // Update progress bar color based on usage
            if (progressPercentage > 90) {
                monthlyProgressFill.style.backgroundColor = '#ef4444'; // Red
            } else if (progressPercentage > 75) {
                monthlyProgressFill.style.backgroundColor = '#f59e0b'; // Orange
            } else {
                monthlyProgressFill.style.backgroundColor = '#10b981'; // Green
            }
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
        
        // Debug: Log all form data
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: "${value}" (type: ${typeof value}, length: ${value.length})`);
        }
        
        // Special focus on goal_type
        const goalType = formData.get('goal_type');
        const goalTypeSelect = document.getElementById('goalType');
        
        
        if (goalTypeSelect) {
            for (let i = 0; i < goalTypeSelect.options.length; i++) {
                const option = goalTypeSelect.options[i];
                console.log(`    ${i}: value="${option.value}", text="${option.text}"`);
            }
        }
        
        if (goalId) {
            formData.append('action', 'update_goal');
        } else {
            formData.append('action', 'test_create_goal');
        }

        try {
            // Use different endpoints for create vs update
            const endpoint = goalId ? '/budget/actions/savings_handler.php' : '/budget/test_new_goal_creation.php';
            const response = await fetch(endpoint, {
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
        const goalId = formData.get('goal_id');
        const amount = parseFloat(formData.get('amount'));
        
        // Client-side validation
        if (!goalId) {
            this.showSnackbar('Please select a goal', 'error');
            return;
        }
        
        if (!amount || amount <= 0) {
            this.showSnackbar('Please enter a valid amount', 'error');
            return;
        }
        
        // Find the selected goal and check if amount exceeds remaining
        const goal = this.currentGoals.find(g => g.id == goalId);
        if (goal) {
            const remaining = goal.target_amount - goal.current_amount;
            if (amount > remaining) {
                this.showSnackbar(`Amount exceeds remaining goal target. Maximum you can save is ₵${remaining.toFixed(2)}`, 'error');
                return;
            }
        }
        
        formData.append('action', 'add_contribution');

        try {
            const response = await fetch('/budget/actions/savings_handler.php', {
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
                
                // Show completion message if goal was completed
                if (data.is_completed) {
                    this.showSnackbar(`🎉 Congratulations! You've completed your "${data.goal_name}" goal!`, 'success');
                } else {
                    this.showSnackbar('Contribution added successfully!', 'success');
                }
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
            const response = await fetch('/budget/actions/savings_handler.php', {
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

// Initialize when DOM is loaded (simplified - main init handled above)
document.addEventListener('DOMContentLoaded', function() {
    // Add visibility change listener for auto-refresh
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && window.savingsManager) {
            window.savingsManager.loadSavingsData();
        }
    });
});

// Function to load goal types from database
async function loadGoalTypes() {
    try {
        const response = await fetch('../api/goal_types.php');
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get the raw text first to see what we're actually receiving
        const text = await response.text();
        
        // Try to parse as JSON
        const data = JSON.parse(text);
        
        if (data.success && data.goal_types) {
            const goalTypeSelect = document.getElementById('goalType');
            
            // Clear existing options except the first one (Choose type)
            while (goalTypeSelect.children.length > 1) {
                goalTypeSelect.removeChild(goalTypeSelect.lastChild);
            }
            
            // Add goal type options
            data.goal_types.forEach(goalType => {
                const option = document.createElement('option');
                option.value = goalType.value;
                option.textContent = goalType.display;
                goalTypeSelect.appendChild(option);
            });
            
            
            // Debug: List all loaded goal types
            data.goal_types.forEach((goalType, index) => {
                console.log(`  ${index}: value="${goalType.value}", display="${goalType.display}"`);
            });
        } else {
            console.error('Failed to load goal types:', data.message);
            // Show error but don't populate with hardcoded values
            const goalTypeSelect = document.getElementById('goalType');
            if (goalTypeSelect) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Error loading goal types - please refresh';
                goalTypeSelect.appendChild(option);
            }
        }
    } catch (error) {
        console.error('Error loading goal types:', error);
        // Show error but don't populate with hardcoded values
        const goalTypeSelect = document.getElementById('goalType');
        if (goalTypeSelect) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Network error - please refresh';
            goalTypeSelect.appendChild(option);
        }
    }
}

// Function to populate goal dropdowns with user's existing goals
function populateGoalDropdowns() {
    if (!window.savingsManager || !window.savingsManager.currentGoals) {
        return;
    }
    
    const goals = window.savingsManager.currentGoals;
    
    // Populate round-up goal dropdown
    const roundUpGoal = document.getElementById('roundUpGoal');
    if (roundUpGoal) {
        // Clear existing options except first one
        while (roundUpGoal.children.length > 1) {
            roundUpGoal.removeChild(roundUpGoal.lastChild);
        }
        
        goals.forEach(goal => {
            if (goal.status === 'active') {
                const option = document.createElement('option');
                option.value = goal.id;
                option.textContent = `${window.savingsManager.getGoalIcon(goal.goal_type)} ${goal.goal_name}`;
                roundUpGoal.appendChild(option);
            }
        });
    }
    
    // Populate weekly goal dropdown
    const weeklyGoal = document.getElementById('weeklyGoal');
    if (weeklyGoal) {
        // Clear existing options except first one
        while (weeklyGoal.children.length > 1) {
            weeklyGoal.removeChild(weeklyGoal.lastChild);
        }
        
        goals.forEach(goal => {
            if (goal.status === 'active') {
                const option = document.createElement('option');
                option.value = goal.id;
                option.textContent = `${window.savingsManager.getGoalIcon(goal.goal_type)} ${goal.goal_name}`;
                weeklyGoal.appendChild(option);
            }
        });
    }
    
    // Populate goal distribution section
    const goalDistribution = document.getElementById('goalDistribution');
    if (goalDistribution) {
        if (goals.length === 0) {
            goalDistribution.innerHTML = `
                <div class="distribution-placeholder">
                    <p>Create some goals first to see distribution options</p>
                </div>
            `;
        } else {
            goalDistribution.innerHTML = goals
                .filter(goal => goal.status === 'active')
                .map(goal => `
                    <div class="distribution-item">
                        <span>${window.savingsManager.getGoalIcon(goal.goal_type)} ${goal.goal_name}</span>
                        <input type="number" value="0" min="0" step="50" data-goal-id="${goal.id}">
                    </div>
                `).join('');
        }
    }
}

// Utility function for consistent currency formatting
function formatCurrency(amount) {
    // Handle null, undefined, NaN, or invalid values
    if (amount === null || amount === undefined || isNaN(amount) || amount === '') {
        return '₵0.00';
    }
    
    // Convert to number and ensure it's valid
    let numAmount = parseFloat(amount);
    if (isNaN(numAmount)) {
        return '₵0.00';
    }
    
    // Round to 2 decimal places to avoid floating point precision issues
    numAmount = Math.round(numAmount * 100) / 100;
    
    return `₵${numAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
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

// Comprehensive Auto-Save Functions
function showTab(tabId) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}

function loadComprehensiveAutoSaveConfig() {
    fetch('/budget/api/comprehensive_autosave.php?action=get_config')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const config = data.config;
                
                // Load global settings
                document.getElementById('globalAutoSaveEnabled').checked = config.global_enabled || false;
                document.getElementById('defaultSaveAmount').value = config.default_amount || '';
                document.getElementById('defaultSavePercentage').value = config.default_percentage || '';
                
                // Load triggers
                document.getElementById('triggerSalary').checked = config.triggers?.includes('salary') || false;
                document.getElementById('triggerAdditional').checked = config.triggers?.includes('additional') || false;
                document.getElementById('triggerScheduled').checked = config.triggers?.includes('scheduled') || false;
                document.getElementById('triggerExpense').checked = config.triggers?.includes('expense') || false;
                
                // Load allocation method
                if (config.allocation_method) {
                    document.querySelector(`input[name="allocationMethod"][value="${config.allocation_method}"]`).checked = true;
                }
                
                // Load conditions
                document.getElementById('minSaveAmount').value = config.min_amount || '';
                document.getElementById('maxSaveAmount').value = config.max_amount || '';
                document.getElementById('scheduleType').value = config.schedule_type || '';
                document.getElementById('preferredTime').value = config.preferred_time || '09:00';
                
                document.getElementById('checkAccountBalance').checked = config.check_balance || false;
                document.getElementById('checkMonthlyBudget').checked = config.check_budget || false;
                document.getElementById('pauseOnOverspend').checked = config.pause_on_overspend || false;
                
                loadGoalRules();
                loadAutoSaveStats();
            }
        })
        .catch(error => {
            console.error('Error loading comprehensive auto-save config:', error);
            showSnackbar('Error loading auto-save configuration', 'error');
        });
}

function loadGoalRules() {
    fetch('/budget/api/comprehensive_autosave.php?action=get_goal_rules')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('goalRulesList');
                container.innerHTML = '';
                
                data.rules.forEach(rule => {
                    const ruleElement = createGoalRuleElement(rule);
                    container.appendChild(ruleElement);
                });
            }
        })
        .catch(error => {
            console.error('Error loading goal rules:', error);
        });
}

function createGoalRuleElement(rule) {
    const div = document.createElement('div');
    div.className = 'goal-rule-item';
    div.innerHTML = `
        <div class="rule-header">
            <h6>${rule.goal_name}</h6>
            <button type="button" class="btn-delete" onclick="deleteGoalRule(${rule.id})">×</button>
        </div>
        <div class="rule-settings">
            <div class="form-group">
                <label>Save Amount/Percentage</label>
                <div class="input-group">
                    <input type="number" value="${rule.amount || ''}" placeholder="Amount" step="0.01">
                    <span>or</span>
                    <input type="number" value="${rule.percentage || ''}" placeholder="%" min="1" max="100">
                    <span>%</span>
                </div>
            </div>
            <div class="form-group">
                <label>Priority Level</label>
                <select>
                    <option value="1" ${rule.priority === 1 ? 'selected' : ''}>High</option>
                    <option value="2" ${rule.priority === 2 ? 'selected' : ''}>Medium</option>
                    <option value="3" ${rule.priority === 3 ? 'selected' : ''}>Low</option>
                </select>
            </div>
        </div>
    `;
    return div;
}

function addGoalRule() {
    Swal.fire({
        title: 'Add Goal Auto-Save Rule',
        html: `
            <div class="swal-form">
                <select id="swal-goal" class="swal2-select">
                    <option value="">Choose a goal...</option>
                </select>
                <input type="number" id="swal-rule-amount" class="swal2-input" placeholder="Fixed amount (₵)" step="0.01">
                <input type="number" id="swal-rule-percentage" class="swal2-input" placeholder="Percentage (%)" min="1" max="100">
                <select id="swal-priority" class="swal2-select">
                    <option value="1">High Priority</option>
                    <option value="2">Medium Priority</option>
                    <option value="3">Low Priority</option>
                </select>
            </div>
        `,
        confirmButtonText: 'Add Rule',
        showCancelButton: true,
        didOpen: () => {
            // Load goals for dropdown
            fetch('/budget/api/comprehensive_autosave.php?action=get_goals')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('swal-goal');
                        data.goals.forEach(goal => {
                            const option = document.createElement('option');
                            option.value = goal.id;
                            option.textContent = goal.goal_name;
                            select.appendChild(option);
                        });
                    }
                });
        },
        preConfirm: () => {
            const goalId = document.getElementById('swal-goal').value;
            const amount = document.getElementById('swal-rule-amount').value;
            const percentage = document.getElementById('swal-rule-percentage').value;
            const priority = document.getElementById('swal-priority').value;
            
            if (!goalId) {
                Swal.showValidationMessage('Please select a goal');
                return false;
            }
            
            if (!amount && !percentage) {
                Swal.showValidationMessage('Please enter either an amount or percentage');
                return false;
            }
            
            return { goalId, amount, percentage, priority };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'add_goal_rule');
            formData.append('goal_id', result.value.goalId);
            formData.append('amount', result.value.amount);
            formData.append('percentage', result.value.percentage);
            formData.append('priority', result.value.priority);
            
            fetch('/budget/api/comprehensive_autosave.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSnackbar('Goal rule added successfully', 'success');
                    loadGoalRules();
                } else {
                    showSnackbar(data.message || 'Failed to add goal rule', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding goal rule:', error);
                showSnackbar('Error adding goal rule', 'error');
            });
        }
    });
}

function deleteGoalRule(ruleId) {
    Swal.fire({
        title: 'Delete Rule?',
        text: 'Are you sure you want to delete this auto-save rule?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete_goal_rule');
            formData.append('rule_id', ruleId);
            
            fetch('/budget/api/comprehensive_autosave.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSnackbar('Rule deleted successfully', 'success');
                    loadGoalRules();
                } else {
                    showSnackbar(data.message || 'Failed to delete rule', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting rule:', error);
                showSnackbar('Error deleting rule', 'error');
            });
        }
    });
}

function loadAutoSaveStats() {
    fetch('/budget/api/comprehensive_autosave.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalAutoSaved').textContent = '₵' + (data.stats.total_saved || '0.00');
                document.getElementById('totalExecutions').textContent = data.stats.total_executions || '0';
                document.getElementById('avgSaveAmount').textContent = '₵' + (data.stats.avg_amount || '0.00');
                
                loadAutoSaveHistory();
            }
        })
        .catch(error => {
            console.error('Error loading auto-save stats:', error);
        });
}

function loadAutoSaveHistory() {
    fetch('/budget/api/comprehensive_autosave.php?action=get_history')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('autoSaveHistory');
                container.innerHTML = '';
                
                if (data.history.length === 0) {
                    container.innerHTML = '<p class="no-data">No auto-save history found.</p>';
                    return;
                }
                
                data.history.forEach(item => {
                    const historyElement = document.createElement('div');
                    historyElement.className = 'history-item';
                    historyElement.innerHTML = `
                        <div class="history-date">${new Date(item.executed_at).toLocaleDateString()}</div>
                        <div class="history-description">
                            <strong>₵${item.total_amount}</strong> saved to ${item.goals_count} goal(s)
                            <small>Triggered by: ${item.trigger_type}</small>
                        </div>
                        <div class="history-status ${item.status}">${item.status}</div>
                    `;
                    container.appendChild(historyElement);
                });
            }
        })
        .catch(error => {
            console.error('Error loading auto-save history:', error);
        });
}

function saveComprehensiveAutoSaveConfig() {
    const config = {
        global_enabled: document.getElementById('globalAutoSaveEnabled').checked,
        default_amount: document.getElementById('defaultSaveAmount').value,
        default_percentage: document.getElementById('defaultSavePercentage').value,
        
        triggers: [],
        allocation_method: document.querySelector('input[name="allocationMethod"]:checked').value,
        
        min_amount: document.getElementById('minSaveAmount').value,
        max_amount: document.getElementById('maxSaveAmount').value,
        schedule_type: document.getElementById('scheduleType').value,
        preferred_time: document.getElementById('preferredTime').value,
        
        check_balance: document.getElementById('checkAccountBalance').checked,
        check_budget: document.getElementById('checkMonthlyBudget').checked,
        pause_on_overspend: document.getElementById('pauseOnOverspend').checked
    };
    
    // Collect triggers
    if (document.getElementById('triggerSalary').checked) config.triggers.push('salary');
    if (document.getElementById('triggerAdditional').checked) config.triggers.push('additional');
    if (document.getElementById('triggerScheduled').checked) config.triggers.push('scheduled');
    if (document.getElementById('triggerExpense').checked) config.triggers.push('expense');
    
    const formData = new FormData();
    formData.append('action', 'save_config');
    formData.append('config', JSON.stringify(config));
    
    // Show loading screen during save
    if (window.budgetlyLoader) {
        window.budgetlyLoader.show();
    }
    
    fetch('/budget/api/comprehensive_autosave.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading screen
        if (window.budgetlyLoader) {
            window.budgetlyLoader.hide();
        }
        
        if (data.success) {
            showSnackbar('Auto-save configuration saved successfully!', 'success');
            closeModal('comprehensiveAutoSaveModal');
            loadAutoSaveOverview(); // Refresh the overview
        } else {
            showSnackbar(data.message || 'Failed to save configuration', 'error');
        }
    })
    .catch(error => {
        // Hide loading screen on error
        if (window.budgetlyLoader) {
            window.budgetlyLoader.hide();
        }
        console.error('Error saving configuration:', error);
        showSnackbar('Error saving configuration', 'error');
    });
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
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-times-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
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

// Test function for loading screen (can be called from browser console)
window.testLoadingScreen = function(duration = 3000) {
    if (window.budgetlyLoader) {
        console.log('Testing loading screen for', duration, 'ms');
        window.budgetlyLoader.show();
        setTimeout(() => {
            window.budgetlyLoader.hide();
            console.log('Loading screen test complete');
        }, duration);
    } else {
        console.log('Loading screen not available');
    }
};

    </script>
    <script src="../public/js/loading.js"></script>
    <script src="../public/js/mobile-nav.js"></script>
</body>
</html>