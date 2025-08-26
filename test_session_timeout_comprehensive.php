<?php
/**
 * Comprehensive Session Timeout Test
 * Tests session timeout functionality across all protected pages
 */

// Start session to get current session info
session_start();

// Include the session timeout middleware
require_once 'includes/session_timeout_middleware.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Timeout Test - Comprehensive</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .test-results {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .pages-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .page-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .page-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .live-status {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .session-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Session Timeout Test Suite</h1>
            <p>Comprehensive testing of session timeout functionality across all protected pages</p>
        </div>

        <!-- Current Session Status -->
        <div class="test-section">
            <h2>📊 Current Session Status</h2>
            <div id="sessionStatus" class="session-info">
                <strong>Loading session information...</strong>
            </div>
            <button class="btn" onclick="checkSessionStatus()">🔄 Refresh Session Status</button>
        </div>

        <!-- Protected Pages Test -->
        <div class="test-section">
            <h2>🛡️ Protected Pages</h2>
            <p>These pages should have session timeout protection enabled:</p>
            
            <div class="pages-list">
                <div class="page-card">
                    <h4>Personal Dashboard</h4>
                    <button class="btn" onclick="testPageAccess('templates/personal-dashboard.php')">Test Access</button>
                </div>
                <div class="page-card">
                    <h4>Budget Planning</h4>
                    <button class="btn" onclick="testPageAccess('templates/budget.php')">Test Access</button>
                </div>
                <div class="page-card">
                    <h4>Savings</h4>
                    <button class="btn" onclick="testPageAccess('templates/savings.php')">Test Access</button>
                </div>
                <div class="page-card">
                    <h4>Personal Expenses</h4>
                    <button class="btn" onclick="testPageAccess('templates/personal-expense.php')">Test Access</button>
                </div>
                <div class="page-card">
                    <h4>Analytics/Reports</h4>
                    <button class="btn" onclick="testPageAccess('templates/analytics.php')">Test Access</button>
                </div>
                <div class="page-card">
                    <h4>Feedback</h4>
                    <button class="btn" onclick="testPageAccess('templates/feedback.php')">Test Access</button>
                </div>
            </div>
        </div>

        <!-- Session Timeout Simulation -->
        <div class="test-section">
            <h2>⏰ Session Timeout Simulation</h2>
            <p>Test what happens when a session expires:</p>
            
            <div class="live-status">
                <strong>⚠️ Warning:</strong> These tests will simulate session expiration. You may need to log back in after testing.
            </div>
            
            <button class="btn btn-danger" onclick="simulateTimeout()">💀 Simulate Session Timeout</button>
            <button class="btn btn-success" onclick="refreshSession()">🔄 Refresh Session</button>
            
            <div id="timeoutResults" class="test-results" style="display: none;"></div>
        </div>

        <!-- Live Session Monitoring -->
        <div class="test-section">
            <h2>📡 Live Session Monitoring</h2>
            <p>Real-time monitoring of session status:</p>
            
            <button class="btn" onclick="toggleMonitoring()" id="monitorBtn">▶️ Start Monitoring</button>
            <button class="btn" onclick="clearMonitorLog()">🗑️ Clear Log</button>
            
            <div id="monitorLog" class="test-results" style="max-height: 300px; overflow-y: auto;"></div>
        </div>

        <!-- Manual Test Instructions -->
        <div class="test-section">
            <h2>📝 Manual Testing Instructions</h2>
            <ol>
                <li><strong>Check Current Session:</strong> Click "Refresh Session Status" to see current session details</li>
                <li><strong>Test Page Access:</strong> Click "Test Access" buttons to verify each page loads properly</li>
                <li><strong>Simulate Timeout:</strong> Click "Simulate Session Timeout" then try accessing any protected page</li>
                <li><strong>Monitor Live:</strong> Start monitoring to see real-time session updates</li>
                <li><strong>Wait for Natural Timeout:</strong> Leave the page idle for 30+ minutes and try accessing a protected page</li>
            </ol>
        </div>
    </div>

    <script>
        let monitoring = false;
        let monitorInterval;

        // Check current session status
        async function checkSessionStatus() {
            try {
                const response = await fetch('test_session_timeout.php?action=check', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                let statusHtml = `
                    <strong>Session Valid:</strong> ${data.valid ? '✅ Yes' : '❌ No'}<br>
                    <strong>User ID:</strong> ${data.user_id || 'Not set'}<br>
                    <strong>Session Timeout:</strong> ${data.session_timeout_minutes} minutes<br>
                    <strong>Time Remaining:</strong> ${Math.floor(data.time_remaining / 60)} minutes ${data.time_remaining % 60} seconds<br>
                    <strong>Last Activity:</strong> ${data.last_activity}<br>
                    <strong>Current Time:</strong> ${data.current_time}
                `;
                
                if (!data.valid && data.reason) {
                    statusHtml += `<br><strong>Reason:</strong> <span style="color: red;">${data.reason}</span>`;
                }
                
                document.getElementById('sessionStatus').innerHTML = statusHtml;
                
            } catch (error) {
                document.getElementById('sessionStatus').innerHTML = `<span style="color: red;">Error checking session: ${error.message}</span>`;
            }
        }

        // Test access to a specific page
        async function testPageAccess(pagePath) {
            try {
                const response = await fetch(pagePath, {
                    method: 'HEAD',
                    credentials: 'same-origin'
                });
                
                const statusDiv = event.target.parentElement;
                let resultDiv = statusDiv.querySelector('.test-result');
                
                if (!resultDiv) {
                    resultDiv = document.createElement('div');
                    resultDiv.className = 'test-result';
                    statusDiv.appendChild(resultDiv);
                }
                
                if (response.ok) {
                    resultDiv.innerHTML = '<div class="status success">✅ Page accessible</div>';
                } else if (response.status === 302 || response.status === 301) {
                    resultDiv.innerHTML = '<div class="status error">🔒 Redirected (likely session expired)</div>';
                } else {
                    resultDiv.innerHTML = `<div class="status error">❌ Error: ${response.status}</div>`;
                }
                
            } catch (error) {
                console.error('Error testing page access:', error);
            }
        }

        // Simulate session timeout
        async function simulateTimeout() {
            try {
                const response = await fetch('test_session_timeout.php?action=simulate', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                document.getElementById('timeoutResults').style.display = 'block';
                document.getElementById('timeoutResults').textContent = JSON.stringify(data, null, 2);
                
                // Update session status
                setTimeout(checkSessionStatus, 1000);
                
            } catch (error) {
                document.getElementById('timeoutResults').style.display = 'block';
                document.getElementById('timeoutResults').textContent = `Error: ${error.message}`;
            }
        }

        // Refresh session
        async function refreshSession() {
            try {
                const response = await fetch('api/ping.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Session refreshed successfully!');
                    checkSessionStatus();
                } else {
                    alert('❌ Failed to refresh session: ' + (data.error || 'Unknown error'));
                }
                
            } catch (error) {
                alert('❌ Error refreshing session: ' + error.message);
            }
        }

        // Toggle monitoring
        function toggleMonitoring() {
            const btn = document.getElementById('monitorBtn');
            
            if (monitoring) {
                monitoring = false;
                clearInterval(monitorInterval);
                btn.textContent = '▶️ Start Monitoring';
                addToMonitorLog('🛑 Monitoring stopped');
            } else {
                monitoring = true;
                btn.textContent = '⏸️ Stop Monitoring';
                addToMonitorLog('🚀 Monitoring started');
                
                monitorInterval = setInterval(async () => {
                    try {
                        const response = await fetch('test_session_timeout.php?action=check', {
                            method: 'GET',
                            credentials: 'same-origin'
                        });
                        
                        const data = await response.json();
                        const timestamp = new Date().toLocaleTimeString();
                        
                        const status = data.valid ? '✅' : '❌';
                        const timeRemaining = Math.floor(data.time_remaining / 60);
                        
                        addToMonitorLog(`[${timestamp}] ${status} Session valid: ${data.valid} | Time remaining: ${timeRemaining}m`);
                        
                    } catch (error) {
                        addToMonitorLog(`[${new Date().toLocaleTimeString()}] ❌ Error: ${error.message}`);
                    }
                }, 5000); // Check every 5 seconds
            }
        }

        // Add message to monitor log
        function addToMonitorLog(message) {
            const log = document.getElementById('monitorLog');
            log.textContent += message + '\n';
            log.scrollTop = log.scrollHeight;
        }

        // Clear monitor log
        function clearMonitorLog() {
            document.getElementById('monitorLog').textContent = '';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            checkSessionStatus();
        });
    </script>
</body>
</html>
