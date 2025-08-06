<?php
session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and has family access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['family_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    $familyId = $_SESSION['family_id'];
    $currentMonth = date('Y-m');
    $lastMonth = date('Y-m', strtotime('-1 month'));

    // Get total pool (contributions - expenses) - Use calculated value for accuracy
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(fc.amount), 0) as total_contributions
        FROM family_contributions fc
        WHERE fc.family_id = ?
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $totalContributions = floatval($stmt->get_result()->fetch_assoc()['total_contributions']);

    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(fe.amount), 0) as total_expenses
        FROM family_expenses fe
        WHERE fe.family_id = ?
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $totalExpenses = floatval($stmt->get_result()->fetch_assoc()['total_expenses']);

    $totalPool = $totalContributions - $totalExpenses;
    
    // Sync check: Update stored total_pool if it's out of sync
    $stmt = $conn->prepare("SELECT total_pool FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $storedPool = floatval($stmt->get_result()->fetch_assoc()['total_pool']);
    
    if (abs($storedPool - $totalPool) > 0.01) { // Allow for small rounding differences
        // Update the stored value
        $stmt = $conn->prepare("UPDATE family_groups SET total_pool = ? WHERE id = ?");
        $stmt->bind_param("di", $totalPool, $familyId);
        $stmt->execute();
        
        error_log("Family pool sync: Updated family {$familyId} from {$storedPool} to {$totalPool}");
    }

    // Get monthly contributions (current month)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(fc.amount), 0) as monthly_contrib
        FROM family_contributions fc
        WHERE fc.family_id = ? 
        AND DATE_FORMAT(fc.contribution_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $familyId, $currentMonth);
    $stmt->execute();
    $monthlyContrib = floatval($stmt->get_result()->fetch_assoc()['monthly_contrib']);

    // Get last month contributions for comparison
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(fc.amount), 0) as monthly_contrib
        FROM family_contributions fc
        WHERE fc.family_id = ? 
        AND DATE_FORMAT(fc.contribution_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $familyId, $lastMonth);
    $stmt->execute();
    $lastMonthContrib = floatval($stmt->get_result()->fetch_assoc()['monthly_contrib']);

    $contribChange = $lastMonthContrib > 0 ? round((($monthlyContrib - $lastMonthContrib) / $lastMonthContrib) * 100, 1) : 0;

    // Get monthly expenses (current month)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_expenses
        FROM family_expenses
        WHERE family_id = ? 
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $familyId, $currentMonth);
    $stmt->execute();
    $monthlyExpenses = floatval($stmt->get_result()->fetch_assoc()['monthly_expenses']);

    // Get last month expenses for comparison
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_expenses
        FROM family_expenses
        WHERE family_id = ? 
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $familyId, $lastMonth);
    $stmt->execute();
    $lastMonthExpenses = floatval($stmt->get_result()->fetch_assoc()['monthly_expenses']);

    $expenseChange = $lastMonthExpenses > 0 ? round((($monthlyExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100, 1) : 0;

    // Calculate savings rate and net savings
    $netSavings = $monthlyContrib - $monthlyExpenses;
    $savingsRate = $monthlyContrib > 0 ? round(($netSavings / $monthlyContrib) * 100, 1) : 0;

    // Get active members count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM family_members_only 
        WHERE family_id = ? AND is_active = 1
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $activeMembers = intval($stmt->get_result()->fetch_assoc()['count']);

    // Get monthly contribution count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM family_contributions fc
        WHERE fc.family_id = ? 
        AND DATE_FORMAT(fc.contribution_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $familyId, $currentMonth);
    $stmt->execute();
    $contributionCount = intval($stmt->get_result()->fetch_assoc()['count']);

    // Get monthly expense count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM family_expenses
        WHERE family_id = ? 
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->bind_param("is", $familyId, $currentMonth);
    $stmt->execute();
    $expenseCount = intval($stmt->get_result()->fetch_assoc()['count']);

    // Get current cycle information - enhanced to handle closed cycles
    $stmt = $conn->prepare("
        SELECT 
            id,
            cycle_month,
            cycle_year,
            cycle_month_num,
            start_date,
            end_date,
            is_current,
            is_closed,
            total_collected,
            total_target,
            members_completed,
            members_pending,
            closed_at,
            CASE 
                WHEN is_closed = TRUE THEN -999
                ELSE DATEDIFF(end_date, CURDATE())
            END as days_remaining,
            CASE 
                WHEN is_closed = TRUE THEN 100
                WHEN DATEDIFF(end_date, CURDATE()) < 0 THEN 100
                ELSE ROUND((DATEDIFF(CURDATE(), start_date) / DATEDIFF(end_date, start_date)) * 100)
            END as progress_percentage
        FROM monthly_cycles 
        WHERE family_id = ? 
        AND (
            (is_current = TRUE AND is_closed = FALSE) OR 
            (is_closed = TRUE AND DATE_FORMAT(closed_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m'))
        )
        ORDER BY 
            CASE WHEN is_closed = FALSE THEN 0 ELSE 1 END,
            created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $currentCycle = $stmt->get_result()->fetch_assoc();

    // Handle case where no current cycle exists
    if (!$currentCycle) {
        // Include cycle functions
        require_once '../includes/cycle_functions.php';
        
        // Check if we should create a new cycle (only at beginning of month)
        $currentDay = date('j');
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        // If it's early in the month (first 3 days), auto-create cycle
        if ($currentDay <= 3) {
            checkAndStartNewMonthCycles($conn);
            
            // Try to get the cycle again
            $stmt->execute();
            $currentCycle = $stmt->get_result()->fetch_assoc();
        }
        
        // If still no cycle, check if there's a recently closed cycle to display
        if (!$currentCycle) {
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    cycle_month,
                    cycle_year,
                    cycle_month_num,
                    start_date,
                    end_date,
                    is_current,
                    is_closed,
                    total_collected,
                    total_target,
                    members_completed,
                    members_pending,
                    closed_at,
                    -999 as days_remaining,
                    100 as progress_percentage
                FROM monthly_cycles 
                WHERE family_id = ? 
                AND is_closed = TRUE
                ORDER BY closed_at DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $familyId);
            $stmt->execute();
            $currentCycle = $stmt->get_result()->fetch_assoc();
            
            // Add a flag to indicate this is a "waiting for new month" state
            if ($currentCycle) {
                $currentCycle['waiting_for_new_month'] = true;
            }
        }
    }

    // Replace the member query section in your dashboard API with this:

    // FIXED: Get family members with calculated total contributions
    $stmt = $conn->prepare("
    SELECT 
        fmo.id,
        fmo.first_name,
        fmo.last_name,
        fmo.role,
        fmo.monthly_contribution_goal,
        COALESCE(total_contrib.total_amount, 0) as calculated_total_contributed,
        COALESCE(monthly_contrib.amount, 0) as monthly_contribution,
        COALESCE(contrib_count.count, 0) as contribution_count
    FROM family_members_only fmo
    LEFT JOIN (
        SELECT 
            member_only_id,
            SUM(amount) as total_amount
        FROM family_contributions
        WHERE family_id = ? 
            AND member_only_id IS NOT NULL
        GROUP BY member_only_id
    ) total_contrib ON fmo.id = total_contrib.member_only_id
    LEFT JOIN (
        SELECT 
            member_only_id,
            SUM(amount) as amount
        FROM family_contributions
        WHERE family_id = ? 
            AND DATE_FORMAT(contribution_date, '%Y-%m') = ?
            AND member_only_id IS NOT NULL
        GROUP BY member_only_id
    ) monthly_contrib ON fmo.id = monthly_contrib.member_only_id
    LEFT JOIN (
        SELECT 
            member_only_id,
            COUNT(*) as count
        FROM family_contributions
        WHERE family_id = ? 
            AND member_only_id IS NOT NULL
        GROUP BY member_only_id
    ) contrib_count ON fmo.id = contrib_count.member_only_id
    WHERE fmo.family_id = ? AND fmo.is_active = 1
    ORDER BY calculated_total_contributed DESC, fmo.first_name ASC
");

    $stmt->bind_param("iisii", $familyId, $familyId, $currentMonth, $familyId, $familyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $goal = floatval($row['monthly_contribution_goal']);
        $achieved = floatval($row['monthly_contribution']);
        $totalContributed = floatval($row['calculated_total_contributed']); // Using calculated value
        $progress = $goal > 0 ? round(($achieved / $goal) * 100, 0) : 0;

        $members[] = [
            'id' => intval($row['id']),
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'first_name' => $row['first_name'],
            'role' => ucfirst($row['role']),
            'total_contributed' => $totalContributed, // Now correctly calculated from contributions
            'monthly_contribution' => $achieved,
            'monthly_goal' => $goal,
            'contribution_count' => intval($row['contribution_count']),
            'progress_percentage' => min(100, max(0, $progress))
        ];
    }

    // Get recent activity
    $stmt = $conn->prepare("
        (SELECT 
            'contribution' as type,
            fc.amount,
            fc.contribution_date as activity_date,
            CASE 
                WHEN fc.member_only_id IS NOT NULL THEN CONCAT(fmo.first_name, ' ', fmo.last_name)
                ELSE 'Unknown Member'
            END as member_name,
            COALESCE(fc.notes, 'Monthly contribution') as description,
            fc.created_at
        FROM family_contributions fc
        LEFT JOIN family_members_only fmo ON fc.member_only_id = fmo.id
        WHERE fc.family_id = ?)
        UNION ALL
        (SELECT 
            'expense' as type,
            -fe.amount as amount,
            fe.expense_date as activity_date,
            CONCAT('Family Expense: ', UPPER(fe.expense_type)) as member_name,
            fe.description as description,
            fe.created_at
        FROM family_expenses fe
        WHERE fe.family_id = ?)
        ORDER BY created_at DESC
        LIMIT 10
    ");

    $stmt->bind_param("ii", $familyId, $familyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $time = time() - strtotime($row['created_at']);

        if ($time < 60) $timeAgo = 'Just now';
        elseif ($time < 3600) $timeAgo = floor($time / 60) . ' minutes ago';
        elseif ($time < 86400) $timeAgo = floor($time / 3600) . ' hours ago';
        elseif ($time < 2592000) $timeAgo = floor($time / 86400) . ' days ago';
        elseif ($time < 31536000) $timeAgo = floor($time / 2592000) . ' months ago';
        else $timeAgo = floor($time / 31536000) . ' years ago';

        $activities[] = [
            'type' => $row['type'],
            'title' => $row['type'] === 'contribution' ?
                $row['member_name'] . ' made a contribution' :
                $row['member_name'],
            'description' => $timeAgo . ' - ' . $row['description'],
            'amount' => floatval($row['amount']),
            'date' => $row['activity_date']
        ];
    }

    // Get chart data (last 6 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(month_data.month_date, '%b %Y') as period,
            COALESCE(contrib.contributions, 0) as contributions,
            COALESCE(exp.expenses, 0) as expenses
        FROM (
            SELECT DATE_SUB(CURDATE(), INTERVAL n MONTH) as month_date
            FROM (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) months
        ) month_data
        LEFT JOIN (
            SELECT 
                DATE_FORMAT(contribution_date, '%Y-%m') as month_key,
                SUM(amount) as contributions
            FROM family_contributions 
            WHERE family_id = ? 
            AND contribution_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(contribution_date, '%Y-%m')
        ) contrib ON DATE_FORMAT(month_data.month_date, '%Y-%m') = contrib.month_key
        LEFT JOIN (
            SELECT 
                DATE_FORMAT(expense_date, '%Y-%m') as month_key,
                SUM(amount) as expenses
            FROM family_expenses 
            WHERE family_id = ? 
            AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ) exp ON DATE_FORMAT(month_data.month_date, '%Y-%m') = exp.month_key
        ORDER BY month_data.month_date ASC
    ");

    $stmt->bind_param("ii", $familyId, $familyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $chartLabels = [];
    $chartContributions = [];
    $chartExpenses = [];

    while ($row = $result->fetch_assoc()) {
        $chartLabels[] = $row['period'];
        $chartContributions[] = floatval($row['contributions']);
        $chartExpenses[] = floatval($row['expenses']);
    }

    // Prepare response data
    $stats = [
        'totalPool' => [
            'amount' => $totalPool,
            'change' => 0 // You can calculate this if needed
        ],
        'monthlyContributions' => [
            'amount' => $monthlyContrib,
            'change' => $contribChange
        ],
        'monthlyExpenses' => [
            'amount' => $monthlyExpenses,
            'change' => $expenseChange
        ],
        'savingsRate' => max(0, $savingsRate),
        'activeMembers' => $activeMembers,
        'contributionCount' => $contributionCount,
        'expenseCount' => $expenseCount,
        'netSavings' => $netSavings
    ];

    $chartData = [
        'labels' => $chartLabels,
        'contributions' => $chartContributions,
        'expenses' => $chartExpenses
    ];

    // Get debt information
    require_once '../includes/debt_functions.php';
    $debtSummary = getFamilyDebtSummary($conn, $familyId);
    $outstandingDebts = getOutstandingDebts($conn, $familyId);
    
    // Add debt info to member data
    foreach ($members as &$member) {
        // For family_members_only, we need to check if there's a corresponding family_members record
        $stmt = $conn->prepare("SELECT id FROM family_members WHERE family_id = ? AND display_name LIKE ? LIMIT 1");
        $searchName = '%' . $member['name'] . '%';
        $stmt->bind_param("is", $familyId, $searchName);
        $stmt->execute();
        $familyMember = $stmt->get_result()->fetch_assoc();
        
        if ($familyMember) {
            $memberDebtInfo = getMemberTotalDebt($conn, $familyMember['id']);
            $member['total_debt'] = floatval($memberDebtInfo['total_debt']);
            $member['debt_count'] = intval($memberDebtInfo['debt_count']);
            $member['has_debt'] = $member['total_debt'] > 0;
            $member['family_member_id'] = $familyMember['id']; // Store for payment processing
        } else {
            $member['total_debt'] = 0;
            $member['debt_count'] = 0;
            $member['has_debt'] = false;
            $member['family_member_id'] = null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'members' => $members,
            'activities' => $activities,
            'chartData' => $chartData,
            'currentCycle' => $currentCycle,
            'debtSummary' => $debtSummary,
            'outstandingDebts' => $outstandingDebts
        ],
        'debug' => [
            'family_id' => $familyId,
            'current_month' => $currentMonth,
            'member_count' => count($members)
        ]
    ]);
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard data',
        'error' => $e->getMessage() // Remove this in production
    ]);
}
