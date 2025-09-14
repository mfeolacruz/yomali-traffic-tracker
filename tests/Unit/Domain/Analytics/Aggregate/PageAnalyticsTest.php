<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Analytics\Aggregate;

use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;
use Yomali\Tracker\Domain\Analytics\ValueObject\Url;
use Yomali\Tracker\Domain\Analytics\ValueObject\VisitCount;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class PageAnalyticsTest extends UnitTestCase
{
    public function testConstructorWithValidValues(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);
        $firstVisit = new \DateTimeImmutable('2023-01-01 10:00:00');
        $lastVisit = new \DateTimeImmutable('2023-01-31 15:30:00');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals($url, $pageAnalytics->url);
        $this->assertEquals($visitCount, $pageAnalytics->visitCount);
        $this->assertEquals($firstVisit, $pageAnalytics->firstVisit);
        $this->assertEquals($lastVisit, $pageAnalytics->lastVisit);
    }

    public function testConstructorWithEqualDates(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(1, 1);
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $date, $date);

        $this->assertEquals($date, $pageAnalytics->firstVisit);
        $this->assertEquals($date, $pageAnalytics->lastVisit);
    }

    public function testConstructorThrowsExceptionWhenFirstVisitIsAfterLastVisit(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);
        $firstVisit = new \DateTimeImmutable('2023-01-31');
        $lastVisit = new \DateTimeImmutable('2023-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('First visit cannot be after last visit');

        new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);
    }

    public function testCreateFactoryMethod(): void
    {
        $url = Url::fromString('https://example.com/test');
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = PageAnalytics::create($url, 8, 15, $firstVisit, $lastVisit);

        $this->assertEquals($url, $pageAnalytics->url);
        $this->assertEquals(8, $pageAnalytics->visitCount->uniqueVisits);
        $this->assertEquals(15, $pageAnalytics->visitCount->totalVisits);
        $this->assertEquals($firstVisit, $pageAnalytics->firstVisit);
        $this->assertEquals($lastVisit, $pageAnalytics->lastVisit);
    }

    public function testFromRawDataFactoryMethod(): void
    {
        $firstVisit = new \DateTimeImmutable('2023-01-01 09:30:00');
        $lastVisit = new \DateTimeImmutable('2023-01-31 18:45:00');

        $pageAnalytics = PageAnalytics::fromRawData(
            'https://example.com/blog/post',
            'example.com',
            '/blog/post',
            12,
            25,
            $firstVisit,
            $lastVisit
        );

        $this->assertEquals('https://example.com/blog/post', $pageAnalytics->url->getValue());
        $this->assertEquals('example.com', $pageAnalytics->url->getDomain());
        $this->assertEquals('/blog/post', $pageAnalytics->url->getPath());
        $this->assertEquals(12, $pageAnalytics->visitCount->uniqueVisits);
        $this->assertEquals(25, $pageAnalytics->visitCount->totalVisits);
        $this->assertEquals($firstVisit, $pageAnalytics->firstVisit);
        $this->assertEquals($lastVisit, $pageAnalytics->lastVisit);
    }

    public function testGetDomainReturnsUrlDomain(): void
    {
        $url = Url::fromString('https://blog.example.com/post');
        $visitCount = new VisitCount(3, 7);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals('blog.example.com', $pageAnalytics->getDomain());
    }

    public function testGetPathReturnsUrlPath(): void
    {
        $url = Url::fromString('https://example.com/blog/article');
        $visitCount = new VisitCount(4, 9);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals('/blog/article', $pageAnalytics->getPath());
    }

    public function testGetUniqueVisitsReturnsVisitCountUniqueVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(6, 14);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals(6, $pageAnalytics->getUniqueVisits());
    }

    public function testGetTotalVisitsReturnsVisitCountTotalVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(7, 16);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals(16, $pageAnalytics->getTotalVisits());
    }

    public function testHasTrafficReturnsTrueWhenVisitCountHasVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(2, 5);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertTrue($pageAnalytics->hasTraffic());
    }

    public function testHasTrafficReturnsFalseWhenVisitCountHasNoVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = VisitCount::zero();
        $date = new \DateTimeImmutable('2023-01-01');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $date, $date);

        $this->assertFalse($pageAnalytics->hasTraffic());
    }

    public function testGetUniqueRatioReturnsVisitCountUniqueRatio(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(3, 6);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals(0.5, $pageAnalytics->getUniqueRatio());
    }

    public function testGetVisitDurationInDaysWithSameDate(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(1, 1);
        $date = new \DateTimeImmutable('2023-01-15');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $date, $date);

        $this->assertEquals(1, $pageAnalytics->getVisitDurationInDays());
    }

    public function testGetVisitDurationInDaysWithDifferentDates(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-07');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals(7, $pageAnalytics->getVisitDurationInDays());
    }

    public function testGetVisitDurationInDaysWithOneMonth(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(8, 20);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertEquals(31, $pageAnalytics->getVisitDurationInDays());
    }

    public function testIsSamePageReturnsTrueForSameUrl(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(4, 8);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);
        $sameUrl = Url::fromString('https://example.com/page');

        $this->assertTrue($pageAnalytics->isSamePage($sameUrl));
    }

    public function testIsSamePageReturnsFalseForDifferentUrl(): void
    {
        $url = Url::fromString('https://example.com/page1');
        $visitCount = new VisitCount(4, 8);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);
        $differentUrl = Url::fromString('https://example.com/page2');

        $this->assertFalse($pageAnalytics->isSamePage($differentUrl));
    }

    public function testMergeWithSamePageCombinesVisitCounts(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-15')
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4),
            new \DateTimeImmutable('2023-01-10'),
            new \DateTimeImmutable('2023-01-31')
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertEquals(5, $merged->getUniqueVisits());
        $this->assertEquals(10, $merged->getTotalVisits());
    }

    public function testMergeWithSamePageUsesEarliestFirstVisit(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6),
            new \DateTimeImmutable('2023-01-05'),
            new \DateTimeImmutable('2023-01-15')
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-31')
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertEquals('2023-01-01', $merged->firstVisit->format('Y-m-d'));
    }

    public function testMergeWithSamePageUsesLatestLastVisit(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-15')
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4),
            new \DateTimeImmutable('2023-01-10'),
            new \DateTimeImmutable('2023-01-31')
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertEquals('2023-01-31', $merged->lastVisit->format('Y-m-d'));
    }

    public function testMergeCreatesNewInstance(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-15')
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4),
            new \DateTimeImmutable('2023-01-10'),
            new \DateTimeImmutable('2023-01-31')
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertNotSame($pageAnalytics1, $merged);
        $this->assertNotSame($pageAnalytics2, $merged);
    }

    public function testMergeThrowsExceptionForDifferentPages(): void
    {
        $pageAnalytics1 = new PageAnalytics(
            Url::fromString('https://example.com/page1'),
            new VisitCount(3, 6),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-15')
        );

        $pageAnalytics2 = new PageAnalytics(
            Url::fromString('https://example.com/page2'),
            new VisitCount(2, 4),
            new \DateTimeImmutable('2023-01-10'),
            new \DateTimeImmutable('2023-01-31')
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot merge analytics for different pages');

        $pageAnalytics1->merge($pageAnalytics2);
    }

    public function testEqualsReturnsTrueForSameValues(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics1 = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);
        $pageAnalytics2 = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);

        $this->assertTrue($pageAnalytics1->equals($pageAnalytics2));
        $this->assertTrue($pageAnalytics2->equals($pageAnalytics1));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $pageAnalytics = new PageAnalytics(
            Url::fromString('https://example.com/page'),
            new VisitCount(5, 10),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-31')
        );

        $this->assertTrue($pageAnalytics->equals($pageAnalytics));
    }

    public function testEqualsReturnsFalseForDifferentUrl(): void
    {
        $visitCount = new VisitCount(5, 10);
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics1 = new PageAnalytics(
            Url::fromString('https://example.com/page1'), 
            $visitCount, 
            $firstVisit, 
            $lastVisit
        );
        $pageAnalytics2 = new PageAnalytics(
            Url::fromString('https://example.com/page2'), 
            $visitCount, 
            $firstVisit, 
            $lastVisit
        );

        $this->assertFalse($pageAnalytics1->equals($pageAnalytics2));
    }

    public function testEqualsReturnsFalseForDifferentVisitCount(): void
    {
        $url = Url::fromString('https://example.com/page');
        $firstVisit = new \DateTimeImmutable('2023-01-01');
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics1 = new PageAnalytics($url, new VisitCount(5, 10), $firstVisit, $lastVisit);
        $pageAnalytics2 = new PageAnalytics($url, new VisitCount(6, 12), $firstVisit, $lastVisit);

        $this->assertFalse($pageAnalytics1->equals($pageAnalytics2));
    }

    public function testEqualsReturnsFalseForDifferentFirstVisit(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);
        $lastVisit = new \DateTimeImmutable('2023-01-31');

        $pageAnalytics1 = new PageAnalytics($url, $visitCount, new \DateTimeImmutable('2023-01-01'), $lastVisit);
        $pageAnalytics2 = new PageAnalytics($url, $visitCount, new \DateTimeImmutable('2023-01-02'), $lastVisit);

        $this->assertFalse($pageAnalytics1->equals($pageAnalytics2));
    }

    public function testEqualsReturnsFalseForDifferentLastVisit(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);
        $firstVisit = new \DateTimeImmutable('2023-01-01');

        $pageAnalytics1 = new PageAnalytics($url, $visitCount, $firstVisit, new \DateTimeImmutable('2023-01-30'));
        $pageAnalytics2 = new PageAnalytics($url, $visitCount, $firstVisit, new \DateTimeImmutable('2023-01-31'));

        $this->assertFalse($pageAnalytics1->equals($pageAnalytics2));
    }
}