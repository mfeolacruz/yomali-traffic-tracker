<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page 1 - Yomali Tracker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #007bff; }
        .nav { margin: 20px 0; }
        .nav a {
            display: inline-block;
            margin-right: 15px;
            padding: 10px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav a:hover { background: #0056b3; }
        .info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏠 Test Page 1 - Home</h1>
        
        <div class="info">
            <strong>Current URL:</strong> <span id="currentUrl"></span><br>
            <strong>Page:</strong> Home Page<br>
            <strong>Tracker Status:</strong> <span id="trackerStatus">Loading...</span>
        </div>
        
        <p>Welcome to the Yomali Traffic Tracker test page! This page demonstrates the JavaScript tracking SDK in action.</p>
        
        <p>The tracker automatically captures:</p>
        <ul>
            <li>Current page URL</li>
            <li>Visitor IP address (from HTTP headers)</li>
            <li>Timestamp when the page is visited</li>
        </ul>
        
        <div class="nav">
            <a href="page2.php">Go to Page 2 (About)</a>
            <a href="page3.php">Go to Page 3 (Services)</a>
            <a href="page4.php">Go to Page 4 (Contact)</a>
            <a href="../dashboard/">View Dashboard</a>
        </div>
        
        <p><strong>Instructions:</strong></p>
        <ol>
            <li>Navigate between these test pages</li>
            <li>Check the browser's Network tab to see tracking requests</li>
            <li>View the dashboard to see tracked visits</li>
        </ol>
    </div>

    <!-- Yomali Tracking SDK -->
    <script src="http://localhost:8888/tracker.js"></script>
    
    <script>
        // Update current URL display
        document.getElementById('currentUrl').textContent = window.location.href;
        
        // Check if tracker loaded
        setTimeout(function() {
            const status = window.YomaliTracker ? 'Active ✅' : 'Failed ❌';
            document.getElementById('trackerStatus').textContent = status;
        }, 1000);
    </script>
</body>
</html>