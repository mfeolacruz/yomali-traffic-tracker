<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration\Presentation\Api\Controller;

use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsHandler;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository\MySQLAnalyticsRepository;
use Yomali\Tracker\Presentation\Api\Controller\AnalyticsController;
use Yomali\Tracker\Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for AnalyticsController
 * Tests the full request flow with real database
 * 
 * @group integration
 * @group database
 */
final class AnalyticsControllerIntegrationTest extends IntegrationTestCase
{
    private AnalyticsController $controller;
    private MySQLAnalyticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MySQLAnalyticsRepository();
        $this->controller = new AnalyticsController(
            new GetPageAnalyticsHandler($this->repository)
        );

        // Clean visits table before each test
        $this->truncateTable('visits');
    }

    public function testGetPageAnalyticsWithEmptyDatabase(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertEmpty($response['data']);
        $this->assertEquals(0, $response['pagination']['total']);
    }

    public function testGetPageAnalyticsWithRealData(): void
    {
        // Arrange - Insert test data directly into database
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/page1', 'example.com', '/page1', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/page2', 'example.com', '/page2', '2023-01-03 12:00:00');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertCount(2, $response['data']); // 2 unique pages
        
        // Check first page (should have more visits, ordered DESC)
        $firstPage = $response['data'][0];
        $this->assertEquals('https://example.com/page1', $firstPage['url']);
        $this->assertEquals('example.com', $firstPage['domain']);
        $this->assertEquals('/page1', $firstPage['path']);
        $this->assertEquals(2, $firstPage['unique_visits']);
        $this->assertEquals(2, $firstPage['total_visits']);

        // Check pagination
        $this->assertEquals(1, $response['pagination']['page']);
        $this->assertEquals(20, $response['pagination']['limit']);
        $this->assertEquals(2, $response['pagination']['total']);
        $this->assertEquals(1, $response['pagination']['total_pages']);
        $this->assertFalse($response['pagination']['has_next_page']);
        $this->assertFalse($response['pagination']['has_previous_page']);
    }

    public function testGetPageAnalyticsWithDomainFilter(): void
    {
        // Arrange - Insert test data for different domains
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/page2', 'example.com', '/page2', '2023-01-03 12:00:00');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['domain' => 'example.com'];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertCount(2, $response['data']); // Only example.com pages
        
        foreach ($response['data'] as $page) {
            $this->assertEquals('example.com', $page['domain']);
        }
        
        $this->assertEquals(2, $response['pagination']['total']);
    }

    public function testGetPageAnalyticsWithDateRangeFilter(): void
    {
        // Arrange - Insert test data with different dates
        $this->insertVisit('192.168.1.1', 'https://example.com/old', 'example.com', '/old', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/new', 'example.com', '/new', '2023-01-15 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/future', 'example.com', '/future', '2023-02-01 12:00:00');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [
            'start_date' => '2023-01-10',
            'end_date' => '2023-01-20'
        ];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertCount(1, $response['data']); // Only page within date range
        $this->assertEquals('https://example.com/new', $response['data'][0]['url']);
    }

    public function testGetPageAnalyticsWithPagination(): void
    {
        // Arrange - Insert multiple pages
        for ($i = 1; $i <= 5; $i++) {
            $this->insertVisit("192.168.1.$i", "https://example.com/page$i", 'example.com', "/page$i", "2023-01-0$i 10:00:00");
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => '2', 'limit' => '2'];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertCount(2, $response['data']); // 2 items per page
        
        $this->assertEquals(2, $response['pagination']['page']);
        $this->assertEquals(2, $response['pagination']['limit']);
        $this->assertEquals(5, $response['pagination']['total']);
        $this->assertEquals(3, $response['pagination']['total_pages']);
        $this->assertTrue($response['pagination']['has_next_page']);
        $this->assertTrue($response['pagination']['has_previous_page']);
    }

    public function testGetPageAnalyticsWithInvalidPageParameter(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => '0'];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(400, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Page must be at least 1"}',
            $output
        );
    }

    public function testGetPageAnalyticsWithInvalidLimitParameter(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['limit' => '200'];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(400, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Limit must be between 1 and 100"}',
            $output
        );
    }

    public function testGetPageAnalyticsOptionsRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        // Act
        ob_start();
        $this->controller->getPageAnalytics();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(204, http_response_code());
        $this->assertEmpty($output);
    }

    public function testGetPageAnalyticsWithInvalidMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Act
        ob_start();
        $this->controller->getPageAnalytics();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(405, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Method not allowed"}',
            $output
        );
    }

    public function testGetPageAnalyticsWithInvalidDateFilter(): void
    {
        // Arrange - Insert test data
        $this->insertVisit('192.168.1.1', 'https://example.com/page', 'example.com', '/page', '2023-01-01 10:00:00');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['start_date' => 'invalid-date'];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert - Should handle gracefully and return all data (invalid dates ignored)
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertCount(1, $response['data']);
    }

    public function testGetPageAnalyticsWithComplexScenario(): void
    {
        // Arrange - Insert complex test scenario
        // Same page, multiple visitors, multiple timestamps
        $this->insertVisit('192.168.1.1', 'https://example.com/popular', 'example.com', '/popular', '2023-01-01 09:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/popular', 'example.com', '/popular', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.1', 'https://example.com/popular', 'example.com', '/popular', '2023-01-01 11:00:00'); // repeat visitor
        $this->insertVisit('192.168.1.3', 'https://example.com/popular', 'example.com', '/popular', '2023-01-02 15:00:00');
        
        // Different page, fewer visits
        $this->insertVisit('192.168.1.4', 'https://example.com/less-popular', 'example.com', '/less-popular', '2023-01-01 14:00:00');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];

        // Act
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();

        // Assert
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        $this->assertCount(2, $response['data']);
        
        // First page should be most popular (ordered by total_visits DESC)
        $popularPage = $response['data'][0];
        $this->assertEquals('https://example.com/popular', $popularPage['url']);
        $this->assertEquals(3, $popularPage['unique_visits']); // 3 unique IPs
        $this->assertEquals(4, $popularPage['total_visits']);   // 4 total visits
        
        // Second page should be less popular
        $lessPopularPage = $response['data'][1];
        $this->assertEquals('https://example.com/less-popular', $lessPopularPage['url']);
        $this->assertEquals(1, $lessPopularPage['unique_visits']);
        $this->assertEquals(1, $lessPopularPage['total_visits']);
    }

    private function insertVisit(string $ip, string $url, string $domain, string $path, string $createdAt): void
    {
        $pdo = $this->getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO visits (ip_address, page_url, page_domain, page_path, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ip, $url, $domain, $path, $createdAt]);
    }

    protected function tearDown(): void
    {
        // Reset globals
        $_GET = [];
        $_SERVER = [];
        
        parent::tearDown();
    }
}