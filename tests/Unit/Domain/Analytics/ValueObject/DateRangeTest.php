<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Analytics\ValueObject;

use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class DateRangeTest extends UnitTestCase
{
    public function testConstructorWithValidDates(): void
    {
        $start = new \DateTimeImmutable('2023-01-01 00:00:00');
        $end = new \DateTimeImmutable('2023-01-31 23:59:59');

        $dateRange = new DateRange($start, $end);

        $this->assertEquals($start, $dateRange->startDate);
        $this->assertEquals($end, $dateRange->endDate);
    }

    public function testConstructorWithEqualDates(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $dateRange = new DateRange($date, $date);

        $this->assertEquals($date, $dateRange->startDate);
        $this->assertEquals($date, $dateRange->endDate);
    }

    public function testConstructorThrowsExceptionWhenStartDateIsAfterEndDate(): void
    {
        $start = new \DateTimeImmutable('2023-01-31');
        $end = new \DateTimeImmutable('2023-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date must be before or equal to end date');

        new DateRange($start, $end);
    }

    public function testFromStringsWithValidDates(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');

        $this->assertEquals('2023-01-01', $dateRange->startDate->format('Y-m-d'));
        $this->assertEquals('2023-01-31', $dateRange->endDate->format('Y-m-d'));
    }

    public function testFromStringsWithDateTimeStrings(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01 10:30:00', '2023-01-31 15:45:00');

        $this->assertEquals('2023-01-01 10:30:00', $dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-31 15:45:00', $dateRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testFromStringsThrowsExceptionWithInvalidStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');

        DateRange::fromStrings('invalid-date', '2023-01-31');
    }

    public function testFromStringsThrowsExceptionWithInvalidEndDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');

        DateRange::fromStrings('2023-01-01', 'invalid-date');
    }

    public function testFromStringsValidatesDateOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date must be before or equal to end date');

        DateRange::fromStrings('2023-01-31', '2023-01-01');
    }

    public function testLastDaysWithValidDays(): void
    {
        $dateRange = DateRange::lastDays(7);

        $this->assertInstanceOf(DateRange::class, $dateRange);
        $this->assertEquals(7, $dateRange->getDurationInDays());
    }

    public function testLastDaysWithSingleDay(): void
    {
        $dateRange = DateRange::lastDays(1);

        $this->assertEquals(1, $dateRange->getDurationInDays());
    }

    public function testLastDaysThrowsExceptionWithZeroDays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Days must be at least 1');

        DateRange::lastDays(0);
    }

    public function testLastDaysThrowsExceptionWithNegativeDays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Days must be at least 1');

        DateRange::lastDays(-5);
    }

    public function testCurrentMonthReturnsCurrentMonth(): void
    {
        $dateRange = DateRange::currentMonth();

        $now = new \DateTimeImmutable();
        $expectedStart = new \DateTimeImmutable('first day of this month 00:00:00');
        $expectedEnd = new \DateTimeImmutable('last day of this month 23:59:59');

        $this->assertEquals($expectedStart->format('Y-m-d H:i:s'), $dateRange->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedEnd->format('Y-m-d H:i:s'), $dateRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testContainsReturnsTrueForDateWithinRange(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $dateInRange = new \DateTimeImmutable('2023-01-15');

        $this->assertTrue($dateRange->contains($dateInRange));
    }

    public function testContainsReturnsTrueForStartDate(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $startDate = new \DateTimeImmutable('2023-01-01');

        $this->assertTrue($dateRange->contains($startDate));
    }

    public function testContainsReturnsTrueForEndDate(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $endDate = new \DateTimeImmutable('2023-01-31');

        $this->assertTrue($dateRange->contains($endDate));
    }

    public function testContainsReturnsFalseForDateBeforeRange(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $dateBefore = new \DateTimeImmutable('2022-12-31');

        $this->assertFalse($dateRange->contains($dateBefore));
    }

    public function testContainsReturnsFalseForDateAfterRange(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');
        $dateAfter = new \DateTimeImmutable('2023-02-01');

        $this->assertFalse($dateRange->contains($dateAfter));
    }

    public function testGetDurationInDaysWithSingleDay(): void
    {
        $date = new \DateTimeImmutable('2023-01-01');
        $dateRange = new DateRange($date, $date);

        $this->assertEquals(1, $dateRange->getDurationInDays());
    }

    public function testGetDurationInDaysWithMultipleDays(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-31');

        $this->assertEquals(31, $dateRange->getDurationInDays());
    }

    public function testGetDurationInDaysWithOneWeek(): void
    {
        $dateRange = DateRange::fromStrings('2023-01-01', '2023-01-07');

        $this->assertEquals(7, $dateRange->getDurationInDays());
    }
}