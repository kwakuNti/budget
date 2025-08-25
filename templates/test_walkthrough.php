<!DOCTYPE html>
<html>
<head>
    <title>Walkthrough Debug Test</title>
</head>
<body>
    <h2>Walkthrough Debug Test</h2>
    <div id="results"></div>
    
    <script>
        async function testSession() {
            try {
                const response = await fetch('../api/debug_session.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                document.getElementById('results').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('results').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
            }
        }
        
        async function testWalkthrough() {
            try {
                const response = await fetch('../api/walkthrough_status.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                document.getElementById('results').innerHTML += '<h3>Walkthrough API Test:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('results').innerHTML += '<p style="color: red;">Walkthrough Error: ' + error.message + '</p>';
            }
        }
        
        // Run tests on page load
        document.addEventListener('DOMContentLoaded', function() {
            testSession();
            setTimeout(testWalkthrough, 1000);
        });
    </script>
</body>
</html>
