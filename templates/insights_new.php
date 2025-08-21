<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check user type and redirect family users to family dashboard
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
    <title>Financial Insights - Smart Analytics</title>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/insights.css">
    <!-- Chart.js for advanced visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="../index.php" class="nav-brand">
                    <i data-feather="trending-up"></i>
                    <span>Budget Insights</span>
                </a>
            </div>
            <div class="nav-right">
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($user_first_name); ?></span>
                    <a href="../actions/signout.php" class="btn-logout">
                        <i data-feather="log-out"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="insights-hero">
            <div class="hero-content">
                <h1><i data-feather="bar-chart-2"></i> Financial Insights</h1>
                <p>Get deep insights into your financial patterns and make smarter decisions</p>
                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalSpent">₵0</div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="monthlyAvg">₵0</div>
                        <div class="stat-label">Monthly Average</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="healthScoreNumber">85</div>
                        <div class="stat-label">Health Score</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Insights Grid -->
        <div class="insights-container">
            
            <!-- Financial Health Card -->
            <div class="insight-card health-card">
                <div class="card-header">
                    <h3><i data-feather="heart"></i> Financial Health</h3>
                    <button class="refresh-btn" onclick="refreshFinancialHealth()">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="health-display">
                        <div class="health-circle">
                            <svg class="progress-ring" width="140" height="140">
                                <circle cx="70" cy="70" r="60" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                                <circle id="healthProgress" cx="70" cy="70" r="60" stroke="url(#healthGradient)" stroke-width="8" 
                                        fill="none" stroke-linecap="round" 
                                        stroke-dasharray="377" stroke-dashoffset="377"
                                        style="transition: stroke-dashoffset 1s ease-in-out"/>
                                <defs>
                                    <linearGradient id="healthGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#10b981"/>
                                        <stop offset="100%" style="stop-color:#3b82f6"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="health-score-center">
                                <div class="score-number" id="healthScoreDisplay">85</div>
                                <div class="score-status" id="healthStatus">Good</div>
                            </div>
                        </div>
                        <div class="health-recommendations">
                            <h4>Recommendations</h4>
                            <div id="healthRecommendations">
                                <div class="recommendation-item">
                                    <i data-feather="check-circle"></i>
                                    <span>Loading recommendations...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spending Patterns Card -->
            <div class="insight-card">
                <div class="card-header">
                    <h3><i data-feather="pie-chart"></i> Spending Patterns</h3>
                    <button class="refresh-btn" onclick="refreshSpendingPatterns()">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="spendingPatternsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Goal Analytics Card -->
            <div class="insight-card">
                <div class="card-header">
                    <h3><i data-feather="target"></i> Goal Progress</h3>
                    <button class="refresh-btn" onclick="refreshGoalAnalytics()">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="goalProgressChart"></canvas>
                    </div>
                    <div class="goal-stats">
                        <div id="goalStats">
                            <div class="metric-row">
                                <span class="metric-label">Active Goals</span>
                                <span class="metric-value">0</span>
                            </div>
                            <div class="metric-row">
                                <span class="metric-label">Completed Goals</span>
                                <span class="metric-value">0</span>
                            </div>
                            <div class="metric-row">
                                <span class="metric-label">Total Progress</span>
                                <span class="metric-value">0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Performance Card -->
            <div class="insight-card">
                <div class="card-header">
                    <h3><i data-feather="bar-chart"></i> Budget Performance</h3>
                    <button class="refresh-btn" onclick="refreshBudgetPerformance()">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="budgetPerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Income Trends Card -->
            <div class="insight-card">
                <div class="card-header">
                    <h3><i data-feather="trending-up"></i> Income Trends</h3>
                    <button class="refresh-btn" onclick="refreshIncomeTrends()">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="incomeTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Predictive Insights Card -->
            <div class="insight-card predictions-card">
                <div class="card-header">
                    <h3><i data-feather="zap"></i> AI Predictions</h3>
                    <button class="refresh-btn" onclick="refreshPredictions()">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div id="predictionsContainer">
                        <div class="prediction-item">
                            <div class="prediction-icon">
                                <i data-feather="calendar"></i>
                            </div>
                            <div class="prediction-content">
                                <h4>Next Month Prediction</h4>
                                <p>Loading predictions...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- AI Chat Assistant -->
    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <div class="chat-title">
                <i data-feather="message-circle"></i>
                <span>AI Financial Assistant</span>
            </div>
            <button class="chat-close" onclick="toggleChat()">
                <i data-feather="x"></i>
            </button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="message bot-message">
                <div class="message-avatar">
                    <i data-feather="cpu"></i>
                </div>
                <div class="message-content">
                    <p>Hi! I'm your AI financial assistant. Ask me anything about your budget, expenses, or financial goals!</p>
                </div>
            </div>
        </div>
        <div class="chat-suggestions">
            <div class="suggestion-chip" onclick="sendPredefinedMessage('How is my spending this month?')">
                Spending Analysis
            </div>
            <div class="suggestion-chip" onclick="sendPredefinedMessage('What are my biggest expenses?')">
                Top Expenses
            </div>
            <div class="suggestion-chip" onclick="sendPredefinedMessage('How can I improve my budget?')">
                Budget Tips
            </div>
        </div>
        <div class="chat-input-container">
            <input type="text" id="chatInput" placeholder="Ask me about your finances..." 
                   onkeypress="handleChatKeyPress(event)">
            <button class="chat-send" onclick="sendChatMessage()">
                <i data-feather="send"></i>
            </button>
        </div>
    </div>

    <!-- Chat Toggle Button -->
    <button class="chat-toggle" id="chatToggle" onclick="toggleChat()">
        <i data-feather="message-circle"></i>
        <span class="chat-badge" id="chatBadge">1</span>
    </button>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading insights...</p>
        </div>
    </div>

    <!-- Snackbar -->
    <div id="snackbar" class="snackbar">
        <span id="snackbar-message"></span>
        <button id="snackbar-close" onclick="hideSnackbar()">
            <i data-feather="x"></i>
        </button>
    </div>

    <script>
        // Global variables for charts
        let chartInstances = {};
        let chatOpen = false;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();
            initializeInsights();
        });

        function initializeInsights() {
            showLoadingOverlay();
            
            // Load all insights with retry mechanism
            Promise.all([
                loadWithRetry(refreshFinancialHealth, 'Financial Health'),
                loadWithRetry(refreshSpendingPatterns, 'Spending Patterns'), 
                loadWithRetry(refreshGoalAnalytics, 'Goal Analytics'),
                loadWithRetry(refreshBudgetPerformance, 'Budget Performance'),
                loadWithRetry(refreshIncomeTrends, 'Income Trends'),
                loadWithRetry(refreshPredictions, 'Predictions')
            ]).then(() => {
                hideLoadingOverlay();
                console.log('All insights loading completed');
            }).catch((error) => {
                hideLoadingOverlay();
                console.error('Error loading insights:', error);
                showSnackbar('Failed to load some insights', 'error');
            });
        }

        async function loadWithRetry(loadFunction, componentName, maxRetries = 3) {
            for (let i = 0; i < maxRetries; i++) {
                try {
                    await loadFunction();
                    console.log(componentName + ' loaded successfully');
                    return;
                } catch (error) {
                    console.warn(componentName + ' failed attempt ' + (i + 1) + ':', error);
                    if (i === maxRetries - 1) {
                        throw new Error(componentName + ' failed after ' + maxRetries + ' attempts');
                    }
                    await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
                }
            }
        }

        // Financial Health Functions
        async function refreshFinancialHealth() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=financial_health');
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                updateHealthScore(data);
                updateHeroStats(data);
            } catch (error) {
                console.error('Error fetching financial health:', error);
                showSnackbar('Failed to load financial health data', 'error');
            }
        }

        function updateHealthScore(data) {
            const heroHealthScore = document.getElementById('healthScoreNumber');
            const healthScoreDisplay = document.getElementById('healthScoreDisplay');
            const healthStatus = document.getElementById('healthStatus');
            const healthProgress = document.getElementById('healthProgress');
            const healthRecommendations = document.getElementById('healthRecommendations');

            const healthScore = data.health_score || 85;

            if (heroHealthScore) {
                heroHealthScore.textContent = healthScore;
            }

            if (healthScoreDisplay) {
                healthScoreDisplay.textContent = healthScore;
            }

            if (healthStatus) {
                let status = 'Excellent';
                if (healthScore < 70) status = 'Needs Improvement';
                else if (healthScore < 85) status = 'Good';
                healthStatus.textContent = status;
            }

            if (healthProgress) {
                const circumference = 2 * Math.PI * 60;
                const offset = circumference - (healthScore / 100) * circumference;
                healthProgress.style.strokeDashoffset = offset;
            }

            if (healthRecommendations && data.recommendations) {
                if (data.recommendations.length > 0) {
                    let recHtml = '';
                    for (let i = 0; i < data.recommendations.length; i++) {
                        recHtml += '<div class="recommendation-item">';
                        recHtml += '<i data-feather="check-circle"></i>';
                        recHtml += '<span>' + data.recommendations[i] + '</span>';
                        recHtml += '</div>';
                    }
                    healthRecommendations.innerHTML = recHtml;
                    feather.replace();
                } else {
                    healthRecommendations.innerHTML = '<div class="recommendation-item"><i data-feather="check-circle"></i><span>Your financial health is looking good!</span></div>';
                    feather.replace();
                }
            }
        }

        function updateHeroStats(data) {
            const totalSpent = document.getElementById('totalSpent');
            const monthlyAvg = document.getElementById('monthlyAvg');

            if (totalSpent && data.total_spent) {
                totalSpent.textContent = '₵' + parseFloat(data.total_spent).toLocaleString();
            }

            if (monthlyAvg && data.monthly_average) {
                monthlyAvg.textContent = '₵' + parseFloat(data.monthly_average).toLocaleString();
            }
        }

        // Spending Patterns Functions
        async function refreshSpendingPatterns() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=spending_patterns');
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                createSpendingPatternsChart(data);
            } catch (error) {
                console.error('Error fetching spending patterns:', error);
                showChartError('spendingPatternsChart', 'Failed to load spending patterns');
                showSnackbar('Failed to load spending patterns', 'error');
            }
        }

        function createSpendingPatternsChart(data) {
            const ctx = document.getElementById('spendingPatternsChart');
            if (!ctx) {
                console.error('Spending patterns chart canvas not found');
                return;
            }
            
            const context = ctx.getContext('2d');
            
            if (chartInstances.spendingPatterns) {
                chartInstances.spendingPatterns.destroy();
            }

            if (!data.daily_patterns || data.daily_patterns.length === 0) {
                showNoDataMessage('spendingPatternsChart', 'No spending data available');
                return;
            }

            chartInstances.spendingPatterns = new Chart(context, {
                type: 'doughnut',
                data: {
                    labels: data.daily_patterns.map(item => item.category),
                    datasets: [{
                        label: 'Spending by Category',
                        data: data.daily_patterns.map(item => parseFloat(item.amount)),
                        backgroundColor: [
                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                            '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Goal Analytics Functions
        async function refreshGoalAnalytics() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=goal_analytics');
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                createGoalProgressChart(data);
                updateGoalStats(data.statistics);
            } catch (error) {
                console.error('Error fetching goal analytics:', error);
                showChartError('goalProgressChart', 'Failed to load goal analytics');
                showSnackbar('Failed to load goal analytics', 'error');
            }
        }

        function createGoalProgressChart(data) {
            const ctx = document.getElementById('goalProgressChart');
            if (!ctx) {
                console.error('Goal progress chart canvas not found');
                return;
            }
            
            const context = ctx.getContext('2d');
            
            if (chartInstances.goalProgress) {
                chartInstances.goalProgress.destroy();
            }

            if (!data.goals || data.goals.length === 0) {
                showNoDataMessage('goalProgressChart', 'No goals found');
                return;
            }

            chartInstances.goalProgress = new Chart(context, {
                type: 'bar',
                data: {
                    labels: data.goals.map(goal => goal.goal_name),
                    datasets: [{
                        label: 'Progress %',
                        data: data.goals.map(goal => parseFloat(goal.completion_percentage)),
                        backgroundColor: data.goals.map(goal => 
                            parseFloat(goal.completion_percentage) >= 100 ? '#10b981' : '#3b82f6'
                        ),
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateGoalStats(stats) {
            const statsContainer = document.getElementById('goalStats');
            if (!statsContainer) {
                console.error('Goal stats container not found');
                return;
            }
            
            if (!stats) {
                statsContainer.innerHTML = '<div class="metric-row"><span class="metric-label">No goal data available</span></div>';
                return;
            }
            
            let html = '';
            html += '<div class="metric-row">';
            html += '<span class="metric-label">Active Goals</span>';
            html += '<span class="metric-value">' + (stats.active_goals || 0) + '</span>';
            html += '</div>';
            html += '<div class="metric-row">';
            html += '<span class="metric-label">Completed Goals</span>';
            html += '<span class="metric-value">' + (stats.completed_goals || 0) + '</span>';
            html += '</div>';
            html += '<div class="metric-row">';
            html += '<span class="metric-label">Total Progress</span>';
            html += '<span class="metric-value">' + (stats.average_progress || 0) + '%</span>';
            html += '</div>';
            html += '<div class="metric-row">';
            html += '<span class="metric-label">Total Amount</span>';
            html += '<span class="metric-value">₵' + parseFloat(stats.total_amount || 0).toLocaleString() + '</span>';
            html += '</div>';
            
            statsContainer.innerHTML = html;
        }

        // Budget Performance Functions
        async function refreshBudgetPerformance() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=budget_performance');
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                createBudgetPerformanceChart(data);
            } catch (error) {
                console.error('Error fetching budget performance:', error);
                showChartError('budgetPerformanceChart', 'Failed to load budget performance');
                showSnackbar('Failed to load budget performance', 'error');
            }
        }

        function createBudgetPerformanceChart(data) {
            const ctx = document.getElementById('budgetPerformanceChart');
            if (!ctx) {
                console.error('Budget performance chart canvas not found');
                return;
            }
            
            const context = ctx.getContext('2d');
            
            if (chartInstances.budgetPerformance) {
                chartInstances.budgetPerformance.destroy();
            }

            if (!data.categories || data.categories.length === 0) {
                showNoDataMessage('budgetPerformanceChart', 'No budget data available');
                return;
            }

            chartInstances.budgetPerformance = new Chart(context, {
                type: 'bar',
                data: {
                    labels: data.categories.map(item => item.category_name),
                    datasets: [
                        {
                            label: 'Budgeted',
                            data: data.categories.map(item => parseFloat(item.budgeted)),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderRadius: 4
                        },
                        {
                            label: 'Actual',
                            data: data.categories.map(item => parseFloat(item.actual)),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₵' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Income Trends Functions
        async function refreshIncomeTrends() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=income_trends');
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                createIncomeTrendsChart(data);
            } catch (error) {
                console.error('Error fetching income trends:', error);
                showChartError('incomeTrendsChart', 'Failed to load income trends');
                showSnackbar('Failed to load income trends', 'error');
            }
        }

        function createIncomeTrendsChart(data) {
            const ctx = document.getElementById('incomeTrendsChart');
            if (!ctx) {
                console.error('Income trends chart canvas not found');
                return;
            }
            
            const context = ctx.getContext('2d');
            
            if (chartInstances.incometrends) {
                chartInstances.incometrends.destroy();
            }

            if (!data.income_history || data.income_history.length === 0) {
                showNoDataMessage('incomeTrendsChart', 'No income data available');
                return;
            }

            const months = data.income_history.map(item => item.month);
            const amounts = data.income_history.map(item => parseFloat(item.amount));

            chartInstances.incometrends = new Chart(context, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Monthly Income',
                        data: amounts,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₵' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Predictive Insights Functions
        async function refreshPredictions() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=predictions');
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                updatePredictions(data);
            } catch (error) {
                console.error('Error fetching predictions:', error);
                showSnackbar('Failed to load predictions', 'error');
            }
        }

        function updatePredictions(data) {
            const container = document.getElementById('predictionsContainer');
            if (!container) {
                console.error('Predictions container not found');
                return;
            }

            if (data.predictions && data.predictions.length > 0) {
                let html = '';
                for (let i = 0; i < data.predictions.length; i++) {
                    const prediction = data.predictions[i];
                    html += '<div class="prediction-item">';
                    html += '<div class="prediction-icon">';
                    html += '<i data-feather="' + (prediction.icon || 'trending-up') + '"></i>';
                    html += '</div>';
                    html += '<div class="prediction-content">';
                    html += '<h4>' + prediction.title + '</h4>';
                    html += '<p>' + prediction.description + '</p>';
                    html += '</div>';
                    html += '</div>';
                }
                container.innerHTML = html;
                feather.replace();
            } else {
                container.innerHTML = '<div class="prediction-item"><div class="prediction-icon"><i data-feather="calendar"></i></div><div class="prediction-content"><h4>Next Month Prediction</h4><p>Based on your spending patterns, you\'re on track to save ₵500 next month.</p></div></div>';
                feather.replace();
            }
        }

        // Chat Functions
        function toggleChat() {
            const chatContainer = document.getElementById('chatContainer');
            const chatToggle = document.getElementById('chatToggle');
            const chatBadge = document.getElementById('chatBadge');
            
            if (chatContainer && chatToggle) {
                if (chatContainer.classList.contains('open')) {
                    chatContainer.classList.remove('open');
                    chatOpen = false;
                } else {
                    chatContainer.classList.add('open');
                    chatOpen = true;
                    if (chatBadge) {
                        chatBadge.style.display = 'none';
                    }
                }
            }
        }

        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            if (!input) return;
            
            const message = input.value.trim();
            if (message) {
                addChatMessage(message, 'user');
                input.value = '';
                
                // Simulate bot response
                setTimeout(() => {
                    const response = generateChatResponse(message);
                    addChatMessage(response, 'bot');
                }, 1000);
            }
        }

        function sendPredefinedMessage(message) {
            addChatMessage(message, 'user');
            
            setTimeout(() => {
                const response = generateChatResponse(message);
                addChatMessage(response, 'bot');
            }, 1000);
        }

        function addChatMessage(message, sender) {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;

            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + sender + '-message';
            
            let html = '';
            html += '<div class="message-avatar">';
            html += '<i data-feather="' + (sender === 'bot' ? 'cpu' : 'user') + '"></i>';
            html += '</div>';
            html += '<div class="message-content">';
            html += '<p>' + message + '</p>';
            html += '</div>';
            
            messageDiv.innerHTML = html;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            feather.replace();
        }

        function generateChatResponse(message) {
            const responses = {
                'How is my spending this month?': 'Based on your current data, you\'ve spent ₵2,450 this month, which is 15% below your budget. Great job!',
                'What are my biggest expenses?': 'Your top expense categories are: Food (₵800), Transportation (₵600), and Entertainment (₵400).',
                'How can I improve my budget?': 'Consider reducing entertainment expenses by 20% and setting up automatic savings transfers.',
                'default': 'I\'m here to help with your financial questions! Try asking about your spending, goals, or budget performance.'
            };
            
            return responses[message] || responses['default'];
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatMessage();
            }
        }

        // Utility Functions
        function showLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.add('show');
            }
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('show');
            }
        }

        function showChartError(chartId, message) {
            const chartElement = document.getElementById(chartId);
            if (chartElement) {
                const container = chartElement.parentElement;
                let html = '';
                html += '<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: #6b7280;">';
                html += '<i data-feather="alert-circle" style="width: 48px; height: 48px; color: #ef4444; margin-bottom: 1rem;"></i>';
                html += '<p style="text-align: center; font-weight: 500;">' + message + '</p>';
                html += '<button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer;">Retry</button>';
                html += '</div>';
                container.innerHTML = html;
                feather.replace();
            }
        }

        function showNoDataMessage(chartId, message) {
            const chartElement = document.getElementById(chartId);
            if (chartElement) {
                const container = chartElement.parentElement;
                let html = '';
                html += '<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: #6b7280;">';
                html += '<i data-feather="inbox" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>';
                html += '<p style="text-align: center; font-style: italic;">' + message + '</p>';
                html += '</div>';
                container.innerHTML = html;
                feather.replace();
            }
        }

        function showSnackbar(message, type) {
            type = type || 'info';
            const snackbar = document.getElementById('snackbar');
            const snackbarMessage = document.getElementById('snackbar-message');
            
            if (snackbar && snackbarMessage) {
                snackbarMessage.textContent = message;
                snackbar.className = 'snackbar ' + type;
                snackbar.classList.add('show');
                
                setTimeout(() => {
                    hideSnackbar();
                }, 5000);
            }
        }

        function hideSnackbar() {
            const snackbar = document.getElementById('snackbar');
            if (snackbar) {
                snackbar.classList.remove('show');
            }
        }
    </script>
</body>
</html>
