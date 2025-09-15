<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page 4 - Contact - Yomali Tracker</title>
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
        h1 { color: #dc3545; }
        .nav { margin: 20px 0; }
        .nav a {
            display: inline-block;
            margin-right: 15px;
            padding: 10px 15px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav a:hover { background: #c82333; }
        .info {
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .contact-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test Page 4 - Contact</h1>
        
        <div class="info">
            <strong>Current URL:</strong> <span id="currentUrl"></span><br>
            <strong>Page:</strong> Contact Page<br>
            <strong>Tracker Status:</strong> <span id="trackerStatus">Loading...</span>
        </div>
        
        <p>Get in touch with us! This contact page demonstrates form tracking capabilities.</p>
        
        <div class="contact-form">
            <h3>Contact Form</h3>
            <form onsubmit="handleFormSubmit(event)">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                
                <button type="submit">Send Message</button>
            </form>
        </div>
        
        <h3>Contact Information</h3>
        <p>
            <strong>Email:</strong> support@yomali-tracker.com<br>
            <strong>Phone:</strong> +1 (555) 123-4567<br>
            <strong>Address:</strong> 123 Analytics Street, Data City, DC 12345
        </p>
        
        <div class="nav">
            <a href="page1.php">Go to Page 1 (Home)</a>
            <a href="page2.php">Go to Page 2 (About)</a>
            <a href="page3.php">Go to Page 3 (Services)</a>
            <a href="../dashboard.php">View Dashboard</a>
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
        
        // Handle form submission (demo only)
        function handleFormSubmit(event) {
            event.preventDefault();
            alert('Form submitted! (This is just a demo - no data is actually sent)');
            
            // You could track form submissions here if needed
            // window.yomali && window.yomali.track({event: 'form_submit', form: 'contact'});
        }
    </script>
</body>
</html>