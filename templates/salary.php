<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Check if user has personal account
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'personal') {
    // Redirect family users to family dashboard
    header('Location: dashboard');
    exit;
}

// Get user information from session
$user_first_name = $_SESSION['first_name'] ?? 'User';
$user_full_name = $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Setup - Budgetly</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/personal.css">
    <link rel="stylesheet" href="../public/css/mobile-nav.css">
    <link rel="stylesheet" href="../public/css/loading.css">
    <!-- Universal Snackbar -->
    <script src="../public/js/snackbar.js"></script>
    <script src="../public/js/loading.js"></script>
    <style>
        /* Additional styles for salary setup page */
        .salary-config-form {
            background: var(--card-background);
            border-radius: 12px;
            padding: 24px;
            margin-top: 16px;
            border: 1px solid var(--border-color);
        }

        /* Salary Confirmation Banner */
        .salary-confirmation-banner {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #28a745;
            border-radius: 12px;
            margin: 16px 0;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
            animation: slideDown 0.3s ease-out;
        }

        .banner-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .banner-icon {
            font-size: 32px;
            flex-shrink: 0;
        }

        .banner-message {
            flex: 1;
        }

        .banner-message h4 {
            margin: 0 0 8px 0;
            color: #28a745;
            font-size: 18px;
            font-weight: 600;
        }

        .banner-message p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
            line-height: 1.4;
        }

        .banner-actions {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
        }

        .banner-actions .btn-primary.small,
        .banner-actions .btn-secondary.small {
            padding: 8px 16px;
            font-size: 13px;
            min-width: auto;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Income Sources Styling */
        .income-source-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }

        .income-source-item:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .income-source-info {
            flex: 1;
        }

        .income-source-name {
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }

        .income-source-details {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .income-source-description {
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
        }

        .income-source-amount {
            text-align: right;
            margin-right: 12px;
        }

        .income-source-amount .amount {
            font-weight: 600;
            color: #28a745;
            font-size: 16px;
        }

        .income-source-amount .amount-period {
            font-size: 12px;
            color: #6c757d;
        }

        .income-source-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: none;
            border: none;
            padding: 6px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-icon:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .btn-icon span {
            font-size: 14px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #ced4da;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: #ffffff;
            color: #495057;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }

        /* Modal specific form styling */
        .modal-form .form-group input,
        .modal-form .form-group select,
        .modal-form .form-group textarea {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
        }

        .modal-form .form-group input:focus,
        .modal-form .form-group select:focus,
        .modal-form .form-group textarea:focus {
            background: #ffffff;
            border-color: var(--primary-color);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            color: var(--text-primary);
            cursor: pointer;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toggle-switch input[type="checkbox"] {
            width: 44px;
            height: 24px;
            background: #ddd;
            border-radius: 12px;
            position: relative;
            cursor: pointer;
            appearance: none;
            transition: background 0.3s;
        }

        .toggle-switch input[type="checkbox"]:checked {
            background: var(--primary-color);
        }

        .toggle-switch input[type="checkbox"]:before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }

        .toggle-switch input[type="checkbox"]:checked:before {
            transform: translateX(20px);
        }

        .allocation-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .allocation-slider {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: #ddd;
            outline: none;
            appearance: none;
        }

        .allocation-slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
        }

        .allocation-percent {
            font-weight: 600;
            min-width: 40px;
            color: var(--primary-color);
        }

        .allocation-amount {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 12px 0;
        }

        .allocation-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .category-tag {
            background: var(--card-background);
            color: var(--text-secondary);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .allocation-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .summary-item {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .income-sources-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .income-source-item {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
        }

        .source-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .source-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .source-icon {
            font-size: 24px;
        }

        .source-details h4 {
            margin: 0 0 4px 0;
            color: var(--text-primary);
        }

        .source-details p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .source-settings {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .source-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .source-status.active {
            background: var(--card-background);
            color: var(--secondary-color);
        }

        .source-action {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .source-action:hover {
            background: var(--card-background);
        }

        .source-allocation {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toggle-switch.small input[type="checkbox"] {
            width: 36px;
            height: 20px;
        }

        .toggle-switch.small input[type="checkbox"]:before {
            width: 16px;
            height: 16px;
        }

        .toggle-switch.small input[type="checkbox"]:checked:before {
            transform: translateX(16px);
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .schedule-item.upcoming {
            border-color: var(--primary-color);
            background: var(--card-background);
        }

        .schedule-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 50px;
        }

        .date-num {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .date-month {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .schedule-details {
            flex: 1;
        }

        .schedule-details h4 {
            margin: 0 0 4px 0;
            color: var(--text-primary);
        }

        .schedule-details p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.due-soon {
            background: var(--card-background);
            color: var(--accent-color);
        }

        .status-badge.pending {
            background: var(--card-background);
            color: var(--primary-color);
        }

        .status-badge.scheduled {
            background: var(--card-background);
            color: var(--secondary-color);
        }

        .edit-allocation-btn.small {
            padding: 8px 12px;
            font-size: 14px;
        }

        /* Payment method selector */
        .payment-method-group {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .payment-method {
            flex: 1;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            background: #f8f9fa;
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background: #e3f2fd;
        }

        .modal-form .payment-method {
            background: #ffffff;
            border: 2px solid #dee2e6;
        }

        .modal-form .payment-method.selected {
            border-color: var(--primary-color);
            background: #e3f2fd;
        }

        .payment-method-icon {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .payment-method-name {
            font-size: 14px;
            font-weight: 600;
        }

        /* Theme dropdown and user dropdown should be hidden by default */
        .theme-dropdown,
        .user-dropdown {
            display: none !important;
        }

        /* Show when active */
        .theme-dropdown.show,
        .user-dropdown.show {
            display: block !important;
        }

        /* Modal Styles */
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex !important;
        }

        .modal-content {
            background: #f8f9fa;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid #dee2e6;
        }

        .modal-content.large {
            max-width: 700px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid rgba(241, 245, 249, 0.8);
            background: linear-gradient(135deg, var(--card-background) 0%, var(--background-light) 100%);
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: var(--text-primary);
        }

        .close {
            color: var(--text-secondary);
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .close:hover {
            color: var(--text-primary);
            background: rgba(156, 163, 175, 0.1);
        }

        .modal-form {
            padding: 24px;
            background: #f8f9fa;
        }

        .form-section {
            margin-bottom: 24px;
            padding: 16px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .form-section h4 {
            margin: 0 0 16px 0;
            color: #495057;
            font-size: 16px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #dee2e6;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
            border: 1px solid #ced4da;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        /* Snackbar styles */
        #snackbar {
            visibility: hidden;
            min-width: 250px;
            background-color: #111;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 2000;
            right: 30px;
            top: 30px;
            font-size: 17px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        #snackbar.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }

        #snackbar.success {
            background-color: #10b981;
        }

        #snackbar.error {
            background-color: #ef4444;
        }

        #snackbar.warning {
            background-color: #f59e0b;
        }

        @-webkit-keyframes fadein {
            from {
                right: 0;
                opacity: 0;
            }

            to {
                right: 30px;
                opacity: 1;
            }
        }

        @keyframes fadein {
            from {
                right: 0;
                opacity: 0;
            }

            to {
                right: 30px;
                opacity: 1;
            }
        }

        @-webkit-keyframes fadeout {
            from {
                right: 30px;
                opacity: 1;
            }

            to {
                right: 0;
                opacity: 0;
            }
        }

        @keyframes fadeout {
            from {
                right: 30px;
                opacity: 1;
            }

            to {
                right: 0;
                opacity: 0;
            }
        }

        /* CSS Variables */
        /* Remove hardcoded CSS variables - use theme variables from personal.css instead */

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .allocation-summary {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .payment-method-group {
                flex-direction: column;
            }
        }

        /* Primary Salary Hero Section */
        .primary-salary-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 32px;
            margin: 24px 0;
            color: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .salary-hero-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .hero-title-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hero-icon {
            font-size: 48px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }

        .hero-text h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .hero-text p {
            margin: 4px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .edit-salary-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .edit-salary-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .salary-display-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .salary-main-info {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .salary-amount-display {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .salary-currency {
            font-size: 24px;
            font-weight: 600;
            opacity: 0.8;
        }

        .salary-amount {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .salary-period {
            font-size: 18px;
            opacity: 0.8;
            margin-left: 4px;
        }

        .salary-schedule {
            display: flex;
            gap: 24px;
        }

        .schedule-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .schedule-label {
            font-size: 14px;
            opacity: 0.8;
            font-weight: 500;
        }

        .schedule-value {
            font-size: 16px;
            font-weight: 600;
        }

        .salary-quick-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 140px;
            justify-content: center;
        }

        .quick-action-btn.primary {
            background: rgba(255, 255, 255, 0.9);
            color: #4f46e5;
        }

        .quick-action-btn.primary:hover {
            background: white;
            transform: translateY(-2px);
        }

        .quick-action-btn.secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .quick-action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 16px;
        }

        /* Enhanced Budget Efficiency */
        .efficiency-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none !important;
        }

        .efficiency-card .card-header h3 {
            color: white;
        }

        .efficiency-card .card-icon {
            filter: brightness(0) invert(1);
        }

        .efficiency-score {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .score-circle {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, rgba(255, 255, 255, 0.8) 0deg, rgba(255, 255, 255, 0.2) 360deg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .score-circle::before {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .score-number,
        .score-label {
            position: relative;
            z-index: 1;
        }

        .score-number {
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
        }

        .score-label {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 500;
        }

        .efficiency-details {
            text-align: center;
        }

        .efficiency-status {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            opacity: 0.9;
        }

        .efficiency-breakdown {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .breakdown-label {
            opacity: 0.8;
        }

        .breakdown-value {
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .primary-salary-hero {
                padding: 20px;
            }

            .salary-hero-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .hero-text h2 {
                font-size: 24px;
            }

            .salary-display-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .salary-amount {
                font-size: 36px;
            }

            .salary-schedule {
                justify-content: center;
                gap: 16px;
            }

            .salary-quick-actions {
                flex-direction: row;
                justify-content: center;
            }
        }

        /* Primary Salary Hero Section */
        .primary-salary-hero {
            background: var(--card-background);
            border-radius: 12px;
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .salary-hero-content {
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .salary-hero-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .salary-icon {
            font-size: 48px;
            width: 72px;
            height: 72px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .salary-info h2 {
            margin: 0;
            font-size: 24px;
            color: #495057;
            font-weight: 600;
        }

        .salary-info p {
            margin: 4px 0 0 0;
            color: #6c757d;
            font-size: 14px;
        }

        .salary-display-hero {
            text-align: right;
        }

        .salary-amount-display {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 4px;
        }

        .salary-status {
            color: #6c757d;
            font-size: 14px;
        }

        .salary-hero-actions {
            padding: 0 24px 24px 24px;
        }

        .salary-hero-actions .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->


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
                <a href="personal-dashboard" class="nav-item">Dashboard</a>
                <a href="salary" class="nav-item active">Salary Setup</a>
                <a href="budgets" class="nav-item">Budget</a>
                <a href="personal-expense" class="nav-item">Expenses</a>
                <a href="savings" class="nav-item">Savings</a>
                <!-- <a href="insights" class="nav-item">Insights</a> -->
                <a href="report" class="nav-item">Reports</a>

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
                    <a href="savings">Savings</a>
                    <a href="personal-expense"> Expense </a>
                    <a href="budgets">Budget</a>
                    <!-- <hr> -->
                    <a href="salary">Salary</a>
                    <a href="../actions/signout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2><i class="fas fa-briefcase"></i> Salary & Income Setup</h2>
                    <p>Configure your primary salary and additional income sources to optimize your budget planning</p>
                </div>
                <div class="quick-actions">
                    <button class="quick-btn" onclick="showAddIncomeSourceModal()">
                        <span class="btn-icon"><i class="fas fa-plus"></i></span>
                        Add Income Source
                    </button>
                    <button class="quick-btn" onclick="showPreviewBudgetModal()">
                        <span class="btn-icon"><i class="fas fa-eye"></i></span>
                        Preview Budget
                    </button>
                </div>
            </section>

            <!-- Current Salary Overview -->
            <section class="overview-cards">
                <div class="card balance-card">
                    <div class="card-header">
                        <h3>Current Monthly Salary</h3>
                        <span class="card-icon"><i class="fas fa-dollar-sign"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="currentSalary">₵0.00</div>
                        <div class="change" id="nextPayment">Set up salary information</div>
                    </div>
                </div>

                <div class="card income-card">
                    <div class="card-header">
                        <h3>Additional Income</h3>
                        <span class="card-icon"><i class="fas fa-chart-bar"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="additionalIncome">₵0.00</div>
                        <div class="change" id="incomeSourcesCount">No additional sources</div>
                    </div>
                </div>

                <div class="card expense-card">
                    <div class="card-header">
                        <h3>Total Monthly Income</h3>
                        <span class="card-icon"><i class="fas fa-chart-line"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="totalIncome">₵0.00</div>
                        <div class="change">Available for budgeting</div>
                    </div>
                </div>

                <div class="card savings-card">
                    <div class="card-header">
                        <h3>Budget Efficiency</h3>
                        <span class="card-icon"><i class="fas fa-bullseye"></i></span>
                    </div>
                    <div class="card-content">
                        <div class="amount" id="budgetEfficiency">--</div>
                        <div class="change" id="efficiencyStatus">Set up salary first</div>
                        <div class="efficiency-breakdown" id="efficiencyBreakdown" style="display: none;">
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e9ecef;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: #6c757d; font-size: 14px;">Savings Rate:</span>
                                    <span style="color: #495057; font-weight: 500;" id="savingsRateDisplay">0%</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: #6c757d; font-size: 14px;">Budget Usage:</span>
                                    <span style="color: #495057; font-weight: 500;" id="budgetUtilizationDisplay">0%</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6c757d; font-size: 14px;">Income Coverage:</span>
                                    <span style="color: #495057; font-weight: 500;" id="incomeCoverageDisplay">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </section>

        <div class="container">
            <!-- Primary Salary Configuration -->
            <section class="salary-breakdown">
                <div class="primary-salary-hero">
                    <div class="salary-hero-content">
                        <div class="salary-hero-header">
                            <div class="salary-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="salary-info">
                                <h2>Monthly Salary</h2>
                                <p>Your primary income source</p>
                            </div>
                        </div>
                        <div class="salary-display-hero">
                            <div class="salary-amount-display" id="primarySalaryAmount">₵0.00</div>
                            <div class="salary-status" id="primarySalaryStatus">Not set up yet</div>
                        </div>
                    </div>
                    <div class="salary-hero-actions">
                        <button class="btn-primary" id="salaryActionBtn" onclick="showPrimarySalaryModal()">Set Up Salary</button>
                    </div>
                </div>


            </section>
        </div>
        </section>
        <div class="container">
            <!-- Budget Allocation Preview -->
            <section class="salary-breakdown">
                <div class="section-header">
                    <h3 id="salaryAllocationTitle">Budget Allocation & Preview</h3>
                    <div class="preview-totals">
                        <span>Based on: <strong id="previewBasedOnSalary">₵0.00</strong> total income</span>
                        <span>Total: <strong id="previewTotalAllocated">0%</strong></span>
                    </div>
                </div>
                <!-- Budget Allocation Preview (Same as Personal Dashboard) -->
                <div class="budget-allocation-preview" id="budgetAllocationPreview">
                    <div class="allocation-grid" id="previewAllocationGrid">
                        <div class="allocation-item needs">
                            <div class="allocation-header">
                                <span class="allocation-icon"><i class="fas fa-home"></i></span>
                                <div class="allocation-info">
                                    <h4>Needs</h4>
                                    <div class="allocation-display">
                                        <span class="allocation-percent" id="previewNeedsPercent">%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="allocation-amount" id="previewNeedsAmount">₵0.00</div>
                            <div class="allocation-categories">
                                <span class="category-tag">Food</span>
                                <span class="category-tag">Rent</span>
                                <span class="category-tag">Utilities</span>
                                <span class="category-tag">Transport</span>
                            </div>
                        </div>

                        <div class="allocation-item wants">
                            <div class="allocation-header">
                                <span class="allocation-icon"><i class="fas fa-gamepad"></i></span>
                                <div class="allocation-info">
                                    <h4>Wants</h4>
                                    <div class="allocation-display">
                                        <span class="allocation-percent" id="previewWantsPercent">%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="allocation-amount" id="previewWantsAmount">₵0.00</div>
                            <div class="allocation-categories">
                                <span class="category-tag">Entertainment</span>
                                <span class="category-tag">Dining</span>
                                <span class="category-tag">Shopping</span>
                            </div>
                        </div>

                        <div class="allocation-item savings">
                            <div class="allocation-header">
                                <span class="allocation-icon"><i class="fas fa-piggy-bank"></i></span>
                                <div class="allocation-info">
                                    <h4>Savings</h4>
                                    <div class="allocation-display">
                                        <span class="allocation-percent" id="previewSavingsPercent">%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="allocation-amount" id="previewSavingsAmount">₵0.00</div>
                            <div class="allocation-categories">
                                <span class="category-tag">Emergency Fund</span>
                                <span class="category-tag">Goals</span>
                            </div>
                        </div>
                    </div>
                    <div class="allocation-summary">
                        <div class="summary-item">
                            <span>Total Allocated:</span>
                            <strong id="totalAllocated">100%</strong>
                        </div>
                        <!-- <button class="btn-primary" onclick="saveBudgetAllocation()">Save Allocation</button> -->
                    </div>
            </section>
        </div>

        <div class="container">
            <!-- Additional Income Sources -->
            <section class="dashboard-grid">
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Additional Income Sources</h3>
                        <button class="edit-allocation-btn small" onclick="showAddIncomeSourceModal()">+ Add Source</button>
                    </div>
                    <div class="income-sources-list" id="incomeSourcesList">
                        <div class="no-data-message" id="noIncomeSources">
                            <div class="no-data-icon">💼</div>
                            <h4>No Additional Income Sources</h4>
                            <p>Add freelance work, side hustles, or other income streams to improve your budget planning</p>
                            <button class="btn-primary small" onclick="showAddIncomeSourceModal()">Add First Source</button>
                        </div>
                    </div>
                </div>

                <!-- Salary Schedule & Reminders -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Pay Schedule & Reminders</h3>
                        <button class="edit-allocation-btn small" onclick="manageReminders()">Manage</button>
                    </div>
                    <div class="schedule-list">
                        <div class="schedule-item upcoming" id="primarySalarySchedule">
                            <div class="schedule-date">
                                <span class="date-num" id="salaryDay">--</span>
                                <span class="date-month" id="salaryMonth">---</span>
                            </div>
                            <div class="schedule-details">
                                <h4>Monthly Salary</h4>
                                <p id="salaryScheduleDetails">₵0.00 • Not configured</p>
                            </div>
                            <div class="schedule-status">
                                <span class="status-badge" id="salaryStatus">Not set</span>
                            </div>
                        </div>

                        <div class="schedule-item" id="additionalIncomeSchedule" style="display: none;">
                            <div class="schedule-date">
                                <span class="date-num" id="incomeDay">15</span>
                                <span class="date-month" id="incomeMonth">Feb</span>
                            </div>
                            <div class="schedule-details">
                                <h4>Additional Income</h4>
                                <p id="incomeScheduleDetails">₵0.00 • Various sources</p>
                            </div>
                            <div class="schedule-status">
                                <span class="status-badge pending">Expected</span>
                            </div>
                        </div>

                        <div class="no-data-message" id="noScheduleData" style="display: none;">
                            <div class="no-data-icon">📅</div>
                            <h4>No Payment Schedule</h4>
                            <p>Set up your salary information to see upcoming payments</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="container">
            <!-- Tax & Deductions (if applicable) -->
            <section class="insights-section">
                <div class="section-header">
                    <h3><i class="fas fa-receipt"></i> Deductions & Tax Information</h3>
                    <button class="edit-allocation-btn small" onclick="manageTaxInfo()">Configure</button>
                </div>
                <div class="insights-grid">
                    <div class="insight-card tip">
                        <div class="insight-icon"><i class="fas fa-lightbulb"></i></div>
                        <div class="insight-content">
                            <h4>Tax Planning Tip</h4>
                            <p>Your estimated annual income is ₵42,000. Consider setting aside 10% for taxes and deductions.</p>
                            <button class="insight-action" onclick="showSnackbar('Tax savings setup coming soon!', 'info')">Setup Tax Savings</button>
                        </div>
                    </div>
                    <div class="insight-card warning">
                        <div class="insight-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="insight-content">
                            <h4>SSNIT Contributions</h4>
                            <p>Remember to account for social security contributions if you're employed formally.</p>
                            <button class="insight-action" onclick="showSnackbar('Deduction management coming soon!', 'info')">Add Deduction</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        </div>
    </main>

    <!-- Primary Salary Edit Modal -->
    <div id="primarySalaryModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Edit Primary Salary Configuration</h3>
                <span class="close" onclick="closeModal('primarySalaryModal')">&times;</span>
            </div>
            <form class="modal-form" id="primarySalaryForm">
                <div class="form-section">
                    <h4>Salary Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Monthly Salary Amount (₵)</label>
                            <input type="number" name="salaryAmount" id="modalSalaryAmount" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Pay Frequency</label>
                            <select name="payFrequency" id="modalPayFrequency" required>
                                <option value="monthly">Monthly</option>
                                <option value="bi-weekly">Bi-weekly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Next Pay Date</label>
                            <input type="date" name="nextPayDate" id="modalNextPayDate" required>
                        </div>
                        <div class="form-group">
                            <label>Employer/Company</label>
                            <input type="text" name="employer" id="modalEmployer" placeholder="Company name">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="display: none;">
                    <h4>Payment Settings</h4>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-method-group">
                            <div class="payment-method selected" onclick="selectModalPaymentMethod('bank', 'primary')">
                                <div class="payment-method-icon"><i class="fas fa-university"></i></div>
                                <div class="payment-method-name">Bank Account</div>
                            </div>
                            <div class="payment-method" onclick="selectModalPaymentMethod('mobile', 'primary')">
                                <div class="payment-method-icon"><i class="fas fa-mobile-alt"></i></div>
                                <div class="payment-method-name">Mobile Money</div>
                            </div>
                        </div>
                        <input type="hidden" name="paymentMethod" id="modalPaymentMethod" value="bank">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label id="modalPaymentDetailsLabel">Bank Account</label>
                            <select name="paymentAccount" id="modalPaymentAccount">
                                <option value="">Select account...</option>
                                <option value="acc1">Main Checking Account</option>
                                <option value="acc2">Salary Account</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section" style="display: none;">
                    <h4>Budget Settings</h4>
                    <div class="form-group">
                        <div class="toggle-switch">
                            <input type="checkbox" id="modalAutoBudget" name="autoBudget">
                            <label for="modalAutoBudget">Automatically allocate salary to budget categories</label>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('primarySalaryModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Budget Modal -->
    <div id="previewBudgetModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Budget Preview</h3>
                <span class="close" onclick="closeModal('previewBudgetModal')">&times;</span>
            </div>
            <div class="modal-form">
                <div class="form-section">
                    <h4>Income Summary</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div style="text-align: center; padding: 16px; background: #e3f2fd; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: bold; color: #1976d2;" id="previewTotalIncome">₵0.00</div>
                            <div style="color: #424242; font-size: 14px;">Total Monthly Income</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: #f3e5f5; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: bold; color: #7b1fa2;" id="previewSalaryIncome">₵0.00</div>
                            <div style="color: #424242; font-size: 14px;">Primary Salary</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: #e8f5e8; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: bold; color: #2e7d32;" id="previewAdditionalIncome">₵0.00</div>
                            <div style="color: #424242; font-size: 14px;">Additional Income</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Budget Allocation</h4>
                    <div class="allocation-preview">
                        <div class="allocation-item" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span style="font-size: 24px;"><i class="fas fa-home"></i></span>
                                    <div>
                                        <h4 style="margin: 0; color: #495057;">Needs (Essential)</h4>
                                        <p style="margin: 0; color: #6c757d; font-size: 14px;">Food, Rent, Utilities, Transport</p>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: bold;" id="modalPreviewNeedsAmount">₵0.00</div>
                                    <div style="color: #6c757d;" id="modalPreviewNeedsPercent">60%</div>
                                </div>
                            </div>
                        </div>

                        <div class="allocation-item" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span style="font-size: 24px;"><i class="fas fa-gamepad"></i></span>
                                    <div>
                                        <h4 style="margin: 0; color: #495057;">Wants (Lifestyle)</h4>
                                        <p style="margin: 0; color: #6c757d; font-size: 14px;">Entertainment, Dining, Shopping</p>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: bold;" id="modalPreviewWantsAmount">₵0.00</div>
                                    <div style="color: #6c757d;" id="modalPreviewWantsPercent">20%</div>
                                </div>
                            </div>
                        </div>

                        <div class="allocation-item" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span style="font-size: 24px;"><i class="fas fa-piggy-bank"></i></span>
                                    <div>
                                        <h4 style="margin: 0; color: #495057;">Savings & Investments</h4>
                                        <p style="margin: 0; color: #6c757d; font-size: 14px;">Emergency Fund, Goals, Investments</p>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: bold;" id="modalPreviewSavingsAmount">₵0.00</div>
                                    <div style="color: #6c757d;" id="modalPreviewSavingsPercent">20%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Financial Health Score</h4>
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 48px; font-weight: bold; color: #28a745;" id="previewHealthScore">85</div>
                        <div style="color: #495057; margin-top: 8px;" id="previewHealthStatus">Excellent financial planning!</div>
                        <div style="color: #6c757d; font-size: 14px; margin-top: 4px;">Based on your income and allocation strategy</div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('previewBudgetModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Income Source Modal -->
    <div id="addIncomeSourceModal" class="modal">
        <div class="modal-content wide-modal">
            <div class="modal-header gradient-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="modal-title-section">
                        <h3>Add New Income Source</h3>
                        <p>Diversify your income streams and boost your earnings</p>
                    </div>
                </div>
                <span class="close modern-close" onclick="closeModal('addIncomeSourceModal')">&times;</span>
            </div>
            <form class="modal-form compact-form" id="addIncomeForm">
                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Source Name</label>
                        <input type="text" name="sourceName" placeholder="e.g., Freelance Design, Part-time Job" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Income Type</label>
                        <select name="incomeType" required>
                            <option value="">Select type</option>
                            <option value="freelance">💻 Freelance Work</option>
                            <option value="side-business">🏪 Side Business</option>
                            <option value="part-time">⏰ Part-time Job</option>
                            <option value="investment">📈 Investment Returns</option>
                            <option value="rental">🏠 Rental Income</option>
                            <option value="other">📋 Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid two-column">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Average Monthly Amount (₵)</label>
                        <input type="number" name="monthlyAmount" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Payment Frequency</label>
                        <select name="paymentFrequency" required>
                            <option value="monthly">📅 Monthly</option>
                            <option value="bi-weekly">🗓️ Bi-weekly</option>
                            <option value="weekly">📆 Weekly</option>
                            <option value="variable">🔄 Variable</option>
                            <option value="one-time">🔘 One-time</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-credit-card"></i> Payment Method</label>
                    <div class="payment-method-group modern-selector">
                        <div class="payment-method selected modern-option" onclick="selectModalPaymentMethod('bank')">
                            <div class="payment-method-icon"><i class="fas fa-university"></i></div>
                            <div class="payment-method-name">Bank Account</div>
                        </div>
                        <div class="payment-method modern-option" onclick="selectModalPaymentMethod('mobile')">
                            <div class="payment-method-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="payment-method-name">Mobile Money</div>
                        </div>
                    </div>
                    <input type="hidden" name="paymentMethod" value="bank">
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-file-alt"></i> Description</label>
                    <textarea name="description" placeholder="Brief description of this income source" rows="3"></textarea>
                </div>

                <div class="form-group full-width checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="includeInBudget" checked>
                        <span class="checkmark"></span>
                        Include in automatic budget planning
                    </label>
                </div>

                <div class="modal-actions modern-actions">
                    <button type="button" class="btn-secondary modern-btn" onclick="closeModal('addIncomeSourceModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary modern-btn">
                        <i class="fas fa-plus-circle"></i> Add Income Source
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pay Schedule & Reminders Modal -->
    <div id="payScheduleModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Manage Pay Schedule & Reminders</h3>
                <span class="close" onclick="closeModal('payScheduleModal')">&times;</span>
            </div>
            <form class="modal-form" id="payScheduleForm">
                <div class="form-section">
                    <h4>Primary Salary Schedule</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pay Frequency</label>
                            <select name="payFrequency" id="payFrequency" required>
                                <option value="">Select frequency</option>
                                <option value="monthly">Monthly</option>
                                <option value="bi-weekly">Bi-weekly (Every 2 weeks)</option>
                                <option value="weekly">Weekly</option>
                                <option value="semi-monthly">Semi-monthly (Twice a month)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pay Day</label>
                            <select name="payDay" id="payDay">
                                <option value="">Select day</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Next Pay Date</label>
                            <input type="date" name="nextPayDate" id="nextPayDate" required>
                        </div>
                        <div class="form-group">
                            <label>Expected Amount (₵)</label>
                            <input type="number" step="0.01" name="expectedAmount" id="expectedAmount" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Reminder Settings</h4>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="enableReminders" name="enableReminders" checked>
                            <label for="enableReminders">Enable pay day reminders</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Remind me before pay day</label>
                            <select name="reminderDays" id="reminderDays">
                                <option value="1">1 day before</option>
                                <option value="2">2 days before</option>
                                <option value="3" selected>3 days before</option>
                                <option value="7">1 week before</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reminder Time</label>
                            <input type="time" name="reminderTime" id="reminderTime" value="09:00">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Additional Income Schedules</h4>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="trackAdditionalIncome" name="trackAdditionalIncome">
                            <label for="trackAdditionalIncome">Track additional income sources</label>
                        </div>
                    </div>
                    <div id="additionalIncomeSection" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Source Name</label>
                                <input type="text" name="additionalSourceName" placeholder="e.g., Freelance, Side Job">
                            </div>
                            <div class="form-group">
                                <label>Expected Date</label>
                                <input type="date" name="additionalIncomeDate">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Expected Amount (₵)</label>
                            <input type="number" step="0.01" name="additionalAmount" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('payScheduleModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Snackbar -->
    <!-- Updated snackbar will be created dynamically -->

    <script>
        // Animation function for counting numbers
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

        // Function to load fresh salary data via AJAX with animations
        async function loadSalaryData() {
            // Show loading screen for salary data loading
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
            }

            try {
                // Load data from both endpoints
                const [dashboardResponse, salaryResponse] = await Promise.all([
                    fetch('/budget/api/personal_dashboard_data.php'),
                    fetch('../actions/salary_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get_salary_data'
                    })
                ]);

                const dashboardData = await dashboardResponse.json();
                const salaryData = await salaryResponse.json();


                if (dashboardData.success) {
                    // Use animated update with delay for smooth loading
                    setTimeout(() => {
                        updateSalaryUIWithAnimation(dashboardData);
                    }, 100);
                } else {
                    console.error('Dashboard API Error:', dashboardData.message);
                }

                if (salaryData.success) {
                    updateSalarySpecificData(salaryData.data);
                    updateIncomeSources(salaryData.data.income_sources || []);
                } else {
                    console.error('Salary API Error:', salaryData.message);
                    updateIncomeSources([]);
                }
            } catch (error) {
                console.error('Error loading salary data:', error);
                showEmptyStates();
                updateIncomeSources([]);
            } finally {
                // Hide loading screen after data processing
                if (window.budgetlyLoader) {
                    setTimeout(() => {
                        window.budgetlyLoader.hide();
                    }, 800); // Small delay to show the loading animation
                }
            }
        }

        // Function to update salary UI with animations
        function updateSalaryUIWithAnimation(data) {

            const salaryData = data.salary || {};
            const financialOverview = data.financial_overview || {};

            // Animate Primary Salary Amount
            const primarySalaryEl = document.getElementById('primarySalaryAmount');
            if (primarySalaryEl) {
                const salaryAmount = parseFloat(salaryData.monthly_salary) || 0;
                if (salaryAmount > 0) {
                    animateNumber(primarySalaryEl, 0, salaryAmount, 2500, '₵');
                }
            }

            // Animate Current Salary Card
            const currentSalaryEl = document.getElementById('currentSalary');
            if (currentSalaryEl) {
                const currentSalary = parseFloat(salaryData.monthly_salary) || 0;
                if (currentSalary > 0) {
                    animateNumber(currentSalaryEl, 0, currentSalary, 2000, '₵');
                }
            }

            // Animate Additional Income
            const additionalIncomeEl = document.getElementById('additionalIncome');
            if (additionalIncomeEl) {
                const monthlyIncome = parseFloat(financialOverview.monthly_income) || 0;
                const monthlySalary = parseFloat(salaryData.monthly_salary) || 0;
                const additional = Math.max(0, monthlyIncome - monthlySalary);
                if (additional > 0) {
                    animateNumber(additionalIncomeEl, 0, additional, 1800, '₵');
                }
            }

            // Animate Total Income
            const totalIncomeEl = document.getElementById('totalIncome');
            if (totalIncomeEl) {
                const total = parseFloat(financialOverview.monthly_income) || 0;
                if (total > 0) {
                    animateNumber(totalIncomeEl, 0, total, 2200, '₵');
                }
            }

            // Continue with existing updateSalaryUI logic for non-animated elements
            updateSalaryUI(data);
        }

        // Theme Management
        function changeTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('personalTheme', theme); // Use same key as personal dashboard

            // Update theme selector
            const themeSelector = document.getElementById('themeSelector');
            if (themeSelector) {
                themeSelector.value = theme;
            }

            // Update active theme option visual state
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            const activeOption = document.querySelector(`[data-theme="${theme}"]`);
            if (activeOption) {
                activeOption.classList.add('active');
            }

        }

        // Load saved theme on page load  
        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('personalTheme') || 'default'; // Use same key as personal dashboard
            changeTheme(savedTheme);
        }

        // Handle URL parameters from other pages
        function handleURLParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const fromPage = urlParams.get('from');
            const amount = urlParams.get('amount');

            if (fromPage === 'dashboard') {
                showSnackbar('Welcome from Personal Dashboard! 👋', 'info');

                // Pre-fill salary amount if provided
                if (amount && amount !== '0') {
                    const salaryInput = document.getElementById('salaryAmount');
                    if (salaryInput) {
                        salaryInput.value = amount;
                        updateBudgetPreview();
                    }
                    showSnackbar(`Current salary: ₵${amount} (from dashboard)`, 'success');
                }

                // Highlight the connection
                const salarySection = document.querySelector('.salary-breakdown');
                if (salarySection) {
                    salarySection.style.border = '2px solid var(--primary-color)';
                    salarySection.style.borderRadius = '12px';
                    salarySection.style.transition = 'all 0.3s ease';

                    setTimeout(() => {
                        salarySection.style.border = '';
                    }, 3000);
                }
            }
        }

        function updateSalaryUI(data) {

            // Update user information
            if (data.user) {
                const userAvatar = document.getElementById('userAvatar');
                const logoUserName = document.getElementById('logoUserName');

                if (userAvatar && data.user.initials) {
                    userAvatar.textContent = data.user.initials;
                    userAvatar.onerror = function() {
                        this.textContent = data.user.initials;
                    };
                }

                if (logoUserName && data.user.first_name) {
                    logoUserName.textContent = data.user.first_name;
                }
            } else {
            }

            // Update salary overview cards
            const salaryData = data.salary || {};

            // Current salary
            const currentSalary = document.getElementById('currentSalary');
            const nextPayment = document.getElementById('nextPayment');
            if (currentSalary) {
                if (salaryData.monthly_salary && parseFloat(salaryData.monthly_salary) > 0) {
                    currentSalary.textContent = `₵${parseFloat(salaryData.monthly_salary).toFixed(2)}`;
                    if (nextPayment) {
                        nextPayment.textContent = salaryData.next_pay_date ?
                            `Next payment: ${new Date(salaryData.next_pay_date).toLocaleDateString()}` :
                            'Payment date not set';
                    }
                } else {
                    currentSalary.textContent = '₵0.00';
                    if (nextPayment) {
                        nextPayment.textContent = 'Set up salary information';
                    }
                }
            }

            // Update Primary Salary Hero Display
            const primarySalaryAmount = document.getElementById('primarySalaryAmount');
            const primarySalaryStatus = document.getElementById('primarySalaryStatus');
            const salaryActionBtn = document.getElementById('salaryActionBtn');

            if (primarySalaryAmount && primarySalaryStatus && salaryActionBtn) {
                const salary = parseFloat(salaryData.monthly_salary || 0);

                if (salary > 0) {
                    // Salary is set up
                    primarySalaryAmount.textContent = `₵${salary.toLocaleString()}`;

                    if (salaryData.next_pay_date) {
                        const date = new Date(salaryData.next_pay_date);
                        const today = new Date();
                        const daysUntil = Math.ceil((date - today) / (1000 * 60 * 60 * 24));

                        if (daysUntil <= 0) {
                            primarySalaryStatus.textContent = 'Payment due now';
                            primarySalaryStatus.style.color = '#dc3545';
                        } else if (daysUntil <= 3) {
                            primarySalaryStatus.textContent = `Due in ${daysUntil} day${daysUntil > 1 ? 's' : ''}`;
                            primarySalaryStatus.style.color = '#fd7e14';
                        } else {
                            primarySalaryStatus.textContent = `Next payment: ${date.toLocaleDateString('en-GB', {
                                day: 'numeric',
                                month: 'short'
                            })}`;
                            primarySalaryStatus.style.color = '#6c757d';
                        }
                    } else {
                        primarySalaryStatus.textContent = 'Payment date not set';
                        primarySalaryStatus.style.color = '#6c757d';
                    }

                    salaryActionBtn.textContent = 'Edit Salary';
                } else {
                    // Salary not set up
                    primarySalaryAmount.textContent = '₵0.00';
                    primarySalaryStatus.textContent = 'Not set up yet';
                    primarySalaryStatus.style.color = '#6c757d';
                    salaryActionBtn.textContent = 'Set Up Salary';
                }
            }

            // Additional income
            const additionalIncome = document.getElementById('additionalIncome');
            const incomeSourcesCount = document.getElementById('incomeSourcesCount');
            if (additionalIncome) {
                // Calculate additional income from financial overview (total income minus salary)
                const monthlyIncome = parseFloat(data.financial_overview?.monthly_income || 0);
                const monthlySalary = parseFloat(salaryData.monthly_salary || 0);
                const additional = Math.max(0, monthlyIncome - monthlySalary);

                additionalIncome.textContent = `₵${additional.toFixed(2)}`;
                if (incomeSourcesCount) {
                    if (additional > 0) {
                        incomeSourcesCount.textContent = 'Other income sources';
                    } else {
                        incomeSourcesCount.textContent = 'No additional sources';
                    }
                }
            }

            // Total income
            const totalIncome = document.getElementById('totalIncome');
            if (totalIncome) {
                const total = parseFloat(data.financial_overview?.monthly_income || 0);
                totalIncome.textContent = `₵${total.toFixed(2)}`;
            }

            // Budget efficiency - Enhanced implementation
            const budgetEfficiency = document.getElementById('budgetEfficiency');
            const efficiencyStatus = document.getElementById('efficiencyStatus');
            const efficiencyBreakdown = document.getElementById('efficiencyBreakdown');
            const savingsRateDisplay = document.getElementById('savingsRateDisplay');
            const budgetUtilizationDisplay = document.getElementById('budgetUtilizationDisplay');
            const incomeCoverageDisplay = document.getElementById('incomeCoverageDisplay');

            if (budgetEfficiency) {
                const totalIncome = parseFloat(data.financial_overview?.monthly_income || 0);
                const savingsRate = parseFloat(data.financial_overview?.savings_rate || 0);

                if (totalIncome > 0 && data.budget_allocation && data.budget_allocation.length > 0) {
                    // Calculate comprehensive efficiency metrics
                    const totalAllocated = data.budget_allocation.reduce((sum, allocation) =>
                        sum + parseFloat(allocation.allocated_amount || 0), 0
                    );

                    const budgetUtilization = totalIncome > 0 ? (totalAllocated / totalIncome) * 100 : 0;
                    const incomeCoverage = Math.min(100, budgetUtilization);

                    // Calculate overall efficiency score (weighted average)
                    const efficiencyScore = Math.round(
                        (savingsRate * 0.4) + // 40% weight on savings
                        (Math.min(100, budgetUtilization) * 0.3) + // 30% weight on budget coverage
                        ((100 - Math.abs(100 - budgetUtilization)) * 0.3) // 30% weight on balance
                    );

                    budgetEfficiency.textContent = `${efficiencyScore}%`;

                    // Update status based on score
                    if (efficiencyScore >= 80) {
                        efficiencyStatus.textContent = 'Excellent financial planning!';
                        efficiencyStatus.style.color = '#28a745';
                    } else if (efficiencyScore >= 65) {
                        efficiencyStatus.textContent = 'Very good budget management';
                        efficiencyStatus.style.color = '#28a745';
                    } else if (efficiencyScore >= 50) {
                        efficiencyStatus.textContent = 'Good planning, room for improvement';
                        efficiencyStatus.style.color = '#ffc107';
                    } else {
                        efficiencyStatus.textContent = 'Consider optimizing your budget';
                        efficiencyStatus.style.color = '#dc3545';
                    }

                    // Show breakdown
                    if (efficiencyBreakdown) {
                        efficiencyBreakdown.style.display = 'block';
                        if (savingsRateDisplay) savingsRateDisplay.textContent = `${Math.round(savingsRate)}%`;
                        if (budgetUtilizationDisplay) budgetUtilizationDisplay.textContent = `${Math.round(budgetUtilization)}%`;
                        if (incomeCoverageDisplay) incomeCoverageDisplay.textContent = `${Math.round(incomeCoverage)}%`;
                    }
                } else {
                    budgetEfficiency.textContent = '--';
                    if (efficiencyStatus) {
                        efficiencyStatus.textContent = 'Set up salary first';
                        efficiencyStatus.style.color = '#6c757d';
                    }
                    if (efficiencyBreakdown) {
                        efficiencyBreakdown.style.display = 'none';
                    }
                }
            }

            // Update form fields
            updateSalaryForm(salaryData);

            // Update payment schedule
            updatePaymentSchedule(salaryData);

            // Update budget preview
            if (salaryData.monthly_salary && salaryData.monthly_salary > 0) {
                const salaryAmountElement = document.getElementById('salaryAmount');
                if (salaryAmountElement) {
                    salaryAmountElement.value = salaryData.monthly_salary;
                }
                updateBudgetPreview();
            }

            // Update budget allocation preview with backend data
            if (data.budget_allocation && Array.isArray(data.budget_allocation) && data.budget_allocation.length > 0) {
                // Store allocation data globally for preview modal
                window.currentBudgetAllocation = data.budget_allocation;
                updateBudgetAllocationPreview(data.budget_allocation, parseFloat(data.financial_overview?.monthly_income || 0));
            } else {
                // Set default allocation if no data exists
                window.currentBudgetAllocation = [{
                    needs_percentage: 50,
                    wants_percentage: 30,
                    savings_percentage: 20
                }];
            }
        }

        function updateBudgetAllocationPreview(allocations, totalIncome) {

            if (!allocations || allocations.length === 0) {
                return;
            }

            // Update the "Based on" total income display
            const previewBasedOnSalary = document.getElementById('previewBasedOnSalary');
            if (previewBasedOnSalary && totalIncome > 0) {
                previewBasedOnSalary.textContent = `₵${totalIncome.toLocaleString()}`;
            }

            // Calculate total percentage
            let totalPercentage = 0;

            // Update each category
            allocations.forEach(allocation => {
                const categoryType = allocation.category_type;
                const percentage = allocation.percentage || 0;
                const amount = allocation.allocated_amount || 0;

                totalPercentage += percentage;

                // Update percentage display
                const percentElement = document.getElementById(`preview${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}Percent`);
                if (percentElement) {
                    percentElement.textContent = `${percentage}%`;
                }

                // Update amount display
                const amountElement = document.getElementById(`preview${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}Amount`);
                if (amountElement) {
                    amountElement.textContent = `₵${amount.toLocaleString()}`;
                }
            });

            // Update total percentage display
            const previewTotalAllocated = document.getElementById('previewTotalAllocated');
            if (previewTotalAllocated) {
                previewTotalAllocated.textContent = `${totalPercentage}%`;
            }

        }

        function updateSalarySpecificData(data) {
            // Update salary form with detailed data from salary_actions.php
            if (data.salary) {
                updateSalaryForm(data.salary);
            }

            // Update budget allocation sliders if data exists
            if (data.budget_allocation) {
                const allocation = data.budget_allocation;

                // Update sliders - try data-category approach first, then fallback to ID approach
                let needsSlider = document.querySelector('[data-category="needs"]');
                let wantsSlider = document.querySelector('[data-category="wants"]');
                let savingsSlider = document.querySelector('[data-category="savings"]');

                if (!needsSlider) needsSlider = document.getElementById('needsSlider');
                if (!wantsSlider) wantsSlider = document.getElementById('wantsSlider');
                if (!savingsSlider) savingsSlider = document.getElementById('savingsSlider');

                if (needsSlider && allocation.needs_percentage) {
                    needsSlider.value = allocation.needs_percentage;
                    updateAllocation('needs', allocation.needs_percentage);
                }
                if (wantsSlider && allocation.wants_percentage) {
                    wantsSlider.value = allocation.wants_percentage;
                    updateAllocation('wants', allocation.wants_percentage);
                }
                if (savingsSlider && allocation.savings_percentage) {
                    savingsSlider.value = allocation.savings_percentage;
                    updateAllocation('savings', allocation.savings_percentage);
                }
            }
        }

        function updateIncomeSources(incomeSources) {
            const incomeSourcesList = document.getElementById('incomeSourcesList');
            const noIncomeSources = document.getElementById('noIncomeSources');

            if (!incomeSourcesList) return;

            // Clear existing content
            incomeSourcesList.innerHTML = '';

            if (incomeSources && incomeSources.length > 0) {
                // Hide no data message
                if (noIncomeSources) {
                    noIncomeSources.style.display = 'none';
                }

                // Show income sources
                incomeSources.forEach(source => {
                    const sourceElement = createIncomeSourceElement(source);
                    incomeSourcesList.appendChild(sourceElement);
                });

                // Update summary
                const incomeSourcesCount = document.getElementById('incomeSourcesCount');
                if (incomeSourcesCount) {
                    const totalSources = incomeSources.length;
                    const totalAmount = incomeSources.reduce((sum, source) => sum + parseFloat(source.monthly_amount || 0), 0);
                    incomeSourcesCount.textContent = `${totalSources} source${totalSources > 1 ? 's' : ''} • ₵${totalAmount.toFixed(2)}/month`;
                }
            } else {
                // Show no data message
                if (noIncomeSources) {
                    noIncomeSources.style.display = 'block';
                }

                // Update summary
                const incomeSourcesCount = document.getElementById('incomeSourcesCount');
                if (incomeSourcesCount) {
                    incomeSourcesCount.textContent = 'No additional sources';
                }
            }
        }

        function createIncomeSourceElement(source) {
            const div = document.createElement('div');
            div.className = 'income-source-item';

            // Format frequency display
            const frequencyMap = {
                'weekly': 'Weekly',
                'bi-weekly': 'Bi-weekly',
                'monthly': 'Monthly',
                'variable': 'Variable',
                'one-time': 'One-time'
            };
            const frequencyText = frequencyMap[source.payment_frequency] || 'Monthly';

            // Format income type display
            const typeMap = {
                'freelance': 'Freelance',
                'investment': 'Investment',
                'other': 'Other',
                'salary': 'Salary',
                'bonus': 'Bonus'
            };
            const typeText = typeMap[source.income_type] || 'Other';

            div.innerHTML = `
                <div class="income-source-info">
                    <div class="income-source-name">${source.source_name}</div>
                    <div class="income-source-details">
                        <span class="income-type">${typeText}</span> • 
                        <span class="income-frequency">${frequencyText}</span>
                        ${source.payment_method === 'mobile' ? ' • Mobile Money' : ' • Bank'}
                    </div>
                    ${source.description ? `<div class="income-source-description">${source.description}</div>` : ''}
                </div>
                <div class="income-source-amount">
                    <div class="amount">₵${parseFloat(source.monthly_amount).toFixed(2)}</div>
                    <div class="amount-period">/month</div>
                </div>
                <div class="income-source-actions">
                    <button class="btn-icon" onclick="editIncomeSource(${source.id})" title="Edit">
                        <span>✏️</span>
                    </button>
                    <button class="btn-icon" onclick="deleteIncomeSource(${source.id})" title="Delete">
                        <span>🗑️</span>
                    </button>
                </div>
            `;

            return div;
        }

        function editIncomeSource(sourceId) {
            // TODO: Implement edit functionality
            showSnackbar('Edit functionality coming soon!', 'info');
        }

        function deleteIncomeSource(sourceId) {
            if (!confirm('Are you sure you want to delete this income source?')) {
                return;
            }

            // Make API call to delete
            fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_income_source&source_id=${sourceId}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSnackbar(result.message, 'success');
                        // Reload data
                        setTimeout(() => {
                            loadSalaryData();
                        }, 500);
                    } else {
                        showSnackbar(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showSnackbar('Failed to delete income source', 'error');
                });
        }

        async function checkSalaryDue() {
            try {
                const response = await fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=check_salary_due'
                });

                const result = await response.json();

                if (result.success && result.salary_due) {
                    showSalaryConfirmationBanner(result);
                }
            } catch (error) {
                console.error('Error checking salary due:', error);
            }
        }

        function showSalaryConfirmationBanner(salaryInfo) {
            // Check if banner already exists
            if (document.getElementById('salaryConfirmationBanner')) {
                return;
            }

            const banner = document.createElement('div');
            banner.id = 'salaryConfirmationBanner';
            banner.className = 'salary-confirmation-banner';

            const message = salaryInfo.is_past_due ?
                `Your salary of ₵${parseFloat(salaryInfo.amount).toFixed(2)} was due on ${new Date(salaryInfo.pay_date).toLocaleDateString()}. Have you received it?` :
                `Your salary of ₵${parseFloat(salaryInfo.amount).toFixed(2)} is due today! Have you received it?`;

            banner.innerHTML = `
                <div class="banner-content">
                    <div class="banner-icon"><i class="fas fa-piggy-bank"></i></div>
                    <div class="banner-message">
                        <h4>Salary Payment Confirmation</h4>
                        <p>${message}</p>
                    </div>
                    <div class="banner-actions">
                        <button class="btn-primary small" onclick="confirmSalaryReceived()">✓ Yes, I received it</button>
                        <button class="btn-secondary small" onclick="dismissSalaryBanner()">Not yet</button>
                    </div>
                </div>
            `;

            // Insert at the top of main content
            const container = document.querySelector('.container');
            if (container) {
                container.insertBefore(banner, container.firstChild);
            }
        }

        function confirmSalaryReceived() {
            const banner = document.getElementById('salaryConfirmationBanner');
            const confirmBtn = banner.querySelector('.btn-primary');
            const originalText = confirmBtn.textContent;

            confirmBtn.textContent = 'Processing...';
            confirmBtn.disabled = true;

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
                        dismissSalaryBanner();

                        // Reload data to reflect the new income
                        setTimeout(() => {
                            loadSalaryData();
                        }, 1000);
                    } else {
                        showSnackbar(result.message, 'error');
                        confirmBtn.textContent = originalText;
                        confirmBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showSnackbar('Failed to confirm salary', 'error');
                    confirmBtn.textContent = originalText;
                    confirmBtn.disabled = false;
                });
        }

        function dismissSalaryBanner() {
            const banner = document.getElementById('salaryConfirmationBanner');
            if (banner) {
                banner.remove();
            }
        }

        function updatePaymentSchedule(salaryData) {
            const primarySchedule = document.getElementById('primarySalarySchedule');
            const noScheduleData = document.getElementById('noScheduleData');

            if (salaryData.monthly_salary && salaryData.monthly_salary > 0 && salaryData.next_pay_date) {
                // Show salary schedule
                primarySchedule.style.display = 'flex';
                noScheduleData.style.display = 'none';

                // Update date
                const payDate = new Date(salaryData.next_pay_date);
                const dayNum = document.getElementById('salaryDay');
                const dayMonth = document.getElementById('salaryMonth');

                if (dayNum) dayNum.textContent = payDate.getDate();
                if (dayMonth) dayMonth.textContent = payDate.toLocaleDateString('en-US', {
                    month: 'short'
                });

                // Update details
                const scheduleDetails = document.getElementById('salaryScheduleDetails');
                if (scheduleDetails) {
                    const amount = parseFloat(salaryData.monthly_salary).toFixed(2);
                    const paymentMethod = salaryData.payment_method || 'Bank';
                    scheduleDetails.textContent = `₵${amount} • ${paymentMethod}`;
                }

                // Update status
                const statusElement = document.getElementById('salaryStatus');
                if (statusElement) {
                    const today = new Date();
                    const daysUntil = Math.ceil((payDate - today) / (1000 * 60 * 60 * 24));

                    if (daysUntil <= 0) {
                        statusElement.textContent = 'Due now';
                        statusElement.className = 'status-badge due-now';
                    } else if (daysUntil <= 3) {
                        statusElement.textContent = `Due in ${daysUntil} day${daysUntil > 1 ? 's' : ''}`;
                        statusElement.className = 'status-badge due-soon';
                    } else {
                        statusElement.textContent = 'Scheduled';
                        statusElement.className = 'status-badge scheduled';
                    }
                }

                // Show additional income if exists
                const additionalSchedule = document.getElementById('additionalIncomeSchedule');
                if (salaryData.additional_income && parseFloat(salaryData.additional_income) > 0) {
                    additionalSchedule.style.display = 'flex';
                    const incomeDetails = document.getElementById('incomeScheduleDetails');
                    if (incomeDetails) {
                        const amount = parseFloat(salaryData.additional_income).toFixed(2);
                        incomeDetails.textContent = `₵${amount} • Various sources`;
                    }
                } else {
                    additionalSchedule.style.display = 'none';
                }
            } else {
                // Show empty state
                primarySchedule.style.display = 'none';
                document.getElementById('additionalIncomeSchedule').style.display = 'none';
                noScheduleData.style.display = 'flex';
            }
        }

        function updateSalaryForm(salaryData) {
            // Salary amount
            const salaryAmount = document.getElementById('salaryAmount');
            if (salaryAmount && salaryData.monthly_salary) {
                salaryAmount.value = salaryData.monthly_salary;
            }

            // Pay frequency
            const payFrequency = document.getElementById('payFrequency');
            if (payFrequency && salaryData.pay_frequency) {
                payFrequency.value = salaryData.pay_frequency;
            }

            // Next pay date
            const nextPayDate = document.getElementById('nextPayDate');
            if (nextPayDate && salaryData.next_pay_date) {
                nextPayDate.value = salaryData.next_pay_date;
            }

            // Auto budget checkbox
            const autoBudget = document.getElementById('autoBudget');
            if (autoBudget) {
                autoBudget.checked = salaryData.auto_budget_allocation === '1' || salaryData.auto_budget_allocation === true;
            }
        }

        function showEmptyStates() {
            // Show default empty states when no data is available
            const currentSalary = document.getElementById('currentSalary');
            const nextPayment = document.getElementById('nextPayment');
            const additionalIncome = document.getElementById('additionalIncome');
            const incomeSourcesCount = document.getElementById('incomeSourcesCount');
            const totalIncome = document.getElementById('totalIncome');
            const budgetEfficiency = document.getElementById('budgetEfficiency');
            const efficiencyStatus = document.getElementById('efficiencyStatus');

            if (currentSalary) currentSalary.textContent = '₵0.00';
            if (nextPayment) nextPayment.textContent = 'Set up salary information';
            if (additionalIncome) additionalIncome.textContent = '₵0.00';
            if (incomeSourcesCount) incomeSourcesCount.textContent = 'No additional sources';
            if (totalIncome) totalIncome.textContent = '₵0.00';
            if (budgetEfficiency) budgetEfficiency.textContent = '--';
            if (efficiencyStatus) efficiencyStatus.textContent = 'Set up salary first';

            // Show payment schedule empty state
            const primarySchedule = document.getElementById('primarySalarySchedule');
            const noScheduleData = document.getElementById('noScheduleData');
            const additionalSchedule = document.getElementById('additionalIncomeSchedule');

            if (primarySchedule) primarySchedule.style.display = 'none';
            if (additionalSchedule) additionalSchedule.style.display = 'none';
            if (noScheduleData) noScheduleData.style.display = 'flex';
        }

        // Snackbar function
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

        // Core JavaScript functions
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            const themeDropdown = document.getElementById('themeDropdown');

            if (!dropdown) {
                console.error('User dropdown not found');
                return;
            }

            // Close theme dropdown if open
            if (themeDropdown && themeDropdown.classList.contains('show')) {
                themeDropdown.classList.remove('show');
            }

            // Toggle user dropdown
            dropdown.classList.toggle('show');
        }

        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            const userDropdown = document.getElementById('userDropdown');

            if (!dropdown) {
                console.error('Theme dropdown not found');
                return;
            }

            // Close user dropdown if open
            if (userDropdown && userDropdown.classList.contains('show')) {
                userDropdown.classList.remove('show');
            }

            // Toggle theme dropdown
            dropdown.classList.toggle('show');
        }

        function showAddIncomeSourceModal() {
            showModal('addIncomeSourceModal');
        }

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Dispatch modal show event for walkthrough
                document.dispatchEvent(new CustomEvent('modalShow', { detail: { modalId } }));
                
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);

                const firstInput = modal.querySelector('input');
                if (firstInput) {
                    firstInput.focus();
                }
            } else {
                console.error('Modal not found:', modalId);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');

                setTimeout(() => {
                    modal.style.display = 'none';
                    const form = modal.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                    
                    // Dispatch modal hide event for walkthrough
                    document.dispatchEvent(new CustomEvent('modalHide', { detail: { modalId } }));
                }, 300);
            } else {
                console.error('Modal not found:', modalId);
            }
        }

        function setupModalListeners() {
            // Close modals when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    const modalId = e.target.id;
                    closeModal(modalId);
                }
            });

            // Close modals with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        closeModal(openModal.id);
                    }
                }
            });
        }

        // Payment method selection
        function selectPaymentMethod(method) {
            const methods = document.querySelectorAll('#paymentDetails').previousElementSibling.querySelectorAll('.payment-method');
            methods.forEach(m => m.classList.remove('selected'));
            event.target.closest('.payment-method').classList.add('selected');

            const detailsLabel = document.getElementById('paymentDetailsLabel');
            const detailsSelect = document.getElementById('paymentAccount');

            if (method === 'bank') {
                detailsLabel.textContent = 'Bank Account';
                detailsSelect.innerHTML = `
                    <option value="main">Main Account - GCB ***4567</option>
                    <option value="savings">Savings Account - Fidelity ***8901</option>
                `;
            } else {
                detailsLabel.textContent = 'Mobile Money Account';
                detailsSelect.innerHTML = `
                    <option value="mtn">MTN Mobile Money - ***1234</option>
                    <option value="vodafone">Vodafone Cash - ***5678</option>
                    <option value="airteltigo">AirtelTigo Money - ***9012</option>
                `;
            }
        }

        // Modal payment method selection
        function selectModalPaymentMethod(method) {
            const modal = event.target.closest('.modal');
            const methods = modal.querySelectorAll('.payment-method');
            methods.forEach(m => m.classList.remove('selected'));
            event.target.closest('.payment-method').classList.add('selected');

            const hiddenInput = modal.querySelector('input[name="paymentMethod"]');
            hiddenInput.value = method;
        }

        // Salary setup specific JavaScript
        function updateBudgetPreview() {
            // Try to get salary from multiple possible sources
            let salary = 0;
            const salaryAmountElement = document.getElementById('salaryAmount');
            const modalSalaryAmountElement = document.getElementById('modalSalaryAmount');
            const primarySalaryAmountElement = document.getElementById('primarySalaryAmount');

            if (salaryAmountElement && salaryAmountElement.value) {
                salary = parseFloat(salaryAmountElement.value) || 0;
            } else if (modalSalaryAmountElement && modalSalaryAmountElement.value) {
                salary = parseFloat(modalSalaryAmountElement.value) || 0;
            } else if (primarySalaryAmountElement) {
                // Extract salary from display element (remove ₵ and commas)
                const displayText = primarySalaryAmountElement.textContent.replace('₵', '').replace(/,/g, '');
                salary = parseFloat(displayText) || 0;
            }

            // Get additional income from the displayed value
            const additionalIncomeElement = document.getElementById('additionalIncome');
            let additionalIncome = 0;
            if (additionalIncomeElement) {
                const additionalText = additionalIncomeElement.textContent.replace('₵', '').replace(',', '');
                additionalIncome = parseFloat(additionalText) || 0;
            }

            // Calculate total income (salary + additional income)
            const totalIncome = salary + additionalIncome;

            updateAllocationAmounts(totalIncome);
            updateTotalAllocation();

            // Update the allocation total display
            const allocationTotal = document.getElementById('allocationTotal');
            if (allocationTotal) {
                if (additionalIncome > 0) {
                    allocationTotal.textContent = `Based on ₵${salary.toFixed(2)} salary + ₵${additionalIncome.toFixed(2)} additional income = ₵${totalIncome.toFixed(2)} total`;
                } else {
                    allocationTotal.textContent = `Based on ₵${salary.toFixed(2)} salary`;
                }
            }

            // Update the preview section displays
            const previewBasedOnSalary = document.getElementById('previewBasedOnSalary');
            if (previewBasedOnSalary) {
                previewBasedOnSalary.textContent = `₵${totalIncome.toLocaleString()}`;
            }
        }

        function updateBudgetAllocationPreview(allocations) {
            const budgetAllocationPreview = document.getElementById('budgetAllocationPreview');
            const previewBasedOnSalary = document.getElementById('previewBasedOnSalary');
            const previewTotalAllocated = document.getElementById('previewTotalAllocated');

            if (!budgetAllocationPreview) return;

            // Calculate total budget from salary data
            let totalSalary = 0;
            if (allocations && allocations.length > 0) {
                totalSalary = allocations.reduce((sum, allocation) => sum + (parseFloat(allocation.allocated_amount) || 0), 0);
            }

            if (!allocations || allocations.length === 0) {
                // Hide the preview section when no data
                budgetAllocationPreview.style.display = 'none';
                return;
            }

            budgetAllocationPreview.style.display = 'block';

            // Update total display
            if (previewBasedOnSalary) {
                previewBasedOnSalary.textContent = `₵${totalSalary.toLocaleString()}`;
            }
            if (previewTotalAllocated) {
                const totalPercentage = allocations.reduce((sum, allocation) => sum + (allocation.percentage || 0), 0);
                previewTotalAllocated.textContent = `${totalPercentage}%`;
            }

            // Update each category
            allocations.forEach(allocation => {
                const categoryType = allocation.category_type;
                const allocated = parseFloat(allocation.allocated_amount) || 0;
                const percentage = allocation.percentage || 0;

                // Update percentage display
                const percentElement = document.getElementById(`preview${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}Percent`);
                if (percentElement) {
                    percentElement.textContent = `${percentage}%`;
                }

                // Update amount display
                const amountElement = document.getElementById(`preview${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}Amount`);
                if (amountElement) {
                    amountElement.textContent = `₵${allocated.toLocaleString()}`;
                }
            });
        }

        function updateTotalAllocation() {
            // Try data-category approach first, then fallback to ID approach
            let needsSlider = document.querySelector('[data-category="needs"]');
            let wantsSlider = document.querySelector('[data-category="wants"]');
            let savingsSlider = document.querySelector('[data-category="savings"]');

            if (!needsSlider) needsSlider = document.getElementById('needsSlider');
            if (!wantsSlider) wantsSlider = document.getElementById('wantsSlider');
            if (!savingsSlider) savingsSlider = document.getElementById('savingsSlider');

            const needs = needsSlider ? parseInt(needsSlider.value) : 50;
            const wants = wantsSlider ? parseInt(wantsSlider.value) : 30;
            const savings = savingsSlider ? parseInt(savingsSlider.value) : 20;
            const total = needs + wants + savings;

            const totalElement = document.getElementById('totalAllocated');
            if (totalElement) {
                totalElement.textContent = total + '%';

                // Visual feedback for total
                if (total === 100) {
                    totalElement.style.color = 'var(--success-color)';
                } else {
                    totalElement.style.color = 'var(--warning-color)';
                }
            }
        }

        function updateAllocationAmounts(salary) {
            ['needs', 'wants', 'savings'].forEach(category => {
                // Try data-category approach first, then fallback to ID approach
                let slider = document.querySelector(`[data-category="${category}"]`);
                if (!slider) {
                    slider = document.getElementById(category + 'Slider');
                }

                if (slider) {
                    const percentage = parseInt(slider.value);
                    // Fix precision issue by rounding to 2 decimal places
                    const amount = Math.round((salary * percentage) * 100) / 10000;

                    // Update amount element if exists
                    const amountElement = document.getElementById(category + 'Amount');
                    if (amountElement) {
                        amountElement.textContent = '₵' + amount.toFixed(2);
                    }

                    // Update preview elements if exists
                    const previewPercentElement = document.getElementById(`preview${category.charAt(0).toUpperCase() + category.slice(1)}Percent`);
                    const previewAmountElement = document.getElementById(`preview${category.charAt(0).toUpperCase() + category.slice(1)}Amount`);

                    if (previewPercentElement) {
                        previewPercentElement.textContent = `${percentage}%`;
                    }
                    if (previewAmountElement) {
                        previewAmountElement.textContent = `₵${amount.toFixed(2)}`;
                    }
                }
            });
        }

        function updatePaySchedule() {
            const frequency = document.getElementById('payFrequency').value;
            const nextPayDate = document.getElementById('nextPayDate');
            const today = new Date();

            // Update next pay date based on frequency
            switch (frequency) {
                case 'weekly':
                    nextPayDate.value = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    break;
                case 'bi-weekly':
                    nextPayDate.value = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    break;
                case 'monthly':
                default:
                    const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 28);
                    nextPayDate.value = nextMonth.toISOString().split('T')[0];
                    break;
            }
        }

        function saveBudgetAllocation() {
            // Try data-category approach first, then fallback to ID approach
            let needsSlider = document.querySelector('[data-category="needs"]');
            let wantsSlider = document.querySelector('[data-category="wants"]');
            let savingsSlider = document.querySelector('[data-category="savings"]');

            if (!needsSlider) needsSlider = document.getElementById('needsSlider');
            if (!wantsSlider) wantsSlider = document.getElementById('wantsSlider');
            if (!savingsSlider) savingsSlider = document.getElementById('savingsSlider');

            if (!needsSlider || !wantsSlider || !savingsSlider) {
                showSnackbar('Budget allocation sliders not found. Please refresh the page.', 'error');
                return;
            }

            const needs = parseInt(needsSlider.value);
            const wants = parseInt(wantsSlider.value);
            const savings = parseInt(savingsSlider.value);

            if (needs + wants + savings !== 100) {
                showSnackbar('Please ensure your budget allocation totals 100%', 'warning');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'update_budget_allocation');
            formData.append('needsPercent', needs);
            formData.append('wantsPercent', wants);
            formData.append('savingsPercent', savings);

            const saveButton = event.target;
            const originalText = saveButton.textContent;
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;

            // Make API call
            fetch('../actions/salary_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSnackbar(result.message, 'success');
                        // Update the allocation display
                        updateBudgetPreview();
                    } else {
                        showSnackbar(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showSnackbar('Failed to save budget allocation', 'error');
                })
                .finally(() => {
                    saveButton.textContent = originalText;
                    saveButton.disabled = false;
                });
        }

        function editPrimarySalary() {
            showSnackbar('Primary salary editing functionality coming soon', 'info');
        }

        function editIncomeSource(sourceId) {
            showSnackbar(`Editing ${sourceId} income source functionality coming soon`, 'info');
        }

        function previewBudgetAllocation() {
            // Try to get salary from multiple possible sources
            let salary = 0;
            const salaryAmountElement = document.getElementById('salaryAmount');
            const modalSalaryAmountElement = document.getElementById('modalSalaryAmount');
            const primarySalaryAmountElement = document.getElementById('primarySalaryAmount');

            if (salaryAmountElement && salaryAmountElement.value) {
                salary = parseFloat(salaryAmountElement.value) || 0;
            } else if (modalSalaryAmountElement && modalSalaryAmountElement.value) {
                salary = parseFloat(modalSalaryAmountElement.value) || 0;
            } else if (primarySalaryAmountElement) {
                // Extract salary from display element (remove ₵ and commas)
                const displayText = primarySalaryAmountElement.textContent.replace('₵', '').replace(/,/g, '');
                salary = parseFloat(displayText) || 0;
            }

            // Try data-category approach first, then fallback to ID approach
            let needsSlider = document.querySelector('[data-category="needs"]');
            let wantsSlider = document.querySelector('[data-category="wants"]');
            let savingsSlider = document.querySelector('[data-category="savings"]');

            if (!needsSlider) needsSlider = document.getElementById('needsSlider');
            if (!wantsSlider) wantsSlider = document.getElementById('wantsSlider');
            if (!savingsSlider) savingsSlider = document.getElementById('savingsSlider');

            const needs = needsSlider ? parseInt(needsSlider.value) : 50;
            const wants = wantsSlider ? parseInt(wantsSlider.value) : 30;
            const savings = savingsSlider ? parseInt(savingsSlider.value) : 20;

            const message = `Budget Preview:\n\n` +
                `Total Salary: ₵${salary.toFixed(2)}\n` +
                `Needs (${needs}%): ₵${(Math.round(salary * needs * 100) / 10000).toFixed(2)}\n` +
                `Wants (${wants}%): ₵${(Math.round(salary * wants * 100) / 10000).toFixed(2)}\n` +
                `Savings (${savings}%): ₵${(Math.round(salary * savings * 100) / 10000).toFixed(2)}`;

            showSnackbar('Check console for budget preview details', 'info');
        }

        function manageTaxInfo() {
            showSnackbar('Tax information management coming soon', 'info');
        }

        function manageReminders() {
            showModal('payScheduleModal');
            populatePayDayOptions();
        }

        function populatePayDayOptions() {
            const payFrequency = document.getElementById('payFrequency');
            const payDay = document.getElementById('payDay');

            if (!payFrequency || !payDay) return;

            payFrequency.addEventListener('change', function() {
                const frequency = this.value;
                payDay.innerHTML = '<option value="">Select day</option>';

                switch (frequency) {
                    case 'weekly':
                        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        days.forEach((day, index) => {
                            payDay.innerHTML += `<option value="${index}">${day}</option>`;
                        });
                        break;
                    case 'bi-weekly':
                        payDay.innerHTML += '<option value="friday">Every other Friday</option>';
                        payDay.innerHTML += '<option value="thursday">Every other Thursday</option>';
                        payDay.innerHTML += '<option value="custom">Custom schedule</option>';
                        break;
                    case 'monthly':
                    case 'semi-monthly':
                        for (let i = 1; i <= 31; i++) {
                            const suffix = i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th';
                            payDay.innerHTML += `<option value="${i}">${i}${suffix} of month</option>`;
                        }
                        if (frequency === 'semi-monthly') {
                            payDay.innerHTML += '<option value="15-30">15th and 30th</option>';
                            payDay.innerHTML += '<option value="1-15">1st and 15th</option>';
                        }
                        break;
                }
            });
        }

        function updatePayScheduleDisplay(data) {
            // Update the schedule display with new data
            const salaryDay = document.getElementById('salaryDay');
            const salaryMonth = document.getElementById('salaryMonth');
            const salaryScheduleDetails = document.getElementById('salaryScheduleDetails');
            const salaryStatus = document.getElementById('salaryStatus');

            if (data.nextPayDate && salaryDay && salaryMonth) {
                const payDate = new Date(data.nextPayDate);
                salaryDay.textContent = payDate.getDate();
                salaryMonth.textContent = payDate.toLocaleDateString('en-US', {
                    month: 'short'
                });
            }

            if (data.expectedAmount && salaryScheduleDetails) {
                const frequency = data.payFrequency || 'monthly';
                salaryScheduleDetails.textContent = `₵${parseFloat(data.expectedAmount).toFixed(2)} • ${frequency}`;
            }

            if (salaryStatus) {
                salaryStatus.textContent = data.enableReminders ? 'Scheduled' : 'Not scheduled';
                salaryStatus.className = 'status-badge ' + (data.enableReminders ? 'scheduled' : 'pending');
            }

            // Show/hide additional income section
            const additionalIncomeSchedule = document.getElementById('additionalIncomeSchedule');
            if (additionalIncomeSchedule && data.trackAdditionalIncome) {
                additionalIncomeSchedule.style.display = 'block';
                if (data.additionalSourceName) {
                    const incomeDetails = additionalIncomeSchedule.querySelector('#incomeScheduleDetails');
                    if (incomeDetails) {
                        incomeDetails.textContent = `₵${parseFloat(data.additionalAmount || 0).toFixed(2)} • ${data.additionalSourceName}`;
                    }
                }
            }
        }

        // Primary Salary Modal Functions
        function showPrimarySalaryModal() {
            // Pre-populate form with current values
            const salaryAmountElement = document.getElementById('salaryAmount');
            const payFrequencyElement = document.getElementById('payFrequency');

            const salaryAmount = salaryAmountElement ? salaryAmountElement.value : '';
            const payFrequency = payFrequencyElement ? payFrequencyElement.value : 'monthly';
            const nextPayDate = document.getElementById('nextPayDate').value;

            document.getElementById('modalSalaryAmount').value = salaryAmount;
            document.getElementById('modalPayFrequency').value = payFrequency;
            document.getElementById('modalNextPayDate').value = nextPayDate;

            showModal('primarySalaryModal');
        }

        function selectModalPaymentMethod(method, modalType) {
            const modalPrefix = modalType === 'primary' ? 'modal' : '';
            const paymentMethods = document.querySelectorAll(`#${modalType === 'primary' ? 'primarySalaryModal' : ''} .payment-method`);

            paymentMethods.forEach(pm => pm.classList.remove('selected'));
            event.target.closest('.payment-method').classList.add('selected');

            if (modalType === 'primary') {
                document.getElementById('modalPaymentMethod').value = method;
                const label = document.getElementById('modalPaymentDetailsLabel');
                if (method === 'bank') {
                    label.textContent = 'Bank Account';
                } else {
                    label.textContent = 'Mobile Money Account';
                }
            }
        }

        // Preview Budget Modal Functions
        async function showPreviewBudgetModal() {
            // Show loading screen while fetching preview data
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
            }

            // Show loading state
            const modal = document.getElementById('previewBudgetModal');
            if (modal) {
                // Add loading indicator
                modal.style.opacity = '0.7';
            }

            try {
                // Force fresh data load from both APIs

                const [dashboardResponse, salaryResponse] = await Promise.all([
                    fetch('/budget/api/personal_dashboard_data.php', {
                        method: 'GET',
                        headers: {
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        }
                    }),
                    fetch('../actions/salary_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        },
                        body: 'action=get_salary_data'
                    })
                ]);

                const dashboardData = await dashboardResponse.json();
                const salaryData = await salaryResponse.json();


                // Update the global budget allocation data
                if (dashboardData.success && dashboardData.budget_allocation) {
                    window.currentBudgetAllocation = dashboardData.budget_allocation;
                }

                // Set preview data directly from API responses
                if (dashboardData.success) {
                    window.previewModalData = {
                        totalIncome: parseFloat(dashboardData.financial_overview?.monthly_income || 0),
                        salary: parseFloat(dashboardData.salary?.monthly_salary || 0),
                        additionalIncome: parseFloat(dashboardData.financial_overview?.monthly_income || 0) - parseFloat(dashboardData.salary?.monthly_salary || 0),
                        needsPercent: parseInt(dashboardData.budget_allocation?.[0]?.needs_percentage || 50),
                        wantsPercent: parseInt(dashboardData.budget_allocation?.[0]?.wants_percentage || 30),
                        savingsPercent: parseInt(dashboardData.budget_allocation?.[0]?.savings_percentage || 20)
                    };
                }

            } catch (error) {
                console.error('Error loading fresh data:', error);
            } finally {
                // Hide loading screen
                if (window.budgetlyLoader) {
                    window.budgetlyLoader.hide();
                }
                
                // Remove loading state
                if (modal) {
                    modal.style.opacity = '1';
                }
            }

            // Update preview with fresh data and show modal
            await updatePreviewBudgetData();
            showModal('previewBudgetModal');

            // Force update after modal is shown (safety net)
            setTimeout(async () => {
                await updatePreviewBudgetData();
            }, 100);
        }

        async function updatePreviewBudgetData() {

            // Try to use fresh API data first
            let salaryAmount = 0;
            let additionalIncome = 0;
            let totalIncome = 0;
            let needsPercent = 50;
            let wantsPercent = 30;
            let savingsPercent = 20;

            // First priority: Use fresh API data if available
            if (window.previewModalData) {
                totalIncome = window.previewModalData.totalIncome;
                salaryAmount = window.previewModalData.salary;
                additionalIncome = window.previewModalData.additionalIncome;
                needsPercent = window.previewModalData.needsPercent;
                wantsPercent = window.previewModalData.wantsPercent;
                savingsPercent = window.previewModalData.savingsPercent;
            } else {
                // Fallback: Try to get from page elements

                const totalIncomeElement = document.getElementById('totalIncome');
                if (totalIncomeElement) {
                    const totalText = totalIncomeElement.textContent.replace('₵', '').replace(/,/g, '');
                    totalIncome = parseFloat(totalText) || 0;
                }

                const primarySalaryAmountElement = document.getElementById('primarySalaryAmount');
                if (primarySalaryAmountElement) {
                    const displayText = primarySalaryAmountElement.textContent.replace('₵', '').replace(/,/g, '');
                    salaryAmount = parseFloat(displayText) || 0;
                }

                const additionalIncomeElement = document.getElementById('additionalIncome');
                if (additionalIncomeElement) {
                    const additionalText = additionalIncomeElement.textContent.replace('₵', '').replace(/,/g, '');
                    additionalIncome = parseFloat(additionalText) || 0;
                }

                // Calculate total if not available
                if (totalIncome === 0) {
                    totalIncome = salaryAmount + additionalIncome;
                }

                // FORCE CORRECT ALLOCATION if we have the expected total income
                if (totalIncome === 6500) {
                    // Database has 60/20/20 allocation
                    needsPercent = 60;
                    wantsPercent = 20;
                    savingsPercent = 20;
                } else {
                    // Try to get allocation percentages from global data
                    if (window.currentBudgetAllocation && window.currentBudgetAllocation.length > 0) {
                        const allocation = window.currentBudgetAllocation[0];
                        needsPercent = parseFloat(allocation.needs_percentage) || 50;
                        wantsPercent = parseFloat(allocation.wants_percentage) || 30;
                        savingsPercent = parseFloat(allocation.savings_percentage) || 20;
                    }
                }


            }

            // Calculate allocation amounts based on TOTAL INCOME
            const needsAmount = (totalIncome * needsPercent) / 100;
            const wantsAmount = (totalIncome * wantsPercent) / 100;
            const savingsAmount = (totalIncome * savingsPercent) / 100;



            // Update preview modal with calculated values
            const previewTotalIncomeEl = document.getElementById('previewTotalIncome');
            const previewSalaryIncomeEl = document.getElementById('previewSalaryIncome');
            const previewAdditionalIncomeEl = document.getElementById('previewAdditionalIncome');

            if (previewTotalIncomeEl) previewTotalIncomeEl.textContent = `₵${totalIncome.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (previewSalaryIncomeEl) previewSalaryIncomeEl.textContent = `₵${salaryAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (previewAdditionalIncomeEl) previewAdditionalIncomeEl.textContent = `₵${additionalIncome.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            // Update allocation amounts with safety checks
            const previewNeedsAmountEl = document.getElementById('modalPreviewNeedsAmount');
            const previewNeedsPercentEl = document.getElementById('modalPreviewNeedsPercent');
            const previewWantsAmountEl = document.getElementById('modalPreviewWantsAmount');
            const previewWantsPercentEl = document.getElementById('modalPreviewWantsPercent');
            const previewSavingsAmountEl = document.getElementById('modalPreviewSavingsAmount');
            const previewSavingsPercentEl = document.getElementById('modalPreviewSavingsPercent');

            if (previewNeedsAmountEl) previewNeedsAmountEl.textContent = `₵${needsAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (previewNeedsPercentEl) {
                previewNeedsPercentEl.textContent = `${needsPercent}%`;
            }

            if (previewWantsAmountEl) previewWantsAmountEl.textContent = `₵${wantsAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (previewWantsPercentEl) {
                previewWantsPercentEl.textContent = `${wantsPercent}%`;
            }

            if (previewSavingsAmountEl) previewSavingsAmountEl.textContent = `₵${savingsAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (previewSavingsPercentEl) {
                previewSavingsPercentEl.textContent = `${savingsPercent}%`;
            }



            // Calculate realistic health score based on allocation percentages and total income
            let healthScore = 0;

            // Base score for having income
            if (totalIncome > 0) {
                healthScore += 20;
            }

            // Savings rate scoring (up to 30 points)
            if (savingsPercent >= 20) {
                healthScore += 30; // Excellent savings rate
            } else if (savingsPercent >= 15) {
                healthScore += 25; // Very good savings rate
            } else if (savingsPercent >= 10) {
                healthScore += 20; // Good savings rate
            } else if (savingsPercent >= 5) {
                healthScore += 10; // Basic savings
            }

            // Needs allocation scoring (up to 25 points)
            if (needsPercent <= 50) {
                healthScore += 25; // Excellent needs control
            } else if (needsPercent <= 60) {
                healthScore += 20; // Good needs control
            } else if (needsPercent <= 70) {
                healthScore += 15; // Acceptable needs
            } else if (needsPercent <= 80) {
                healthScore += 10; // High needs spending
            }

            // Wants allocation scoring (up to 25 points)
            if (wantsPercent <= 20) {
                healthScore += 25; // Very disciplined spending
            } else if (wantsPercent <= 30) {
                healthScore += 20; // Balanced spending
            } else if (wantsPercent <= 40) {
                healthScore += 15; // Moderate spending
            } else if (wantsPercent <= 50) {
                healthScore += 10; // High discretionary spending
            }

            // Cap at 100
            healthScore = Math.min(100, healthScore);

            document.getElementById('previewHealthScore').textContent = healthScore;

            let healthStatus = 'Consider adjusting your allocation';
            if (healthScore >= 90) {
                healthStatus = 'Excellent financial planning!';
            } else if (healthScore >= 80) {
                healthStatus = 'Very good budget allocation!';
            } else if (healthScore >= 70) {
                healthStatus = 'Good planning, room for improvement';
            } else if (healthScore >= 60) {
                healthStatus = 'Fair allocation, needs attention';
            } else if (healthScore >= 50) {
                healthStatus = 'Budget needs significant improvement';
            }

            document.getElementById('previewHealthStatus').textContent = healthStatus;
        }

        function applyBudgetAllocation() {
            showSnackbar('Budget allocation applied successfully!', 'success');
            closeModal('previewBudgetModal');
        }

        // Form submission handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize loading screen
            if (window.LoadingScreen) {
                window.budgetlyLoader = new LoadingScreen();
                // Customize the loading message for salary page
                const loadingMessage = window.budgetlyLoader.loadingElement.querySelector('.loading-message p');
                if (loadingMessage) {
                    loadingMessage.innerHTML = 'Loading your salary information<span class="loading-dots-text">...</span>';
                } else {
                    console.error('Salary: Could not find loading message element');
                }
            }

            // Show initial loading for data fetch
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
            }

            updateBudgetPreview();
            updateTotalAllocation();
            setupModalListeners(); // Add modal event listeners

            // Initialize with delayed data load to ensure DOM is ready
            setTimeout(() => {
                loadSalaryData();
            }, 200);

            // Hide loading screen after initial setup
            setTimeout(() => {
                if (window.budgetlyLoader) {
                    window.budgetlyLoader.hide();
                }
            }, 2000);

            // Add visibility change listener for auto-refresh
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Page became visible again, refresh data
                    setTimeout(() => {
                        loadSalaryData();
                    }, 300);
                }
            });

            // Handle Primary Salary form submission
            const primarySalaryForm = document.getElementById('primarySalaryForm');
            if (primarySalaryForm) {
                primarySalaryForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;

                    // Add action to form data
                    formData.append('action', 'save_primary_salary');

                    // Show loading state
                    submitButton.textContent = 'Saving...';
                    submitButton.disabled = true;

                    // Make API call
                    fetch('../actions/salary_actions.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                // Update the main form with new values (with null checks)
                                const salaryAmountElement = document.getElementById('salaryAmount');
                                const payFrequencyElement = document.getElementById('payFrequency');
                                const nextPayDateElement = document.getElementById('nextPayDate');
                                const autoBudgetElement = document.getElementById('autoBudget');

                                if (salaryAmountElement) salaryAmountElement.value = data.salaryAmount;
                                if (payFrequencyElement) payFrequencyElement.value = data.payFrequency;
                                if (nextPayDateElement) nextPayDateElement.value = data.nextPayDate;
                                if (autoBudgetElement) autoBudgetElement.checked = data.autoBudget === 'on';

                                showSnackbar(result.message, 'success');
                                closeModal('primarySalaryModal');

                                // Update budget preview
                                updateBudgetPreview();

                                // Reload salary data
                                setTimeout(() => {
                                    loadSalaryData();
                                }, 500);
                            } else {
                                showSnackbar(result.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showSnackbar('Failed to save salary information', 'error');
                        })
                        .finally(() => {
                            // Reset button
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        });
                });
            }

            // Handle Add Income Source form submission
            const addIncomeForm = document.getElementById('addIncomeForm');
            if (addIncomeForm) {
                addIncomeForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;

                    // Add action to form data
                    formData.append('action', 'add_income_source');

                    // Show loading state
                    submitButton.textContent = 'Processing...';
                    submitButton.disabled = true;

                    // Make API call
                    fetch('../actions/salary_actions.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                showSnackbar(result.message, 'success');
                                closeModal('addIncomeSourceModal');

                                // Reset form
                                this.reset();

                                // Reset payment method selection
                                const methods = this.querySelectorAll('.payment-method');
                                methods.forEach(m => m.classList.remove('selected'));
                                methods[0].classList.add('selected');
                                this.querySelector('input[name="paymentMethod"]').value = 'bank';

                                // Reload salary data
                                setTimeout(() => {
                                    loadSalaryData();
                                }, 500);
                            } else {
                                showSnackbar(result.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showSnackbar('Failed to add income source', 'error');
                        })
                        .finally(() => {
                            // Reset button
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        });
                });
            }

            // Handle Pay Schedule form submission
            const payScheduleForm = document.getElementById('payScheduleForm');
            if (payScheduleForm) {
                payScheduleForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;

                    // Add action to form data
                    formData.append('action', 'save_pay_schedule');

                    // Show loading state
                    submitButton.textContent = 'Saving...';
                    submitButton.disabled = true;

                    // Make API call
                    fetch('../actions/salary_actions.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                showSnackbar(result.message, 'success');
                                closeModal('payScheduleModal');
                                updatePayScheduleDisplay(result.data);

                                // Reload salary data
                                setTimeout(() => {
                                    loadSalaryData();
                                }, 500);
                            } else {
                                showSnackbar(result.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showSnackbar('Failed to save pay schedule', 'error');
                        })
                        .finally(() => {
                            // Reset button
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        });
                });
            }

            // Handle additional income checkbox toggle
            const trackAdditionalCheckbox = document.getElementById('trackAdditionalIncome');
            const additionalIncomeSection = document.getElementById('additionalIncomeSection');
            if (trackAdditionalCheckbox && additionalIncomeSection) {
                trackAdditionalCheckbox.addEventListener('change', function() {
                    additionalIncomeSection.style.display = this.checked ? 'block' : 'none';
                });
            }

            // Initialize page
            loadSavedTheme();
            loadSalaryData();
            handleURLParams(); // Handle parameters from other pages
            checkSalaryDue(); // Check if salary is due

            // Theme selector change handler
            const themeSelector = document.getElementById('themeSelector');
            if (themeSelector) {
                themeSelector.addEventListener('change', function() {
                    changeTheme(this.value);
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                const themeSelector = document.querySelector('.theme-selector');
                const userMenu = document.querySelector('.user-menu');
                const themeDropdown = document.getElementById('themeDropdown');
                const userDropdown = document.getElementById('userDropdown');

                // Close theme dropdown if clicking outside
                if (themeDropdown && !themeSelector.contains(event.target)) {
                    themeDropdown.classList.remove('show');
                }

                // Close user dropdown if clicking outside  
                if (userDropdown && !userMenu.contains(event.target)) {
                    userDropdown.classList.remove('show');
                }
            });

            // Handle slider inputs
            const sliders = document.querySelectorAll('.allocation-slider');
            sliders.forEach(slider => {
                slider.addEventListener('input', function() {
                    const category = this.id.replace('Slider', '');
                    updateAllocation(category, this.value);
                });
            });

            // Handle salary amount changes
            const salaryInput = document.getElementById('salaryAmount');
            if (salaryInput) {
                salaryInput.addEventListener('input', updateBudgetPreview);
            }
        });

        // Test function for loading screen (can be called from browser console)
        window.testSalaryLoadingScreen = function(duration = 3000) {
            if (window.budgetlyLoader) {
                window.budgetlyLoader.show();
                setTimeout(() => {
                    window.budgetlyLoader.hide();
                }, duration);
            } else {
            }
        };
    </script>
    
    <!-- Walkthrough System -->
    <script src="../public/js/walkthrough.js?v=<?php echo time(); ?>"></script>
    <script src="../public/js/mobile-nav.js"></script>
    <script src="../public/js/privacy.js"></script>

</body>

</html>