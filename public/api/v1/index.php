<?php

declare(strict_types=1);

// Get the endpoint from the URL path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract endpoint name (last part of /api/v1/endpoint)
$endpoint = end($pathParts);

// Validate endpoint name (security: only allow alphanumeric, hyphens, underscores)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $endpoint)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid endpoint name']);
    exit;
}

// Build the file path for the endpoint
$file = __DIR__ . '/' . $endpoint . '.php';

// Check if the endpoint file exists
if (file_exists($file)) {
    // Include and execute the endpoint file
    require $file;
    exit;
}

// Endpoint not found
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'Endpoint not found',
    'endpoint' => $endpoint,
    'available_endpoints' => array_map(
        fn($file) => pathinfo($file, PATHINFO_FILENAME),
        glob(__DIR__ . '/*.php', GLOB_NOSORT)
    )
]);