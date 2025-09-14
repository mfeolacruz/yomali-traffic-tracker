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

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals($url, $pageAnalytics->url);
        $this->assertEquals($visitCount, $pageAnalytics->visitCount);
    }

    public function testConstructorWithSingleVisit(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(1, 1);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals($url, $pageAnalytics->url);
        $this->assertEquals($visitCount, $pageAnalytics->visitCount);
    }

    public function testConstructorWorksWithZeroVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = VisitCount::zero();

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals($url, $pageAnalytics->url);
        $this->assertEquals($visitCount, $pageAnalytics->visitCount);
    }

    public function testCreateFactoryMethod(): void
    {
        $url = Url::fromString('https://example.com/test');

        $pageAnalytics = PageAnalytics::create($url, 8, 15);

        $this->assertEquals($url, $pageAnalytics->url);
        $this->assertEquals(8, $pageAnalytics->visitCount->uniqueVisits);
        $this->assertEquals(15, $pageAnalytics->visitCount->totalVisits);
    }

    public function testFromRawDataFactoryMethod(): void
    {
        $pageAnalytics = PageAnalytics::fromRawData(
            'https://example.com/blog/post',
            'example.com',
            '/blog/post',
            12,
            25
        );

        $this->assertEquals('https://example.com/blog/post', $pageAnalytics->url->getValue());
        $this->assertEquals('example.com', $pageAnalytics->url->getDomain());
        $this->assertEquals('/blog/post', $pageAnalytics->url->getPath());
        $this->assertEquals(12, $pageAnalytics->visitCount->uniqueVisits);
        $this->assertEquals(25, $pageAnalytics->visitCount->totalVisits);
    }

    public function testGetDomainReturnsUrlDomain(): void
    {
        $url = Url::fromString('https://blog.example.com/post');
        $visitCount = new VisitCount(3, 7);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals('blog.example.com', $pageAnalytics->getDomain());
    }

    public function testGetPathReturnsUrlPath(): void
    {
        $url = Url::fromString('https://example.com/blog/article');
        $visitCount = new VisitCount(4, 9);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals('/blog/article', $pageAnalytics->getPath());
    }

    public function testGetUniqueVisitsReturnsVisitCountUniqueVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(6, 14);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals(6, $pageAnalytics->getUniqueVisits());
    }

    public function testGetTotalVisitsReturnsVisitCountTotalVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(7, 16);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals(16, $pageAnalytics->getTotalVisits());
    }

    public function testHasTrafficReturnsTrueWhenVisitCountHasVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(2, 5);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertTrue($pageAnalytics->hasTraffic());
    }

    public function testHasTrafficReturnsFalseWhenVisitCountHasNoVisits(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = VisitCount::zero();

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertFalse($pageAnalytics->hasTraffic());
    }

    public function testGetUniqueRatioReturnsVisitCountUniqueRatio(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(3, 6);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals(0.5, $pageAnalytics->getUniqueRatio());
    }

    public function testGetVisitCountValues(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(1, 1);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals(1, $pageAnalytics->getUniqueVisits());
        $this->assertEquals(1, $pageAnalytics->getTotalVisits());
    }

    public function testGetVisitCountWithDifferentValues(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals(5, $pageAnalytics->getUniqueVisits());
        $this->assertEquals(10, $pageAnalytics->getTotalVisits());
    }

    public function testGetVisitCountWithLargeValues(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(8, 20);

        $pageAnalytics = new PageAnalytics($url, $visitCount);

        $this->assertEquals(8, $pageAnalytics->getUniqueVisits());
        $this->assertEquals(20, $pageAnalytics->getTotalVisits());
    }

    public function testIsSamePageReturnsTrueForSameUrl(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(4, 8);

        $pageAnalytics = new PageAnalytics($url, $visitCount);
        $sameUrl = Url::fromString('https://example.com/page');

        $this->assertTrue($pageAnalytics->isSamePage($sameUrl));
    }

    public function testIsSamePageReturnsFalseForDifferentUrl(): void
    {
        $url = Url::fromString('https://example.com/page1');
        $visitCount = new VisitCount(4, 8);

        $pageAnalytics = new PageAnalytics($url, $visitCount);
        $differentUrl = Url::fromString('https://example.com/page2');

        $this->assertFalse($pageAnalytics->isSamePage($differentUrl));
    }

    public function testMergeWithSamePageCombinesVisitCounts(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6)
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4)
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertEquals(5, $merged->getUniqueVisits());
        $this->assertEquals(10, $merged->getTotalVisits());
    }

    public function testMergeWithSamePageCombinesCorrectly(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6)
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4)
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertEquals(5, $merged->getUniqueVisits());
        $this->assertEquals(10, $merged->getTotalVisits());
    }

    public function testMergeCreatesNewInstance(): void
    {
        $url = Url::fromString('https://example.com/page');
        
        $pageAnalytics1 = new PageAnalytics(
            $url,
            new VisitCount(3, 6)
        );

        $pageAnalytics2 = new PageAnalytics(
            $url,
            new VisitCount(2, 4)
        );

        $merged = $pageAnalytics1->merge($pageAnalytics2);

        $this->assertNotSame($pageAnalytics1, $merged);
        $this->assertNotSame($pageAnalytics2, $merged);
    }

    public function testMergeThrowsExceptionForDifferentPages(): void
    {
        $pageAnalytics1 = new PageAnalytics(
            Url::fromString('https://example.com/page1'),
            new VisitCount(3, 6)
        );

        $pageAnalytics2 = new PageAnalytics(
            Url::fromString('https://example.com/page2'),
            new VisitCount(2, 4)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot merge analytics for different pages');

        $pageAnalytics1->merge($pageAnalytics2);
    }

    public function testEqualsReturnsTrueForSameValues(): void
    {
        $url = Url::fromString('https://example.com/page');
        $visitCount = new VisitCount(5, 10);

        $pageAnalytics1 = new PageAnalytics($url, $visitCount);
        $pageAnalytics2 = new PageAnalytics($url, $visitCount);

        $this->assertTrue($pageAnalytics1->equals($pageAnalytics2));
        $this->assertTrue($pageAnalytics2->equals($pageAnalytics1));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $pageAnalytics = new PageAnalytics(
            Url::fromString('https://example.com/page'),
            new VisitCount(5, 10)
        );

        $this->assertTrue($pageAnalytics->equals($pageAnalytics));
    }

    public function testEqualsReturnsFalseForDifferentUrl(): void
    {
        $visitCount = new VisitCount(5, 10);

        $pageAnalytics1 = new PageAnalytics(
            Url::fromString('https://example.com/page1'), 
            $visitCount
        );
        $pageAnalytics2 = new PageAnalytics(
            Url::fromString('https://example.com/page2'), 
            $visitCount
        );

        $this->assertFalse($pageAnalytics1->equals($pageAnalytics2));
    }

    public function testEqualsReturnsFalseForDifferentVisitCount(): void
    {
        $url = Url::fromString('https://example.com/page');

        $pageAnalytics1 = new PageAnalytics($url, new VisitCount(5, 10));
        $pageAnalytics2 = new PageAnalytics($url, new VisitCount(6, 12));

        $this->assertFalse($pageAnalytics1->equals($pageAnalytics2));
    }
}