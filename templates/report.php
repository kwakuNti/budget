<?php
session_start();

require_once '../config/connection.php';
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['user_type'] !== 'personal') {
    // Redirect family users to family dashboard
    header('Location: ../index.php');
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
    <title>Financial Report - Comprehensive Analysis</title>
    <link rel="stylesheet" href="../public/css/report.css">
    <link rel="stylesheet" href="../public/css/export-utility.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="../public/js/export-utility.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <span class="brand-icon"><i class="fas fa-chart-bar"></i></span>
                <span class="brand-text">Financial Report</span>
            </div>
            <div class="nav-actions">
                <button class="nav-btn" id="themeToggle"><i class="fas fa-palette"></i></button>
                <button class="nav-btn" id="exportReport"><i class="fas fa-download"></i> Export</button>
                <button class="nav-btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                <a href="personal-dashboard.php" class="nav-btn">← Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="report-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Financial Report</h1>
                    <p>Comprehensive analysis for <strong><?php echo htmlspecialchars($user_first_name); ?></strong></p>
                    <div class="report-period">
                        <span id="reportPeriod">Loading report period...</span>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="stat-value" data-metric="health-score">0</span>
                        <span class="stat-label">Health Score</span>
                        <div class="stat-indicator excellent"></div>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-value" data-metric="total-insights">0</span>
                        <span class="stat-label">Total Insights</span>
                        <div class="stat-indicator good"></div>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-value" data-metric="categories-analyzed">0</span>
                        <span class="stat-label">Categories</span>
                        <div class="stat-indicator good"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Executive Summary -->
        <section class="executive-summary">
            <h2><i class="fas fa-clipboard-list"></i> Executive Summary</h2>
            <div class="summary-grid">
                <div class="summary-card primary">
                    <div class="summary-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="summary-content">
                        <h3>Total Income</h3>
                        <div class="summary-value" data-metric="total-income">₵0</div>
                        <div class="summary-change positive">+0%</div>
                    </div>
                </div>
                <div class="summary-card secondary">
                    <div class="summary-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="summary-content">
                        <h3>Total Expenses</h3>
                        <div class="summary-value" data-metric="total-expenses">₵0</div>
                        <div class="summary-change negative">+0%</div>
                    </div>
                </div>
                <div class="summary-card success">
                    <div class="summary-icon"><i class="fas fa-bullseye"></i></div>
                    <div class="summary-content">
                        <h3>Net Savings</h3>
                        <div class="summary-value" data-metric="net-savings">₵0</div>
                        <div class="summary-change positive">+0%</div>
                    </div>
                </div>
                <div class="summary-card accent">
                    <div class="summary-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="summary-content">
                        <h3>Savings Rate</h3>
                        <div class="summary-value" data-metric="savings-rate">0%</div>
                        <div class="summary-change positive">+0%</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Financial Health Overview -->
        <section class="financial-health-section">
            <h2>🏥 Financial Health Analysis</h2>
            <div class="health-grid">
                <div class="health-score-card">
                    <div class="score-display">
                        <div class="score-circle">
                            <svg class="score-svg" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" class="score-bg"></circle>
                                <circle cx="60" cy="60" r="50" class="score-progress" id="healthScoreCircle"></circle>
                            </svg>
                            <div class="score-text">
                                <span class="score-number" id="healthScoreNumber">0</span>
                                <span class="score-label">Health Score</span>
                            </div>
                        </div>
                    </div>
                    <div class="score-breakdown">
                        <div class="breakdown-item">
                            <span class="breakdown-label">Emergency Fund</span>
                            <div class="breakdown-bar">
                                <div class="breakdown-fill" data-score="emergency"></div>
                            </div>
                            <span class="breakdown-value" data-value="emergency">0%</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Expense Management</span>
                            <div class="breakdown-bar">
                                <div class="breakdown-fill" data-score="expenses"></div>
                            </div>
                            <span class="breakdown-value" data-value="expenses">0%</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Savings Rate</span>
                            <div class="breakdown-bar">
                                <div class="breakdown-fill" data-score="savings"></div>
                            </div>
                            <span class="breakdown-value" data-value="savings">0%</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Goal Progress</span>
                            <div class="breakdown-bar">
                                <div class="breakdown-fill" data-score="goals"></div>
                            </div>
                            <span class="breakdown-value" data-value="goals">0%</span>
                        </div>
                    </div>
                </div>
                <div class="health-recommendations">
                    <h3><i class="fas fa-lightbulb"></i> Key Recommendations</h3>
                    <div class="recommendations-list" id="healthRecommendations">
                        <div class="recommendation-item loading">
                            <div class="rec-icon"><i class="fas fa-sync-alt"></i></div>
                            <div class="rec-content">
                                <div class="rec-title">Loading recommendations...</div>
                                <div class="rec-description">Analyzing your financial data</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Income vs Expenses Analysis -->
        <section class="income-expense-section">
            <h2>💹 Income vs Expenses Analysis</h2>
            <div class="analysis-grid">
                <div class="chart-container large">
                    <h3>Monthly Trend Analysis</h3>
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
                <div class="metrics-container">
                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="metric-icon"><i class="fas fa-chart-line"></i></span>
                            <span class="metric-title">Income Trend</span>
                        </div>
                        <div class="metric-value positive" data-metric="income-trend">+0%</div>
                        <div class="metric-description">vs last month</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="metric-icon">📉</span>
                            <span class="metric-title">Expense Trend</span>
                        </div>
                        <div class="metric-value negative" data-metric="expense-trend">+0%</div>
                        <div class="metric-description">vs last month</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="metric-icon"><i class="fas fa-dollar-sign"></i></span>
                            <span class="metric-title">Net Flow</span>
                        </div>
                        <div class="metric-value" data-metric="net-flow">₵0</div>
                        <div class="metric-description">monthly average</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Budget Breakdown -->
        <section class="budget-breakdown-section">
            <h2><i class="fas fa-bullseye"></i> Budget Breakdown & Performance</h2>
            <div class="breakdown-grid">
                <div class="budget-overview">
                    <h3 id="budgetRuleTitle">Budget Rule Analysis</h3>
                    <div class="budget-circle-container">
                        <canvas id="budgetBreakdownChart"></canvas>
                    </div>
                    <div class="budget-legend">
                        <div class="legend-item needs">
                            <span class="legend-color"></span>
                            <span class="legend-label" id="needsLabel">Needs</span>
                            <span class="legend-value" data-value="needs-percentage">0%</span>
                        </div>
                        <div class="legend-item wants">
                            <span class="legend-color"></span>
                            <span class="legend-label" id="wantsLabel">Wants</span>
                            <span class="legend-value" data-value="wants-percentage">0%</span>
                        </div>
                        <div class="legend-item savings">
                            <span class="legend-color"></span>
                            <span class="legend-label" id="savingsLabel">Savings</span>
                            <span class="legend-value" data-value="savings-percentage">0%</span>
                        </div>
                    </div>
                </div>
                <div class="budget-performance">
                    <h3>Category Performance</h3>
                    <div class="performance-list" id="categoryPerformance">
                        <div class="performance-item loading">
                            <div class="perf-category">Loading categories...</div>
                            <div class="perf-bar">
                                <div class="perf-fill"></div>
                            </div>
                            <div class="perf-values">
                                <span class="perf-spent">₵0</span>
                                <span class="perf-budget">/ ₵0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Spending Analysis -->
        <section class="spending-analysis-section">
            <h2>🛒 Detailed Spending Analysis</h2>
            <div class="spending-grid">
                <div class="spending-chart-container">
                    <h3>Category Distribution</h3>
                    <canvas id="spendingDistributionChart"></canvas>
                </div>
                <div class="spending-trends-container">
                    <h3>Weekly Spending Patterns</h3>
                    <canvas id="weeklySpendingChart"></canvas>
                </div>
                <div class="spending-insights">
                    <h3>🧠 Spending Insights</h3>
                    <div class="insights-list" id="spendingInsights">
                        <div class="insight-item">
                            <div class="insight-icon">📊</div>
                            <div class="insight-content">
                                <div class="insight-title">Analyzing spending patterns...</div>
                                <div class="insight-description">Loading insights</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Goals & Savings -->
        <section class="goals-savings-section">
            <h2>🎯 Goals & Savings Analysis</h2>
            <div class="goals-grid">
                <div class="goals-overview">
                    <h3>Goal Progress Overview</h3>
                    <canvas id="goalsProgressChart"></canvas>
                </div>
                <div class="savings-analysis">
                    <h3>Savings Growth</h3>
                    <canvas id="savingsGrowthChart"></canvas>
                </div>
                <div class="goals-details">
                    <h3>📋 Goal Details</h3>
                    <div class="goals-list" id="goalsList">
                        <div class="goal-item loading">
                            <div class="goal-header">
                                <span class="goal-name">Loading goals...</span>
                                <span class="goal-progress">0%</span>
                            </div>
                            <div class="goal-bar">
                                <div class="goal-fill"></div>
                            </div>
                            <div class="goal-details-text">
                                <span class="goal-current">₵0</span>
                                <span class="goal-target">/ ₵0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Predictive Analysis -->
        <section class="predictive-analysis-section">
            <h2>🔮 Predictive Analysis & Forecasts</h2>
            <div class="predictions-grid">
                <div class="forecast-chart-container">
                    <h3>6-Month Financial Forecast</h3>
                    <canvas id="forecastChart"></canvas>
                </div>
                <div class="predictions-panel">
                    <h3>🤖 AI Predictions</h3>
                    <div class="predictions-list" id="aiPredictions">
                        <div class="prediction-item">
                            <div class="pred-icon">🔄</div>
                            <div class="pred-content">
                                <div class="pred-title">Generating predictions...</div>
                                <div class="pred-confidence">Loading AI insights</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scenarios-panel">
                    <h3>📊 What-If Scenarios</h3>
                    <div class="scenarios-list">
                        <div class="scenario-item">
                            <div class="scenario-title">10% Expense Reduction</div>
                            <div class="scenario-result positive" data-scenario="reduce-expenses">+₵0/month</div>
                        </div>
                        <div class="scenario-item">
                            <div class="scenario-title">5% Income Increase</div>
                            <div class="scenario-result positive" data-scenario="increase-income">+₵0/month</div>
                        </div>
                        <div class="scenario-item">
                            <div class="scenario-title">Emergency Fund Goal</div>
                            <div class="scenario-result neutral" data-scenario="emergency-fund">0 months</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benchmarking -->
        <section class="benchmarking-section">
            <h2>📊 Benchmarking & Comparisons</h2>
            <div class="benchmark-grid">
                <div class="benchmark-chart-container">
                    <h3>Performance vs Averages</h3>
                    <canvas id="benchmarkChart"></canvas>
                </div>
                <div class="benchmark-metrics">
                    <div class="benchmark-item">
                        <div class="benchmark-header">
                            <span class="benchmark-label">Savings Rate</span>
                            <span class="benchmark-status excellent">Excellent</span>
                        </div>
                        <div class="benchmark-comparison">
                            <div class="comparison-bar">
                                <div class="your-performance" data-performance="savings-rate"></div>
                                <div class="average-line" data-average="savings-rate"></div>
                            </div>
                            <div class="comparison-labels">
                                <span class="your-label">You: <span data-your="savings-rate">0%</span></span>
                                <span class="avg-label">Avg: <span data-avg="savings-rate">15%</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="benchmark-item">
                        <div class="benchmark-header">
                            <span class="benchmark-label">Emergency Fund</span>
                            <span class="benchmark-status good">Good</span>
                        </div>
                        <div class="benchmark-comparison">
                            <div class="comparison-bar">
                                <div class="your-performance" data-performance="emergency-fund"></div>
                                <div class="average-line" data-average="emergency-fund"></div>
                            </div>
                            <div class="comparison-labels">
                                <span class="your-label">You: <span data-your="emergency-fund">0 months</span></span>
                                <span class="avg-label">Avg: <span data-avg="emergency-fund">3 months</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="benchmark-item">
                        <div class="benchmark-header">
                            <span class="benchmark-label">Expense Ratio</span>
                            <span class="benchmark-status warning">Needs Work</span>
                        </div>
                        <div class="benchmark-comparison">
                            <div class="comparison-bar">
                                <div class="your-performance" data-performance="expense-ratio"></div>
                                <div class="average-line" data-average="expense-ratio"></div>
                            </div>
                            <div class="comparison-labels">
                                <span class="your-label">You: <span data-your="expense-ratio">0%</span></span>
                                <span class="avg-label">Avg: <span data-avg="expense-ratio">70%</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Action Items -->
        <section class="action-items-section">
            <h2>🚀 Recommended Actions</h2>
            <div class="actions-grid">
                <div class="priority-actions">
                    <h3>🔥 High Priority</h3>
                    <div class="actions-list high-priority" id="highPriorityActions">
                        <div class="action-item">
                            <div class="action-checkbox">
                                <input type="checkbox" id="action1">
                                <label for="action1"></label>
                            </div>
                            <div class="action-content">
                                <div class="action-title">Loading priority actions...</div>
                                <div class="action-description">Analyzing your financial data</div>
                                <div class="action-impact">Impact: Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="medium-actions">
                    <h3>⚡ Medium Priority</h3>
                    <div class="actions-list medium-priority" id="mediumPriorityActions">
                        <div class="action-item">
                            <div class="action-checkbox">
                                <input type="checkbox" id="action2">
                                <label for="action2"></label>
                            </div>
                            <div class="action-content">
                                <div class="action-title">Loading medium actions...</div>
                                <div class="action-description">Optimizing your budget</div>
                                <div class="action-impact">Impact: Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="optimization-actions">
                    <h3>🎯 Optimization</h3>
                    <div class="actions-list optimization" id="optimizationActions">
                        <div class="action-item">
                            <div class="action-checkbox">
                                <input type="checkbox" id="action3">
                                <label for="action3"></label>
                            </div>
                            <div class="action-content">
                                <div class="action-title">Loading optimization tips...</div>
                                <div class="action-description">Enhancing your financial strategy</div>
                                <div class="action-impact">Impact: Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <div class="loading-text">Generating your comprehensive financial report...</div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../public/js/report.js"></script>
</body>
</html>
