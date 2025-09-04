-- Update walkthrough steps with better element targeting and comprehensive budget page guidance
-- This script updates the walkthrough_steps table with improved steps

-- Clear existing steps
DELETE FROM walkthrough_steps WHERE walkthrough_type IN ('initial_setup', 'help_guide');

-- Insert improved initial setup walkthrough steps
INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip, is_active) VALUES
-- Step 1: Setup Income (Dashboard)
('initial_setup', 'setup_income', 1, '/personal-dashboard', '.setup-salary-btn-hero', 'Set Up Your Income', 'Welcome to Budgetly! Let\'s start by setting up your income. This is essential for budget planning and goal tracking. Click the "Set Up Income" button to begin.', 1, 0, 1),

-- Step 2: Configure Salary (Salary Page)  
('initial_setup', 'configure_salary', 2, '/salary', '#salaryActionBtn', 'Configure Your Salary', 'Great! Now enter your salary details. Fill in your income information to help us calculate your available budget and auto-save for your goals.', 1, 0, 1),

-- Step 3: Setup Budget (Budget Page)
('initial_setup', 'setup_budget', 3, '/budgets', '.budget-actions', 'Set Up Your Budget', 'Perfect! Now let\'s set up your budget. You can use our templates or create custom categories. This will help you track your spending and stay on target.', 0, 1, 1);

-- Insert comprehensive help guide steps for each page

-- Dashboard Help Guide
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
