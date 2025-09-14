<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Analytics\ValueObject;

use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class AnalyticsFilterTest extends UnitTestCase
{
    public function testConstructorWithNoFilters(): void
    {
        $filter = new AnalyticsFilter();

        $this->assertNull($filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasAnyFilter());
    }

    public function testConstructorWithDateRangeOnly(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = new AnalyticsFilter($dateRange);

        $this->assertEquals($dateRange, $filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertTrue($filter->hasDateFilter());
        $this->assertFalse($filter->hasDomainFilter());
        $this->assertTrue($filter->hasAnyFilter());
    }

    public function testConstructorWithDomainOnly(): void
    {
        $filter = new AnalyticsFilter(null, 'example.com');

        $this->assertNull($filter->dateRange);
        $this->assertEquals('example.com', $filter->domain);
        $this->assertFalse($filter->hasDateFilter());
        $this->assertTrue($filter->hasDomainFilter());
        $this->assertTrue($filter->hasAnyFilter());
    }

    public function testConstructorWithBothFilters(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = new AnalyticsFilter($dateRange, 'example.com');

        $this->assertEquals($dateRange, $filter->dateRange);
        $this->assertEquals('example.com', $filter->domain);
        $this->assertTrue($filter->hasDateFilter());
        $this->assertTrue($filter->hasDomainFilter());
        $this->assertTrue($filter->hasAnyFilter());
    }

    public function testConstructorNormalizesEmptyStringDomainToNull(): void
    {
        $filter = new AnalyticsFilter(null, '');

        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasDomainFilter());
    }

    public function testConstructorNormalizesWhitespaceOnlyDomainToNull(): void
    {
        $filter = new AnalyticsFilter(null, '   ');

        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasDomainFilter());
    }

    public function testAllFactoryMethod(): void
    {
        $filter = AnalyticsFilter::all();

        $this->assertNull($filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasAnyFilter());
    }

    public function testByDomainFactoryMethod(): void
    {
        $filter = AnalyticsFilter::byDomain('example.com');

        $this->assertNull($filter->dateRange);
        $this->assertEquals('example.com', $filter->domain);
        $this->assertTrue($filter->hasDomainFilter());
        $this->assertFalse($filter->hasDateFilter());
    }

    public function testByDateRangeFactoryMethod(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = AnalyticsFilter::byDateRange($dateRange);

        $this->assertEquals($dateRange, $filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertTrue($filter->hasDateFilter());
        $this->assertFalse($filter->hasDomainFilter());
    }

    public function testCreateFactoryMethod(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = AnalyticsFilter::create($dateRange, 'example.com');

        $this->assertEquals($dateRange, $filter->dateRange);
        $this->assertEquals('example.com', $filter->domain);
        $this->assertTrue($filter->hasAnyFilter());
    }

    public function testFromHttpParamsWithValidParameters(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('2023-01-01', '2023-01-31', 'example.com');

        $this->assertNotNull($filter->dateRange);
        $this->assertEquals('2023-01-01', $filter->dateRange->startDate->format('Y-m-d'));
        $this->assertEquals('2023-01-31', $filter->dateRange->endDate->format('Y-m-d'));
        $this->assertEquals('example.com', $filter->domain);
    }

    public function testFromHttpParamsWithNullParameters(): void
    {
        $filter = AnalyticsFilter::fromHttpParams(null, null, null);

        $this->assertNull($filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasAnyFilter());
    }

    public function testFromHttpParamsWithEmptyStringParameters(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('', '', '');

        $this->assertNull($filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasAnyFilter());
    }

    public function testFromHttpParamsWithWhitespaceParameters(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('  ', '  ', '  ');

        $this->assertNull($filter->dateRange);
        $this->assertNull($filter->domain);
        $this->assertFalse($filter->hasAnyFilter());
    }

    public function testFromHttpParamsWithInvalidDateFormats(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('invalid-date', '2023-01-31', 'example.com');

        $this->assertNull($filter->dateRange);
        $this->assertEquals('example.com', $filter->domain);
    }

    public function testFromHttpParamsWithMissingEndDate(): void
    {
        $filter = AnalyticsFilter::fromHttpParams('2023-01-01', null, 'example.com');

        $this->assertNotNull($filter->dateRange);
        $this->assertEquals('2023-01-01 00:00:00', $filter->dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2099-12-31 23:59:59', $filter->dateRange->endDate->format('Y-m-d H:i:s'));
        $this->assertEquals('example.com', $filter->domain);
    }

    public function testFromHttpParamsWithMissingStartDate(): void
    {
        $filter = AnalyticsFilter::fromHttpParams(null, '2023-01-31', 'example.com');

        $this->assertNotNull($filter->dateRange);
        $this->assertEquals('2020-01-01 00:00:00', $filter->dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-31 00:00:00', $filter->dateRange->endDate->format('Y-m-d H:i:s'));
        $this->assertEquals('example.com', $filter->domain);
    }

    public function testWithDateRangeCreatesNewInstance(): void
    {
        $originalFilter = AnalyticsFilter::byDomain('example.com');
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        
        $newFilter = $originalFilter->withDateRange($dateRange);

        $this->assertNotSame($originalFilter, $newFilter);
        $this->assertEquals($dateRange, $newFilter->dateRange);
        $this->assertEquals('example.com', $newFilter->domain);
        $this->assertNull($originalFilter->dateRange);
    }

    public function testWithDomainCreatesNewInstance(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $originalFilter = AnalyticsFilter::byDateRange($dateRange);
        
        $newFilter = $originalFilter->withDomain('example.com');

        $this->assertNotSame($originalFilter, $newFilter);
        $this->assertEquals($dateRange, $newFilter->dateRange);
        $this->assertEquals('example.com', $newFilter->domain);
        $this->assertNull($originalFilter->domain);
    }

    public function testWithoutDateRangeCreatesNewInstance(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $originalFilter = new AnalyticsFilter($dateRange, 'example.com');
        
        $newFilter = $originalFilter->withoutDateRange();

        $this->assertNotSame($originalFilter, $newFilter);
        $this->assertNull($newFilter->dateRange);
        $this->assertEquals('example.com', $newFilter->domain);
        $this->assertEquals($dateRange, $originalFilter->dateRange);
    }

    public function testWithoutDomainCreatesNewInstance(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $originalFilter = new AnalyticsFilter($dateRange, 'example.com');
        
        $newFilter = $originalFilter->withoutDomain();

        $this->assertNotSame($originalFilter, $newFilter);
        $this->assertEquals($dateRange, $newFilter->dateRange);
        $this->assertNull($newFilter->domain);
        $this->assertEquals('example.com', $originalFilter->domain);
    }

    public function testHasDateFilterReturnsTrueWhenDateRangeIsSet(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = new AnalyticsFilter($dateRange);

        $this->assertTrue($filter->hasDateFilter());
    }

    public function testHasDateFilterReturnsFalseWhenDateRangeIsNull(): void
    {
        $filter = new AnalyticsFilter(null, 'example.com');

        $this->assertFalse($filter->hasDateFilter());
    }

    public function testHasDomainFilterReturnsTrueWhenDomainIsSet(): void
    {
        $filter = new AnalyticsFilter(null, 'example.com');

        $this->assertTrue($filter->hasDomainFilter());
    }

    public function testHasDomainFilterReturnsFalseWhenDomainIsNull(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = new AnalyticsFilter($dateRange);

        $this->assertFalse($filter->hasDomainFilter());
    }

    public function testHasAnyFilterReturnsTrueWhenOnlyDateRangeIsSet(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $filter = new AnalyticsFilter($dateRange);

        $this->assertTrue($filter->hasAnyFilter());
    }

    public function testHasAnyFilterReturnsTrueWhenOnlyDomainIsSet(): void
    {
        $filter = new AnalyticsFilter(null, 'example.com');

        $this->assertTrue($filter->hasAnyFilter());
    }

    public function testHasAnyFilterReturnsFalseWhenNoFiltersAreSet(): void
    {
        $filter = new AnalyticsFilter();

        $this->assertFalse($filter->hasAnyFilter());
    }
}