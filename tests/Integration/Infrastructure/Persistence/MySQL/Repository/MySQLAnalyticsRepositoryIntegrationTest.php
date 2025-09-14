<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration\Infrastructure\Persistence\MySQL\Repository;

use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository\MySQLAnalyticsRepository;
use Yomali\Tracker\Tests\Integration\IntegrationTestCase;

/**
 * @group integration
 * @group database
 */
final class MySQLAnalyticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private MySQLAnalyticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MySQLAnalyticsRepository(MySQLConnection::getInstance());
        $this->truncateTable('visits');
    }

    public function testGetPageAnalyticsWithEmptyDatabase(): void
    {
        $filter = AnalyticsFilter::all();
        $result = $this->repository->getPageAnalytics($filter);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetPageAnalyticsWithSingleVisit(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page', 'example.com', '/page', '2023-01-15 10:30:00');

        // Act
        $filter = AnalyticsFilter::all();
        $result = $this->repository->getPageAnalytics($filter);

        // Assert
        $this->assertCount(1, $result);
        
        $pageAnalytics = $result[0];
        $this->assertEquals('https://example.com/page', $pageAnalytics->url->getValue());
        $this->assertEquals('example.com', $pageAnalytics->getDomain());
        $this->assertEquals('/page', $pageAnalytics->getPath());
        $this->assertEquals(1, $pageAnalytics->getUniqueVisits());
        $this->assertEquals(1, $pageAnalytics->getTotalVisits());
        $this->assertEquals('2023-01-15 10:30:00', $pageAnalytics->firstVisit->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-15 10:30:00', $pageAnalytics->lastVisit->format('Y-m-d H:i:s'));
    }

    public function testGetPageAnalyticsWithMultipleVisitsSamePage(): void
    {
        // Arrange - same page, different IPs and times
        $this->insertVisit('192.168.1.1', 'https://example.com/blog', 'example.com', '/blog', '2023-01-10 09:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/blog', 'example.com', '/blog', '2023-01-15 14:30:00');
        $this->insertVisit('192.168.1.1', 'https://example.com/blog', 'example.com', '/blog', '2023-01-20 18:45:00'); // repeat visitor

        // Act
        $filter = AnalyticsFilter::all();
        $result = $this->repository->getPageAnalytics($filter);

        // Assert
        $this->assertCount(1, $result);
        
        $pageAnalytics = $result[0];
        $this->assertEquals('https://example.com/blog', $pageAnalytics->url->getValue());
        $this->assertEquals(2, $pageAnalytics->getUniqueVisits()); // 2 unique IPs
        $this->assertEquals(3, $pageAnalytics->getTotalVisits());   // 3 total visits
        $this->assertEquals('2023-01-10 09:00:00', $pageAnalytics->firstVisit->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-20 18:45:00', $pageAnalytics->lastVisit->format('Y-m-d H:i:s'));
    }

    public function testGetPageAnalyticsWithMultipleDifferentPages(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/home', 'example.com', '/', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/about', 'example.com', '/about', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-03 12:00:00');
        
        // Add more visits to first page to make it most visited
        $this->insertVisit('192.168.1.4', 'https://example.com/home', 'example.com', '/', '2023-01-04 13:00:00');
        $this->insertVisit('192.168.1.5', 'https://example.com/home', 'example.com', '/', '2023-01-05 14:00:00');

        // Act
        $filter = AnalyticsFilter::all();
        $result = $this->repository->getPageAnalytics($filter);

        // Assert
        $this->assertCount(3, $result);
        
        // Should be ordered by total_visits DESC, unique_visits DESC
        $this->assertEquals('https://example.com/home', $result[0]->url->getValue());
        $this->assertEquals(3, $result[0]->getTotalVisits());
        $this->assertEquals(3, $result[0]->getUniqueVisits());
        
        // The other two pages have 1 visit each, so order between them is not guaranteed
        // Just check they both have 1 visit and are present
        $urls = array_map(fn($page) => $page->url->getValue(), array_slice($result, 1));
        $this->assertContains('https://example.com/about', $urls);
        $this->assertContains('https://blog.example.com/post1', $urls);
        
        $this->assertEquals(1, $result[1]->getTotalVisits());
        $this->assertEquals(1, $result[2]->getTotalVisits());
    }

    public function testGetPageAnalyticsWithDomainFilter(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/page2', 'example.com', '/page2', '2023-01-03 12:00:00');

        // Act
        $filter = AnalyticsFilter::byDomain('example.com');
        $result = $this->repository->getPageAnalytics($filter);

        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $pageAnalytics) {
            $this->assertEquals('example.com', $pageAnalytics->getDomain());
        }
    }

    public function testGetPageAnalyticsWithDateRangeFilter(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/old', 'example.com', '/old', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/new', 'example.com', '/new', '2023-01-15 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/newer', 'example.com', '/newer', '2023-01-31 12:00:00');

        // Act - filter for January 10-20
        $dateRange = DateRange::fromStrings('2023-01-10', '2023-01-20');
        $filter = AnalyticsFilter::byDateRange($dateRange);
        $result = $this->repository->getPageAnalytics($filter);

        // Assert - should only include the middle visit
        $this->assertCount(1, $result);
        $this->assertEquals('https://example.com/new', $result[0]->url->getValue());
    }

    public function testGetPageAnalyticsWithBothFilters(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-15 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-15 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/page2', 'example.com', '/page2', '2023-02-01 12:00:00');

        // Act
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = AnalyticsFilter::create($dateRange, 'example.com');
        $result = $this->repository->getPageAnalytics($filter);

        // Assert - should only include example.com pages in January
        $this->assertCount(1, $result);
        $this->assertEquals('https://example.com/page1', $result[0]->url->getValue());
        $this->assertEquals('example.com', $result[0]->getDomain());
    }

    public function testCountPagesWithNoFilter(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/page2', 'example.com', '/page2', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/page1', 'example.com', '/page1', '2023-01-03 12:00:00'); // duplicate page

        // Act
        $filter = AnalyticsFilter::all();
        $count = $this->repository->countPages($filter);

        // Assert - should count unique pages only
        $this->assertEquals(2, $count);
    }

    public function testCountPagesWithDomainFilter(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-02 11:00:00');

        // Act
        $filter = AnalyticsFilter::byDomain('example.com');
        $count = $this->repository->countPages($filter);

        // Assert
        $this->assertEquals(1, $count);
    }

    public function testGetPageAnalyticsByUrlFound(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/test', 'example.com', '/test', '2023-01-10 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/test', 'example.com', '/test', '2023-01-20 15:00:00');

        // Act
        $result = $this->repository->getPageAnalyticsByUrl('https://example.com/test');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('https://example.com/test', $result->url->getValue());
        $this->assertEquals(2, $result->getUniqueVisits());
        $this->assertEquals(2, $result->getTotalVisits());
    }

    public function testGetPageAnalyticsByUrlNotFound(): void
    {
        // Act
        $result = $this->repository->getPageAnalyticsByUrl('https://example.com/nonexistent');

        // Assert
        $this->assertNull($result);
    }

    public function testGetPageAnalyticsByUrlWithDateRange(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/test', 'example.com', '/test', '2023-01-05 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/test', 'example.com', '/test', '2023-01-15 15:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/test', 'example.com', '/test', '2023-01-25 20:00:00');

        // Act - filter for January 10-20
        $dateRange = DateRange::fromStrings('2023-01-10', '2023-01-20');
        $result = $this->repository->getPageAnalyticsByUrl('https://example.com/test', $dateRange);

        // Assert - should only include the middle visit
        $this->assertNotNull($result);
        $this->assertEquals(1, $result->getUniqueVisits());
        $this->assertEquals(1, $result->getTotalVisits());
    }

    public function testGetTopDomains(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/page2', 'example.com', '/page2', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/page3', 'example.com', '/page3', '2023-01-03 12:00:00'); // 3 visits for example.com
        
        $this->insertVisit('192.168.1.4', 'https://blog.example.com/post1', 'blog.example.com', '/post1', '2023-01-04 13:00:00');
        $this->insertVisit('192.168.1.5', 'https://blog.example.com/post2', 'blog.example.com', '/post2', '2023-01-05 14:00:00'); // 2 visits for blog.example.com
        
        $this->insertVisit('192.168.1.6', 'https://shop.example.com/product1', 'shop.example.com', '/product1', '2023-01-06 15:00:00'); // 1 visit for shop.example.com

        // Act
        $result = $this->repository->getTopDomains();

        // Assert - should be ordered by visit count DESC
        $this->assertEquals(['example.com', 'blog.example.com', 'shop.example.com'], $result);
    }

    public function testGetTopDomainsWithLimit(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://domain1.com/page', 'domain1.com', '/page', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://domain2.com/page', 'domain2.com', '/page', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://domain3.com/page', 'domain3.com', '/page', '2023-01-03 12:00:00');

        // Act
        $result = $this->repository->getTopDomains(limit: 2);

        // Assert
        $this->assertCount(2, $result);
    }

    public function testGetTopDomainsWithDateRange(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://old.com/page', 'old.com', '/page', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://new.com/page', 'new.com', '/page', '2023-01-15 11:00:00');

        // Act
        $dateRange = DateRange::fromStrings('2023-01-10', '2023-01-31');
        $result = $this->repository->getTopDomains($dateRange);

        // Assert
        $this->assertEquals(['new.com'], $result);
    }

    public function testGetTotalStatistics(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/page2', 'example.com', '/page2', '2023-01-02 11:00:00');
        $this->insertVisit('192.168.1.1', 'https://example.com/page1', 'example.com', '/page1', '2023-01-03 12:00:00'); // repeat visitor

        // Act
        $result = $this->repository->getTotalStatistics();

        // Assert
        $this->assertEquals(2, $result['unique_visits']); // 2 unique IPs
        $this->assertEquals(3, $result['total_visits']);  // 3 total visits
        $this->assertEquals(2, $result['pages']);         // 2 unique pages
    }

    public function testGetTotalStatisticsWithDateRange(): void
    {
        // Arrange
        $this->insertVisit('192.168.1.1', 'https://example.com/old', 'example.com', '/old', '2023-01-01 10:00:00');
        $this->insertVisit('192.168.1.2', 'https://example.com/new', 'example.com', '/new', '2023-01-15 11:00:00');
        $this->insertVisit('192.168.1.3', 'https://example.com/newer', 'example.com', '/newer', '2023-01-31 12:00:00');

        // Act
        $dateRange = DateRange::fromStrings('2023-01-10', '2023-01-20');
        $result = $this->repository->getTotalStatistics($dateRange);

        // Assert - should only include the middle visit
        $this->assertEquals(1, $result['unique_visits']);
        $this->assertEquals(1, $result['total_visits']);
        $this->assertEquals(1, $result['pages']);
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
}