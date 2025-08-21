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

        .recommendations {
            text-align: left;
            margin-top: 1rem;
        }

        .recommendation {
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            background: var(--accent-light);
            border-radius: 12px;
            border-left: 4px solid var(--accent-color);
            font-size: 0.95rem;
        }

        /* Chat Assistant Styles */
        .chat-assistant {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .chat-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px var(--primary-shadow);
            transition: all 0.3s ease;
        }

        .chat-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px var(--primary-shadow);
        }

        .chat-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transform: scale(0);
            transform-origin: bottom right;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .chat-window.open {
            transform: scale(1);
        }

        .chat-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
        }

        .chat-messages {
            height: 350px;
            overflow-y: auto;
            padding: 1rem;
            background: #fafafa;
        }

        .chat-message {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            max-width: 85%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease;
        }

        .chat-message.user {
            background: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .chat-message.assistant {
            background: white;
            color: var(--text-primary);
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .chat-input {
            display: flex;
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            background: white;
        }

        .chat-input input {
            flex: 1;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            padding: 0.75rem 1rem;
            outline: none;
            font-size: 0.9rem;
        }

        .chat-input input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .chat-input button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-left: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .chat-input button:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .suggestion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .suggestion-chip {
            background: var(--accent-light);
            color: var(--primary-color);
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .suggestion-chip:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        .loading-indicator {
            display: none;
            text-align: center;
            padding: 1rem;
            color: var(--text-secondary);
        }

        .loading-dots::after {
            content: '';
            animation: dots 1.5s infinite;
        }

        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .insights-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .insights-hero {
                padding: 2rem 1rem;
            }
            
            .insights-hero h1 {
                font-size: 2rem;
            }
            
            .insights-stats {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .chat-window {
                width: calc(100vw - 2rem);
                right: 1rem;
                left: 1rem;
            }
            
            .chat-assistant {
                bottom: 1rem;
                right: 1rem;
            }
        }

        /* Loading States */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
            height: 20px;
            margin: 10px 0;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .error-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .retry-btn {
            background: var(--secondary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon">💰</div>
                <div class="logo-text">
                    <h1 id="logoUserName"><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Financial Insights</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard.php" class="nav-item">Dashboard</a>
                <a href="salary.php" class="nav-item">Salary Setup</a>
                <a href="budget.php" class="nav-item">Budget</a>
                <a href="personal-expense.php" class="nav-item">Expenses</a>
                <a href="savings.php" class="nav-item">Savings</a>
                <a href="insights.php" class="nav-item active">Insights</a>
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
                            <span>Ocean Blue</span>
                        </div>
                        <div class="theme-option" data-theme="forest" onclick="changeTheme('forest')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #059669, #047857)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #10b981, #059669)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #34d399, #10b981)"></div>
                            </div>
                            <span>Forest Green</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="user-menu">
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar"><?php 
                    echo strtoupper(substr($user_first_name, 0, 1));
                ?></div>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user_full_name); ?></h4>
                        <p>Personal Account</p>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="../actions/signout.php" class="dropdown-item">
                        <span class="dropdown-icon">🚪</span>
                        Sign Out
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Hero Section -->
            <div class="insights-hero">
                <h1>🧠 Smart Financial Insights</h1>
                <p>Discover patterns, trends, and opportunities in your financial data</p>
                <div class="insights-stats">
                    <div class="stat-item">
                        <span class="stat-number" id="totalInsights">12</span>
                        <span class="stat-label">Active Insights</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="healthScore">85</span>
                        <span class="stat-label">Health Score</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="trendsAnalyzed">6</span>
                        <span class="stat-label">Trends Analyzed</span>
                    </div>
                </div>
            </div>

            <!-- Financial Health Score -->
            <div class="insights-grid">
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">❤️</span> Financial Health</h3>
                        <button class="refresh-btn" onclick="refreshFinancialHealth()">🔄</button>
                    </div>
                    <div class="health-score" id="healthScoreContainer">
                        <div class="health-score-number" id="healthScoreNumber">Loading...</div>
                        <div class="health-status" id="healthStatus">Analyzing...</div>
                        <div class="progress-ring">
                            <svg width="120" height="120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="url(#healthGradient)" stroke-width="8" 
                                        stroke-linecap="round" stroke-dasharray="314" stroke-dashoffset="314" id="healthProgress"/>
                                <defs>
                                    <linearGradient id="healthGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#10b981"/>
                                        <stop offset="100%" style="stop-color:#3b82f6"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="recommendations" id="healthRecommendations"></div>
                    </div>
                </div>

                <!-- Spending Patterns -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">📊</span> Spending Patterns</h3>
                        <button class="refresh-btn" onclick="refreshSpendingPatterns()">🔄</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="spendingPatternsChart"></canvas>
                    </div>
                </div>

                <!-- Goal Analytics -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">🎯</span> Goal Progress</h3>
                        <button class="refresh-btn" onclick="refreshGoalAnalytics()">🔄</button>
                    </div>
                    <div id="goalAnalyticsContainer">
                        <div class="chart-container">
                            <canvas id="goalProgressChart"></canvas>
                        </div>
                        <div id="goalStats"></div>
                    </div>
                </div>

                <!-- Budget Performance -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">💰</span> Budget Performance</h3>
                        <button class="refresh-btn" onclick="refreshBudgetPerformance()">🔄</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="budgetPerformanceChart"></canvas>
                    </div>
                </div>

                <!-- Income Trends -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">📈</span> Income Trends</h3>
                        <button class="refresh-btn" onclick="refreshIncomeTrends()">🔄</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="incomeTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Predictive Insights -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">🔮</span> Predictive Insights</h3>
                        <button class="refresh-btn" onclick="refreshPredictions()">🔄</button>
                    </div>
                    <div id="predictionsContainer">
                        <div class="loading-indicator">
                            <div class="loading-dots">Analyzing patterns</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Assistant -->
            <div class="chat-assistant">
                <div class="chat-window" id="chatWindow">
                    <div class="chat-header">
                        <span>🤖 Financial Assistant</span>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message assistant">
                            Hi! I'm your financial assistant. Ask me anything about your finances!
                        </div>
                        <div class="suggestion-chips">
                            <div class="suggestion-chip" onclick="askQuestion('spending this month')">Monthly Spending</div>
                            <div class="suggestion-chip" onclick="askQuestion('savings progress')">Savings Progress</div>
                            <div class="suggestion-chip" onclick="askQuestion('budget status')">Budget Status</div>
                            <div class="suggestion-chip" onclick="askQuestion('financial health')">Financial Health</div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <input type="text" id="chatInput" placeholder="Ask about your finances..." 
                               onkeypress="handleChatKeyPress(event)">
                        <button onclick="sendChatMessage()">📤</button>
                    </div>
                </div>
                <button class="chat-toggle" id="chatToggle" onclick="toggleChat()">🤖</button>
            </div>
        </main>
    </div>

    <script>
        // Global variables for charts
        let chartInstances = {};
        let chatOpen = false;

        // Initialize Feather Icons and the insights page
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
                    console.log(`${componentName} loaded successfully`);
                    return;
                } catch (error) {
                    console.warn(`${componentName} failed attempt ${i + 1}:`, error);
                    if (i === maxRetries - 1) {
                        throw new Error(`${componentName} failed after ${maxRetries} attempts`);
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
                    throw new Error(`HTTP error! status: ${response.status}`);
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
            // Update hero section health score
            const heroHealthScore = document.getElementById('healthScoreNumber');
            const healthScoreDisplay = document.getElementById('healthScoreDisplay');
            const healthStatus = document.getElementById('healthStatus');
            const healthProgress = document.getElementById('healthProgress');
            const healthRecommendations = document.getElementById('healthRecommendations');

            const healthScore = data.health_score || 85;

            // Update hero stats
            if (heroHealthScore) {
                heroHealthScore.textContent = healthScore;
            }

            // Update health card
            if (healthScoreDisplay) {
                healthScoreDisplay.textContent = healthScore;
            }

            if (healthStatus) {
                let status = 'Excellent';
                if (healthScore < 70) status = 'Needs Improvement';
                else if (healthScore < 85) status = 'Good';
                healthStatus.textContent = status;
            }

            // Animate progress ring
            if (healthProgress) {
                const circumference = 2 * Math.PI * 60;
                const offset = circumference - (healthScore / 100) * circumference;
                healthProgress.style.strokeDashoffset = offset;
            }

            // Display recommendations
            if (healthRecommendations && data.recommendations) {
                if (data.recommendations.length > 0) {
                    healthRecommendations.innerHTML = data.recommendations.map(rec => 
                        `<div class="recommendation-item">
                            <i data-feather="check-circle"></i>
                            <span>${rec}</span>
                        </div>`
                    ).join('');
                    feather.replace();
                } else {
                    healthRecommendations.innerHTML = `
                        <div class="recommendation-item">
                            <i data-feather="check-circle"></i>
                            <span>Your financial health is looking good!</span>
                        </div>`;
                    feather.replace();
                }
            }
        }

        function updateHeroStats(data) {
            const totalSpent = document.getElementById('totalSpent');
            const monthlyAvg = document.getElementById('monthlyAvg');

            if (totalSpent && data.total_spent) {
                totalSpent.textContent = `₵${parseFloat(data.total_spent).toLocaleString()}`;
            }

            if (monthlyAvg && data.monthly_average) {
                monthlyAvg.textContent = `₵${parseFloat(data.monthly_average).toLocaleString()}`;
            }
        }

        // Spending Patterns Functions
        async function refreshSpendingPatterns() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=spending_patterns');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
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

            // Handle empty data
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
                    throw new Error(`HTTP error! status: ${response.status}`);
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
            
            statsContainer.innerHTML = `
                <div class="metric-row">
                    <span class="metric-label">Active Goals</span>
                    <span class="metric-value">${stats.active_goals || 0}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Completed Goals</span>
                    <span class="metric-value">${stats.completed_goals || 0}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Total Progress</span>
                    <span class="metric-value">${stats.average_progress || 0}%</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Total Amount</span>
                    <span class="metric-value">₵${parseFloat(stats.total_amount || 0).toLocaleString()}</span>
                </div>
            `;
        }

        // Budget Performance Functions
        async function refreshBudgetPerformance() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=budget_performance');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
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
                    throw new Error(`HTTP error! status: ${response.status}`);
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
                    throw new Error(`HTTP error! status: ${response.status}`);
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
                container.innerHTML = data.predictions.map(prediction => `
                    <div class="prediction-item">
                        <div class="prediction-icon">
                            <i data-feather="${prediction.icon || 'trending-up'}"></i>
                        </div>
                        <div class="prediction-content">
                            <h4>${prediction.title}</h4>
                            <p>${prediction.description}</p>
                        </div>
                    </div>
                `).join('');
                feather.replace();
            } else {
                container.innerHTML = `
                    <div class="prediction-item">
                        <div class="prediction-icon">
                            <i data-feather="calendar"></i>
                        </div>
                        <div class="prediction-content">
                            <h4>Next Month Prediction</h4>
                            <p>Based on your spending patterns, you're on track to save ₵500 next month.</p>
                        </div>
                    </div>
                `;
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
            messageDiv.className = `message ${sender}-message`;
            
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <i data-feather="${sender === 'bot' ? 'cpu' : 'user'}"></i>
                </div>
                <div class="message-content">
                    <p>${message}</p>
                </div>
            `;
            
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
                container.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: #6b7280;">
                        <i data-feather="alert-circle" style="width: 48px; height: 48px; color: #ef4444; margin-bottom: 1rem;"></i>
                        <p style="text-align: center; font-weight: 500;">${message}</p>
                        <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer;">
                            Retry
                        </button>
                    </div>
                `;
                feather.replace();
            }
        }

        function showNoDataMessage(chartId, message) {
            const chartElement = document.getElementById(chartId);
            if (chartElement) {
                const container = chartElement.parentElement;
                container.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: #6b7280;">
                        <i data-feather="inbox" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                        <p style="text-align: center; font-style: italic;">${message}</p>
                    </div>
                `;
                feather.replace();
            }
        }

        function showSnackbar(message, type = 'info') {
            const snackbar = document.getElementById('snackbar');
            const snackbarMessage = document.getElementById('snackbar-message');
            
            if (snackbar && snackbarMessage) {
                snackbarMessage.textContent = message;
                snackbar.className = `snackbar ${type}`;
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
            const statusElement = document.getElementById('healthStatus');
            const progressElement = document.getElementById('healthProgress');
            const recommendationsElement = document.getElementById('healthRecommendations');

            // Add null checks with descriptive error message
            if (!scoreElement) {
                console.error('Health score number element not found');
                return;
            }
            if (!statusElement) {
                console.error('Health status element not found');
                return;
            }
            if (!progressElement) {
                console.error('Health progress element not found');
                return;
            }
            if (!recommendationsElement) {
                console.error('Health recommendations element not found');
                return;
            }

            const healthScore = data.health_score || 0;
            scoreElement.textContent = healthScore;
            
            let status = 'Excellent';
            if (healthScore < 70) status = 'Needs Improvement';
            else if (healthScore < 85) status = 'Good';
            
            statusElement.textContent = status;

            // Animate progress ring
            const circumference = 2 * Math.PI * 50;
            const offset = circumference - (healthScore / 100) * circumference;
            progressElement.style.strokeDashoffset = offset;

            // Display recommendations
            if (data.recommendations && data.recommendations.length > 0) {
                recommendationsElement.innerHTML = data.recommendations.map(rec => 
                    `<div class="recommendation">${rec}</div>`
                ).join('');
            } else {
                recommendationsElement.innerHTML = '<div class="recommendation">💡 Start tracking your expenses to get personalized recommendations!</div>';
            }
        }

        // Spending Patterns Functions
        async function refreshSpendingPatterns() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=spending_patterns');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
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

            // Handle empty data
            if (!data.daily_patterns || data.daily_patterns.length === 0) {
                showNoDataMessage('spendingPatternsChart', 'No spending data available');
                return;
            }

            chartInstances.spendingPatterns = new Chart(context, {
                type: 'line',
                data: {
                    labels: data.daily_patterns.map(item => item.day_name),
                    datasets: [{
                        label: 'Average Daily Spending',
                        data: data.daily_patterns.map(item => parseFloat(item.avg_amount) || 0),
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
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₵' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
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
                    throw new Error(`HTTP error! status: ${response.status}`);
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

            const completedGoals = data.goals.filter(goal => parseFloat(goal.completion_percentage) >= 100);
            const inProgressGoals = data.goals.filter(goal => parseFloat(goal.completion_percentage) < 100);

            chartInstances.goalProgress = new Chart(context, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress'],
                    datasets: [{
                        data: [completedGoals.length, inProgressGoals.length],
                        backgroundColor: ['#10b981', '#3b82f6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
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
            
            statsContainer.innerHTML = `
                <div class="metric-row">
                    <span class="metric-label">Total Goals</span>
                    <span class="metric-value">${stats.total || 0}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Completed</span>
                    <span class="metric-value">${stats.completed || 0}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">On Track</span>
                    <span class="metric-value">${stats.on_track || 0}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Completion Rate</span>
                    <span class="metric-value">${stats.completion_rate || 0}%</span>
                </div>
            `;
        }

        // Budget Performance Functions
        async function refreshBudgetPerformance() {
            try {
                const response = await fetch('/budget/api/insights_data.php?action=budget_performance');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
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
                        },
                        {
                            label: 'Actual',
                            data: data.categories.map(item => parseFloat(item.actual)),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
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
                            beginAtZero: true
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
                    throw new Error(`HTTP error! status: ${response.status}`);
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

            const months = [...new Set(data.income_history.map(item => item.month))];
            const salaryData = months.map(month => {
                const item = data.income_history.find(h => h.month === month && h.type === 'salary');
                return item ? parseFloat(item.amount) : 0;
            });

            chartInstances.incometrends = new Chart(context, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Monthly Income',
                        data: salaryData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
                            beginAtZero: true
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
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                updatePredictions(data);
            } catch (error) {
                console.error('Error fetching predictions:', error);
                const container = document.getElementById('predictionsContainer');
                if (container) {
                    container.innerHTML = `
                        <div class="error-state">
                            <div class="error-icon">🔮</div>
                            <p>Failed to load predictions</p>
                            <button class="retry-btn" onclick="refreshPredictions()">Try Again</button>
                        </div>
                    `;
                }
                showSnackbar('Failed to load predictions', 'error');
            }
        }

        function updatePredictions(data) {
            const container = document.getElementById('predictionsContainer');
            if (!container) {
                console.error('Predictions container not found');
                return;
            }
            
            let html = '';

            if (data.next_month_expense) {
                html += `
                    <div class="metric-row">
                        <span class="metric-label">🔮 Next Month Forecast</span>
                        <span class="metric-value">₵${data.next_month_expense.toLocaleString()}</span>
                    </div>
                `;
            }

            if (data.goal_predictions && data.goal_predictions.length > 0) {
                html += '<div style="margin-top: 1rem;"><strong>Goal Predictions:</strong></div>';
                data.goal_predictions.forEach(pred => {
                    const color = pred.likelihood === 'high' ? '#10b981' : 
                                 pred.likelihood === 'medium' ? '#f59e0b' : '#ef4444';
                    html += `
                        <div class="metric-row">
                            <span class="metric-label">${pred.goal_name}</span>
                            <span class="metric-value" style="color: ${color}">
                                ₵${pred.daily_savings_needed}/day (${pred.likelihood})
                            </span>
                        </div>
                    `;
                });
            }

            if (!html) {
                html = '<div class="metric-row"><span class="metric-label">Analyzing your financial patterns...</span></div>';
            }

            container.innerHTML = html;
        }

        // Chat Functions
        function toggleChat() {
            chatOpen = !chatOpen;
            const chatWindow = document.getElementById('chatWindow');
            const chatToggle = document.getElementById('chatToggle');
            
            if (chatOpen) {
                chatWindow.classList.add('open');
                chatToggle.textContent = '✖️';
            } else {
                chatWindow.classList.remove('open');
                chatToggle.textContent = '🤖';
            }
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatMessage();
            }
        }

        async function sendChatMessage() {
            const input = document.getElementById('chatInput');
            if (!input) {
                console.error('Chat input not found');
                return;
            }
            
            const query = input.value.trim();
            
            if (!query) return;

            addChatMessage('user', query);
            input.value = '';

            showChatLoading();

            try {
                const response = await fetch(`/budget/api/insights_data.php?action=chat_response&query=${encodeURIComponent(query)}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                hideChatLoading();
                
                if (data.error) {
                    addChatMessage('assistant', 'Sorry, I encountered an error. Please try again.');
                } else {
                    addChatMessage('assistant', data.message);
                    if (data.suggestions) {
                        addSuggestionChips(data.suggestions);
                    }
                }
            } catch (error) {
                console.error('Chat error:', error);
                hideChatLoading();
                addChatMessage('assistant', 'Sorry, I encountered an error. Please try again.');
            }
        }

        function askQuestion(question) {
            document.getElementById('chatInput').value = question;
            sendChatMessage();
        }

        function addChatMessage(sender, message) {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) {
                console.error('Chat messages container not found');
                return;
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender}`;
            messageDiv.textContent = message;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function addSuggestionChips(suggestions) {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) {
                console.error('Chat messages container not found');
                return;
            }
            
            const chipsDiv = document.createElement('div');
            chipsDiv.className = 'suggestion-chips';
            
            suggestions.forEach(suggestion => {
                const chip = document.createElement('div');
                chip.className = 'suggestion-chip';
                chip.textContent = suggestion;
                chip.onclick = () => askQuestion(suggestion);
                chipsDiv.appendChild(chip);
            });
            
            messagesContainer.appendChild(chipsDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function showChatLoading() {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) {
                console.error('Chat messages container not found');
                return;
            }
            
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'chat-message assistant loading-message';
            loadingDiv.innerHTML = '<div class="loading-dots">Thinking</div>';
            messagesContainer.appendChild(loadingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideChatLoading() {
            const loadingMessage = document.querySelector('.loading-message');
            if (loadingMessage) {
                loadingMessage.remove();
            }
        }

        // Theme System
        function setupThemeSystem() {
            // Load saved theme
            const savedTheme = localStorage.getItem('selectedTheme') || 'default';
            changeTheme(savedTheme);
        }

        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            dropdown.classList.toggle('show');
        }

        function changeTheme(theme) {
            document.body.setAttribute('data-theme', theme);
            localStorage.setItem('selectedTheme', theme);
            
            // Update active theme indicator
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`[data-theme="${theme}"]`).classList.add('active');
            
            // Close dropdown
            document.getElementById('themeDropdown').classList.remove('show');
        }

        // User menu functionality
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
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

        // Utility Functions
        function showNotification(message, type = 'info') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: type,
                title: message
            });
        }

        function showLoadingState(containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = `
                    <div class="loading-indicator" style="display: block; text-align: center; padding: 2rem;">
                        <div class="skeleton" style="height: 40px; margin: 10px 0;"></div>
                        <div class="skeleton" style="height: 20px; margin: 10px 0; width: 80%;"></div>
                        <div class="skeleton" style="height: 20px; margin: 10px 0; width: 60%;"></div>
                        <div style="color: var(--text-secondary); margin-top: 1rem;">Loading data...</div>
                    </div>
                `;
            }
        }

        function showErrorState(containerId, message, retryFunctionName) {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">⚠️</div>
                        <p>${message}</p>
                        <button class="retry-btn" onclick="${retryFunctionName}()">Try Again</button>
                    </div>
                `;
            }
        }

        function showChartError(chartId, message) {
            const canvas = document.getElementById(chartId);
            if (!canvas) return;
            
            const container = canvas.parentElement;
            if (container) {
                container.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">📊</div>
                        <p>${message}</p>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">Please try refreshing the page</p>
                    </div>
                `;
            }
        }

        function showNoDataMessage(chartId, message) {
            const canvas = document.getElementById(chartId);
            if (!canvas) return;
            
            const container = canvas.parentElement;
            if (container) {
                container.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">📊</div>
                        <p>${message}</p>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">Start adding expenses to see insights!</p>
                    </div>
                `;
            }
        }

        // Snackbar notification function (from personal-dashboard design)
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

        // Theme management functions
        function setupThemeSystem() {
            const savedTheme = localStorage.getItem('personalTheme') || 'default';
            changeTheme(savedTheme);
        }

        function changeTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('personalTheme', theme);
            
            // Update active theme indicator if theme selector exists
            const themeOptions = document.querySelectorAll('.theme-option');
            themeOptions.forEach(option => {
                option.classList.toggle('active', option.dataset.theme === theme);
            });
        }

        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.theme-selector')) {
                const dropdown = document.getElementById('themeDropdown');
                if (dropdown) dropdown.classList.remove('show');
            }
            if (!event.target.closest('.user-menu')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown) dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
