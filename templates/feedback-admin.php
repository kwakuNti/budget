<?php
session_start();

// Check if user is logged in and is admin (you can adjust this check based on your admin system)
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

require_once '../config/connection.php';

// Get feedback with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "uf.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($type_filter)) {
    $where_conditions[] = "uf.feedback_type = ?";
    $params[] = $type_filter;
    $param_types .= 's';
}

if (!empty($priority_filter)) {
    $where_conditions[] = "uf.priority = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM user_feedback uf $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_feedback = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_feedback / $limit);

// Get feedback data
$query = "SELECT 
    uf.*,
    u.first_name,
    u.last_name,
    u.email
FROM user_feedback uf
JOIN users u ON uf.user_id = u.id
$where_clause
ORDER BY uf.created_at DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$feedback_list = $result->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
    AVG(rating) as avg_rating
FROM user_feedback";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Nkansah Budget Manager</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/feedback-admin.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="logo-text">
                    <h1>Admin Panel</h1>
                    <p>Feedback Management</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard" class="nav-item">Dashboard</a>
                <a href="feedback-admin" class="nav-item active">Feedback</a>
            </nav>
            
            <a href="../actions/signout.php" class="signout-btn">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <section class="page-header">
                <div class="header-content">
                    <h2><i class="fas fa-comments"></i> Feedback Management</h2>
                    <p>Manage user feedback and support requests</p>
                </div>
            </section>

            <!-- Statistics Cards -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-comments"></i></div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total']); ?></h3>
                            <p>Total Feedback</p>
                        </div>
                    </div>
                    <div class="stat-card new">
                        <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['new_count']); ?></h3>
                            <p>New</p>
                        </div>
                    </div>
                    <div class="stat-card progress">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['in_progress_count']); ?></h3>
                            <p>In Progress</p>
                        </div>
                    </div>
                    <div class="stat-card resolved">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['resolved_count']); ?></h3>
                            <p>Resolved</p>
                        </div>
                    </div>
                    <div class="stat-card urgent">
                        <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['urgent_count']); ?></h3>
                            <p>Urgent</p>
                        </div>
                    </div>
                    <div class="stat-card rating">
                        <div class="stat-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-content">
                            <h3><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : 'N/A'; ?></h3>
                            <p>Avg Rating</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filters -->
            <section class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All</option>
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="type">Type:</label>
                        <select name="type" id="type">
                            <option value="">All</option>
                            <option value="bug_report" <?php echo $type_filter === 'bug_report' ? 'selected' : ''; ?>>Bug Report</option>
                            <option value="feature_request" <?php echo $type_filter === 'feature_request' ? 'selected' : ''; ?>>Feature Request</option>
                            <option value="general" <?php echo $type_filter === 'general' ? 'selected' : ''; ?>>General</option>
                            <option value="complaint" <?php echo $type_filter === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                            <option value="compliment" <?php echo $type_filter === 'compliment' ? 'selected' : ''; ?>>Compliment</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="priority">Priority:</label>
                        <select name="priority" id="priority">
                            <option value="">All</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    
                    <a href="feedback-admin" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </section>

            <!-- Feedback List -->
            <section class="feedback-list-section">
                <div class="feedback-table-container">
                    <table class="feedback-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedback_list as $feedback): ?>
                            <tr class="feedback-row" data-id="<?php echo $feedback['id']; ?>">
                                <td>#<?php echo $feedback['id']; ?></td>
                                <td>
                                    <div class="user-info">
                                        <strong><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($feedback['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge <?php echo $feedback['feedback_type']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $feedback['feedback_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="subject-cell">
                                        <?php echo htmlspecialchars($feedback['subject']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="priority-badge <?php echo $feedback['priority']; ?>">
                                        <?php echo ucfirst($feedback['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $feedback['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $feedback['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($feedback['rating']): ?>
                                        <div class="rating-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa<?php echo $i <= $feedback['rating'] ? 's' : 'r'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-rating">No rating</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                                        <small><?php echo date('g:i A', strtotime($feedback['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-sm btn-primary" onclick="viewFeedback(<?php echo $feedback['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-sm btn-secondary" onclick="updateStatus(<?php echo $feedback['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&priority=<?php echo $priority_filter; ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        (<?php echo number_format($total_feedback); ?> total)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&priority=<?php echo $priority_filter; ?>" class="btn btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../public/js/feedback-admin.js"></script>
</body>
</html>
