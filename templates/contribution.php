<?php
session_start();
require_once '../includes/member_summary_functions.php';
require_once '../includes/contribution_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    header("Location: login.php");
    exit;
}


$familyId = $_SESSION['family_id'];
$filterMember = $_GET['member'] ?? '';
$filterDateFrom = $_GET['dateFrom'] ?? '';
$filterDateTo = $_GET['dateTo'] ?? '';
$filterAmountRange = $_GET['amountRange'] ?? '';

$members = getFamilyMemberContributions($familyId);
$contributions = getFilteredContributions($conn, $familyId, $filterMember, $filterDateFrom, $filterDateTo, $filterAmountRange);


$monthlyStats = getMonthlyStats($conn, $familyId);
$memberLabels = [];
$contributedThisMonth = [];
$contributionGoals = [];

foreach ($members as $member) {
    $memberLabels[] = $member['full_name'];
    $contributedThisMonth[] = floatval($member['total_contributed_this_month']);
    $contributionGoals[] = floatval($member['monthly_contribution_goal']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family - Contributions</title>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <link rel="stylesheet" href="../public/css/contribution.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    const chartData = {
        labels: <?= json_encode($memberLabels) ?>,
        thisMonth: <?= json_encode($contributedThisMonth) ?>,
        goals: <?= json_encode($contributionGoals) ?>
    };
</script>

</head>

<body>

    <!-- Snackbar for notifications -->
    <div id="snackbar"></div>

    <!-- Sidebar Toggle Button -->
    <button id="sidebarToggle">☰</button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Nkansah</h1>
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
                <a href="#" class="nav-link active">
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
                <a href="momo" class="nav-link">
                    <span class="nav-icon">🏦</span>
                    <span>MoMo Account</span>
                </a>
            </li>
                        <li class="nav-item">
                <a href="analytics" class="nav-link ">
                    <span class="nav-icon">📊</span>
                    <span>Analytics</span>
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
        <!-- Contributions Header -->
        <div class="contributions-header">
            <div class="dashboard-title">
                <h2>Family Contributions</h2>
                <p class="dashboard-subtitle">Manage contributions and monthly goals</p>
            </div>
            <div class="contributions-actions">
                <button class="btn btn-secondary" onclick="exportContributions()">
                    📤 Export
                </button>
                <button class="btn btn-secondary" onclick="showGoalsModal()">
                    🎯 Set Goals
                </button>
                <button class="btn btn-primary" onclick="showContributionModal()">
                    ➕ Add Contribution
                </button>
            </div>
        </div>

        <!-- Monthly Goals Section -->
        <div class="goals-section">
            <?php foreach ($members as $member): ?>
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-member">
                            <div class="member-avatar">
                                <?= strtoupper(substr($member['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h3><?= htmlspecialchars($member['full_name']) ?></h3>
                                <div class="member-role">
                                    <?= ucfirst($member['role']) ?>
                                </div>
                            </div>
                        </div>
                        <button class="edit-goal-btn" onclick="editGoal('<?= $member['full_name'] ?>')">✏️ Edit</button>
                    </div>

                    <div class="goal-progress">
                        <div class="member-progress-label">
                            <span>Monthly Goal Progress</span>
                            <span>₵<?= number_format($member['total_contributed_this_month'], 2) ?> / ₵<?= number_format($member['monthly_contribution_goal'], 2) ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= min($member['progress'], 100) ?>%">
                                <?= $member['progress'] ?>%
                            </div>
                        </div>
                    </div>

                    <div class="goal-stats">
                        <div class="goal-stat">
                            <div class="goal-stat-value">
                                <?= $member['contribution_count'] ?? 0 ?>
                            </div>
                            <div class="goal-stat-label">Contributions</div>
                        </div>
                        <div class="goal-stat">
                            <div class="goal-stat-value">
                                ₵<?= number_format($member['average_contribution'] ?? 0, 2) ?>
                            </div>
                            <div class="goal-stat-label">Average</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Analytics Row -->
        <div class="analytics-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Member Contribution Comparison</h3>
                    <div class="chart-controls">
                        <button class="chart-control active" data-period="3m">3M</button>
                        <button class="chart-control" data-period="6m">6M</button>
                        <button class="chart-control" data-period="1y">1Y</button>
                    </div>
                </div>
                <div class="chart-container" >
                    <canvas id="memberComparisonChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">This Month's Stats</h3>
                </div>
                <div class="quick-stats-container">
                    <div class="quick-stat-item">
                        <div class="quick-stat-number">₵<?= number_format($monthlyStats['total'], 2); ?>
                        </div>
                        <div class="quick-stat-label">Total This Month</div>
                        <div class="quick-stat-icon">💰</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number">₵<?= number_format($monthlyStats['average'], 2); ?>
                        </div>
                        <div class="quick-stat-label">Average Per Member</div>
                        <div class="quick-stat-icon">📊</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number"><?= $monthlyStats['count']; ?>
                        </div>
                        <div class="quick-stat-label">Total Contributions</div>
                        <div class="quick-stat-icon">📈</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number"><?= $monthlyStats['achievement']; ?>
                            %</div>
                        <div class="quick-stat-label">Goal Achievement</div>
                        <div class="quick-stat-icon">🎯</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <!-- Filter Section -->
        <div class="filter-section">
            <h3 style="margin-bottom: 20px;">Filter Contributions</h3>
            <form method="GET" class="filter-row">
                <div class="form-group">
                    <label>Member</label>
                    <select name="member" id="filterMember">
                        <option value="">All Members</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= htmlspecialchars($member['full_name']) ?>" <?= $filterMember === $member['full_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($member['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="dateFrom" id="filterDateFrom" value="<?= htmlspecialchars($filterDateFrom) ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="dateTo" id="filterDateTo" value="<?= htmlspecialchars($filterDateTo) ?>">
                </div>
                <div class="form-group">
                    <label>Amount Range</label>
                    <select name="amountRange" id="filterAmount">
                        <option value="">All Amounts</option>
                        <option value="0-50" <?= $filterAmountRange === '0-50' ? 'selected' : '' ?>>₵0 - ₵50</option>
                        <option value="50-100" <?= $filterAmountRange === '50-100' ? 'selected' : '' ?>>₵50 - ₵100</option>
                        <option value="100-200" <?= $filterAmountRange === '100-200' ? 'selected' : '' ?>>₵100 - ₵200</option>
                        <option value="200+" <?= $filterAmountRange === '200+' ? 'selected' : '' ?>>₵200+</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Contributions Table -->
        <div class="contributions-table-container">
            <div class="chart-header">
                <h3 class="chart-title">Contribution History</h3>
            </div>
            <table class="contributions-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contributionsTableBody">
                    <?php if (!empty($contributions)): ?>
                        <?php foreach ($contributions as $contribution): ?>
                            <tr>
                                <td>
                                    <div class="member-badge">
                                        <div class="member-avatar-small"><?= strtoupper($contribution['first_name'][0]) ?></div>
                                        <?= htmlspecialchars($contribution['full_name']) ?>
                                    </div>
                                </td>
                                <td class="amount-cell">₵<?= number_format($contribution['amount'], 2) ?></td>
                                <td class="date-cell"><?= date("M j, Y", strtotime($contribution['contribution_date'])) ?></td>
                                <td><?= htmlspecialchars($contribution['notes'] ?? '') ?></td>
                                <td>
                                    <button class="edit-goal-btn" onclick="editContribution(<?= $contribution['id'] ?>)">✏️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No contributions found for selected filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

            <div class="pagination">
                <button disabled>← Previous</button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button>Next →</button>
            </div>
        </div>
    </div>

    <!-- Goal Edit Modal -->
    <div id="goalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Monthly Goal</h3>
                <span class="close" onclick="closeGoalModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="goalForm">
                    <div class="form-group">
                        <label>Member</label>
                        <input type="text" id="goalMember" readonly>
                    </div>
                    <div class="form-group">
                        <label>Monthly Goal Amount (₵)</label>
                        <input type="number" id="goalAmount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Goal Description (Optional)</label>
                        <input type="text" id="goalDescription" placeholder="e.g., Monthly savings target">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Goal</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Contribution Modal -->
    <div id="contributionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Contribution</h3>
                <span class="close" onclick="closeContributionModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="newContributionForm">
                    <div class="form-group">
                        <label>Member</label>
                        <select id="newContributionMember" required>
                            <option value="">Select Member</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= htmlspecialchars($member['full_name']) ?>">
                                    <?= htmlspecialchars($member['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount (₵)</label>
                        <input type="number" id="newContributionAmount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="newContributionDate" required>
                    </div>
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" id="newContributionNote" placeholder="e.g., Monthly contribution">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Contribution</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../public/js/contribution.js"></script>
    <script>
    console.log("Chart Data:", chartData);
</script>

</body>

</html>