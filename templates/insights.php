<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
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
    header('Location: ../index');
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
    <title>Financial Insights - Smart Analytics</title>
    <?php include '../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../public/css/insights.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Custom Properties for Theme System */
        :root {
            /* Default Theme (Ocean Blue) */
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-darker: #1d4ed8;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --accent-color: #f59e0b;
            --accent-dark: #d97706;
            --danger-color: #ef4444;
            --background-gradient: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
            --card-background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --hover-color: #f1f5f9;
            --shadow-color: rgba(59, 130, 246, 0.15);
            --theme-name: 'Ocean Blue';
            
            /* Gradient Variables */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --secondary-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --accent-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            
            /* Additional Variables */
            --input-background: #ffffff;
            --text-muted: #9ca3af;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.5);
            --card-shadow-sm: 0 4px 15px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            --button-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --primary-shadow: rgba(59, 130, 246, 0.1);
            --danger-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            --warning-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
            --secondary-background: #f1f5f9;
            --success-color: #059669;
            --warning-color: #d97706;
            --accent-background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            --accent-border: rgba(59, 130, 246, 0.2);
            --primary-light: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            --success-light: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --warning-light: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            --accent-light: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }

        /* Forest Green Theme */
        [data-theme="forest"] {
            --primary-color: #059669;
            --primary-dark: #047857;
            --primary-darker: #065f46;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --accent-color: #34d399;
            --accent-dark: #10b981;
            --danger-color: #ef4444;
            --background-gradient: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #f8fafc 100%);
            --card-background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #d1fae5;
            --hover-color: #ecfdf5;
            --shadow-color: rgba(5, 150, 105, 0.15);
            --theme-name: 'Forest Green';
            
            /* Gradient Variables for Forest Theme */
            --primary-gradient: linear-gradient(135deg, #059669 0%, #047857 100%);
            --secondary-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --accent-gradient: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            
            /* Additional Variables for Forest Theme */
            --input-background: #ffffff;
            --text-muted: #9ca3af;
            --card-shadow: 0 10px 30px rgba(5, 150, 105, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.5);
            --card-shadow-sm: 0 4px 15px rgba(5, 150, 105, 0.05);
            --hover-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            --button-shadow: 0 2px 8px rgba(5, 150, 105, 0.1);
            --primary-shadow: rgba(5, 150, 105, 0.1);
            --danger-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            --warning-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
            --secondary-background: #ecfdf5;
            --success-color: #059669;
            --warning-color: #d97706;
            --accent-background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --accent-border: rgba(5, 150, 105, 0.2);
            --primary-light: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --success-light: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --warning-light: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            --accent-light: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        /* Additional Theme - Sunset Orange */
        [data-theme="sunset"] {
            --primary-color: #f59e0b;
            --primary-dark: #d97706;
            --primary-darker: #b45309;
            --secondary-color: #f97316;
            --secondary-dark: #ea580c;
            --accent-color: #fbbf24;
            --accent-dark: #f59e0b;
            --danger-color: #ef4444;
            --background-gradient: linear-gradient(135deg, #fffbeb 0%, #fef3c7 50%, #f8fafc 100%);
            --card-background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #fed7aa;
            --hover-color: #fef3c7;
            --shadow-color: rgba(245, 158, 11, 0.15);
            --theme-name: 'Sunset Orange';
            
            /* Gradient Variables for Sunset Theme */
            --primary-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --secondary-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            --accent-gradient: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            
            /* Additional Variables for Sunset Theme */
            --input-background: #ffffff;
            --text-muted: #9ca3af;
            --card-shadow: 0 10px 30px rgba(245, 158, 11, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.5);
            --card-shadow-sm: 0 4px 15px rgba(245, 158, 11, 0.05);
            --hover-shadow: 0 20px 40px rgba(245, 158, 11, 0.15);
            --button-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);
            --primary-shadow: rgba(245, 158, 11, 0.1);
            --danger-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            --warning-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
            --secondary-background: #fef3c7;
            --success-color: #059669;
            --warning-color: #d97706;
            --accent-background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            --accent-border: rgba(245, 158, 11, 0.2);
            --primary-light: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            --success-light: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --warning-light: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            --accent-light: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }

        /* Additional Theme - Purple Dreams */
        [data-theme="purple"] {
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-darker: #6d28d9;
            --secondary-color: #a855f7;
            --secondary-dark: #9333ea;
            --accent-color: #c084fc;
            --accent-dark: #a855f7;
            --danger-color: #ef4444;
            --background-gradient: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 50%, #f8fafc 100%);
            --card-background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e9d5ff;
            --hover-color: #f3e8ff;
            --shadow-color: rgba(139, 92, 246, 0.15);
            --theme-name: 'Purple Dreams';
            
            /* Gradient Variables for Purple Theme */
            --primary-gradient: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --secondary-gradient: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            --accent-gradient: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            
            /* Additional Variables for Purple Theme */
            --input-background: #ffffff;
            --text-muted: #9ca3af;
            --card-shadow: 0 10px 30px rgba(139, 92, 246, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.5);
            --card-shadow-sm: 0 4px 15px rgba(139, 92, 246, 0.05);
            --hover-shadow: 0 20px 40px rgba(139, 92, 246, 0.15);
            --button-shadow: 0 2px 8px rgba(139, 92, 246, 0.1);
            --primary-shadow: rgba(139, 92, 246, 0.1);
            --danger-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            --warning-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
            --secondary-background: #f3e8ff;
            --success-color: #059669;
            --warning-color: #d97706;
            --accent-background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            --accent-border: rgba(139, 92, 246, 0.2);
            --primary-light: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            --success-light: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --warning-light: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            --accent-light: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--background-gradient);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 16px;
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .dashboard {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--background-gradient);
        }

        /* Enhanced Container Styles */
        .container, .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: var(--card-background);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 50%, var(--primary-darker) 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px var(--shadow-color);
            transition: all 0.3s ease;
        }

        .header:hover {
            box-shadow: 0 6px 30px var(--shadow-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            font-size: 1.8rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .logo-text h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: white !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 0;
            background: none !important;
            -webkit-text-fill-color: white !important;
            background-clip: unset !important;
        }

        .logo-text p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.95);
            margin: 0;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        /* Global Heading Styles */
        h1, h2, h3, h4, h5, h6 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            color: var(--text-primary);
            font-weight: 700;
            line-height: 1.3;
            margin: 0 0 1rem 0;
            letter-spacing: -0.02em;
        }

        /* Global heading styles - except header */
        .main-content h1,
        .insights-hero h1,
        .container h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        h5 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        h6 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .logo-text p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.95);
            margin-top: -4px;
        }

        .header-nav {
            display: flex;
            gap: 0.5rem;
        }

        .nav-item {
            padding: 0.75rem 1.25rem;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.85);
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            left: 0;
        }

        .nav-item:hover,
        .nav-item.active {
            color: white;
            transform: translateY(-2px);
        }

        .theme-selector {
            position: relative;
        }

        .theme-toggle-btn {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .theme-toggle-btn:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .theme-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--card-background);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            min-width: 250px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .theme-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .theme-dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--primary-light);
        }

        .theme-dropdown-header h4 {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            margin: 0;
            text-align: center;
        }

        .themes-grid {
            padding: 1rem;
            display: grid;
            gap: 0.75rem;
        }

        .theme-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .theme-option:hover {
            background: var(--hover-color);
            transform: translateX(4px);
        }

        .theme-option.active {
            background: var(--primary-light);
            border-color: var(--primary-color);
        }

        .theme-preview {
            display: flex;
            gap: 4px;
        }

        .theme-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }

        .user-menu {
            position: relative;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px var(--shadow-color);
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .user-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--card-background);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-info {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--primary-light);
            text-align: center;
        }

        .user-info h4 {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            margin: 0 0 0.25rem 0;
        }

        .user-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--hover-color);
            color: var(--text-primary);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            min-height: calc(100vh - 80px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Hero Section */
        .insights-hero {
            text-align: center;
            margin-bottom: 3rem;
            background: var(--card-background);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .insights-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color), var(--danger-color));
            border-radius: 24px 24px 0 0;
        }

        .insights-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(
                circle at 30% 20%,
                rgba(59, 130, 246, 0.1) 0%,
                transparent 40%
            ),
            radial-gradient(
                circle at 70% 80%,
                rgba(16, 185, 129, 0.1) 0%,
                transparent 40%
            );
            pointer-events: none;
        }

        .insights-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 2;
            text-shadow: none;
        }

        .insights-hero p {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 2;
            font-weight: 500;
        }

        .insights-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px var(--shadow-color);
            letter-spacing: -0.02em;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Insights Grid */
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        /* Enhanced Div and Card Styles */
        div {
            transition: all 0.3s ease;
        }

        .insight-card {
            background: var(--card-background);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .insight-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color), var(--danger-color));
            border-radius: 24px 24px 0 0;
        }

        .card, .dashboard-card, .main-card {
            background: var(--card-background);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before, .dashboard-card::before, .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color), var(--danger-color));
            border-radius: 20px 20px 0 0;
        }

        .card:hover, .dashboard-card:hover, .main-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--hover-shadow);
        }

        .insight-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .insight-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }

        .insight-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .insight-card-header h3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .insight-icon {
            font-size: 1.5rem;
            display: inline-block;
        }

        .refresh-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: none;
            background: var(--primary-gradient);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .refresh-btn:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        /* Health Score Specific */
        .health-score {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .health-score-number {
            font-size: 4rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px var(--shadow-color);
            letter-spacing: -0.02em;
            transition: all 0.3s ease;
        }

        .health-score-number.excellent {
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .health-score-number.good {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .health-score-number.warning {
            background: var(--warning-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .health-score-number.poor {
            background: var(--danger-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .health-status {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--success-color);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .progress-ring svg {
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            transition: stroke-dashoffset 1s ease-in-out;
        }

        /* Enhanced Progress Bar Styles */
        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--secondary-background);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid var(--border-color);
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 10px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-bar.success .progress-bar-fill {
            background: var(--success-gradient);
        }

        .progress-bar.warning .progress-bar-fill {
            background: var(--warning-gradient);
        }

        .progress-bar.danger .progress-bar-fill {
            background: var(--danger-gradient);
        }

        .circular-progress {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(
                var(--primary-color) 0deg,
                var(--primary-color) calc(var(--progress, 0) * 3.6deg),
                var(--secondary-background) calc(var(--progress, 0) * 3.6deg),
                var(--secondary-background) 360deg
            );
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--card-shadow-sm);
            border: 2px solid var(--border-color);
        }

        .circular-progress::before {
            content: '';
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--card-background);
        }

        .circular-progress-text {
            position: relative;
            z-index: 1;
            font-weight: 700;
            color: var(--text-primary);
        }

        .recommendations {
            width: 100%;
            text-align: left;
        }

        .recommendation {
            padding: 1rem 1.5rem;
            margin: 0.75rem 0;
            background: var(--primary-light);
            border-radius: 16px;
            border-left: 4px solid var(--primary-color);
            font-size: 0.95rem;
            color: var(--text-primary);
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .recommendation:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .recommendation.success {
            background: var(--success-light);
            border-left-color: var(--success-color);
        }

        .recommendation.warning {
            background: var(--warning-light);
            border-left-color: var(--warning-color);
        }

        .recommendation.info {
            background: var(--primary-light);
            border-left-color: var(--primary-color);
        }

        .recommendation::before {
            content: '💡';
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0.3;
            font-size: 1.2rem;
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .metric-item {
            text-align: center;
            padding: 1rem;
            border-radius: 12px;
            background: var(--card-background);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .metric-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .metric-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Animation keyframes */
        @keyframes progressRing {
            0% { stroke-dashoffset: 314; }
            100% { stroke-dashoffset: var(--progress-offset, 94); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .recommendation-item {
            animation: fadeInUp 0.5s ease forwards;
        }

        /* Chat Message Styling */
        .chat-message-text {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .chat-data-summary {
            background: var(--secondary-background);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .data-item {
            margin-bottom: 0.25rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .data-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .data-item strong {
            color: var(--primary-color);
        }

        /* AI Predictions Styling */
        .insights-predictions {
            margin-bottom: 1rem;
        }

        .prediction-item {
            background: linear-gradient(135deg, var(--card-background) 0%, var(--secondary-background) 100%);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.5;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .prediction-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--primary-shadow);
        }

        .prediction-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
        }

        /* Chat Data Summary Styling */
        .chat-data-summary {
            background: var(--surface-secondary, var(--card-background));
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            border-left: 3px solid var(--accent-color, var(--primary-color));
            transition: all 0.3s ease;
        }

        .chat-data-summary:hover {
            background: var(--surface-hover, rgba(255,255,255,0.05));
            transform: translateX(2px);
        }

        .chat-data-summary h4 {
            color: var(--accent-color, var(--primary-color));
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .chat-data-summary p {
            margin: 4px 0;
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .chat-data-summary .data-highlight {
            color: var(--accent-color, var(--primary-color));
            font-weight: 600;
        }

        .chat-message-text .amount {
            color: var(--secondary-color);
            font-weight: 700;
        }

        .chat-message-text .percentage {
            color: var(--accent-color);
            font-weight: 700;
        }

        .chat-message-text {
            line-height: 1.6;
        }

        .prediction-meta {
            margin-top: 15px;
            padding: 8px 12px;
            background: var(--surface-tertiary, rgba(255,255,255,0.03));
            border-radius: 6px;
            border-left: 2px solid var(--accent-color, var(--primary-color));
        }

        .prediction-meta small {
            color: var(--text-secondary);
            font-size: 12px;
            opacity: 0.8;
        }

        .insights-predictions h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        /* Error States */
        .error-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: var(--text-secondary);
            text-align: center;
        }

        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .retry-btn {
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        /* Chart Specific Styles */
        .chart-container {
            position: relative;
            width: 100%;
            height: 300px;
            background: var(--card-background);
            border-radius: 16px;
            padding: 1rem;
            box-shadow: var(--card-shadow-sm);
            border: 1px solid var(--border-color);
        }

        #goalProgressChart,
        #spendingPatternsChart,
        #budgetPerformanceChart,
        #incomeTrendsChart {
            background: transparent !important;
            border-radius: 12px;
        }

        /* Loading States */
        .skeleton {
            background: linear-gradient(90deg, var(--secondary-background) 25%, var(--border-color) 50%, var(--secondary-background) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
            height: 20px;
            margin: 10px 0;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Chat Assistant */
        .chat-assistant {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .chat-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 40px var(--shadow-color);
            transition: all 0.3s ease;
        }

        .chat-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 16px 50px var(--shadow-color);
        }

        .chat-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 450px;
            height: 650px;
            background: var(--card-background);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transform: scale(0);
            transform-origin: bottom right;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .chat-window.open {
            transform: scale(1);
        }

        .chat-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .chat-messages {
            height: 450px;
            overflow-y: auto;
            padding: 1rem;
            background: var(--secondary-background);
        }

        .chat-message {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            max-width: 85%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease;
        }

        .chat-message.user {
            background: var(--primary-gradient);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 6px;
        }

        .chat-message.assistant {
            background: var(--card-background);
            color: var(--text-primary);
            border-bottom-left-radius: 6px;
            box-shadow: var(--card-shadow-sm);
        }

        .suggestion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .suggestion-chip {
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid var(--accent-border);
            transition: all 0.3s ease;
        }

        .suggestion-chip:hover {
            background: var(--primary-gradient);
            color: white;
            transform: scale(1.05);
        }

        .chat-input {
            display: flex;
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            background: var(--card-background);
        }

        .chat-input input {
            flex: 1;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            padding: 0.75rem 1rem;
            outline: none;
            font-size: 0.9rem;
            background: var(--input-background);
        }

        .chat-input input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-shadow);
        }

        .chat-input button {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-left: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .chat-input button:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        /* Snackbar */
        .snackbar {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--card-background);
            color: var(--text-primary);
            backdrop-filter: blur(20px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            z-index: 10000;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
        }

        .snackbar.show {
            transform: translateX(-50%) translateY(0);
        }

        .snackbar.success { border-left: 4px solid var(--success-color); }
        .snackbar.error { border-left: 4px solid var(--danger-color, #ef4444); }
        .snackbar.warning { border-left: 4px solid var(--warning-color); }
        .snackbar.info { border-left: 4px solid var(--primary-color); }

        .snackbar-icon {
            font-size: 1.2rem;
        }

        .snackbar-message {
            flex: 1;
            color: var(--text-primary);
            font-weight: 500;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .insights-hero {
                padding: 2rem 1rem;
            }

            .insights-hero h1 {
                font-size: 2rem;
            }

            .insights-stats {
                flex-direction: column;
                gap: 1.5rem;
            }

            .insights-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .main-content {
                padding: 1rem;
            }

            .chat-window {
                width: calc(100vw - 2rem);
                height: 70vh;
                right: 1rem;
                left: 1rem;
            }

            .chat-messages {
                height: calc(70vh - 180px);
            }

            .chat-assistant {
                bottom: 1rem;
                right: 1rem;
            }
        }

        @media (max-width: 480px) {
            .insights-grid {
                grid-template-columns: 1fr;
            }

            .insight-card {
                padding: 1.5rem;
            }

            .logo-text h1 {
                font-size: 1.25rem;
            }

            .nav-item {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon">💰</div>
                <div class="logo-text">
                    <h1 id="logoUserName"><?php echo htmlspecialchars($user_first_name); ?></h1>
                    <p>Financial Insights</p>
                </div>
            </div>
            
            <nav class="header-nav">
                <a href="personal-dashboard" class="nav-item">Dashboard</a>
                <a href="salary" class="nav-item">Salary Setup</a>
                <a href="budget" class="nav-item">Budget</a>
                <a href="personal-expense" class="nav-item">Expenses</a>
                <a href="savings" class="nav-item">Savings</a>
                <!-- <a href="insights" class="nav-item active">Insights</a> -->
                <a href="report" class="nav-item">Reports</a>
            </nav>

            <div class="theme-selector">
                <button class="theme-toggle-btn" onclick="toggleThemeSelector()" title="Change Theme">
                    <span class="theme-icon">🎨</span>
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
                            <span>Ocean Blue</span>
                        </div>
                        <div class="theme-option" data-theme="forest" onclick="changeTheme('forest')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #059669, #047857)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #10b981, #059669)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #34d399, #10b981)"></div>
                            </div>
                            <span>Forest Green</span>
                        </div>
                        <div class="theme-option" data-theme="sunset" onclick="changeTheme('sunset')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #f59e0b, #d97706)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #f97316, #ea580c)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #fbbf24, #f59e0b)"></div>
                            </div>
                            <span>Sunset Orange</span>
                        </div>
                        <div class="theme-option" data-theme="purple" onclick="changeTheme('purple')">
                            <div class="theme-preview">
                                <div class="theme-color" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #a855f7, #9333ea)"></div>
                                <div class="theme-color" style="background: linear-gradient(135deg, #c084fc, #a855f7)"></div>
                            </div>
                            <span>Purple Dreams</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="user-menu">
                <div class="user-avatar" onclick="toggleUserMenu()" id="userAvatar"><?php 
                    echo strtoupper(substr($user_first_name, 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)); 
                ?></div>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user_full_name); ?></h4>
                        <p>Personal Account</p>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="../actions/signout.php" class="dropdown-item">
                        <span class="dropdown-icon">🚪</span>
                        Sign Out
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <!-- Hero Section -->
                <div class="insights-hero">
                    <h1>🧠 Smart Financial Insights</h1>
                    <p>Discover patterns, trends, and opportunities in your financial data</p>
                    <div class="insights-stats">
                    <div class="stat-item">
                        <div class="stat-number" id="totalInsights" data-metric="total-insights">0</div>
                        <div class="stat-label">Active Insights</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="healthScore" data-metric="health-score">0</div>
                        <div class="stat-label">Health Score</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="trendsAnalyzed" data-metric="trends-analyzed">0</div>
                        <div class="stat-label">Trends Analyzed</div>
                    </div>
                </div>
            </div>

            <!-- Financial Health Score -->
            <div class="insights-grid">
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">❤️</span> Financial Health</h3>
                        <button class="refresh-btn" onclick="refreshFinancialHealth()">🔄</button>
                    </div>
                    <div class="health-score" id="healthScoreContainer">
                        <div class="health-score-number" id="healthScoreNumber">85</div>
                        <div class="health-status" id="healthStatus">Good</div>
                        <div class="progress-ring">
                            <svg width="120" height="120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="url(#healthGradient)" stroke-width="8" 
                                        stroke-linecap="round" stroke-dasharray="314" stroke-dashoffset="94" id="healthProgress"/>
                                <defs>
                                    <linearGradient id="healthGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:var(--success-color)"/>
                                        <stop offset="100%" style="stop-color:var(--primary-color)"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="recommendations" id="healthRecommendations">
                            <div class="recommendation">💡 Great job! Your spending is well within budget this month.</div>
                            <div class="recommendation">💰 Consider increasing your emergency fund to 6 months of expenses.</div>
                        </div>
                    </div>
                </div>

                <!-- Financial Metrics Overview -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">💰</span> Financial Overview</h3>
                    </div>
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-label">Monthly Income</div>
                            <div class="metric-value" data-metric="income">₵0</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Monthly Expenses</div>
                            <div class="metric-value" data-metric="expenses">₵0</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Total Savings</div>
                            <div class="metric-value" data-metric="savings">₵0</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Expense Ratio</div>
                            <div class="metric-value" data-metric="expense-ratio">0%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Savings Rate</div>
                            <div class="metric-value" data-metric="savings-rate">0%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Total Goals</div>
                            <div class="metric-value" data-metric="total-goals">0</div>
                        </div>
                    </div>
                </div>

                <!-- Spending Patterns -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">📊</span> Spending Patterns</h3>
                        <button class="refresh-btn" onclick="refreshSpendingPatterns()">🔄</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="spendingPatternsChart"></canvas>
                    </div>
                </div>

                <!-- Goal Analytics -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">🎯</span> Goal Progress</h3>
                        <button class="refresh-btn" onclick="refreshGoalAnalytics()">🔄</button>
                    </div>
                    <div id="goalAnalyticsContainer">
                        <div class="chart-container">
                            <canvas id="goalProgressChart"></canvas>
                        </div>
                        <div id="goalStats">
                            <div class="recommendation">
                                <strong>Active Goals:</strong> 3 goals in progress
                            </div>
                            <div class="recommendation">
                                <strong>Completion Rate:</strong> 67% average progress
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget Performance -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">💰</span> Budget Performance</h3>
                        <button class="refresh-btn" onclick="refreshBudgetPerformance()">🔄</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="budgetPerformanceChart"></canvas>
                    </div>
                </div>

                <!-- Income Trends -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">📈</span> Income Trends</h3>
                        <button class="refresh-btn" onclick="refreshIncomeTrends()">🔄</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="incomeTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Predictive Insights -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">🔮</span> AI Predictive Insights</h3>
                        <button class="refresh-btn" onclick="refreshPredictions()">🔄</button>
                    </div>
                    <div id="predictionsContainer">
                        <div class="insights-predictions">
                            <div class="prediction-item">🤖 Loading AI insights...</div>
                        </div>
                    </div>
                </div>

                <!-- Actionable Recommendations -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">💡</span> Smart Recommendations</h3>
                        <button class="refresh-btn" onclick="loadAIPredictions()">🔄</button>
                    </div>
                    <div id="actionableRecommendations">
                        <div class="recommendation">Loading personalized recommendations...</div>
                    </div>
                </div>

                <!-- Financial Benchmarks -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">📊</span> Financial Benchmarks</h3>
                        <button class="refresh-btn" onclick="initializeInsights()">🔄</button>
                    </div>
                    <div id="benchmarksContainer">
                        <div class="recommendation">Loading benchmarks...</div>
                    </div>
                </div>

                <!-- Savings Performance Analytics -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">💰</span> Savings Analytics</h3>
                        <button class="refresh-btn" onclick="initializeInsights()">🔄</button>
                    </div>
                    <div id="savingsMetrics" class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-label">Loading...</div>
                            <div class="metric-value">...</div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Goal Insights -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">🎯</span> Goal Optimization</h3>
                        <button class="refresh-btn" onclick="initializeInsights()">🔄</button>
                    </div>
                    <div id="goalInsightsContainer">
                        <div class="recommendation">Loading goal insights...</div>
                    </div>
                </div>

                <!-- Trend Analysis -->
                <div class="insight-card">
                    <div class="insight-card-header">
                        <h3><span class="insight-icon">📈</span> Trend Analysis</h3>
                        <button class="refresh-btn" onclick="initializeInsights()">🔄</button>
                    </div>
                    <div id="trendInsights">
                        <div class="recommendation">Loading trend analysis...</div>
                    </div>
                </div>
            </div>

            <!-- Chat Assistant -->
            <div class="chat-assistant">
                <div class="chat-window" id="chatWindow">
                    <div class="chat-header">
                        <span>🤖 Financial Assistant</span>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message assistant">
                            Hi! I'm your financial assistant. Ask me anything about your finances!
                        </div>
                        <div class="suggestion-chips">
                            <div class="suggestion-chip" onclick="askQuestion('spending this month')">Monthly Spending</div>
                            <div class="suggestion-chip" onclick="askQuestion('savings progress')">Savings Progress</div>
                            <div class="suggestion-chip" onclick="askQuestion('budget status')">Budget Status</div>
                            <div class="suggestion-chip" onclick="askQuestion('financial health')">Financial Health</div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <input type="text" id="chatInput" placeholder="Ask about your finances..." 
                               onkeypress="handleChatKeyPress(event)">
                        <button onclick="sendChatMessage()">📤</button>
                    </div>
                </div>
                <button class="chat-toggle" id="chatToggle" onclick="toggleChat()">🤖</button>
            </div>
            </div> <!-- Close container -->
        </main>
    </div>

    <script>
        // Global variables for charts
        let chartInstances = {};
        let chatOpen = false;

        // Counter animation function
        function animateCounter(element, start, end, duration = 2000) {
            const startTime = Date.now();
            const startValue = start;
            const endValue = end;
            
            function updateCounter() {
                const now = Date.now();
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const currentValue = startValue + (endValue - startValue) * easeOut;
                
                if (element.dataset.format === 'currency') {
                    element.textContent = '₵' + Math.floor(currentValue).toLocaleString();
                } else if (element.dataset.format === 'percentage') {
                    element.textContent = Math.floor(currentValue) + '%';
                } else {
                    element.textContent = Math.floor(currentValue).toLocaleString();
                }
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        }

        // Utility function to safely fetch from API
        async function fetchInsightsData(action) {
            try {
                const response = await fetch(`../api/enhanced_insights_data.php?action=${action}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                return data;
            } catch (error) {
                console.error(`Error fetching ${action}:`, error);
                showSnackbar(`Failed to load ${action}`, 'error');
                return null;
            }
        }

        // Load all insights data on page load
        async function initializeInsights() {
            showLoadingState();
            
            try {
                // Load essential data first
                const financialHealth = await fetchInsightsData('financial_health');
                if (financialHealth) {
                    updateFinancialHealthDisplay(financialHealth);
                    updateHeroStats(financialHealth);
                }
                
                // Load additional insights progressively
                loadProgressiveInsights();
                
            } catch (error) {
                console.error('Error initializing insights:', error);
                showSnackbar('Failed to load insights', 'error');
            } finally {
                hideLoadingState();
            }
        }

        // Load insights progressively to avoid overwhelming the server
        async function loadProgressiveInsights() {
            // Load spending analytics
            try {
                const spendingData = await fetchInsightsData('spending_patterns');
                if (spendingData && spendingData.current_month_categories) {
                    updateSpendingAnalytics(spendingData);
                }
            } catch (error) {
                console.log('Spending patterns will load later');
            }
            
            // Load recommendations
            try {
                const recommendations = await fetchInsightsData('personalized_recommendations');
                if (recommendations) {
                    updateRecommendations(recommendations);
                }
            } catch (error) {
                console.log('Recommendations will load later');
            }
            
            // Skip AI predictions for now due to server issues
            // TODO: Fix AI predictions endpoint
            
            // Load remaining insights with delays to prevent server overload
            setTimeout(async () => {
                try {
                    const goalData = await fetchInsightsData('goal_optimization');
                    if (goalData) {
                        updateGoalInsights(goalData);
                    }
                } catch (error) {
                    console.log('Goal insights will load later');
                }
            }, 1000);
            
            setTimeout(async () => {
                try {
                    const behavioralData = await fetchInsightsData('behavioral_insights');
                    if (behavioralData) {
                        updateBehavioralInsights(behavioralData);
                    }
                } catch (error) {
                    console.log('Behavioral insights will load later');
                }
            }, 2000);
        }

        // Enhanced update functions for comprehensive insights
        function updateHeroStats(financialHealthData) {
            // Calculate dynamic insights metrics from financial health data
            const totalInsights = financialHealthData?.recommendations?.length || 5;
            const healthScore = financialHealthData?.health_score || 0;
            const trendsAnalyzed = 4; // Health score, expenses, savings, goals
            
            animateCounter(document.querySelector('[data-metric="total-insights"]'), 0, totalInsights, 1200);
            animateCounter(document.querySelector('[data-metric="health-score"]'), 0, healthScore, 1500);
            animateCounter(document.querySelector('[data-metric="trends-analyzed"]'), 0, trendsAnalyzed, 1800);
        }

        function countTotalInsights(data) {
            let count = 0;
            if (data.financial_health?.recommendations) count += data.financial_health.recommendations.length;
            if (data.predictions?.predictions) count += data.predictions.predictions.length;
            if (data.recommendations) count += data.recommendations.length;
            if (data.goal_insights?.goal_insights) count += data.goal_insights.goal_insights.length;
            return Math.max(5, count);
        }

        function updateSpendingAnalytics(spendingData) {
            if (!spendingData) return;
            
            // Update spending patterns chart with real data
            const ctx = document.getElementById('spendingPatternsChart');
            if (!ctx) return;
            
            const context = ctx.getContext('2d');
            
            if (chartInstances.spendingPatterns) {
                chartInstances.spendingPatterns.destroy();
            }

            const categories = spendingData.current_month_categories || [];
            
            chartInstances.spendingPatterns = new Chart(context, {
                type: 'doughnut',
                data: {
                    labels: categories.map(cat => cat.category),
                    datasets: [{
                        data: categories.map(cat => parseFloat(cat.category_total || 0)),
                        backgroundColor: [
                            getThemeVariable('--primary-color'),
                            getThemeVariable('--secondary-color'),
                            getThemeVariable('--accent-color'),
                            getThemeVariable('--warning-color'),
                            getThemeVariable('--success-color'),
                            getThemeVariable('--danger-color')
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                color: getThemeVariable('--text-primary')
                            }
                        }
                    }
                }
            });
        }

        function updateSavingsPerformance(savingsData) {
            if (!savingsData) return;
            
            // Update metrics display
            const metricsContainer = document.querySelector('#savingsMetrics');
            if (metricsContainer) {
                metricsContainer.innerHTML = `
                    <div class="metric-item">
                        <div class="metric-label">Total Saved</div>
                        <div class="metric-value">₵${savingsData.total_saved?.toLocaleString() || '0'}</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Overall Progress</div>
                        <div class="metric-value">${savingsData.overall_progress || 0}%</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Monthly Needed</div>
                        <div class="metric-value">₵${savingsData.monthly_savings_needed?.toLocaleString() || '0'}</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Savings Momentum</div>
                        <div class="metric-value">${savingsData.savings_momentum || 0}%</div>
                    </div>
                `;
            }
        }

        function updateGoalInsights(goalData) {
            if (!goalData) return;
            
            const goalsContainer = document.querySelector('#goalInsightsContainer');
            if (goalsContainer && goalData.goal_insights) {
                goalsContainer.innerHTML = goalData.goal_insights.map(insight => 
                    '<div class="recommendation">' + insight + '</div>'
                ).join('');
            }
        }

        function updateTrendAnalysis(trendData) {
            if (!trendData) return;
            
            // Update trend insights
            const trendsContainer = document.querySelector('#trendInsights');
            if (trendsContainer && trendData.insights) {
                trendsContainer.innerHTML = 
                    '<div class="recommendation">📈 ' + trendData.insights.overall_trend + '</div>' +
                    '<div class="recommendation">💰 Average monthly expenses: ₵' + (trendData.insights.avg_monthly_expenses?.toFixed(2) || '0') + '</div>';
            }
        }

        function updatePredictions(predictionsData) {
            if (!predictionsData) return;
            
            const predictionsContainer = document.querySelector('#predictionsContainer .insights-predictions');
            if (predictionsContainer && predictionsData.predictions) {
                let html = '<h4>🤖 AI-Powered Predictions</h4>';
                predictionsData.predictions.forEach(prediction => {
                    html += '<div class="prediction-item">💡 ' + prediction + '</div>';
                });
                html += '<div class="prediction-meta">';
                html += '<small>Confidence: ' + (predictionsData.confidence_score || 75) + '% • Generated by: ' + (predictionsData.generated_by || 'AI') + ' • ' + new Date(predictionsData.timestamp || Date.now()).toLocaleTimeString() + '</small>';
                html += '</div>';
                
                predictionsContainer.innerHTML = html;
            }
        }

        function updateRecommendations(recommendationsData) {
            if (!recommendationsData || !Array.isArray(recommendationsData)) return;
            
            const recommendationsContainer = document.querySelector('#actionableRecommendations');
            if (recommendationsContainer) {
                let html = '';
                recommendationsData.forEach(rec => {
                    html += '<div class="recommendation-card ' + rec.priority + '">';
                    html += '<div class="recommendation-header">';
                    html += '<h4>' + rec.title + '</h4>';
                    html += '<span class="priority-badge ' + rec.priority + '">' + rec.priority + '</span>';
                    html += '</div>';
                    html += '<p>' + rec.description + '</p>';
                    html += '<div class="action-items">';
                    if (rec.action_items) {
                        rec.action_items.forEach(item => {
                            html += '<div class="action-item">• ' + item + '</div>';
                        });
                    }
                    html += '</div>';
                    html += '<div class="potential-impact">' + rec.potential_impact + '</div>';
                    html += '</div>';
                });
                recommendationsContainer.innerHTML = html;
            }
        }

        function updateBenchmarks(benchmarksData) {
            if (!benchmarksData) return;
            
            const benchmarksContainer = document.querySelector('#benchmarksContainer');
            if (benchmarksContainer) {
                let html = '';
                Object.entries(benchmarksData).forEach(([key, benchmark]) => {
                    const suffix = key.includes('rate') || key.includes('spending') ? '%' : 
                                  key.includes('fund') ? ' months' : '';
                    
                    html += '<div class="benchmark-item ' + benchmark.status + '">';
                    html += '<div class="benchmark-label">' + benchmark.description + '</div>';
                    html += '<div class="benchmark-values">';
                    html += '<span class="user-value">You: ' + (benchmark.user_value?.toFixed(1) || '0') + suffix + '</span>';
                    html += '<span class="benchmark-value">Target: ' + benchmark.benchmark + suffix + '</span>';
                    html += '</div>';
                    html += '<div class="benchmark-status ' + benchmark.status + '">';
                    html += benchmark.status === 'good' ? '✅ On Track' : '⚠️ Needs Improvement';
                    html += '</div>';
                    html += '</div>';
                });
                benchmarksContainer.innerHTML = html;
            }
        }

        async function loadAIPredictions() {
            try {
                const aiData = await fetchInsightsData('ai_predictions');
                if (aiData) {
                    updatePredictions(aiData);
                }
            } catch (error) {
                console.error('Error loading AI predictions:', error);
            }
        }

        // Show loading state
        function showLoadingState() {
            const loadingElements = document.querySelectorAll('.metric-card, .insight-card');
            loadingElements.forEach(card => {
                card.style.opacity = '0.6';
                card.style.pointerEvents = 'none';
            });
        }

        // Hide loading state
        function hideLoadingState() {
            const loadingElements = document.querySelectorAll('.metric-card, .insight-card');
            loadingElements.forEach(card => {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            });
        }

        // Generate AI-powered predictive insights
        async function generatePredictiveInsights(healthData, spendingData, goalData) {
            try {
                const insightsContainer = document.querySelector('.insights-predictions');
                if (!insightsContainer) return;

                // Show loading state
                insightsContainer.innerHTML = `
                    <h4>🤖 AI-Powered Predictions</h4>
                    <div class="prediction-item">🔄 Generating insights...</div>
                `;

                // Fetch AI-powered insights from the API
                const response = await fetch('../api/insights_data.php?action=predictions');
                const data = await response.json();

                if (data.insights && data.insights.length > 0) {
                    // Display AI-generated insights
                    insightsContainer.innerHTML = `
                        <h4>🤖 AI-Powered Predictions</h4>
                        ${data.insights.map(insight => `
                            <div class="prediction-item">
                                💡 ${insight}
                            </div>
                        `).join('')}
                        <div class="prediction-meta">
                            <small>Generated by: ${data.generated_by === 'ai' ? 'AI Assistant' : 'Financial Analysis'} • ${new Date(data.timestamp).toLocaleTimeString()}</small>
                        </div>
                    `;
                } else {
                    // Fallback display
                    insightsContainer.innerHTML = `
                        <h4>🤖 AI-Powered Predictions</h4>
                        <div class="prediction-item">📈 Keep tracking your finances! More insights will appear as you build your financial history.</div>
                    `;
                }

            } catch (error) {
                console.error('Error generating predictive insights:', error);
                
                // Error fallback
                const insightsContainer = document.querySelector('.insights-predictions');
                if (insightsContainer) {
                    insightsContainer.innerHTML = `
                        <h4>🤖 AI-Powered Predictions</h4>
                        <div class="prediction-item">⚠️ Unable to generate insights right now. Please try again later.</div>
                    `;
                }
            }
        }

        // Update financial health display with animations
        function updateFinancialHealthDisplay(data) {
            // Update hero stats with animations
            const totalInsightsElement = document.querySelector('[data-metric="total-insights"]');
            if (totalInsightsElement) {
                totalInsightsElement.dataset.format = 'number';
                // Count total insights based on available data
                const totalInsights = 5 + (data.recommendations ? data.recommendations.length : 0);
                animateCounter(totalInsightsElement, 0, totalInsights, 1200);
            }

            const heroHealthScoreElement = document.querySelector('[data-metric="health-score"]');
            if (heroHealthScoreElement) {
                heroHealthScoreElement.dataset.format = 'number';
                animateCounter(heroHealthScoreElement, 0, data.health_score, 1500);
            }

            const trendsAnalyzedElement = document.querySelector('[data-metric="trends-analyzed"]');
            if (trendsAnalyzedElement) {
                trendsAnalyzedElement.dataset.format = 'number';
                // Count trends analyzed based on data categories
                const trendsCount = 6; // spending patterns, income trends, goal progress, budget performance, financial health, predictions
                animateCounter(trendsAnalyzedElement, 0, trendsCount, 1800);
            }

            // Update health score with animation
            const healthScoreElement = document.querySelector('.health-score-number');
            if (healthScoreElement) {
                healthScoreElement.dataset.format = 'number';
                animateCounter(healthScoreElement, 0, data.health_score, 1500);
                
                // Update health score class based on value
                setTimeout(() => {
                    updateHealthScoreClass(data.health_score);
                }, 1500);
            }

            // Update income with animation
            const incomeElement = document.querySelector('[data-metric="income"]');
            if (incomeElement) {
                incomeElement.dataset.format = 'currency';
                animateCounter(incomeElement, 0, data.monthly_income || 0, 2000);
            }

            // Update expenses with animation
            const expensesElement = document.querySelector('[data-metric="expenses"]');
            if (expensesElement) {
                expensesElement.dataset.format = 'currency';
                animateCounter(expensesElement, 0, data.total_expenses || 0, 2000);
            }

            // Update savings with animation
            const savingsElement = document.querySelector('[data-metric="savings"]');
            if (savingsElement) {
                savingsElement.dataset.format = 'currency';
                animateCounter(savingsElement, 0, data.total_saved || 0, 2000);
            }

            // Update expense ratio with animation
            const expenseRatioElement = document.querySelector('[data-metric="expense-ratio"]');
            if (expenseRatioElement) {
                expenseRatioElement.dataset.format = 'percentage';
                animateCounter(expenseRatioElement, 0, data.expense_ratio, 1800);
            }

            // Update savings rate with animation
            const savingsRateElement = document.querySelector('[data-metric="savings-rate"]');
            if (savingsRateElement) {
                savingsRateElement.dataset.format = 'percentage';
                animateCounter(savingsRateElement, 0, data.savings_rate, 1800);
            }

            // Update recommendations
            updateRecommendations(data.recommendations);
        }

        // Update health score class based on value
        function updateHealthScoreClass(healthScore) {
            const scoreElement = document.querySelector('.health-score-number');
            if (!scoreElement) return;
            
            // Remove existing health score classes
            scoreElement.classList.remove('excellent', 'good', 'warning', 'poor');
            
            let scoreClass = 'excellent';
            if (healthScore < 50) {
                scoreClass = 'poor';
            } else if (healthScore < 70) {
                scoreClass = 'warning';
            } else if (healthScore < 85) {
                scoreClass = 'good';
            }
            
            // Add the appropriate class for theme coloring
            scoreElement.classList.add(scoreClass);
        }

        // Update goal analytics display
        function updateGoalAnalytics(data) {
            const stats = data.statistics;
            
            // Animate goal statistics
            const totalGoalsElement = document.querySelector('[data-metric="total-goals"]');
            if (totalGoalsElement) {
                totalGoalsElement.dataset.format = 'number';
                animateCounter(totalGoalsElement, 0, stats.total, 1200);
            }

            const completedGoalsElement = document.querySelector('[data-metric="completed-goals"]');
            if (completedGoalsElement) {
                completedGoalsElement.dataset.format = 'number';
                animateCounter(completedGoalsElement, 0, stats.completed, 1400);
            }

            const onTrackGoalsElement = document.querySelector('[data-metric="on-track-goals"]');
            if (onTrackGoalsElement) {
                onTrackGoalsElement.dataset.format = 'number';
                animateCounter(onTrackGoalsElement, 0, stats.on_track, 1600);
            }

            const completionRateElement = document.querySelector('[data-metric="completion-rate"]');
            if (completionRateElement) {
                completionRateElement.dataset.format = 'percentage';
                animateCounter(completionRateElement, 0, stats.completion_rate, 1800);
            }
        }

        // Update recommendations display
        function updateRecommendations(recommendations) {
            const recommendationsContainer = document.querySelector('.recommendations-list');
            if (recommendationsContainer && recommendations.length > 0) {
                recommendationsContainer.innerHTML = recommendations.map(rec => 
                    `<li class="recommendation-item">${rec}</li>`
                ).join('');
            }
        }

        // Initialize the insights page
        document.addEventListener('DOMContentLoaded', function() {
            initializePlaceholderCharts();
            initializeInsights();
            setupThemeSystem();
            setupAutoRefresh();
        });

        // Initialize placeholder charts to show loading state
        function initializePlaceholderCharts() {
            // Initialize spending patterns chart with placeholder data
            const spendingCtx = document.getElementById('spendingPatternsChart');
            if (spendingCtx) {
                chartInstances.spendingPatterns = new Chart(spendingCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Loading...'],
                        datasets: [{
                            data: [1],
                            backgroundColor: ['#e5e7eb'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // Initialize other charts with similar placeholder data
            const goalCtx = document.getElementById('goalProgressChart');
            if (goalCtx) {
                chartInstances.goalProgress = new Chart(goalCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Loading...'],
                        datasets: [{
                            data: [0],
                            backgroundColor: ['#e5e7eb']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        }

        // Setup smart automatic refresh system
        function setupAutoRefresh() {
            let lastRefreshTime = Date.now();
            let refreshCooldown = 30000; // 30 seconds minimum between refreshes
            let isRefreshing = false;
            
            // Smart refresh function with cooldown
            function smartRefresh(reason) {
                const now = Date.now();
                if (isRefreshing || (now - lastRefreshTime) < refreshCooldown) {
                    console.log(`Refresh skipped (${reason}) - cooldown active`);
                    return;
                }
                
                isRefreshing = true;
                lastRefreshTime = now;
                
                initializeInsights().finally(() => {
                    isRefreshing = false;
                });
            }
            
            // Only refresh when returning to tab after being away for a while
            let wasHidden = false;
            let hiddenStartTime = 0;
            
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    wasHidden = true;
                    hiddenStartTime = Date.now();
                } else if (wasHidden) {
                    const awayTime = Date.now() - hiddenStartTime;
                    // Only refresh if away for more than 2 minutes
                    if (awayTime > 120000) {
                        smartRefresh('tab visible after 2+ minutes');
                    }
                    wasHidden = false;
                }
            });

            // Refresh on window focus only if away for significant time
            let lastFocusTime = Date.now();
            window.addEventListener('focus', function() {
                const now = Date.now();
                const awayTime = now - lastFocusTime;
                // Only refresh if window was unfocused for more than 5 minutes
                if (awayTime > 300000) {
                    smartRefresh('window focus after 5+ minutes');
                }
                lastFocusTime = now;
            });

            // Reduced auto-refresh to every 15 minutes (instead of 5)
            setInterval(() => {
                if (!document.hidden && !isRefreshing) {
                    smartRefresh('15-minute interval');
                }
            }, 900000); // 15 minutes
        }

        function initializeHealthScoreTheme() {
            const healthScoreElement = document.getElementById('healthScoreNumber');
            if (healthScoreElement) {
                const score = parseInt(healthScoreElement.textContent) || 85;
                healthScoreElement.classList.remove('excellent', 'good', 'warning', 'poor');
                
                let scoreClass = 'excellent';
                if (score < 50) scoreClass = 'poor';
                else if (score < 70) scoreClass = 'warning';
                else if (score < 85) scoreClass = 'good';
                
                healthScoreElement.classList.add(scoreClass);
            }
        }

        // Financial Health Functions
        async function refreshFinancialHealth(data = null) {
            try {
                if (!data) {
                    data = await fetchInsightsData('financial_health');
                }
                if (!data) return;

                // Update health score with animation
                updateHealthScore(data);
                
            } catch (error) {
                console.error('Error refreshing financial health:', error);
                showSnackbar('Failed to load financial health data', 'error');
            }
        }

        function updateHealthScore(data) {
            const scoreElement = document.getElementById('healthScoreNumber');
            const statusElement = document.getElementById('healthStatus');
            const progressElement = document.getElementById('healthProgress');
            const recommendationsElement = document.getElementById('healthRecommendations');

            if (!scoreElement || !statusElement || !progressElement || !recommendationsElement) {
                console.error('Health score elements not found');
                return;
            }

            const healthScore = data.health_score || 0;
            
            // Animate the health score
            scoreElement.dataset.format = 'number';
            animateCounter(scoreElement, 0, healthScore, 2000);
            
            // Remove existing health score classes
            scoreElement.classList.remove('excellent', 'good', 'warning', 'poor');
            
            let status = 'Excellent';
            let scoreClass = 'excellent';
            
            if (healthScore < 50) {
                status = 'Poor';
                scoreClass = 'poor';
            } else if (healthScore < 70) {
                status = 'Needs Improvement';
                scoreClass = 'warning';
            } else if (healthScore < 85) {
                status = 'Good';
                scoreClass = 'good';
            }
            
            // Add the appropriate class for theme coloring after animation
            setTimeout(() => {
                scoreElement.classList.add(scoreClass);
                statusElement.textContent = status;
            }, 2000);

            // Animate progress ring
            if (progressElement) {
                progressElement.style.setProperty('--progress', `${healthScore}%`);
                progressElement.style.animation = 'none';
                progressElement.offsetHeight; // Trigger reflow
                progressElement.style.animation = 'progressRing 2s ease-out forwards';
            }

            // Update recommendations with animation
            if (recommendationsElement && data.recommendations) {
                recommendationsElement.innerHTML = '';
                data.recommendations.forEach((recommendation, index) => {
                    setTimeout(() => {
                        const li = document.createElement('div');
                        li.className = 'recommendation';
                        li.textContent = recommendation;
                        li.style.opacity = '0';
                        li.style.transform = 'translateY(20px)';
                        recommendationsElement.appendChild(li);
                        
                        // Animate in
                        setTimeout(() => {
                            li.style.transition = 'all 0.5s ease';
                            li.style.opacity = '1';
                            li.style.transform = 'translateY(0)';
                        }, 50);
                    }, index * 200);
                });
            }
        }

        async function refreshSpendingPatterns(data = null) {
            try {
                if (!data) {
                    data = await fetchInsightsData('spending_patterns');
                }
                if (!data) return;

                const ctx = document.getElementById('spendingPatternsChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.spendingPatterns) {
                    chartInstances.spendingPatterns.destroy();
                }

                // Use real daily patterns data
                const dailyPatterns = data.daily_patterns || [];

                chartInstances.spendingPatterns = new Chart(context, {
                    type: 'line',
                    data: {
                        labels: dailyPatterns.map(item => item.day_name),
                        datasets: [{
                            label: 'Average Daily Spending',
                            data: dailyPatterns.map(item => parseFloat(item.avg_amount || 0)),
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
                            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() + '20',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim()
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim(),
                                    callback: function(value) {
                                        return '₵' + value;
                                    }
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Error refreshing spending patterns:', error);
                showChartError('spendingPatternsChart', 'Failed to load spending patterns');
            }
        }

        // Goal Analytics Functions
        async function refreshGoalAnalytics(data = null) {
            try {
                if (!data) {
                    data = await fetchInsightsData('goal_analytics');
                }
                if (!data) return;

                const ctx = document.getElementById('goalProgressChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.goalProgress) {
                    chartInstances.goalProgress.destroy();
                }

                // Use real goal data
                const goals = data.goals || [];

                chartInstances.goalProgress = new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: goals.map(goal => goal.goal_name),
                        datasets: [{
                            label: 'Progress %',
                            data: goals.map(goal => parseFloat(goal.completion_percentage || 0)),
                            backgroundColor: goals.map(goal => {
                                const percentage = parseFloat(goal.completion_percentage || 0);
                                const successColor = getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim();
                                const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim();
                                const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
                                
                                return percentage >= 75 ? successColor : 
                                       percentage >= 50 ? warningColor : primaryColor;
                            }),
                            borderRadius: 8,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim()
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            },
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim(),
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Error refreshing goal analytics:', error);
                showChartError('goalProgressChart', 'Failed to load goal analytics');
            }
        }

        // Budget Performance Functions
        async function refreshBudgetPerformance(data = null) {
            try {
                if (!data) {
                    data = await fetchInsightsData('budget_performance');
                }
                if (!data) return;

                const ctx = document.getElementById('budgetPerformanceChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.budgetPerformance) {
                    chartInstances.budgetPerformance.destroy();
                }

                // Use real budget data
                const categories = data.categories || [];

                chartInstances.budgetPerformance = new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: categories.map(item => item.category_name),
                        datasets: [
                            {
                                label: 'Budgeted',
                                data: categories.map(item => parseFloat(item.budgeted || 0)),
                                backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
                                borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-dark').trim(),
                                borderWidth: 1,
                                borderRadius: 4
                            },
                            {
                                label: 'Actual',
                                data: categories.map(item => parseFloat(item.actual || 0)),
                                backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim(),
                                borderColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-dark').trim(),
                                borderWidth: 1,
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim()
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim()
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim(),
                                    callback: function(value) {
                                        return '₵' + value;
                                    }
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Error refreshing budget performance:', error);
                showChartError('budgetPerformanceChart', 'Failed to load budget performance');
            }
        }

        // Income Trends Functions
        async function refreshIncomeTrends(data = null) {
            try {
                if (!data) {
                    data = await fetchInsightsData('income_trends');
                }
                if (!data) return;

                const ctx = document.getElementById('incomeTrendsChart');
                if (!ctx) return;
                
                const context = ctx.getContext('2d');
                
                if (chartInstances.incometrends) {
                    chartInstances.incometrends.destroy();
                }

                // Use real income history data
                const incomeHistory = data.income_history || [];

                chartInstances.incometrends = new Chart(context, {
                    type: 'line',
                    data: {
                        labels: incomeHistory.map(item => item.month),
                        datasets: [{
                            label: 'Monthly Income',
                            data: incomeHistory.map(item => parseFloat(item.amount || 0)),
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim(),
                            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim() + '20',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim(),
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim()
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim(),
                                    callback: function(value) {
                                        return '₵' + value.toLocaleString();
                                    }
                                },
                                grid: {
                                    color: getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()
                                }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Error refreshing income trends:', error);
                showChartError('incomeTrendsChart', 'Failed to load income trends');
            }
        }

        // Predictive Insights Functions
        async function refreshPredictions() {
            try {
                const container = document.getElementById('predictionsContainer');
                if (!container) return;
                
                // Sample predictions
                const predictions = [
                    "Next Month Forecast: Based on your patterns, expect to spend ₵2,450",
                    "Savings Opportunity: You could save an extra ₵300 by reducing dining out",
                    "Goal Achievement: On track to reach your vacation fund goal in 4 months",
                    "Budget Alert: You're trending 5% over budget in the Entertainment category"
                ];

                container.innerHTML = predictions.map(prediction => 
                    `<div class="recommendation">🔮 ${prediction}</div>`
                ).join('');

                showSnackbar('Predictions updated successfully', 'success');
            } catch (error) {
                console.error('Error fetching predictions:', error);
                container.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">🔮</div>
                        <p>Failed to load predictions</p>
                        <button class="retry-btn" onclick="refreshPredictions()">Try Again</button>
                    </div>
                `;
                showSnackbar('Failed to load predictions', 'error');
            }
        }

        // Chat Functions
        function toggleChat() {
            chatOpen = !chatOpen;
            const chatWindow = document.getElementById('chatWindow');
            const chatToggle = document.getElementById('chatToggle');
            
            if (chatOpen) {
                chatWindow.classList.add('open');
                chatToggle.textContent = '✖️';
            } else {
                chatWindow.classList.remove('open');
                chatToggle.textContent = '🤖';
            }
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatMessage();
            }
        }

        async function sendChatMessage() {
            const input = document.getElementById('chatInput');
            if (!input) return;
            
            const query = input.value.trim();
            if (!query) return;

            // Add user message
            addChatMessage(query, true);
            input.value = '';

            showChatLoading();

            try {
                // Get AI response from API
                const response = await generateChatResponse(query);
                hideChatLoading();
                
                // Display the AI response properly
                if (typeof response === 'object' && response.message) {
                    displayChatResponse(response);
                } else {
                    addChatMessage(response || 'Sorry, I could not process your request.', false);
                }
            } catch (error) {
                hideChatLoading();
                addChatMessage('Sorry, I encountered an error. Please try again.', false);
                console.error('Chat error:', error);
            }
        }

        async function askQuestion(question) {
            document.getElementById('chatInput').value = question;
            await sendChatMessage();
        }

        function addChatMessage(message, isUser = false) {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${isUser ? 'user' : 'assistant'}`;
            
            // Handle different message formats
            if (typeof message === 'object' && message.message) {
                // Format AI response with better line breaks and structure
                let formattedMessage = message.message;
                
                // Convert line breaks to HTML
                formattedMessage = formattedMessage.replace(/\n/g, '<br>');
                
                // Format bullet points
                formattedMessage = formattedMessage.replace(/•\s/g, '<br>• ');
                formattedMessage = formattedMessage.replace(/\*\s/g, '<br>• ');
                
                // Format numbered lists
                formattedMessage = formattedMessage.replace(/(\d+)\.\s/g, '<br>$1. ');
                
                // Add emphasis to dollar amounts
                formattedMessage = formattedMessage.replace(/\$([0-9,]+)/g, '<strong class="amount">$$$1</strong>');
                
                // Add emphasis to percentages
                formattedMessage = formattedMessage.replace(/(\d+)%/g, '<strong class="percentage">$1%</strong>');
                
                messageDiv.innerHTML = `
                    <div class="chat-message-text">${formattedMessage}</div>
                    ${message.data ? formatDataSummary(message.data) : ''}
                `;
            } else {
                // Format simple string messages
                let formattedMessage = typeof message === 'string' ? message : 'No response available';
                
                // Apply same formatting to simple messages
                formattedMessage = formattedMessage.replace(/\n/g, '<br>');
                formattedMessage = formattedMessage.replace(/•\s/g, '<br>• ');
                formattedMessage = formattedMessage.replace(/\$([0-9,]+)/g, '<strong class="amount">$$$1</strong>');
                formattedMessage = formattedMessage.replace(/(\d+)%/g, '<strong class="percentage">$1%</strong>');
                
                messageDiv.innerHTML = `<div class="chat-message-text">${formattedMessage}</div>`;
            }
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function generateChatResponse(message) {
            try {
                // Call the AI-powered chat API
                const response = await fetch(`../api/insights_data.php?action=chat_response&query=${encodeURIComponent(message)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                return data; // Return the full AI response with message and optional data
                
            } catch (error) {
                console.error('Error getting chat response:', error);
                return {
                    message: "Sorry, I'm having trouble processing your request right now. Please try again.",
                    type: 'error'
                };
            }
        }

        // Display chat response with proper formatting
        function displayChatResponse(response) {
            let formattedMessage = response.message;
            let additionalContent = '';

            // If there's structured data, format it nicely
            if (response.data && typeof response.data === 'object') {
                additionalContent = formatDataSummary(response.data);
            }

            // Display the AI response
            addChatMessage(formattedMessage + additionalContent, false);
        }

        // Format structured data for chat display
        function formatDataSummary(data) {
            let summary = '';
            
            if (data.total !== undefined) {
                summary += `\n\n📊 <strong>Summary:</strong>`;
                summary += `\n• Total: ₵${Number(data.total).toLocaleString()}`;
                
                if (data.transactions !== undefined) {
                    summary += `\n• Transactions: ${data.transactions}`;
                }
                
                if (data.avg_transaction !== undefined) {
                    summary += `\n• Average: ₵${Number(data.avg_transaction).toLocaleString()}`;
                }
            }

            if (data.health_score !== undefined) {
                summary += `\n\n💯 <strong>Health Score:</strong> ${data.health_score}/100`;
                
                if (data.savings_rate !== undefined) {
                    summary += `\n• Savings Rate: ${data.savings_rate}%`;
                }
            }

            if (data.completion_rate !== undefined) {
                summary += `\n\n🎯 <strong>Goal Progress:</strong> ${data.completion_rate}%`;
            }

            return summary;
        }

        function showChatLoading() {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;
            
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'chat-message assistant loading-message';
            loadingDiv.innerHTML = '<div class="loading-dots">Analyzing your data...</div>';
            messagesContainer.appendChild(loadingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideChatLoading() {
            const loadingMessage = document.querySelector('.loading-message');
            if (loadingMessage) {
                loadingMessage.remove();
            }
        }

        // Theme System Functions
        function setupThemeSystem() {
            const savedTheme = localStorage.getItem('personalTheme') || 'default';
            changeTheme(savedTheme);
        }

        function toggleThemeSelector() {
            const dropdown = document.getElementById('themeDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        function changeTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('personalTheme', theme);
            
            // Update active theme indicator
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.toggle('active', option.dataset.theme === theme);
            });
            
            // Refresh all elements that depend on theme variables
            setTimeout(() => {
                initializeHealthScoreTheme();
                refreshAllCharts();
            }, 100);
            
            // Close dropdown
            const dropdown = document.getElementById('themeDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function refreshAllCharts() {
            // Force browser to recompute CSS variables
            document.body.style.display = 'none';
            document.body.offsetHeight; // Trigger reflow
            document.body.style.display = '';
            
            // Refresh all charts to pick up new theme colors
            setTimeout(() => {
                refreshFinancialHealth();
                refreshSpendingPatterns(); 
                refreshGoalAnalytics();
                refreshBudgetPerformance();
                refreshIncomeTrends();
            }, 50);
        }

        // Utility function to get fresh CSS variable value
        function getThemeVariable(varName) {
            return getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
        }

        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.theme-selector')) {
                const dropdown = document.getElementById('themeDropdown');
                if (dropdown) dropdown.classList.remove('show');
            }
            if (!event.target.closest('.user-menu')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown) dropdown.classList.remove('show');
            }
        });

        // Utility Functions
        function showLoadingState(containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                const originalContent = container.innerHTML;
                container.innerHTML = `
                    <div class="loading-indicator" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px;">
                        <div class="skeleton" style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 1rem;"></div>
                        <div class="skeleton" style="width: 150px; height: 20px; margin-bottom: 0.5rem;"></div>
                        <div class="skeleton" style="width: 100px; height: 16px;"></div>
                    </div>
                `;
                
                setTimeout(() => {
                    container.innerHTML = originalContent;
                }, 1000);
            }
        }

        function showChartError(chartId, message) {
            const canvas = document.getElementById(chartId);
            if (!canvas) return;
            
            const container = canvas.parentElement;
            if (container) {
                container.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">📊</div>
                        <p>${message}</p>
                        <button class="retry-btn" onclick="location.reload()">Retry</button>
                    </div>
                `;
            }
        }

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
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
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

        // Add missing function for behavioral insights
        function updateBehavioralInsights(behavioralData) {
            if (!behavioralData) return;
            
            // Update behavioral insights section if it exists
            const behavioralSection = document.querySelector('#behavioral-insights');
            if (behavioralSection) {
                behavioralSection.innerHTML = `
                    <div class="insight-card">
                        <h3>Behavioral Insights</h3>
                        <p>Spending patterns and behavior analysis loaded successfully.</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>