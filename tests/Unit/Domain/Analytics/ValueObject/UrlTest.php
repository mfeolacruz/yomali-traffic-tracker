<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Analytics\ValueObject;

use Yomali\Tracker\Domain\Analytics\ValueObject\Url;
use Yomali\Tracker\Tests\Unit\UnitTestCase;

/**
 * @group unit
 */
final class UrlTest extends UnitTestCase
{
    public function testConstructorWithValidValues(): void
    {
        $url = new Url('https://example.com/path', 'example.com', '/path');

        $this->assertEquals('https://example.com/path', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/path', $url->getPath());
    }

    public function testConstructorThrowsExceptionWithEmptyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        new Url('', 'example.com', '/path');
    }

    public function testConstructorThrowsExceptionWithWhitespaceOnlyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        new Url('   ', 'example.com', '/path');
    }

    public function testConstructorThrowsExceptionWithEmptyDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain cannot be empty');

        new Url('https://example.com/path', '', '/path');
    }

    public function testConstructorThrowsExceptionWithWhitespaceOnlyDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain cannot be empty');

        new Url('https://example.com/path', '   ', '/path');
    }

    public function testFromStringWithValidHttpsUrl(): void
    {
        $url = Url::fromString('https://example.com/test/path');

        $this->assertEquals('https://example.com/test/path', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/test/path', $url->getPath());
    }

    public function testFromStringWithValidHttpUrl(): void
    {
        $url = Url::fromString('http://example.com/test');

        $this->assertEquals('http://example.com/test', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/test', $url->getPath());
    }

    public function testFromStringWithRootPath(): void
    {
        $url = Url::fromString('https://example.com');

        $this->assertEquals('https://example.com', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/', $url->getPath());
    }

    public function testFromStringWithUrlContainingQuery(): void
    {
        $url = Url::fromString('https://example.com/path?param=value');

        $this->assertEquals('https://example.com/path?param=value', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/path', $url->getPath());
    }

    public function testFromStringWithUrlContainingFragment(): void
    {
        $url = Url::fromString('https://example.com/path#section');

        $this->assertEquals('https://example.com/path#section', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/path', $url->getPath());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $url = Url::fromString('  https://example.com/path  ');

        $this->assertEquals('https://example.com/path', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/path', $url->getPath());
    }

    public function testFromStringThrowsExceptionWithEmptyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        Url::fromString('');
    }

    public function testFromStringThrowsExceptionWithWhitespaceOnlyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        Url::fromString('   ');
    }

    public function testFromStringThrowsExceptionWithInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');

        Url::fromString('not-a-valid-url');
    }

    public function testFromStringThrowsExceptionWithUrlWithoutHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');

        Url::fromString('/just/a/path');
    }

    public function testCreateFactoryMethod(): void
    {
        $url = Url::create('https://example.com/test', 'example.com', '/test');

        $this->assertEquals('https://example.com/test', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/test', $url->getPath());
    }

    public function testEqualsReturnsTrueForSameUrl(): void
    {
        $url1 = Url::fromString('https://example.com/path');
        $url2 = Url::fromString('https://example.com/path');

        $this->assertTrue($url1->equals($url2));
        $this->assertTrue($url2->equals($url1));
    }

    public function testEqualsReturnsFalseForDifferentUrls(): void
    {
        $url1 = Url::fromString('https://example.com/path1');
        $url2 = Url::fromString('https://example.com/path2');

        $this->assertFalse($url1->equals($url2));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $url = Url::fromString('https://example.com/path');

        $this->assertTrue($url->equals($url));
    }

    public function testIsSameDomainReturnsTrueForSameDomain(): void
    {
        $url1 = Url::fromString('https://example.com/path1');
        $url2 = Url::fromString('https://example.com/path2');

        $this->assertTrue($url1->isSameDomain($url2));
        $this->assertTrue($url2->isSameDomain($url1));
    }

    public function testIsSameDomainReturnsFalseForDifferentDomains(): void
    {
        $url1 = Url::fromString('https://example.com/path');
        $url2 = Url::fromString('https://other.com/path');

        $this->assertFalse($url1->isSameDomain($url2));
    }

    public function testIsSamePathReturnsTrueForSamePath(): void
    {
        $url1 = Url::fromString('https://example1.com/same/path');
        $url2 = Url::fromString('https://example2.com/same/path');

        $this->assertTrue($url1->isSamePath($url2));
        $this->assertTrue($url2->isSamePath($url1));
    }

    public function testIsSamePathReturnsFalseForDifferentPaths(): void
    {
        $url1 = Url::fromString('https://example.com/path1');
        $url2 = Url::fromString('https://example.com/path2');

        $this->assertFalse($url1->isSamePath($url2));
    }

    public function testToStringReturnsUrlValue(): void
    {
        $url = Url::fromString('https://example.com/path');

        $this->assertEquals('https://example.com/path', (string) $url);
    }

    public function testFromStringWithSubdomain(): void
    {
        $url = Url::fromString('https://blog.example.com/post');

        $this->assertEquals('https://blog.example.com/post', $url->getValue());
        $this->assertEquals('blog.example.com', $url->getDomain());
        $this->assertEquals('/post', $url->getPath());
    }

    public function testFromStringWithPort(): void
    {
        $url = Url::fromString('https://example.com:8080/path');

        $this->assertEquals('https://example.com:8080/path', $url->getValue());
        $this->assertEquals('example.com', $url->getDomain());
        $this->assertEquals('/path', $url->getPath());
    }
}