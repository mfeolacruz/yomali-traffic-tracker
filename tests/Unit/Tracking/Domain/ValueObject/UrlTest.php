<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Tracking\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Tracking\Domain\ValueObject\Url;

final class UrlTest extends TestCase
{
    public function testValidUrlWithPath(): void
    {
        $url = new Url('https://example.com/page/test');

        $this->assertSame('https://example.com/page/test', $url->fullUrl);
        $this->assertSame('example.com', $url->domain);
        $this->assertSame('/page/test', $url->path);
    }

    public function testValidUrlWithQueryString(): void
    {
        $url = new Url('https://example.com/search?q=test&page=2');

        $this->assertSame('https://example.com/search?q=test&page=2', $url->fullUrl);
        $this->assertSame('example.com', $url->domain);
        $this->assertSame('/search?q=test&page=2', $url->path);
    }

    public function testValidUrlWithoutPath(): void
    {
        $url = new Url('https://example.com');

        $this->assertSame('https://example.com', $url->fullUrl);
        $this->assertSame('example.com', $url->domain);
        $this->assertSame('/', $url->path);
    }

    public function testValidUrlWithPort(): void
    {
        $url = new Url('https://example.com:8080/test');

        $this->assertSame('https://example.com:8080/test', $url->fullUrl);
        $this->assertSame('example.com', $url->domain);
        $this->assertSame('/test', $url->path);
    }

    public function testInvalidUrlThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL: not a url');

        new Url('not a url');
    }

    public function testUrlTooLongThrowsException(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2040);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL too long (max 2048 characters)');

        new Url($longUrl);
    }

    public function testUrlAtMaxLength(): void
    {
        $maxUrl = 'https://example.com/' . str_repeat('a', 2028); // Total = 2048

        $url = new Url($maxUrl);
        $this->assertSame(2048, strlen($url->fullUrl));
    }

    public function testStringableInterface(): void
    {
        $url = new Url('https://example.com');

        $this->assertSame('https://example.com', (string) $url);
        $this->assertInstanceOf(\Stringable::class, $url);
    }
}