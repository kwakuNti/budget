<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/member_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    header("Location: login");
    exit;
}

$familyId = $_SESSION['family_id'];
$members = getAllFamilyMembers($familyId);
$totalMembers = getTotalActiveMembers($familyId);
$avgContribution = number_format(getAverageMonthlyContribution($familyId), 2);
$topContributor = getTopContributor($familyId)['name'] ?? 'N/A';
$topContributorAmount = getTopContributor($familyId)['total_contributed'] ?? 0;

$lastMonthAvg = number_format(getAverageMonthlyContributionLastMonth($familyId), 2);
$growthRate = $lastMonthAvg > 0 ? (($avgContribution - $lastMonthAvg) / $lastMonthAvg) * 100 : 0;
$growthMessage = $growthRate > 0
    ? '↗ Improved from last month'
    : ($growthRate < 0 ? '↘ Drop from last month' : 'No change');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family - Members</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <link rel="stylesheet" href="../public/css/members.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
                <a href="#" class="nav-link active">
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
                <a href="" class="nav-link">
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
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>👥 Family Members</h2>
                <p class="dashboard-subtitle">Manage family member profiles and contributions</p>
            </div>
            <div class="dashboard-actions">
                <button class="btn btn-primary" onclick="showAddMemberModal()">
                    ➕ Add Member
                </button>
            </div>
        </div>

        <!-- Family Statistics -->
        <div class="stats-grid">
            <div class="stat-card total-members">
                <div class="stat-icon members">👥</div>
                <div class="stat-value"><span id="totalMembers"><?= $totalMembers ?></span></div>
                <div class="stat-label">Total Members</div>
                <div class="stat-change positive">↗ All members active</div>
            </div>

            <div class="stat-card active-contributors">
                <div class="stat-icon contrib">✅</div>
                <div class="stat-value"><span id="activeContributors"><?= $totalMembers ?></span></div>
                <div class="stat-label">Active Contributors</div>
                <div class="stat-change positive">↗ 100% participation</div>
            </div>


            <div class="stat-card avg-contribution">
                <div class="stat-icon pool">💰</div>
                <div class="stat-value">₵<span id="avgContribution"><?= $avgContribution ?></span></div>
                <div class="stat-label">Average Monthly</div>
                <div class="stat-change <?= $growthRate >= 0 ? 'positive' : 'negative' ?>">
                    <?= $growthMessage ?>
                </div>
            </div>

            <div class="stat-card top-contributor">
                <div class="stat-icon savings">🏆</div>
                <div class="stat-value"><span id="topContributor"><?= $topContributor ?></span></div>
                <div class="stat-label">Top Contributor</div>
                <div class="stat-change positive">
                    <?php
                    if ($topContributorAmount > 0) {
                        echo "↗ Leading this month";
                    } else {
                        echo "↗ Ready to contribute";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Members Grid -->
        <div class="members-grid">
            <?php foreach ($members as $member):
                $recent = getRecentContributions($member['id']);
                $contribCount = getMemberContributionCount($member['id']);
                $thisMonth = getCurrentMonthTotal($member['id']);
                $progress = $member['monthly_contribution_goal'] > 0
                    ? min(100, ($thisMonth / $member['monthly_contribution_goal']) * 100)
                    : 0;
            ?>
                <div class="member-card" data-member-id="<?= $member['id'] ?>">
                    <div class="member-header">
                        <div class="member-avatar"><?= strtoupper($member['first_name'][0]) ?>
                            <div class="member-status status-active"></div>
                        </div>
                        <div class="member-info">
                            <h3><?= $member['first_name'] . ' ' . $member['last_name'] ?></h3>
                            <div class="member-role"><?= ucfirst($member['role']) ?></div>
                            <div class="member-details">
                                <div class="member-contact">📱 <?= $member['phone_number'] ?></div>
                                <div class="member-joined">Joined <?= date('M Y', strtotime($member['added_at'])) ?></div>
                            </div>
                        </div>
                        <div class="member-actions">
                            <button class="btn-icon" onclick='openEditModal(`<?= htmlspecialchars(json_encode($member), ENT_QUOTES, 'UTF-8') ?>`)' title="Edit Member">✏️</button>
                            <button class="btn-icon" onclick="confirmDelete(<?= $member['id'] ?>)" title="Delete Member">🗑️</button>
                        </div>
                    </div>
                    <div class="member-stats">
                        <div class="member-stat">
                            <div class="member-stat-value">₵<?= number_format($member['total_contributed'], 2) ?></div>
                            <div class="member-stat-label">Total Contributed</div>
                        </div>
                        <div class="member-stat">
                            <div class="member-stat-value"><?= $contribCount ?></div>
                            <div class="member-stat-label">Contributions</div>
                        </div>
                        <div class="member-stat">
                            <div class="member-stat-value">₵<?= number_format($thisMonth, 2) ?></div>
                            <div class="member-stat-label">This Month</div>
                        </div>
                    </div>
                    <div class="member-progress">
                        <div class="member-progress-label"><span>Monthly Goal (₵<?= number_format($member['monthly_contribution_goal'], 2) ?>)</span><span>₵<?= number_format($thisMonth, 2) ?> / ₵<?= number_format($member['monthly_contribution_goal'], 2) ?></span></div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                        </div>
                    </div>
                    <div class="recent-contributions">
                        <div class="contributions-header">Recent Contributions</div>
                        <?php if (!empty($recent)): ?>
                            <?php foreach ($recent as $r): ?>
                                <div class="contribution-item">
                                    <span class="contribution-date"><?= date('M d, Y', strtotime($r['contribution_date'])) ?></span>
                                    <span class="contribution-amount">₵<?= number_format($r['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">No recent contributions</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <!-- Delete Member Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <form method="POST" action="../actions/member_actions.php">
                <div class="modal-header">
                    <h2 class="modal-title">Delete Member</h2>
                    <span class="close" onclick="closeDeleteModal()">&times;</span>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <p style="font-size: 16px;">Are you sure you want to permanently delete this member from the family list?</p>
                    </div>
                    <input type="hidden" name="action" value="delete_member">
                    <input type="hidden" name="member_id" id="deleteMemberId">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Member</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div id="editMemberModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Member</h3>
                <span class="close" onclick="closeEditMemberModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editMemberForm" method="POST" action="../actions/member_actions.php">
                    <input type="hidden" name="action" value="edit_member">
                    <input type="hidden" name="member_id" id="editMemberId">

                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" id="editFirstName" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" id="editLastName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" id="editPhone" required>
                        </div>
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" id="editRole" required>
                                <option value="">Select Role</option>
                                <option value="Parent">Parent</option>
                                <option value="Child">Child</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Monthly Contribution Goal (₵) *</label>
                            <input type="number" name="monthly_goal" id="editMonthlyGoal" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditMemberModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Member</h3>
                <span class="close" onclick="closeAddMemberModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addMemberForm" method="POST" action="../actions/member_actions.php">
                    <input type="hidden" name="action" value="add_member">
                    <input type="hidden" name="family_id" value="<?= $familyId ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" placeholder="e.g., John" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" placeholder="e.g., Doe" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" placeholder="+233 24 123 4567" required>
                        </div>
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" required>
                                <option value="parent">Parent</option>
                                <option value="child">Child</option>
                                <option value="spouse">Spouse</option>
                                <option value="sibling">Sibling</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Monthly Contribution Goal (₵) *</label>
                            <input type="number" name="monthly_goal" step="0.01" min="0" placeholder="300.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="Any additional information about this member..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddMemberModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <script src="../public/js/members.js"></script>
</body>

</html>