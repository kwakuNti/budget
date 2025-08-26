<?php
session_start();

// Check session timeout
require_once '../includes/session_timeout_middleware.php';
$session_check = checkSessionTimeout();
if (!$session_check['valid']) {
    header('Location: ../login.php?timeout=1');
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check user type and redirect family users to family dashboard
require_once '../config/connection.php';
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['user_type'] !== 'personal') {
    // Redirect family users to family dashboard
    header('Location: ../index.php');
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
    <title>Personal Dashboard </title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/mobile-nav.css">
    <link rel="stylesheet" href="../public/css/walkthrough.css">
    <link rel="stylesheet" href="../public/css/privacy.css">
    <link rel="stylesheet" href="../public/css/loading.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced Payday Countdown Styles */
        .payday-countdown-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark), var(--secondary-color));
            color: white;
            padding: 3rem 2rem;
            border-radius: 24px;
            text-align: center;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px var(--shadow-color), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .payday-countdown-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="grad" cx="50%" cy="50%" r="50%"><stop offset="0%" style="stop-color:rgba(255,255,255,0.1);stop-opacity:1" /><stop offset="100%" style="stop-color:rgba(255,255,255,0);stop-opacity:0" /></radialGradient></defs><circle cx="200" cy="200" r="150" fill="url(%23grad)"/><circle cx="800" cy="300" r="100" fill="url(%23grad)"/><circle cx="600" cy="700" r="120" fill="url(%23grad)"/></svg>') center/cover;
            pointer-events: none;
            opacity: 0.3;
        }

        .payday-countdown-hero h2 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .payday-countdown-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .countdown-main-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3rem;
            margin: 2rem 0;
            position: relative;
            z-index: 2;
        }

        .countdown-number-large {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #ffffff, #f0f9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .countdown-details {
            text-align: left;
        }

        .countdown-label-large {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .pay-date-display {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .progress-ring-large {
            position: relative;
        }

        .progress-ring-large svg {
            width: 140px;
            height: 140px;
            transform: rotate(-90deg);
        }

        .progress-ring-large .progress-percentage-large {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .salary-info-hero {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 16px;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .salary-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .salary-amount-hero {
            font-size: 2rem;
            font-weight: 800;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .setup-salary-btn-hero {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .setup-salary-btn-hero:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments for payday hero */
        @media (max-width: 768px) {
            .payday-countdown-hero {
                padding: 2rem 1rem;
                margin-bottom: 2rem;
            }

            .payday-countdown-hero h2 {
                font-size: 2rem;
            }

            .countdown-main-display {
                flex-direction: column;
                gap: 1.5rem;
                align-items: center;
                text-align: center;
            }

            .countdown-number-large {
                font-size: 4rem;
                order: 1;
            }

            .countdown-details {
                text-align: center;
                order: 2;
            }

            .progress-ring-large {
                order: 3;
                margin-top: 1rem;
            }

            .salary-display {
                flex-direction: column;
                gap: 1rem;
            }

            .salary-amount-hero {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .payday-countdown-hero {
                padding: 1.5rem 1rem;
            }

            .payday-countdown-hero h2 {
                font-size: 1.8rem;
            }

            .payday-countdown-hero p {
                font-size: 1rem;
            }

            .countdown-main-display {
                gap: 1rem;
            }

            .countdown-number-large {
                font-size: 3rem;
            }

            .progress-ring-large svg {
                width: 80px;
                height: 80px;
            }

            .progress-percentage-large {
                font-size: 0.9rem;
            }

            .countdown-label-large {
                font-size: 1.2rem;
            }

            .progress-ring-large svg {
                width: 100px;
                height: 100px;
            }

            .progress-percentage-large {
                font-size: 1rem;
            }
        }

        /* Mobile Navigation Styles */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            padding: 0;
            transition: all 0.3s ease;
        }

        .hamburger-line {
            width: 20px;
            height: 2px;
            background-color: white;
            transition: all 0.3s ease;
            transform-origin: center;
        }

        .hamburger-line:not(:last-child) {
            margin-bottom: 4px;
        }

        /* Mobile menu toggle animation */
        .mobile-menu-toggle.active .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                order: 2;
            }

            .header-nav {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                border-radius: 0 0 20px 20px;
                padding: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(0, 0, 0, 0.1);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 1000;
                flex-direction: column;
                gap: 10px;
            }

            .header-nav.mobile-open {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }

            .header-nav .nav-item {
                color: #1f2937;
                padding: 12px 16px;
                border-radius: 10px;
                transition: all 0.3s ease;
                text-align: center;
                font-weight: 600;
                text-decoration: none;
                display: block;
                border: 1px solid transparent;
            }

            .header-nav .nav-item:hover,
            .header-nav .nav-item.active {
                background: #3b82f6;
                color: white;
                transform: translateX(5px);
                border-color: #2563eb;
            }

            .theme-selector {
                order: 3;
            }

            .logo {
                order: 1;
            }

            .header-content {
                justify-content: space-between;
            }
        }

        @media (max-width: 480px) {
            .header-nav {
                top: 70px;
                padding: 15px;
            }
        }

        /* Additional transaction styles */
        .transaction-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            opacity: 0.7;
        }

        .no-transactions {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
        }

        .no-transactions-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .no-transactions p {
            margin: 0.5rem 0;
            font-weight: 500;
        }

        .no-transactions small {
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Savings Goals Styles */
        .no-goals {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
        }

        .no-goals-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .no-goals p {
            margin: 0.5rem 0;
            font-weight: 500;
        }

        .no-goals small {
            font-size: 0.875rem;
            opacity: 0.7;
            display: block;
            margin-bottom: 1rem;
        }

        .create-goal-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .create-goal-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .goal-item {
            background: rgba(255,255,255,0.18);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 1.5rem 1.5rem 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .goal-item:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 32px 0 rgba(59, 130, 246, 0.10), 0 2px 8px rgba(16,185,129,0.08);
        }

        .goal-item.high-priority {
            border-left: 6px solid #ef4444;
        }
        .goal-item.medium-priority {
            border-left: 6px solid #f59e0b;
        }
        .goal-item.low-priority {
            border-left: 6px solid #10b981;
        }

        .goal-progress-circle {
            width: 70px;
            height: 70px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .goal-progress-circle svg {
            width: 70px;
            height: 70px;
        }
        .goal-progress-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.1rem;
            font-weight: 700;
            color: #222;
            text-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .goal-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .goal-interval {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: var(--background-secondary);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .goal-percentage {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-left: 0.5rem;
        }

        .goal-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .goal-action-btn {
            flex: 1;
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--primary-color);
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .goal-action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .goal-action-btn.secondary {
            background: transparent;
            color: var(--primary-color);
        }

        .goal-action-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        /* New styles for goal status and view-only display */
        .goal-remaining {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            font-style: italic;
        }

        .goal-status-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        .goal-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .goal-status.completed {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .goal-status.on-track {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .goal-status.moderate {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .goal-status.slow {
            background: rgba(249, 115, 22, 0.1);
            color: #ea580c;
            border: 1px solid rgba(249, 115, 22, 0.3);
        }

        .goal-status.behind {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .goal-view-btn {
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .goal-view-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        /* Transactions Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 10000;
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal-overlay.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-container {
            background: var(--card-background);
            border-radius: 20px;
            width: 95%;
            max-width: 1200px;
            max-height: 85vh;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: modalSlideIn 0.3s ease-out;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: between;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            flex: 1;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: white;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 24px 24px;
            border-bottom: 1px solid var(--border-color);
            background: var(--background-color);
        }

        .modal-stats .stat-card {
            background: var(--card-background);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .modal-stats .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .modal-stats .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }

        .modal-stats .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-filters {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background: var(--background-color);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
            gap: 16px;
            align-items: center;
        }

        .filter-row select,
        .filter-row input {
            padding: 10px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-background);
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .filter-row select:focus,
        .filter-row input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .clear-btn {
            padding: 10px 20px;
            background: var(--text-secondary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .clear-btn:hover {
            background: var(--text-primary);
            transform: translateY(-1px);
        }

        .modal-contentx {
            flex: 1;
            overflow: hidden;
            padding: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .modal-transactions-list {
            flex: 1;
            overflow-y: auto;
            width: 100%;
        }

        .modal-transaction-item {
            display: flex;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        .modal-transaction-item:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .modal-transaction-item:last-child {
            border-bottom: none;
        }

        .modal-transaction-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .modal-transaction-icon.income {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .modal-transaction-icon.expense {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .modal-transaction-details {
            flex: 1;
            min-width: 0;
        }

        .modal-transaction-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            font-size: 1.05rem;
        }

        .modal-transaction-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .modal-transaction-category {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .modal-transaction-amount {
            font-size: 1.15rem;
            font-weight: 700;
            text-align: right;
            min-width: 120px;
        }

        .modal-transaction-amount.income {
            color: #10b981;
        }

        .modal-transaction-amount.expense {
            color: #ef4444;
        }

        .modal-transaction-date {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .modal-pagination {
            padding: 16px 24px;
            text-align: center;
            border-top: 1px solid var(--border-color);
            background: var(--background-color);
            flex-shrink: 0;
        }

        .modal-pagination button {
            padding: 6px 12px;
            margin: 0 2px;
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .modal-pagination button:hover {
            background: var(--primary-color);
            color: white;
        }

        .modal-pagination button.active {
            background: var(--primary-color);
            color: white;
        }

        .loading-state {
            text-align: center;
            padding: 60px 24px;
            color: var(--text-secondary);
        }

        .loading-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .no-modal-transactions {
            text-align: center;
            padding: 60px 24px;
            color: var(--text-secondary);
        }

        .no-modal-transactions-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideIn {
            from { 
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .modal-container {
                width: 92%;
                max-width: 1000px;
            }
        }

        @media (max-width: 768px) {
            .modal-container {
                width: 95%;
                max-height: 90vh;
            }

            .modal-header {
                padding: 16px 20px;
            }

            .modal-stats {
                grid-template-columns: repeat(2, 1fr);
                padding: 16px 20px;
            }

            .modal-filters {
                padding: 16px 20px;
            }

            .filter-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .modal-transaction-item {
                padding: 16px 20px;
            }

            .modal-transaction-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
                margin-right: 16px;
            }

            .modal-transaction-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .modal-transaction-amount {
                font-size: 1rem;
                min-width: auto;
            }
        }

        /* Budget Template Preview Modal styles - Minimalistic & Clean */
        .budget-template-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            position: relative;
            overflow: hidden;
        }
        
        .budget-template-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            opacity: 0.3;
            pointer-events: none;
        }
        
        .budget-template-hero h4 {
            margin: 0 0 8px 0;
            font-size: 1.6rem;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }
        
        .budget-template-hero small {
            opacity: 0.85;
            font-size: 1rem;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }
        
        .allocation-bar {
            display: flex;
            width: 100%;
            height: 12px;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0,0,0,0.2);
            margin: 24px 0 20px 0;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }
        
        .allocation-segment {
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .allocation-segment::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(255,255,255,0.2), transparent);
        }
        
        .allocation-segment.needs { 
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .allocation-segment.wants { 
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .allocation-segment.savings { 
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .allocation-legend {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
            position: relative;
            z-index: 2;
        }
        
        .legend-item {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 16px 12px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .legend-item:hover {
            background: rgba(255,255,255,0.18);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .legend-left { 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .legend-dot { 
            width: 16px; 
            height: 16px; 
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            margin-bottom: 4px;
        }
        .legend-dot.needs { background: #3b82f6; }
        .legend-dot.wants { background: #f59e0b; }
        .legend-dot.savings { background: #10b981; }
        
        .legend-label { 
            font-weight: 600; 
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        
        .legend-amount { 
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 8px;
        }
        
        .pill {
            background: rgba(255,255,255,0.25);
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .empty-template-state { 
            text-align: center; 
            padding: 48px 32px;
            color: var(--text-secondary);
            background: var(--card-background);
            border-radius: 16px;
            border: 2px dashed var(--border-color);
        }
        
        .empty-template-state h4 { 
            margin: 16px 0 12px 0;
            color: var(--text-primary);
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .empty-template-state p { 
            color: var(--text-secondary); 
            margin: 0 0 24px 0;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        /* Modal improvements */
        #budgetTemplateViewModal .modal-content {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-radius: 24px;
            overflow: hidden;
        }
        
        #budgetTemplateViewModal .modal-header {
            background: var(--card-background);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 24px 32px 20px;
        }
        
        #budgetTemplateViewModal .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        #budgetTemplateViewModal .modal-body {
            padding: 0 32px 24px;
        }
        
        #budgetTemplateViewModal .modal-actions {
            padding: 20px 32px 32px;
            gap: 12px;
            border-top: 1px solid var(--border-color);
        }
        
        #budgetTemplateViewModal .modal-actions .btn-secondary {
            background: var(--background-color);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        #budgetTemplateViewModal .modal-actions .btn-secondary:hover {
            background: var(--text-secondary);
            color: white;
            border-color: var(--text-secondary);
        }
        
        #budgetTemplateViewModal .modal-actions .btn-primary {
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        #budgetTemplateViewModal .modal-actions .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
        }
    </style>
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
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            
            <nav class="header-nav" id="headerNav">
                <a href="personal-dashboard.php" class="nav-item active">Dashboard</a>
                <a href="salary.php" class="nav-item">Salary Setup</a>
                <a href="budget.php" class="nav-item">Budget</a>
                <a href="personal-expense.php" class="nav-item">Expenses</a>
                <a href="savings.php" class="nav-item">Savings</a>
                <!-- <a href="insights.php" class="nav-item">Insights</a> -->

                <a href="report.php" class="nav-item">Reports</a>
                <a href="feedback.php" class="nav-item">Feedback</a>

            </nav>

            <div class="theme-selector">
                <button class="theme-toggle-btn" onclick="toggleThemeSelector()" title="Change Theme">
                    <span class="theme-icon"><i class="fas fa-palette"></i></span>
                </button>
                <div class="theme-dropdown" id="themeDropdown">
                    <div class="theme-dropdown-header">
                        <h4>Choose Theme</h4>
                    </div>
                    <div class="themes-grid">
                        <div class="theme-option active" data-theme="default" onclick="changeTheme('default')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #3b82f6, #2563eb)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #10b981, #059669)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #f59e0b, #d97706)"></div>
                            </div>
                            <span class="theme-name">Ocean Blue</span>
                        </div>
                        <div class="theme-option" data-theme="forest" onclick="changeTheme('forest')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #059669, #047857)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #10b981, #065f46)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #34d399, #059669)"></div>
                            </div>
                            <span class="theme-name">Forest Green</span>
                        </div>
                        <div class="theme-option" data-theme="sunset" onclick="changeTheme('sunset')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #f59e0b, #d97706)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #f97316, #ea580c)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fbbf24, #f59e0b)"></div>
                            </div>
                            <span class="theme-name">Sunset Orange</span>
                        </div>
                        <div class="theme-option" data-theme="purple" onclick="changeTheme('purple')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #a855f7, #9333ea)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #c084fc, #a855f7)"></div>
                            </div>
                            <span class="theme-name">Royal Purple</span>
                        </div>
                        <div class="theme-option" data-theme="rose" onclick="changeTheme('rose')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #f43f5e, #e11d48)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fb7185, #f43f5e)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fda4af, #fb7185)"></div>
                            </div>
                            <span class="theme-name">Rose Pink</span>
                        </div>
                        <div class="theme-option" data-theme="dark" onclick="changeTheme('dark')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #374151, #1f2937)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #6b7280, #4b5563)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #9ca3af, #6b7280)"></div>
                            </div>
                            <span class="theme-name">Dark Mode</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-menu">
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar"><?php 
                    echo strtoupper(substr($user_first_name, 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)); 
                ?></div>
                <div class="user-dropdown" id="userDropdown">
                    <!-- <a href="profile.php">Profile Settings</a> -->
                    <!-- <a href="income-sources.php">Income Sources</a> -->
                    <!-- <a href="categories.php">Categories</a> -->
                    <!-- <hr> -->
                    <!-- <a href="family-dashboard.php">Switch to Family</a> -->
                    <a href="../actions/signout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Hero Payday Countdown Section -->
            <section class="payday-countdown-hero">
                <h2><i class="fas fa-bullseye"></i> Next Payday Countdown</h2>
                <p>Stay motivated and track your financial progress</p>
                
                <div class="countdown-main-display">
                    <div class="countdown-number-large" id="daysUntilPayLarge">12</div>
                    <div class="countdown-details">
                        <div class="countdown-label-large">Days Until Payday</div>
                        <div class="pay-date-display" id="payDateTextLarge">January 28, 2025</div>
                    </div>
                    <div class="progress-ring-large">
                        <svg class="progress-ring-svg" width="140" height="140">
                            <circle class="progress-ring-circle-bg" cx="70" cy="70" r="60" stroke="rgba(255,255,255,0.2)" stroke-width="8" fill="transparent"/>
                            <circle class="progress-ring-circle" cx="70" cy="70" r="60" stroke="url(#gradient-large)" stroke-width="8" fill="transparent" stroke-dasharray="377" stroke-dashoffset="151" stroke-linecap="round"/>
                            <defs>
                                <linearGradient id="gradient-large" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#f0f9ff;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="progress-percentage-large" id="payProgressPercentageLarge">60%</div>
                    </div>
                </div>
                
                <div class="salary-info-hero">
                    <div class="salary-display">
                        <div class="salary-amount-hero" id="monthlySalaryHero">Monthly Income: ₵0.00</div>
                        <button class="setup-salary-btn-hero" onclick="window.location.href='salary.php'"><i class="fas fa-cog"></i> Setup Income</button>
                        <button class="setup-salary-btn-hero" onclick="showSalaryPaidModal()"><i class="fas fa-check-circle"></i> I've Been Paid</button>
                    </div>
                </div>
            </section>

            <!-- Welcome Section with Quick Actions -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2 id="welcomeMessage">Welcome back, <?php echo htmlspecialchars($user_first_name); ?>!</h2>
                    <p id="salaryDueInfo">Ready to manage your finances today?</p>
                </div>
                
                <div class="quick-actions">
                    <button class="quick-btn" onclick="showAddIncomeModal()">
                        <span class="btn-icon"><i class="fas fa-dollar-sign"></i></span>
                        Add Income
                    </button>
                    <button class="quick-btn" onclick="showAddExpenseModal()">
                        <span class="btn-icon"><i class="fas fa-money-bill-wave"></i></span>
                        Add Expense
                    </button>
                    <button class="quick-btn" onclick="showBudgetTemplateViewModal()">
                        <span class="btn-icon"><i class="fas fa-chart-bar"></i></span>
                        View Budget
                    </button>
                </div>
            </section>

            <!-- Financial Overview Cards -->
            <section class="overview-cards">
                <div class="card balance-card">
                    <div class="card-header">
                        <h3>Current Balance</h3>
                        <span class="card-icon"><i class="fas fa-credit-card"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="currentBalance">₵0.00</div>
                        <div class="change" id="balanceChange">Loading...</div>
                    </div>
                </div>

                <div class="card income-card">
                    <div class="card-header">
                        <h3>This Month Income</h3>
                        <span class="card-icon"><i class="fas fa-chart-line"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="monthlyIncome">₵0.00</div>
                        <div class="change" id="nextSalaryDate">Loading...</div>
                    </div>
                </div>

                <div class="card expense-card">
                    <div class="card-header">
                        <h3>This Month Expenses</h3>
                        <span class="card-icon"><i class="fas fa-chart-bar"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="monthlyExpenses">₵0.00</div>
                        <div class="change" id="budgetRemaining">Loading...</div>
                    </div>
                </div>

                <div class="card savings-card">
                    <div class="card-header">
                        <h3>Total Saved This Month</h3>
                        <span class="card-icon"><i class="fas fa-bullseye"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="autoSavings">₵0.00</div>
                        <div class="change" id="savingsPercentage">Loading...</div>
                    </div>
                </div>
            </section>

            <!-- Financial Insights & Advice -->
            <section class="insights-section">
                <div class="section-header">
                    <h3><i class="fas fa-lightbulb"></i> Smart Financial Insights</h3>
                    <a href="report.php" class="view-all">View All Insights</a>
                </div>
                <div class="insights-grid" id="insightsGrid">
                    <!-- Dynamic insights will be populated here -->
                    <div class="insight-card loading">
                        <div class="insight-icon">🔄</div>
                        <div class="insight-content">
                            <h4>Loading Insights...</h4>
                            <p>Analyzing your financial data to provide personalized insights...</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dashboard Grid -->
            <section class="dashboard-grid">
                <!-- Savings Goals -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Savings Goals</h3>
                        <a href="savings.php" class="view-all">Manage</a>
                    </div>
                    <div class="savings-goals" id="savingsGoals">
                        <div class="goal-item">
                            <div class="goal-header">
                                <span class="goal-name"><i class="fas fa-clock"></i> Loading goals...</span>
                                <span class="goal-interval">Please wait</span>
                            </div>
                            <div class="goal-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="goal-text">Loading savings goals...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Recent Transactions</h3>
                        <a href="#" class="view-all" onclick="openTransactionsModal()">View All →</a>
                    </div>
                    <div class="transactions-list" id="recentTransactions">
                        <div class="transaction-item">
                            <div class="transaction-icon"><i class="fas fa-clock"></i></div>
                            <div class="transaction-details">
                                <div class="transaction-name">Loading transactions...</div>
                                <div class="transaction-category">Please wait</div>
                            </div>
                            <div class="transaction-amount">₵0.00</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modals -->
    <!-- Salary Setup Modal -->
    <div id="salarySetupModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Salary & Budget Setup</h3>
                <span class="close" onclick="closeModal('salarySetupModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-section">
                    <h4>Salary Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Monthly Salary (₵)</label>
                            <input type="number" step="0.01" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Pay Frequency</label>
                            <select required>
                                <option value="monthly">Monthly</option>
                                <option value="bi-weekly">Bi-weekly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Next Pay Date</label>
                        <input type="date" value="2025-01-28" required>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Budget Allocation</h4>
                    <div class="allocation-setup">
                        <div class="allocation-row">
                            <label>Needs (Essential expenses)</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="50" class="allocation-slider" data-category="needs">
                                <span class="allocation-percent">50%</span>
                                <span class="allocation-amount">₵1,800</span>
                            </div>
                        </div>
                        <div class="allocation-row">
                            <label>Wants (Non-essential)</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="30" class="allocation-slider" data-category="wants">
                                <span class="allocation-percent">30%</span>
                                <span class="allocation-amount">₵1,080</span>
                            </div>
                        </div>
                        <div class="allocation-row">
                            <label>Savings & Investments</label>
                            <div class="allocation-controls">
                                <input type="range" min="0" max="100" value="20" class="allocation-slider" data-category="savings">
                                <span class="allocation-percent">20%</span>
                                <span class="allocation-amount">₵720</span>
                            </div>
                        </div>
                    </div>
                    <div class="allocation-total">
                        <strong>Total: <span id="totalAllocation">100%</span></strong>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('salarySetupModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Setup</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Income Modal -->
    <div id="addIncomeModal" class="modal">
        <div class="modal-content wide-modal">
            <div class="modal-header gradient-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="modal-title-section">
                        <h3>Add New Income Source</h3>
                        <p>Track your earnings and boost your financial growth</p>
                    </div>
                </div>
                <span class="close modern-close" onclick="closeModal('addIncomeModal')">&times;</span>
            </div>
            <form class="modal-form compact-form">
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Source Name</label>
                        <input type="text" name="sourceName" placeholder="e.g., Freelance Work, Side Business" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Amount (₵)</label>
                        <input type="number" name="monthlyAmount" step="0.01" placeholder="0.00" required>
                    </div>
                </div>
                
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Income Type</label>
                        <select name="incomeType" required>
                            <option value="">Select type</option>
                            <option value="salary">💼 Salary</option>
                            <option value="freelance">💻 Freelance</option>
                            <option value="side-business">🏪 Side Business</option>
                            <option value="part-time">⏰ Part-time Job</option>
                            <option value="investment">📈 Investment</option>
                            <option value="rental">🏠 Rental Income</option>
                            <option value="bonus">🎁 Bonus</option>
                            <option value="other">📋 Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Payment Frequency</label>
                        <select name="paymentFrequency">
                            <option value="monthly">📅 Monthly</option>
                            <option value="bi-weekly">🗓️ Bi-weekly</option>
                            <option value="weekly">📆 Weekly</option>
                            <option value="quarterly">📊 Quarterly</option>
                            <option value="annual">📈 Annual</option>
                            <option value="variable">🔄 Variable</option>
                            <option value="one-time">🔘 One-time</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-file-alt"></i> Description (Optional)</label>
                        <input type="text" name="description" placeholder="Additional details about this income source">
                    </div>
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="includeInBudget" checked>
                            <span class="checkmark"></span>
                            Include in budget calculations
                        </label>
                    </div>
                </div>
                
                <div class="modal-actions modern-actions">
                    <button type="button" class="btn-secondary modern-btn" onclick="closeModal('addIncomeModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary modern-btn">
                        <i class="fas fa-plus-circle"></i> Add Income
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content wide-modal">
            <div class="modal-header gradient-header expense-header">
                <div class="modal-header-content">
                    <div class="modal-icon expense-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="modal-title-section">
                        <h3>Record New Expense</h3>
                        <p>Keep track of your spending and stay within budget</p>
                    </div>
                </div>
                <span class="close modern-close" onclick="closeModal('addExpenseModal')">&times;</span>
            </div>
            <form class="modal-form compact-form">
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Amount (₵)</label>
                        <input type="number" name="amount" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Date</label>
                        <input type="date" name="expense_date" required>
                    </div>
                </div>
                
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-tags"></i> Budget Category</label>
                        <select name="category_id" required>
                            <option value="">Loading categories...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-credit-card"></i> Payment Method</label>
                        <select name="payment_method">
                            <option value="cash">💵 Cash</option>
                            <option value="card">💳 Card</option>
                            <option value="mobile_money">📱 Mobile Money</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="cheque">📄 Cheque</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-edit"></i> Description</label>
                        <input type="text" name="description" placeholder="What was this for?" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="Additional notes">
                    </div>
                </div>
                
                <div class="modal-actions modern-actions">
                    <button type="button" class="btn-secondary modern-btn" onclick="closeModal('addExpenseModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary modern-btn expense-btn">
                        <i class="fas fa-plus-circle"></i> Record Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Budget Template Preview Modal -->
    <div id="budgetTemplateViewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Budget Template</h3>
                <span class="close" onclick="closeModal('budgetTemplateViewModal')">&times;</span>
            </div>
            <div class="modal-body" id="budgetTemplateViewContent" style="padding: 0 16px 16px 16px;">
                <div style="text-align:center; padding: 24px 12px; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 8px;"><i class="fas fa-clock"></i></div>
                    <div>Loading template...</div>
                </div>
            </div>
            <div class="modal-actions" style="display:flex; gap:8px; justify-content:flex-end; padding: 0 16px 16px 16px;">
                <button type="button" class="btn-secondary" onclick="window.location.href='budget.php'">Open Budget Page</button>
                <button type="button" class="btn-primary" onclick="closeModal('budgetTemplateViewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Salary Paid Confirmation Modal -->
    <div id="salaryPaidModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Salary Received</h3>
                <span class="close" onclick="closeModal('salaryPaidModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="salary-confirmation-info">
                    <div class="confirmation-icon"><i class="fas fa-piggy-bank"></i></div>
                    <h4>Did you receive your salary?</h4>
                    <p id="salaryConfirmationDetails">Confirming will add your salary amount to your current balance and update your next pay date.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('salaryPaidModal')">Cancel</button>
                    <button type="button" class="btn-primary" onclick="confirmSalaryFromDashboard()">✓ Yes, I received it</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Dashboard JavaScript
        // Wait for loading.js to be available
        function initializeDashboard() {
            console.log('Dashboard: Initializing dashboard');
            console.log('Dashboard: LoadingScreen available?', typeof window.LoadingScreen);
            
            // Initialize loading screen with dashboard-specific message
            if (window.LoadingScreen) {
                console.log('Dashboard: Creating LoadingScreen');
                window.budgetlyLoader = new LoadingScreen();
                console.log('Dashboard: LoadingScreen created', window.budgetlyLoader);
                
                // Customize the loading message for dashboard
                const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
                if (loadingMessage) {
                    loadingMessage.innerHTML = 'Loading your dashboard<span class="loading-dots-text">...</span>';
                    console.log('Dashboard: Loading message customized');
                } else {
                    console.error('Dashboard: Could not find loading message element');
                }
            } else {
                console.error('Dashboard: LoadingScreen class not available');
            }

            // Show initial loading for data fetch
            if (window.budgetlyLoader) {
                console.log('Dashboard: Showing loading screen');
                window.budgetlyLoader.show();
            } else {
                console.error('Dashboard: budgetlyLoader not available');
            }

            // Initialize dashboard
            loadDashboardData();
            updatePaydayCountdown();
            
            // Update data every 5 minutes (instead of 30 seconds)
            setInterval(loadDashboardData, 300000);
            
            // Update countdown every hour
            setInterval(updatePaydayCountdown, 3600000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard: DOMContentLoaded fired');
            
            // Enhanced loading screen availability check
            function checkLoadingScreen(attempts = 0) {
                const maxAttempts = 10;
                
                if (window.LoadingScreen) {
                    console.log('Dashboard: LoadingScreen found after', attempts, 'attempts');
                    initializeDashboard();
                } else if (attempts < maxAttempts) {
                    console.log('Dashboard: LoadingScreen not ready, attempt', attempts + 1, 'of', maxAttempts);
                    setTimeout(() => checkLoadingScreen(attempts + 1), 50);
                } else {
                    console.error('Dashboard: LoadingScreen still not available after', maxAttempts, 'attempts');
                    // Initialize without loading screen
                    loadDashboardData();
                    updatePaydayCountdown();
                    setInterval(loadDashboardData, 300000);
                    setInterval(updatePaydayCountdown, 3600000);
                }
            }
            
            checkLoadingScreen();
        });

        // Animated number counting function
        function animateNumber(element, start, end, duration = 2000, prefix = '', suffix = '') {
            if (!element) return;
            
            const startTime = performance.now();
            const difference = end - start;
            
            function step(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = start + (difference * easeOutQuart);
                
                if (suffix === '%') {
                    element.textContent = prefix + Math.round(current) + suffix;
                } else {
                    element.textContent = prefix + current.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + suffix;
                }
                
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            }
            
            requestAnimationFrame(step);
        }

        function loadDashboardData() {
            // Show loading screen for data refresh (but only if not initial load)
            if (window.budgetlyLoader && document.body.classList.contains('loaded')) {
                window.budgetlyLoader.show();
            }

            fetch('../api/personal_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateDashboardUI(data);
                        // Load enhanced insights after main dashboard data
                        loadEnhancedInsights();
                        
                        // Mark body as loaded after first successful load
                        document.body.classList.add('loaded');
                    } else {
                        console.error('Failed to load dashboard data:', data.message);
                        showSnackbar('Failed to load dashboard data', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                    showSnackbar('Error loading dashboard data', 'error');
                })
                .finally(() => {
                    // Hide loading screen after data processing
                    if (window.budgetlyLoader) {
                        setTimeout(() => {
                            window.budgetlyLoader.hide();
                        }, 500);
                    }
                });
        }

        // Load enhanced insights for the dashboard
        function loadEnhancedInsights() {
            fetch('../api/enhanced_insights_data.php?action=dashboard_insights')
                .then(response => response.json())
                .then(insights => {
                    if (insights && Array.isArray(insights)) {
                        updateDashboardInsights(insights);
                    } else {
                        console.error('Invalid insights data:', insights);
                        showDefaultInsights();
                    }
                })
                .catch(error => {
                    console.error('Error fetching insights:', error);
                    showDefaultInsights();
                });
        }

        // Update dashboard insights display
        function updateDashboardInsights(insights) {
            const insightsGrid = document.getElementById('insightsGrid');
            if (!insightsGrid) return;
            
            let html = '';
            insights.slice(0, 4).forEach(insight => {
                html += `
                    <div class="insight-card ${insight.type}">
                        <div class="insight-icon">${insight.icon}</div>
                        <div class="insight-content">
                            <h4>${insight.title}</h4>
                            <p>${insight.message}</p>
                            <button class="insight-action" onclick="window.location.href='${insight.link}'">${insight.action}</button>
                        </div>
                    </div>
                `;
            });
            
            // Add "More Insights" card if there are more than 4 insights
            if (insights.length > 4) {
                html += `
                    <div class="insight-card more">
                        <div class="insight-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="insight-content">
                            <h4>More Insights Available</h4>
                            <p>Discover ${insights.length - 4} additional insights to optimize your finances.</p>
                            <button class="insight-action" onclick="window.location.href='insights.php'">View All Insights</button>
                        </div>
                    </div>
                `;
            }
            
            insightsGrid.innerHTML = html;
        }

        // Show default insights if enhanced insights fail to load
        function showDefaultInsights() {
            const insightsGrid = document.getElementById('insightsGrid');
            if (!insightsGrid) return;
            
            insightsGrid.innerHTML = `
                <div class="insight-card tip">
                    <div class="insight-icon"><i class="fas fa-lightbulb"></i></div>
                    <div class="insight-content">
                        <h4>Track Your Progress</h4>
                        <p>Start tracking your expenses to get personalized financial insights.</p>
                        <button class="insight-action" onclick="window.location.href='personal-expense.php'">Add Expenses</button>
                    </div>
                </div>
                <div class="insight-card info">
                    <div class="insight-icon"><i class="fas fa-bullseye"></i></div>
                    <div class="insight-content">
                        <h4>Set Financial Goals</h4>
                        <p>Create savings goals to stay motivated and track your progress.</p>
                        <button class="insight-action" onclick="window.location.href='savings.php'">Create Goals</button>
                    </div>
                </div>
                <div class="insight-card success">
                    <div class="insight-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="insight-content">
                        <h4>Smart Insights Available</h4>
                        <p>Get AI-powered insights and predictions based on your financial data.</p>
                        <button class="insight-action" onclick="window.location.href='insights.php'">View Insights</button>
                    </div>
                </div>
            `;
        }

        function updateDashboardUI(data) {
            const overview = data.financial_overview;
            const salary = data.salary;
            
            // Update welcome message with real name
            const welcomeElement = document.getElementById('welcomeMessage');
            if (welcomeElement && data.user) {
                welcomeElement.textContent = `Welcome back, ${data.user.first_name}!`;
            }
            
            // Update financial overview cards with animation
            const currentBalance = overview.available_balance || 0;
            const monthlyIncome = overview.monthly_income || 0;
            const monthlyExpenses = overview.monthly_expenses || 0;
            const autoSavings = calculateAutoSavings(data);
            
            animateNumber(document.getElementById('currentBalance'), 0, currentBalance, 2000, '₵');
            animateNumber(document.getElementById('monthlyIncome'), 0, monthlyIncome, 2000, '₵');
            animateNumber(document.getElementById('monthlyExpenses'), 0, monthlyExpenses, 2000, '₵');
            animateNumber(document.getElementById('autoSavings'), 0, autoSavings, 2000, '₵');
            
            // Update status text
            updateStatusText(data);
            
            // Update salary hero section
            updateSalaryHero(data);
            
            // Update recent transactions - fetch comprehensive data
            loadRecentTransactionsData();
            
            // Update savings goals
            updateSavingsGoals(data.savings_goals || []);
            
            // Update payday countdown with real data
            updatePaydayCountdownData(data);
        }

        function calculateAutoSavings(data) {
            // Return actual total savings for the current month
            if (data.savings_overview) {
                return data.savings_overview.monthly_contributions || 0;
            }
            const overview = data.financial_overview;
            return overview.total_savings_this_month || 0;
        }

        function updateStatusText(data) {
            const overview = data.financial_overview;
            
            // Balance change
            const balanceChangeEl = document.getElementById('balanceChange');
            if (balanceChangeEl) {
                if (overview.available_balance > 0) {
                    balanceChangeEl.textContent = 'Available to spend';
                    balanceChangeEl.className = 'change positive';
                } else if (overview.available_balance < 0) {
                    balanceChangeEl.textContent = 'Overspent this month';
                    balanceChangeEl.className = 'change negative';
                } else {
                    balanceChangeEl.textContent = 'Balanced budget';
                    balanceChangeEl.className = 'change';
                }
            }
            
            // Next salary info
            const nextSalaryEl = document.getElementById('nextSalaryDate');
            if (nextSalaryEl && data.salary) {
                if (data.salary_confirmed) {
                    nextSalaryEl.textContent = 'Salary confirmed for this month';
                } else {
                    const payDate = new Date(data.salary.next_pay_date);
                    nextSalaryEl.textContent = `Next: ${payDate.toLocaleDateString()}`;
                }
            }
            
            // Budget remaining
            const budgetRemainingEl = document.getElementById('budgetRemaining');
            if (budgetRemainingEl) {
                const remaining = overview.monthly_income - overview.monthly_expenses;
                if (remaining > 0) {
                    budgetRemainingEl.textContent = `₵${remaining.toFixed(2)} remaining`;
                } else {
                    budgetRemainingEl.textContent = `₵${Math.abs(remaining).toFixed(2)} overspent`;
                }
            }
            
            // Savings status
            const savingsPercentageEl = document.getElementById('savingsPercentage');
            if (savingsPercentageEl) {
                let totalSaved = 0;
                let targetSavings = 0;
                
                if (data.savings_overview) {
                    totalSaved = data.savings_overview.monthly_contributions || 0;
                    targetSavings = data.savings_overview.monthly_target || 0;
                } else {
                    totalSaved = overview.total_savings_this_month || 0;
                    const allocation = data.budget_allocation;
                    if (allocation && overview.monthly_income > 0) {
                        const savingsAllocation = allocation.find(a => a.category_type === 'savings');
                        if (savingsAllocation) {
                            targetSavings = savingsAllocation.allocated_amount || 0;
                        }
                    }
                }
                
                if (targetSavings > 0) {
                    const progressPercentage = (totalSaved / targetSavings * 100).toFixed(1);
                    
                    if (totalSaved >= targetSavings) {
                        savingsPercentageEl.textContent = `${progressPercentage}% of target (Goal achieved!)`;
                        savingsPercentageEl.className = 'change positive';
                    } else if (progressPercentage >= 75) {
                        savingsPercentageEl.textContent = `${progressPercentage}% of monthly target`;
                        savingsPercentageEl.className = 'change positive';
                    } else if (progressPercentage >= 50) {
                        savingsPercentageEl.textContent = `${progressPercentage}% of monthly target`;
                        savingsPercentageEl.className = 'change';
                    } else {
                        savingsPercentageEl.textContent = `${progressPercentage}% of monthly target`;
                        savingsPercentageEl.className = 'change negative';
                    }
                } else {
                    savingsPercentageEl.textContent = totalSaved > 0 ? 'Savings recorded' : 'No savings target set';
                }
            }
        }

        function updateSalaryHero(data) {
            const salaryAmountEl = document.getElementById('monthlySalaryHero');
            if (salaryAmountEl && data.salary) {
                const amount = data.salary.monthly_salary || 0;
                salaryAmountEl.textContent = `Monthly Salary: ₵${amount.toLocaleString()}`;
            }
        }

        function loadRecentTransactionsData() {
            // Only use confirmed transactions from dashboard API for both expenses and incomes
            fetch('../api/personal_dashboard_data.php')
                .then(response => response.json())
                .then(dashboardData => {
                    let allTransactions = [];
                    if (dashboardData.success && dashboardData.recent_transactions) {
                        allTransactions = dashboardData.recent_transactions.map(txn => ({
                            id: txn.id,
                            amount: parseFloat(txn.amount),
                            description: txn.description || (txn.type === 'income' ? 'Salary Payment' : 'Expense'),
                            type: txn.type,
                            category_name: txn.category || (txn.type === 'income' ? 'Salary Income' : 'Uncategorized'),
                            date: txn.date || txn.created_at,
                            created_at: txn.created_at,
                            payment_method: txn.payment_method || (txn.type === 'income' ? 'bank' : 'cash')
                        }));
                    }
                    // Sort by newest first and take only the most recent 10
                    allTransactions.sort((a, b) => {
                        const dateA = new Date(a.created_at || a.date);
                        const dateB = new Date(b.created_at || b.date);
                        return dateB - dateA;
                    });
                    const recentTransactions = allTransactions.slice(0, 10);
                    updateRecentTransactions(recentTransactions);
                })
                .catch(error => {
                    console.error('Error fetching recent transactions:', error);
                    updateRecentTransactions([]);
                });
        }

        function updateRecentTransactions(transactions) {
            const transactionsContainer = document.getElementById('recentTransactions');
            if (!transactionsContainer) return;
            
            if (!transactions || transactions.length === 0) {
                transactionsContainer.innerHTML = `
                    <div class="no-transactions">
                        <div class="no-transactions-icon"><i class="fas fa-receipt"></i></div>
                        <p>No recent transactions</p>
                        <small>Your recent income and expenses will appear here</small>
                    </div>
                `;
                return;
            }
            
            // Sort transactions by newest first (using created_at or date)
            transactions.sort((a, b) => {
                const dateA = new Date(a.created_at || a.expense_date || a.date);
                const dateB = new Date(b.created_at || b.expense_date || b.date);
                return dateB - dateA;
            });
            
            const transactionsHTML = transactions.map(transaction => {
                const isIncome = transaction.type === 'income';
                const isExpense = transaction.type === 'expense';
                const amount = parseFloat(transaction.amount);
                
                // Format the transaction date - use created_at for more accurate timing
                let transactionDate;
                if (transaction.created_at) {
                    transactionDate = new Date(transaction.created_at);
                } else if (transaction.expense_date) {
                    transactionDate = new Date(transaction.expense_date);
                } else {
                    transactionDate = new Date();
                }
                
                const timeAgo = getTimeAgo(transactionDate);
                
                // Determine icon and styling based on transaction type
                let icon, amountClass, amountPrefix;
                if (isIncome) {
                    icon = '<i class="fas fa-plus-circle"></i>';
                    amountClass = 'income';
                    amountPrefix = '+';
                } else {
                    icon = '<i class="fas fa-minus-circle"></i>';
                    amountClass = 'expense';
                    amountPrefix = '-';
                }
                
                return `
                    <div class="transaction-item">
                        <div class="transaction-icon ${amountClass}">${icon}</div>
                        <div class="transaction-details">
                            <div class="transaction-name">${escapeHtml(transaction.description || 'Transaction')}</div>
                            <div class="transaction-category">${escapeHtml(transaction.category_name || 'Uncategorized')}</div>
                            <div class="transaction-time">${timeAgo}</div>
                        </div>
                        <div class="transaction-amount ${amountClass}">${amountPrefix}₵${amount.toFixed(2)}</div>
                    </div>
                `;
            }).join('');
            
            transactionsContainer.innerHTML = transactionsHTML;
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffTime = now - date;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
            const diffMinutes = Math.floor(diffTime / (1000 * 60));
            
            if (diffDays > 0) {
                return diffDays === 1 ? '1 day ago' : `${diffDays} days ago`;
            } else if (diffHours > 0) {
                return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
            } else if (diffMinutes > 0) {
                return diffMinutes === 1 ? '1 minute ago' : `${diffMinutes} minutes ago`;
            } else {
                return 'Just now';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function updateSavingsGoals(goals) {
            const savingsContainer = document.getElementById('savingsGoals');
            if (!savingsContainer) return;
            
            if (!goals || goals.length === 0) {
                savingsContainer.innerHTML = `
                    <div class="no-goals">
                        <div class="no-goals-icon"><i class="fas fa-bullseye"></i></div>
                        <p>No savings goals set</p>
                        <small>Create your first savings goal to track your progress</small>
                        <button class="create-goal-btn" onclick="showCreateGoalModal()">Create Goal</button>
                    </div>
                `;
                return;
            }
            
            const goalsHTML = goals.map(goal => {
                const progressPercentage = goal.progress_percentage || 0;
                const isOnTrack = goal.is_on_track;
                const timeToTarget = getTimeToTarget(goal.target_date);
                const currentAmount = goal.current_amount || 0;
                const targetAmount = goal.target_amount || 0;
                const remaining = targetAmount - currentAmount;
                const priorityClass = goal.priority === 'high' ? 'high-priority' : goal.priority === 'medium' ? 'medium-priority' : 'low-priority';
                const goalIcon = getGoalIcon(goal.goal_type);
                let statusIndicator = '';
                let progressColor = '#10b981';
                if (progressPercentage >= 100) {
                    statusIndicator = '<span class="goal-status completed">✓ Completed</span>';
                    progressColor = '#059669';
                } else if (progressPercentage >= 75) {
                    statusIndicator = '<span class="goal-status on-track"><i class="fas fa-chart-line"></i> On Track</span>';
                    progressColor = '#10b981';
                } else if (progressPercentage >= 50) {
                    statusIndicator = '<span class="goal-status moderate"><i class="fas fa-bolt"></i> In Progress</span>';
                    progressColor = '#f59e0b';
                } else if (progressPercentage >= 25) {
                    statusIndicator = '<span class="goal-status slow"><i class="fas fa-clock"></i> Getting Started</span>';
                    progressColor = '#f97316';
                } else {
                    statusIndicator = '<span class="goal-status behind">🚨 Needs Attention</span>';
                    progressColor = '#ef4444';
                }
                // Circular progress SVG
                const radius = 32;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (progressPercentage / 100) * circumference;
                return `
                <div class="goal-item ${priorityClass}">
                    <div class="goal-progress-circle">
                        <svg>
                            <circle cx="35" cy="35" r="32" stroke="#e5e7eb" stroke-width="6" fill="none" />
                            <circle cx="35" cy="35" r="32" stroke="${progressColor}" stroke-width="7" fill="none" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}" stroke-linecap="round" />
                        </svg>
                        <div class="goal-progress-percentage">${Math.round(progressPercentage)}%</div>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="goal-header" style="margin-bottom:0.2rem;">
                            <span class="goal-name" style="font-size:1.1rem; font-weight:700;">${goalIcon} ${escapeHtml(goal.goal_name)}</span>
                            <span class="goal-interval" style="font-size:0.85rem;">${timeToTarget}</span>
                        </div>
                        <div style="font-size:1.05rem; font-weight:600; color:#222; margin-bottom:0.2rem;">
                            ₵${currentAmount.toLocaleString()} <span style="font-size:0.95rem; font-weight:400; color:#888;">/ ₵${targetAmount.toLocaleString()}</span>
                        </div>
                        <div class="goal-remaining" style="margin-bottom:0.3rem;">
                            ${remaining > 0 ? `₵${remaining.toLocaleString()} left to reach goal` : 'Goal achieved! <i class="fas fa-trophy"></i>'}
                        </div>
                        <div class="goal-status-section" style="margin-top:0.5rem; padding-top:0.5rem; border-top:1px solid #e5e7eb;">
                            ${statusIndicator}
                            <button class="goal-view-btn" onclick="viewGoalDetails(${goal.id})">View Details</button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
            
            savingsContainer.innerHTML = goalsHTML;
        }

        function getTimeToTarget(targetDate) {
            if (!targetDate) return 'No deadline';
            
            const target = new Date(targetDate);
            const now = new Date();
            const diffTime = target.getTime() - now.getTime();
            const diffMonths = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffMonths > 12) {
                const years = Math.ceil(diffMonths / 12);
                return `${years} year${years > 1 ? 's' : ''} left`;
            } else if (diffMonths > 1) {
                return `${diffMonths} month${diffMonths > 1 ? 's' : ''} left`;
            } else if (diffDays > 0) {
                return `${diffDays} day${diffDays > 1 ? 's' : ''} left`;
            } else {
                return 'Due now!';
            }
        }

        function getGoalIcon(goalType) {
            const iconMap = {
                'emergency_fund': '<i class="fas fa-shield-alt"></i>',
                'vacation': '<i class="fas fa-plane"></i>',
                'car': '<i class="fas fa-car"></i>',
                'house': '<i class="fas fa-home"></i>',
                'education': '<i class="fas fa-graduation-cap"></i>',
                'other': '<i class="fas fa-piggy-bank"></i>'
            };
            return iconMap[goalType] || '<i class="fas fa-piggy-bank"></i>';
        }

        function addToGoal(goalId) {
            // Redirect to savings page for goal management
            window.location.href = `savings.php?goal=${goalId}`;
        }

        function editGoal(goalId) {
            // Redirect to savings page for goal editing
            window.location.href = `savings.php?edit=${goalId}`;
        }

        function viewGoalDetails(goalId) {
            // Navigate to savings page to view detailed goal information
            window.location.href = `savings.php?view=${goalId}`;
        }

        function showCreateGoalModal() {
            // Navigate to savings page to create a new goal
            window.location.href = 'savings.php';
        }

        // Navigation functions for insight actions
        function navigateToSavings() {
            window.location.href = 'savings.php';
        }

        function navigateToBudget() {
            window.location.href = 'budget.php';
        }

        function updatePaydayCountdownData(data) {
            if (!data.salary || !data.salary.next_pay_date) {
                // No salary data, use default countdown
                updatePaydayCountdown();
                return;
            }
            
            const nextPayDate = new Date(data.salary.next_pay_date);
            const today = new Date();
            const timeDiff = nextPayDate.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            // Update countdown displays
            const daysElement = document.getElementById('daysUntilPayLarge');
            const payDateElement = document.getElementById('payDateTextLarge');
            
            if (daysElement) {
                if (daysDiff <= 0) {
                    daysElement.textContent = '0';
                    if (payDateElement) {
                        payDateElement.textContent = 'Salary is due today!';
                    }
                } else {
                    animateNumber(daysElement, 0, daysDiff, 1000, '', '');
                    if (payDateElement) {
                        payDateElement.textContent = nextPayDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                }
            }
            
            // Calculate and animate progress
            updatePaydayProgress(data);
        }

        function updatePaydayProgress(data) {
            if (!data.salary) return;
            
            const payFrequency = data.salary.pay_frequency || 'monthly';
            let totalDays = 30; // default for monthly
            
            switch (payFrequency) {
                case 'weekly': totalDays = 7; break;
                case 'bi-weekly': totalDays = 14; break;
                case 'monthly': totalDays = 30; break;
                default: totalDays = 30;
            }
            
            const nextPayDate = new Date(data.salary.next_pay_date);
            const today = new Date();
            const daysDiff = Math.ceil((nextPayDate.getTime() - today.getTime()) / (1000 * 3600 * 24));
            
            const progressPercentage = Math.max(0, Math.min(100, ((totalDays - daysDiff) / totalDays) * 100));
            
            // Update progress ring
            const circle = document.querySelector('.progress-ring-circle');
            const radius = 60;
            const circumference = 2 * Math.PI * radius;
            const strokeDashoffset = circumference - (progressPercentage / 100) * circumference;
            
            if (circle) {
                circle.style.strokeDashoffset = strokeDashoffset;
            }
            
            const progressEl = document.getElementById('payProgressPercentageLarge');
            if (progressEl) {
                animateNumber(progressEl, 0, Math.round(progressPercentage), 1500, '', '%');
            }
        }

        function updatePaydayCountdown() {
            // Fallback function when no salary data is available
            const daysElement = document.getElementById('daysUntilPayLarge');
            const payDateElement = document.getElementById('payDateTextLarge');
            
            if (daysElement) {
                daysElement.textContent = '--';
            }
            if (payDateElement) {
                payDateElement.textContent = 'Set up your salary to see countdown';
            }
            
            const progressEl = document.getElementById('payProgressPercentageLarge');
            if (progressEl) {
                progressEl.textContent = '0%';
            }
        }

        // Theme functionality
        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            dropdown.classList.toggle('show');
        }

        function changeTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            
            // Update active theme option
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`[data-theme="${theme}"]`).classList.add('active');
            
            // Close dropdown
            document.getElementById('themeDropdown').classList.remove('show');
            
            // Save theme preference
            localStorage.setItem('personalTheme', theme);
        }

        // Mobile Menu functionality
        function toggleMobileMenu() {
            const nav = document.getElementById('headerNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            nav.classList.toggle('mobile-open');
            toggle.classList.toggle('active');
        }

        function closeMobileMenu() {
            const nav = document.getElementById('headerNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            nav.classList.remove('mobile-open');
            toggle.classList.remove('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('headerNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                closeMobileMenu();
            }
        });

        // Close mobile menu when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // Add click listeners to nav items to close mobile menu
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', closeMobileMenu);
            });
        });

        // User menu functionality
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Modal functionality
        function showSalarySetupModal() {
            showModal('salarySetupModal');
        }

        // Load expense categories for the modal
        function loadExpenseCategories() {
            return fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_categories'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const categorySelect = document.querySelector('#addExpenseModal select[name="category_id"]');
                    if (categorySelect) {
                        // Clear existing options
                        categorySelect.innerHTML = '<option value="">Select category</option>';
                        
                        // The response has categories grouped by type: needs, wants, savings
                        const categoryGroups = data.categories || {};
                        
                        Object.keys(categoryGroups).forEach(groupKey => {
                            const categories = categoryGroups[groupKey];
                            if (categories && categories.length > 0) {
                                const optgroup = document.createElement('optgroup');
                                optgroup.label = groupKey.charAt(0).toUpperCase() + groupKey.slice(1);
                                
                                categories.forEach(category => {
                                    const option = document.createElement('option');
                                    option.value = category.id;
                                    const remaining = category.remaining_budget !== undefined ? category.remaining_budget : 0;
                                    option.textContent = `${category.name} (₵${remaining.toFixed(2)} remaining)`;
                                    optgroup.appendChild(option);
                                });
                                
                                categorySelect.appendChild(optgroup);
                            }
                        });
                    }
                } else {
                    console.error('Failed to load categories:', data.message);
                    showSnackbar('Failed to load categories', 'warning');
                }
            })
            .catch(error => {
                console.error('Error loading categories:', error);
                showSnackbar('Error loading categories', 'warning');
            });
        }

        function showAddIncomeModal() {
            showModal('addIncomeModal');
        }

        function showAddExpenseModal() {
            // Load categories first, then show modal
            loadExpenseCategories().then(() => {
                // Set default date to today
                const dateInput = document.querySelector('#addExpenseModal input[name="expense_date"]');
                if (dateInput) {
                    const today = new Date().toISOString().split('T')[0];
                    dateInput.value = today;
                }
                showModal('addExpenseModal');
            });
        }

        function showSalaryPaidModal() {
            showModal('salaryPaidModal');
        }

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        function confirmSalaryFromDashboard() {
            
            // Update button state
            const confirmBtn = document.querySelector('#salaryPaidModal .btn-primary');
            if (confirmBtn) {
                const originalText = confirmBtn.textContent;
                confirmBtn.textContent = 'Processing...';
                confirmBtn.disabled = true;
                
                // Make API call to confirm salary
                fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=confirm_salary_received'
                })
                .then(response => response.json())
                .then(result => {
                    
                    if (result.success) {
                        showSnackbar(result.message, 'success');
                        closeModal('salaryPaidModal');
                        
                        // Refresh dashboard data to show updated income
                        setTimeout(() => {
                            loadDashboardData();
                            updatePaydayCountdown();
                        }, 1000);
                    } else {
                        showSnackbar(result.message || 'Failed to confirm salary', 'error');
                        
                        // Reset button state
                        confirmBtn.textContent = originalText;
                        confirmBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error confirming salary:', error);
                    showSnackbar('Failed to confirm salary - please try again', 'error');
                    
                    // Reset button state
                    confirmBtn.textContent = originalText;
                    confirmBtn.disabled = false;
                });
            } else {
                // Fallback if button not found
                fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=confirm_salary_received'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSnackbar(result.message, 'success');
                        closeModal('salaryPaidModal');
                        setTimeout(() => loadDashboardData(), 1000);
                    } else {
                        showSnackbar(result.message || 'Failed to confirm salary', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showSnackbar('Failed to confirm salary', 'error');
                });
            }
        }

        function navigateToSalarySetup() {
            window.location.href = 'budget.php';
        }

        // Budget Template View (Dashboard)
        function showBudgetTemplateViewModal() {
            const container = document.getElementById('budgetTemplateViewContent');
            if (container) {
                container.innerHTML = `
                    <div style="text-align:center; padding: 24px 12px; color: var(--text-secondary);">
                        <div style="font-size: 2rem; margin-bottom: 8px;">⏳</div>
                        <div>Loading template...</div>
                    </div>
                `;
            }
            showModal('budgetTemplateViewModal');
            fetch('../api/budget_categories.php')
                .then(r => r.json())
                .then(data => {
                    if (!container) return;
                    if (data.success && data.allocation && (data.allocation.needs_percentage || data.allocation.wants_percentage || data.allocation.savings_percentage)) {
                        const alloc = data.allocation;
                        const monthly = parseFloat(alloc.monthly_salary || 0);
                        const needsPct = parseFloat(alloc.needs_percentage || 0);
                        const wantsPct = parseFloat(alloc.wants_percentage || 0);
                        const savingsPct = parseFloat(alloc.savings_percentage || 0);
                        const needsAmt = parseFloat(alloc.needs_amount || 0);
                        const wantsAmt = parseFloat(alloc.wants_amount || 0);
                        const savingsAmt = parseFloat(alloc.savings_amount || 0);
                        const totalPct = needsPct + wantsPct + savingsPct;
                        container.innerHTML = `
                            <div class="budget-template-hero">
                                <h4>Active Allocation ${totalPct === 100 ? '' : '<span class=\'pill\'>Not totaling 100%</span>'}</h4>
                                <small>Based on monthly income of ₵${(monthly||0).toLocaleString()}</small>
                                <div class="allocation-bar">
                                    <div class="allocation-segment needs" style="width: ${Math.max(0, needsPct)}%"></div>
                                    <div class="allocation-segment wants" style="width: ${Math.max(0, wantsPct)}%"></div>
                                    <div class="allocation-segment savings" style="width: ${Math.max(0, savingsPct)}%"></div>
                                </div>
                                <div class="allocation-legend">
                                    <div class="legend-item">
                                        <div class="legend-left">
                                            <span class="legend-dot needs"></span>
                                            <span class="legend-label">Needs</span>
                                            <span class="pill">${needsPct}%</span>
                                        </div>
                                        <div class="legend-amount">₵${needsAmt.toLocaleString()}</div>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-left">
                                            <span class="legend-dot wants"></span>
                                            <span class="legend-label">Wants</span>
                                            <span class="pill">${wantsPct}%</span>
                                        </div>
                                        <div class="legend-amount">₵${wantsAmt.toLocaleString()}</div>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-left">
                                            <span class="legend-dot savings"></span>
                                            <span class="legend-label">Savings</span>
                                            <span class="pill">${savingsPct}%</span>
                                        </div>
                                        <div class="legend-amount">₵${savingsAmt.toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        container.innerHTML = `
                            <div class="empty-template-state">
                                <div style="font-size: 2.2rem;">📋</div>
                                <h4>No budget template set</h4>
                                <p>Apply a template (e.g., 50/30/20) to allocate your income across Needs, Wants, and Savings.</p>
                            </div>
                        `;
                    }
                })
                .catch(() => {
                    if (!container) return;
                    container.innerHTML = `
                        <div style="text-align:center; padding: 24px 12px; color: var(--text-secondary);">
                            <div style="font-size: 2rem; margin-bottom: 8px;">❌</div>
                            <div>Failed to load budget template. Try again from the Budget page.</div>
                        </div>
                    `;
                });
        }

        // Snackbar notification function
        function showSnackbar(message, type = 'info') {
            // Remove existing snackbar if any
            const existingSnackbar = document.querySelector('.snackbar');
            if (existingSnackbar) {
                existingSnackbar.remove();
            }

            // Create new snackbar
            const snackbar = document.createElement('div');
            snackbar.className = `snackbar ${type}`;
            
            const icons = {
                success: '<i class="fas fa-check-circle"></i>',
                error: '<i class="fas fa-times-circle"></i>',
                warning: '<i class="fas fa-exclamation-triangle"></i>',
                info: '<i class="fas fa-info-circle"></i>'
            };
            
            snackbar.innerHTML = `
                <span class="snackbar-icon">${icons[type] || icons.info}</span>
                <span class="snackbar-message">${message}</span>
            `;
            
            document.body.appendChild(snackbar);
            
            // Show snackbar
            setTimeout(() => snackbar.classList.add('show'), 100);
            
            // Hide snackbar after 4 seconds
            setTimeout(() => {
                snackbar.classList.remove('show');
                setTimeout(() => snackbar.remove(), 300);
            }, 4000);
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.theme-selector')) {
                document.getElementById('themeDropdown').classList.remove('show');
            }
            if (!event.target.closest('.user-menu')) {
                document.getElementById('userDropdown').classList.remove('show');
            }
        });

        // Load saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('personalTheme') || 'default';
            changeTheme(savedTheme);
        });

        // Form handling for modals
        document.querySelectorAll('.modal-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const modalId = this.closest('.modal').id;
                const formData = new FormData(this);
                
                // Add loading state to submit button
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;
                
                switch(modalId) {
                    case 'salarySetupModal':
                        handleSalarySetup(formData, submitBtn, originalText, modalId);
                        break;
                    case 'addIncomeModal':
                        handleAddIncome(formData, submitBtn, originalText, modalId);
                        break;
                    case 'addExpenseModal':
                        handleAddExpense(formData, submitBtn, originalText, modalId);
                        break;
                    default:
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                        showSnackbar('Unknown form type', 'error');
                }
            });
        });

        // Handle add income form submission
        function handleAddIncome(formData, submitBtn, originalText, modalId) {
            // Data is already properly formatted from the form
            formData.append('action', 'add_income_source');
            
            fetch('../actions/salary_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSnackbar('Income source added successfully!', 'success');
                    closeModal(modalId);
                    document.getElementById(modalId).querySelector('form').reset();
                    
                    // Refresh dashboard data
                    setTimeout(() => {
                        loadDashboardData();
                    }, 500);
                } else {
                    showSnackbar(result.message || 'Failed to add income source', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding income:', error);
                showSnackbar('Error adding income. Please try again.', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        // Handle add expense form submission
        function handleAddExpense(formData, submitBtn, originalText, modalId) {
            // Data is already properly formatted from the form
            formData.append('action', 'add_expense');
            
            fetch('../actions/personal_expense_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSnackbar('Expense recorded successfully!', 'success');
                    closeModal(modalId);
                    document.getElementById(modalId).querySelector('form').reset();
                    
                    // Refresh dashboard data
                    setTimeout(() => {
                        loadDashboardData();
                    }, 500);
                } else {
                    showSnackbar(result.message || 'Failed to record expense', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding expense:', error);
                showSnackbar('Error recording expense. Please try again.', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        // Handle salary setup form submission (placeholder for now)
        function handleSalarySetup(formData, submitBtn, originalText, modalId) {
            // For now, just show success message
            // This would need proper implementation based on salary setup requirements
            setTimeout(() => {
                showSnackbar('Salary and budget setup saved successfully!', 'success');
                closeModal(modalId);
                document.getElementById(modalId).querySelector('form').reset();
                setTimeout(loadDashboardData, 500);
                
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1000);
        }

        // Allocation slider functionality for salary setup modal
        document.addEventListener('DOMContentLoaded', function() {
            const sliders = document.querySelectorAll('.allocation-slider');
            const salaryInput = document.querySelector('input[type="number"][step="0.01"]');
            
            function updateAllocation() {
                const salary = parseFloat(salaryInput?.value || 3600);
                let total = 0;
                
                sliders.forEach(slider => {
                    const percentage = parseInt(slider.value);
                    const amount = (salary * percentage) / 100;
                    
                    const percentSpan = slider.parentElement.querySelector('.allocation-percent');
                    const amountSpan = slider.parentElement.querySelector('.allocation-amount');
                    
                    if (percentSpan) percentSpan.textContent = percentage + '%';
                    if (amountSpan) amountSpan.textContent = '₵' + amount.toLocaleString();
                    
                    total += percentage;
                });
                
                const totalSpan = document.getElementById('totalAllocation');
                if (totalSpan) {
                    totalSpan.textContent = total + '%';
                    totalSpan.style.color = total === 100 ? '#059669' : '#ef4444';
                }
            }
            
            sliders.forEach(slider => {
                slider.addEventListener('input', updateAllocation);
            });
            
            if (salaryInput) {
                salaryInput.addEventListener('input', updateAllocation);
            }
            
            // Initial update
            updateAllocation();
        });

        // Modal functionality
        let modalAllTransactions = [];
        let modalFilteredTransactions = [];
        let modalCurrentPage = 1;
        const modalItemsPerPage = 15;

        function openTransactionsModal() {
            const modal = document.getElementById('transactionsModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Load transactions data
            loadModalTransactions();
        }

        function closeTransactionsModal() {
            const modal = document.getElementById('transactionsModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function loadModalTransactions() {
            // Show loading state
            document.getElementById('modalTransactionsList').innerHTML = `
                <div class="loading-state">
                    <div class="loading-icon">⏳</div>
                    <p>Loading transactions...</p>
                </div>
            `;

            // Use only confirmed transactions from dashboard API for both expenses and incomes
            fetch('../api/personal_dashboard_data.php')
                .then(response => response.json())
                .then(dashboardData => {
                    modalAllTransactions = [];
                    if (dashboardData.success && dashboardData.recent_transactions) {
                        modalAllTransactions = dashboardData.recent_transactions.map(txn => ({
                            id: txn.id,
                            amount: parseFloat(txn.amount),
                            description: txn.description || (txn.type === 'income' ? 'Salary Payment' : 'Expense'),
                            type: txn.type,
                            category_name: txn.category || (txn.type === 'income' ? 'Salary Income' : 'Uncategorized'),
                            date: txn.date || txn.created_at,
                            created_at: txn.created_at,
                            payment_method: txn.payment_method || (txn.type === 'income' ? 'bank' : 'cash')
                        }));
                    }
                    // Sort by newest first
                    modalAllTransactions.sort((a, b) => {
                        const dateA = new Date(a.created_at || a.date);
                        const dateB = new Date(b.created_at || b.date);
                        return dateB - dateA;
                    });
                    modalFilteredTransactions = [...modalAllTransactions];
                    updateModalStatistics();
                    displayModalTransactions();
                    populateModalFilters();
                })
                .catch(error => {
                    console.error('Error loading modal transactions:', error);
                    document.getElementById('modalTransactionsList').innerHTML = `
                        <div class="no-modal-transactions">
                            <div class="no-modal-transactions-icon">❌</div>
                            <p>Error loading transactions</p>
                            <small>Please try again</small>
                        </div>
                    `;
                });
        }

        function updateModalStatistics() {
            const totalCount = modalFilteredTransactions.length;
            const totalIncome = modalFilteredTransactions
                .filter(t => t.type === 'income')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
            const totalExpenses = modalFilteredTransactions
                .filter(t => t.type === 'expense')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
            const netAmount = totalIncome - totalExpenses;
            
            document.getElementById('modalTotalTransactions').textContent = totalCount;
            document.getElementById('modalTotalIncome').textContent = `₵${totalIncome.toLocaleString()}`;
            document.getElementById('modalTotalExpenses').textContent = `₵${totalExpenses.toLocaleString()}`;
            document.getElementById('modalNetAmount').textContent = `₵${netAmount.toLocaleString()}`;
            document.getElementById('modalNetAmount').style.color = netAmount >= 0 ? '#10b981' : '#ef4444';
        }

        function displayModalTransactions() {
            const container = document.getElementById('modalTransactionsList');
            const startIndex = (modalCurrentPage - 1) * modalItemsPerPage;
            const endIndex = startIndex + modalItemsPerPage;
            const pageTransactions = modalFilteredTransactions.slice(startIndex, endIndex);
            
            if (pageTransactions.length === 0) {
                container.innerHTML = `
                    <div class="no-modal-transactions">
                        <div class="no-modal-transactions-icon">📝</div>
                        <p>No transactions found</p>
                        <small>Try adjusting your filters</small>
                    </div>
                `;
                document.getElementById('modalPagination').style.display = 'none';
                return;
            }
            
            const transactionsHTML = pageTransactions.map(transaction => {
                const isIncome = transaction.type === 'income';
                const amount = parseFloat(transaction.amount || 0);
                const date = new Date(transaction.created_at || transaction.date);
                
                return `
                    <div class="modal-transaction-item">
                        <div class="modal-transaction-icon ${transaction.type}">
                            ${isIncome ? '💰' : '💸'}
                        </div>
                        <div class="modal-transaction-details">
                            <div class="modal-transaction-title">${escapeHtml(transaction.description || 'Transaction')}</div>
                            <div class="modal-transaction-meta">
                                <span class="modal-transaction-category">${escapeHtml(transaction.category_name || 'Uncategorized')}</span>
                                <span>•</span>
                                <span>${transaction.payment_method || 'Unknown'}</span>
                            </div>
                            <div class="modal-transaction-date">${formatModalDate(date)}</div>
                        </div>
                        <div class="modal-transaction-amount ${transaction.type}">
                            ${isIncome ? '+' : '-'}₵${amount.toLocaleString()}
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = transactionsHTML;
            updateModalPagination();
        }

        function updateModalPagination() {
            const totalPages = Math.ceil(modalFilteredTransactions.length / modalItemsPerPage);
            const container = document.getElementById('modalPagination');
            
            if (totalPages <= 1) {
                container.style.display = 'none';
                return;
            }
            
            container.style.display = 'block';
            let paginationHTML = '';
            
            // Previous button
            if (modalCurrentPage > 1) {
                paginationHTML += `<button onclick="changeModalPage(${modalCurrentPage - 1})">Previous</button>`;
            }
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === modalCurrentPage || i === 1 || i === totalPages || (i >= modalCurrentPage - 1 && i <= modalCurrentPage + 1)) {
                    paginationHTML += `<button class="${i === modalCurrentPage ? 'active' : ''}" onclick="changeModalPage(${i})">${i}</button>`;
                } else if (i === modalCurrentPage - 2 || i === modalCurrentPage + 2) {
                    paginationHTML += `<span>...</span>`;
                }
            }
            
            // Next button
            if (modalCurrentPage < totalPages) {
                paginationHTML += `<button onclick="changeModalPage(${modalCurrentPage + 1})">Next</button>`;
            }
            
            container.innerHTML = paginationHTML;
        }

        function changeModalPage(page) {
            modalCurrentPage = page;
            displayModalTransactions();
        }

        function populateModalFilters() {
            const categoryFilter = document.getElementById('modalCategoryFilter');
            const categories = [...new Set(modalAllTransactions.map(t => t.category_name).filter(Boolean))];
            
            categoryFilter.innerHTML = '<option value="">All Categories</option>';
            categories.forEach(category => {
                categoryFilter.innerHTML += `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`;
            });
        }

        function filterModalTransactions() {
            const typeFilter = document.getElementById('modalTypeFilter').value;
            const categoryFilter = document.getElementById('modalCategoryFilter').value;
            const dateFrom = document.getElementById('modalDateFromFilter').value;
            const dateTo = document.getElementById('modalDateToFilter').value;
            
            modalFilteredTransactions = modalAllTransactions.filter(transaction => {
                if (typeFilter && transaction.type !== typeFilter) return false;
                if (categoryFilter && transaction.category_name !== categoryFilter) return false;
                
                const transactionDate = new Date(transaction.created_at || transaction.date);
                if (dateFrom && transactionDate < new Date(dateFrom)) return false;
                if (dateTo && transactionDate > new Date(dateTo + 'T23:59:59')) return false;
                
                return true;
            });
            
            // Re-sort filtered transactions
            modalFilteredTransactions.sort((a, b) => {
                const dateA = new Date(a.created_at || a.date);
                const dateB = new Date(b.created_at || b.date);
                return dateB - dateA;
            });
            
            modalCurrentPage = 1;
            updateModalStatistics();
            displayModalTransactions();
        }

        function clearModalFilters() {
            document.getElementById('modalTypeFilter').value = '';
            document.getElementById('modalCategoryFilter').value = '';
            document.getElementById('modalDateFromFilter').value = '';
            document.getElementById('modalDateToFilter').value = '';
            
            modalFilteredTransactions = [...modalAllTransactions];
            modalCurrentPage = 1;
            updateModalStatistics();
            displayModalTransactions();
        }

        function formatModalDate(date) {
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('transactionsModal');
            if (event.target === modal) {
                closeTransactionsModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTransactionsModal();
            }
        });

        // Test function for loading screen (can be called from browser console)
        window.testDashboardLoadingScreen = function(duration = 3000) {
            if (window.budgetlyLoader) {
                console.log('Testing dashboard loading screen for', duration, 'ms');
                window.budgetlyLoader.show();
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                    console.log('Dashboard loading screen test complete');
                }, duration);
            } else {
                console.log('Loading screen not available');
            }
        };
    </script>

    <!-- Transactions Modal -->
    <div id="transactionsModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2>💸 All Transactions</h2>
                <button class="modal-close" onclick="closeTransactionsModal()">&times;</button>
            </div>
            
            <div class="modal-stats">
                <div class="stat-card">
                    <div class="stat-value" id="modalTotalTransactions">0</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="modalTotalIncome">₵0.00</div>
                    <div class="stat-label">Income</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="modalTotalExpenses">₵0.00</div>
                    <div class="stat-label">Expenses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="modalNetAmount">₵0.00</div>
                    <div class="stat-label">Net</div>
                </div>
            </div>
            
            <div class="modal-filters">
                <div class="filter-row">
                    <select id="modalTypeFilter" onchange="filterModalTransactions()">
                        <option value="">All Types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expenses</option>
                    </select>
                    <select id="modalCategoryFilter" onchange="filterModalTransactions()">
                        <option value="">All Categories</option>
                    </select>
                    <input type="date" id="modalDateFromFilter" onchange="filterModalTransactions()">
                    <input type="date" id="modalDateToFilter" onchange="filterModalTransactions()">
                    <button onclick="clearModalFilters()" class="clear-btn">Clear</button>
                </div>
            </div>
            
            <div class="modal-contentx">
                <div class="modal-transactions-list" id="modalTransactionsList">
                    <div class="loading-state">
                        <div class="loading-icon">⏳</div>
                        <p>Loading transactions...</p>
                    </div>
                </div>
                <div class="modal-pagination" id="modalPagination" style="display: none;">
                    <!-- Pagination will be inserted here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Screen Script -->
    <script src="../public/js/loading.js"></script>
    <!-- Privacy System Script -->
    <script src="../public/js/privacy.js"></script>
    <!-- Walkthrough System Scripts -->
    <script src="../public/js/walkthrough.js"></script>
    <!-- Mobile Navigation Script -->
    <script src="../public/js/mobile-nav.js"></script>
    
    <script>
        // Test function for loading screen (can be called from browser console)
        window.testDashboardLoadingScreen = function(duration = 3000) {
            if (window.budgetlyLoader) {
                console.log('Testing dashboard loading screen for', duration, 'ms');
                window.budgetlyLoader.show();
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                    console.log('Dashboard loading screen test complete');
                }, duration);
            } else {
                console.log('Loading screen not available');
            }
        };

        // Emergency function to hide loading screen (can be called from browser console)
        window.hideLoadingScreen = function() {
            if (window.budgetlyLoader) {
                window.budgetlyLoader.hide();
                console.log('Loading screen forcefully hidden');
            } else {
                console.log('Loading screen not available');
            }
        };

        // Keyboard shortcut to hide loading screen (Escape key)
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                window.hideLoadingScreen();
            }
        });

        // Global error handler to ensure loading screen is hidden
        window.addEventListener('error', function(event) {
            console.error('Global error caught:', event.error);
            if (window.budgetlyLoader) {
                window.budgetlyLoader.hide();
            }
        });

        // Promise rejection handler
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            if (window.budgetlyLoader) {
                window.budgetlyLoader.hide();
            }
        });
    </script>
</body>
</html>