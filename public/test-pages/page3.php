<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page 3 - Services - Yomali Tracker</title>
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
        h1 { color: #ffc107; }
        .nav { margin: 20px 0; }
        .nav a {
            display: inline-block;
            margin-right: 15px;
            padding: 10px 15px;
            background: #ffc107;
            color: #212529;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav a:hover { background: #e0a800; }
        .info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .service {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Test Page 3 - Services</h1>
        
        <div class="info">
            <strong>Current URL:</strong> <span id="currentUrl"></span><br>
            <strong>Page:</strong> Services Page<br>
            <strong>Tracker Status:</strong> <span id="trackerStatus">Loading...</span>
        </div>
        
        <p>Welcome to our Services page! This page showcases what the Yomali Traffic Tracker can offer.</p>
        
        <div class="service">
            <h3>🔍 Analytics Tracking</h3>
            <p>Comprehensive page view tracking with real-time data collection and processing.</p>
        </div>
        
        <div class="service">
            <h3>📊 Dashboard Interface</h3>
            <p>Professional analytics dashboard with filtering, sorting, and pagination capabilities.</p>
        </div>
        
        <div class="service">
            <h3>🌐 Cross-Domain Tracking</h3>
            <p>Track visits across multiple domains and subdomains with unified analytics.</p>
        </div>
        
        <div class="service">
            <h3>⚡ High Performance</h3>
            <p>Lightweight JavaScript SDK that doesn't impact your website's performance.</p>
        </div>
        
        <div class="service">
            <h3>🔒 Privacy Compliant</h3>
            <p>GDPR-friendly tracking that respects visitor privacy and data protection regulations.</p>
        </div>
        
        <div class="nav">
            <a href="page1.php">Go to Page 1 (Home)</a>
            <a href="page2.php">Go to Page 2 (About)</a>
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