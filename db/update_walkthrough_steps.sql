-- Update walkthrough steps with better element targeting and comprehensive budget page guidance
-- This script updates the walkthrough_steps table with improved steps

-- Clear existing initial setup steps
DELETE FROM walkthrough_steps WHERE walkthrough_type = 'initial_setup';

-- Insert improved initial setup walkthrough steps with TEMPLATE SELECTION requirement
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
-- Step 1: Setup Income (Dashboard)
('initial_setup', 'setup_income', 1, '/personal-dashboard', '.setup-salary-btn-hero', 'Set Up Your Income', 'Welcome to Budgetly! Let\'s start by setting up your income. This is essential for budget planning and goal tracking. Click the "Set Up Income" button to begin.', 1, 0, 1),

-- Step 2: Configure Salary (Salary Page)  
('initial_setup', 'configure_salary', 2, '/salary', '#salaryActionBtn', 'Configure Your Salary', 'Great! Now enter your salary details. Fill in your income information to help us calculate your available budget and auto-save for your goals.', 1, 0, 1),

-- Step 3: Choose Budget Template (Budget Page) - REQUIRED
('initial_setup', 'choose_template', 3, '/budgets', 'button[onclick="showBudgetTemplateModal()"]', 'Choose a Budget Template', 'Perfect! Now you MUST choose a budget template to get started. Templates help organize your finances with proven strategies. Click "Use Template" to see options.', 1, 0, 1),

-- Step 4: Complete Template Selection (Inside Modal) - REQUIRED
('initial_setup', 'select_template', 4, '/budgets', '.template-card', 'Select Your Template', 'Choose one of these popular budget templates. The 50/30/20 rule is great for beginners - 50% needs, 30% wants, 20% savings. Click on a template to select it.', 1, 0, 1);

-- Clear existing help guide steps and insert specific element targeting
DELETE FROM walkthrough_steps WHERE walkthrough_type = 'help_guide';

-- Dashboard Help Guide - Specific Elements Only
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'dashboard_income_card', 1, '/personal-dashboard', '.income-card', 'Monthly Income', 'This card shows your total monthly income. Click "Set Up Income" if you need to add or modify your salary and other income sources.', 0, 1, 1),
('help_guide', 'dashboard_expenses_card', 2, '/personal-dashboard', '.expenses-card', 'Monthly Expenses', 'View your total monthly expenses here. This updates automatically as you add expenses throughout the month.', 0, 1, 1),
('help_guide', 'dashboard_balance_card', 3, '/personal-dashboard', '.balance-card', 'Available Balance', 'This shows how much money you have left after expenses and savings. Keep this positive to stay on budget!', 0, 1, 1),
('help_guide', 'dashboard_quick_actions', 4, '/personal-dashboard', '.quick-actions', 'Quick Actions', 'Use these buttons to quickly add expenses, record income, or create savings goals without navigating to other pages.', 0, 1, 1),

-- Budget Page Help Guide - Specific Elements
('help_guide', 'budget_template_btn', 1, '/budgets', 'button[onclick="showBudgetTemplateModal()"]', 'Budget Templates', 'Click here to use pre-built budget templates. Great for getting started quickly with proven budgeting strategies.', 0, 1, 1),
('help_guide', 'budget_categories_list', 2, '/budgets', '.budget-categories-container', 'Your Budget Categories', 'These are your spending categories with monthly limits. Green means on track, yellow is a warning, red means over budget.', 0, 1, 1),
('help_guide', 'budget_add_category', 3, '/budgets', '.add-category-btn', 'Add New Category', 'Create custom budget categories for your specific needs. Set spending limits to control your expenses.', 0, 1, 1),
('help_guide', 'budget_allocation_summary', 4, '/budgets', '.allocation-summary', 'Budget Summary', 'See how your total budget is allocated across Needs, Wants, and Savings. Aim for a balanced approach.', 0, 1, 1),

-- Salary Page Help Guide - Specific Elements
('help_guide', 'salary_setup_btn', 1, '/salary', '#salaryActionBtn', 'Set Up Primary Salary', 'Click here to configure your main salary. Enter your monthly amount, pay frequency, and any deductions.', 0, 1, 1),
('help_guide', 'salary_overview_card', 2, '/salary', '.salary-overview', 'Salary Overview', 'View your current salary settings and payment schedule. This affects your budget calculations.', 0, 1, 1),
('help_guide', 'salary_additional_income', 3, '/salary', '.additional-income-section', 'Additional Income', 'Add other income sources like freelance work, side jobs, or investments for a complete financial picture.', 0, 1, 1),

-- Expenses Page Help Guide - Specific Elements  
('help_guide', 'expenses_add_btn', 1, '/personal-expense', '.add-expense-btn', 'Add New Expense', 'Click to record a new expense. Choose the right category to track where your money goes.', 0, 1, 1),
('help_guide', 'expenses_recent_list', 2, '/personal-expense', '.recent-expenses', 'Recent Expenses', 'View and manage your recent expenses. You can edit or delete entries if needed.', 0, 1, 1),
('help_guide', 'expenses_category_filter', 3, '/personal-expense', '.category-filters', 'Filter by Category', 'Use these filters to view expenses by specific categories and analyze spending patterns.', 0, 1, 1),
('help_guide', 'expenses_monthly_summary', 4, '/personal-expense', '.monthly-summary', 'Monthly Summary', 'See your total expenses for the current month and compare against your budget.', 0, 1, 1),

-- Savings Page Help Guide - Specific Elements
('help_guide', 'savings_create_goal', 1, '/savings', '.create-goal-btn', 'Create Savings Goal', 'Start your savings journey by creating a specific goal. Set a target amount and deadline.', 0, 1, 1),
('help_guide', 'savings_goals_list', 2, '/savings', '.goals-container', 'Your Savings Goals', 'Track progress toward all your goals. Each goal shows current amount and target.', 0, 1, 1),
('help_guide', 'savings_contribute_btn', 3, '/savings', '.contribute-btn', 'Add Money to Goals', 'Make contributions to your savings goals. Every little bit helps you reach your targets!', 0, 1, 1),
('help_guide', 'savings_progress_bars', 4, '/savings', '.goal-progress', 'Progress Tracking', 'Visual progress bars show how close you are to reaching each savings goal.', 0, 1, 1),

-- Analytics Page Help Guide - Specific Elements
('help_guide', 'analytics_spending_chart', 1, '/analytics', '.spending-chart', 'Spending Analysis', 'Charts show your spending patterns over time. Look for trends and areas to improve.', 0, 1, 1),
('help_guide', 'analytics_category_breakdown', 2, '/analytics', '.category-breakdown', 'Category Breakdown', 'See what percentage of your budget goes to each category. Identify your biggest expenses.', 0, 1, 1),
('help_guide', 'analytics_insights', 3, '/analytics', '.insights-panel', 'Financial Insights', 'Get personalized recommendations to improve your financial health and reach goals faster.', 0, 1, 1);
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'dashboard_overview', 1, '/personal-dashboard', '.financial-overview', 'Financial Overview', 'This section shows your monthly income, expenses, and available balance. It updates automatically as you add income and track expenses.', 0, 1, 1),
('help_guide', 'dashboard_income', 2, '/personal-dashboard', '.income-section', 'Income Management', 'Here you can view and manage your income sources. Click "Set Up Income" to add or modify your salary and other income streams.', 0, 1, 1),
('help_guide', 'dashboard_expenses', 3, '/personal-dashboard', '.expenses-section', 'Expense Tracking', 'This section shows your recent expenses and spending trends. You can add new expenses or review your spending patterns here.', 0, 1, 1),
('help_guide', 'dashboard_goals', 4, '/personal-dashboard', '.goals-section', 'Savings Goals', 'Track your savings goals and progress here. Set financial targets and monitor how well you\'re sticking to your savings plan.', 0, 1, 1);

-- Budget Page Help Guide
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'budget_templates', 1, '/budgets', 'button[onclick="showBudgetTemplateModal()"]', 'Budget Templates', 'Use our pre-built budget templates to get started quickly. Templates include common categories like housing, food, transportation, and entertainment.', 0, 1, 1),
('help_guide', 'budget_categories', 2, '/budgets', '.category-section', 'Budget Categories', 'These are your spending categories. Each category has a monthly limit to help you control spending. You can create custom categories or modify existing ones.', 0, 1, 1),
('help_guide', 'budget_creation', 3, '/budgets', '#createCategoryBtn', 'Create Categories', 'Click here to create new budget categories. You can set spending limits, choose icons, and organize your expenses the way that works best for you.', 0, 1, 1),
('help_guide', 'budget_monitoring', 4, '/budgets', '.budget-progress', 'Budget Monitoring', 'This shows how much you\'ve spent in each category versus your budget. Green means you\'re on track, yellow is a warning, and red means you\'ve exceeded the limit.', 0, 1, 1);

-- Salary Page Help Guide  
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'salary_primary', 1, '/salary', '.primary-salary-card', 'Primary Salary', 'This is your main income source. Enter your monthly salary, frequency, and any automatic deductions like taxes or insurance.', 0, 1, 1),
('help_guide', 'salary_additional', 2, '/salary', '.additional-income', 'Additional Income', 'Add other income sources like freelance work, investments, or side jobs. This gives you a complete picture of your monthly income.', 0, 1, 1),
('help_guide', 'salary_autosave', 3, '/salary', '.auto-save-section', 'Auto-Save Settings', 'Set up automatic savings from your salary. This helps you save consistently without having to remember to transfer money manually.', 0, 1, 1);

-- Expenses Page Help Guide
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'expenses_add', 1, '/expenses', '.add-expense-btn', 'Add Expenses', 'Click here to record new expenses. You can categorize expenses, add descriptions, and even upload receipts for better tracking.', 0, 1, 1),
('help_guide', 'expenses_list', 2, '/expenses', '.expenses-list', 'Expense History', 'View all your recorded expenses here. You can filter by date, category, or amount to analyze your spending patterns.', 0, 1, 1),
('help_guide', 'expenses_categories', 3, '/expenses', '.category-filter', 'Category Filtering', 'Use these filters to view expenses by category. This helps you understand where your money is going and identify areas to cut back.', 0, 1, 1);

-- Savings Page Help Guide
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'savings_goals', 1, '/savings', '.savings-goals', 'Savings Goals', 'Set and track your savings goals here. Whether it\'s an emergency fund, vacation, or major purchase, having clear goals keeps you motivated.', 0, 1, 1),
('help_guide', 'savings_progress', 2, '/savings', '.progress-tracking', 'Progress Tracking', 'Monitor how close you are to reaching your savings goals. The progress bars show your current savings versus your target amount.', 0, 1, 1),
('help_guide', 'savings_contributions', 3, '/savings', '.add-savings-btn', 'Add Contributions', 'Record money you\'ve saved toward your goals. Regular contributions, even small ones, add up quickly over time.', 0, 1, 1);

-- Analytics Page Help Guide
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
('help_guide', 'analytics_overview', 1, '/analytics', '.analytics-dashboard', 'Analytics Dashboard', 'Get insights into your spending habits with charts and graphs. See where your money goes and identify trends over time.', 0, 1, 1),
('help_guide', 'analytics_trends', 2, '/analytics', '.spending-trends', 'Spending Trends', 'These charts show how your spending changes over time. Look for patterns and seasonal variations in your expenses.', 0, 1, 1),
('help_guide', 'analytics_categories', 3, '/analytics', '.category-breakdown', 'Category Breakdown', 'See what percentage of your budget goes to each category. This helps you understand your spending priorities and make adjustments.', 0, 1, 1);
