<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yomali Tracker - Test Pages</title>
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
        h1 { color: #6f42c1; text-align: center; }
        .page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .page-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        .page-card:hover {
            border-color: #6f42c1;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.2);
        }
        .page-card h3 {
            margin-top: 0;
            color: #6f42c1;
        }
        .dashboard-link {
            background: #6f42c1;
            color: white;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
        }
        .dashboard-link:hover {
            background: #5a32a1;
            color: white;
        }
        .instructions {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Yomali Traffic Tracker - Test Suite</h1>
        
        <div class="instructions">
            <h3>📋 Testing Instructions</h3>
            <ol>
                <li><strong>Navigate through test pages</strong> - Click on each page below to test tracking</li>
                <li><strong>Check Network tab</strong> - Open browser DevTools and monitor tracking requests</li>
                <li><strong>View Dashboard</strong> - See real-time analytics of your test visits</li>
                <li><strong>Test different scenarios</strong> - Try refreshing pages, going back/forward</li>
            </ol>
        </div>
        
        <h2>🧪 Test Pages</h2>
        <div class="page-grid">
            <a href="page1.php" class="page-card">
                <h3>🏠 Page 1 - Home</h3>
                <p>Welcome page with tracker overview and navigation to other test pages.</p>
            </a>
            
            <a href="page2.php" class="page-card">
                <h3>📋 Page 2 - About</h3>
                <p>About page showcasing tracker features and capabilities.</p>
            </a>
            
            <a href="page3.php" class="page-card">
                <h3>🛠️ Page 3 - Services</h3>
                <p>Services page with detailed feature descriptions and offerings.</p>
            </a>
            
            <a href="page4.php" class="page-card">
                <h3>📧 Page 4 - Contact</h3>
                <p>Contact page with form and contact information.</p>
            </a>
        </div>
        
        <div style="text-align: center;">
            <a href="../dashboard/" class="dashboard-link">
                📊 View Analytics Dashboard
            </a>
        </div>
        
        <div class="instructions">
            <h3>🔍 What to Look For</h3>
            <ul>
                <li><strong>Network Requests:</strong> Each page should send a POST request to <code>/api/v1/track</code></li>
                <li><strong>Response Codes:</strong> Successful tracking returns HTTP 204 (No Content)</li>
                <li><strong>Dashboard Updates:</strong> New visits should appear in the analytics dashboard</li>
                <li><strong>URL Tracking:</strong> Each page visit tracks the full URL including query parameters</li>
            </ul>
        </div>
    </div>

    <!-- Yomali Tracking SDK -->
    <script src="http://localhost:8888/tracker.js"></script>
</body>
</html>