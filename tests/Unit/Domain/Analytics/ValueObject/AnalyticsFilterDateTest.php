<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Analytics\ValueObject;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;

final class AnalyticsFilterDateTest extends TestCase
{
    public function testFromHttpParamsWithBothDatesCreatesDateRange(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('2023-01-01', '2023-01-31', null);

        $this->assertTrue($filter->hasDateFilter());
        $this->assertEquals('2023-01-01 00:00:00', $filter->dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-31 00:00:00', $filter->dateRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testFromHttpParamsWithOnlyStartDateCreatesRangeToFuture(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('2023-01-01', null, null);

        $this->assertTrue($filter->hasDateFilter());
        $this->assertEquals('2023-01-01 00:00:00', $filter->dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2099-12-31 23:59:59', $filter->dateRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testFromHttpParamsWithOnlyEndDateCreatesRangeFromPast(): void
    {
        $filter = AnalyticsFilter::fromHttpParams(null, '2023-01-31', null);

        $this->assertTrue($filter->hasDateFilter());
        $this->assertEquals('2020-01-01 00:00:00', $filter->dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-31 00:00:00', $filter->dateRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testFromHttpParamsWithNoDatesCreatesNoDateFilter(): void
    {
        $filter = AnalyticsFilter::fromHttpParams(null, null, null);

        $this->assertFalse($filter->hasDateFilter());
        $this->assertNull($filter->dateRange);
    }

    public function testFromHttpParamsWithEmptyStringsCreatesNoDateFilter(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('', '', null);

        $this->assertFalse($filter->hasDateFilter());
        $this->assertNull($filter->dateRange);
    }

    public function testFromHttpParamsWithInvalidDateFormatIgnoresDateFilter(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('invalid-date', '2023-01-31', null);

        $this->assertFalse($filter->hasDateFilter());
        $this->assertNull($filter->dateRange);
    }

    public function testFromHttpParamsWithInvalidSingleDateIgnoresDateFilter(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('invalid-date', null, null);

        $this->assertFalse($filter->hasDateFilter());
        $this->assertNull($filter->dateRange);
    }

    public function testFromHttpParamsWithFutureSingleDateCreatesCorrectRange(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('2025-12-31', null, null);

        $this->assertTrue($filter->hasDateFilter());
        $this->assertEquals('2025-12-31 00:00:00', $filter->dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2099-12-31 23:59:59', $filter->dateRange->endDate->format('Y-m-d H:i:s'));
    }
}