<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/expense_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    header("Location: login");
    exit;
}

$family_id = $_SESSION['family_id'];

// Get expense statistics
$expenseStats = getExpenseStats($conn, $family_id);

// Get recent expenses
$recentExpenses = getAllExpenses($conn, $family_id, 10);

// Get chart data
$expenseTrendsData = getExpenseTrendsData($conn, $family_id, '6m');
$categoryData = getCategoryBreakdownData($conn, $family_id, 'current');

// Get quick add suggestions
$quickSuggestions = getQuickAddSuggestions($conn, $family_id, 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family - Expenses</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/expense.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <!-- Snackbar for notifications -->
    <div id="snackbar"></div>

    <!-- Sidebar Toggle Button -->
    <button id="sidebarToggle">☰</button>
    
    <!-- Sidebar (keeping existing structure) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Nkansah</h1>
            <p>Family Fund</p>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="family-dashboard" class="nav-link">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="members" class="nav-link">
                    <span class="nav-icon">👥</span>
                    <span>Members</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="contribution" class="nav-link">
                    <span class="nav-icon">💰</span>
                    <span>Contributions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <span class="nav-icon">💸</span>
                    <span>Expenses</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="momo" class="nav-link">
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
        <!-- Expenses Header -->
        <div class="expenses-header">
            <div class="expenses-title">
                <h2>Family Expenses</h2>
                <p class="expenses-subtitle">Track and manage family expenditures</p>
            </div>
            <div class="expenses-actions">
                <button class="btn btn-secondary" onclick="exportExpensesData()">
                    📤 Export Expenses
                </button>
                <button class="btn btn-primary" onclick="showAddExpenseModal()">
                    ➕ Add Expense
                </button>
            </div>
        </div>

        <!-- Expense Statistics -->
        <div class="expense-stats-grid">
            <div class="expense-stat-card total-expenses">
                <div class="stat-icon expenses">💸</div>
                <div class="stat-value">₵<span id="totalExpenses"><?= number_format(floatval($expenseStats['total_expenses']), 2) ?></span></div>
                <div class="stat-label">Total Expenses (All Time)</div>
                <div class="stat-change negative">↗ <?= abs($expenseStats['total_change']) ?>% from last month</div>
            </div>
            
            <div class="expense-stat-card monthly-expenses">
                <div class="stat-icon monthly">📊</div>
                <div class="stat-value">₵<span id="currentMonthExpenses"><?= number_format($expenseStats['this_month_expenses'], 2) ?></span></div>
                <div class="stat-label">This Month's Expenses</div>
                <div class="stat-change negative">↗ <?= abs($expenseStats['monthly_change']) ?>% from last month</div>
            </div>
            
            <div class="expense-stat-card avg-monthly">
                <div class="stat-icon average">📈</div>
                <div class="stat-value">₵<span id="avgMonthlyExpenses"><?= number_format($expenseStats['average_monthly'], 2) ?></span></div>
                <div class="stat-label">Monthly Average</div>
                <div class="stat-change positive">↘ <?= abs($expenseStats['average_change']) ?>% from last month</div>
            </div>
            
            <div class="expense-stat-card largest-category">
                <div class="stat-icon category">🏠</div>
                <div class="stat-value"><span id="largestCategory"><?= $expenseStats['top_category'] ?></span></div>
                <div class="stat-label">Top Category This Month</div>
                <div class="stat-change neutral">₵<?= number_format($expenseStats['top_category_amount'], 2) ?> (<?= $expenseStats['top_category_percent'] ?>% of total)</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="expense-analytics-grid">
            <!-- Expense Trends Chart -->
            <div class="chart-card expense-trends">
                <div class="chart-header">
                    <h3 class="chart-title">Expense Trends</h3>
                    <div class="chart-controls">
                        <button class="chart-control active" data-period="6m">6M</button>
                        <button class="chart-control" data-period="1y">1Y</button>
                        <button class="chart-control" data-period="all">All</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="expenseTrendsChart"></canvas>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="chart-card category-breakdown">
                <div class="chart-header">
                    <h3 class="chart-title">Category Breakdown</h3>
                    <div class="period-selector">
                        <select id="categoryPeriod">
                            <option value="current">This Month</option>
                            <option value="last">Last Month</option>
                            <option value="quarter">This Quarter</option>
                        </select>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Dynamic Quick Add Expenses -->
        <div class="quick-expense-section">
            <div class="section-header">
                <h3>Quick Add Expenses</h3>
                <p>Based on your recent spending patterns</p>
            </div>
            <div class="quick-expense-grid" id="quickExpenseGrid">
                <?php foreach ($quickSuggestions as $suggestion): ?>
                    <div class="quick-expense-card" onclick="quickAddExpense('<?= htmlspecialchars($suggestion['type']) ?>', <?= $suggestion['amount'] ?>)">
                        <div class="quick-expense-icon"><?= htmlspecialchars($suggestion['icon']) ?></div>
                        <div class="quick-expense-name"><?= htmlspecialchars($suggestion['name']) ?></div>
                        <div class="quick-expense-amount">
                            <?= $suggestion['amount'] > 0 ? '₵' . $suggestion['amount'] : 'Custom' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="recent-expenses-section">
            <div class="section-header">
                <h3>Recent Expenses</h3>
                <div class="section-actions">
                    <button class="btn btn-outline" onclick="showExpenseFilters()">
                        🔍 Filter
                    </button>
                    <button class="btn btn-outline" onclick="showAllExpenses()">
                        📋 View All
                    </button>
                </div>
            </div>
            
            <div class="expenses-table-container">
                <table class="expenses-table" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTableBody">
                        <?php if (empty($recentExpenses)): ?>
                            <tr>
                                <td colspan="6" class="no-data">
                                    <div style="text-align: center; padding: 40px; color: #64748b;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
                                        <h3>No expenses recorded yet</h3>
                                        <p>Start tracking your family expenses by adding your first expense above.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentExpenses as $expense): ?>
                                <?php
                                $expenseDate = new DateTime($expense['expense_date']);
                                $today = new DateTime();
                                $diffDays = $today->diff($expenseDate)->days;
                                
                                if ($diffDays == 0) {
                                    $dateDisplay = 'Today';
                                } elseif ($diffDays == 1) {
                                    $dateDisplay = 'Yesterday';
                                } elseif ($diffDays <= 7) {
                                    $dateDisplay = $diffDays . ' days ago';
                                } else {
                                    $dateDisplay = $expenseDate->format('M j');
                                }
                                
                                // Map expense types to display info
                                $categoryMap = [
                                    'dstv' => ['icon' => '📺', 'name' => 'DSTV', 'class' => 'entertainment'],
                                    'wifi' => ['icon' => '📶', 'name' => 'WiFi', 'class' => 'internet'],
                                    'utilities' => ['icon' => '⚡', 'name' => 'Utilities', 'class' => 'utilities'],
                                    'dining' => ['icon' => '🍽️', 'name' => 'Dining', 'class' => 'dining'],
                                    'maintenance' => ['icon' => '🔧', 'name' => 'Maintenance', 'class' => 'maintenance'],
                                    'other' => ['icon' => '📦', 'name' => 'Other', 'class' => 'other']
                                ];
                                
                                $categoryInfo = $categoryMap[$expense['category']] ?? $categoryMap['other'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="expense-date">
                                            <span class="date-day"><?= htmlspecialchars($dateDisplay) ?></span>
                                            <span class="date-full"><?= $expenseDate->format('M j, Y') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expense-category <?= $categoryInfo['class'] ?>">
                                            <span class="category-icon"><?= $categoryInfo['icon'] ?></span>
                                            <span><?= htmlspecialchars($categoryInfo['name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($expense['description']) ?></td>
                                    <td class="expense-amount">₵<?= number_format($expense['amount'], 2) ?></td>
                                    <td>
                                        <div class="added-by">
                                            <div class="user-avatar"><?= strtoupper(substr($expense['added_by'], 0, 1)) ?></div>
                                            <span><?= htmlspecialchars($expense['added_by']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn edit" onclick="editExpense(<?= $expense['id'] ?>)">✏️</button>
                                            <button class="action-btn delete" onclick="deleteExpense(<?= $expense['id'] ?>)">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Expense</h3>
                <span class="close" onclick="closeAddExpenseModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addExpenseForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select id="expenseCategory" required>
                                <option value="">Select Category</option>
                                <option value="utilities">Utilities</option>
                                <option value="dstv">DSTV</option>
                                <option value="wifi">WiFi/Internet</option>
                                <option value="dining">Dining</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount (₵)</label>
                            <input type="number" id="expenseAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" id="expenseDescription" placeholder="e.g., Monthly electricity bill">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" id="expenseDate" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select id="paymentMethod" required>
                                <option value="momo">Mobile Money</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea id="expenseNotes" rows="3" placeholder="Additional notes about this expense..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddExpenseModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Modal -->
    <div id="quickAddModal" class="modal">
        <div class="modal-content quick-add-modal">
            <div class="modal-header">
                <h3>Quick Add Expense</h3>
                <span class="close" onclick="closeQuickAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="quick-add-info">
                    <div class="quick-add-category">
                        <span class="category-icon" id="quickCategoryIcon">📺</span>
                        <span class="category-name" id="quickCategoryName">DSTV</span>
                    </div>
                    <div class="quick-add-amount">
                        <input type="number" id="quickAmount" step="0.01" min="0" placeholder="Enter amount">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="quickDescription" placeholder="e.g., Monthly DSTV subscription">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickAddModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmQuickAdd()">Add Expense</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        // Initialize with real database data
        window.expenseStats = <?= json_encode($expenseStats) ?>;
        window.recentExpenses = <?= json_encode($recentExpenses) ?>;
        window.familyId = <?= json_encode($family_id) ?>;
        window.initialExpenseTrends = <?= json_encode($expenseTrendsData) ?>;
        window.initialCategoryData = <?= json_encode($categoryData) ?>;
        window.quickSuggestions = <?= json_encode($quickSuggestions) ?>;
    </script>
    <script src="../public/js/expense.js"></script>
</body>
</html>