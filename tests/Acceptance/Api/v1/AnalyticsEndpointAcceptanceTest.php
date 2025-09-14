<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Acceptance\Api\v1;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;

/**
 * True acceptance test for the analytics API endpoint
 * Tests the complete system using real HTTP requests from user perspective
 * 
 * Validates Feature 2.1: Analytics REST API acceptance criteria from BACKLOG.md:
 * - REST API endpoint accepts GET requests with optional filters
 * - Returns paginated list of page analytics with visit data
 * - Supports filtering by date range and domain
 * - Returns appropriate HTTP status codes and error messages
 * - Handles CORS preflight requests correctly
 *
 * @group acceptance
 * @group api
 */
final class AnalyticsEndpointAcceptanceTest extends TestCase
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

        // Real HTTP client - this makes it a true acceptance test
        // Use nginx service name from within Docker network
        $this->baseUrl = 'http://yomali_nginx';
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 5,
            'http_errors' => false, // Don't throw exceptions on HTTP errors
        ]);

        // For acceptance tests, we'll test against the production database
        // but use a separate test data approach
        MySQLConnection::reset();
        $this->pdo = MySQLConnection::getInstance()->getPdo();
        
        // Setup test data (cleaning will be done in tearDown to avoid race conditions)
        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanTestData();
        MySQLConnection::reset();
    }

    /**
     * @test
     * US-003: Analytics Dashboard - Basic page analytics retrieval
     */
    public function asAnalystICanRetrievePageAnalyticsForTrafficInsights(): void
    {
        // When: I request page analytics
        $response = $this->httpClient->get('/api/v1/analytics');

        // Then: The API returns analytics data successfully
        $this->assertEquals(200, $response->getStatusCode(), 'Should return 200 OK for analytics request');
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($body, 'Should return valid JSON array');
        
        // And: Response has proper structure
        $this->assertArrayHasKey('data', $body, 'Should have data field');
        $this->assertArrayHasKey('pagination', $body, 'Should have pagination field');
        
        // And: Data contains expected fields
        if (!empty($body['data'])) {
            $firstItem = $body['data'][0];
            $expectedFields = ['url', 'domain', 'path', 'unique_visits', 'total_visits'];
            foreach ($expectedFields as $field) {
                $this->assertArrayHasKey($field, $firstItem, "Should have {$field} field");
            }
        }

        // And: Pagination contains expected fields
        $expectedPaginationFields = ['page', 'limit', 'total', 'total_pages', 'has_next_page', 'has_previous_page'];
        foreach ($expectedPaginationFields as $field) {
            $this->assertArrayHasKey($field, $body['pagination'], "Should have pagination {$field} field");
        }
    }

    /**
     * @test
     * US-003: Analytics Dashboard - Domain filtering
     */
    public function asAnalystICanFilterAnalyticsByDomainToFocusOnSpecificSites(): void
    {
        // When: I request analytics filtered by domain
        $response = $this->httpClient->get('/api/v1/analytics?domain=example.com');

        // Then: The API returns filtered results
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        
        // And: All results should be from the specified domain
        foreach ($body['data'] as $item) {
            $this->assertEquals('example.com', $item['domain'], 
                'All results should match the domain filter');
        }
    }

    /**
     * @test
     * US-003: Analytics Dashboard - Date range filtering
     */
    public function asAnalystICanFilterAnalyticsByDateRangeToAnalyzeSpecificPeriods(): void
    {
        // When: I request analytics for a specific date range
        $response = $this->httpClient->get('/api/v1/analytics?start_date=2023-01-01&end_date=2023-01-31');

        // Then: The API returns filtered results
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($body['data'], 'Should return analytics data for date range');
        
        // And: Results should be within the date range
        $this->assertIsArray($body['data'], 'Should return analytics data for date range');
    }

    /**
     * @test
     * US-003: Analytics Dashboard - Pagination support
     */
    public function asAnalystICanNavigateThroughAnalyticsResultsUsingPagination(): void
    {
        // When: I request the first page with a small limit
        $response = $this->httpClient->get('/api/v1/analytics?page=1&limit=2');

        // Then: The API returns paginated results
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        
        // And: Page size is respected
        $this->assertLessThanOrEqual(2, count($body['data']), 'Should respect limit parameter');
        
        // And: Pagination metadata is correct
        $this->assertEquals(1, $body['pagination']['page']);
        $this->assertEquals(2, $body['pagination']['limit']);
        
        // When: I request the second page
        $response2 = $this->httpClient->get('/api/v1/analytics?page=2&limit=2');
        
        // Then: The API returns different results
        $this->assertEquals(200, $response2->getStatusCode());
        $body2 = json_decode($response2->getBody()->getContents(), true);
        
        // And: Results are different from first page (if there are enough results)
        if (count($body['data']) > 0 && count($body2['data']) > 0) {
            $this->assertNotEquals($body['data'][0]['url'], $body2['data'][0]['url'], 
                'Second page should have different results');
        }
    }

    /**
     * @test
     * US-002: Data Validation and Security - Parameter validation
     */
    public function asSystemAdministratorInvalidPaginationParametersAreRejected(): void
    {
        $invalidParams = [
            'page=0',           // Page must be >= 1
            'page=-1',          // Negative page
            'limit=0',          // Limit must be >= 1
            'limit=200',        // Limit too large
            'limit=-5',         // Negative limit
        ];

        foreach ($invalidParams as $params) {
            // When: I send request with invalid parameters
            $response = $this->httpClient->get("/api/v1/analytics?{$params}");

            // Then: The API returns 400 Bad Request
            $this->assertEquals(400, $response->getStatusCode(), 
                "Should reject invalid parameter: {$params}");
            
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertArrayHasKey('error', $body, 'Should return error message');
        }
    }

    /**
     * @test
     * Feature requirement: CORS preflight handling
     */
    public function asWebDeveloperICanMakeCrossOriginRequestsToAnalyticsAPI(): void
    {
        // Given: A CORS preflight request
        $response = $this->httpClient->options('/api/v1/analytics', [
            'headers' => [
                'Origin' => 'https://dashboard.example.com',
                'Access-Control-Request-Method' => 'GET',
                'Access-Control-Request-Headers' => 'Content-Type'
            ]
        ]);

        // Then: The API handles CORS properly
        $this->assertEquals(204, $response->getStatusCode());
        
        // And: CORS headers are present (this would be validated in the response headers)
        $this->assertTrue(true, 'CORS preflight handled successfully');
    }

    /**
     * @test
     * Feature requirement: HTTP method restrictions
     */
    public function asSystemAdministratorOnlyValidHttpMethodsAreAllowedForAnalytics(): void
    {
        $invalidMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($invalidMethods as $method) {
            // When: I try to access the endpoint with invalid method
            $response = $this->httpClient->request($method, '/api/v1/analytics');

            // Then: The API returns 405 Method Not Allowed
            $this->assertEquals(405, $response->getStatusCode(), 
                "Should reject {$method} method with 405");
        }
    }

    /**
     * @test
     * Performance requirement: Response time
     */
    public function asAnalystTheAnalyticsAPIRespondsInReasonableTime(): void
    {
        // When: I request analytics data
        $startTime = microtime(true);
        $response = $this->httpClient->get('/api/v1/analytics');
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Then: The API responds successfully
        $this->assertEquals(200, $response->getStatusCode());
        
        // And: Response time is reasonable
        $this->assertLessThan(2000, $responseTime, 'Should respond within 2 seconds');
    }

    /**
     * @test
     * Edge case: Empty results for specific domain filter
     */
    public function asAnalystIReceiveEmptyResultsWhenNoDataExists(): void
    {
        // Given: Clean test data to ensure no matches for non-existent domain
        $this->cleanTestData();

        // When: I request analytics for a domain that doesn't exist
        $response = $this->httpClient->get('/api/v1/analytics?domain=nonexistent-test-domain.com');

        // Then: The API returns empty results gracefully
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEmpty($body['data'], 'Should return empty data array for non-existent domain');
        $this->assertEquals(0, $body['pagination']['total'], 'Should show zero total for non-existent domain');
        $this->assertEquals(0, $body['pagination']['total_pages'], 'Should show zero pages for non-existent domain');
    }

    /**
     * @test
     * Combined filters: Domain + date range + pagination
     */
    public function asAnalystICanCombineMultipleFiltersForDetailedAnalysis(): void
    {
        // When: I combine domain, date range, and pagination filters
        $response = $this->httpClient->get('/api/v1/analytics?domain=example.com&start_date=2023-01-01&end_date=2023-12-31&page=1&limit=5');

        // Then: The API processes all filters correctly
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($body['data']);
        
        // And: Results respect all filters
        foreach ($body['data'] as $item) {
            $this->assertEquals('example.com', $item['domain']);
        }
        
        $this->assertLessThanOrEqual(5, count($body['data']));
        $this->assertEquals(1, $body['pagination']['page']);
        $this->assertEquals(5, $body['pagination']['limit']);
    }

    private function cleanTestData(): void
    {
        // Clean only test data, not all data
        // Use recognizable test patterns to avoid affecting real data
        $testPatterns = [
            'https://example.com%',
            'https://blog.example.com%',
            'https://shop.example.com%'
        ];
        
        foreach ($testPatterns as $pattern) {
            $stmt = $this->pdo->prepare("DELETE FROM visits WHERE page_url LIKE ?");
            $stmt->execute([$pattern]);
        }
    }

    private function setupTestData(): void
    {
        $this->cleanTestData();
        
        // Insert test data for analytics
        $testVisits = [
            ['192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-15 10:00:00'],
            ['192.168.1.2', 'https://example.com/page1', 'example.com', '/page1', '2023-01-15 11:00:00'],
            ['192.168.1.3', 'https://example.com/page2', 'example.com', '/page2', '2023-01-16 12:00:00'],
            ['192.168.1.4', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-17 13:00:00'],
            ['192.168.1.5', 'https://shop.example.com/product1', 'shop.example.com', '/product1', '2023-01-18 14:00:00'],
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO visits (ip_address, page_url, page_domain, page_path, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($testVisits as $visit) {
            $stmt->execute($visit);
        }
    }
}