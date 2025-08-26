<?php
//analytics.php
session_start();

// Check session timeout
require_once '../includes/session_timeout_middleware.php';
$session_check = checkSessionTimeout();
if (!$session_check['valid']) {
    header('Location: ../login.php?timeout=1');
    exit;
}

require_once '../config/connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    header("Location: login.php");
    exit;
}

$family_id = $_SESSION['family_id'];

// Helper function to safely format numbers
function safe_number_format($value, $decimals = 2)
{
    return number_format((float)($value ?? 0), $decimals);
}

// Helper function to safely convert to float
function safe_float($value)
{
    return (float)($value ?? 0);
}

// Get available years with data
function getAvailableYears($conn, $family_id)
{
    $stmt = $conn->prepare("
        SELECT DISTINCT YEAR(contribution_date) as year
        FROM family_contributions 
        WHERE family_id = ?
        UNION
        SELECT DISTINCT cycle_year as year
        FROM monthly_cycles 
        WHERE family_id = ?
        ORDER BY year DESC
    ");
    $stmt->bind_param("ii", $family_id, $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get yearly overview statistics
function getYearlyOverview($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(contrib.total_contributions, 0) as total_contributions,
            COALESCE(expense.total_expenses, 0) as total_expenses,
            COALESCE(contrib.contribution_count, 0) as contribution_count,
            COALESCE(expense.expense_count, 0) as expense_count,
            COALESCE(contrib.total_contributions, 0) - COALESCE(expense.total_expenses, 0) as net_savings,
            COALESCE(contrib.active_contributors, 0) as active_contributors
        FROM (
            SELECT 
                SUM(amount) as total_contributions,
                COUNT(*) as contribution_count,
                COUNT(DISTINCT CASE 
                    WHEN contributor_type = 'user' THEN member_id 
                    ELSE member_only_id 
                END) as active_contributors
            FROM family_contributions 
            WHERE family_id = ? AND YEAR(contribution_date) = ?
        ) contrib
        CROSS JOIN (
            SELECT 
                COALESCE(SUM(amount), 0) as total_expenses,
                COUNT(*) as expense_count
            FROM family_expenses 
            WHERE family_id = ? AND YEAR(expense_date) = ?
        ) expense
    ");
    $stmt->bind_param("iiii", $family_id, $year, $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // Ensure all values are properly set
    return [
        'total_contributions' => safe_float($result['total_contributions']),
        'total_expenses' => safe_float($result['total_expenses']),
        'contribution_count' => (int)($result['contribution_count'] ?? 0),
        'expense_count' => (int)($result['expense_count'] ?? 0),
        'net_savings' => safe_float($result['net_savings']),
        'active_contributors' => (int)($result['active_contributors'] ?? 0)
    ];
}

// Get monthly breakdown for a year
function getMonthlyBreakdown($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            MONTH(date_col) as month,
            MONTHNAME(date_col) as month_name,
            COALESCE(SUM(CASE WHEN type = 'contribution' THEN amount ELSE 0 END), 0) as contributions,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expenses,
            COUNT(CASE WHEN type = 'contribution' THEN 1 END) as contribution_count,
            COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count,
            COALESCE(SUM(CASE WHEN type = 'contribution' THEN amount ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as net_amount
        FROM (
            SELECT contribution_date as date_col, amount, 'contribution' as type
            FROM family_contributions 
            WHERE family_id = ? AND YEAR(contribution_date) = ?
            UNION ALL
            SELECT expense_date as date_col, amount, 'expense' as type
            FROM family_expenses 
            WHERE family_id = ? AND YEAR(expense_date) = ?
        ) combined
        GROUP BY MONTH(date_col), MONTHNAME(date_col)
        ORDER BY MONTH(date_col)
    ");
    $stmt->bind_param("iiii", $family_id, $year, $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ensure all numeric values are properly formatted
    foreach ($result as &$row) {
        $row['contributions'] = safe_float($row['contributions']);
        $row['expenses'] = safe_float($row['expenses']);
        $row['net_amount'] = safe_float($row['net_amount']);
        $row['contribution_count'] = (int)($row['contribution_count'] ?? 0);
        $row['expense_count'] = (int)($row['expense_count'] ?? 0);
    }

    return $result;
}

// Get cycle performance data
function getCyclePerformance($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            mc.*,
            COALESCE(COUNT(mmp.id), 0) as total_members,
            COALESCE(SUM(CASE WHEN mmp.is_completed = 1 THEN 1 ELSE 0 END), 0) as completed_members,
            COALESCE(SUM(mmp.target_amount), 0) as total_target,
            COALESCE(SUM(mmp.contributed_amount), 0) as total_contributed,
            COALESCE(SUM(CASE WHEN mmp.is_completed = 0 THEN mmp.target_amount - mmp.contributed_amount ELSE 0 END), 0) as total_deficit,
            COALESCE(ROUND(AVG(CASE WHEN mmp.target_amount > 0 THEN (mmp.contributed_amount / mmp.target_amount) * 100 ELSE 0 END), 2), 0) as avg_completion_rate
        FROM monthly_cycles mc
        LEFT JOIN member_monthly_performance mmp ON mc.id = mmp.cycle_id
        WHERE mc.family_id = ? AND mc.cycle_year = ?
        GROUP BY mc.id
        ORDER BY mc.cycle_month_num
    ");
    $stmt->bind_param("ii", $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ensure all numeric values are properly formatted
    foreach ($result as &$row) {
        $row['total_target'] = safe_float($row['total_target']);
        $row['total_contributed'] = safe_float($row['total_contributed']);
        $row['total_deficit'] = safe_float($row['total_deficit']);
        $row['avg_completion_rate'] = safe_float($row['avg_completion_rate']);
        $row['total_members'] = (int)($row['total_members'] ?? 0);
        $row['completed_members'] = (int)($row['completed_members'] ?? 0);
    }

    return $result;
}

// Get member performance across all cycles
function getMemberPerformanceHistory($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN mmp.member_type = 'user' THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                WHEN mmp.member_type = 'member' THEN CONCAT(COALESCE(fmo.first_name, ''), ' ', COALESCE(fmo.last_name, ''))
                ELSE 'Unknown Member'
            END as member_name,
            mmp.member_type,
            mmp.member_id,
            mmp.member_only_id,
            mc.cycle_month,
            mc.cycle_month_num,
            COALESCE(mmp.target_amount, 0) as target_amount,
            COALESCE(mmp.contributed_amount, 0) as contributed_amount,
            mmp.is_completed,
            CASE WHEN COALESCE(mmp.target_amount, 0) > 0 THEN ROUND((COALESCE(mmp.contributed_amount, 0) / mmp.target_amount) * 100, 2) ELSE 0 END as completion_percentage,
            CASE 
                WHEN mmp.member_type = 'user' THEN COALESCE(fm.role, 'member')
                WHEN mmp.member_type = 'member' THEN COALESCE(fmo.role, 'other')
                ELSE 'unknown'
            END as role
        FROM member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        LEFT JOIN family_members fm ON mmp.member_id = fm.id AND mmp.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mmp.member_only_id = fmo.id AND mmp.member_type = 'member'
        WHERE mc.family_id = ? AND mc.cycle_year = ?
        ORDER BY member_name, mc.cycle_month_num
    ");
    $stmt->bind_param("ii", $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ensure all numeric values are properly formatted
    foreach ($result as &$row) {
        $row['target_amount'] = safe_float($row['target_amount']);
        $row['contributed_amount'] = safe_float($row['contributed_amount']);
        $row['completion_percentage'] = safe_float($row['completion_percentage']);
    }

    return $result;
}

// Get expense category breakdown
function getExpenseCategoryBreakdown($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            expense_type,
            COUNT(*) as transaction_count,
            COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(AVG(amount), 0) as avg_amount,
            COALESCE(MIN(amount), 0) as min_amount,
            COALESCE(MAX(amount), 0) as max_amount,
            CASE 
                WHEN (SELECT SUM(amount) FROM family_expenses WHERE family_id = ? AND YEAR(expense_date) = ?) > 0
                THEN ROUND((COALESCE(SUM(amount), 0) / (SELECT SUM(amount) FROM family_expenses WHERE family_id = ? AND YEAR(expense_date) = ?)) * 100, 2)
                ELSE 0
            END as percentage_of_total
        FROM family_expenses 
        WHERE family_id = ? AND YEAR(expense_date) = ?
        GROUP BY expense_type
        ORDER BY total_amount DESC
    ");
    $stmt->bind_param("iiiiii", $family_id, $year, $family_id, $year, $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ensure all numeric values are properly formatted
    foreach ($result as &$row) {
        $row['total_amount'] = safe_float($row['total_amount']);
        $row['avg_amount'] = safe_float($row['avg_amount']);
        $row['min_amount'] = safe_float($row['min_amount']);
        $row['max_amount'] = safe_float($row['max_amount']);
        $row['percentage_of_total'] = safe_float($row['percentage_of_total']);
        $row['transaction_count'] = (int)($row['transaction_count'] ?? 0);
    }

    return $result;
}

// Get debt history and trends
function getDebtHistory($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            mdh.*,
            CASE 
                WHEN mdh.member_type = 'user' THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                WHEN mdh.member_type = 'member' THEN CONCAT(COALESCE(fmo.first_name, ''), ' ', COALESCE(fmo.last_name, ''))
                ELSE 'Unknown Member'
            END as member_name,
            CASE 
                WHEN mdh.member_type = 'user' THEN COALESCE(fm.role, 'member')
                WHEN mdh.member_type = 'member' THEN COALESCE(fmo.role, 'other')
                ELSE 'unknown'
            END as role
        FROM member_debt_history mdh
        LEFT JOIN family_members fm ON mdh.member_id = fm.id AND mdh.member_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON mdh.member_only_id = fmo.id AND mdh.member_type = 'member'
        WHERE mdh.family_id = ? AND YEAR(STR_TO_DATE(CONCAT(mdh.cycle_month, '-01'), '%Y-%m-%d')) = ?
        ORDER BY mdh.cycle_month, member_name
    ");
    $stmt->bind_param("ii", $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ensure all numeric values are properly formatted
    foreach ($result as &$row) {
        $row['deficit_amount'] = safe_float($row['deficit_amount']);
        $row['target_amount'] = safe_float($row['target_amount']);
        $row['contributed_amount'] = safe_float($row['contributed_amount']);
    }

    return $result;
}

// Get top contributors
function getTopContributors($conn, $family_id, $year)
{
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN fc.contributor_type = 'user' THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                WHEN fc.contributor_type = 'member' THEN CONCAT(COALESCE(fmo.first_name, ''), ' ', COALESCE(fmo.last_name, ''))
                ELSE 'Unknown Contributor'
            END as contributor_name,
            fc.contributor_type,
            COALESCE(SUM(fc.amount), 0) as total_contributed,
            COUNT(fc.id) as contribution_count,
            COALESCE(AVG(fc.amount), 0) as avg_contribution,
            COALESCE(MIN(fc.amount), 0) as min_contribution,
            COALESCE(MAX(fc.amount), 0) as max_contribution,
            MIN(fc.contribution_date) as first_contribution,
            MAX(fc.contribution_date) as last_contribution
        FROM family_contributions fc
        LEFT JOIN family_members fm ON fc.member_id = fm.id AND fc.contributor_type = 'user'
        LEFT JOIN users u ON fm.user_id = u.id
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id AND fc.contributor_type = 'member'
        WHERE fc.family_id = ? AND YEAR(fc.contribution_date) = ?
        GROUP BY contributor_name, fc.contributor_type
        HAVING total_contributed > 0
        ORDER BY total_contributed DESC
    ");
    $stmt->bind_param("ii", $family_id, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ensure all numeric values are properly formatted
    foreach ($result as &$row) {
        $row['total_contributed'] = safe_float($row['total_contributed']);
        $row['avg_contribution'] = safe_float($row['avg_contribution']);
        $row['min_contribution'] = safe_float($row['min_contribution']);
        $row['max_contribution'] = safe_float($row['max_contribution']);
        $row['contribution_count'] = (int)($row['contribution_count'] ?? 0);
    }

    return $result;
}

// Get comparison with previous year
function getYearComparison($conn, $family_id, $current_year)
{
    $previous_year = $current_year - 1;

    $stmt = $conn->prepare("
        SELECT 
            'current' as period,
            COALESCE(SUM(fc.amount), 0) as total_contributions,
            COALESCE(SUM(fe.amount), 0) as total_expenses,
            COUNT(DISTINCT fc.id) as contribution_count,
            COUNT(DISTINCT fe.id) as expense_count
        FROM family_contributions fc
        LEFT JOIN family_expenses fe ON fc.family_id = fe.family_id AND YEAR(fe.expense_date) = ?
        WHERE fc.family_id = ? AND YEAR(fc.contribution_date) = ?
        
        UNION ALL
        
        SELECT 
            'previous' as period,
            COALESCE(SUM(fc.amount), 0) as total_contributions,
            COALESCE(SUM(fe.amount), 0) as total_expenses,
            COUNT(DISTINCT fc.id) as contribution_count,
            COUNT(DISTINCT fe.id) as expense_count
        FROM family_contributions fc
        LEFT JOIN family_expenses fe ON fc.family_id = fe.family_id AND YEAR(fe.expense_date) = ?
        WHERE fc.family_id = ? AND YEAR(fc.contribution_date) = ?
    ");
    $stmt->bind_param("iiiiii", $current_year, $family_id, $current_year, $previous_year, $family_id, $previous_year);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $comparison = [];
    foreach ($results as $row) {
        $comparison[$row['period']] = [
            'total_contributions' => safe_float($row['total_contributions']),
            'total_expenses' => safe_float($row['total_expenses']),
            'contribution_count' => (int)($row['contribution_count'] ?? 0),
            'expense_count' => (int)($row['expense_count'] ?? 0)
        ];
    }

    return $comparison;
}

// Get current selected year or default
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Fetch all data
$available_years = getAvailableYears($conn, $family_id);
$yearly_overview = getYearlyOverview($conn, $family_id, $selected_year);
$monthly_breakdown = getMonthlyBreakdown($conn, $family_id, $selected_year);
$cycle_performance = getCyclePerformance($conn, $family_id, $selected_year);
$member_performance = getMemberPerformanceHistory($conn, $family_id, $selected_year);
$expense_categories = getExpenseCategoryBreakdown($conn, $family_id, $selected_year);
$debt_history = getDebtHistory($conn, $family_id, $selected_year);
$top_contributors = getTopContributors($conn, $family_id, $selected_year);
$year_comparison = getYearComparison($conn, $family_id, $selected_year);

// Get family info
$stmt = $conn->prepare("SELECT family_name FROM family_groups WHERE id = ?");
$stmt->bind_param("i", $family_id);
$stmt->execute();
$family = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($family['family_name']); ?> - Analytics</title>
    <?php include '../includes/favicon.php'; ?>
    <!-- <link rel="stylesheet" href="../public/css/dashboard.css"> -->
    <link rel="stylesheet" href="../public/css/analytics.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

</head>

<body>
    <!-- Snackbar for notifications -->
    <div id="snackbar"></div>

    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    </div>

    <!-- CRITICAL: Sidebar Toggle Button - Must be visible -->
    <button id="sidebarToggle">☰</button>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Family</h1>
            <p>Family Fund</p>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard" class="nav-link">
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
                <a href="expense" class="nav-link">
                    <span class="nav-icon">💸</span>
                    <span>Expenses</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <span class="nav-icon">📊</span>
                    <span>Analytics</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="momo" class="nav-link">
                    <span class="nav-icon">🏦</span>
                    <span>MoMo Account</span>
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
        <!-- Analytics Header -->
        <div class="analytics-header">
            <div class="analytics-title">
                <h2>📊 Family Analytics</h2>
                <p class="analytics-subtitle">Comprehensive insights into family financial performance</p>
            </div>
            <div class="analytics-controls">
                <div class="year-selector">
                    <select id="yearSelect" onchange="changeYear(this.value)">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= $year['year'] ?>" <?= $year['year'] == $selected_year ? 'selected' : '' ?>>
                                <?= $year['year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-secondary" onclick="exportAnalytics()">
                    📤 Export Report
                </button>
                <button class="btn btn-primary" onclick="showDetailedReport()">
                    📋 Detailed Report
                </button>
            </div>
        </div>

        <!-- Year Overview Cards -->
        <div class="year-overview-grid">
            <div class="overview-card total-contributions">
                <div class="overview-icon">💰</div>
                <div class="overview-content">
                    <div class="overview-value">₵<?= safe_number_format($yearly_overview['total_contributions']) ?></div>
                    <div class="overview-label">Total Contributions</div>
                    <div class="overview-subtitle"><?= $yearly_overview['contribution_count'] ?> transactions</div>
                </div>
                <div class="overview-trend">
                    <?php if (isset($year_comparison['current']) && isset($year_comparison['previous'])): ?>
                        <?php
                        $change = $year_comparison['previous']['total_contributions'] > 0
                            ? (($year_comparison['current']['total_contributions'] - $year_comparison['previous']['total_contributions']) / $year_comparison['previous']['total_contributions']) * 100
                            : 0;
                        ?>
                        <span class="trend-value <?= $change >= 0 ? 'positive' : 'negative' ?>">
                            <?= $change >= 0 ? '↗' : '↘' ?> <?= abs(round($change, 1)) ?>%
                        </span>
                        <span class="trend-label">vs last year</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overview-card total-expenses">
                <div class="overview-icon">💸</div>
                <div class="overview-content">
                    <div class="overview-value">₵<?= safe_number_format($yearly_overview['total_expenses']) ?></div>
                    <div class="overview-label">Total Expenses</div>
                    <div class="overview-subtitle"><?= $yearly_overview['expense_count'] ?> transactions</div>
                </div>
                <div class="overview-trend">
                    <?php if (isset($year_comparison['current']) && isset($year_comparison['previous'])): ?>
                        <?php
                        $change = $year_comparison['previous']['total_expenses'] > 0
                            ? (($year_comparison['current']['total_expenses'] - $year_comparison['previous']['total_expenses']) / $year_comparison['previous']['total_expenses']) * 100
                            : 0;
                        ?>
                        <span class="trend-value <?= $change <= 0 ? 'positive' : 'negative' ?>">
                            <?= $change >= 0 ? '↗' : '↘' ?> <?= abs(round($change, 1)) ?>%
                        </span>
                        <span class="trend-label">vs last year</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overview-card net-savings">
                <div class="overview-icon">💎</div>
                <div class="overview-content">
                    <div class="overview-value">₵<?= safe_number_format($yearly_overview['net_savings']) ?></div>
                    <div class="overview-label">Net Savings</div>
                    <div class="overview-subtitle">
                        <?= $yearly_overview['total_contributions'] > 0 ? round(($yearly_overview['net_savings'] / $yearly_overview['total_contributions']) * 100, 1) : 0 ?>% savings rate
                    </div>
                </div>
                <div class="overview-trend">
                    <span class="trend-value <?= $yearly_overview['net_savings'] >= 0 ? 'positive' : 'negative' ?>">
                        <?= $yearly_overview['net_savings'] >= 0 ? '💰' : '⚠️' ?>
                    </span>
                    <span class="trend-label"><?= $yearly_overview['net_savings'] >= 0 ? 'Surplus' : 'Deficit' ?></span>
                </div>
            </div>

            <div class="overview-card active-members">
                <div class="overview-icon">👥</div>
                <div class="overview-content">
                    <div class="overview-value"><?= $yearly_overview['active_contributors'] ?></div>
                    <div class="overview-label">Active Contributors</div>
                    <div class="overview-subtitle"><?= count($cycle_performance) ?> completed cycles</div>
                </div>
                <div class="overview-trend">
                    <span class="trend-value positive">✅</span>
                    <span class="trend-label">Participating</span>
                </div>
            </div>
        </div>

        <!-- Monthly Performance Chart -->
        <div class="chart-section">
            <div class="chart-card monthly-performance">
                <div class="chart-header">
                    <h3>Monthly Performance - <?= $selected_year ?></h3>
                    <div class="chart-legend">
                        <span class="legend-item contributions">
                            <span class="legend-color"></span>
                            Contributions
                        </span>
                        <span class="legend-item expenses">
                            <span class="legend-color"></span>
                            Expenses
                        </span>
                        <span class="legend-item net">
                            <span class="legend-color"></span>
                            Net Savings
                        </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown Table -->
        <div class="data-section">
            <div class="section-header">
                <h3>Monthly Breakdown</h3>
                <div class="section-actions">
                    <button class="btn btn-outline" onclick="showMonthDetails()">
                        📅 Month Details
                    </button>
                </div>
            </div>
            <div class="monthly-breakdown-grid">
                <?php foreach ($monthly_breakdown as $month): ?>
                    <div class="month-card" onclick="showMonthDetail(<?= $month['month'] ?>, '<?= $month['month_name'] ?>')">
                        <div class="month-header">
                            <h4><?= $month['month_name'] ?></h4>
                            <div class="month-number"><?= str_pad($month['month'], 2, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        <div class="month-stats">
                            <div class="month-stat contributions">
                                <div class="stat-value">₵<?= safe_number_format($month['contributions']) ?></div>
                                <div class="stat-label">Contributions (<?= $month['contribution_count'] ?>)</div>
                            </div>
                            <div class="month-stat expenses">
                                <div class="stat-value">₵<?= safe_number_format($month['expenses']) ?></div>
                                <div class="stat-label">Expenses (<?= $month['expense_count'] ?>)</div>
                            </div>
                            <div class="month-stat net <?= $month['net_amount'] >= 0 ? 'positive' : 'negative' ?>">
                                <div class="stat-value">₵<?= safe_number_format($month['net_amount']) ?></div>
                                <div class="stat-label">Net <?= $month['net_amount'] >= 0 ? 'Surplus' : 'Deficit' ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cycle Performance Section -->
        <div class="data-section">
            <div class="section-header">
                <h3>Monthly Cycle Performance</h3>
                <p class="section-subtitle">Track goal completion and member participation across all cycles</p>
            </div>
            <div class="cycle-performance-table">
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Status</th>
                            <th>Target</th>
                            <th>Collected</th>
                            <th>Completion %</th>
                            <th>Members</th>
                            <th>Deficit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cycle_performance as $cycle): ?>
                            <tr class="cycle-row">
                                <td>
                                    <div class="cycle-month">
                                        <strong><?= date('M Y', strtotime($cycle['cycle_month'] . '-01')) ?></strong>
                                        <small><?= $cycle['cycle_month'] ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="cycle-status <?= $cycle['is_closed'] ? 'closed' : 'active' ?>">
                                        <?= $cycle['is_closed'] ? '🔒 Closed' : '🔄 Active' ?>
                                    </span>
                                </td>
                                <td class="amount">₵<?= safe_number_format($cycle['total_target']) ?></td>
                                <td class="amount">₵<?= safe_number_format($cycle['total_contributed']) ?></td>
                                <td>
                                    <div class="completion-indicator">
                                        <div class="completion-bar">
                                            <div class="completion-fill" style="width: <?= $cycle['avg_completion_rate'] ?>%"></div>
                                        </div>
                                        <span class="completion-text"><?= round($cycle['avg_completion_rate'], 1) ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="member-stats">
                                        <span class="completed"><?= $cycle['completed_members'] ?></span> /
                                        <span class="total"><?= $cycle['total_members'] ?></span>
                                    </div>
                                </td>
                                <td class="amount deficit">₵<?= safe_number_format($cycle['total_deficit']) ?></td>
                                <td>
                                    <button class="btn-small btn-outline" onclick="showCycleDetails(<?= $cycle['id'] ?>)">
                                        👁️ View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Member Performance Heatmap -->
        <div class="data-section">
            <div class="section-header">
                <h3>Member Performance Heatmap</h3>
                <p class="section-subtitle">Visual representation of each member's monthly goal completion</p>
            </div>
            <div class="member-heatmap" id="memberHeatmap">
                <!-- Will be generated by JavaScript -->
            </div>
        </div>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <!-- Top Contributors -->
            <div class="analytics-card top-contributors">
                <div class="card-header">
                    <h3>🏆 Top Contributors</h3>
                </div>
                <div class="contributors-list">
                    <?php foreach (array_slice($top_contributors, 0, 5) as $index => $contributor): ?>
                        <div class="contributor-item">
                            <div class="contributor-rank"><?= $index + 1 ?></div>
                            <div class="contributor-info">
                                <div class="contributor-name"><?= htmlspecialchars($contributor['contributor_name']) ?></div>
                                <div class="contributor-stats">
                                    ₵<?= safe_number_format($contributor['total_contributed']) ?> •
                                    <?= $contributor['contribution_count'] ?> contributions
                                </div>
                            </div>
                            <div class="contributor-badge">
                                <?= $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : '🏅')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Expense Categories -->
            <div class="analytics-card expense-breakdown">
                <div class="card-header">
                    <h3>💸 Expense Categories</h3>
                </div>
                <div class="category-list">
                    <?php foreach ($expense_categories as $category): ?>
                        <div class="category-item">
                            <div class="category-info">
                                <div class="category-name">
                                    <?php
                                    $icons = [
                                        'dstv' => '📺',
                                        'wifi' => '📶',
                                        'utilities' => '⚡',
                                        'dining' => '🍽️',
                                        'maintenance' => '🔧',
                                        'other' => '📦'
                                    ];
                                    echo $icons[$category['expense_type']] ?? '📦';
                                    ?>
                                    <?= ucfirst($category['expense_type']) ?>
                                </div>
                                <div class="category-stats">
                                    ₵<?= safe_number_format($category['total_amount']) ?> •
                                    <?= $category['transaction_count'] ?> transactions
                                </div>
                            </div>
                            <div class="category-percentage"><?= $category['percentage_of_total'] ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Debt Overview -->
            <div class="analytics-card debt-overview">
                <div class="card-header">
                    <h3>⚠️ Debt Overview</h3>
                </div>
                <div class="debt-summary">
                    <?php
                    $total_debt = array_sum(array_column($debt_history, 'deficit_amount'));
                    $uncleared_debt = array_sum(array_column(array_filter($debt_history, function ($d) {
                        return !$d['is_cleared'];
                    }), 'deficit_amount'));
                    $members_with_debt = count(array_unique(array_column(array_filter($debt_history, function ($d) {
                        return !$d['is_cleared'];
                    }), 'member_name')));
                    ?>
                    <div class="debt-stat">
                        <div class="debt-value">₵<?= safe_number_format($total_debt) ?></div>
                        <div class="debt-label">Total Debt Recorded</div>
                    </div>
                    <div class="debt-stat outstanding">
                        <div class="debt-value">₵<?= safe_number_format($uncleared_debt) ?></div>
                        <div class="debt-label">Outstanding Debt</div>
                    </div>
                    <div class="debt-stat members">
                        <div class="debt-value"><?= $members_with_debt ?></div>
                        <div class="debt-label">Members with Debt</div>
                    </div>
                </div>
                <?php if (!empty($debt_history)): ?>
                    <div class="recent-debt">
                        <h4>Recent Debt Records</h4>
                        <?php foreach (array_slice($debt_history, 0, 3) as $debt): ?>
                            <div class="debt-item <?= $debt['is_cleared'] ? 'cleared' : 'outstanding' ?>">
                                <div class="debt-member"><?= htmlspecialchars($debt['member_name']) ?></div>
                                <div class="debt-details">
                                    <?= $debt['cycle_month'] ?> • ₵<?= safe_number_format($debt['deficit_amount']) ?>
                                    <?= $debt['is_cleared'] ? ' ✅' : ' ⏳' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Savings Trends -->
            <div class="analytics-card savings-trends">
                <div class="card-header">
                    <h3>💎 Savings Trends</h3>
                </div>
                <div class="savings-chart-container">
                    <canvas id="savingsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Month Detail Modal -->
    <div id="monthDetailModal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3 id="monthDetailTitle">Month Details</h3>
                <span class="close" onclick="closeModal('monthDetailModal')">&times;</span>
            </div>
            <div class="modal-body" id="monthDetailBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Cycle Detail Modal -->
    <div id="cycleDetailModal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3 id="cycleDetailTitle">Cycle Details</h3>
                <span class="close" onclick="closeModal('cycleDetailModal')">&times;</span>
            </div>
            <div class="modal-body" id="cycleDetailBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Pass data to JavaScript -->
    <script>
        window.analyticsData = {
            selectedYear: <?= json_encode($selected_year) ?>,
            familyId: <?= json_encode($family_id) ?>,
            monthlyBreakdown: <?= json_encode($monthly_breakdown) ?>,
            cyclePerformance: <?= json_encode($cycle_performance) ?>,
            memberPerformance: <?= json_encode($member_performance) ?>,
            expenseCategories: <?= json_encode($expense_categories) ?>,
            topContributors: <?= json_encode($top_contributors) ?>,
            debtHistory: <?= json_encode($debt_history) ?>,
            yearlyOverview: <?= json_encode($yearly_overview) ?>
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            initializeSidebar();
        });

function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');

    if (toggleBtn && sidebar && mainContent) {
        
        const handleResize = () => {
            if (window.innerWidth >= 1200) {
                // Desktop behavior - toggle always visible, sidebar starts closed
                toggleBtn.style.display = 'block';
                // Don't auto-open sidebar, let user control it
            } else {
                // Mobile behavior - toggle always visible, sidebar starts closed
                toggleBtn.style.display = 'block';
                // On mobile, if sidebar is open and we resize, close it
                if (sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    mainContent.style.marginLeft = '0';
                }
            }
        };

        // Initial setup - sidebar closed, toggle visible
        sidebar.classList.remove('open');
        mainContent.style.marginLeft = '0';
        toggleBtn.style.display = 'block';
        
        // Initial resize check
        handleResize();

        // Toggle button click handler
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            if (sidebar.classList.contains('open')) {
                // Close sidebar
                sidebar.classList.remove('open');
                mainContent.style.marginLeft = '0';
            } else {
                // Open sidebar
                sidebar.classList.add('open');
                if (window.innerWidth >= 1200) {
                    // Desktop: sidebar pushes content
                    mainContent.style.marginLeft = '280px';
                } else {
                    // Mobile: sidebar overlays content
                    mainContent.style.marginLeft = '0';
                }
            }
        });

        // Close when clicking outside (mobile only)
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1200 && 
                !sidebar.contains(e.target) && 
                !toggleBtn.contains(e.target) &&
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                mainContent.style.marginLeft = '0';
            }
        });

        window.addEventListener('resize', handleResize);
    }
}

        // Sign out function
        function signOut() {
            if (confirm('Are you sure you want to sign out?')) {
                document.getElementById('loadingOverlay').style.display = 'flex';

                fetch('../actions/signout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(() => {
                        window.location.href = 'login.php';
                    })
                    .catch(() => {
                        // Fallback - redirect anyway
                        window.location.href = 'login.php';
                    });
            }
        }

        // Basic analytics functions
        function changeYear(year) {
            document.getElementById('loadingOverlay').style.display = 'flex';
            setTimeout(() => {
                window.location.href = `analytics.php?year=${year}`;
            }, 500);
        }

        function exportAnalytics() {
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Create basic CSV export
            const csvData = [
                ['Family Analytics Report - ' + window.analyticsData.selectedYear],
                [''],
                ['Year Overview'],
                ['Total Contributions', 'GHS ' + window.analyticsData.yearlyOverview.total_contributions],
                ['Total Expenses', 'GHS ' + window.analyticsData.yearlyOverview.total_expenses],
                ['Net Savings', 'GHS ' + window.analyticsData.yearlyOverview.net_savings],
                ['Active Contributors', window.analyticsData.yearlyOverview.active_contributors],
                [''],
                ['Monthly Breakdown'],
                ['Month', 'Contributions', 'Expenses', 'Net Amount']
            ];

            window.analyticsData.monthlyBreakdown.forEach(month => {
                csvData.push([
                    month.month_name,
                    month.contributions,
                    month.expenses,
                    month.net_amount
                ]);
            });

            downloadCSV(csvData, `Family_Analytics_${window.analyticsData.selectedYear}.csv`);

            setTimeout(() => {
                document.getElementById('loadingOverlay').style.display = 'none';
                showSnackbar('Analytics exported successfully!', 'success');
            }, 2000);
        }

        function showDetailedReport() {
            showSnackbar('Detailed report feature coming soon!', 'info');
        }

        function showMonthDetail(month, monthName) {
            showSnackbar(`Viewing details for ${monthName}`, 'info');
        }

        function showCycleDetails(cycleId) {
            showSnackbar('Showing cycle details for ID: ' + cycleId, 'info');
        }

        function downloadCSV(data, filename) {
            const csvContent = data.map(row =>
                row.map(field => {
                    if (typeof field === 'string' && (field.includes(',') || field.includes('"') || field.includes('\n'))) {
                        return '"' + field.replace(/"/g, '""') + '"';
                    }
                    return field;
                }).join(',')
            ).join('\n');

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');

            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function showSnackbar(message, type = 'info') {
            const snackbar = document.getElementById('snackbar');
            if (snackbar) {
                snackbar.textContent = message;
                snackbar.className = `snackbar show ${type}`;

                setTimeout(() => {
                    snackbar.className = 'snackbar';
                }, 3000);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
    <script src="../public/js/analytics.js"></script>
</body>

</html>