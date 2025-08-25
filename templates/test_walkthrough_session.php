<!DOCTYPE html>
<html>
<head>
    <title>Walkthrough Session Test</title>
</head>
<body>
    <h2>Walkthrough Session Test</h2>
    <button onclick="testSessionDebug()">Test Session Debug</button>
    <button onclick="testWalkthroughStatus()">Test Walkthrough Status</button>
    <div id="results"></div>
    
    <script>
        async function testSessionDebug() {
            try {
                const response = await fetch('../public/walkthrough/debug.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                document.getElementById('results').innerHTML = '<h3>Session Debug:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('results').innerHTML = '<p style="color: red;">Debug Error: ' + error.message + '</p>';
            }
        }
        
        async function testWalkthroughStatus() {
            try {
                const response = await fetch('../public/walkthrough/status.php', {
                    credentials: 'same-origin'
                });
                const text = await response.text();
                
                document.getElementById('results').innerHTML += '<h3>Walkthrough Status (Raw Response):</h3>';
                document.getElementById('results').innerHTML += '<p>Status: ' + response.status + '</p>';
                document.getElementById('results').innerHTML += '<pre>' + text + '</pre>';
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    document.getElementById('results').innerHTML += '<h4>Parsed JSON:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (parseError) {
                    document.getElementById('results').innerHTML += '<p style="color: red;">JSON Parse Error: ' + parseError.message + '</p>';
                }
            } catch (error) {
                document.getElementById('results').innerHTML += '<p style="color: red;">Walkthrough Error: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>
