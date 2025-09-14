<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tracking\Infrastructure\Http\Controller;

use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommand;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommandHandler;

/**
 * HTTP Controller for tracking visits
 */
final class TrackingController
{
    public function __construct(
        private readonly TrackVisitCommandHandler $trackVisitHandler
    ) {
    }

    /**
     * Handle tracking request
     */
    public function track(): void
    {
        $this->setCorsHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST, OPTIONS');
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $rawBody = $this->getRequestBody();
        $this->processTrackingRequest($rawBody);
    }

    /**
     * Process the tracking request with the given body
     * Extracted for testing purposes
     */
    public function processTrackingRequest(string $rawBody): void
    {
        try {
            $data = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }

            if (empty($data['url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'URL is required']);
                return;
            }

            $ipAddress = $this->getClientIpAddress();

            $command = new TrackVisitCommand(
                ipAddress: $ipAddress,
                pageUrl: $data['url']
            );

            $this->trackVisitHandler->handle($command);

            http_response_code(204);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Get request body - separated for testing
     */
    protected function getRequestBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    private function getClientIpAddress(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    private function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 86400');
        header('Content-Type: application/json');
    }
}
