<?php
/**
 * Test script to debug savings goals appearing in budget
 */
require_once 'config/connection.php';

// Use test user ID 2
$userId = 2;

echo "<h2>Debugging Savings Goals in Budget Integration</h2>";

echo "<h3>1. Check if user has personal goals:</h3>";
$stmt = $conn->prepare("
    SELECT id, goal_name, target_amount, current_amount, budget_category_id, auto_save_enabled, save_amount 
    FROM personal_goals 
    WHERE user_id = ? AND is_completed = 0
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($goals)) {
    echo "<p><strong>No active goals found for user $userId</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Goal Name</th><th>Target</th><th>Current</th><th>Budget Category ID</th><th>Auto Save</th><th>Save Amount</th></tr>";
    foreach ($goals as $goal) {
        echo "<tr>";
        echo "<td>" . $goal['id'] . "</td>";
        echo "<td>" . $goal['goal_name'] . "</td>";
        echo "<td>₵" . number_format($goal['target_amount'], 2) . "</td>";
        echo "<td>₵" . number_format($goal['current_amount'], 2) . "</td>";
        echo "<td>" . ($goal['budget_category_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($goal['auto_save_enabled'] ? 'Yes' : 'No') . "</td>";
        echo "<td>₵" . number_format($goal['save_amount'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>2. Check budget categories for savings:</h3>";
$stmt = $conn->prepare("
    SELECT id, name, category_type, icon, color, budget_limit 
    FROM budget_categories 
    WHERE user_id = ? AND category_type = 'savings' AND is_active = TRUE
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($categories)) {
    echo "<p><strong>No savings budget categories found for user $userId</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Icon</th><th>Color</th><th>Budget Limit</th></tr>";
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td>" . $cat['id'] . "</td>";
        echo "<td>" . $cat['name'] . "</td>";
        echo "<td>" . $cat['category_type'] . "</td>";
        echo "<td>" . $cat['icon'] . "</td>";
        echo "<td>" . $cat['color'] . "</td>";
        echo "<td>₵" . number_format($cat['budget_limit'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>3. Test the new budget data API query:</h3>";
$stmt = $conn->prepare("
    SELECT 
        pg.id as goal_id,
        pg.goal_name,
        pg.current_amount,
        pg.target_amount,
        COALESCE(pg.auto_save_enabled, 0) as auto_save_enabled,
        COALESCE(pg.save_amount, 0) as save_amount,
        bc.id as category_id,
        bc.name as category_name,
        bc.icon,
        bc.color,
        COALESCE(SUM(pgc.amount), 0) as actual_contributed,
        COUNT(pgc.id) as contribution_count
    FROM personal_goals pg
    LEFT JOIN budget_categories bc ON pg.budget_category_id = bc.id
    LEFT JOIN personal_goal_contributions pgc ON pg.id = pgc.goal_id 
        AND MONTH(pgc.contribution_date) = MONTH(CURRENT_DATE())
        AND YEAR(pgc.contribution_date) = YEAR(CURRENT_DATE())
    WHERE pg.user_id = ? 
        AND pg.is_completed = 0
        AND COALESCE(pg.status, 'active') = 'active'
    GROUP BY pg.id, pg.goal_name, pg.current_amount, pg.target_amount, 
             pg.auto_save_enabled, pg.save_amount, bc.id, bc.name, bc.icon, bc.color
    ORDER BY pg.goal_name
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$budgetApiResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($budgetApiResult)) {
    echo "<p><strong>No results from budget API query for user $userId</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Goal ID</th><th>Goal Name</th><th>Category ID</th><th>Category Name</th><th>Current</th><th>Target</th><th>Auto Save</th><th>Monthly Contributions</th></tr>";
    foreach ($budgetApiResult as $row) {
        echo "<tr>";
        echo "<td>" . $row['goal_id'] . "</td>";
        echo "<td>" . $row['goal_name'] . "</td>";
        echo "<td>" . ($row['category_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['category_name'] ?? 'NULL') . "</td>";
        echo "<td>₵" . number_format($row['current_amount'], 2) . "</td>";
        echo "<td>₵" . number_format($row['target_amount'], 2) . "</td>";
        echo "<td>₵" . number_format($row['save_amount'], 2) . "</td>";
        echo "<td>₵" . number_format($row['actual_contributed'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>4. Test current month contributions:</h3>";
$stmt = $conn->prepare("
    SELECT pgc.*, pg.goal_name 
    FROM personal_goal_contributions pgc
    JOIN personal_goals pg ON pgc.goal_id = pg.id
    WHERE pgc.user_id = ? 
    AND MONTH(pgc.contribution_date) = MONTH(CURRENT_DATE())
    AND YEAR(pgc.contribution_date) = YEAR(CURRENT_DATE())
    ORDER BY pgc.contribution_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$contributions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($contributions)) {
    echo "<p><strong>No contributions this month for user $userId</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Goal</th><th>Amount</th><th>Date</th><th>Source</th><th>Description</th></tr>";
    foreach ($contributions as $contrib) {
        echo "<tr>";
        echo "<td>" . $contrib['goal_name'] . "</td>";
        echo "<td>₵" . number_format($contrib['amount'], 2) . "</td>";
        echo "<td>" . $contrib['contribution_date'] . "</td>";
        echo "<td>" . $contrib['source'] . "</td>";
        echo "<td>" . $contrib['description'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>5. Test budget allocation:</h3>";
$stmt = $conn->prepare("
    SELECT * FROM personal_budget_allocation 
    WHERE user_id = ? AND is_active = 1 
    ORDER BY created_at DESC LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$allocation = $stmt->get_result()->fetch_assoc();

if (!$allocation) {
    echo "<p><strong>No budget allocation found for user $userId</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Salary</th><th>Savings %</th><th>Savings Amount</th><th>Needs %</th><th>Wants %</th></tr>";
    echo "<tr>";
    echo "<td>₵" . number_format($allocation['monthly_salary'], 2) . "</td>";
    echo "<td>" . $allocation['savings_percentage'] . "%</td>";
    echo "<td>₵" . number_format($allocation['savings_amount'], 2) . "</td>";
    echo "<td>" . $allocation['needs_percentage'] . "%</td>";
    echo "<td>" . $allocation['wants_percentage'] . "%</td>";
    echo "</tr>";
    echo "</table>";
}
?>
