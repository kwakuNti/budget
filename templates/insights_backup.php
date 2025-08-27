<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Insights - Smart Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
            line-height: 1.6;
        }

        .dashboard {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-text p {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: -4px;
        }

        .header-nav {
            display: flex;
            gap: 0.5rem;
        }

        .nav-item {
            padding: 0.75rem 1.25rem;
            text-decoration: none;
            color: #64748b;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            left: 0;
        }

        .nav-item:hover,
        .nav-item.active {
            color: white;
            transform: translateY(-2px);
        }

        .theme-selector {
            position: relative;
        }

        .theme-toggle-btn {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .theme-toggle-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .theme-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-width: 250px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .theme-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .theme-dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .theme-dropdown-header h4 {
            color: #1e293b;
            font-weight: 600;
        }

        .themes-grid {
            padding: 1rem;
            display: grid;
            gap: 0.75rem;
        }

        .theme-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .theme-option:hover {
            background: #f8fafc;
            transform: translateX(4px);
        }

        .theme-option.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-color: #667eea;
        }

        .theme-preview {
            display: flex;
            gap: 4px;
        }

        .theme-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }

        .user-menu {
            position: relative;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .user-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-info {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .user-info h4 {
            color: #1e293b;
            font-weight: 600;
        }

        .user-info p {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 2px;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            color: #1e293b;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
        }

        /* Hero Section */
        .insights-hero {
            text-align: center;
            margin-bottom: 3rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .insights-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .insights-hero p {
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .insights-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.875rem;
        }

        /* Insights Grid */
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .insight-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .insight-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .insight-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.15);
        }

        .insight-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .insight-card-header h3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .insight-icon {
            font-size: 1.5rem;
            display: inline-block;
        }

        .refresh-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .refresh-btn:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Health Score Specific */
        .health-score {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .health-score-number {
            font-size: 4rem;
            font-weight: 900;
            background: linear-gradient(135deg, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .health-status {
            font-size: 1.25rem;
            font-weight: 600;
            color: #10b981;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .progress-ring svg {
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            transition: stroke-dashoffset 1s ease-in-out;
        }

        .recommendations {
            width: 100%;
            text-align: left;
        }

        .recommendation {
            padding: 1rem;
            margin: 0.75rem 0;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(59, 130, 246, 0.1));
            border-radius: 16px;
            border-left: 4px solid #10b981;
            font-size: 0.95rem;
            color: #1e293b;
            position: relative;
            overflow: hidden;
        }

        .recommendation::before {
            content: '💡';
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0.3;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        /* Error States */
        .error-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: #64748b;
            text-align: center;
        }

        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .retry-btn {
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
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

        /* Chat Assistant */
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .chat-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 16px 50px rgba(102, 126, 234, 0.5);
        }

        .chat-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 500px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            transform: scale(0);
            transform-origin: bottom right;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .chat-window.open {
            transform: scale(1);
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .chat-messages {
            height: 300px;
            overflow-y: auto;
            padding: 1rem;
            background: rgba(248, 250, 252, 0.8);
        }

        .chat-message {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            max-width: 85%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease;
        }

        .chat-message.user {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 6px;
        }

        .chat-message.assistant {
            background: white;
            color: #1e293b;
            border-bottom-left-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .suggestion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .suggestion-chip {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
        }

        .suggestion-chip:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: scale(1.05);
        }

        .chat-input {
            display: flex;
            padding: 1rem;
            border-top: 1px solid rgba(226, 232, 240, 0.5);
            background: rgba(255, 255, 255, 0.8);
        }

        .chat-input input {
            flex: 1;
            border: 1px solid rgba(226, 232, 240, 0.5);
            border-radius: 25px;
            padding: 0.75rem 1rem;
            outline: none;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.8);
        }

        .chat-input input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .chat-input button {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Snackbar */
        .snackbar {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 10000;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
        }

        .snackbar.show {
            transform: translateX(-50%) translateY(0);
        }

        .snackbar.success { border-left: 4px solid #10b981; }
        .snackbar.error { border-left: 4px solid #ef4444; }
        .snackbar.warning { border-left: 4px solid #f59e0b; }
        .snackbar.info { border-left: 4px solid #3b82f6; }

        .snackbar-icon {
            font-size: 1.2rem;
        }

        .snackbar-message {
            flex: 1;
            color: #1e293b;
            font-weight: 500;
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
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
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

            .insights-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .main-content {
                padding: 1rem;
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

        @media (max-width: 480px) {
            .insights-grid {
                grid-template-columns: 1fr;
            }

            .insight-card {
                padding: 1.5rem;
            }

            .logo-text h1 {
                font-size: 1.25rem;
            }

            .nav-item {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
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
                    <h1 id="logoUserName">John</h1>
                    <p>Financial Insights</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard" class="nav-item">Dashboard</a>
                <a href="salary" class="nav-item">Salary Setup</a>
                <a href="budget" class="nav-item">Budget</a>
                <a href="personal-expense" class="nav-item">Expenses</a>
                <a href="savings" class="nav-item">Savings</a>
                <!-- <a href="insights.php" class="nav-item active">Insights</a> -->
                <a href="report" class="nav-item">Reports</a>
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
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar">J</div>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-info">
                        <h4>John Doe</h4>
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
                        <div class="stat-number" id="totalInsights">12</div>
                        <div class="stat-label">Active Insights</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="healthScore">85</div>
                        <div class="stat-label">Health Score</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="trendsAnalyzed">6</div>
                        <div class="stat-label">Trends Analyzed</div>
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
                        <div class="health-score-number" id="healthScoreNumber">85</div>
                        <div class="health-status" id="healthStatus">Good</div>
                        <div class="progress-ring">
                            <svg width="120" height="120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="url(#healthGradient)" stroke-width="8" 
                                        stroke-linecap="round" stroke-dasharray="314" stroke-dashoffset="94" id="healthProgress"/>
                                <defs>
                                    <linearGradient id="healthGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#10b981"/>
                                        <stop offset="100%" style="stop-color:#3b82f6"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="recommendations" id="healthRecommendations">
                            <div class="recommendation">💡 Great job! Your spending is well within budget this month.</div>
                            <div class="recommendation">💰 Consider increasing your emergency fund to 6 months of expenses.</div>
                        </div>
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
                        <div id="goalStats">
                            <div class="recommendation">
                                <strong>Active Goals:</strong> 3 goals in progress
                            </div>
                            <div class="recommendation">
                                <strong>Completion Rate:</strong> 67% average progress
                            </div>
                        </div>
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
                        <div class="recommendation">
                            <strong>Next Month Forecast:</strong> Based on your patterns, expect to spend ₵2,450
                        </div>
                        <div class="recommendation">
                            <strong>Savings Opportunity:</strong> You could save an extra ₵300 by reducing dining out
                        </div>
                        <div class="recommendation">
                            <strong>Goal Achievement:</strong> On track to reach your vacation fund goal in 4 months
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

        // Initialize the insights page
        document.addEventListener('DOMContentLoaded', function() {
            initializeInsights();
            setupThemeSystem();
        });

        function initializeInsights() {
            // Load all insights with sample data
            setTimeout(() => {
                refreshFinancialHealth();
                refreshSpendingPatterns(); 
                refreshGoalAnalytics();
                refreshBudgetPerformance();
                refreshIncomeTrends();
                refreshPredictions();
            }, 500);
        }

        // Financial Health Functions
        async function refreshFinancialHealth() {
            try {
                // Simulate loading
                showLoadingState('healthScoreContainer');
                
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Sample data
                const data = {
                    health_score: 85,
                    recommendations: [
                        "Great job! Your spending is well within budget this month.",
                        "Consider increasing your emergency fund to 6 months of expenses.",
                        "You're saving 20% of your income - excellent progress!"
                    ]
                };

                updateHealthScore(data);
                showSnackbar('Financial health updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching financial health:', error);
                showSnackbar('Failed to load financial health data', 'error');
            }
        }

        function updateHealthScore(data) {
            const scoreElement = document.getElementById('healthScoreNumber');
            const statusElement = document.getElementById('healthStatus');
            const progressElement = document.getElementById('healthProgress');
            const recommendationsElement = document.getElementById('healthRecommendations');

            if (!scoreElement || !statusElement || !progressElement || !recommendationsElement) {
                console.error('Health score elements not found');
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
                    `<div class="recommendation">💡 ${rec}</div>`
                ).join('');
            }
        }

        // Spending Patterns Functions
        async function refreshSpendingPatterns() {
            try {
                const ctx = document.getElementById('spendingPatternsChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.spendingPatterns) {
                    chartInstances.spendingPatterns.destroy();
                }

                // Sample data
                const data = {
                    daily_patterns: [
                        { day_name: 'Monday', avg_amount: 120 },
                        { day_name: 'Tuesday', avg_amount: 95 },
                        { day_name: 'Wednesday', avg_amount: 140 },
                        { day_name: 'Thursday', avg_amount: 110 },
                        { day_name: 'Friday', avg_amount: 180 },
                        { day_name: 'Saturday', avg_amount: 220 },
                        { day_name: 'Sunday', avg_amount: 160 }
                    ]
                };

                chartInstances.spendingPatterns = new Chart(context, {
                    type: 'line',
                    data: {
                        labels: data.daily_patterns.map(item => item.day_name),
                        datasets: [{
                            label: 'Average Daily Spending',
                            data: data.daily_patterns.map(item => item.avg_amount),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6
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
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '₵' + value;
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

                showSnackbar('Spending patterns updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching spending patterns:', error);
                showChartError('spendingPatternsChart', 'Failed to load spending patterns');
                showSnackbar('Failed to load spending patterns', 'error');
            }
        }

        // Goal Analytics Functions
        async function refreshGoalAnalytics() {
            try {
                const ctx = document.getElementById('goalProgressChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.goalProgress) {
                    chartInstances.goalProgress.destroy();
                }

                // Sample data
                const data = {
                    goals: [
                        { goal_name: 'Emergency Fund', completion_percentage: 75 },
                        { goal_name: 'Vacation Fund', completion_percentage: 45 },
                        { goal_name: 'New Car', completion_percentage: 30 },
                        { goal_name: 'Home Deposit', completion_percentage: 85 }
                    ]
                };

                chartInstances.goalProgress = new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: data.goals.map(goal => goal.goal_name),
                        datasets: [{
                            label: 'Progress %',
                            data: data.goals.map(goal => goal.completion_percentage),
                            backgroundColor: data.goals.map(goal => 
                                goal.completion_percentage >= 75 ? '#10b981' : 
                                goal.completion_percentage >= 50 ? '#f59e0b' : '#667eea'
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

                showSnackbar('Goal analytics updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching goal analytics:', error);
                showChartError('goalProgressChart', 'Failed to load goal analytics');
                showSnackbar('Failed to load goal analytics', 'error');
            }
        }

        // Budget Performance Functions
        async function refreshBudgetPerformance() {
            try {
                const ctx = document.getElementById('budgetPerformanceChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.budgetPerformance) {
                    chartInstances.budgetPerformance.destroy();
                }

                // Sample data
                const data = {
                    categories: [
                        { category_name: 'Food', budgeted: 800, actual: 750 },
                        { category_name: 'Transport', budgeted: 400, actual: 450 },
                        { category_name: 'Entertainment', budgeted: 300, actual: 280 },
                        { category_name: 'Utilities', budgeted: 200, actual: 195 },
                        { category_name: 'Shopping', budgeted: 500, actual: 520 }
                    ]
                };

                chartInstances.budgetPerformance = new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: data.categories.map(item => item.category_name),
                        datasets: [
                            {
                                label: 'Budgeted',
                                data: data.categories.map(item => item.budgeted),
                                backgroundColor: 'rgba(102, 126, 234, 0.7)',
                                borderRadius: 4
                            },
                            {
                                label: 'Actual',
                                data: data.categories.map(item => item.actual),
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
                                        return '₵' + value;
                                    }
                                }
                            }
                        }
                    }
                });

                showSnackbar('Budget performance updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching budget performance:', error);
                showChartError('budgetPerformanceChart', 'Failed to load budget performance');
                showSnackbar('Failed to load budget performance', 'error');
            }
        }

        // Income Trends Functions
        async function refreshIncomeTrends() {
            try {
                const ctx = document.getElementById('incomeTrendsChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.incometrends) {
                    chartInstances.incometrends.destroy();
                }

                // Sample data
                const data = {
                    income_history: [
                        { month: 'Jan', amount: 3500 },
                        { month: 'Feb', amount: 3500 },
                        { month: 'Mar', amount: 3700 },
                        { month: 'Apr', amount: 3500 },
                        { month: 'May', amount: 3800 },
                        { month: 'Jun', amount: 3500 }
                    ]
                };

                chartInstances.incometrends = new Chart(context, {
                    type: 'line',
                    data: {
                        labels: data.income_history.map(item => item.month),
                        datasets: [{
                            label: 'Monthly Income',
                            data: data.income_history.map(item => item.amount),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6
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

                showSnackbar('Income trends updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching income trends:', error);
                showChartError('incomeTrendsChart', 'Failed to load income trends');
                showSnackbar('Failed to load income trends', 'error');
            }
        }

        // Predictive Insights Functions
        async function refreshPredictions() {
            try {
                const container = document.getElementById('predictionsContainer');
                if (!container) return;
                
                // Sample predictions
                const predictions = [
                    "Next Month Forecast: Based on your patterns, expect to spend ₵2,450",
                    "Savings Opportunity: You could save an extra ₵300 by reducing dining out",
                    "Goal Achievement: On track to reach your vacation fund goal in 4 months",
                    "Budget Alert: You're trending 5% over budget in the Entertainment category"
                ];

                container.innerHTML = predictions.map(prediction => 
                    `<div class="recommendation">🔮 ${prediction}</div>`
                ).join('');

                showSnackbar('Predictions updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching predictions:', error);
                container.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">🔮</div>
                        <p>Failed to load predictions</p>
                        <button class="retry-btn" onclick="refreshPredictions()">Try Again</button>
                    </div>
                `;
                showSnackbar('Failed to load predictions', 'error');
            }
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
            if (!input) return;
            
            const query = input.value.trim();
            if (!query) return;

            addChatMessage('user', query);
            input.value = '';

            showChatLoading();

            // Simulate AI response
            setTimeout(() => {
                hideChatLoading();
                const response = generateChatResponse(query);
                addChatMessage('assistant', response);
            }, 1500);
        }

        function askQuestion(question) {
            document.getElementById('chatInput').value = question;
            sendChatMessage();
        }

        function addChatMessage(sender, message) {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender}`;
            messageDiv.textContent = message;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function generateChatResponse(message) {
            const responses = {
                'spending this month': 'You\'ve spent ₵2,195 this month, which is 12% below your budget. Great job staying on track!',
                'savings progress': 'Your savings rate is 22% this month. You\'re exceeding your 20% target - excellent work!',
                'budget status': 'Overall budget performance: 88% used. You\'re doing well in most categories, but watch your entertainment spending.',
                'financial health': 'Your financial health score is 85/100. Strong areas: savings rate and budget adherence. Area for improvement: emergency fund.'
            };
            
            const lowerMessage = message.toLowerCase();
            for (const [key, response] of Object.entries(responses)) {
                if (lowerMessage.includes(key)) {
                    return response;
                }
            }
            
            return 'I\'m here to help with your financial questions! Try asking about your spending, savings, budget, or financial health.';
        }

        function showChatLoading() {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;
            
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'chat-message assistant loading-message';
            loadingDiv.innerHTML = '<div class="loading-dots">Analyzing your data...</div>';
            messagesContainer.appendChild(loadingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideChatLoading() {
            const loadingMessage = document.querySelector('.loading-message');
            if (loadingMessage) {
                loadingMessage.remove();
            }
        }

        // Theme System Functions
        function setupThemeSystem() {
            const savedTheme = localStorage.getItem('selectedTheme') || 'default';
            changeTheme(savedTheme);
        }

        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        function changeTheme(theme) {
            document.body.setAttribute('data-theme', theme);
            localStorage.setItem('selectedTheme', theme);
            
            // Update active theme indicator
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.toggle('active', option.dataset.theme === theme);
            });
            
            // Close dropdown
            const dropdown = document.getElementById('themeDropdown');
            if (dropdown) dropdown.classList.remove('show');
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

        // Utility Functions
        function showLoadingState(containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                const originalContent = container.innerHTML;
                container.innerHTML = `
                    <div class="loading-indicator" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px;">
                        <div class="skeleton" style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 1rem;"></div>
                        <div class="skeleton" style="width: 150px; height: 20px; margin-bottom: 0.5rem;"></div>
                        <div class="skeleton" style="width: 100px; height: 16px;"></div>
                    </div>
                `;
                
                setTimeout(() => {
                    container.innerHTML = originalContent;
                }, 1000);
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
                        <button class="retry-btn" onclick="location.reload()">Retry</button>
                    </div>
                `;
            }
        }

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
    </script>
</body>
</html>