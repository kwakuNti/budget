<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/momo_functions.php';


// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    header('Location: login.php');
    exit;
}

$family_id = $_SESSION['family_id'];
$user_id = $_SESSION['user_id'];

// Enhanced debug logging
error_log("=== MOMO PAGE DEBUG START ===");
error_log("Session data - User ID: " . $user_id . ", Family ID: " . $family_id);


try {
    // Test direct database query first (this should work based on your debug)
    error_log("Testing direct database query...");
    $direct_stmt = $conn->prepare("SELECT ma.*, fg.family_name FROM momo_accounts ma LEFT JOIN family_groups fg ON ma.family_id = fg.id WHERE ma.family_id = ? AND ma.is_active = 1 ORDER BY ma.is_primary DESC, ma.id DESC LIMIT 1");
    $direct_stmt->bind_param("i", $family_id);
    $direct_stmt->execute();
    $direct_result = $direct_stmt->get_result();
    $direct_account = $direct_result->fetch_assoc();
    
    error_log("Direct query result: " . json_encode($direct_account));
    
    // Now test the function
    error_log("Testing getFamilyMoMoAccount function...");
    $momoAccount = getFamilyMoMoAccount($conn, $family_id);
    error_log("Function result: " . json_encode($momoAccount));
    
    // If function fails but direct query works, use direct result
    if (!$momoAccount && $direct_account) {
        error_log("Function failed but direct query worked - using direct result");
        $momoAccount = $direct_account;
    }
    
    // If both fail, try the simple version
    if (!$momoAccount) {
        error_log("Both failed, trying simple version...");
        $momoAccount = getFamilyMoMoAccountSimple($conn, $family_id);
        error_log("Simple function result: " . json_encode($momoAccount));
    }
    
    // Final check - if still null, force a basic query
    if (!$momoAccount) {
        error_log("All functions failed, trying basic query...");
        $basic_stmt = $conn->prepare("SELECT * FROM momo_accounts WHERE family_id = ? LIMIT 1");
        $basic_stmt->bind_param("i", $family_id);
        $basic_stmt->execute();
        $basic_result = $basic_stmt->get_result();
        $momoAccount = $basic_result->fetch_assoc();
        error_log("Basic query result: " . json_encode($momoAccount));
    }

    // Get MoMo statistics
    $momoStats = getMoMoStats($conn, $family_id);
    if (!$momoStats) {
        error_log("Stats function failed, using defaults");
        $momoStats = [
            'total_requests' => 0,
            'total_received' => 0,
            'pending_requests' => 0,
            'recent_requests' => 0,
            'recent_received' => 0
        ];
    }
    error_log("MoMo Stats: " . json_encode($momoStats));

    // Get recent payment requests
    $recentRequests = getRecentPaymentRequests($conn, $family_id, 10);
    if (!$recentRequests) {
        $recentRequests = [];
    }

    // Get family members for payment requests
    $familyMembers = getFamilyMembersForPayment($conn, $family_id);
    if (!$familyMembers) {
        $familyMembers = [];
    }

    // Check if user has MoMo permissions
    $canPerformOperations = canPerformMoMoOperations($conn, $user_id, $family_id);
    if (!$canPerformOperations) {
        $canPerformOperations = true; // Default to true
    }

    // Get network info - only if we have an account
    $networkInfo = $momoAccount ? getNetworkInfo($momoAccount['network']) : getNetworkInfo('mtn');
    
    // Final debug check
    error_log("Final momoAccount check: " . ($momoAccount ? "EXISTS" : "NULL"));
    if ($momoAccount) {
        error_log("Account details: Phone=" . ($momoAccount['phone_number'] ?? 'NULL') . 
                 ", Network=" . ($momoAccount['network'] ?? 'NULL') . 
                 ", Balance=" . ($momoAccount['balance'] ?? 'NULL'));
    }
    
} catch (Exception $e) {
    error_log("MoMo page error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Set safe defaults
    $momoAccount = null;
    $momoStats = [
        'total_requests' => 0,
        'total_received' => 0,
        'pending_requests' => 0,
        'recent_requests' => 0,
        'recent_received' => 0
    ];
    $recentRequests = [];
    $familyMembers = [];
    $canPerformOperations = true;
    $networkInfo = getNetworkInfo('mtn');
}

error_log("=== MOMO PAGE DEBUG END ===");

// Add a temporary debug display at the top of the page (remove this after fixing)
$debug_display = false; // Set to true to show debug info on page
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nkansah Family - MoMo Account</title>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <link rel="stylesheet" href="../public/css/momo.css">
</head>

<body>
    <!-- Debug Display (temporary - remove after fixing) -->
    <?php if ($debug_display): ?>
    <div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
        <strong>DEBUG INFO:</strong><br>
        Family ID: <?= $family_id ?><br>
        User ID: <?= $user_id ?><br>
        MoMo Account: <?= $momoAccount ? 'EXISTS' : 'NULL' ?><br>
        <?php if ($momoAccount): ?>
            Phone: <?= htmlspecialchars($momoAccount['phone_number'] ?? 'NULL') ?><br>
            Network: <?= htmlspecialchars($momoAccount['network'] ?? 'NULL') ?><br>
            Balance: <?= htmlspecialchars($momoAccount['balance'] ?? 'NULL') ?><br>
        <?php endif; ?>
        <button onclick="this.parentElement.style.display='none'">Hide Debug</button>
    </div>
    <?php endif; ?>

    <!-- Snackbar for notifications -->
    <div id="snackbar"></div>

    <!-- Sidebar Toggle Button -->
    <button id="sidebarToggle" class="sidebar-toggle">☰</button>

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
        <!-- Page Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Mobile Money Account</h2>
                <p class="dashboard-subtitle">Manage your MoMo settings and send payment requests</p>
            </div>
            <div class="dashboard-actions">
                <?php if ($momoAccount): ?>
                    <button class="btn btn-secondary" onclick="refreshBalance()" id="refreshBalanceBtn">
                        🔄 Refresh Balance
                    </button>
                    <button class="btn btn-primary" onclick="showRequestModal()">
                        📱 Request Payment
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary" onclick="showSetupModal()">
                        📱 Setup MoMo Account
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$momoAccount): ?>
            <!-- No MoMo Account Setup -->
            <div class="no-account-section">
                <div class="no-account-card">
                    <div class="no-account-icon">📱</div>
                    <h3>No MoMo Account Connected</h3>
                    <p>Connect your family's mobile money account to start sending payment requests and managing funds.</p>
                    

                    
                    <button class="btn btn-primary" onclick="showSetupModal()">
                        <span>📱</span> Setup MoMo Account
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- MoMo Account Overview -->
            <div class="momo-overview">
                <!-- Current Account Card -->
                <div class="momo-account-card">
                    <div class="momo-header">
                        <div class="network-logo <?= strtolower($momoAccount['network'] ?? 'mtn') ?>">
                            <span><?= htmlspecialchars($networkInfo['short'] ?? 'MTN') ?></span>
                        </div>
                        <div class="account-status">
                            <span class="status-badge active">Active</span>
                        </div>
                    </div>
                    
                    <div class="account-details">
                        <div class="phone-number">
                            <span class="label">Phone Number</span>
                            <span class="number" id="currentNumber"><?= htmlspecialchars($momoAccount['phone_number'] ?? '') ?></span>
                        </div>
                        
                        <div class="account-balance">
                            <span class="balance-label">Available Balance</span>
                            <span class="balance-amount">₵<span id="momoBalance"><?= number_format(floatval($momoAccount['balance'] ?? 0), 2) ?></span></span>
                        </div>
                    </div>

                    <div class="account-actions">
                        <button class="action-btn" onclick="showNetworkModal()">
                            <span class="action-icon">🔄</span>
                            Switch Network
                        </button>
                        <button class="action-btn" onclick="showNumberModal()">
                            <span class="action-icon">📱</span>
                            Change Number
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="momo-stats-grid">
                    <div class="momo-stat-card">
                        <div class="stat-icon requests">📤</div>
                        <div class="stat-value" id="totalRequests"><?= intval($momoStats['total_requests'] ?? 0) ?></div>
                        <div class="stat-label">Payment Requests Sent</div>
                    </div>
                    
                    <div class="momo-stat-card">
                        <div class="stat-icon received">📥</div>
                        <div class="stat-value" id="totalReceived">₵<?= number_format(floatval($momoStats['total_received'] ?? 0), 0) ?></div>
                        <div class="stat-label">Total Received</div>
                    </div>
                    
                    <div class="momo-stat-card">
                        <div class="stat-icon pending">⏳</div>
                        <div class="stat-value" id="pendingRequests"><?= intval($momoStats['pending_requests'] ?? 0) ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
            </div>

            <!-- Network Options -->
            <div class="network-section">
                <div class="section-card">
                    <div class="section-header">
                        <h3>Available Networks</h3>
                        <p>Choose your preferred mobile money network</p>
                    </div>

                    <div class="network-grid">
                        <div class="network-option <?= ($momoAccount['network'] ?? '') == 'mtn' ? 'active' : '' ?>" data-network="mtn">
                            <div class="network-logo-large mtn">MTN</div>
                            <div class="network-name">MTN Mobile Money</div>
                            <div class="network-status"><?= ($momoAccount['network'] ?? '') == 'mtn' ? 'Currently Active' : 'Available' ?></div>
                        </div>

                        <div class="network-option" data-network="vodafone">
                            <div class="network-logo-large vodafone">Voda</div>
                            <div class="network-name">Vodafone Cash</div>
                            <div class="network-status">Coming Soon</div>
                        </div>

                        <div class="network-option" data-network="airteltigo">
                            <div class="network-logo-large airteltigo">AT</div>
                            <div class="network-name">AirtelTigo Money</div>
                            <div class="network-status">Coming Soon</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Requests Section -->
            <div class="requests-section">
                <div class="section-card">
                    <div class="section-header">
                        <h3>Recent Payment Requests</h3>
                        <p>Track your sent payment requests</p>
                    </div>

                    <div class="requests-list" id="requestsList">
                        <?php if (empty($recentRequests)): ?>
                            <div class="no-requests">
                                <div class="no-requests-icon">📱</div>
                                <h4>No payment requests yet</h4>
                                <p>Start by sending your first payment request to family members.</p>
                                <button class="btn btn-primary" onclick="showRequestModal()">
                                    Send First Request
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentRequests as $request): ?>
                                <?php
                                $requestDate = new DateTime($request['requested_at']);
                                $now = new DateTime();
                                $diff = $now->diff($requestDate);

                                if ($diff->days == 0) {
                                    if ($diff->h == 0) {
                                        $timeAgo = $diff->i . ' minute' . ($diff->i != 1 ? 's' : '') . ' ago';
                                    } else {
                                        $timeAgo = $diff->h . ' hour' . ($diff->h != 1 ? 's' : '') . ' ago';
                                    }
                                } elseif ($diff->days == 1) {
                                    $timeAgo = '1 day ago';
                                } else {
                                    $timeAgo = $diff->days . ' days ago';
                                }

                                $statusClass = strtolower($request['status']);
                                $statusText = ucfirst($request['status']);
                                ?>
                                <div class="request-item">
                                    <div class="request-member">
                                        <div class="member-avatar"><?= strtoupper(substr($request['recipient_name'] ?? 'U', 0, 1)) ?></div>
                                        <div class="member-info">
                                            <div class="member-name"><?= htmlspecialchars($request['recipient_name'] ?? 'Unknown') ?></div>
                                            <div class="member-phone"><?= htmlspecialchars($request['recipient_phone'] ?? '') ?></div>
                                        </div>
                                    </div>

                                    <div class="request-details">
                                        <div class="request-amount">₵<?= number_format($request['amount'] ?? 0, 2) ?></div>
                                        <div class="request-purpose"><?= htmlspecialchars($request['purpose'] ?? '') ?></div>
                                    </div>

                                    <div class="request-status">
                                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        <div class="request-time"><?= $timeAgo ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (count($recentRequests) >= 10): ?>
                        <div class="load-more">
                            <button class="btn btn-secondary" onclick="loadMoreRequests()">Load More</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- All the modals remain the same as in your original code -->
    <!-- MoMo Setup Modal -->
    <div id="setupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Setup MoMo Account</h3>
                <span class="close" onclick="closeSetupModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="setupMoMoForm">
                    <div class="form-group">
                        <label>Select Network</label>
                        <div class="network-selection">
                            <div class="network-option-modal active-selection" data-network="mtn">
                                <div class="network-logo-small mtn">MTN</div>
                                <span>MTN Mobile Money</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" id="setupPhoneNumber" required placeholder="+233 XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label>Account Name</label>
                        <input type="text" id="setupAccountName" required placeholder="e.g., Nkansah Family Fund">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeSetupModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Setup Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Request Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Payment Request</h3>
                <span class="close" onclick="closeRequestModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="paymentRequestForm">
                    <div class="form-group">
                        <label>Select Member(s)</label>
                        <div class="member-checkboxes">
                            <?php if (empty($familyMembers)): ?>
                                <p>No family members available. Please add family members first.</p>
                            <?php else: ?>
                                <?php foreach ($familyMembers as $member): ?>
                                    <?php
                                    $memberId = $member['member_type'] . '_' . $member['member_id'];
                                    $displayName = $member['display_name'] ?: $member['full_name'];
                                    ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="members" value="<?= $memberId ?>" data-phone="<?= htmlspecialchars($member['phone_number']) ?>">
                                        <span class="checkmark"></span>
                                        <div class="member-details">
                                            <span class="name"><?= htmlspecialchars($displayName) ?></span>
                                            <span class="phone"><?= htmlspecialchars($member['phone_number']) ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Amount (₵)</label>
                        <input type="number" id="requestAmount" step="0.01" min="1" required placeholder="Enter amount to request">
                    </div>

                    <div class="form-group">
                        <label>Purpose/Description</label>
                        <input type="text" id="requestPurpose" required placeholder="e.g., Monthly contribution, DSTV payment">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="sendSMS">
                            Also send SMS notification
                        </label>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeRequestModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Network Switch Modal -->
    <div id="networkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Switch Network</h3>
                <span class="close" onclick="closeNetworkModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="network-selection">
                    <div class="network-option-modal" data-network="mtn">
                        <div class="network-logo-small mtn">MTN</div>
                        <span>MTN Mobile Money</span>
                    </div>
                </div>
                
                <form id="networkSwitchForm" style="display: none;">
                    <div class="form-group">
                        <label>New Phone Number</label>
                        <input type="tel" id="switchPhoneNumber" required placeholder="+233 XX XXX XXXX">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeNetworkModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="confirmNetworkSwitch()">Switch Network</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Number Modal -->
    <div id="numberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Phone Number</h3>
                <span class="close" onclick="closeNumberModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="changeNumberForm">
                    <div class="form-group">
                        <label>New Phone Number</label>
                        <input type="tel" id="newPhoneNumber" required placeholder="+233 XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label>Confirm with MoMo PIN</label>
                        <input type="password" id="confirmPin" required placeholder="Enter your MoMo PIN">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeNumberModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Change Number</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        window.momoData = {
            account: <?= json_encode($momoAccount) ?>,
            stats: <?= json_encode($momoStats) ?>,
            familyMembers: <?= json_encode($familyMembers) ?>,
            canPerformOperations: <?= json_encode($canPerformOperations) ?>,
            familyId: <?= json_encode($family_id) ?>,
            userId: <?= json_encode($user_id) ?>
        };
        

    </script>

    <script src="../public/js/momo.js"></script>
</body>

</html>