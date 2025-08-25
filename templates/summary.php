<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Family Summary - Nkansah Family Fund</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/favicon.php'; ?>
    <!-- Styles -->
    <link rel="stylesheet" href="../public/css/summary.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <header>
        <h1>Family Summary Dashboard</h1>
    </header>

    <section class="filters">
        <label for="month">Month:</label>
        <select id="month">
            <option value="">All</option>
            <!-- JS will populate -->
        </select>

        <label for="year">Year:</label>
        <select id="year">
            <option value="">All</option>
            <!-- JS will populate -->
        </select>

        <button onclick="loadSummary()">Apply Filter</button>
    </section>

    <section class="summary-cards">
        <div class="card">
            <h3>Total Contributions</h3>
            <p id="total-contributions">₵0.00</p>
        </div>
        <div class="card">
            <h3>Total Expenses</h3>
            <p id="total-expenses">₵0.00</p>
        </div>
        <div class="card">
            <h3>Net Savings</h3>
            <p id="net-savings">₵0.00</p>
        </div>
        <div class="card">
            <h3>Active Members</h3>
            <p id="active-members">0</p>
        </div>
    </section>

    <section class="charts">
        <div class="chart-container">
            <h3>Contributions Over Time</h3>
            <canvas id="contributionChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Contributions by Member</h3>
            <canvas id="memberChart"></canvas>
        </div>
    </section>

    <section class="member-table">
        <h3>Member Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contributions (₵)</th>
                    <th>Expenses (₵)</th>
                    <th>Net (₵)</th>
                </tr>
            </thead>
            <tbody id="member-data">
                <!-- JS will populate -->
            </tbody>
        </table>
    </section>

    <script src="../public/js/summary.js"></script>
</body>
</html>
