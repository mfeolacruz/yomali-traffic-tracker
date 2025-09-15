<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page 2 - About - Yomali Tracker</title>
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
        h1 { color: #28a745; }
        .nav { margin: 20px 0; }
        .nav a {
            display: inline-block;
            margin-right: 15px;
            padding: 10px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav a:hover { background: #1e7e34; }
        .info {
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Test Page 2 - About</h1>
        
        <div class="info">
            <strong>Current URL:</strong> <span id="currentUrl"></span><br>
            <strong>Page:</strong> About Page<br>
            <strong>Tracker Status:</strong> <span id="trackerStatus">Loading...</span>
        </div>
        
        <p>This is the About page for testing the Yomali Traffic Tracker. Each page visit is automatically tracked!</p>
        
        <h3>About the Tracker</h3>
        <p>The Yomali Traffic Tracker is a lightweight JavaScript SDK that:</p>
        <ul>
            <li>Automatically tracks page views</li>
            <li>Captures visitor IP addresses securely</li>
            <li>Provides real-time analytics dashboard</li>
            <li>Works with any website or web application</li>
        </ul>
        
        <h3>Features</h3>
        <ul>
            <li>🚀 Simple one-line integration</li>
            <li>📊 Real-time analytics</li>
            <li>🔒 Privacy-focused tracking</li>
            <li>📱 Mobile-responsive dashboard</li>
        </ul>
        
        <div class="nav">
            <a href="page1.php">Go to Page 1 (Home)</a>
            <a href="page3.php">Go to Page 3 (Services)</a>
            <a href="page4.php">Go to Page 4 (Contact)</a>
            <a href="../dashboard/">View Dashboard</a>
        </div>
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