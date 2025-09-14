<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Application\Analytics\Query;

use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsQuery;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class GetPageAnalyticsQueryTest extends UnitTestCase
{
    public function testConstructorWithAnalyticsFilter(): void
    {
        $filter = AnalyticsFilter::all();
        $query = new GetPageAnalyticsQuery($filter);

        $this->assertSame($filter, $query->filter);
    }

    public function testConstructorWithDateRangeFilter(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = AnalyticsFilter::byDateRange($dateRange);
        $query = new GetPageAnalyticsQuery($filter);

        $this->assertEquals($dateRange, $query->filter->dateRange);
        $this->assertNull($query->filter->domain);
    }

    public function testConstructorWithDomainFilter(): void
    {
        $filter = AnalyticsFilter::byDomain('example.com');
        $query = new GetPageAnalyticsQuery($filter);

        $this->assertNull($query->filter->dateRange);
        $this->assertEquals('example.com', $query->filter->domain);
    }

    public function testConstructorWithBothFilters(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = AnalyticsFilter::create($dateRange, 'example.com');
        $query = new GetPageAnalyticsQuery($filter);

        $this->assertEquals($dateRange, $query->filter->dateRange);
        $this->assertEquals('example.com', $query->filter->domain);
    }

    public function testConstructorWithNoFilters(): void
    {
        $filter = AnalyticsFilter::all();
        $query = new GetPageAnalyticsQuery($filter);

        $this->assertNull($query->filter->dateRange);
        $this->assertNull($query->filter->domain);
        $this->assertFalse($query->filter->hasAnyFilter());
    }

    public function testConstructorWithComplexFilter(): void
    {
        $dateRange = DateRange::lastDays(30);
        $filter = new AnalyticsFilter($dateRange, 'blog.example.com');
        $query = new GetPageAnalyticsQuery($filter);

        $this->assertEquals($dateRange, $query->filter->dateRange);
        $this->assertEquals('blog.example.com', $query->filter->domain);
        $this->assertTrue($query->filter->hasAnyFilter());
        $this->assertTrue($query->filter->hasDateFilter());
        $this->assertTrue($query->filter->hasDomainFilter());
    }
}