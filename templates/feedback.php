<?php
session_start();

// Check session timeout
require_once '../includes/session_timeout_middleware.php';
$session_check = checkSessionTimeout();
if (!$session_check['valid']) {
    header('Location: ../login?timeout=1');
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Get user information from session
$user_first_name = $_SESSION['first_name'] ?? 'User';
$user_full_name = $_SESSION['full_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Nkansah Budget Manager</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/feedback.css">
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
                <div class="logo-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="logo-text">
                    <h1 id="logoUserName"><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Finance Dashboard</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard" class="nav-item">Dashboard</a>
                <a href="salary" class="nav-item">Salary Setup</a>
                <a href="budget" class="nav-item">Budget</a>
                <a href="personal-expense" class="nav-item">Expenses</a>
                <a href="savings" class="nav-item">Savings</a>
                <a href="report" class="nav-item">Reports</a>
                <a href="feedback" class="nav-item active">Feedback</a>
            </nav>

            <div class="theme-selector">
                <button class="theme-toggle-btn" onclick="toggleThemeSelector()" title="Change Theme">
                    <span class="theme-icon"><i class="fas fa-palette"></i></span>
                </button>
                <div class="theme-dropdown" id="themeDropdown">
                    <div class="theme-option" data-theme="light">
                        <div class="theme-preview light"></div>
                        <span>Ocean Blue</span>
                    </div>
                    <div class="theme-option" data-theme="forest">
                        <div class="theme-preview forest"></div>
                        <span>Forest Green</span>
                    </div>
                    <div class="theme-option" data-theme="sunset">
                        <div class="theme-preview sunset"></div>
                        <span>Sunset Orange</span>
                    </div>
                    <div class="theme-option" data-theme="midnight">
                        <div class="theme-preview midnight"></div>
                        <span>Midnight Purple</span>
                    </div>
                </div>
            </div>
            
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
                    <h2><i class="fas fa-comment-dots"></i> Share Your Feedback</h2>
                    <p>Help us improve your experience. Your feedback is valuable to us!</p>
                </div>
            </section>

            <!-- Feedback Form -->
            <section class="feedback-form-section">
                <div class="form-container">
                    <form id="feedbackForm" class="feedback-form">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="feedbackType">
                                    <i class="fas fa-tags"></i> Feedback Type
                                </label>
                                <select id="feedbackType" name="feedback_type" required>
                                    <option value="">Select feedback type...</option>
                                    <option value="bug_report">Bug Report</option>
                                    <option value="feature_request">Feature Request</option>
                                    <option value="general">General Feedback</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="compliment">Compliment</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="subject">
                                    <i class="fas fa-heading"></i> Subject
                                </label>
                                <input type="text" id="subject" name="subject" required 
                                       placeholder="Brief description of your feedback">
                            </div>

                            <div class="form-group full-width">
                                <label for="message">
                                    <i class="fas fa-comment"></i> Your Message
                                </label>
                                <textarea id="message" name="message" rows="6" required 
                                          placeholder="Please provide detailed feedback. The more information you provide, the better we can help you."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="priority">
                                    <i class="fas fa-exclamation-triangle"></i> Priority
                                </label>
                                <select id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="rating">
                                    <i class="fas fa-star"></i> Overall Rating (Optional)
                                </label>
                                <div class="rating-input">
                                    <input type="hidden" id="rating" name="rating" value="">
                                    <div class="stars" id="starRating">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <span class="rating-text">Click to rate</span>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label for="pageUrl">
                                    <i class="fas fa-link"></i> Page URL (Optional)
                                </label>
                                <input type="url" id="pageUrl" name="page_url" 
                                       placeholder="If your feedback is about a specific page, enter the URL here">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Feedback
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Feedback Tips -->
            <section class="feedback-tips">
                <div class="tips-container">
                    <h3><i class="fas fa-lightbulb"></i> Tips for Better Feedback</h3>
                    <div class="tips-grid">
                        <div class="tip-card">
                            <div class="tip-icon"><i class="fas fa-bug"></i></div>
                            <h4>Bug Reports</h4>
                            <p>Include steps to reproduce the issue, what you expected to happen, and what actually happened.</p>
                        </div>
                        <div class="tip-card">
                            <div class="tip-icon"><i class="fas fa-magic"></i></div>
                            <h4>Feature Requests</h4>
                            <p>Describe the feature you'd like to see and how it would improve your experience.</p>
                        </div>
                        <div class="tip-card">
                            <div class="tip-icon"><i class="fas fa-info-circle"></i></div>
                            <h4>General Feedback</h4>
                            <p>Share your overall experience, suggestions for improvement, or any other thoughts.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../public/js/personal.js"></script>
    <script src="../public/js/feedback.js"></script>
</body>
</html>
