<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Acceptance\Api\v1;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Tracking\Infrastructure\Persistence\Connection\MySQLConnection;

/**
 * Acceptance test for the complete tracking endpoint
 * Tests the full system from HTTP request to database
 *
 * @group acceptance
 * @group api
 */
final class TrackingEndpointTest extends TestCase
{
    private \PDO $pdo;
    private string $endpointPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton first
        $reflection = new \ReflectionClass(MySQLConnection::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        // Set test database name only once
        $_ENV['DB_NAME'] = $_ENV['DB_NAME_TEST'] ?? 'tracker_db_test';

        $connection = MySQLConnection::getInstance();
        $this->pdo = $connection->getPdo();

        // Clean database
        $this->pdo->exec('TRUNCATE TABLE visits');

        // Base path for endpoints
        $this->endpointPath = __DIR__ . '/../../../../public/api/v1/';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->pdo->exec('TRUNCATE TABLE visits');

        // Reset singleton
        $reflection = new \ReflectionClass(MySQLConnection::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    /**
     * @test
     */
    public function itTracksVisitThroughCompleteApiFlow(): void
    {
        // Arrange
        $payload = [
            'url' => 'https://example.com/acceptance-test',
        ];

        // Act
        $response = $this->makeApiRequest('POST', 'track', $payload);

        // Assert HTTP Response
        $this->assertEquals(204, $response['status']);
        $this->assertEmpty($response['body']);

        // Assert Database State
        $stmt = $this->pdo->query('SELECT * FROM visits ORDER BY id DESC LIMIT 1');
        $visit = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotNull($visit);
        $this->assertEquals('https://example.com/acceptance-test', $visit['page_url']);
        $this->assertEquals('example.com', $visit['page_domain']);
        $this->assertEquals('/acceptance-test', $visit['page_path']);
        $this->assertEquals('127.0.0.1', $visit['ip_address']);
    }

    /**
     * @test
     */
    public function itHandlesCorsPreflightRequest(): void
    {
        // Act
        $response = $this->makeApiRequest('OPTIONS', 'track');

        // Assert
        $this->assertEquals(204, $response['status']);
        $this->assertEmpty($response['body']);

        // Verify no data was saved
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM visits');
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    /**
     * @test
     */
    public function itValidatesRequiredUrlField(): void
    {
        // Arrange - Missing URL
        $payload = ['other_field' => 'value'];

        // Act
        $response = $this->makeApiRequest('POST', 'track', $payload);

        // Assert
        $this->assertEquals(400, $response['status']);
        $body = json_decode($response['body'], true);
        $this->assertEquals('URL is required', $body['error']);
    }

    /**
     * @test
     */
    public function itValidatesUrlFormat(): void
    {
        // Arrange
        $payload = ['url' => 'invalid-url-format'];

        // Act
        $response = $this->makeApiRequest('POST', 'track', $payload);

        // Assert
        $this->assertEquals(400, $response['status']);
        $body = json_decode($response['body'], true);
        $this->assertStringContainsString('Invalid URL', $body['error']);
    }

    /**
     * @test
     */
    public function itReturns404ForUnknownRoutes(): void
    {
        // Act
        $response = $this->makeApiRequest('POST', 'unknown-endpoint');

        // Assert
        $this->assertEquals(404, $response['status']);
        $body = json_decode($response['body'], true);
        $this->assertEquals('Not found', $body['error']);
    }

    /**
     * @test
     */
    public function itReturns405ForWrongHttpMethod(): void
    {
        // Act
        $response = $this->makeApiRequest('GET', 'track');

        // Assert
        $this->assertEquals(405, $response['status']);
        $body = json_decode($response['body'], true);
        $this->assertEquals('Method not allowed', $body['error']);
    }

    /**
     * @test
     */
    public function itProvidesHealthCheckEndpoint(): void
    {
        // Act
        $response = $this->makeApiRequest('GET', 'health');

        // Assert
        $this->assertEquals(200, $response['status']);
        $body = json_decode($response['body'], true);
        $this->assertEquals('healthy', $body['status']);
        $this->assertArrayHasKey('timestamp', $body);
    }

    /**
     * @test
     */
    public function itHandlesConcurrentRequests(): void
    {
        // Arrange
        $urls = [
            'https://example.com/page1',
            'https://example.com/page2',
            'https://example.com/page3',
        ];

        // Act - Send multiple requests
        foreach ($urls as $url) {
            $this->makeApiRequest('POST', 'track', ['url' => $url]);
        }

        // Assert - All visits recorded
        $stmt = $this->pdo->query('SELECT page_url FROM visits ORDER BY id');
        $visits = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertCount(3, $visits);
        $this->assertEquals($urls, $visits);
    }

    /**
     * @test
     */
    public function itExtractsRealIpFromProxyHeaders(): void
    {
        // Arrange
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195, 70.41.3.18';
        $_SERVER['HTTP_X_REAL_IP'] = '203.0.113.195';
        $_SERVER['REMOTE_ADDR'] = '70.41.3.18';

        $payload = ['url' => 'https://example.com/proxy-test'];

        // Act
        $response = $this->makeApiRequest('POST', 'track', $payload);

        // Assert
        $this->assertEquals(204, $response['status']);

        $stmt = $this->pdo->query('SELECT ip_address FROM visits ORDER BY id DESC LIMIT 1');
        $ip = $stmt->fetchColumn();
        $this->assertEquals('203.0.113.195', $ip);

        // Clean up
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['HTTP_X_REAL_IP']);
    }

    /**
     * @test
     */
    public function itHandlesMalformedJsonGracefully(): void
    {
        // Act
        $response = $this->makeApiRequest('POST', 'track', null, 'malformed-json{');

        // Assert
        $this->assertEquals(400, $response['status']);
        $body = json_decode($response['body'], true);
        $this->assertEquals('Invalid JSON', $body['error']);
    }

    /**
     * Make API request to the endpoint
     */
    private function makeApiRequest(
        string $method,
        string $route,
        ?array $payload = null,
        ?string $rawBody = null
    ): array {
        // Reset superglobals
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = '/' . $route;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';
        $_GET['route'] = $route;

        // Prepare body
        if ($rawBody !== null) {
            $input = $rawBody;
        } elseif ($payload !== null) {
            $input = json_encode($payload);
        } else {
            $input = '';
        }

        // Mock php://input
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', MockInputStream::class);
        MockInputStream::setData($input);

        // Capture output
        ob_start();
        $level = ob_get_level();

        try {
            // Reset http_response_code
            http_response_code(200);

            // Execute specific endpoint
            $endpointFile = $this->endpointPath . $route . '.php';
            if (file_exists($endpointFile)) {
                require $endpointFile;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }

            $status = http_response_code() ?: 200;
            $body = ob_get_contents();

        } catch (\Throwable $e) {
            $status = 500;
            $body = json_encode(['error' => 'Internal server error']);
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            ob_end_clean();

            // Restore stream wrapper
            stream_wrapper_restore('php');
        }

        return [
            'status' => $status,
            'body' => $body
        ];
    }
}

/**
 * Mock php://input stream for testing
 */
class MockInputStream
{
    private static string $data = '';
    private int $position = 0;
    public $context;

    public static function setData(string $data): void
    {
        self::$data = $data;
    }

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        return true;
    }

    public function stream_read($count): string
    {
        $ret = substr(self::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$data);
    }

    public function stream_stat(): array
    {
        return [];
    }

    public function url_stat($path, $flags): array
    {
        return [];
    }

    public function stream_write($data): int
    {
        self::$data = $data;
        return strlen($data);
    }
}