<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Presentation\Api\Controller;

use Yomali\Tracker\Application\Analytics\DTO\PageAnalyticsDTO;
use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsHandler;
use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsQuery;
use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;
use Yomali\Tracker\Domain\Analytics\Repository\AnalyticsRepositoryInterface;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\Url;
use Yomali\Tracker\Domain\Analytics\ValueObject\VisitCount;
use Yomali\Tracker\Presentation\Api\Controller\AnalyticsController;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class AnalyticsControllerTest extends UnitTestCase
{
    private AnalyticsRepositoryInterface $repository;
    private GetPageAnalyticsHandler $handler;
    private AnalyticsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $this->handler = new GetPageAnalyticsHandler($this->repository);
        $this->controller = new AnalyticsController($this->handler);
    }

    public function testGetPageAnalyticsWithOptionsRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        
        ob_start();
        $this->controller->getPageAnalytics();
        $output = ob_get_clean();
        
        $this->assertEquals(204, http_response_code());
        $this->assertEmpty($output);
    }

    public function testGetPageAnalyticsWithInvalidMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        $this->controller->getPageAnalytics();
        $output = ob_get_clean();
        
        $this->assertEquals(405, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Method not allowed"}',
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithEmptyResults(): void
    {
        $this->resetGlobals();
        
        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn([]);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(200, http_response_code());
        
        $expectedResponse = [
            'data' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 0,
                'total_pages' => 0,
                'has_next_page' => false,
                'has_previous_page' => false,
            ],
        ];
        
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse),
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithInvalidPageParameter(): void
    {
        $_GET = ['page' => '0'];
        
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(400, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Page must be at least 1"}',
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithInvalidLimitParameter(): void
    {
        $_GET = ['limit' => '200'];
        
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(400, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Limit must be between 1 and 100"}',
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithInvalidLimitTooSmall(): void
    {
        $_GET = ['limit' => '0'];
        
        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(400, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Limit must be between 1 and 100"}',
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithDefaultParameters(): void
    {
        $this->resetGlobals();
        
        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($this->callback(function (AnalyticsFilter $filter) {
                return !$filter->hasAnyFilter();
            }))
            ->willReturn([]);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertEquals(1, $response['pagination']['page']); // Default page
        $this->assertEquals(20, $response['pagination']['limit']); // Default limit
    }

    public function testProcessGetPageAnalyticsRequestWithFilters(): void
    {
        $_GET = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-31',
            'domain' => 'example.com',
            'page' => '2',
            'limit' => '10',
        ];
        
        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($this->callback(function (AnalyticsFilter $filter) {
                return $filter->hasDateFilter() 
                    && $filter->hasDomainFilter()
                    && $filter->domain === 'example.com';
            }))
            ->willReturn([]);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertEquals(2, $response['pagination']['page']);
        $this->assertEquals(10, $response['pagination']['limit']);
    }

    public function testGetPageAnalyticsWithValidGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        
        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn([]);

        ob_start();
        $this->controller->getPageAnalytics();
        $output = ob_get_clean();
        
        $this->assertEquals(200, http_response_code());
        $response = json_decode($output, true);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
    }

    public function testProcessGetPageAnalyticsRequestWithRealDataAndPagination(): void
    {
        $_GET = ['page' => '1', 'limit' => '2'];
        
        $aggregates = [
            PageAnalytics::create(
                Url::fromString('https://example.com/page1'),
                5, 15,
                new \DateTimeImmutable('2023-01-01 09:00:00'),
                new \DateTimeImmutable('2023-01-15 17:00:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page2'),
                8, 20,
                new \DateTimeImmutable('2023-01-05 11:30:00'),
                new \DateTimeImmutable('2023-01-25 19:45:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page3'),
                12, 30,
                new \DateTimeImmutable('2023-01-10 08:15:00'),
                new \DateTimeImmutable('2023-01-31 22:30:00')
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn($aggregates);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(200, http_response_code());
        
        $response = json_decode($output, true);
        
        // Check that toArray() is called on DTOs
        $this->assertCount(2, $response['data']); // limit 2
        $this->assertEquals('https://example.com/page1', $response['data'][0]['url']);
        $this->assertEquals('example.com', $response['data'][0]['domain']);
        $this->assertEquals('/page1', $response['data'][0]['path']);
        
        // Check pagination metadata
        $this->assertEquals(1, $response['pagination']['page']);
        $this->assertEquals(2, $response['pagination']['limit']);
        $this->assertEquals(3, $response['pagination']['total']);
        $this->assertEquals(2, $response['pagination']['total_pages']);
        $this->assertTrue($response['pagination']['has_next_page']);
        $this->assertFalse($response['pagination']['has_previous_page']);
    }

    public function testProcessGetPageAnalyticsRequestWithSecondPage(): void
    {
        $_GET = ['page' => '2', 'limit' => '2'];
        
        $aggregates = [
            PageAnalytics::create(
                Url::fromString('https://example.com/page1'),
                5, 15,
                new \DateTimeImmutable('2023-01-01 09:00:00'),
                new \DateTimeImmutable('2023-01-15 17:00:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page2'),
                8, 20,
                new \DateTimeImmutable('2023-01-05 11:30:00'),
                new \DateTimeImmutable('2023-01-25 19:45:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page3'),
                12, 30,
                new \DateTimeImmutable('2023-01-10 08:15:00'),
                new \DateTimeImmutable('2023-01-31 22:30:00')
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn($aggregates);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        // Should show only page3 (index 2)
        $this->assertCount(1, $response['data']);
        $this->assertEquals('https://example.com/page3', $response['data'][0]['url']);
        
        // Check pagination flags
        $this->assertEquals(2, $response['pagination']['page']);
        $this->assertFalse($response['pagination']['has_next_page']);
        $this->assertTrue($response['pagination']['has_previous_page']);
    }

    public function testProcessGetPageAnalyticsRequestWithPageBeyondResults(): void
    {
        $_GET = ['page' => '10', 'limit' => '5'];
        
        $aggregates = [
            PageAnalytics::create(
                Url::fromString('https://example.com/page1'),
                5, 15,
                new \DateTimeImmutable('2023-01-01 09:00:00'),
                new \DateTimeImmutable('2023-01-15 17:00:00')
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn($aggregates);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        // Should return empty data for page beyond available results
        $this->assertEmpty($response['data']);
        $this->assertEquals(10, $response['pagination']['page']);
        $this->assertEquals(1, $response['pagination']['total']);
        $this->assertEquals(1, $response['pagination']['total_pages']);
        $this->assertFalse($response['pagination']['has_next_page']);
        $this->assertTrue($response['pagination']['has_previous_page']);
    }

    public function testProcessGetPageAnalyticsRequestWithRepositoryException(): void
    {
        $this->resetGlobals();
        
        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willThrowException(new \InvalidArgumentException('Repository validation error'));

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(400, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Repository validation error"}',
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithGenericException(): void
    {
        $this->resetGlobals();
        
        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willThrowException(new \RuntimeException('Database connection error'));

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $this->assertEquals(500, http_response_code());
        $this->assertJsonStringEqualsJsonString(
            '{"error": "Internal server error"}',
            $output
        );
    }

    public function testProcessGetPageAnalyticsRequestWithSinglePageNoNextPrevious(): void
    {
        $_GET = ['page' => '1', 'limit' => '10'];
        
        $aggregates = [
            PageAnalytics::create(
                Url::fromString('https://example.com/only-page'),
                3, 7,
                new \DateTimeImmutable('2023-01-01 09:00:00'),
                new \DateTimeImmutable('2023-01-15 17:00:00')
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn($aggregates);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertEquals(1, $response['pagination']['total_pages']);
        $this->assertFalse($response['pagination']['has_next_page']);
        $this->assertFalse($response['pagination']['has_previous_page']);
    }

    public function testProcessGetPageAnalyticsRequestWithExactPageLimit(): void
    {
        $_GET = ['page' => '2', 'limit' => '2'];
        
        // Exactly 4 items, 2 per page = exactly 2 pages
        $aggregates = [
            PageAnalytics::create(
                Url::fromString('https://example.com/page1'),
                1, 1,
                new \DateTimeImmutable('2023-01-01 09:00:00'),
                new \DateTimeImmutable('2023-01-01 09:00:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page2'),
                1, 1,
                new \DateTimeImmutable('2023-01-01 10:00:00'),
                new \DateTimeImmutable('2023-01-01 10:00:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page3'),
                1, 1,
                new \DateTimeImmutable('2023-01-01 11:00:00'),
                new \DateTimeImmutable('2023-01-01 11:00:00')
            ),
            PageAnalytics::create(
                Url::fromString('https://example.com/page4'),
                1, 1,
                new \DateTimeImmutable('2023-01-01 12:00:00'),
                new \DateTimeImmutable('2023-01-01 12:00:00')
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->willReturn($aggregates);

        ob_start();
        $this->controller->processGetPageAnalyticsRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertEquals(2, $response['pagination']['total_pages']);
        $this->assertEquals(2, $response['pagination']['page']);
        $this->assertFalse($response['pagination']['has_next_page']); // Last page
        $this->assertTrue($response['pagination']['has_previous_page']); // Has previous
    }

    private function resetGlobals(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $this->resetGlobals();
        parent::tearDown();
    }
}