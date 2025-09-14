<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Application\Analytics\DTO;

use Yomali\Tracker\Application\Analytics\DTO\PageAnalyticsDTO;
use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;
use Yomali\Tracker\Domain\Analytics\ValueObject\Url;
use Yomali\Tracker\Domain\Analytics\ValueObject\VisitCount;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class PageAnalyticsDTOTest extends UnitTestCase
{
    public function testConstructorWithValidValues(): void
    {
        $dto = new PageAnalyticsDTO(
            'https://example.com/page',
            'example.com',
            '/page',
            10,
            25,
            '2023-01-01 10:00:00',
            '2023-01-31 15:30:00'
        );

        $this->assertEquals('https://example.com/page', $dto->url);
        $this->assertEquals('example.com', $dto->domain);
        $this->assertEquals('/page', $dto->path);
        $this->assertEquals(10, $dto->uniqueVisits);
        $this->assertEquals(25, $dto->totalVisits);
        $this->assertEquals('2023-01-01 10:00:00', $dto->firstVisit);
        $this->assertEquals('2023-01-31 15:30:00', $dto->lastVisit);
    }

    public function testFromAggregateCreatesCorrectDTO(): void
    {
        $url = Url::fromString('https://blog.example.com/post');
        $visitCount = new VisitCount(8, 20);
        $firstVisit = new \DateTimeImmutable('2023-02-01 09:15:30');
        $lastVisit = new \DateTimeImmutable('2023-02-28 18:45:20');

        $aggregate = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);
        $dto = PageAnalyticsDTO::fromAggregate($aggregate);

        $this->assertEquals('https://blog.example.com/post', $dto->url);
        $this->assertEquals('blog.example.com', $dto->domain);
        $this->assertEquals('/post', $dto->path);
        $this->assertEquals(8, $dto->uniqueVisits);
        $this->assertEquals(20, $dto->totalVisits);
        $this->assertEquals('2023-02-01 09:15:30', $dto->firstVisit);
        $this->assertEquals('2023-02-28 18:45:20', $dto->lastVisit);
    }

    public function testFromAggregateWithRootPath(): void
    {
        $url = Url::fromString('https://example.com');
        $visitCount = new VisitCount(5, 5);
        $date = new \DateTimeImmutable('2023-03-15 12:00:00');

        $aggregate = new PageAnalytics($url, $visitCount, $date, $date);
        $dto = PageAnalyticsDTO::fromAggregate($aggregate);

        $this->assertEquals('https://example.com', $dto->url);
        $this->assertEquals('example.com', $dto->domain);
        $this->assertEquals('/', $dto->path);
        $this->assertEquals(5, $dto->uniqueVisits);
        $this->assertEquals(5, $dto->totalVisits);
        $this->assertEquals('2023-03-15 12:00:00', $dto->firstVisit);
        $this->assertEquals('2023-03-15 12:00:00', $dto->lastVisit);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $dto = new PageAnalyticsDTO(
            'https://shop.example.com/products',
            'shop.example.com',
            '/products',
            15,
            40,
            '2023-04-01 08:30:00',
            '2023-04-30 20:15:00'
        );

        $array = $dto->toArray();

        $expectedArray = [
            'url' => 'https://shop.example.com/products',
            'domain' => 'shop.example.com',
            'path' => '/products',
            'unique_visits' => 15,
            'total_visits' => 40,
            'first_visit' => '2023-04-01 08:30:00',
            'last_visit' => '2023-04-30 20:15:00',
        ];

        $this->assertEquals($expectedArray, $array);
    }

    public function testToArrayWithZeroVisits(): void
    {
        $dto = new PageAnalyticsDTO(
            'https://example.com/empty',
            'example.com',
            '/empty',
            0,
            0,
            '2023-05-01 00:00:00',
            '2023-05-01 00:00:00'
        );

        $array = $dto->toArray();

        $this->assertEquals(0, $array['unique_visits']);
        $this->assertEquals(0, $array['total_visits']);
    }

    public function testFromAggregateWithComplexUrl(): void
    {
        $url = Url::fromString('https://api.example.com/v1/users/profile?id=123');
        $visitCount = new VisitCount(12, 35);
        $firstVisit = new \DateTimeImmutable('2023-06-01 14:22:33');
        $lastVisit = new \DateTimeImmutable('2023-06-15 16:44:55');

        $aggregate = new PageAnalytics($url, $visitCount, $firstVisit, $lastVisit);
        $dto = PageAnalyticsDTO::fromAggregate($aggregate);

        $this->assertEquals('https://api.example.com/v1/users/profile?id=123', $dto->url);
        $this->assertEquals('api.example.com', $dto->domain);
        $this->assertEquals('/v1/users/profile', $dto->path);
    }
}