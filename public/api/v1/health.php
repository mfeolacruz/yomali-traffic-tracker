<?php

declare(strict_types=1);

// Simple health check endpoint
header('Content-Type: application/json');

try {
    $response = [
        'status' => 'healthy',
        'timestamp' => time(),
        'service' => 'yomali-tracker-api',
        'version' => '1.0.0'
    ];

    http_response_code(200);
    echo json_encode($response, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Health check failed'], JSON_THROW_ON_ERROR);
}