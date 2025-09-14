<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Analytics\ValueObject;

use Yomali\Tracker\Domain\Analytics\ValueObject\VisitCount;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class VisitCountTest extends UnitTestCase
{
    public function testConstructorWithValidValues(): void
    {
        $visitCount = new VisitCount(10, 25);

        $this->assertEquals(10, $visitCount->uniqueVisits);
        $this->assertEquals(25, $visitCount->totalVisits);
    }

    public function testConstructorWithZeroValues(): void
    {
        $visitCount = new VisitCount(0, 0);

        $this->assertEquals(0, $visitCount->uniqueVisits);
        $this->assertEquals(0, $visitCount->totalVisits);
    }

    public function testConstructorWithEqualValues(): void
    {
        $visitCount = new VisitCount(15, 15);

        $this->assertEquals(15, $visitCount->uniqueVisits);
        $this->assertEquals(15, $visitCount->totalVisits);
    }

    public function testConstructorThrowsExceptionWhenUniqueVisitsIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unique visits cannot be negative');

        new VisitCount(-1, 10);
    }

    public function testConstructorThrowsExceptionWhenTotalVisitsIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total visits cannot be negative');

        new VisitCount(5, -1);
    }

    public function testConstructorThrowsExceptionWhenUniqueVisitsExceedsTotalVisits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unique visits cannot exceed total visits');

        new VisitCount(15, 10);
    }

    public function testZeroFactoryMethod(): void
    {
        $visitCount = VisitCount::zero();

        $this->assertEquals(0, $visitCount->uniqueVisits);
        $this->assertEquals(0, $visitCount->totalVisits);
    }

    public function testFromTotalsFactoryMethod(): void
    {
        $visitCount = VisitCount::fromTotals(8, 20);

        $this->assertEquals(8, $visitCount->uniqueVisits);
        $this->assertEquals(20, $visitCount->totalVisits);
    }

    public function testFromTotalsValidatesValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unique visits cannot exceed total visits');

        VisitCount::fromTotals(25, 15);
    }

    public function testAddMethodWithValidCounts(): void
    {
        $count1 = new VisitCount(5, 10);
        $count2 = new VisitCount(3, 8);

        $result = $count1->add($count2);

        $this->assertEquals(8, $result->uniqueVisits);
        $this->assertEquals(18, $result->totalVisits);
    }

    public function testAddMethodWithZeroCounts(): void
    {
        $count1 = new VisitCount(5, 10);
        $count2 = VisitCount::zero();

        $result = $count1->add($count2);

        $this->assertEquals(5, $result->uniqueVisits);
        $this->assertEquals(10, $result->totalVisits);
    }

    public function testAddMethodCreatesNewInstance(): void
    {
        $count1 = new VisitCount(5, 10);
        $count2 = new VisitCount(3, 8);

        $result = $count1->add($count2);

        $this->assertNotSame($count1, $result);
        $this->assertNotSame($count2, $result);
    }

    public function testHasVisitsReturnsTrueWhenTotalVisitsIsGreaterThanZero(): void
    {
        $visitCount = new VisitCount(5, 10);

        $this->assertTrue($visitCount->hasVisits());
    }

    public function testHasVisitsReturnsFalseWhenTotalVisitsIsZero(): void
    {
        $visitCount = VisitCount::zero();

        $this->assertFalse($visitCount->hasVisits());
    }

    public function testGetUniqueRatioWithValidVisits(): void
    {
        $visitCount = new VisitCount(5, 10);

        $this->assertEquals(0.5, $visitCount->getUniqueRatio());
    }

    public function testGetUniqueRatioWithPerfectRatio(): void
    {
        $visitCount = new VisitCount(10, 10);

        $this->assertEquals(1.0, $visitCount->getUniqueRatio());
    }

    public function testGetUniqueRatioWithZeroTotalVisits(): void
    {
        $visitCount = VisitCount::zero();

        $this->assertEquals(0.0, $visitCount->getUniqueRatio());
    }

    public function testEqualsReturnsTrueForSameCounts(): void
    {
        $count1 = new VisitCount(5, 10);
        $count2 = new VisitCount(5, 10);

        $this->assertTrue($count1->equals($count2));
        $this->assertTrue($count2->equals($count1));
    }

    public function testEqualsReturnsFalseForDifferentUniqueCounts(): void
    {
        $count1 = new VisitCount(5, 10);
        $count2 = new VisitCount(6, 10);

        $this->assertFalse($count1->equals($count2));
    }

    public function testEqualsReturnsFalseForDifferentTotalCounts(): void
    {
        $count1 = new VisitCount(5, 10);
        $count2 = new VisitCount(5, 12);

        $this->assertFalse($count1->equals($count2));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $visitCount = new VisitCount(5, 10);

        $this->assertTrue($visitCount->equals($visitCount));
    }
}