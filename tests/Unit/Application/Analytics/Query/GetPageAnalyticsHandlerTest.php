<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Application\Analytics\Query;

use Yomali\Tracker\Application\Analytics\DTO\PageAnalyticsDTO;
use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsHandler;
use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsQuery;
use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;
use Yomali\Tracker\Domain\Analytics\Repository\AnalyticsRepositoryInterface;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;
use Yomali\Tracker\Domain\Analytics\ValueObject\Url;
use Yomali\Tracker\Domain\Analytics\ValueObject\VisitCount;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class GetPageAnalyticsHandlerTest extends UnitTestCase
{
    private AnalyticsRepositoryInterface $repository;
    private GetPageAnalyticsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $this->handler = new GetPageAnalyticsHandler($this->repository);
    }

    public function testHandleWithEmptyResults(): void
    {
        $filter = AnalyticsFilter::all();
        $query = new GetPageAnalyticsQuery($filter);

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($filter)
            ->willReturn([]);

        $result = $this->handler->handle($query);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testHandleWithSinglePage(): void
    {
        $filter = AnalyticsFilter::all();
        $query = new GetPageAnalyticsQuery($filter);

        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(10, 25);
        
        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($filter)
            ->willReturn([$pageAnalytics]);

        $result = $this->handler->handle($query);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PageAnalyticsDTO::class, $result[0]);
        
        $dto = $result[0];
        $this->assertEquals('https://example.com/page', $dto->url);
        $this->assertEquals('example.com', $dto->domain);
        $this->assertEquals('/page', $dto->path);
        $this->assertEquals(10, $dto->uniqueVisits);
        $this->assertEquals(25, $dto->totalVisits);
    }

    public function testHandleWithMultiplePages(): void
    {
        $filter = AnalyticsFilter::byDomain('example.com');
        $query = new GetPageAnalyticsQuery($filter);

        $page1 = new PageAnalytics(
            Url::fromString('https://example.com/page1'),
            new VisitCount(5, 15)
        );

        $page2 = new PageAnalytics(
            Url::fromString('https://example.com/page2'),
            new VisitCount(8, 20)
        );

        $page3 = new PageAnalytics(
            Url::fromString('https://example.com/blog/post'),
            new VisitCount(12, 30)
        );

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($filter)
            ->willReturn([$page1, $page2, $page3]);

        $result = $this->handler->handle($query);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        foreach ($result as $dto) {
            $this->assertInstanceOf(PageAnalyticsDTO::class, $dto);
        }

        // Verify first page
        $this->assertEquals('https://example.com/page1', $result[0]->url);
        $this->assertEquals(5, $result[0]->uniqueVisits);
        $this->assertEquals(15, $result[0]->totalVisits);

        // Verify second page
        $this->assertEquals('https://example.com/page2', $result[1]->url);
        $this->assertEquals(8, $result[1]->uniqueVisits);
        $this->assertEquals(20, $result[1]->totalVisits);

        // Verify third page
        $this->assertEquals('https://example.com/blog/post', $result[2]->url);
        $this->assertEquals('/blog/post', $result[2]->path);
        $this->assertEquals(12, $result[2]->uniqueVisits);
        $this->assertEquals(30, $result[2]->totalVisits);
    }

    public function testHandleWithDateRangeFilter(): void
    {
        $dateRange = DateRange::fromStrings('2023-02-01', '2023-02-28');
        $filter = AnalyticsFilter::byDateRange($dateRange);
        $query = new GetPageAnalyticsQuery($filter);

        $pageAnalytics = new PageAnalytics(
            Url::fromString('https://blog.example.com/february-post'),
            new VisitCount(15, 35)
        );

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($filter)
            ->willReturn([$pageAnalytics]);

        $result = $this->handler->handle($query);

        $this->assertCount(1, $result);
        $this->assertEquals('https://blog.example.com/february-post', $result[0]->url);
        $this->assertEquals('blog.example.com', $result[0]->domain);
        $this->assertEquals('/february-post', $result[0]->path);
    }

    public function testHandleWithBothFilters(): void
    {
        $dateRange = DateRange::lastDays(7);
        $filter = AnalyticsFilter::create($dateRange, 'shop.example.com');
        $query = new GetPageAnalyticsQuery($filter);

        $pageAnalytics = new PageAnalytics(
            Url::fromString('https://shop.example.com/products/widget'),
            new VisitCount(3, 8)
        );

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($filter)
            ->willReturn([$pageAnalytics]);

        $result = $this->handler->handle($query);

        $this->assertCount(1, $result);
        $this->assertEquals('shop.example.com', $result[0]->domain);
        $this->assertEquals('/products/widget', $result[0]->path);
    }

    public function testHandleWithZeroVisitPage(): void
    {
        $filter = AnalyticsFilter::all();
        $query = new GetPageAnalyticsQuery($filter);

        $pageAnalytics = new PageAnalytics(
            Url::fromString('https://example.com/empty-page'),
            VisitCount::zero()
        );

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($filter)
            ->willReturn([$pageAnalytics]);

        $result = $this->handler->handle($query);

        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]->uniqueVisits);
        $this->assertEquals(0, $result[0]->totalVisits);
    }

    public function testHandleCallsRepositoryWithCorrectFilter(): void
    {
        $dateRange = DateRange::currentMonth();
        $domain = 'test.example.com';
        $filter = AnalyticsFilter::create($dateRange, $domain);
        $query = new GetPageAnalyticsQuery($filter);

        $this->repository
            ->expects($this->once())
            ->method('getPageAnalytics')
            ->with($this->callback(function (AnalyticsFilter $passedFilter) use ($dateRange, $domain) {
                return $passedFilter->dateRange === $dateRange 
                    && $passedFilter->domain === $domain;
            }))
            ->willReturn([]);

        $this->handler->handle($query);
    }
}