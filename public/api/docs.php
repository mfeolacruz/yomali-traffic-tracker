<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yomali Traffic Tracker API - Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            background-color: #1f8a70;
            border-bottom: 1px solid #bfcbd9;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: #f9f9f9;
        }
        .swagger-ui .topbar .download-url-wrapper input[type=text] {
            border: 2px solid #547f00;
        }
        .swagger-ui .info .title {
            color: #1f8a70;
        }
        .swagger-ui .topbar {
            display: none !important;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Detect current host for dynamic URL
            const currentHost = window.location.origin;
            const swaggerUrl = currentHost + '/api/swagger.yaml';
            
            const ui = SwaggerUIBundle({
                url: swaggerUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                tryItOutEnabled: true,
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                onComplete: function() {
                    // Swagger UI loaded
                },
                onFailure: function(error) {
                    // Failed to load Swagger UI
                }
            });
            
            // Custom styling after load
            setTimeout(function() {
                const topbar = document.querySelector('.topbar');
                if (topbar) {
                    topbar.style.display = 'none';
                }
            }, 100);
        };
    </script>
</body>
</html>