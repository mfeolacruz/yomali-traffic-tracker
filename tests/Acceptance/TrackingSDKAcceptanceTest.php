<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Acceptance;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;

/**
 * Acceptance test for the JavaScript Tracking SDK
 * Tests the complete tracking workflow from SDK to database
 * 
 * @group acceptance
 * @group tracking-sdk
 */
final class TrackingSDKAcceptanceTest extends TestCase
{
    private Client $httpClient;
    private string $baseUrl;
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // For acceptance tests, ensure we use production database (not test database)
        // This resets any environment changes made by integration tests
        if (file_exists('/var/www/.env')) {
            $dotenv = \Dotenv\Dotenv::createMutable('/var/www');
            $dotenv->load();
            
            // Explicitly set production database for acceptance tests
            $prodDbName = $_ENV['DB_NAME'] ?? 'tracker_db';
            putenv('DB_NAME=' . $prodDbName);
            $_ENV['DB_NAME'] = $prodDbName;
            
            // Clear APP_ENV to ensure we use production settings
            putenv('APP_ENV=');
            unset($_ENV['APP_ENV']);
        }

        // Real HTTP client for acceptance testing
        $this->baseUrl = 'http://yomali_nginx';
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 5,
            'http_errors' => false,
        ]);

        // For acceptance tests, we'll test against the production database
        MySQLConnection::reset();
        $this->pdo = MySQLConnection::getInstance()->getPdo();
        
        // Clean test data
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanTestData();
        MySQLConnection::reset();
    }
    
    private function cleanTestData(): void
    {
        // Clean only test data, not all data
        $testPatterns = [
            'http://localhost:8888/test-pages/%',
            'https://example-sdk-test.com/%'
        ];
        
        foreach ($testPatterns as $pattern) {
            $stmt = $this->pdo->prepare("DELETE FROM visits WHERE page_url LIKE ?");
            $stmt->execute([$pattern]);
        }
    }

    /**
     * @test
     * US-004: Basic Page View Tracking - Complete SDK workflow
     */
    public function asWebsiteOwnerICanEmbedTheTrackerAndCollectPageViewData(): void
    {
        // Given: A website with the tracking SDK embedded
        $testUrl = 'https://example-sdk-test.com/landing-page';
        
        // When: The SDK sends tracking data (simulating JavaScript tracker)
        $trackingData = ['url' => $testUrl];
        
        $response = $this->httpClient->post('/api/v1/track', [
            'json' => $trackingData,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Forwarded-For' => '198.51.100.42', // Simulated visitor IP
                'User-Agent' => 'Mozilla/5.0 (Yomali SDK Test)'
            ]
        ]);

        // Then: The tracking request succeeds
        $this->assertEquals(204, $response->getStatusCode(), 'Tracking request should succeed');
        $this->assertEmpty($response->getBody()->getContents(), 'Should return empty body');

        // And: The visit data is stored correctly
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE page_url = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$testUrl]);
        $visit = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotNull($visit, 'Visit should be stored in database');
        $this->assertEquals($testUrl, $visit['page_url']);
        $this->assertEquals('example-sdk-test.com', $visit['page_domain']);
        $this->assertEquals('/landing-page', $visit['page_path']);
        $this->assertEquals('198.51.100.42', $visit['ip_address']);
    }

    /**
     * @test
     * US-004: SDK can track multiple page visits from same session
     */
    public function asWebsiteOwnerTheSDKCanTrackMultiplePageVisitsFromSameUser(): void
    {
        // Given: A user visiting multiple pages
        $visitedPages = [
            'https://example-sdk-test.com/home',
            'https://example-sdk-test.com/about',
            'https://example-sdk-test.com/services',
            'https://example-sdk-test.com/contact'
        ];

        $visitorIP = '203.0.113.195';

        // When: Each page visit is tracked
        foreach ($visitedPages as $pageUrl) {
            $response = $this->httpClient->post('/api/v1/track', [
                'json' => ['url' => $pageUrl],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Forwarded-For' => $visitorIP,
                    'User-Agent' => 'Mozilla/5.0 (Session Test)'
                ]
            ]);

            $this->assertEquals(204, $response->getStatusCode(), 
                "Tracking should succeed for page: {$pageUrl}");
        }

        // Then: All visits are recorded correctly
        $stmt = $this->pdo->prepare('SELECT page_url FROM visits WHERE ip_address = ? AND page_url LIKE ? ORDER BY id');
        $stmt->execute([$visitorIP, 'https://example-sdk-test.com/%']);
        $recordedUrls = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertEquals($visitedPages, $recordedUrls, 'All page visits should be recorded in order');
    }

    /**
     * @test
     * US-004: SDK handles different URL formats correctly
     */
    public function asWebsiteOwnerTheSDKCorrectlyHandlesDifferentUrlFormats(): void
    {
        // Given: Different types of URLs that might be tracked
        $urlTestCases = [
            [
                'url' => 'https://example-sdk-test.com/',
                'expectedDomain' => 'example-sdk-test.com',
                'expectedPath' => '/'
            ],
            [
                'url' => 'https://example-sdk-test.com/category/subcategory?param=value&other=123',
                'expectedDomain' => 'example-sdk-test.com',
                'expectedPath' => '/category/subcategory'
            ],
            [
                'url' => 'https://subdomain.example-sdk-test.com/path',
                'expectedDomain' => 'subdomain.example-sdk-test.com',
                'expectedPath' => '/path'
            ]
        ];

        foreach ($urlTestCases as $index => $testCase) {
            // When: The URL is tracked
            $response = $this->httpClient->post('/api/v1/track', [
                'json' => ['url' => $testCase['url']],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Forwarded-For' => '192.0.2.' . (100 + $index), // Different IP for each test
                ]
            ]);

            // Then: The tracking succeeds
            $this->assertEquals(204, $response->getStatusCode());

            // And: The URL is parsed correctly
            $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE page_url = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$testCase['url']]);
            $visit = $stmt->fetch(\PDO::FETCH_ASSOC);

            $this->assertNotNull($visit, "Visit should be recorded for URL: {$testCase['url']}");
            $this->assertEquals($testCase['expectedDomain'], $visit['page_domain'], 
                "Domain should be correctly extracted from: {$testCase['url']}");
            $this->assertEquals($testCase['expectedPath'], $visit['page_path'], 
                "Path should be correctly extracted from: {$testCase['url']}");
        }
    }

    /**
     * @test
     * Performance: SDK tracking should be fast
     */
    public function asWebsiteOwnerTheSDKTrackingDoesNotImpactPagePerformance(): void
    {
        // Given: A page visit to track
        $testUrl = 'https://example-sdk-test.com/performance-test';

        // When: The tracking request is made
        $startTime = microtime(true);
        
        $response = $this->httpClient->post('/api/v1/track', [
            'json' => ['url' => $testUrl],
            'headers' => ['Content-Type' => 'application/json']
        ]);
        
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Then: The response is fast and successful
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertLessThan(100, $responseTime, 'Tracking should respond within 100ms');

        // And: The data is still recorded correctly
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM visits WHERE page_url = ?');
        $stmt->execute([$testUrl]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(1, $count, 'Visit should be recorded despite fast response');
    }

    /**
     * @test 
     * Edge case: SDK handles malformed requests gracefully
     */
    public function asWebsiteOwnerTheSDKHandlesMalformedRequestsGracefully(): void
    {
        // Given: Various malformed tracking requests that might occur
        $malformedRequests = [
            ['url' => ''], // Empty URL
            ['url' => 'not-a-valid-url'], // Invalid URL format
            ['wrongField' => 'https://example.com'], // Wrong field name
            [] // No data
        ];

        foreach ($malformedRequests as $badData) {
            // When: A malformed request is sent
            $response = $this->httpClient->post('/api/v1/track', [
                'json' => $badData,
                'headers' => ['Content-Type' => 'application/json']
            ]);

            // Then: The request is rejected with appropriate error
            $this->assertEquals(400, $response->getStatusCode(), 
                'Malformed requests should be rejected with 400 Bad Request');
            
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertArrayHasKey('error', $body, 'Should return error message');
        }

        // And: No invalid data is stored
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM visits WHERE page_url IN ("", "not-a-valid-url")');
        $invalidCount = $stmt->fetchColumn();
        
        $this->assertEquals(0, $invalidCount, 'No invalid tracking data should be stored');
    }
}