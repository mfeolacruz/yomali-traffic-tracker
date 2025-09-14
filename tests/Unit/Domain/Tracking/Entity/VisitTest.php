<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Tracking\Entity;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Domain\Tracking\Entity\Visit;
use Yomali\Tracker\Domain\Tracking\ValueObject\{Url};
use Yomali\Tracker\Domain\Tracking\ValueObject\IpAddress;

final class VisitTest extends TestCase
{
    public function testCreateVisit(): void
    {
        $visit = Visit::create('192.168.1.1', 'https://example.com/page');

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertSame('192.168.1.1', (string)$visit->ipAddress);
        $this->assertSame('https://example.com/page', (string)$visit->url);
        $this->assertInstanceOf(\DateTimeImmutable::class, $visit->createdAt);
        $this->assertNull($visit->getId());
    }

    public function testCreateVisitWithInvalidIp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address');

        Visit::create('invalid.ip', 'https://example.com');
    }

    public function testCreateVisitWithInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL');

        Visit::create('192.168.1.1', 'not a url');
    }

    public function testFromPrimitives(): void
    {
        $visit = Visit::fromPrimitives(
            id: 123,
            ipAddress: '10.0.0.1',
            pageUrl: 'https://example.com/test',
            createdAt: '2024-01-15 10:30:00'
        );

        $this->assertSame(123, $visit->getId());
        $this->assertSame('10.0.0.1', (string)$visit->ipAddress);
        $this->assertSame('https://example.com/test', (string)$visit->url);
        $this->assertSame('2024-01-15 10:30:00', $visit->createdAt->format('Y-m-d H:i:s'));
    }

    public function testVisitWithValueObjects(): void
    {
        $ip = new IpAddress('192.168.1.100');
        $url = new Url('https://test.com/page');
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');

        $visit = new Visit(
            ipAddress: $ip,
            url: $url,
            createdAt: $date
        );

        $this->assertSame($ip, $visit->ipAddress);
        $this->assertSame($url, $visit->url);
        $this->assertSame($date, $visit->createdAt);
    }

    public function testDefaultCreatedAt(): void
    {
        $before = new \DateTimeImmutable();

        $visit = Visit::create('192.168.1.1', 'https://example.com');

        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $visit->createdAt);
        $this->assertLessThanOrEqual($after, $visit->createdAt);
    }

    public function testUrlComponentsAccess(): void
    {
        $visit = Visit::create('192.168.1.1', 'https://example.com/path?query=1');

        $this->assertSame('example.com', $visit->url->domain);
        $this->assertSame('/path?query=1', $visit->url->path);
    }
}