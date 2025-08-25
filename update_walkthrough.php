<?php
require_once 'config/connection.php';

echo "Updating walkthrough configuration...\n";

try {
    // First, clear existing walkthrough steps
    $conn->query("DELETE FROM walkthrough_steps");
    echo "✅ Cleared existing walkthrough steps\n";
    
    // Insert updated initial setup walkthrough steps
    $stmt = $conn->prepare("
        INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip) VALUES
        ('initial_setup', 'setup_income', 1, 'templates/personal-dashboard.php', '.setup-salary-btn-hero', 'Set Up Your Income', 'Welcome! Let\'s start by setting up your income. This is essential for budget planning and goal tracking. Click \"Set Up Income\" to begin.', 1, 0),
        ('initial_setup', 'configure_salary', 2, 'templates/salary.php', '#salaryActionBtn', 'Configure Your Salary', 'Great! Now enter your salary details. This will help us calculate your available budget and auto-save for your goals.', 1, 0),
        ('initial_setup', 'setup_budget', 3, 'templates/budget.php', 'button[onclick=\"showBudgetTemplateModal()\"]', 'Set Up Your Budget', 'Perfect! Now let\'s set up your budget. You can click \"Use Template\" to choose from our popular templates, or you can skip this step to create a custom budget later.', 0, 1)
    ");
    $stmt->execute();
    echo "✅ Added initial setup walkthrough steps\n";
    
    // Insert page-specific help tours
    $stmt = $conn->prepare("
        INSERT INTO walkthrough_steps (walkthrough_type, step_name, step_order, page_url, target_element, title, content, action_required, can_skip) VALUES
        -- Dashboard page tour
        ('help_guide', 'dashboard_overview', 1, 'templates/personal-dashboard.php', '.financial-overview', 'Financial Overview', 'This shows your monthly income, expenses, and available balance. It updates automatically as you add income and expenses.', 0, 1),
        ('help_guide', 'dashboard_goals', 2, 'templates/personal-dashboard.php', '.goals-section', 'Your Savings Goals', 'Track your progress towards savings goals. You can add new goals or contribute to existing ones from here.', 0, 1),
        ('help_guide', 'dashboard_quick_actions', 3, 'templates/personal-dashboard.php', '.quick-actions', 'Quick Actions', 'Access common tasks like adding expenses, updating income, or viewing detailed reports.', 0, 1),
        
        -- Budget page tour  
        ('help_guide', 'budget_categories', 1, 'templates/budget.php', '.budget-categories-container', 'Budget Categories', 'Manage your spending categories here. Set limits and track how much you spend in each category.', 0, 1),
        ('help_guide', 'budget_templates', 2, 'templates/budget.php', 'button[onclick=\"showBudgetTemplateModal()\"]', 'Budget Templates', 'Use pre-made budget templates based on popular budgeting methods like 50/30/20 rule.', 0, 1),
        ('help_guide', 'budget_allocation', 3, 'templates/budget.php', '.budget-allocation', 'Budget Allocation', 'See how your income is divided between needs, wants, and savings based on your budget plan.', 0, 1),
        
        -- Salary page tour
        ('help_guide', 'salary_setup', 1, 'templates/salary.php', '#salaryActionBtn', 'Salary Management', 'Set up and manage your primary income source. This affects your budget calculations.', 0, 1),
        ('help_guide', 'salary_schedule', 2, 'templates/salary.php', '.schedule-section', 'Payment Schedule', 'View your upcoming salary payments and track your income over time.', 0, 1)
    ");
    $stmt->execute();
    echo "✅ Added page-specific help guides\n";
    
    echo "\n🎉 Walkthrough configuration updated successfully!\n";
    echo "\nNew features:\n";
    echo "- Initial setup: Income → Salary → Budget (no completion message)\n";
    echo "- Page help: Blue help button on each page for guided tours\n";
    echo "- Help tours are skippable and can be stopped anytime\n";
    echo "- Salary setup still happens only once during initial setup\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
