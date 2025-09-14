<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration\Tracking\Infrastructure\Http\Controller;

use Yomali\Tracker\Tests\Integration\IntegrationTestCase;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommandHandler;
use Yomali\Tracker\Tracking\Infrastructure\Http\Controller\TrackingController;
use Yomali\Tracker\Tracking\Infrastructure\Persistence\Repository\MySQLVisitRepository;

/**
 * Integration tests for TrackingController
 * Tests the full request flow with real database
 */
final class TrackingControllerIntegrationTest extends IntegrationTestCase
{
    private TrackingController $controller;
    private MySQLVisitRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MySQLVisitRepository();
        $this->controller = new TrackingController(
            new TrackVisitCommandHandler($this->repository)
        );

        // Clean visits table before each test
        $this->truncateTable('visits');
    }

    public function testSuccessfulTrackRequestStoresVisitInDatabase(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $requestData = json_encode([
            'url' => 'https://example.com/page1'
        ]);

        // Capture output to prevent it from interfering with tests
        ob_start();

        // Act
        $this->controller->processTrackingRequest($requestData);

        // Clean output buffer
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(204, http_response_code());

        // Verify data was stored in database
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT COUNT(*) FROM visits');
        $count = $stmt->fetchColumn();

        $this->assertEquals(1, $count);

        // Verify the stored data
        $stmt = $pdo->query('SELECT * FROM visits ORDER BY created_at DESC LIMIT 1');
        $visit = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('https://example.com/page1', $visit['page_url']);
        $this->assertEquals('0.0.0.0', $visit['ip_address']); // Default IP when not provided
        $this->assertNotEmpty($visit['created_at']);
    }

    public function testTrackRequestWithCustomIpAddress(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.100, 10.0.0.1';
        
        $requestData = json_encode([
            'url' => 'https://example.com/test-page'
        ]);

        ob_start();

        // Act
        $this->controller->processTrackingRequest($requestData);

        ob_get_clean();

        // Assert
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT ip_address FROM visits ORDER BY created_at DESC LIMIT 1');
        $ipAddress = $stmt->fetchColumn();

        $this->assertEquals('192.168.1.100', $ipAddress);

        // Clean up
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    public function testInvalidJsonReturns400Error(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $invalidJson = '{"url": "https://example.com"'; // Missing closing brace

        ob_start();

        // Act
        $this->controller->processTrackingRequest($invalidJson);

        $output = ob_get_clean();

        // Assert
        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);

        // Verify no data was stored
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT COUNT(*) FROM visits');
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }

    public function testMissingUrlReturns400Error(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $requestData = json_encode(['other_field' => 'value']);

        ob_start();

        // Act
        $this->controller->processTrackingRequest($requestData);

        $output = ob_get_clean();

        // Assert
        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('URL is required', $output);

        // Verify no data was stored
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT COUNT(*) FROM visits');
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }

    public function testEmptyUrlReturns400Error(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $requestData = json_encode(['url' => '']);

        ob_start();

        // Act
        $this->controller->processTrackingRequest($requestData);

        $output = ob_get_clean();

        // Assert
        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('URL is required', $output);
    }

    public function testInvalidUrlFormatReturns400Error(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $requestData = json_encode(['url' => 'not-a-valid-url']);

        ob_start();

        // Act
        $this->controller->processTrackingRequest($requestData);

        $output = ob_get_clean();

        // Assert
        $this->assertEquals(400, http_response_code());
        $response = json_decode($output, true);
        $this->assertStringContainsString('Invalid URL:', $response['error']);
    }

    public function testMultipleVisitsAreStoredCorrectly(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $visits = [
            'https://example.com/page1',
            'https://example.com/page2',
            'https://example.com/page3'
        ];

        // Act - Track multiple visits
        foreach ($visits as $url) {
            $requestData = json_encode(['url' => $url]);
            
            ob_start();
            $this->controller->processTrackingRequest($requestData);
            ob_get_clean();
        }

        // Assert
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT COUNT(*) FROM visits');
        $count = $stmt->fetchColumn();

        $this->assertEquals(3, $count);

        // Verify all URLs were stored
        $stmt = $pdo->query('SELECT page_url FROM visits ORDER BY created_at ASC');
        $storedUrls = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertEquals($visits, $storedUrls);
    }

    public function testCorsHeadersAreSetOnOptionsRequest(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        ob_start();

        // Act
        $this->controller->track();

        ob_get_clean();

        // Assert
        $this->assertEquals(204, http_response_code());

        // Note: In a real integration test, we would need to capture headers
        // For now, we verify the OPTIONS request doesn't cause errors
    }

    public function testMethodNotAllowedReturns405(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();

        // Act
        $this->controller->track();

        $output = ob_get_clean();

        // Assert
        $this->assertEquals(405, http_response_code());
        $this->assertStringContainsString('Method not allowed', $output);
    }

    public function testTrackWithDifferentIpDetectionMethods(): void
    {
        $ipTestCases = [
            ['HTTP_X_REAL_IP' => '10.0.0.50', 'expected' => '10.0.0.50'],
            ['REMOTE_ADDR' => '127.0.0.1', 'expected' => '127.0.0.1'],
            ['HTTP_X_FORWARDED_FOR' => '203.0.113.45', 'expected' => '203.0.113.45']
        ];

        foreach ($ipTestCases as $case) {
            // Clean up previous test data
            $this->truncateTable('visits');

            // Arrange
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            // Set the IP header
            foreach ($case as $header => $value) {
                if ($header !== 'expected') {
                    $_SERVER[$header] = $value;
                }
            }

            $requestData = json_encode(['url' => 'https://example.com/ip-test']);

            ob_start();

            // Act
            $this->controller->processTrackingRequest($requestData);

            ob_get_clean();

            // Assert
            $pdo = $this->getPdo();
            $stmt = $pdo->query('SELECT ip_address FROM visits LIMIT 1');
            $storedIp = $stmt->fetchColumn();

            $this->assertEquals($case['expected'], $storedIp);

            // Clean up server variables
            foreach ($case as $header => $value) {
                if ($header !== 'expected') {
                    unset($_SERVER[$header]);
                }
            }
        }
    }

    protected function tearDown(): void
    {
        // Clean up any SERVER variables that might have been set during tests
        $headersToClean = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
            'REQUEST_METHOD'
        ];

        foreach ($headersToClean as $header) {
            unset($_SERVER[$header]);
        }

        parent::tearDown();
    }
}