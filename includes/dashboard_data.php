<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/contribution_functions.php';
require_once __DIR__ . '/../includes/expense_functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class DashboardData {
    private $conn;
    private $familyId;
    
    public function __construct($connection, $familyId) {
        $this->conn = $connection;
        $this->familyId = $familyId;
    }
    
    /**
     * Get all dashboard statistics
     */
    public function getDashboardStats() {
        $stats = [
            'totalPool' => $this->getTotalPool(),
            'monthlyContributions' => $this->getMonthlyContributions(),
            'monthlyExpenses' => $this->getMonthlyExpenses(),
            'savingsRate' => 0,
            'activeMembers' => $this->getActiveMembersCount(),
            'contributionCount' => $this->getMonthlyContributionCount(),
            'expenseCount' => $this->getMonthlyExpenseCount(),
            'netSavings' => 0
        ];
        
        // Calculate savings rate and net savings
        $monthlyContrib = $stats['monthlyContributions']['amount'];
        $monthlyExp = $stats['monthlyExpenses']['amount'];
        
        $stats['netSavings'] = $monthlyContrib - $monthlyExp;
        $stats['savingsRate'] = $monthlyContrib > 0 ? 
            round(($stats['netSavings'] / $monthlyContrib) * 100, 1) : 0;
            
        return $stats;
    }
    
    /**
     * Get total family pool amount
     */
    private function getTotalPool() {
        $stmt = $this->conn->prepare("
            SELECT 
                COALESCE(SUM(fc.amount), 0) - COALESCE((
                    SELECT SUM(fe.amount) 
                    FROM family_expenses fe 
                    WHERE fe.family_id = ?
                ), 0) as total_pool
            FROM family_contributions fc
            JOIN family_members_only fmo ON fc.member_only_id = fmo.id
            WHERE fmo.family_id = ?
        ");
        
        $stmt->bind_param("ii", $this->familyId, $this->familyId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'amount' => floatval($result['total_pool']),
            'change' => $this->calculatePoolChange()
        ];
    }
    
    /**
     * Get monthly contributions with change percentage
     */
    private function getMonthlyContributions() {
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        // Current month total
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(fc.amount), 0) as total
            FROM family_contributions fc
            JOIN family_members_only fmo ON fc.member_only_id = fmo.id
            WHERE fmo.family_id = ? 
            AND DATE_FORMAT(fc.contribution_date, '%Y-%m') = ?
        ");
        
        $stmt->bind_param("is", $this->familyId, $currentMonth);
        $stmt->execute();
        $currentTotal = floatval($stmt->get_result()->fetch_assoc()['total']);
        
        // Last month total
        $stmt->bind_param("is", $this->familyId, $lastMonth);
        $stmt->execute();
        $lastTotal = floatval($stmt->get_result()->fetch_assoc()['total']);
        
        $change = $lastTotal > 0 ? round((($currentTotal - $lastTotal) / $lastTotal) * 100, 1) : 0;
        
        return [
            'amount' => $currentTotal,
            'change' => $change
        ];
    }
    
    /**
     * Get monthly expenses with change percentage
     */
    private function getMonthlyExpenses() {
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        // Current month total
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM family_expenses
            WHERE family_id = ? 
            AND DATE_FORMAT(expense_date, '%Y-%m') = ?
        ");
        
        $stmt->bind_param("is", $this->familyId, $currentMonth);
        $stmt->execute();
        $currentTotal = floatval($stmt->get_result()->fetch_assoc()['total']);
        
        // Last month total
        $stmt->bind_param("is", $this->familyId, $lastMonth);
        $stmt->execute();
        $lastTotal = floatval($stmt->get_result()->fetch_assoc()['total']);
        
        $change = $lastTotal > 0 ? round((($currentTotal - $lastTotal) / $lastTotal) * 100, 1) : 0;
        
        return [
            'amount' => $currentTotal,
            'change' => $change
        ];
    }
    
    /**
     * Calculate pool change from last month
     */
    private function calculatePoolChange() {
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        // Get net change for current month (contributions - expenses)
        $stmt = $this->conn->prepare("
            SELECT 
                COALESCE(contrib.total, 0) - COALESCE(exp.total, 0) as net_current
            FROM (
                SELECT COALESCE(SUM(fc.amount), 0) as total
                FROM family_contributions fc
                JOIN family_members_only fmo ON fc.member_only_id = fmo.id
                WHERE fmo.family_id = ? AND DATE_FORMAT(fc.contribution_date, '%Y-%m') = ?
            ) contrib
            CROSS JOIN (
                SELECT COALESCE(SUM(amount), 0) as total
                FROM family_expenses
                WHERE family_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
            ) exp
        ");
        
        $stmt->bind_param("isis", $this->familyId, $currentMonth, $this->familyId, $currentMonth);
        $stmt->execute();
        $currentNet = floatval($stmt->get_result()->fetch_assoc()['net_current']);
        
        // Get net change for last month
        $stmt->bind_param("isis", $this->familyId, $lastMonth, $this->familyId, $lastMonth);
        $stmt->execute();
        $lastNet = floatval($stmt->get_result()->fetch_assoc()['net_current']);
        
        return $lastNet > 0 ? round((($currentNet - $lastNet) / $lastNet) * 100, 1) : 0;
    }
    
    /**
     * Get active members count
     */
    private function getActiveMembersCount() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM family_members_only 
            WHERE family_id = ? AND is_active = 1
        ");
        
        $stmt->bind_param("i", $this->familyId);
        $stmt->execute();
        return intval($stmt->get_result()->fetch_assoc()['count']);
    }
    
    /**
     * Get monthly contribution count
     */
    private function getMonthlyContributionCount() {
        $currentMonth = date('Y-m');
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM family_contributions fc
            JOIN family_members_only fmo ON fc.member_only_id = fmo.id
            WHERE fmo.family_id = ? 
            AND DATE_FORMAT(fc.contribution_date, '%Y-%m') = ?
        ");
        
        $stmt->bind_param("is", $this->familyId, $currentMonth);
        $stmt->execute();
        return intval($stmt->get_result()->fetch_assoc()['count']);
    }
    
    /**
     * Get monthly expense count
     */
    private function getMonthlyExpenseCount() {
        $currentMonth = date('Y-m');
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM family_expenses
            WHERE family_id = ? 
            AND DATE_FORMAT(expense_date, '%Y-%m') = ?
        ");
        
        $stmt->bind_param("is", $this->familyId, $currentMonth);
        $stmt->execute();
        return intval($stmt->get_result()->fetch_assoc()['count']);
    }
    
    /**
     * Get family members with their statistics
     */
    public function getFamilyMembers() {
        $stmt = $this->conn->prepare("
            SELECT 
                fmo.id,
                fmo.first_name,
                fmo.last_name,
                fmo.role,
                fmo.monthly_contribution_goal,
                fmo.total_contributed,
                COALESCE(monthly_contrib.amount, 0) as monthly_contribution,
                COALESCE(contrib_count.count, 0) as contribution_count
            FROM family_members_only fmo
            LEFT JOIN (
                SELECT 
                    member_only_id,
                    SUM(amount) as amount
                FROM family_contributions
                WHERE DATE_FORMAT(contribution_date, '%Y-%m') = ?
                GROUP BY member_only_id
            ) monthly_contrib ON fmo.id = monthly_contrib.member_only_id
            LEFT JOIN (
                SELECT 
                    member_only_id,
                    COUNT(*) as count
                FROM family_contributions
                WHERE DATE_FORMAT(contribution_date, '%Y-%m') = ?
                GROUP BY member_only_id
            ) contrib_count ON fmo.id = contrib_count.member_only_id
            WHERE fmo.family_id = ? AND fmo.is_active = 1
            ORDER BY fmo.role DESC, fmo.first_name ASC
        ");
        
        $currentMonth = date('Y-m');
        $stmt->bind_param("ssi", $currentMonth, $currentMonth, $this->familyId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $members = [];
        while ($row = $result->fetch_assoc()) {
            $goal = floatval($row['monthly_contribution_goal']);
            $achieved = floatval($row['monthly_contribution']);
            $progress = $goal > 0 ? round(($achieved / $goal) * 100, 0) : 0;
            
            $members[] = [
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'first_name' => $row['first_name'],
                'role' => ucfirst($row['role']),
                'total_contributed' => floatval($row['total_contributed']),
                'monthly_contribution' => $achieved,
                'monthly_goal' => $goal,
                'contribution_count' => intval($row['contribution_count']),
                'progress_percentage' => min(100, $progress)
            ];
        }
        
        return $members;
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 10) {
        $stmt = $this->conn->prepare("
            (SELECT 
                'contribution' as type,
                fc.amount,
                fc.contribution_date as activity_date,
                CONCAT(fmo.first_name, ' ', fmo.last_name) as member_name,
                fc.notes as description,
                fc.created_at
            FROM family_contributions fc
            JOIN family_members_only fmo ON fc.member_only_id = fmo.id
            WHERE fmo.family_id = ?)
            UNION ALL
            (SELECT 
                'expense' as type,
                -fe.amount as amount,
                fe.expense_date as activity_date,
                fe.description as member_name,
                CONCAT(fe.expense_type, ' - ', fe.description) as description,
                fe.created_at
            FROM family_expenses fe
            WHERE fe.family_id = ?)
            ORDER BY created_at DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("iii", $this->familyId, $this->familyId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $timeAgo = $this->timeAgo($row['created_at']);
            
            $activities[] = [
                'type' => $row['type'],
                'title' => $row['type'] === 'contribution' ? 
                    $row['member_name'] . ' made a contribution' : 
                    $row['member_name'],
                'description' => $timeAgo,
                'amount' => floatval($row['amount']),
                'date' => $row['activity_date']
            ];
        }
        
        return $activities;
    }
    
    /**
     * Get contribution trend data for charts
     */
    public function getContributionTrends($period = '6m') {
        switch($period) {
            case '6m':
                $interval = 6;
                $groupBy = "DATE_FORMAT(fc.contribution_date, '%b')";
                break;
            case '1y':
                $interval = 12;
                $groupBy = "DATE_FORMAT(fc.contribution_date, '%b')";
                break;
            case 'all':
                $interval = 24;
                $groupBy = "CONCAT(YEAR(fc.contribution_date), ' Q', QUARTER(fc.contribution_date))";
                break;
            default:
                $interval = 6;
                $groupBy = "DATE_FORMAT(fc.contribution_date, '%b')";
        }
        
        // Get contributions data
        $stmt = $this->conn->prepare("
            SELECT 
                $groupBy as period,
                COALESCE(SUM(fc.amount), 0) as contributions,
                COALESCE(exp.expenses, 0) as expenses
            FROM family_contributions fc
            JOIN family_members_only fmo ON fc.member_only_id = fmo.id
            LEFT JOIN (
                SELECT 
                    $groupBy as period,
                    SUM(amount) as expenses
                FROM family_expenses 
                WHERE family_id = ? 
                AND expense_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY $groupBy
            ) exp ON $groupBy = exp.period
            WHERE fmo.family_id = ? 
            AND fc.contribution_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY $groupBy
            ORDER BY MIN(fc.contribution_date) ASC
        ");
        
        $stmt->bind_param("iiii", $this->familyId, $interval, $this->familyId, $interval);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $contributionData = [];
        $expenseData = [];
        
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['period'];
            $contributionData[] = floatval($row['contributions']);
            $expenseData[] = floatval($row['expenses']);
        }
        
        return [
            'labels' => $labels,
            'contributions' => $contributionData,
            'expenses' => $expenseData
        ];
    }
    
    /**
     * Calculate time ago string
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        return floor($time/31536000) . ' years ago';
    }
}

// Usage example in dashboard.php
if (isset($_SESSION['family_id'])) {
    $dashboardData = new DashboardData($conn, $_SESSION['family_id']);
    $stats = $dashboardData->getDashboardStats();
    $members = $dashboardData->getFamilyMembers();
    $activities = $dashboardData->getRecentActivity();
    $chartData = $dashboardData->getContributionTrends('6m');
}
?>