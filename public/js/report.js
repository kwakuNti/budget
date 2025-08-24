/**
 * Comprehensive Financial Report JavaScript
 * Powers interactive charts, data visualization, and dynamic content
 */

class FinancialReportApp {
    constructor() {
        this.charts = {};
        this.data = {};
        this.currentTheme = 'ocean';
        this.isLoading = false;
        this.chartsInitialized = false; // Flag to prevent multiple initializations
        
        this.init();
    }

    async init() {
        console.log('🚀 Initializing Financial Report App');
        
        // Show loading overlay
        this.showLoading();
        
        // Initialize event listeners
        this.initEventListeners();
        
        // Load and display data
        await this.loadAllData();
        
        // Initialize charts
        this.initializeAllCharts();
        
        // Hide loading overlay
        this.hideLoading();
        
        // Add cleanup on page unload to prevent memory leaks
        window.addEventListener('beforeunload', () => this.cleanup());
        
        // Add periodic chart size monitoring to prevent infinite growth
        this.startChartSizeMonitoring();
        
        console.log('✅ Financial Report App initialized successfully');
    }
    
    startChartSizeMonitoring() {
        // Disabled chart size monitoring as it's causing console warnings
        // Charts are now properly constrained via CSS
        console.log('Chart size monitoring disabled - using CSS constraints instead');
    }
    
    cleanup() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }

    initEventListeners() {
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Export functionality
        const exportBtn = document.getElementById('exportReport');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }

        // Refresh functionality
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        // Disabled resize handler to prevent chart growth issues
        // TODO: Re-implement with proper bounds checking
        /*
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                Object.values(this.charts).forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }, 250);
        });
        */
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
        this.isLoading = true;
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
        this.isLoading = false;
    }

    async loadAllData() {
        try {
            console.log('📊 Loading comprehensive financial data...');
            
            // Load comprehensive report data
            const response = await fetch('../api/comprehensive_report_data.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.data = result.data;
                console.log('✅ Comprehensive data loaded successfully:', this.data);
                
                // Update all UI elements with real data
                this.updateHeroStats();
                this.updateExecutiveSummary();
                this.updateFinancialHealth();
                this.updateReportPeriod();
                this.updateMetrics();
                this.updateRecommendations();
                this.updateInsights();
                this.updateGoals();
                this.updatePredictions();
                this.updateBenchmarks();
                this.updateActionItems();
                this.updateCategoryPerformance();
                
            } else {
                console.error('❌ API returned error:', result.message);
                this.showErrorState(result.message);
            }
        } catch (error) {
            console.error('❌ Error loading data:', error);
            this.showErrorState('Failed to load financial data');
        }
    }

    updateHeroStats() {
        const healthScore = this.data.financial_health?.health_score || 87;
        const totalInsights = this.data.recommendations?.length || 12;
        const categoriesAnalyzed = Object.keys(this.data.expense_analysis?.category_breakdown || {}).length || 8;

        this.updateElement('[data-metric="health-score"]', healthScore);
        this.updateElement('[data-metric="total-insights"]', totalInsights);
        this.updateElement('[data-metric="categories-analyzed"]', categoriesAnalyzed);
    }

    updateExecutiveSummary() {
        const healthData = this.data.financial_health || {};
        const income = healthData.monthly_income || 6500;
        const expenses = healthData.total_expenses || 4850;
        const savings = healthData.net_savings || (income - expenses);
        const savingsRate = healthData.savings_rate || 0;

        this.updateElement('[data-metric="total-income"]', `₵${income.toLocaleString()}`);
        this.updateElement('[data-metric="total-expenses"]', `₵${expenses.toLocaleString()}`);
        this.updateElement('[data-metric="net-savings"]', `₵${savings.toLocaleString()}`);
        this.updateElement('[data-metric="savings-rate"]', `${savingsRate}%`);

        // Update change indicators using trend data
        const incomeData = this.data.income_analysis || {};
        const expenseData = this.data.expense_analysis || {};
        
        this.updateElement('[data-metric="total-income"] + .summary-change', 
            incomeData.trend_percentage ? `${incomeData.trend_percentage > 0 ? '+' : ''}${incomeData.trend_percentage}%` : '+5.2%');
        this.updateElement('[data-metric="total-expenses"] + .summary-change', 
            expenseData.trend_percentage ? `${expenseData.trend_percentage > 0 ? '+' : ''}${expenseData.trend_percentage}%` : '+2.1%');
        this.updateElement('[data-metric="net-savings"] + .summary-change', '+12.8%');
        this.updateElement('[data-metric="savings-rate"] + .summary-change', '+3.4%');
    }

    updateFinancialHealth() {
        const healthData = this.data.financial_health || {};
        const healthScore = healthData.health_score || 87;
        
        // Update health score circle
        this.updateHealthScoreCircle(healthScore);
        
        // Update breakdown scores from real data
        const breakdown = healthData.health_breakdown || {};
        const breakdownScores = {
            emergency: Math.round(breakdown.emergency_fund_score || 85),
            expenses: Math.round(breakdown.expense_management_score || 78),
            savings: Math.round(breakdown.savings_rate_score || 92),
            goals: Math.round(breakdown.goal_progress_score || 88)
        };

        Object.entries(breakdownScores).forEach(([key, score]) => {
            this.updateElement(`[data-score="${key}"]`, '', (el) => {
                el.style.width = `${score}%`;
            });
            this.updateElement(`[data-value="${key}"]`, `${score}%`);
        });
    }

    updateCategoryPerformance() {
        const budgetData = this.data.budget_performance || {};
        const categoryPerformance = budgetData.category_performance || [];
        
        const container = document.getElementById('categoryPerformance');
        if (container && categoryPerformance.length > 0) {
            container.innerHTML = categoryPerformance.map(category => {
                const budgeted = parseFloat(category.budgeted_amount) || 0;
                const spent = parseFloat(category.actual_spent) || 0;
                const percentage = budgeted > 0 ? (spent / budgeted * 100) : 0;
                const status = percentage > 100 ? 'over-budget' : percentage > 80 ? 'warning' : 'on-track';
                
                return `
                    <div class="performance-item ${status}">
                        <div class="perf-category">${category.category_name}</div>
                        <div class="perf-bar">
                            <div class="perf-fill" style="width: ${Math.min(percentage, 100)}%"></div>
                        </div>
                        <div class="perf-values">
                            <span class="perf-spent">₵${spent.toLocaleString()}</span>
                            <span class="perf-budget">/ ₵${budgeted.toLocaleString()}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    updateHealthScoreCircle(score) {
        const circle = document.getElementById('healthScoreCircle');
        const number = document.getElementById('healthScoreNumber');
        
        if (circle && number) {
            const circumference = 2 * Math.PI * 50; // radius = 50
            const offset = circumference - (score / 100) * circumference;
            
            setTimeout(() => {
                circle.style.strokeDashoffset = offset;
                number.textContent = score;
            }, 500);
        }
    }

    updateReportPeriod() {
        const now = new Date();
        const monthName = now.toLocaleString('default', { month: 'long' });
        const year = now.getFullYear();
        
        this.updateElement('#reportPeriod', `${monthName} ${year} Financial Report`);
    }

    updateMetrics() {
        // Use real data for trends
        const incomeData = this.data.income_analysis || {};
        const expenseData = this.data.expense_analysis || {};
        const healthData = this.data.financial_health || {};
        
        this.updateElement('[data-metric="income-trend"]', 
            incomeData.trend_percentage ? `${incomeData.trend_percentage > 0 ? '+' : ''}${incomeData.trend_percentage}%` : '0%');
        this.updateElement('[data-metric="expense-trend"]', 
            expenseData.trend_percentage ? `${expenseData.trend_percentage > 0 ? '+' : ''}${expenseData.trend_percentage}%` : '0%');
        this.updateElement('[data-metric="net-flow"]', 
            healthData.net_savings ? `₵${healthData.net_savings.toLocaleString()}` : '₵0');
        
        // Update budget breakdown percentages with user's actual allocation
        const budgetData = this.data.budget_performance || {};
        const userAllocation = budgetData.user_allocation;
        
        if (userAllocation) {
            // Update the dynamic values
            this.updateElement('[data-value="needs-percentage"]', `${userAllocation.needs_percentage}%`);
            this.updateElement('[data-value="wants-percentage"]', `${userAllocation.wants_percentage}%`);
            this.updateElement('[data-value="savings-percentage"]', `${userAllocation.savings_percentage}%`);
            
            // Update the labels and title to show user's actual rule
            const needsPercent = userAllocation.needs_percentage;
            const wantsPercent = userAllocation.wants_percentage;
            const savingsPercent = userAllocation.savings_percentage;
            
            // Update title
            this.updateElement('#budgetRuleTitle', `${needsPercent}/${wantsPercent}/${savingsPercent} Budget Rule Analysis`);
            
            // Update legend labels
            this.updateElement('#needsLabel', `Needs (${needsPercent}%)`);
            this.updateElement('#wantsLabel', `Wants (${wantsPercent}%)`);
            this.updateElement('#savingsLabel', `Savings (${savingsPercent}%)`);
        } else {
            // Fallback to default 50/30/20
            this.updateElement('[data-value="needs-percentage"]', '50%');
            this.updateElement('[data-value="wants-percentage"]', '30%');
            this.updateElement('[data-value="savings-percentage"]', '20%');
            
            this.updateElement('#budgetRuleTitle', '50/30/20 Budget Rule Analysis');
            this.updateElement('#needsLabel', 'Needs (50%)');
            this.updateElement('#wantsLabel', 'Wants (30%)');
            this.updateElement('#savingsLabel', 'Savings (20%)');
        }
    }

    updateRecommendations() {
        const recommendations = this.data.recommendations || [
            {
                title: "Build Emergency Fund",
                description: "Increase your emergency fund to cover 6 months of expenses",
                icon: "🛡️"
            },
            {
                title: "Optimize Spending",
                description: "Reduce dining out expenses by 15% to save ₵320/month",
                icon: "💰"
            },
            {
                title: "Investment Opportunity",
                description: "Consider investing 10% of income in diversified portfolio",
                icon: "📈"
            }
        ];

        const container = document.getElementById('healthRecommendations');
        if (container) {
            container.innerHTML = recommendations.map(rec => `
                <div class="recommendation-item">
                    <div class="rec-icon">${rec.icon || '💡'}</div>
                    <div class="rec-content">
                        <div class="rec-title">${rec.title}</div>
                        <div class="rec-description">${rec.description}</div>
                    </div>
                </div>
            `).join('');
        }
    }

    updateInsights() {
        const spendingData = this.data.spending_analytics || {};
        const peakDay = spendingData.peak_spending_day || 'Friday';
        const expenseData = this.data.expense_analysis || {};
        const topCategory = expenseData.highest_category?.category || 'Entertainment';
        
        const insights = [
            {
                title: "Peak Spending Day",
                description: `You spend the most on ${peakDay}s compared to other weekdays`,
                icon: "📅"
            },
            {
                title: "Category Alert",
                description: `${topCategory} expenses are your highest spending category`,
                icon: "🎭"
            },
            {
                title: "Savings Opportunity",
                description: "Review subscription services for potential savings",
                icon: "💡"
            }
        ];

        const container = document.getElementById('spendingInsights');
        if (container) {
            container.innerHTML = insights.map(insight => `
                <div class="insight-item">
                    <div class="insight-icon">${insight.icon}</div>
                    <div class="insight-content">
                        <div class="insight-title">${insight.title}</div>
                        <div class="insight-description">${insight.description}</div>
                    </div>
                </div>
            `).join('');
        }
    }

    updateGoals() {
        const goalsData = this.data.goals_progress || {};
        const goals = goalsData.goals || [
            {
                goal_name: "Emergency Fund",
                current_amount: 8500,
                target_amount: 15000,
                progress_percentage: 57
            },
            {
                goal_name: "Vacation Savings",
                current_amount: 2300,
                target_amount: 4000,
                progress_percentage: 58
            },
            {
                goal_name: "New Car",
                current_amount: 12000,
                target_amount: 25000,
                progress_percentage: 48
            }
        ];

        const container = document.getElementById('goalsList');
        if (container) {
            container.innerHTML = goals.map(goal => `
                <div class="goal-item">
                    <div class="goal-header">
                        <span class="goal-name">${goal.goal_name}</span>
                        <span class="goal-progress">${Math.round(goal.progress_percentage)}%</span>
                    </div>
                    <div class="goal-bar">
                        <div class="goal-fill" style="width: ${goal.progress_percentage}%"></div>
                    </div>
                    <div class="goal-details-text">
                        <span class="goal-current">₵${parseFloat(goal.current_amount).toLocaleString()}</span>
                        <span class="goal-target">/ ₵${parseFloat(goal.target_amount).toLocaleString()}</span>
                    </div>
                </div>
            `).join('');
        }
    }

    updatePredictions() {
        const forecastData = this.data.trends_forecasts || {};
        const incomeData = this.data.income_analysis || {};
        const expenseData = this.data.expense_analysis || {};
        
        const predictions = [
            {
                title: "6-Month Savings Projection",
                confidence: `Based on current trend: ${forecastData.savings_forecast?.confidence || 'High'} Confidence`,
                icon: "📊"
            },
            {
                title: "Emergency Fund Complete",
                confidence: `Est. completion timeline available`,
                icon: "🎯"
            },
            {
                title: "Income Trend Analysis",
                confidence: `${forecastData.income_forecast?.trend || 'Stable'} pattern detected`,
                icon: "📈"
            }
        ];

        const container = document.getElementById('aiPredictions');
        if (container) {
            container.innerHTML = predictions.map(pred => `
                <div class="prediction-item">
                    <div class="pred-icon">${pred.icon}</div>
                    <div class="pred-content">
                        <div class="pred-title">${pred.title}</div>
                        <div class="pred-confidence">${pred.confidence}</div>
                    </div>
                </div>
            `).join('');
        }

        // Update scenario values using real data
        const monthlyIncome = this.data.financial_health?.monthly_income || 6500;
        const monthlyExpenses = this.data.financial_health?.total_expenses || 4850;
        
        this.updateElement('[data-scenario="reduce-expenses"]', `+₵${Math.round(monthlyExpenses * 0.1)}/month`);
        this.updateElement('[data-scenario="increase-income"]', `+₵${Math.round(monthlyIncome * 0.05)}/month`);
        this.updateElement('[data-scenario="emergency-fund"]', 
            `${Math.round((monthlyExpenses * 6) / (monthlyIncome - monthlyExpenses))} months`);
    }

    updateBenchmarks() {
        const benchmarkData = this.data.benchmarks || {};
        
        const benchmarks = {
            'savings-rate': { 
                your: benchmarkData.savings_rate?.your_rate || 25, 
                avg: benchmarkData.savings_rate?.average || 15 
            },
            'emergency-fund': { 
                your: benchmarkData.emergency_fund?.your_months || 4.2, 
                avg: benchmarkData.emergency_fund?.minimum || 3 
            },
            'expense-ratio': { 
                your: benchmarkData.expense_ratio?.your_ratio || 75, 
                avg: benchmarkData.expense_ratio?.average || 70 
            }
        };

        Object.entries(benchmarks).forEach(([key, data]) => {
            this.updateElement(`[data-your="${key}"]`, 
                key === 'emergency-fund' ? `${data.your} months` : `${data.your}%`);
            this.updateElement(`[data-avg="${key}"]`, 
                key === 'emergency-fund' ? `${data.avg} months` : `${data.avg}%`);
            
            // Update performance bars
            const performanceEl = document.querySelector(`[data-performance="${key}"]`);
            if (performanceEl) {
                const percentage = key === 'emergency-fund' ? 
                    (data.your / 6) * 100 : 
                    key === 'expense-ratio' ? 
                        100 - (data.your - 50) : // Inverse for expense ratio
                        data.your;
                performanceEl.style.width = `${Math.min(Math.max(percentage, 0), 100)}%`;
            }
        });
    }

    updateActionItems() {
        const recommendations = this.data.recommendations || [];
        
        // Group recommendations by priority
        const actionItems = {
            high: recommendations.filter(r => r.priority === 'high'),
            medium: recommendations.filter(r => r.priority === 'medium'),
            optimization: recommendations.filter(r => r.priority === 'low')
        };

        // Add default items if no recommendations available
        if (actionItems.high.length === 0) {
            actionItems.high = [
                {
                    title: "Review and optimize high-expense categories",
                    description: "Focus on reducing dining and entertainment costs",
                    impact: "Save ₵400+/month"
                }
            ];
        }

        if (actionItems.medium.length === 0) {
            actionItems.medium = [
                {
                    title: "Negotiate subscription services",
                    description: "Review and cancel unused subscriptions",
                    impact: "Save ₵150/month"
                }
            ];
        }

        if (actionItems.optimization.length === 0) {
            actionItems.optimization = [
                {
                    title: "Implement 24-hour purchase rule",
                    description: "Wait 24 hours before non-essential purchases",
                    impact: "Reduce impulse buying"
                }
            ];
        }

        Object.entries(actionItems).forEach(([priority, items]) => {
            const containerId = priority === 'high' ? 'highPriorityActions' :
                               priority === 'medium' ? 'mediumPriorityActions' : 'optimizationActions';
            const container = document.getElementById(containerId);
            
            if (container) {
                container.innerHTML = items.map((item, index) => `
                    <div class="action-item">
                        <div class="action-checkbox">
                            <input type="checkbox" id="${priority}_action_${index}">
                            <label for="${priority}_action_${index}"></label>
                        </div>
                        <div class="action-content">
                            <div class="action-title">${item.title}</div>
                            <div class="action-description">${item.description || item.action}</div>
                            <div class="action-impact">Impact: ${item.impact}</div>
                        </div>
                    </div>
                `).join('');
            }
        });
    }

    initializeAllCharts() {
        if (this.chartsInitialized) {
            console.log('📈 Charts already initialized, skipping...');
            return;
        }
        
        console.log('📈 Initializing all charts...');
        
        // Initialize each chart with error handling
        this.safeInitChart('incomeExpenseChart', () => this.initIncomeExpenseChart());
        this.safeInitChart('budgetBreakdownChart', () => this.initBudgetBreakdownChart());
        this.safeInitChart('spendingDistributionChart', () => this.initSpendingDistributionChart());
        this.safeInitChart('weeklySpendingChart', () => this.initWeeklySpendingChart());
        this.safeInitChart('goalsProgressChart', () => this.initGoalsProgressChart());
        this.safeInitChart('savingsGrowthChart', () => this.initSavingsGrowthChart());
        this.safeInitChart('forecastChart', () => this.initForecastChart());
        this.safeInitChart('benchmarkChart', () => this.initBenchmarkChart());
        
        this.chartsInitialized = true;
    }

    safeInitChart(chartId, initFunction) {
        try {
            // Clean up existing chart if it exists
            const chartKey = chartId.replace('Chart', '');
            if (this.charts[chartKey]) {
                this.charts[chartKey].destroy();
                delete this.charts[chartKey];
            }
            
            // Set canvas dimensions before creating chart
            const canvas = document.getElementById(chartId);
            if (canvas) {
                canvas.style.width = '100%';
                canvas.style.height = '400px';
                canvas.style.maxWidth = '100%';
                canvas.style.maxHeight = '400px';
                canvas.width = canvas.offsetWidth;
                canvas.height = 400;
            }
            
            initFunction();
            console.log(`✅ ${chartId} initialized successfully`);
            
            // Double-check canvas size after initialization
            if (canvas) {
                canvas.style.height = '400px';
                canvas.style.maxHeight = '400px';
            }
        } catch (error) {
            console.error(`❌ Error initializing ${chartId}:`, error);
        }
    }

    initIncomeExpenseChart() {
        const ctx = document.getElementById('incomeExpenseChart');
        if (!ctx) return;

        // Use real data if available
        const incomeData = this.data.income_analysis || {};
        const expenseData = this.data.expense_analysis || {};
        
        let monthlyData;
        if (incomeData.monthly_data && expenseData.monthly_data) {
            // Use real data
            const incomeMonths = incomeData.monthly_data || [];
            const expenseMonths = expenseData.monthly_data || [];
            
            // Create a map for easier lookup
            const expenseMap = {};
            expenseMonths.forEach(month => {
                expenseMap[month.month] = parseFloat(month.total_expenses);
            });
            
            monthlyData = {
                labels: incomeMonths.map(month => {
                    const date = new Date(month.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short' });
                }),
                income: incomeMonths.map(month => parseFloat(month.total_income)),
                expenses: incomeMonths.map(month => expenseMap[month.month] || 0)
            };
        } else {
            // Fallback to generated data
            monthlyData = this.generateMonthlyTrendData();
        }
        
        this.charts.incomeExpense = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [
                    {
                        label: 'Income',
                        data: monthlyData.income,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: monthlyData.expenses,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: false, // Disable auto-responsiveness
                maintainAspectRatio: false,
                animation: {
                    duration: 0 // Disable animations
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ₵${context.raw.toLocaleString()}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => `₵${value.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }

    initBudgetBreakdownChart() {
        const ctx = document.getElementById('budgetBreakdownChart');
        if (!ctx) return;

        // Get user's actual budget allocation
        const budgetData = this.data.budget_performance || {};
        const userAllocation = budgetData.user_allocation;
        
        let needsPercent, wantsPercent, savingsPercent;
        
        if (userAllocation) {
            needsPercent = parseInt(userAllocation.needs_percentage) || 50;
            wantsPercent = parseInt(userAllocation.wants_percentage) || 30;
            savingsPercent = parseInt(userAllocation.savings_percentage) || 20;
        } else {
            // Fallback to defaults
            needsPercent = 50;
            wantsPercent = 30;
            savingsPercent = 20;
        }

        this.charts.budgetBreakdown = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [`Needs (${needsPercent}%)`, `Wants (${wantsPercent}%)`, `Savings (${savingsPercent}%)`],
                datasets: [{
                    data: [needsPercent, wantsPercent, savingsPercent],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(139, 92, 246)'
                    ],
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: false, // DISABLED to prevent infinite growth
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${context.parsed}%`
                        }
                    }
                }
            }
        });
    }

    initSpendingDistributionChart() {
        const ctx = document.getElementById('spendingDistributionChart');
        if (!ctx) return;

        const expenseData = this.data.expense_analysis || {};
        const categoryBreakdown = expenseData.category_breakdown || [];
        
        let categoryLabels, categoryData;
        
        if (categoryBreakdown.length > 0) {
            categoryLabels = categoryBreakdown.map(cat => cat.category);
            categoryData = categoryBreakdown.map(cat => parseFloat(cat.total_amount));
        } else {
            // Fallback data
            categoryLabels = ['Food', 'Transportation', 'Entertainment', 'Utilities', 'Shopping', 'Healthcare', 'Other'];
            categoryData = [1200, 800, 600, 450, 380, 320, 250];
        }

        this.charts.spendingDistribution = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(139, 92, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(6, 182, 212)',
                        'rgb(168, 85, 247)'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: false, // Disable auto-responsiveness
                maintainAspectRatio: false,
                animation: {
                    duration: 0 // Disable animations completely
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ₵${context.raw.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }

    initWeeklySpendingChart() {
        const ctx = document.getElementById('weeklySpendingChart');
        if (!ctx) return;

        const spendingData = this.data.spending_analytics || {};
        const weeklyPatterns = spendingData.weekly_patterns || [];
        
        let weeklyData;
        if (weeklyPatterns.length > 0) {
            // Map database data to chart format
            const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            weeklyData = dayOrder.map(day => {
                const dayData = weeklyPatterns.find(p => p.day_name === day);
                return dayData ? parseFloat(dayData.avg_amount) : 0;
            });
        } else {
            // Friday typically higher spending (fallback)
            weeklyData = [120, 95, 110, 130, 185, 220, 160];
        }

        this.charts.weeklySpending = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Daily Average',
                    data: weeklyData,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: false, // Disable auto-responsiveness
                maintainAspectRatio: false,
                animation: {
                    duration: 0 // Disable animations completely
                },
                layout: {
                    padding: 0
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `Average: ₵${context.raw.toLocaleString()}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: undefined, // Let chart auto-scale
                        ticks: {
                            callback: (value) => `₵${value}`
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 0
                        }
                    }
                }
            }
        });
    }

    initGoalsProgressChart() {
        const ctx = document.getElementById('goalsProgressChart');
        if (!ctx) return;

        const goalsData = this.data.goals_progress || {};
        const goals = goalsData.goals || [];
        
        let labels, data, colors;
        
        if (goals.length > 0) {
            labels = goals.map(goal => goal.goal_name);
            data = goals.map(goal => Math.round(goal.progress_percentage));
            colors = goals.map((_, index) => {
                const colorList = [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ];
                return colorList[index % colorList.length];
            });
        } else {
            // Fallback data
            labels = ['Emergency Fund', 'Vacation', 'New Car', 'Investment'];
            data = [57, 58, 48, 23];
            colors = [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(245, 158, 11, 0.8)'
            ];
        }

        this.charts.goalsProgress = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Progress %',
                    data: data,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false, // DISABLED to prevent infinite growth
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `Progress: ${context.raw}%`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: (value) => `${value}%`
                        }
                    }
                }
            }
        });
    }

    initSavingsGrowthChart() {
        const ctx = document.getElementById('savingsGrowthChart');
        if (!ctx) return;

        const savingsData = this.data.savings_analysis || {};
        const monthlySavings = savingsData.monthly_savings || [];
        
        let growthData;
        if (monthlySavings.length > 0) {
            growthData = {
                labels: monthlySavings.map(month => {
                    const date = new Date(month.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short' });
                }),
                values: monthlySavings.map(month => parseFloat(month.cumulative_savings || month.net_savings))
            };
        } else {
            growthData = this.generateSavingsGrowthData();
        }

        this.charts.savingsGrowth = new Chart(ctx, {
            type: 'line',
            data: {
                labels: growthData.labels,
                datasets: [{
                    label: 'Savings Balance',
                    data: growthData.values,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: false, // DISABLED to prevent infinite growth
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `Balance: ₵${context.raw.toLocaleString()}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => `₵${value.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }

    initForecastChart() {
        const ctx = document.getElementById('forecastChart');
        if (!ctx) return;

        const forecastData = this.generateForecastData();

        this.charts.forecast = new Chart(ctx, {
            type: 'line',
            data: {
                labels: forecastData.labels,
                datasets: [
                    {
                        label: 'Historical',
                        data: forecastData.historical,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Projected',
                        data: forecastData.projected,
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderDash: [5, 5],
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: false, // DISABLED to prevent infinite growth
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ₵${context.raw?.toLocaleString() || 'N/A'}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => `₵${value.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }

    initBenchmarkChart() {
        const ctx = document.getElementById('benchmarkChart');
        if (!ctx) return;

        this.charts.benchmark = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Savings Rate', 'Emergency Fund', 'Debt Ratio', 'Investment %', 'Budget Adherence'],
                datasets: [
                    {
                        label: 'Your Performance',
                        data: [85, 70, 90, 60, 88],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        pointBackgroundColor: 'rgb(59, 130, 246)'
                    },
                    {
                        label: 'Average',
                        data: [65, 50, 70, 45, 75],
                        borderColor: 'rgb(156, 163, 175)',
                        backgroundColor: 'rgba(156, 163, 175, 0.1)',
                        pointBackgroundColor: 'rgb(156, 163, 175)'
                    }
                ]
            },
            options: {
                responsive: false, // DISABLED to prevent infinite growth
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });
    }

    // Data generation helpers
    generateMonthlyTrendData() {
        const months = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const baseIncome = 6500;
        const baseExpenses = 4850;
        
        return {
            labels: months,
            income: months.map(() => baseIncome + (Math.random() * 400 - 200)),
            expenses: months.map(() => baseExpenses + (Math.random() * 600 - 300))
        };
    }

    generateWeeklySpendingData() {
        // Friday typically higher spending
        return [120, 95, 110, 130, 185, 220, 160];
    }

    generateSavingsGrowthData() {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        let balance = 15000;
        const values = [balance];
        
        for (let i = 1; i < months.length; i++) {
            balance += 800 + (Math.random() * 400 - 200);
            values.push(balance);
        }
        
        return { labels: months, values };
    }

    generateForecastData() {
        const labels = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'];
        const historical = [15000, 15800, 16200, null, null, null, null];
        const projected = [null, null, 16200, 17000, 17850, 18600, 19400];
        
        return { labels, historical, projected };
    }

    // Utility functions
    updateElement(selector, content, callback = null) {
        const element = document.querySelector(selector);
        if (element) {
            if (callback) {
                callback(element);
            } else {
                element.textContent = content;
            }
        }
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'ocean' ? 'forest' : 'ocean';
        document.body.setAttribute('data-theme', this.currentTheme === 'forest' ? 'forest' : '');
        
        // Update charts with new theme colors
        this.updateChartThemes();
    }

    updateChartThemes() {
        // This would update chart colors based on theme
        // Implementation depends on specific color requirements
        console.log(`🎨 Updating charts for ${this.currentTheme} theme`);
    }

    exportReport() {
        // Prepare data for export
        const reportData = this.prepareExportData();
        
        // Show export modal with options
        window.exportUtility.showExportModal(reportData, {
            title: 'Financial Report',
            filename: `financial-report-${new Date().toISOString().split('T')[0]}`,
            formats: ['pdf', 'excel', 'csv'],
            element: document.querySelector('.report-container') || document.body
        });
    }

    prepareExportData() {
        const data = [];
        
        // Financial Health Metrics
        const healthData = this.data.financial_health || {};
        data.push({
            Category: 'Financial Health',
            Metric: 'Health Score',
            Value: healthData.health_score || 0,
            Unit: '/100'
        });
        data.push({
            Category: 'Financial Health',
            Metric: 'Monthly Income',
            Value: healthData.monthly_income || 0,
            Unit: 'GHS'
        });
        data.push({
            Category: 'Financial Health',
            Metric: 'Total Expenses',
            Value: healthData.total_expenses || 0,
            Unit: 'GHS'
        });
        data.push({
            Category: 'Financial Health',
            Metric: 'Net Savings',
            Value: healthData.net_savings || 0,
            Unit: 'GHS'
        });
        data.push({
            Category: 'Financial Health',
            Metric: 'Savings Rate',
            Value: healthData.savings_rate || 0,
            Unit: '%'
        });

        // Budget Allocation
        const budgetData = this.data.budget_performance || {};
        const userAllocation = budgetData.user_allocation;
        if (userAllocation) {
            data.push({
                Category: 'Budget Allocation',
                Metric: 'Needs Percentage',
                Value: userAllocation.needs_percentage || 0,
                Unit: '%'
            });
            data.push({
                Category: 'Budget Allocation',
                Metric: 'Wants Percentage',
                Value: userAllocation.wants_percentage || 0,
                Unit: '%'
            });
            data.push({
                Category: 'Budget Allocation',
                Metric: 'Savings Percentage',
                Value: userAllocation.savings_percentage || 0,
                Unit: '%'
            });
        }

        // Expense Categories
        const expenseData = this.data.expense_analysis || {};
        const categoryBreakdown = expenseData.category_breakdown || [];
        categoryBreakdown.forEach(category => {
            data.push({
                Category: 'Expense Breakdown',
                Metric: category.category,
                Value: category.total_amount || 0,
                Unit: 'GHS'
            });
        });

        return data;
    }

    exportReport() {
        // Show export options to user
        const exportOptions = [
            { label: 'PDF Report', action: () => this.exportToPDF() },
            { label: 'Excel Data', action: () => this.exportToExcel() },
            { label: 'CSV Data', action: () => this.exportToCSV() }
        ];

        // Create a simple modal for export options
        const modal = document.createElement('div');
        modal.className = 'export-modal';
        modal.innerHTML = `
            <div class="export-modal-content">
                <h3><i class="fas fa-download"></i> Export Financial Report</h3>
                <div class="export-options">
                    ${exportOptions.map(option => `
                        <button class="export-option-btn" data-action="${option.label}">
                            <i class="fas fa-file-${option.label.includes('PDF') ? 'pdf' : option.label.includes('Excel') ? 'excel' : 'csv'}"></i>
                            ${option.label}
                        </button>
                    `).join('')}
                </div>
                <button class="export-cancel-btn"><i class="fas fa-times"></i> Cancel</button>
            </div>
        `;

        document.body.appendChild(modal);

        // Add event listeners
        modal.querySelector('.export-cancel-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        exportOptions.forEach((option, index) => {
            const btn = modal.querySelectorAll('.export-option-btn')[index];
            btn.addEventListener('click', () => {
                option.action();
                document.body.removeChild(modal);
            });
        });
    }

    async exportToPDF() {
        console.log('📄 Exporting report to PDF...');
        
        try {
            // Wait for any pending animations or dynamic content
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            const element = document.querySelector('.report-container');
            
            // Temporarily add export optimization class
            element.classList.add('export-optimized');
            
            // Temporarily modify styles for better PDF rendering
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'visible';
            
            const canvas = await html2canvas(element, {
                scale: 2, // Higher scale for better quality
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                height: element.scrollHeight,
                width: element.scrollWidth,
                scrollX: 0,
                scrollY: 0,
                windowWidth: element.scrollWidth,
                windowHeight: element.scrollHeight,
                onclone: (clonedDoc) => {
                    // Ensure all styles are applied in the cloned document
                    const clonedElement = clonedDoc.querySelector('.report-container');
                    if (clonedElement) {
                        clonedElement.style.width = element.scrollWidth + 'px';
                        clonedElement.style.height = 'auto';
                        clonedElement.classList.add('export-optimized');
                        
                        // Force all chart canvases to render properly
                        const charts = clonedElement.querySelectorAll('canvas');
                        charts.forEach(chart => {
                            chart.style.maxHeight = 'none';
                            chart.style.height = 'auto';
                            chart.style.background = 'white';
                        });
                        
                        // Ensure gradient backgrounds are white for export
                        const gradientElements = clonedElement.querySelectorAll('.gradient-bg, .hero-section');
                        gradientElements.forEach(el => {
                            el.style.background = 'white';
                            el.style.color = '#1e293b';
                        });
                    }
                }
            });
            
            // Restore original styles
            document.body.style.overflow = originalOverflow;
            element.classList.remove('export-optimized');
            
            const imgData = canvas.toDataURL('image/png', 1.0);
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const pageHeight = 295;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            const today = new Date().toISOString().split('T')[0];
            pdf.save(`financial-report-${today}.pdf`);
            console.log('✅ PDF exported successfully');
        } catch (error) {
            console.error('❌ Error exporting PDF:', error);
            alert('Error exporting PDF. Please try again.');
        }
    }

    async exportToExcel() {
        console.log('📊 Exporting to Excel...');
        
        try {
            // Prepare data for Excel export
            const exportData = {
                financial_health: this.data.financial_health,
                budget_performance: this.data.budget_performance,
                expense_analysis: this.data.expense_analysis,
                income_analysis: this.data.income_analysis
            };

            // Create a simple Excel-compatible format (CSV with tabs)
            let excelContent = "Financial Report Export\n\n";
            
            // Financial Health Section
            if (exportData.financial_health) {
                excelContent += "Financial Health\n";
                excelContent += `Health Score\t${exportData.financial_health.health_score}\n`;
                excelContent += `Monthly Income\t₵${exportData.financial_health.monthly_income}\n`;
                excelContent += `Total Expenses\t₵${exportData.financial_health.total_expenses}\n`;
                excelContent += `Net Savings\t₵${exportData.financial_health.net_savings}\n`;
                excelContent += `Savings Rate\t${exportData.financial_health.savings_rate}%\n\n`;
            }

            // Budget Performance Section
            if (exportData.budget_performance?.user_allocation) {
                const allocation = exportData.budget_performance.user_allocation;
                excelContent += "Budget Allocation\n";
                excelContent += `Needs\t${allocation.needs_percentage}%\t₵${allocation.needs_amount}\n`;
                excelContent += `Wants\t${allocation.wants_percentage}%\t₵${allocation.wants_amount}\n`;
                excelContent += `Savings\t${allocation.savings_percentage}%\t₵${allocation.savings_amount}\n\n`;
            }

            // Expense Analysis
            if (exportData.expense_analysis?.category_breakdown) {
                excelContent += "Expense Categories\n";
                excelContent += "Category\tAmount\n";
                exportData.expense_analysis.category_breakdown.forEach(cat => {
                    excelContent += `${cat.category}\t₵${cat.total_amount}\n`;
                });
            }

            // Create and download file
            const blob = new Blob([excelContent], { type: 'application/vnd.ms-excel' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `financial-report-${new Date().toISOString().split('T')[0]}.xls`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            console.log('✅ Excel export completed');
        } catch (error) {
            console.error('❌ Error exporting to Excel:', error);
            alert('Error exporting to Excel. Please try again.');
        }
    }

    async exportToCSV() {
        console.log('📄 Exporting to CSV...');
        
        try {
            let csvContent = "Financial Report CSV Export\n\n";
            
            // Add summary data
            if (this.data.financial_health) {
                const health = this.data.financial_health;
                csvContent += "Metric,Value\n";
                csvContent += `Health Score,${health.health_score}\n`;
                csvContent += `Monthly Income,${health.monthly_income}\n`;
                csvContent += `Total Expenses,${health.total_expenses}\n`;
                csvContent += `Net Savings,${health.net_savings}\n`;
                csvContent += `Savings Rate,${health.savings_rate}%\n\n`;
            }

            // Add expense breakdown if available
            if (this.data.expense_analysis?.category_breakdown) {
                csvContent += "Expense Categories\n";
                csvContent += "Category,Amount\n";
                this.data.expense_analysis.category_breakdown.forEach(cat => {
                    csvContent += `${cat.category},${cat.total_amount}\n`;
                });
            }

            // Create and download CSV
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `financial-report-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            console.log('✅ CSV export completed');
        } catch (error) {
            console.error('❌ Error exporting to CSV:', error);
            alert('Error exporting to CSV. Please try again.');
        }
    }

    async refreshData() {
        console.log('🔄 Refreshing all data...');
        this.showLoading();
        
        try {
            await this.loadAllData();
            
            // Update metrics without refreshing charts to prevent loops
            this.updateHeroStats();
            this.updateExecutiveSummary();
            this.updateFinancialHealth();
            this.updateRecommendations();
            
            console.log('✅ Data refreshed successfully');
        } catch (error) {
            console.error('❌ Error refreshing data:', error);
        } finally {
            this.hideLoading();
        }
    }

    handleActionItemChange(checkbox) {
        const actionItem = checkbox.closest('.action-item');
        if (actionItem) {
            if (checkbox.checked) {
                actionItem.style.opacity = '0.7';
                actionItem.style.textDecoration = 'line-through';
            } else {
                actionItem.style.opacity = '1';
                actionItem.style.textDecoration = 'none';
            }
        }
    }

    showErrorState(message) {
        console.error('💥 Showing error state:', message);
        
        // Show error message in hero section
        const heroText = document.querySelector('.hero-text p');
        if (heroText) {
            heroText.innerHTML = `<span style="color: #ef4444;">⚠️ ${message}</span>`;
        }
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.reportApp = new FinancialReportApp();
});

// Export for global access
window.FinancialReportApp = FinancialReportApp;
